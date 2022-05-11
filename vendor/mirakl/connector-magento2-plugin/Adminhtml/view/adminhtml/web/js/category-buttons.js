define(['uiComponent', 'mage/translate'], function(Component, $t) {
 
    return Component.extend({
        initialize: function () {
            this._super();
        },

        /**
         * enable mirakl_sync flag for this category and all children
         */
        enableSync: function () {
            this.callAction('enable', $t(this.alertEnableMsg));
        },

        /**
         * disable mirakl_sync flag for this category and all children
         */
        disableSync: function () {
            this.callAction('disable', $t(this.alertDisableMsg));
        },

        /**
         * enable/disable mirakl_sync flag on this category and all children
         *
         * @param {String} action
         * @param {String} alertMsg
         */
        callAction: function (action, alertMsg) {
            var categoryId = this.source.data.entity_id;

            confirmSetLocation(
                alertMsg,
                this.actionUrl+'action/'+action+'/category/'+categoryId+'/'
            );
        }
    });
});