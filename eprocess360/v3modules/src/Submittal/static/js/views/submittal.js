/**
 * Submittal: Views
 */

/**
 * Backbone view for a single submittal
 * @typedef {Object} SubmittalView
 */
var SubmittalView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} [options]
     * @returns {SubmittalView}
     */
    initialize: function(options) {
        var thisView = this;
        this.defaultElement = _.has(options, 'el') ? false : true;

        this.urlPrefix = hbInitData().meta.Submittal.apiPath + '/submittals/' + this.model.get('idSubmittal');

        // For each file in the submittal data instantiate a model then put them in a collection
        this.files = new FileUpload.FileList(_.map(this.model.get('files'), function(fileData) {
            return new FileUpload.FileModel(fileData, {urlPrefix: thisView.urlPrefix});
        }));

        // Listen to bannercomplete custom event on this.model
        this.listenTo(this.model, 'bannercomplete', this.eventBannerComplete);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * If preserveChildren is true, don't re-render sub-views
     * @param {boolean=false} preserveContents
     * @param {boolean=false} forceExpand
     * @returns {SubmittalView}
     */
    render: function (preserveContents, forceExpand) {
        if(typeof preserveContents === 'undefined') preserveContents = false;
        if(typeof forceExpand === 'undefined') forceExpand = false;

        var template, oldEl, oldContents, newContents;

        template = Handlebars.templates.submittalSingle;

        // If preserving contents, detach the contents container before re-rendering self
        if(preserveContents) {
            oldContents = this.$el.find('.submittal-contents').detach();
        }

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({submittal: this.model.attributes, meta: hbInitData().meta.Submittal}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({submittal: this.model.attributes, meta: hbInitData().meta.Submittal}));
            oldEl.replaceWith(this.$el);
        }

        // Update panel coloring and cosmetic details for complete/incomplete status
        if(!forceExpand && this.model.get('status').isComplete) {
            this.$el.find('.submittal-single').addBack('.submittal-single').removeClass('panel-primary').addClass('panel-default');
            this.$el.find('.menu-button').removeClass('btn-primary').addClass('btn-default');
        }

        this.applyPermissions();

        // newChildContainer is an empty content container right after rendering
        newContents = this.$el.find('.submittal-contents');

        // If preserving contents, replace with the old contents container, else re-render
        if(preserveContents) {
            newContents.replaceWith(oldContents);
        }
        else {
            if(this.model.get('status').isComplete) {
                // Render subview for file list and reviews
                //this.contentView = new SubmittalCompleteSubview({el: this.$el.find('.submittal-contents'), model: this.model});
                this.contentView = new SubmittalCompleteSubview({
                    el: this.$el.find('.submittal-contents'),
                    model: this.model,
                    files: this.files,
                    urlPrefix: this.urlPrefix
                });

                if(this.contentView.fileListView.fileViews.length != 0) {
                    this.$el.find('#no-documents-for-submittal').hide();

                }
                this.$el.append(this.contentView.render().$el);
                this.contentView.render();
            }
            else {
                // Render subview for file uploads
                //this.contentView = new SubmittalFileUploadView({el: this.$el.find('.submittal-contents'), model: this.model});
                this.contentView = new SubmittalFileUploadView({
                    el: this.$el.find('.submittal-contents'),
                    model: this.model,
                    files: this.files,
                    urlPrefix: this.urlPrefix
                });
                this.$el.find('#no-documents-for-submittal').hide();
                this.contentView.render();
            }
        }

        return this;
    },
    events: {
        "click .submittal-menu a": "eventMenuItem",
        "click .btn-complete-submittal": "eventButtonClose",
        "click .panel-heading": "eventToggleExpanded"
    },
    permissionTargets: {
        Submittal: 'meta'
    },
    /**
     * Update this submittal
     * @param {Object} data
     * @param {boolean=false} preserveContents
     * @param {boolean=false} forceExpand
     */
    updateSubmittal: function(data, preserveContents, forceExpand) {
        var thisView = this;
        if(typeof preserveContents === 'undefined') preserveContents = false;
        if(typeof forceExpand === 'undefined') forceExpand = false;

        this.model.set(data);

        // note wait: true - don't update the model until the request comes back successful
        this.model.save(null, {
            wait: true,
            success: function(model, response, options) {
                if(response.redirect) {
                    window.location = response.redirect;
                }
                else {
                    thisView.render(preserveContents, forceExpand);
                }
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Delete this submittal phase. idPhase is not actually needed for this
     * blah blah see what I said for SubmittalPhaseView.
     * @param {number} idSubmittal
     */
    deleteSubmittal: function(idSubmittal) {
        var thisView, collection;
        thisView = this;

        if(idSubmittal == thisView.model.get('idSubmittal')) {
            collection = thisView.model.collection;

            thisView.model.destroy({
                wait: true,
                success: function(model, response, options) {
                    thisView.$el.slideUp(500, function() {
                        thisView.remove();
                        collection.trigger('refresh', thisView, true, collection);
                    });
                },
                error: function(model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });
        }
    },
    /**
     * Event handler for closing/completing submittal via incomplete submittal
     * banner
     * @param {SubmittalModel} model
     */
    eventBannerComplete: function(model) {
        var newStatus = _.clone(this.model.get('status'));
        newStatus.isComplete = true;
        this.updateSubmittal({
            status: newStatus
        }, false, true);
    },
    /**
     * Event handler for edit submittal menu item
     * @param {Object} e
     */
    eventEdit: function(e) {
        var thisView = this;

        phaseEditModal.show(
            {
                title: thisView.model.get('title'),
                description: thisView.model.get('description')
            },
            "Edit Submittal",
            function(data) {
                thisView.updateSubmittal(data, true);
            }
        );
    },
    /**
     * Event handler for close submittal menu item
     * @param {Object} e
     */
    eventClose: function(e) {
        var newStatus = _.clone(this.model.get('status'));
        var thisView = this;

        bootbox.confirm("Are you sure you want to close this submittal?", function (result) {
            if(result) {
                newStatus.isComplete = true;
                thisView.updateSubmittal({
                    status: newStatus
                }, false, true);
            }
        });
    },
    /**
     * Event hander for open submittal menu item
     * @param {Object} e
     */
    eventOpen: function(e) {
        var newStatus = _.clone(this.model.get('status'));
        var thisView = this;

        bootbox.confirm("Are you sure you want to open this submittal?", function (result) {
            if (result) {
                    newStatus.isComplete = false;
                    thisView.updateSubmittal({
                        status: newStatus
                    }, false, true);
            }
        });
    },
    /**
     * Event handler for delete submittal menu item
     * @param {Object} e
     */
    eventDelete: function(e){
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this submittal?", function (result) {
            if(result) {
                thisView.deleteSubmittal(parseInt($(e.target).data('idsubmittal')));
            }
        });


    },
    /**
     * Event handler for reviews menu item
     * @param {Object} e
     */
    eventReviews: function(e) {
        window.location = hbInitData().meta.Submittal.path + '/submittals/' + this.model.get('idSubmittal') + '/reviews';
    },
    /**
     * Shared event dispatch handler for submittal menu items.
     * @param {Object} e
     */
    eventMenuItem: function(e) {
        var target, action;
        target = $(e.target);
        action = target.data('action');

        target.closest('.dropdown').removeClass('open');

        if(action) {
            e.preventDefault();
            e.stopPropagation();
            // menu items are distinguished in data-action attributes
            switch (action) {
                case 'edit':
                    this.eventEdit(e);
                    break;
                case 'close':
                    this.eventClose(e);
                    break;
                case 'open':
                    this.eventOpen(e);
                    break;
                case 'delete':
                    this.eventDelete(e);
                    break;
            }
        }
    },
    /**
     * Event handler for complete/close submittal via subview "Complete & Save" button
     * @param {Object} e
     */
    eventButtonClose: function(e) {
        e.preventDefault();
        this.eventClose(e);
    },
    /**
     * Event handler for toggle expanded (click on header)
     * @param {Object} e
     */
    eventToggleExpanded: function(e) {
        if($(e.target).hasClass('panel-heading')) {
            this.$el.find('.submittal-contents').slideToggle(500, function () {
                if (!$(e.target).hasClass('panel-heading-border-radius')) {
                    $(e.target).addClass('panel-heading-border-radius');
                }
                else {
                    $(e.target).removeClass('panel-heading-border-radius');
                }
            });


        }
    }
});

/**
 * Backbone view for a collection of submittals
 * @typedef {Object} SubmittalListView
 */
var SubmittalListView = BizzyBone.BaseView.extend({
    initialize: function(options) {
        var thisView = this;
        this.submittalViews = [];

        // For each SubmittalModel in the collection, instantiate a view
        _.each(this.collection.models, function(submittalModel) {
            thisView.submittalViews.push(new SubmittalView({model: submittalModel}));
        });

        // Listen to (custom) refresh event on collection
        this.listenTo(this.collection, 'refresh', this.eventRefresh);

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * Render each child view into the container
     * @returns {SubmittalListView}
     */
    render: function () {
        var childrenContainer, firstSubView;

        childrenContainer = this.$el;
        firstSubView = true;

        _.each(this.submittalViews, function(childView) {
            childView.render(false, firstSubView);
            childView.$el.appendTo(childrenContainer);
            firstSubView = false;
        });

        return this;
    },
    /**
     * Helper to add a model to the collection *and* make a view for it
     * @param {SubmittalModel} submittal
     * @returns {SubmittalView}
     */
    addSubmittal: function(submittal) {
        var submittalView;
        this.collection.add(submittal);
        submittalView = new SubmittalView({model: submittal});
        this.submittalViews.unshift(submittalView);
        return submittalView;
    },
    /**
     * Event handler for custom refresh event (used to force a re-render of
     * submittals list after a submittal view is added or removed so as to
     * maintain appropriate display status for all submittal views.
     * @param {SubmittalView} view
     * @param {boolean} wasRemoved
     * @param {SubmittalList} collection
     */
    eventRefresh: function(view, wasRemoved, collection) {
        if(wasRemoved) {
            this.submittalViews.splice(_.indexOf(this.submittalViews, view), 1);
        }
        this.render();
    }
});

/**
 * Backbone view for the incomplete submittal banner. When instantiated by a
 * SubmittalPhaseView it is given a pointer to a SubmittalModel for an
 * incomplete submittal, giving it the ability to trigger an event cascade to
 * close it.
 * @typedef {SubmittalIncompleteBannerView}
 */
var SubmittalIncompleteBannerView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {SubmittalIncompleteBannerView}
     */
    initialize: function(options) {
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SubmittalIncompleteBannerView}
     */
    render: function() {
        var template;

        template = Handlebars.templates.incompleteBanner;
        this.$el.html(template({meta: hbInitData().meta.Submittal}));

        this.applyPermissions();

        return this;
    },
    /**
     * Set the target model of this 'Complete' button
     * @param submittalModel
     * @returns {SubmittalIncompleteBannerView}
     */
    setModel: function(submittalModel) {
        this.model = submittalModel;

        // Listen to model 'change:status' event
        this.listenTo(this.model, 'change:status', this.eventStatusUpdated);

        return this;
    },
    events: {
        "click .complete-submittal": "eventBannerButton"
    },
    permissionTargets: {
        Submittal: 'meta'
    },
    /**
     * Event handler for "Complete & Save" button. Works by triggering a
     * bannercomplete custom event on this.model. SubmittalView listens to this
     * and handles the rest.
     * @param {Object} e
     */
    eventBannerButton: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to close submittal #" + this.model.get('sequenceNumber') + "?", function (result) {
            if(result) {
                thisView.model.trigger('bannercomplete', thisView.model);
            }
        });
    },
    /**
     * Event handler for submittal change:status event
     * @param {SubmittalModel} model
     */
    eventStatusUpdated: function(model) {
        var thisView, numOpen;
        thisView = this;
        numOpen = this.collection.countIncomplete();

        if(model.get('status').isComplete) {
            // If we're closing the last submittal, remove the banner. Else target the next open
            // submittal
            if(!numOpen) {
                thisView.$el.fadeOut(500, function() {
                    thisView.remove();
                });
            }
            else {
                thisView.setModel(thisView.collection.getFirstIncomplete());
            }

        }
    }
});

