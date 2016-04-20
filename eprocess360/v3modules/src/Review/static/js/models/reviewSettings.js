/**
 * Review Settings: Models
 */

/**
 * Backbone base model for review type data
 * @typedef {Object} ReviewTypeModel
 */
var ReviewTypeModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Review.apiPath + '/types',
    idAttribute: 'idReviewType',
    defaults: {
        title: '',
        idGroup: 0,
        groupTitle: ''
    },
    dontSave: [
        'groupTitle'
    ]
});

/**
 * Backbone base model for review rule data
 * @typedef {Object} ReviewRuleModel
 */
var ReviewRuleModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Review.apiPath + '/rules',
    idAttribute: 'idReviewRule',
    defaults: {
        order: 0,
        idReviewType: 0,
        reviewType: '',
        actionType: '',
        expression: '',
        conditions: []
    },
    dontSave: [
        'reviewType',
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

/**
 * Backbone collection of review types
 * @typedef {Object} ReviewTypeList
 */
var ReviewTypeList = BizzyBone.BaseCollection.extend({
    model: ReviewTypeModel,
    url: hbInitData().meta.Review.apiPath + '/types',
    /**
     * Get an array containing the names of all the review types in this collection
     * @returns {Array.<string>}
     */
    toTypeArray: function() {
        var typeNames = [];

        _.each(this.models, function(typeModel) {
            typeNames.push(_.clone(typeModel.attributes));
        });

        return typeNames;
    }
});

/**
 * Backbone collection of review types
 * @typedef {Object} ReviewRuleList
 */
var ReviewRuleList = BizzyBone.BaseCollection.extend({
    model: ReviewRuleModel,
    url: hbInitData().meta.Review.apiPath + '/rules'
});