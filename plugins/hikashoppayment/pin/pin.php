<?php
defined('_JEXEC') or die('Restricted access');

/**
 * Pin payment plugin for hikashop
 *
 * @package HikaShop for Joomla!
 * @version 2.0.0
 * @author butterfly
 */
class plgHikashoppaymentPin extends hikashopPaymentPlugin
{
    /**
     * Contains a list of accepted currencies, se configurations/currencies.php
     *
     * @var array
     */
    public $accepted_currencies; //Must be public to comply with hikashopPaymentPlugin

    /**
     * @var bool
     */
    var $multiple = true;

    /**
     * Used by JPlugin class to determine if language
     *
     * @var bool
     */
    var $autoloadLanguage = true;

    /**
     * Specific configuration options
     *
     * @var array
     */
    var $pluginConfig = array(
        'api_key' => array('PLG_HIKASHOPPAYMENT_PIN_API_KEY', 'input'),
        'secret_key' => array('PLG_HIKASHOPPAYMENT_PIN_SECRET_KEY', 'input'),
        'sandbox' => array('SANDBOX', 'boolean', '0'),
        'return_url' => array('RETURN_URL', 'input'),
        'verified_status' => array('VERIFIED_STATUS', 'orderstatus'),
        'charge_description' => array('PLG_HIKASHOPPAYMENT_PIN_CHARGE_DESCRIPTION', 'input'),
        'tokenizer_fields_sep' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_TITLE', 'separator'),
        'tokenizer_fields_address_line1' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_LINE1', 'boolean', '1'),
        'tokenizer_fields_address_line2' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_LINE2', 'boolean', '1'),
        'tokenizer_fields_address_city' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_CITY', 'boolean', '1'),
        'tokenizer_fields_address_postcode' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_POSTCODE', 'boolean', '1'),
        'tokenizer_fields_address_state' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_STATE', 'boolean', '1'),
        'tokenizer_fields_address_country' => array('PLG_HIKASHOPPAYMENT_PIN_TOKENIZER_FIELD_ADDRESS_COUNTRY', 'boolean', '1'),
    );

    /**
     * @var string
     */
    var $name = 'pin';

    /**
     * @var string
     */
    private $hostedFieldJsUrl = 'https://cdn.pin.net.au/pin.hosted_fields.v1.js';

    /**
     * Use this in the payment form template
     *
     * @var array
     */
    protected $paymentFormParams = array();

    /**
     * @var bool
     */
    protected $jsLoaded = false;

    /**
     * A hikashop variable
     *
     * @var bool
     */
    protected $noForm = false;

    /**
     * Constructor function load accepted currencies
     *
     * @param object $subject
     * @param array $config
     */
    public function __construct(&$subject, $config)
    {

        if (hikashop_isClient('site')) {
            $this->noForm = hikaInput::get()->getInt('noform', 1);
        }

        $currencyPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'currencies.php';
        $acceptedCurrencies = array('AUD'); //Default to only accept aud

        //$acceptedCurrencies defined in currencies.php
        if (file_exists($currencyPath)) {
            require($currencyPath);
        }

        $this->accepted_currencies = $acceptedCurrencies;
        parent::__construct($subject, $config);
    }

    /**
     * Get pin payment properties
     *
     * @return string
     */
    private function getHostedFieldJsUrl()
    {
        return $this->hostedFieldJsUrl;
    }

    /**
     * Add javascripts
     *
     * @return void
     */
    protected function addScripts()
    {
        static $addedScripts;
        if (isset($addedScripts)) {
            return;
        }
        $addedScripts = true;

        $doc = JFactory::getDocument();
        $scripts = array(
            $this->getHostedFieldJsUrl()
        );

        foreach ($scripts as $script) {
            $doc->addScript($script);
        }

        //Styling
        $url = JUri::root() . 'plugins/hikashoppayment/pin/assets/style.css';
        $doc->addStyleSheet($url);
        //

        $this->addedScripts = true;
    }

    /**
     * @param $order
     * @param $method
     * @param bool $ajax
     * @return bool
     */
    private function loadJSCode($order, $method, $ajax = false)
    {
        if (!is_object($method) || $ajax || $this->jsLoaded) {
            return false;
        }

        $this->jsLoaded = true;

        //Used in payment form template
        $this->paymentFormParams['sandbox'] = $method->payment_params->sandbox;
        $this->paymentFormParams['api_key'] = $method->payment_params->api_key;

        $this->paymentFormParams['billing_address'] = $this->extractAddress($order);

        $doc = JFactory::getDocument();

        $jsPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'script.js.php';
        ob_start();
        require($jsPath);
        $js = ob_get_contents();
        ob_end_clean();

        $js = str_replace(array('<script type="text/javascript">', '</script>'), '', $js);
        $doc->addScriptDeclaration($js);
        return true;
    }

