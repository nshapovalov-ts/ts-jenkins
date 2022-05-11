/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery'
], function ($) {
    return {
        getData: function () {
            var customAttributes = {
                annual_purchasing_spend:[],
                frequently_order:[],
                purchase_priorities:[],
                categories_usually_buy:[],
                industry: [],
                sell_goods: [],
                sell_goods_offline: [],
                currently_goods_online: [],
                tradesquare: [],
                my_network: [],
                business_type: [],
            };
            $('body form').find('input:checked').each(function () {
                var self = $(this),
                parentEl = self.parent().parent().parent(),
                preferenceType = self.attr('name');
                if(parentEl.is(':visible')){
                    if (preferenceType && !customAttributes[preferenceType]) {
                        customAttributes[preferenceType] = [];
                    }
                    customAttributes[preferenceType].push($(this).val());
                }
            });
            customAttributes.abn = $('input[name="abn"]').val();
            customAttributes.lpo_code = $('input[name="lpo_code"]').val();
            return {
                "firstname": $('input[name="firstname"]').val(),
                "lastname": $('input[name="lastname"]').val(),
                "custom_attributes": customAttributes
            }
        }
    }
});
