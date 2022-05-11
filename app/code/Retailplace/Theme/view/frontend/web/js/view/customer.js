define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function (ko, Component, customerData, $t) {
    'use strict';
    var getCustomerInfo = function () {
        var customer = customerData.get('customer');

        return customer();
    };
    var getCustomerInfo = function () {
        var customer = customerData.get('customer');

        return customer();
    };

    var isLoggedIn = function (customerInfo) {
        customerInfo = customerInfo || getCustomerInfo();

        return customerInfo && customerInfo.firstname;
    };
    var isLoggedIn = function (customerInfo) {
        customerInfo = customerInfo || getCustomerInfo();

        return customerInfo && customerInfo.firstname;
    };
    return Component.extend({

        message: {
            loggedMessage: $t('Welcome, %1!')
        },
        htmlLoggedMessage: ko.observable(),
        isLoggedIn: ko.observable(),
        isRun: ko.observable(),
        customer: ko.observable({}),

        initialize: function () {
            this.isRun(false);
            this._super();
            this.initSubscribers();
            if (this.isCustomerLogged != 0) {
                this.isLoggedIn(true);
            } else {
                this.isLoggedIn(false);
            }
            this.checkCustomerLocalStorage();
        },
        initObservable: function () {
            this._super().observe(
                'isLoggedIn'
            );
            return this;
        },

        /**
         * initialize subscribers
         */
        initSubscribers: function () {
            var self = this;
            self.isLoggedIn.subscribe(
                function (isLoggedIn) {
                    if (isLoggedIn == true) {
                        jQuery('.action.showcart.mincart').show();
                        jQuery('.login-sinup .top.my_account_link').show();

                        jQuery('.action.showcart.seller-sign-up').hide();
                        jQuery('.login-sinup .loginBtn,.login-sinup .signUpBtn').hide();

                        jQuery('.mobile-menu-button .mobile-login-btn').hide();
                        jQuery('.mobile-menu-button .mobile-signup-btn').hide();
                        jQuery('.mobile-menu-button .mobile-logout-btn').show();
                        if (!jQuery('body').hasClass('is_customer_login')) {
                            jQuery('body').addClass('is_customer_login')
                        }

                    } else {
                        jQuery('.action.showcart.mincart').hide();
                        jQuery('.login-sinup .top.my_account_link').hide();

                        jQuery('.action.showcart.seller-sign-up').show();
                        jQuery('.login-sinup .loginBtn,.login-sinup .signUpBtn').show();

                        jQuery('.mobile-menu-button .mobile-logout-btn').hide();
                        jQuery('.mobile-menu-button .mobile-login-btn').show();
                        jQuery('.mobile-menu-button .mobile-signup-btn').show();
                        if (jQuery('body').hasClass('is_customer_login')) {
                            jQuery('body').removeClass('is_customer_login')
                        }
                    }
                }
            );
        },
        /**
         * Check customer localstorage
         */
        checkCustomerLocalStorage: function () {

            var customerInfo = getCustomerInfo();
            var self = this;
            if (customerInfo && customerInfo.data_id) {
                if (isLoggedIn()) {
                    self.isLoggedIn(true);
                }
            } else {
                customerData.reload(['customer'], false).done(function () {
                    if (isLoggedIn()) {
                        self.isLoggedIn(true);
                    }
                }).fail(function () {
                    self.isLoggedIn(false);
                });
            }


            if (this.isLoggedIn() == false && customerInfo.fullname != undefined && this.isRun() == false) {
                this.isRun(true);
                customerData.reload(['customer'], false).done(function () {
                    if (isLoggedIn()) {
                        self.isLoggedIn(true);
                    }
                }).fail(function () {
                    self.isLoggedIn(false);
                });
            }
        }
    });
});
