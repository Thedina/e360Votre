/**
 * Backbone routers for Task module
 */

var TaskListRouter = Backbone.Router.extend({
    /**
     * @param {Object} options
     * @param {TaskListMainView} options.taskListView
     */
    initialize: function(options) {
        if(_.has(options, 'taskListView')) {
            this.taskListView = options.taskListView;
        }

        this.isInitialGroup = true;
    },
    routes: {
        "showGroup/:idGroup": "showGroup"
    },
    /**
     * @param {number} idGroup
     */
    showGroup: function(idGroup) {
        idGroup = parseInt(idGroup);

        if(!(this.isInitialGroup && idGroup === 0)) {
            this.taskListView.setGroup(idGroup);
        }

        this.isInitialGroup = false;
    }
});