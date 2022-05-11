define([
    'jquery',
    'mage/url',
    'mage/storage',
    'Retailplace_CustomerAccount/js/model/password',
    'Retailplace_CustomerAccount/js/model/error-processor',
    'Retailplace_CustomerAccount/js/model/full-screen-loader',
], function (
    $,
    url,
    storage,
    changePasswordData,
    errorProcessor,
    fullScreenLoader
) {
    var logoutUrl = url.build('customer/ajax/logout');

    return function (deferred) {

        $.ajax({
            type: "get",
            cache: false,
            showLoader: true,
            url: logoutUrl,
            /**
             * Response handler
             * @param {Object} response
             */
            success: function (response) {
                if (response.message) {
                    deferred.resolve();
                } else {
                    deferred.reject();
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
