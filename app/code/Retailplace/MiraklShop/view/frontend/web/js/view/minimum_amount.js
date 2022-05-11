/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

define([
    'ko',
    'jquery',
    'underscore',
    'mage/translate',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'owlcarousel'
], function (ko, $, _, $t, Component, customerData, priceUtils) {
    'use strict';

    return Component.extend({
        priceFormat: {
            decimalSymbol: '.',
            groupLength: 3,
            groupSymbol: ",",
            integerRequired: false,
            pattern: "$%s",
            precision: 2,
            requiredPrecision: 2
        },
        popUpSettings: {
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                    dots: true,
                }, 480: {
                    items: 2,
                    nav: false,
                    dots: true,
                }, 768: {
                    items: 2
                }, 992: {
                    items: 3
                }, 1200: {
                    items: 3
                },
            },
            autoplay: false,
            loop: false,
            nav: true,
            navRewind: false,
            dots: false,
            autoplayHoverPause: true,
            margin: 0
        },
        minOrderAmountData: {
            description: '',
            percentClass: '',
            comment: ''
        },
        freeShippingAmountData: {
            shippingAmount: '',
            description: '',
            percentClass: '',
            comment: '',
            tooltip: ''
        },
        minQuoteRequestAmountData: {
            description: '',
            percentClass: '',
            comment: '',
            quoteRequestLink: '',
            isReached: false
        },
        productAddedToCart: false,

        initialize: function () {
            let self = this;
            this._super();
            this.cartData = customerData.get('cart');
            this.cartData.subscribe(function (updatedCart) {
                if (updatedCart.shop_amounts && updatedCart.shop_amounts[self.sellerShopId]) {
                    this.minOrderAmount = parseFloat(updatedCart.shop_amounts[self.sellerShopId].min_order_amount);
                    this.minFreeShippingOrderAmount = parseFloat(updatedCart.shop_amounts[self.sellerShopId].free_shipping_amount);
                    this.minQuoteRequestAmount = parseFloat(updatedCart.shop_amounts[self.sellerShopId].min_quote_amount);
                }

                if (self.productAddedToCart) {
                    setTimeout(function () {
                        self.showMinOrderPopup();
                    }, 3000);
                }
            });
            let addToCartForm = $('#product_addtocart_form');
            if (addToCartForm.length) {
                $(document).on('ajax:addToCart', function (e, data) {
                    var response = data.response;
                    self.productAddedToCart = false;
                    if (typeof(response.product) !== 'undefined' && response.product.sku) {
                        self.productAddedToCart = true;
                    }
                    let body = $("html, body");
                    body.stop().animate({scrollTop: 0}, 500, 'swing');
                });
            }
        },
        getMinQuoteRequestAmountData: function () {
            if (this.isReachedMinQuoteRequestAmount()) {
                this.minQuoteRequestAmountData.isReached = true;
                this.minQuoteRequestAmountData.quoteRequestLink = this.quoteRequestLink;
            } else {
                this.minQuoteRequestAmountData.isReached = false;
                if (!this.isSellerProductAdded()) {
                    this.minQuoteRequestAmountData.description = this.getMinimumQuoteRequestAmountRemaining() + ' ' + $t('minimum');
                    this.minQuoteRequestAmountData.comment = $t('quote request amount');
                } else {
                    this.minQuoteRequestAmountData.description = this.getMinimumQuoteRequestAmountRemaining() + ' ' + $t('left')
                    this.minQuoteRequestAmountData.comment = $t('to meet min quote request amount');
                }

            }
            this.minQuoteRequestAmountData.percentClass = this.getPercentAmountClass(this.getMinimumQuoteRequestAmountPercent());

            this.minQuoteRequestAmountData.isQuoteRequestBlockVisible =
                !!(this.isQuoteRequestBlockVisible && this.minQuoteRequestAmount);

            return this.minQuoteRequestAmountData;
        },
        getMinOrderAmountData: function () {
            if (this.hasMinOrderAmount()) {
                if (this.isSellerProductAdded() && this.isReachedMinOrderAmount()) {
                    this.minOrderAmountData.description = $t('Reached');
                    this.minOrderAmountData.comment = $t('Min order amount');
                }
                if (this.isSellerProductAdded() && !this.isReachedMinOrderAmount()) {
                    this.minOrderAmountData.description = this.getMinimumOrderAmountRemaining() + ' ' + $t('left');
                    this.minOrderAmountData.comment = $t('to meet min order amount');
                }
                if (!this.isSellerProductAdded()) {
                    this.minOrderAmountData.description = this.getMinimumOrderAmount() + ' ' + $t('minimum');
                    this.minOrderAmountData.comment = $t('order amount');
                }
            } else {
                this.minOrderAmountData.description = $t('No minimum');
                this.minOrderAmountData.comment = $t('order amount');
            }
            this.minOrderAmountData.percentClass = this.getPercentAmountClass(this.getMinimumOrderAmountPercent());
            return this.minOrderAmountData;
        },
        getFreeShippingAmountData: function () {
            this.freeShippingAmountData.tooltip = this.tooltipAdditionalText;
            this.freeShippingAmountData.shippingAmount = this.getMinimumFreeShippingAmount();
            if (this.isSellerProductAdded() && this.isReachedFreeShippingOrderAmount()) {
                this.freeShippingAmountData.description = $t('Free shipping');
                this.freeShippingAmountData.comment = $t('Order amount reached');
                this.freeShippingAmountData.tooltip = '';
            }
            if (this.isSellerProductAdded() && !this.isReachedFreeShippingOrderAmount()) {
                this.freeShippingAmountData.description = $t('Free shipping');
                this.freeShippingAmountData.comment = this.getMinimumFreeShippingAmountRemaining() + ' ' + $t('order amount left');
            }
            if (!this.isSellerProductAdded()) {
                this.freeShippingAmountData.description = $t('Free shipping');
                this.freeShippingAmountData.comment = $t('Above') + ' ' + this.getMinimumFreeShippingAmount() + ' ' + $t('order amount');
            }
            this.freeShippingAmountData.percentClass = this.getPercentAmountClass(this.getFreeShippingAmountPercent());
            return this.freeShippingAmountData;
        },
        getMinimumOrderAmount: function () {
            return priceUtils.formatPrice(this.minOrderAmount, this.priceFormat);
        },
        getMinimumFreeShippingAmount: function () {
            return priceUtils.formatPrice(this.minFreeShippingOrderAmount, this.priceFormat);
        },
        isReachedMinOrderAmount: function () {
            let isReached = false;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                isReached = this.cartData().shop_amounts[this.sellerShopId].is_min_order_amount_reached;
            }
            return isReached;
        },
        isReachedMinQuoteRequestAmount: function () {
            let isReached = false;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                isReached = this.cartData().shop_amounts[this.sellerShopId].is_min_quote_amount_reached;
            }
            return isReached;
        },
        isReachedFreeShippingOrderAmount: function () {
            let isReached = false;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                isReached = this.cartData().shop_amounts[this.sellerShopId].is_free_shipping_amount_reached;
            }
            return isReached;
        },
        getMinimumOrderAmountPercent: function () {
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                return this.cartData().shop_amounts[this.sellerShopId].min_order_amount_percent;
            }
            return false;
        },
        getMinimumQuoteRequestAmountPercent: function () {
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                return this.cartData().shop_amounts[this.sellerShopId].min_quote_amount_percent;
            }
            return false;
        },
        getFreeShippingAmountPercent: function () {
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                return this.cartData().shop_amounts[this.sellerShopId].free_shipping_amount_percent;
            }
            return false;
        },
        isCurrentProductAdded: function () {
            let result = false,
                self = this;
            if (this.currentProductId) {
                this.cartData().items.forEach(function (item) {
                    if (item['product_id'] == self.currentProductId) {
                        result = true;
                    }
                });
            } else {
                result = this.isSellerProductAdded();
            }

            return result;
        },
        isSellerProductAdded: function () {
            return !!(this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]);
        },
        hasMinOrderAmount: function () {
            return !!this.minOrderAmount;
        },
        hasFreeshippingOrderAmount: function() {
            return !!this.minFreeShippingOrderAmount;
        },
        getMinimumOrderAmountRemaining: function () {
            let amountRemaining = this.minOrderAmount;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                amountRemaining = this.cartData().shop_amounts[this.sellerShopId].min_order_amount_remaining;
            }

            return priceUtils.formatPrice(amountRemaining, this.priceFormat);
        },
        getMinimumQuoteRequestAmountRemaining: function () {
            let amountRemaining = this.minQuoteRequestAmount;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                amountRemaining = this.cartData().shop_amounts[this.sellerShopId].min_quote_amount_remaining;
            }

            return priceUtils.formatPrice(amountRemaining, this.priceFormat);
        },
        getMinimumFreeShippingAmountRemaining: function () {
            let amountRemaining = this.minFreeShippingOrderAmount;
            if (this.cartData().shop_amounts && this.cartData().shop_amounts[this.sellerShopId]) {
                amountRemaining = this.cartData().shop_amounts[this.sellerShopId].free_shipping_amount_remaining;
            }

            return priceUtils.formatPrice(amountRemaining, this.priceFormat);
        },
        getMinimumOrderAmountRemainingPdp: function () {
            if (this.isSellerProductAdded()) {
                return this.getMinimumOrderAmountRemaining();
            } else {
                return priceUtils.formatPrice(this.minOrderAmount, this.priceFormat);
            }
        },
        getPercentAmountClass: function (percent) {
            let dd = 'p' + percent + ' ';
            return dd + 'c100 big orange';
        },
        hasFreeShipping: function () {
            return this.minFreeShippingOrderAmount;
        },
        isAllFreeShipping: function () {
            return this.isFreeShipping;
        },
        addCarousel: function () {
            $(".pdpseller_pop_pro_slider").owlCarousel(this.popUpSettings);
        },
        isShowTooltip: function () {
            return this.isTooltip;
        },
        showMinOrderPopup: function () {
            if (!this.isReachedMinOrderAmount()) {
                $('#lightpdpseller').addClass("pdpseller_open");
                $('#fadeseller').addClass("pdpseller_open");
            }
        }
    })
})
