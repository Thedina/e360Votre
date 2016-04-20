/**
 * Mail: Models
 */

/**
 * Backbone model for email log entry
 * @typedef {Object} MailLogModel
 */
var MailLogModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Mail.apiPath + '/logs',
    idAttribute: 'idMail',
    defaults: {
        idUser: 0,
        idProject: 0,
        idTemplate: 0,
        vars: {},
        dateAdded: '',
        fakeMail: false,
        queue: {},
        recipients: [],
        files: [],
        subject: '',
        bodyHTML: '',
        bodyText: ''
    },
    /**
     * Perform resend request
     * @param {Object} options
     */
    resend: function(options) {
        options = _.extend({
            method: 'PUT',
            url: hbInitData().meta.Mail.apiPath + '/resend/' + this.get('idMail')
        }, options);

        this.save({idMail: this.get('idMail')}, options);
    }
});

// Add multiview functionality to MailLogModel
MailLogModel = Multiview.modelMultiviewable(MailLogModel);

/**
 * Backbone model for email template definition
 * @typedef {Object} MailTemplateModel
 */
var MailTemplateModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Mail.apiPath + '/templates',
    idAttribute: 'idTemplate',
    defaults: {
        idController: 0,
        templateName: '',
        subject: '',
        bodyHTML: ''
    }
});

// Add multiview functionality to MailTemplateModel
MailTemplateModel = Multiview.modelMultiviewable(MailTemplateModel);

/**
 * A collection of MailLogs
 * @typedef {Object} MailLogList
 */
var MailLogList = BizzyBone.BaseCollection.extend({
    model: MailLogModel,
    url: hbInitData().meta.Mail.apiPath + '/logs'
});

// Add multiview functionality to MailLogList
MailLogList = Multiview.collectionMultiviewable(MailLogList);

/**
 * A collection of MailTemplates
 * @typedef {Object} MailTemplateList
 */
var MailTemplateList = BizzyBone.BaseCollection.extend({
    model: MailTemplateModel,
    url: hbInitData().meta.Mail.apiPath + '/templates'
});

// Add multiview functionality to MailTemplateList
MailTemplateList = Multiview.collectionMultiviewable(MailTemplateList);