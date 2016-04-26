/**
 * Category: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} CategoryListMainView
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
        "click #btn-new-skill": "eventButtonNewGroup"
    },
    /**
     * Event handler for click "New Group" button
     * @param {Object} e
     */
    eventButtonNewGroup: function(e) {
        var newGroup = new SkillModel();
        modalAddSkill.show(newGroup, this.collection);
    },
    /**
     * Event hander for collection add group
     * @param model
     */
    eventSkillAdded: function(model) {
        var newView = new SkillListItemView({model: model});
        this.skillViews.push(newView);
        newView.render().$el.appendTo($('#skill-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for group list item
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
        "click .btn-edit": "eventButtonEditGroup",
        "click .btn-remove": "eventButtonRemoveGroup"
    },
    /**
     * Event handler for click edit group button
     * @param {Object} e
     */
    eventButtonEditGroup: function(e) {
        modalEditSkill.show(this.model);
    },
    /**
     * Event hander for click remove group button
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
 * @typedef {Object} ModalEditGroup
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
     * Show the group add/edit group modal. To set up save callbacks, takes a
     * new or existing group model and (for adding) a collection to add to.
     * @param {GroupModel} groupModal
     * @param {GroupList} groupCollection
     * @returns {ModalEditSkill}
     */
    show: function(groupModal, groupCollection) {
        this.model = groupModal;
        this.collection = groupCollection;

        this.render();

        this.$el.children().first().modal('show');

        //return this;

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
     * Even handler for "Save" button. Saves new or existing group model.
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
     * @returns {ModalEditSkill}
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
    show: function(groupModal, groupCollection) {
        this.model = groupModal;
        this.collection = groupCollection;

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
     * Even handler for "Save" button. Saves new or existing group model.
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



/**
 * Backbone view for group with user list
 * @typedef {Object} GroupView
 */
var GroupView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} [options]
     * @returns {GroupView}
     */
    initialize: function(options) {
        var thisView = this;
        this.userViews = [];

        // Instantiate a model for each group user and put them in a collection
        this.collection = new GroupUserList(_.map(this.model.get('users'), function(userData) {
            return new GroupUserModel(userData, {group: thisView.model});
        }));

        _.each(this.collection.models, function(userModel) {
            thisView.userViews.push(new GroupUserView({model: userModel}));
        });

        this.listenTo(this.model, 'change', this.eventSkillUpdated);
        this.listenTo(this.collection, 'add', this.eventUserAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    render: function() {
        var template, userList;
        template = Handlebars.templates.groupInsideMain;

        this.$el.html(template({group: this.model.attributes, meta: hbInitData().meta.Group}));
        this.applyPermissions();

        userList = $('#user-list');

        _.each(this.userViews, function(userView) {
            userList.append(userView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-add-user": "eventButtonAddUser",
        "click #btn-edit-group": "eventButtonEditGroup"
    },
    /**
     * Event handler for add user button click
     * @param {Object} e
     */
    eventButtonAddUser: function(e) {
        var newUser = new GroupUserModel(null, {group: this.model});
        modalEditUser.show(newUser, this.collection);
    },
    /**
     * Event handler for edit group button click
     * @param e
     */
    eventButtonEditGroup: function(e) {
        modalEditGroup.show(this.model);
    },
    /**
     * Event handler for collection add user
     * @param {GroupUserModel} model
     */
    eventUserAdded: function(model) {
        var newView = new GroupUserView({model: model});
        this.userViews.push(newView);
        newView.render().$el.appendTo($('#user-list')).hide().fadeIn(500);
    },
    eventSkillUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for user in group user list
 * @typedef {Object} GroupUserView
 */
var GroupUserView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {GroupUserView}
     */
    initialize: function(options) {
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventUserUpdated);
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {GroupUserView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.groupInsideUser;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({groupUser: this.model.attributes, group: this.model.group, meta: hbInitData().meta.Group}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({groupUser: this.model.attributes, group: this.model.group, meta: hbInitData().meta.Group}));
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
        "click .btn-edit": "eventEditUser",
        "click .btn-remove": "eventRemoveUser"
    },
    /**
     * Event handler for edit user button click
     * @param {Object} e
     */
    eventEditUser: function(e) {
        modalEditUser.show(this.model);
    },
    /**
     * Event hander for remove user button click
     * @param {Object} e
     */
    eventRemoveUser: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to remove this user from the group?", function (result) {
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
     * Event handler for user model change event
     * @param {GroupUserModel} model
     */
    eventUserUpdated: function(model) {
        this.render();
    }
});
