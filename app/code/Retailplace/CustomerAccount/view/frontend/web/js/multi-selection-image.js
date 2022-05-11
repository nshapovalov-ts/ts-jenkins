define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('retailplace.multiSelectionImage', {
        _create: function () {
            this._bindEvents();
        },
        _bindEvents: function () {
            $('.item' ,this.element).click(function() {
                var self = $(this),
                    input = self.find('input');
                if (input.is(':checked')) {
                    self.removeClass('active');
                    input.prop('checked', false);
                } else {
                    self.addClass('active')
                    input.prop('checked', true);
                }
                input.trigger("change");
            });
        }
    });
    return $.retailplace.multiSelectionImage;
})
