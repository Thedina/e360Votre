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
        description: ''
    },
    skillModel: CategorySkillsModel
});

CategoryModel = Multiview.modelMultiviewable(CategoryModel, hbInitData().meta.Inspection);

/**
 * A list of CategoryModels
 * @typedef {Object} CategoryList
 */
var CategoryList = BizzyBone.BaseCollection.extend({
    model: CategoryModel,
    url: hbInitData().meta.Inspection.apiPath
});

CategoryList = Multiview.collectionMultiviewable(CategoryList);


/**
 * Backbone model for Category skills
 * @typedef {Object} CategorySkillsModel
 */
var CategorySkillsModel = BizzyBone.BaseModel.extend({
    
    urlRoot: hbInitData().meta.Inspection.apiPath + '/skills',
    idAttribute: 'idInspCategory',
    canSave: true,
    
    initialize: function(models, options) {
        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    }
});

/**
 * Backbone model for Category limitations
 * @typedef {Object} CategoryLimitationsModel
 */
var CategoryLimitationsModel = BizzyBone.BaseModel.extend({
    
    urlRoot: hbInitData().meta.Inspection.apiPath + '/limitations',
    idAttribute: 'idInspCategory',
    canSave: true,
    
    initialize: function(models, options) {
        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    }
});


/**
 * Backbone model for Category types
 * @typedef {Object} CategoryTypesModel
 */
var CategoryTypesModel = BizzyBone.BaseModel.extend({
    
    urlRoot: hbInitData().meta.Inspection.apiPath + '/types',
    idAttribute: 'idInspCategory',
    canSave: true,
    
    initialize: function(models, options) {
        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    }
});
