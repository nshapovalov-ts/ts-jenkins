/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function ($, _, Component, customerData) {
    'use strict';

    return Component.extend({

        /** @inheritDoc */
        initialize: function () {
            this._super();
            this.initWishlistProduct();
        },

        /**
         * Init Wish list Product
         *
         * @inheritDoc
         */
        initWishlistProduct: function () {
            this.wishlist = customerData.get('wishlist');
            if (this.wishlist().counter) {
                this.activeWishlist(this.wishlist().wishlist_item_ids);
            }
            this.wishlist.subscribe(function (value) {
                this.activeWishlist(value.wishlist_item_ids);
            }, this);
        },

        /**
         * Active Wishlist
         *
         * @param itemIds
         */
        activeWishlist: function (itemIds) {
            const self = this;
            $.each(itemIds, function (productId, id) {
                id = parseInt(id);
                let item = $(self.itemElement + productId);
                if (!item.length) {
                    return;
                }

                if (!id) {
                    item.addClass("allready-adedd-whilist");
                    return;
                }

                let postData = item.data('post');
                if (typeof (postData) !== "undefined" && typeof (postData.data) !== "undefined") {
                    let sellerid = postData.data.seller_id
                    if (typeof (sellerid) !== "undefined" && sellerid === id) {
                        item.addClass("allready-adedd-whilist");
                    }
                }
            })
        }
    });
});
