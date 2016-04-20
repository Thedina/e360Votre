/**
 * Projects: Models
 */

/**
 * Backbone model for project
 * @typedef {Object} ProjectModel
 */
var ProjectModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Project.apiPath,
    idAttribute: 'idProject',
    defaults: {
        idController: 0,
        title: '',
        description: '',
        state: '',
        status: {
            isActive: false
        }
    }
});

// Add multiview functionality to ProjectModel
ProjectModel = Multiview.modelMultiviewable(ProjectModel);

/**
 * A list of projects
 * @typedef {Object} Project List
 */
var ProjectList = BizzyBone.BaseCollection.extend({
    model: ProjectModel,
    url: hbInitData().meta.Project.apiPath
});

// Add multiview functionality to ProjectList
ProjectList = Multiview.collectionMultiviewable(ProjectList);