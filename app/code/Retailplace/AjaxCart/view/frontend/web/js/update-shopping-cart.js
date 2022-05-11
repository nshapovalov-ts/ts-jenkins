/**
 * Retailplace_AjaxCart
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('mage.updateShoppingCart', widget, {
            flag: true,
            validateItems: function (url, data) {
                if (this.flag) {
                    this.flag = false;
                } else {
                    return;
                }

                this._super(url, data);
            },
            /**
             * Real submit of validated form.
             */
            submitForm: function () {
                this.flag = true;
            },

            onError: function (response) {
                this.element.focus();
                $('.qty-input').each(function(indx, elem){
                    $(elem).val($(elem).attr('data-initial-qty'));
                });
                this._super(response);
                this.flag = true;
            },
        });

        return $.mage.updateShoppingCart;
    }
});
