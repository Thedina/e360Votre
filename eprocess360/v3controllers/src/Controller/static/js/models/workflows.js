/**
 * Workflows (Project Controllers): Models
 */

/**
 * Backbone model for workflow data
 * @typedef {Object} WorkflowModel
 */
var WorkflowModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Controller.apiPath,
    idAttribute: 'idController',
    defaults: {
        class: '',
        title: '',
        description: '',
        itemOrder: 0,
        moduleStatus: '',
        path: '',
        status: {
            isAbnormalNamespace: false,
            isActive: false,
            isAllCreate: false
        }
    }
});

// Add multiview functionality to WorkflowModel
WorkflowModel = Multiview.modelMultiviewable(WorkflowModel);

var WorkflowList = BizzyBone.BaseCollection.extend({
    model: WorkflowModel,
    url: hbInitData().meta.Controller.apiPath
});

// Add multiview functionality to WorkflowList
WorkflowList = Multiview.collectionMultiviewable(WorkflowList);