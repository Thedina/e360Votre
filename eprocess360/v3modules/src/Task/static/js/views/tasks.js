/**
 * Backbone views for Task module
 */

/**
 * Containing Backbone view for task list
 * @typedef {Object} TaskListMainView
 */
var TaskListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {TaskListMainView}
     */
    initialize: function(options) {
        this.mode = 'week';
        this.showComplete = true;
        this.groupSelectView = new TaskGroupSelectView({collection: new TaskCountData()});
        this.tableView = new TaskTableView({collection: this.collection});

        this.setStartDate(moment().startOf('week').add(1, 'day'), true);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TaskListMainView}
     */
    render: function(preserveContents) {
        if(typeof preserveContents === 'undefined') preserveContents = false;

        var thisView, template;
        thisView = this;
        template = Handlebars.templates.taskListMain;

        this.$el.html(template({initDate: this.startDateDisplay, meta: hbInitData().meta.Task}));

        this.$el.find('#date-select').datepicker({
            format: 'mm/dd/yy',
            daysOfWeekDisabled: [0, 2, 3, 4, 5, 6],
            weekStart: 1
        }).on('show', function(e) {
            $('td.day.active').closest('tr').find('td.day.disabled').addClass('highlighted');
        }).on('changeDate', function(e) {
            $('td.day.active').closest('tr').find('td.day.disabled').addClass('highlighted');
            thisView.eventPickerSelectDate(e);
        });

        this.groupSelectView.setElement(this.$el.find('#task-group-select'));
        this.groupSelectView.render();

        this.tableView.setElement(this.$el.find('#task-list'));
        this.tableView.render();

        return this;
    },
    events: {
        "click #btn-date-previous": "eventDatePrevious",
        "click #btn-date-next": "eventDateNext",
        "click #btn-toggle-show-complete": "eventToggleShowComplete"
    },
    /**
     * Set the start date for the tasks view
     * @param {Date} date
     * @param {boolean} init
     * @returns {TaskListMainView}
     */
    setStartDate: function(date, init) {
        init || (init = false);

        var thisView = this;

        this.startDate = date;
        this.endDate = moment(date).add(6, 'days');
        this.startDateDisplay = moment(date).format('MM/DD/YY');
        this.startDateFull = moment(date).format('YYYY-MM-DD');

        this.$el.find('#date-select').val(this.startDateDisplay);
        $('#header-date').text(this.startDateDisplay);

        if(!init) {
            this.collection.requestUpdate(this.groupSelectView.getGroup(), {
                showPastDue: true,
                startDate: thisView.startDateFull,
                endDate: thisView.endDate.format('YYYY-MM-DD')
            });
        }

        return this;
    },
    /**
     * Function to change the selected filter group
     * @param {number} idGroup
     * @returns {TaskListMainView}
     */
    setGroup: function(idGroup) {
        /** TODO update group selection more efficiently */
        this.groupSelected = idGroup;
        this.groupSelectView.setGroup(this.groupSelected);

        this.collection.requestUpdate(idGroup, {
            showPastDue: true,
            startDate: this.startDateFull,
            endDate: this.endDate.format('YYYY-MM-DD')
        });
        return this;
    },
    /**
     * Event handler for datepicker select date
     * @param {Object} e
     */
    eventPickerSelectDate: function(e) {
        this.setStartDate(e.date);
    },
    /**
     * Event handler for previous date button
     * @param {Object} e
     */
    eventDatePrevious: function(e) {
        this.setStartDate(moment(this.startDate).subtract(1, 'week'));
    },
    /**
     * Event handler for next date button
     * @param {Object} e
     */
    eventDateNext: function(e) {
        this.setStartDate(moment(this.startDate).add(1, 'week'));
    },
    /**
     * Event handler for hide/show completed tasks
     * @param {Object} e
     */
    eventToggleShowComplete: function(e) {
        $(e.target).toggleClass('active').blur();
        this.showComplete = this.showComplete ? false : true;

        if(this.showComplete) {
            $('.task-complete').show();
        }
        else {
            $('.task-complete').hide();
        }
    }
});

/**
 * Backbone subview for managing group selection/filter interface
 * @typedef {Object} TaskGroupSelectView
 */
var TaskGroupSelectView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {TaskGroupSelectView}
     */
    initialize: function(options) {
        this.groupSelected = 0;
        this.itemViews = [];

        if(!this.collection) {
            this.collection = new TaskCountData();
        }

        // Listen to collection add model event
        this.listenTo(this.collection, 'add', this.eventGroupAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TaskGroupSelectView}
     */
    render: function() {
        var thisView = this;

        this.$el.empty();

        _.each(this.itemViews, function(itemView) {
            thisView.$el.append(itemView.render(thisView.groupSelected).$el);
        });

        return this;
    },
    /**
     * @param {boolean} firstLoad
     * @returns {TaskGroupSelectView}
     */
    update: function(firstLoad) {
        var loadingTemplate = Handlebars.templates.taskGroupSelectLoading;

        if(firstLoad) {
            this.$el.empty();
            this.$el.append(loadingTemplate());
        }

        this.collection.fetch({
            error: function(collection, response, options) {
                Util.showError(response.responseJSON);
            }
        });

        return this;
    },
    /**
     * @param {number} idGroup
     */
    setGroup: function(idGroup) {
        this.groupSelected = idGroup;
        this.render();
    },
    /**
     * @returns {number}
     */
    getGroup: function() {
        return this.groupSelected;
    },
    /**
     * Event handler for group added to group select
     * @param {TaskCountModel} model
     */
    eventGroupAdded: function(model) {
        this.itemViews.push(new TaskGroupSelectItemView({model: model}));
        this.render();
    }
});

