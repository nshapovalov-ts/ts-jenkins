define([
    'uiElement',
    'jquery',
    'ko',
    'underscore',
    'Amasty_InvisibleCaptcha/js/model/am-recaptcha',
    'Amasty_InvisibleCaptcha/js/view/am-recaptcha-loader',
    'domReady!'
], function (Element, $, ko, _, amReCaptchaModel, amReCaptchaLoader) {
    'use strict';

    return Element.extend({
        defaults: {
            formsToProtect: '',
            inputName: 'amasty_invisible_token'
        },

        /**
         * @inheritDoc
         * @returns {Object}
         */
        initObservable: function () {
            $(window).on('recaptchaapiready', this.initFormHandler.bind(this));

            $(this.formsToProtect).on('submit', function (event) {
                if (amReCaptchaModel.isScriptAdded) {
                    return
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                this.loadApi();

                this.firstSubmittedForm = $(event.target);

            }.bind(this));

            return this._super();
        },

        /**
         * Loads reCaptcha API
         * @returns {void}
         */
        loadApi: function () {
            window[amReCaptchaModel.onLoadCallback] = function () {
                $(window).trigger('recaptchaapiready');
            };

            amReCaptchaModel.lang = this.lang;
            amReCaptchaLoader.addReCaptchaScript();
        },

        /**
         * Append hidden input into each form
         * @returns {void}
         */
        initFormHandler: function () {
            var formsToProtect = $(this.formsToProtect);

            _.each(formsToProtect, function (form) {
                var tokenInput = document.createElement('input'),
                    buttonElement = form.querySelector("[type='submit']");

                tokenInput.type = 'hidden';
                tokenInput.name = this.inputName;
                form.appendChild(tokenInput);

                this.renderCaptcha(null, form, buttonElement);
            }.bind(this));
        },

        /**
         * Render captcha and save in model
         * @param {Event} event
         * @param {Element} form
         * @param {Element} buttonElement
         * @returns {void}
         */
        renderCaptcha: function (event, form, buttonElement) {
            var $form = $(form),
                widgetId = window.grecaptcha.render(buttonElement, {
                    'theme': this.theme,
                    'badge': this.badge,
                    'sitekey': this.sitekey,
                    'callback': function (token) {
                        if ($form.valid()) {
                            $form.find("[name='" + this.inputName + "']").attr('value', token);
                            $form.submit();
                        }

                        this.resetCaptcha();
                    }.bind(this),
                    'expired-callback': this.resetCaptcha
                });

            amReCaptchaModel.captchaList.push(widgetId);

            if (this.firstSubmittedForm && this.firstSubmittedForm.attr('id') === $form.attr('id')) {
                $(buttonElement).trigger('click');

                this.firstSubmittedForm = null;
            }
        },

        /**
         * Reset captcha
         * @returns {void}
         */
        resetCaptcha: function () {
            _.each(amReCaptchaModel.captchaList, function (captcha) {
                window.grecaptcha.reset(captcha);
            });
        }
    });
});
