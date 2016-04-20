/**
 * Fee Settings: Views
 */

/**
 * Backbone view for fee settings main container
 * @typedef {Object} FeeSettingsMainView
 */
var FeeSettingsMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeSettingsMainView}
     */
    initialize: function(options) {
        this.feeTagList = new FeeTagList();
        this.feeTagList.reset(this.model.get('feeTags'));
        this.tagsView = new FeeSettingsTagsView({collection: this.feeTagList});

        this.matrixList = new FeeMatrixList();
        this.matrixList.reset(this.model.get('feeMatrices'));
        this.matrixView = new FeeMatrixView({collection: this.matrixList});

        // Listen to custom revalidate event on matrix collection
        this.listenTo(this.matrixList, 'revalidate', this.eventRevalidateMatrix);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeSettingsMainView}
     */
    render: function() {
        var template, formTemplate;
        template = Handlebars.templates.feeSettingsMain;

        this.$el.html(template({
            feeTemplate: this.model.attributes,
            meta: hbInitData().meta.Fee
        }));

        this.tagsView.setElement(this.$el.find('#fee-tag-settings'));
        this.tagsView.render();

        this.matrixView.setElement(this.$el.find('#fee-matrix'));
        this.matrixView.render();

        this.eventToggleMatrix();
        this.updateMatrixWarning();

        return this;
    },
    /**
     * Check if the matrix is valid and show/hide the warning banner
     * @returns {boolean}
     */
    updateMatrixWarning: function() {
        var isValid = this.matrixList.validateMatrix();

        if(isValid) {
            this.$el.find('#matrix-warning').hide();
        }
        else {
            this.$el.find('#matrix-warning').show();
        }

        return isValid;
    },
    events: {
        "click .btn-save-template": "eventSaveTemplate",
        "change #fee-template-edit-ismatrix": "eventToggleMatrix",
        "change #matrix-test-input": "eventMatrixTest",
        "click #btn-clear-matrix": "eventClearMatrix"
    },
    /**
     * Event handler for toggle matrix calculation type
     * @param {Object} e
     */
    eventToggleMatrix: function(e) {
        var enabled = $('#fee-template-edit-ismatrix').is(':checked');

        if(enabled) {
            $('.matrix-edit').show();
        }
        else {
            $('.matrix-edit').hide();
        }
    },
    /**
     * Event handler for changing value in matrix test input
     * @param {Object} e
     */
    eventMatrixTest: function(e) {
        var input, output;
        input = accounting.unformat($('#matrix-test-input').val());
        output = 0;

        if(this.updateMatrixWarning()) {
            output = this.matrixList.calcResult(input).toFixed(2);
        }

        $('#matrix-test-output').val(feesFormatMoney(output, true));
    },
    /**
     * Event handler for "Clear Matrix" button
     * @param {Object} e
     */
    eventClearMatrix: function(e) {
        this.matrixList.clearMatrix();
    },
    /**
     * Event handler for 'Save' buttons
     * @param {Object} e
     */
    eventSaveTemplate: function(e) {
        modalSaveTemplate.show(this.model, this.feeTagList, this.matrixList);
    },
    /**
     * Event handler for matrix collection custom 'revalidate' event
     * @param {FeeMatrixList} collection
     */
    eventRevalidateMatrix: function(collection) {
        this.updateMatrixWarning();
    }
});

/**
 * Backbone view for fee settings tags section
 * @typedef {Object} FeeSettingsTagsView
 */
