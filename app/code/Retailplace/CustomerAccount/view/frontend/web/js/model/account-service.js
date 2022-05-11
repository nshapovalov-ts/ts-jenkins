define([
    'mage/storage',
    'Retailplace_CustomerAccount/js/model/full-screen-loader',
], function (storage, fullScreenLoader) {

    return function (serviceUrl, payload) {
        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl, JSON.stringify(payload)
        ).fail(
            function (response) {

            }
        ).success(
            function (response) {

            }
        ).always(
            function () {
                fullScreenLoader.stopLoader();
            }
        );
    }
})