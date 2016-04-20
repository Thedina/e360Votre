/**
 * File Upload (combined)
 */

/**
 * @namespace FileUpload
 */
FileUpload = (function() {
    var fu = {};

    /**
     * Generates HTML for options list for file category select.
     * @param {Array.<string>} categories
     * @param {string} [selected]
     * @returns {string}
     */
    Handlebars.registerHelper('fileCategories', function(categories, selected) {
        var options = "<option value=''>N/A</option>";
        _.each(categories, function(name) {
            options += "<option value='" + name + "'" + (name === selected ? " selected='selected'" : "") + ">" + name + "</option>";
        });
        return options;
    });

    /**
     * Makes Util.formatFileSize() callable from Handlebars template
     * @param {number} bytes
     * @param {number} [decimals=2]
     * @param {boolean} [baseTen=false]
     * @returns {string}
     */
    Handlebars.registerHelper('fileSize', function(bytes, decimals) {
        return Util.formatFileSize(bytes, decimals, false);
    });

    /**
     * Simple helper to make sure we never display *nothing* for file category.
     * @param {string} category
     * @returns {string}
     */
    Handlebars.registerHelper('displayCategory', function(category) {
        if(!category) {
            return 'N/A';
        }
        else {
            return category;
        }
    });

    /**
     * Precompile all core file upload templates by passing them to specified
     * compiler function
     * @param {function} compiler
     */
    fu.compileTemplates = function(compiler) {
        _.each({
            fileUploadMain: '#hb-fileupload-main',
            fileUploadFile: '#hb-fileupload-file',
            fileListMain: '#hb-filelist-main',
            fileListFile: '#hb-filelist-file'
        }, compiler);
    };

    /**
     * Backbone model for files
     * @typedef {Object} FileModel
     */
    fu.FileModel = Backbone.Model.extend({
        /**
         * Initialize with optional urlPrefix
         * @param {Object} [attributes]
         * @param {Object} [options]
         * @param [options.urlPrefix]
         * @returns {FileModel}
         */
        initialize: function(attributes, options) {
            if(_.has(options, 'urlPrefix')) {
                this.urlPrefix = options.urlPrefix;
            }
            return Backbone.Model.prototype.initialize.call(this, attributes, options);
        },
        /**
         * file URL root is [baseUrl]/[urlPrefix]/files e.g. PUT to
         * [siteUrl]/api/v1/projects/1/submittals/submittals/38/files/77
         * @returns {string}
         */
        urlRoot: function() {
            if(_.has(this, 'urlPrefix')) {
                return this.urlPrefix + '/files';
            }
            else {
                return hbInitData().meta.apiPath + '/files';
            }
        },
        idAttribute: 'idFile',
        defaults: {
            idFolder: 0,
            idUser: 0,
            fileName: "",
            category: "",
            description: "",
            size: 0,
            dateCreated: "",
            cloudDatetime: "",
            flags: {
                active: true,
                local: true
            },
            bytesSent: 0
        },
        /**
         * Save omits 'bytesSent' attribute
         * @param {Object} [attributes]
         * @param {Object} [options]
         * @returns {Object}
         */
        save: function(attributes, options) {
            attributes = _.omit(attributes, ['bytesSent']);

            return Backbone.Model.prototype.save.call(this, attributes, options);
        }
    });

    /**
     * A list of FileModels
     * @typedef {Object} FileList
     */
    fu.FileList = Backbone.Collection.extend({
        model: fu.FileModel
    });

    fu.asFileUploadContainerView = function(view) {
        var extendWith, events;

        extendWith = {
            /**
             * Event handler for dragging a file over the drop zone. Add the
             * filedrop-target class to indicate droppability.
             * @param {Object} e
             */
            eventDragEnter: function(e) {
                this.$el.addClass('filedrop-target');
            },
            /**
             * Event hander for dragging a file out of the drop zone. Remove
             * the filedrop-target class.
             * @param {Object} e
             */
            eventDragExit: function(e) {
                this.$el.removeClass('filedrop-target');
            }
        };

        events = _.extend(_.clone(view.prototype.events), {
            "dragover": "eventDragEnter",
            "dragexit": "eventDragExit"
        });

        extendWith.events = events;

        return view.extend(extendWith);
    };

    /**
     * Reusable Backbone review for file uploads
     * @typedef {FileUploadView}
     */
    fu.FileUploadView = Backbone.View.extend({
        /**
         * FileUploadView takes a urlPrefix and a jQuery object for the
         * containing/file drop target element
         * @param {Object} options
         * @param {string} options.urlPrefix
         * @param {jQuery} options.containerEl
         * @returns {FileUploadView}
         */
        initialize: function(options) {
            var thisView = this;
            if(_.has(options, 'urlPrefix')) {
                this.urlPrefix = options.urlPrefix;
            }

            if(_.has(options, 'containerEl') && options.containerEl instanceof jQuery) {
                this.containerEl = options.containerEl;
                this.browseButton = this.containerEl.find('.btn-add-file');
            }

            this.fileViews = [];

            // For each FileModel in the collection, instantiate a view
            _.each(this.collection.models, function(fileModel) {
                thisView.fileViews.push(new fu.UploadedFileView({model: fileModel}));
            });

            // Listen to collection remove event
            this.listenTo(this.collection, 'remove', this.eventFileRemoved);

            return Backbone.View.prototype.initialize.call(this, options);
        },
        /**
         * Because of peculiarites of rendering order sometimes you might need to
         * pass the containerEl jQuery object in the render function. Also note
         * that dropzone is set up here.
         * @param {Object} containerEl
         * @returns {FileUploadView}
         */
        render: function(containerEl) {
            var template, fileList, thisView;
            thisView = this;

            // If we did get containerEl, save it
            if(containerEl instanceof jQuery) {
                thisView.containerEl = containerEl;
                thisView.browseButton = this.containerEl.find('.btn-add-file');
            }

            template = Handlebars.templates.fileUploadMain;
            this.$el.html(template({meta: hbInitData().meta}));

            if(!this.collection.length) this.$el.hide();
            else this.$el.show();

            // Set up dropzone on el for this view. Override most of the defaults
            this.containerEl.dropzone({
                url: thisView.urlPrefix + '/' + 'files',
                clickable: thisView.browseButton.get(),
                previewTemplate: "",
                /**
                 * Dispatch to FileUploadView.eventAddFile()
                 * @param {Object} file
                 */
                addedfile: function(file) {
                    thisView.eventAddFile(file);
                },
                /**
                 * Progress callback updates the corresponding file model with bytes sent
                 * @param {Object} file
                 * @param {number} progress
                 * @param {number} bytesSent
                 */
                uploadprogress: function(file, progress, bytesSent) {
                    if(_.isObject(file.model)) {
                        file.model.set({bytesSent: bytesSent})
                    }
                },
                /**
                 * If file uploaded successfully trigger the uploaded event on the
                 * corresponding file model. If it fails, delete the model.
                 * @param {Object} file
                 */
                complete: function(file) {
                    var response, fileData;

                    if(file.status === 'canceled') {
                        file.model.destroy();
                    }
                    else {
                        response = JSON.parse(file.xhr.response);

                        if (!_.isEmpty(response.errors)) {
                            Util.showError(response);
                            file.model.destroy();
                        }
                        else {
                            if (_.isArray(response.data)) {
                                fileData = response.data.pop();

                                if (_.isObject(file.model)) {
                                    file.model.set(fileData);
                                    file.model.trigger('uploaded', file.model);
                                }
                            }
                        }
                    }
                }
            });

            fileList = this.$el.find('.file-list');

            // render the file subviews
            _.each(this.fileViews, function(fileView) {
                fileView.render();
                fileView.$el.appendTo(fileList);
            });

            return this;
        },
        /**
         * Returns true if no file view is pending an update (i.e. has view.saving set)
         * @returns {boolean}
         */
        areFilesReady: function() {
            var ready = true;

            _.each(this.fileViews, function(fileView) {
                if(fileView.saving) {
                    ready = false;
                }
            });

            return ready;
        },
        /**
         * Event hander for dropping/adding a file
         * @param {Object} file
         */
        eventAddFile: function(file) {
            var fileView, newFile;

            // Clear filedrop-target since we are no longer hovering
            this.containerEl.removeClass('filedrop-target');

            // Add the file model
            newFile = new fu.FileModel(
                {
                    fileName: file.name,
                    description: file.name,
                    size: file.size
                },
                {
                    urlPrefix: this.urlPrefix
                }
            );

            // Add a reference to the dropzone itself and the model to the dropzone file object
            file.dropzone = this.containerEl.get().dropzone;
            file.model = newFile;

            // Add the file view
            this.collection.add(newFile);
            fileView = new fu.UploadedFileView({model: newFile, file: file, uploading: true});
            this.$el.show();
            this.fileViews.push(fileView);
            fileView.render();
            this.$el.find('.file-list').append(fileView.$el);
        },
        /**
         * Event handler for file list model removed
         * @param {FileModel} model
         * @param {FileList} collection
         */
        eventFileRemoved: function(model, collection) {
            if(!collection.length) this.$el.hide();
            else this.$el.show();
        }
    });

    /**
     * Backbone subview for a file inside the reusable file upload view
     * @typedef {UploadedFileView}
     */
    fu.UploadedFileView = Backbone.View.extend({
        /**
         * @param {Object} options
         * @param {boolean} options.uploading
         * @returns {UploadedFileView}
         */
        initialize: function(options) {
            this.defaultElement = _.has(options, 'el') ? false : true;

            // listen to several events coming from the model
            this.listenTo(this.model, 'change:bytesSent', this.eventProgress);
            this.listenTo(this.model, 'uploaded', this.eventFileUploaded);
            this.listenTo(this.model, 'destroy', this.eventFileDeleted);

            this.typeTimer = null;
            this.descTimer = null;
            this.waitForUpload = false;
            this.saving = false;

            if(_.has(options, 'file')) {
                this.file = options.file;
            }

            if(_.has(options, 'uploading')) {
                this.uploading = options.uploading;
            }
            else {
                this.uploading = false;
            }

            return Backbone.View.prototype.initialize.call(this, options);
        },
        /**
         * @returns {UploadedFileView}
         */
        render: function() {
            var template, oldEl;

            template = Handlebars.templates.fileUploadFile;

            // If this is the first render, fill $el from the template. Otherwise replace it.
            if(this.$el.is(':empty')) {
                this.$el.html(template({file: this.model.attributes, meta: hbInitData().meta}));

                // If we rendered into the default div (i.e. this.el was never set) lose the outer
                // div and point whatever is the outermost container from the template
                if(this.defaultElement) {
                    this.setElement(this.$el.children().first());
                }
            }
            else {
                oldEl = this.$el;
                this.setElement(template({file: this.model.attributes, meta: hbInitData().meta}));
                oldEl.replaceWith(this.$el);
            }

            return this;
        },
        /**
         * setElement is modified to set this.defaultElement to false when first
         * called
         * @param {Object} element
         * @return {UploadedFileView}
         */
        setElement: function(element) {
            this.defaultElement = false;
            return Backbone.View.prototype.setElement.call(this, element);
        },
        events: {
            "click .btn-delete": "eventDeleteFile",
            "change .file-type": "eventChangeType",
            "keydown .file-desc": "eventDescEditStart",
            "focusout .file-desc": "eventDescUnfocus",
            "keyup .file-desc": "eventDescEditStop"
        },
        /**
         * Save the file category if it has changed in the view
         */
        saveType: function() {
            var thisView, type;

            thisView = this;
            type = this.$el.find('.file-type').val();

            if(type !== this.model.get('category')) {
                this.model.save({category: type}, {
                    wait: true,
                    success: function(model, response, options) {
                        thisView.saving = false;
                        thisView.$el.find('.ul-status').text('Done');
                    },
                    error: function(model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        },
        /**
         * Save the file description if it has changed in the view
         */
        saveDesc: function() {
            var thisView, desc;

            thisView = this;
            desc = this.$el.find('.file-desc').val();

            if(desc !== this.model.get('description')) {
                this.model.save({description: desc}, {
                    wait: true,
                    success: function(model, response, options) {
                        thisView.saving = false;
                        thisView.$el.find('.ul-status').text('Done');
                    },
                    error: function(model, response, options) {
                        Util.showError(response.responseJSON);
                    }
                });
            }
        },
        /**
         * Force save the file category *and* description. Used after one or both was
         * changed while the file was uploading
         */
        saveAll: function() {
            var thisView, toSave;
            thisView = this;

            toSave = {
                category: this.$el.find('.file-type').val(),
                description: this.$el.find('.file-desc').val()
            };

            this.model.save(toSave, {
                wait: true,
                success: function(model, response, options) {
                    thisView.saving = false;
                    thisView.$el.find('.ul-status').text('Done');
                },
                error: function(model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });

        },
        /**
         * Event handler for upload progress callback
         * @param {FileModel} model
         */
        eventProgress: function(model) {
            var statusText = Util.formatFileSize(model.get('bytesSent')) + '/' + Util.formatFileSize(model.get('size')) + ' Uploading';
            this.$el.find('.ul-status').text(statusText);
        },
        /**
         * Event handler for upload completed callback
         * @param {FileModel} model
         */
        eventFileUploaded: function(model) {
            this.uploading = false;

            if(this.waitForUpload) {
                this.saveAll();
                this.waitForUpload = false;
            }

            this.$el.attr('data-idfile', model.get('idFile'));
            this.$el.find('.ul-status').text('Done');
        },
        /**
         * Event handler for file deletion model event
         * @param {FileModel} model
         */
        eventFileDeleted: function(model) {
            var thisView = this;
            this.$el.fadeOut(500, function() {
                thisView.remove();
            });
        },
        /**
         * Event handler for file delete button
         * @param {Object} e
         */
        eventDeleteFile: function(e) {
            var thisView = this;

            if(!thisView.uploading) {
                bootbox.confirm("Are you sure you want to remove this file?", function (result) {
                    if (result) {
                        thisView.model.destroy({
                            wait: true,
                            error: function (model, response, options) {
                                Util.showError(response.responseJSON);
                            }
                        });
                    }
                });
            }
            else if(thisView.file) {
                bootbox.confirm("Are you sure you want to cancel this upload?", function (result) {
                    if (result) {
                        thisView.file.dropzone.cancelUpload(thisView.file);
                    }
                });
            }
        },
        /**
         * Event handler for category select.
         * @param {Object} e
         */
        eventChangeType: function(e) {
            var thisView, type;

            if(!this.uploading) {
                thisView = this;
                type = this.$el.find('.file-type').val();

                // if the category has actually been changed, start a timer to call saveType()
                if (type !== this.model.get('category')) {
                    thisView.saving = true;
                    thisView.$el.find('.ul-status').text('Saving...');

                    clearTimeout(this.typeTimer);
                    thisView.typeTimer = setTimeout(function () {
                        thisView.saveType.call(thisView);
                    }, 1000);
                }
            }
            else {
                this.waitForUpload = true;
            }
        },
        /**
         * Event handler for keydown in description field
         * @param {Object} e
         */
        eventDescEditStart: function(e) {
            // clear desc edit timer as long as user keeps typing
            if(!_.isNull(this.descTimer)) {
                clearTimeout(this.descTimer);
                this.descTimer = null;
            }
        },
        /**
         * Event handler for keyup in description field
         * @param {Object} e
         */
        eventDescEditStop: function(e) {
            var thisView, desc;

            if(!this.uploading) {
                thisView = this;
                desc = this.$el.find('.file-desc').val();

                // if description field has actually changed, start a timer to call saveDesc()
                if (desc !== this.model.get('description')) {
                    thisView.saving = true;
                    thisView.$el.find('.ul-status').text('Saving...');
                    
                    if(!this.descTimer) {
                        clearTimeout(this.descTimer);
                        this.descTimer = setTimeout(function () {
                            thisView.descTimer = null;
                            thisView.saveDesc.call(thisView);
                        }, 1000);
                    }
                }
            }
            else {
                this.waitForUpload = true;
            }
        },
        /**
         * Event handler for focusout of description field. This just shortcuts to
         * saveDesc() to prevent any shenanigans getting around the key handlers
         * @param {Object} e
         */
        eventDescUnfocus: function(e) {
            var desc = this.$el.find('.file-desc').val();

            if(desc !== this.model.get('description')) {
                this.saving = true;
                this.$el.find('.ul-status').text('Saving...');
                this.saveDesc();
            }
        }
    });

    /**
     * Reusable backbone view for file list
     * @typedef {Object} FileListView
     */
    fu.FileListView = Backbone.View.extend({
        /**
         * @param options
         * @param {string} options.urlPrefix
         * @returns {FileListView}
         */
        initialize: function(options) {
            var thisView = this;
            if(_.has(options, 'urlPrefix')) {
                this.urlPrefix = options.urlPrefix;
            }

            this.fileViews = [];

            // For each FileModel in the collection, instantiate a view
            _.each(this.collection.models, function(fileModel) {
                thisView.fileViews.push(new fu.ListedFileView({model: fileModel}));
            });

            return Backbone.View.prototype.initialize.call(this, options);
        },
        /**
         * @returns {FileListView}
         */
        render: function() {
            var template, fileList;

            template = Handlebars.templates.fileListMain;
            this.$el.html(template({meta: hbInitData().meta}));

            this.$el.find('.btn-downall').remove();

            if(!this.collection.length) this.$el.hide();
            else this.$el.show();

            fileList = this.$el.find('.file-list');

            // render the file subviews
            _.each(this.fileViews, function(fileView) {
                fileView.render();
                fileView.$el.appendTo(fileList);
            });

            return this;
        }
    });

    /**
     * Backbone subview for a file inside the file list view
     * @typedef {Object} ListedFileView
     */
    fu.ListedFileView = Backbone.View.extend({
        /**
         * @param [options]
         * @returns {ListedFileView}
         */
        initialize: function(options) {
            this.defaultElement = _.has(options, 'el') ? false : true;
            return Backbone.View.prototype.initialize.call(this, options);
        },
        /**
         * @returns {ListedFileView}
         */
        render: function() {
            var template, oldEl, fileTypeText;
            template = Handlebars.templates.fileListFile;

            fileTypeText = this.model.get('fileName').split('.');
            if(fileTypeText.length > 1) fileTypeText = fileTypeText[fileTypeText.length - 1].toUpperCase();
            else fileTypeText = 'FILE';

            // If this is the first render, fill $el from the template. Otherwise replace it.
            if(this.$el.is(':empty')) {
                this.$el.html(template({
                    file: this.model.attributes,
                    fileTypeText: fileTypeText,
                    meta: hbInitData().meta
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
                    file: this.model.attributes,
                    fileTypeText: fileTypeText,
                    meta: hbInitData().meta
                }));
                oldEl.replaceWith(this.$el);
            }

            return this;
        },
        /**
         * setElement is modified to set this.defaultElement to false when first
         * called
         * @param {Object} element
         * @return {ListedFileView}
         */
        setElement: function(element) {
            this.defaultElement = false;
            return Backbone.View.prototype.setElement.call(this, element);
        }
    });

    return fu;
})();