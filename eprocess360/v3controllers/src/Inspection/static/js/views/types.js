/**
 * Category: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} CategoryListMainView
 */
var TypesListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {CategoryListMainView}
     */
    initialize: function(options) {
        
        var thisView = this;
        this.typeViews = [];
        
        // For each CategoryModel in the collection, instantiate a view
        _.each(this.collection.models, function(TypesModel) {
            thisView.typeViews.push(new TypeListItemView({model: TypesModel}));
        });

        this.listenTo(this.collection, 'add', this.eventTypeAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListMainView}
     */
    render: function() {
        
        console.log("CHECKKK");
        var template, typeList;
        
        template = Handlebars.templates.typeListMain;
        this.$el.html(template({meta: hbInitData().meta.Inspection}));
        this.applyPermissions();
        
        typeList = $('#type-list'); //module.type.categories.handlerbars.html
        
        _.each(this.typeViews, function(typeViews) {
            typeList.append(typeViews.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-new-group": "eventButtonNewGroup"
    },
    /**
     * Event handler for click "New Group" button
     * @param {Object} e
     */
    eventButtonNewGroup: function(e) {
        var newGroup = new GroupModel();
        modalEditGroup.show(newGroup, this.collection);
    },
    /**
     * Event hander for collection add group
     * @param model
     */
    eventTypeAdded: function(model) {
        var newView = new TypesListItemView({model: model});
        this.typeViews.push(newView);
        newView.render().$el.appendTo($('#group-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for group list item
 * @typedef {Object} CategoryListItemView
 */
var TypeListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {CategoryListItemView}
     */
    initialize: function(options) {
        
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventTypesUpdated);
        
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListItemView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.typeListItem
        console.log("exiiiit----", this.model.attributes);
//        console.log(template);
//        return false; 
        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({type: this.model.attributes, meta: hbInitData().meta.Inspection}));
             //------------------------Twig data render----------------------
             //
            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({type: this.model.attributes, meta: hbInitData().meta.Inspection}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {CategoryListItemView}
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
        modalEditType.show(this.model);
    },
    /**
     * Event hander for click remove group button
     * @param {Object} e
     */
    eventButtonRemoveGroup: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this group?", function(result) {
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
    eventTypesUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for edit group modal
 * @typedef {Object} ModalEditType
 */
var ModalEditType = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditType}
     */
    initialize: function(options) {
        
        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditTypeUser}
     */
    render: function() {
        
        var template;
        template = Handlebars.templates.typeModelEdit; // set da template here
        this.$el.html(template({type: this.model.attributes, meta: hbInitData().meta.Group})); //meta.Group ?

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
     * @returns {ModalEditType}
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
     * @returns {ModalEditType}
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

        wasNew = this.model.isNew();
        newStatus = _.clone(this.model.get('status'));
        newStatus.isActive = $('#group-addedit-isactive').prop('checked');
        toSave.status = newStatus;
        toSave.title = $('#group-addedit-title').val();
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

        this.listenTo(this.model, 'change', this.eventCategoryUpdated);
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
    eventCategoryUpdated: function(model) {
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
     * @returns {CategoryListItemView}
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

/**
 * Backbone view for edit user modal
 * @typedef {Object} ModalEditTypeUser
 */
var ModalEditTypeUser = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditTypeUser}
     */
    initialize: function(options) {
        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * If render() is called while user model.isNew(), set up and display the
     * user search box. Otherwise remove all traces of it.
     * @returns {ModalEditTypeUser}
     */
    render: function() {
        var thisView, template, userSearch;
        thisView = this;
        template = Handlebars.templates.groupModalEditUser;
        this.$el.html(template({groupUser: this.model.attributes, group: this.model.group, meta: hbInitData().meta.Group}));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        // If not working with a new user, don't show the user select portion
        // of the form
        if(!this.model.isNew()) {
            $('#edituser-modal-user-subform').remove();
        }
        else {
            userSearch = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.whitespace,
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    wildcard: '%QUERY',
                    url: hbInitData().meta.User.apiPath + '/find?name=%QUERY'
                }
            });
            $('#groupuser-addedit-username').typeahead(
                null,
                {
                    name: 'user-api',
                    /**
                     * Custom source function pulls user ID out of data from
                     * user search and separates from names.
                     * @param {Array} query
                     * @param {function} sync
                     * @param {function} async
                     * @returns {Number|*}
                     */
                    source: function(query, sync, async) {
                        var userNames = [];
                        return userSearch.search(
                            query,
                            function(data) {
                                //sync
                                sync(data[0]);
                            },
                            function(data) {
                                //async
                                // data[0] is still a fat hack, because for some reason we see the
                                // incoming object with errors, data, meta as an array without names :(
                                _.each(data[0], function(d) {
                                    thisView.userIDs[d.name] = d.id;
                                    userNames.push(d.name);
                                });
                                async(userNames);
                            }
                        );
                    }
                }
            );
        }

        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "typeahead:select": "eventTypeaheadSelect"
    },
    /**
     * Show the group add/edit user modal. To set up save callbacks, takes a
     * new or existing group user model and (for adding) a collection to add
     * to.
     * @param {GroupUserModel} userModel
     * @param {GroupUserList} userCollection
     * @returns {ModalEditTypeUser}
     */
    show: function(userModel, userCollection) {
        this.model = userModel;
        this.collection = userCollection;

        this.render();

        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditTypeUser}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Event handler for "Save" button. Saves new or existing group user model.
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, newStatus, wasNew, type, url;
        thisView = this;
        toSave = {};

        wasNew = this.model.isNew();
        newStatus = _.clone(this.model.get('status'));
        newStatus.isActive = $('#groupuser-addedit-isactive').prop('checked');
        toSave.status = newStatus;
        toSave.idRole = parseInt($('#groupuser-addedit-idrole').val());
        if(wasNew) {
            toSave.idUser = parseInt($('#groupuser-addedit-iduser').val());
            type = 'POST';
            url = this.model.urlRoot();
        }
        else {
            type = 'PUT';
            url = this.model.urlRoot() + '/' + this.model.get('idUser');
        }

        this.model.save(toSave, {
            type: type,
            url: url,
            wait: true,
            success: function(model, response, options) {
                if(wasNew) {
                    thisView.collection.add(model);
                }
                thisView.hide();
            },
            error: function (model, response, options) {
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
    },
    /**
     * Event handler for selecting option from typeahead. Sets corresponding
     * user ID for selected user into hidden field.
     * @param {Object} e
     * @param name
     */
    eventTypeaheadSelect: function(e, name) {
        $('#groupuser-addedit-iduser').val(this.userIDs[name]);
    }
});