/**
 * Fees: Views
 */

/**
 * Backbone view for fees main
 * @typedef {Object} FeesMainView
 */
var FeesMainView = BizzyBone.BaseView.extend({
    /**
     * @param {Object} options
     * @returns {FeesMainView}
     */
    initialize: function(options) {
        var thisView = this;

        if(_.has(options, 'feeTypeList')) this.feeTypeList = options.feeTypeList;
        if(_.has(options, 'receiptList')) this.receiptList = options.receiptList;
        if(_.has(options, 'feeScheduleList')) this.feeScheduleList = options.feeScheduleList;

        this.feeListViews = {};
        this.receiptListView = new ReceiptListMainView({collection: this.receiptList});

        _.each(this.collection.getFeesByType(), function(fees, idFeeType) {
            thisView.feeListViews[idFeeType] = new FeeListMainView({feeType: thisView.feeTypeList.get(idFeeType), myFees: fees});
        });

        // Listen to fee list add, reset and update events
        this.listenTo(this.collection, 'add', this.eventFeeAdded);
        this.listenTo(this.collection, 'reset', this.eventFeesReset);
        this.listenTo(this.collection, 'update', this.eventFeesUpdated);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeesMainView}
     */
    render: function() {
        var thisView, template;
        thisView = this;
        template = Handlebars.templates.feesMain;

        this.$el.html(template({
            meta: hbInitData().meta.Fee
        }));

        this.setFeeSchedule(this.idFeeSchedule || hbInitData().meta.Fee.idFeeSchedule);
        this.updatePayButton();
        this.applyPermissions();

        _.each(this.feeListViews, function(listView) {
            thisView.$el.append(listView.render().$el);
        });

        this.$el.append(this.receiptListView.render().$el);

        return this;
    },
    /**
     * Update the amount displayed on and enabled status of the payment button
     * @returns {FeesMainView}
     */
    updatePayButton: function() {
        var theButton, totalBalance, hasUnpaidDeposit;
        theButton = this.$el.find('#btn-pay-fees');
        hasUnpaidDeposit = this.collection.hasUnpaidDeposit();
        totalBalance = this.collection.getTotalBalance(hasUnpaidDeposit);

        theButton.find('#pay-btn-balance').text(feesFormatMoney(totalBalance, false));

        if(totalBalance.eq(0)) theButton.addClass('disabled');
        else theButton.removeClass('disabled');

        return this;
    },
    /**
     * Recreate fee item views from up-to-date collection and re-render
     * @returns {FeesMainView}
     */
    rebuildFeeViews: function() {
        var thisView = this;

        _.each(this.feeListViews, function(listView) {
            listView.remove();
        });

        this.feeListViews = {};

        _.each(this.collection.getFeesByType(), function(fees, idFeeType) {
            thisView.feeListViews[idFeeType] = new FeeListMainView({feeType: thisView.feeTypeList.get(idFeeType), myFees: fees});
        });

        this.render();

        return this;
    },
    /**
     * Update current fee schedule ID and text
     * @param idFeeSchedule
     */
    setFeeSchedule: function(idFeeSchedule) {
        this.idFeeSchedule = idFeeSchedule;
        $('#btn-edit-feeschedule').text(feeScheduleList.get(idFeeSchedule).get('feeTagValue'));
    },
    /**
     * @param {boolean} recalculate
     * @returns {FeesMainView}
     */
    refreshFees: function(recalculate) {
        var thisView = this;

        recalculate = (typeof recalculate !== 'undefined' ? recalculate : false);

        this.collection.fetch({
            reset: true,
            data: {recalculate: recalculate},
            success: function(model, response, options) {
                thisView.updatePayButton();
            },
            error: function (model, response, options) {
                Util.showError(response.responseJSON);
            }
        });
        return this;
    },
    /**
     * @returns {FeesMainView}
     */
    refreshReceipts: function() {
        this.receiptList.fetch({reset: true});
        return this;
    },
    events: {
        "click #btn-pay-fees": "eventPayFees",
        "click #btn-edit-feeschedule": "eventEditFeeSchedule",
        "click .fee-menu a": "eventMenuItem"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for 'Pay' button
     * @param {Object} e
     */
    eventPayFees: function(e) {
        if(!$(e.target).hasClass('disabled')) {
            modalPayFees.show(this.collection);
        }
    },
    /**
     * Event handler for click edit fee schedule link
     * @param {Object} e
     */
    eventEditFeeSchedule: function(e) {
        e.preventDefault();
        modalFeeSchedule.show(this.idFeeSchedule);
    },
    /**
     * Event handler for 'Add New' menu item
     * @param {Object} e
     */
    eventNew: function(e) {
        var newFee = new FeeModel();
        modalEditFees.show(newFee, this.collection);
    },
    /**
     * Event handler for 'Recalculate' menu item
     * @param {Object} e
     */
    eventRecalculate: function(e) {
        this.refreshFees(true);
    },
    /**
     * Event dispatch handler for fee menu item
     * @param {Object} e
     */
    eventMenuItem: function(e) {
        var target = $(e.target);
        e.preventDefault();
        e.stopPropagation();

        target.closest('.dropdown').removeClass('open');

        // menu items are distinguished by data-action attributes
        switch(target.data('action')) {
            case 'new':
                this.eventNew(e);
                break;
            case 'recalculate':
                this.eventRecalculate(e);
                break;
        }
    },
    /**
     * Event handler for fee add event
     * @param {FeeModel} model
     */
    eventFeeAdded: function(model) {
        var feeTemplate = model.get('feeTemplate');

        if(feeTemplate && feeTemplate.idFeeType) {
            this.feeListViews[feeTemplate.idFeeType].addFee(model);
        }

        this.updatePayButton();
    },
    /**
     * Event handler for fee list reset event
     * @param {FeeList} collection
     */
    eventFeesReset: function(collection) {
        this.rebuildFeeViews();
    },
    /**
     * Event handler for fee list update event
     * @param {FeeList} collection
     * @param {FeeModel} model
     */
    eventFeesUpdated: function(collection, model) {
        collection.sortFee(model);
        this.rebuildFeeViews();
    }
});

/**
 * Backbone view for fee list main
 * @typedef {Object} FeeListMainView
 */
var FeeListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @param options.myFees
     * @returns {FeeListMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        if(_.has(options, 'feeType')) this.feeType = options.feeType;
        if(_.has(options, 'myFees')) this.myFees = options.myFees;

        _.each(this.myFees, function(feeModel) {
            thisView.itemViews.push(new FeeListItemView({model: feeModel}));
        });

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeListMainView}
     */
    render: function() {
        var template, listElem, hasFees, total, paid, balance;
        hasFees = this.myFees.length;
        total = new BigNumber(0);
        paid = new BigNumber(0);
        balance = new BigNumber(0);

        _.each(this.myFees, function(feeModel) {
            total = total.plus(feeModel.get('total'));
            if(feeModel.get('paid')) paid = paid.plus(feeModel.get('paid'));
            if(feeModel.get('balance')) balance = balance.plus(feeModel.get('balance'));
        });

        if(this.feeType.get('feeTypeFlags').isPayable) {
            template = Handlebars.templates.feeListBalanceMain;
        }
        else {
            template = Handlebars.templates.feeListTotalMain;
        }

        this.renderTemplate({
            feeList: {
                hasFees: hasFees,
                title: this.feeType.get('feeTypeTitle'),
                total: total.valueOf(),
                paid: paid.valueOf(),
                balance: balance.valueOf()
            },
            meta: hbInitData().meta.Fee
        }, template);

        this.applyPermissions();

        listElem = this.$el.find('.fee-list');

        _.each(this.itemViews, function(itemView) {
            listElem.append(itemView.render().$el);
        });

        return this;
    },
    /**
     * Add a new fee to this subview
     * @param {FeeModel} feeModel
     * @returns {FeeListMainView}
     */
    addFee: function(feeModel) {
        var newView = new FeeListItemView({model: feeModel});
        this.itemViews.push(newView);
        this.myFees.push(feeModel);
        this.render();

        return this;
    },
    events: {
        "click .panel-heading": "eventToggleExpanded"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for toggle expanded (click header)
     * @param {Object} e
     */
    eventToggleExpanded: function(e) {
        if($(e.target).hasClass('panel-heading')) {
            this.$el.find('.fee-table').slideToggle(500);
        }
    }
});

