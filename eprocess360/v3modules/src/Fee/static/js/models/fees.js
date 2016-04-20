/**
 * Fees: Models
 */

/**
 * Backbone model for fee
 * @typedef {Object} FeeModel
 */
var FeeModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath,
    idAttribute: 'idFee',
    defaults: {
        idFeeTemplate: 0,
        idProject: 0,
        notes: '',
        quantity: 0,
        feeUnitPrice: 0,
        total: 0,
        overrides: {
            totalOverride: false,
            unitPriceOverride: false
        },
        variables: {},
        feeTemplate: {}
    },
    dontSave: [
        'feeTemplate'
    ],
    /**
     * Call the preview API to recalculate info for this model without affecting the DB
     * @returns {FeeModel}
     */
    preview: function() {
        var thisModel = this;

        this.save(null, {
            wait: true,
            method: 'POST',
            url: this.urlRoot + '/preview',
            success: function(model, response, options) {
                thisModel.trigger('preview', model);
            }
        });
        return this;
    }
});

/**
 * Backbone model for fee receipt
 * @typedef {Object} ReceiptModel
 */
var ReceiptModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath + '/receipts',
    idAttribute: 'idReceipt',
    defaults: {
        paid: 0,
        paymentMethod: '',
        receiptNumber: 0,
        idUser: 0,
        userName: '',
        receiptNotes: '',
        datePaid: null,
        status: {
            isVoid: false
        }
    },
    /**
     * POST receipt data plus {[idFee]:[paymentAmount]...} payment data to payment API path
     * @param {Object} feePayments
     * @param {Object} options
     */
    processPayment: function(feePayments, options) {
        var toSave = _.clone(this.attributes);
        toSave.feePayments = feePayments;
        options = _.extend({
            method: 'POST',
            url: hbInitData().meta.Fee.apiPath + '/process'
        }, options);

        this.save(toSave, options);
    }
});

/**
 * Backbone model for fee template
 * @typedef {Object} FeeTemplateModel
 */
var FeeTemplateModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath,
    idAttribute: 'idFeeTemplate',
    defaults: {
        title: '',
        description: '',
        minimumValue: 0,
        fixedAmount: 0,
        unitPrice: 0,
        unitVariable: '',
        matrixFormula: '',
        formula: '',
        calculationMethod: {
            isFixed: false,
            isFormula: false,
            isMatrix: false,
            isUnit: false
        },
        status: {
            isActive: false
        },
        idFeeType: 0,
        feeSchedule: 0,
        projectCount: 0,
        version: '',
        feeTags: [],
        feeMatrices: []
    },
    dontSave: [
        'projectCount',
        'version'
    ],
    /**
     * Force a POST to the base URL even if the model isn't new (i.e. in case
     * of a copy)
     * @param attributes
     * @param options
     * @returns {Object}
     */
    forcePost: function(attributes, options) {
        options = _.extend({
            method: 'POST',
            url: this.urlRoot
        }, options);

        return this.save(attributes, options);
    }
});

// Add multiview capability to FeeTemplateModel
FeeTemplateModel = Multiview.modelMultiviewable(FeeTemplateModel, hbInitData().meta.Fee);

/**
 * Backbone model for fee type
 * @typedef {Object} FeeTypeModel
 */
var FeeTypeModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath + '/types',
    idAttribute: 'idFeeType',
    defaults: {
        idController: 0,
        feeTypeTitle: '',
        feeTypeFlags: {
            isOpen: false,
            isPayable: false
        }
    },
    /**
     * @param models
     * @param options
     * @returns {FeeTypeModel}
     */
    initialize: function(models, options) {
        return BizzyBone.BaseModel.prototype.initialize.call(this, models, options);
    },
    /**
     * Give a fee model a reference to this fee type
     * @param {FeeModel} feeModel
     */
    addFee: function(feeModel) {
        feeModel.feeType = this;
    },
    /**
     * If fee model has a reference to this fee type, remove it
     * @param {FeeModel} feeModel
     */
    removeFee: function(feeModel) {
        if(feeModel.feeType == this) {
            feeModel.feeType = null;
        }
    }
});

// Add multiview capability to FeeTypeModel
FeeTypeModel = Multiview.modelMultiviewable(FeeTypeModel, hbInitData().meta.Fee);

/**
 * Backbone model for fee tags
 * @typedef {Object} FeeTagModel
 */
var FeeTagModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath + '/tags',
    idAttribute: 'idFeeTag',
    defaults: {
        idFeeTagCategory: 0,
        idController: 0,
        feeTagValue: ''
    },
    /**
     * Special function to save to project fee schedule route
     * @param idFeeTag
     * @param options
     * @returns {*|Object}
     */
    saveProjectFeeSchedule: function(idFeeTag, options) {
        idFeeTag || (idFeeTag = this.get('idFeeTag'));
        options = _.extend({method: 'POST', url: hbInitData().meta.Fee.apiPath + '/schedule'}, options);

        return this.save({idFeeTag: idFeeTag}, options);
    }
});

// Add multiview capability to FeeTagModel
FeeTagModel = Multiview.modelMultiviewable(FeeTagModel, hbInitData().meta.Fee);

/**
 * Backbone model for fee tag categories
 * TODO: change name to FeeTagCategoryModel
 * @typdef {Object} FeeTagCategory
 */
var FeeTagCategory = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath + '/categories',
    idAttribute: 'idFeeTagCategory',
    defaults: {
        idController: 0,
        title: '',
        status: {
            isFeeSchedule: false
        }
    }
});

// Add multiview capability to FeeTagCategory(Model)
FeeTagCategory = Multiview.modelMultiviewable(FeeTagCategory, hbInitData().meta.Fee);

/**
 * Backbone model for a matrix row
 * @typedef {Object} FeeMatrixRowModel
 */
var FeeMatrixRowModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Fee.apiPath + '/tags',
    idAttribute: 'idFeeMatrix',
    defaults: {
        idFeeTemplate: 0,
        startingValue: 0,
        baseFee: 0,
        increment: 0,
        incrementFee: 0,
        order: 0
    }
});

/**
 * A collection of FeeModels
 * @typedef {Object} FeeList
 */
var FeeList = BizzyBone.BaseCollection.extend({
    model: FeeModel,
    url: hbInitData().meta.Fee.apiPath,
    /**
     * @param models
     * @param options
     * @param options.feeTypeList
     * @returns {*}
     */
    initialize: function(models, options) {
        if(_.has(options, 'feeTypeList')) this.feeTypeList = options.feeTypeList;

        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    },
    /**
     * @param models
     * @param options
     * @returns {*}
     */
    set: function(models, options) {
        var thisCollection, result, processedModels, rawOptions;
        thisCollection = this;

        // Make a copy of options so we can check the silence flag even after *temporarily* suppressing the 'add' event
        if(options.add) {
            rawOptions = _.clone(options);
            options.silent = true;
        }

        result = Backbone.Collection.prototype.set.call(this, models, options);
        processedModels = _.isArray(result) ? result : [result];

        _.each(processedModels, function(feeModel) {
            thisCollection.sortFee(feeModel);

            // Weird implementation postpones add event until after fees have been given types
            if(options.add && !rawOptions.silent) {
                // Making a note here that Backbone internal impl seems to call this trigger on each *model*. So far
                // calling it on the collection seems to work better but just in case...
                //feeModel.trigger('add', feeModel, this, rawOptions);
                thisCollection.trigger('add', feeModel, this, rawOptions);
            }
        });

        return result;
    },
    /**
     * Find the corresponding FeeTypeModel for this FeeModel
     * @param {FeeModel} feeModel
     */
    sortFee: function(feeModel) {
        if(this.feeTypeList) {
            this.feeTypeList.sortFee(feeModel);
        }
    },
    /**
     * Get fee models sorted into sub-lists by type
     * @returns {Object}
     */
    getFeesByType: function() {
        var thisCollection, feesByType = {};
        thisCollection = this;

        _.each(this.feeTypeList.models, function(feeTypeModel) {
            feesByType[feeTypeModel.get('idFeeType')] = [];
        });

        _.each(_.keys(feesByType), function(idFeeType) {
            _.each(thisCollection.models, function(feeModel) {
                if(feeModel.get('feeTemplate').idFeeType == idFeeType) {
                    feesByType[idFeeType].push(feeModel);
                }
            });
        });

        return feesByType;
    },
    /**
     * Get the total owed balance
     * @param {boolean} depositsOnly
     * @returns {BigNumber}
     */
    getTotalBalance: function(depositsOnly) {
        depositsOnly = (typeof depositsOnly !== 'undefined' ? depositsOnly : false);

        return _.reduce(this.models, function(sum, feeModel) {
            if(feeModel.feeType && feeModel.feeType.get('feeTypeFlags').isPayable && (!depositsOnly || feeModel.feeType.get('feeTypeFlags').isDeposit)) {
                return sum.add(feeModel.get('balance'));
            }
            else {
                return sum;
            }
        }, new BigNumber(0));
    },
    /**
     * Get the sum of deposits allocatable
     * @returns {BigNumber}
     */
    getAllocatableTotal: function() {
        return _.reduce(this.models, function(total, feeModel) {
            // For now don't *really* need to check specifically if the type is deposit - do we?
            //if(feeModel.feeType && feeModel.feeType.get('feeTypeFlags').isDeposit) {
            return total.plus((feeModel.get('allocatable') ? feeModel.get('allocatable') : 0));
        }, new BigNumber(0));
    },
    /**
     * Check if there is an unpaid deposit in the collection
     * @returns {boolean}
     */
    hasUnpaidDeposit: function() {
        var curModel, i;

        for(i = 0; i < this.models.length; i++) {
            curModel = this.models[i];
            if(curModel.feeType && curModel.feeType.get('feeTypeFlags').isDeposit && (curModel.get('paid') < curModel.get('total'))) {
                return true;
            }
        }

        return false;
    }
});

