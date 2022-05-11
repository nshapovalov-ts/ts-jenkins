/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'mage/utils/wrapper',
    'multiQuoteManagement'
], function (
    wrapper,
    multiQuoteManagement
) {
    'use strict';

    /**
     * Override Cart-related Endpoint URLs to add extra params
     */
    return function (resourceUrlManager) {
        resourceUrlManager.getUrlForTotalsEstimationForNewAddress = wrapper.wrapSuper(
            resourceUrlManager.getUrlForTotalsEstimationForNewAddress,
            function(quote) {
                var params = this.getCheckoutMethod() == 'guest' ?
                        {
                            cartId: quote.getQuoteId()
                        } : {},
                    urls = {
                        'guest': '/guest-carts/:cartId/totals-information',
                        'customer': '/carts/mine/totals-information' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId = wrapper.wrapSuper(
            resourceUrlManager.getUrlForEstimationShippingMethodsByAddressId,
            function(quote) {
                var params = this.getCheckoutMethod() == 'guest' ?
                    {
                        quoteId: quote.getQuoteId()
                    } : {},
                urls = {
                    'default': '/carts/mine/estimate-shipping-methods-by-address-id' + multiQuoteManagement.getExtendedParameterString()
                };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getUrlForSetShippingInformation = wrapper.wrapSuper(
            resourceUrlManager.getUrlForSetShippingInformation,
            function(quote) {
                var params = this.getCheckoutMethod() == 'guest' ?
                    {
                        cartId: quote.getQuoteId()
                    } : {},
                    urls = {
                        'guest': '/guest-carts/:cartId/shipping-information',
                        'customer': '/carts/mine/shipping-information' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress = wrapper.wrapSuper(
            resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress,
            function(quote) {
                var params = this.getCheckoutMethod() == 'guest' ?
                    {
                        cartId: quote.getQuoteId()
                    } : {},
                    urls = {
                        'guest': '/guest-carts/:quoteId/estimate-shipping-methods',
                        'customer': '/carts/mine/estimate-shipping-methods' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getUrlForCartTotals = wrapper.wrapSuper(
            resourceUrlManager.getUrlForCartTotals,
            function(quote) {
                var params = this.getCheckoutMethod() == 'guest' ?
                    {
                        quoteId: quote.getQuoteId()
                    } : {},
                    urls = {
                        'guest': '/guest-carts/:quoteId/totals',
                        'customer': '/carts/mine/totals' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getApplyCouponUrl = wrapper.wrapSuper(
            resourceUrlManager.getApplyCouponUrl,
            function(couponCode, quoteId) {
                var params = this.getCheckoutMethod() == 'guest' ?
                        {
                            quoteId: quoteId
                        } : {},
                    urls = {
                        'guest': '/guest-carts/' + quoteId + '/coupons/' + encodeURIComponent(couponCode),
                        'customer': '/carts/mine/coupons/' + encodeURIComponent(couponCode) + '/' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        resourceUrlManager.getCancelCouponUrl = wrapper.wrapSuper(
            resourceUrlManager.getCancelCouponUrl,
            function(quoteId) {
                var params = this.getCheckoutMethod() == 'guest' ?
                        {
                            quoteId: quoteId
                        } : {},
                    urls = {
                        'guest': '/guest-carts/' + quoteId + '/coupons/',
                        'customer': '/carts/mine/coupons/' + multiQuoteManagement.getExtendedParameterString()
                    };

                return this.getUrl(urls, params);
            }
        );

        return resourceUrlManager;
    }
});
