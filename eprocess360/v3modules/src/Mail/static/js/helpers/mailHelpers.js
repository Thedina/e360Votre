/**
 * Handlebars helpers for mail log
 */

/**
 * Format mail date and status from queue data
 * @param {Object} queue
 * @returns {string}
 */
Handlebars.registerHelper('mailStatus', function(queue) {
    var out = "";

    if(queue.failed) {
        out += "FAILED";
    }
    else {
        out += Util.dateFormatDisplay(queue.lastDate) + " " + Util.timeFormatDisplay(queue.lastDate);
    }

    out += " after " + queue.tries + (queue.tries == 1 ? " try" : " tries");

    return out;
});

/**
 * Generate mail send status indicator from queue data
 * @param {Object} queue
 * @returns {string}
 */
Handlebars.registerHelper('mailSent', function(queue) {
    var text, labelClass;

    if(queue.sent) {
        text = 'Sent';
        labelClass = 'success';
    }
    else if(queue.failed) {
        text = 'Failed';
        labelClass = 'danger';
    }
    else {
        text = 'Unsent';
        labelClass = 'warning';
    }

    return '<span class="label label-' + labelClass +'">' + text + '</span>';
});