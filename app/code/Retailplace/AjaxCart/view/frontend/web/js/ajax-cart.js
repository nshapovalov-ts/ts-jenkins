/**
 * Retailplace_AjaxCart
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/mage'
], function ($, getTotalsAction, customerData, $t, alert) {
    'use strict';

    return function (config, element) {
        var form = $(element);
        var postForm = $('#saveZip');
        var formData = new FormData(form[0]);
        var flag = true;

        form.on('submit', function (e) {
            e.preventDefault();
        });

        $(document).on('ajax:updateCartItemQty', function (e) {
            updateCart();
        });

        /*Remove item from cart and move to wishlist*/
        $('.actions-toolbar a.action').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var url = JSON.parse(this.getAttribute('data-post')).action;
            var requestData = JSON.parse(this.getAttribute('data-post')).data;
            requestData.form_key = formData.get('form_key');

            $.ajax({
                url: url,
                data: $.param(requestData),
                type: 'post',
                showLoader: true,
                complete: function () {
                    updateCart();
                }
            });
        });

        function updateCart() {
            if (flag) {
                flag = false;
            } else {
                return;
            }
            formData = new FormData(form[0]);

            $.ajax({
                url: '/checkout/cart/updatePostAjax/',
                data: formData,
                type: 'post',
                showLoader: true,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    $('.supplier-checkout').each(function(index, elem) {
                        var $elem = $(elem);
                        var elemGroupId = $elem.attr('group-id');

                        if (elemGroupId && !Object.keys(data.groups).includes(elemGroupId)) {
                            $elem.closest('tr').remove();
                        }
                    });

                    $.each(data.groups, function (group_id, group_data) {
                        $('.shop-header[group-id="' + group_id + '"] .shipping_limitation td').html(group_data.shipping_limitation);
                        $('.shop-header[group-id="' + group_id + '"] .cart_shipping_right').html(group_data.shipping_types);
                        $('.supplier-checkout[group-id="' + group_id + '"] .checkout-one-brand').remove();
                        $('.shop-header[group-id="' + group_id + '"] .quote-request').remove();
                        $('.supplier-checkout[group-id="' + group_id + '"]').html(group_data.seller_checkout);
                    });
                    $('#block-shipping-heading .postcode').html(data.postcode);
                    $('.selle-all').replaceWith(data.groups.totals_html);
                    customerData.reload(['cart'], true);
                    $('.action.primary.checkout span').text(data.checkout_button_text);

                    $.each(data.items, function (item_id, item_data) {
                        if ($("#shopping-cart-table .item-" + item_id).length === 0) {
                            location.reload();
                        }
                        $("#shopping-cart-table .item-" + item_id + " .col.price .price").html(item_data.price);
                        $("#shopping-cart-table .item-" + item_id + " .col.subtotal .price").html(item_data.row_total);
                        $("#cart-" + item_id + "-qty").val(item_data.qty);
                        $("#cart-" + item_id + "-qty").attr('data-initial-qty',item_data.qty);
                        $(".cart.item-" + item_id + " .item-messages").remove();
                        var messages = createItemMessages(item_data.messages);
                        $(".cart.item-" + item_id).append(messages);
                    });
                    updateTotals();
                    updateCheckoutButton(data.checkout_allowed);
                    removeDeletedItems(data);
                },
                error: function() {
                    alert({
                        content: $t("Could not update cart")
                    })
                },
                complete: function () {
                    flag = true;
                    $(document.body).trigger('processStop');
                }
            });
        }

        function createItemMessages(messages) {
            var messageBlock = $('<tr>').addClass('item-messages')
            $.each(messages, function (indx, message) {
                messageBlock.append($('<td>').addClass('item-message-wrapper').attr('colspan', 5).append(
                        $('<div>').addClass('cart item message').addClass(message.type).append(
                            $('<div>').text(message.text)
                        )
                    )
                );
            });

            return messageBlock;
        }

        function updateTotals() {
            var deferred = $.Deferred();
            getTotalsAction([], deferred);
        }

        function updateCheckoutButton(checkout_allowed) {
            var button = $('.action.primary.checkout');
            if (checkout_allowed) {
                button.removeAttr('disabled');
                button.removeClass('disabled');
            } else {
                button.attr('disabled', true);
                button.addClass('disabled');
            }
        }

        function removeDeletedItems(data) {
            $('tbody.shop-header').each(function (indx, elem) {
                if (Object.keys(data.groups).indexOf(elem.getAttribute('group-id')) === -1) {
                    elem.remove();
                }
            });

            $('tbody.cart.item').each(function (indx, elem) {
                if (Object.keys(data.items).indexOf(elem.getAttribute('item-id')) === -1) {
                    elem.remove();
                }
            });

            if (Object.keys(data.items).length === 0) {
                location.reload();
            }
        }
    };
});
