define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Ui/js/lib/core/class',
    'Magento_Customer/js/customer-data'
], function ($, wrapper,quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader, Class, customerData) {
    'use strict';

    return function(targetModule){
        var onCheckout = targetModule.prototype.onCheckout;
        var onCheckoutWrapper = wrapper.wrap(onCheckout, function(original,resolve, reject, args){
            fullScreenLoader.startLoader();
            var payload = null;
            /** Checkout for guest and registered customer. */

            try{
                storage.get(
                    window.checkoutConfig.payment.zipmoneypayment.checkoutUri
                ).done(function (data) {
                    resolve({
                        data: {redirect_uri: data.redirect_uri}
                    });
                }).fail(function (data) {
                    window.location.href = window.checkout.shoppingCartUrl;
                    reject();
                }).always(function (data) {
                    fullScreenLoader.stopLoader();
                });
            } catch(e){
                console.log(e);
            }
        });
        targetModule.prototype.onCheckout = onCheckoutWrapper;
        return targetModule;
    };
});
