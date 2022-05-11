var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/cart/shipping-estimation': {
                'Retailplace_MiraklSeller/js/view/cart/shipping-estimation': true
            },
            'ZipMoney_ZipMoneyPayment/js/action/place-zip-order': {
                'Retailplace_MiraklSeller/js/action/place-zip-order': true
            }
        }
    }
};
