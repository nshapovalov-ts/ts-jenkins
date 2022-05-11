/*
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

var config = {
    config: {
        mixins: {
            'StripeIntegration_Payments/js/view/payment/method-renderer/stripe_payments': {
                'Retailplace_MobileVerification/js/view/payment/method-renderer/stripe_payments_mixin': true
            }
        }
    }
};
