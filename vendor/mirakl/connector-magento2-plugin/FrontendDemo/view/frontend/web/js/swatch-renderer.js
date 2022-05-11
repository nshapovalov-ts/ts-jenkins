define([
    'jquery',
    'underscore',
    'baseSwatchRenderer'
], function ($, _) {
    'use strict';

    $.widget('mage.SwatchRenderer', $.mage.SwatchRenderer, {
        _init: function () {
            this._super();

            if (this.inProductList) {
                this.offerInput = $('<input type="hidden" name="offer_id" />');
                this.productForm.append(this.offerInput);
            }
        },

        _Rebuild: function () {
            this._super();
            this._FilterAllOffers();
        },

        _GetOptionsSelected: function ($widget) {
            var prefix = $widget.element.find('div[data-attribute-id]').length ? 'data-' : '';
            return $widget.element.find('.' + $widget.options.classes.attributeClass + '[' + prefix + 'option-selected]');
        },

        _OnClick: function ($this, $widget) {
            this._super($this, $widget);

            if (this.inProductList) {
                this.offerInput.val('');

                var options = _.object(_.keys($widget.optionsMap), {}),
                    $optionsSelected = this._GetOptionsSelected($widget);

                $optionsSelected.each(function () {
                    var prefix = $(this)[0].hasAttribute('data-attribute-id') ? 'data-' : '';
                    var attributeId = $(this).attr(prefix + 'attribute-id');
                    options[attributeId] = $(this).attr(prefix + 'option-selected');
                });

                var result = $widget.options.jsonConfig.optionPrices[_.findKey($widget.options.jsonConfig.index, options)];

                if (result && result.offerData) {
                    this.offerInput.val(result.offerData.offerId);
                }
            }
        },

        _FilterAllOffers: function () {
            if (!$('#marketplace_offers').size()) {
                return;
            }

            var $widget = this,
                options = _.object(_.keys($widget.optionsMap), {}),
                optionsLength = Object.keys(options).length,
                $optionsSelected = this._GetOptionsSelected($widget);

            if (optionsLength !== $optionsSelected.length) {
                return;
            }

            var $productOffers = $('#product-offers').hide(),
                $offersTab = $('#tab-label-marketplace_offers').hide(),
                $productOffersChoicebox = $('#product-offers-choicebox').hide(),
                $productOffersChoiceboxTable = $('#choicebox-product-offers-list'),
                $offerListElements = $('#product-offers-list').find('tr.offer'),
                $offerChoiceBoxElements = $productOffersChoiceboxTable.find('tr.offer'),
                nbOfferChoiceBoxElementsMax = $productOffersChoiceboxTable.data('max-offers');

            $optionsSelected.each(function () {
                var prefix = $(this)[0].hasAttribute('data-attribute-id') ? 'data-' : '';
                var attributeId = $(this).attr(prefix + 'attribute-id');
                options[attributeId] = $(this).attr(prefix + 'option-selected');
            });

            var result = $widget.options.jsonConfig.optionPrices[_.findKey($widget.options.jsonConfig.index, options)];
            result = result.offerData;

            $offerChoiceBoxElements.hide().filter('.sku-' + result.productSku).show();
            // Hide BuyBox offer
            if (result.type === 'offer') {
                $offerChoiceBoxElements.filter('.offer-' + result.offerId).hide();
            }

            var $shown = $offerListElements.hide().filter('.sku-' + result.productSku).show();

            // Show offers content if the tab is hidden
            if (!$offersTab.is(':visible')) {
                $offersTab.show();
                $offersTab.click();
                $productOffers.show();
            }

            // Show offers content if the choicebox is hidden
            if (!$productOffersChoicebox.is(':visible')) {
                $productOffersChoicebox.show();
            }

            var $offerChoiceBoxElementsVisible = $offerChoiceBoxElements.filter(':visible');
            var offerChoiceBoxElementsVisibleLength = $offerChoiceBoxElementsVisible.length;

            if (offerChoiceBoxElementsVisibleLength < 1 ||
                (offerChoiceBoxElementsVisibleLength === 1 && result.type === 'offer' && $offerListElements.length === 1)
            ) {
                $productOffersChoicebox.hide();
            } else {
                $productOffersChoicebox.show();

                // we must hide the others and recalculate the right elements visible
                if (offerChoiceBoxElementsVisibleLength > nbOfferChoiceBoxElementsMax) {
                    $offerChoiceBoxElements.filter(':visible:gt(' + (nbOfferChoiceBoxElementsMax - 1) + ')').hide();
                }

                // define "X offer from Y" label
                $('.product-offers-summary-number').html($shown.length);
                var firstPrice = $offerChoiceBoxElementsVisible.first().find('.offer-price .price').first().text();
                $('.switch > span.price').html(firstPrice);
            }
        },

        _getPrices: function (newPrices, displayPrices) {
            displayPrices = this._super(newPrices, displayPrices);

            if (typeof(newPrices) === 'undefined') {
                return displayPrices;
            }

            var mkPrices = newPrices,
                offerData = {};
            if (newPrices.offerData) {
                offerData = newPrices.offerData;
            } else {
                var products = this._CalcProducts(),
                    optionPrices = this.options.jsonConfig.optionPrices;
                if (products.length == 1 && optionPrices[products[0]].offerData) {
                    mkPrices = optionPrices[products[0]];
                    offerData = mkPrices.offerData;
                }
            }

            if (offerData['type'] === 'offer') {
                displayPrices['offer'] = {
                    minShippingPrice: mkPrices['minShippingPrice'].amount,
                    minShippingPriceInclTax: mkPrices['minShippingPriceInclTax'].amount,
                    priceAdditionalInfo: offerData['priceAdditionalInfo'],
                    conditionLabel: offerData['conditionLabel'],
                    qty: offerData['stock'],
                    offerId: offerData['offerId'],
                    soldBy: offerData['soldBy'],
                    soldByUrl: offerData['soldByUrl'],
                    shopEvaluationsCount: offerData['shopEvaluationsCount'],
                    shopEvaluation: offerData['shopEvaluation']
                };
            }

            return displayPrices;
        }
    });

    return $.mage.SwatchRenderer;
});
