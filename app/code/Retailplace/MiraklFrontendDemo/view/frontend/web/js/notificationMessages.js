define([
        'jquery',
        'mage/url',
        'jquery/jquery.cookie',
        'jquery/jquery-storageapi'
    ], function ($, url) {

        return {

            /**
             * Init
             *
             * @param interval
             * @param selectors
             */
            init: function (interval, selectors) {
                this.url = url.build('/rest/V1/customers/me/messages/notification', {});
                this.selectors = selectors;
                this.interval = 15;
                this.cookieName = '_nmc';
                this.isChanged = true;

                if (typeof (interval) !== "undefined") {
                    this.interval = interval;
                }

                this.updateMessageCount();
                setInterval(this.updateMessageCount.bind(this), 60 * 1000);

                var self = this;
                $('body').on('updateMessageCount', function () {
                    self.updateMessageCount();
                });

                return this;
            },

            /**
             * Get New Messages Count
             */
            getNewMessagesCount: function () {
                var self = this;
                $.ajax({
                    url: self.url,
                    type: 'GET',
                    dataType: 'json',
                    complete: function (response) {
                        var responseData = $.parseJSON(response.responseText);
                        if (typeof (responseData.new_messages_count) !== "undefined" && responseData.new_messages_count !== null) {
                            self.updateCounter(responseData.new_messages_count);
                        }
                    },
                    error: function (xhr, status, errorThrown) {
                        console.log('Please try again.');
                    }
                });
            },

            /**
             * Set Cookie
             *
             * @param key
             * @param value
             */
            setCookie: function (key, value) {
                var check_cookie = $.cookieStorage.get(key);
                var date = new Date();
                date.setTime(date.getTime() + this.interval * 60);
                $.cookieStorage.setPath('/');
                if (check_cookie) {
                    $.cookieStorage.set(key, value);
                } else {
                    $.cookieStorage.setExpires(date);
                    $.cookieStorage.set(key, value);
                }
            },

            /**
             * Get Cookie
             *
             * @param key
             * @returns {*}
             */
            getCookie: function (key) {
                return $.cookieStorage.get(key);
            },

            /**
             * Update Message Count
             */
            updateMessageCount: function () {
                let messagesCount = this.getCookie(this.cookieName);
                if (messagesCount === null) {
                    this.getNewMessagesCount();
                } else {
                    if (this.isChanged) {
                        this.updateCounter(messagesCount);
                    }
                }
            },

            /**
             * Update Counter
             *
             * @param count
             */
            updateCounter: function (count) {
                if (typeof (count) !== "undefined" && count !== "") {
                    if (!this.isChanged) {
                        this.setCookie(this.cookieName, count);
                        this.isChanged = true;
                    }
                }

                if (this.isChanged && typeof (this.selectors) === "object") {
                    $.each(this.selectors, function (index, value) {
                        let el = $(value);
                        if (el.length > 0) {
                            el.html(count);

                            if (count > 0) {
                                el.show();
                            } else {
                                el.hide();
                            }
                        }
                    });
                    this.isChanged = false;
                }

            }
        }
    });