var FeeSettingsTagsView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeSettingsTagsView}
     */
    initialize: function(options) {
        var thisView = this;
        this.tagTable = {};
        this.categoryViews = [];
        this.categoryList = new FeeTagCategoryList();
        this.categoryList.reset(_.toArray(hbInitData().meta.Fee.feeTagCategories));

        _.each(this.categoryList.models, function(categoryModel) {
            if(!categoryModel.get('status').isFeeSchedule) {
                thisView.categoryViews.push(new FeeSettingsTagsCategoryView({
                    model: categoryModel,
                    collection: thisView.collection
                }));
            }
        });

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeSettingsTagsView}
     */
    render: function() {
        var tagsList = this.$el.find('#fee-tags-list');

        _.each(this.categoryViews, function(categoryView) {
            tagsList.append(categoryView.render().$el);
        });

        this.initTagSearch();

        this.$el.find('#tag-warning').hide();

        return this;
    },
    events: {
        "click #btn-add-tag": "eventAddTag",
        "typeahead:select": "eventTypeaheadSelect",
        "typeahead:autocomplete": "eventTypeaheadSelect"
    },
    /**
     * Set up tag search typeahead/autocomplete
     * @returns {FeeSettingsTagsView}
     */
    initTagSearch: function() {
        var thisView, tagSearch;
        thisView = this;

        tagSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                wildcard: '%QUERY',
                url: hbInitData().meta.Fee.apiPath + '/tags/find?value=%QUERY'
            }
        });

        this.$el.find('#fee-tag-search').typeahead(
            null,
            {
                name: 'tags-api',
                /**
                 * Custom source function pulls user ID out of data from
                 * user search and separates from names.
                 * @param {Array} query
                 * @param {function} sync
                 * @param {function} async
                 * @returns {Number|*}
                 */
                source: function (query, sync, async) {
                    var tagNames = [];
                    return tagSearch.search(
                        query,
                        function (data) {
                            //sync
                            sync(data[1]);
                        },
                        function (data) {
                            //async
                            // data[1] is still a fat hack, because for some reason we see the
                            // incoming object with errors, data, meta as an array without names :(
                            _.each(data[1], function (d) {
                                thisView.tagTable[d.feeTagValue] = d;
                                tagNames.push(d.feeTagValue);
                            });
                            async(tagNames);
                        }
                    );
                }
            }
        );

        return this;
    },
    /**
     * Remove currently deselected tags from the tags list
     */
    cleanupTags: function() {
        var thisView = this;

        $('#fee-tags-list').find('input[type="checkbox"]').each(function(index) {
            var idFeeTag, categoryList;
            idFeeTag = parseInt($(this).data('idfeetag'));
            categoryList = $(this).closest('.category-tags-list');

            if(!$(this).is(':checked')) {
                $(this).closest('.button-checkbox').remove();
                thisView.collection.remove(idFeeTag);

                if(categoryList.is(':empty')) {
                    categoryList.closest('.h4').hide();
                }
            }
        });
    },
    /**
     * Event handler for click 'Add Tag' button
     * @param {Object} e
     */
    eventAddTag: function(e) {
        var newTag = new FeeTagModel(this.curTag);
        e.preventDefault();
        this.collection.add(newTag);
    },
    /**
     * Event handler for tag typeahead select or autocomplete item
     * @param {Object} e
     * @param {string} name
     */
    eventTypeaheadSelect: function(e, name) {
        var tagName = $('#fee-tag-search').val();
        this.curTag = this.tagTable[tagName];
    }
});

/**
 * Backbone view for fee settings tags category
 * @typedef {Object} FeeSettingsTagsCategoryView
 */
var FeeSettingsTagsCategoryView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeSettingsTagsCategoryView}
     */
    initialize: function(options) {
        // Listen to (full) tag collection add event
        this.listenTo(this.collection, 'add', this.eventTagAdded);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeSettingsTagsCategoryView}
     */
    render: function() {
        var template = Handlebars.templates.feeSettingsTagCategory;

        this.renderTemplate({
            tagCategory: this.model.attributes,
            meta: hbInitData().meta.Fee
        }, template);

        if(!this.renderTags()) {
            // Hide the category if it doesn't have any tags
            this.$el.hide();
        }
        else {
            this.$el.show();
        }

        return this;
    },
    /**
     * Factor out tag item rendering. Returns false if no tags in this category.
     * @returns {boolean}
     */
    renderTags: function() {
        var thisView, tagsList, tagsInCategory;
        thisView = this;
        tagsList = this.$el.find('.category-tags-list');
        tagsInCategory = this.collection.getByCategory(this.model.get('idFeeTagCategory'));

        if(!tagsInCategory.length) return false;

        _.each(tagsInCategory, function(tagModel) {
            var newTag = thisView.renderOneTag(tagModel, tagsList);
            tagsList.append(newTag);
            initCheckButton.call(newTag.get()[0]);
        });

        return true;
    },
    /**
     * Render an instance of the individual tag template
     * @param {FeeTagModel} tagModel
     * @returns {jQuery}
     */
    renderOneTag: function(tagModel) {
        var tagTemplate = Handlebars.templates.feeSettingsTagItem;

        return $(tagTemplate({
            feeTag: tagModel.attributes,
            meta: hbInitData().meta.Fee
        }));
    },
    /**
     * Event handler for tag collection model added
     * @param {FeeTagModel} model
     */
    eventTagAdded: function(model) {
        // Re-render view iff it corresponds to the category that contains the tag
        if(model.get('idFeeTagCategory') == this.model.get('idFeeTagCategory')) {
            this.render();
        }
    }
});

