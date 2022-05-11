/**
 * reCaptcha model
 */

define(function () {
    'use strict';

    return {
        onLoadCallback: 'amInvisibleCaptchaOnloadCallback',
        isScriptAdded: false,
        captchaList: [],
        url: 'https://www.google.com/recaptcha/api.js',
        lang: 'hl=en'
    };
});
