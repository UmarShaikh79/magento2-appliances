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
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function ($, modal, customerData, priceUtils) {
    'use strict';

    return function (config, element) {
        var $summaryBar = $(element);
        var $modalElem  = $('#epp-manage-modal');
        var updateUrl   = config.updateUrl;
        var modalWidget = null;

        var priceFormat = (window.checkoutConfig && window.checkoutConfig.priceFormat)
            ? window.checkoutConfig.priceFormat
            : {
                pattern:           config.currencySymbol + '%s',
                decimalSymbol:     '.',
                groupSymbol:       ',',
                groupLength:       3,
                precision:         2,
                requiredPrecision: 2,
                integerRequired:   false
            };

        function fmt(amount)
        {
            return priceUtils.formatPrice(amount, priceFormat);
        }

        // ── Helpers — use document-level selectors so they work after the modal
        // widget re-parents #epp-manage-modal into an <aside> overlay. ──────────

        function getCheckedItems()
        {
            return $('.epp-item-checkbox:checked');
        }

        function getSelectedPlan()
        {
            return $('input[name="epp_modal_plan"]:checked');
        }

        // ── Dynamic total ─────────────────────────────────────────────────────

        function recalcTotal()
        {
            var checked   = getCheckedItems().length;
            var $plan     = getSelectedPlan();
            var planPrice = $plan.length ? parseFloat($plan.attr('data-price') || 0) : 0;
            $('#epp-modal-total').text(fmt(checked * planPrice));
        }

        // ── Select All with indeterminate support ─────────────────────────────

        function syncSelectAll()
        {
            var $boxes  = $('.epp-item-checkbox');
            var total   = $boxes.length;
            var checked = $boxes.filter(':checked').length;
            var $sa     = $('#epp-select-all');

            if (checked === 0) {
                $sa.prop('checked', false).prop('indeterminate', false);
            } else if (checked === total) {
                $sa.prop('checked', true).prop('indeterminate', false);
            } else {
                $sa.prop('checked', false).prop('indeterminate', true);
            }
        }

        // ── AJAX save ─────────────────────────────────────────────────────────

        function doUpdate()
        {
            var $btn    = $('#epp-modal-update-btn');
            var formKey = $('input[name="form_key"]').first().val() || '';
            var eppCode = getSelectedPlan().val() || '';
            var itemIds = [];

            getCheckedItems().each(function () {
                itemIds.push($(this).val());
            });

            if (!eppCode) {
                alert($.mage.__('Please select a protection plan.'));
                return;
            }
            if (itemIds.length === 0) {
                alert($.mage.__('Please select at least one item to protect.'));
                return;
            }

            $btn.prop('disabled', true).text($.mage.__('Updating…'));

            $.ajax({
                url:      updateUrl,
                type:     'POST',
                dataType: 'json',
                data: {
                    item_ids: itemIds,
                    epp_code: eppCode,
                    form_key: formKey
                },
                success: function (response) {
                    if (response && response.error) {
                        alert(response.error);
                        $btn.prop('disabled', false).text($.mage.__('UPDATE'));
                        return;
                    }
                    customerData.invalidate(['cart']);
                    window.location.reload();
                },
                error: function () {
                    alert($.mage.__('An error occurred. Please try again.'));
                    $btn.prop('disabled', false).text($.mage.__('UPDATE'));
                }
            });
        }

        // ── Modal init ────────────────────────────────────────────────────────

        function restoreFormState()
        {
            // Moving the modal into the overlay can reset checkbox/radio state in
            // some browsers, so restore each control from its defaultChecked value
            // and make sure one plan radio ends up selected.
            $('input[name="epp_modal_plan"]').each(function () {
                this.checked = this.defaultChecked;
            });
            if (getSelectedPlan().length === 0) {
                $('input[name="epp_modal_plan"]').first().prop('checked', true);
            }
            $('.epp-item-checkbox').each(function () {
                this.checked = this.defaultChecked;
            });
        }

        function openModal()
        {
            if (!modalWidget) {
                modalWidget = modal({
                    type:             'popup',
                    title:            config.modalTitle || $.mage.__('Protection Plan'),
                    responsive:       true,
                    innerScroll:      true,
                    clickableOverlay: false,
                    modalClass:       'epp-kent-modal',
                    buttons:          []
                }, $modalElem);
            }

            restoreFormState();
            syncSelectAll();
            recalcTotal();
            $modalElem.modal('openModal');
        }

        // ── Event bindings ────────────────────────────────────────────────────

        $summaryBar.on('click', '#epp-manage-btn', function () {
            openModal();
        });

        // Delegate from document — works regardless of where modal re-parents elements
        $(document).on('change', '.epp-item-checkbox', function () {
            syncSelectAll();
            recalcTotal();
        });

        $(document).on('change', '#epp-select-all', function () {
            $('.epp-item-checkbox').prop('checked', $(this).is(':checked'));
            syncSelectAll();
            recalcTotal();
        });

        $(document).on('change', 'input[name="epp_modal_plan"]', function () {
            recalcTotal();
        });

        $(document).on('click', '#epp-modal-update-btn', function () {
            doUpdate();
        });

        // ── Initial total ─────────────────────────────────────────────────────

        $(function () {
            syncSelectAll();
            recalcTotal();
        });
    };
});