/**
 * Backbone view for fee settings matrix section
 * @typedef {Object} FeeMatrixView
 */
var FeeMatrixView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeMatrixView}
     */
    initialize: function(options) {
        var thisView = this;
        this.rowViews = [];

        if(!this.collection.length) {
            this.addFirstRow();
        }

        _.each(this.collection.models, function(matrixModel) {
            thisView.rowViews.push(new FeeMatrixRowView({model: matrixModel}));
        });

        // Listen to collection add, remove and matrixclear events
        this.listenTo(this.collection, 'add', this.eventRowAdded);
        this.listenTo(this.collection, 'remove', this.eventRowRemoved);
        this.listenTo(this.collection, 'matrixclear', this.eventMatrixCleared);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeMatrixView}
     */
    render: function() {
        var thisView = this;

        _.each(this.rowViews, function(rowView) {
            thisView.$el.append(rowView.render().$el);
        });

        return this;
    },
    /**
     * Call extractValues() for each row (usually before re-rendering or otherwise messing with the views)
     * @returns {FeeMatrixView}
     */
    rowsExtractValues: function() {
        _.each(this.rowViews, function(rowView) {
            rowView.extractValues();
        });

        return this;
    },
    /**
     * Helper for the task of adding a row if there aren't any
     * @returns {FeeMatrixView}
     */
    addFirstRow: function() {
        var newRow = new FeeMatrixRowModel();
        this.collection.add(newRow);
        return this;
    },
    /**
     * Event handler for collection model added
     * @param {FeeMatrixRowModel} model
     * @param {FeeMatrixList} collection
     */
    eventRowAdded: function(model, collection) {
        var newView = new FeeMatrixRowView({model: model});
        this.rowsExtractValues();
        this.rowViews.splice(model.get('order'), 0, newView);
        this.render();
    },
    /**
     * Event handler for collection model removed
     * @param {FeeMatrixRowModel} model
     * @param {FeeMatrixList} collection
     */
    eventRowRemoved: function(model, collection) {
        var thisView, removeIndex;
        thisView = this;
        removeIndex = model.get('order');

        this.rowViews[removeIndex].$el.fadeOut(500, function() {
            thisView.rowViews[removeIndex].remove();
            thisView.rowViews.splice(removeIndex, 1);

            if(!collection.length) {
                thisView.addFirstRow();
            }
        });
    },
    /**
     * Event handler for collection 'matrixclear' custom event
     * @param {FeeMatrixList} collection
     */
    eventMatrixCleared: function(collection) {
        _.each(this.rowViews, function(rowView) {
            rowView.remove();
        });

        this.rowViews = [];

        if(!collection.length) {
            this.addFirstRow();
        }
    }
});

/**
 * Backbone view for fee matrix row
 * @typedef {Object} FeeMatrixRowView
 */
