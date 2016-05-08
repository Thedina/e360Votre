/**
 * Category: Views
 */

var CategoryMultiviewRow = Multiview.multiviewRowFactory({
    
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventCategoryChanged);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-remove": "eventRemove",
        "click .btn-assign-types": "eventButtonAssignTypes",
        "click .btn-assign-skills": "eventButtonAssignSkills",
        "click .btn-assign-limitations": "eventButtonAssignLimitations"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {        
        modalEditCategory.show(this.model);
    },
    /**
     * Event handler for model change event
     * @param {CategoryModel} model
     */
    eventCategoryChanged: function(model) {
        this.render();
    },
    /**
     * Event handler for click assign types button
     * @param {Object} e
     */
    eventButtonAssignTypes: function(e){
        
        var modelCategoryTypes = new CategoryTypesModel({idInspCategory : this.model.id});
        modalAssignTypes.show(modelCategoryTypes);
    },
    /**
     * Event handler for click assign skills button
     * @param {Object} e
     */
    eventButtonAssignSkills: function(e){
        
        var modelCategorySkill = new CategorySkillsModel({idInspCategory : this.model.id});
        modalAssignSkills.show(modelCategorySkill);
    },
    /**
     * Event handler for click assign limitations button
     * @param {Object} e
     */
    eventButtonAssignLimitations: function(e){
        
        var modelCategoryLimitation = new CategoryLimitationsModel({idInspCategory : this.model.id});
        modalAssignLimitations.show(modelCategoryLimitation);
    },
    /**
     * Event handler for "Remove" button
     * @param {Object} e
     */
    eventRemove: function(e) {
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
    }
});

var CategoryMultiviewMain = Multiview.multiviewMainFactory(CategoryMultiviewRow, {});


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

        this.multiview = new CategoryMultiviewMain({collection: this.collection});
        this.listenTo(this.collection, 'add', this.eventCategoryAdded);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {CategoryListMainView}
     */
    render: function() {
        
        var template, multiview;
        template = Handlebars.templates.categoryListMain;

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
        var newView = new CategoryMultiviewRow({model: model});
        newView.render().$el.appendTo($('#multiview-list')).hide().fadeIn(500);
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
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
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
     * @returns {ModalEditCategory}
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
        
        var thisView, toSave, wasNew;
        thisView = this;
        toSave   = {};

        wasNew              = this.model.isNew();
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



var ModalAssignSkills = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalAssignSkills}
     */
    initialize: function(options) {
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalAssignSkills}
     */
    render: function() {
        
        var thisView;
        thisView = this;
        thisView.showModal = true;
        
        thisView.model.fetch({
            wait: true,
            success: function(model, response, options) {
                thisView.showModal = true;
                thisView.renderSkills(response.data); 
                if(thisView.showModal){
                    thisView.$el.children().first().modal('show');
                    thisView.showModal = false;
                    return thisView;
                }
            },
            error: function (model, response, options) {
                thisView.showModal = false;
                Util.showError(response.responseJSON);
            }
        });
        
        return this;
    },
    
    renderSkills: function(skills){
        
        var template, elemSkillsList, templateSkillItem;
        
        template = Handlebars.templates.categoryModalAddSkills;
        this.$el.html(template({category: this.model.attributes, meta: hbInitData().meta.Inspection}));
        
        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }
        
        elemSkillsList = $('#skills-list');

        _.each(skills, function(categorySkill) {
            templateSkillItem = Handlebars.templates.categorySkillItem;
            elemSkillsList.append(templateSkillItem({skill: categorySkill}));                 
        });
    },
    
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },

    /**
     * @param {CategoryModel} categoryModal
     * @param {CategoryList} categoryCollection
     * @returns {ModalAssignSkills}
     */
    show: function(categorySkillModal) {

        this.model = categorySkillModal;
        this.render();
        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalAssignSkills}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves assigend skills.
     * @param {Object} e
     */
    eventSave: function(e) {
        
        var thisView, toSave;
        thisView = this;
        toSave   = {};
        
        var skillValues = [];
        $.each($("input[name='skills[]']"), function(element) {
            var skillsData, skillId, skillAssigned;
            skillId = $(this).attr('attr-id');
            skillAssigned = $(this).prop('checked');
            skillsData = { 'id' : skillId, 'assigned' : skillAssigned };
            skillValues.push(skillsData);
        });

        toSave.skills   = skillValues;
        
        this.model.save(toSave, {
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
    }
});



