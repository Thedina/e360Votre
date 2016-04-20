/**
 * Utility module
 */

/**
 * @namespace Util
 */
Util = (function() {
    var f = {};

    /**
     * Formats a size in bytes into kilo/mega/giga as is appropriate. Defaults
     * to two decimal places. Default to KiB/MiB/GiB (i.e. powers of 2) but can
     * optionally do KB/MB/GB (powers of 10)
     * @param {number} bytes
     * @param {number} [decimals=2]
     * @param {boolean} [baseTen=false]
     * @returns {string}
     */
    f.formatFileSize = function(bytes, decimals, baseTen) {
        var kilo, mega, giga, out, abbr;

        decimals = decimals ? decimals : 2;

        kilo = baseTen ? 1000 : 1024;
        abbr = 'B';

        mega = kilo * kilo;
        giga = mega * kilo;

        if(bytes < kilo) {
            out = bytes;
        }
        else if(bytes >= kilo && bytes < mega) {
            out = bytes / kilo;
            abbr = 'K' + abbr;
        }
        else if(bytes >= mega && bytes < giga) {
            out = bytes / mega;
            abbr = 'M' + abbr;
        }
        else if(bytes >= giga) {
            out = bytes / giga;
            abbr = 'G' + abbr;
        }

        return out.toFixed(decimals) + ' ' + abbr;
    };

    /**
     * Takes a response object either as {error: [string]} or
     * {error: {message: [string]}} and shows the error message. for now that's
     * just an alert.
     * @param {Object} response
     * @param {string|Object} [response.errors]
     * @param {string|Object} [response.error]
     */
    f.showError = function(response) {
        var messages = [];

        if(_.has(response, 'errors')) {
            _.each(response.errors, function(e) {
                messages.push(e.message);
            });
            bootbox.alert(messages.join("\n"));
        }
        else if(_.has(response, 'error')) {
            if(_.isObject(response.error)) {
                bootbox.alert(response.error.message);
            }
            else if(_.isString(response.error)) {
                bootbox.alert(response.error);
            }
            else {
                bootbox.alert('Unexpected error format.')
            }
        }
    };

    /**
     * Simple string interpolation function (borrowed from Douglas Crockford
     * except he extended String.prototype). Interpolables are formatted as
     * {param}
     * @param {string} str
     * @param {Object} params
     * @returns {string}
     */
    f.strSub = function(str, params) {
        return str.replace(
            /\{([^{}]*)\}/g,
            function (a, b) {
                var r = params[b];
                return typeof r === 'string' || typeof r === 'number' ? r : a;
            }
        );
    };

    /**
     * Converts data in an array to a "lookup table" object indexed on key
     * @param {Array} arrayData
     * @param {string} key
     * @returns {Object}
     */
    f.tableize = function(arrayData, key) {
        var table = {};
        _.each(arrayData, function(item) {
            table[item[key]] = item;
        });
        return table;
    };

    /**
     * Helper helper (heh) to generate a select <option>
     * @param {string|number} value
     * @param {string} name
     * @param {boolean} selected
     * @param {Array.<string>} addClasses
     * @returns {string}
     */
    f.makeOption = function(value, name, selected, addClasses) {
        return "<option value='" + value + "'" + (addClasses ? " class='" + addClasses.join(" ") + "'" : "") + (selected ? " selected='selected'" : "") + ">" + name + "</option>";
    };

    /**
     * Parse YYYY-MM-DD HH:II:SS into JS Date. Treat the input as UTC because
     * we want time-zone stuff to be handled server-side.
     * @param {string|null} dateString
     * @returns {Date|null}
     */
    f.parseDateTime = function(dateString) {
        var dt;
        if(dateString && typeof dateString === 'string') {
            dt = dateString.split(' ');
            if (dt.length > 1) {
                return new Date(dt[0] + 'T' + dt[1] + '.000Z');
            }
            else {
                return new Date(dt[0]);
            }
        }
        else {
            return null;
        }
    };

    /**
     * Reformat date(time) string as mm/dd/yy[yy]
     * @param {string|null} dateString
     * @param {boolean} shortYear
     * @returns {string}
     */
    f.dateFormatDisplay = function(dateString, shortYear) {
        var d = f.parseDateTime(dateString);
        if(d) {
            return (d.getUTCMonth() + 1) + '/' + d.getUTCDate() + '/' + (shortYear ? d.getUTCFullYear().toString().substring(2) : d.getUTCFullYear());
        }
        else {
            return '--/--/' + (shortYear ? '--' : '----');
        }
    };

    /**
     * Reformat date(time) string as yyyy-mm-dd. Now uses moment.js
     * @param {Date|string|null} dateString
     * @returns {string|null}
     */
    f.dateFormatStorage = function(dateString) {
        if(_.isString(dateString)) {
            if(isNaN(Date.parse(dateString))) {
                return null;
            }
            else {
                return moment(new Date(dateString)).format('YYYY-MM-DD');
            }
        }
        else if(_.isDate(dateString)) {
            return moment(dateString).format('YYYY-MM-DD');
        }
        else {
            return null;
        }
    };

    /**
     * Reformat datetime string or Date object as h:mm A. Now uses moment.js
     * @param {Date|string} dateString
     * @returns {string}
     */
    f.timeFormatDisplay = function(dateString) {
        return moment(dateString).format('h:mm A');
    };

    /**
     * Get name for weekday as numbered (0-6)
     * @param {number} num
     * @returns {string}
     */
    f.weekdayByNumber = function(num) {
        var theDays = {
            0: 'Sunday',
            1: 'Monday',
            2: 'Tuesday',
            3: 'Wednesday',
            4: 'Thursday',
            5: 'Friday',
            6: 'Saturday'
        };

        return theDays[num];
    };

    /**
     * Slightly idiosyncratic implementation of random password generation.
     * Currently requires browser to support crypto, which in turn requires
     * typed arrays. This means IE 11+, most notably. Will add a backup RNG
     * at some point.
     * @param {number} length
     * @returns {string}
     */
    f.randomPassword = function(length) {
        var charTable, tableSize, randVals, str, curLength;
        charTable = {
            0: 'a', 1: 'b', 2: 'c', 3: 'd', 4: 'e',
            5: 'f', 6: 'g', 7: 'h', 8: 'i', 9: 'j',
            10: 'k', 11: 'l', 12: 'm', 13: 'n', 14:'o',
            15: 'p', 16: 'q', 17: 'r', 18: 's', 19: 't',
            20: 'u', 21: 'v', 22: 'w', 23: 'x', 24: 'y',
            25: 'z', 26: 'A', 27: 'B', 28: 'C', 29: 'D',
            30: 'E', 31: 'F', 32: 'G', 33: 'H', 34: 'I',
            35: 'J', 36: 'K', 37: 'L', 38: 'M', 39: 'N',
            40: 'O', 41: 'P', 42: 'Q', 43: 'R', 44: 'S',
            45: 'T', 46: 'U', 47: 'V', 48: 'W', 49: 'X',
            50: 'Y', 51: 'Z', 52: '0', 53: '1', 54: '2',
            55: '3', 56: '4', 57: '5', 58: '6', 59: '7',
            60: '8', 61: '9', 62: '~', 63: '!', 64: '@',
            65: '#', 66: '$', 67: '%', 68: '^', 69: '&',
            70: '*', 71: '(', 72: ')', 73: '-', 74: '_',
            75: '=', 76: '+', 77: '[', 78: '{', 79: ']',
            80: '}', 81: '|', 82: '/', 83: '?', 84: ':',
            85: ';', 86: '<', 87: '>', 88: '`'
        };

        tableSize = Object.keys(charTable).length;

        if (!_.isFunction(window.crypto.getRandomValues)) {
            //TODO add *good* backup RNG
            bootbox.alert('Your browser does not support secure random password generation.');
            return null;
        }

        str = "";
        curLength = 0;

        while(curLength < length) {
            randVals = new Int8Array(length);
            window.crypto.getRandomValues(randVals);
            randVals = randVals.map(function (val) {
                return val & 127;
            });

            randVals.forEach(function (val) {
                if (val < tableSize  && curLength < length) {
                    str += charTable[val];
                    curLength++;
                }
            });
        }

        return str;
    };

    /**
     * Perhaps best explained with an example:
     * Util.traverseNested({a: {b: {c: 'd'}}}, ['a', 'b', 'c']) returns 'd'
     * @param {Object} obj
     * @param {Array.<string>} keys
     * @returns {*}
     */
    f.traverseNested = function(obj, keys) {
        var k = keys.shift();

        if(!_.isObject(obj)) {
            return obj;
        }
        else if(!keys.length) {
            return obj[k];
        }
        else {
            return f.traverseNested(obj[k], keys);
        }
    };

    /**
     * Util.traverseNestedKeepKey({a: {b: {c: 'd'}}}, ['a', 'b', 'c']) returns ['c', 'd']
     * @param {Object} obj
     * @param {Array.<string>} keys
     * @returns {Array}
     */
    f.traverseNestedKeepKey = function(obj, keys) {
        var k = keys.shift();

        if(!keys.length) {
            return [k, obj[k]];
        }
        else {
            return f.traverseNestedKeepKey(obj[k], keys);
        }
    };

    /**
     * Parse a string input into an appropriate type (or pass a non-string
     * input through)
     * @param val
     * @returns {string|number|boolean}
     */
    f.parseWhatever = function(val) {
        if(!_.isString(val)) return val;
        else if(val === 'true') return true;
        else if(val === 'false') return false;
        else if(val == parseFloat(val)) return parseFloat(val);
        else return val;
    };

    return f;
})();