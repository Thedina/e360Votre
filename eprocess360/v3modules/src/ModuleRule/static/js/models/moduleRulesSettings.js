/**
 * ModuleRule Settings: Models
 */

/**
 * Backbone base model for moduleRule type data
 * @typedef {Object} ModuleRuleTypeModel
 */
//var ModuleRuleTypeModel = BizzyBone.BaseModel.extend({
//    urlRoot: hbInitData().meta.ModuleRule.apipath,
//    idAttribute: 'idModuleRuleType',
//    defaults: {
//        title: '',
//        idGroup: 0,
//        groupTitle: ''
//    },
//    dontSave: [
//        'groupTitle'
//    ]
//});

/**
 * Backbone base model for moduleRule rule data
 * @typedef {Object} ModuleRuleModel
 */
var ModuleRuleModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.ModuleRule.apiPath,
    idAttribute: 'idModuleRule',
    defaults: {
        order: 0,
        idObjectAction: 0,
        objectActionTitle: '',
        actionType: '',
        expression: '',
        conditions: []
    },
    dontSave: [
        'expression'
    ],
    /**
     * Convert the condition data to a string for display purposes
     * @returns {string}
     */
    conditionsToString: function() {
        var condStrs = [];

        _.each(this.get('conditions'), function(condition) {
            var str = condition.variable + " " + condition.comparator + " " + condition.value;
            if(condition.conjunction != "N/A") str = " " + condition.conjunction;
            out.push(str);
        });

        return condStrs.join(" ");
    }
});

///**
// * Backbone collection of moduleRule types
// * @typedef {Object} ModuleRuleTypeList
// */
//var ModuleRuleTypeList = BizzyBone.BaseCollection.extend({
//    model: ModuleRuleTypeModel,
//    url: hbInitData().meta.ModuleRule.apipath,
//    /**
//     * Get an array containing the names of all the moduleRule types in this collection
//     * @returns {Array.<string>}
//     */
//    toTypeArray: function() {
//        var typeNames = [];
//
//        _.each(this.models, function(typeModel) {
//            typeNames.push(_.clone(typeModel.attributes));
//        });
//
//        return typeNames;
//}
//});

/**
 * Backbone collection of moduleRule
 * @typedef {Object} ModuleRuleList
 */
var ModuleRuleList = BizzyBone.BaseCollection.extend({
    model: ModuleRuleModel,
    url: hbInitData().meta.ModuleRule.apiPath
});