/**
 * Handlebars helpers for Task module
 */

/**
 * Build a <td> for the due date of a past due task or the completion date
 * otherwise.
 * @param {Object} task
 * @returns {string}
 */
Handlebars.registerHelper('makeDateColumn', function(task) {
    var html = "<td";

    if(task.pastDue) {
        html += " class='past-due'";
    }

    html += ">";

    if(task.pastDue) {
        html += Util.dateFormatDisplay(task.dateDue);
    }
    else {
        html += Util.dateFormatDisplay(task.dateCompleted);
    }

    html += "</td>";

    return html;
});

/**
 * Build a <td> for the task status icon
 * @param {Object} task
 * @returns {string}
 */
Handlebars.registerHelper('makeStatusIcon', function(task) {
    var html, highlightClass, icon;

    if(task.status.isComplete) {
        icon = 'fa fa-check-square-o';
    }
    else {
        icon = 'fa fa-square-o';
    }

    if(task.pastDue) {
        highlightClass = 'alert-danger';
        icon = 'fa fa-exclamation-triangle';
    }
    else if(moment(task.dateDue).format('YYYY-MM-DD') === moment().format('YYYY-MM-DD')) {
        // Easiest way to check if it's due today but may be better to pass around a single
        // authoritative 'today' instead really
        highlightClass = 'alert-warning';
    }
    else {
        highlightClass = null;
    }

    html = "<td" + (highlightClass !== null ? " class='" + highlightClass + "' " : " ") + "style='width: 1em;'>";

    html += "<i class='" + icon + "'></i>";

    html += "</td>";

    return html;
});

/**
 * Generate class attribute for an item in the task list
 * @param {Object} task
 * @returns {string}
 */
Handlebars.registerHelper('taskItemClasses', function(task) {
    var classes = [];

    if(task.status.isComplete) {
        classes.push('task-complete');
    }

    return !_.isEmpty(classes) ? "class='" + classes.join(' ') + "'" : "";
});

/**
 * Generate text for task assigned to
 * @param {Object} task
 * @returns {string}
 */
Handlebars.registerHelper('assignedTo', function(task) {
    if(!parseInt(task.idUser)) {
        return 'Unassigned';
    }
    else if(task.idUser == hbInitData().meta.User.idUser) {
        return hbInitData().meta.User.name;
    }
    else if (_.isString(task.userName)) {
        return task.userName;
    }
});

Handlebars.registerHelper('groupUserOptions', function(groupUsers, selected) {
    var html = "";

    _.each(groupUsers, function(user) {
        html += Util.makeOption(
            user.get('idUser'),
            user.get('firstName') + ' ' + user.get('lastName'),
            user.get('idUser') == selected
        );
    });

    return html;
});