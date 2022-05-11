/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/resource-url-manager': {
                'Retailplace_MultiQuote/js/api-urls': true
            },
            'Magento_Checkout/js/model/step-navigator': {
                'Retailplace_MultiQuote/js/step-navigator': true
            }
        }
    },
    map: {
        '*': {
            'multiQuoteManagement':
                'Retailplace_MultiQuote/js/multi-quote-management',
            'Magento_Checkout/js/action/set-payment-information-extended':
                'Retailplace_MultiQuote/js/set-payment-information-extended',
            'Magento_Checkout/js/action/place-order':
                'Retailplace_MultiQuote/js/place-order',
            'Magento_Checkout/js/action/get-payment-information':
                'Retailplace_MultiQuote/js/get-payment-information'
        }
    }
};
