/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return {
        /**
         * Allow only tab and numbers
         * If input was changed, launch the callback function
         */
        onKeyUp: function (event, updateCallback) {
            let key = event.which;
            let inputs = $(event.target).add($(event.target).nextAll()).add($(event.target).prevAll());
            inputs.each(function(i, input) {
                let isValid = input.validity.valid;
                if (!isValid) {
                    input.value = '';
                }
            });

            if (!this.isNum(key) && !this.isDelete(key) && !this.isBackspace(key)) {
                return true;
            }

            if (typeof updateCallback === 'function') {
                updateCallback();
            }
            return true;
        },

        collectValue: function (element) {
            let inputs = $(element).find('input'),
                result = '';

            inputs.each(function (i, input) {
                result += $(input).val();
            });

            return result;
        },

        splitValue: function (element, value) {
            let inputs = $(element).find('input'),
                result = '';

            inputs.each(function (i, input) {
                $(input).val(value.charAt(i));
            });

            return result;
        },

        updateValue: function (event) {
            let parent = $(event.target).parent();
            this.splitValue(parent, this.collectValue(parent));
        },

        onKeyDown: function (event) {
            let key = event.which;

            if (this.isTab(key)) {
                return true;
            }

            if (this.isRightArrow(key)) {
                this.goToNext($(event.target));

                return false;
            }

            if (this.isLeftArrow(key)) {
                this.goToPrev($(event.target));

                return false;
            }

            if (this.isDelete(key) || this.isBackspace(key)) {
                $(event.target).val('');
                this.updateValue(event);
                this.goToPrev($(event.target));

                return false;
            }

            if (this.isNum(key)) {
                // Numpad
                if (key >= 96) {
                    key -= 48;
                }
                $(event.target).val(String.fromCharCode(key));
                this.goToNext($(event.target));

                return false;
            }

            return true;
        },

        onPaste: function (event, updateCallback) {
            event.preventDefault();
            let pastedData = event.originalEvent.clipboardData.getData('text')
                .replace(/[^0-9]*/g, '');
            let inputs = $(event.target).add($(event.target).nextAll());
            let focusedInput = $(event.target);

            inputs.each(function (i, input) {
                let value = pastedData.charAt(i);
                $(input).val(value);
                if (value) {
                    focusedInput = this.goToNext(focusedInput);
                }
            }.bind(this));

            if (typeof updateCallback === 'function') {
                updateCallback();
            }

            return false;
        },

        goToPrev: function (element) {
            let prev = $(element).prev();
            if (prev && prev.length) {
                prev.focus();
            }
            return prev;
        },

        goToNext: function (element) {
            let next = $(element).next();
            if (next && next.length) {
                next.focus();
            }
            return next;
        },

        isNum: function (key) {
            return (key >= 48 && key <= 57) || (key >= 96 && key <= 105);
        },

        isTab(key) {
            return key === 9;
        },

        isDelete(key) {
            return key === 46;
        },

        isBackspace(key) {
            return key === 8;
        },

        isLeftArrow(key) {
            return key === 37;
        },

        isRightArrow(key) {
            return key === 39;
        }
    };
});
