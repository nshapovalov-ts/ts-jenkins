define([
    'jquery'
], function ($) {
    'use strict';
    $.widget('mage.sideMenuFilter', {
        options: {
            activeClass: 'active'
        },
        /** @inheritdoc */
        _create: function () {
            this.bind();
        },
        bind: function () {
            let self = this;
            self.element.find('.mob_filter_title').on('click', function () {
                let visibleStatus = $(this).next('dl.filter-options').is(':visible');
                self.element.find('dl.filter-options').hide();
                self.element.find('.mob_filter_title').removeClass(self.options.activeClass);
                if (!visibleStatus) {
                    $(this).addClass(self.options.activeClass);
                    $(this).next('dl.filter-options').show();
                }
            });
        }
    });
    return $.mage.sideMenuFilter;
});
