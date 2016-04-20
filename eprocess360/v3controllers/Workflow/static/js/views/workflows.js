/**
 * Workflows: Views
 */

/**
 * Backbone view for edit workflow modal
 * @typedef {Object} WorkflowEditModalView
 */
var WorkflowEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {WorkflowEditModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        this.groupList = new GroupList();

        // Listen to group list reset event
        this.listenTo(this.groupList, 'reset', this.eventGroupsLoaded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {WorkflowEditModalView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.workflowModalEdit;

        this.$el.html(template({
            workflow: this.model.attributes,
            meta: hbInitData().meta.Workflow
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {WorkflowModel} workflowModel
     * @returns {WorkflowEditModalView}
     */
    show: function(workflowModel) {
        this.model = workflowModel;

        this.groupList.fetch({reset: true});

        return this;
    },
    /**
     * @returns {WorkflowEditModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, groupsSelected;
        thisView = this;
        groupsSelected = [];

        $('#modal-workflow-groups').find('option').each(function(index) {
            if($(this).is(':selected')) {
                groupsSelected.push(parseInt($(this).val()));
            }
        });

        toSave = {
            title: $('#modal-workflow-title').val(),
            description: $('#modal-workflow-description').val(),
            class: $('#modal-workflow-class').find(':selected').val(),
            groups: groupsSelected,
            status: {
                isActive: $('#modal-workflow-active').is(':checked')
            }
        };

        this.model.save(toSave, {
            wait: true
        });
    },
    /**
     * Event handler for modal cancel button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    },
    /**
     * Event handler for group list reset
     * @param {GroupList} collection
     */
    eventGroupsLoaded: function(collection) {
        this.render();
        this.$el.children().first().modal('show');
    }
});