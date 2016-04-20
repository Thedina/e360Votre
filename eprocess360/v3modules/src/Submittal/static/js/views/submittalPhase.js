/**
 * Submittal Phase: Views
 */

/**
 * Backbone view for a single submittal phase (all variants!)
 * @typedef {Object} SubmittalPhaseView
 */
var SubmittalPhaseView = BizzyBone.BaseView.extend({
    /**
     * Initialize. maxDepth is the depth of the topmost submittal phase in the
     * hierarchy being rendered, used to decide details of rendering.
     * @param [options]
     * @param [options.maxDepth=2]
     * @returns {SubmittalPhaseView}
     */
    initialize: function(options) {
        this.maxDepth = _.isNumber(options.maxDepth) ? options.maxDepth : 2;
        // If this.el is not set at initialization, we are rendering into the
        // default div
        this.defaultElement = _.has(options, 'el') ? false : true;

        // Instantiate a model for each child phase and put them in a collection
        this.childPhases = new SubmittalPhaseList(_.map(this.model.get('children'), function(childData) {
            return new SubmittalPhaseModel(childData);
        }));

        // Instantiate a SubmittalPhaseChildrenView responsible for the child phase list
        this.childrenView = new SubmittalPhaseChildrenView({
            collection: this.childPhases,
            maxDepth: this.maxDepth
        });

        // Instantiate a model for each child submittal and put them in a collection
        this.submittals = new SubmittalList(_.map(this.model.get('submittals'), function(submittalData) {
            return new SubmittalModel(submittalData);
        }));

        // Instantiate a SubmittalListView responisble for the submittal list
        this.submittalsView = new SubmittalListView({
            collection: this.submittals
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * setElement is modified to set this.defaultElement to false when first
     * called
     * @param element
     * @returns {SubmittalPhaseView}
     */
    setElement: function(element) {
        this.defaultElement = false;
       return Backbone.View.prototype.setElement.call(this, element);
    },
    /**
     * If preserveChildren is true, don't re-render child views
     * @param {boolean=false} preserveChildren
     * @returns {SubmittalPhaseView}
     */
    render: function(preserveChildren) {
        if(typeof preserveChildren === 'undefined') preserveChildren = false;

        var template, oldEl, oldChildContainer, newChildContainer, subsIncomplete, firstSubIncomplete;

        // SubmittalPhaseView handles all variants of the submittal phase interface.
        // Figure out which Handlebars template to use here from maxDepth and current depth
        if(this.maxDepth === 2) {
            switch (this.model.get('depth')) {
                case 2:
                    template = Handlebars.templates.subPhaseL2;
                    break;
                case 1:
                    template = Handlebars.templates.subPhaseL1;
                    break;
                case 0:
                    template = Handlebars.templates.subPhaseL0;
                    break;
                default:
                    break;
            }
        }
        else if(this.maxDepth === 1) {
            switch (this.model.get('depth')) {
                case 1:
                    template = Handlebars.templates.subPhaseL1Expanded;
                    break;
                case 0:
                    template = Handlebars.templates.subPhaseL0;
                    break;
                default:
                    break;
            }
        }
        else if(this.maxDepth === 0) {
            if(this.model.get('depth') === 0) {
                template = Handlebars.templates.subPhaseL0Expanded;
            }
        }

        //If we can't find *any* appropriate template, don't render
        if(!_.isFunction(template)) {
            return false;
        }

        // If preserving children, detach the child container before re-rendering self
        if(preserveChildren) {
            oldChildContainer = this.$el.find('.submittalphase-children-container').first().detach();
        }

        // If this is the first render, fill $el from the template. Otherwise replace it.
        if(this.$el.is(':empty')) {
            this.$el.html(template({phase: this.model.attributes, meta: hbInitData().meta.Submittal}));

            // If we rendered into the default div (i.e. this.el was never set) lose the outer
            // div and point whatever is the outermost container from the template
            if(this.defaultElement) {
                this.setElement(this.$el.children().first());
            }
        }
        else {
            oldEl = this.$el;
            this.setElement(template({phase: this.model.attributes, meta: hbInitData().meta.Submittal}));
            oldEl.replaceWith(this.$el);
        }

        // Haaaackck(!) to remove link to base /submittals from breadcrumbs
        if(this.maxDepth === 2 && this.model.get('depth') === 2) {
            $(".breadcrumb li[rel='parent']").remove();
        }
        else if(this.maxDepth === 1 && this.model.get('depth') === 1) {
            $(".breadcrumb li[rel='grandparent']").remove();
        }

        // Update panel coloring for complete/incomplete status
        if(this.model.get('status').isComplete) {
            this.$el.find('.submittalphase-level1').addBack('.submittalphase-level1').removeClass('panel-primary').addClass('panel-default');
            this.$el.find('.menu-button').removeClass('btn-primary').addClass('btn-default');
        }

        this.applyPermissions();

        // newChildContainer is an empty child container right after rendering
        newChildContainer = this.$el.find('.submittalphase-children-container').first();

        // if we were preserving children, replace that with the old, non-empty one
        if(preserveChildren) {
            newChildContainer.replaceWith(oldChildContainer);
            this.childrenView.setElement(oldChildContainer);
        }
        else {
            this.childrenView.setElement(newChildContainer);
        }

        // if we were not preserving children, render them!
        if(!preserveChildren && !_.isEmpty(this.model.get('children'))) {
            this.childrenView.render();
        }

        // once submittal list container div exists give it to this.submittalsView
        this.submittalsView.setElement(this.$el.find('.submittal-list'));

        // If this is the level 0 expanded view handle submittal list stuff
        if(this.maxDepth === 0 && !_.isEmpty(this.submittals)) {
            // find the first incomplete submittal so we can hook up the banner
            subsIncomplete = this.submittals.countIncomplete();
            firstSubIncomplete = this.submittals.getFirstIncomplete();

            // if there is an incomplete submittal, add the incomplete submittal banner and
            // give it access to the first incomplete submittal
            if(subsIncomplete > 0) {
                var banner = new SubmittalIncompleteBannerView({collection: this.submittals});
                banner.setModel(firstSubIncomplete).render();
                this.$el.find('.submittal-list').before(banner.$el);
            }

            // render the submittal list, obvs
            this.submittalsView.render();
        }

        return this;
    },
    events: {
        "click .phase-btn-new": "eventNewPhase",
        "click .phase-btn-edit": "eventEditPhase",
        "click .phase-btn-delete": "eventDeletePhase",
        "click .submittal-btn-new":"eventNewSubmittal",
        "click .phase-menu a": "eventMenuItem"
    },
    permissionTargets: {
        Submittal: 'meta'
    },
    /**
     * Create a new submittal phase as a child of this one
     * @param data
     * @param data.title
     * @param data.description
     */
    createPhase: function(data) {
        var thisView = this;

        var newPhase = new SubmittalPhaseModel({
            title: data.title,
            description: data.description,
            idParent: this.model.get('idSubmittalPhase')
        });

        newPhase.save(null, {
            wait: true,
            success: function(model, response, options) {
                var newPhaseView = thisView.childrenView.addChildPhase(newPhase).render();
                newPhaseView.$el.appendTo(thisView.childrenView.$el).hide().fadeIn(500);
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Update this submittal phase
     * @param data
     * @param data.title
     * @param data.description
     */
    updatePhase: function(data) {
        var thisView = this;

        this.model.set({
            title: data.title,
            description: data.description
        });

        // note wait: true - don't update the model until the request comes back successful
        this.model.save(null, {
            wait: true,
            success: function(model, response, options) {
                thisView.render(true);
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Delete this submittal phase. idPhase is not actually needed for this I
     * guess consider it a double-check?
     * @param {number} idPhase
     */
    deletePhase: function(idPhase) {
        var thisView = this;

        // see? double-check! (this could be omitted but whatever at this point)
        if(idPhase == thisView.model.get('idSubmittalPhase')) {
            // wait: true - you get the point
            thisView.model.destroy({
                wait: true,
                success: function(model, response, options) {
                    var redirectTo = false;

                    // if this is the top-level view on the page redirect to the page
                    // for its parent because we're helpful like that!
                    if(model.get('depth') === thisView.maxDepth) {
                        redirectTo = model.get('idParent');
                    }

                    thisView.$el.fadeOut(500, function() {
                        thisView.remove();
                    });

                    if(redirectTo) {
                        window.location = hbInitData().meta.Submittal.path + '/' + redirectTo;
                    }
                },
                error: function(model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });
        }
    },
    /**
     * Create a new submittal as a child of this phase. Note that submittals
     * don't *actually* have a title and description on the backend yet.
     * Someday, I'm told...
     * @param data
     * @param data.title
     * @param data.description
     */
    createSubmittal: function(data) {
        var thisView = this;

        var newSubmittal = new SubmittalModel({
            title: data.title,
            description: data.description,
            idSubmittalPhase: this.model.get('idSubmittalPhase')
        });

        newSubmittal.save(null, {
            wait: true,
            success: function(model, response, options) {
                var newSubmittalView = thisView.submittalsView.addSubmittal(newSubmittal).render();
                newSubmittalView.$el.prependTo(thisView.submittalsView.$el).hide().slideDown(500, function() {
                    thisView.submittalsView.collection.trigger('refresh', newSubmittalView, false, thisView.submittalsView.collection);
                });
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
    },
    /**
     * Event handler for new phase button or menu item
     * @param {Object} e
     */
    eventNewPhase: function(e) {
        var thisView = this;

        phaseEditModal.show(null,
            "New Submittal Phase",
            function(data) {
                thisView.createPhase(data);
            }
        );
    },
    /**
     * Event handler for edit phase button or menu item
     * @param {Object} e
     */
    eventEditPhase: function(e) {
        var thisView = this;

        phaseEditModal.show(
            {
                title: thisView.model.get('title'),
                description: thisView.model.get('description')
            },
            "Edit Submittal Phase",
            function(data) {
                thisView.updatePhase(data);
            }
        );
    },
    /**
     * Event handler for delete phase button or menu item
     * @param {Object} e
     */
    eventDeletePhase: function(e) {
        var thisView = this;
        bootbox.confirm("Are you sure you want to delete this submittal phase?", function (result) {
            thisView.deletePhase(parseInt($(e.target).data('idphase')));
        });
    },
    /**
     * Event handler for new submittal button
     * @param {Object} e
     */
    eventNewSubmittal: function(e){
        var thisView = this;

        phaseEditModal.show(null,
            "New Submittal",
            function(data) {
                thisView.createSubmittal(data);
            }
        );
    },
    /**
     * Shared event dispatch handler for phase menu items
     * @param {Object} e
     */
    eventMenuItem: function(e) {
        e.preventDefault();
        e.stopPropagation();
        var target = $(e.target);

        target.closest('.dropdown').removeClass('open');

        // menu items are distinguished in data-action attributes
        switch(target.data('action')) {
            case 'new':
                this.eventNewPhase(e);
                break;
            case 'edit':
                this.eventEditPhase(e);
                break;
            case 'delete':
                this.eventDeletePhase(e);
                break;
        }
    }
});

/**
 * Backbone view for a collection of submittal phases
 * @typedef {Object} SubmittalPhaseChildrenView
 */
var SubmittalPhaseChildrenView = BizzyBone.BaseView.extend({
    /**
     * See SubmittalPhaseView.initialize() about maxDepth
     * @param [options]
     * @param [options.maxDepth=2]
     * @returns {SubmittalPhaseChildrenView}
     */
    initialize: function(options) {
        var thisView = this;
        this.maxDepth = _.isNumber(options.maxDepth) ? options.maxDepth : 2;
        this.childViews = [];

        // For each SubmittalPhaseModel in the collection, instantiate a view
        _.each(this.collection.models, function(phaseModel) {
            thisView.childViews.push(new SubmittalPhaseView({model: phaseModel}));
        });

        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * Render each child view into the container
     * @returns {SubmittalPhaseChildrenView}
     */
    render: function() {
        var childrenContainer = this.$el;

        _.each(this.childViews, function(childView) {
            childView.render();
            childView.$el.appendTo(childrenContainer);
        });

        return this;
    },
    /**
     * Helper to add a model to the collection *and* make a view for it
     * @param {SubmittalPhaseModel} childPhase
     * @returns {SubmittalPhaseView}
     */
    addChildPhase: function(childPhase) {
        var childView;
        this.collection.add(childPhase);
        childView = new SubmittalPhaseView({model: childPhase});
        this.childViews.push(childView);

        return childView;
    }
});

/**
 * Flexible backbone view for modal dialog
 * @typedef {Object} ModalBaseView
 */
var ModalBaseView = BizzyBone.BaseView.extend({
    /**
     * ModalBaseView takes a Handlebars template and an object with data field
     * names as keys and jQuery selectors as values so as to be able to extract
     * data from that template whatever inputs it may contain.
     * @param {Object} options
     * @param {function} options.template
     * @param {Object} options.fields
     * @returns {ModalBaseView}
     */
    initialize: function(options) {
        if(_.has(options, 'template')) {
            this.template = options.template;
        }

        if(_.has(options, 'fields')) {
            this.fields = options.fields;
        }

        // ModalBaseView always renders into the #modal-node div
        this.setElement($('#modal-node'));
        this.rendered = false;
        return Backbone.View.prototype.initialize.call(this, options);
    },
    /**
     * A modal only needs to be rendered once - after that it can just be shown
     * and hidden.
     * @returns {ModalBaseView}
     */
    render: function() {
        this.$el.html(this.template({meta: hbInitData().meta.Submittal}));
        this.applyPermissions();
        this.rendered = true;

        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    permissionTargets: {
        Submittal: 'meta'
    },
    template: null,
    fields: {},
    /**
     * Show the modal. data takes jQuery selectors as keys and data as values,
     * filling in the selected fields with the corresponding values. headerText
     * fills in the headerText. saveCallback and cancelCallback are what they
     * sound like.
     * @param {Object} data
     * @param {string} headerText
     * @param {function} saveCallback
     * @param {function} cancelCallback
     */
    show: function(data, headerText, saveCallback, cancelCallback) {
        var thisView = this;

        this.saveCallback = saveCallback;
        this.cancelCallback = cancelCallback;

        if(!_.isObject(data)) {
            data = _.mapObject(thisView.fields, function(value, key) {
                return "";
            });
        }

        if(!this.rendered) {
            this.render();
        }

        this.$el.children().first().modal('show');

        _.each(data, function(value, key, list) {
            if(_.has(thisView.fields, key)) {
                $(thisView.fields[key]).val(value);
            }
        });

        this.$el.find('.modal-header').text(headerText);
    },
    /**
     * Hide the modal.
     */
    hide: function() {
        this.$el.children().first().modal('hide');
    },
    /**
     * Event handler for the 'Save' button. Calls the most recently specified
     * save callback, passing it data extracted from the modal as specified by
     * this.fields
     */
    eventSave: function() {
        var data = {};

        if(_.isFunction(this.saveCallback)) {
            _.each(this.fields, function(value, key, list) {
                data[key] = $(value).val();
            });
            this.saveCallback(data);
        }
        this.hide();
        this.saveCallback = null;
        this.cancelCallback = null;
    },
    /**
     * Event handler for the 'Cancel' button. Just calls the cancel callback,
     * closes the modal, and clears the callbacks.
     */
    eventCancel: function() {
        if(_.isFunction(this.cancelCallback)) {
            this.cancelCallback();
        }
        this.hide();
        this.saveCallback = null;
        this.cancelCallback = null;
    }
});