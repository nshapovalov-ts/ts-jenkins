define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'mage/utils/wrapper'
], function (quote, selectShippingMethodAction, wrapper) {
    'use strict';

    var extender = {
        /**
         * @inheritdoc
         */
        resolveShippingRates: function (originFn, ratesData) {
            if (ratesData.length > 1 && !quote.shippingMethod()) {
                var operatorRatesData = [];
                for (var i = 0; i < ratesData.length; i++) {
                    if (ratesData[i].offer_id == undefined) {
                        operatorRatesData.push(ratesData[i]);
                    }
                }
                //set shipping rate if we have only one operator available shipping rate
                if (operatorRatesData.length === 1) {
                    selectShippingMethodAction(operatorRatesData[0]);
                }
            }

            return originFn(ratesData);
        }
    };

    return function (target) {
        return wrapper.extend(target, extender);
    };
});
