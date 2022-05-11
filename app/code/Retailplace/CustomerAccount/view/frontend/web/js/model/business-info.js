define([
    'jquery'
], function ($) {
    return {
        getData: function () {
            var firstname = $('input[name="firstname"]').val(),
                lastname = $('input[name="lastname"]').val(),
                region = $('#region_id').val(),
                country = $("#country").val();

            var customer = {
                "firstname": firstname,
                "lastname": lastname,
                "custom_attributes": {
                    "business_name": $('input[name="business_name"]').val(),
                    'abn': $('input[name="abn"]').val()
                }
            }

            if (region && country) {
                var address = {
                    "customer_id": window.customerData.id,
                    "region_id": region, // RegionId must needs to pass
                    "country_id": country,
                    "street":[
                        $('#street_1').val()
                    ],
                    "firstname": firstname,
                    "lastname": lastname,
                    "telephone": $('input[name="phone_number"]').val(),
                    "city": $('#city').val(),
                    "postcode": $('#zip').val()
                }
                if ($('input[name="address_id"]').val()) {
                    address.id = $('input[name="address_id"]').val();
                }
                customer.addresses = [
                    address
                ]
            }

            return customer;
        }
    }
});