/**
 * Backbone view for fee list item
 * @typedef {Object} FeeListItemView
 */
var FeeListItemView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeListItemView}
     */
    initialize: function(options) {
        // Listen to model change and destroy eventz
        this.listenTo(this.model, 'change', this.eventFeeChanged);
        this.listenTo(this.model, 'destroy', this.eventFeeRemoved);
        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeListItemView}
     */
    render: function() {
        var template;

        if(this.model.feeType.get('feeTypeFlags').isPayable) {
            template = Handlebars.templates.feeListBalanceItem;
        }
        else {
            template = Handlebars.templates.feeListTotalItem;
        }

        this.renderTemplate({
            fee: this.model.attributes,
            meta: hbInitData().meta.Fee
        }, template);

        this.applyPermissions();

        return this;
    },
    events: {
        "click .btn-edit": "eventEdit"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for 'Edit' button
     * @param {Object} e
     */
    eventEdit: function(e) {
        modalEditFees.show(this.model);
    },
    /**
     * Event handler for model change event
     * @param {FeeModel} model
     */
    eventFeeChanged: function(model) {
        model.collection.trigger('update', model.collection, model);
    },
    /**
     * @param {FeeModel} model
     */
    eventFeeRemoved: function(model) {
        var thisView = this;
        this.$el.fadeOut(500, function() {
            thisView.remove();
        });
    }
});