/**
 * Backbone subview for single item in group selection/filter interface
 * @typedef {Object} TaskGroupSelectItemView
 */
var TaskGroupSelectItemView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {TaskGroupSelectItemView}
     */
    initialize: function(options) {
        this.groupSelected = 0;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @param {number} groupSelected
     * @returns {TaskGroupSelectItemView}
     */
    render: function(groupSelected) {
        if(typeof groupSelected === 'undefined') groupSelected = 0;

        var template;
        template = Handlebars.templates.taskGroupSelectItem;

        if(groupSelected !== this.groupSelected) {
            this.groupSelected = groupSelected;
        }

        this.renderTemplate({group: this.model.attributes, meta: hbInitData().meta.Task}, template);


        // Show/hide past due icon
        if(this.model.get('pastDue') > 0) {
            this.$el.find('#has-past-due-' + this.model.get('idGroup')).show();
        }
        else {
            this.$el.find('#has-past-due-' + this.model.get('idGroup')).hide();
        }

        // Add/remove 'active' class for selection highlighting
        if(this.model.get('idGroup') === this.groupSelected) {
            this.$el.addClass('active');
        }
        else {
            this.$el.removeClass('active');
        }

        return this;
    },
    events: {
        "click a": "eventSelectGroup"
    },
    /**
     * Event handler for click on group item
     * @param {Object} e
     */
    eventSelectGroup: function(e) {
        e.preventDefault();
        tasksRouter.navigate('/showGroup/' + $(e.target).closest('a').data('idgroup'), {trigger: true});
    },
    /**
     * Event handler for group count/info updated
     * @param {TaskCountModel} model
     */
    eventGroupChanged: function(model) {
        this.render(this.groupSelected);
    },
    /**
     * Event handler for group removed
     * @param {TaskCountModel} model
     */
    eventGroupRemoved: function(model) {
        var thisView = this;
        thisView.$el.fadeOut(500, function() {
            thisView.remove();
        });
    }
});

/**
 * Backbone subview for task list table container
 * @typedef {Object} TaskTableView
 */
var TaskTableView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {TaskTableView}
     */
    initialize: function(options) {
        this.initItemViews(true);

        // Listen to collection reset event
        this.listenTo(this.collection, 'reset', this.eventTasksReset);

        // Listen to collection taskmoved event
        this.listenTo(this.collection, 'taskmoved', this.eventTaskMoved);
        
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TaskTableView}
     */
    render: function() {
        var thisView, sectionTemplate, curSection, isToday;
        thisView = this;
        sectionTemplate = Handlebars.templates.taskListSection;

        this.$el.empty();

        // Render 'Past Due' tasks
        if(!_.isEmpty(this.pastDueViews)) {
            thisView.$el.append($(sectionTemplate({section: {
                identifier: 'pastdue',
                title: 'Past Due',
                date: '',
                headerWhichDate: 'Due'
            }})));

            curSection = $('#task-list-section-pastdue');

            _.each(thisView.pastDueViews, function(itemView) {
                curSection.append(itemView.render().$el);
            });
        }

        // Render tasks due for each day of the week
        _.each(this.byDayViews, function(viewsByDay, day) {
            if(!_.isEmpty(viewsByDay)) {
                thisView.$el.append($(sectionTemplate({section: {
                    identifier: 'day' + day,
                    title: Util.weekdayByNumber(day),
                    date: Util.dateFormatDisplay(viewsByDay[0].model.get('dateDue')),
                    headerWhichDate: 'Completed'
                }})));

                curSection = $('#task-list-section-day' + day);

                _.each(viewsByDay, function(itemView) {
                    curSection.append(itemView.render().$el);
                });
            }
        });

        return this;
    },
    /**
     * Build or rebuild child view arrays from collection.
     * @param {boolean} initial
     * @returns {TaskTableView}
     */
    initItemViews: function(initial) {
        initial || (initial = false);

        var thisView = this;

        if(!initial) {
            this.cleanupItemViews();
        }

        this.pastDueViews = [];
        this.byDayViews = [[],[],[],[],[],[],[]]; // One empty array for each day of the week...

        // For each model in collection.pastDue, instantiate a view
        _.each(this.collection.pastDue, function(taskModel) {
            thisView.pastDueViews.push(new TaskListItemView({model: taskModel}));
        });

        // For each model in collection.byDay, instantiate a view
        _.each(_.range(0, 7), function(dayNum) {
            var dayModels = thisView.collection.getByDay(dayNum);
            if(!_.isEmpty(dayModels)) {
                _.each(dayModels, function(taskModel) {
                    thisView.byDayViews[dayNum].push(new TaskListItemView({model: taskModel}));
                });
            }
        });

        return this;
    },
    /**
     * Make sure to remove all child views before instantiating new ones!
     * @returns {TaskTableView}
     */
    cleanupItemViews: function() {
        var thisView = this;

        // Remove all 'past due' views
        _.each(thisView.pastDueViews, function(view, i) {
            view.$el.remove();
            view.remove();
            delete thisView.pastDueViews[i];
        });

        // Remove all 'by day' views
        _.each(_.range(0, 7), function(dayNum) {
            _.each(thisView.byDayViews[dayNum], function(view, i) {
                view.$el.remove();
                view.remove();
                delete thisView.byDayViews[dayNum][i];
            });
        });

        return this;
    },
    /**
     * Event handler for collection taskmoved (must re-render whole view)
     * @param {TaskModel} model
     */
    eventTaskMoved: function(model) {
        this.initItemViews();
        this.render();
    },
    /**
     * Event handler for tasks list reset
     * @param {TaskList} collection
     */
    eventTasksReset: function(collection) {
        this.initItemViews();
        this.render();
    }
});

