/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true*/
/*global alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, quote) {
        'use strict';

        return {
            /**
             * @returns {Boolean}
             */
            isFullMarketplaceQuote: function() {
                if (typeof window.checkoutConfig.is_full_marketplace_quote !== 'undefined') {
                    return window.checkoutConfig.is_full_marketplace_quote;
                }

                var result = true;
                $.each(quote.getItems(), function(i, item) {
                    if (!item.parent_item_id && !item.mirakl_shop_id) {
                        result = false;
                        return false;
                    }
                });

                return result;
            }
        };
    }
);
