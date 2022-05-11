/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    var extender = {
        /**
         * @inheritdoc
         */
        setShippingRates: function (originFn, ratesData) {
            originFn(ratesData);

            // Fix radio buttons are disabled in checkout after switching address
            $('.table-checkout-shipping-method input[type=radio]').prop('disabled', false);
        }
    };

    return function (target) {
        return wrapper.extend(target, extender);
    };
});
