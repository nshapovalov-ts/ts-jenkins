/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery'
    ],
    function($) {
        'use strict';
        return {
            config : {
                /**
                 * arrays with keys:
                 *      'parent_attribute_id'
                 *      'parent_attribute_code'
                 *      'parent_option_id'
                 *      'depend_attribute_id'
                 *      'depend_attribute_code'
                 *      'parent_attribute_element_uid'
                 *      'depend_attribute_element_uid'
                 */
            },
            indexedElements : [],
            /**
             * @param {Object[]} options
             * @param {Object[]} options.depends
             * @returns {jquery}
             */
            init: function (options) {
                this.config = options.depends;
                this.initElements();
                return this;
            },
            // init parent element listeners
            initElements: function() {
                var different = [];
                $.each(this.config, function(key, relation) {
                    var element = this.getElement(relation.parent_attribute_element_uid);
                    if (element != void(0) && element.length && $.inArray(element.selector, different) == -1) {
                        different.push(element.selector);
                        element.on('change', function (event) {
                            this.observer(event);
                            this.indexedElements = [];
                        }.bind(this));
                        // for custom check
                        element.on('check_relations', this.observer.bind(this));
                        element.find('input,select').each(function (key, input) {
                            $(input).trigger("check_relations");
                        });
                        this.indexedElements = [];
                    }
                }.bind(this));
            },
            getElement: function (id) {
                return $('[data-ui-id="' + id + '"]');
            },
            observer: function (event) {
                var element = $(event.target);
                var block = $(event.currentTarget);
                if (element && block) {
                    var elementId = block.attr('data-ui-id');
                    this.runDependencies(element, elementId);
                }
            },
            runDependencies: function (element, elementId) {
                // Find dependents elements
                var elementDependencies = this.findElementRelations(elementId);
                // Iterate throw elements and show required elements
                $.each(elementDependencies, function(key, relation) {
                    if (this.getElement(relation.depend_id).length) {
                            // Multiselect and select
                        if (this.isCanShow(relation)) {
                            this.showBlock(relation.depend_id);
                        } else if (this.indexedElements.indexOf(relation.depend_id) < 0) {
                            this.hideBlock(relation.depend_id);
                        }

                    }
                }.bind(this));
            },
            isCanShow: function (relationToShow) {
                var parentRelations = this.findElementParentRelations(relationToShow);
                var result = true;

                // check all parent elements
                $.each(parentRelations, function(key, relation) {
                    var block = this.getElement(relation.parent_id);
                    if (result && block.length) {
                        result = !!(this.checkCheckbox(block.find('input'), relation) || this.checkSelect(block.find('select'), relation));
                    }
                }.bind(this));

                return result;
            },
            /*
             * check for Checkbox, radio
             */
            checkCheckbox: function (elementSet, relation) {
                var result = false;
                $.each(elementSet, function(key, element) {
                    if (!result) {
                        element = $(element);
                        result = element.val() == relation.value && element.is(':checked') === true && element.is(":visible");
                    }
                });
                return result;
            },
            /*
             * check for select, multiselect
             */
            checkSelect: function (element, relation) {
                if (!element.length) {
                    return false;
                }
                return element.val() != void(0) && element.val().indexOf(relation.value) != -1 && element.is(":visible")
            },

            hideBlock: function (id) {
                var element = this.getElement(id);
                element.hide();
                element.find('input,select').each(function (key, input) {
                    $(input).trigger("check_relations");
                });
            },
            showBlock: function (id) {
                var element = this.getElement(id);
                element.show();
                this.indexedElements.push(id);
                element.find('input,select').each(function (key, input) {
                    $(input).trigger("check_relations");
                });
            },
            findElementRelations: function (elementUId) {
                var elements = [];
                $.each(this.config, function(key, item) {
                    if (item.parent_attribute_element_uid == elementUId) {
                        var el = {
                            'depend_id': item.depend_attribute_element_uid,
                            'value': item.parent_option_id
                        };
                        elements.push(el);
                    }
                });
                return elements;
            },
            findElementParentRelations: function (elementUId) {
                var elements = [];
                $.each(this.config, function(key, item) {
                    if (item.depend_attribute_element_uid == elementUId.depend_id
                        && item.parent_option_id == elementUId.value
                    ) {
                        var el = {
                            'parent_id': item.parent_attribute_element_uid,
                            'depend_id': item.depend_attribute_element_uid,
                            'value': item.parent_option_id
                        };
                        elements.push(el);
                    }
                });
                return elements;
            }
        }
    }
);
