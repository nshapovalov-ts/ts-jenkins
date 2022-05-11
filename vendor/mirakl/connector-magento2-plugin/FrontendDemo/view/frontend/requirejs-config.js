var config = {
    map: {
        "*": {
            "basePriceBox": "Magento_Catalog/js/price-box",
            "baseConfigurable": "Magento_ConfigurableProduct/js/configurable",
            "baseSwatchRenderer": "Magento_Swatches/js/swatch-renderer",
            "Magento_Checkout/js/action/set-shipping-information": "Mirakl_FrontendDemo/js/action/set-shipping-information",
            "Magento_Checkout/js/view/shipping": "Mirakl_FrontendDemo/js/view/checkout/shipping",
            "Magento_Swatches/js/swatch-renderer": "Mirakl_FrontendDemo/js/swatch-renderer",
            "priceBox": "Mirakl_FrontendDemo/js/price-box",
            "configurable": "Mirakl_FrontendDemo/js/configurable"
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-service': {
                'Mirakl_FrontendDemo/js/model/shipping-service-mixin': true
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Mirakl_FrontendDemo/js/model/checkout-data-resolver-mixin': true
            }
        }
    }
};
