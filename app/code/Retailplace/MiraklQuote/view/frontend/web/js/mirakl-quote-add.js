/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define(['jquery', 'uiComponent', 'ko', 'mage/url', 'mage/translate', 'mage/cookies'], function ($, Component, ko, urlBuilder, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Retailplace_MiraklQuote/quote-add'
            },
            lineCollection: ko.observableArray([]),
            miraklShop: ko.observable({}),
            currentStep: 1,
            pageErrors: ko.observableArray([]),
            formErrors: ko.observableArray([]),
            /**
             * Init Component
             *
             * @param {Object} config
             */
            initialize: function (config) {
                this._super();
                this.sellerId = config.sellerId;
                this.getQuoteLineCollection(this.sellerId);
            },
            /**
             * Render Quote Items
             *
             * @param {string} sellerId
             */
            getQuoteLineCollection: function(sellerId) {
                var self = this;
                self.pageErrors([]);

                $('body').trigger('processStart');
                $.ajax({
                    url: urlBuilder.build('quotes/actions/addRenderPost'),
                    type: 'POST',
                    data: {seller_id: sellerId},
                    dataType: 'json',
                    success: function (data) {
                        var responseBody = data.response;
                        if (data.is_success) {
                            ko.utils.arrayPushAll(self.lineCollection, responseBody['quote_line_collection']);
                            self.miraklShop(responseBody['shop']);
                        } else {
                            self.pageErrors.push(responseBody);
                        }
                    },
                    error: function (request, error) {
                        self.pageErrors.push($t('Unable to create Quote Request'));
                    },
                    complete: function() {
                        $('body').trigger('processStop');
                    }
                });
            },
            /**
             * Init Quote Sending Form
             *
             * @param {Element} form
             */
            formInit: function (form) {
                var self = this;

                $(form).on('submit', function (e) {
                    self.formErrors([]);
                    if ($(this).valid()) {
                        e.preventDefault();
                        $('body').trigger('processStart');
                        $.ajax({
                            url: urlBuilder.build('quotes/actions/addPost'),
                            type: 'POST',
                            data: {
                                seller_id: self.sellerId,
                                quote_message: form['quote_message'].value,
                                form_key: $.mage.cookies.get('form_key')
                            },
                            dataType: 'json',
                            success: function (data) {
                                var responseBody = data.response;
                                if (data.is_success) {
                                    self.formErrors([]);
                                    window.location.href = urlBuilder.build(
                                        'quotes/actions/view/id/'
                                        + responseBody.id
                                        + '-' + self.sellerId
                                    );
                                } else {
                                    responseBody.error.forEach(function(error) {
                                        self.formErrors.push(error.field + ': ' + error.message);
                                    });
                                }
                            },
                            error: function (request, error) {
                                self.formErrors.push($t('Quote Sending Error'));
                            },
                            complete: function() {
                                $('body').trigger('processStop');
                            }
                        });
                    }
                });
            },
            /**
             * Get State Step Class Name
             *
             * @param {number} blockStep
             * @returns {string}
             */
            getStepClass: function(blockStep) {
                var className = 'progress-step';
                if (this.currentStep > blockStep) {
                    className += ' progress-done'
                } else if (this.currentStep == blockStep) {
                    className += ' progress-active'
                }

                return className;
            }
        });
    }
);
