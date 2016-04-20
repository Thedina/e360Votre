/**
 * Handlebars helpers for submittals module
 */

/**
 * Returns appropriate status text for submittal based on its status
 * @param {Object} status
 * @param {boolean} status.isComplete
 * @param {boolean} status.hasReview
 * @param {boolean} status.reviewsAccepted
 * @param (boolean) status.reviewsCompleted
 * @returns {string}
 */
Handlebars.registerHelper('submittalStatus', function(status) {
    if(status === null) {
        return "No Submittals";
    }
    if(status.isComplete) {
        if(status.hasReview) {
            if(status.reviewsAccepted) {
                return "Reviews Accepted";
            }
            else {
                if (status.reviewsCompleted) {
                    return "Reviews Completed <span class='label label-danger'>Not Accepted</span>"
                }
                else
                    return "Under Review";
            }
        }
        else {
            return "Complete";
        }
    }
    else {
        return "Incomplete";
    }
});

/**
 * Returns submittal menu *data-action value* as either open or close based on
 * whether it is currently open or closed
 * @param {Object} status
 * @param {boolean} status.isComplete
 * @returns {string}
 */
Handlebars.registerHelper('submittalCloseOpenAction', function(status) {
    if(status.isComplete) {
        return 'open';
    }
    else {
        return 'close';
    }
});

/**
 * Returns submittal menu *text* as either open or close based on whether it is
 * currently open or closed
 * @param {Object} status
 * @param {boolean} status.isComplete
 * @returns {string}
 */
Handlebars.registerHelper('submittalCloseOpenText', function(status) {
    if(status.isComplete) {
        return 'Open';
    }
    else {
        return 'Close';
    }
});