/**
 * Backbone view for receipt list
 * @typedef {Object} ReceiptListMainView
 */
var ReceiptListMainView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReceiptListMainView}
     */
    initialize: function(options) {
        var thisView = this;
        this.itemViews = [];

        _.each(this.collection.models, function(receiptModel) {
            thisView.itemViews.push(new ReceiptListItemView({model: receiptModel}));
        });

        // Listen to collection add, reset, and update events
        this.listenTo(this.collection, 'add', this.eventReceiptAdded);
        this.listenTo(this.collection, 'reset', this.eventReceiptListReset);
        this.listenTo(this.collection, 'update', this.eventReceiptListUpdate);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReceiptListMainView}
     */
    render: function() {
        var template, receiptList, hasReceipts;
        template = Handlebars.templates.feeReceiptListMain;
        hasReceipts = this.collection.length;

        this.renderTemplate({
            sumPaid: this.collection.sumPaid(),
            hasReceipts: hasReceipts,
            meta: hbInitData().meta.Fee
        }, template);

        this.applyPermissions();

        receiptList = this.$el.find('.receipt-list');

        _.each(this.itemViews, function(itemView) {
            receiptList.append(itemView.render().$el);
        });

        return this;
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for receipt model added
     * @param {ReceiptModel} model
     */
    eventReceiptAdded: function(model) {
        var newView = new ReceiptListItemView({model: model});
        this.itemViews.push(newView);
        newView.render().$el.appendTo(this.$el.find('.receipt-list')).hide().fadeIn(500);
    },
    /**
     * Event handler for receipt list 'reset' event
     * @param {ReceiptList} collection
     */
    eventReceiptListReset: function(collection) {
        var thisView = this;
        this.itemViews = [];

        _.each(this.collection.models, function(receiptModel) {
            thisView.itemViews.push(new ReceiptListItemView({model: receiptModel}));
        });

        this.render();
    },
    /**
     * @param collection
     */
    eventReceiptListUpdate: function(collection) {
        this.render();
    }
});

/**
 * Backbone view for receipt list
 * @typedef {Object} ReceiptListItemView
 */
