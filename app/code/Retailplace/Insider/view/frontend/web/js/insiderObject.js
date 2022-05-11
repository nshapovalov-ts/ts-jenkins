/**
 * Retailplace_Insider
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
], function ($, customerData) {
    'use strict';

    return function (config) {
        var customer = customerData.get('customer');
        if (customer().insider_object) {
            config.user = customer().insider_object.user;
        }
        var cart = customerData.get('cart');
        if (cart().insider_object) {
            config.basket = cart().insider_object.basket;
        }

        window.insider_object = config;
    };
});
