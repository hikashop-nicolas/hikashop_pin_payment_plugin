<script type="text/javascript">
    jQuery(function ($) {
        var form = $('form[name=hikashop_checkout_form]');
        if (form.length < 1) {
            return;
        }

        var submitButton = $('input[type=submit].hikashop_cart_input_button');
        if (submitButton.length) {
            <?php //Hacky way of overriding hikashop js blob on the submit button; ?>
            submitButton.each(function () {
                if (jQuery(this).attr('name') != 'next' || jQuery(this).data('pin-processed-button') == 'true') {
                    return;
                }
                jQuery(this).data('pin-processed-button', 'true');

                var hikaClick = this.onclick;
                jQuery(this).data('onclick', this.onclick);

                this.onclick = function (event) {
                    if (event != undefined) {
                        event.preventDefault();
                    }

                    if (window.pinHikaShopPayments<?php echo $method->payment_id; ?>.pinSelected()) {
                        if ($('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #card_token').length == 0) {
                            window.pinHikaShopPayments<?php echo $method->payment_id; ?>.tokenizeHostedFields(false, hikaClick);
                        }
                    } else {
                        try {
                            hikaClick();
                        } catch (e) {
                            console.info(e);
                        }
                    }
                    return;
                };
            });
        } else {
            form.on('submit', function (e) {
                if ($('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #card_token').length == 0 && window.pinHikaShopPayments<?php echo $method->payment_id; ?>.pinSelected()) {
                    e.preventDefault();
                    window.pinHikaShopPayments<?php echo $method->payment_id; ?>.tokenizeHostedFields();
                } else {
                    this.submit();
                }
            });
        }

        window.pinHikaShopPayments<?php echo $method->payment_id; ?>.initPinHostedField();
    });

    window.pinHikaShopPayments<?php echo $method->payment_id; ?> = {
        initCounter: 0,

        /**
         * Init payment fields on page
         */
        initPinHostedField: function (counter) {
            var payButton = window.pinHikaShopPayments<?php echo $method->payment_id; ?>.getAjaxPayButton();
            payButton.each(function () {
                jQuery(this).data('onclick', this.onclick);

                this.onclick = function (event) {
                    event.preventDefault();
                    window.pinHikaShopPayments<?php echo $method->payment_id; ?>.tokenizeHostedFields(false);
                    return;
                };
            });

            if (counter == undefined) {
                counter = true;
            }
            if (counter) {
                this.initCounter++;
            }

            if (this.initCounter > 1) {
                this.initCounter = 0;
                return;
            }

            window.pinHikaShopPayments<?php echo $method->payment_id; ?>.showSpinner();

            fields = HostedFields.create({
                sandbox: <?php echo $this->paymentFormParams['sandbox']?'true':'false' ?>,
                fields: {
                    name: {
                        selector: '#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #pin_name_<?php echo $method->payment_id; ?>',
                        placeholder: '<?php echo json_encode(\Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_PH_NAME')); ?>'
                    },
                    number: {
                        selector: '#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #pin_number_<?php echo $method->payment_id; ?>',
                        placeholder: '<?php echo json_encode(\Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_PH_NUMBER')); ?>'
                    },
                    cvc: {
                        selector: '#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #pin_cvc_<?php echo $method->payment_id; ?>',
                        placeholder: '<?php echo json_encode(\Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_PH_CVC')); ?>'
                    },
                    expiry: {
                        selector: '#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #pin_expiry_<?php echo $method->payment_id; ?>',
                        placeholder: '<?php echo json_encode(\Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_PH_EXPIRY')); ?>'
                    }
                }
            });

            fields.on('ready', function () {
                window.pinHikaShopPayments<?php echo $method->payment_id; ?>.pinLoaded();
            });
        },

        pinSelected: function () {
            var selected = jQuery('.hikashop_checkout_payment_radio:checked').val() == "<?php echo $method->payment_id; ?>";
            selected = selected || jQuery('.hikashop_checkout_payment_radio:checked').val() == "<?php echo $method->payment_type.'_'.$method->payment_id;?>";

            return selected;
        },

        pinLoaded: function () {
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_inner').css('opacity', '1');
            window.pinHikaShopPayments<?php echo $method->payment_id; ?>.hideSpinner();
        },

        getAjaxPayButton: function () {
            var button = jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>').parents().find('.hikabtn_checkout_payment_submit');
            return button;
        },

        tokenizeHostedFields: function (submitForm, customEvent) {
            if (jQuery('.hikashop_cart_input_button')) {
                jQuery('.hikashop_cart_input_button').prop('disabled', true);
            }
            if (submitForm == undefined) {
                submitForm = true;
            }

            var addressDetails = {
                publishable_api_key: '<?php echo @$this->paymentFormParams['api_key']; ?>',
                address_line1: <?php echo json_encode(@$this->paymentFormParams['billing_address']->address_street); ?>,
                address_line2: <?php echo json_encode(@$this->paymentFormParams['billing_address']->address_street2); ?>,
                address_city: <?php echo json_encode(@$this->paymentFormParams['billing_address']->address_city); ?>,
                address_postcode: <?php echo json_encode(@$this->paymentFormParams['billing_address']->address_post_code); ?>,
                address_state: <?php echo json_encode(@$this->paymentFormParams['billing_address']->state_code); ?>,
                address_country: <?php echo json_encode(@$this->paymentFormParams['billing_address']->address_country); ?>
            };

            <?php
            $tokenizerFields = array(
                'address_line1' => 'address_street',
                'address_line2' => 'address_street2',
                'address_city' => 'address_city',
                'address_postcode' => 'address_post_code',
                'address_state' => 'state_code',
                'address_country' => 'address_country'
            );

            $addressDetails = 'var addressDetails = {publishable_api_key: \'' . @$this->paymentFormParams['api_key'] . '\',';

            foreach ($tokenizerFields as $jsFieldKey => $billingField) {
                $paramName = 'tokenizer_fields_' . $jsFieldKey;

                if (isset($method->payment_params->$paramName) && !intval($method->payment_params->$paramName)) {
                    continue;
                }

                $value = @$this->paymentFormParams['billing_address']->$billingField;
                if (is_null($value)) {
                   continue;
                }

                $addressDetails .= $jsFieldKey . ': ' . json_encode($value) . ',';
            }

            $addressDetails = trim($addressDetails, ',');
            $addressDetails .= '};';

            echo $addressDetails;
            ?>

            fields.tokenize(
                addressDetails,
                function (err, response) {
                    if (err) {
                        window.pinHikaShopPayments<?php echo $method->payment_id; ?>.handleErrors(err);
                        if (jQuery('.hikashop_cart_input_button')) {
                            jQuery('.hikashop_cart_input_button').prop('disabled', false);
                        }
                        return;
                    }
                    // Append a hidden element to the form with the card_token
                    jQuery('<input>').attr({
                        type: 'hidden',
                        id: 'card_token',
                        name: 'card_token',
                        value: response.token
                    }).appendTo('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>');

                    if (submitForm) {
                        jQuery('#hikashop_checkout_form').submit();
                    } else if (customEvent != undefined) {
                        customEvent();
                    } else {
                        //"Save credit card"
                        window.pinHikaShopPayments<?php echo $method->payment_id; ?>.showSummary();
                    }
                }
            );
        },

        resetPayForm: function () {
            var payButton = window.pinHikaShopPayments<?php echo $method->payment_id; ?>.getAjaxPayButton();
            payButton.css('visibility', 'visible');
            jQuery('#pinreset_<?php echo $method->payment_id; ?>').css('visibility', 'hidden');
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_summary').css('display', 'none');
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_inner').css('opacity', '1');

            return false;
        },

        showSummary: function () {
            var payButton = window.pinHikaShopPayments<?php echo $method->payment_id; ?>.getAjaxPayButton();
            payButton.css('visibility', 'hidden');

            jQuery('.pin_name_<?php echo $method->payment_id; ?>').html(jQuery('#pin.name .name').val());

            jQuery('#pinreset_<?php echo $method->payment_id; ?>').css('visibility', 'visible');
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_summary').css('display', 'block');
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_inner').css('opacity', '0');
        },

        /**
         * Handle errors
         * @param err
         */
        handleErrors: function (err) {
            jQuery('.error_message').text('');

            if (err.messages == undefined && err.error != undefined) {
                jQuery('#pin_payments_main_error<?php echo $method->payment_id; ?>').text(err.error_description);
            }

            if (err.messages == undefined) {
                return;
            }
            err.messages.forEach(function (errMsg) {
                jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?> #errors_for_' + errMsg.param + '<?php echo $method->payment_id; ?>').text(errMsg.message);
            });
        },

        showSpinner: function () {
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_spinner').css('visibility', 'visible');
        },

        hideSpinner: function () {
            jQuery('#plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_spinner').css('visibility', 'hidden');
        }
    };
</script>