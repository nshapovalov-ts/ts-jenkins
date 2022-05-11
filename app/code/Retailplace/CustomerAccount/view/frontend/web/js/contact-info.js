/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'mage/translate',
    'mage/mage'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        var form = $(element), formData,
            messageSelector = form.find($(".message-placeholder")),
            button = form.find($('#btn-done')),
            buttonUpload = form.find($('#btn-upload-file')),
            input = form.find($('input[type=file]'));

        buttonUpload.click(function () {
            input.trigger('click');
        });

        button.on('click', function () {
            formData = new FormData(form[0]);

            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                showLoader: true,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.error) {
                        messageSelector.html(data.error).removeClass('success').addClass('error');
                    } else {
                        if (data.redirect_url) {
                            messageSelector.html($t("Customer data was saved successfully and you will be redirected in 3 seconds.")).addClass('success').removeClass('error');
                            $($.mage.redirect(data.redirect_url, 'assign', 3000));
                        } else {
                            messageSelector.html($t("Saved data successfully")).addClass('success').removeClass('error');
                        }
                    }
                }
            });
        })
    };
});