/**
 * A collection of ReceiptModels
 * @typedef {Object} ReceiptList
 */
var ReceiptList = BizzyBone.BaseCollection.extend({
    model: ReceiptModel,
    url: hbInitData().meta.Fee.apiPath + '/receipts',
    /**
     * Get the sum of paid amounts for all receipts in this collection
     * @returns {BigNumber}
     */
    sumPaid: function() {
        var sum = new BigNumber(0);

        _.each(this.models, function(receiptModel) {
            if(!receiptModel.get('status').isVoid) {
                sum = sum.plus(receiptModel.get('paid'));
            }
        });

        return sum;
    }
});

/**
 * A collection of FeeTemplateModels
 * @typedef {Object} FeeTemplateList
 */
var FeeTemplateList = BizzyBone.BaseCollection.extend({
    model: FeeTemplateModel,
    url: hbInitData().meta.Fee.apiPath
});

// Add multiview capability to FeeTemplateList
FeeTemplateList = Multiview.collectionMultiviewable(FeeTemplateList);

/**
 * A collection of FeeTypeModels
 * @typedef {Object} FeeTypeList
 */
var FeeTypeList = BizzyBone.BaseCollection.extend({
    model: FeeTypeModel,
    url: hbInitData().meta.Fee.apiPath + '/types',
    /**
     * @param models
     * @param options
     * @returns {*}
     */
    initialize: function(models, options) {
        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    },
    /**
     * Find the corresponding FeeTypeModel from this collection for the specified FeeModel
     * @param {FeeModel} feeModel
     */
    sortFee: function(feeModel) {
        var idFeeType = feeModel.get('feeTemplate').idFeeType;

        // TODO: can probably do this just with collection.get(idFeeType) dunno what I was thinking
        _.each(this.models, function(feeTypeModel) {
            if(feeTypeModel.get('idFeeType') == idFeeType) {
                feeTypeModel.addFee(feeModel);
            }
        });
    }
});

// Add multiview capability to FeeTypeList
FeeTypeList = Multiview.collectionMultiviewable(FeeTypeList);

/**
 * A collection of FeeTags
 * @typedef {Object} FeeTagList
 */
var FeeTagList = BizzyBone.BaseCollection.extend({
    model: FeeTagModel,
    url: hbInitData().meta.Fee.apiPath + '/tags',
    /**
     * Convert tag model data from this collection to an array format
     * @returns {Array.<Object>}
     */
    toArray: function() {
        return _.pluck(this.models, 'attributes');
    },
    /**
     * Find all models in the collection with the specified value
     * @param value
     * @returns {Array.<FeeTagModel>|FeeTagModel|null}
     */
    getByValue: function(value) {
        var results = [];

        _.each(this.models, function(tagModel) {
            if(tagModel.get('feeTagValue') == value) {
                results.push(tagModel);
            }
        });

        if(!results.length) return null;
        if(results.length === 1) return results[0];
        return results;
    },
    /**
     * Find all models in the collection with the specified idFeeTagCategory
     * @param {number} idFeeTagCategory
     * @returns {Array}
     */
    getByCategory: function(idFeeTagCategory) {
        var results = [];

        _.each(this.models, function(tagModel) {
            if(tagModel.get('idFeeTagCategory') == idFeeTagCategory) {
                results.push(tagModel);
            }
        });

        return results;
    }
});

// Add multiview capability to FeeTagList
FeeTagList = Multiview.collectionMultiviewable(FeeTagList);

/**
 * A list of fee tag categories
 * @typedef {Object} FeeTagCategoryList
 */
var FeeTagCategoryList = BizzyBone.BaseCollection.extend({
    model: FeeTagCategory,
    url: hbInitData().meta.Fee.apiPath + '/categories',
    /**
     * Given a FeeTagModel instance, find the FeeTagCategory corresponding to
     * its idFeeTagCategory (if it's in this collection) and add a reference to
     * it to the model.
     * @param {FeeTagModel} feeTagModel
     */
    sortTag: function(feeTagModel) {
        var idFeeTag = feeTagModel.get('idFeeTag');
        feeTagModel.category = this.get(idFeeTag);
    }
});

