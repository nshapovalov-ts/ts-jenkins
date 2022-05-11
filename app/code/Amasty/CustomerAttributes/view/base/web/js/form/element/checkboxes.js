/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (http://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

define([
    'ko',
    'underscore',
    'uiRegistry',
    'mageUtils',
    'Magento_Ui/js/form/element/abstract',
    'Amasty_CustomerAttributes/js/form/relationAbstract'
], function (ko, _, registry, utils, Abstract, relationAbstract) {
    'use strict';

    return Abstract.extend(relationAbstract).extend({

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            var defaultValue = this.value;
            this._super();
            var value = this.value;
            this.value = ko.observableArray([]).extend(value);
            this.value(this.normalizeData(defaultValue));
            return this;
        },

        /**
         * Splits incoming string value.
         *
         * @returns {Array}
         */
        normalizeData: function (value) {
            if (utils.isEmpty(value)) {
                value = [];
            }

            return _.isString(value) ? value.split(',') : value;
        },

        /**
         * Defines if value has changed
         *
         * @returns {Boolean}
         */
        hasChanged       : function () {
            var value = this.value(),
                initial = this.initialValue;

            return !utils.equalArrays(value, initial);
        },
        elementCheck: function(relation) {
            return (this.value().indexOf(relation.option_value) >= 0 && this.visible());
        }
    });
});
