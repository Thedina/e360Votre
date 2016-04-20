/**
 * Submittal Phase: Models
 */

/**
 * Backbone model for nestable submittal phases
 * @typedef {Object} SubmittalPhaseModel
 */
var SubmittalPhaseModel = Backbone.Model.extend({
    urlRoot: hbInitData().meta.Submittal.apiPath, /*+ '/submittalPhase'*/
    idAttribute: 'idSubmittalPhase',
    defaults: {
        title: '',
        description: '',
        idProject: 0,
        idParent: 0,
        idController:0,
        idFolder: 0,
        depth: 0,
        sequenceNumber: 0,
        childNextSequenceNumber: 0,
        status: {
            isComplete: false,
            limitOneIncomplete: false
        },
        activeSubmittalNumber: null,
        activeSubmittalCreated: null,
        activeSubmittalStatus: null,
        firstReviewDue: '',
        lastReviewOut: '',
        children: []
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
     * Save to server omitting children array
     * @param {Object} [attributes]
     * @param {Object} [options]
     * @returns {Object}
     */
    save: function(attributes, options) {
        options || (options = {});
        attributes = attributes ? _.omit(attributes, 'children', 'activeSubmittalNumber', 'activeSubmittalStatus', 'activeSubmittalCreated', 'firstReviewDue', 'lastReviewOut') : _.omit(this.attributes, 'children', 'activeSubmittalNumber', 'activeSubmittalStatus', 'activeSubmittalCreated', 'firstReviewDue', 'lastReviewOut');

        if(_.has(attributes, 'children')) {
            delete attributes.children;
        }

        options.data = JSON.stringify(attributes);

        return Backbone.Model.prototype.save.call(this, attributes, options);
    }
});

/**
 * A list of SubmittalPhaseModels
 * @typedef {Object} SubmittalPhaseList
 */
var SubmittalPhaseList = Backbone.Collection.extend({
    model: SubmittalPhaseModel
});