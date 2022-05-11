/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */
define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.sellerPage', {
        options: {
            mainContentId: '#maincontent'
        },
        /**
         * @param {Object} config
         * @param {Object} element
         * @private
         */
        _create: function (config, element) {
            this.initScroll();
        },
        /**
         *
         * @private
         */
        initScroll: function () {
            let self = this;
            if (window.location.href.indexOf('?') > -1) {
                $(document).scrollTop($(self.options.mainContentId).offset().top);
            }
        }
    });

    return $.mage.sellerPage;
});
