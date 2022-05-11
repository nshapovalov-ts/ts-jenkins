/*
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */
define([
    'jquery',
    'Magento_Ui/js/model/messageList',
], function($, globalMessages) {
    'use strict';
    return function(target) {
        return target.extend({
            initialize: function (config, messageContainer) {
                this._super()
                    .initObservable();
                this.config = config;
                this.messageContainer = messageContainer || config.messageContainer || globalMessages;

                return this;
            },
            onHiddenChange: function (isHidden) {
                var self = this;
                if (!this.isStripeMessage() && isHidden) {
                    setTimeout(function () {
                        $(self.selector).hide('blind', {}, 500);
                    }, 5000);
                }
            },
            isStripeMessage: function () {
                return this.config.index.indexOf('stripe_payments') !== -1;
            }
        });
    };
});