var FeeMatrixRowView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeMatrixRowView}
     */
    initialize: function(options) {
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeMatrixRowView}
     */
    render: function() {
        var template = Handlebars.templates.feeMatrixRow;

        this.renderTemplate({
            row: this.model.attributes,
            meta: hbInitData().meta.Fee
        }, template);

        return this;
    },
    /**
     * Update the model values for this row based on the form entries
     * @returns {FeeMatrixRowView}
     */
    extractValues: function() {
        var newValues = {
            startingValue: accounting.unformat(this.$el.find('.matrix-starting-value').val()),
            baseFee: accounting.unformat(this.$el.find('.matrix-base-fee').val()),
            increment: accounting.unformat(this.$el.find('.matrix-increment').val()),
            incrementFee: accounting.unformat(this.$el.find('.matrix-increment-fee').val())
        };

        this.model.set(newValues);
        if(this.model.collection) this.model.collection.trigger('revalidate', this.model.collection);
        return this;
    },
    events: {
        "click .btn-matrix-add": "eventMatrixAdd",
        "click .btn-matrix-remove": "eventMatrixRemove",
        "change .matrix-input": "extractValues",
        "change .matrix-starting-value": "eventChangeStartingValue"
    },
    /**
     * Event handler for matrix row '+' button
     * @param {Object} e
     */
    eventMatrixAdd: function(e) {
        var newRow = new FeeMatrixRowModel({
            startingValue: this.model.get('startingValue'),
            baseFee: this.model.get('baseFee'),
            increment: this.model.get('increment'),
            incrementFee: this.model.get('incrementFee'),
            order: this.model.get('order') + 1
        });
        this.model.collection.addUpdateOrder(newRow.get('order'));
        this.model.collection.add(newRow);
        //newRow.render().$el.insertAfter(this.$el).hide().fadeIn(500);
    },
    /**
     * Event handler for matrix row '-' button
     * @param {Object} e
     */
    eventMatrixRemove: function(e) {
        this.model.collection.removeUpdateOrder(this.model.get('order'));
        this.model.collection.remove(this.model);
    },
    /**
     * Event handler for set starting value - generates an initial base fee
     * (which will make the function continuous)
     * @param {Object} e
     */
    eventChangeStartingValue: function(e) {
        var startingValue, initialCalc;

        startingValue = accounting.unformat(this.$el.find('.matrix-starting-value').val());
        initialCalc = (this.model.collection.calcResult(startingValue, this.model.get('order') - 1)).toFixed(3);

        this.model.set({baseFee: parseFloat(initialCalc)});
        this.$el.find('.matrix-base-fee').val(feesFormatMoney(initialCalc, false, 3));
    }
});

/**
 * Backbone view for fee save modal
 * @typedef {Object} FeeTemplateSaveModalView
 */
