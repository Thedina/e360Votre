/**
 * Handlebars helpers for module rules
 */

/**
 * Helper to look up module type name by id
 * @param {number} idObjectAction
 * @param {Array} moduleTypes
 * @returns {string}
 */
Handlebars.registerHelper('moduleTypeName', function(idObjectAction, moduleTypes) {
    return moduleTypes[idObjectAction].title;
});

/**
 * Helper to generate module type options
 * @param {Array.<string>} moduleTypes
 * @param {string} selected
 * @returns {string}
 */
Handlebars.registerHelper('moduleTypeOptions', function(moduleTypes, selected) {
    var options = "";
    _.each(moduleTypes, function(type) {
        options += Util.makeOption(
            type.idObjectAction,
            type.objectActionTitle,
            type.idObjectAction == selected
        );
    });

    return options;
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