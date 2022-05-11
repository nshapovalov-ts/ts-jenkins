/*
 * Retailplace_TopMenuFilter
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

define([
    "jquery",
    "Magento_Ui/js/modal/modal",
    "mage/tooltip",
    'mage/validation',
    'mage/translate',
    "Amasty_Shopby/js/jquery.ui.touch-punch.min",
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.topFilterFromTo', $.mage.amShopbyFilterFromTo, {
        from: null,
        to: null,
        value: null,
        timer: null,
        go: null,
        skip: false,
        _create: function () {
            $(function () {
                this.value = this.element.find('[data-amshopby-fromto="value"]');
                this.from = this.element.find('[data-amshopby-fromto="from"]');
                this.to = this.element.find('[data-amshopby-fromto="to"]');
                this.go = this.element.find('[data-amshopby-fromto="go"]');

                this.value.on('top_filter:clear', this.onTopFilterClear.bind(this));

                this.value.on('amshopby:sync_change', this.onSyncChange.bind(this));
                var fromValue = this.options.from ? parseFloat(this.options.from).toFixed(0) : '',
                    toValue = this.options.to ? parseFloat(this.options.to).toFixed(0) : '',
                    newValue = fromValue + '-' + toValue;

                this.value.trigger('amshopby:sync_change', [[this.value.val() ? this.value.val() : newValue, true]]);
                this.from.on('keyup', this.eventListener.bind(this));
                this.to.on('keyup', this.eventListener.bind(this));

                this.element.find('form').mage('validation', {
                    errorPlacement: function (error, element) {
                        var parent = element.parent();
                        if (parent.hasClass('range')) {
                            parent.find(this.errorElement + '.' + this.errorClass).remove().end().append(error);
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    messages: {
                        'am_shopby_filter_widget_attr_price_from': {
                            'greater-than-equals-to': $.mage.__('Please enter a valid price range.'),
                            'validate-digits-range': $.mage.__('Please enter a valid price range.')
                        },
                        'am_shopby_filter_widget_attr_price_to': {
                            'greater-than-equals-to': $.mage.__('Please enter a valid price range.'),
                            'validate-digits-range': $.mage.__('Please enter a valid price range.')
                        }
                    }
                });
            }.bind(this));
        },

        preFormat: function (values) {
            var value = values[0].split('-'),
                fixed = this.getFixed(this.isSlider(), 0),
                max = Number(this.options.max).toFixed(fixed),
                min = Number(this.options.min).toFixed(fixed),
                to = max, from = min;

            if (value.length === 2 && (value[0] || value[1])) {
                from = value[0] === '' ? 0 : parseFloat(value[0]).toFixed(fixed);
                to = (value[1] === 0 || value[1] === '') ? this.options.max : parseFloat(value[1]).toFixed(fixed);

                if (this.isDropDown()) {
                    to = Math.ceil(to);
                }
            }

            return {'from': from, 'to': to};
        },

        onSyncChange: function (event, values) {
            var max = this.options.max;
            let labelValues = this.preFormat(values);
            let labelFrom = '$' + labelValues.from;
            let labelTo = (labelValues.to === max) ? "Max" : '$' + labelValues.to;

            this.element.find('[data-amshopby-fromto="from"]').val(labelFrom);
            this.element.find('[data-amshopby-fromto="to"]').val(labelTo);
            this.value.trigger('tops_filter:change_value', []);
        },

        onChange: function (event) {
            var to = this.to.val(),
                from = this.from.val(),
                fixed = this.getFixed(this.isSlider(), this.isPrice());

            let modifiedField = event.currentTarget.getAttribute('data-amshopby-fromto');
            let isFieldUpdate = false;

            to = $.trim(to);
            from = $.trim(from);

            let isMaxValue = to.match(/($)|(Max)/ig)[0] === "Max";

            if (from.match(/($)/ig)[0] === "" || !isMaxValue) {
                isFieldUpdate = true;
            }

            to = to.replace(/\D/ig, '');
            from = from.replace(/\D/ig, '');

            let isValid = true;

            if (typeof (to) === "undefined" || to === "") {
                to = this.options.max;
                isValid = isMaxValue === true;
            }

            if (typeof (from) === "undefined" || from === "") {
                from = this.options.min;
                isValid = false;
            }

            var fromToInterval = this.checkFromTo(parseFloat(from).toFixed(fixed), parseFloat(to).toFixed(fixed), modifiedField);

            this.newTo = fromToInterval.to.toFixed(fixed);
            this.newFrom = fromToInterval.from.toFixed(fixed);
            let newVal = this.newFrom + '-' + this.newTo;
            this.value.val(newVal);

            if (!isValid) {
                return;
            }

            this.value.val(newVal);
            this.value.trigger('change');
            this.value.trigger('sync');

            if (isFieldUpdate) {
                this.onSyncChange(null, [newVal]);
            }

            if (this.go.length === 0) {
                $.mage.amShopbyFilterAbstract.prototype.renderShowButton(event, this.element[0]);
                let linkHref = this.options.url
                    .replace('amshopby_slider_from', this.newFrom)
                    .replace('amshopby_slider_to', this.newTo);
                this.apply(linkHref);
            }
        },

        checkFromTo: function (from, to, modifiedField) {
            let interval = {};
            from = parseFloat(from);
            to = parseFloat(to);

            interval.from = from < this.options.min ? this.options.min : from;
            interval.from = interval.from > this.options.max ? this.options.min : interval.from;
            interval.to = to > this.options.max ? this.options.max : to;
            interval.to = interval.to < this.options.min ? this.options.max : interval.to;

            if (parseFloat(interval.from) > parseFloat(interval.to) && typeof (modifiedField) !== 'undefined') {
                if (modifiedField === 'from') {
                    interval.from = interval.to;
                } else if (modifiedField === 'to') {
                    interval.to = interval.from;
                }
            }

            interval.from = Number(interval.from);
            interval.to = Number(interval.to);

            return interval;
        },

        onTopFilterClear: function () {
            let labelValues = this.preFormat([""]);
            let values = labelValues.from + '-' + labelValues.to;
            this.value.val(values);
            this.value.trigger('change');
            this.value.trigger('sync');
            this.onSyncChange(null, [values]);
        },

        apply: function (link, clearFilter) {
            var code = typeof this.options.code != 'undefined' && this.options.code ? this.options.code : "";
            $('#apply' + code).data('request-link', link);
        },
        getFixed: function (value, isPrice) {
            return 0;
        },
        getSignsCount: function (step, isPrice) {
            return 0;
        },

        eventListener: function (event) {
            if (this.timer !== null) {
                clearTimeout(this.timer);
            }
            this.timer = setTimeout(this.onChange.bind(this), 1000, event);
        }
    });

})
