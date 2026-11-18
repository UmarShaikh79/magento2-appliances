/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * Delivery date widget for the PDP.
 * Reads picker config (earliest date, disabled days, holidays) injected
 * by the PHP block and initialises a jQuery UI datepicker on "Change" click.
 *
 * Config shape (from Calculator::getPickerData()):
 *   { earliest: 'YYYY-MM-DD', disabledDays: [0,6], holidays: ['YYYY-MM-DD', …] }
 */
define(['jquery', 'jquery/ui'], function ($) {
    'use strict';

    return function (config, element) {
        var $widget      = $(element);
        var $changeBtn   = $widget.find('.delivery-change-btn');
        var $selectedRow = $widget.find('.delivery-selected');
        var $selectedLbl = $widget.find('.delivery-selected-date');
        var $clearBtn    = $widget.find('.delivery-clear-btn');
        var $input       = $widget.find('.delivery-date-input');
        var $headline    = $widget.find('.delivery-headline');

        // The widget lives outside <form#product_addtocart_form>, so the hidden
        // input would never be POSTed. Move it inside the form now so the
        // observer can read delivery_date from the request params.
        var $form = $('#product_addtocart_form');
        if ($form.length) {
            $input.detach().appendTo($form);
        }

        // Parse earliest date parts (avoid timezone shift from new Date('YYYY-MM-DD'))
        var parts    = config.earliest.split('-');
        var earliest = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));

        // Index holidays by date string so we can look them up quickly below
        var holidaySet = {};
        (config.holidays || []).forEach(function (d) {
            holidaySet[d] = true;
        });

        // Off-screen input — must NOT be display:none or datepicker._findPos() throws
        var $pickerInput = $('<input type="text">')
            .css({ position: 'absolute', left: '-9999px', top: '0', width: '0', height: '0', opacity: '0' })
            .appendTo($widget);

        $pickerInput.datepicker({
            minDate: earliest,
            dateFormat: 'yy-mm-dd',
            beforeShow: function (input, inst) {
                // Reposition calendar below the Change button after jQuery UI places it
                setTimeout(function () {
                    inst.dpDiv.css({
                        top:  $changeBtn.offset().top + $changeBtn.outerHeight() + 4,
                        left: $changeBtn.offset().left
                    });
                }, 0);
            },
            beforeShowDay: function (date) {
                var dow = date.getDay(); // 0=Sun … 6=Sat
                if ((config.disabledDays || []).indexOf(dow) !== -1) {
                    return [false, ''];
                }
                var iso = date.getFullYear() + '-' +
                    String(date.getMonth() + 1).padStart(2, '0') + '-' +
                    String(date.getDate()).padStart(2, '0');
                if (holidaySet[iso]) {
                    return [false, 'delivery-holiday', 'Holiday'];
                }
                return [true, ''];
            },
            onSelect: function (dateStr) {
                // Format for display: "Wednesday, June 7"
                var d       = $.datepicker.parseDate('yy-mm-dd', dateStr);
                var display = $.datepicker.formatDate('DD, MM d', d);

                $input.val(dateStr);
                $selectedLbl.text(display);
                $selectedRow.show();
                $headline.hide();
            }
        });

        $changeBtn.on('click', function () {
            $pickerInput.datepicker('show');
        });

        $clearBtn.on('click', function () {
            $input.val('');
            $selectedRow.hide();
            $headline.show();
            $pickerInput.datepicker('setDate', null);
        });

        // Pre-select the earliest eligible date on page load so the hidden input
        // is already populated if the customer adds to cart without changing it.
        var defaultDisplay = $.datepicker.formatDate('DD, MM d', earliest);
        $input.val(config.earliest);
        $selectedLbl.text(defaultDisplay);
        $selectedRow.show();
        $headline.hide();
        $pickerInput.datepicker('setDate', earliest);
    };
});
