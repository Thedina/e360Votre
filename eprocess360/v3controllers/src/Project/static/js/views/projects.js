/**
 * Projects: Views
 */

/**
 * Backbone view for project multiview row
 * @typedef {Object} ProjectMultiviewRow
 */
var ProjectMultiviewRow = Multiview.multiviewRowFactory({});

/**
 * Backbone view for project multiview main
 * @typedef {Object} ProjectMultiviewMain
 */
var ProjectMultiviewMain = Multiview.multiviewMainFactory(ProjectMultiviewRow, {});

var ProjectListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ProjectListMainView}
     */
    initialize: function(options) {
        this.multiview = new ProjectMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ProjectListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.projectListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});