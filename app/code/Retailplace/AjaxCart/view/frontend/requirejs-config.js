/**
 * Retailplace_AjaxCart
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/update-shopping-cart': {
                'Retailplace_AjaxCart/js/update-shopping-cart': true
            }
        }
    },
    map: {
        "*": {
            "Magento_SalesRule/template/cart/totals/discount.html":
                "Retailplace_AjaxCart/template/cart/totals/discount.html"
        }
    }
};
