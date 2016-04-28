/**
 * Skill: Views
 */

/**
 * Backbone view for skill list
 * @typedef {Object} SkillListMainView
 */
var SkillListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {SkillListMainView}
     */
    initialize: function(options) {
        
        var thisView = this;
        this.skillViews = [];
        
        // For each SkillModel in the collection, instantiate a view
        _.each(this.collection.models, function(SkillModel) {
            thisView.skillViews.push(new SkillListItemView({model: SkillModel}));
        });

        this.listenTo(this.collection, 'add', this.eventSkillAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SkillListMainView}
     */
    render: function() {
        
        var template, skillList;
        
        template = Handlebars.templates.skillListMain;
        this.$el.html(template({meta: hbInitData().meta.Inspection}));
        this.applyPermissions();

        skillList = $('#skill-list');
        
        _.each(this.skillViews, function(skillView) {
            skillList.append(skillView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-new-skill": "eventButtonNewSkill"
    },
    /**
     * Event handler for click "New Group" button
     * @param {Object} e
     */
    eventButtonNewSkill: function(e) {
        var newSkill = new SkillModel();
        modalAddSkill.show(newSkill, this.collection);
    },
    /**
     * Event hander for collection add skill
     * @param model
     */
    eventSkillAdded: function(model) {
        var newView = new SkillListItemView({model: model});
        this.skillViews.push(newView);
        newView.render().$el.appendTo($('#skill-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for skill list item
 * @typedef {Object} SkillListItemView
 */
var SkillListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {SkillListItemView}
     */
    initialize: function(options) {
        
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventSkillUpdated);
        
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SkillListItemView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.skillListItem;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({skills: this.model.attributes, meta: hbInitData().meta.Inspection}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({skills: this.model.attributes, meta: hbInitData().meta.Inspection}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {SkillListItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click .btn-edit": "eventButtonEditSkill",
        "click .btn-remove": "eventButtonRemoveGroup"
    },
    /**
     * Event handler for click edit skill button
     * @param {Object} e
     */
    eventButtonEditSkill: function(e) {
        modalEditSkill.show(this.model);
    },
    /**
     * Event hander for click remove skill button
     * @param {Object} e
     */
    eventButtonRemoveGroup: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this skill?", function(result) {
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
     * Event handler for group model change
     * @param {GroupModel} model
     */
    eventSkillUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for edit group modal
 * @typedef {Object} ModalEditSkill
 */
var ModalEditSkill = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditSkill}
     */
    initialize: function(options) {
        
        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },

    render: function() {
        
        var template;
        template = Handlebars.templates.EditSkill;
        this.$el.html(template({skill: this.model.attributes, meta: hbInitData().meta.Inspection}));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "submit form": "eventSave"
    },
    
    /**
     * @param {SkillModal} skillModal
     * @param {SkillCollection} skillCollection
     * @returns {ModalEditSkill}
     */
    show: function(skillModal, skillCollection) {
        this.model = skillModal;
        this.collection = skillCollection;

        this.render();

        this.$el.children().first().modal('show');

        return this;

    },
    /**
     * Just hide the modal
     * @returns {ModalEditSkill}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves new or existing skill model.
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, newStatus, wasNew;
        thisView = this;
        toSave = {};

        wasNew          = this.model.isNew();
        
        toSave.title        = $('#skill-addedit-title').val();
        toSave.description  = $('#skill-addedit-desc').val();
        
        thisView.model.save(toSave, {
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
     * Even handler for "Cancel" button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});

var ModalAddSkill = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalAddSkill}
     */
    initialize: function(options) {

        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },


    render: function() {

        var template;
        template = Handlebars.templates.AddSkill;
        this.$el.html(template({skill: this.model.attributes, meta: hbInitData().meta.Inspection}));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "submit form": "eventSave"
    },
    /**
     * Show the group add/edit group modal. To set up save callbacks, takes a
     * new or existing group model and (for adding) a collection to add to.
     * @param {GroupModel} groupModal
     * @param {GroupList} groupCollection
     * @returns {ModalEditSkill}
     */
    /**
     * 
     * @param {SkillModal} skillModal
     * @param {SkillCollection} skillCollection
     * @returns {ModalAddSkill}
     */
    show: function(skillModal, skillCollection) {
        this.model = skillModal;
        this.collection = skillCollection;

        this.render();

        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalAddSkill}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves new or existing skill model.
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, newStatus, wasNew;
        thisView = this;
        toSave   = {};

        wasNew          = this.model.isNew();
        
        toSave.title        = $('#inspectionskill-addedit-title').val();
        toSave.description  = $('#inspectionskill-addedit-desc').val();
        
        thisView.model.save(toSave, {
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
     * Even handler for "Cancel" button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});