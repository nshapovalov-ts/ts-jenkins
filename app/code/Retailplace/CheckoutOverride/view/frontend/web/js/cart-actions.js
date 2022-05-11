/**
 * Retailplace_CheckoutOverride
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

define([
    'jquery',
    'uiClass',
    'domReady!'
], function ($, Class) {

    "use strict";

    return Class.extend({
        /**
         * Component Init
         */
        initialize: function () {
            this.initEventHandlers();
        },
        /**
         * Cart Update Action
         */
        triggerUpdate: function () {
            $('#form-validate').prop('is_valid', true);
            $('.cart-container .cart.main.actions .action.update').click();
        },
        /**
         * Increase Amount Button Handler
         *
         * @param {HTMLElement} button
         * @returns {boolean}
         */
        increaseQty: function (button) {
            const qtyBlock = $(button).closest('.qty-block'),
                input = qtyBlock.find('.qty-input'),
                currentQty = parseInt(input.val(), 10),
                packQty = parseInt(input.data('unit-per-pack'), 10);

            input.val(currentQty + packQty);
            this.triggerUpdate();

            return true;
        },
        /**
         * Decrease Amount Button Handler
         *
         * @param {HTMLElement} button
         * @returns {boolean}
         */
        decreaseQty: function (button) {
            const qtyBlock = $(button).closest('.qty-block'),
                input = qtyBlock.find('.qty-input'),
                currentQty = parseInt(input.val(), 10),
                packQty = parseInt(input.data('unit-per-pack'), 10),
                minimumQty = parseInt(input.data('minimum-qty'), 10);

            if (currentQty > minimumQty) {
                input.val(currentQty - packQty);
                this.triggerUpdate();
            }

            return true;
        },
        /**
         * Update Qty Field Handler
         *
         * @param {HTMLElement} qtyInput
         * @returns {boolean}
         */
        setQty: function (qtyInput) {
            const qtyBlock = $(qtyInput).closest('.qty-block'),
                input = $(qtyInput),
                currentQty = parseInt(input.val(), 10),
                packQty = parseInt(input.data('unit-per-pack'), 10),
                minimumQty = parseInt(input.data('minimum-qty'), 10),
                errorBlock = qtyBlock.find('.error-message-pack-cart'),
                initialQty = parseInt(input.data('initial-qty'), 10);

            if (currentQty % packQty === 0 && currentQty >= minimumQty) {
                errorBlock.hide();
                input.data('initial-qty', currentQty);
                this.triggerUpdate();
            } else {
                errorBlock.show();
                input.val(initialQty);
            }

            return true;
        },
        /**
         * Init all Handlers
         */
        initEventHandlers: function () {
            const self = this;

            $('.increaseQty').click(function () {
                $.proxy(self.increaseQty(this), self);
            });

            $('.decreaseQty').click(function () {
                $.proxy(self.decreaseQty(this), self);
            });

            $('.qty-input').change(function () {
                $.proxy(self.setQty(this), self);
            });

            $('#shopping-cart-table .cart_shipping_right').on('click', '.seller-shipping', function () {
                self.triggerUpdate();
            });

            $('#form-validate').on("submit", function () {
                if ($('#form-validate').prop('is_valid')) {
                    return true;
                }

                let input = $(':focus');
                if (input.length > 0) {
                    $('#form-validate').prop('is_valid', false);
                    $.proxy(self.setQty(input), this);
                }

                return false;
            });

            $(document).on("ajax:updateCartItemQty", function () {
                $('#form-validate').prop('is_valid', true);
            });

            $(document.body).on("processStop", function () {
                $('#form-validate').prop('is_valid', false);
            });

        }
    });
})
