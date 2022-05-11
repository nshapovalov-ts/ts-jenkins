define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magecomp_Smspro/js/model/validate'
    ],
    function (Component, additionalValidators, orderVerifyValidation) {
        'use strict';
        additionalValidators.registerValidator(orderVerifyValidation);

        return Component.extend({});
    }
);
