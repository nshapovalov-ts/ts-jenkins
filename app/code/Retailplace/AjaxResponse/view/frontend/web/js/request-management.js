/**
 * Retailplace_AjaxResponse
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'uiComponent',
    'jquery',
    'mage/url',
    'mage/translate',
    'ajaxResponse'
], function (Component, $, urlBuilder, $t, ajaxResponse) {
    'use strict';

    return {
        /**
         * Post Ajax Request Wrapper
         *
         * @param {String} url
         * @param {String} data
         * @param {Object} handlers
         */
        sendRequest: function(url, data, handlers) {
            let self = this;

            handlers = this.extendHandlers(handlers);
            $('body').trigger('processStart');
            $.ajax({
                url: this.processUrl(url),
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (data, textStatus, jqXHR) {
                    handlers.success(self.getResponseModel(data), textStatus, jqXHR);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    handlers.error(jqXHR, textStatus, errorThrown);
                },
                complete: function(jqXHR, textStatus) {
                    handlers.complete(jqXHR, textStatus);
                    $('body').trigger('processStop');
                }
            });
        },
        /**
         * Convert Controller path to URL
         *
         * @param {String} url
         * @returns {String}
         */
        processUrl: function(url) {
            if (url.indexOf('http') !== 0) {
                url = urlBuilder.build(url);
            }

            return url;
        },
        /**
         * Set default empty handlers
         *
         * @param {Object} handlers
         * @returns {Object}
         */
        extendHandlers: function(handlers) {
            if (!handlers) {
                handlers = {};
            }

            return $.extend({
                success: function() {},
                error: function() {},
                complete: function() {}
            }, handlers);
        },
        /**
         * Generate Response Model
         *
         * @param {Object} data
         * @returns {Object}
         */
        getResponseModel: function(data) {
            return ajaxResponse.init(data);
        }
    };
})
