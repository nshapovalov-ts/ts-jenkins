/**
 * Retailplace_Insider
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        function initEvent() {
            if (typeof Insider !== 'undefined') {
                Insider.eventManager.once('ins-sr:only-api-campaign:load', function (event, data) {
                    $.get(config.url, {
                        "sku[]": data.products.map(function (val) {
                            return val.item_id
                        })
                    }).done(
                        function (data) {
                            $('.insider_wrapper').html(data)
                        }
                    );
                });
            } else {
                setTimeout(initEvent, 1000);
            }
        }
        initEvent();
    };
});
