/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Theme/js/view/messages'
], function ($, customerData, messages) {
    'use strict';

    return function (config, element) {
        var block = $(element);
        block.on('click', '.action.towishlist', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var url = JSON.parse(this.getAttribute('data-post')).action;
            var requestData = JSON.parse(this.getAttribute('data-post')).data;
            requestData.form_key = $('input[name="form_key"]').val();
            var self = $(this);

            $.ajax({
                url: url,
                data: $.param(requestData),
                type: 'post',
                showLoader: true,
                complete: function () {
                    messages().messages({messages: [{type: "clean", text: ""}]});
                    customerData.reload(['wishlist'], true);
                },
                success: function () {
                    self.addClass('allready-adedd-whilist');
                }
            });
        });
    };
});
