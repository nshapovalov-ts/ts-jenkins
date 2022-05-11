define([
    'ko'
], function (ko) {
    'use strict';

    /**
     * @abstract
     */
    return {
        dependsToShow: [],
        clear: function () {
            this.dependsToShow = [];
        },
        add: function (relationName, optionValue) {
            var result = false;
            jQuery.each(this.dependsToShow, function(index, value) {
                if (value[0] === relationName && value[1] === optionValue) {
                    result = true;
                }
            });

            if (result === false) {
                this.dependsToShow.push([relationName, optionValue]);
            }
        },
        get: function () {
            return this.dependsToShow;
        },
        isExist: function (relationName, optionValue) {
            var result = false;
            jQuery.each(this.dependsToShow, function(index, value) {
                if (value[0] === relationName && value[1] === optionValue) {
                    result = true;
                    return false;
                }
            });

            return result;
        }
    };
});