var ModalAssignLimitations = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalAssignLimitations}
     */
    initialize: function(options) {
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalAssignLimitations}
     */
    render: function() {
        
        var thisView;
        thisView = this;
        thisView.showModal = true;
        
        thisView.model.fetch({
            wait: true,
            success: function(model, response, options) {
                thisView.showModal = true;
                thisView.renderLimitations(response.data); 
                if(thisView.showModal){
                    thisView.$el.children().first().modal('show');
                    thisView.showModal = false;
                    return thisView;
                }
            },
            error: function (model, response, options) {
                thisView.showModal = false;
                Util.showError(response.responseJSON);
            }
        });
        
        return this;
    },
    
    renderLimitations: function(limitations){
        
        var template, elemLimitationsList, templateLimitationItem;
        
        template = Handlebars.templates.categoryModalAddLimitations;
        this.$el.html(template({}));
        
        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }
        
        elemLimitationsList = $('#limitations-list');

        _.each(limitations, function(categoryLimitation) {
            templateLimitationItem = Handlebars.templates.categoryLimitationItem;
            elemLimitationsList.append(templateLimitationItem({limitation: categoryLimitation}));                 
        });
    },
    
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel",
        "submit form": "eventSave"
    },
    
    /**
     * @param {CategoryLimitationModal} categoryLimitationModal
     * @returns {ModalAssignLimitations}
     */
    show: function(categoryLimitationModal) {
        
        this.model = categoryLimitationModal;
        this.render();
        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalAssignLimitations}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves assigned limitations.
     * @param {Object} e
     */
    eventSave: function(e) {
        
        var thisView, toSave;
        thisView = this;
        toSave   = {};
        
        var limitationValues = [];
        
        $.each($("input[name='limitations[]']"), function(element) {
            
            var limitationData, limitationId, limitationAssigned;
            limitationId = $(this).attr('attr-id');
            limitationAssigned = $(this).prop('checked');
            limitationData = { 'id' : limitationId, 'assigned' : limitationAssigned };
            limitationValues.push(limitationData);
        });

        toSave.limitations  = limitationValues;
        
        this.model.save(toSave, {
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
    }
});



var ModalAssignTypes = BizzyBone.BaseView.extend({
    /**
     * 
     * @param {Object} options
     * @returns {ModalAssignTypes}
     */
    initialize: function(options) {
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },

    render: function() {
        
        var thisView;
        thisView = this;
        thisView.showModal = true;
        
        thisView.model.fetch({
            wait: true,
            success: function(model, response, options) {
                thisView.showModal = true;
                thisView.renderTypes(response.data); 
                if(thisView.showModal){
                    thisView.$el.children().first().modal('show');
                    thisView.showModal = false;
                    return thisView;
                }
            },
            error: function (model, response, options) {
                thisView.showModal = false;
                Util.showError(response.responseJSON);
            }
        });
        
        return this;
    },
    
    renderTypes: function(types){
        
        var template, elemTypesList, templateTypeItem;
        
        template = Handlebars.templates.categoryModalAddTypes;
        this.$el.html(template({category: this.model.attributes, meta: hbInitData().meta.Inspection}));
        
        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }
        
        elemTypesList = $('#type-list');

        _.each(types, function(categorySkill) {
            templateTypeItem = Handlebars.templates.categoryTypesItem;
            elemTypesList.append(templateTypeItem({type: categorySkill}));                 
        });
    },
    
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    /**
     * 
     * @param {categoryTypeModal} categoryTypeModal
     * @returns {ModalAssignTypes}
     */
    show: function(categoryTypeModal) {
        
        this.model = categoryTypeModal;
        this.render();
        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalAssignTypes}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves category type assignment.
     * @param {Object} e
     */
    eventSave: function(e) {
        
        var thisView, toSave, type, url;
        thisView = this;
        toSave   = {};
        
        var typeValues = [];
        $.each($("input[name='types[]']"), function(element) {
            
            var typesData, typesId, typesAssigned;
            typesId = $(this).attr('attr-id');
            typesAssigned = $(this).prop('checked');
            typesData = { 'id' : typesId, 'assigned' : typesAssigned };
            typeValues.push(typesData);
        });

        toSave.types   = typeValues;
        
        this.model.save(toSave, {
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
    }
});
