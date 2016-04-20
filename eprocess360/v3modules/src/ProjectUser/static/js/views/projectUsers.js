/**
 * Project Users: Views
 */

/**
 * Backbone view for project user list main
 * @typedef {Object} ProjectUsersMainView
 */
var ProjectUsersMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ProjectUsersMainView}
     */
    initialize: function (options) {
        var thisView = this;
        this.showGlobal = true;
        this.itemViews = [];

        _.each(this.collection.models, function (userModel) {
            thisView.itemViews.push(new ProjectUsersItemView({model: userModel}));
        });

        // Listen to collection add model event
        this.listenTo(this.collection, 'add', this.eventUserAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ProjectUsersMainView}
     */
    render: function () {
        var template = Handlebars.templates.projectUsersMain;

        this.$el.html(template({meta: hbInitData().meta.ProjectUser}));
        this.applyPermissions();

        this.renderItems();

        return this;
    },
    /**
     * Render helper for user list (so they can be cleared and re-rendered)
     * without touching the main view.
     * @param {boolean} clear
     * @returns {ProjectUsersMainView}
     */
    renderItems: function (clear) {
        if (typeof clear === 'undefined') clear = false;

        var thisView, userList;
        thisView = this;

        userList = this.$el.find('#user-list');

        if (clear) {
            userList.empty();
        }

        _.each(this.itemViews, function (itemView) {
            userList.append(itemView.render(thisView.showGlobal).$el);
        });


        return this;
    },
    permissionTargets: {
        ProjectUser: 'meta'
    },
    events: {
        "click #btn-add-user": "eventAddUser",
        "click #btn-toggle-global": "eventToggleGlobal"
    },
    /**
     * Event handler for 'Add User' button
     * @param {Object} e
     */
    eventAddUser: function (e) {
        var newUser = new ProjectUserModel();
        projectUserModal.show(newUser, this.collection);
    },
    /**
     * Event handler for toggle global button
     * @param {Object} e
     */
    eventToggleGlobal: function (e) {
        $(e.target).toggleClass('active').blur();
        this.showGlobal = this.showGlobal ? false : true;
        this.renderItems(true);
    },
    /**
     * Event handler for model added to collection
     * @param {ProjectUserModel} model
     */
    eventUserAdded: function (model) {
        var newView = new ProjectUsersItemView({model: model});
        this.itemViews.push(newView);
        newView.render().$el.appendTo($('#user-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for project user list item
 * @typedef {Object} ProjectUsersItemView
 */
var ProjectUsersItemView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ProjectUsersItemView}
     */
    initialize: function (options) {
        // Listen to model change and destroy events
        this.listenTo(this.model, 'change', this.eventUserChanged);
        this.listenTo(this.model, 'destroy', this.eventUserDestroyed);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @param {boolean} showGlobal
     * @returns {ProjectUsersItemView}
     */
    render: function (showGlobal) {
        if (typeof showGlobal === 'undefined') showGlobal = true;

        var template = Handlebars.templates.projectUsersItem;

        // If showGlobal is not set and no local roles, don't render
        if (showGlobal || this.model.get('localRoles').length) {
            this.renderTemplate({
                projectUser: this.model.attributes,
                roleTable: hbInitData().meta.Roles,
                showGlobal: showGlobal,
                meta: hbInitData().meta.ProjectUser
            }, template);

            this.applyPermissions();
        }

        return this;
    },
    permissionTargets: {
        ProjectUser: 'meta'
    },
    events: {
        "click .btn-edit-user": "eventEditUser"
    },
    /**
     * Event handler for 'Edit' button
     * @param {Object} e
     */
    eventEditUser: function (e) {
        e.preventDefault();
        projectUserModal.show(this.model);
    },
    /**
     * Event handler for model changed
     * @param {ProjectUserModel} model
     */
    eventUserChanged: function (model) {
        this.render();
    },
    /**
     * Event handler for model destroyed
     * @param {ProjectUserModel} model
     */
    eventUserDestroyed: function (model) {
        var thisView = this;
        thisView.$el.fadeOut(500, function () {
            thisView.remove();
        });
    }
});

/**
 * Backbone view for project user add/edit modal
 * @typedef {Object} ProjectUsersEditModalView
 */
var ProjectUsersEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ProjectUsersEditModalView}
     */
    initialize: function (options) {
        this.rendered = false;
        this.userIDs = {};
        this.lastValue = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    render: function () {
        var template;
        template = Handlebars.templates.projectUsersEditModal;

        this.$el.html(template({
            projectUser: this.model.attributes,
            meta: hbInitData().meta.ProjectUser
        }));

        this.renderForm();

        this.$el.find('#user-edit-modal-add-banner').hide();
        this.$el.find('#user-edit-modal-remove-banner').hide();
        this.$el.find('#user-edit-modal-warning-banner').hide();

        this.initUserSearch();

        // If never rendered before, insert the modal div at the top of the page
        if (!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        if (this.model.get('idUser')) {

            //this.$el.find('#modal-user-search').replaceWith(this.model.get('userName'));
            this.$el.find('#modal-user-search').typeahead('destroy');
            this.$el.find('#modal-user-search').val(this.model.get('userName'));
            this.$el.find('#modal-user-search').prop('disabled', true);
            this.lastValue = $('#modal-user-search').val();
            this.$el.find('#modal-iduser').val(this.model.get('idUser'));
        }

        return this;
    },
    /**
     * *Big 'ol* render method...
     * @returns {ProjectUsersEditModalView}
     */
    renderForm: function () {
        var roleTemplate, availableRoles, roleList, inheritedRoleList, globalRoleData;
        roleTemplate = Handlebars.templates.projectUsersModalRole;
        availableRoles = hbInitData().meta.Roles;
        curRoles = this.model.getLocalRolesSorted();
        globalRoleText = this.model.getGlobalRoles().join('/');

        roleList = this.$el.find('#modal-role-list');
        inheritedRoleList = this.$el.find('#modal-inherited-role-list');

        // Render all checkboxes for all available local roles
        _.each(availableRoles, function (roleName, id) {
            var roleData = {
                idRole: id,
                name: roleName,
                label: roleName
            };

            roleData.granted = _.has(curRoles.project, id);
            roleData.isGlobal = false;

            roleList.append($(roleTemplate({role: roleData})));

            // If role inherited by this user *also* render in inherited section
            if (_.has(curRoles.inherited, id)) {
                roleData.label = roleName + ' granted by group ' + curRoles.inherited[id].grantedBy;
                roleData.granted = _.has(curRoles.inherited, id);
                roleData.isGlobal = true;
                roleData.noEdit = true;

                inheritedRoleList.append($(roleTemplate({role: roleData})));
            }
        });

        // And add a line for global roles...
        if (globalRoleText.length) {
            globalRoleData = {
                idRole: 'global-roles',
                name: globalRoleText,
                label: 'Global roles: ' + globalRoleText,
                granted: true,
                isGlobal: true,
                noEdit: true
            };

            inheritedRoleList.append($(roleTemplate({role: globalRoleData})));
        }


        return this;
    },

    /**
     * Split user search stuff out because this view already has one of the
     * biggest render methods ever. Perhaps this should be globalized
     * eventually actually?
     * @returns {ProjectUsersEditModalView}
     */
    initUserSearch: function () {
        var thisView, userSearch;
        thisView = this;

        userSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                wildcard: '%QUERY',
                url: hbInitData().meta.User.apiPath + '/find?name=%QUERY'
            }
        });

        this.$el.find('#modal-user-search').typeahead(
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
                source: function (query, sync, async) {
                    var userNames = [];
                    return userSearch.search(
                        query,
                        function (data) {
                            //sync
                            sync(data[1]);
                        },
                        function (data) {
                            //async
                            // data[1] is still a fat hack, because for some reason we see the
                            // incoming object with errors, data, meta as an array without names :(
                            _.each(data[1], function (d) {
                                thisView.userIDs[d.name] = d.id;
                                userNames.push(d.name);
                            });
                            async(userNames);
                        }
                    );
                }
            }
        );

        return this;
    },
    /**
     * @param {ProjectUserModel} userModel
     * @param {ProjectUserList} userCollection
     * @returns {ProjectUsersEditModalView}
     */
    show: function (userModel, userCollection) {
        this.model = userModel;
        this.collection = userCollection;

        this.render();

        this.$el.children().first().modal('show');
        return this;
    },
    /**
     * Just hide the modal
     * @returns {ProjectUsersEditModalView}
     */
    hide: function () {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "change .role-checkbox": "eventChangeSelection",
        "typeahead:select": "eventTypeaheadSelect",
        "typeahead:autocomplete": "eventTypeaheadSelect",
        "typeahead:idle": "eventClearUser"
    },
    /**
     * Event handler for check or uncheck roles. Shows 'added' banner if new
     * user or user without project roles and at least one role has been
     * selected. Shows 'removed' banner if no role is selected.
     * @param {Object} e
     */
    eventChangeSelection: function (e) {
        var anyChecked, numProjectRoles;
        anyChecked = $('#modal-role-list').find('.role-checkbox').is(':checked');
        numProjectRoles = _.size(this.model.getLocalRolesSorted().project);

        if (this.model.isNew() || !numProjectRoles) {
            // Is a user potentially being added?
            if (anyChecked) {
                $('#user-edit-modal-add-banner').show();
            }
            else {
                $('#user-edit-modal-add-banner').hide();
            }
        }
        else {
            // Is a user potentially being removed?
            if (!anyChecked) {
                $('#user-edit-modal-remove-banner').show();
            }
            else {
                $('#user-edit-modal-remove-banner').hide();
            }
        }
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function (e) {
        var thisView, wasNew, toSave, selectedRoles, updatedUserRoles;
        thisView = this;
        selectedRoles = [];
        updatedUserRoles = [];
        toSave = {};

        wasNew = this.model.isNew();

        $('#modal-role-list').find('.role-checkbox').each(function (index) {
            if ($(this).is(':checked')) {
                selectedRoles.push($(this).val());
            }
        });

        // Update roles
        _.each(selectedRoles, function (idRole) {
            updatedUserRoles.push({
                idLocalRole: idRole,
                grantedBy: 'self'
            });
        });

        _.each(this.model.get('localRoles'), function (role) {
            if (role.grantedBy !== 'self') {
                updatedUserRoles.push(role);
            }
        });

        toSave.localRoles = updatedUserRoles;

        toSave.idUser = parseInt($('#modal-iduser').val());

        //if (idUser || parseInt($('#modal-iduser').val())) {
        //    if (!idUser) toSave.idUser = parseInt($('#modal-iduser').val());

        if (toSave.idUser > 0) {
            this.model.save(toSave, {
                wait: true,
                success: function (model, response, options) {
                    if (wasNew && toSave.localRoles.length) {
                        thisView.collection.add(model);
                    }
                    else if (_.isEmpty(response.data)) {
                        // User has been removed. Destroy it's model without making another request
                        model.trigger('destroy', model, model.collection);
                    }

                    thisView.hide();
                },
                error: function (model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });
        }
        else {
            thisView.hide();
        }


    },
    /**
     * Event handler for 'Cancel' button
     * @param {Object} e
     */
    eventCancel: function (e) {
        this.hide();
    },
    /**
     * Event handler for selecting option from typeahead. Sets corresponding
     * user ID for selected user into hidden field.
     * @param {Object} e
     * @param name
     */
    eventTypeaheadSelect: function (e, name) {
        var idUser, collection;
        if ($('#modal-user-search').val() === this.lastValue) return;
        this.lastValue = $('#modal-user-search').val();

        // select & autocomplete will auto-fill with existing user
        idUser = this.userIDs[name];
        $('#modal-iduser').val(idUser);
        $('.modal-addremove-banner-name').text(name);
        if (idUser) {
            collection = this.collection ? this.collection : (this.model ? this.model.collection : null);
        }

        if (collection.getByID(idUser)) {
            this.model = collection.getByID(idUser);
            this.$el.find('.modal-role-list').empty();
            this.renderForm();
        }
        else {
            this.$el.find('.modal-role-list').empty();
            this.model = new ProjectUserModel();
            this.renderForm();
        }

        $('#user-edit-modal-add-banner').hide();
        $('#user-edit-modal-remove-banner').hide();
        $('#user-edit-modal-warning-banner').hide();

    },
    /**
     * Event handler for clearing the input field and rentering a user not found via typeahead & search.
     *
    @param (Object) e
     */
    eventClearUser: function (e) {
        // if value was same as the one fired by autocomplete or select, return
        if ($('#modal-user-search').val() === this.lastValue) return;
        this.lastValue = $('#modal-user-search').val();

        // this event will only fire when the user cannot be found in the database
        var name = $('#modal-user-search').val();

        // reset user ID to 0 since not in database
        $('#modal-iduser').val(0);
        $('.modal-addremove-banner-name').text(name);

        // reset role list
        this.$el.find('#modal-inherited-role-list').empty();
        this.$el.find('.modal-role-list').find('.role-checkbox').prop('checked', false);

        // if there's nothing in there at all, hide all banners
        /// until email invite functionality is implemented, hide banners in all cases, except for the alert
        if (name.length > 0) {
            $('#user-edit-modal-warning-banner').show();

        }
        else {
            $('#user-edit-modal-warning-banner').hide();
        }
        $('#user-edit-modal-add-banner').hide();
        $('#user-edit-modal-remove-banner').hide();
        // else handle the input of new users

    }

});