/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * Mixin for Magento_Checkout/js/view/summary/cart-items.
 *
 * Swaps in our template (to show the EPP badge and delivery date per item)
 * and overrides setItems() to re-apply epp_plan_name and delivery_date_label
 * to each item. The AJAX totals refresh between checkout steps strips those
 * fields out, so we read them back from window.checkoutConfig.quoteItemData
 * (set by the PHP plugin) each time setItems() runs.
 */
define([], function () {
    'use strict';

    return function (Component) {
        return Component.extend({
            defaults: {
                template: 'MageTips_Appliances/summary/cart-items'
            },

            /**
             * Re-apply epp_plan_name and delivery_date_label to each item.
             * Runs on load and after every totals update.
             *
             * @param {Array} items
             */
            setItems: function (items) {
                if (items && items.length) {
                    var quoteItemData = window.checkoutConfig.quoteItemData || [];
                    var eppMap      = {};
                    var deliveryMap = {};

                    quoteItemData.forEach(function (qItem) {
                        var id = parseInt(qItem.item_id, 10);
                        if (qItem.epp_plan_name) {
                            eppMap[id] = qItem.epp_plan_name;
                        }
                        if (qItem.delivery_date_label) {
                            deliveryMap[id] = qItem.delivery_date_label;
                        }
                    });

                    items = items.map(function (item) {
                        var id = parseInt(item.item_id, 10);

                        if (eppMap[id]) {
                            item.epp_plan_name = eppMap[id];
                        }
                        if (deliveryMap[id]) {
                            item.delivery_date_label = deliveryMap[id];
                        }

                        return item;
                    });
                }

                return this._super(items);
            }
        });
    };
});
