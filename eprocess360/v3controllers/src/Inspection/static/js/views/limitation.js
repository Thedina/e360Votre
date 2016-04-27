/**
 * Category: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} CategoryListMainView
 */


var LimitationListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {CategoryListMainView}
     */
    initialize: function(options) {

        var thisView = this;
        this.limitationViews = [];

        // For each CategoryModel in the collection, instantiate a view
        _.each(this.collection.models, function(limitationModel) {
            thisView.limitationViews.push(new LimitationListItemView({model: limitationModel}));
        });

        this.listenTo(this.collection, 'add', this.eventLimitationAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListMainView}
     */
    render: function() {

        
        var template, limitationList;

        template = Handlebars.templates.limitationListMain;
        this.$el.html(template({meta: hbInitData().meta.Inspection}));
        this.applyPermissions();

        limitationList = $('#limitation-list');

        _.each(this.limitationViews, function(categoryView) {
            limitationList.append(categoryView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-new-group": "eventButtonNewLimitation"
    },
    /**
     * Event handler for click "New Group" button
     * @param {Object} e
     */
    eventButtonNewLimitation: function(e) {
        var newLimitation = new LimitationModel();
        modalEditLimitation.show(newLimitation, this.collection);
    },
    /**
     * Event hander for collection add group
     * @param model
     */
    eventLimitationAdded: function(model) {
        var newView = new LimitationListItemView({model: model});
        this.limitationViews.push(newView);
        newView.render().$el.appendTo($('#limitation-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for group list item
 * @typedef {Object} LimitationListItemView
 */
var LimitationListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {LimitationListItemView}
     */
    initialize: function(options) {

        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventCategoryUpdated);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {LimitationListItemView}
     */
    render: function() {
        var template;
        
        template = Handlebars.templates.limitationListItem;
        
        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({limitation: this.model.attributes, meta: hbInitData().meta.Inspection}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());

            }
        }
        else {
            
            oldEl = this.$el;
            this.setElement(template({limitation: this.model.attributes, meta: hbInitData().meta.Inspection}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {LimitationListItemView}
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
        modalEditLimitation.show(this.model);
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
    eventCategoryUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for edit group modal
 * @typedef {Object} ModalEditGroup
 */
var ModalEditLimitation = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditGroup}
     */
    initialize: function(options) {

        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditGroupUser}
     */
    render: function() {

        var template;
        template = Handlebars.templates.limitationEditView;//------------ds
        this.$el.html(template({limitation: this.model.attributes, meta: hbInitData().meta.Inspection}));
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
     * @returns {ModalEditGroup}
     */
    show: function(limitationModal, limitationCollection) {
        this.model = limitationModal;
        this.collection = limitationCollection;

        this.render();

        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditGroup}
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

        toSave.title        = $('#limitation-addedit-title').val();
        toSave.description  = $('#limitation-addedit-description').val();
        
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
