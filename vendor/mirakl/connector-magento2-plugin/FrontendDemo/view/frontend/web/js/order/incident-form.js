define([
    'jquery',
    'mage/mage',
    'mage/translate'
], function ($, mage) {
    'use strict';

    return function (config, element) {
        $(element).mage('validation', {
            errorPlacement: function (error, element) {
            	var errorPlacement = element.parent().find('button');
                if (errorPlacement.length) {
                    errorPlacement.after(error);
                } else {
                    element.after(error);
                }
            },
            submitHandler: function(form) {
                if (confirm($.mage.__('Are you sure?'))) {
                    form.submit();
                }
            }
        });
    };
});