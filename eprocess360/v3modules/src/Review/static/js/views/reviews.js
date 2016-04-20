/**
 * Reviews: Views
 */

/**
 * Backbone view for list of reviews
 * @typedef {Object} ReviewListMainView
 */
var ReviewListMainView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @param {string} options.urlPrefix
     * @param {Array} options.reviewTypes
     * @param {Array} options.reviewers
     * @param {Array} options.groups
     * @param {Array} options.links
     * @returns {ReviewListMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        if(_.has(options, 'urlPrefix')) {
            this.urlPrefix = options.urlPrefix;
        }

        if(_.has(options, 'reviewTypes')) {
            this.reviewTypes = options.reviewTypes;
        }

        if(_.has(options, 'reviewers')) {
            this.reviewerTable = Util.tableize(options.reviewers, 'idUser');
        }

        if(_.has(options, 'groups')) {
            this.groupTable = Util.tableize(options.groups, 'idGroup');
        }

        if(_.has(options, 'links')) {
            this.links = options.links;
        }

        // For each ReviewModel in the collection, instantiate a view
        _.each(thisView.collection.models, function(reviewModel) {
            thisView.itemViews.push(new ReviewListItemView({model: reviewModel, parent: thisView}));
        });

        // Listen to collection add, change, and destroy events
        this.listenTo(this.collection, 'add', this.eventReviewAdded);
        this.listenTo(this.collection, 'change', this.eventReviewChanged);
        this.listenTo(this.collection, 'destroy', this.eventReviewDestroyed);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewListMainView}
     */
    render: function() {
        var template, reviewList, completionStatus;
        template = Handlebars.templates.reviewListMain;
        completionStatus = this.getCompletionStatus();

        this.$el.html(template({
            links: this.links,
            completionStatus: completionStatus,
            meta: hbInitData().meta.Review
        }));

        this.applyPermissions();

        reviewList = this.$el.find('#review-list');

        _.each(this.itemViews, function(itemView) {
            reviewList.append(itemView.render().$el);
        });

        return this;
    },
    /**
     * Update review completion text without re-rendering
     * @returns {ReviewListMainView}
     */
    updateCompletionText: function() {
        var status = this.getCompletionStatus();

        $('#status-summary').text(status.numComplete + '/' + status.numReviews + ' Complete');

        return this;
    },
    /**
     * Count total reviews and reviews complete
     * @returns {{numReviews: number, numComplete: number}}
     */
    getCompletionStatus: function() {
        var numReviews, numComplete;
        numReviews = 0;
        numComplete = 0;

        _.each(this.collection.models, function(reviewModel) {
            numReviews++;
            if(reviewModel.get('status').isComplete) numComplete++;
        });

        return {
            numReviews: numReviews,
            numComplete: numComplete
        };
    },
    events: {
        "click #btn-add-review": "eventButtonAddReview"
    },
    permissionTargets: {
        Review: 'meta'
    },
    /**
     * Event handler for 'Add Review" button
     * @param {Object} e
     */
    eventButtonAddReview: function(e) {
        var review = new ReviewModel({urlPrefix: this.urlPrefix});
        reviewModalAddEdit.show(review, this.reviewTypes, this.reviewerTable, this.groupTable, this.collection);
    },
    /**
     * Event handler for model added to collection
     * @param {ReviewModel} model
     */
    eventReviewAdded: function(model) {
        var newView, thisView;
        thisView = this;

        newView = new ReviewListItemView({model: model, parent: this});
        this.itemViews.push(newView);
        newView.render().$el.appendTo($('#review-list')).hide().fadeIn(500, function() {
            thisView.updateCompletionText();
        });
    },
    /**
     * Event handler for model changed in collection
     * @param {ReviewModel} model
     */
    eventReviewChanged: function(model) {
        var thisView = this;
        thisView.updateCompletionText();
    },
    /**
     * Event handler for model removed from collection
     * @param {ReviewModel} model
     */
    eventReviewDestroyed: function(model) {
        var thisView = this;
        thisView.updateCompletionText();
    }
});

/**
 * Backbone view for single item in list of reviews
 * @typedef {Object} ReviewListItemView
 */
