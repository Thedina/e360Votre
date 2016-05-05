/**
 * Types: Models
 */

/**
 * Backbone model for Types
 * @typedef {Object} TypeModel
 */
var TypesModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspection.apiPath,
    idAttribute: 'idInspType',
    defaults: {
        idController: 0,
        title: '',
        description: '',
    },
});

/**
 * A list of TypesModel
 * @type Backbone.Collection.extend
 */
var TypesList = BizzyBone.BaseCollection.extend({
    model: TypesModel,
    url: hbInitData().meta.Inspection.apiPath
});
