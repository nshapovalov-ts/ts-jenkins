define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Search/form-mini'
], function ($) {
    'use strict';

    function isEmpty(value) {
        return value.length === 0 || value == null || /^\s+$/.test(value);
    }

    $.widget('mage.amFaqAutoSuggest', $.mage.quickSearch, {
        options: {
            autocomplete: 'off',
            minSearchLength: 3,
            responseFieldElements: 'ul li',
            selectClass: 'selected',
            template:
            '<li class="<%- data.row_class %>" id="qs-option-<%- data.index %>" role="option">' +
            '<div class="qs-option-name">' +
            '<%- data.title %>' +
            '</div>' +
            '<div aria-hidden="true" class="amfaq-category">' +
            'Category: <%- data.category %>' +
            '</div>' +
            '<div class="qs-option-url" style="display: none">' +
            '<%- data.url %>' +
            '</div>' +
            '</li>',
            submitBtn: 'button[type="submit"]',
            searchLabel: '[data-role=minisearch-label]',
            isExpandable: null
        },

        _create: function () {
            this._super();
            this.searchForm.on('submit', $.proxy(function () {
                var result = this._onSubmit();
                this._updateAriaHasPopup(false);
                return result; // return false to disable form submit
            }, this));
        },

        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }

            if (this.responseList.selected) {
                window.location.href = this.responseList.selected.find('.qs-option-url').text();
                return false;
            }
        },

        _onPropertyChange: function () {
            this._super();
        }
    });

    return $.mage.amFaqAutoSuggest;
});
