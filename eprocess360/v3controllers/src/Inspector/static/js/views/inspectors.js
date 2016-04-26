/**
 * Category: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} InspectorListMainView
 */
var InspectorListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {InspectorListMainView}
     */
    initialize: function(options) {
        
        var thisView = this;
        this.inspectorViews = [];
        
        // For each CategoryModel in the collection, instantiate a view
        _.each(this.collection.models, function(inspectorModel) {
            thisView.inspectorViews.push(new InspectorListItemView({model: inspectorModel}));
        });
        
        this.listenTo(this.collection, 'add', this.eventInspectorAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {InspectorListMainView}
     */
    render: function() {
        
        var template, inspectorList;
        
        template = Handlebars.templates.inspectorListMain;
        
        this.$el.html(template({meta: hbInitData().meta.Inspector}));
        this.applyPermissions();
        
        inspectorList = $('#inspector-list');
        
        _.each(this.inspectorViews, function(inspectorView) {
            console.log(inspectorView);
            inspectorList.append(inspectorView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspector: 'meta'
    },
    events: {
        "click #btn-new-inspector": "eventButtonNewInspection"
    },
    /**
     * Event handler for click "New Category" button
     * @param {Object} e
     */
    eventButtonNewInspection: function(e) {
        var newInspector = new InspectorModel();
        modalAddInspectorUser.show(newInspector, this.collection);
    },
    /**
     * Event hander for collection add category
     * @param model
     */
    eventInspectorAdded: function(model) {
        var newView = new InspectorListItemView({model: model});
        this.categoryViews.push(newView);
        newView.render().$el.appendTo($('#category-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for category list item
 * @typedef {Object} InspectorListItemView
 */
var InspectorListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {InspectorListItemView}
     */
    initialize: function(options) {
        
        this.defaultElement = _.has(options, 'el') ? false : true;
//        this.listenTo(this.model, 'change', this.eventCategoryUpdated);
        
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {InspectorListItemView}
     */
    render: function() {
        var template;
        
        template = Handlebars.templates.inspectorListItem;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({inspector: this.model.attributes, meta: hbInitData().meta.Inspector}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({inspector: this.model.attributes, meta: hbInitData().meta.Inspector}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {InspectorListItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    permissionTargets: {
        Inspector: 'meta'
    },
    events: {
        "click .btn-edit": "eventButtonEditGroup",
        "click .btn-remove": "eventButtonRemoveCategory"
    },
    /**
     * Event handler for click edit category button
     * @param {Object} e
     */
    eventButtonEditGroup: function(e) {
        ModalAddInspectorUser.show(this.model);
    },
    /**
     * Event hander for click remove category button
     * @param {Object} e
     */
    eventButtonRemoveCategory: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this inspector?", function(result) {
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
     * Event handler for category model change
     * @param {GroupModel} model
     */
//    eventCategoryUpdated: function(model) {
//        this.render();
//    }
});



var ModalAddInspectorUser = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditGroupUser}
     */
    initialize: function(options) {
        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * If render() is called while user model.isNew(), set up and display the
     * user search box. Otherwise remove all traces of it.
     * @returns {ModalEditGroupUser}
     */
    render: function() {
        var thisView, template, userSearch;
        thisView = this;
        template = Handlebars.templates.inspectorModalAddUser;
        this.$el.html(template({inspectorUser: this.model.attributes, inspector: this.model.inspector, meta: hbInitData().meta.Inspector}));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        // If not working with a new user, don't show the user select portion
        // of the form
        userSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                wildcard: '%QUERY',
                url: hbInitData().meta.User.apiPath + '/find?name=%QUERY'
            }
        });
        $('#inspector-addedit-username').typeahead(
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
     * @returns {ModalEditGroupUser}
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
     * @returns {ModalEditGroupUser}
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
        var thisView, toSave, type, url, redirectUrl;
        thisView    = this;
        toSave      = {};
        redirectUrl = hbInitData().meta.Inspector.path;
        
        type            = 'POST';
        url             = this.model.urlRoot;
        toSave.idUser   = parseInt($('#inspector-addedit-iduser').val());
        
        this.model.save(toSave, {
            type: type,
            url: url,
            wait: true,
            success: function(model, response, options) {
                window.location = redirectUrl + "/" + toSave.idUser;
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
        $('#inspector-addedit-iduser').val(this.userIDs[name]);
    }
});


var InspectorSkillsMain = BizzyBone.BaseView.extend({
   
    initialize: function(options) {
        var thisView = this;
        thisView.bindClickEvent();
        return Backbone.View.prototype.initialize.call(this, options);
    },
    
    bindClickEvent : function(){
        
        var thisView = this;
        $('#btn-assign-skills').click(function(){
            thisView.eventButtonAssignSkills();
        });
    },
    
    events: {
        "click #btn-assign-skills": "eventButtonAssignSkills"
    },
    /**
     * Event handler for click "New Category" button
     * @param {Object} e
     */
    eventButtonAssignSkills: function(e) {
        
        var newInspectorSkills = new InspectorSkillsModel();
        modalAssignInspSkills.show(newInspectorSkills, this.collection);
    },
    /**
     * Event hander for collection add category
     * @param model
     */
    eventInspectorAdded: function(model) {
        var newView = new InspectorListItemView({model: model});
        this.categoryViews.push(newView);
        newView.render().$el.appendTo($('#category-list')).hide().fadeIn(500);
    }
});





var ModalAssignInspectorSkills = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditGroupUser}
     */
    initialize: function(options) {
        this.rendered = false;
        this.skillViews = [];
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * If render() is called while user model.isNew(), set up and display the
     * user search box. Otherwise remove all traces of it.
     * @returns {ModalEditGroupUser}
     */
    render: function() {
        var thisView, url;
        thisView = this;
        url = thisView.model.urlRoot + "/getskills/" + hbInitData().data.idInspector;
        thisView.showModal = true;
        
        thisView.model.save({}, {
            url: url,
            wait: true,
            async :false,
            success: function(model, response, options) {
                
                thisView.showModal = true;
                thisView.renderSkills(response.data);
            },
            error: function (model, response, options) {
                thisView.showModal = false;
                Util.showError(response.responseJSON);
            }
        });
        
    },
    renderSkills: function(skills){
        
        var templateModal, templateSkillItem, thisView = this, elemSkillsList;
        
        templateModal = Handlebars.templates.inspectorModalAddSkills;
        thisView.$el.html(templateModal());
                
        if(!this.rendered) {
            $(document.body).prepend(thisView.$el);
            thisView.rendered = true;
        }

        elemSkillsList = $('#skills-list');

        _.each(skills, function(inspectorSkill) {
            templateSkillItem = Handlebars.templates.inspectorSkillItem;
            elemSkillsList.append(templateSkillItem({skill: inspectorSkill}));                 
        });
        
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    /**
     * Show the group add/edit user modal. To set up save callbacks, takes a
     * new or existing group user model and (for adding) a collection to add
     * to.
     * @param {GroupUserModel} userModel
     * @param {GroupUserList} userCollection
     * @returns {ModalEditGroupUser}
     */
    show: function(inspSkillModel, skillCollection) {
        
        
        
        this.model = inspSkillModel;
        this.collection = skillCollection;
        
        this.render();
        
        if(this.showModal){
            this.$el.children().first().modal('show');
        }

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditGroupUser}
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
        var thisView, toSave, type, url, redirectUrl;
        thisView    = this;
        toSave      = {};
        

        var skillValues = [];
        $.each($("input[name='skills[]']"), function(element) {
            
            var skillsData, skillId, skillAssigned;
            skillId = $(this).attr('attr-id');
            skillAssigned = $(this).prop('checked');
            skillsData = { 'id' : skillId, 'assigned' : skillAssigned };
            skillValues.push(skillsData);
        });

        type            = 'POST';
        url             = this.model.urlRoot + "/skills/" + hbInitData().data.idInspector;
        toSave.skills   = skillValues;
        
        this.model.save(toSave, {
            type: type,
            url: url,
            wait: true,
            success: function(model, response, options) {
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
});


//var SkillListItemView = BizzyBone.BaseView.extend({
//    /**
//     * @param [options]
//     * @returns {InspectorListItemView}
//     */
//    initialize: function(options) {
//        
//        this.defaultElement = _.has(options, 'el') ? false : true;        
//        return Backbone.View.prototype.initialize.call(this, options);
//    },
//    /**
//     * @returns {InspectorListItemView}
//     */
//    render: function() {
//        var template;
//        
//        template = Handlebars.templates.inspectorSkillItem;
//        
//        this.$el.html(template({skill: this.data}));
//        
//        return this;
//    },
//});


var InspectorLimitationsMain = BizzyBone.BaseView.extend({
   
    initialize: function(options) {
        var thisView = this;
        thisView.bindClickEvent();
        return Backbone.View.prototype.initialize.call(this, options);
    },
    
    bindClickEvent : function(){
        
        var thisView = this;
        $('#btn-assign-limitations').click(function(){
            thisView.eventButtonAssignLimitations();
        });
    },
    /**
     * Event handler for click "New Category" button
     * @param {Object} e
     */
    eventButtonAssignLimitations: function(e) {
        
        var newInspectorLimitations = new InspectorLimitationsModel();
        modalAssignInspLimitations.show(newInspectorLimitations, this.collection);
    },
    /**
     * Event hander for collection add category
     * @param model
     */
    eventInspectorAdded: function(model) {
        var newView = new InspectorListItemView({model: model});
        this.categoryViews.push(newView);
        newView.render().$el.appendTo($('#category-list')).hide().fadeIn(500);
    }
});




var ModalAssignInspectorLimitations = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditGroupUser}
     */
    initialize: function(options) {
        this.rendered = false;
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * If render() is called while user model.isNew(), set up and display the
     * user search box. Otherwise remove all traces of it.
     * @returns {ModalEditGroupUser}
     */
    render: function() {
        var thisView, url;
        thisView = this;
        url = thisView.model.urlRoot + "/getlimitations/" + hbInitData().data.idInspector;
        
        thisView.model.save({}, {
            url: url,
            wait: true,
            async :false,
            success: function(model, response, options) {

                thisView.renderLimitations(response.data);
                
            },
            error: function (model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
        
    },
    renderLimitations: function(skills){
        
        var templateModal, templateLimitationItem, thisView = this, elemLimitationsList;
        
        templateModal = Handlebars.templates.inspectorModalAddLimitations;
        thisView.$el.html(templateModal());
                
        if(!this.rendered) {
            $(document.body).prepend(thisView.$el);
            thisView.rendered = true;
        }

        elemLimitationsList = $('#limitations-list');


        _.each(skills, function(inspectorLimitation) {
            templateLimitationItem = Handlebars.templates.inspectorLimitationItem;
            elemLimitationsList.append(templateLimitationItem({limitation: inspectorLimitation}));                 
        });
        
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    /**
     * Show the group add/edit user modal. To set up save callbacks, takes a
     * new or existing group user model and (for adding) a collection to add
     * to.
     * @param {GroupUserModel} userModel
     * @param {GroupUserList} userCollection
     * @returns {ModalEditGroupUser}
     */
    show: function(inspLimitationModel, limitationCollection) {
        
        this.model = inspLimitationModel;
        this.collection = limitationCollection;
        
        this.render();

        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditGroupUser}
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
        var thisView, toSave, type, url, redirectUrl;
        thisView    = this;
        toSave      = {};
        redirectUrl = hbInitData().meta.Inspector.path;

        var limitationValues = [];
        $.each($("input[name='limitations[]']"), function(element) {
            
            var limitationsData, limitationId, limitationAssigned;
            limitationId = $(this).attr('attr-id');
            limitationAssigned = $(this).prop('checked');
            limitationsData = { 'id' : limitationId, 'assigned' : limitationAssigned };
            limitationValues.push(limitationsData);
        });

        type                = 'POST';
        url                 = this.model.urlRoot + "/limitations/" + hbInitData().data.idInspector;
        toSave.limitations  = limitationValues;
        
        this.model.save(toSave, {
            type: type,
            url: url,
            wait: true,
            success: function(model, response, options) {
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
});


//var LimitationListItemView = BizzyBone.BaseView.extend({
//    /**
//     * @param [options]
//     * @returns {InspectorListItemView}
//     */
//    initialize: function(options) {
//        
//        this.defaultElement = _.has(options, 'el') ? false : true;        
//        return Backbone.View.prototype.initialize.call(this, options);
//    },
//    /**
//     * @returns {InspectorListItemView}
//     */
//    render: function() {
//        var template;
//        
//        template = Handlebars.templates.inspectorLimitationItem;
//        
//        this.$el.html(template({limitation: this.data}));
//        
//        return this;
//    },
//});