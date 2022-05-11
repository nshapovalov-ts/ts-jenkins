/**
 * Retailplace_Insider
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function (config) {
        function dispatchEvent() {
            if (typeof Insider !== 'undefined') {
                var skus = config.skus.split(',');
                var insider_request = {products: []};
                skus.forEach(function (elem) {
                    insider_request.products.push({item_id: elem});
                })
                Insider.eventManager.dispatch('ins-sr:only-api-campaign:load', insider_request);
            } else {
                setTimeout(dispatchEvent, 1000);
            }
        }

        dispatchEvent();
    };
});
