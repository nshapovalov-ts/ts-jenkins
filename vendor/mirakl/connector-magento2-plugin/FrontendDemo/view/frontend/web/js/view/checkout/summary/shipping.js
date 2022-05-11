define(
    [
        'Magento_Tax/js/view/checkout/summary/shipping'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            /**
             * @override
             */
            getShippingMethodTitle: function() {
                return '';
            }
        });
    }
);
