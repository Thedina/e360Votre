/**
 * Handlebars helpers for groups
 */

/**
 * Get appropriate status text for group
 * @param {Object} status
 * @param {boolean} status.isActive
 * @returns {string}
 */
Handlebars.registerHelper('groupStatus', function(status) {
    if(status.isActive) {
        return 'Active';
    }
    else {
        return 'Inactive';
    }
});

/**
 * Get appropriate status text for group user
 * @param {Object} status
 * @param {boolean} status.isActive
 * @returns {string}
 */
Handlebars.registerHelper('groupUserStatus', function(status) {
    if(status.isActive) {
        return 'Active';
    }
    else {
        return 'Inactive';
    }
});

/**
 * Get role name for role ID
 * @param {number} idRole
 * @param {Object} roleTable
 * @returns {string}
 */
Handlebars.registerHelper('groupUserRole', function(idRole, roleTable) {
    return roleTable[idRole];
});

/**
 * Generates HTML for options list for user role select.
 * @param {Object} roleTable
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('roleOptions', function(roleTable, selected) {
    var options = "";
    _.each(roleTable, function(name, id) {
        if(id) {
            options += "<option value='" + id + "'" + (id === selected ? " selected='selected'" : "") + ">" + name + "</option>";
        }
    });
    return options;
});

/**
 * Generate checked property if status.isActive
 * @param {Object} status
 * @param {boolean} status.isActive
 * @return {string}
 */
Handlebars.registerHelper('activeChecked', function(status) {
   return status.isActive ? "checked='checked'" : "";
});