/**
 * Workflows (Project Controllers): Views
 */

/**
 * Backbone view for workflow multiview row
 * @typedef {Object} WorkflowMultiviewRow
 */
var WorkflowMultiviewRow = Multiview.multiviewRowFactory({});

/**
 * Backbone view for workflow multiview Main
 * @typedef {Object} WorkflowMultiviewMain
 */
var WorkflowMultiviewMain = Multiview.multiviewMainFactory(WorkflowMultiviewRow, {});

/**
 * @typedef {Object} WorkflowListMainView
 */
var WorkflowListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {WorkflowListMainView}
     */
    initialize: function(options) {
        this.multiview = new WorkflowMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {WorkflowListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.workflowListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});