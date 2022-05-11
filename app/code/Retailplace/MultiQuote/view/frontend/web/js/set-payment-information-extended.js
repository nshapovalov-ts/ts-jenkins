/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'underscore',
    'multiQuoteManagement'
], function (quote, urlBuilder, storage, errorProcessor, customer, getTotalsAction, fullScreenLoader, _, multiQuoteManagement) {
    'use strict';

    /**
     * Filter template data.
     *
     * @param {Object|Array} data
     */
    var filterTemplateData = function (data) {
        return _.each(data, function (value, key, list) {
            if (_.isArray(value) || _.isObject(value)) {
                list[key] = filterTemplateData(value);
            }

            if (key === '__disableTmpl') {
                delete list[key];
            }
        });
    };

    /**
     * Override Endpoint URL to add extra params
     */
    return function (messageContainer, paymentData, skipBilling) {
        var serviceUrl,
            payload;

        paymentData = filterTemplateData(paymentData);
        skipBilling = skipBilling || false;
        payload = {
            cartId: quote.getQuoteId(),
            paymentMethod: paymentData
        };

        /**
         * Checkout for guest and registered customer.
         */
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/set-payment-information', {
                cartId: quote.getQuoteId()
            });
            payload.email = quote.guestEmail;
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information' + multiQuoteManagement.getExtendedParameterString(), {});
        }

        if (skipBilling === false) {
            payload.billingAddress = quote.billingAddress();
        }

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl, JSON.stringify(payload)
        ).fail(
            function (response) {
                errorProcessor.process(response, messageContainer);
            }
        ).always(
            function () {
                fullScreenLoader.stopLoader();
            }
        );
    };
});
