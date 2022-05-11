/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/action/get-totals',
    'Magento_SalesRule/js/model/coupon'
], function ($, wrapper, quote, messageContainer, setPaymentInformationAction, getTotalsAction, coupon) {
    'use strict';

    return function (selectPaymentMethodAction) {

        return wrapper.wrap(selectPaymentMethodAction, function (originalSelectPaymentMethodAction, paymentMethod) {

            originalSelectPaymentMethodAction(paymentMethod);
            if (paymentMethod === null) {
                return;
            }

            if (paymentMethod.method === 'stripe_payments_invoice'
                || paymentMethod.method === 'stripe_payments') {
                $('.stripe-payments-card-number div.__PrivateStripeElement').appendTo('#stripe-payments-card-number.stripe-payments-card-number');
                $('.stripe-payments-card-expiry div.__PrivateStripeElement').appendTo('#stripe-payments-card-expiry.stripe-payments-card-expiry');
                $('.stripe-payments-card-cvc div.__PrivateStripeElement').appendTo('#stripe-payments-card-cvc.stripe-payments-card-cvc');
            }

            $.when(
                setPaymentInformationAction(
                    messageContainer,
                    {
                        method: paymentMethod.method
                    }
                )
            ).done(
                function () {
                    var deferred = $.Deferred(),

                        /**
                         * Update coupon form.
                         */
                        updateCouponCallback = function () {
                            if (quote.totals() && !quote.totals()['coupon_code']) {
                                coupon.setCouponCode('');
                                coupon.setIsApplied(false);
                            }
                        };

                    getTotalsAction([], deferred);
                    $.when(deferred).done(updateCouponCallback);
                }
            );
        });
    };

});
