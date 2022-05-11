define(
    [
        'underscore',
        'Magento_Checkout/js/view/cart/shipping-rates',
        'Mirakl_FrontendDemo/js/model/quote-helper'
    ],
    function (_, Component, quoteHelper) {
        'use strict';

        return Component.extend({
            /**
             * @override
             */
            initObservable: function () {
                var self = this;

                this._super();

                this.shippingRates.subscribe(function (rates) {
                    self.shippingRateGroups([]);
                    _.each(rates, function (rate) {
                        if (!quoteHelper.isFullMarketplaceQuote() || rate['carrier_code'] !== 'freeshipping') {
                            var carrierTitle = rate['carrier_title'];

                            if (self.shippingRateGroups.indexOf(carrierTitle) === -1) {
                                self.shippingRateGroups.push(carrierTitle);
                            }
                        }
                    });
                });

                return this;
            }
        });
    }
);
