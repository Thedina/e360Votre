/**
 * Reviews: Models
 */

/**
 * Backbone model for reviews
 * @typedef {Object} ReviewModel
 */
var ReviewModel = BizzyBone.BaseModel.extend({
    idAttribute: 'idReview',
    defaults: {
        idUser: 0,
        idGroup: 0,
        type: '',
        title: '',
        description: '',
        dateCreated: null,
        dateDue: null,
        dateCompleted: null,
        status: {
            isActive: true,
            isComplete: false,
            isAccepted: false
        },
        submittalComplete: false,
        links: [],
        files: [],
        reviewableFiles: [],
        reviewTypes: [],
        groups: [],
        reviewers: []
    },
    dontSave: [
        'submittalComplete',
        'links',
        'files',
        'reviewableFiles',
        'reviewTypes',
        'groups',
        'reviewers'
    ],
    /**
     * @param {Object} attributes
     * @param {Object} options
     * @param {number} options.urlPrefix
     * @returns {ReviewModel}
     */
    initialize: function(attributes, options) {
        if(_.has(options, 'urlPrefix')) {
            this.urlPrefix = options.urlPrefix;
        }
        return BizzyBone.BaseModel.prototype.initialize.call(this, attributes, options);
    },
    /**
     * Custom urlRoot() because review paths must include the url prefix
     * @returns {string}
     */
    urlRoot: function() {
        if(_.has(this, 'urlPrefix')) {
            return this.urlPrefix + '/' + hbInitData().meta.Review.name
        }
        else {
            return hbInitData().meta.Review.apiPath;
        }
    }
});

/**
 * A list of reviews
 * @typedef {Object} ReviewList
 */
var ReviewList = BizzyBone.BaseCollection.extend({
    model: ReviewModel
});