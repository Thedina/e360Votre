/**
 * Handlebars helpers for review module
 */

/**
 * Helper to generate status text for review
 * @param {Object} status
 * @param {boolean} status.isComplete
 * @returns {string}
 */
Handlebars.registerHelper('reviewStatus', function(status) {
    if(status.isComplete) {
        return 'Review Complete';
    }
    else {
        return 'Under Review';
    }
});

/**
 * Helper to look up full name from ID-indexed user table
 * @param {number} idUser
 * @param {Object} reviewerTable
 * @returns {string}
 */
Handlebars.registerHelper('reviewerName', function(idUser, reviewerTable) {
    var rev = reviewerTable[idUser];
    if(!rev) return 'No Reviewer';
    return rev.firstName + ' ' + rev.lastName;
});

/**
 * Helper to assign status labels to reviews
 * @param {number} idUser
 * @param {Object} reviewerTable
 * @returns {string}
 */
Handlebars.registerHelper('makeStatusLabels', function(status, idReviewer, dateDue, reviewerTable) {
    var html, pastDue, unassigned;
    html = [];
    pastDue = false;
    unassigned = false;

    if (status.isComplete) {
        html.push("<label class='label label-primary'>Complete</label>");
    }
    else if (moment(dateDue).format('YYYY-MM-DD') < moment().format('YYYY-MM-DD') || (!status.allDay && moment(dateDue).format('YYYY-MM-DD') === moment().format('YYYY-MM-DD')))
    {
        pastDue = true;
        html.push("<label class='label label-danger'>Past Due</label>");
    }

    if (!reviewerTable[idReviewer] && !status.isComplete) {
        unassigned = true;
        html.push("<label class='label label-warning'>Unassigned</label>");
    }

    if (status.isAccepted && status.isComplete) {
        html.push("<label class='label label-success'>Accepted</label>");
    }
    else if (!status.isAccepted && status.isComplete) {
        html.push("<label class='label label-danger'>Not Accepted</label>");
    }

    if (!pastDue && !unassigned && !status.isComplete) {
        html.push("<label class='label label-primary'>Under Review</label>");
    }

    return html.join(' ');
});

/**
 * Helper to identify whether review is assigned to self.
 * @param (number) idReviewer
 * @param (number) idUser
 * @returns (boolean)
 */
Handlebars.registerHelper('notAssignedToMe', function(idReviewer, idUser) {
    if (idReviewer != idUser)
        return true;
});

/**
 * Helper to look up review type name by id
 * @param {number} idReviewType
 * @param {Array} reviewTypes
 * @returns {string}
 */
Handlebars.registerHelper('reviewTypeName', function(idReviewType, reviewTypes) {
    return reviewTypes[idReviewType].title;
});

/**
 * Helper to generate review type options
 * @param {Array.<string>} reviewTypes
 * @param {string} selected
 * @returns {string}
 */
Handlebars.registerHelper('reviewTypeOptions', function(reviewTypes, selected) {
    var options = "";
    _.each(reviewTypes, function(type) {
        options += Util.makeOption(
            type.idReviewType,
            type.title,
            type.idReviewType == selected
        );
    });

    return options;
});

/**
 * Helper to generate group options
 * @param {Object} groupTable
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('groupOptions', function(groupTable, selected) {
    var options = "";
    _.each(groupTable, function(group, idGroup) {
        options += Util.makeOption(idGroup, group.title, idGroup == selected);
    });

    return options;
});

/**
 * Helper to generate reviewer options
 * @param {Object} groupTable
 * @param {Object} reviewerTable
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('reviewerOptions', function(groupTable, reviewerTable, selected) {
    var options, reviewerAlphabetical;
    options = "";

    reviewerAlphabetical = _.sortBy(reviewerTable, function(rev) {
        if(!rev) return 'N/A'; // TODO: what are the implications of this? When if ever will it happen?
        return rev.firstName + rev.lastName;
    });

    _.each(reviewerAlphabetical, function(user) {
        var inGroups = [];
        _.each(groupTable, function(group, idGroup) {
            if(_.contains(group.users, user.idUser)) {
                inGroups.push('group-' + idGroup);
            }
        });

        options += Util.makeOption(
            user.idUser,
            user.firstName + ' ' + user.lastName,
            user.idUser == selected,
            inGroups
        );
    });
    
    options += '<option class="permanent" value="0"' + (selected == 0 ? 'selected="selected"' : '') + '>No Reviewer</option>';

    return options;
});

/**
 * Helper to generate Group: Reviewer text
 * @param {number} idGroup
 * @param {number} idUser
 * @param {Object} groupTable
 * @param {Object} reviewerTable
 * @returns {string}
 */
Handlebars.registerHelper('assignedTo', function(idGroup, idUser, groupTable, reviewerTable) {
    var group, reviewer;
    group = groupTable[idGroup];
    reviewer = reviewerTable[idUser];
    if(!reviewer) return group.title + ': ' + 'No Reviewer';
    return group.title + ': ' + reviewer.firstName + ' ' + reviewer.lastName;
});

/**
 * Helper to generate action type list
 * @param {string} selected
 * @returns {string}
 */
Handlebars.registerHelper('ruleActionTypeOptions', function(selected) {
    var options, actionTypes;
    actionTypes = ['Add', 'Remove'];
    options = "";

    _.each(actionTypes, function(action) {
        options += Util.makeOption(
            action,
            action,
            action === selected
        );
    });

    return options;
});
