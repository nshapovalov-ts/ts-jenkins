
/**
 * Mirakl_FrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'basePriceBox'
], function ($, utils) {
    'use strict';

    $.widget('mage.priceBox', $.mage.priceBox, {
        _init: function initPriceBox() {
            var box = this.element,
                $addToCartButton = $('#product-addtocart-button');

            if (!$addToCartButton.length || !$addToCartButton.has('data-offer')) {
                box.trigger('updatePrice');
            }

            this.cache.displayPrices = utils.deepClone(this.options.prices);
        },

        reloadPrice: function reDrawPrices() {
            this._super();
            this.reloadOffer();
        },
        getFormattedPrice: function (price) {
            var priceFormat = {
                decimalSymbol: '.',
                groupLength: 3,
                groupSymbol: ",",
                integerRequired: false,
                pattern: "$%s",
                precision: 2,
                requiredPrecision: 2
            };

            return utils.formatPrice(price, priceFormat);
        },
        /**
         * Get Offer price from range depends on Qty
         */
        getActiveOfferPrice: function(offerData, qty) {
            var priceRanges,
                discountRanges,
                offerPrice = null;

            if (offerData && offerData.priceRanges) {
                priceRanges = offerData.priceRanges.split(',');
                priceRanges.sort(function(a, b) {
                    return a.split('|')[0] - b.split('|')[0];
                });

                priceRanges.forEach(function(value, key) {
                    let quantity = value.split('|')[0],
                        tierPrice = parseFloat(value.split('|')[1]);

                    if (qty >= quantity) {
                        offerPrice = tierPrice;
                    }
                });

                if (offerData.discountRanges) {
                    discountRanges = offerData.discountRanges.split(',');
                    discountRanges.sort(function(a, b) {
                        return a.split('|')[0] - b.split('|')[0];
                    });
                    discountRanges.forEach(function(value, key) {
                        let quantity = value.split('|')[0],
                            tierPrice = parseFloat(value.split('|')[1]);

                        if (qty >= quantity && tierPrice < offerPrice) {
                            offerPrice = tierPrice;
                        }
                    });
                }
            }

            return offerPrice;
        },
        /**
         * Render min shipping price and price additional info and stock and state
         */
        reloadOffer: function reloadOffer() {
            var $priceBox = $('.product-info-main'),
                offerData,
                offerExists,
                $offerListElements = $('#product-offers-list').find('tr.offer'),
                boxForm,
                boxButton,
                prices,
                offerId,
                qty,
                offerprice,
                oldPrice,
                calfinal,
                $offerListElementsVisible,
                $rating;

            try {
                offerExists = typeof this.cache.additionalPriceObject.prices.offer != "undefined";
            } catch (e) {
                // And we're able to catch the Error it would normally throw for
                // referencing a property of undefined
                offerExists = false;
            }
            if (offerExists) {
                offerData = this.cache.additionalPriceObject.prices.offer;
            } else {
                boxForm = jQuery('#product_addtocart_form');
                boxButton = boxForm.find('#product-addtocart-button');
                if (boxForm.size() && boxForm.data('mageConfigurable')) {
                    // Checkbox are used
                    prices = boxForm.data('mageConfigurable').options.spConfig.optionPrices;
                    offerId = boxButton.attr('data-offer');

                    if (offerId && prices) {
                        prices = _.find(prices, function (price) {
                            return price.offerData.offerId == offerId;
                        });
                    }

                    if (offerId && prices) {
                        qty = Number.isNaN(parseInt($('.showdroupdownqty').val())) ? parseInt($('.showtextqty').val()) : parseInt($('.showdroupdownqty').val());
                        offerprice = prices.finalPrice['amount'];
                        oldPrice = prices.oldPrice['amount'];
                        $priceBox.find('.offer-price .price').text(this.getFormattedPrice(offerprice));
                        $priceBox.find('.offer-price .price').parent().find('.offer-old-price').remove();
                        if (oldPrice && oldPrice > offerprice) {
                            $priceBox.find('.offer-price .price').after(' <span class="offer-old-price"><span class="price">'
                                + this.getFormattedPrice(oldPrice)
                                + '</span></span>'
                            );
                        }

                        let offerTierPrice = this.getActiveOfferPrice(prices.offerData, qty);
                        if (offerTierPrice !== null) {
                            offerprice = offerTierPrice;
                        }

                        calfinal = qty * offerprice;

                        $(".priceccal").text($.mage.__('Add %1 to Cart').replace('%1', this.getFormattedPrice(calfinal)));
                        boxButton.prop('disabled', false);
                        offerData = prices.offerData;
                        offerData.qty = offerData.stock;
                        offerData.minShippingPrice = prices.minShippingPrice.amount;
                        offerData.minShippingPriceInclTax = prices.minShippingPriceInclTax.amount;
                    } else {
                        $(".priceccal").text($.mage.__('Unavailable'));
                        boxButton.prop('disabled', true);
                    }
                }
            }

            $offerListElementsVisible = $offerListElements.filter(':visible');
            if ($offerListElementsVisible.length) {
                $('.product-info-price .price-box').html(
                    $offerListElementsVisible.find('td.price').first().html()
                );
            }

            if (offerData) {
                $priceBox.find('#product-addtocart-button').attr('data-offer', offerData.offerId);

                $priceBox.find('div.offer-price-description').html(offerData.priceAdditionalInfo);
                $priceBox.find('.product-info-stock-sku .stock-available-offer').html(offerData.qty);
                $priceBox.find('.product-info-stock-sku .offer-state-label').html(offerData.conditionLabel);
                $priceBox.find('.offer-shop-name').html(offerData.soldBy);
                $priceBox.find('.offer-seller-name > a').attr('href', offerData.soldByUrl);

                if (offerData.shopEvaluationsCount > 0) {
                    $rating = $priceBox.find('.offer-data .rating-result');
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
                //$priceBox.find('.price-box .offer-price-description').show();
                $priceBox.find('.product-info-stock-sku .stock.offer').show();
                $priceBox.find('.product-info-stock-sku .offer-state').show();
                $priceBox.find('.offer-data').show();
                $priceBox.find('.prices-tier').hide();
                $priceBox.find('.prices-tier').removeClass('d-none');
                $priceBox.find('.prices-tier.product-' + offerData.productSku).show();
            } else {
                //$priceBox.find('#product-addtocart-button').attr('data-offer', '');
                $priceBox.find('.price-box .offer-wrapper').hide();
                $priceBox.find('.price-box .offer-price-description').hide();
                $priceBox.find('.product-info-stock-sku .stock.offer').hide();
                $priceBox.find('.product-info-stock-sku .offer-state').hide();
                $priceBox.find('.offer-data').hide();
                $priceBox.find('.prices-tier').hide();
            }
        }
    });

    return $.mage.priceBox;
});
