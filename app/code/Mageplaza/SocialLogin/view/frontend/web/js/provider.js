/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * What I found in my quick research is:


 In magento 1 it was quite easy to get the admin session on frontend since magento had no restrictions when it expose the admin session cookie.



 But in magento2, the admin cookie will get exposed only for the admin path, for example /admin, because of this, you won't be able to access the admin session on frontend.



 But as an alternate solution, you can try:

 Build an admin route & a controller for you module
 When frontend loads, send an ajax request to the admin controller and check the auth for isLoggedIn()
 Initialize your plugin / related code via JS through ajax callback.
 *
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

define(
    [
        'jquery',
        'Magento_Customer/js/customer-data'
    ], function ($, customerData) {
        'use strict';

        /**
         * @param url
         * @param windowObj
         */
        window.updateQueryStringParameter = function (uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
                return uri + separator + key + "=" + value;
            }
        }
        window.updateURL = function () {
            var myParam = location.search.split('social=');
            if (myParam.length > 1) {
                if (history.pushState) {
                    var parameter = 'social';
                    var url=document.location.href;
                    var urlparts = url.split('?');

                    if (urlparts.length >= 2) {
                        var urlBase = urlparts.shift();
                        var queryString = urlparts.join("?");

                        var prefix = encodeURIComponent(parameter) + '=';
                        var pars = queryString.split(/[&;]/g);
                        for (var i = pars.length; i-- > 0;)
                            if (pars[i].lastIndexOf(prefix, 0) !== -1)
                                pars.splice(i, 1);
                        url = urlBase + '?' + pars.join('&');
                    }
                    window.history.pushState({path: url}, document.title, url);
                    return url;
                }
            }
            return;

        }
        window.updateURL();
        window.socialCallback = function (url, windowObj) {
            customerData.invalidate(['customer']);
            customerData.reload(['customer'], true);
            if (window.redirect_url != null) {
                window.redirect_url = window.updateQueryStringParameter(window.redirect_url, "social", "1");
                window.location.replace(window.redirect_url);
            } else if (url !== '') {
                window.location.replace(url);
            } else {
                window.location.reload(true);
            }

            windowObj.close();
        };

        return function (config, element) {
            var model = {
                initialize: function () {
                    var self = this;
                    $(element).on(
                        'click', function () {
                            self.openPopup();
                        }
                    );
                },

                openPopup: function () {
                    window.redirect_url = config.redirect_url;
                    window.open(config.url, config.label, this.getPopupParams());
                },

                getPopupParams: function (w, h, l, t) {
                    this.screenX = typeof window.screenX !== 'undefined' ? window.screenX : window.screenLeft;
                    this.screenY = typeof window.screenY !== 'undefined' ? window.screenY : window.screenTop;
                    this.outerWidth = typeof window.outerWidth !== 'undefined' ? window.outerWidth : document.body.clientWidth;
                    this.outerHeight = typeof window.outerHeight !== 'undefined' ? window.outerHeight : (document.body.clientHeight - 22);
                    this.width = w ? w : 500;
                    this.height = h ? h : 420;
                    this.left = l ? l : parseInt(this.screenX + ((this.outerWidth - this.width) / 2), 10);
                    this.top = t ? t : parseInt(this.screenY + ((this.outerHeight - this.height) / 2.5), 10);

                    return (
                        'width=' + this.width +
                        ',height=' + this.height +
                        ',left=' + this.left +
                        ',top=' + this.top
                    );
                }
            };
            model.initialize();

            return model;
        };
    }
);
