/**
 * Retailplace_Recentview
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery'
], function (Component, customerData, $) {
    'use strict';
    return Component.extend({
        recentsection: function () {
            return customerData.get('recently_viewed_product')().html;
        },
    });
});
