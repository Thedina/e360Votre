/**
 * Handlebars helpers available globally
 */

/**
 * @typedef {Object} LinkData
 * @property {string} href
 * @property {string} rel
 * @property {string} title
 */

/**
 * Process a breadcrumb link array to generate HTML breadcrumbs
 * @param {Array.<LinkData>} links
 * @returns {string}
 */
Handlebars.registerHelper('breadcrumbLinks', function(links) {
    return _.reduceRight(links, function(html, link) {
        var selfLink = (link.rel === 'self');
        return html + "<li rel='" + link.rel + "'" + (selfLink ? " class='active'" : "") + ">" + (selfLink ? "" : "<a href='" + link.href + "'>") + link.title + (selfLink ? "" : "</a>") + "</li>";
    }, "");
});

/**
 * Wrapper to export Util.dateFormatDisplay() for use in templates
 * @param {string} dateString
 * @param {boolean} shortYear
 * @returns {string}
 */
Handlebars.registerHelper('dateFormatDisplay', function(dateString) {
    return Util.dateFormatDisplay(dateString, false);
});

/**
 * Wrapper to export Util.dateFormatStorage() for use in templates
 * @param {string} dateString
 * @returns {string}
 */
Handlebars.registerHelper('dateFormatStorage', function(dateString) {
    return Util.dateFormatStorage(dateString);
});

/**
 * Wrapper to export Util.timeFormatStorage() for use in templates
 * @param {Date|string} dateString
 * @returns {string}
 */
Handlebars.registerHelper('timeFormatDisplay', function(dateString) {
    return Util.timeFormatDisplay(dateString);
});

/**
 * Wrapper for Util.weekdayByNumber
 * @param {number} num
 * @returns {string}
 */
Handlebars.registerHelper('weekdayByNumber', function(num) {
    return Util.weekdayByNumber(num);
});

/**
 * Generates select options for any case where they come from an array of
 * strings and the values are the same as the names
 * @param {Array.<string>} optionsList
 * @param {string} selected
 * @returns {string}
 */
Handlebars.registerHelper('genericStringOptions', function(optionsList, selected) {
    var options = "";

    _.each(optionsList, function(option) {
        options += Util.makeOption(
            option,
            option,
            option === selected
        );
    });

    return options;
});


/**
 * Formats a floating point value as a fixed-precision string
 * @param {number} value
 * @param {number} precision
 * @returns {string}
 */
Handlebars.registerHelper('toFixed', function(value, precision) {
    value || (value = 0);
    if(_.isString(value)) value = parseFloat(value);

    return value.toFixed(precision);
});

/**
 * Wrapper for Util.formatFileSize(). Defaults to two decimal places and
 * powers-of-two formatting for now.
 * @param {number} bytes
 * @returns {string}
 */
Handlebars.registerHelper('formatFileSize', function(bytes) {
    return Util.formatFileSize(bytes);
});