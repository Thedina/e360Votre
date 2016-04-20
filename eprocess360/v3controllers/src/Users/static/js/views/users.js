/**
 * Users: Views
 */

/**
 * Backbone view for user list multiview row
 * @typedef {Object} UserListMultiviewRow
 */
var UserListMultiviewRow = Multiview.multiviewRowFactory({});

/**
 * Backbone view for user list multiview main
 * @typedef {Object} UserListMultiviewMain
 */
var UserListMultiviewMain = Multiview.multiviewMainFactory(UserListMultiviewRow, {});

/**
 * Backbone view for user list main
 * @typedef {Object} UserListMainView
 */
var UserListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {UserListMainView}
     */
    initialize: function(options) {
        this.multiview = new UserListMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(options);
    },
    /**
     * @returns {UserListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.userListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Users
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});