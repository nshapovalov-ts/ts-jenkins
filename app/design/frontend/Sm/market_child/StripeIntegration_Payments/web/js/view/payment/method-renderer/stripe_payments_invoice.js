/*
 * StripeIntegration_Payments
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'StripeIntegration_Payments/js/view/payment/method-renderer/stripe_payments',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/payment-service'
    ],
    function (
        $,
        ComponentStripePayments,
        urlBuilder,
        storage,
        errorProcessor,
        customer,
        fullScreenLoader,
        getPaymentInformation,
        paymentService
    ) {
        'use strict';

        return ComponentStripePayments.extend({
            externalRedirectUrl: null,
            defaults: {
                template: {
                    name: 'StripeIntegration_Payments/payment/form',
                    afterRender: function (renderedNodesArray, data) {
                        if (data.config().lastMessage) {
                            data.showError(data.config().lastMessage);
                            data.config().lastMessage = null;
                        }
                    }
                },
                stripePaymentsCardSave: false,
                stripePaymentsShowApplePaySection: false
            },

            getDefCode: function () {
                return 'stripe_payments';
            },

            getCode: function () {
                return 'stripe_payments_invoice';
            },

            getDaysDue: function () {
                return window.checkoutConfig.payment[this.getCode()].days_due;
            },

            getTermsAndConditionsLink: function () {
                return window.checkoutConfig.payment[this.getCode()].terms_and_conditions;
            },

            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].frontend_title;
            },

            getFrontendDescription: function () {
                return window.checkoutConfig.payment[this.getCode()].frontend_description;
            },

            config: function () {
                return window.checkoutConfig.payment[this.getDefCode()];
            },

            getCcMonths: function () {
                return window.checkoutConfig.payment[this.getDefCode()].months;
            },

            getCcYears: function () {
                return window.checkoutConfig.payment[this.getDefCode()].years;
            },

            getCvvImageUrl: function () {
                return window.checkoutConfig.payment[this.getDefCode()].cvvImageUrl;
            },

            handlePlaceOrderErrors: function (result) {
                if (this.isRequestError(result.responseJSON.message)) {
                    this.reloadSavedCards(result.responseJSON.message);
                    return;
                }

                this._super(result);
            },

            isRequestError: function (msg) {
                // 500 server side errors
                if (typeof msg == "undefined") {
                    return false;
                }

                // Case of subscriptions
                if (msg.indexOf("Charge is declined") === 0) {
                    return true;
                }

                return false;
            },

            reloadSavedCards: function (errorMessage) {
                const self = this;
                if (customer.isLoggedIn()) {
                    fullScreenLoader.startLoader();
                    paymentService.setPaymentMethods([]);
                    let serviceUrl = urlBuilder.createUrl('/carts/mine/stripe/cards', {});

                    return storage.get(
                        serviceUrl,
                        false
                    ).done(function (response) {
                        const cards = [];
                        $.each(response.cards, function (key, value) {
                            cards[key] = JSON.parse(value);
                        });

                        if (cards) {
                            window.checkoutConfig.payment.stripe_payments.savedCards = cards
                        }

                        getPaymentInformation().done(function () {
                            self.config().lastMessage = errorMessage
                            fullScreenLoader.stopLoader();
                        });

                    }).fail(function (response) {
                        errorProcessor.process(response, self.messageContainer);
                        fullScreenLoader.stopLoader()
                    });
                }
            }
        });
    }
);
