/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'underscore',
    'escaper',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'jquery/jquery-storageapi'
], function ($, Component, customerData, _, escaper, modal, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            cookieMessages: [],
            messages: [],
            allMessages: [],
            allowedTags: ['div', 'span', 'b', 'strong', 'i', 'em', 'u', 'a']
        },

        /**
         * Extends Component object by storage observable messages.
         */
        initialize: function () {
            this._super();

            this.modalOptions = {
                type: 'popup',
                responsive: true,
                title: '',
                modalClass: 'thank-for-your-application',
            }

            var self = this;
            this.allCookieMessages = _.unique($.cookieStorage.get('mage-messages'), 'text');
            this.otherMessages = [];
            this.messages = customerData.get('messages').extend({
                disposableCustomerData: 'messages'
            });

            this.isClearMessages = false;

            try {
                var registeringApproved = false;
                var registering = false;
                var registeringRequiresApproval = false;
                var errorRegistering = false;

                this.allCookieMessages.forEach(function (entry) {
                    if (typeof (entry.view_type) !== "undefined" && entry.view_type !== false) {
                        if (entry.view_type === 'registering') {
                            registering = entry;
                        } else if (entry.view_type === 'registering_approved') {
                            registeringApproved = entry;
                        } else if (entry.view_type === 'registering_requires_approval') {
                            registeringRequiresApproval = entry;
                        } else if (entry.view_type === 'error_registering') {
                            errorRegistering = entry;
                        } else {
                            self.otherMessages.push(entry);
                        }
                    } else {
                        self.otherMessages.push(entry);
                    }
                });

                if (registeringRequiresApproval) {
                    self.showRegisteringRequiresApproval();
                } else if (registeringApproved) {
                    self.showRegisteringApproved(registeringApproved);
                } else if (registering) {
                    self.showRegistering();
                } else if (errorRegistering) {
                    self.showError(errorRegistering);
                } else {
                    self.cookieMessages = self.otherMessages;
                }
            } catch (err) {
                self.cookieMessages = this.allCookieMessages;
                console.log(err);
            }

            // Force to clean obsolete messages
            if (!_.isEmpty(self.messages().messages)) {
                customerData.set('messages', {});
            }

            $.cookieStorage.set('mage-messages', '');

            $(document).bind('ajax:addToCart', function (data) {
                self.isClearMessages = true;
            });
        },

        /**
         * Show Error
         * @param message
         */
        showError: function (message) {
            alert({
                title: $.mage.__('Login Note'),
                content: $.mage.__(this.deentitize(message.text)),
                modalClass: 'sign_up_alert',
                actions: {
                    always: function () {
                    }
                }
            });
        },

        /**
         * Show Registering Requires Approval
         */
        showRegisteringRequiresApproval: function () {
            var popup = modal(this.modalOptions, $('#thank-for-your-application'));
            $("#thank-for-your-application").modal('openModal');
        },

        /**
         * Show successful registration
         */
        showRegistering: function () {
            var popup = modal(this.modalOptions, $('#thank-for-your-application-auto'));
            $("#thank-for-your-application-auto").modal('openModal');
        },

        /**
         * Show Registering Approved
         * @param entry
         */
        showRegisteringApproved: function (entry) {
            var popup = modal(this.modalOptions, $('#thank-for-your-application-auto'));
            $('#thank-for-your-application-auto .thank-u-child-2').html('');
            $('#thank-for-your-application-auto .thank-u-child-2').html('<p>' + entry.text + '</p>');
            $("#thank-for-your-application-auto").modal('openModal');
        },

        deentitize: function (ret) {
            var ret = ret.replace(/&gt;/g, '>');
            ret = ret.replace(/&lt;/g, '<');
            ret = ret.replace(/&quot;/g, '"');
            ret = ret.replace(/&apos;/g, "'");
            ret = ret.replace(/&amp;/g, '&');
            return ret;
        },

        /**
         * Get All Messages
         * @param messages
         * @returns {*}
         */
        getAllMessages: function (messages) {
            var self = this;
            if (typeof (messages) !== "undefined" && messages.length) {
                messages.forEach(function (entry) {
                    if (typeof(entry.type) !== "undefined" && entry.type === 'clean' || self.isClearMessages) {
                        self.allMessages = [];
                        self.isClearMessages = false;
                    }
                    self.allMessages.push(entry);
                });

                const uniqueMessages = [];
                const map = new Map();
                self.allMessages.forEach(function (item) {
                    if (!map.has(item.text)) {
                        map.set(item.text, true);
                        uniqueMessages.push({
                            type: item.type,
                            text: item.text
                        });
                    }
                });

                self.allMessages = uniqueMessages;
            }

            return self.allMessages;
        },

        /**
         * Prepare the given message to be rendered as HTML
         *
         * @param {String} message
         * @return {String}
         */
        prepareMessageForHtml: function (message) {
            return escaper.escapeHtml(message, this.allowedTags);
        }
    });
});
