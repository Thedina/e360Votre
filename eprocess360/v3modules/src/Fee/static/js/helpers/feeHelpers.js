/**
 * Handlebars helpers for fees
 */

/**
 * Helper to generate select options for fee schedules
 * @param {Array.<Object>} feeScheduleList
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('feeScheduleOptions', function(feeScheduleList, selected) {
    var html = "";

    _.each(feeScheduleList, function(feeSchedule) {
        html += Util.makeOption(
            feeSchedule.idFeeTag,
            feeSchedule.feeTagValue,
            feeSchedule.idFeeTag == selected
        );
    });

    return html;
});

/**
 * Helper to generate select options for fee types
 * @param {Array.<Object>} feeTypeList
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('feeTypeOptions', function(feeTypeList, selected) {
    var html = "";

    _.each(feeTypeList, function(feeType) {
        html += Util.makeOption(
            feeType.idFeeType,
            feeType.feeTypeTitle,
            feeType.idFeeType == selected
        );
    });

    return html;
});

/**
 * Generate select options for fee tag categories
 * @param {Array.<Object>} feeTagCategories
 * @param {number} selected
 * @returns {string}
 */
Handlebars.registerHelper('feeTagCategoryOptions', function(feeTagCategories, selected) {
    var html = "";

    _.each(feeTagCategories, function(tagCategory) {
        html += Util.makeOption(
            tagCategory.idFeeTagCategory,
            tagCategory.title,
            tagCategory.idFeeTagCategory == selected
        );
    });

    return html;
});

/**
 * Display either the quantity or "N/A"
 * @param {Object} fee
 * @returns {string}
 */
Handlebars.registerHelper('displayQuantity', function(fee) {
    if(fee.feeTemplate.calculationMethod && fee.feeTemplate.calculationMethod.isUnit) {
        return fee.quantity.toString();
    }
    else {
        return "N/A";
    }
});

/**
 * Display either the specified unit variable name or "Quantity"
 * @param {Object} fee
 * @return string
 */
Handlebars.registerHelper('unitVariableLabel', function(fee) {
    if(_.isString(fee.feeTemplate.unitVariable) && fee.feeTemplate.unitVariable.length) {
        return fee.feeTemplate.unitVariable;
    }
    else {
        return 'Quantity';
    }
});

/**
 * Display either the base value, override value, or "N/A" for unit price
 * HACK: display 'N/A' if [total]/[quantity] != [unit price]
 * @param {Object} fee
 * @param {boolean} showSymbol
 * @returns {string}
 */
Handlebars.registerHelper('displayUnitPrice', function(fee, showSymbol, meta) {
    var quantity, unitPrice, total;
    showSymbol = (typeof showSymbol !== 'undefined' ? showSymbol : true);

    if(fee.feeTemplate.calculationMethod && fee.feeTemplate.calculationMethod.isUnit) {
        if(fee.overrides.unitPriceOverride) {
            quantity = new BigNumber(fee.quantity);
            unitPrice = new BigNumber(fee.feeUnitPrice);
            total = new BigNumber(fee.total);

            if(total.eq(0)) {
                if(!quantity.eq(0)) return 'N/A';
            }
            else if(!total.dividedBy(quantity).eq(unitPrice)) {
                return 'N/A';
            }

            if(showSymbol) return accounting.formatMoney(fee.feeUnitPrice);
            else return accounting.formatMoney(fee.feeUnitPrice, '');
        }
        else {
            quantity = new BigNumber(fee.quantity);
            unitPrice = new BigNumber(fee.feeTemplate.fixedAmount);
            total = new BigNumber(fee.total);

            if(total.eq(0)) {
                if(!quantity.eq(0)) return 'N/A';
            }
            else if(!total.dividedBy(quantity).eq(unitPrice)) {
                return 'N/A';
            }

            if(showSymbol) return accounting.formatMoney(fee.feeTemplate.fixedAmount);
            else return accounting.formatMoney(fee.feeTemplate.fixedAmount, '');
        }
    }
    else {
        return "N/A";
    }
});

/**
 * Display either the formula or "N/A"
 * @param {Object} fee
 * @returns {string}
 */
Handlebars.registerHelper('displayFormula', function(fee) {
    if(fee.feeTemplate.calculationMethod) {
        return fee.feeTemplate.formula;
    }
    else {
        return "N/A";
    }
});

/**
 * Helper to generate ACTIVE/INACTIVE formatted text for the fee template
 * settings save modal
 * @param {boolean} isActive
 * @returns {string}
 */
Handlebars.registerHelper('activeInactive', function(isActive) {
    if(isActive) {
        return '<span class="label label-success">ACTIVE</span>';
    }
    else {
        return '<span class="label label-warning">INACTIVE</span>';
    }
});

/**
 * Helper to look up a fee schedule year given the tag ID for that fee schedule
 * @param {number} idFeeTag
 * @param {Array.<Object>} feeScheduleOptions
 * @returns {string}
 */
Handlebars.registerHelper('feeScheduleYear', function(idFeeTag, feeScheduleOptions) {
    if(_.has(feeScheduleOptions, idFeeTag)) {
        return feeScheduleOptions[idFeeTag].feeTagValue;
    }
    else {
        return 'N/A';
    }
});

/**
 * Helper to look up a fee schedule year given the tag ID for that fee schedule
 * @param {number} idCategory
 * @param {Array.<Object>} feeTagCategories
 * @returns {string}
 */
Handlebars.registerHelper('feeTagCategoryTitle', function(idCategory, feeTagCategories) {
    if(_.has(feeTagCategories, idCategory)) {
        return feeTagCategories[idCategory].title;
    }
    else {
        return 'N/A';
    }
});

/**
 * Format a number as money, appropriately for current system config
 * @param {number} value
 * @param {boolean} showSymbol
 * @param {number} places
 * @returns {string}
 */
Handlebars.registerHelper('formatMoney', function(value, showSymbol, places) {
    showSymbol = (typeof showSymbol !== 'undefined' ? showSymbol : true);
    places = _.isNumber(places) ? places : accounting.settings.currency.precision;

    if(showSymbol) return accounting.formatMoney(value, accounting.settings.currency.symbol, places);
    else return accounting.formatMoney(value, '', places);
});
