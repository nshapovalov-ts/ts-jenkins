define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict'

    /**
     *
     * @param countryCode
     * @param regionCode
     * @returns {string|null}
     */
    function getRegionId(countryCode, regionCode) {
        let countryData = customerData.get('directory-data'),
            regions_data = countryData()[countryCode]?.regions;

        if (regions_data) {
            for (const [key, value] of Object.entries(regions_data)) {
                if (value.code === regionCode) {
                    return key;
                }
            }
        }
        return null;
    }

    /**
     * Return address information
     */
    return function (address_components) {
        let addressInfo = {
            route: '',
            locality: '',
            administrative_area_level_1: '',
            administrative_area_level_2: '',
            country: '',
            postal_code: ''
        }

        address_components?.forEach(function (item, idx) {

            if (addressInfo.hasOwnProperty(item.types[0])) {
                if (item.types[0] === "country") {
                    addressInfo[item.types[0]] = item.short_name;
                } else {
                    addressInfo[item.types[0]] = item.long_name;
                }
            }
        })

        addressInfo.region_id = getRegionId(addressInfo.country, addressInfo.administrative_area_level_1)
        return addressInfo;
    }
})
