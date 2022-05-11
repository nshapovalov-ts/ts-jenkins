/**
 * @api
 */
define([
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (url, globalMessageList, $t) {
    'use strict';

    return {
        /**
         * @param {Object} response
         * @param {Object} messageContainer
         * @param redirect
         */
        process: function (response, messageContainer, redirect = true) {
            var error;

            messageContainer = messageContainer || globalMessageList;

            if (response.status == 401 && redirect) { //eslint-disable-line eqeqeq
                window.location.replace(url.build('customer/account/edit/'));
            } else {
                try {
                    error = JSON.parse(response.responseText);
                } catch (exception) {
                    error = {
                        message: $t('Something went wrong with your request. Please try again later.')
                    };
                }
                messageContainer.addErrorMessage(error);
            }
        }
    };
});
