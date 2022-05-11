define([
    'jquery',
    'mage/url',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Retailplace_CustomerAccount/js/model/password',
    'Retailplace_CustomerAccount/js/action/logout',
    'Retailplace_CustomerAccount/js/model/error-processor',
    'Retailplace_CustomerAccount/js/model/full-screen-loader',
], function (
    $,
    url,
    storage,
    customerData,
    changePasswordData,
    actionLogout,
    errorProcessor,
    fullScreenLoader
) {
    var serviceUrl = url.build('rest/V1/customers/me/changeEmailAndPassword');

    return function (deferred) {

        $.ajax({
            type: "post",
            url: serviceUrl,
            data: JSON.stringify(changePasswordData.getData()),
            dataType: 'json',
            showLoader: true,
            contentType: 'application/json',
            /**
             * Response handler
             * @param {Object} response
             */
            success: function (response) {
                if (response) {
                    window.customerData.email = response.customer.email;
                    if (response.change_password_status) {
                        deferred = actionLogout(deferred);
                        $.when(deferred).done(function () {
                            customerData.invalidate(['cart', 'customer']);
                            var referer = 'referer/' + window.editUrlEncode;
                            var loginUrl = 'customer/account/login/' + referer +'/';
                            window.location.replace(url.build(loginUrl));
                        })
                    } else {
                        deferred.resolve();
                    }
                }
            }
        }).fail(function (response) {
            deferred.reject();
            errorProcessor.process(response, null, false);
        }).always(function () {
            fullScreenLoader.stopLoader()
        });

        return deferred.promise();
    }
})
