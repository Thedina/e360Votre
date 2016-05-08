/**
 * Inspectors: Models
 */

/**
 * Backbone model for Inspector
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
});


var InspectorList = BizzyBone.BaseCollection.extend({
    model: InspectorModel,
    url: hbInitData().meta.Inspector.apiPath,
});

var InspectorSkills = BizzyBone.BaseCollection.extend({
    model: InspectorModel,
    url: hbInitData().meta.Inspector.apiPath,
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
    /**
     * @param {Object} attributes
     * @param {Object} [options]
     * @returns {GroupModel}
     */
    set: function(attributes, options) {    

        return BizzyBone.BaseModel.prototype.set.call(this, attributes, options);
    },
});


var InspectorLimitationsModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspector.apiPath,
    idAttribute: 'idInspLimitation',
    defaults: {
        idInspector: 0,
        firstName : '',
        lastName : '',
        description: '',
    },
    /**
     * @param {Object} attributes
     * @param {Object} [options]
     * @returns {InspectorLimitationsModel}
     */
    set: function(attributes, options) {    

        return BizzyBone.BaseModel.prototype.set.call(this, attributes, options);
    },
});