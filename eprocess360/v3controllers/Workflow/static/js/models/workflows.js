/**
 * Workflows: Models
 */

var WorkflowModel = BizzyBone.BaseModel.extend({
        idAttribute: 'idWorkflow',
        defaults: {
            title: '',
            description: '',
            class: '',
            groups: [],
            status: {
                isActive: false
            }
        }
    });