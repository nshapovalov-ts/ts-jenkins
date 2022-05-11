define([
    'jquery',
    'underscore',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($, _, mageTemplate) {
    'use strict';

    /**
     * Check wether the incoming string is not empty or if doesn't consist of spaces.
     *
     * @param {String} value - Value to check.
     * @returns {Boolean}
     */
    function isEmpty(value) {
        return (value.length === 0) || (value === null) || /^\s+$/.test(value);
    }

    $.widget('mage.quickSearch', {
        options: {
            timeout: 1500,
            autocomplete: 'off',
            minSearchLength: 2,
            responseFieldElements: 'ul li',
            selectClass: 'selected',
            template:
                '<li class="<%- data.row_class %>" id="qs-option-<%- data.index %>" role="option">' +
                '<span class="qs-option-name">' +
                ' <%- data.title %>' +
                '</span>' +
                '<span aria-hidden="true" class="amount">' +
                '<%- data.num_results %>' +
                '</span>' +
                '</li>',
            template2:
                '<li class="suggested_seller <%- data.row_class %>" id="qs-option-<%- data.index %>" role="option">' +
                '<a href="<%- data.url %>" title="<%- data.name %>"><%- data.name %></a>' +
                '</li>',
            sellerTemplate:
                '<li class="suggested_seller" data-seller-id="<%- seller.id %>">' +
                ' <% if(seller.logo){ %>'+
                '<img src="<%- seller.logo %>" />' +
                ' <% } else { %>'+
                '<span class="seller_circle_logo" ><%- seller.seller_first %></span>' +
                ' <% } %>'+
                '<div class="seller_sec">'+
                '<a href="<%- seller.url %>" title="<%- seller.name %>"><%- seller.name %></a>' +
                '<span class="min_order_amount"><%- seller.min_order_amount %></span>'+
                '</div>'+
                '</li>',
            resultsTemplate:
                '<li class="full-search">' +
                '<a href="<%- data.url %>" title="' + $.mage.__('View full list') + '">' +
                '<span>' + $.mage.__('View All Results') + ': <%- data.size %></span>' +
                '</a>' +
                '<button id="btn-quicksearch-close" class="action close" data-bind="attr: { title: $t(\'Close\') }" data-action="close" type="button" title="' + $.mage.__('Close') + '">' +
                '</button>' +
                '</li>',
            submitBtn: 'button[type="submit"]',
            closeBtn: 'button.close',
            searchLabel: '[data-role=minisearch-label]'
        },

        /**
         * Object creation
         *
         * @private
         * @return void
         */
        _create: function () {
            this.responseList = {
                indexList: null,
                selected: null
            };
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = $(this.options.searchLabel);
            this.loading = false;
            this.timer = 0;

            _.bindAll(this, '_onKeyDown', '_onPropertyChange', '_onSubmit');

            this.submitBtn.disabled = true;

            this.element.attr('autocomplete', this.options.autocomplete);

            this.element.trigger('blur');

            this.element.on('focus', $.proxy(function () {
                this.searchLabel.addClass('active');
            }, this));
            this.element.on('keydown', this._onKeyDown);
            this.element.on('input propertychange', $.proxy(function () {
                if (this.timer) {
                    clearTimeout(this.timer);
                }
                this.timer = setTimeout(this._onPropertyChange, this.options.timeout);
            }, this));

            this.searchForm.on('submit', $.proxy(function () {
                this._onSubmit();
                this._updateAriaHasPopup(false);
            }, this));
        },

        /**
         * Get first visible element
         *
         * @private
         * @return {Element} The first element in the suggestion list.
         */
        _getFirstVisibleElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.first() : false;
        },

        /**
         * Get last element
         *
         * @private
         * @return {Element} The last element in the suggestion list.
         */
        _getLastElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.last() : false;
        },

        /**
         * Update aria has popup
         *
         * @private
         * @param {Boolean} show Set attribute aria-haspopup to "true/false" for element.
         * @return void
         */
        _updateAriaHasPopup: function (show) {
            if (show) {
                this.element.attr('aria-haspopup', 'true');
            } else {
                this.element.attr('aria-haspopup', 'false');
            }
        },

        /**
         * Clears the item selected from the suggestion list and resets the suggestion list.
         *
         * @private
         * @param {Boolean} all - Controls whether to clear the suggestion list.
         * @return void
         */
        _resetResponseList: function (all) {
            this.responseList.selected = null;

            if (all === true) {
                this.responseList.indexList = null;
            }
        },

        /**
         * Executes when the search box is submitted. Sets the search input field to the
         * value of the selected item.
         *
         * @private
         * @param {Event} e - The submit event
         * @return void
         */
        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }

            if (this.responseList.selected) {
                this.element.val(this.responseList.selected.find('.qs-option-name').text());
            }
        },

        /**
         * Executes when keys are pressed in the search input field. Performs specific actions
         * depending on which keys are pressed.
         *
         * @private
         * @param {Event} e - The key down event
         * @return {Boolean} Default return type for any unhandled keys
         */
        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            switch (keyCode) {
                case $.ui.keyCode.HOME:
                    this._getFirstVisibleElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getFirstVisibleElement();
                    break;
                case $.ui.keyCode.END:
                    this._getLastElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getLastElement();
                    break;
                case $.ui.keyCode.ESCAPE:
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    break;
                case $.ui.keyCode.ENTER:
                    this.searchForm.trigger('submit');
                    break;
                case $.ui.keyCode.DOWN:
                    if (this.responseList.indexList) {
                        if (!this.responseList.selected) {
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        } else if (!this._getLastElement().hasClass(this.options.selectClass)) {
                            var nextElement = this.responseList.selected.next();
                            this.responseList.selected.removeClass(this.options.selectClass);
                            nextElement.addClass(this.options.selectClass);
                            this.responseList.selected = nextElement;
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                case $.ui.keyCode.UP:
                    if (this.responseList.indexList !== null) {
                        if (!this._getFirstVisibleElement().hasClass(this.options.selectClass)) {
                            var prevElement = this.responseList.selected.prev();
                            this.responseList.selected.removeClass(this.options.selectClass);
                            prevElement.addClass(this.options.selectClass);
                            this.responseList.selected = prevElement;
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getLastElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getLastElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                default:
                    return true;
            }
        }, _capitalize: function(s) {
            return  s && s[0].toUpperCase() + s.slice(1);
        },
        _small: function(s) {
            return  s && s[0].toLowerCase() + s.slice(1);
        },

        /**
         * Executes when the value of the search input field changes. Executes a GET request
         * to populate a suggestion list based on entered text. Handles click (select), hover,
         * and mouseout events on the populated suggestion list dropdown.
         *
         * @private
         * @return void
         */
        _onPropertyChange: function () {
            var searchField = this.element,
                source = this.options.template,
                resultsSource = this.options.resultsTemplate,
                sellerSource = this.options.sellerTemplate,
                template = mageTemplate(source),
                resultsTemplate = mageTemplate(resultsSource),
                sellerTemplate = mageTemplate(sellerSource),
                dropdown = $('<div role="listbox"></div>'),
                sellerDropdown = $('<ul role="seller_listbox"></ul>'),
                productsDropdown = $('<ul role="product_listbox"></ul>'),
                value = this.element.val(),
                latitude = $.cookie("latitude"),
                longitude = $.cookie("longitude");
            this.submitBtn.disabled = isEmpty(value);
            var self = this;
            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                searchField.closest('form').addClass('loading');
                //$(".search-loader").removeClass('search-loader-hide');
                this.submitBtn.disabled = true;
                Pace.restart();
                $.get(this.options.url, {q: value}, $.proxy(function (data) {
                    this.submitBtn.disabled = false;

                    // Add full result link
                    // var html = resultsTemplate({
                    //     data: data.info
                    // });
                    // dropdown.append(html);
                    //Suggested Products List
                    if (!$.isEmptyObject(data.results)) {
                        dropdown.append(productsDropdown);
                        var productTitleHtml = '<span class="search_title">' + $.mage.__("Suggestion") + '</span>';
                        productsDropdown.append(productTitleHtml);
                        var query = data.query;
                        $.each(data.results, function (index, element) {
                            element.index = index;
                            var html = template({
                                data: element
                            });
                            /*html = html
                                .replace(query, "<span class='highlight-query'>"+ query +"</span>")
                                .replace(self._capitalize(query), "<span class='highlight-query'>"+ self._capitalize(query) +"</span>")
                                .replace(self._small(query), "<span class='highlight-query'>"+ self._small(query) +"</span>");*/
                            html = _.unescape(html);
                            productsDropdown.append(html);
                        });
                    }

                    //Suggested seller List
                    if (!$.isEmptyObject(data.sellers)) {
                        dropdown.append(sellerDropdown);
                        var sellerTitleHtml = '<span class="search_title">' + $.mage.__("Sellers") + '</span>';
                        sellerDropdown.append(sellerTitleHtml);
                        $.each(data.sellers, function (index, element) {
                            element.index = index;
                            element.seller_first = element.name.charAt(0);
                            var html = sellerTemplate({
                                seller: element
                            });
                            sellerDropdown.append(html);
                        });
                    }

                    if ($.isEmptyObject(data.results) && $.isEmptyObject(data.sellers)) {
                        dropdown.append("<span class='no-res-found'>No results found</span>");
                    }
                    this.responseList.indexList = this.autoComplete.html(dropdown)
                        .show()
                        .find(this.options.responseFieldElements + ':visible');

                    this._resetResponseList(false);
                    this.element
                        .removeAttr('aria-activedescendant')
                        .closest('form').removeClass('loading');
                    $(".search-loader").addClass('search-loader-hide');
                    if (this.responseList.indexList.length) {
                        this._updateAriaHasPopup(true);
                    } else {
                        this._updateAriaHasPopup(false);
                    }

                    this.responseList.indexList
                        .on('click', function (e) {
                            this.responseList.selected = $(e.currentTarget);
                            this.searchForm.trigger('submit');
                        }.bind(this))
                        .on('mouseenter mouseleave', function (e) {
                            this.responseList.indexList.removeClass(this.options.selectClass);
                            $(e.target).addClass(this.options.selectClass);
                            this.responseList.selected = $(e.target);
                            this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                        }.bind(this))
                        .on('mouseout', function (e) {
                            if (!this._getLastElement() && this._getLastElement().hasClass(this.options.selectClass)) {
                                $(e.target).removeClass(this.options.selectClass);
                                this._resetResponseList(false);
                            }
                        }.bind(this));

                    // Close action
                    var closeBtn = this.autoComplete.find(this.options.closeBtn);
                    closeBtn.on('click', $.proxy(function () {
                        this.autoComplete.hide();
                    }, this));
                    $(document).on('click', $.proxy(function (event) {
                        if (this.searchForm.has($(event.target)).length <= 0) {
                            this.autoComplete.hide();
                        }
                    }, this));

                }, this));

            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        }
    });

    return $.mage.quickSearch;
});
