/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([], function () {
    'use strict';

    return {
        /**
         * Get Extended URI params string
         *
         * @returns {string}
         */
        getExtendedParameterString: function () {
            var sellerId = this.getSellerId(),
                miraklQuoteId = this.getMiraklQuoteId(),
                parameterString = '';
            if (sellerId) {
                parameterString = '?quote-seller=' + sellerId;
            } else if (miraklQuoteId) {
                parameterString = '?mirakl-quote=' + miraklQuoteId;
            }

            return parameterString;
        },
        /**
         * Get extended params string in Magento Request format
         *
         * @returns {string}
         */
        getExtendedMagentoRequestString: function () {
            var sellerId = this.getSellerId(),
                miraklQuoteId = this.getMiraklQuoteId(),
                parameterString = '';
            if (sellerId) {
                parameterString = 'index/index/quote-seller/' + sellerId;
            } else if (miraklQuoteId) {
                parameterString = 'index/index/mirakl-quote/' + miraklQuoteId;
            }

            return parameterString;
        },
        /**
         * Add extra params to Checkout URL Config
         */
        updateCheckoutUrl: function() {
            if (window.checkoutConfig.checkoutUrl.search('quote-seller') == -1
                && window.checkoutConfig.checkoutUrl.search('mirakl-quote') == -1) {
                window.checkoutConfig.checkoutUrl = window.checkoutConfig.checkoutUrl
                    + this.getExtendedMagentoRequestString();
            }
        },
        /**
         * Get Quote Seller ID from URL
         *
         * @returns {int|null}
         */
        getSellerId: function () {
            var currentUrl = window.location.href.replace(window.location.hash, '').split('/'),
                result = null;
            currentUrl.forEach(function (value, index) {
                if (value === 'quote-seller' && currentUrl[index + 1]) {
                    result = parseInt(currentUrl[index + 1], 10);
                }
            });

            return result;
        },
        /**
         * Get Mirakl Quote ID from URL
         *
         * @returns {string|null}
         */
        getMiraklQuoteId: function () {
            var currentUrl = window.location.href.replace(window.location.hash, '').split('/'),
                result = null;
            currentUrl.forEach(function (value, index) {
                if (value === 'mirakl-quote' && currentUrl[index + 1]) {
                    result = encodeURIComponent(currentUrl[index + 1]);
                }
            });

            return result;
        }
    }
});
