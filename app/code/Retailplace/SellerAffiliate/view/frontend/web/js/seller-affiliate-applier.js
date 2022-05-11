/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'mage/cookies'
], function ($, Component, urlBuilder) {
    'use strict';

    return Component.extend({
        graphqlPath: 'graphql',
        
        /**
         * Init Component
         *
         * @param {Object} config
         */
        initialize: function (config) {
            let self = this;
            this._super(config);
            this.initConfig(config);
            this.appendAffiliate();
        },
        /**
         * Init Config Values
         *
         * @param {Object} config
         */
        initConfig: function(config) {
            this.affiliatePrefix = config.affiliatePrefix;
            this.affiliateCookieName = config.affiliateCookieName;
            this.affiliateCookieLifetime = config.affiliateCookieLifetime;
        },
        /**
         * Append Affiliate
         */
        appendAffiliate: function() {
            let sellerId = this.getSellerId(),
                clientDateTime = this.getClientDateTime(),
                currentUrl = window.location.href;

            if (sellerId) {
                this.sendAffiliateRequest(sellerId, clientDateTime, currentUrl);
            }
        },
        /**
         * Get Seller ID from Affiliate Code
         *
         * @returns {string|null}
         */
        getSellerId: function() {
            let affiliateKey = window.location.hash.replace('#', ''),
                sellerId = null;

            if (affiliateKey.indexOf(this.affiliatePrefix) === 0) {
                sellerId = affiliateKey.substring(this.affiliatePrefix.length);
            }

            return sellerId;
        },
        /**
         * Get Customer Time in MySQL format
         *
         * @returns {string}
         */
        getClientDateTime: function() {
            let date = new Date();

            return date.getFullYear() + '-' +
                ('00' + (date.getMonth() + 1)).slice(-2) + '-' +
                ('00' + date.getDate()).slice(-2) + ' ' +
                ('00' + date.getHours()).slice(-2) + ':' +
                ('00' + date.getMinutes()).slice(-2) + ':' +
                ('00' + date.getSeconds()).slice(-2);
        },
        /**
         * Send Request for new Affiliate adding
         *
         * @param {string} sellerId
         * @param {string} clientDateTime
         * @param {string} currentUrl
         */
        sendAffiliateRequest: function(sellerId, clientDateTime, currentUrl) {
            let self = this;

            $.post({
                url: urlBuilder.build('graphql'),
                data: JSON.stringify({
                    query: self.getCreateShopAffiliateQuery(sellerId, currentUrl, clientDateTime)
                }),
                contentType: 'application/json'
            })
                .success(function (response) {
                    let createShopAffiliate = response.data.createShopAffiliate,
                        isCustomerLoggerIn = false;
                    if (createShopAffiliate) {
                        isCustomerLoggerIn = createShopAffiliate['is_logged_in'];
                    }
                    if (!isCustomerLoggerIn) {
                        self.addAffiliateCookies(sellerId, clientDateTime, currentUrl);
                    }
                })
                .error(function() {
                    self.addAffiliateCookies(sellerId, clientDateTime, currentUrl);
                })
                .complete(function() {
                    window.location.hash = '';
                });
        },
        /**
         * Generate Graphql Query
         *
         * @param {string} sellerId
         * @param {string} currentUrl
         * @param {string} clientDateTime
         * @returns {Object}
         */
        getCreateShopAffiliateQuery: function(sellerId, currentUrl, clientDateTime) {
            return `mutation {
                      createShopAffiliate(
                        input: {
                          seller_id: "${sellerId}",
                          affiliate_url: "${currentUrl}",
                          clientside_datetime: "${clientDateTime}",
                        }
                      ) {
                        is_logged_in
                      }
                    }`;
        },
        /**
         * Add Affiliate Cookies
         *
         * @param {string} sellerId
         * @param {string} clientDateTime
         * @param {string} currentUrl
         */
        addAffiliateCookies: function(sellerId, clientDateTime, currentUrl) {
            let sellerObjectsArray = this.getSellerObjectsArray(),
                isAddNewCookie = true;

            sellerObjectsArray.forEach(function(value) {
                if (value['seller_id'] === sellerId) {
                    isAddNewCookie = false;
                }
            });

            if (isAddNewCookie) {
                let sellerObject = {seller_id: sellerId, current_date: clientDateTime, current_url: currentUrl};
                sellerObjectsArray.push(sellerObject);
                $.mage.cookies.set(
                    this.affiliateCookieName,
                    JSON.stringify(sellerObjectsArray),
                    {
                        lifetime: this.affiliateCookieLifetime
                    }
                );
            }
        },
        /**
         * Get current Affiliate objects from Cookie
         *
         * @returns {Object[]}
         */
        getSellerObjectsArray: function() {
            let existingSellerIds = $.mage.cookies.get(this.affiliateCookieName),
                sellerObjectsArray = [];
            if (existingSellerIds) {
                try {
                    sellerObjectsArray = JSON.parse(existingSellerIds);
                } catch (e) {
                    sellerObjectsArray = [];
                }
            }

            return sellerObjectsArray;
        }
    })
});
