var config = {
    config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'Retailplace_Variantsfix/js/model/skuswitch': true
            },
            'Magento_Swatches/js/swatch-renderer': {
                'Retailplace_Variantsfix/js/model/swatch-skuswitch': true
            }
        }
    }
};
