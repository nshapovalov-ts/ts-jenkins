define(
    [
        'Magento_Tax/js/view/checkout/summary/shipping'
    ],
    function (Component) {
        'use strict';

        var quoteData = window.checkoutConfig.quoteData;

        return Component.extend({
            /**
             * @override
             */
            getShippingMethodTitle: function() {
                return '';
            },

            /**
             * @override
             */
            isCalculated: function() {
                return this._super() || quoteData.mirakl_shipping_fee > 0;
            }
        });
    }
);