/**
 * Backbone subview for single task in task list
 * @typedef {Object} TaskTableView
 */
var TaskListItemView = BizzyBone.BaseView.extend({
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTaskUpdated);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {TaskListItemView}
     */
    render: function() {
        var template = Handlebars.templates.taskListItem;

        this.renderTemplate({task: this.model.attributes}, template);

        this.applyPermissions();

        if(this.model.get('status').allDay) {
            this.$el.find('.time-due').remove();
        }

        if (this.model.get('idUser') != hbInitData().meta.User.idUser) {
            this.$el.find("[data-action='unassign']").remove();
        }

        if (!parseInt(this.model.get('idUser'))) {
            this.$el.find('.assignedTo').html('<mark>Unassigned</mark>');
        }

        return this;
    },
    events: {
        "click .task-menu a": "eventMenuItem"
    },
    permissionTargets: {
        Task: 'meta'
    },
    /**
     * Event handler for model change
     * @param {TaskModel} model
     */
    eventTaskUpdated: function(model) {
        this.render();
    },
    /**
     * Event handler for edit task
     * @param {Object} e
     */
    eventEdit: function(e) {
        modalEditTask.show(this.model);
    },
    /**
     * Event handler for unassign self task
     * @param {Object} e
     */
    eventUnassignSelf: function(e) {
        /** TODO only display this option on tasks you are assigned to*/
        var thisView = this;

        bootbox.confirm("Are you sure you want to unassign this task?", function (result) {
            if (result) {
                thisView.model.save({idUser: 0}, {
                    wait: true,
                    error: function (model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        });
    },
    /**
     * Event handler for delete task
     * @param {Object} e
     */
    eventDelete: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this task?", function (result) {
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
     * Event dispatch handler for task menu items
     * @param {Object} e
     */
    eventMenuItem: function(e) {
        e.preventDefault();
        e.stopPropagation();
        var target = $(e.target);

        target.closest('.dropdown').removeClass('open');

        // menu items are distinguished in data-action attributes
        switch(target.data('action')) {
            case 'edit':
                this.eventEdit(e);
                break;
            case 'unassign':
                this.eventUnassignSelf(e);
                break;
            case 'delete':
                this.eventDelete(e);
                break;
        }
    }
});

/**
 * Backbone view for edit task modal
 * @typedef {Object} ModalEditTaskView
 */
var ModalEditTaskView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalEditTaskView}
     */
    initialize: function(options) {
        this.rendered = false;
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalEditTaskView}
     */
    render: function() {
        var template = Handlebars.templates.taskModalEdit;

        this.$el.html(template({
            task: this.model.attributes,
            groupUsers: this.groupUserList.models,
            meta: hbInitData().meta.Task
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {TaskModel} taskModel
     * @returns {ModalEditTaskView}
     */
    show: function(taskModel) {
        this.model = taskModel;

        // TODO This could be made much more elegant but involves changes to groups impl (use collection routes!) as well
        this.group = new GroupModel({idGroup: this.model.get('idGroup')});
        this.groupUserList = new GroupUserList();
        this.listenTo(this.group, 'change', this.eventGroupLoaded);

        //this.render();
        //this.$el.children().first().modal('show');

        this.group.fetch();

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditTaskView}
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
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave;
        thisView = this;

        toSave = {
            title: $('#task-edit-title').val(),
            description: $('#task-edit-description').val(),
            idUser: parseInt($('#task-edit-iduser').val()),
            dateDue: Util.dateFormatStorage($('#task-edit-datedue').val())
        };

        this.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                thisView.hide();
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for 'Cancel' button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    },
    /**
     * Event handler for this.group change event
     * @param {GroupModel} model
     */
    eventGroupLoaded: function(model) {
        this.groupUserList.reset(model.get('users'));
        this.render();
        this.$el.children().first().modal('show');
    }
});