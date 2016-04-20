/**
 * Submittal: Models
 */

/**
 * Backbone model for submittals
 * @typedef {Object} SubmittalModel
 */
var SubmittalModel = Backbone.Model.extend({
    urlRoot: hbInitData().meta.Submittal.apiPath + '/submittals',
    idAttribute: 'idSubmittal',
    defaults: {
        sequenceNumber: 0,
        idUser: 0,
        idFolder: 0,
        idSubmittalPhase: 0,
        title: '',
        description: '',
        dateCreated: '',
        dateCompleted: '',
        status: {
            isComplete: false,
            hasReview: false
        },
        files: [],
        reviewers: [],
        reviews: []
    },
    /**
     * Parse response data. If data is nested in response.data member use
     * that.
     * @param {Object} response
     * @param {Object} [options]
     * @returns {Object}
     */
    parse: function(response, options) {
        if(_.has(response, 'data')) {
            response = response.data;
        }

        return response;
    },
    /**
     * Omit files etc. - stuff that doesn't get saved back
     * @param {Object} attributes
     * @param {Object} options
     * @returns {Object}
     */
    save: function(attributes, options) {
        options || (options = {});
        attributes = attributes ? _.omit(attributes, 'files', 'reviewers', 'reviews') : _.omit(this.attributes, 'files', 'reviewers', 'reviews');

        options.data = JSON.stringify(attributes);
        return Backbone.Model.prototype.save.call(this, attributes, options);
    }
});

/**
 * A list of SubmittalModels
 * @typedef {Object} SubmittalList
 */
var SubmittalList = Backbone.Collection.extend({
    model: SubmittalModel,
    /**
     * Get a count of open (incomplete) submittals
     * @returns {number}
     */
    countIncomplete: function() {
        return _.reduce(this.models, function(count, submittalModel) {
            return count + (submittalModel.get('status').isComplete ? 0 : 1);
        }, 0);
    },
    /**
     * Get the first (by sequence number) incomplete submittal model
     * @returns {SubmittalModel}
     */
    getFirstIncomplete: function() {
        var firstIncomplete = null;

        _.each(this.models, function(sub) {
            if(!sub.get('status').isComplete) {
                if(firstIncomplete === null || sub.get('sequenceNumber') < firstIncomplete.get('sequenceNumber')) {
                    firstIncomplete = sub;
                }
            }
        });

        return firstIncomplete;
    }
});