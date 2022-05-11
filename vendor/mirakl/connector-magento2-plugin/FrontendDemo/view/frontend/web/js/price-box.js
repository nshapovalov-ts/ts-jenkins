define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'basePriceBox'
], function ($, utils) {
    'use strict';

    $.widget('mage.priceBox', $.mage.priceBox, {
        _init: function initPriceBox() {
            var box = this.element;
            var $addToCartButton = $('#product-addtocart-button');

            if (!$addToCartButton.length || !$addToCartButton.has('data-offer')) {
                box.trigger('updatePrice');
            }

            this.cache.displayPrices = utils.deepClone(this.options.prices);
        },

        reloadPrice: function reDrawPrices() {
            this._super();
            this.reloadOffer();
        },

        /**
         * Render min shipping price and price additional info and stock and state
         */
        reloadOffer: function reloadOffer() {
            var $priceBox = $('.product-info-main'),
                offerData,
                $offerListElements = $('#product-offers-list').find('tr.offer');

            if (this.cache.additionalPriceObject.prices) {
                offerData = this.cache.additionalPriceObject.prices.offer;
            } else {
                var boxForm = jQuery('#product_addtocart_form'),
                    boxButton = boxForm.find('#product-addtocart-button');

                if (boxForm.size() && boxButton.data('offer') && boxForm.data('mageConfigurable')) {
                    // Checkbox are used
                    var prices = boxForm.data('mageConfigurable').options.spConfig.optionPrices,
                        offerId = boxButton.data('offer'),
                        offerData;

                    prices = _.find(prices, function(price){ return price.offerData.offerId == offerId; });
                    if (prices) {
                        offerData = prices.offerData;
                        offerData.qty = offerData.stock;
                        offerData.minShippingPrice = prices.minShippingPrice.amount;
                        offerData.minShippingPriceInclTax = prices.minShippingPriceInclTax.amount;
                    }
                }
            }

            var $offerListElementsVisible = $offerListElements.filter(':visible');
            if ($offerListElementsVisible.length) {
                $('.product-info-price .price-box').html(
                    $offerListElementsVisible.find('td.price').first().html()
                );
            }

            if (offerData) {
                $priceBox.find('#product-addtocart-button').data('offer', offerData.offerId);

                $priceBox.find('div.offer-price-description').html(offerData.priceAdditionalInfo);
                $priceBox.find('.product-info-stock-sku .stock-available-offer').html(offerData.qty);
                $priceBox.find('.product-info-stock-sku .offer-state-label').html(offerData.conditionLabel);
                $priceBox.find('.offer-shop-name').html(offerData.soldBy);
                $priceBox.find('.offer-seller-name > a').attr('href', offerData.soldByUrl);

                if (offerData.shopEvaluationsCount > 0) {
                    var $rating = $priceBox.find('.offer-data .rating-result');
                    $rating.show();
                    $rating.attr('title', offerData.shopEvaluation + '%');
                    $rating.children('span:eq(0)').css('width', offerData.shopEvaluation + '%');
                    $rating.children('span:eq(1)').text(offerData.shopEvaluation);

                    $priceBox.find('.offer-data .offer-seller-rating').show().find('.number').text(offerData.shopEvaluation);
                } else {
                    $priceBox.find('.offer-data .rating-result').hide();
                    $priceBox.find('.offer-data .offer-seller-rating').hide();
                }

                $priceBox.find('.offer-wrapper').show();
                $priceBox.find('.price-box .offer-price-description').show();
                $priceBox.find('.product-info-stock-sku .stock.offer').show();
                $priceBox.find('.product-info-stock-sku .offer-state').show();
                $priceBox.find('.offer-data').show();
                $priceBox.find('.prices-tier').hide();
            } else {
                $priceBox.find('#product-addtocart-button').data('offer', '');
                $priceBox.find('.price-box .offer-wrapper').hide();
                $priceBox.find('.price-box .offer-price-description').hide();
                $priceBox.find('.product-info-stock-sku .stock.offer').hide();
                $priceBox.find('.product-info-stock-sku .offer-state').hide();
                $priceBox.find('.offer-data').hide();
                $priceBox.find('.prices-tier').show();
            }
        }
    });

    return $.mage.priceBox;
});
