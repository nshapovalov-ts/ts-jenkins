/*global define,alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-save-processor'
    ],
    function ($, quote, shippingSaveProcessor) {
        'use strict';

        return function () {
            // Add additional methods to payload
            if (!quote.shippingAddress().extension_attributes) {
                quote.shippingAddress().extension_attributes = {};
            }
            quote.shippingAddress().extension_attributes.additional_methods = $.param($('input[name^="shipping_method"]:checked'));

            return shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
        };
    }
);
