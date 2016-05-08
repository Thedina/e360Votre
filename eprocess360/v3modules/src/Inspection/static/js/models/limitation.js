/**
 * Limitations: Models
 */

/**
 * Backbone model for limitations
 * @typedef {Object} LimitationModel
 */
var LimitationModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Inspection.apiPath,
    idAttribute: 'idInspLimitation',
    defaults: {
        title: '',
        description: '',
    }
});

LimitationModel = Multiview.modelMultiviewable(LimitationModel, hbInitData().meta.Inspection);

/**
 * A list of LimitationModel
 * @typedef {Object} LimitationList
 */
var LimitationList = BizzyBone.BaseCollection.extend({
    model: LimitationModel,
    url: hbInitData().meta.Inspection.apiPath
});


LimitationList = Multiview.collectionMultiviewable(LimitationList);