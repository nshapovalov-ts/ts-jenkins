/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (http://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

define([
    'ko',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'Amasty_CustomerAttributes/js/form/relationAbstract'
], function (ko, _, registry, Abstract, relationAbstract) {
    'use strict';

    return Abstract.extend(relationAbstract).extend({

        /**
         * Calls 'initObservable' of parent, initializes 'options' and 'initialOptions'
         *     properties, calls 'setOptions' passing options to it
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();
            return this;
        }
    });
});
