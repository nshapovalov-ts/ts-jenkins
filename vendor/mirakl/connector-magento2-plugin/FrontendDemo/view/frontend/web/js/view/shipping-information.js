/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/shipping-information',
        'Magento_Checkout/js/model/quote',
        'Mirakl_FrontendDemo/js/model/quote-helper'
    ],
    function ($, Component, quote, quoteHelper) {
        'use strict';

        return Component.extend({
            /**
             * @override
             */
            getShippingMethodTitle: function() {
                return this.isFullMarketplaceQuote() ? '' : this._super();
            },
            isFullMarketplaceQuote: function() {
                return quoteHelper.isFullMarketplaceQuote();
            },
            getAdditionalShippingMethods: function() {
                var methods = [];
                var shops = {};
                $(quote.getItems()).each(function(index, item) {
                    if (item.mirakl_offer_id) {
                        var shopId = item.mirakl_shop_id;
                        var leadtimeToShip = parseInt(item.mirakl_leadtime_to_ship, 10);
                        if (typeof(shops[shopId]) === 'undefined') {
                            shops[shopId] = [];
                        }
                        if (-1 === shops[shopId].indexOf(leadtimeToShip)) {
                            shops[shopId].push(leadtimeToShip);
                            methods.push({
                                shop_name: item.mirakl_shop_name,
                                shipping_type_label: item.mirakl_shipping_type_label
                            });
                        }
                    }
                });

                return methods;
            }
        });
    }
);
