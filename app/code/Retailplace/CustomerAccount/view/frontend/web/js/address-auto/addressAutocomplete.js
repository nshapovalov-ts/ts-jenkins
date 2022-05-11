define([
        "jquery",
        "Retailplace_CustomerAccount/js/address-auto/address",
        "mage/translate",
        "domReady!"
    ],
    function ($, addressService) {
        'use strict';

        $.widget('retailplace.addressAutocomplete', {
            options: {
                specificCountry: "AU",
                fields: {
                    city: $('#city'),
                    street_1: $('#street_1'),
                    state: $('#region_id'),
                    zipCode: $('#zip'),
                    country: $('#country')
                }
            },

            _create: function () {
                this.initAddressAutocomplete();
            },

            /**
             * Render Address Autocomplete
             */
            initAddressAutocomplete: function () {
                this.autocomplete = new google.maps.places.Autocomplete(this.element[0]);
                this.autocomplete.setComponentRestrictions({'country': this.options.specificCountry});
                this.bindListener();
            },

            bindListener: function () {
                let self = this;
                this.autocomplete.addListener('place_changed', function () {
                    let googleAddressJson = self.autocomplete.getPlace(),
                        addressFieldsInfo = addressService(googleAddressJson?.address_components);

                    var streetNumber = googleAddressJson.formatted_address.match(/^(\S*)/);
                    if ($.isArray(streetNumber)) {
                        var street = addressFieldsInfo.route;
                        var result = street.replace(/^(\s*\d+\s*)/, '');
                        addressFieldsInfo.route = streetNumber[0] + " " + result;
                    }
                    self.element.val(googleAddressJson.formatted_address);
                    self.options.fields.country.val(addressFieldsInfo.country).trigger('change');
                    self.options.fields.street_1.val(addressFieldsInfo.route);
                    self.options.fields.zipCode.val(addressFieldsInfo.postal_code);
                    self.options.fields.city.val(addressFieldsInfo.locality);
                    self.options.fields.state.find('option').each(function (item, idx) {
                        if (idx.text === addressFieldsInfo.administrative_area_level_1) {
                            self.options.fields.state.val(idx.value);
                        }
                    });
                });
            }
        });

        return $.retailplace.addressAutocomplete;
    });
