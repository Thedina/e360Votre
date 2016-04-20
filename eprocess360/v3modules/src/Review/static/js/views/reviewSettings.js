/**
 * Review Settings: Views
 */

/**
 * Backbone view for review types main
 * @typedef {Object} ReviewTypesMainView
 */
var ReviewTypesMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReviewTypesMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        _.each(this.collection.models, function(typeModel) {
            thisView.itemViews.push(new ReviewTypesItemView({model: typeModel}));
        });

        // Listen to collection add model event
        this.listenTo(this.collection, 'add', this.eventTypeAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewTypesMainView}
     */
    render: function() {
        var template, typeList;
        template = Handlebars.templates.reviewTypeListMain;

        this.$el.html(template({meta: hbInitData().meta.Review}));

        typeList = this.$el.find('#review-type-list');

        _.each(this.itemViews, function(itemView) {
            typeList.append(itemView.render().$el);
        });

        return this;
    },
    events: {
        "click #btn-add-review-type": "eventAddType"
    },
    /**
     * Event handler for "New Type" type button
     * @param {Object} e
     */
    eventAddType: function(e) {
        var reviewType = new ReviewTypeModel();
        reviewTypeModal.show(reviewType, this.collection);
    },
    /**
     * Event handler for collection model added
     * @param {ReviewTypeModel} model
     */
    eventTypeAdded: function(model) {
        var newView = new ReviewTypesItemView({model: model});
        this.itemViews.push(newView);
        newView.render().$el.appendTo($('#review-type-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for review types list item
 * @typedef {Object} ReviewTypesItemView
 */
var ReviewTypesItemView = BizzyBone.BaseView.extend({
    /**
     *
     * @param options
     * @returns {ReviewTypesItemView}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTypeChanged);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewTypesItemView}
     */
    render: function() {
        var template = Handlebars.templates.reviewTypeListItem;

        this.renderTemplate({reviewType: this.model.attributes, meta: hbInitData().meta.Review}, template);

        return this;
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-delete": "eventDelete"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {
        e.preventDefault();
        reviewTypeModal.show(this.model);
    },
    /**
     * Event handler for "Delete" button
     * @param {Object} e
     */
    eventDelete: function(e) {
        var thisView = this;
        e.preventDefault();


        bootbox.confirm("Are you sure you want to delete this review type?", function (result) {
            if (result) {
                thisView.model.destroy({
                    wait: true,
                    success: function (model, response, options) {
                        thisView.$el.fadeOut(500, function () {
                            thisView.remove();
                        });
                    },
                    error: function (model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        });
    },
    /**
     * Event handler for model changed
     * @param {ReviewTypeModel} model
     */
    eventTypeChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for review type edit modal
 * @typedef {Object} ReviewTypesModalView
 */
var ReviewTypesModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReviewTypesModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        this.groupList = new GroupList();

        // Listen to group list reset event
        this.listenTo(this.groupList, 'reset', this.eventGroupsLoaded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewTypesModalView}
     */
    render: function() {
        var template = Handlebars.templates.reviewTypeEditModal;
        this.$el.html(template({
            reviewType: this.model.attributes,
            groupTable: this.groupList.toGroupTable(),
            meta: hbInitData().meta.Review
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param typeModel
     * @param typeCollection
     * @returns {ReviewTypesModalView}
     */
    show: function(typeModel, typeCollection) {
        this.model = typeModel;
        this.collection = typeCollection;

        this.groupList.fetch({reset: true});

        return this;
    },
    /**
     * @returns {ReviewTypesModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;

        toSave = {
            title: $('#review-type-edit-name').val(),
            idGroup: $('#review-type-edit-group').val(),
            groupTitle: $("#review-type-edit-group option:selected").text()
        };

        wasNew = this.model.isNew();

        this.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                model.set({groupTitle: toSave.groupTitle});
                if(wasNew) {
                    thisView.collection.add(model);
                }

                thisView.hide();
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for 'Cancel' button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    },
    /**
     * Event handler for group list load/reset
     * @param {GroupList} collection
     */
    eventGroupsLoaded: function(collection) {
        this.render();
        this.$el.children().first().modal('show');
    }
});

/**
 * Backbone view for review rules main
 * @typedef {Object} ReviewRulesMainView
 */
var ReviewRulesMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReviewRulesMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        _.each(this.collection.models, function(ruleModel) {
            thisView.itemViews.push(new ReviewRulesItemView({model: ruleModel}));
        });

        // Listen to collection add model event
        this.listenTo(this.collection, 'add', this.eventRuleAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewRulesMainView}
     */
    render: function() {
        var template, typeList;
        template = Handlebars.templates.reviewRuleListMain;

        this.$el.html(template({meta: hbInitData().meta.Review}));

        typeList = this.$el.find('#review-rule-list');

        _.each(this.itemViews, function(itemView) {
            typeList.append(itemView.render().$el);
        });

        return this;
    },
    events: {
        "click #btn-add-review-rule": "eventAddRule"
    },
    /**
     * Event handler for "New Type" type button
     * @param {Object} e
     */
    eventAddRule: function(e) {
        var reviewRule = new ReviewRuleModel();
        reviewRuleModal.show(reviewRule, this.collection);
    },
    /**
     * Event handler for collection model added
     * @param {ReviewRuleModel} model
     */
    eventRuleAdded: function(model) {
        var newView = new ReviewRulesItemView({model: model});
        this.itemViews.push(newView);
        newView.render().$el.appendTo($('#review-rule-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for review rules list item
 * @typedef {Object} ReviewRulesItemView
 */
var ReviewRulesItemView = BizzyBone.BaseView.extend({
    /**
     *
     * @param options
     * @returns {ReviewRulesItemView}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventRuleChanged);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewRulesItemView}
     */
    render: function() {
        var template = Handlebars.templates.reviewRuleListItem;

        this.renderTemplate({
            reviewRule: this.model.attributes,
            meta: hbInitData().meta.Review
        }, template);

        return this;
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-delete": "eventDelete"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {
        e.preventDefault();
        reviewRuleModal.show(this.model);
    },
    /**
     * Event handler for "Delete" button
     * @param {Object} e
     */
    eventDelete: function(e) {
        var thisView = this;
        e.preventDefault();

        bootbox.confirm("Are you sure you want to delete this rule?", function (result) {
            if (result) {
                thisView.model.destroy({
                    wait: true,
                    success: function (model, response, options) {
                        thisView.$el.fadeOut(500, function () {
                            thisView.remove();
                        });
                    },
                    error: function (model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        });
    },
    /**
     * Event handler for model changed
     * @param {ReviewRuleModel} model
     */
    eventRuleChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for review rule edit modal
 * @typedef {Object} ReviewRulesModalView
 */
var ReviewRulesModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReviewRulesModalView}
     */
    initialize: function(options) {
        if(_.has(options, 'conditionOptions')) this.conditionOptions = options.conditionOptions;
        this.rendered = false;

        this.typeList = new ReviewTypeList();

        // Listen to type list reset event
        this.listenTo(this.typeList, 'reset', this.eventReviewTypesLoaded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewRulesModalView}
     */
    render: function() {
        var thisView, template, conditionList;
        thisView = this;
        template = Handlebars.templates.reviewRuleEditModal;

        this.$el.html(template({
            reviewRule: this.model.attributes,
            reviewTypes: this.typeList.toTypeArray(),
            meta: hbInitData().meta.Review
        }));

        conditionList = this.$el.find('#rule-form-condition-list');

        if(_.isEmpty(this.conditions)) {
            this.addCondition();
        }
        else {
            _.each(this.conditions, function(condition, conditionNum) {
                conditionList.append(thisView.renderCondition(condition, conditionNum));
            });
        }

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * A rule can have multiple conjoined conditions, so they are rendered from
     * a separate template.
     * @param {Object} condition
     * @param {number} conditionNum
     * @returns {jQuery}
     */
    renderCondition: function(condition, conditionNum) {
        var template;
        template = Handlebars.templates.reviewRuleEditModalCondition;

        return $(template({
            condition: condition,
            conditionNum: conditionNum,
            conditionOptions: this.conditionOptions,
            meta: hbInitData().meta.Review
        }));
    },
    /**
     * Extract a condition data object from a corresponding DOM element
     * @param {jQuery} element
     * @returns {{variable: string, comparator: string, value: string, conjunction: string}|null}
     */
    extractCondition: function(element) {
        var varVal = element.find('.edit-variable').val();
        if(varVal != 'N/A') {
            return {
                variable: varVal,
                comparator: element.find('.edit-comparator').val(),
                value: element.find('.edit-value').val(),
                conjunction: element.find('.edit-conjunction').val()
            };
        }
        else {
            return null;
        }
    },
    /**
     * Add a new blank condition
     * @returns {jQuery}
     */
    addCondition: function() {
        var newCondition, numConditions;
        numConditions = this.conditions.length;
        newCondition = {
            variable: this.conditionOptions.variables[0],
            comparator: this.conditionOptions.comparators[0],
            value: '',
            conjunction: this.conditionOptions.conjunctions[0]
        };
        this.conditions.push(newCondition);
        return this.renderCondition(newCondition, numConditions).appendTo(this.$el.find('#rule-form-condition-list')).find('.condition-full').hide();
    },
    /**
     * Remove condition with specified number (unless it's the last one remaining)
     * @param {number} conditionNum
     */
    removeCondition: function(conditionNum) {
        if(this.conditions.length > 1) {
            $('#rule-form-condition-list').find('.review-rule-condition').each(function (index) {
                var curNum = parseInt($(this).data('num'));
                if (curNum == conditionNum) {
                    $(this).remove();
                }
                else if(curNum > conditionNum) {
                    $(this).data('num', curNum - 1);
                    $(this).attr('data-num', curNum - 1);
                }
            });
            this.conditions.splice(conditionNum, 1);
        }
    },
    /**
     * @param ruleModel
     * @param ruleCollection
     * @returns {ReviewRulesModalView}
     */
    show: function(ruleModel, ruleCollection) {
        this.model = ruleModel;
        this.collection = ruleCollection;
        this.conditions = _.clone(this.model.get('conditions'));

        this.typeList.fetch({reset: true});

        return this;
    },
    /**
     * @returns {ReviewTypesModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "change select.edit-variable": "eventChangeVariable"
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew, conditions;
        thisView = this;
        conditions = [];

        $('#rule-form-condition-list').find('.review-rule-condition').each(function(index) {
            var num, cond;
            num = parseInt($(this).data('num'));
            cond = thisView.extractCondition($(this));
            if(cond !== null) conditions[num] = cond;
        });

        toSave = {
            actionType: $('#review-rule-edit-action-type').val(),
            idReviewType: parseInt($('#review-rule-edit-action-target').val()),
            conditions: conditions
        };

        wasNew = this.model.isNew();

        this.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                if(wasNew) {
                    thisView.collection.add(model);
                }

                thisView.hide();
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for 'Cancel' button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    },
    /**
     * Event handler for change variable selection
     * @param {Object} e
     */
    eventChangeVariable: function(e) {
        var target, conditionEl, curSelected, lastSelected, conditionNum;
        target = $(e.target);
        conditionEl = target.closest('.review-rule-condition');
        conditionNum = parseInt(conditionEl.data('num'));

        if(!target.data('lastSelected')) {
            lastSelected = this.conditions[conditionNum].variable;
            target.data('lastSelected', lastSelected);
        }
        else {
            lastSelected = target.data('lastSelected');
        }

        curSelected = target.children(':selected').val();
        target.data('lastSelected', curSelected);

        if(curSelected != lastSelected) {
            if(lastSelected == 'N/A') {
                conditionEl.find('.condition-full').show();
                this.addCondition();
            }
            else if(curSelected == 'N/A') {
                this.removeCondition(conditionNum);
            }
        }
    },
    /**
     * Event handler for review types fetch
     * @param {ReviewTypeList} collection
     */
    eventReviewTypesLoaded: function(collection) {
        this.render();
        this.$el.children().first().modal('show');
    }
});