var FeeTemplateSaveModalView = BizzyBone.BaseView.extend({
    /**S
     * @param options
     * @returns {FeeTemplateSaveModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTemplateSaveModalView}
     */
    render: function() {
        var template = Handlebars.templates.feeModalSave;

        this.$el.html(template({
            feeTemplate: this.model.attributes,
            newActive: $('#fee-template-edit-isactive').is(':checked'),
            isNew: newObject,
            meta: hbInitData().meta.Fee
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {FeeTemplateModel} templateModel
     * @param {FeeTagList} tagsList
     * @param {FeeMatrixList} matrixList
     * @returns {FeeTemplateSaveModalView}
     */
    show: function(templateModel, tagsList, matrixList) {
        this.model = templateModel;
        this.tagsList = tagsList;
        this.matrixList = matrixList;

        this.render();
        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeTemplateSaveModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    /**
     * (Sorta hacky) take an array of tag data and filter out tags that don't
     * have a checked item in the tags list
     * @param tagsArray
     * @returns {Array}
     */
    filterActiveTags: function(tagsArray) {
        var out = [];

        _.each(tagsArray, function(tag) {
            if($('#tag-item-' + tag.idFeeTag).is(':checked')) out.push(tag);
        });

        return out;
    },
    events: {
        "click .btn-save": "eventSave",
        "click .btn-cancel": "eventCancel",
        "show.bs.collapse": "eventChangeMode"
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, saveMethod, toSave, saveOptions;
        thisView = this;
        saveMethod = $('input[name="save-method"]').filter(':checked').val();

        toSave = {
            title: $('#fee-template-edit-name').val(),
            description: $('#fee-template-edit-description').val(),
            idFeeSchedule: $('#fee-edit-schedule').children(':selected').val(),
            idFeeType: $('#fee-template-edit-type').children(':selected').val(),
            fixedAmount: accounting.unformat($('#fee-edit-basis').val()),
            minimumValue: accounting.unformat($('#fee-edit-minimum-value').val()),
            formula: $('#fee-edit-formula').val(),
            matrixFormula: $('#matrix-edit-formula').val(),
            feeTags: this.filterActiveTags(this.tagsList.toArray()),
            calculationMethod: _.clone(this.model.get('calculationMethod')),
            status: _.clone(this.model.get('status'))
        };

        toSave.calculationMethod.isFixed = $('#fee-template-edit-isfixed').is(':checked');
        toSave.calculationMethod.isUnit = $('#fee-template-edit-isunit').is(':checked');
        toSave.calculationMethod.isFormula = $('#fee-template-edit-isformula').is(':checked');
        toSave.calculationMethod.isMatrix = $('#fee-template-edit-ismatrix').is(':checked');

        if(toSave.calculationMethod.isMatrix) {
            toSave.feeMatrices = this.matrixList.toArray();
        }

        toSave.status.isActive = $('#fee-template-edit-isactive').is(':checked');

        saveOptions = {
            wait: true,
            success: function(model, response, options) {
                thisView.hide();

                if(saveMethod === 'copy') {
                    window.location = hbInitData().meta.Fee.path + '/' + model.get('idFeeTemplate');
                }
                else {
                    baseView.tagsView.cleanupTags();
                }
            },
            error: function(model, response, options) {
                Util.showError(response.responseJSON);
            }
        };

        if(saveMethod === 'copy') {
            this.model.forcePost(toSave, saveOptions);
        }
        else {
            this.model.save(toSave, saveOptions);
        }
    },
    /**
     * Event handler for modal cancel button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide()
    },
    /**
     * Event handler for toggle/collapse panel
     * @param {Object} e
     */
    eventChangeMode: function(e) {
        var saveMethod = $(e.target).data('savemethod');

        $('input[value="' + saveMethod + '"]').prop('checked', true);
    }
});

/**
 * Backbone view for fee template multiview row
 * @typedef {Object} FeeTemplateMultiviewRow
 */
var FeeTemplateMultiviewRow = Multiview.multiviewRowFactory({});

/**
 * Backbone view for fee template multiview main
 * @typedef {Object} FeeTemplateMultiviewMain
 */
var FeeTemplateMultiviewMain = Multiview.multiviewMainFactory(FeeTemplateMultiviewRow, {});

/**
 * Backbone view for fee template list page full
 * @typdef {Object} FeeTemplateListMainView
 */
var FeeTemplateListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTemplateListMainView}
     */
    initialize: function(options) {
        this.multiview = new FeeTemplateMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTemplateListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.feeTemplateListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    }
});

/**
 * Backbone view for fee tag multiview row custom
 * @typedef {Object} FeeTagMultiviewRow
 */
var FeeTagMultiviewRow = Multiview.multiviewRowFactory({
    /**
     * @param options
     * @returns {FeeTagMultiviewRow}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTagChanged);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-remove": "eventRemove"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {
        modalEditTag.show(this.model);
    },
    /**
     * Event handler for "Remove" button
     * @param {Object} e
     */
    eventRemove: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this fee tag?", function (result) {
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
     * Event handler for model change event
     * @param {FeeTagModel} model
     */
    eventTagChanged: function(model) {
        this.render();
    }
});

var FeeTagMultiviewMain = Multiview.multiviewMainFactory(FeeTagMultiviewRow, {
    /**
     * @param options
     * @returns {FeeTagMultiviewMain}
     */
    initialize: function(options) {
        // Listen to collection add event
        this.listenTo(this.collection, 'add', this.eventTagAdded);

        return Multiview.MultiviewMainView.prototype.initialize.call(this, options);
    },
    /**
     * Event handler for model added to collection
     * @param {FeeTagModel} model
     */
    eventTagAdded: function(model) {
        this.addRowView(model);
    }
});

/**
 * Backbone view for fee tag list main
 * @typedef {Object} FeeTagListMainView
 */
var FeeTagListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTagListMainView}
     */
    initialize: function(options) {
        this.multiview = new FeeTagMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTagListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.feeTagListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    },
    events: {
        "click #btn-new-tag": "eventNewTag"
    },
    /**
     * Event handler for "New Tag" button
     * @param {Object} e
     */
    eventNewTag: function(e) {
        var newModel = new FeeTagModel();
        modalEditTag.show(newModel, this.collection);
    }
});

