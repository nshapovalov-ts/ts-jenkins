/**
 * Mirakl_Variantsfix
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function(targetModule){

        var reloadPrice = targetModule.prototype._reloadPrice;
        var reloadPriceWrapper = wrapper.wrap(reloadPrice, function(original){
            var result = original();
            var simpleSku = this.options.spConfig.skus[this.simpleProduct];
            var simpleName = this.options.spConfig.names[this.simpleProduct];
            var simpleRetailPrice = this.options.spConfig.retail_price[this.simpleProduct];
            var leadTimeToShip = this.options.spConfig.lead_time_to_ship[this.simpleProduct];
            var description = this.options.spConfig.descriptions[this.simpleProduct];
            var oldDescription = this.options.spConfig.configurable_description;
            var margin = this.options.spConfig.margin[this.simpleProduct];
            var leadTime  = function(days) {
                let leadTime = '';
                if (days) {
                    let daysFormat = (days === 1) ? ' day' : ' days'
                    leadTime = days.toString() + daysFormat;
                }

                return leadTime;
            }
            /*var shortDescription = this.options.spConfig.short_descriptions[this.simpleProduct];
            var oldShortDescription = this.options.spConfig.configurable_short_description;*/
            if(margin){
                $('.product-info-stock-sku .margin').replaceWith(margin);
            }
            if(simpleSku != '') {
                $('div.product-info-main .sku .value').html(simpleSku);
            }
            if(simpleName != '') {
                $('div.product-info-main .page-title .base .name-tag').html(simpleName);
            }
            var descriptionSelect = $('#description .product.attribute.description .value');

            if(description != '' && typeof description != 'undefined') {
                descriptionSelect.html(description);
            }else{
                descriptionSelect.html(oldDescription);
            }

            $('.product-promotions').addClass('hidden');
            $('.product-promotions.promotions-' + simpleSku).removeClass('hidden');

            /*var shortDescriptionSelect = $('.product.attribute.overview [itemprop="description"]');

            if(shortDescription != '' && typeof shortDescription != 'undefined') {
                shortDescriptionSelect.html(shortDescription);
            }else{
                shortDescriptionSelect.html(oldShortDescription);
            }*/

            let isShowRetailPrice = false;

            if (simpleRetailPrice) {
                let retailPrice = parseFloat(simpleRetailPrice.replace(/[^0-9a-z.]/gi, '')) ;
                isShowRetailPrice = (retailPrice > 0);
            }
            $(".ship-time").text(leadTime(leadTimeToShip));
            if (isShowRetailPrice) {
                if($('div.product-info-main .retail-price .retail-price').length < 1){
                    let html = '<div class="retail-price">\n' +
                        '            <span class="retail-price-text">RRP </span>\n' +
                        '            <span class="retail-price">'+ simpleRetailPrice +'</span>\n' +
                        '        </div>';
                    $('.offer-price-description').after(html);

                }else{
                    $('div.product-info-main .retail-price .retail-price').html(simpleRetailPrice);
                }
            }else{
                if($('div.product-info-main .retail-price .retail-price').length > 0){
                    $('div.product-info-main .retail-price').hide();
                }
            }
            return result;
        });
        targetModule.prototype._reloadPrice = reloadPriceWrapper;
        return targetModule;
    };
});
