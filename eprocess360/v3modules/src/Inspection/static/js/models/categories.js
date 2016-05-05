/**
 * Categories: Models
 */


/**
 * Backbone model for inspection categories
 * @type Object|Backbone.Model.extend
 */
var CategoryModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspection.apiPath,
    idAttribute: 'idInspCategory',
    defaults: {
        title: '',
        description: '',
    },
    
});

/**
 * A list of CategoryModels
 * @typedef {Object} CategoryList
 */
var CategoryList = BizzyBone.BaseCollection.extend({
    model: CategoryModel,
    url: hbInitData().meta.Inspection.apiPath,
});
