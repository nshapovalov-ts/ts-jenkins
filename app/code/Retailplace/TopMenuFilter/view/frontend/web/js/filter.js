define([
    'jquery',
    'underscore',
    'jquery/ui',
    'Sm_Market/js/owl.carousel',
    'mage/translate'
], function ($, _) {
    'use strict';

    $.widget('mage.topFilter', {
        options: {
            isEnableOwnCarrousel: false,
            selectClass: 'selected',
            disableClass: 'disabled',
            activeClass: 'active'
        },
        /** @inheritdoc */
        _create: function () {
            this.enableCarrousel();
            this.bind();
        },
        bind: function () {
            let self = this;
            let selectClass = self.options.selectClass;
            let disabledClass = self.options.disableClass;
            let activeClass = self.options.activeClass;
            this.disabledClass = disabledClass;

            self.preselectClearAll(self, selectClass, disabledClass);

            self.element.find('.filteroption input.filter-check').on('change', function () {
                $(this).not(this).prop('checked', false);
                if (self.element.find('.filter-check:checked').length > 0) {
                    self.preselectClearAll(self, selectClass, disabledClass);
                } else {
                    let filterOption = $(this).parents('.filteroption');
                    filterOption.find('.clear-all').addClass(disabledClass);

                    let attributeCode = filterOption.prev('.filter_block_title').find('a').data('attribute-code');
                    let filterButtton = self.element.find('.' + attributeCode + '-button a');
                    if (filterButtton.hasClass(selectClass)) {
                        filterButtton.removeClass(selectClass)
                    }
                }
            });

            self.element.find('input.am-filter-price').on('change', function () {
                self.updateClearLabel(this);
            });
            self.element.find('[data-amshopby-fromto="value"]').on('tops_filter:change_value', function () {
                self.updateClearLabel(this);
            });

            self.element.find('.filteroption .clear-all a').on('click', function (e) {
                let filterOption = $(this).parents('.filteroption');
                filterOption.find('input.filter-check:checked').removeAttr('checked');

                filterOption.find('.clear-all').addClass(disabledClass);
                let attributeCode = filterOption.prev('.filter_block_title').find('a').data('attribute-code');
                let filterButtton = self.element.find('.' + attributeCode + '-button a');
                if (filterButtton.hasClass(selectClass)) {
                    filterButtton.removeClass(selectClass)
                }

                filterOption.find('[data-amshopby-fromto="value"]').trigger('top_filter:clear');
                let elm = filterOption.find('.clear-all');
                if (!elm.hasClass(disabledClass)) {
                    elm.addClass(disabledClass);
                }
            });

            self.element.find('.filter_block_title a').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                let attributeCode = $(this).data('attribute-code');
                let filterBlock = self.element.find('.' + attributeCode + '-filter');
                let filterOption = filterBlock.find('.filteroption');
                let filterButtton = self.element.find('.' + attributeCode + '-button a');
                let isActive = filterButtton.hasClass(activeClass);
                self.element.find('.filter_block_title a.' + activeClass).removeClass(activeClass);
                self.element.find('.filteroption').hide();
                if (!isActive) {
                    filterOption.show();
                    filterButtton.addClass(activeClass);
                }
            });

            self.element.find('.filteroption').on('click', function (e) {
                e.stopPropagation();
            });

            $('body').click(function () {
                self.element.find('.filteroption').hide();
            });

            self.element.find('.filter-options-content .filter-search').on('keyup', function (e) {
                let filter = $(this).val();
                $(this).parents('.filter-options-content').find('.filter-options ul li').each(function () {
                    if ($(this).text().search(new RegExp(filter, 'i')) < 0) {
                        $(this).hide();
                    } else {
                        $(this).show()
                    }
                });
            });

            self.element.find('.filter-options-content .applyfilter .apply').on('click', function (e) {
                let requestLink = $(this).data('request-link');
                if (typeof requestLink != 'undefined' && requestLink !== "") {
                    window.location.href = requestLink;
                    return;
                }

                let requestVar = $(this).data('request-var');
                let filterOptionLiDiv = $(this).parents('.filter-options-content').find('.filter-options');
                let filterInput = filterOptionLiDiv.find('.filter-check:checked');
                if (typeof filterInput != 'undefined' && filterInput.attr('type') == 'radio') {
                    window.location.href = filterInput.data('url');
                } else {
                    let existedParamArr = [];
                    filterInput.each(function () {
                        let filterValue = $(this).val();
                        if (!(existedParamArr.indexOf(filterValue) != -1)) {
                            existedParamArr.push(filterValue);
                        }
                    });
                    let url = '';
                    if (existedParamArr.length !== 0) {
                        let existedParam = self.getParamFromCurrentUrl(requestVar);
                        if (existedParam) {
                            existedParamArr.concat(existedParam.split(','));
                        }
                        let paramvalue = encodeURIComponent(existedParamArr.join(','));
                        url = self.addOrUpdateParamFromCurrentUrl(requestVar, paramvalue);
                    } else {
                        url = self.removeURLParamFromCurrentUrl(requestVar);
                    }
                    //console.log(url);
                    if (url) {
                        window.location.href = url;
                    }
                }
            });

            /*self.element.find('.filteroption ul').each(function () {
                let LiN = $(this).find('li').length;
                if (LiN > 6) {
                    $('li', this).eq(5).nextAll().hide().addClass('toggleable');
                    $(this).append('<li class="more">See More...</li>');
                }
            });

            self.element.find('.filteroption ul').on('click', '.more', function () {
                if ($(this).hasClass('less')) {
                    $(this).text('See More...').removeClass('less');
                } else {
                    $(this).text('See Less...').addClass('less');
                }
                $(this).siblings('li.toggleable').slideToggle();
            });*/
        },
        updateClearLabel: function (e) {
            let filterOption = $(e).parents('.filteroption');
            let elm = filterOption.find('.clear-all');
            if (elm.hasClass(this.disabledClass)) {
                elm.removeClass(this.disabledClass);
            }
        },
        preselectClearAll: function (self, selectClass, disabledClass) {
            if (self.element.find('.filter-check:checked').length > 0) {
                self.element.find('.filter-check:checked').each(function (e) {
                    let filterOption = $(this).parents('.filteroption');
                    filterOption.find('.clear-all').removeClass(disabledClass);
                    let attributeCode = filterOption.prev('.filter_block_title').find('a').data('attribute-code');
                    let filterButtton = self.element.find('.' + attributeCode + '-button a')
                    if (!filterButtton.hasClass(selectClass)) {
                        filterButtton.addClass(selectClass)
                    }
                });
            }
        },
        getParamFromCurrentUrl: function (parameter) {
            let res = null;
            try {
                let qs = decodeURIComponent(window.location.search.substring(1));
                let ar = qs.split('&');
                $.each(ar, function (a, b) {
                    let kv = b.split('=');
                    if (parameter === kv[0]) {
                        res = kv[1];
                        return false;//break loop
                    }
                });
            } catch (e) {
            }
            return res;
        },
        addOrUpdateParamFromCurrentUrl: function (parameter, paramVal, uri) {
            if (!uri) {
                uri = window.location.href;
            }
            let re = new RegExp('([?&])' + parameter + '=[^&#]*', 'i');
            if (re.test(uri)) {
                uri = uri.replace(re, '$1' + parameter + '=' + paramVal);
            } else {
                let separator = /\?/.test(uri) ? '&' : '?';
                uri = uri + separator + parameter + '=' + paramVal;
            }
            return uri;
        },
        removeURLParamFromCurrentUrl: function (parameter, uri) {
            if (!uri) {
                uri = window.location.href;
            }
            //prefer to use l.search if you have a location/link object
            let urlparts = uri.split('?');
            if (urlparts.length >= 2) {
                let prefix = encodeURIComponent(parameter) + '=';
                let pars = urlparts[1].split(/[&;]/g);

                //reverse iteration as may be destructive
                for (let i = pars.length; i-- > 0;) {
                    //idiom for string.startsWith
                    if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                        pars.splice(i, 1);
                    }
                }
                uri = urlparts[0] + '?' + pars.join('&');
                return uri;
            } else {
                return uri;
            }
        },
        enableCarrousel: function () {
            if (!this.options.isEnableOwnCarrousel) {
                return false;
            }
            this.element.find('.filter-section.mobile').owlCarousel({
                loop: false,
                margin: 0,
                responsiveClass: true,
                autoWidth: true,
                navRewind: false,
                nav: true,
                responsive: {
                    0: {
                        items: 1,
                        nav: true
                    },
                    768: {
                        items: 1,
                        nav: false
                    },
                    1000: {
                        items: 1,
                        nav: false,
                        dots: false
                    }
                }
            });
        }
    })
    ;
    return $.mage.topFilter;
})
;