    /**
     * Tell hikashop to display custom html when payment method is selected
     *
     * @param $method
     * @return bool
     */
    private function loadHTML(&$method)
    {
        $method->custom_html = '';


        $ajax = hikaInput::get()->getCmd('tmpl', '') === 'ajax';
        $blockTask = hikaInput::get()->getCmd('blocktask', '');

        if (!$ajax || $blockTask == 'payment') {
            $formPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'paymentform.php';

            ob_start();
            require($formPath);
            $html = ob_get_contents();
            ob_end_clean();

            $method->custom_html = $html;
        }
        //
        return true;
    }

    /**
     * @param $cart
     * @return mixed
     */
    protected function extractAddress(&$cart)
    {
        if (!isset($cart->shipping_address) && !isset($cart->billing_address)) {
            return new stdClass();
        }

        $address = $cart->shipping_address;
        if (!empty($cart->billing_address)) {
            $address = $cart->billing_address;
        }

        $addressArray = array($address);

        /** @var hikashopAddressClass $addressClass */
        //Load the address details from db into address
        if (!is_object($address->address_state)) {
            $addressClass = hikashop_get('class.address');
            $addressClass->loadZone($addressArray);
        }
        //

        //State code
        $values = array(
            @$address->address_state_code_2,
            @$address->address_state_code_3,
            @$address->address_state_name
        );
        if (isset($address->address_state) && is_object($address->address_state)) {
            $values[] = $address->address_state->zone_code_2;
            $values[] = $address->address_state->zone_code_3;
        }

        $stateCode = '';
        foreach ($values as $value) {
            $stateCode = trim($value);
            if (strlen($stateCode)) {
                break;
            }
        }
        $address->state_code = $stateCode;
        //

        //Country code
        $values = array(
            @$address->address_country_code_2,
            @$address->address_country_code_3,
            @$address->address_country_name
        );
        if (isset($address->address_country) && is_object($address->address_country)) {
            $values[] = $address->address_country->zone_code_3;
            $values[] = $address->address_country->zone_code_2;
        }
        $countryCode = '';
        foreach ($values as $value) {
            $countryCode = trim($value);
            if (strlen($countryCode)) {
                break;
            }
        }
        $address->country_code = $countryCode;
        //

        return $address;
    }

