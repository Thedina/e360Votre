/**
 * Categories: Models
 */

/**
 * Backbone model for inspection categories
 * @typedef {Object} CategoryModel
 */
var InspectorModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspector.apiPath,
    idAttribute: 'idInspector',
    defaults: {
        idInspector: 0,
        firstName : '',
        lastName : '',
        description: '',
    },
    dontSave: [
        'users',
        'roles'
    ],
    /**
     * If roles in attributes, call initRoleTable()
     * @param {Object} attributes
     * @param {Object} [options]
     * @returns {GroupModel}
     */
    set: function(attributes, options) {    
        if(_.has(attributes, 'roles')) {
            this.initRoleTable(attributes.roles);
        }
        return BizzyBone.BaseModel.prototype.set.call(this, attributes, options);
    },
    /**
     * Create lookup table from roles data
     * @param {Object} roles
     */
    initRoleTable: function(roles) {
        var thisModel = this;
        thisModel.roleTable = {};
        _.each(roles, function(role) {
            thisModel.roleTable[role.idRole] = role.title;
        });

        return thisModel;
    }
});

/**
 * Backbone model for users in groups
 * @typedef {Object} GroupUserModel
 */
//var GroupUserModel = BizzyBone.BaseModel.extend({
//    /**
//     * Takes an idGroup of the containing group
//     * @param attributes
//     * @param options
//     * @param options.idGroup
//     * @returns {GroupUserModel}
//     */
//    initialize: function(attributes, options){
//        if(_.has(options, 'group')) {
//            this.group = options.group;
//        }
//        return BizzyBone.BaseModel.prototype.initialize.call(this, attributes, options);
//    },
//    /**
//     * If this has an idGroup, use that in the URL
//     * @returns {string}
//     */
//    urlRoot: function() {
//        // So I probably didn't *need* to implement string interpolation for this case,
//        // as it turns out. But I don't want to forget that it exists now.
//        return hbInitData().meta.Group.apiPath + (this.group.get('idGroup') ? Util.strSub('/{id}', {id: this.group.get('idGroup')}) : '') + '/users';
//    },
//    idAttribute: 'idUser',
//    defaults: {
//        idRole: 0,
//        firstName: '',
//        lastName: '',
//        status: {
//            isActive: true
//        }
//    }
//});

/**
 * A list of CategoryModels
 * @typedef {Object} CategoryList
 */
var InspectorList = BizzyBone.BaseCollection.extend({
    model: InspectorModel,
    url: hbInitData().meta.Inspector.apiPath,
    /**
     * Export a lookup table of group names by ID
     * @returns {Object}
     */
//    toGroupTable: function() {
//        var table = {};
//
//        _.each(this.models, function(groupModel) {
//            table[groupModel.get('idGroup')] = _.clone(groupModel.attributes);
//        });
//
//        return table;
//    }
});

var InspectorSkills = BizzyBone.BaseCollection.extend({
    model: InspectorModel,
    url: hbInitData().meta.Inspector.apiPath,
    /**
     * Export a lookup table of group names by ID
     * @returns {Object}
     */
//    toGroupTable: function() {
//        var table = {};
//
//        _.each(this.models, function(groupModel) {
//            table[groupModel.get('idGroup')] = _.clone(groupModel.attributes);
//        });
//
//        return table;
//    }
});











var InspectorSkillsModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspector.apiPath,
    idAttribute: 'idInspSkill',
    defaults: {
        idInspector: 0,
        firstName : '',
        lastName : '',
        description: '',
    },
    dontSave: [
        'users',
        'roles'
    ],
    /**
     * If roles in attributes, call initRoleTable()
     * @param {Object} attributes
     * @param {Object} [options]
     * @returns {GroupModel}
     */
    set: function(attributes, options) {    

        return BizzyBone.BaseModel.prototype.set.call(this, attributes, options);
    },
});