define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var self = $(element), formData,
            form = $(config.formId),
            input = self.find($('input[type=file]')),
            messageSelector = self.find($(".upload-file-message")),
            button = self.find($('#btn-upload-file'));

        button.click(function () {
            input.trigger('click');
        });
        input.on('change', function () {
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
                        messageSelector.html("File uploaded successfully").addClass('success').removeClass('error');
                    }
                }
            });
        });
    };
});
