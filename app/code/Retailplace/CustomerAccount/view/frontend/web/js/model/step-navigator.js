define([
    'jquery',
    'ko'
], function ($, ko) {

    return {

        /**
         * Sets window location hash.
         *
         * @param {String} hash
         */
        setHash: function (hash) {
            window.location.hash = hash;
        },
    }
})