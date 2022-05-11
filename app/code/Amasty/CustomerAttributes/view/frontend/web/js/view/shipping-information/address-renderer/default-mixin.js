define([
    'underscore',
    'uiRegistry'
], function (_, uiRegistry) {
    'use strict';

    return function(targetModule){
        //if targetModule is a uiClass based object
        return targetModule.extend({
            getTemplate:function()
            {
                var result = this._super(); //call parent method
                var attributes = uiRegistry.get('amastyCustomerAttributes');

                _.each(attributes, function (value, index) {
                    if (this.address._latestValue !== null) {
                        if (this.address._latestValue.customAttributes !== void (0)
                            && this.address._latestValue.customAttributes[value] !== void (0)
                        ) {
                            delete this.address._latestValue.customAttributes[value];
                        }
                        if (this.address._latestValue.custom_attributes !== void (0)
                            && this.address._latestValue.custom_attributes[value] !== void (0)
                        ) {
                            delete this.address._latestValue.custom_attributes[value];
                        }
                    }
                }.bind(this));

                return result;
            }
        });
    };
});
