/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define(['jquery', 'uiComponent', 'ko', 'mage/url', 'mage/translate'], function ($, Component, ko, urlBuilder, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Retailplace_MiraklQuote/quotes-listing'
            },
            miraklQuoteRequests: ko.observableArray([]),
            pagination: ko.observable(''),
            pageErrors: ko.observableArray([]),
            /**
             * Init Component
             *
             * @param {Object} config
             */
            initialize: function (config) {
                this._super();
                this.getQuotesForCustomer(config.page);
            },
            /**
             * Load Mirakl Quotes Collection
             *
             * @param {number} page
             * @param {boolean} isAjax
             */
            getQuotesForCustomer: function(page, isAjax = false) {
                var self = this,
                    data = page ? {p: page} : {};

                self.pageErrors([]);
                $('body').trigger('processStart');
                $.ajax({
                    url: urlBuilder.build('quotes/actions/listingPost'),
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (data) {
                        var responseBody = data.response;
                        self.miraklQuoteRequests([]);
                        if (data.is_success) {
                            ko.utils.arrayPushAll(self.miraklQuoteRequests, responseBody.quoteRequests);
                            self.pagination(responseBody.pagination);
                            self.scrollToListing(isAjax);
                        } else {
                            self.pageErrors.push(responseBody);
                        }
                    },
                    error: function () {
                        self.pageErrors.push($t('Unable to get Quotes List'));
                    },
                    complete: function() {
                        $('body').trigger('processStop');
                    }
                });
            },
            /**
             * Parse Page Param from URL
             *
             * @param {string} url
             * @returns {number}
             */
            parsePageParam: function(url) {
                var result = 1,
                    queryString = url.replace(window.location.hash, '').split('?')[1];
                queryString.split('&').forEach(function (value) {
                    value = value.split('=');
                    if (value[0] === 'p') {
                        result = parseInt(value[1], 10);
                    }
                });

                return result;
            },
            /**
             * Init Ajax Pagination
             *
             * @param {Element} element
             */
            ajaxPaginationInit: function(element) {
                var self = this;
                $(element).on('click', 'a', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
                    window.history.pushState({url: url},'', url);
                    self.getQuotesForCustomer(self.parsePageParam(url), true);
                });
            },
            /**
             * Scroll to Listing Top
             *
             * @param {boolean} isAjax
             */
            scrollToListing: function(isAjax) {
                if (isAjax) {
                    window.location.hash = 'quotes-listing';
                }
            }
        });
    }
);
