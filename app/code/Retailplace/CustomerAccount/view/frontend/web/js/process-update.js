define([
    'jquery',
    'mage/url',
    'mage/storage',
    'Retailplace_CustomerAccount/js/model/payload',
    'Retailplace_CustomerAccount/js/model/steps',
    'Retailplace_CustomerAccount/js/model/account-service',
    'Retailplace_CustomerAccount/js/action/change-password',
    'Retailplace_CustomerAccount/js/model/full-screen-loader',
    'Retailplace_CustomerAccount/js/model/error-processor',
    'mage/validation'
], function (
    $,
    url,
    storage,
    payloadData,
    steps,
    accountService,
    changePassword,
    fullScreenLoader,
    errorProcessor
) {
    return function (config) {
        /** @TODO need to bind store code*/
        var serviceUrl = url.build('rest/V1/customers/me/update'),
            $currentStepEl = $('#registration_step'),
            $btnActions = $('.btn-actions'),
            $btnNext = $('#next-step'),
            $btnBack = $('#back-step');
        //Init step
        if (window.registrationStep
            && !window.isChangePassword
            && window.incompleteApplication
        ) {
            var stepIndex = steps.indexOf(window.registrationStep);
            if (steps.length > stepIndex + 1) {
                $currentStepEl.val(steps[stepIndex + 1]);
            } else {
                $currentStepEl.val(window.registrationStep);
            }
        } else {
            $currentStepEl.val('register');
        }

        steps.forEach(function (element, index) {
            if (element === $currentStepEl.val()) {
                $('#' + element).show();
                $btnActions.show();
                //Did you know part
                $('#did-you-know .' + element).show();
            } else {
                $('#' + element).hide();
                //Did you know part
                $('#did-you-know .' + element).hide();
            }
        })
        //Remove loading mask
        $('#edit-loading-mask').remove();

        var approvalConditional = $('.approval-conditional'),
            approvalSuccess = $(".approval-success");
        // Need to hide the back and next button
        if ($currentStepEl.val() === "register") {
            $btnBack.hide();
        }
        if ($currentStepEl.val() === "finish") {
            $btnNext.hide();
            // Show & hide form success and conditional
            if (window.autoApprovalStatus === "approved") {
                approvalConditional.hide();
                approvalSuccess.show();
            } else if (window.autoApprovalStatus === "conditionally_approved") {
                approvalSuccess.hide();
                approvalConditional.show();
            }
        }

        $(function () {

            $('.actions a').on('click', function () {
                var activeIndex = 0;
                code = $currentStepEl.val();

                steps.forEach(function (element, index) {
                    if (element === code) {
                        activeIndex = index;
                    } else {
                        $('#' + element).hide();
                    }
                })
                var elementValue = $(this).data('value');

                if (elementValue === 'next') {

                    if (steps.length > activeIndex + 1) {
                        //Validate form data
                        var $form = $('#' + code).find('form');
                        if (!$form.validation('isValid')) {
                            return false;
                        }
                        //Update data
                        var payload = payloadData(code);
                        fullScreenLoader.startLoader();
                        storage.post(
                            serviceUrl, JSON.stringify(payload)
                        ).fail(
                            function (response) {
                                errorProcessor.process(response);
                            }
                        ).success(
                            function (response) {
                                var deferred = $.Deferred();
                                if (code === 'register' &&
                                    ($('input[name="change_password"]').is(':checked')
                                        || $('input[name="change_email"]').is(':checked'))
                                ) {
                                    deferred = changePassword(deferred);

                                } else {
                                    deferred.resolve();
                                }

                                $.when(deferred).done(function () {
                                    //Need to update default shipping
                                    $('input[name="address_id"]').val(response.default_shipping);

                                    //Show next step
                                    var nextStep = steps[activeIndex + 1];
                                    $('#' + code).hide();
                                    $('#' + nextStep).show();
                                    //Did you know part
                                    $('#did-you-know .' + code).hide();
                                    $('#did-you-know .' + nextStep).show();
                                    $currentStepEl.val(nextStep);
                                    $btnActions.show();
                                    // Show & hide form success and conditional
                                    $.each(response.custom_attributes, function (i, value) {
                                        var customAttibutes = response.custom_attributes[i]
                                        if (customAttibutes.attribute_code === "is_auto_approved_status") {
                                            if (customAttibutes.value === "approved") {
                                                approvalSuccess.show();
                                                approvalConditional.hide();
                                            } else if (customAttibutes.value === "conditionally_approved") {
                                                approvalSuccess.hide();
                                                approvalConditional.show();
                                            }
                                        }
                                    });
                                    //Need to hide the back and next button
                                    if (nextStep !== "register") {
                                        $btnBack.show();
                                    }
                                    if (nextStep === "finish") {
                                        $btnNext.hide();
                                        $btnBack.hide();
                                    }
                                });
                            }
                        ).always(
                            function () {
                                fullScreenLoader.stopLoader();
                            }
                        );
                    }
                } else {
                    if (activeIndex > 0) {
                        var previousStep = steps[activeIndex - 1];
                        $('#' + code).hide();
                        $('#' + previousStep).show();
                        //Did you know part
                        $('#did-you-know .' + code).hide();
                        $('#did-you-know .' + previousStep).show();

                        $currentStepEl.val(previousStep);
                        //Need to hide the back and next button
                        if (previousStep !== "finish") {
                            $btnNext.show();
                        }
                        if (previousStep === "register") {
                            $btnBack.hide();
                        }
                    }
                }
            })
        });
    }
})