var ReviewListItemView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @param {Object} options.parent
     * @returns {ReviewListItemView}
     */
    initialize: function(options) {
        this.defaultElement = _.has(options, 'el') ? false : true;

        if(_.has(options, 'parent')) {
            this.parent = options.parent;
        }

        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventReviewChanged);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewListItemView}
     */
    render: function() {
        var template, oldEl;
        template = Handlebars.templates.reviewListItem;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({
                review: this.model.attributes,
                reviewTypes: this.parent.reviewTypes,
                reviewerTable: this.parent.reviewerTable,
                meta: hbInitData().meta.Review,
                user: hbInitData().meta.User
            }));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({
                review: this.model.attributes,
                reviewTypes: this.parent.reviewTypes,
                reviewerTable: this.parent.reviewerTable,
                meta: hbInitData().meta.Review
            }));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {ReviewListItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    events: {
        "click .review-menu a": "eventMenuItem"
    },
    permissionTargets: {
        Review: 'meta'
    },
    /**
     * Event handler for "Edit"
     * @param {Object} e
     */
    eventEdit: function(e) {
        reviewModalAddEdit.show(this.model, this.parent.reviewTypes, this.parent.reviewerTable, this.parent.groupTable);
    },
    /**
     * Event handler for "Assign Me"
     * @param {Object} e
     */
    eventAssignMe: function(e) {
        this.model.save({idUser: hbInitData().meta.User.idUser}, {
            wait: true,
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for "Delete"
     * @param {Object} e
     */
    eventDelete: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this review?", function (result) {
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
    },
    /**
     * Event handler for "Reopen"
     * @param {Object} e
     */
    eventReopen: function(e) {
        var newStatus;

        newStatus = _.clone(this.model.get('status'));
        newStatus.isComplete = false;

        this.model.save({status: newStatus}, {
            wait: true,
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event dispatch handler for review item menu
     * @param e
     */
    eventMenuItem: function(e) {
        e.preventDefault();
        e.stopPropagation();
        var target = $(e.target);

        target.closest('.dropdown').removeClass('open');

        // menu items are distinguished in data-action attributes
        switch(target.data('action')) {
            case 'edit':
                this.eventEdit(e);
                break;
            case 'assignme':
                this.eventAssignMe(e);
                break;
            case 'delete':
                this.eventDelete(e);
                break;
            case 'reopen':
                this.eventReopen(e);
                break;
        }
    },
    /**
     * Event handler for review model change
     * @param {ReviewModel} model
     */
    eventReviewChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for review inside full
 * @typedef {Object} ReviewMainView
 */
var ReviewMainView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @param {Array} options.reviewers
     * @param {Array} options.groups
     * @returns {ReviewMainView}
     */
    initialize: function(options) {
        this.defaultElement = _.has(options, 'el') ? false : true;

        this.reviewerTable = Util.tableize(this.model.get('reviewers'), 'idUser');
        this.groupTable = Util.tableize(this.model.get('groups'), 'idGroup');

        this.submittedView = new ReviewSubmittalFilesView({model: this.model});
        this.commentsView = new ReviewCommentsMainView({model: this.model});

        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventReviewChanged);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @param {boolean} preserveSubviews
     * @returns {ReviewMainView}
     */
    render: function(preserveSubviews) {
        if(typeof preserveSubviews === 'undefined') preserveSubviews = false;

        var template, oldSubmittedView, oldCommentsView;
        template = Handlebars.templates.reviewInsideMain;

        if(preserveSubviews) {
            oldSubmittedView = this.submittedView.$el.detach();
            oldCommentsView = this.commentsView.$el.detach();
        }

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({
                review: this.model.attributes,
                groupTable: this.groupTable,
                reviewerTable: this.reviewerTable,
                user: hbInitData().meta.User,
                meta: hbInitData().meta.Review
            }));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({review: this.model.attributes,
                groupTable: this.groupTable,
                reviewerTable: this.reviewerTable,
                meta: hbInitData().meta.Review
            }));
            oldEl.replaceWith(this.$el);
        }

        if(this.model.get('status').isComplete) {
            this.$el.find('#review-incomplete-banner').hide();
        }

        if(preserveSubviews) {
            this.$el.append(oldSubmittedView);
            this.$el.append(oldCommentsView);
        }
        else {
            this.$el.append(this.submittedView.render().$el);
            this.$el.append(this.commentsView.render().$el);
        }

        this.applyPermissions();

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {ReviewListItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    events: {
        "click #review-menu a": "eventMenuItem",
        "click #btn-complete-review":"eventCompleteReview",
        "click #review-info div": "eventClickInfo"
    },
    permissionTargets: {
        Review: 'meta'
    },
    /**
     * Event handler for "Edit"
     * @param {Object} e
     */
    eventEdit: function(e) {
        reviewModalAddEdit.show(this.model, this.model.get('reviewTypes'), this.reviewerTable, this.groupTable);
    },
    /**
     * Event handler for "Assign Me"
     * @param {Object} e
     */
    eventAssignMe: function(e) {
        this.model.save({idUser: hbInitData().meta.User.idUser}, {
            wait: true,
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for "Delete" with redirect to reviews list
     * @param {Object} e
     */
    eventDelete: function(e) {
        var thisView = this;

        thisView.model.destroy({
            wait: true,
            success: function(model, response, options) {
                thisView.$el.fadeOut(500, function() {
                    var parentURL;

                    _.each(thisView.model.get('links'), function(link) {
                        if(link.rel === 'parent') {
                            parentURL = link.href;
                        }
                    });

                    thisView.remove();

                    if(parentURL) {
                        window.location = parentURL;
                    }
                });
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for "Reopen"
     * @param {Object} e
     */
    eventReopen: function(e) {
        var thisView, newStatus;
        thisView = this;

        newStatus = _.clone(this.model.get('status'));
        newStatus.isComplete = false;

        this.model.save({status: newStatus}, {
            wait: true,
            success: function(model, response, options) {
                thisView.render(false);
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event dispatch handler for review item menu
     * @param e
     */
    eventMenuItem: function(e) {
        e.preventDefault();
        e.stopPropagation();
        var target = $(e.target);

        target.closest('.dropdown').removeClass('open');

        // menu items are distinguished in data-action attributes
        switch(target.data('action')) {
            case 'edit':
                this.eventEdit(e);
                break;
            case 'assignme':
                this.eventAssignMe(e);
                break;
            case 'delete':
                this.eventDelete(e);
                break;
            case 'reopen':
                this.eventReopen(e);
                break;
        }
    },
    /**
     * Event handler for clicking on the info/date bar.
     * @param {Object} e
     */
    eventClickInfo: function(e) {
        e.stopPropagation();

        if(hbInitData().meta.Review.permissions.WRITE) {
            this.eventEdit(e);
        }
    },
    /**
     * Event handler for "Complete Review" button, after it bubbles up from
     * ReviewFileUploadView
     * @param {Object} e
     */
    eventCompleteReview: function(e) {
        var thisView, newStatus;
        thisView = this;

        e.preventDefault();

        bootbox.confirm("Are you sure this review is complete?", function (result) {
            if (result) {
                newStatus = _.clone(thisView.model.get('status'));
                newStatus.isComplete = true;
                newStatus.isAccepted = $('#checkbox-accepted').prop('checked') ? true : false;
                var completeReview = function () {
                    thisView.model.save({status: newStatus}, {
                        wait: true,
                        success: function (model, response, options) {
                            thisView.render(false);
                        },
                        error: function (model, response, options) {
                            Util.showError(response.responseJSON);
                        }
                    });
                };
                if (newStatus.isAccepted === true) {
                    completeReview();
                } else {
                    bootbox.confirm("The submittal has not been accepted. Do you want to complete the review without accepting the submittal?", function(result) {
                        if (result) {
                            completeReview();
                        }
                    });
                }
            }
        });
    },
    /**
     * Event handler for review model change
     * @param {ReviewModel} model
     */
    eventReviewChanged: function(model) {
        this.render(true);
    }
});

/**
 * Backbone view for review comments subview
 * @typedef {Object} ReviewCommentsMainView
 */
var ReviewCommentsMainView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ReviewCommentsMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.defaultElement = _.has(options, 'el') ? false : true;
        this.contentView = null;

        // For each file in the review data instantiate a model then put them in a collection
        this.files = new FileUpload.FileList(_.map(this.model.get('files'), function(fileData) {
            return new FileUpload.FileModel(fileData, {
                urlPrefix: thisView.model.urlPrefix + '/reviews/' + thisView.model.get('idReview')
            });
        }));
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReviewCommentsMainView}
     */
    render: function() {
        var template, oldEl;
        template = Handlebars.templates.reviewCommentsMain;

        if(hbInitData().meta.Review.permissions.WRITE || this.model.get('status').isComplete) {
            // If this is the first render, fill $el from the template. Otherwise replace it.
            if (this.$el.is(':empty')) {
                this.$el.html(template({review: this.model.attributes, meta: hbInitData().meta.Review}));

                // If we rendered into the default div (i.e. this.el was never set) lose the outer
                // div and point whatever is the outermost container from the template
                if (this.defaultElement) {
                    this.setElement(this.$el.children().first());
                }
            }
            else {
                oldEl = this.$el;
                this.setElement(template({review: this.model.attributes, meta: hbInitData().meta.Review}));
                oldEl.replaceWith(this.$el);
            }

            this.applyPermissions();

            // TODO: way to handle this without instantiating a new view each time?
            if (this.model.get('status').isComplete) {
                // Render file list subview
                this.contentView = new FileUpload.FileListView({
                    model: this.model,
                    collection: this.files,
                    urlPrefix: this.model.urlPrefix + '/reviews/' + this.model.get('idReview')
                });

                if (this.contentView.fileViews.length != 0) {
                    this.$el.find('#no-documents-for-comment').hide();
                    this.$el.append(this.contentView.render().$el);
                }
            }
            else {
                // Render file upload subview
                this.contentView = new ReviewFileUploadView({model: this.model, collection: this.files});
                this.$el.find('#no-documents-for-comment').hide();
                this.$el.append(this.contentView.render().$el);
            }
        }

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {ReviewCommentsMainView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    permissionTargets: {
        Review: 'meta'
    }
});

/**
 * Backbone view for review-specific file upload subview
 * @typedef {Object} ReviewFileUploadView
 */
var ReviewFileUploadView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @param {Object} options.urlPrefix
     * @returns {ReviewFileUploadView}
     */
    initialize: function(options) {
        var thisView = this;
        this.defaultElement = _.has(options, 'el') ? false : true;

        // Get an instance of the reusable FileUploadView with the url prefix for this review
        this.fileUploadView = new FileUpload.FileUploadView({
            model: this.model,
            collection: this.collection,
            urlPrefix: thisView.model.urlPrefix + '/reviews/' + thisView.model.get('idReview')
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * Render this review-specific view plus the generic file upload subview
     * @returns {ReviewFileUploadView}
     */
    render: function() {
        var template, oldEl;

        template = Handlebars.templates.reviewFileUpload;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({review: this.model.attributes, meta: hbInitData().meta.Review}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({review: this.model.attributes, meta: hbInitData().meta.Review}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        this.fileUploadView.render(this.$el);
        this.$el.append(this.fileUploadView.$el);

        return this;
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {ReviewFileUploadView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    events: {
        "click #btn-complete-review":"eventCompleteReview"
    },
    permissionTargets: {
        Review: 'meta'
    },
    /**
     * Event handler for complete review button. Checks if files are ready and
     * lets the event bubble up to the containing review view if they are.
     * @param {Object} e
     */
    eventCompleteReview: function(e) {
        if(!this.fileUploadView.areFilesReady()) {
            e.preventDefault();
            e.stopPropagation();
            bootbox.alert('Wait for file info to finish saving!');
        }
    }
});

// Add event handlers etc. for ReviewFileUploadView to function as a file upload target
ReviewFileUploadView = FileUpload.asFileUploadContainerView(ReviewFileUploadView);

/**
 * Wrapper for FileUpload.FileUploadView
 * @typedef {Object} ReviewCommentsListView
 */
var ReviewCommentsListView = BizzyBone.BaseView.extend({
    initialize: function(options) {

    },
    render: function() {
        return this;
    }
});

/**
 * Backbone view for files-under-review subview
 * @typedef {Object} ReviewSubmittalFilesView
 */
var ReviewSubmittalFilesView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} [options]
     * @returns {ReviewSubmittalFilesView}
     */
    initialize: function(options) {
        var thisView = this;
        this.defaultElement = _.has(options, 'el') ? false : true;

        // For each reviewable file (file from submittal) in the review data instantiate
        // a model then put them in a collection
        this.reviewableFiles = new FileUpload.FileList(_.map(this.model.get('reviewableFiles'), function(fileData) {
            return new FileUpload.FileModel(fileData, {
                urlPrefix: thisView.model.urlPrefix + '/reviews/' + thisView.model.get('idReview')
            });
        }));

        // Get an instance of the reusable FileUpload.FileListView with the url prefix for this review
        this.fileListView = new FileUpload.FileListView({
            model: this.model,
            collection: this.reviewableFiles,
            urlPrefix: thisView.model.urlPrefix + '/reviews/' + thisView.model.get('idReview')
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param {jQuery} element
     * @returns {ReviewListItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    /**
     * @returns {ReviewSubmittalFilesView}
     */
    render: function() {
        var template, oldEl;

        template = Handlebars.templates.reviewSubmittalContents;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({review: this.model.attributes, meta: hbInitData().meta.Review}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({review: this.model.attributes, meta: hbInitData().meta.Review}));
            oldEl.replaceWith(this.$el);
        }

        this.applyPermissions();

        // Render file list subview
        if(this.model.get('objectIsComplete')) {
            this.$el.find('#incomplete-submittal-warning').hide();
        }
        if(this.fileListView.fileViews.length != 0) {
            this.$el.find('#no-documents-under-review').hide();
            this.fileListView.render();
        }

        this.$el.append(this.fileListView.$el);

        return this;
    },
    permissionTargets: {
        Review: 'meta'
    }
});

/**
 * Backbone view for add/edit review modal
 * @typedef {Object} ModalAddEditReview
 */
var ModalAddEditReview = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {ModalAddEditReview}
     */
    initialize: function(options) {
        this.rendered = false;
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ModalAddEditReview}
     */
    render: function() {
        var template;
        template = Handlebars.templates.reviewModalAddEdit;

        this.$el.html(template({
            review: this.model.attributes,
            reviewTypes: this.reviewTypes,
            groupTable: this.groupTable,
            reviewerTable: this.reviewerTable,
            meta: hbInitData().meta.Review
        }));

        this.applyPermissions();

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {ReviewModel} reviewModel
     * @param {Array} reviewTypes
     * @param {Object} reviewerTable
     * @param {Object} groupTable
     * @param {ReviewList} reviewCollection
     * @returns {ModalAddEditReview}
     */
    show: function(reviewModel, reviewTypes, reviewerTable, groupTable, reviewCollection) {
        var curGroup, g;

        this.model = reviewModel;
        this.reviewTypes = reviewTypes;
        this.reviewerTable = reviewerTable;
        this.groupTable = groupTable;
        this.collection = reviewCollection;

        // Hack to auto-select first group if there's no selected group
        curGroup = this.model.get('idGroup');
        if(!curGroup) {
            for(g in groupTable) {
                curGroup = groupTable[g].idGroup;
                break;
            }

            this.model.set({idGroup: curGroup});
        }

        this.render();

        this.$el.children().first().modal('show');
        this.filterReviewerSelect(curGroup);

        return this;
    },
    /**
     * Just hide the modal
     * @returns {ModalEditGroupUser}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * Hide all reviewer options *except* the ones in group idGroup
     * @param {string} idGroup
     */
    filterReviewerSelect: function(idGroup) {
        var options = $('#review-addedit-idreviewer').find('option').hide();
        options.filter('.permanent, .group-' + idGroup).show();
    },
    events: {
        "change #review-addedit-idgroup": "eventChangeGroup",
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    permissionTargets: {
        Review: 'meta'
    },
    /**
     * Event handler for group selection change
     * @param {Object} e
     */
    eventChangeGroup: function(e) {
        this.filterReviewerSelect($('#review-addedit-idgroup').val());
    },
    /**
     * Event handler for 'Save' button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;

        toSave = {
            idReviewType: $('#review-addedit-type').val(),
            description: $('#review-addedit-description').val(),
            idGroup: parseInt($('#review-addedit-idgroup').val()),
            idUser: parseInt($('#review-addedit-idreviewer').val()),
            dateDue: Util.dateFormatStorage($('#review-addedit-datedue').val())
        };

        wasNew = thisView.model.isNew();

        thisView.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                if(wasNew) {
                    thisView.collection.add(model);
                }
                thisView.hide();
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for 'Cancel' button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});