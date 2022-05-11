define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/validation'
], function ($, modal) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            buttons: [],
            modalClass: 'seller-message-popup'
    };
        return {
            openSellerMessagePopup :function (ajaxurl) {
                $("#message_seller_link").click(function (e) {
                    e.preventDefault();
                    $.ajax({
                        url:ajaxurl,
                        type:'POST',
                        showLoader: true,
                        dataType:'json',
                        data: data,
                        complete: function (response) {
                            var responseData = $.parseJSON(response.responseText);
                            $("#seller_message_container").html(responseData.content).modal(options).modal('openModal');
                        },
                        error: function (xhr, status, errorThrown) {
                            console.log('Please try again.');
                        }
                    });
                });
            },

            sendSellerMessage :function () {
                var form = $("#seller_message");
                var ajaxurl = form.attr('action');
                form.mage('validation', {});

                $("#send_message_button").click(function (e) {
                    e.preventDefault();
                    if (form.validation('isValid')) {
                        $.ajax({
                            url:ajaxurl,
                            type:'POST',
                            showLoader: true,
                            dataType:'json',
                            data: form.serialize(),
                            complete: function (response) {
                                var responseData = $.parseJSON(response.responseText);
                                $("#seller_message_container").html(responseData.content);
                                $('body').trigger('updateMessageCount');
                            },
                            error: function (xhr, status, errorThrown) {
                                console.log('Please try again.');
                            }
                        });
                    }
                });
            }
    }
});
