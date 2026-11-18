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
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals'
], function (Component, totals) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MageTips_Appliances/checkout/summary/epp-charge'
        },
        totals: totals,

        isDisplayed: function () {
            return this.getPureValue() !== 0;
        },

        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        },

        getPureValue: function () {
            var segment = this.totals.getSegment('appliances_epp_charge');

            return segment ? parseFloat(segment.value) : 0;
        }
    });
});
