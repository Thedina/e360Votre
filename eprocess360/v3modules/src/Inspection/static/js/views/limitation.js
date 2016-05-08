/**
 * Limitation: Views
 */

var LimitationMultiviewRow = Multiview.multiviewRowFactory({
    
    initialize: function(options) {
        
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventLimitationUpdated);
        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
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

var LimitationMultiviewMain = Multiview.multiviewMainFactory(LimitationMultiviewRow, {});

/**
 * Backbone view for limitation list
 * @typedef {Object} LimitationListMainView
 */
var LimitationListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {LimitationListMainView}
     */
    initialize: function(options) {
        
        this.multiview = new LimitationMultiviewMain({collection: this.collection});
        this.listenTo(this.collection, 'add', this.eventLimitationAdded);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListMainView}
     */
    render: function() {

        var template, multiview;
        template = Handlebars.templates.limitationListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Inpsection
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-new-limitation": "eventButtonNewLimitation"
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

        var newView = new LimitationMultiviewRow({model: model});
        newView.render().$el.appendTo($('#multiview-list')).hide().fadeIn(500);
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
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
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
     * @param {LimitationModal} limitationModal
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