var ReceiptListItemView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {ReceiptListItemView}
     */
    initialize: function(options) {
        // Listen to model change event
        this.listenTo(this.model, 'change', this.eventReceiptChanged);

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {ReceiptListItemView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.feeReceiptListItem;

        this.renderTemplate({
            receipt: this.model.attributes,
            meta: hbInitData().meta.Fee
        }, template);

        this.applyPermissions();

        return this;
    },
    events: {
        "click .btn-void": "eventVoid"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for 'Void' button
     * @param {Object} e
     */
    eventVoid: function(e) {
        var newStatus = _.clone(this.model.get('status'));
        newStatus.isVoid = true;
        var thisView = this;

        bootbox.confirm("Are you sure you want to void this receipt?", function (result) {
            if (result) {
                thisView.model.save({status: newStatus}, {
                    wait: true,
                    success: function(model, response, options) {
                        baseView.refreshFees();
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
     * @param {ReceiptModel} model
     */
    eventReceiptChanged: function(model) {
        model.collection.trigger('update', model.collection);
    }
});

/**
 * Backbone view for fee payment modal
 * @typedef {Object} FeePaymentModalView
 */
var FeePaymentModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeePaymentModalView}
     */
    initialize: function(options) {
        this.rendered = false;
        this.isSaving = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeePaymentModalView}
     */
    render: function() {
        var template, itemTemplate, paymentTable, userName, hasUnpaidDeposit;
        template = Handlebars.templates.feeModalPay;
        itemTemplate = Handlebars.templates.feeModalPayItem;
        hasUnpaidDeposit = this.collection.hasUnpaidDeposit();

        userName = hbInitData().meta.User.name;

        this.$el.html(template({
            depositsAllocatable: this.depositsAllocatable,
            meta: hbInitData().meta.Fee
        }));

        paymentTable = this.$el.find('#fee-modal-payment-items');

        _.each(this.collection.models, function(feeModel) {
            // If there are unpaid deposits, only select those by default. Otherwise select all unpaid
            var defaultSelected = (!hasUnpaidDeposit) || feeModel.feeType.get('feeTypeFlags').isDeposit;

            if(feeModel.feeType.get('feeTypeFlags').isPayable && feeModel.get('paid') != feeModel.get('total')) {
                paymentTable.append(itemTemplate({
                    fee: feeModel.attributes,
                    defaultSelected: defaultSelected,
                    meta: hbInitData().meta.Fee
                }));
            }
        });

        this.$el.find('#fee-payment-otc-name').val(userName);

        this.updateTotal();
        this.applyPermissions();

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * Update the "total" banner to show the current sum entered in payment inputs
     */
    updateTotal: function() {
        var totalPayment = 0;

        this.$el.find('input.input-amount').each(function(index) {
            if($(this).closest('.payment-item').find('.pay-this').is(':checked')) {
                totalPayment += accounting.unformat($(this).val());
            }
        });

        this.$el.find('#fee-payment-total').text(feesFormatMoney(totalPayment));
        this.$el.find('#deposit-allocatable-after').text(feesFormatMoney(this.depositsAllocatable - totalPayment));
    },
    /**
     * Update the state of the 'Select All' checkbox
     * @returns {FeePaymentModalView}
     */
    updateSelectAll: function() {
        if(!$('.pay-this').not(':checked').length) {
            $('#payment-select-all').prop('checked', true);
        }
        else {
            $('#payment-select-all').prop('checked', false);
        }

        return this;
    },
    /**
     * @param {FeeList} feeList
     * @returns {FeePaymentModalView}
     */
    show: function(feeList) {
        this.collection = feeList;

        // Get the total of deposits available to allocate to fees
        this.depositsAllocatable = feeList.getAllocatableTotal();

        this.render();
        this.$el.children().first().modal('show');

        // If allocatable deposits exist, show that tab by default
        if(this.depositsAllocatable > 0) {
            $('#fee-payment-tabs').find('a[href="#paywith-deposits"]').tab('show');
        }
        else {
            $('#fee-payment-tabs').find('a[href="#paywith-deposits"]').attr('data-toggle', '');
            $('#fee-payment-tabs').find('a[href="#paywith-deposits"]').parent().addClass('disabled');
            $('#fee-payment-tabs').find('a[href="#paywith-deposits"]').removeAttr('href');
        }

        this.updateSelectAll();

        return this;
    },
    /**
     * @returns {FeePaymentModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSubmit",
        "click #btn-close-modal": "eventCancel",
        "change input.input-amount": "eventPaymentChanged",
        "change .pay-this": "eventPaymentSelectionChanged",
        "change #payment-select-all": "eventSelectDeselectAll",
        "click #btn-generate-receipt": "eventGenerateReceipt"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for value changed in payment input
     * @param {Object} e
     */
    eventPaymentChanged: function(e) {
        this.updateTotal();
    },
    /**
     * Event handler for payment item checked or unchecked
     * @param {Object} e
     */
    eventPaymentSelectionChanged: function(e) {
        this.updateTotal();
        this.updateSelectAll();
    },
    /**
     * Event handler for check or uncheck 'Select All' checkbox
     * @param {Object} e
     */
    eventSelectDeselectAll: function(e) {
        var checked = $('#payment-select-all').is(':checked');

        if(checked) $('.pay-this').prop('checked', true);
        else $('.pay-this').prop('checked', false);

        this.updateTotal();
    },
    /**
     * Event handler for 'Submit' button
     * @param {Object} e
     */
    eventSubmit: function(e) {
        var thisView, curTab, receiptData, newReceipt, feePayments, totalPayment;

        if(!this.isSaving) {
            this.isSaving = true;
            thisView = this;
            feePayments = {};
            totalPayment = 0;
            curTab = this.$el.find('.payment-content').find('.tab-pane.active').attr('id');

            // Get fee payment info
            this.$el.find('.payment-item').each(function (index) {
                var elem;
                if ($(this).find('.pay-this').is(':checked')) {
                    elem = $(this).find('input.input-amount');
                    totalPayment += feePayments[parseInt(elem.data('idfee'))] = accounting.unformat(elem.val());
                }
            });

            // Get receipt info needed for whatever the payment method is
            if (curTab === 'paywith-cash') {
                receiptData = {
                    userName: $('#fee-payment-otc-name').val(),
                    paymentMethod: $('#fee-payment-otc-type').find(':selected').val(),
                    receiptNumber: parseInt($('#fee-payment-otc-receiptno').val()),
                    receiptNotes: $('#fee-payment-otc-notes').val()
                };
            }
            else if (curTab === 'paywith-deposits') {
                if (totalPayment > this.depositsAllocatable) {
                    bootbox.alert("There is not enough available in deposits to cover this payment!");
                    return false;
                }
                else {
                    receiptData = {
                        paymentMethod: 'Deposit'
                    };
                }
            }
            else {
                bootbox.alert('That payment mechanism is not yet implemented!');
                return false;
            }

            newReceipt = new ReceiptModel(receiptData);

            newReceipt.processPayment(feePayments, {
                wait: true,
                success: function (model, response, options) {
                    // Gotta reload fees and receipts to show changes
                    if(response.redirect) {
                        thisView.isSaving = false;
                        window.location = response.redirect;
                    }
                    else {
                        baseView.refreshFees().refreshReceipts();
                        thisView.hide();
                        thisView.isSaving = false;
                    }
                },
                error: function (model, response, options) {
                    Util.showError(response.responseJSON);
                    thisView.isSaving = false;
                }
            });
        }
    },
    /**
     * Event handler for generating receipt numbers
     * @param e
     */
    eventGenerateReceipt: function(e) {
        e.preventDefault();

        $.ajax(hbInitData().meta.Fee.apiPath + '/receipts/generate', {
            type: 'GET',
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            data: JSON.stringify({
            })
        }).success(function(data) {
            $('#fee-payment-otc-receiptno').val(data.data);
        }).fail(function(response) {
            Util.showError(response.responseJSON);
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

/**
 * Backbone view for the primary fee edit modal.
 * @typedef {Object} FeeEditModalView
 */
var FeeEditModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeEditModalView}
     */
    initialize: function(options) {
        this.rendered = false;
        this.loading = false;
        this.template = null;
        this.templatesFound = {};

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeEditModalView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.feeModalEdit;

        this.$el.html(template({
            fee: this.model.attributes,
            meta: hbInitData().meta.Fee
        }));

        // Render the "meat" of the modal
        this.renderFeeFields();

        // Set up the fee template typeahead search
        this.initFeeTemplateSearch();

        this.applyPermissions();

        // If never rendered before, insert the modal div at the top of the page
        if(!this.rendered) {
            $(document.body).prepend(this.$el);
            this.rendered = true;
        }

        return this;
    },
    /**
     * Factor out part of form that has to be re-rendered with template change
     * @returns {FeeEditModalView}
     */
    renderFeeFields: function() {
        var template = Handlebars.templates.feeModalEditFields;

        this.$el.find('#modal-fee-fields').html(template({
            fee: this.tempModel.attributes,
            meta: hbInitData().meta.Fee
        }));

        this.renderFormulaTerms();

        return this;
    },
    /**
     * Factor out rendering of variable/function terms/inputs
     * @returns {FeeEditModalView}
     */
    renderFormulaTerms: function() {
        var varTemplate, varData, termList;
        varTemplate = Handlebars.templates.feeModalFormulaVariable;
        termList = this.$el.find('#modal-fee-formula-terms');

        if (this.tempModel.get('feeTemplate').variables) {
            // for handling new fees (add fee)
            varData = this.tempModel.get('feeTemplate').variables;
        }
        else {
            // for handling existing fees (edit fee)
            varData = this.tempModel.attributes.variables;
        }

        // Render variables
        if(varData) {
            _.each(varData, function(val, name) {
                termList.append($(varTemplate({
                    termName: name,
                    termValue: val
                })));
            });
        }

        return this;
    },
    /**
     * Factor out code to set up typeahead search
     * @returns {FeeEditModalView}
     */
    initFeeTemplateSearch: function() {
        var thisView, templateSearch;
        thisView = this;

        templateSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                wildcard: '%QUERY',
                url: hbInitData().meta.Fee.apiPath + '/templates/find?title=%QUERY'
            }
        });

        this.$el.find('#modal-search-feetype').typeahead(
            null,
            {
                name: 'fee-template-api',
                /**
                 * Custom source function pulls fee template data from
                 * user search and separates from names.
                 * @param {Array} query
                 * @param {function} sync
                 * @param {function} async
                 * @returns {Number|*}
                 */
                source: function (query, sync, async) {
                    var templateTitles = [];
                    thisView.loading = true;
                    return templateSearch.search(
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
                                thisView.templatesFound[d.title] = d;
                                templateTitles.push(d.title);
                            });

                            thisView.loading = false;
                            async(templateTitles);
                        }
                    );
                }
            }
        );

        return this;
    },
    /**
     * Pull a table of variable values from all existing variable input fields
     * @returns {Object}
     */
    extractVariables: function() {
        var varData = {};

        $('.variable-input').each(function(index) {
            varData[$(this).attr('name')] = accounting.unformat($(this).val());
        });

        return varData;
    },
    /**
     * HACK to set unit price field to 'N/A' if [total]/[quantity] != [unit price]
     */
    validateUnitPrice: function() {
        var quantity, unitPrice, total, isValid;
        quantity = $('#modal-fee-quantity').val();
        unitPrice = $('#modal-fee-unit-price').val();
        total = $('#modal-fee-total').val();
        isValid = true;

        if(quantity == 'N/A' || unitPrice == 'N/A') {
            isValid = false;
        }
        else {
            quantity = new BigNumber(accounting.unformat(quantity));
            unitPrice = new BigNumber(accounting.unformat(unitPrice));
            total = new BigNumber(accounting.unformat(total));

            if(quantity.eq(0)) {
                if(!total.eq(0)) {
                    isValid = false;
                }
            }
            else if(!total.dividedBy(quantity).eq(unitPrice)) {
                isValid = false;
            }
        }

        if(!isValid) {
            $('#modal-fee-unit-price').val('N/A');
            $('#modal-fee-unit-price-override').prop('checked', false);
            $('#modal-fee-unit-price').prop('disabled', true);
        }
        /*else {
            $('#modal-fee-unit-price').val(feesFormatMoney(this.tempModel.get(''), false))
        }*/
    },
    /**
     * @param {FeeModel} feeModel
     * @param {FeeList} feeList
     * @returns {FeeEditModalView}
     */
    show: function(feeModel, feeList) {
        this.model = feeModel;
        this.collection = feeList;

        // Render with clone of model data so we can mess with the template and simple cancel
        this.tempModel = this.model.clone();

        // Listen for temp model preview response
        this.listenTo(this.tempModel, 'preview', this.eventPreviewed);

        // Store template title on opening so we can tell for sure when it changes
        this.lastTitle = this.model.get('title');

        this.render();

        if (this.collection) {
            $('#btn-delete-fee').hide();
        }

        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeEditModalView}
     */
    hide: function() {
        this.stopListening(this.tempModel);
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click #btn-save": "eventSave",
        "click #btn-cancel": "eventCancel",
        "click #btn-delete-fee" : "eventDeleteFee",
        "change .modal-override": "eventChangeOverride",
        "typeahead:select": "eventTypeaheadSelect",
        "typeahead:autocomplete": "eventTypeaheadSelect",
        "change #modal-fee-quantity": "eventRequestPreview",
        "change #modal-fee-unit-price": "eventRequestPreview",
        "change .variable-input": "eventRequestPreview",
        "change #modal-fee-total": "validateUnitPrice"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for changes that require invocation of the fee preview API
     * @param {Object} e
     */
    eventRequestPreview: function(e) {
        var quantity, unitPrice, overrides;

        quantity = $('#modal-fee-quantity').val();
        quantity = (quantity != 'N/A' ? parseInt(quantity) : 0);
        unitPrice = $('#modal-fee-unit-price').val();
        unitPrice = (unitPrice != 'N/A' ? accounting.unformat(unitPrice) : 0);
        overrides = _.clone(this.tempModel.get('overrides'));
        overrides.totalOverride = false;

        this.tempModel.set({
            quantity: quantity,
            feeUnitPrice: unitPrice,
            variables: this.extractVariables(),
            overrides: overrides
        });

        this.tempModel.preview();
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, toSave, wasNew;
        thisView = this;

        if(!this.tempModel.get('idFeeTemplate')) {
            bootbox.alert('No fee template is selected!');
            return false;
        }
        else if(this.loading) {
            bootbox.alert("Please wait for fee data to load!");
            return false;
        }

        toSave = _.clone(this.tempModel.attributes);

        wasNew = this.model.isNew();

        toSave.quantity = $('#modal-fee-quantity').val();
        toSave.quantity = (toSave.quantity !== 'N/A' ? parseInt(toSave.quantity) : 0);
        toSave.feeUnitPrice = $('#modal-fee-unit-price').val();
        toSave.feeUnitPrice = (toSave.feeUnitPrice !== 'N/A' ? accounting.unformat(toSave.feeUnitPrice) : 0);
        toSave.variables = this.extractVariables();
        toSave.total = accounting.unformat($('#modal-fee-total').val());
        toSave.notes = $('#modal-fee-notes').val();

        this.model.save(toSave, {
            wait: true,
            success: function(model, response, options) {
                if(wasNew) {
                    thisView.collection.add(model);
                }

                baseView.refreshFees(true);
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
    },
    /**
     * Event handler for check or uncheck override
     * @param e
     */
    eventChangeOverride: function(e) {
        var disabled, overrides, overrideWhat;
        disabled = !$(e.target).is(':checked');
        overrideWhat = $(e.target).data('override') + 'Override';
        overrides = _.clone(this.tempModel.get('overrides'));

        $(e.target).closest('.form-group').find('input').not('.modal-override').prop('disabled', disabled);

        if(_.has(overrides, overrideWhat)) {
            overrides[overrideWhat] = !disabled;
        }

        this.tempModel.set({overrides: overrides});
    },
    /**
     * Event handler for template typeahead select or autocomplete item
     * @param {Object} e
     * @param {string} name
     */
    eventTypeaheadSelect: function(e, name) {
        var templateTitle, newTemplate;
        templateTitle = $('#modal-search-feetype').val();

        if(templateTitle !== this.lastTitle) {
            this.template = this.templatesFound[templateTitle];
            this.lastTitle = templateTitle;

            newTemplate = {
                idFeeType: this.template.idFeeType,
                title: this.template.title,
                description: this.template.description,
                formula: this.template.formula,
                fixedAmount: this.template.fixedAmount,
                unitPrice: this.template.unitPrice,
                unitVariable: this.template.unitVariable,
                matrixFormula: this.template.matrixFormula,
                minimumValue: this.template.minimumValue,
                variables: _.clone(this.template.variables),
                calculationMethod: _.clone(this.template.calculationMethod)
            };

            this.tempModel.set({idFeeTemplate: this.template.idFeeTemplate, feeTemplate: newTemplate});
            this.renderFeeFields();
            this.loading = true;
            this.tempModel.preview();
        }
    },
    /**
     * Event handler for model preview event
     * @param {FeeModel} model
     */
    eventPreviewed: function(model) {
        $('#modal-fee-total').val(feesFormatMoney(model.get('total'), false));

        if(!model.get('overrides').totalOverride) {
            $('#modal-fee-total-override').prop('checked', false);
            $('#modal-fee-total').prop('disabled', true);
        }

        this.validateUnitPrice();
        this.loading = false;
    },
    /**
     *
     */
    eventDeleteFee: function(e) {
        var thisView = this;
        if (thisView.model.attributes.paid) {
            bootbox.alert('This fee has been paid toward! If you wish to delete the fee, refund the payment from this fee.');
        }
        else {
            thisView.hide();
            var collection = thisView.model.collection;
            thisView.model.destroy({
                wait: true,
                error: function(model, response, options) {
                    Util.showError(response.responseJSON);
                }
            });

        }
    }
});

/**
 * Backbone view for the fee schedule select modal. Yes it's really ugly how it
 * uses baseView globally and jQuery to update the text. There are a lot of
 * things weird about this modal. But deadlines.
 * @typedef {Object} FeeScheduleModalView
 */
var FeeScheduleModalView = BizzyBone.BaseView.extend({
    /**
     * @param options
     * @returns {FeeScheduleModalView }
     */
    initialize: function(options) {
        this.rendered = false;

        return BizzyBone.BaseView.prototype.initialize.call(this, options);
    },
    /**
     * @returns {FeeScheduleModalView}
     */
    render: function() {
        var template;
        template = Handlebars.templates.feeModalFeeSchedule;

        this.$el.html(template({
            feeScheduleList: _.pluck(this.collection.models, 'attributes'),
            idFeeSchedule: baseView.idFeeSchedule,
            meta: hbInitData().meta.Fee
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
     * @returns {FeeScheduleModalView}
     */
    show: function() {
        this.render();
        this.$el.children().first().modal('show');

        return this;
    },
    /**
     * @returns {FeeScheduleModalView}
     */
    hide: function() {
        this.$el.children().first().modal('hide');
        return this;
    },
    events: {
        "click .btn-primary": "eventSave",
        "click .btn-default": "eventCancel"
    },
    permissionTargets: {
        Fee: 'meta'
    },
    /**
     * Event handler for modal save button
     * @param {Object} e
     */
    eventSave: function(e) {
        var thisView, scheduleTagModel;
        thisView = this;

        // Do we *reeaaaally* need to instantiate a model and all that to accomplish this? Well, no, probably not but
        scheduleTagModel = new FeeTagModel({idFeeTag: parseInt($('#select-feeschedule').find(':selected').val())});
        scheduleTagModel.saveProjectFeeSchedule(null, {
            wait: true,
            success: function(model, response, options) {
                baseView.setFeeSchedule(model.get('idFeeTag'));
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