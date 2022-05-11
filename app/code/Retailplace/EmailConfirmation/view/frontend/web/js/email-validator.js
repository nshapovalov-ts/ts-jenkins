/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'uiComponent',
    'jquery',
    'ajaxRequestManagement',
    'ko',
    'mage/translate',
    'Retailplace_MobileVerification/js/view/single-digit-input',
    'mage/mage'
], function (Component, $, ajaxRequestManagement, ko, $t, singleDigitInput) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Retailplace_EmailConfirmation/validation'
        },
        pageErrors: ko.observableArray([]),
        /**
         * Init Component
         *
         * @param {Object} config
         * @param {Object} form
         */
        initialize: function (config, form) {
            this._super();
            this.backUrl = config.backUrl;
            this.email = config.email;
        },
        /**
         * Init Form Send
         *
         * @param {Element} form
         */
        initForm: function(form) {
            let self = this;

            $(form).submit(function(e) {
                e.preventDefault();

                let verifyCode = singleDigitInput.collectValue(
                    $(form).find('.verification-code')
                );

                if (verifyCode.length < 5) {
                    self.pageErrors([$t('Please enter 5 digits code')]);
                } else {
                    ajaxRequestManagement.sendRequest(
                        'email-confirmation/validation/codeValidatePost',
                        $(form).serialize(),
                        {
                            success: function (response) {
                                if (response.getIsSuccess()) {
                                    if (response.getData('redirect_url')) {
                                        $.mage.redirect(response.getData('redirect_url'));
                                    }
                                } else {
                                    self.pageErrors([response.getErrorMessage()]);
                                }
                            },
                            error: function () {
                                self.pageErrors([$t('Server Error')]);
                            }
                        }
                    );
                }
            });

            $(form).find('.verification-code')
                .on("keyup", function (event) {
                    singleDigitInput.onKeyUp(event, function() {
                        return self.codeUpdateCallback(form)
                    });
                })
                .on("keydown", singleDigitInput.onKeyDown.bind(singleDigitInput));

            $(form).find('.verification-code')
                .on("keydown", function() {
                    self.pageErrors([]);
                });

            $(form).find('[type=number]').on("paste", function (event) {
                singleDigitInput.onPaste(event, function() {
                    return self.codeUpdateCallback(form)
                });
            });
        },
        codeUpdateCallback: function(form) {
            let verifyInputsBlock = $(form).find('.verification-code ');
            let verifyCode = singleDigitInput.collectValue(verifyInputsBlock);

            if (verifyCode.length === 5) {
                $(form).find('.validation-code').val(verifyCode);
                $(form).submit();
            } else {
                $(form).find('.validation-code').val('');
            }
        },
        /**
         * Get Referer Url
         *
         * @returns {String}
         */
        getBackUrl: function() {
            return this.backUrl;
        },
        /**
         * Get Email
         *
         * @returns {String}
         */
        getEmail: function() {
            return this.email;
        }
    })
});
