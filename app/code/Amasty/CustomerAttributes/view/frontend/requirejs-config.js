var config = {
    config: {mixins: {
        'Magento_Checkout/js/action/set-shipping-information': {
            'Amasty_CustomerAttributes/js/action/set-shipping-information-mixin': true
        },
        'Magento_Checkout/js/view/shipping-information/address-renderer/default': {
            'Amasty_CustomerAttributes/js/view/shipping-information/address-renderer/default-mixin': true
        }
    }
}
};
