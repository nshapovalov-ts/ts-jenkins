/*
 * Retailplace_TopMenuFilter
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

define([
    "jquery",
    "Magento_Ui/js/modal/modal",
    "mage/tooltip",
    'mage/validation',
    'mage/translate',
    "Amasty_Shopby/js/jquery.ui.touch-punch.min",
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.topFilterSlider', $.mage.amShopbyFilterSlider, {
        apply: function (link, clearFilter) {
            var code = typeof this.options.code != 'undefined' && this.options.code ? this.options.code : "";
            $('#apply' + code).data('request-link', link);
        },
        getFixed: function (value, isPrice) {
            return 0;
        },
        getSignsCount: function (step, isPrice) {
            return 0;
        }
    })
})
