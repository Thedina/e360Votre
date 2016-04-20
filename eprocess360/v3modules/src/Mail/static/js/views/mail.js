/**
 * Mail: Views
 */

/**
 * Backbone view for full mail log entry
 * @typdef {Object} MailLogEntryView
 */
var MailLogEntryView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {MailLogEntryView}
     */
    initialize: function(options) {
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {MailLogEntryView}
     */
    render: function() {
        var thisView, template, recipientList, attachmentList, variableList;
        thisView = this;
        template = Handlebars.templates.mailLogView;

        this.$el.html(template({
            mail: this.model.attributes,
            meta: hbInitData().meta.Mail
        }));

        recipientList = this.$el.find('#recipient-list');
        variableList = this.$el.find('#variable-list');
        attachmentList = this.$el.find('#attachment-list');

        _.each(this.model.get('recipients'), function(userInfo) {
            recipientList.append(thisView.renderRecipient(userInfo));
        });

        _.each(this.model.get('vars'), function(variableInfo) {
            variableList.append(thisView.renderVariable(variableInfo));
        });

        if(!this.model.get('files').length) {
            this.$el.find('#attachment-header').hide();
        }
        else {
            this.$el.find('#attachment-header').show();
            _.each(this.model.get('files'), function(fileInfo) {
                attachmentList.append(thisView.renderAttachment(fileInfo));
            });
        }

        return this;
    },
    /**
     * Render a single recipient list item
     * @param {Object} userInfo
     * @returns {jQuery|HTMLElement}
     */
    renderRecipient: function(userInfo) {
        var template = Handlebars.templates.mailLogRecipientItem;

        return $(template({
            user: userInfo,
            meta: hbInitData().meta.Mail
        }));
    },
    /**
     * Render a single attachment list item
     * @param {Object} fileInfo
     * @returns {jQuery|HTMLElement}
     */
    renderAttachment: function(fileInfo) {
        var template = Handlebars.templates.mailLogAttachmentItem;

        return $(template({
            file: fileInfo,
            meta: hbInitData().meta.Mail
        }));
    },
    /**
     * Render a single variable list item
     * @param {Object} variableInfo
     * @returns {jQuery|HTMLElement}
     */
    renderVariable: function(variableInfo) {
        var template = Handlebars.templates.mailLogVariableItem;

        return $(template({
            variable: variableInfo,
            meta: hbInitData().meta.Mail
        }));
    },
    events: {
        "click #btn-resend": "eventResend"
    },
    /**
     * Event handler for 'Resend' button
     * @param {Object} e
     */
    eventResend: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to resend this message?", function (result) {
            if(result) {
                thisView.model.resend({
                    wait: true,
                    success: function(model, response, options) {
                        var sent = response.data && response.data.sent;
                        bootbox.alert("Resend attempt " + (sent ? "successful!" : "failed!"));
                    },
                    error: function(model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        });
    }
});

/**
 * Backbone view for mail log multiview row
 * @typedef {Object} MailLogMultiviewRow
 */
var MailLogMultiviewRow = Multiview.multiviewRowFactory({});

/**
 * Backbone view for mail log multiview mail
 * @typedef {Object} MailLogMultiviewMail
 */
var MailLogMultiviewMain = Multiview.multiviewMainFactory(MailLogMultiviewRow, {});

/**
 * Backbone view for mail log multiview page main
 * @typedef {Object} MailLogListMainView
 */
var MailLogListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {MailLogListMainView}
     */
    initialize: function(options) {
        this.multiview = new MailLogMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {MailLogListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.mailLogListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Mail
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});

/**
 * Backbone view for email template editor
 * @typedef {Object} MailTemplateEditView
 */
var MailTemplateEditView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {MailTemplateEditView}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTemplateChanged);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {MailTemplateEditView}
     */
    render: function() {
        var template = Handlebars.templates.mailTemplateEditor;

        this.$el.html(template({
            template: this.model.attributes,
            meta: hbInitData().meta.Mail
        }));

        return this;
    },
    /**
     * @returns {MailTemplateEditView}
     */
    fillEditor: function() {
        $('#mailbody').summernote('code', this.model.get('bodyHTML'));
        return this;
    },
    events: {
        "click #btn-save": "eventSave"
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var toSave, wasNew;
        wasNew = this.model.isNew();

        toSave = {
            templateName: $('#template-edit-name').val(),
            subject: $('#template-edit-subject').val(),
            bodyHTML: $('.note-editable').html()
        };

        this.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                if(wasNew) {
                    window.history.pushState(
                        {idTemplate: model.get('idTemplate')},
                        '',
                        hbInitData().meta.Mail.path + '/templates/' + model.get('idTemplate')
                    );
                }
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for model change event
     * @param {MailTemplateModel} model
     */
    eventTemplateChanged: function(model) {
        this.render();
        initSummernote();
        this.fillEditor();
    }
});

/**
 * Backbone view for mail template multiview row
 * @typedef {Object} MailTemplateMultiviewRow
 */
var MailTemplateMultiviewRow = Multiview.multiviewRowFactory({
    events: {
        "click .btn-remove": "eventRemove"
    },
    /**
     * Event handler for "Remove" button
     * @param {Object} e
     */
    eventRemove: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this email template?", function (result) {
            if (result) {
                thisView.model.destroy({
                    wait: true,
                    success: function (model, response, options) {
                        thisView.$el.fadeOut(500, function () {
                            thisView.remove();
                        });
                    },
                    error: function (model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        });
    }
});

/**
 * Backbone view for mail template multiview main
 * @typedef {Object} MailTemplateMultiviewMain
 */
var MailTemplateMultiviewMain = Multiview.multiviewMainFactory(MailTemplateMultiviewRow, {});

/**
 * Backbone view for mail template list page main
 * @typedef {Object} MailTemplateListMainView
 */
var MailTemplateListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {MailTemplateListMainView}
     */
    initialize: function(options) {
        this.multiview = new MailTemplateMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {MailTemplateListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.mailTemplateListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Mail
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});