define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/checkout-data'
], function ($, wrapper,checkoutData) {
    'use strict';

    return function(targetModule){
        var initialize = targetModule.prototype.initialize;
        var initializeWrapper = wrapper.wrap(initialize, function(original){
            checkoutData.setShippingAddressFromData(null);
            return original();
        });
        targetModule.prototype.initialize = initializeWrapper;
        return targetModule;
    };
});
