/**
 * Handlebars helpers for project users
 */

/**
 * Generate text/icons for roles list table column
 * @param {Array.<Object>} localRoles
 * @param {Object} globalRoles
 * @param {Object} roleTable
 * @param {boolean} showGlobal
 * @returns {string}
 */
Handlebars.registerHelper('displayRoles', function(localRoles, globalRoles, roleTable, showGlobal) {
    var roleText, globalRoleText, output;
    roleText = [];
    globalRoleText = [];
    output = "";

    _.each(localRoles, function(role) {
        if (roleText.indexOf(roleTable[role.idLocalRole]) == -1) {
            roleText.push(roleTable[role.idLocalRole]);
        }
    });

    if(showGlobal) {
        _.each(globalRoles, function(value, globalRole) {
            var roleName;
            if (value) {
                switch(globalRole) {
                    case 'isRead': roleName = 'Read'; break;
                    case 'isWrite': roleName = 'Write'; break;
                    case 'isCreate': roleName = 'Create'; break;
                    case 'isDelete': roleName = 'Delete'; break;
                    case 'isAdmin': roleName = 'Admin'; break;
                }
                globalRoleText.push(roleName)
            }
        });
    }

    output += roleText.join(", ");
    output += (roleText.length && globalRoleText.length ? ", " : "");
    output += (globalRoleText.length ? "<i class='glyphicon glyphicon-globe'></i>&nbsp;" + globalRoleText.join('/') : "");

    return output;
});

/**
 * Generate text/icons for 'granted by' list table column. Show each granter at
 * most once.
 * @param {Array.<Object>} localRoles
 * @returns {string}
 */
Handlebars.registerHelper('displayAllGrantedBy', function(localRoles) {
    var granters = {};

    _.each(localRoles, function(role) {
        var grantedText = "";

        if(!granters[role.grantedBy]) {
            if(role.grantedBy !== 'self') {
                grantedText += "<i class='fa fa-users'></i>&nbsp;";
            }

            grantedText += role.grantedBy;
            granters[role.grantedBy] = grantedText;
        }
    });

    return _.toArray(granters).join(', ');
});