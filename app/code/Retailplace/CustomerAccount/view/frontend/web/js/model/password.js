define([
    'jquery'
], function ($) {
    return {
        getData: function () {

            var customer = {
                "customer_id": window.customerData.id,
                "email": $('input[name="email"]').val(),
                "current_password": $('input[name="current_password"]').val(),
                "new_password": $('input[name="password"]').val(),
                "is_change_email": false,
                "is_change_password": false
            }
            if ($('input[name="change_password"]').is(':checked')) {
                customer.is_change_password = true;
            }
            if ($('input[name="change_email"]').is(':checked')) {
                customer.is_change_email = true;
            }

            return customer;
        }
    }
});
