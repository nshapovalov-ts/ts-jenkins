define([
    'mage/utils/wrapper',
    'jquery',
    'mage/translate',
    'uiRegistry'
], function (wrapper, $, $t, uiRegistry) {
    'use strict';

    return function (shippingValidator) {
        shippingValidator.initFields = wrapper.wrapSuper(shippingValidator.initFields, function (formPath) {
            var self = this;
            this._super(formPath);
            uiRegistry.async(formPath + '.street')(self.bindStreetValidation.bind(self));
        });

        shippingValidator.bindStreetValidation = function (element) {
            var self = this;
            $.each(element.elems(), function (index, elem) {
                self.bindStreetValidationHandler(elem);
            });
        };

        shippingValidator.bindStreetValidationHandler = function (element) {
            var self = this;
            element.on('value', function () {
                clearTimeout(self.validateStreetTimeout);
                self.validateStreetTimeout = setTimeout(function () {
                    element.warn(false);
                    if (element.value().toLowerCase().indexOf('po box') !== -1) {
                        element.warn($t("Our Suppliers do not support deliveries to Po Box's. Please add an alternative shipping address."));
                    }
                }, 500);
            });
        }

        return shippingValidator;
    };
});
