define(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'mage/url',
        'mage/translate',
        'mage/validation'
    ],
    function ($, modal, url) {
        'use strict';
        return {
            validate: function () {
                var enabledModule = window.checkoutConfig.orderverify.enabledModule;
                var paymentMethods = window.checkoutConfig.orderverify.paymentMethods;
                var selectedPaymentMethod = jQuery('input[name="payment[method]"]:checked').val();
                var mobileNumber = window.checkoutConfig.orderverify.mobileNumber;

                if(mobileNumber == "" || mobileNumber == null){
                    mobileNumber = $("input[name=telephone]").val();
                    $("#orderVerifyMobValue").value = $("input[name=telephone]").val();
                }

                var orderNotification = false;

                if (enabledModule == false || jQuery.inArray(selectedPaymentMethod, paymentMethods) == -1) {
                    return true;
                }

                if (jQuery('#verified').val() == 0) {
                    var titleText = $.mage.__('Order Verification');
                    var quoteforsave = {
                        type: 'popup',
                        modalClass: 'sms-order-verify-popup',
                        responsive: true,
                        innerScroll: true,
                        clickableOverlay: true,
                        title: titleText,
                        buttons: [{
                            text: $.mage.__('Verify'),
                            class: 'action primary',
                            click: function () {
                                var dataForm = $('#otp-verification-form');
                                var ignore = null;
                                var otp = jQuery('#otpvalue').val();
                                var mobile = jQuery('#orderVerifyMobValue').val();
                                var linkUrl = url.build('smspro/customer/checkoutotpnumber');
                                $.ajax({
                                    url: linkUrl,
                                    type: 'POST',
                                    data: {otp: otp,mobile :mobile },
                                    showLoader: true,
                                    success: function (response) {
                                        if (response.success) {
                                            orderNotification = true;
                                            jQuery('.messages.otp-error').hide();
                                            jQuery('.messages.otp-resend').hide();
                                            jQuery('.messages.otp-senderror').hide();
                                            jQuery('.messages.otp-success').show();
                                            jQuery('#verified').val(1);
                                            setTimeout(function () {
                                                $('.sms-order-popup-verification').modal('closeModal');
                                            }, 2000);
                                            $(".payment-method._active").find('.action.primary.checkout').trigger('click');
                                        } else {
                                            jQuery('.messages.otp-success').hide();
                                            jQuery('.messages.otp-resend').hide();
                                            jQuery('.messages.otp-senderror').hide();
                                            jQuery('.messages.otp-error').show();
                                        }
                                    }
                                });
                            }
                        }
                            ,
                            {
                                text: $.mage.__('Resend'),
                                class: 'action primary',
                                click: function () {
                                    var ignore = null;
                                    var linkUrl = url.build('smspro/customer/checkoutotp');
                                    $.ajax({
                                        url: linkUrl,
                                        type: 'POST',
                                        data: {resend: 1,mobile : $('#orderVerifyMobValue').val()},
                                        showLoader: true,
                                        success: function (response) {
                                            if (response.success) {
                                                jQuery('.messages.otp-success').hide();
                                                jQuery('.messages.otp-error').hide();
                                                jQuery('.messages.otp-senderror').hide();
                                                jQuery('.messages.otp-resend').show();
                                            } else {
                                                jQuery('.messages.otp-success').hide();
                                                jQuery('.messages.otp-error').hide();
                                                jQuery('.messages.otp-resend').hide();
                                                jQuery('.messages.otp-senderror').show();
                                            }

                                        }
                                    });
                                }
                            }
                        ]
                    };
                    $('.orderVerifySendOtp').click(function () {
                        jQuery('.messages.otp-senderror').hide();
                        if(!$('#orderVerifyMobValue').val() || $('#orderVerifyMobValue').val().length < 8 || !$.isNumeric($('#orderVerifyMobValue').val()) || $('#orderVerifyMobValue').val().substring(0,1)=='+' || $('#orderVerifyMobValue').val().substring(0,1)=='-'){
                            jQuery('.messages.otp-senderror').show();
                            return this;
                        }
                        $.ajax({
                            url: url.build('smspro/customer/checkoutotp'),
                            type: 'POST',
                            data: {resend: 0,mobile : $('#orderVerifyMobValue').val()},
                            showLoader: true,
                            success: function (response) {
                                if (response.success) {
                                    jQuery('.messages.otp-success').hide();
                                    jQuery('.messages.otp-error').hide();
                                    jQuery('.messages.otp-senderror').hide();
                                    jQuery('.messages.otp-resend').show();
                                } else {
                                    jQuery('.messages.otp-success').hide();
                                    jQuery('.messages.otp-error').hide();
                                    jQuery('.messages.otp-resend').hide();
                                    jQuery('.messages.otp-senderror').show();
                                }
                            }
                        });
                    });
                    var quoteforsave = modal(quoteforsave, $('.sms-order-popup-verification'));

                    $('.sms-order-popup-verification').modal('openModal').on('modalclosed', function () {
                        orderNotification = true;
                    });
                } else {
                    orderNotification = true;
                }
                return orderNotification;
            }
        };
    }
);