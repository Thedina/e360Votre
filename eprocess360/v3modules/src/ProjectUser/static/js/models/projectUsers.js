/**
 * Project Users: Models
 */

/**
 * Backbone model for project user
 * @typedef {Object} ProjectUserModel
 */
var ProjectUserModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.ProjectUser.apiPath,
    idAttribute: 'idUser',
    defaults: {
        idProject: 0,
        localRoles: [],
        globalRoles: {
            isAdmin: false,
            isCreate: false,
            isDelete: false,
            isRead: false,
            isWrite: false
        }
    },
    /**
     * Check if this user has the specified role *only if grantedBy 'self'*
     * @param idRole
     * @returns {boolean}
     */
    hasProjectRole: function(idRole) {
        var wellDoYou = false;

        _.each(this.localRoles, function(localRole) {
            if(localRole.idLocalRole == idRole && localRole.grantedBy === 'self') wellDoYou = true;
        });

        return wellDoYou;
    },
    /**
     * Check if this user has the specified role *not grantedBy 'self'*
     * @param idRole
     * @returns {boolean}
     */
    hasInheritedRole: function(idRole) {
        var wellDoYou = false;

        _.each(this.localRoles, function(localRole) {
            if(localRole.idLocalRole == idRole && localRole.grantedBy !== 'self') wellDoYou = true;
        });

        return wellDoYou;
    },
    /**
     * Return all user's role IDs sorted between 'self'-granted and inherited
     * @returns {{own: {Object}, inherited: {Object}}}
     */
    getLocalRolesSorted: function() {
        var projectRoles = {};
        var inheritedRoles = {};

        _.each(this.get('localRoles'), function(localRole) {
            if(localRole.grantedBy === 'self') {
                projectRoles[localRole.idLocalRole] = localRole;
            }
            else {
                inheritedRoles[localRole.idLocalRole] = localRole;
            }
        });

        return {
            project: projectRoles,
            inherited: inheritedRoles
        };
    },
    /**
     * Return array of names of all user's global roles
     * @returns {Array.<string>}
     */
    getGlobalRoles: function() {
        var globalRoles = [];

        _.each(this.get('globalRoles'), function(value, globalRole) {
            var roleName;

            if (value) {
                switch(globalRole) {
                    case 'isRead': roleName = 'Read'; break;
                    case 'isWrite': roleName = 'Write'; break;
                    case 'isCreate': roleName = 'Create'; break;
                    case 'isDelete': roleName = 'Delete'; break;
                    case 'isAdmin': roleName = 'Admin'; break;
                }

                globalRoles.push(roleName)
            }
        });

        return globalRoles;
    }
});

/**
 * A list of ProjectUserModel
 * @typedef {Object} ProjectUserList
 */
var ProjectUserList = BizzyBone.BaseCollection.extend({
    model: ProjectUserModel,
    url: hbInitData().meta.ProjectUser.apiPath,
    /**
     * Get a user model by ID
     * @param {number} idUser
     * @returns {ProjectUserModel}|null
     */
    getByID: function(idUser) {
        var returnVar;
        returnVar = null;
        _.each(this.models, function(userModel) {
            if(userModel.get('idUser') == idUser) {
                returnVar = userModel;
            }
        });

        return returnVar;
    }
});