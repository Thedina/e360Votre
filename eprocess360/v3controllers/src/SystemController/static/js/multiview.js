/**
 * Multiview (global package)
 */

/**
 * @namespace Multiview
 */
Multiview = (function() {
    var mtv = {};

    /**
     * Handlebars helpers
     */

    /**
     * Generates title of the multiview bar.
     */
    Handlebars.registerHelper('multiviewTitle', function () {
        var $links = hbInitData().meta.dashboardBar.links;
        var $title;

        for (var i = 0; i < $links.length; i++) {
            if ($links[i]['active'] == true) {
                $title = $links[i]['title'];
            }
        }

        return $title;

    });

    /**
     * Generate HTML for the bucket-by selection dropdown menu button (display of
     * current selection etc.)
     * @param {Array.<string>} options
     * @param {Object} selected
     * @returns {string}
     */
    Handlebars.registerHelper('bucketByCurrent', function(options, selected) {
        var html, selectedText, sortIcon;
        html = "";

        if(!selected || !selected.selected.length) selectedText = 'None';
        else selectedText = _.find(options, function(option) { return option.column == selected.selected; }).label;

        if(selected.order == 'DESC') {
            sortIcon = 'glyphicon-sort-by-alphabet-alt';
        }
        else {
            sortIcon = 'glyphicon-sort-by-alphabet';
        }

        // TODO: make the glyphicon indicate sort order somehow? (the current one doesn't have an inverted version)
        html += '<span class="hidden-sm">' + selectedText + '</span> <i class="glyphicon ' + sortIcon + '"></i>';

        return html;
    });

    /**
     * Generate select options for bucket-by dropdown
     * @param {Array.<string>} options
     * @param {Object} selected
     * @returns {string}
     */
    Handlebars.registerHelper('bucketByOptions', function(options, selected) {
        var html, checkIcon;
        checkIcon = '&nbsp;<span class="glyphicon glyphicon-ok" aria-hidden="true" aria-label="selected"></span>';
        html = "";

        _.each(options, function(option) {
            html += '<li class="bucket-option" data-column="' + option.column + '">Group by ' + option.label + (option.column == selected.selected ? checkIcon : '') + '</li>';
        });

        html += '<li class="bucket-option" data-column="">No Grouping' + (selected.selected === '' ? checkIcon : '') + '</li>';
        html += '<li class="divider"></li>';
        html += '<li class="bucket-order" data-order="ASC">Ascending' + (selected.order == 'ASC' || !selected.order ? checkIcon : '') + '</li>';
        html += '<li class="bucket-order" data-order="DESC">Descending' + (selected.order == 'DESC' ? checkIcon : '') + '</li>';

        return html;
    });

    /**
     * Generate select options for filter-by dropdown
     * @param {Array.<string>} options
     * @param {string} selected
     * @returns {string}
     */
    Handlebars.registerHelper('filterOptions', function(options, selected) {
        var html = "";

        html += Util.makeOption('', 'None', !selected.length);

        _.each(options, function(option, val) {
            html += Util.makeOption(
                option,
                option,
                _.contains(selected, option)
            );
        });

        return html;
    });

    /**
     * Generate HTML for page number list items
     * @param {number} numPages
     * @param {number} curPage
     * @returns {string}
     */
    Handlebars.registerHelper('pageNumbers', function(numPages, curPage) {
        function pageNum(num) {
            return '<li class="btn-page' + (num == curPage ? ' active' : '') + '"><a href="#/page=' + num + '" data-page="' + num + '">' + num + '</a></li>';
        }

        var divider, i, html;
        divider = '<li class="disabled"><a href="" class="page-divider">..</a></li>';

        html = '';

        for(i = 1; i <= numPages; i++) {
            if(i < 3 || i == numPages) {
                html += pageNum(i);
            }
            else if(i == 3) {
                html += pageNum(i);

                if(numPages > 3) {
                    html += divider;
                }
            }
            else if(numPages > 4 && i == (numPages - 1)) {
                html += pageNum(i);
            }
        }

        return html;
    });

    /**
     * Generate ascending or descending sort indicator for column
     * @param {Object} sort
     * @param {string} sort.selected
     * @param {string} sort.order
     * @param {string} column
     * @returns {string}
     */
    Handlebars.registerHelper('columnSortIcon', function(sort, column) {
        var iconClass = "";

        if(sort.selected === column) {
            if(sort.order === 'ASC') iconClass = " glyphicon-chevron-up";
            else if(sort.order === 'DESC') iconClass = " glyphicon-chevron-down";
        }

        return '<span class="glyphicon' + iconClass + '"></span>';
    });

    /**
     * Precompile all core multiview templates by passing them to specified
     * compiler function
     * @param {function} compiler
     */
    mtv.compileTemplates = function(compiler) {
        _.each({
            multiviewMain: $('#hb-multiview-main'),
            multiviewFilterControls: $('#hb-multiview-filter-controls'),
            multiviewPageControls: $('#hb-multiview-page-controls'),
            multiviewColumnHeaders: $('#hb-multiview-column-headers'),
            multiviewBucketHeader: $('#hb-multiview-bucket-header'),
            multiviewRow: $('#hb-multiview-row'),
            multivewColDefault: $('#hb-multiview-col-default'),
            multivewColLink: $('#hb-multiview-col-link')
        }, compiler);
    };

    /**
     * Precompile extended templates (a set of common "custom" templates
     * defined in global.multiview.handlebars.html)
     * @param compiler
     */
    mtv.compileExtendedTemplates = function(compiler) {
        _.each({
            mvCustomColumnEdit: $('#hb-multiview-custom-column-edit'),
            mvCustomColumnRemove: $('#hb-multiview-custom-column-remove'),
            mvCustomColumnStatusActive : $('#hb-multiview-custom-status-active'),
            mvCustomColumnAssignType: $('#hb-multiview-custom-column-assign-types'),
            mvCustomColumnAssignSkill: $('#hb-multiview-custom-column-assign-skills'),
            mvCustomColumnAssignLimitation: $('#hb-multiview-custom-column-assign-limitations'),
        }, compiler);
    };

    /**
     * Backbone: Models
     */

    /**
     * Backbone model template for multiview controls
     * @typedef {Object} MultiviewControlModel
     */
    mtv.MultiviewControlModel = BizzyBone.BaseModel.extend({
        defaults: {
            api: '',
            currentPage: 0,
            numPages: 0,
            limit: 0,
            rowCount: 0,
            columns : {},
            sort: {},
            searchBy: {},
            bucketBy: {},
            filterBy: {}
        },
        dontSave: [
            'api',
            'columns',
            'numPages',
            'limit',
            'rowCount'
        ],
        /**
         * @param options
         * @returns {MultiviewControlModel}
         */
        initialize: function(options) {
            return BizzyBone.BaseModel.prototype.initialize.call(this, options);
        },
        /**
         * Pull out control specifications from column data
         * @returns {{search: Array, bucket: Array, filter: Array}}
         */
        getColumnInfo: function() {
            var searchInfo, filterInfo, bucketInfo;
            searchInfo = [];
            bucketInfo = [];
            filterInfo = [];

            _.each(this.get('columns'), function(col) {
                if(col.searchBy) {
                    searchInfo.push({
                        column: col.key,
                        label: col.label
                    });
                }

                if(col.bucketBy) {
                    bucketInfo.push({
                        column: col.key,
                        label: col.label
                    });
                }

                if(col.filterBy) {
                    filterInfo.push({
                        column: col.key,
                        options: col.filterByOptions
                    });
                }
            });

            return {
                search: searchInfo,
                bucket: bucketInfo,
                filter: filterInfo
            };
        },
        /**
         * Reset sort/search/bucket/filter settings
         */
        reset: function() {
            var newSort, newSearch, newBucket, newFilter;
            newSort = {
                selected: '',
                order: ''
            };
            newBucket = {
                selected: '',
                order: ''
            };
            newSearch = _.clone(this.get('searchBy'));
            newFilter = _.clone(this.get('filterBy'));

            _.each(newSearch, function(val, col) {
                newSearch[col] = "";
            });

            _.each(newFilter, function(val, col) {
                newFilter[col] = [];
            });

            this.set({
                sort: newSort,
                searchBy: newSearch,
                bucketBy: newBucket,
                filterBy: newFilter
            }, {silent: true});

            this.trigger('reset', this);
        },
        /**
         * Check whether we can sort on column (specified by key/name)
         * @param {string} column
         * @returns {boolean}
         */
        canSortOn: function(column) {
            return _.findWhere(this.get('columns'), {key: column}).sort;
        },
        /**
         * Updates the sort attr with appropriate values when the user selects or
         * @param {string} column
         */
        updateSort: function(column) {
            var sort = this.get('sort');

            if(sort.selected === column) {
                if(sort.order === 'DESC' || sort.order === '') sort.order = 'ASC';
                else if(sort.order === 'ASC') sort.order = 'DESC';
            }
            else {
                sort.selected = column;

                if(sort.order === '') sort.order = 'ASC';
            }

            this.set({sort: sort}, {silent: true});

            this.trigger('resort', this);
        }
    });

    /**
     * Add multiview functionality to an existing model
     * @param {BizzyBone.BaseModel} model
     * @param {Object} meta
     * @returns {function}
     */
    mtv.modelMultiviewable = function(model, meta) {
        // could do this the same way as collectionMultiviewable() - with parent inside
        // the closure, but don't need to right now because there are no calls to super functions
        var m = {
            /**
             * Pull out column data from the model matching the columns to be
             * displayed
             * @param {Object} columns
             * @returns {Object}
             */
            extractColumnDataOld: function(columns) {
                var thisModel, out;
                thisModel = this;
                out = {};

                _.each(columns, function(info, key) {
                    var splitKey, attr, val, temp;

                    splitKey = key.split('.');
                    attr = splitKey.shift();
                    val = thisModel.get(attr);

                    if(_.isObject(val))  {
                        temp = Util.traverseNestedKeepKey(val, splitKey);
                        attr = temp[0];
                        val = temp[1];
                    }

                    out[attr] = val;
                });

                return out;
            },
            /**
             * Pull out model data marching a column string/name
             * @param {string} key
             * @returns {*}
             */
            extractColumnValue: function(key) {
                var splitKey, attr, val;

                splitKey = key.split('.');
                attr = splitKey.shift();
                val = this.get(attr);

                if(_.isObject(val))  {
                    val = Util.traverseNested(val, splitKey);
                }

                return val;
            }
        };

        m.meta = meta;

        return model.extend(m);
    };

    /**
     * Add multiview functionality to an existing collection
     * @param {BizzyBone.BaseCollection} collection
     * @returns {function}
     */
    mtv.collectionMultiviewable = function(collection) {
        var c, parent;
        parent = Object.create(collection.prototype);

        c = {
            model: parent.model,
            /**
             * @param models
             * @param options
             * @param {MultiviewControlModel} options.controlModel
             * @returns {Object}
             */
            initialize: function(models, options) {
                if(_.has(options, 'controlModel')) this.controlModel = options.controlModel;

                return parent.initialize.call(this, models, options);
            },
            /**
             * Custom parse function to pull out controls/meta data (updating
             * this.controlModel if it exists or creating it if it does not)
             * @param {Object} response
             * @returns {Array}
             */
            parse: function(response) {
                var rowData, pagesChanged;

                if(_.has(response, 'data')) response = response.data;

                rowData = response.results;
                delete response.results;

                if(_.has(this, 'controlModel')) {
                    // Hack to trigger event for num pages changed even though it
                    // TODO: find a way to make more consistent use of events? The reason you can't
                    // simply listen to changed to this.controlModel on refresh is that those changes
                    // have already been set()*before* fetchFiltered() is called. And that kinda has
                    // to happen because controlModel isn't a 'real' model but is embedded in the
                    // collection. Is there a way to flag these changes so that the change events can
                    // be postponed, then triggered?
                    pagesChanged = (this.controlModel.get('numPages') != response.numPages);

                    this.controlModel.set(response, {silent: true});

                    if(pagesChanged) {
                        this.controlModel.trigger('change:numPages', this.controlModel);
                    }
                }
                else {
                    this.controlModel = new mtv.MultiviewControlModel(response);
                }

                return rowData;
            },
            /**
             * Load data from an object as parsed by parse()
             * @param {Object} response
             * @param {Object} options
             * @returns {Array.<Object>}
             */
            loadData: function(response, options) {
                return this.reset(this.parse(response), options);
            },
            /**
             * Get models sorted into buckets by bucketAttr. Returns array of
             * {name: [bucket name], contents: Array.<[Models]>}
             * @param {string} bucketAttr
             * @param {string} order
             * @returns {Array}
             */
            getByBucket: function(bucketAttr, order) {
                var buckets, sorted;
                order || (order = 'ASC');

                buckets = _.groupBy(this.models, function(rowModel) {
                    return rowModel.extractColumnValue(bucketAttr);
                });

                sorted = _.sortBy(_.map(buckets, function(bucket, key) {
                    return {name: key, contents: bucket};
                }), 'name');

                if(order === 'DESC') sorted = sorted.reverse();

                return sorted;
            },
            /**
             * Get the names of all values of bucketAttr for models in this
             * collection, sorted in order
             * @param {string} bucketAttr
             * @param {string} order
             * @returns {Array.<string>}
             */
            getBuckets: function(bucketAttr, order) {
                var buckets;
                order || (order = 'ASC');

                buckets = _.countBy(this.models, function(rowModel) {
                    return rowModel.extractColumnValue(bucketAttr);
                });

                buckets = _.sortBy(_.keys(buckets));
                if(order === 'DESC') buckets = buckets.reverse();

                return buckets;
            },
            /**
             * Call this.getBuckets() with current bucketBy values (if set else
             * return null)
             * @returns {Array.<string>|null}
             */
            getCurrentBuckets: function() {
                var bucketBy = this.controlModel.get('bucketBy');

                if(!bucketBy.selected.length) return null;
                return this.getBuckets(bucketBy.selected, bucketBy.order);
            },
            /**
             * Update rows, applying all sorts, searches, filters and bucketing
             */
            fetchFiltered: function(options) {
                var attrs = this.controlModel.attributesToSave();
                options || (options = {});
                options = _.extend(options, {
                    reset: true,
                    data: attrs,
                    wait: true
                });

                this.fetch(options);
            },
            /**
             * Update rows searching by column matching text
             * @param {string} column
             * @param {string} text
             */
            searchBy: function(column, text) {
                var searchBy = _.clone(this.controlModel.get('searchBy'));

                searchBy[column] = text;
                this.controlModel.set({searchBy: searchBy});

                this.fetchFiltered();
            },
            /**
             * Update rows bucketing by column in order
             * @param {Object} selection
             * @param {string|null} selection.column
             * @param {string|null} selection.order
             */
            bucketBy: function(selection) {
                var bucketBy = _.clone(this.controlModel.get('bucketBy'));

                if(selection.column !== null) bucketBy.selected = selection.column;
                if(selection.order !== null) bucketBy.order = selection.order;
                this.controlModel.set({bucketBy: bucketBy});

                this.fetchFiltered();
            },
            /**
             * Update rows filtering by column matching value(s)
             * @param {string} column
             * @param {Array} values
             */
            filterBy: function(column, values) {
                var filterBy = _.clone(this.controlModel.get('filterBy'));

                filterBy[column] = values;
                this.controlModel.set({filterBy: filterBy});

                this.fetchFiltered();
            },
            /**
             * Update rows sorting, setting column and/or toggling order as
             * appropriate
             * @param {string} column
             */
            sortBy: function(column) {
                this.controlModel.updateSort(column);

                this.fetchFiltered();
            },
            /**
             * Reset sort/search/bucket/filter and fetch rows
             */
            resetFilters: function() {
                this.controlModel.reset();
                this.fetchFiltered();
            },
            /**
             * Fetch rows for pageNum
             * @param {number} pageNum
             */
            getPage: function(pageNum) {
                this.controlModel.set({currentPage: pageNum});
                this.fetchFiltered();
            },
            /**
             * Fetch rows for previous page
             */
            prevPage: function() {
                var curPage = this.controlModel.get('currentPage');

                if(curPage > 1) {
                    this.getPage(parseInt(curPage) - 1);
                }
            },
            /**
             * Fetch rows for next page
             */
            nextPage: function() {
                var curPage, lastPage;

                curPage = this.controlModel.get('currentPage');
                lastPage = this.controlModel.get('numPages');

                if(curPage < lastPage) {
                    this.getPage(parseInt(curPage) + 1);
                }
            },
            /**
             * If current page becomes invalid, push the user to first page
             */
            checkPageBounds: function() {
                if(this.controlModel.get('currentPage') > this.controlModel.get('numPages')) {
                    this.getPage(1);
                }
            }
        };

        c.meta = parent.model.prototype.meta;

        return collection.extend(c);
    };

    /**
     * Backbone: Views
     */

    /**
     * Build a multiview row view class extending the base multiview row view
     * @param {Object} extendWith
     * @returns {Function|*}
     */
    mtv.multiviewRowFactory = function(extendWith) {
        var mv, events;
        extendWith || (extendWith = {});

        /**
         * Backbone view for multiview row
         * @typedef {Object} MultiviewRowView
         */
        mtv.MultiviewRowView = BizzyBone.BaseView.extend({
            /**
             * @param options
             * @returns {MultiviewRowView}
             */
            initialize: function(options) {
                return BizzyBone.BaseView.prototype.initialize.call(this, options);
            },
            /**
             * @returns {MultiviewRowView}
             */
            render: function() {
                var thisView, template, columns;
                thisView = this;
                template = Handlebars.templates.multiviewRow;
                columns = this.model.collection.controlModel.get('columns');

                this.renderTemplate(null, template);

                _.each(columns, function(column) {
                    if(column.enabled) thisView.renderColumn(column);
                });

                return this;
            },
            /**
             * @param {Object} column
             * @returns {MultiviewRowView}
             */
            renderColumn: function(column) {
                var template, vars;
                vars = {
                    column: column,
                    meta: this.model.meta
                };

                if(_.isString(column.template)) {
                    template = Handlebars.templates[column.template];
                    vars.row = this.model.attributes;
                }
                else {
                    if(column.isLink) {
                        template = Handlebars.templates.multivewColLink;
                        vars.url = this.model.get('url');

                        // If no URL specified, default to resource path from urlRoot and ID
                        // HACK: convert api path to path path - note that this will break if the api path
                        // format changes...
                        if(!vars.url) vars.url = this.model.urlRoot.replace('/api/v1', '') + '/' + this.model.id;
                    }
                    else {
                        template = Handlebars.templates.multivewColDefault;
                    }

                    if(column.key !== null) vars.val = this.model.extractColumnValue(column.key);
                    else vars.val = null;
                }

                this.$el.append(template(vars));

                return this;
            },
            events: {}
        });

        if(_.has(extendWith, 'events')) {
            events = _.extend(_.clone(mtv.MultiviewRowView.prototype.events), extendWith.events);
            extendWith.events = events;
        }

        mv = function(options) {
            mtv.MultiviewRowView.apply(this, arguments);
        };

        mv.prototype = Object.create(mtv.MultiviewRowView.prototype);
        _.extend(mv.prototype, extendWith);

        return mv;
    };

    /**
     * Build a multiview main view class extending the base multiview main view
     * and using rowViewClass as the row view
     * @param {function} rowViewClass
     * @param {Object} extendWith
     * @returns {Function|*}
     */
    mtv.multiviewMainFactory = function(rowViewClass, extendWith) {
        var mv, events;
        extendWith || (extendWith = {});

        /**
         * Backbone view for multiview main (consolidated with control panel)
         * @typdef {Object} MultiviewMainView
         */
        mtv.MultiviewMainView = BizzyBone.BaseView.extend({
            /**
             * @param options
             * @param options.isBlock
             * @param options.title
             * @returns {MultiviewMainView}
             */
            initialize: function(options) {
                if(_.has(options, 'collection')) this.controlModel = options.collection.controlModel;
                if(_.has(options, 'isBlock')) this.isBlock = options.isBlock;
                if(_.has(options, 'title')) this.title = options.title;

                this.searchTimers = {};

                this.initRowViews();

                // Listen to collection reset event
                this.listenTo(this.collection, 'reset', this.eventRowsLoaded);

                // Listen to controlModel change:bucketBy, change:currentPage, change:numPages, reset and resort events
                this.listenTo(this.controlModel, 'change:bucketBy', this.eventBucketsChanged);
                this.listenTo(this.controlModel, 'change:currentPage', this.eventPagingChanged);
                this.listenTo(this.controlModel, 'change:numPages', this.eventPagingChanged);
                this.listenTo(this.controlModel, 'reset', this.eventFiltersReset);
                this.listenTo(this.controlModel, 'resort', this.eventResorted);

                return BizzyBone.BaseView.prototype.initialize.call(this, options);
            },
            /**
             * @returns {MultiviewMainView}
             */
            render: function() {
                var template;
                template = Handlebars.templates.multiviewMain;

                this.$el.html(template({
                    isBlock: this.isBlock,
                    title: this.title,
                    meta: this.collection.meta
                }));

                this.renderFilterControls();
                this.renderPageControls();
                this.renderColumnHeaders();
                this.renderRows();

                return this;
            },
            /**
             * Render search/bucket/filter controls
             * @returns {MultiviewMainView}
             */
            renderFilterControls: function() {
                var template, columnInfo;
                template = Handlebars.templates.multiviewFilterControls;
                columnInfo = this.controlModel.getColumnInfo();

                this.$el.find('#filter-controls').html(template({
                    searchBy: this.controlModel.get('searchBy'),
                    bucketBy: this.controlModel.get('bucketBy'),
                    filterBy: this.controlModel.get('filterBy'),
                    searchInfo: columnInfo.search,
                    bucketInfo: columnInfo.bucket,
                    filterInfo: columnInfo.filter,
                    meta: this.collection.meta
                }));

                return this;
            },
            /**
             * Render page controls
             * @returns {MultiviewMainView}
             */
            renderPageControls: function() {
                var template, curPage, numPages, firstPage, lastPage;
                template = Handlebars.templates.multiviewPageControls;
                curPage = this.controlModel.get('currentPage');
                numPages = this.controlModel.get('numPages');

                firstPage = (curPage == 1);
                lastPage = (curPage == numPages);

                this.$el.find('#page-controls').html(template({
                    curPage: curPage,
                    numPages: numPages,
                    firstPage: firstPage,
                    lastPage: lastPage,
                    meta: this.collection.meta
                }));

                return this;
            },
            /**
             * Render column headers
             * @returns {MultiviewMainView}
             */
            renderColumnHeaders: function() {
                var template = Handlebars.templates.multiviewColumnHeaders;

                this.$el.find('#multiview-list-header').html(template({
                    columns: this.controlModel.get('columns'),
                    sort: this.controlModel.get('sort')
                }));

                return this;
            },
            /**
             * Render row list
             * @returns {MultiviewMainView}
             */
            renderRows: function() {
                var thisView, bucketTemplate, multiviewList, bucketBy, buckets, bucketedViews;
                thisView = this;
                bucketTemplate = Handlebars.templates.multiviewBucketHeader;
                multiviewList = this.$el.find('#multiview-list');
                bucketBy = this.controlModel.get('bucketBy');

                multiviewList.empty();

                if(!bucketBy.selected.length) {
                    // Regular render (no buckets)
                    _.each(this.rowViews, function(rowView) {
                        multiviewList.append(rowView.render().$el);
                    });
                }
                else {
                    // Render with buckets
                    buckets = this.collection.getBuckets(bucketBy.selected, bucketBy.order);
                    bucketedViews = this.viewsByBucket(bucketBy.selected, buckets);

                    _.each(buckets, function(bucketVal) {
                        multiviewList.append(bucketTemplate({
                            numColumns: thisView.controlModel.get('columns').length,
                            bucketVal: bucketVal
                        }));

                        _.each(bucketedViews[bucketVal], function(rowView) {
                            multiviewList.append(rowView.render().$el);
                        });
                    });
                }

                return this;
            },
            /**
             * Initialize or reset row subviews
             */
            initRowViews: function() {
                var thisView = this;

                _.each(this.rowViews, function(rowView) {
                    rowView.remove();
                });

                this.rowViews = [];

                _.each(this.collection.models, function(rowModel) {
                    thisView.rowViews.push(new rowViewClass({model: rowModel}));
                });
            },
            /**
             * Divide views into buckets - pivoting on bucketBy with possibilities
             * specified by buckets
             * @param {string} bucketCol
             * @param {Array.<string>} buckets
             * @returns {Array.<MultiviewRowView>|null}
             */
            viewsByBucket: function(bucketCol, buckets) {
                var bucketedViews = {};

                _.each(this.rowViews, function(view) {
                    var viewVal = view.model.extractColumnValue(bucketCol);

                    _.each(buckets, function(bucketVal) {
                        if(!_.isArray(bucketedViews[bucketVal])) bucketedViews[bucketVal] = [];
                        if(Util.parseWhatever(bucketVal) == viewVal) bucketedViews[bucketVal].push(view);
                    });
                });

                return bucketedViews;
            },
            /**
             * Helper to add a new row view (while hiding base view internals
             * and magic HTML IDs)
             * @param {Object} model
             * @returns {FeeTagCategoryMultiviewRow}
             */
            addRowView: function(model) {
                var newView = new FeeTagCategoryMultiviewRow({model: model});
                this.rowViews.push(newView);
                newView.render().$el.appendTo($('#multiview-list')).hide().fadeIn(500);

                return newView;
            },
            events: {
                "keydown .search-input": "eventSearchStartTyping",
                "keyup .search-input": "eventSearchStopTyping",
                "click .bucket-select > li": "eventBucketSelect",
                "change .filter-select": "eventFilterSelect",
                "click #btn-reset-filter": "eventResetFilters",
                "click .btn-page > a": "eventGetPage",
                "click #btn-prev-page": "eventPrevPage",
                "click #btn-next-page": "eventNextPage",
                "click th.column-label": "eventChangeSort"
            },
            /**
             * Event handler for search field keydown
             * @param {Object} e
             */
            eventSearchStartTyping: function(e) {
                var column = $(e.target).data('column');

                clearTimeout(this.searchTimers[column]);
            },
            /**
             * Event handler for search field keyup or blur
             * @param {Object} e
             */
            eventSearchStopTyping: function(e) {
                var thisView, target, column;
                thisView = this;
                target = $(e.target);
                column = target.data('column');

                clearTimeout(this.searchTimers[column]);
                this.searchTimers[column] = setTimeout(function() {
                    thisView.eventDoSearch(column, target);
                }, 500);
            },
            /**
             * Event handler for search timer timeout
             * @param {string} column
             * @param {Object} target
             */
            eventDoSearch: function(column, target) {
                this.collection.searchBy(column, target.val());
            },
            /**
             * Event hander for filter section 'Reset' button
             * @param {Object} e
             */
            eventResetFilters: function(e) {
                this.collection.resetFilters();
            },
            /**
             * Event handler for bucket select
             * @param e
             */
            eventBucketSelect: function(e) {
                var target, selection;
                target = $(e.target);
                selection = {};

                if(target.hasClass('bucket-option')) selection.column = target.data('column');
                else selection.column = null;

                if(target.hasClass('bucket-order')) selection.order = target.data('order');
                else selection.order = null;

                this.collection.bucketBy(selection);
            },
            /**
             * Event handler for filter select
             * @param {Object} e
             */
            eventFilterSelect: function(e) {
                var target, column, values;
                target = $(e.target);
                column = target.data('column');
                values = [];

                target.children('option').each(function(index) {
                    if($(this).is(':selected') && $(this).val().length) values.push($(this).val());
                });

                this.collection.filterBy(column, values);
            },
            /**
             * Event handler for select page number
             * @param {Object} e
             */
            eventGetPage: function(e) {
                var target = $(e.target);
                e.preventDefault();

                if(!target.closest('li').hasClass('active')) {
                    this.collection.getPage(parseInt(target.data('page')));
                }
            },
            /**
             * Event handler for previous page button
             * @param {Object} e
             */
            eventPrevPage: function(e) {
                e.preventDefault();

                if(!$(e.target).closest('li').hasClass('disabled')) {
                    this.collection.prevPage();
                }
            },
            /**
             * Event handler for next page button
             * @param {Object} e
             */
            eventNextPage: function(e) {
                e.preventDefault();

                if(!$(e.target).closest('li').hasClass('disabled')) {
                    this.collection.nextPage();
                }
            },
            /**
             * Event handler for select/toggle sort column (click on column header)
             * @param {Object} e
             */
            eventChangeSort: function(e) {
                var col, nextSort;
                col = $(e.target).data('column');

                if(this.controlModel.canSortOn(col)) {
                    this.collection.sortBy(col);
                }
            },
            /**
             * Event handler for collection reset
             * @param {Object} collection
             */
            eventRowsLoaded: function(collection) {
                this.initRowViews();
                this.renderRows();
            },
            /**
             * Event handler for current page or number of pages changed
             * @param {MultiviewControlModel} model
             */
            eventPagingChanged: function(model) {
                this.collection.checkPageBounds();
                this.renderPageControls();
            },
            /**
             * Event handler for control model bucketBy changed
             * @param {MultiviewControlModel} model
             */
            eventBucketsChanged: function(model) {
                this.renderFilterControls();
            },
            /**
             * Event handler for control model reset (custom event)
             * @param {MultiviewControlModel} model
             */
            eventFiltersReset: function(model) {
                this.renderFilterControls();
            },
            /**
             * Event handler for control model resort (custom event)
             * @param {MultiviewControlModel} model
             */
            eventResorted: function(model) {
                this.renderColumnHeaders();
            }
        });

        if(_.has(extendWith, 'events')) {
            events = _.extend(_.clone(mtv.MultiviewMainView.prototype.events), extendWith.events);
            extendWith.events = events;
        }

        mv = function(options) {
            mtv.MultiviewMainView.apply(this, arguments);
        };

        mv.prototype = Object.create(mtv.MultiviewMainView.prototype);

        _.extend(mv.prototype, extendWith);

        return mv;
    };

    return mtv;

})();