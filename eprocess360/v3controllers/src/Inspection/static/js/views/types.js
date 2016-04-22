/**
 * Type: Views
 */

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
        
        var thisView = this;
        this.typeViews = [];
        
        // For each TypeModel in the collection, instantiate a view
        _.each(this.collection.models, function(TypesModel) {
            thisView.typeViews.push(new TypeListItemView({model: TypesModel}));
        });

        this.listenTo(this.collection, 'add', this.eventTypeAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TypeListMainView}
     */
    render: function() {
        
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
        "click #btn-new-type": "eventButtonNewGroup"
    },
    /**
     * Event handler for click "New Inspection Type" button
     * @param {Object} e
     */
    eventButtonNewGroup: function(e) {
        var newGroup = new TypesModel();
        modalAddType.show(newGroup, this.collection);
    },
    /**
     * Event hander for collection add group
     * @param model
     */
    eventTypeAdded: function(model) {
        var newView = new TypeListItemView({model: model});
        this.typeViews.push(newView);
        newView.render().$el.appendTo($('#type-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for Type list item
 * @typedef {Object} TypeListItemView
 */
var TypeListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {TypeListItemView}
     */
    initialize: function(options) {
        
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventTypesUpdated);
        
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TypeListItemView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.typeListItem 
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
     * @returns {TypeListItemView}
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
     * Event handler for click edit Type button
     * @param {Object} e
     */
    eventButtonEditGroup: function(e) {
        modalEditType.show(this.model);
    },
    /**
     * Event hander for click remove TYpe button
     * @param {Object} e
     */
    eventButtonRemoveGroup: function(e) {
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
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditTypeUser}
     */
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
        "click .btn-default": "eventCancel",
        "submit form": "eventSave"
    },
   /**
    * 
    * @param {type} typesModal
    * @param {type} typesCollection
    * @returns {typesAnonym$8}
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
        toSave   = {};

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
                Util.showError(response.responseJSON +'Test Error');
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


var ModalAddType = BizzyBone.BaseView.extend({
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
        template = Handlebars.templates.typeModelAdd; // set da template here
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
        "click .btn-default": "eventCancel",
        "submit form": "eventSave"
    },
   /**
    * 
    * @param {type} groupModal
    * @param {type} groupCollection
    * @returns {typesAnonym$11}
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
        toSave   = {};

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
                Util.showError(response.responseJSON +'Test Error');
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

