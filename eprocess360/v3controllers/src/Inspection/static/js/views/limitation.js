/**
 * Limitation: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} CategoryListMainView
 */


var LimitationListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {LimitationListMainView}
     */
    initialize: function(options) {

        var thisView = this;
        this.limitationViews = [];

        // For each LimitationModel in the collection, instantiate a view
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
     * Event handler for click "New Limitation" button
     * @param {Object} e
     */
    eventButtonNewLimitation: function(e) {
        var newLimitation = new LimitationModel();
        modalEditLimitation.show(newLimitation, this.collection);
    },
    /**
     * Event hander for collection add limitation
     * @param model
     */
    eventLimitationAdded: function(model) {
        var newView = new LimitationListItemView({model: model});
        this.limitationViews.push(newView);
        newView.render().$el.appendTo($('#limitation-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for limitation list item
 * @typedef {Object} LimitationListItemView
 */
var LimitationListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {LimitationListItemView}
     */
    initialize: function(options) {

        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventLimitationUpdated);

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
        "click .btn-edit": "eventButtonEditLimitation",
        "click .btn-remove": "eventButtonRemoveLimitation"
    },
    /**
     * Event handler for click edit limitation button
     * @param {Object} e
     */
    eventButtonEditLimitation: function(e) {
        modalEditLimitation.show(this.model);
    },
    /**
     * Event hander for click remove limitation button
     * @param {Object} e
     */
    eventButtonRemoveLimitation: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this limitation?", function(result) {
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
     * Event handler for limitation model change
     * @param {GroupModel} model
     */
    eventLimitationUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for edit limitation modal
 * @typedef {Object} ModalEditLimitation
 */
var ModalEditLimitation = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditLimitation}
     */
    initialize: function(options) {

        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditLimitation}
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
     * @param {:imitationModal} limitationModal
     * @param {LimitationCollection} limitationCollection
     * @returns {ModalEditLimitation}
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
     * @returns {ModalEditLimitation}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves new or existing limitation model.
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
