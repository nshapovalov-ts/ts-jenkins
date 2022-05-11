define([
    'Retailplace_CustomerAccount/js/model/steps',
    'Retailplace_CustomerAccount/js/model/customer-data',
    'Retailplace_CustomerAccount/js/model/personal-info',
    'Retailplace_CustomerAccount/js/model/business-info',
    'Retailplace_CustomerAccount/js/model/preferences',
], function (steps, customerData, personalInfo, businessInfo, preferences) {

    return function (step) {
        var payload = {},
            data = {};
        payload.need_validate_approval = false;
        payload.need_validate_address = false;

        if (step === 'register') {
            data = personalInfo.getData();
        } else if (step === 'business_info'){
            data = businessInfo.getData();
            payload.need_validate_address = true;
        } else if (step === 'preferences') {
            data = preferences.getData();
            payload.need_validate_address = true;
            payload.need_validate_approval = true;
        } else if (step === 'finish') {
            payload.customer = {}
        }

        if (!data.custom_attributes) {
            data.custom_attributes = {
                "registration_step": step
            }
        } else {
            data.custom_attributes.registration_step = step;
        }
        payload.customer = {...customerData, ...data};

        return payload;
    }
});
