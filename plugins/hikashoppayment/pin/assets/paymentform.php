<?php if ($ajax): ?>
<script type="application/javascript">
    jQuery(function ($) {
        var pinSelected = $('.hikashop_checkout_payment_radio:checked').val() == "<?php echo $method->payment_id; ?>";
        pinSelected = pinSelected || $('.hikashop_checkout_payment_radio:checked').val() == "<?php echo $method->payment_type.'_'.$method->payment_id;?>";
        if (pinSelected) {
            window.pinHikaShopPayments<?php echo $method->payment_id; ?>.initPinHostedField();
        }
    });
</script>
<?php endif; ?>

<!-- pin payment form -->
<div id="pin_payments_main_error<?php echo $method->payment_id; ?>" class="error_message"></div>

<div id="plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>" class="plg_hikashoppayment_pin">
    <div class="plg_hikashoppayment_pin_spinner"
         id="plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_spinner" style="visibility: visible">
        <img src="<?php echo HIKASHOP_IMAGES . 'spinner.gif'; ?>"/> Loading payment form...
    </div>

    <div id="plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_summary"
         class="hikashop_messages hikashop_info" style="display: none;">
        <ul>
            <li><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_DATA_VALID'); ?></li>
        </ul>
    </div>

    <div class="plg_hikashoppayment_pin_inner"
         id="plg_hikashoppayment_pin_fields<?php echo $method->payment_id; ?>_inner" style="opacity: 0">

        <label
            for="pin_name_<?php echo $method->payment_id; ?>"><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_NAME'); ?></label><br/>

        <div id="pin_name_<?php echo $method->payment_id; ?>">

        </div>
        <div id="errors_for_name<?php echo $method->payment_id; ?>" class="error_message"></div>

        <label
            for="pin_number_<?php echo $method->payment_id; ?>"><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_NUMBER'); ?></label><br/>

        <div id="pin_number_<?php echo $method->payment_id; ?>">

        </div>
        <div id="errors_for_number<?php echo $method->payment_id; ?>" class="error_message"></div>

        <label
            for="pin_cvc_<?php echo $method->payment_id; ?>"><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_CVC'); ?></label><br/>

        <div id="pin_cvc_<?php echo $method->payment_id; ?>">

        </div>
        <div id="errors_for_cvc<?php echo $method->payment_id; ?>" class="error_message"></div>

        <label
            for="pin_expiry_<?php echo $method->payment_id; ?>"><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_FIELD_EXPIRY'); ?></label><br/>

        <div id="pin_expiry_<?php echo $method->payment_id; ?>">

        </div>
        <div id="errors_for_expiry<?php echo $method->payment_id; ?>" class="error_message"></div>
    </div>

    <button id="pinreset_<?php echo $method->payment_id; ?>" class="hikabtn hikabtn_checkout_payment_reset"
            style="visibility: hidden"
            onclick="return window.pinHikaShopPayments<?php echo $method->payment_id; ?>.resetPayForm();"><?php echo \Joomla\CMS\Language\Text::_('PLG_HIKASHOPPAYMENT_PIN_RESETFORM'); ?></button>
</div>