// Add multiview capability to FeeTagCategoryList
FeeTagCategoryList = Multiview.collectionMultiviewable(FeeTagCategoryList);

/**
 * A list of matrix rows
 * @typedef {Object} FeeMatrixList
 */
var FeeMatrixList = BizzyBone.BaseCollection.extend({
    model: FeeMatrixRowModel,
    comparator: 'order',
    /**
     * @param models
     * @param options
     * @returns {FeeMatrixList}
     */
    initialize: function(models, options) {
        return BizzyBone.BaseCollection.prototype.initialize.call(this, models, options);
    },
    /**
     * @param models
     * @param options
     * @returns {FeeMatrixRowModel|Array.<FeeMatrixRowModel>}
     */
    set: function(models, options) {
        var results;

        results = BizzyBone.BaseCollection.prototype.set.call(this, models, options);

        //this.sort();

        return results;
    },
    /**
     * @param models
     * @param options
     * @returns {FeeMatrixRowModel|Array.<FeeMatrixRowModel>}
     */
    remove: function(models, options) {
        var results;
        rseults = BizzyBone.BaseCollection.prototype.remove.call(this, models, options);

        //this.sort();

        return results;
    },
    /**
     * Update the order of existing models when adding a model
     * @param {number} index
     */
    addUpdateOrder: function(index) {
        _.each(this.models, function(matrixModel) {
            var curOrder = matrixModel.get('order');
            if(curOrder >= index) {
                matrixModel.set({order: curOrder + 1}, {silent: true});
            }
        });
    },
    /**
     * Update the order of existing models when removing a model
     * @param {number} index
     */
    removeUpdateOrder: function(index) {
        _.each(this.models, function(matrixModel) {
            var curOrder = matrixModel.get('order');
            if(curOrder > index) {
                matrixModel.set({order: curOrder - 1}, {silent: true});
            }
        });
    },
    /**
     * Reconstruct the 'order' attribute of the models in this collection to reflect
     * their actual order in the list
     */
    refreshOrder: function() {
        var curNum = 0;

        _.each(this.models, function (matrixModel) {
            matrixModel.set({order: curNum++}, {silent: true})
        });

        this.sort();
    },
    /**
     * Extract all matrix values in a simple array format
     * @returns {Array}
     */
    toArray: function() {
        if(!this.length) {
            return [];
        }

        return _.pluck(this.models, 'attributes');
    },
    /**
     * Calculate the output of the matrix function for a test input
     * @param {BigNumber|number|string} input
     * @param {number} lastRow
     * @returns {BigNumber}
     */
    calcResult: function(input, lastRow) {
        var result, curRow, startVal, baseFee, inc, incFee, incMult, i;
        input = input instanceof BigNumber ? input : new BigNumber(input);
        lastRow = _.isNumber(lastRow) ? lastRow : this.models.length - 1;
        result = new BigNumber(0);

        for(i = lastRow; i >= 0; i--) {
            curRow = this.models[i];
            startVal = new BigNumber(curRow.get('startingValue'));

            if(input.gte(curRow.get('startingValue'))) {
                baseFee = new BigNumber(curRow.get('baseFee'));
                inc = new BigNumber(curRow.get('increment'));
                incFee = new BigNumber(curRow.get('incrementFee'));
                result = new BigNumber(baseFee);
                incMult = input.minus(startVal).dividedBy(inc).ceil();
                result = result.add(incMult.times(incFee));
                break;
            }
        }

        return result;
    },
    /**
     * Check if this is a valid matrix
     * @returns {boolean}
     */
    validateMatrix: function() {
        var isValid, curRow, lastStart, i;
        lastStart = new BigNumber(-1);
        isValid = true;

        for(i = 0; i < this.models.length; i++) {
            curRow = this.models[i];

            if(lastStart.gt(curRow.get('startingValue'))) {
                isValid = false;
                break;
            }

            _.each(['startingValue', 'baseFee', 'increment', 'incrementFee'], function(checkWhat) {
                var checkVal = new BigNumber(curRow.get(checkWhat));

                if(checkVal.lt(0) || !checkVal.isFinite()) {
                    isValid = false;
                }
            });

            if(!isValid) break;

            lastStart = new BigNumber(curRow.get('startingValue'));
        }

        return isValid;
    },
    /**
     * Clear the matrix. Triggers custom 'matrixclear' event
     */
    clearMatrix: function() {
        this.reset(null);
        this.trigger('matrixclear', this);
    }
});
