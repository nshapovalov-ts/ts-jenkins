/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'mage/utils/wrapper',
    'multiQuoteManagement'
], function (
    wrapper,
    multiQuoteManagement
) {
    'use strict';

    /**
     * Add extra params to Checkout URL
     */
    return function (stepNavigator) {
        stepNavigator.navigateTo = wrapper.wrapSuper(
            stepNavigator.navigateTo,
            function(code, scrollToElementId) {
                multiQuoteManagement.updateCheckoutUrl();

                return this._super(code, scrollToElementId);
            }
        );

        return stepNavigator;
    }
});
