/**
 * Skill: Views
 */

var SkillMultiviewRow = Multiview.multiviewRowFactory({
    
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventSkillUpdated);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    events: {
        "click .btn-edit": "eventButtonEditSkill",
        "click .btn-remove": "eventButtonRemoveSkill"
    },
    /**
     * Event handler for click edit skill button
     * @param {Object} e
     */
    eventButtonEditSkill: function(e) {
        modalEditSkill.show(this.model);
    },
    /**
     * Event hander for click remove skill button
     * @param {Object} e
     */
    eventButtonRemoveSkill: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this skill?", function(result) {
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
     * @param {SkillsModel} model
     */
    eventSkillUpdated: function(model) {
        this.render();
    }
    
});

var SkillMultiviewMain = Multiview.multiviewMainFactory(SkillMultiviewRow, {});

/**
 * Backbone view for skill list
 * @typedef {Object} SkillListMainView
 */
var SkillListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {SkillListMainView}
     */
    initialize: function(options) {
        
        this.multiview = new SkillMultiviewMain({collection: this.collection});
        this.listenTo(this.collection, 'add', this.eventSkillAdded);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SkillListMainView}
     */
    render: function() {
        
        var template, multiview;
        template = Handlebars.templates.skillListMain;

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
        "click #btn-new-skill": "eventButtonNewSkill"
    },
    /**
     * Event handler for click "New Group" button
     * @param {Object} e
     */
    eventButtonNewSkill: function(e) {
        var newSkill = new SkillsModel();
        modalEditSkill.show(newSkill, this.collection);
    },
    /**
     * Event hander for collection add skill
     * @param model
     */
    eventSkillAdded: function(model) {
        
        var newView = new SkillMultiviewRow({model: model});
        newView.render().$el.appendTo($('#multiview-list')).hide().fadeIn(500);
    }
});

/**
 * Backbone view for edit group modal
 * @typedef {Object} ModalEditSkill
 */
var ModalEditSkill = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditSkill}
     */
    initialize: function(options) {
        
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },

    render: function() {
        
        var template;
        template = Handlebars.templates.EditSkill;
        this.$el.html(template({skill: this.model.attributes, meta: hbInitData().meta.Inspection}));

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
     * @param {SkillModal} skillModal
     * @param {SkillCollection} skillCollection
     * @returns {ModalEditSkill}
     */
    show: function(skillModal, skillCollection) {
        this.model = skillModal;
        this.collection = skillCollection;

        this.render();

        this.$el.children().first().modal('show');

        return this;

    },
    /**
     * Just hide the modal
     * @returns {ModalEditSkill}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Even handler for "Save" button. Saves new or existing skill model.
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, newStatus, wasNew;
        thisView = this;
        toSave = {};

        wasNew          = this.model.isNew();
        
        toSave.title        = $('#skill-addedit-title').val();
        toSave.description  = $('#skill-addedit-desc').val();
        
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
