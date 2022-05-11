define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('retailplace.selectionImage', {
        _create: function () {
            this._bindEvents();
        },

        _bindEvents: function () {
            $('.item', this.element).click(function(e){
                var parentEl = $(this).parent(),
                    input =  $(this).find('input');
                parentEl.find('.item').removeClass('active');
                if(input.is(':checked')){
                    input.prop('checked', false);
                    e.preventDefault();
                }
                else {
                    e.preventDefault();
                    input.prop('checked', true);
                    $(this).addClass('active');
                }
                input.trigger('change');
            });
        }
    });

    return $.retailplace.selectionImage;
})
