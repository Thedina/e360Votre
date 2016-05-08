/**
 * Skills: Models
 */

/**
 * Backbone model for skills
 * @typedef {Object} SkillModel
 */
var SkillsModel= BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspection.apiPath,
    idAttribute: 'idInspSkill',
    defaults: {
        title: '',
        desription: '',
    },
});

SkillsModel = Multiview.modelMultiviewable(SkillsModel, hbInitData().meta.Inspection);

/**
 * A list of SkillModel
 * @typedef {Object} SkillList
 */
var SkillsList = BizzyBone.BaseCollection.extend({
    model: SkillsModel,
    url: hbInitData().meta.Inspection.apiPath
});

SkillsList = Multiview.collectionMultiviewable(SkillsList);
