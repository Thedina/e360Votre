/**
 * Skills: Models
 */

/**
 * Backbone model for skills
 * @typedef {Object} SkillModel
 */
var SkillModel= BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspection.apiPath,
    idAttribute: 'idInspSkill',
    defaults: {
        title: '',
        desription: '',
    },
});

/**
 * A list of SkillModel
 * @typedef {Object} SkillList
 */
var SkillList = BizzyBone.BaseCollection.extend({
    model: SkillModel,
    url: hbInitData().meta.Inspection.apiPath
});
