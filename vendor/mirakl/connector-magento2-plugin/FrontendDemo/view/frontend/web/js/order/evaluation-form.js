define([
    'jquery',
    'mage/mage'
], function ($, mage) {
    'use strict';

    return function (config, element) {
        $(element).mage('validation', {
            errorPlacement: function (error, element) {

                if (element.parent().is('.radio-box')) {
                	element.parent().siblings(this.errorElement + '.' + this.errorClass).remove();
                	element.parent().after(error);
                } else {
                    element.after(error);
                }
            }
        });
    };
});