    /**
     * @param string $token
     * @param $total
     * @param string $description
     * @return bool
     */
    protected function processPayment($token, $total, $description = '')
    {
        if (!strlen($token)) {
            $this->app->enqueueMessage(JText::_('PLG_HIKASHOPPAYMENT_PIN_ERROR_NO_TOKEN'), 'error');
            return false;
        }

        //Pin expects the amount in the currency's base unit (cents for AUD, yen for JPY)
        $digits = (int)$this->currency->currency_locale['int_frac_digits'];
        if ($digits > 2) {
            $digits = 2;
        }
        $amount = (int) round($total * pow(10, $digits));

        $data = array(
            'amount' => $amount,
            'currency' => strtolower($this->currency->currency_code),
            'description' => $description,
            'ip_address' => trim($_SERVER['REMOTE_ADDR']),
            'capture' => 'true',
            'email' => $this->user->user_email,
        );
        //Card tokens are prefixed with card_, customer tokens are not
        if (strpos($token, 'card_') !== false) {
            $data['card_token'] = $token;
        } else {
            $data['customer_token'] = $token;
        }

        require_once __DIR__ . DIRECTORY_SEPARATOR . 'pin_api.php';
        $api = new PinPaymentsAPI(
            $this->payment_params->secret_key,
            !empty($this->payment_params->sandbox),
            !empty($this->payment_params->debug)
        );

        try {
            $response = $api->createCharge($data);
        } catch (Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        //Pin returns an "error" key on failure, otherwise response.token holds the charge reference
        if (!is_object($response) || isset($response->error)) {
            $message = '';
            if (is_object($response)) {
                $message = !empty($response->error_description) ? $response->error_description : @$response->error;
            }
            if (!strlen($message)) {
                $message = JText::_('PLG_HIKASHOPPAYMENT_PIN_ERROR_NO_TOKEN');
            }
            $this->app->enqueueMessage($message, 'error');
            return false;
        }

        if (isset($response->response->token)) {
            return $response->response->token;
        }

        return false;
    }

    /**
     * Before order create, check payment config
     *
     * @param $order
     * @param $do
     * @return bool
     */
    public function onBeforeOrderCreate(&$order, &$do)
    {
        if (parent::onBeforeOrderCreate($order, $do) === true) {
            return true;
        }
        if (empty($this->payment_params->api_key)) {
            $this->app->enqueueMessage(JText::_('PLG_HIKASHOPPAYMENT_PIN_CHECK_CONFIG'), 'error');
            $do = false;
        }

        if ($do) {
            /** @var hikashopPaymentClass $paymentClass */
            $paymentClass = hikashop_get('class.payment');
            $paymentClass->readCC();
            $app = JFactory::getApplication();

            $token = $app->getUserState(HIKASHOP_COMPONENT . '.pinct');
            $app->setUserState(HIKASHOP_COMPONENT . '.pinct', '');

            $total = $order->cart->full_total->prices[0]->price_value_with_tax;

            //Order description
            /*
            $description = array();
            foreach ($order->cart->products as $product) {
                $description[] = strip_tags($product->order_product_name);
            }
            $description = implode(' - ', $description);
            */

            //Save to get order number
            $orderRef = @$this->payment_params->charge_description;
            if (!strlen($orderRef)) {
                $orderRef = 'Order: [ORDER]';
            }

            $orderRef = str_replace('[ORDER]', $order->order_token, $orderRef);
            $responseToken = $this->processPayment($token, $total, $orderRef);

            if ($responseToken !== false) {
                $history = new stdClass();
                $history->notified = 0;
                $history->amount = round($order->cart->full_total->prices[0]->price_value_with_tax, 2) . $this->currency->currency_code;
                $history->data = JText::sprintf('PLG_HIKASHOPPAYMENT_PIN_REF', $orderRef, $responseToken);

                $this->modifyOrder($order, $this->payment_params->verified_status, $history, true);
            } else {
                $do = false;
            }
        }

        return true;
    }

    /**
     * @param $order
     * @param $methods
     * @param $method_id
     * @return bool|void
     */
    public function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        $this->removeCart = true;
        $method =& $methods[$method_id];
        $this->return_url = @$method->payment_params->return_url;
        return $this->showPage('thanks');
    }

    /**
     * @param $element
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = $this->name;
        $element->payment_description = 'You can pay by credit card using this payment method';
        $element->payment_images = 'MasterCard,VISA,';
        $element->payment_params->return_url = HIKASHOP_LIVE;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->pending_status = 'created';
        $element->payment_params->verified_status = 'confirmed';
        $element->payment_params->charge_description = 'Order: [ORDER]';

        $element->payment_params->sandbox = '1';
    }

    /**
     * @param $element
     * @return bool
     */
    public function onPaymentConfigurationSave(&$element)
    {
        if (empty($element->payment_params->api_key)) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('PLG_HIKASHOPPAYMENT_PIN_API_KEY_ERROR'), 'error');
            return false;
        }
        return true;
    }

    /**
     * This method will be called by HikaShop when it is displaying the payment layout of the checkout process.
     * @param $order
     * @param $methods
     * @param $usable_methods
     * @return bool|void
     */
    public function onPaymentDisplay(&$order, &$methods, &$usable_methods)
    {
        if (!parent::onPaymentDisplay($order, $methods, $usable_methods)) {
            return false;
        }

        //Add javascript

        $ajax = hikaInput::get()->getCmd('tmpl', '') === 'ajax';

        if (!$ajax) {
            $this->addScripts();
        }
        $method = null;
        foreach ($usable_methods as $usable_method) {
            if ($usable_method->payment_type == $this->name) {
                $method = $usable_method;
                $this->loadJSCode($order, $method, $ajax);
                $this->loadHTML($method);
            }
        }
        //

        return true;
    }

    /**
     * @param $cart
     * @param $rates
     * @param $payment_id
     * @return bool
     */
    function onPaymentSave(&$cart, &$rates, &$payment_id)
    {
        $cart->cart_payment_id = $payment_id;
        $token = hikaInput::get()->getString('card_token', '');

        $payment = $cart->payment;
        $paymentType = '';
        if (is_object($payment)) {
            $paymentType = $payment->payment_type;
        }

        $proceed = strlen($token); // && $paymentType == $this->name;
        if ($proceed) {
            $this->app->setUserState(HIKASHOP_COMPONENT . '.pinct', $token);
        }

        return parent::onPaymentSave($cart, $rates, $payment_id);
    }
}
