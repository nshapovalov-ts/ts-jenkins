/*
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

define(['Magento_Checkout/js/model/quote'], function (quote) {
    'use strict';
    return function (stripePayments) {
        return stripePayments.extend({
            initialize: function () {
                this._super();
                if (this.hasSavedCards()) {
                    let pm = this.config().savedCards[0];
                    let name = (pm.id + ':' + pm.brand + ':' + pm.last4)
                    this.stripePaymentsSelectedCard(name);
                }
            },

            /**
             * Check Visible Method
             *
             * @returns {boolean}
             */
            checkVisibleMethod: function () {
                var isChecked = this.isChecked();
                if (isChecked === "undefined" || isChecked === null || isChecked.indexOf('stripe_payments') === -1) {
                    return true;
                }

                return this.getCode() === isChecked;
            },

            /**
             * Get CC Description
             *
             * @returns {string|*|string}
             */
            getCCDescription: function () {
                let title = "";

                if (this.getCode() !== "stripe_payments") {
                    return title;
                }

                if (quote === null) {
                    return title;
                }

                let totals = quote.totals();
                let extensionAttributes = totals.extension_attributes;

                if (typeof (extensionAttributes) === "undefined") {
                    return title;
                }

                let info = extensionAttributes.stripe_payment_info;

                if (typeof(info) === "undefined") {
                    return title;
                }

                let available = info.available;
                let duty = info.duty;

                let description = '';
                let descriptionV2 = this.getCCDescriptionV2();
                let descriptionV1 = this.getCCDescriptionV1();

                if (duty > 0) {
                    if (typeof (descriptionV2) === "undefined" || descriptionV2 === "") {
                        return title;
                    }

                    description = this.prepareDescription(descriptionV2, '%limit%', available);
                    title = this.prepareDescription(description, '%duty%', duty);
                } else {
                    if (typeof (descriptionV1) === "undefined" || descriptionV1 === "") {
                        return title;
                    }
                    title = this.prepareDescription(descriptionV1, '%limit%', available);
                }
                return typeof (title) !== "undefined" && title !== "" ? title : "";
            },

            /**
             * Prepare Description
             *
             * @param str
             * @param find
             * @param replace
             * @returns {*}
             */
            prepareDescription: function (str, find, replace) {
                var escapedFind = find.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
                return str.replace(new RegExp(escapedFind, 'g'), replace);
            },

            /**
             * Get CC Description V2
             *
             * @returns {boolean|*}
             */
            getCCDescriptionV1: function () {
                let payment = window.checkoutConfig.payment[this.getCode()];
                if (typeof (payment) !== "undefined" && typeof (payment.cc_description_v1) !== "undefined") {
                    return payment.cc_description_v1;
                }

                return false;
            },

            /**
             * Get CC Description V2
             *
             * @returns {boolean|*}
             */
            getCCDescriptionV2: function () {
                let payment = window.checkoutConfig.payment[this.getCode()];
                if (typeof (payment) !== "undefined" && typeof (payment.cc_description_v2) !== "undefined") {
                    return payment.cc_description_v2;
                }

                return false;
            },

            /**
             * Get Save Card Name
             *
             * @returns {string}
             */
            getSaveCardName: function () {
                return this.getCode() + "_payment[cc_saved]";
            },

            /**
             * Get Selected Saved Card
             *
             * @returns {null|*}
             */
            getSelectedSavedCard: function () {
                const elements = document.getElementsByName(this.getSaveCardName());
                if (elements.length === 0) {
                    return null;
                }

                let selected = null;
                for (let i = 0; i < elements.length; i++) {
                    if (elements[i].checked) {
                        selected = elements[i];
                    }
                }

                if (!selected) {
                    return null;
                }

                if (selected.value === 'new_card') {
                    return null;
                }

                return selected.value;
            }

        });
    };
});
