/**
 * Category: Views
 */

/**
 * Backbone view for category list
 * @typedef {Object} CategoryListMainView
 */
var CategoryListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {CategoryListMainView}
     */
    initialize: function(options) {
        
        var thisView = this;
        this.categoryViews = [];
        
        // For each CategoryModel in the collection, instantiate a view
        _.each(this.collection.models, function(categoryModel) {
            thisView.categoryViews.push(new CategoryListItemView({model: categoryModel}));
        });
        
        this.listenTo(this.collection, 'add', this.eventCategoryAdded);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListMainView}
     */
    render: function() {
        
        var template, categoryList;
        
        template = Handlebars.templates.categoryListMain;
        this.$el.html(template({meta: hbInitData().meta.Inspection}));
        this.applyPermissions();
        
        categoryList = $('#category-list');
        
        _.each(this.categoryViews, function(categoryView) {
            categoryList.append(categoryView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Inspection: 'meta'
    },
    events: {
        "click #btn-new-category": "eventButtonNewCategory"
    },
    /**
     * Event handler for click "New Category" button
     * @param {Object} e
     */
    eventButtonNewCategory: function(e) {
        var newCategory = new CategoryModel();
        modalEditCategory.show(newCategory, this.collection);
    },
    /**
     * Event hander for collection add category
     * @param model
     */
    eventCategoryAdded: function(model) {
        var newView = new CategoryListItemView({model: model});
        this.categoryViews.push(newView);
        newView.render().$el.appendTo($('#category-list')).hide().fadeIn(500);
    }
});

/**
 * backbone view for category list item
 * @typedef {Object} CategoryListItemView
 */
var CategoryListItemView = BizzyBone.BaseView.extend({
    /**
     * @param [options]
     * @returns {CategoryListItemView}
     */
    initialize: function(options) {
        
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.listenTo(this.model, 'change', this.eventCategoryUpdated);
        
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListItemView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.categoryListItem;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({category: this.model.attributes, meta: hbInitData().meta.Inspection}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({category: this.model.attributes, meta: hbInitData().meta.Inspection}));
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
        "click .btn-remove": "eventButtonRemoveCategory"
    },
    /**
     * Event handler for click edit category button
     * @param {Object} e
     */
    eventButtonEditGroup: function(e) {
        modalEditCategory.show(this.model);
    },
    /**
     * Event hander for click remove category button
     * @param {Object} e
     */
    eventButtonRemoveCategory: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this category?", function(result) {
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
    eventCategoryUpdated: function(model) {
        this.render();
    }
});

/**
 * Backbone view for edit category modal
 * @typedef {Object} ModalEditCategory
 */
var ModalEditCategory = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditCategory}
     */
    initialize: function(options) {
        this.rendered = false;
        this.userIDs = {};
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditCategory}
     */
    render: function() {
        
        var template;
        template = Handlebars.templates.categoryModalEditCategory;
        
        this.$el.html(template({category: this.model.attributes, meta: hbInitData().meta.Inspection}));
        
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
     * Show the category add/edit category modal. To set up save callbacks, takes a
     * new or existing category model and (for adding) a collection to add to.
     * @param {CategoryModel} categoryModal
     * @param {CategoryList} categoryCollection
     * @returns {ModalEditGroup}
     */
    show: function(categoryModal, categoryCollection) {

        this.model = categoryModal;
        this.collection = categoryCollection;

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
     * Even handler for "Save" button. Saves new or existing category model.
     * @param {Object} e
     */
    eventSave: function(e) {
        
        var thisView, toSave, newStatus, wasNew;
        thisView = this;
        toSave   = {};

        wasNew          = this.model.isNew();

        toSave.title        = $('#category-addedit-title').val();
        toSave.description  = $('#category-addedit-desc').val();
        
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
