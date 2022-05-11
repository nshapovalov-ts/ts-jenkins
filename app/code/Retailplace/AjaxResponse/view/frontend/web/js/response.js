/**
 * Retailplace_AjaxResponse
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([], function () {
    'use strict';

    return {
        responsePayload: {},
        /**
         * Init Model
         *
         * @param {Object} data
         * @returns {Object}
         */
        init: function (data) {
            this.responsePayload = data;

            return this;
        },
        /**
         * Is Success Getter
         *
         * @returns {boolean}
         */
        getIsSuccess: function() {
            return !!this.responsePayload['is_success'];
        },
        /**
         * Success Message Getter
         *
         * @returns {String|null}
         */
        getSuccessMessage: function() {
            return this.responsePayload['success_message'] ?? null;
        },
        /**
         * Error Message Getter
         *
         * @returns {String|null}
         */
        getErrorMessage: function() {
            return this.responsePayload['error_message'] ?? null;
        },
        /**
         * Response Getter
         *
         * @param {String|null} key
         * @returns {Object|String}
         */
        getData: function(key = null) {
            let responseData = this.responsePayload['response_data'] ?? null,
                result;

            if (responseData && key) {
                result = responseData[key] ?? null;
            } else {
                result = responseData;
            }

            return result;
        }
    }
})
