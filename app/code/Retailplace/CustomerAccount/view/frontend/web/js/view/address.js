define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('retailplace.address', {

        _create: function () {
            this._bindEvents();
        },

        _bindEvents: function () {

            var street = $('#street'),
                enterAddress = $('#link-enter-address');

            var checkFormData = setInterval(function () {
                if (street.val() !== "") {
                    enterAddress.trigger('click');
                    clearInterval(checkFormData);
                }
            }, 1000);

            enterAddress.click(function(){
                street.removeClass('required');
                $("#enter-address-wrap").show();
                $(this).hide();
            });
        }
    });

    return $.retailplace.address;
})
