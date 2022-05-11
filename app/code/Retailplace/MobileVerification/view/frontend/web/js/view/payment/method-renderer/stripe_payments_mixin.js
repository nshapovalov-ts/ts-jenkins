/*
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Retailplace_MobileVerification/js/view/single-digit-input',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/modal'
], function ($, url, $t, singleDigitInput, quote) {
    'use strict';
    return function (stripePayments) {
        return stripePayments.extend({
            /**
             * initialize
             */
            initialize: function () {
                this._super();
                this.step = 1;
                this.modalStatus = false;
                this.firstOpen = true;
                this.isProcess = false;
                this.sendCodeUrl = url.build('/mobile_verification/ajax/sendOtp', {});
                this.validateCodeUrl = url.build('/mobile_verification/ajax/validateOtp', {});
                this.popupInitialised = false;
                this.ajaxRequestPending = false;
                this.phone = "";
            },

            /**
             * Init Verification Popup
             */
            initVerificationPopup: function () {
                if (this.popupInitialised) {
                    return;
                }

                let options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: false,
                    modalClass: 'verification_phone_number_popup'
                };

                this.modal = $('#verification_phone_number').modal(options);

                this.modal.on('modalclosed', function () {
                    this.modalStatus = false;
                });

                this.modal.on('modalopen', function () {
                    this.modalStatus = true;
                });

                let self = this;
                let phoneUpdateCallback = self.checkForm.bind(self);
                let codeUpdateCallback = function () {
                    let verifyInputs = $('#verification_phone_number .verification_code');
                    let verifyCode = singleDigitInput.collectValue(verifyInputs);

                    if (verifyCode.length === 4) {
                        verifyCode = self.verifyCodeFilter(verifyCode);
                        self.checkCode(verifyCode, self.checkCodeCallback.bind(self))
                    }
                };

                $('.verification_phone_number_popup .modal-header, .verification_phone_number_popup .modal-footer').hide();

                $('#verification_phone_number > .modal_close_icon').on('click', function () {
                    self.modal.modal('closeModal');
                    $('#verification_phone_number .modal_inner_content_step_1, #verification_phone_number .modal_inner_content_step_2').hide();
                });

                $("#verification_phone_number .number_phone").on("keyup", function (event) {
                    return singleDigitInput.onKeyUp(event, phoneUpdateCallback);
                }).on("keydown", singleDigitInput.onKeyDown.bind(singleDigitInput));

                $("#verification_phone_number .number_phone input").on("paste", function (event) {
                    return singleDigitInput.onPaste(event, phoneUpdateCallback);
                });

                $("#verification_phone_number .send_button, #verification_phone_number .resend_verify_code").on("click", function (event) {
                    let phoneInputs = $('#verification_phone_number .number_phone');
                    let phone = singleDigitInput.collectValue(phoneInputs);

                    self.cleanError();
                    self.sendCode(phone, self.sendCodeCallback.bind(self))
                });

                $("#verification_phone_number .change_phone_number").on("click", function (event) {
                    self.step = 1;
                    self.updatePopupStep();
                });

                $("#verification_phone_number .verification_code").on("keyup", function (event) {
                    return singleDigitInput.onKeyUp(event, codeUpdateCallback);
                }).on("keydown", singleDigitInput.onKeyDown.bind(singleDigitInput));

                $("#verification_phone_number .verification_code input").on("paste", function (event) {
                    return singleDigitInput.onPaste(event, codeUpdateCallback);
                });

                this.popupInitialised = true;
            },

            /**
             * is Customer Phone Number Confirmed
             *
             * @returns {boolean|*}
             */
            isCustomerPhoneNumberConfirmed: function () {
                let status = true;

                if (this.getCode() !== "stripe_payments_invoice") {
                    return status;
                }

                let payment = window.checkoutConfig.payment[this.getCode()];
                if (typeof (payment) !== "undefined" && typeof (payment.customer_phone_number_confirmed) !== "undefined") {
                    status = payment.customer_phone_number_confirmed;
                }

                return status;
            },

            /**
             * Clean Error
             */
            cleanError: function () {
                $('.modal_inner_content_step_1 .message.message-error, .modal_inner_content_step_2 .message.message-error').hide();
                $('.modal_inner_content_step_1 .message.message-error > div, .modal_inner_content_step_2 .message.message-error > div').text("");
            },

            /**
             * showErrorMessage
             * @param message
             */
            showErrorMessage: function (message) {
                $('#verification_phone_number .modal_inner_content_step_' + this.step + ' .message.message-error > div').text(message);
                $('#verification_phone_number .modal_inner_content_step_' + this.step + ' .message.message-error').show();
            },

            /**
             * Check Form
             */
            checkForm: function () {
                this.cleanError();

                let phoneInputs = $('#verification_phone_number .number_phone');
                let phone = singleDigitInput.collectValue(phoneInputs);

                //send verify code of current phone, is valid for first popup open
                if (this.firstOpen) {
                    if (!this.validatePhone(phone)) {
                        if (window.customerData && window.customerData.custom_attributes.phone_number !== undefined) {
                            phone = window.customerData.custom_attributes.phone_number.value;
                            phone = this.phoneNumberFilter(phone);
                        }
                    }
                    singleDigitInput.splitValue(phoneInputs, phone)

                    if (this.validatePhone(phone)) {
                        this.sendCode(phone, this.sendCodeCallback.bind(this))
                    }
                    this.firstOpen = false;
                    return;
                }

                //update form
                if (this.step === 1) {
                    if (this.validatePhone(phone)) {
                        $('#verification_phone_number .send_button').show();
                    } else {
                        $('#verification_phone_number .send_button').hide();
                    }
                    return;
                }

                if (this.step === 2) {
                    if (this.validatePhone(phone)) {
                        $('#verification_phone_number .resend_verify_code').show();
                    } else {
                        $('#verification_phone_number .resend_verify_code').hide();
                    }
                }

            },

            /**
             * ValidatePhone
             * @param phoneNumber
             * @returns {boolean}
             */
            validatePhone: function (phoneNumber) {
                if (phoneNumber.length === 0) {
                    return false;
                }

                if (phoneNumber.length < 10) {
                    return false;
                }

                let firstDigits = phoneNumber.substr(0, 2);
                if (firstDigits !== '04' && firstDigits !== '05') {
                    this.showErrorMessage($t('The phone number is not mobile.'));
                    return false;
                }

                return true;
            },

            /**
             * Phone Number Filter
             * @param phoneNumber
             * @returns {string}
             */
            phoneNumberFilter: function (phoneNumber) {
                phoneNumber = phoneNumber.replace(/[^0-9]/g, "");

                let firstDigits = phoneNumber.substr(0, 2);
                if (firstDigits === '61') {
                    //replace 61 by 0
                    phoneNumber = '0' + phoneNumber.substring(2, phoneNumber.length)
                }
                //get 10 last digits
                if (phoneNumber.length > 10) {
                    phoneNumber = phoneNumber.substring(phoneNumber.length - 10, phoneNumber.length)
                }

                return phoneNumber;
            },

            /**
             * Verify Code Filter
             * @param code
             * @returns {string}
             */
            verifyCodeFilter: function (code) {
                code = code.replace(/[^0-9]/g, "");

                if (code.length > 4) {
                    code = code.substring(0, 4)
                }

                return code;
            },

            /**
             * Open Popup
             */
            openPopup: function () {
                this.initVerificationPopup();
                this.checkForm();
                this.updatePopupStep();

                if (!this.modalStatus) {
                    this.modal.modal("openModal");
                }
            },

            updatePopupStep: function () {
                $('#verification_phone_number .modal_inner_content').hide();
                $('#verification_phone_number .modal_inner_content_step_' + this.step).show();

                if (this.step === 3) {
                    setTimeout(function () {
                        this.modal.modal('closeModal');
                    }.bind(this), 4000);
                }
            },

            /**
             * Send Code
             * @param phone
             * @param callback
             */
            sendCode: function (phone, callback) {
                let self = this;
                if (this.ajaxRequestPending) {
                    return;
                }

                $('#verification_phone_number .phone_number').html(phone);
                this.ajaxRequestPending = true;
                $.ajax({
                    type: "post",
                    url: self.sendCodeUrl,
                    data: {"phone": phone},
                    showLoader: true,
                    success: function (response) {
                        if (response.is_success) {
                            this.phone = phone;
                            callback(true);
                        } else {
                            let errorMessage = response.response;
                            callback(false, errorMessage);
                        }
                    },
                    error: function () {
                        let errorMessage = $t("An error occurred while sending the code, please try again later.");
                        callback(false, errorMessage);
                    },
                    complete: function () {
                        self.ajaxRequestPending = false;
                    }
                });
            },

            /**
             * Send Code Callback
             * @param status
             * @param message
             */
            sendCodeCallback: function (status, message) {
                if (status) {
                    this.step = 2;
                    this.updatePopupStep();
                } else {
                    if (typeof (message) !== "undefined") {
                        this.showErrorMessage(message);
                    }
                }
            },

            /**
             * Check Code
             * @param code
             * @param callback
             */
            checkCode: function (code, callback) {
                let self = this;
                if (this.ajaxRequestPending) {
                    return;
                }

                this.ajaxRequestPending = true;
                $.ajax({
                    type: "post",
                    url: self.validateCodeUrl,
                    data: {"otp": code},
                    showLoader: true,
                    success: function (response) {
                        if (response.is_success) {
                            callback(true);
                        } else {
                            let errorMessage = response.response;
                            callback(false, errorMessage);
                        }
                    },
                    error: function () {
                        let errorMessage = $t("An error occurred while validating the code, please try again later.");
                        callback(false, errorMessage);
                    },
                    complete: function () {
                        self.ajaxRequestPending = false;
                    }
                });
            },

            /**
             * Check Code Callback
             * @param status
             * @param message
             */
            checkCodeCallback: function (status, message) {
                if (status) {
                    this.step = 3;
                    this.updatePopupStep();
                    if (quote.billingAddress() && this.phone !== "" && quote.billingAddress().telephone !== this.phone) {
                        quote.billingAddress().telephone = this.phone;
                    }
                    this.placeOrder();
                } else {
                    if (typeof (message) !== "undefined") {
                        $('#verification_phone_number .verification_code').val("");
                        this.showErrorMessage(message);
                    }
                }
            },

            /**
             * Place Order
             */
            placeOrder: function () {
                if (this.step !== 3 && !this.isCustomerPhoneNumberConfirmed()) {
                    this.openPopup();
                    return;
                }

                this._super();
            }
        });
    };
});
