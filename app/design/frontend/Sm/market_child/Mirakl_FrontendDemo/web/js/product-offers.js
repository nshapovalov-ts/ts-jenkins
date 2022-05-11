/**
 * Mirakl_FrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 *
 * @see FrontendDemo/view/frontend/templates/product/view/mirakl_offers.phtml
 */

define([
        'jquery',
        'domReady!'
    ], function ($) {
        'use strict';
        $('.add-offer-to-cart').prop('disabled', false);
        $('.add-offer-to-cart').on('click', function (e) {
            e.preventDefault();
            // Remove error on input
            var removeError = function (element) {
                element.removeClass('mage-error')
                    .removeAttr('aria-describedby')
                    .removeAttr('aria-invalid');
            };
            $('.offer .mage-error').each(function () {
                removeError($(this));
            });

            var $form = $('#product_addtocart_form');

            // Add offer input in buybox if it does not exist
            var $offerInput = $form.find('#offer-id');
            if ($offerInput.size() === 0) {
                $offerInput = $('<input type="hidden" name="offer_id" id="offer-id" />');
                $form.append($offerInput);
            }

            var $offerId = $(this).attr('data-offer');
            //$(this).data('offer');
            $offerInput.val($offerId);

            var $qtyInput = null;
            var $offerQty = null;
            if ($(this).parents('.offer').size()) {
                var $qty = 1;
                $qtyInput = $('#qty');

                var $offerClass = null;
                if ($(this).hasClass('choicebox')) {
                    $offerClass = '#qty-choicebox-' + $offerId;
                } else if ($(this).data('product')) {
                    $offerClass = '#qty-operator-' + $(this).data('product');
                } else {
                    $offerClass = '#qty-' + $offerId;
                }

                $offerQty = $(this).parents('.offer:first').find($offerClass);
                if ($offerQty && $offerQty.val()) {
                    $qty = $offerQty.val();
                }

                $qtyInput.data('old-qty', $qtyInput.val());
                $qtyInput.data('old-validate', $qtyInput.data('validate'));
                $qtyInput.val($qty);

                var $offerRules = $offerQty.data('validate');

                // Set offer rule for marketplace only
                if ($offerQty.hasClass('marketplace-offer')) {
                    $qtyInput.data('validate', $offerRules);
                    $qtyInput.rules('add', $offerRules);
                }
            }

            $form.submit();

            if ($qtyInput) {
                $qtyInput.val($qtyInput.data('old-qty'));

                if ($offerQty.hasClass('marketplace-offer')) {
                    $qtyInput.data('validate', $qtyInput.data('old-validate'));
                    $qtyInput.rules('add', $qtyInput.data('old-validate'));
                }

                if ($qtyInput.is('.mage-error')) {
                    $offerQty.addClass('mage-error')
                        .attr('aria-describedby', $qtyInput.attr('aria-describedby'))
                        .attr('aria-invalid', $qtyInput.attr('aria-invalid'));
                    removeError($qtyInput);
                }

                $offerQty.focus();
            }

            return false;
        });
    }
)