/**
 * Backbone view for fee tag edit modal
 * @typedef {Object} FeeTagEditModalView
 */
var FeeTagEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTagEditModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTagEditModalView}
     */
    render: function() {
        var template = Handlebars.templates.feeTagEditModal;

        this.$el.html(template({
            feeTag: this.model.attributes,
            meta: hbInitData().meta.Fee
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {FeeTagModel} tagModel
     * @param {FeeTagList} tagList
     * @returns {FeeTagEditModalView}
     */
    show: function(tagModel, tagList) {
        this.model = tagModel;
        this.collection = tagList;

        this.render();
        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeTagEditModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-save": "eventSave",
        "click .btn-cancel": "eventCancel"
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;
        wasNew = this.model.isNew();

        toSave = {
            feeTagValue: $('#tag-edit-value').val(),
            idFeeTagCategory: parseInt($('#tag-edit-category').find('option:selected').val())
        };

        this.model.save(toSave, {
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
     * Event handler for modal cancel button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});

/**
 * Backbone view for fee tag category multiview row custom
 * @typedef {Object} FeeTagCategoryMultiviewRow
 */
var FeeTagCategoryMultiviewRow = Multiview.multiviewRowFactory({
    /**
     * @param options
     * @returns {FeeTagCategoryMultiviewRow}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventCategoryChanged);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-remove": "eventRemove"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {
        modalEditCategory.show(this.model);
    },
    /**
     * Event handler for "Remove" button
     * @param {Object} e
     */
    eventRemove: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this fee tag category?", function (result) {
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
     * Event handler for model change event
     * @param {FeeTagCategory} model
     */
    eventCategoryChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for fee tag category multiview custom
 * @typedef {Object} FeeTagCategoryMultiviewMain
 */
var FeeTagCategoryMultiviewMain = Multiview.multiviewMainFactory(FeeTagCategoryMultiviewRow, {
    /**
     * @param options
     * @returns {FeeTagCategoryMultiviewMain}
     */
    initialize: function(options) {
        // Listen to collection add event
        this.listenTo(this.collection, 'add', this.eventCategoryAdded);

        return Multiview.MultiviewMainView.prototype.initialize.call(this, options);
    },
    /**
     * Event handler for category added
     * @param {FeeTagCategory} model
     */
    eventCategoryAdded: function(model) {
        this.addRowView(model);
    }
});

/**
 * Backbone view for fee tag category list main
 * @typedef {Object} FeeTagCategoryListMainView
 */
var FeeTagCategoryListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTagCategoryListMainView}
     */
    initialize: function(options) {
        this.multiview = new FeeTagCategoryMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTagCategoryListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.feeTagCategoryListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    },
    events: {
        "click #btn-new-category": "eventNewCategory"
    },
    /**
     * Event handler for "New Category" button
     * @param {Object} e
     */
    eventNewCategory: function(e) {
        var newModel = new FeeTagCategory();
        modalEditCategory.show(newModel, this.collection);
    }
});

/**
 * Backbone view for fee tag category edit modal
 * @typedef {Object} FeeTagCategoryEditModalView
 */
var FeeTagCategoryEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTagCategoryEditModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTagCategoryEditModalView}
     */
    render: function() {
        var template = Handlebars.templates.feeTagCategoryEditModal;

        this.$el.html(template({
            feeTagCategory: this.model.attributes,
            meta: hbInitData().meta.Fee
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {FeeTagCategory} categoryModel
     * @param {FeeTagCategoryList} categoryList
     * @returns {FeeTagCategoryEditModalView}
     */
    show: function(categoryModel, categoryList) {
        this.model = categoryModel;
        this.collection = categoryList;

        this.render();
        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeTagCategoryEditModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-save": "eventSave",
        "click .btn-cancel": "eventCancel"
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;
        wasNew = this.model.isNew();

        toSave = {
            title: $('#category-edit-title').val(),
            status: {
                isFeeSchedule: $('#category-edit-isfeeschedule').is(':checked')
            }
        };

        this.model.save(toSave, {
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
     * Event handler for modal cancel button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});

/**
 * Backbone view for fee type multiview row
 * @typedef {Object} FeeTypeMultiviewRow
 */
var FeeTypeMultiviewRow = Multiview.multiviewRowFactory({
    /**
     * @param options
     * @returns {FeeTypeMultiviewRow}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventTypeChanged);

        return Multiview.MultiviewRowView.prototype.initialize.call(this, options);
    },
    events: {
        "click .btn-edit": "eventEdit",
        "click .btn-remove": "eventRemove"
    },
    /**
     * Event handler for "Edit" button
     * @param {Object} e
     */
    eventEdit: function(e) {
        modalEditType.show(this.model);
    },
    /**
     * Event handler for "Remove" button
     * @param {Object} e
     */
    eventRemove: function(e) {
        var thisView = this;

        bootbox.confirm("Are you sure you want to delete this fee type?", function (result) {
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
     * Event handler for model change event
     * @param {FeeTypeModel} model
     */
    eventTypeChanged: function(model) {
        this.render();
    }
});

/**
 * Backbone view for fee type multiview main
 * @typedef {Object} FeeTypeMultiviewMain
 */
var FeeTypeMultiviewMain = Multiview.multiviewMainFactory(FeeTypeMultiviewRow, {
    /**
     * @param options
     * @returns {FeeTypeMultiviewMain}
     */
    initialize: function(options) {
        // Listen to collection add event
        this.listenTo(this.collection, 'add', this.eventCategoryAdded);

        return Multiview.MultiviewMainView.prototype.initialize.call(this, options);
    },
    /**
     * Event handler for type added
     * @param {FeeTypeModel} model
     */
    eventCategoryAdded: function(model) {
        this.addRowView(model);
    }
});

/**
 * Backbone view for fee type list main
 * @typedef {Object} FeeTypeListMainView
 */
var FeeTypeListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTypeListMainView}
     */
    initialize: function(options) {
        this.multiview = new FeeTypeMultiviewMain({collection: this.collection});

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTypeListMainView}
     */
    render: function() {
        var template, multiview;
        template = Handlebars.templates.feeTypeListMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        multiview = this.$el.find('#multiview');
        multiview.append(this.multiview.render().$el);

        return this;
    },
    events: {
        "click #btn-new-type": "eventNewType"
    },
    /**
     * Event handler for "New Category" button
     * @param {Object} e
     */
    eventNewType: function(e) {
        var newModel = new FeeTypeModel();
        modalEditType.show(newModel, this.collection);
    }
});

/**
 * Backbone view for fee type edit modal
 * @typedef {Object} FeeTypeEditModalView
 */
var FeeTypeEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeTypeEditModalView}
     */
    initialize: function(options) {
        this.rendered = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeTypeEditModalView}
     */
    render: function() {
        var template = Handlebars.templates.feeTypeEditModal;

        this.$el.html(template({
            feeType: this.model.attributes,
            meta: hbInitData().meta.Fee
        }));

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * @param {FeeTagCategory} typeModel
     * @param {FeeTagCategoryList} typeList
     * @returns {FeeTypeEditModalView}
     */
    show: function(typeModel, typeList) {
        this.model = typeModel;
        this.collection = typeList;

        this.render();
        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeTypeEditModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-save": "eventSave",
        "click .btn-cancel": "eventCancel"
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;
        wasNew = this.model.isNew();

        toSave = {
            feeTypeTitle: $('#type-edit-title').val(),
            feeTypeFlags: {
                isPayable: $('#type-edit-ispayable').is(':checked'),
                isOpen: $('#type-edit-isopen').is(':checked'),
                isDeposit: $('#type-edit-isdeposit').is(':checked')
            }
        };

        this.model.save(toSave, {
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
     * Event handler for modal cancel button
     * @param {Object} e
     */
    eventCancel: function(e) {
        this.hide();
    }
});