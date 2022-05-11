define([
    'Magento_Checkout/js/model/url-builder',
    'Retailplace_CustomerAccount/js/model/account-service'
    ], function (urlBuilder, accountService) {

    return function (customerData) {
        var payload = {
            customer: customerData
        };
        var serviceUrl = urlBuilder.createUrl('/V1/customers/me/update', {});

        return accountService(serviceUrl, payload);
    }
})