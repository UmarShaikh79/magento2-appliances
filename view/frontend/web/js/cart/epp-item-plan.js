/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * @category  MageTips
 * @package   MageTips_Appliances
 * @author    Muhammad Umar <umarshiekh619@gmail.com>
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link      https://github.com/UmarShaikh79/magento2-appliances
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, customerData) {
    'use strict';

    return function (config, element) {
        var $box    = $(element);
        var saveUrl = config.saveUrl;
        var itemId  = config.itemId;
        var hasPlan = config.hasPlan;

        function getFormKey()
        {
            return $('input[name="form_key"]').first().val() || '';
        }

        function setLoading(on)
        {
            $box.find('.epp-btn-add, .epp-btn-remove, .epp-plan-select').prop('disabled', on);
            if (on) {
                $box.find('.epp-btn-add').css({'opacity': '0.6', 'cursor': 'not-allowed'});
            }
        }

        function doSave(eppCode)
        {
            setLoading(true);
            $.ajax({
                url:      saveUrl,
                type:     'POST',
                dataType: 'json',
                data: {
                    item_id:  itemId,
                    epp_code: eppCode,
                    form_key: getFormKey()
                },
                success: function (response) {
                    if (response && response.error) {
                        alert(response.error);
                        setLoading(false);
                        return;
                    }
                    customerData.invalidate(['cart']);
                    window.location.reload();
                },
                error: function () {
                    alert($.mage.__('An error occurred. Please try again.'));
                    setLoading(false);
                }
            });
        }

        // Dropdown change
        $box.on('change', '.epp-plan-select', function () {
            var code = $(this).val();
            // No ADD button means a plan is already active, so save the new choice right away.
            if ($box.find('.epp-btn-add').length === 0) {
                doSave(code || '');
            } else {
                // Still picking for the first time — only enable ADD once a plan is chosen.
                var $btn = $box.find('.epp-btn-add');
                if (code) {
                    $btn.prop('disabled', false).css({'opacity': '1', 'cursor': 'pointer'});
                } else {
                    $btn.prop('disabled', true).css({'opacity': '0.5', 'cursor': 'not-allowed'});
                }
            }
        });

        // ADD button — first-time add (no plan selected yet)
        $box.on('click', '.epp-btn-add', function () {
            var code = $box.find('.epp-plan-select').val();
            if (code) {
                doSave(code);
            }
        });

        // REMOVE button — clear the plan
        $box.on('click', '.epp-btn-remove', function () {
            doSave('');
        });
    };
});
