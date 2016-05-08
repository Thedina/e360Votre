/**
 * Type: Views
 */

var TypeMultiviewRow = Multiview.multiviewRowFactory({
    
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTypesUpdated);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    
    events: {
        "click .btn-edit": "eventButtonEditType",
        "click .btn-remove": "eventButtonRemoveType"
    },
    
    eventButtonEditType: function(e) {
        modalEditType.show(this.model);
    },
    
    /**
     * Event hander for click remove Type button
     * @param {Object} e
     */
    eventButtonRemoveType: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this Inspection Type?", function(result) {
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
     * Event handler for Type model change
     * @param {TypeModel} model
     */
    eventTypesUpdated: function(model) {
        this.render();
    }
    
});

var TypeMultiviewMain = Multiview.multiviewMainFactory(TypeMultiviewRow, {});

/**
 * Backbone view for Type list
 * @typedef {Object} TypeListMainView
 */
var TypesListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {TypeListMainView}
     */
    initialize: function(options) {
        this.multiview = new TypeMultiviewMain({collection: this.collection});
        this.listenTo(this.collection, 'add', this.eventTypeAdded);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TypeListMainView}
     */
    render: function() {
        
        var template, multiview;
        template = Handlebars.templates.typeListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Inpsection
        }));
        
        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    },
    events: {
        "click #btn-new-type": "eventButtonNewType"
    },
    /**
     * Event handler for click "New Inspection Type" button
     * @param {Object} e
     */
    eventButtonNewType: function(e) {
        var newType = new TypesModel();
        modalEditType.show(newType, this.collection);
    },
    /**
     * Event hander for collection add type
     * @param model
     */
    eventTypeAdded: function(model) {

        var newView = new TypeMultiviewRow({model: model});
        newView.render().$el.appendTo($('#multiview-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for edit type modal
 * @typedef {Object} ModalEditType
 */
var ModalEditType = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditType}
     */
    initialize: function(options) {
        
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    render: function() {
        
        var template;
        template = Handlebars.templates.typeModelEdit; // set da template here
        this.$el.html(template({type: this.model.attributes, meta: hbInitData().meta.Inspection}));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
   /**
    * 
    * @param {TypesModal} typesModal
    * @param {TypesCollection} typesCollection
    * @returns {ModalEditType}
    */
    show: function(typesModal, typesCollection) {
        this.model = typesModal;
        this.collection = typesCollection;

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
     * 
     * @param {type} e
     * @returns {undefined}
     */
    eventSave: function(e) {

        var thisView, toSave, wasNew;
        thisView = this;
        toSave = {};

        wasNew          = this.model.isNew();
        toSave.title        = $('#Type-addedit-name').val();//Change the in handlerbaar input id
        toSave.description  = $('#Type-addedit-descr').val();
  
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