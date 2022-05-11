define([
    'jquery'
], function ($) {

    return {
        getData: function () {
            return {
                "firstname": $('input[name="firstname"]').val(),
                "lastname": $('input[name="lastname"]').val(),
                "custom_attributes": {
                    "phone_number": $('input[name="phone_number"]').val()
                }
            }
        }
    }
});