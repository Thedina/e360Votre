/**
 * Some extensions to Backbone I should have done way earlier...
 */

/**
 * @namespace BizzyBone
 */
BizzyBone = (function() {
    var bb = {};

    bb.BaseView = function(options) {
        Backbone.View.apply(this, arguments);
    };

    bb.BaseView.prototype = Object.create(Backbone.View.prototype);

    _.extend(bb.BaseView.prototype, Backbone.View.prototype, {
        /**
         * @param options
         * @returns {bb.BaseView}
         */
        initialize: function(options) {
            // Set defaultElement flag if el has not been specified
            this.defaultElement = _.has(options, 'el') ? false : true;
            return Backbone.View.prototype.initialize.call(this, options);
        },
        /**
         * Renders a template (or this.template) in environment env. Will
         * initially render *into* el, but on subsequent calls replace it. If
         * el is not set, will shed the default div and point el to the outer
         * container in the template.
         * @param {Object} env
         * @param {function} [template]
         */
        renderTemplate: function(env, template) {
            var oldEl;
            env || (env = {});
            template = template ? template : this.template;

            if(this.$el.is(':empty')) {
                this.$el.html(template(env));

                // If we rendered into the default div (i.e. this.el was never set) lose the outer
                // div and point whatever is the outermost container from the template
                if(this.defaultElement) {
                    this.setElement(this.$el.children().first());
                }
            }
            else {
                oldEl = this.$el;
                this.setElement(template(env));
                oldEl.replaceWith(this.$el);
                oldEl.remove();
            }
        },
        /**
         * setElement is modified to set this.defaultElement to false when first
         * called
         * @param {jQuery} element
         * @returns {BaseView}
         */
        setElement: function(element) {
            this.defaultElement = false;
            return Backbone.View.prototype.setElement.call(this, element);
        },
        /**
         * Improved applyPermissions() with boolean parser
         * @param {Object} permissionTargets
         * @param {Object} meta
         * @returns {boolean}
         */
        applyPermissions: function(permissionTargets, meta) {
            
            function tokenize(str) {
                var matchToken, results, curMatch;
                matchToken = /\(|\)|&&|\|\||!|[A-Za-z]+:[a-z]+/g;
                results = [];

                while((curMatch = matchToken.exec(str)) !== null) {
                    results.push(curMatch[0]);
                }

                return results;
            }

            function parsePerms(tokens, perms) {
                var negated, bool, nextBool, op;

                if(tokens[0] === '!') {
                    negated = true;
                    tokens.shift();
                }
                else {
                    negated = false;
                }

                bool = parseBool(tokens, perms);

                if(negated) bool = !bool;

                while(tokens[0] === '&&' || tokens[0] === '||') {
                    op = tokens.shift();

                    if(!tokens.length || tokens[0] === '&&' || tokens[0] == '||') {
                        throw {
                            msg: "parsePerms(): no expression after operator"
                        };
                    }

                    nextBool = parseBool(tokens, perms);

                    if(op === '&&') bool = bool && nextBool;
                    else if(op === '||') bool = bool || nextBool;
                }

                return bool;
            }

            function parseBool(tokens, perms) {
                var permToken, split, exp;
                permToken = /[A-Za-z]+:[a-z]+/;

                if(permToken.test(tokens[0])) {
                    split = tokens.shift().split(':');
                    return perms[split[0]][split[1].toUpperCase()];
                }

                if(tokens[0] === '(') {
                    tokens.shift();
                    exp = parsePerms(tokens);

                    if(tokens[0] !== ')') {
                        throw {
                            msg: "parseBool(): missing closing paren"
                        };
                    }

                    tokens.shift();
                    return exp;
                }

                if(tokens[0] === ')') {
                    throw {
                        msg: "parseBool(): unexpected closing paren"
                    };
                }

                return parsePerms(tokens);
            }

            var thisView, pt, target;
            thisView = this;

            if(!_.isObject(permissionTargets)) permissionTargets = {};
            if(!_.isObject(meta)) meta = hbInitData().meta;

            if(_.isObject(this.permissionTargets)) permissionTargets = _.extend(permissionTargets, this.permissionTargets);

            if(_.isObject(permissionTargets)) {
                for(target in permissionTargets) {
                    
                    pt = permissionTargets[target]
                    
                    if(pt === 'meta') {
                        console.log('sss', meta[target]);
                        pt = meta[target].permissions;
                    }

                    if(!_.isObject(pt)) return false;

                    permissionTargets[target] = pt;
                }
            }
            else {
                return false;
            }

            thisView.$el.find('.use-perm').each(function(index) {
                
                console.log('permm', this);
                
                var ifPerm, hasPerm;
                ifPerm = $(this).data('ifperm');
                ifPerm = tokenize(ifPerm);

                try {
                    hasPerm = parsePerms(ifPerm, permissionTargets);
                }
                catch (e) {
                    console.log(e.msg);
                    hasPerm = false;
                }

                if(hasPerm) $(this).removeClass('hide');
                else $(this).addClass('hide');
            });

            return true;
        }
    });

    bb.BaseView.extend = Backbone.View.extend;

    bb.BaseModel = function(attributes, options) {
        Backbone.Model.apply(this, arguments);
    };

    bb.BaseModel.prototype = Object.create(Backbone.Model.prototype);

    _.extend(bb.BaseModel.prototype, Backbone.Model.prototype, {
        _debounceTime: 500,
        /**
         * @param attributes
         * @param options
         * @returns {bb.BaseModel}
         */
        initialize: function(attributes, options) {
            this.canSave = true;
            return Backbone.Model.prototype.initialize.call(this, attributes, options);
        },
        /**
         * Parse response data. If data is nested in response.data member use
         * that.
         * @param {Object} response
         * @param {Object} [options]
         * @returns {Object}
         */
        parse: function(response, options) {
            if(_.has(response, 'data')) {
                response = response.data;
            }

            return response;
        },
        /**
         * Save, omitting keys in this.dontSave and applying the debounce timer
         * @param {Object} attributes
         * @param {Object} options
         * @returns {Object}
         */
        save: function(attributes, options) {
            var thisModel = this;
            options || (options = {});

            if(this.canSave || options.noDebounce) {

                //save either passed attributes or all attributes if false-y, omitting those in this.dontSave
                attributes = attributes ? _.omit.apply(this, [attributes].concat(this.dontSave)) : _.omit.apply(this, [this.attributes].concat(this.dontSave));

                options.data = JSON.stringify(attributes);
                this.canSave = false;
                setTimeout(function () {
                    thisModel.canSave = true;
                }, this._debounceTime);

                return Backbone.Model.prototype.save.call(this, attributes, options);
            }
            else {
                return false; // Throw an exception, maybe?
            }
        },
        /**
         * Get this model's attributes, minus those specified to be omitted from saving
         */
        attributesToSave: function() {
            return _.omit.apply(this, [this.attributes].concat(this.dontSave));
        }
    });

    bb.BaseModel.extend = Backbone.Model.extend;

    bb.BaseCollection = function(options) {
        Backbone.Collection.apply(this, arguments);
    };

    bb.BaseCollection.prototype = Object.create(Backbone.Collection.prototype);

    _.extend(bb.BaseCollection.prototype, Backbone.Collection.prototype, {
        /**
         * Parse response data. If data is nested in response.data member use
         * that.
         * @param {Object} response
         * @param {Object} [options]
         * @returns {Object}
         */
        parse: function(response, options) {
            if(_.has(response, 'data')) {
                response = response.data;
            }

            return response;
        }
    });

    bb.BaseCollection.extend = Backbone.Collection.extend;

    return bb;
})();