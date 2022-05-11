define(
    [
        'jquery',
        'ko',
        'uiComponent'
    ],
    function ($, ko, Component) {
        'use strict';
        var enabledModule = window.checkoutConfig.orderverify.enabledModule;
        var mobileNumber = window.checkoutConfig.orderverify.mobileNumber;

        return Component.extend({
            defaults: {
                template: 'Magecomp_Smspro/orderverify/verify'
            },
            canVisibleOrderVerify: enabledModule,
            mobileNumber: mobileNumber
        });
    }
);
