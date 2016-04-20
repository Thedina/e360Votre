/**
 * Backbone models for Task module
 */

/**
 * Backbone model for tasks
 * @typedef {Object} TaskModel
 */
var TaskModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Task.apiPath,
    idAttribute: 'idTask',
    defaults: {
        idUser: 0,
        title: '',
        description: '',
        idGroup: 0,
        dateCreated: null,
        dateDue: null,
        dateCompleted: null,
        url: '',
        status: {
            isComplete: false,
            isRead: false,
            hasReview: false,
            allDay: false
        },
        pastDue: false,
        userName: ''
    },
    dontSave: [
        'pastDue',
        'userName'
    ]
});

/**
 * Backbone model (not a very good one) for task counts and metadata
 * @typedef {Object} TaskCountModel
 */
var TaskCountModel = Backbone.Model.extend({
    idAttribute: 'idGroup',
    defaults: {
        title: '',
        taskCount: 0,
        pastDue: false
    }
});


/**
 * A list of TaskModels
 * @typedef {Object} TaskList
 */
var TaskList = BizzyBone.BaseCollection.extend({
    model: TaskModel,
    url: hbInitData().meta.Task.apiPath,
    /**
     * @param {Array} models
     * @param {Object} options
     */
    initialize: function(models, options) {
        this.UPDATE_WAIT = 200;
        this.updateTimer = null;
        this.pendingGroup = null;
        this.pendingData = null;
        this.noDate = [];
        this.pastDue = [];
        this.byDay = [[],[],[],[],[],[],[]]; // One empty array for each day of the week...

        // Listen to change event on models
        this.listenTo(this, 'change:dateDue', this.eventTaskDateChanged);
    },
    /**
     * Custom parse function to pull things out of 'data' (should go in BizzyBone maybe?)
     * @param {Object} response
     * @returns {Object}
     */
    parse: function(response) {
        if(_.has(response, 'data')) response = response.data;

        return response;
    },
    /**
     * Custom set function to maintain references to models sorted by day of week/past due
     * @param {Array} models
     * @param {Object} options
     * @returns {TaskModel|Array.<TaskModel>}
     */
    set: function(models, options) {
        var thisCollection, processedModels;
        thisCollection = this;

        processedModels = Backbone.Collection.prototype.set.call(this, models, options);
        processedModels = _.isArray(processedModels) ? processedModels : [processedModels];

        if(options.reset) {
            this.noDate = [];
            this.pastDue = [];
            this.byDay = [[],[],[],[],[],[],[]];
        }

        _.each(processedModels, function(taskModel) {
            var taskDate;
            if(taskModel.get('dateDue')) {
                taskDate = moment(taskModel.get('dateDue'));

                if (taskModel.get('pastDue')) {
                    // Add to pastDue
                    if(options.reset) {
                        thisCollection.pastDue.push(taskModel);
                    }
                    else {
                        thisCollection.smartPush(thisCollection.pastDue, taskModel);
                    }
                }
                else {
                    // Add to byDay
                    if(options.reset) {
                        thisCollection.byDay[taskDate.day()].push(taskModel);
                    }
                    else {
                        thisCollection.smartPush(thisCollection.byDay[taskDate.day()], taskModel);
                    }
                }
            }
            else {
                // Add to noDate
                if(options.reset) {
                    thisCollection.noDate.push(taskModel);
                }
                else {
                    thisCollection.smartPush(thisCollection.noDate, taskModel);
                }
            }
        });

        return processedModels;
    },
    /**
     * Custom remove function to clean up pastDue and byDay
     * @param {Array} models
     * @param {Object} options
     * @returns {TaskModel|Array.<TaskModel>}
     */
    remove: function(models, options) {
        var thisCollection = this;

        _.each(models, function(modelID) {
            var toRemove, taskDate, day;

            if(thisCollection._isModel(modelID)) modelID = modelID.id;

            toRemove = thisCollection.get(modelID);

            if(toRemove) {
                if(toRemove.get('dateDue')) {
                    taskDate = moment(toRemove.get('dateDue'));

                    if (toRemove.get('pastDue')) {
                        // Remove from pastDue
                        thisCollection.pastDue = _.reject(thisCollection.pastDue, function (m) {
                            return m.id === toRemove.id;
                        });
                    }
                    else {
                        // Remove from byDay
                        day = taskDate.day();
                        thisCollection.byDay[day] = _.reject(thisCollection.byDay[day], function (m) {
                            return m.id === toRemove.id;
                        });
                    }
                }
                else {
                    // Remove from noDate
                    thisCollection.noDate = _.reject(thisCollection.pastDue, function (m) {
                        return m.id === toRemove.id;
                    });
                }
            }
        });

        return Backbone.Collection.prototype.remove.call(this, models, options);
    },
    /**
     * Resort a task model into the correct bucket after it has changed
     * @param {TaskModel} model
     * @returns {boolean}
     */
    updateTaskBucket: function(model) {
        var thisCollection, origDate, taskDate;
        thisCollection = this;

        // If not date not changed, return false
        if(model.get('dateDue') === model.previous('dateDue')) {
            return false;
        }

        // If date changed, remove from old bucket
        if(model.previous('dateDue')) {
            origDate = moment(model.previous('dateDue'));

            if (model.previous('pastDue')) {
                // Remove from pastDue
                thisCollection.pastDue = _.reject(thisCollection.pastDue, function (m) {
                    return m.id === model.id;
                });
            }
            else {
                // Remove from byDay
                day = origDate.day();
                thisCollection.byDay[day] = _.reject(thisCollection.byDay[day], function (m) {
                    return m.id === model.id;
                });
            }
        }
        else {
            // Remove from noDate
            thisCollection.noDate = _.reject(thisCollection.pastDue, function (m) {
                return m.id === model.id;
            });
        }

        // Add to correct bucket
        if(model.get('dateDue')) {
            taskDate = moment(model.get('dateDue'));

            if (model.get('pastDue')) {
                // Add to pastDue
                thisCollection.smartPush(thisCollection.pastDue, model);
            }
            else {
                // Add to byDay
                thisCollection.smartPush(thisCollection.byDay[taskDate.day()], model);
            }
        }
        else {
            // Add to noDate
            thisCollection.smartPush(thisCollection.noDate, model);
        }

        return true;
    },
    /**
     * Helper to retrieve model array for day of week (0-6)
     * @param {number} day
     * @returns {Array.<TaskModel>}
     */
    getByDay: function(day) {
        return this.byDay[day];
    },
    /**
     * Function to add models to an array of models only if their cid is not
     * already present
     * @param {Array.<TaskModel>} array
     * @param {TaskModel} model
     */
    smartPush: function(array, model) {
        var doAdd = true;

        _.each(array, function(existingModel) {
            if(existingModel.get('idTask') === model.get('idTask')) doAdd = false;
        });

        if(doAdd) {
            array.push(model);
        }
    },
    /**
     * Begin a countdown (for rate-limiting purposes) to update the task list,
     * either from the user tasks or group tasks route.
     * @param {number} idGroup
     * @param {Object} data
     * @returns {TaskList}
     */
    requestUpdate: function(idGroup, data) {
        var thisCollection = this;
        this.pendingGroup = idGroup;
        this.pendingData = data;

        window.clearTimeout(this.updateTimer);
        this.updateTimer = window.setTimeout(function() {
            var useUrl = thisCollection.pendingGroup ? thisCollection.url + '/groups/' + thisCollection.pendingGroup : thisCollection.url;

            if(!(thisCollection.pendingGroup === null || thisCollection.pendingData === null)) {
                thisCollection.fetch.call(thisCollection, {
                    url: useUrl,
                    reset: true,
                    data: data
                });

                thisCollection.pendingGroup = null;
                thisCollection.pendingData = null;
            }
        }, this.UPDATE_WAIT);

        return this;
    },
    /**
     * Event handler for task date change
     * @param {TaskModel} model
     */
    eventTaskDateChanged: function(model) {
        this.updateTaskBucket(model);
        this.trigger('taskmoved');
    }
});

/**
 * A collection of task counts and metadata.
 * @typedef {Object} {TaskCountData}
 */
var TaskCountData = BizzyBone.BaseCollection.extend({
    model: TaskCountModel,
    url: hbInitData().meta.Task.apiPath + '/count',
    /**
     * Special parse function to pull out group data and merge with "this user"
     * data
     * @param {Object} response
     * @returns {Array}
     */
    parse: function(response) {
        var groupsCount, userCount;

        if(_.has(response, 'data')) {
            response = response.data;
        }

        groupsCount = response.groupsCount;

        userCount = {
            idGroup: 0,
            title: hbInitData().meta.User.name.split(' ')[0],
            taskCount: response.taskCount,
            pastDue: response.pastDue
        };

        groupsCount.unshift(userCount);

        return groupsCount;
    }
});