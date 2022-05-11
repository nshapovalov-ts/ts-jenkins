/*
 * Magento_Checkout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, quote, urlBuilder, storage, errorProcessor, customer, methodConverter, paymentService, fullScreenLoader) {
    'use strict';

    return function (deferred, messageContainer) {
        var serviceUrl;

        deferred = deferred || $.Deferred();

        /**
         * Checkout for guest and registered customer.
         */
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/payment-information', {
                cartId: quote.getQuoteId()
            });
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
        }

        fullScreenLoader.startLoader();

        return storage.get(serviceUrl, false).done(function (response) {
            quote.setTotals(response.totals);
            paymentService.setPaymentMethods(methodConverter(response['payment_methods']));
            deferred.resolve();
        }).fail(function (response) {
            errorProcessor.process(response, messageContainer);
            deferred.reject();
        }).always(
            function () {
                fullScreenLoader.stopLoader();
            }
        );
    };
});