/**
 * Backbone view for submittal-specific file upload sub-view.
 * @typedef {Object} SubmittalFileUploadView
 */
var SubmittalFileUploadView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} [options]
     * @param {Object} [options.files]
     * @returns {SubmittalFileUploadView}
     */
    initialize: function(options) {
        var thisView;
        thisView = this;

        if(_.has(options, 'files')) {
            this.files = options.files;
        }

        if(_.has(options, 'urlPrefix')) {
            this.urlPrefix = options.urlPrefix;
        }

        // Get an instance of the reusable FileUploadView with the url prefix for this submittal
        this.fileUploadView = new FileUpload.FileUploadView({
            model: this.model,
            collection: this.files,
            urlPrefix: this.urlPrefix
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * Render this submittal-specific view plus the generic file upload subview
     * @returns {SubmittalFileUploadView}
     */
    render: function() {
        var template, oldEl;
        template = Handlebars.templates.submittalContentIncomplete;

        this.$el.html(template({submittal: this.model.attributes, meta: hbInitData().meta.Submittal}));

        this.applyPermissions();

        this.fileUploadView.render(this.$el);
        this.$el.append(this.fileUploadView.$el);

        return this;
    },
    events: {
        "click .btn-complete-submittal": "eventCompleteSubmittal"
    },
    permissionTargets: {
        Submittal: 'meta'
    },
    /**
     * Event handler for "Complete & Submit" button. Here actually just checks
     * fileUploadView.areFilesReady(). If files *are* ready to proceed, let the
     * event bubble up to the submittal view to handle it.
     * @param {Object} e
     */
    eventCompleteSubmittal: function(e) {
        if(!this.fileUploadView.areFilesReady()) {
            e.preventDefault();
            e.stopPropagation();
            bootbox.alert('Wait for file info to finish saving!');
        }
    }
});

// Add event handlers etc. for SubmittalFileUploadView to function as a file upload target
SubmittalFileUploadView = FileUpload.asFileUploadContainerView(SubmittalFileUploadView);

/**
 *
 * @typedef {Object} SubmittalCompleteSubview
 */
var SubmittalCompleteSubview = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {SubmittalCompleteSubview}
     */
    initialize: function(options) {
        var thisView;
        thisView = this;

        if(_.has(options, 'files')) {
            this.files = options.files;
        }

        //  For each review in the submittal data instantiate a model then put them in a collection
        this.reviews = new ReviewList(_.map(this.model.get('reviews'), function(reviewData) {
            return new ReviewModel(reviewData, {urlPrefix: thisView.urlPrefix});
        }));

        // Get an instance of the reusable FileUpload.FileListView with the url prefix for this submittal
        this.fileListView = new FileUpload.FileListView({
            model: this.model,
            collection: this.files,
            urlPrefix: this.urlPrefix
        });

        // Get an instance of the review summary subview
        this.reviewsSubview = new SubmittalReviewsSubview({
            model: this.model,
            collection: this.reviews,
            urlPrefix: this.urlPrefix
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SubmittalCompleteSubview}
     */
    render: function() {
        // Render file list subview
        this.fileListView.render();

        if (this.fileListView.fileViews.length == 0) {
            this.fileListView.$el.find('.file-upload-table').hide();
        }

        this.$el.append(this.fileListView.$el);

        if(!_.isEmpty(this.reviews.models)) {
            this.$el.append(this.reviewsSubview.render().$el);
        }
        return this;
    }
});

/**
 * Backbone view for closed submittal reviews summary
 * @typedef {Object} SubmittalReviewsSubview
 */
var SubmittalReviewsSubview = BizzyBone.BaseView.extend({
    /**
     *
     * @param {Object} options
     * @param {string} options.urlPrefix
     * @returns {SubmittalReviewsSubview}
     */
    initialize: function(options) {
        var thisView = this;

        if(_.has(options, 'urlPrefix')) {
            this.urlPrefix = options.urlPrefix;
        }

        this.itemViews = [];

        // For each review in the collection instantiate a SubmittalReviewItemView
        _.each(this.collection.models, function(reviewModel) {
            thisView.itemViews.push(new SubmittalReviewItemView({model: reviewModel, parent: thisView}));
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SubmittalReviewsSubview}
     */
    render: function() {
        var template, reviewList;
        template = Handlebars.templates.submittalReviewsMain;

        this.$el.html(template({submittal: this.model.attributes, meta: hbInitData().meta.Submittal}));

        this.applyPermissions();

        reviewList = this.$el.find('.review-list');

        _.each(this.itemViews, function(itemView) {
            reviewList.append(itemView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Submittal: 'meta'
    }
});

/**
 * Backbone view for closed submittal review summary item
 * @type {Object} SubmittalReviewItemView
 */
var SubmittalReviewItemView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @param {SubmittalReviewsSubview} options.parent
     * @returns {SubmittalReviewItemView}
     */
    initialize: function(options) {
        this.defaultElement = _.has(options, 'el') ? false : true;

        if(_.has(options, 'parent')) {
            this.parent = options.parent;
            this.reviewerTable = Util.tableize(this.parent.model.get('reviewers'), 'idUser');
        }

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * @returns {SubmittalReviewItemView}
     */
    render: function() {
        var template, oldEl;
        template = Handlebars.templates.submittalReviewItem;

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({
                review: this.model.attributes,
                submittal: this.parent.model.attributes,
                reviewerTable: this.reviewerTable,
                meta: hbInitData().meta.Submittal
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
                submittal: this.parent.model.attributes,
                reviewerTable: this.reviewerTable,
                meta: hbInitData().meta.Submittal
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
     * @returns {SubmittalReviewItemView}
     */
    setElement: function(element) {
        this.defaultElement = false;
        return Backbone.View.prototype.setElement.call(this, element);
    },
    permissionTargets: {
        Submittal: 'meta'
    }
});