/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/url',
    'mage/translate',
    'mage/cookies'
    ], function ($, Component, ko, urlBuilder, $t) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Retailplace_MiraklQuote/quote-view'
            },
            quote: ko.observable({}),
            miraklQuoteRequest: ko.observable({}),
            miraklShopQuoteRequest: ko.observable({}),
            miraklMessages: ko.observableArray([]),
            miraklData: ko.observable(),
            messengerError: ko.observable(),
            pageErrors: ko.observableArray([]),
            formErrors: ko.observableArray([]),
            currentStep: 0,
            /**
             * Init Component
             *
             * @param {Object} config
             */
            initialize: function (config) {
                this._super();
                this.getQuoteById(config.quoteRequestId);
            },
            /**
             * Get Mirakl Quote Data
             *
             * @param {string} quoteRequestId
             */
            getQuoteById: function(quoteRequestId) {
                var self = this;

                self.pageErrors([]);
                $('body').trigger('processStart');
                $.ajax({
                    url: urlBuilder.build('quotes/actions/viewPost'),
                    type: 'POST',
                    data: {id: quoteRequestId},
                    dataType: 'json',
                    success: function (data) {
                        var responseBody = data.response;
                        if (data.is_success) {
                            self.currentStep = responseBody['mirakl_quote_request']['step'];
                            self.miraklShopQuoteRequest(responseBody['mirakl_quote_request']['shop_quote_requests'][0]);
                            self.miraklQuoteRequest(responseBody['mirakl_quote_request']);
                            self.quote(responseBody['quote']);
                            self.getMessages(responseBody);
                        } else {
                            self.pageErrors.push($t('Quote Getting Error'));
                        }
                    },
                    error: function () {
                        self.pageErrors.push($t('Quote Getting Error'));
                    },
                    complete: function() {
                        $('body').trigger('processStop');
                    }
                });
            },
            /**
             * Render Messages
             *
             * @param {Object} responseBody
             */
            getMessages: function(responseBody) {
                var self = this;

                if (self.miraklShopQuoteRequest().messages.length) {
                    $.each(self.miraklShopQuoteRequest().messages, function (key, message) {
                        self.miraklMessages.push(message);
                    });
                }
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
            },
            /**
             * Message Sending Init
             *
             * @param {Element} form
             */
            sendMessage: function(form) {
                var self = this,
                    data = $(form).serializeArray();
                data.push({name: 'form_key', value: $.mage.cookies.get('form_key')});
                self.formErrors([]);
                $('body').trigger('processStart');

                $.ajax({
                    url: urlBuilder.build('quotes/actions/newMessagePost'),
                    type: 'POST',
                    data: $.param(data),
                    dataType: 'json',
                    success: function (data) {
                        if (data.is_success) {
                            var message = {
                                body: data.response.message,
                                to: $(form).data('shop-name'),
                                date_created_formatted: data.response.date_created_formatted,
                                from: $(form).data('firstname') + ' ' + $(form).data('lastname'),
                                direction: 'outbound'
                            };
                            form['message'].value = '';
                            self.miraklMessages.unshift(message);
                        } else {
                            self.formErrors.push(data.response);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        self.formErrors.push($t('Unable to send Message'));
                    },
                    complete: function() {
                        $('body').trigger('processStop');
                    }
                });
            },
            /**
             * Auto Scroll to Quote Block after render
             *
             * @param {Element} quoteBlock
             */
            scrollToQuote: function(quoteBlock) {
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $(quoteBlock).offset().top
                    }, 200);
                }, 10);
            }
        });
    }
);
