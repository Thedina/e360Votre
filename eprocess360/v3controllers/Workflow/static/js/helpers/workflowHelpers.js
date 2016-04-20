/**
 * Handlebars helpers for workflows
 */

/**
 * Generate select options for workflow class
 * @param {Array.<string>} classList
 * @param {string} selected
 * @returns {string}
 */
Handlebars.registerHelper('classOptions', function(classList, selected) {
    var html = "";

    _.each(classList, function(className) {
        html += Util.makeOption(
            className,
            className,
            selected === className
        );
    });

    return html;
});

/**
 * Generate multi-select options for group
 * @param {Array} groupList
 * @param {Array.<number>} selected
 * @returns {string}
 */
Handlebars.registerHelper('workflowGroupOptions', function(groupList, selected) {
    var html = "";

    _.each(groupList, function(group) {
        html += Util.makeOption(
            group.idGroup,
            group.title,
            _.contains(selected, group.idGroup)
        );
    });

    return html;
});