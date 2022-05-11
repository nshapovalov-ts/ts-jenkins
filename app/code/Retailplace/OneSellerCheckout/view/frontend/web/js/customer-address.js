/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/error-processor',
    'jquery'
], function (resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor, $) {
    'use strict';

    /**
     * Override to force select Shipping Method after it's fetching
     */
    return {
        /**
         * @param {Object} address
         */
        getRates: function (address) {
            var cache;

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getKey());

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
            } else {
                storage.post(
                    resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId(),
                    JSON.stringify({
                        addressId: address.customerAddressId
                    }),
                    false
                ).done(function (result) {
                    rateRegistry.set(address.getKey(), result);
                    shippingService.setShippingRates(result);
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                        shippingService.isLoading(false);
                        var shippingMethods = $('[name*="shipping_method"]:checked');
                        if (shippingMethods.length == 1) {
                            $('[name*="shipping_method"]:checked').trigger('click');
                        }
                    }
                );
            }
        }
    };
});
