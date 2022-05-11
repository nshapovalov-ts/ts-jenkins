/*
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

var config = {
    config: {
        mixins: {
            'StripeIntegration_Payments/js/view/payment/method-renderer/stripe_payments': {
                'Retailplace_Stripe/js/view/payment/method-renderer/stripe_payments_mixin': true
            },
            'Magento_Ui/js/view/messages': {
                'Retailplace_Stripe/js/messages-mixin': true
            },
        }
    }
};
