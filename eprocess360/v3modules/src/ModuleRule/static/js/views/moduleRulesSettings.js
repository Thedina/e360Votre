/**
 * ModuleRule Settings: Views
 */

/**
 * Backbone view for module rules main
 * @typedef {Object} ModuleRulesMainView
 */
var ModuleRulesMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ModuleRulesMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        _.each(this.collection.models, function(ruleModel) {
            thisView.itemViews.push(new ModuleRulesItemView({model: ruleModel}));
        });

        // Listen to collection add model event
        this.listenTo(this.collection, 'add', this.eventRuleAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModuleRulesMainView}
     */
    render: function() {
        var template, typeList;
        template = Handlebars.templates.moduleRuleListMain;

        this.$el.html(template({meta: hbInitData().meta.ModuleRule}));

        typeList = this.$el.find('#module-rule-list');

        _.each(this.itemViews, function(itemView) {
            typeList.append(itemView.render().$el);
        });

        return this;
    },
    events: {
        "click #btn-add-module-rule": "eventAddRule"
    },
    /**
     * Event handler for "New Type" type button
     * @param {Object} e
     */
    eventAddRule: function(e) {
        var moduleRule = new ModuleRuleModel();
        moduleRuleModal.show(moduleRule, this.collection);
    },
    /**
     * Event handler for collection model added
     * @param {ModuleRuleModel} model
     */
    eventRuleAdded: function(model) {
        var newView = new ModuleRulesItemView({model: model});
        this.itemViews.push(newView);
        newView.render().$el.appendTo($('#module-rule-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for module rules list item
 * @typedef {Object} ModuleRulesItemView
 */
var ModuleRulesItemView = BizzyBone.BaseView.extend({
    /**
     *
     * @param options
     * @returns {ModuleRulesItemView}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventRuleChanged);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModuleRulesItemView}
     */
    render: function() {
        var template = Handlebars.templates.moduleRuleListItem;

        this.renderTemplate({
            moduleRule: this.model.attributes,
            meta: hbInitData().meta.ModuleRule
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
        moduleRuleModal.show(this.model);
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
     * @param {ModuleRuleModel} model
     */
    eventRuleChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for module rule edit modal
 * @typedef {Object} ModuleRulesModalView
 */
var ModuleRulesModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ModuleRulesModalView}
     */
    initialize: function(options) {
        if(_.has(options, 'conditionOptions')) this.conditionOptions = options.conditionOptions;
        this.rendered = false;

        this.typeList = hbInitData().meta.ModuleRule.objectActions;

        // Listen to type list reset event
        this.listenTo(this.typeList, 'reset', this.eventModuleRuleTypesLoaded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModuleRulesModalView}
     */
    render: function() {
        var thisView, template, conditionList;
        thisView = this;
        template = Handlebars.templates.moduleRuleEditModal;

        this.$el.html(template({
            moduleRule: this.model.attributes,
            moduleTypes: this.typeList,
            meta: hbInitData().meta.ModuleRule
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
        template = Handlebars.templates.moduleRuleEditModalCondition;

        return $(template({
            condition: condition,
            conditionNum: conditionNum,
            conditionOptions: this.conditionOptions,
            meta: hbInitData().meta.ModuleRule
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
            $('#rule-form-condition-list').find('.module-rule-condition').each(function (index) {
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
     * @returns {ModuleRulesModalView}
     */
    show: function(ruleModel, ruleCollection) {
        this.model = ruleModel;
        this.collection = ruleCollection;
        this.conditions = _.clone(this.model.get('conditions'));

        //this.typeList.fetch({reset: true});
        this.render();
        this.$el.children().first().modal('show');
        return this;
    },
    /**
     * @returns {ModuleRuleTypesModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "change select.edit-variable": "eventChangeVariable",
        "change select.edit-conjunction": "eventChangeConjunction"
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew, conditions;
        thisView = this;
        conditions = [];

        $('#rule-form-condition-list').find('.module-rule-condition').each(function(index) {
            var num, cond;
            num = parseInt($(this).data('num'));
            cond = thisView.extractCondition($(this));
            if(cond !== null)
                conditions[num] = cond;
            else if (cond == null) {
                if (num > 0) {
                    // there is more than one condition, change previous condition's conjunction to 'N/A'
                    conditions[num-1]['conjunction'] = 'N/A';
                }

            }
        });

        toSave = {
            actionType: $('#module-rule-edit-action-type').val(),
            idObjectAction: parseInt($('#module-rule-edit-action-target').val()),
            objectActionTitle: $('#module-rule-edit-action-target').find('option:selected').text(),
            conditions: conditions
        };


        wasNew = this.model.isNew();

        if (toSave.conditions.length > 0) {
            this.model.save(toSave, {
                wait: true,
                success: function(model, response, options) {
                    if(wasNew && toSave.conditions.length) {

                        thisView.collection.add(model);
                    }
                    thisView.hide();
                },
                error: function(model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });
        } else {
            this.hide();
        }
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
        conditionEl = target.closest('.module-rule-condition');
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
            }
            else if(curSelected == 'N/A') {
                this.removeCondition(conditionNum);
            }
        }
    },

    eventChangeConjunction: function (e) {
        var target, conditionEl, curSelected, lastSelected, conditionNum;
        target = $(e.target);
        console.log(e);
        conditionEl = target.closest('.module-rule-condition');
        conditionNum = parseInt(conditionEl.data('num'));

        if(!target.data('lastSelected')) {
            lastSelected = this.conditions[conditionNum].conjunction;
            target.data('lastSelected', lastSelected);
        }
        else {
            lastSelected = target.data('lastSelected');
        }

        curSelected = target.children(':selected').val();
        target.data('lastSelected', curSelected);

        if(curSelected != lastSelected) {
            if(lastSelected == 'N/A') {
                this.addCondition();
            }
            else if(curSelected == 'N/A') {
                this.removeCondition(conditionNum +1);
            }
        }

    },

    /**
     * Event handler for module types fetch
     * @param {ModuleRuleTypeList} collection
     */
    eventModuleRuleTypesLoaded: function(collection) {
        this.render();
        this.$el.children().first().modal('show');
    }
});