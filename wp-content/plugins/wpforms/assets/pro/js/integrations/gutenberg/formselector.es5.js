(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
// shim for using process in browser

var process = module.exports = {};

process.nextTick = (function () {
    var canSetImmediate = typeof window !== 'undefined'
    && window.setImmediate;
    var canPost = typeof window !== 'undefined'
    && window.postMessage && window.addEventListener
    ;

    if (canSetImmediate) {
        return function (f) { return window.setImmediate(f) };
    }

    if (canPost) {
        var queue = [];
        window.addEventListener('message', function (ev) {
            var source = ev.source;
            if ((source === window || source === null) && ev.data === 'process-tick') {
                ev.stopPropagation();
                if (queue.length > 0) {
                    var fn = queue.shift();
                    fn();
                }
            }
        }, true);

        return function nextTick(fn) {
            queue.push(fn);
            window.postMessage('process-tick', '*');
        };
    }

    return function nextTick(fn) {
        setTimeout(fn, 0);
    };
})();

process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;

process.binding = function (name) {
    throw new Error('process.binding is not supported');
}

// TODO(shtylman)
process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};

},{}],2:[function(require,module,exports){
/*
object-assign
(c) Sindre Sorhus
@license MIT
*/

'use strict';
/* eslint-disable no-unused-vars */
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var hasOwnProperty = Object.prototype.hasOwnProperty;
var propIsEnumerable = Object.prototype.propertyIsEnumerable;

function toObject(val) {
	if (val === null || val === undefined) {
		throw new TypeError('Object.assign cannot be called with null or undefined');
	}

	return Object(val);
}

function shouldUseNative() {
	try {
		if (!Object.assign) {
			return false;
		}

		// Detect buggy property enumeration order in older V8 versions.

		// https://bugs.chromium.org/p/v8/issues/detail?id=4118
		var test1 = new String('abc');  // eslint-disable-line no-new-wrappers
		test1[5] = 'de';
		if (Object.getOwnPropertyNames(test1)[0] === '5') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test2 = {};
		for (var i = 0; i < 10; i++) {
			test2['_' + String.fromCharCode(i)] = i;
		}
		var order2 = Object.getOwnPropertyNames(test2).map(function (n) {
			return test2[n];
		});
		if (order2.join('') !== '0123456789') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test3 = {};
		'abcdefghijklmnopqrst'.split('').forEach(function (letter) {
			test3[letter] = letter;
		});
		if (Object.keys(Object.assign({}, test3)).join('') !==
				'abcdefghijklmnopqrst') {
			return false;
		}

		return true;
	} catch (err) {
		// We don't expect any of the above to throw, but better to be safe.
		return false;
	}
}

module.exports = shouldUseNative() ? Object.assign : function (target, source) {
	var from;
	var to = toObject(target);
	var symbols;

	for (var s = 1; s < arguments.length; s++) {
		from = Object(arguments[s]);

		for (var key in from) {
			if (hasOwnProperty.call(from, key)) {
				to[key] = from[key];
			}
		}

		if (getOwnPropertySymbols) {
			symbols = getOwnPropertySymbols(from);
			for (var i = 0; i < symbols.length; i++) {
				if (propIsEnumerable.call(from, symbols[i])) {
					to[symbols[i]] = from[symbols[i]];
				}
			}
		}
	}

	return to;
};

},{}],3:[function(require,module,exports){
(function (process){
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';

var printWarning = function() {};

if (process.env.NODE_ENV !== 'production') {
  var ReactPropTypesSecret = require('./lib/ReactPropTypesSecret');
  var loggedTypeFailures = {};
  var has = require('./lib/has');

  printWarning = function(text) {
    var message = 'Warning: ' + text;
    if (typeof console !== 'undefined') {
      console.error(message);
    }
    try {
      // --- Welcome to debugging React ---
      // This error was thrown as a convenience so that you can use this stack
      // to find the callsite that caused this warning to fire.
      throw new Error(message);
    } catch (x) { /**/ }
  };
}

/**
 * Assert that the values match with the type specs.
 * Error messages are memorized and will only be shown once.
 *
 * @param {object} typeSpecs Map of name to a ReactPropType
 * @param {object} values Runtime values that need to be type-checked
 * @param {string} location e.g. "prop", "context", "child context"
 * @param {string} componentName Name of the component for error messages.
 * @param {?Function} getStack Returns the component stack.
 * @private
 */
function checkPropTypes(typeSpecs, values, location, componentName, getStack) {
  if (process.env.NODE_ENV !== 'production') {
    for (var typeSpecName in typeSpecs) {
      if (has(typeSpecs, typeSpecName)) {
        var error;
        // Prop type validation may throw. In case they do, we don't want to
        // fail the render phase where it didn't fail before. So we log it.
        // After these have been cleaned up, we'll let them throw.
        try {
          // This is intentionally an invariant that gets caught. It's the same
          // behavior as without this statement except with a better message.
          if (typeof typeSpecs[typeSpecName] !== 'function') {
            var err = Error(
              (componentName || 'React class') + ': ' + location + ' type `' + typeSpecName + '` is invalid; ' +
              'it must be a function, usually from the `prop-types` package, but received `' + typeof typeSpecs[typeSpecName] + '`.' +
              'This often happens because of typos such as `PropTypes.function` instead of `PropTypes.func`.'
            );
            err.name = 'Invariant Violation';
            throw err;
          }
          error = typeSpecs[typeSpecName](values, typeSpecName, componentName, location, null, ReactPropTypesSecret);
        } catch (ex) {
          error = ex;
        }
        if (error && !(error instanceof Error)) {
          printWarning(
            (componentName || 'React class') + ': type specification of ' +
            location + ' `' + typeSpecName + '` is invalid; the type checker ' +
            'function must return `null` or an `Error` but returned a ' + typeof error + '. ' +
            'You may have forgotten to pass an argument to the type checker ' +
            'creator (arrayOf, instanceOf, objectOf, oneOf, oneOfType, and ' +
            'shape all require an argument).'
          );
        }
        if (error instanceof Error && !(error.message in loggedTypeFailures)) {
          // Only monitor this failure once because there tends to be a lot of the
          // same error.
          loggedTypeFailures[error.message] = true;

          var stack = getStack ? getStack() : '';

          printWarning(
            'Failed ' + location + ' type: ' + error.message + (stack != null ? stack : '')
          );
        }
      }
    }
  }
}

/**
 * Resets warning cache when testing.
 *
 * @private
 */
checkPropTypes.resetWarningCache = function() {
  if (process.env.NODE_ENV !== 'production') {
    loggedTypeFailures = {};
  }
}

module.exports = checkPropTypes;

}).call(this,require("hmr7eR"))
},{"./lib/ReactPropTypesSecret":7,"./lib/has":8,"hmr7eR":1}],4:[function(require,module,exports){
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';

var ReactPropTypesSecret = require('./lib/ReactPropTypesSecret');

function emptyFunction() {}
function emptyFunctionWithReset() {}
emptyFunctionWithReset.resetWarningCache = emptyFunction;

module.exports = function() {
  function shim(props, propName, componentName, location, propFullName, secret) {
    if (secret === ReactPropTypesSecret) {
      // It is still safe when called from React.
      return;
    }
    var err = new Error(
      'Calling PropTypes validators directly is not supported by the `prop-types` package. ' +
      'Use PropTypes.checkPropTypes() to call them. ' +
      'Read more at http://fb.me/use-check-prop-types'
    );
    err.name = 'Invariant Violation';
    throw err;
  };
  shim.isRequired = shim;
  function getShim() {
    return shim;
  };
  // Important!
  // Keep this list in sync with production version in `./factoryWithTypeCheckers.js`.
  var ReactPropTypes = {
    array: shim,
    bigint: shim,
    bool: shim,
    func: shim,
    number: shim,
    object: shim,
    string: shim,
    symbol: shim,

    any: shim,
    arrayOf: getShim,
    element: shim,
    elementType: shim,
    instanceOf: getShim,
    node: shim,
    objectOf: getShim,
    oneOf: getShim,
    oneOfType: getShim,
    shape: getShim,
    exact: getShim,

    checkPropTypes: emptyFunctionWithReset,
    resetWarningCache: emptyFunction
  };

  ReactPropTypes.PropTypes = ReactPropTypes;

  return ReactPropTypes;
};

},{"./lib/ReactPropTypesSecret":7}],5:[function(require,module,exports){
(function (process){
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';

var ReactIs = require('react-is');
var assign = require('object-assign');

var ReactPropTypesSecret = require('./lib/ReactPropTypesSecret');
var has = require('./lib/has');
var checkPropTypes = require('./checkPropTypes');

var printWarning = function() {};

if (process.env.NODE_ENV !== 'production') {
  printWarning = function(text) {
    var message = 'Warning: ' + text;
    if (typeof console !== 'undefined') {
      console.error(message);
    }
    try {
      // --- Welcome to debugging React ---
      // This error was thrown as a convenience so that you can use this stack
      // to find the callsite that caused this warning to fire.
      throw new Error(message);
    } catch (x) {}
  };
}

function emptyFunctionThatReturnsNull() {
  return null;
}

module.exports = function(isValidElement, throwOnDirectAccess) {
  /* global Symbol */
  var ITERATOR_SYMBOL = typeof Symbol === 'function' && Symbol.iterator;
  var FAUX_ITERATOR_SYMBOL = '@@iterator'; // Before Symbol spec.

  /**
   * Returns the iterator method function contained on the iterable object.
   *
   * Be sure to invoke the function with the iterable as context:
   *
   *     var iteratorFn = getIteratorFn(myIterable);
   *     if (iteratorFn) {
   *       var iterator = iteratorFn.call(myIterable);
   *       ...
   *     }
   *
   * @param {?object} maybeIterable
   * @return {?function}
   */
  function getIteratorFn(maybeIterable) {
    var iteratorFn = maybeIterable && (ITERATOR_SYMBOL && maybeIterable[ITERATOR_SYMBOL] || maybeIterable[FAUX_ITERATOR_SYMBOL]);
    if (typeof iteratorFn === 'function') {
      return iteratorFn;
    }
  }

  /**
   * Collection of methods that allow declaration and validation of props that are
   * supplied to React components. Example usage:
   *
   *   var Props = require('ReactPropTypes');
   *   var MyArticle = React.createClass({
   *     propTypes: {
   *       // An optional string prop named "description".
   *       description: Props.string,
   *
   *       // A required enum prop named "category".
   *       category: Props.oneOf(['News','Photos']).isRequired,
   *
   *       // A prop named "dialog" that requires an instance of Dialog.
   *       dialog: Props.instanceOf(Dialog).isRequired
   *     },
   *     render: function() { ... }
   *   });
   *
   * A more formal specification of how these methods are used:
   *
   *   type := array|bool|func|object|number|string|oneOf([...])|instanceOf(...)
   *   decl := ReactPropTypes.{type}(.isRequired)?
   *
   * Each and every declaration produces a function with the same signature. This
   * allows the creation of custom validation functions. For example:
   *
   *  var MyLink = React.createClass({
   *    propTypes: {
   *      // An optional string or URI prop named "href".
   *      href: function(props, propName, componentName) {
   *        var propValue = props[propName];
   *        if (propValue != null && typeof propValue !== 'string' &&
   *            !(propValue instanceof URI)) {
   *          return new Error(
   *            'Expected a string or an URI for ' + propName + ' in ' +
   *            componentName
   *          );
   *        }
   *      }
   *    },
   *    render: function() {...}
   *  });
   *
   * @internal
   */

  var ANONYMOUS = '<<anonymous>>';

  // Important!
  // Keep this list in sync with production version in `./factoryWithThrowingShims.js`.
  var ReactPropTypes = {
    array: createPrimitiveTypeChecker('array'),
    bigint: createPrimitiveTypeChecker('bigint'),
    bool: createPrimitiveTypeChecker('boolean'),
    func: createPrimitiveTypeChecker('function'),
    number: createPrimitiveTypeChecker('number'),
    object: createPrimitiveTypeChecker('object'),
    string: createPrimitiveTypeChecker('string'),
    symbol: createPrimitiveTypeChecker('symbol'),

    any: createAnyTypeChecker(),
    arrayOf: createArrayOfTypeChecker,
    element: createElementTypeChecker(),
    elementType: createElementTypeTypeChecker(),
    instanceOf: createInstanceTypeChecker,
    node: createNodeChecker(),
    objectOf: createObjectOfTypeChecker,
    oneOf: createEnumTypeChecker,
    oneOfType: createUnionTypeChecker,
    shape: createShapeTypeChecker,
    exact: createStrictShapeTypeChecker,
  };

  /**
   * inlined Object.is polyfill to avoid requiring consumers ship their own
   * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is
   */
  /*eslint-disable no-self-compare*/
  function is(x, y) {
    // SameValue algorithm
    if (x === y) {
      // Steps 1-5, 7-10
      // Steps 6.b-6.e: +0 != -0
      return x !== 0 || 1 / x === 1 / y;
    } else {
      // Step 6.a: NaN == NaN
      return x !== x && y !== y;
    }
  }
  /*eslint-enable no-self-compare*/

  /**
   * We use an Error-like object for backward compatibility as people may call
   * PropTypes directly and inspect their output. However, we don't use real
   * Errors anymore. We don't inspect their stack anyway, and creating them
   * is prohibitively expensive if they are created too often, such as what
   * happens in oneOfType() for any type before the one that matched.
   */
  function PropTypeError(message, data) {
    this.message = message;
    this.data = data && typeof data === 'object' ? data: {};
    this.stack = '';
  }
  // Make `instanceof Error` still work for returned errors.
  PropTypeError.prototype = Error.prototype;

  function createChainableTypeChecker(validate) {
    if (process.env.NODE_ENV !== 'production') {
      var manualPropTypeCallCache = {};
      var manualPropTypeWarningCount = 0;
    }
    function checkType(isRequired, props, propName, componentName, location, propFullName, secret) {
      componentName = componentName || ANONYMOUS;
      propFullName = propFullName || propName;

      if (secret !== ReactPropTypesSecret) {
        if (throwOnDirectAccess) {
          // New behavior only for users of `prop-types` package
          var err = new Error(
            'Calling PropTypes validators directly is not supported by the `prop-types` package. ' +
            'Use `PropTypes.checkPropTypes()` to call them. ' +
            'Read more at http://fb.me/use-check-prop-types'
          );
          err.name = 'Invariant Violation';
          throw err;
        } else if (process.env.NODE_ENV !== 'production' && typeof console !== 'undefined') {
          // Old behavior for people using React.PropTypes
          var cacheKey = componentName + ':' + propName;
          if (
            !manualPropTypeCallCache[cacheKey] &&
            // Avoid spamming the console because they are often not actionable except for lib authors
            manualPropTypeWarningCount < 3
          ) {
            printWarning(
              'You are manually calling a React.PropTypes validation ' +
              'function for the `' + propFullName + '` prop on `' + componentName + '`. This is deprecated ' +
              'and will throw in the standalone `prop-types` package. ' +
              'You may be seeing this warning due to a third-party PropTypes ' +
              'library. See https://fb.me/react-warning-dont-call-proptypes ' + 'for details.'
            );
            manualPropTypeCallCache[cacheKey] = true;
            manualPropTypeWarningCount++;
          }
        }
      }
      if (props[propName] == null) {
        if (isRequired) {
          if (props[propName] === null) {
            return new PropTypeError('The ' + location + ' `' + propFullName + '` is marked as required ' + ('in `' + componentName + '`, but its value is `null`.'));
          }
          return new PropTypeError('The ' + location + ' `' + propFullName + '` is marked as required in ' + ('`' + componentName + '`, but its value is `undefined`.'));
        }
        return null;
      } else {
        return validate(props, propName, componentName, location, propFullName);
      }
    }

    var chainedCheckType = checkType.bind(null, false);
    chainedCheckType.isRequired = checkType.bind(null, true);

    return chainedCheckType;
  }

  function createPrimitiveTypeChecker(expectedType) {
    function validate(props, propName, componentName, location, propFullName, secret) {
      var propValue = props[propName];
      var propType = getPropType(propValue);
      if (propType !== expectedType) {
        // `propValue` being instance of, say, date/regexp, pass the 'object'
        // check, but we can offer a more precise error message here rather than
        // 'of type `object`'.
        var preciseType = getPreciseType(propValue);

        return new PropTypeError(
          'Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + preciseType + '` supplied to `' + componentName + '`, expected ') + ('`' + expectedType + '`.'),
          {expectedType: expectedType}
        );
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createAnyTypeChecker() {
    return createChainableTypeChecker(emptyFunctionThatReturnsNull);
  }

  function createArrayOfTypeChecker(typeChecker) {
    function validate(props, propName, componentName, location, propFullName) {
      if (typeof typeChecker !== 'function') {
        return new PropTypeError('Property `' + propFullName + '` of component `' + componentName + '` has invalid PropType notation inside arrayOf.');
      }
      var propValue = props[propName];
      if (!Array.isArray(propValue)) {
        var propType = getPropType(propValue);
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected an array.'));
      }
      for (var i = 0; i < propValue.length; i++) {
        var error = typeChecker(propValue, i, componentName, location, propFullName + '[' + i + ']', ReactPropTypesSecret);
        if (error instanceof Error) {
          return error;
        }
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createElementTypeChecker() {
    function validate(props, propName, componentName, location, propFullName) {
      var propValue = props[propName];
      if (!isValidElement(propValue)) {
        var propType = getPropType(propValue);
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected a single ReactElement.'));
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createElementTypeTypeChecker() {
    function validate(props, propName, componentName, location, propFullName) {
      var propValue = props[propName];
      if (!ReactIs.isValidElementType(propValue)) {
        var propType = getPropType(propValue);
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected a single ReactElement type.'));
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createInstanceTypeChecker(expectedClass) {
    function validate(props, propName, componentName, location, propFullName) {
      if (!(props[propName] instanceof expectedClass)) {
        var expectedClassName = expectedClass.name || ANONYMOUS;
        var actualClassName = getClassName(props[propName]);
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + actualClassName + '` supplied to `' + componentName + '`, expected ') + ('instance of `' + expectedClassName + '`.'));
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createEnumTypeChecker(expectedValues) {
    if (!Array.isArray(expectedValues)) {
      if (process.env.NODE_ENV !== 'production') {
        if (arguments.length > 1) {
          printWarning(
            'Invalid arguments supplied to oneOf, expected an array, got ' + arguments.length + ' arguments. ' +
            'A common mistake is to write oneOf(x, y, z) instead of oneOf([x, y, z]).'
          );
        } else {
          printWarning('Invalid argument supplied to oneOf, expected an array.');
        }
      }
      return emptyFunctionThatReturnsNull;
    }

    function validate(props, propName, componentName, location, propFullName) {
      var propValue = props[propName];
      for (var i = 0; i < expectedValues.length; i++) {
        if (is(propValue, expectedValues[i])) {
          return null;
        }
      }

      var valuesString = JSON.stringify(expectedValues, function replacer(key, value) {
        var type = getPreciseType(value);
        if (type === 'symbol') {
          return String(value);
        }
        return value;
      });
      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of value `' + String(propValue) + '` ' + ('supplied to `' + componentName + '`, expected one of ' + valuesString + '.'));
    }
    return createChainableTypeChecker(validate);
  }

  function createObjectOfTypeChecker(typeChecker) {
    function validate(props, propName, componentName, location, propFullName) {
      if (typeof typeChecker !== 'function') {
        return new PropTypeError('Property `' + propFullName + '` of component `' + componentName + '` has invalid PropType notation inside objectOf.');
      }
      var propValue = props[propName];
      var propType = getPropType(propValue);
      if (propType !== 'object') {
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected an object.'));
      }
      for (var key in propValue) {
        if (has(propValue, key)) {
          var error = typeChecker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);
          if (error instanceof Error) {
            return error;
          }
        }
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createUnionTypeChecker(arrayOfTypeCheckers) {
    if (!Array.isArray(arrayOfTypeCheckers)) {
      process.env.NODE_ENV !== 'production' ? printWarning('Invalid argument supplied to oneOfType, expected an instance of array.') : void 0;
      return emptyFunctionThatReturnsNull;
    }

    for (var i = 0; i < arrayOfTypeCheckers.length; i++) {
      var checker = arrayOfTypeCheckers[i];
      if (typeof checker !== 'function') {
        printWarning(
          'Invalid argument supplied to oneOfType. Expected an array of check functions, but ' +
          'received ' + getPostfixForTypeWarning(checker) + ' at index ' + i + '.'
        );
        return emptyFunctionThatReturnsNull;
      }
    }

    function validate(props, propName, componentName, location, propFullName) {
      var expectedTypes = [];
      for (var i = 0; i < arrayOfTypeCheckers.length; i++) {
        var checker = arrayOfTypeCheckers[i];
        var checkerResult = checker(props, propName, componentName, location, propFullName, ReactPropTypesSecret);
        if (checkerResult == null) {
          return null;
        }
        if (checkerResult.data && has(checkerResult.data, 'expectedType')) {
          expectedTypes.push(checkerResult.data.expectedType);
        }
      }
      var expectedTypesMessage = (expectedTypes.length > 0) ? ', expected one of type [' + expectedTypes.join(', ') + ']': '';
      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` supplied to ' + ('`' + componentName + '`' + expectedTypesMessage + '.'));
    }
    return createChainableTypeChecker(validate);
  }

  function createNodeChecker() {
    function validate(props, propName, componentName, location, propFullName) {
      if (!isNode(props[propName])) {
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` supplied to ' + ('`' + componentName + '`, expected a ReactNode.'));
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function invalidValidatorError(componentName, location, propFullName, key, type) {
    return new PropTypeError(
      (componentName || 'React class') + ': ' + location + ' type `' + propFullName + '.' + key + '` is invalid; ' +
      'it must be a function, usually from the `prop-types` package, but received `' + type + '`.'
    );
  }

  function createShapeTypeChecker(shapeTypes) {
    function validate(props, propName, componentName, location, propFullName) {
      var propValue = props[propName];
      var propType = getPropType(propValue);
      if (propType !== 'object') {
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type `' + propType + '` ' + ('supplied to `' + componentName + '`, expected `object`.'));
      }
      for (var key in shapeTypes) {
        var checker = shapeTypes[key];
        if (typeof checker !== 'function') {
          return invalidValidatorError(componentName, location, propFullName, key, getPreciseType(checker));
        }
        var error = checker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);
        if (error) {
          return error;
        }
      }
      return null;
    }
    return createChainableTypeChecker(validate);
  }

  function createStrictShapeTypeChecker(shapeTypes) {
    function validate(props, propName, componentName, location, propFullName) {
      var propValue = props[propName];
      var propType = getPropType(propValue);
      if (propType !== 'object') {
        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type `' + propType + '` ' + ('supplied to `' + componentName + '`, expected `object`.'));
      }
      // We need to check all keys in case some are required but missing from props.
      var allKeys = assign({}, props[propName], shapeTypes);
      for (var key in allKeys) {
        var checker = shapeTypes[key];
        if (has(shapeTypes, key) && typeof checker !== 'function') {
          return invalidValidatorError(componentName, location, propFullName, key, getPreciseType(checker));
        }
        if (!checker) {
          return new PropTypeError(
            'Invalid ' + location + ' `' + propFullName + '` key `' + key + '` supplied to `' + componentName + '`.' +
            '\nBad object: ' + JSON.stringify(props[propName], null, '  ') +
            '\nValid keys: ' + JSON.stringify(Object.keys(shapeTypes), null, '  ')
          );
        }
        var error = checker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);
        if (error) {
          return error;
        }
      }
      return null;
    }

    return createChainableTypeChecker(validate);
  }

  function isNode(propValue) {
    switch (typeof propValue) {
      case 'number':
      case 'string':
      case 'undefined':
        return true;
      case 'boolean':
        return !propValue;
      case 'object':
        if (Array.isArray(propValue)) {
          return propValue.every(isNode);
        }
        if (propValue === null || isValidElement(propValue)) {
          return true;
        }

        var iteratorFn = getIteratorFn(propValue);
        if (iteratorFn) {
          var iterator = iteratorFn.call(propValue);
          var step;
          if (iteratorFn !== propValue.entries) {
            while (!(step = iterator.next()).done) {
              if (!isNode(step.value)) {
                return false;
              }
            }
          } else {
            // Iterator will provide entry [k,v] tuples rather than values.
            while (!(step = iterator.next()).done) {
              var entry = step.value;
              if (entry) {
                if (!isNode(entry[1])) {
                  return false;
                }
              }
            }
          }
        } else {
          return false;
        }

        return true;
      default:
        return false;
    }
  }

  function isSymbol(propType, propValue) {
    // Native Symbol.
    if (propType === 'symbol') {
      return true;
    }

    // falsy value can't be a Symbol
    if (!propValue) {
      return false;
    }

    // 19.4.3.5 Symbol.prototype[@@toStringTag] === 'Symbol'
    if (propValue['@@toStringTag'] === 'Symbol') {
      return true;
    }

    // Fallback for non-spec compliant Symbols which are polyfilled.
    if (typeof Symbol === 'function' && propValue instanceof Symbol) {
      return true;
    }

    return false;
  }

  // Equivalent of `typeof` but with special handling for array and regexp.
  function getPropType(propValue) {
    var propType = typeof propValue;
    if (Array.isArray(propValue)) {
      return 'array';
    }
    if (propValue instanceof RegExp) {
      // Old webkits (at least until Android 4.0) return 'function' rather than
      // 'object' for typeof a RegExp. We'll normalize this here so that /bla/
      // passes PropTypes.object.
      return 'object';
    }
    if (isSymbol(propType, propValue)) {
      return 'symbol';
    }
    return propType;
  }

  // This handles more types than `getPropType`. Only used for error messages.
  // See `createPrimitiveTypeChecker`.
  function getPreciseType(propValue) {
    if (typeof propValue === 'undefined' || propValue === null) {
      return '' + propValue;
    }
    var propType = getPropType(propValue);
    if (propType === 'object') {
      if (propValue instanceof Date) {
        return 'date';
      } else if (propValue instanceof RegExp) {
        return 'regexp';
      }
    }
    return propType;
  }

  // Returns a string that is postfixed to a warning about an invalid type.
  // For example, "undefined" or "of type array"
  function getPostfixForTypeWarning(value) {
    var type = getPreciseType(value);
    switch (type) {
      case 'array':
      case 'object':
        return 'an ' + type;
      case 'boolean':
      case 'date':
      case 'regexp':
        return 'a ' + type;
      default:
        return type;
    }
  }

  // Returns class name of the object, if any.
  function getClassName(propValue) {
    if (!propValue.constructor || !propValue.constructor.name) {
      return ANONYMOUS;
    }
    return propValue.constructor.name;
  }

  ReactPropTypes.checkPropTypes = checkPropTypes;
  ReactPropTypes.resetWarningCache = checkPropTypes.resetWarningCache;
  ReactPropTypes.PropTypes = ReactPropTypes;

  return ReactPropTypes;
};

}).call(this,require("hmr7eR"))
},{"./checkPropTypes":3,"./lib/ReactPropTypesSecret":7,"./lib/has":8,"hmr7eR":1,"object-assign":2,"react-is":11}],6:[function(require,module,exports){
(function (process){
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

if (process.env.NODE_ENV !== 'production') {
  var ReactIs = require('react-is');

  // By explicitly using `prop-types` you are opting into new development behavior.
  // http://fb.me/prop-types-in-prod
  var throwOnDirectAccess = true;
  module.exports = require('./factoryWithTypeCheckers')(ReactIs.isElement, throwOnDirectAccess);
} else {
  // By explicitly using `prop-types` you are opting into new production behavior.
  // http://fb.me/prop-types-in-prod
  module.exports = require('./factoryWithThrowingShims')();
}

}).call(this,require("hmr7eR"))
},{"./factoryWithThrowingShims":4,"./factoryWithTypeCheckers":5,"hmr7eR":1,"react-is":11}],7:[function(require,module,exports){
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';

var ReactPropTypesSecret = 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED';

module.exports = ReactPropTypesSecret;

},{}],8:[function(require,module,exports){
module.exports = Function.call.bind(Object.prototype.hasOwnProperty);

},{}],9:[function(require,module,exports){
(function (process){
/** @license React v16.13.1
 * react-is.development.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';



if (process.env.NODE_ENV !== "production") {
  (function() {
'use strict';

// The Symbol used to tag the ReactElement-like types. If there is no native Symbol
// nor polyfill, then a plain number is used for performance.
var hasSymbol = typeof Symbol === 'function' && Symbol.for;
var REACT_ELEMENT_TYPE = hasSymbol ? Symbol.for('react.element') : 0xeac7;
var REACT_PORTAL_TYPE = hasSymbol ? Symbol.for('react.portal') : 0xeaca;
var REACT_FRAGMENT_TYPE = hasSymbol ? Symbol.for('react.fragment') : 0xeacb;
var REACT_STRICT_MODE_TYPE = hasSymbol ? Symbol.for('react.strict_mode') : 0xeacc;
var REACT_PROFILER_TYPE = hasSymbol ? Symbol.for('react.profiler') : 0xead2;
var REACT_PROVIDER_TYPE = hasSymbol ? Symbol.for('react.provider') : 0xeacd;
var REACT_CONTEXT_TYPE = hasSymbol ? Symbol.for('react.context') : 0xeace; // TODO: We don't use AsyncMode or ConcurrentMode anymore. They were temporary
// (unstable) APIs that have been removed. Can we remove the symbols?

var REACT_ASYNC_MODE_TYPE = hasSymbol ? Symbol.for('react.async_mode') : 0xeacf;
var REACT_CONCURRENT_MODE_TYPE = hasSymbol ? Symbol.for('react.concurrent_mode') : 0xeacf;
var REACT_FORWARD_REF_TYPE = hasSymbol ? Symbol.for('react.forward_ref') : 0xead0;
var REACT_SUSPENSE_TYPE = hasSymbol ? Symbol.for('react.suspense') : 0xead1;
var REACT_SUSPENSE_LIST_TYPE = hasSymbol ? Symbol.for('react.suspense_list') : 0xead8;
var REACT_MEMO_TYPE = hasSymbol ? Symbol.for('react.memo') : 0xead3;
var REACT_LAZY_TYPE = hasSymbol ? Symbol.for('react.lazy') : 0xead4;
var REACT_BLOCK_TYPE = hasSymbol ? Symbol.for('react.block') : 0xead9;
var REACT_FUNDAMENTAL_TYPE = hasSymbol ? Symbol.for('react.fundamental') : 0xead5;
var REACT_RESPONDER_TYPE = hasSymbol ? Symbol.for('react.responder') : 0xead6;
var REACT_SCOPE_TYPE = hasSymbol ? Symbol.for('react.scope') : 0xead7;

function isValidElementType(type) {
  return typeof type === 'string' || typeof type === 'function' || // Note: its typeof might be other than 'symbol' or 'number' if it's a polyfill.
  type === REACT_FRAGMENT_TYPE || type === REACT_CONCURRENT_MODE_TYPE || type === REACT_PROFILER_TYPE || type === REACT_STRICT_MODE_TYPE || type === REACT_SUSPENSE_TYPE || type === REACT_SUSPENSE_LIST_TYPE || typeof type === 'object' && type !== null && (type.$$typeof === REACT_LAZY_TYPE || type.$$typeof === REACT_MEMO_TYPE || type.$$typeof === REACT_PROVIDER_TYPE || type.$$typeof === REACT_CONTEXT_TYPE || type.$$typeof === REACT_FORWARD_REF_TYPE || type.$$typeof === REACT_FUNDAMENTAL_TYPE || type.$$typeof === REACT_RESPONDER_TYPE || type.$$typeof === REACT_SCOPE_TYPE || type.$$typeof === REACT_BLOCK_TYPE);
}

function typeOf(object) {
  if (typeof object === 'object' && object !== null) {
    var $$typeof = object.$$typeof;

    switch ($$typeof) {
      case REACT_ELEMENT_TYPE:
        var type = object.type;

        switch (type) {
          case REACT_ASYNC_MODE_TYPE:
          case REACT_CONCURRENT_MODE_TYPE:
          case REACT_FRAGMENT_TYPE:
          case REACT_PROFILER_TYPE:
          case REACT_STRICT_MODE_TYPE:
          case REACT_SUSPENSE_TYPE:
            return type;

          default:
            var $$typeofType = type && type.$$typeof;

            switch ($$typeofType) {
              case REACT_CONTEXT_TYPE:
              case REACT_FORWARD_REF_TYPE:
              case REACT_LAZY_TYPE:
              case REACT_MEMO_TYPE:
              case REACT_PROVIDER_TYPE:
                return $$typeofType;

              default:
                return $$typeof;
            }

        }

      case REACT_PORTAL_TYPE:
        return $$typeof;
    }
  }

  return undefined;
} // AsyncMode is deprecated along with isAsyncMode

var AsyncMode = REACT_ASYNC_MODE_TYPE;
var ConcurrentMode = REACT_CONCURRENT_MODE_TYPE;
var ContextConsumer = REACT_CONTEXT_TYPE;
var ContextProvider = REACT_PROVIDER_TYPE;
var Element = REACT_ELEMENT_TYPE;
var ForwardRef = REACT_FORWARD_REF_TYPE;
var Fragment = REACT_FRAGMENT_TYPE;
var Lazy = REACT_LAZY_TYPE;
var Memo = REACT_MEMO_TYPE;
var Portal = REACT_PORTAL_TYPE;
var Profiler = REACT_PROFILER_TYPE;
var StrictMode = REACT_STRICT_MODE_TYPE;
var Suspense = REACT_SUSPENSE_TYPE;
var hasWarnedAboutDeprecatedIsAsyncMode = false; // AsyncMode should be deprecated

function isAsyncMode(object) {
  {
    if (!hasWarnedAboutDeprecatedIsAsyncMode) {
      hasWarnedAboutDeprecatedIsAsyncMode = true; // Using console['warn'] to evade Babel and ESLint

      console['warn']('The ReactIs.isAsyncMode() alias has been deprecated, ' + 'and will be removed in React 17+. Update your code to use ' + 'ReactIs.isConcurrentMode() instead. It has the exact same API.');
    }
  }

  return isConcurrentMode(object) || typeOf(object) === REACT_ASYNC_MODE_TYPE;
}
function isConcurrentMode(object) {
  return typeOf(object) === REACT_CONCURRENT_MODE_TYPE;
}
function isContextConsumer(object) {
  return typeOf(object) === REACT_CONTEXT_TYPE;
}
function isContextProvider(object) {
  return typeOf(object) === REACT_PROVIDER_TYPE;
}
function isElement(object) {
  return typeof object === 'object' && object !== null && object.$$typeof === REACT_ELEMENT_TYPE;
}
function isForwardRef(object) {
  return typeOf(object) === REACT_FORWARD_REF_TYPE;
}
function isFragment(object) {
  return typeOf(object) === REACT_FRAGMENT_TYPE;
}
function isLazy(object) {
  return typeOf(object) === REACT_LAZY_TYPE;
}
function isMemo(object) {
  return typeOf(object) === REACT_MEMO_TYPE;
}
function isPortal(object) {
  return typeOf(object) === REACT_PORTAL_TYPE;
}
function isProfiler(object) {
  return typeOf(object) === REACT_PROFILER_TYPE;
}
function isStrictMode(object) {
  return typeOf(object) === REACT_STRICT_MODE_TYPE;
}
function isSuspense(object) {
  return typeOf(object) === REACT_SUSPENSE_TYPE;
}

exports.AsyncMode = AsyncMode;
exports.ConcurrentMode = ConcurrentMode;
exports.ContextConsumer = ContextConsumer;
exports.ContextProvider = ContextProvider;
exports.Element = Element;
exports.ForwardRef = ForwardRef;
exports.Fragment = Fragment;
exports.Lazy = Lazy;
exports.Memo = Memo;
exports.Portal = Portal;
exports.Profiler = Profiler;
exports.StrictMode = StrictMode;
exports.Suspense = Suspense;
exports.isAsyncMode = isAsyncMode;
exports.isConcurrentMode = isConcurrentMode;
exports.isContextConsumer = isContextConsumer;
exports.isContextProvider = isContextProvider;
exports.isElement = isElement;
exports.isForwardRef = isForwardRef;
exports.isFragment = isFragment;
exports.isLazy = isLazy;
exports.isMemo = isMemo;
exports.isPortal = isPortal;
exports.isProfiler = isProfiler;
exports.isStrictMode = isStrictMode;
exports.isSuspense = isSuspense;
exports.isValidElementType = isValidElementType;
exports.typeOf = typeOf;
  })();
}

}).call(this,require("hmr7eR"))
},{"hmr7eR":1}],10:[function(require,module,exports){
/** @license React v16.13.1
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

'use strict';var b="function"===typeof Symbol&&Symbol.for,c=b?Symbol.for("react.element"):60103,d=b?Symbol.for("react.portal"):60106,e=b?Symbol.for("react.fragment"):60107,f=b?Symbol.for("react.strict_mode"):60108,g=b?Symbol.for("react.profiler"):60114,h=b?Symbol.for("react.provider"):60109,k=b?Symbol.for("react.context"):60110,l=b?Symbol.for("react.async_mode"):60111,m=b?Symbol.for("react.concurrent_mode"):60111,n=b?Symbol.for("react.forward_ref"):60112,p=b?Symbol.for("react.suspense"):60113,q=b?
Symbol.for("react.suspense_list"):60120,r=b?Symbol.for("react.memo"):60115,t=b?Symbol.for("react.lazy"):60116,v=b?Symbol.for("react.block"):60121,w=b?Symbol.for("react.fundamental"):60117,x=b?Symbol.for("react.responder"):60118,y=b?Symbol.for("react.scope"):60119;
function z(a){if("object"===typeof a&&null!==a){var u=a.$$typeof;switch(u){case c:switch(a=a.type,a){case l:case m:case e:case g:case f:case p:return a;default:switch(a=a&&a.$$typeof,a){case k:case n:case t:case r:case h:return a;default:return u}}case d:return u}}}function A(a){return z(a)===m}exports.AsyncMode=l;exports.ConcurrentMode=m;exports.ContextConsumer=k;exports.ContextProvider=h;exports.Element=c;exports.ForwardRef=n;exports.Fragment=e;exports.Lazy=t;exports.Memo=r;exports.Portal=d;
exports.Profiler=g;exports.StrictMode=f;exports.Suspense=p;exports.isAsyncMode=function(a){return A(a)||z(a)===l};exports.isConcurrentMode=A;exports.isContextConsumer=function(a){return z(a)===k};exports.isContextProvider=function(a){return z(a)===h};exports.isElement=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===c};exports.isForwardRef=function(a){return z(a)===n};exports.isFragment=function(a){return z(a)===e};exports.isLazy=function(a){return z(a)===t};
exports.isMemo=function(a){return z(a)===r};exports.isPortal=function(a){return z(a)===d};exports.isProfiler=function(a){return z(a)===g};exports.isStrictMode=function(a){return z(a)===f};exports.isSuspense=function(a){return z(a)===p};
exports.isValidElementType=function(a){return"string"===typeof a||"function"===typeof a||a===e||a===m||a===g||a===f||a===p||a===q||"object"===typeof a&&null!==a&&(a.$$typeof===t||a.$$typeof===r||a.$$typeof===h||a.$$typeof===k||a.$$typeof===n||a.$$typeof===w||a.$$typeof===x||a.$$typeof===y||a.$$typeof===v)};exports.typeOf=z;

},{}],11:[function(require,module,exports){
(function (process){
'use strict';

if (process.env.NODE_ENV === 'production') {
  module.exports = require('./cjs/react-is.production.min.js');
} else {
  module.exports = require('./cjs/react-is.development.js');
}

}).call(this,require("hmr7eR"))
},{"./cjs/react-is.development.js":9,"./cjs/react-is.production.min.js":10,"hmr7eR":1}],12:[function(require,module,exports){
"use strict";

var _education = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/education.js"));
var _common = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/common.js"));
var _themesPanel = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/themes-panel.js"));
var _containerStyles = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/container-styles.js"));
var _backgroundStyles = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/background-styles.js"));
var _fieldStyles = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/field-styles.js"));
var _stockPhotos = _interopRequireDefault(require("../../../pro/js/integrations/gutenberg/modules/stock-photos.js"));
var _buttonStyles = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/button-styles.js"));
var _advancedSettings = _interopRequireDefault(require("../../../js/integrations/gutenberg/modules/advanced-settings.js"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); } /* jshint es3: false, esversion: 6 */
/**
 * Gutenberg editor block for Pro.
 *
 * @since 1.8.8
 */
var WPForms = window.WPForms || {};
WPForms.FormSelector = WPForms.FormSelector || function () {
  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Common module object.
     *
     * @since 1.8.8
     *
     * @type {Object}
     */
    common: {},
    /**
     * Panel modules objects.
     *
     * @since 1.8.8
     *
     * @type {Object}
     */
    panels: {},
    /**
     * Stock Photos module object.
     *
     * @since 1.8.8
     *
     * @type {Object}
     */
    stockPhotos: {},
    /**
     * Start the engine.
     *
     * @since 1.8.8
     */
    init: function init() {
      app.education = _education.default;
      app.common = _common.default;
      app.panels.themes = _themesPanel.default;
      app.panels.container = _containerStyles.default;
      app.panels.background = _backgroundStyles.default;
      app.panels.field = _fieldStyles.default;
      app.stockPhotos = _stockPhotos.default;
      app.panels.buttons = _buttonStyles.default;
      app.panels.advanced = _advancedSettings.default;
      var blockOptions = {
        panels: app.panels,
        stockPhotos: app.stockPhotos,
        getThemesPanel: app.panels.themes.getThemesPanel,
        getFieldStyles: app.panels.field.getFieldStyles,
        getContainerStyles: app.panels.container.getContainerStyles,
        getButtonStyles: app.panels.buttons.getButtonStyles,
        getBackgroundStyles: app.panels.background.getBackgroundStyles,
        getCommonAttributes: app.getCommonAttributes,
        setStylesHandlers: app.getStyleHandlers(),
        education: app.education
      };

      // Initialize Advanced Settings module.
      app.panels.advanced.init(app.common);

      // Initialize block.
      app.common.init(blockOptions);
    },
    /**
     * Get style handlers.
     *
     * @since 1.8.8
     *
     * @return {Object} Style handlers.
     */
    getCommonAttributes: function getCommonAttributes() {
      return _objectSpread(_objectSpread(_objectSpread(_objectSpread({}, app.panels.field.getBlockAttributes()), app.panels.container.getBlockAttributes()), app.panels.buttons.getBlockAttributes()), app.panels.background.getBlockAttributes());
    },
    /**
     * Get style handlers.
     *
     * @since 1.8.8
     *
     * @return {Object} Style handlers.
     */
    getStyleHandlers: function getStyleHandlers() {
      return {
        'background-image': app.panels.background.setContainerBackgroundImage,
        'background-position': app.panels.background.setContainerBackgroundPosition,
        'background-repeat': app.panels.background.setContainerBackgroundRepeat,
        'background-width': app.panels.background.setContainerBackgroundWidth,
        'background-height': app.panels.background.setContainerBackgroundHeight,
        'background-color': app.panels.background.setBackgroundColor,
        'background-url': app.panels.background.setBackgroundUrl
      };
    }
  };

  // Provide access to public functions/properties.
  return app;
}();

// Initialize.
WPForms.FormSelector.init();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZWR1Y2F0aW9uIiwiX2ludGVyb3BSZXF1aXJlRGVmYXVsdCIsInJlcXVpcmUiLCJfY29tbW9uIiwiX3RoZW1lc1BhbmVsIiwiX2NvbnRhaW5lclN0eWxlcyIsIl9iYWNrZ3JvdW5kU3R5bGVzIiwiX2ZpZWxkU3R5bGVzIiwiX3N0b2NrUGhvdG9zIiwiX2J1dHRvblN0eWxlcyIsIl9hZHZhbmNlZFNldHRpbmdzIiwiZSIsIl9fZXNNb2R1bGUiLCJkZWZhdWx0IiwiX3R5cGVvZiIsIm8iLCJTeW1ib2wiLCJpdGVyYXRvciIsImNvbnN0cnVjdG9yIiwicHJvdG90eXBlIiwib3duS2V5cyIsInIiLCJ0IiwiT2JqZWN0Iiwia2V5cyIsImdldE93blByb3BlcnR5U3ltYm9scyIsImZpbHRlciIsImdldE93blByb3BlcnR5RGVzY3JpcHRvciIsImVudW1lcmFibGUiLCJwdXNoIiwiYXBwbHkiLCJfb2JqZWN0U3ByZWFkIiwiYXJndW1lbnRzIiwibGVuZ3RoIiwiZm9yRWFjaCIsIl9kZWZpbmVQcm9wZXJ0eSIsImdldE93blByb3BlcnR5RGVzY3JpcHRvcnMiLCJkZWZpbmVQcm9wZXJ0aWVzIiwiZGVmaW5lUHJvcGVydHkiLCJfdG9Qcm9wZXJ0eUtleSIsInZhbHVlIiwiY29uZmlndXJhYmxlIiwid3JpdGFibGUiLCJpIiwiX3RvUHJpbWl0aXZlIiwidG9QcmltaXRpdmUiLCJjYWxsIiwiVHlwZUVycm9yIiwiU3RyaW5nIiwiTnVtYmVyIiwiV1BGb3JtcyIsIndpbmRvdyIsIkZvcm1TZWxlY3RvciIsImFwcCIsImNvbW1vbiIsInBhbmVscyIsInN0b2NrUGhvdG9zIiwiaW5pdCIsImVkdWNhdGlvbiIsInRoZW1lcyIsInRoZW1lc1BhbmVsIiwiY29udGFpbmVyIiwiY29udGFpbmVyU3R5bGVzIiwiYmFja2dyb3VuZCIsImJhY2tncm91bmRTdHlsZXMiLCJmaWVsZCIsImZpZWxkU3R5bGVzIiwiYnV0dG9ucyIsImJ1dHRvblN0eWxlcyIsImFkdmFuY2VkIiwiYWR2YW5jZWRTZXR0aW5ncyIsImJsb2NrT3B0aW9ucyIsImdldFRoZW1lc1BhbmVsIiwiZ2V0RmllbGRTdHlsZXMiLCJnZXRDb250YWluZXJTdHlsZXMiLCJnZXRCdXR0b25TdHlsZXMiLCJnZXRCYWNrZ3JvdW5kU3R5bGVzIiwiZ2V0Q29tbW9uQXR0cmlidXRlcyIsInNldFN0eWxlc0hhbmRsZXJzIiwiZ2V0U3R5bGVIYW5kbGVycyIsImdldEJsb2NrQXR0cmlidXRlcyIsInNldENvbnRhaW5lckJhY2tncm91bmRJbWFnZSIsInNldENvbnRhaW5lckJhY2tncm91bmRQb3NpdGlvbiIsInNldENvbnRhaW5lckJhY2tncm91bmRSZXBlYXQiLCJzZXRDb250YWluZXJCYWNrZ3JvdW5kV2lkdGgiLCJzZXRDb250YWluZXJCYWNrZ3JvdW5kSGVpZ2h0Iiwic2V0QmFja2dyb3VuZENvbG9yIiwic2V0QmFja2dyb3VuZFVybCJdLCJzb3VyY2VzIjpbImZha2VfMmNkNWEzZDIuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG5pbXBvcnQgZWR1Y2F0aW9uIGZyb20gJy4uLy4uLy4uL2pzL2ludGVncmF0aW9ucy9ndXRlbmJlcmcvbW9kdWxlcy9lZHVjYXRpb24uanMnO1xuaW1wb3J0IGNvbW1vbiBmcm9tICcuLi8uLi8uLi9qcy9pbnRlZ3JhdGlvbnMvZ3V0ZW5iZXJnL21vZHVsZXMvY29tbW9uLmpzJztcbmltcG9ydCB0aGVtZXNQYW5lbCBmcm9tICcuLi8uLi8uLi9qcy9pbnRlZ3JhdGlvbnMvZ3V0ZW5iZXJnL21vZHVsZXMvdGhlbWVzLXBhbmVsLmpzJztcbmltcG9ydCBjb250YWluZXJTdHlsZXMgZnJvbSAnLi4vLi4vLi4vanMvaW50ZWdyYXRpb25zL2d1dGVuYmVyZy9tb2R1bGVzL2NvbnRhaW5lci1zdHlsZXMuanMnO1xuaW1wb3J0IGJhY2tncm91bmRTdHlsZXMgZnJvbSAnLi4vLi4vLi4vanMvaW50ZWdyYXRpb25zL2d1dGVuYmVyZy9tb2R1bGVzL2JhY2tncm91bmQtc3R5bGVzLmpzJztcbmltcG9ydCBmaWVsZFN0eWxlcyBmcm9tICcuLi8uLi8uLi9qcy9pbnRlZ3JhdGlvbnMvZ3V0ZW5iZXJnL21vZHVsZXMvZmllbGQtc3R5bGVzLmpzJztcbmltcG9ydCBzdG9ja1Bob3RvcyBmcm9tICcuLi8uLi8uLi9wcm8vanMvaW50ZWdyYXRpb25zL2d1dGVuYmVyZy9tb2R1bGVzL3N0b2NrLXBob3Rvcy5qcyc7XG5pbXBvcnQgYnV0dG9uU3R5bGVzIGZyb20gJy4uLy4uLy4uL2pzL2ludGVncmF0aW9ucy9ndXRlbmJlcmcvbW9kdWxlcy9idXR0b24tc3R5bGVzLmpzJztcbmltcG9ydCBhZHZhbmNlZFNldHRpbmdzIGZyb20gJy4uLy4uLy4uL2pzL2ludGVncmF0aW9ucy9ndXRlbmJlcmcvbW9kdWxlcy9hZHZhbmNlZC1zZXR0aW5ncy5qcyc7XG5cbi8qKlxuICogR3V0ZW5iZXJnIGVkaXRvciBibG9jayBmb3IgUHJvLlxuICpcbiAqIEBzaW5jZSAxLjguOFxuICovXG5jb25zdCBXUEZvcm1zID0gd2luZG93LldQRm9ybXMgfHwge307XG5cbldQRm9ybXMuRm9ybVNlbGVjdG9yID0gV1BGb3Jtcy5Gb3JtU2VsZWN0b3IgfHwgKCBmdW5jdGlvbigpIHtcblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXHRcdC8qKlxuXHRcdCAqIENvbW1vbiBtb2R1bGUgb2JqZWN0LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7T2JqZWN0fVxuXHRcdCAqL1xuXHRcdGNvbW1vbjoge30sXG5cblx0XHQvKipcblx0XHQgKiBQYW5lbCBtb2R1bGVzIG9iamVjdHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEB0eXBlIHtPYmplY3R9XG5cdFx0ICovXG5cdFx0cGFuZWxzOiB7fSxcblxuXHRcdC8qKlxuXHRcdCAqIFN0b2NrIFBob3RvcyBtb2R1bGUgb2JqZWN0LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7T2JqZWN0fVxuXHRcdCAqL1xuXHRcdHN0b2NrUGhvdG9zOiB7fSxcblxuXHRcdC8qKlxuXHRcdCAqIFN0YXJ0IHRoZSBlbmdpbmUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRpbml0KCkge1xuXHRcdFx0YXBwLmVkdWNhdGlvbiA9IGVkdWNhdGlvbjtcblx0XHRcdGFwcC5jb21tb24gPSBjb21tb247XG5cdFx0XHRhcHAucGFuZWxzLnRoZW1lcyA9IHRoZW1lc1BhbmVsO1xuXHRcdFx0YXBwLnBhbmVscy5jb250YWluZXIgPSBjb250YWluZXJTdHlsZXM7XG5cdFx0XHRhcHAucGFuZWxzLmJhY2tncm91bmQgPSBiYWNrZ3JvdW5kU3R5bGVzO1xuXHRcdFx0YXBwLnBhbmVscy5maWVsZCA9IGZpZWxkU3R5bGVzO1xuXHRcdFx0YXBwLnN0b2NrUGhvdG9zID0gc3RvY2tQaG90b3M7XG5cdFx0XHRhcHAucGFuZWxzLmJ1dHRvbnMgPSBidXR0b25TdHlsZXM7XG5cdFx0XHRhcHAucGFuZWxzLmFkdmFuY2VkID0gYWR2YW5jZWRTZXR0aW5ncztcblxuXHRcdFx0Y29uc3QgYmxvY2tPcHRpb25zID0ge1xuXHRcdFx0XHRwYW5lbHM6IGFwcC5wYW5lbHMsXG5cdFx0XHRcdHN0b2NrUGhvdG9zOiBhcHAuc3RvY2tQaG90b3MsXG5cdFx0XHRcdGdldFRoZW1lc1BhbmVsOiBhcHAucGFuZWxzLnRoZW1lcy5nZXRUaGVtZXNQYW5lbCxcblx0XHRcdFx0Z2V0RmllbGRTdHlsZXM6IGFwcC5wYW5lbHMuZmllbGQuZ2V0RmllbGRTdHlsZXMsXG5cdFx0XHRcdGdldENvbnRhaW5lclN0eWxlczogYXBwLnBhbmVscy5jb250YWluZXIuZ2V0Q29udGFpbmVyU3R5bGVzLFxuXHRcdFx0XHRnZXRCdXR0b25TdHlsZXM6IGFwcC5wYW5lbHMuYnV0dG9ucy5nZXRCdXR0b25TdHlsZXMsXG5cdFx0XHRcdGdldEJhY2tncm91bmRTdHlsZXM6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5nZXRCYWNrZ3JvdW5kU3R5bGVzLFxuXHRcdFx0XHRnZXRDb21tb25BdHRyaWJ1dGVzOiBhcHAuZ2V0Q29tbW9uQXR0cmlidXRlcyxcblx0XHRcdFx0c2V0U3R5bGVzSGFuZGxlcnM6IGFwcC5nZXRTdHlsZUhhbmRsZXJzKCksXG5cdFx0XHRcdGVkdWNhdGlvbjogYXBwLmVkdWNhdGlvbixcblx0XHRcdH07XG5cblx0XHRcdC8vIEluaXRpYWxpemUgQWR2YW5jZWQgU2V0dGluZ3MgbW9kdWxlLlxuXHRcdFx0YXBwLnBhbmVscy5hZHZhbmNlZC5pbml0KCBhcHAuY29tbW9uICk7XG5cblx0XHRcdC8vIEluaXRpYWxpemUgYmxvY2suXG5cdFx0XHRhcHAuY29tbW9uLmluaXQoIGJsb2NrT3B0aW9ucyApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgc3R5bGUgaGFuZGxlcnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gU3R5bGUgaGFuZGxlcnMuXG5cdFx0ICovXG5cdFx0Z2V0Q29tbW9uQXR0cmlidXRlcygpIHtcblx0XHRcdHJldHVybiB7XG5cdFx0XHRcdC4uLmFwcC5wYW5lbHMuZmllbGQuZ2V0QmxvY2tBdHRyaWJ1dGVzKCksXG5cdFx0XHRcdC4uLmFwcC5wYW5lbHMuY29udGFpbmVyLmdldEJsb2NrQXR0cmlidXRlcygpLFxuXHRcdFx0XHQuLi5hcHAucGFuZWxzLmJ1dHRvbnMuZ2V0QmxvY2tBdHRyaWJ1dGVzKCksXG5cdFx0XHRcdC4uLmFwcC5wYW5lbHMuYmFja2dyb3VuZC5nZXRCbG9ja0F0dHJpYnV0ZXMoKSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzdHlsZSBoYW5kbGVycy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBTdHlsZSBoYW5kbGVycy5cblx0XHQgKi9cblx0XHRnZXRTdHlsZUhhbmRsZXJzKCkge1xuXHRcdFx0cmV0dXJuIHtcblx0XHRcdFx0J2JhY2tncm91bmQtaW1hZ2UnOiBhcHAucGFuZWxzLmJhY2tncm91bmQuc2V0Q29udGFpbmVyQmFja2dyb3VuZEltYWdlLFxuXHRcdFx0XHQnYmFja2dyb3VuZC1wb3NpdGlvbic6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRDb250YWluZXJCYWNrZ3JvdW5kUG9zaXRpb24sXG5cdFx0XHRcdCdiYWNrZ3JvdW5kLXJlcGVhdCc6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRDb250YWluZXJCYWNrZ3JvdW5kUmVwZWF0LFxuXHRcdFx0XHQnYmFja2dyb3VuZC13aWR0aCc6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRDb250YWluZXJCYWNrZ3JvdW5kV2lkdGgsXG5cdFx0XHRcdCdiYWNrZ3JvdW5kLWhlaWdodCc6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRDb250YWluZXJCYWNrZ3JvdW5kSGVpZ2h0LFxuXHRcdFx0XHQnYmFja2dyb3VuZC1jb2xvcic6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRCYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdCdiYWNrZ3JvdW5kLXVybCc6IGFwcC5wYW5lbHMuYmFja2dyb3VuZC5zZXRCYWNrZ3JvdW5kVXJsLFxuXHRcdFx0fTtcblx0XHR9LFxuXHR9O1xuXG5cdC8vIFByb3ZpZGUgYWNjZXNzIHRvIHB1YmxpYyBmdW5jdGlvbnMvcHJvcGVydGllcy5cblx0cmV0dXJuIGFwcDtcbn0oKSApO1xuXG4vLyBJbml0aWFsaXplLlxuV1BGb3Jtcy5Gb3JtU2VsZWN0b3IuaW5pdCgpO1xuIl0sIm1hcHBpbmdzIjoiOztBQUVBLElBQUFBLFVBQUEsR0FBQUMsc0JBQUEsQ0FBQUMs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
},{"../../../js/integrations/gutenberg/modules/advanced-settings.js":13,"../../../js/integrations/gutenberg/modules/background-styles.js":15,"../../../js/integrations/gutenberg/modules/button-styles.js":16,"../../../js/integrations/gutenberg/modules/common.js":17,"../../../js/integrations/gutenberg/modules/container-styles.js":18,"../../../js/integrations/gutenberg/modules/education.js":19,"../../../js/integrations/gutenberg/modules/field-styles.js":20,"../../../js/integrations/gutenberg/modules/themes-panel.js":21,"../../../pro/js/integrations/gutenberg/modules/stock-photos.js":22}],13:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.custom_css
 * @param strings.custom_css_notice
 * @param strings.copy_paste_settings
 * @param strings.copy_paste_notice
 */
/**
 * Gutenberg editor block.
 *
 * Advanced Settings module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function ($) {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var addFilter = wp.hooks.addFilter;
  var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
  var Fragment = wp.element.Fragment;
  var _ref = wp.blockEditor || wp.editor,
    InspectorAdvancedControls = _ref.InspectorAdvancedControls;
  var TextareaControl = wp.components.TextareaControl;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings;

  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Initialize module.
     *
     * @since 1.8.8
     *
     * @param {Object} commonModule Common module.
     */
    init: function init(commonModule) {
      app.common = commonModule;
      app.hooks();
      app.events();
    },
    /**
     * Hooks.
     *
     * @since 1.8.8
     */
    hooks: function hooks() {
      addFilter('editor.BlockEdit', 'editorskit/custom-advanced-control', app.withAdvancedControls);
    },
    /**
     * Events.
     *
     * @since 1.8.8
     */
    events: function events() {
      $(document).on('focus click', 'textarea', app.copyPasteFocus);
    },
    /**
     * Copy / Paste Style Settings textarea focus event.
     *
     * @since 1.8.8
     */
    copyPasteFocus: function copyPasteFocus() {
      var $input = $(this);
      if ($input.siblings('label').text() === strings.copy_paste_settings) {
        // Select all text, so it's easier to copy and paste value.
        $input.select();
      }
    },
    /**
     * Get fields.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @return {Object} Inspector advanced controls JSX code.
     */
    getFields: function getFields(props) {
      var _props$attributes;
      // Proceed only for WPForms block and when form ID is set.
      if ((props === null || props === void 0 ? void 0 : props.name) !== 'wpforms/form-selector' || !(props !== null && props !== void 0 && (_props$attributes = props.attributes) !== null && _props$attributes !== void 0 && _props$attributes.formId)) {
        return null;
      }

      // Common event handlers.
      var handlers = app.common.getSettingsFieldsHandlers(props);
      return /*#__PURE__*/React.createElement(InspectorAdvancedControls, null, /*#__PURE__*/React.createElement("div", {
        className: app.common.getPanelClass(props) + ' advanced'
      }, /*#__PURE__*/React.createElement(TextareaControl, {
        className: "wpforms-gutenberg-form-selector-custom-css",
        label: strings.custom_css,
        rows: "5",
        spellCheck: "false",
        value: props.attributes.customCss,
        onChange: function onChange(value) {
          return handlers.attrChange('customCss', value);
        }
      }), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-legend",
        dangerouslySetInnerHTML: {
          __html: strings.custom_css_notice
        }
      }), /*#__PURE__*/React.createElement(TextareaControl, {
        className: "wpforms-gutenberg-form-selector-copy-paste-settings",
        label: strings.copy_paste_settings,
        rows: "4",
        spellCheck: "false",
        value: props.attributes.copyPasteJsonValue,
        onChange: function onChange(value) {
          return handlers.pasteSettings(value);
        }
      }), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-legend",
        dangerouslySetInnerHTML: {
          __html: strings.copy_paste_notice
        }
      })));
    },
    /**
     * Add controls on Advanced Settings Panel.
     *
     * @param {Function} BlockEdit Block edit component.
     *
     * @return {Function} BlockEdit Modified block edit component.
     */
    withAdvancedControls: createHigherOrderComponent(function (BlockEdit) {
      return function (props) {
        return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement(BlockEdit, props), app.getFields(props));
      };
    }, 'withAdvancedControls')
  };

  // Provide access to public functions/properties.
  return app;
}(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiJCIsImFkZEZpbHRlciIsIndwIiwiaG9va3MiLCJjcmVhdGVIaWdoZXJPcmRlckNvbXBvbmVudCIsImNvbXBvc2UiLCJGcmFnbWVudCIsImVsZW1lbnQiLCJfcmVmIiwiYmxvY2tFZGl0b3IiLCJlZGl0b3IiLCJJbnNwZWN0b3JBZHZhbmNlZENvbnRyb2xzIiwiVGV4dGFyZWFDb250cm9sIiwiY29tcG9uZW50cyIsIl93cGZvcm1zX2d1dGVuYmVyZ19mbyIsIndwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IiLCJzdHJpbmdzIiwiYXBwIiwiaW5pdCIsImNvbW1vbk1vZHVsZSIsImNvbW1vbiIsImV2ZW50cyIsIndpdGhBZHZhbmNlZENvbnRyb2xzIiwiZG9jdW1lbnQiLCJvbiIsImNvcHlQYXN0ZUZvY3VzIiwiJGlucHV0Iiwic2libGluZ3MiLCJ0ZXh0IiwiY29weV9wYXN0ZV9zZXR0aW5ncyIsInNlbGVjdCIsImdldEZpZWxkcyIsInByb3BzIiwiX3Byb3BzJGF0dHJpYnV0ZXMiLCJuYW1lIiwiYXR0cmlidXRlcyIsImZvcm1JZCIsImhhbmRsZXJzIiwiZ2V0U2V0dGluZ3NGaWVsZHNIYW5kbGVycyIsIlJlYWN0IiwiY3JlYXRlRWxlbWVudCIsImNsYXNzTmFtZSIsImdldFBhbmVsQ2xhc3MiLCJsYWJlbCIsImN1c3RvbV9jc3MiLCJyb3dzIiwic3BlbGxDaGVjayIsInZhbHVlIiwiY3VzdG9tQ3NzIiwib25DaGFuZ2UiLCJhdHRyQ2hhbmdlIiwiZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUwiLCJfX2h0bWwiLCJjdXN0b21fY3NzX25vdGljZSIsImNvcHlQYXN0ZUpzb25WYWx1ZSIsInBhc3RlU2V0dGluZ3MiLCJjb3B5X3Bhc3RlX25vdGljZSIsIkJsb2NrRWRpdCIsImpRdWVyeSJdLCJzb3VyY2VzIjpbImFkdmFuY2VkLXNldHRpbmdzLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIi8qIGdsb2JhbCB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yICovXG4vKiBqc2hpbnQgZXMzOiBmYWxzZSwgZXN2ZXJzaW9uOiA2ICovXG5cbi8qKlxuICogQHBhcmFtIHN0cmluZ3MuY3VzdG9tX2Nzc1xuICogQHBhcmFtIHN0cmluZ3MuY3VzdG9tX2Nzc19ub3RpY2VcbiAqIEBwYXJhbSBzdHJpbmdzLmNvcHlfcGFzdGVfc2V0dGluZ3NcbiAqIEBwYXJhbSBzdHJpbmdzLmNvcHlfcGFzdGVfbm90aWNlXG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIEFkdmFuY2VkIFNldHRpbmdzIG1vZHVsZS5cbiAqXG4gKiBAc2luY2UgMS44LjhcbiAqL1xuZXhwb3J0IGRlZmF1bHQgKCBmdW5jdGlvbiggJCApIHtcblx0LyoqXG5cdCAqIFdQIGNvcmUgY29tcG9uZW50cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCB7IGFkZEZpbHRlciB9ID0gd3AuaG9va3M7XG5cdGNvbnN0IHsgY3JlYXRlSGlnaGVyT3JkZXJDb21wb25lbnQgfSA9IHdwLmNvbXBvc2U7XG5cdGNvbnN0IHsgRnJhZ21lbnQgfVx0PSB3cC5lbGVtZW50O1xuXHRjb25zdCB7IEluc3BlY3RvckFkdmFuY2VkQ29udHJvbHMgfSA9IHdwLmJsb2NrRWRpdG9yIHx8IHdwLmVkaXRvcjtcblx0Y29uc3QgeyBUZXh0YXJlYUNvbnRyb2wgfSA9IHdwLmNvbXBvbmVudHM7XG5cblx0LyoqXG5cdCAqIExvY2FsaXplZCBkYXRhIGFsaWFzZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKi9cblx0Y29uc3QgeyBzdHJpbmdzIH0gPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yO1xuXG5cdC8qKlxuXHQgKiBQdWJsaWMgZnVuY3Rpb25zIGFuZCBwcm9wZXJ0aWVzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGNvbnN0IGFwcCA9IHtcblx0XHQvKipcblx0XHQgKiBJbml0aWFsaXplIG1vZHVsZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGNvbW1vbk1vZHVsZSBDb21tb24gbW9kdWxlLlxuXHRcdCAqL1xuXHRcdGluaXQoIGNvbW1vbk1vZHVsZSApIHtcblx0XHRcdGFwcC5jb21tb24gPSBjb21tb25Nb2R1bGU7XG5cblx0XHRcdGFwcC5ob29rcygpO1xuXHRcdFx0YXBwLmV2ZW50cygpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBIb29rcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGhvb2tzKCkge1xuXHRcdFx0YWRkRmlsdGVyKFxuXHRcdFx0XHQnZWRpdG9yLkJsb2NrRWRpdCcsXG5cdFx0XHRcdCdlZGl0b3Jza2l0L2N1c3RvbS1hZHZhbmNlZC1jb250cm9sJyxcblx0XHRcdFx0YXBwLndpdGhBZHZhbmNlZENvbnRyb2xzXG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFdmVudHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRldmVudHMoKSB7XG5cdFx0XHQkKCBkb2N1bWVudCApXG5cdFx0XHRcdC5vbiggJ2ZvY3VzIGNsaWNrJywgJ3RleHRhcmVhJywgYXBwLmNvcHlQYXN0ZUZvY3VzICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENvcHkgLyBQYXN0ZSBTdHlsZSBTZXR0aW5ncyB0ZXh0YXJlYSBmb2N1cyBldmVudC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGNvcHlQYXN0ZUZvY3VzKCkge1xuXHRcdFx0Y29uc3QgJGlucHV0ID0gJCggdGhpcyApO1xuXG5cdFx0XHRpZiAoICRpbnB1dC5zaWJsaW5ncyggJ2xhYmVsJyApLnRleHQoKSA9PT0gc3RyaW5ncy5jb3B5X3Bhc3RlX3NldHRpbmdzICkge1xuXHRcdFx0XHQvLyBTZWxlY3QgYWxsIHRleHQsIHNvIGl0J3MgZWFzaWVyIHRvIGNvcHkgYW5kIHBhc3RlIHZhbHVlLlxuXHRcdFx0XHQkaW5wdXQuc2VsZWN0KCk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBmaWVsZHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBJbnNwZWN0b3IgYWR2YW5jZWQgY29udHJvbHMgSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0RmllbGRzKCBwcm9wcyApIHtcblx0XHRcdC8vIFByb2NlZWQgb25seSBmb3IgV1BGb3JtcyBibG9jayBhbmQgd2hlbiBmb3JtIElEIGlzIHNldC5cblx0XHRcdGlmICggcHJvcHM/Lm5hbWUgIT09ICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InIHx8ICEgcHJvcHM/LmF0dHJpYnV0ZXM/LmZvcm1JZCApIHtcblx0XHRcdFx0cmV0dXJuIG51bGw7XG5cdFx0XHR9XG5cblx0XHRcdC8vIENvbW1vbiBldmVudCBoYW5kbGVycy5cblx0XHRcdGNvbnN0IGhhbmRsZXJzID0gYXBwLmNvbW1vbi5nZXRTZXR0aW5nc0ZpZWxkc0hhbmRsZXJzKCBwcm9wcyApO1xuXG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQ8SW5zcGVjdG9yQWR2YW5jZWRDb250cm9scz5cblx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT17IGFwcC5jb21tb24uZ2V0UGFuZWxDbGFzcyggcHJvcHMgKSArICcgYWR2YW5jZWQnIH0+XG5cdFx0XHRcdFx0XHQ8VGV4dGFyZWFDb250cm9sXG5cdFx0XHRcdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY3VzdG9tLWNzc1wiXG5cdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5jdXN0b21fY3NzIH1cblx0XHRcdFx0XHRcdFx0cm93cz1cIjVcIlxuXHRcdFx0XHRcdFx0XHRzcGVsbENoZWNrPVwiZmFsc2VcIlxuXHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuY3VzdG9tQ3NzIH1cblx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuYXR0ckNoYW5nZSggJ2N1c3RvbUNzcycsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1sZWdlbmRcIiBkYW5nZXJvdXNseVNldElubmVySFRNTD17IHsgX19odG1sOiBzdHJpbmdzLmN1c3RvbV9jc3Nfbm90aWNlIH0gfT48L2Rpdj5cblx0XHRcdFx0XHRcdDxUZXh0YXJlYUNvbnRyb2xcblx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb3B5LXBhc3RlLXNldHRpbmdzXCJcblx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmNvcHlfcGFzdGVfc2V0dGluZ3MgfVxuXHRcdFx0XHRcdFx0XHRyb3dzPVwiNFwiXG5cdFx0XHRcdFx0XHRcdHNwZWxsQ2hlY2s9XCJmYWxzZVwiXG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5jb3B5UGFzdGVKc29uVmFsdWUgfVxuXHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5wYXN0ZVNldHRpbmdzKCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItbGVnZW5kXCIgZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUw9eyB7IF9faHRtbDogc3RyaW5ncy5jb3B5X3Bhc3RlX25vdGljZSB9IH0+PC9kaXY+XG5cdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdDwvSW5zcGVjdG9yQWR2YW5jZWRDb250cm9scz5cblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEFkZCBjb250cm9scyBvbiBBZHZhbmNlZCBTZXR0aW5ncyBQYW5lbC5cblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7RnVuY3Rpb259IEJsb2NrRWRpdCBCbG9jayBlZGl0IGNvbXBvbmVudC5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge0Z1bmN0aW9ufSBCbG9ja0VkaXQgTW9kaWZpZWQgYmxvY2sgZWRpdCBjb21wb25lbnQuXG5cdFx0ICovXG5cdFx0d2l0aEFkdmFuY2VkQ29udHJvbHM6IGNyZWF0ZUhpZ2hlck9yZGVyQ29tcG9uZW50KFxuXHRcdFx0KCBCbG9ja0VkaXQgKSA9PiB7XG5cdFx0XHRcdHJldHVybiAoIHByb3BzICkgPT4ge1xuXHRcdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0XHQ8RnJhZ21lbnQ+XG5cdFx0XHRcdFx0XHRcdDxCbG9ja0VkaXQgeyAuLi5wcm9wcyB9IC8+XG5cdFx0XHRcdFx0XHRcdHsgYXBwLmdldEZpZWxkcyggcHJvcHMgKSB9XG5cdFx0XHRcdFx0XHQ8L0ZyYWdtZW50PlxuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdH07XG5cdFx0XHR9LFxuXHRcdFx0J3dpdGhBZHZhbmNlZENvbnRyb2xzJ1xuXHRcdCksXG5cdH07XG5cblx0Ly8gUHJvdmlkZSBhY2Nlc3MgdG8gcHVibGljIGZ1bmN0aW9ucy9wcm9wZXJ0aWVzLlxuXHRyZXR1cm4gYXBwO1xufSggalF1ZXJ5ICkgKTtcbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQUE7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBTkEsSUFBQUEsUUFBQSxHQUFBQyxPQUFBLENBQUFDLE9BQUEsR0FPaUIsVUFBVUMsQ0FBQyxFQUFHO0VBQzlCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxJQUFR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R0FBRztRQUNSQyxVQUFVLEVBQUMsT0FBTztRQUNsQkMsS0FBSyxFQUFHZixLQUFLLENBQUNHLFVBQVUsQ0FBQ2EsU0FBVztRQUNwQ0MsUUFBUSxFQUFHLFNBQVhBLFFBQVFBLENBQUtGLEtBQUs7VUFBQSxPQUFNVixRQUFRLENBQUNhLFVBQVUsQ0FBRSxXQUFXLEVBQUVILEtBQU0sQ0FBQztRQUFBO01BQUUsQ0FDbkUsQ0FBQyxlQUNGUixLQUFBLENBQUFDLGFBQUE7UUFBS0MsU0FBUyxFQUFDLHdDQUF3QztRQUFDVSx1QkFBdUIsRUFBRztVQUFFQyxNQUFNLEVBQUVwQyxPQUFPLENBQUNxQztRQUFrQjtNQUFHLENBQU0sQ0FBQyxlQUNoSWQsS0FBQSxDQUFBQyxhQUFBLENBQUM1QixlQUFlO1FBQ2Y2QixTQUFTLEVBQUMscURBQXFEO1FBQy9ERSxLQUFLLEVBQUczQixPQUFPLENBQUNhLG1CQUFxQjtRQUNyQ2dCLElBQUksRUFBQyxHQUFHO1FBQ1JDLFVBQVUsRUFBQyxPQUFPO1FBQ2xCQyxLQUFLLEVBQUdmLEtBQUssQ0FBQ0csVUFBVSxDQUFDbUIsa0JBQW9CO1FBQzdDTCxRQUFRLEVBQUcsU0FBWEEsUUFBUUEsQ0FBS0YsS0FBSztVQUFBLE9BQU1WLFFBQVEsQ0FBQ2tCLGFBQWEsQ0FBRVIsS0FBTSxDQUFDO1FBQUE7TUFBRSxDQUN6RCxDQUFDLGVBQ0ZSLEtBQUEsQ0FBQUMsYUFBQTtRQUFLQyxTQUFTLEVBQUMsd0NBQXdDO1FBQUNVLHVCQUF1QixFQUFHO1VBQUVDLE1BQU0sRUFBRXBDLE9BQU8sQ0FBQ3dDO1FBQWtCO01BQUcsQ0FBTSxDQUMzSCxDQUNxQixDQUFDO0lBRTlCLENBQUM7SUFFRDtBQUNGO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtJQUNFbEMsb0JBQW9CLEVBQUVsQiwwQkFBMEIsQ0FDL0MsVUFBRXFELFNBQVMsRUFBTTtNQUNoQixPQUFPLFVBQUV6QixLQUFLLEVBQU07UUFDbkIsb0JBQ0NPLEtBQUEsQ0FBQUMsYUFBQSxDQUFDbEMsUUFBUSxxQkFDUmlDLEtBQUEsQ0FBQUMsYUFBQSxDQUFDaUIsU0FBUyxFQUFNekIsS0FBUyxDQUFDLEVBQ3hCZixHQUFHLENBQUNjLFNBQVMsQ0FBRUMsS0FBTSxDQUNkLENBQUM7TUFFYixDQUFDO0lBQ0YsQ0FBQyxFQUNELHNCQUNEO0VBQ0QsQ0FBQzs7RUFFRDtFQUNBLE9BQU9mLEdBQUc7QUFDWCxDQUFDLENBQUV5QyxNQUFPLENBQUMiLCJpZ25vcmVMaXN0IjpbXX0=
},{}],14:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _propTypes = _interopRequireDefault(require("prop-types"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */

/**
 * @param strings.remove_image
 */

/**
 * React component for the background preview.
 *
 * @since 1.8.8
 *
 * @param {Object}   props                    Component props.
 * @param {Object}   props.attributes         Block attributes.
 * @param {Function} props.onRemoveBackground Function to remove the background.
 * @param {Function} props.onPreviewClicked   Function to handle the preview click.
 *
 * @return {Object} React component.
 */
var BackgroundPreview = function BackgroundPreview(_ref) {
  var attributes = _ref.attributes,
    onRemoveBackground = _ref.onRemoveBackground,
    onPreviewClicked = _ref.onPreviewClicked;
  var Button = wp.components.Button;
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings;
  return /*#__PURE__*/React.createElement("div", {
    className: "wpforms-gutenberg-form-selector-background-preview"
  }, /*#__PURE__*/React.createElement("style", null, "\n\t\t\t\t\t.wpforms-gutenberg-form-selector-background-preview-image {\n\t\t\t\t\t\t--wpforms-background-url: ".concat(attributes.backgroundUrl, ";\n\t\t\t\t\t}\n\t\t\t\t")), /*#__PURE__*/React.createElement("input", {
    className: "wpforms-gutenberg-form-selector-background-preview-image",
    onClick: onPreviewClicked,
    tabIndex: 0,
    type: "button",
    onKeyDown: function onKeyDown(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        onPreviewClicked();
      }
    }
  }), /*#__PURE__*/React.createElement(Button, {
    isSecondary: true,
    className: "is-destructive",
    onClick: onRemoveBackground
  }, strings.remove_image));
};
BackgroundPreview.propTypes = {
  attributes: _propTypes.default.object.isRequired,
  onRemoveBackground: _propTypes.default.func.isRequired,
  onPreviewClicked: _propTypes.default.func.isRequired
};
var _default = exports.default = BackgroundPreview;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfcHJvcFR5cGVzIiwiX2ludGVyb3BSZXF1aXJlRGVmYXVsdCIsInJlcXVpcmUiLCJlIiwiX19lc01vZHVsZSIsImRlZmF1bHQiLCJCYWNrZ3JvdW5kUHJldmlldyIsIl9yZWYiLCJhdHRyaWJ1dGVzIiwib25SZW1vdmVCYWNrZ3JvdW5kIiwib25QcmV2aWV3Q2xpY2tlZCIsIkJ1dHRvbiIsIndwIiwiY29tcG9uZW50cyIsIl93cGZvcm1zX2d1dGVuYmVyZ19mbyIsIndwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IiLCJzdHJpbmdzIiwiUmVhY3QiLCJjcmVhdGVFbGVtZW50IiwiY2xhc3NOYW1lIiwiY29uY2F0IiwiYmFja2dyb3VuZFVybCIsIm9uQ2xpY2siLCJ0YWJJbmRleCIsInR5cGUiLCJvbktleURvd24iLCJldmVudCIsImtleSIsImlzU2Vjb25kYXJ5IiwicmVtb3ZlX2ltYWdlIiwicHJvcFR5cGVzIiwiUHJvcFR5cGVzIiwib2JqZWN0IiwiaXNSZXF1aXJlZCIsImZ1bmMiLCJfZGVmYXVsdCIsImV4cG9ydHMiXSwic291cmNlcyI6WyJiYWNrZ3JvdW5kLXByZXZpZXcuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IgKi9cbi8qIGpzaGludCBlczM6IGZhbHNlLCBlc3ZlcnNpb246IDYgKi9cblxuaW1wb3J0IFByb3BUeXBlcyBmcm9tICdwcm9wLXR5cGVzJztcblxuLyoqXG4gKiBAcGFyYW0gc3RyaW5ncy5yZW1vdmVfaW1hZ2VcbiAqL1xuXG4vKipcbiAqIFJlYWN0IGNvbXBvbmVudCBmb3IgdGhlIGJhY2tncm91bmQgcHJldmlldy5cbiAqXG4gKiBAc2luY2UgMS44LjhcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gICBwcm9wcyAgICAgICAgICAgICAgICAgICAgQ29tcG9uZW50IHByb3BzLlxuICogQHBhcmFtIHtPYmplY3R9ICAgcHJvcHMuYXR0cmlidXRlcyAgICAgICAgIEJsb2NrIGF0dHJpYnV0ZXMuXG4gKiBAcGFyYW0ge0Z1bmN0aW9ufSBwcm9wcy5vblJlbW92ZUJhY2tncm91bmQgRnVuY3Rpb24gdG8gcmVtb3ZlIHRoZSBiYWNrZ3JvdW5kLlxuICogQHBhcmFtIHtGdW5jdGlvbn0gcHJvcHMub25QcmV2aWV3Q2xpY2tlZCAgIEZ1bmN0aW9uIHRvIGhhbmRsZSB0aGUgcHJldmlldyBjbGljay5cbiAqXG4gKiBAcmV0dXJuIHtPYmplY3R9IFJlYWN0IGNvbXBvbmVudC5cbiAqL1xuY29uc3QgQmFja2dyb3VuZFByZXZpZXcgPSAoIHsgYXR0cmlidXRlcywgb25SZW1vdmVCYWNrZ3JvdW5kLCBvblByZXZpZXdDbGlja2VkIH0gKSA9PiB7XG5cdGNvbnN0IHsgQnV0dG9uIH0gPSB3cC5jb21wb25lbnRzO1xuXHRjb25zdCB7IHN0cmluZ3MgfSA9IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3I7XG5cblx0cmV0dXJuIChcblx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItYmFja2dyb3VuZC1wcmV2aWV3XCI+XG5cdFx0XHQ8c3R5bGU+XG5cdFx0XHRcdHsgYFxuXHRcdFx0XHRcdC53cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWJhY2tncm91bmQtcHJldmlldy1pbWFnZSB7XG5cdFx0XHRcdFx0XHQtLXdwZm9ybXMtYmFja2dyb3VuZC11cmw6ICR7IGF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCB9O1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0YCB9XG5cdFx0XHQ8L3N0eWxlPlxuXHRcdFx0PGlucHV0XG5cdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItYmFja2dyb3VuZC1wcmV2aWV3LWltYWdlXCJcblx0XHRcdFx0b25DbGljaz17IG9uUHJldmlld0NsaWNrZWQgfVxuXHRcdFx0XHR0YWJJbmRleD17IDAgfVxuXHRcdFx0XHR0eXBlPVwiYnV0dG9uXCJcblx0XHRcdFx0b25LZXlEb3duPXtcblx0XHRcdFx0XHQoIGV2ZW50ICkgPT4ge1xuXHRcdFx0XHRcdFx0aWYgKCBldmVudC5rZXkgPT09ICdFbnRlcicgfHwgZXZlbnQua2V5ID09PSAnICcgKSB7XG5cdFx0XHRcdFx0XHRcdG9uUHJldmlld0NsaWNrZWQoKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdD5cblx0XHRcdDwvaW5wdXQ+XG5cdFx0XHQ8QnV0dG9uXG5cdFx0XHRcdGlzU2Vjb25kYXJ5XG5cdFx0XHRcdGNsYXNzTmFtZT1cImlzLWRlc3RydWN0aXZlXCJcblx0XHRcdFx0b25DbGljaz17IG9uUmVtb3ZlQmFja2dyb3VuZCB9XG5cdFx0XHQ+XG5cdFx0XHRcdHsgc3RyaW5ncy5yZW1vdmVfaW1hZ2UgfVxuXHRcdFx0PC9CdXR0b24+XG5cdFx0PC9kaXY+XG5cdCk7XG59O1xuXG5CYWNrZ3JvdW5kUHJldmlldy5wcm9wVHlwZXMgPSB7XG5cdGF0dHJpYnV0ZXM6IFByb3BUeXBlcy5vYmplY3QuaXNSZXF1aXJlZCxcblx0b25SZW1vdmVCYWNrZ3JvdW5kOiBQcm9wVHlwZXMuZnVuYy5pc1JlcXVpcmVkLFxuXHRvblByZXZpZXdDbGlja2VkOiBQcm9wVHlwZXMuZnVuYy5pc1JlcXVpcmVkLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgQmFja2dyb3VuZFByZXZpZXc7XG4iXSwibWFwcGluZ3MiOiI7Ozs7OztBQUdBLElBQUFBLFVBQUEsR0FBQUMsc0JBQUEsQ0FBQUMsT0FBQTtBQUFtQyxTQUFBRCx1QkFBQUUsQ0FBQSxXQUFBQSxDQUFBLElBQUFBLENBQUEsQ0FBQUMsVUFBQSxHQUFBRCxDQUFBLEtBQUFFLE9BQUEsRUFBQUYsQ0FBQTtBQUhuQztBQUNBOztBQUlBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxJQUFNRyxpQkFBaUIsR0FBRyxTQUFwQkEsaUJBQWlCQSxDQUFBQyxJQUFBLEVBQStEO0VBQUEsSUFBeERDLFVBQVUsR0FBQUQsSUFBQSxDQUFWQyxVQUFVO0lBQUVDLGtCQUFrQixHQUFBRixJQUFBLENBQWxCRSxrQkFBa0I7SUFBRUMsZ0JBQWdCLEdBQUFILElBQUEsQ0FBaEJHLGdCQUFnQjtFQUM3RSxJQUFRQyxNQUFNLEdBQUtDLEVBQUUsQ0FBQ0MsVUFBVSxDQUF4QkYsTUFBTTtFQUNkLElBQUFHLHFCQUFBLEdBQW9CQywrQkFBK0I7SUFBM0NDLE9BQU8sR0FBQUYscUJBQUEsQ0FBUEUsT0FBTztFQUVmLG9CQUNDQyxLQUFBLENBQUFDLGFBQUE7SUFBS0MsU0FBUyxFQUFDO0VBQW9ELGdCQUNsRUYsS0FBQSxDQUFBQyxhQUFBLGtJQUFBRSxNQUFBLENBR2dDWixVQUFVLENBQUNhLGFBQWEsNkJBR2pELENBQUMsZUFDUkosS0FBQSxDQUFBQyxhQUFBO0lBQ0NDLFNBQVMsRUFBQywwREFBMEQ7SUFDcEVHLE9BQU8sRUFBR1osZ0JBQWtCO0lBQzVCYSxRQUFRLEVBQUcsQ0FBRztJQUNkQyxJQUFJLEVBQUMsUUFBUTtJQUNiQyxTQUFTLEVBQ1IsU0FEREEsU0FBU0EsQ0FDTkMsS0FBSyxFQUFNO01BQ1osSUFBS0EsS0FBSyxDQUFDQyxHQUFHLEtBQUssT0FBTyxJQUFJRCxLQUFLLENBQUNDLEdBQUcsS0FBSyxHQUFHLEVBQUc7UUFDakRqQixnQkFBZ0IsQ0FBQyxDQUFDO01BQ25CO0lBQ0Q7RUFDQSxDQUVLLENBQUMsZUFDUk8sS0FBQSxDQUFBQyxhQUFBLENBQUNQLE1BQU07SUFDTmlCLFdBQVc7SUFDWFQsU0FBUyxFQUFDLGdCQUFnQjtJQUMxQkcsT0FBTyxFQUFHYjtFQUFvQixHQUU1Qk8sT0FBTyxDQUFDYSxZQUNILENBQ0osQ0FBQztBQUVSLENBQUM7QUFFRHZCLGlCQUFpQixDQUFDd0IsU0FBUyxHQUFHO0VBQzdCdEIsVUFBVSxFQUFFdUIsa0JBQVMsQ0FBQ0MsTUFBTSxDQUFDQyxVQUFVO0VBQ3ZDeEIsa0JBQWtCLEVBQUVzQixrQkFBUyxDQUFDRyxJQUFJLENBQUNELFVBQVU7RUFDN0N2QixnQkFBZ0IsRUFBRXFCLGtCQUFTLENBQUNHLElBQUksQ0FBQ0Q7QUFDbEMsQ0FBQztBQUFDLElBQUFFLFFBQUEsR0FBQUMsT0FBQSxDQUFBL0IsT0FBQSxHQUVhQyxpQkFBaUIiLCJpZ25vcmVMaXN0IjpbXX0=
},{"prop-types":6}],15:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _backgroundPreview = _interopRequireDefault(require("./background-preview.js"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.background_styles
 * @param strings.bottom_center
 * @param strings.bottom_left
 * @param strings.bottom_right
 * @param strings.center_center
 * @param strings.center_left
 * @param strings.center_right
 * @param strings.choose_image
 * @param strings.image_url
 * @param strings.media_library
 * @param strings.no_repeat
 * @param strings.repeat_x
 * @param strings.repeat_y
 * @param strings.select_background_image
 * @param strings.select_image
 * @param strings.stock_photo
 * @param strings.tile
 * @param strings.top_center
 * @param strings.top_left
 * @param strings.top_right
 */
/**
 * Gutenberg editor block.
 *
 * Background styles panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function () {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _ref = wp.blockEditor || wp.editor,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    PanelBody = _wp$components.PanelBody,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl,
    TextControl = _wp$components.TextControl,
    Button = _wp$components.Button;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults;

  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Get block attributes.
     *
     * @since 1.8.8
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      return {
        backgroundImage: {
          type: 'string',
          default: defaults.backgroundImage
        },
        backgroundPosition: {
          type: 'string',
          default: defaults.backgroundPosition
        },
        backgroundRepeat: {
          type: 'string',
          default: defaults.backgroundRepeat
        },
        backgroundSizeMode: {
          type: 'string',
          default: defaults.backgroundSizeMode
        },
        backgroundSize: {
          type: 'string',
          default: defaults.backgroundSize
        },
        backgroundWidth: {
          type: 'string',
          default: defaults.backgroundWidth
        },
        backgroundHeight: {
          type: 'string',
          default: defaults.backgroundHeight
        },
        backgroundColor: {
          type: 'string',
          default: defaults.backgroundColor
        },
        backgroundUrl: {
          type: 'string',
          default: defaults.backgroundUrl
        }
      };
    },
    /**
     * Get Background Styles panel JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props              Block properties.
     * @param {Object} handlers           Block handlers.
     * @param {Object} formSelectorCommon Block properties.
     * @param {Object} stockPhotos        Stock Photos module.
     * @param {Object} uiState            UI state.
     *
     * @return {Object} Field styles JSX code.
     */
    getBackgroundStyles: function getBackgroundStyles(props, handlers, formSelectorCommon, stockPhotos, uiState) {
      // eslint-disable-line max-lines-per-function, complexity
      var isNotDisabled = uiState.isNotDisabled;
      var isProEnabled = uiState.isProEnabled;
      var showBackgroundPreview = uiState.showBackgroundPreview;
      var setShowBackgroundPreview = uiState.setShowBackgroundPreview;
      var lastBgImage = uiState.lastBgImage;
      var setLastBgImage = uiState.setLastBgImage;
      var tabIndex = isNotDisabled ? 0 : -1;
      var cssClass = formSelectorCommon.getPanelClass(props) + (isNotDisabled ? '' : ' wpforms-gutenberg-panel-disabled');
      return /*#__PURE__*/React.createElement(PanelBody, {
        className: cssClass,
        title: strings.background_styles
      }, /*#__PURE__*/React.createElement("div", {
        // eslint-disable-line jsx-a11y/no-static-element-interactions
        className: "wpforms-gutenberg-form-selector-panel-body",
        onClick: function onClick(event) {
          if (isNotDisabled) {
            return;
          }
          event.stopPropagation();
          if (!isProEnabled) {
            return formSelectorCommon.education.showProModal('background', strings.background_styles);
          }
          formSelectorCommon.education.showLicenseModal('background', strings.background_styles, 'background-styles');
        },
        onKeyDown: function onKeyDown(event) {
          if (isNotDisabled) {
            return;
          }
          event.stopPropagation();
          if (!isProEnabled) {
            return formSelectorCommon.education.showProModal('background', strings.background_styles);
          }
          formSelectorCommon.education.showLicenseModal('background', strings.background_styles, 'background-styles');
        }
      }, /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.image,
        tabIndex: tabIndex,
        value: props.attributes.backgroundImage,
        options: [{
          label: strings.none,
          value: 'none'
        }, {
          label: strings.media_library,
          value: 'library'
        }, {
          label: strings.stock_photo,
          value: 'stock'
        }],
        onChange: function onChange(value) {
          return app.setContainerBackgroundImageWrapper(props, handlers, value, lastBgImage, setLastBgImage);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, (props.attributes.backgroundImage !== 'none' || !isNotDisabled) && /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.position,
        value: props.attributes.backgroundPosition,
        tabIndex: tabIndex,
        options: [{
          label: strings.top_left,
          value: 'top left'
        }, {
          label: strings.top_center,
          value: 'top center'
        }, {
          label: strings.top_right,
          value: 'top right'
        }, {
          label: strings.center_left,
          value: 'center left'
        }, {
          label: strings.center_center,
          value: 'center center'
        }, {
          label: strings.center_right,
          value: 'center right'
        }, {
          label: strings.bottom_left,
          value: 'bottom left'
        }, {
          label: strings.bottom_center,
          value: 'bottom center'
        }, {
          label: strings.bottom_right,
          value: 'bottom right'
        }],
        disabled: props.attributes.backgroundImage === 'none' && isNotDisabled,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('backgroundPosition', value);
        }
      }))), (props.attributes.backgroundImage !== 'none' || !isNotDisabled) && /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.repeat,
        tabIndex: tabIndex,
        value: props.attributes.backgroundRepeat,
        options: [{
          label: strings.no_repeat,
          value: 'no-repeat'
        }, {
          label: strings.tile,
          value: 'repeat'
        }, {
          label: strings.repeat_x,
          value: 'repeat-x'
        }, {
          label: strings.repeat_y,
          value: 'repeat-y'
        }],
        disabled: props.attributes.backgroundImage === 'none' && isNotDisabled,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('backgroundRepeat', value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.size,
        tabIndex: tabIndex,
        value: props.attributes.backgroundSizeMode,
        options: [{
          label: strings.dimensions,
          value: 'dimensions'
        }, {
          label: strings.cover,
          value: 'cover'
        }],
        disabled: props.attributes.backgroundImage === 'none' && isNotDisabled,
        onChange: function onChange(value) {
          return app.handleSizeFromDimensions(props, handlers, value);
        }
      }))), (props.attributes.backgroundSizeMode === 'dimensions' && props.attributes.backgroundImage !== 'none' || !isNotDisabled) && /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.width,
        tabIndex: tabIndex,
        value: props.attributes.backgroundWidth,
        isUnitSelectTabbable: isNotDisabled,
        onChange: function onChange(value) {
          return app.handleSizeFromWidth(props, handlers, value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.height,
        tabIndex: tabIndex,
        value: props.attributes.backgroundHeight,
        isUnitSelectTabbable: isNotDisabled,
        onChange: function onChange(value) {
          return app.handleSizeFromHeight(props, handlers, value);
        }
      }))), (!showBackgroundPreview || props.attributes.backgroundUrl === 'url()') && (props.attributes.backgroundImage === 'library' && /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(Button, {
        isSecondary: true,
        tabIndex: tabIndex,
        className: 'wpforms-gutenberg-form-selector-media-library-button',
        onClick: app.openMediaLibrary.bind(null, props, handlers, setShowBackgroundPreview)
      }, strings.choose_image))) || props.attributes.backgroundImage === 'stock' && /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(Button, {
        isSecondary: true,
        tabIndex: tabIndex,
        className: 'wpforms-gutenberg-form-selector-media-library-button',
        onClick: stockPhotos === null || stockPhotos === void 0 ? void 0 : stockPhotos.openModal.bind(null, props, handlers, 'bg-styles', setShowBackgroundPreview)
      }, strings.choose_image)))), (showBackgroundPreview && props.attributes.backgroundImage !== 'none' || props.attributes.backgroundUrl !== 'url()') && /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(_backgroundPreview.default, {
        attributes: props.attributes,
        onRemoveBackground: function onRemoveBackground() {
          app.onRemoveBackground(setShowBackgroundPreview, handlers, setLastBgImage);
        },
        onPreviewClicked: function onPreviewClicked() {
          if (props.attributes.backgroundImage === 'library') {
            return app.openMediaLibrary(props, handlers, setShowBackgroundPreview);
          }
          return stockPhotos === null || stockPhotos === void 0 ? void 0 : stockPhotos.openModal(props, handlers, 'bg-styles', setShowBackgroundPreview);
        }
      })), /*#__PURE__*/React.createElement(TextControl, {
        label: strings.image_url,
        tabIndex: tabIndex,
        value: props.attributes.backgroundImage !== 'none' && props.attributes.backgroundUrl,
        className: 'wpforms-gutenberg-form-selector-image-url',
        onChange: function onChange(value) {
          return handlers.styleAttrChange('backgroundUrl', value);
        },
        onLoad: function onLoad(value) {
          return props.attributes.backgroundImage !== 'none' && handlers.styleAttrChange('backgroundUrl', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-control-label"
      }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
        __experimentalIsRenderedInSidebar: true,
        enableAlpha: true,
        showTitle: false,
        tabIndex: tabIndex,
        className: "wpforms-gutenberg-form-selector-color-panel",
        colorSettings: [{
          value: props.attributes.backgroundColor,
          onChange: function onChange(value) {
            if (!isNotDisabled) {
              return;
            }
            handlers.styleAttrChange('backgroundColor', value);
          },
          label: strings.background
        }]
      })))));
    },
    /**
     * Open media library modal and handle image selection.
     *
     * @since 1.8.8
     *
     * @param {Object}   props                    Block properties.
     * @param {Object}   handlers                 Block handlers.
     * @param {Function} setShowBackgroundPreview Set show background preview.
     */
    openMediaLibrary: function openMediaLibrary(props, handlers, setShowBackgroundPreview) {
      var frame = wp.media({
        title: strings.select_background_image,
        multiple: false,
        library: {
          type: 'image'
        },
        button: {
          text: strings.select_image
        }
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        var setAttr = {};
        var attribute = 'backgroundUrl';
        if (attachment.url) {
          var value = "url(".concat(attachment.url, ")");
          setAttr[attribute] = value;
          props.setAttributes(setAttr);
          handlers.styleAttrChange('backgroundUrl', value);
          setShowBackgroundPreview(true);
        }
      });
      frame.open();
    },
    /**
     * Set container background image.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setContainerBackgroundImage: function setContainerBackgroundImage(container, value) {
      if (value === 'none') {
        container.style.setProperty("--wpforms-background-url", 'url()');
      }
      return true;
    },
    /**
     * Set container background image.
     *
     * @since 1.8.8
     *
     * @param {Object}   props          Block properties.
     * @param {Object}   handlers       Block event handlers.
     * @param {string}   value          Value.
     * @param {string}   lastBgImage    Last background image.
     * @param {Function} setLastBgImage Set last background image.
     */
    setContainerBackgroundImageWrapper: function setContainerBackgroundImageWrapper(props, handlers, value, lastBgImage, setLastBgImage) {
      if (value === 'none') {
        setLastBgImage(props.attributes.backgroundUrl);
        props.attributes.backgroundUrl = 'url()';
        handlers.styleAttrChange('backgroundUrl', 'url()');
      } else if (lastBgImage) {
        props.attributes.backgroundUrl = lastBgImage;
        handlers.styleAttrChange('backgroundUrl', lastBgImage);
      }
      handlers.styleAttrChange('backgroundImage', value);
    },
    /**
     * Set container background position.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setContainerBackgroundPosition: function setContainerBackgroundPosition(container, value) {
      container.style.setProperty("--wpforms-background-position", value);
      return true;
    },
    /**
     * Set container background repeat.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setContainerBackgroundRepeat: function setContainerBackgroundRepeat(container, value) {
      container.style.setProperty("--wpforms-background-repeat", value);
      return true;
    },
    /**
     * Handle real size from dimensions.
     *
     * @since 1.8.8
     *
     * @param {Object} props    Block properties.
     * @param {Object} handlers Block handlers.
     * @param {string} value    Value.
     */
    handleSizeFromDimensions: function handleSizeFromDimensions(props, handlers, value) {
      if (value === 'cover') {
        props.attributes.backgroundSize = 'cover';
        handlers.styleAttrChange('backgroundWidth', props.attributes.backgroundWidth);
        handlers.styleAttrChange('backgroundHeight', props.attributes.backgroundHeight);
        handlers.styleAttrChange('backgroundSizeMode', 'cover');
        handlers.styleAttrChange('backgroundSize', 'cover');
      } else {
        props.attributes.backgroundSize = 'dimensions';
        handlers.styleAttrChange('backgroundSizeMode', 'dimensions');
        handlers.styleAttrChange('backgroundSize', props.attributes.backgroundWidth + ' ' + props.attributes.backgroundHeight);
      }
    },
    /**
     * Handle real size from width.
     *
     * @since 1.8.8
     *
     * @param {Object} props    Block properties.
     * @param {Object} handlers Block handlers.
     * @param {string} value    Value.
     */
    handleSizeFromWidth: function handleSizeFromWidth(props, handlers, value) {
      props.attributes.backgroundSize = value + ' ' + props.attributes.backgroundHeight;
      props.attributes.backgroundWidth = value;
      handlers.styleAttrChange('backgroundSize', value + ' ' + props.attributes.backgroundHeight);
      handlers.styleAttrChange('backgroundWidth', value);
    },
    /**
     * Handle real size from height.
     *
     * @since 1.8.8
     *
     * @param {Object} props    Block properties.
     * @param {Object} handlers Block handlers.
     * @param {string} value    Value.
     */
    handleSizeFromHeight: function handleSizeFromHeight(props, handlers, value) {
      props.attributes.backgroundSize = props.attributes.backgroundWidth + ' ' + value;
      props.attributes.backgroundHeight = value;
      handlers.styleAttrChange('backgroundSize', props.attributes.backgroundWidth + ' ' + value);
      handlers.styleAttrChange('backgroundHeight', value);
    },
    /**
     * Set container background width.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setContainerBackgroundWidth: function setContainerBackgroundWidth(container, value) {
      container.style.setProperty("--wpforms-background-width", value);
      return true;
    },
    /**
     * Set container background height.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setContainerBackgroundHeight: function setContainerBackgroundHeight(container, value) {
      container.style.setProperty("--wpforms-background-height", value);
      return true;
    },
    /**
     * Set container background url.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setBackgroundUrl: function setBackgroundUrl(container, value) {
      container.style.setProperty("--wpforms-background-url", value);
      return true;
    },
    /**
     * Set container background color.
     *
     * @since 1.8.8
     *
     * @param {HTMLElement} container Container element.
     * @param {string}      value     Value.
     *
     * @return {boolean} True if the value was set, false otherwise.
     */
    setBackgroundColor: function setBackgroundColor(container, value) {
      container.style.setProperty("--wpforms-background-color", value);
      return true;
    },
    _showBackgroundPreview: function _showBackgroundPreview(props) {
      return props.attributes.backgroundImage !== 'none' && props.attributes.backgroundUrl && props.attributes.backgroundUrl !== 'url()';
    },
    /**
     * Remove background image.
     *
     * @since 1.8.8
     *
     * @param {Function} setShowBackgroundPreview Set show background preview.
     * @param {Object}   handlers                 Block handlers.
     * @param {Function} setLastBgImage           Set last background image.
     */
    onRemoveBackground: function onRemoveBackground(setShowBackgroundPreview, handlers, setLastBgImage) {
      setShowBackgroundPreview(false);
      handlers.styleAttrChange('backgroundUrl', 'url()');
      setLastBgImage('');
    }
  };
  return app;
}();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfYmFja2dyb3VuZFByZXZpZXciLCJfaW50ZXJvcFJlcXVpcmVEZWZhdWx0IiwicmVxdWlyZSIsImUiLCJfX2VzTW9kdWxlIiwiZGVmYXVsdCIsIl9kZWZhdWx0IiwiZXhwb3J0cyIsIl9yZWYiLCJ3cCIsImJsb2NrRWRpdG9yIiwiZWRpdG9yIiwiUGFuZWxDb2xvclNldHRpbmdzIiwiX3dwJGNvbXBvbmVudHMiLCJjb21wb25lbnRzIiwiU2VsZWN0Q29udHJvbCIsIlBhbmVsQm9keSIsIkZsZXgiLCJGbGV4QmxvY2siLCJfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sIiwiVGV4dENvbnRyb2wiLCJCdXR0b24iLCJfd3Bmb3Jtc19ndXRlbmJlcmdfZm8iLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwic3RyaW5ncyIsImRlZmF1bHRzIiwiYXBwIiwiZ2V0QmxvY2tBdHRyaWJ1dGVzIiwiYmFja2dyb3VuZEltYWdlIiwidHlwZSIsImJhY2tncm91bmRQb3NpdGlvbiIsImJhY2tncm91bmRSZXBlYXQiLCJiYWNrZ3JvdW5kU2l6ZU1vZGUiLCJiYWNrZ3JvdW5kU2l6ZSIsImJhY2tncm91bmRXaWR0aCIsImJhY2tncm91bmRIZWlnaHQiLCJiYWNrZ3JvdW5kQ29sb3IiLCJiYWNrZ3JvdW5kVXJsIiwiZ2V0QmFja2dyb3VuZFN0eWxlcyIsInByb3BzIiwiaGFuZGxlcnMiLCJmb3JtU2VsZWN0b3JDb21tb24iLCJzdG9ja1Bob3RvcyIsInVpU3RhdGUiLCJpc05vdERpc2FibGVkIiwiaXNQcm9FbmFibGVkIiwic2hvd0JhY2tncm91bmRQcmV2aWV3Iiwic2V0U2hvd0JhY2tncm91bmRQcmV2aWV3IiwibGFzdEJnSW1hZ2UiLCJzZXRMYXN0QmdJbWFnZSIsInRhYkluZGV4IiwiY3NzQ2xhc3MiLCJnZXRQYW5lbENsYXNzIiwiUmVhY3QiLCJjcmVhdGVFbGVtZW50IiwiY2xhc3NOYW1lIiwidGl0bGUiLCJiYWNrZ3JvdW5kX3N0eWxlcyIsIm9uQ2xpY2siLCJldmVudCIsInN0b3BQcm9wYWdhdGlvbiIsImVkdWNhdGlvbiIsInNob3dQcm9Nb2RhbCIsInNob3dMaWNlbnNlTW9kYWwiLCJvbktleURvd24iLCJnYXAiLCJhbGlnbiIsImp1c3RpZnkiLCJsYWJlbCIsImltYWdlIiwidmFsdWUiLCJhdHRyaWJ1dGVzIiwib3B0aW9ucyIsIm5vbmUiLCJtZWRpYV9saWJyYXJ5Iiwic3RvY2tfcGhvdG8iLCJvbkNoYW5nZSIsInNldENvbnRhaW5lckJhY2tncm91bmRJbWFnZVdyYXBwZXIiLCJwb3NpdGlvbiIsInRvcF9sZWZ0IiwidG9wX2NlbnRlciIsInRvcF9yaWdodCIsImNlbnRlcl9sZWZ0IiwiY2VudGVyX2NlbnRlciIsImNlbnRlcl9yaWdodCIsImJvdHRvbV9sZWZ0IiwiYm90dG9tX2NlbnRlciIsImJvdHRvbV9yaWdodCIsImRpc2FibGVkIiwic3R5bGVBdHRyQ2hhbmdlIiwicmVwZWF0Iiwibm9fcmVwZWF0IiwidGlsZSIsInJlcGVhdF94IiwicmVwZWF0X3kiLCJzaXplIiwiZGltZW5zaW9ucyIsImNvdmVyIiwiaGFuZGxlU2l6ZUZyb21EaW1lbnNpb25zIiwid2lkdGgiLCJpc1VuaXRTZWxlY3RUYWJiYWJsZSIsImhhbmRsZVNpemVGcm9tV2lkdGgiLCJoZWlnaHQiLCJoYW5kbGVTaXplRnJvbUhlaWdodCIsImlzU2Vjb25kYXJ5Iiwib3Blbk1lZGlhTGlicmFyeSIsImJpbmQiLCJjaG9vc2VfaW1hZ2UiLCJvcGVuTW9kYWwiLCJvblJlbW92ZUJhY2tncm91bmQiLCJvblByZXZpZXdDbGlja2VkIiwiaW1hZ2VfdXJsIiwib25Mb2FkIiwiY29sb3JzIiwiX19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyIiwiZW5hYmxlQWxwaGEiLCJzaG93VGl0bGUiLCJjb2xvclNldHRpbmdzIiwiYmFja2dyb3VuZCIsImZyYW1lIiwibWVkaWEiLCJzZWxlY3RfYmFja2dyb3VuZF9pbWFnZSIsIm11bHRpcGxlIiwibGlicmFyeSIsImJ1dHRvbiIsInRleHQiLCJzZWxlY3RfaW1hZ2UiLCJvbiIsImF0dGFjaG1lbnQiLCJzdGF0ZSIsImdldCIsImZpcnN0IiwidG9KU09OIiwic2V0QXR0ciIsImF0dHJpYnV0ZSIsInVybCIsImNvbmNhdCIsInNldEF0dHJpYnV0ZXMiLCJvcGVuIiwic2V0Q29udGFpbmVyQmFja2dyb3VuZEltYWdlIiwiY29udGFpbmVyIiwic3R5bGUiLCJzZXRQcm9wZXJ0eSIsInNldENvbnRhaW5lckJhY2tncm91bmRQb3NpdGlvbiIsInNldENvbnRhaW5lckJhY2tncm91bmRSZXBlYXQiLCJzZXRDb250YWluZXJCYWNrZ3JvdW5kV2lkdGgiLCJzZXRDb250YWluZXJCYWNrZ3JvdW5kSGVpZ2h0Iiwic2V0QmFja2dyb3VuZFVybCIsInNldEJhY2tncm91bmRDb2xvciIsIl9zaG93QmFja2dyb3VuZFByZXZpZXciXSwic291cmNlcyI6WyJiYWNrZ3JvdW5kLXN0eWxlcy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG5pbXBvcnQgQmFja2dyb3VuZFByZXZpZXcgZnJvbSAnLi9iYWNrZ3JvdW5kLXByZXZpZXcuanMnO1xuXG4vKipcbiAqIEBwYXJhbSBzdHJpbmdzLmJhY2tncm91bmRfc3R5bGVzXG4gKiBAcGFyYW0gc3RyaW5ncy5ib3R0b21fY2VudGVyXG4gKiBAcGFyYW0gc3RyaW5ncy5ib3R0b21fbGVmdFxuICogQHBhcmFtIHN0cmluZ3MuYm90dG9tX3JpZ2h0XG4gKiBAcGFyYW0gc3RyaW5ncy5jZW50ZXJfY2VudGVyXG4gKiBAcGFyYW0gc3RyaW5ncy5jZW50ZXJfbGVmdFxuICogQHBhcmFtIHN0cmluZ3MuY2VudGVyX3JpZ2h0XG4gKiBAcGFyYW0gc3RyaW5ncy5jaG9vc2VfaW1hZ2VcbiAqIEBwYXJhbSBzdHJpbmdzLmltYWdlX3VybFxuICogQHBhcmFtIHN0cmluZ3MubWVkaWFfbGlicmFyeVxuICogQHBhcmFtIHN0cmluZ3Mubm9fcmVwZWF0XG4gKiBAcGFyYW0gc3RyaW5ncy5yZXBlYXRfeFxuICogQHBhcmFtIHN0cmluZ3MucmVwZWF0X3lcbiAqIEBwYXJhbSBzdHJpbmdzLnNlbGVjdF9iYWNrZ3JvdW5kX2ltYWdlXG4gKiBAcGFyYW0gc3RyaW5ncy5zZWxlY3RfaW1hZ2VcbiAqIEBwYXJhbSBzdHJpbmdzLnN0b2NrX3Bob3RvXG4gKiBAcGFyYW0gc3RyaW5ncy50aWxlXG4gKiBAcGFyYW0gc3RyaW5ncy50b3BfY2VudGVyXG4gKiBAcGFyYW0gc3RyaW5ncy50b3BfbGVmdFxuICogQHBhcmFtIHN0cmluZ3MudG9wX3JpZ2h0XG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIEJhY2tncm91bmQgc3R5bGVzIHBhbmVsIG1vZHVsZS5cbiAqXG4gKiBAc2luY2UgMS44LjhcbiAqL1xuZXhwb3J0IGRlZmF1bHQgKCBmdW5jdGlvbigpIHtcblx0LyoqXG5cdCAqIFdQIGNvcmUgY29tcG9uZW50cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCB7IFBhbmVsQ29sb3JTZXR0aW5ncyB9ID0gd3AuYmxvY2tFZGl0b3IgfHwgd3AuZWRpdG9yO1xuXHRjb25zdCB7IFNlbGVjdENvbnRyb2wsIFBhbmVsQm9keSwgRmxleCwgRmxleEJsb2NrLCBfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sLCBUZXh0Q29udHJvbCwgQnV0dG9uIH0gPSB3cC5jb21wb25lbnRzO1xuXG5cdC8qKlxuXHQgKiBMb2NhbGl6ZWQgZGF0YSBhbGlhc2VzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHsgc3RyaW5ncywgZGVmYXVsdHMgfSA9IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3I7XG5cblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gQmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKi9cblx0XHRnZXRCbG9ja0F0dHJpYnV0ZXMoKSB7XG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRiYWNrZ3JvdW5kSW1hZ2U6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5iYWNrZ3JvdW5kSW1hZ2UsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJhY2tncm91bmRQb3NpdGlvbjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJhY2tncm91bmRQb3NpdGlvbixcblx0XHRcdFx0fSxcblx0XHRcdFx0YmFja2dyb3VuZFJlcGVhdDoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJhY2tncm91bmRSZXBlYXQsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJhY2tncm91bmRTaXplTW9kZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJhY2tncm91bmRTaXplTW9kZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0YmFja2dyb3VuZFNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5iYWNrZ3JvdW5kU2l6ZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0YmFja2dyb3VuZFdpZHRoOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYmFja2dyb3VuZFdpZHRoLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRiYWNrZ3JvdW5kSGVpZ2h0OiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYmFja2dyb3VuZEhlaWdodCxcblx0XHRcdFx0fSxcblx0XHRcdFx0YmFja2dyb3VuZENvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRiYWNrZ3JvdW5kVXJsOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYmFja2dyb3VuZFVybCxcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBCYWNrZ3JvdW5kIFN0eWxlcyBwYW5lbCBKU1ggY29kZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgICAgICAgICAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBoYW5kbGVycyAgICAgICAgICAgQmxvY2sgaGFuZGxlcnMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGZvcm1TZWxlY3RvckNvbW1vbiBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzdG9ja1Bob3RvcyAgICAgICAgU3RvY2sgUGhvdG9zIG1vZHVsZS5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gdWlTdGF0ZSAgICAgICAgICAgIFVJIHN0YXRlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBGaWVsZCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0QmFja2dyb3VuZFN0eWxlcyggcHJvcHMsIGhhbmRsZXJzLCBmb3JtU2VsZWN0b3JDb21tb24sIHN0b2NrUGhvdG9zLCB1aVN0YXRlICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG1heC1saW5lcy1wZXItZnVuY3Rpb24sIGNvbXBsZXhpdHlcblx0XHRcdGNvbnN0IGlzTm90RGlzYWJsZWQgPSB1aVN0YXRlLmlzTm90RGlzYWJsZWQ7XG5cdFx0XHRjb25zdCBpc1Byb0VuYWJsZWQgPSB1aVN0YXRlLmlzUHJvRW5hYmxlZDtcblx0XHRcdGNvbnN0IHNob3dCYWNrZ3JvdW5kUHJldmlldyA9IHVpU3RhdGUuc2hvd0JhY2tncm91bmRQcmV2aWV3O1xuXHRcdFx0Y29uc3Qgc2V0U2hvd0JhY2tncm91bmRQcmV2aWV3ID0gdWlTdGF0ZS5zZXRTaG93QmFja2dyb3VuZFByZXZpZXc7XG5cdFx0XHRjb25zdCBsYXN0QmdJbWFnZSA9IHVpU3RhdGUubGFzdEJnSW1hZ2U7XG5cdFx0XHRjb25zdCBzZXRMYXN0QmdJbWFnZSA9IHVpU3RhdGUuc2V0TGFzdEJnSW1hZ2U7XG5cdFx0XHRjb25zdCB0YWJJbmRleCA9IGlzTm90RGlzYWJsZWQgPyAwIDogLTE7XG5cdFx0XHRjb25zdCBjc3NDbGFzcyA9IGZvcm1TZWxlY3RvckNvbW1vbi5nZXRQYW5lbENsYXNzKCBwcm9wcyApICsgKCBpc05vdERpc2FibGVkID8gJycgOiAnIHdwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLWRpc2FibGVkJyApO1xuXG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGNzc0NsYXNzIH0gdGl0bGU9eyBzdHJpbmdzLmJhY2tncm91bmRfc3R5bGVzIH0+XG5cdFx0XHRcdFx0PGRpdiAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIGpzeC1hMTF5L25vLXN0YXRpYy1lbGVtZW50LWludGVyYWN0aW9uc1xuXHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1wYW5lbC1ib2R5XCJcblx0XHRcdFx0XHRcdG9uQ2xpY2s9eyAoIGV2ZW50ICkgPT4ge1xuXHRcdFx0XHRcdFx0XHRpZiAoIGlzTm90RGlzYWJsZWQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdFx0ZXZlbnQuc3RvcFByb3BhZ2F0aW9uKCk7XG5cblx0XHRcdFx0XHRcdFx0aWYgKCAhIGlzUHJvRW5hYmxlZCApIHtcblx0XHRcdFx0XHRcdFx0XHRyZXR1cm4gZm9ybVNlbGVjdG9yQ29tbW9uLmVkdWNhdGlvbi5zaG93UHJvTW9kYWwoICdiYWNrZ3JvdW5kJywgc3RyaW5ncy5iYWNrZ3JvdW5kX3N0eWxlcyApO1xuXHRcdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdFx0Zm9ybVNlbGVjdG9yQ29tbW9uLmVkdWNhdGlvbi5zaG93TGljZW5zZU1vZGFsKCAnYmFja2dyb3VuZCcsIHN0cmluZ3MuYmFja2dyb3VuZF9zdHlsZXMsICdiYWNrZ3JvdW5kLXN0eWxlcycgKTtcblx0XHRcdFx0XHRcdH0gfVxuXHRcdFx0XHRcdFx0b25LZXlEb3duPXsgKCBldmVudCApID0+IHtcblx0XHRcdFx0XHRcdFx0aWYgKCBpc05vdERpc2FibGVkICkge1xuXHRcdFx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRcdGV2ZW50LnN0b3BQcm9wYWdhdGlvbigpO1xuXG5cdFx0XHRcdFx0XHRcdGlmICggISBpc1Byb0VuYWJsZWQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0cmV0dXJuIGZvcm1TZWxlY3RvckNvbW1vbi5lZHVjYXRpb24uc2hvd1Byb01vZGFsKCAnYmFja2dyb3VuZCcsIHN0cmluZ3MuYmFja2dyb3VuZF9zdHlsZXMgKTtcblx0XHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRcdGZvcm1TZWxlY3RvckNvbW1vbi5lZHVjYXRpb24uc2hvd0xpY2Vuc2VNb2RhbCggJ2JhY2tncm91bmQnLCBzdHJpbmdzLmJhY2tncm91bmRfc3R5bGVzLCAnYmFja2dyb3VuZC1zdHlsZXMnICk7XG5cdFx0XHRcdFx0XHR9IH1cblx0XHRcdFx0XHQ+XG5cdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuaW1hZ2UgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9wdGlvbnM9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3Mubm9uZSwgdmFsdWU6ICdub25lJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLm1lZGlhX2xpYnJhcnksIHZhbHVlOiAnbGlicmFyeScgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5zdG9ja19waG90bywgdmFsdWU6ICdzdG9jaycgfSxcblx0XHRcdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gYXBwLnNldENvbnRhaW5lckJhY2tncm91bmRJbWFnZVdyYXBwZXIoIHByb3BzLCBoYW5kbGVycywgdmFsdWUsIGxhc3RCZ0ltYWdlLCBzZXRMYXN0QmdJbWFnZSApIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHR7ICggcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UgIT09ICdub25lJyB8fCAhIGlzTm90RGlzYWJsZWQgKSAmJiAoXG5cdFx0XHRcdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MucG9zaXRpb24gfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFBvc2l0aW9uIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9wdGlvbnM9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy50b3BfbGVmdCwgdmFsdWU6ICd0b3AgbGVmdCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLnRvcF9jZW50ZXIsIHZhbHVlOiAndG9wIGNlbnRlcicgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLnRvcF9yaWdodCwgdmFsdWU6ICd0b3AgcmlnaHQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5jZW50ZXJfbGVmdCwgdmFsdWU6ICdjZW50ZXIgbGVmdCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmNlbnRlcl9jZW50ZXIsIHZhbHVlOiAnY2VudGVyIGNlbnRlcicgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmNlbnRlcl9yaWdodCwgdmFsdWU6ICdjZW50ZXIgcmlnaHQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5ib3R0b21fbGVmdCwgdmFsdWU6ICdib3R0b20gbGVmdCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmJvdHRvbV9jZW50ZXIsIHZhbHVlOiAnYm90dG9tIGNlbnRlcicgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmJvdHRvbV9yaWdodCwgdmFsdWU6ICdib3R0b20gcmlnaHQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRkaXNhYmxlZD17ICggcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UgPT09ICdub25lJyAmJiBpc05vdERpc2FibGVkICkgfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kUG9zaXRpb24nLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdFx0KSB9XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PC9GbGV4PlxuXHRcdFx0XHRcdFx0eyAoIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlICE9PSAnbm9uZScgfHwgISBpc05vdERpc2FibGVkICkgJiYgKFxuXHRcdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MucmVwZWF0IH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kUmVwZWF0IH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IFtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLm5vX3JlcGVhdCwgdmFsdWU6ICduby1yZXBlYXQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy50aWxlLCB2YWx1ZTogJ3JlcGVhdCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLnJlcGVhdF94LCB2YWx1ZTogJ3JlcGVhdC14JyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3MucmVwZWF0X3ksIHZhbHVlOiAncmVwZWF0LXknIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRkaXNhYmxlZD17ICggcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UgPT09ICdub25lJyAmJiBpc05vdERpc2FibGVkICkgfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kUmVwZWF0JywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRhYkluZGV4PXsgdGFiSW5kZXggfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFNpemVNb2RlIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IFtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmRpbWVuc2lvbnMsIHZhbHVlOiAnZGltZW5zaW9ucycgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmNvdmVyLCB2YWx1ZTogJ2NvdmVyJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRdIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0ZGlzYWJsZWQ9eyAoIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlID09PSAnbm9uZScgJiYgaXNOb3REaXNhYmxlZCApIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gYXBwLmhhbmRsZVNpemVGcm9tRGltZW5zaW9ucyggcHJvcHMsIGhhbmRsZXJzLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdCkgfVxuXHRcdFx0XHRcdFx0eyAoICggcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kU2l6ZU1vZGUgPT09ICdkaW1lbnNpb25zJyAmJiBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRJbWFnZSAhPT0gJ25vbmUnICkgfHwgISBpc05vdERpc2FibGVkICkgJiYgKFxuXHRcdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8X19leHBlcmltZW50YWxVbml0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Mud2lkdGggfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR0YWJJbmRleD17IHRhYkluZGV4IH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRXaWR0aCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlPXsgaXNOb3REaXNhYmxlZCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGFwcC5oYW5kbGVTaXplRnJvbVdpZHRoKCBwcm9wcywgaGFuZGxlcnMsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdFx0PF9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmhlaWdodCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRhYkluZGV4PXsgdGFiSW5kZXggfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEhlaWdodCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlPXsgaXNOb3REaXNhYmxlZCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGFwcC5oYW5kbGVTaXplRnJvbUhlaWdodCggcHJvcHMsIGhhbmRsZXJzLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdCkgfVxuXHRcdFx0XHRcdFx0eyAoICEgc2hvd0JhY2tncm91bmRQcmV2aWV3IHx8IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCA9PT0gJ3VybCgpJyApICYmIChcblx0XHRcdFx0XHRcdFx0KCBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRJbWFnZSA9PT0gJ2xpYnJhcnknICYmIChcblx0XHRcdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHRcdFx0PEJ1dHRvblxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdGlzU2Vjb25kYXJ5XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItbWVkaWEtbGlicmFyeS1idXR0b24nIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNsaWNrPXsgYXBwLm9wZW5NZWRpYUxpYnJhcnkuYmluZCggbnVsbCwgcHJvcHMsIGhhbmRsZXJzLCBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgKSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdD5cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MuY2hvb3NlX2ltYWdlIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0PC9CdXR0b24+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXg+XG5cdFx0XHRcdFx0XHRcdCkgKSB8fCAoIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlID09PSAnc3RvY2snICYmIChcblx0XHRcdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHRcdFx0PEJ1dHRvblxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdGlzU2Vjb25kYXJ5XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItbWVkaWEtbGlicmFyeS1idXR0b24nIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNsaWNrPXsgc3RvY2tQaG90b3M/Lm9wZW5Nb2RhbC5iaW5kKCBudWxsLCBwcm9wcywgaGFuZGxlcnMsICdiZy1zdHlsZXMnLCBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgKSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdD5cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MuY2hvb3NlX2ltYWdlIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0PC9CdXR0b24+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXg+XG5cdFx0XHRcdFx0XHRcdCkgKVxuXHRcdFx0XHRcdFx0KSB9XG5cdFx0XHRcdFx0XHR7ICggKCBzaG93QmFja2dyb3VuZFByZXZpZXcgJiYgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UgIT09ICdub25lJyApIHx8IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCAhPT0gJ3VybCgpJyApICYmIChcblx0XHRcdFx0XHRcdFx0PEZsZXggZ2FwPXsgNCB9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT17ICd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXgnIH0ganVzdGlmeT1cInNwYWNlLWJldHdlZW5cIj5cblx0XHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdFx0PGRpdj5cblx0XHRcdFx0XHRcdFx0XHRcdFx0PEJhY2tncm91bmRQcmV2aWV3XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0YXR0cmlidXRlcz17IHByb3BzLmF0dHJpYnV0ZXMgfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdG9uUmVtb3ZlQmFja2dyb3VuZD17XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHQoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdGFwcC5vblJlbW92ZUJhY2tncm91bmQoIHNldFNob3dCYWNrZ3JvdW5kUHJldmlldywgaGFuZGxlcnMsIHNldExhc3RCZ0ltYWdlICk7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdG9uUHJldmlld0NsaWNrZWQ9eyAoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRpZiAoIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlID09PSAnbGlicmFyeScgKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdHJldHVybiBhcHAub3Blbk1lZGlhTGlicmFyeSggcHJvcHMsIGhhbmRsZXJzLCBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgKTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0cmV0dXJuIHN0b2NrUGhvdG9zPy5vcGVuTW9kYWwoIHByb3BzLCBoYW5kbGVycywgJ2JnLXN0eWxlcycsIHNldFNob3dCYWNrZ3JvdW5kUHJldmlldyApO1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdH0gfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8VGV4dENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmltYWdlX3VybCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRhYkluZGV4PXsgdGFiSW5kZXggfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlICE9PSAnbm9uZScgJiYgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kVXJsIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItaW1hZ2UtdXJsJyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2JhY2tncm91bmRVcmwnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0b25Mb2FkPXsgKCB2YWx1ZSApID0+IHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlICE9PSAnbm9uZScgJiYgaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFVybCcsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PC9GbGV4PlxuXHRcdFx0XHRcdFx0KSB9XG5cdFx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdFx0PFBhbmVsQ29sb3JTZXR0aW5nc1xuXHRcdFx0XHRcdFx0XHRcdFx0X19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyXG5cdFx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdFx0c2hvd1RpdGxlPXsgZmFsc2UgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyB0YWJJbmRleCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRcdGNvbG9yU2V0dGluZ3M9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRpZiAoICEgaXNOb3REaXNhYmxlZCApIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kQ29sb3InLCB2YWx1ZSApO1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MuYmFja2dyb3VuZCxcblx0XHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PC9GbGV4PlxuXHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE9wZW4gbWVkaWEgbGlicmFyeSBtb2RhbCBhbmQgaGFuZGxlIGltYWdlIHNlbGVjdGlvbi5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICAgcHJvcHMgICAgICAgICAgICAgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICAgaGFuZGxlcnMgICAgICAgICAgICAgICAgIEJsb2NrIGhhbmRsZXJzLlxuXHRcdCAqIEBwYXJhbSB7RnVuY3Rpb259IHNldFNob3dCYWNrZ3JvdW5kUHJldmlldyBTZXQgc2hvdyBiYWNrZ3JvdW5kIHByZXZpZXcuXG5cdFx0ICovXG5cdFx0b3Blbk1lZGlhTGlicmFyeSggcHJvcHMsIGhhbmRsZXJzLCBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgKSB7XG5cdFx0XHRjb25zdCBmcmFtZSA9IHdwLm1lZGlhKCB7XG5cdFx0XHRcdHRpdGxlOiBzdHJpbmdzLnNlbGVjdF9iYWNrZ3JvdW5kX2ltYWdlLFxuXHRcdFx0XHRtdWx0aXBsZTogZmFsc2UsXG5cdFx0XHRcdGxpYnJhcnk6IHtcblx0XHRcdFx0XHR0eXBlOiAnaW1hZ2UnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b246IHtcblx0XHRcdFx0XHR0ZXh0OiBzdHJpbmdzLnNlbGVjdF9pbWFnZSxcblx0XHRcdFx0fSxcblx0XHRcdH0gKTtcblxuXHRcdFx0ZnJhbWUub24oICdzZWxlY3QnLCAoKSA9PiB7XG5cdFx0XHRcdGNvbnN0IGF0dGFjaG1lbnQgPSBmcmFtZS5zdGF0ZSgpLmdldCggJ3NlbGVjdGlvbicgKS5maXJzdCgpLnRvSlNPTigpO1xuXHRcdFx0XHRjb25zdCBzZXRBdHRyID0ge307XG5cdFx0XHRcdGNvbnN0IGF0dHJpYnV0ZSA9ICdiYWNrZ3JvdW5kVXJsJztcblxuXHRcdFx0XHRpZiAoIGF0dGFjaG1lbnQudXJsICkge1xuXHRcdFx0XHRcdGNvbnN0IHZhbHVlID0gYHVybCgkeyBhdHRhY2htZW50LnVybCB9KWA7XG5cblx0XHRcdFx0XHRzZXRBdHRyWyBhdHRyaWJ1dGUgXSA9IHZhbHVlO1xuXG5cdFx0XHRcdFx0cHJvcHMuc2V0QXR0cmlidXRlcyggc2V0QXR0ciApO1xuXG5cdFx0XHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFVybCcsIHZhbHVlICk7XG5cblx0XHRcdFx0XHRzZXRTaG93QmFja2dyb3VuZFByZXZpZXcoIHRydWUgKTtcblx0XHRcdFx0fVxuXHRcdFx0fSApO1xuXG5cdFx0XHRmcmFtZS5vcGVuKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBjb250YWluZXIgYmFja2dyb3VuZCBpbWFnZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtIVE1MRWxlbWVudH0gY29udGFpbmVyIENvbnRhaW5lciBlbGVtZW50LlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSAgICAgIHZhbHVlICAgICBWYWx1ZS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdGhlIHZhbHVlIHdhcyBzZXQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRzZXRDb250YWluZXJCYWNrZ3JvdW5kSW1hZ2UoIGNvbnRhaW5lciwgdmFsdWUgKSB7XG5cdFx0XHRpZiAoIHZhbHVlID09PSAnbm9uZScgKSB7XG5cdFx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eSggYC0td3Bmb3Jtcy1iYWNrZ3JvdW5kLXVybGAsICd1cmwoKScgKTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBjb250YWluZXIgYmFja2dyb3VuZCBpbWFnZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICAgcHJvcHMgICAgICAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gICBoYW5kbGVycyAgICAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gICB2YWx1ZSAgICAgICAgICBWYWx1ZS5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gICBsYXN0QmdJbWFnZSAgICBMYXN0IGJhY2tncm91bmQgaW1hZ2UuXG5cdFx0ICogQHBhcmFtIHtGdW5jdGlvbn0gc2V0TGFzdEJnSW1hZ2UgU2V0IGxhc3QgYmFja2dyb3VuZCBpbWFnZS5cblx0XHQgKi9cblx0XHRzZXRDb250YWluZXJCYWNrZ3JvdW5kSW1hZ2VXcmFwcGVyKCBwcm9wcywgaGFuZGxlcnMsIHZhbHVlLCBsYXN0QmdJbWFnZSwgc2V0TGFzdEJnSW1hZ2UgKSB7XG5cdFx0XHRpZiAoIHZhbHVlID09PSAnbm9uZScgKSB7XG5cdFx0XHRcdHNldExhc3RCZ0ltYWdlKCBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRVcmwgKTtcblx0XHRcdFx0cHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kVXJsID0gJ3VybCgpJztcblxuXHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kVXJsJywgJ3VybCgpJyApO1xuXHRcdFx0fSBlbHNlIGlmICggbGFzdEJnSW1hZ2UgKSB7XG5cdFx0XHRcdHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCA9IGxhc3RCZ0ltYWdlO1xuXHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kVXJsJywgbGFzdEJnSW1hZ2UgKTtcblx0XHRcdH1cblxuXHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZEltYWdlJywgdmFsdWUgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2V0IGNvbnRhaW5lciBiYWNrZ3JvdW5kIHBvc2l0aW9uLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBjb250YWluZXIgQ29udGFpbmVyIGVsZW1lbnQuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9ICAgICAgdmFsdWUgICAgIFZhbHVlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgdmFsdWUgd2FzIHNldCwgZmFsc2Ugb3RoZXJ3aXNlLlxuXHRcdCAqL1xuXHRcdHNldENvbnRhaW5lckJhY2tncm91bmRQb3NpdGlvbiggY29udGFpbmVyLCB2YWx1ZSApIHtcblx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eSggYC0td3Bmb3Jtcy1iYWNrZ3JvdW5kLXBvc2l0aW9uYCwgdmFsdWUgKTtcblxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBjb250YWluZXIgYmFja2dyb3VuZCByZXBlYXQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7SFRNTEVsZW1lbnR9IGNvbnRhaW5lciBDb250YWluZXIgZWxlbWVudC5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gICAgICB2YWx1ZSAgICAgVmFsdWUuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIGlmIHRoZSB2YWx1ZSB3YXMgc2V0LCBmYWxzZSBvdGhlcndpc2UuXG5cdFx0ICovXG5cdFx0c2V0Q29udGFpbmVyQmFja2dyb3VuZFJlcGVhdCggY29udGFpbmVyLCB2YWx1ZSApIHtcblx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eSggYC0td3Bmb3Jtcy1iYWNrZ3JvdW5kLXJlcGVhdGAsIHZhbHVlICk7XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBIYW5kbGUgcmVhbCBzaXplIGZyb20gZGltZW5zaW9ucy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzIEJsb2NrIGhhbmRsZXJzLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSAgICBWYWx1ZS5cblx0XHQgKi9cblx0XHRoYW5kbGVTaXplRnJvbURpbWVuc2lvbnMoIHByb3BzLCBoYW5kbGVycywgdmFsdWUgKSB7XG5cdFx0XHRpZiAoIHZhbHVlID09PSAnY292ZXInICkge1xuXHRcdFx0XHRwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRTaXplID0gJ2NvdmVyJztcblxuXHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kV2lkdGgnLCBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRXaWR0aCApO1xuXHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kSGVpZ2h0JywgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSGVpZ2h0ICk7XG5cdFx0XHRcdGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2JhY2tncm91bmRTaXplTW9kZScsICdjb3ZlcicgKTtcblx0XHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFNpemUnLCAnY292ZXInICk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRTaXplID0gJ2RpbWVuc2lvbnMnO1xuXG5cdFx0XHRcdGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2JhY2tncm91bmRTaXplTW9kZScsICdkaW1lbnNpb25zJyApO1xuXHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdiYWNrZ3JvdW5kU2l6ZScsIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFdpZHRoICsgJyAnICsgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSGVpZ2h0ICk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEhhbmRsZSByZWFsIHNpemUgZnJvbSB3aWR0aC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzIEJsb2NrIGhhbmRsZXJzLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSAgICBWYWx1ZS5cblx0XHQgKi9cblx0XHRoYW5kbGVTaXplRnJvbVdpZHRoKCBwcm9wcywgaGFuZGxlcnMsIHZhbHVlICkge1xuXHRcdFx0cHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kU2l6ZSA9IHZhbHVlICsgJyAnICsgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSGVpZ2h0O1xuXHRcdFx0cHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kV2lkdGggPSB2YWx1ZTtcblxuXHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFNpemUnLCB2YWx1ZSArICcgJyArIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEhlaWdodCApO1xuXHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFdpZHRoJywgdmFsdWUgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSGFuZGxlIHJlYWwgc2l6ZSBmcm9tIGhlaWdodC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzIEJsb2NrIGhhbmRsZXJzLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSAgICBWYWx1ZS5cblx0XHQgKi9cblx0XHRoYW5kbGVTaXplRnJvbUhlaWdodCggcHJvcHMsIGhhbmRsZXJzLCB2YWx1ZSApIHtcblx0XHRcdHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFNpemUgPSBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRXaWR0aCArICcgJyArIHZhbHVlO1xuXHRcdFx0cHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSGVpZ2h0ID0gdmFsdWU7XG5cblx0XHRcdGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2JhY2tncm91bmRTaXplJywgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kV2lkdGggKyAnICcgKyB2YWx1ZSApO1xuXHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZEhlaWdodCcsIHZhbHVlICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBjb250YWluZXIgYmFja2dyb3VuZCB3aWR0aC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtIVE1MRWxlbWVudH0gY29udGFpbmVyIENvbnRhaW5lciBlbGVtZW50LlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSAgICAgIHZhbHVlICAgICBWYWx1ZS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdGhlIHZhbHVlIHdhcyBzZXQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRzZXRDb250YWluZXJCYWNrZ3JvdW5kV2lkdGgoIGNvbnRhaW5lciwgdmFsdWUgKSB7XG5cdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtYmFja2dyb3VuZC13aWR0aGAsIHZhbHVlICk7XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBTZXQgY29udGFpbmVyIGJhY2tncm91bmQgaGVpZ2h0LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0hUTUxFbGVtZW50fSBjb250YWluZXIgQ29udGFpbmVyIGVsZW1lbnQuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9ICAgICAgdmFsdWUgICAgIFZhbHVlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgdmFsdWUgd2FzIHNldCwgZmFsc2Ugb3RoZXJ3aXNlLlxuXHRcdCAqL1xuXHRcdHNldENvbnRhaW5lckJhY2tncm91bmRIZWlnaHQoIGNvbnRhaW5lciwgdmFsdWUgKSB7XG5cdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtYmFja2dyb3VuZC1oZWlnaHRgLCB2YWx1ZSApO1xuXG5cdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2V0IGNvbnRhaW5lciBiYWNrZ3JvdW5kIHVybC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtIVE1MRWxlbWVudH0gY29udGFpbmVyIENvbnRhaW5lciBlbGVtZW50LlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSAgICAgIHZhbHVlICAgICBWYWx1ZS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdGhlIHZhbHVlIHdhcyBzZXQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRzZXRCYWNrZ3JvdW5kVXJsKCBjb250YWluZXIsIHZhbHVlICkge1xuXHRcdFx0Y29udGFpbmVyLnN0eWxlLnNldFByb3BlcnR5KCBgLS13cGZvcm1zLWJhY2tncm91bmQtdXJsYCwgdmFsdWUgKTtcblxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBjb250YWluZXIgYmFja2dyb3VuZCBjb2xvci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtIVE1MRWxlbWVudH0gY29udGFpbmVyIENvbnRhaW5lciBlbGVtZW50LlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSAgICAgIHZhbHVlICAgICBWYWx1ZS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdGhlIHZhbHVlIHdhcyBzZXQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRzZXRCYWNrZ3JvdW5kQ29sb3IoIGNvbnRhaW5lciwgdmFsdWUgKSB7XG5cdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtYmFja2dyb3VuZC1jb2xvcmAsIHZhbHVlICk7XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHRfc2hvd0JhY2tncm91bmRQcmV2aWV3KCBwcm9wcyApIHtcblx0XHRcdHJldHVybiBwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRJbWFnZSAhPT0gJ25vbmUnICYmXG5cdFx0XHRcdHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCAmJlxuXHRcdFx0XHRwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRVcmwgIT09ICd1cmwoKSc7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFJlbW92ZSBiYWNrZ3JvdW5kIGltYWdlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0Z1bmN0aW9ufSBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgU2V0IHNob3cgYmFja2dyb3VuZCBwcmV2aWV3LlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSAgIGhhbmRsZXJzICAgICAgICAgICAgICAgICBCbG9jayBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge0Z1bmN0aW9ufSBzZXRMYXN0QmdJbWFnZSAgICAgICAgICAgU2V0IGxhc3QgYmFja2dyb3VuZCBpbWFnZS5cblx0XHQgKi9cblx0XHRvblJlbW92ZUJhY2tncm91bmQoIHNldFNob3dCYWNrZ3JvdW5kUHJldmlldywgaGFuZGxlcnMsIHNldExhc3RCZ0ltYWdlICkge1xuXHRcdFx0c2V0U2hvd0JhY2tncm91bmRQcmV2aWV3KCBmYWxzZSApO1xuXHRcdFx0aGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFVybCcsICd1cmwoKScgKTtcblx0XHRcdHNldExhc3RCZ0ltYWdlKCAnJyApO1xuXHRcdH0sXG5cdH07XG5cblx0cmV0dXJuIGFwcDtcbn0oKSApO1xuIl0sIm1hcHBpbmdzIjoiOzs7Ozs7QUFHQSxJQUFBQSxrQkFBQSxHQUFBQyxzQkFBQSxDQUFBQyxPQUFBO0FBQXdELFNBQUFELHVCQUFBRSxDQUFBLFdBQUFBLENBQUEsSUFBQUEsQ0FBQSxDQUFBQyxVQUFBLEdBQUFELENBQUEsS0FBQUUsT0FBQSxFQUFBRixDQUFBO0FBSHhEO0FBQ0E7QUFJQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7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
},{"./background-preview.js":14}],16:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.border_radius
 * @param strings.border_size
 * @param strings.button_color_notice
 * @param strings.button_styles
 * @param strings.dashed
 * @param strings.solid
 */
/**
 * Gutenberg editor block.
 *
 * Button styles panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function () {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _ref = wp.blockEditor || wp.editor,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    PanelBody = _wp$components.PanelBody,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults;

  // noinspection UnnecessaryLocalVariableJS
  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Get block attributes.
     *
     * @since 1.8.8
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      return {
        buttonSize: {
          type: 'string',
          default: defaults.buttonSize
        },
        buttonBorderStyle: {
          type: 'string',
          default: defaults.buttonBorderStyle
        },
        buttonBorderSize: {
          type: 'string',
          default: defaults.buttonBorderSize
        },
        buttonBorderRadius: {
          type: 'string',
          default: defaults.buttonBorderRadius
        },
        buttonBackgroundColor: {
          type: 'string',
          default: defaults.buttonBackgroundColor
        },
        buttonTextColor: {
          type: 'string',
          default: defaults.buttonTextColor
        },
        buttonBorderColor: {
          type: 'string',
          default: defaults.buttonBorderColor
        }
      };
    },
    /**
     * Get Button styles JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props              Block properties.
     * @param {Object} handlers           Block event handlers.
     * @param {Object} sizeOptions        Size selector options.
     * @param {Object} formSelectorCommon Form selector common object.
     *
     * @return {Object}  Button styles JSX code.
     */
    getButtonStyles: function getButtonStyles(props, handlers, sizeOptions, formSelectorCommon) {
      // eslint-disable-line max-lines-per-function
      return /*#__PURE__*/React.createElement(PanelBody, {
        className: formSelectorCommon.getPanelClass(props),
        title: strings.button_styles
      }, /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.size,
        value: props.attributes.buttonSize,
        options: sizeOptions,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('buttonSize', value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.border,
        value: props.attributes.buttonBorderStyle,
        options: [{
          label: strings.none,
          value: 'none'
        }, {
          label: strings.solid,
          value: 'solid'
        }, {
          label: strings.dashed,
          value: 'dashed'
        }, {
          label: strings.dotted,
          value: 'dotted'
        }],
        onChange: function onChange(value) {
          return handlers.styleAttrChange('buttonBorderStyle', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.border_size,
        value: props.attributes.buttonBorderStyle === 'none' ? '' : props.attributes.buttonBorderSize,
        min: 0,
        disabled: props.attributes.buttonBorderStyle === 'none',
        onChange: function onChange(value) {
          return handlers.styleAttrChange('buttonBorderSize', value);
        },
        isUnitSelectTabbable: true
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        onChange: function onChange(value) {
          return handlers.styleAttrChange('buttonBorderRadius', value);
        },
        label: strings.border_radius,
        min: 0,
        isUnitSelectTabbable: true,
        value: props.attributes.buttonBorderRadius
      }))), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-color-picker"
      }, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-control-label"
      }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
        __experimentalIsRenderedInSidebar: true,
        enableAlpha: true,
        showTitle: false,
        className: formSelectorCommon.getColorPanelClass(props.attributes.buttonBorderStyle),
        colorSettings: [{
          value: props.attributes.buttonBackgroundColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonBackgroundColor', value);
          },
          label: strings.background
        }, {
          value: props.attributes.buttonBorderColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonBorderColor', value);
          },
          label: strings.border
        }, {
          value: props.attributes.buttonTextColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonTextColor', value);
          },
          label: strings.text
        }]
      }), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-legend wpforms-button-color-notice"
      }, strings.button_color_notice)));
    }
  };
  return app;
}();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiX3JlZiIsIndwIiwiYmxvY2tFZGl0b3IiLCJlZGl0b3IiLCJQYW5lbENvbG9yU2V0dGluZ3MiLCJfd3AkY29tcG9uZW50cyIsImNvbXBvbmVudHMiLCJTZWxlY3RDb250cm9sIiwiUGFuZWxCb2R5IiwiRmxleCIsIkZsZXhCbG9jayIsIl9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2wiLCJfd3Bmb3Jtc19ndXRlbmJlcmdfZm8iLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwic3RyaW5ncyIsImRlZmF1bHRzIiwiYXBwIiwiZ2V0QmxvY2tBdHRyaWJ1dGVzIiwiYnV0dG9uU2l6ZSIsInR5cGUiLCJidXR0b25Cb3JkZXJTdHlsZSIsImJ1dHRvbkJvcmRlclNpemUiLCJidXR0b25Cb3JkZXJSYWRpdXMiLCJidXR0b25CYWNrZ3JvdW5kQ29sb3IiLCJidXR0b25UZXh0Q29sb3IiLCJidXR0b25Cb3JkZXJDb2xvciIsImdldEJ1dHRvblN0eWxlcyIsInByb3BzIiwiaGFuZGxlcnMiLCJzaXplT3B0aW9ucyIsImZvcm1TZWxlY3RvckNvbW1vbiIsIlJlYWN0IiwiY3JlYXRlRWxlbWVudCIsImNsYXNzTmFtZSIsImdldFBhbmVsQ2xhc3MiLCJ0aXRsZSIsImJ1dHRvbl9zdHlsZXMiLCJnYXAiLCJhbGlnbiIsImp1c3RpZnkiLCJsYWJlbCIsInNpemUiLCJ2YWx1ZSIsImF0dHJpYnV0ZXMiLCJvcHRpb25zIiwib25DaGFuZ2UiLCJzdHlsZUF0dHJDaGFuZ2UiLCJib3JkZXIiLCJub25lIiwic29saWQiLCJkYXNoZWQiLCJkb3R0ZWQiLCJib3JkZXJfc2l6ZSIsIm1pbiIsImRpc2FibGVkIiwiaXNVbml0U2VsZWN0VGFiYmFibGUiLCJib3JkZXJfcmFkaXVzIiwiY29sb3JzIiwiX19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyIiwiZW5hYmxlQWxwaGEiLCJzaG93VGl0bGUiLCJnZXRDb2xvclBhbmVsQ2xhc3MiLCJjb2xvclNldHRpbmdzIiwiYmFja2dyb3VuZCIsInRleHQiLCJidXR0b25fY29sb3Jfbm90aWNlIl0sInNvdXJjZXMiOlsiYnV0dG9uLXN0eWxlcy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4vKipcbiAqIEBwYXJhbSBzdHJpbmdzLmJvcmRlcl9yYWRpdXNcbiAqIEBwYXJhbSBzdHJpbmdzLmJvcmRlcl9zaXplXG4gKiBAcGFyYW0gc3RyaW5ncy5idXR0b25fY29sb3Jfbm90aWNlXG4gKiBAcGFyYW0gc3RyaW5ncy5idXR0b25fc3R5bGVzXG4gKiBAcGFyYW0gc3RyaW5ncy5kYXNoZWRcbiAqIEBwYXJhbSBzdHJpbmdzLnNvbGlkXG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIEJ1dHRvbiBzdHlsZXMgcGFuZWwgbW9kdWxlLlxuICpcbiAqIEBzaW5jZSAxLjguOFxuICovXG5leHBvcnQgZGVmYXVsdCAoICggZnVuY3Rpb24oKSB7XG5cdC8qKlxuXHQgKiBXUCBjb3JlIGNvbXBvbmVudHMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKi9cblx0Y29uc3QgeyBQYW5lbENvbG9yU2V0dGluZ3MgfSA9IHdwLmJsb2NrRWRpdG9yIHx8IHdwLmVkaXRvcjtcblx0Y29uc3QgeyBTZWxlY3RDb250cm9sLCBQYW5lbEJvZHksIEZsZXgsIEZsZXhCbG9jaywgX19leHBlcmltZW50YWxVbml0Q29udHJvbCB9ID0gd3AuY29tcG9uZW50cztcblxuXHQvKipcblx0ICogTG9jYWxpemVkIGRhdGEgYWxpYXNlcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCB7IHN0cmluZ3MsIGRlZmF1bHRzIH0gPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yO1xuXG5cdC8vIG5vaW5zcGVjdGlvbiBVbm5lY2Vzc2FyeUxvY2FsVmFyaWFibGVKU1xuXHQvKipcblx0ICogUHVibGljIGZ1bmN0aW9ucyBhbmQgcHJvcGVydGllcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBhcHAgPSB7XG5cblx0XHQvKipcblx0XHQgKiBHZXQgYmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdCAqL1xuXHRcdGdldEJsb2NrQXR0cmlidXRlcygpIHtcblx0XHRcdHJldHVybiB7XG5cdFx0XHRcdGJ1dHRvblNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5idXR0b25TaXplLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b25Cb3JkZXJTdHlsZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvbkJvcmRlclN0eWxlLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b25Cb3JkZXJTaXplOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYnV0dG9uQm9yZGVyU2l6ZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uQm9yZGVyUmFkaXVzOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYnV0dG9uQm9yZGVyUmFkaXVzLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b25CYWNrZ3JvdW5kQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5idXR0b25CYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJ1dHRvblRleHRDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvblRleHRDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uQm9yZGVyQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5idXR0b25Cb3JkZXJDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBCdXR0b24gc3R5bGVzIEpTWCBjb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzICAgICAgICAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gc2l6ZU9wdGlvbnMgICAgICAgIFNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZm9ybVNlbGVjdG9yQ29tbW9uIEZvcm0gc2VsZWN0b3IgY29tbW9uIG9iamVjdC5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gIEJ1dHRvbiBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0QnV0dG9uU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zLCBmb3JtU2VsZWN0b3JDb21tb24gKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXHRcdFx0cmV0dXJuIChcblx0XHRcdFx0PFBhbmVsQm9keSBjbGFzc05hbWU9eyBmb3JtU2VsZWN0b3JDb21tb24uZ2V0UGFuZWxDbGFzcyggcHJvcHMgKSB9IHRpdGxlPXsgc3RyaW5ncy5idXR0b25fc3R5bGVzIH0+XG5cdFx0XHRcdFx0PEZsZXggZ2FwPXsgNCB9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT17ICd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXgnIH0ganVzdGlmeT1cInNwYWNlLWJldHdlZW5cIj5cblx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLnNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5idXR0b25TaXplIH1cblx0XHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgc2l6ZU9wdGlvbnMgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2J1dHRvblNpemUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyIH1cblx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQm9yZGVyU3R5bGUgfVxuXHRcdFx0XHRcdFx0XHRcdG9wdGlvbnM9e1xuXHRcdFx0XHRcdFx0XHRcdFx0W1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLm5vbmUsIHZhbHVlOiAnbm9uZScgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5zb2xpZCwgdmFsdWU6ICdzb2xpZCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5kYXNoZWQsIHZhbHVlOiAnZGFzaGVkJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmRvdHRlZCwgdmFsdWU6ICdkb3R0ZWQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRdXG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2J1dHRvbkJvcmRlclN0eWxlJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHQ8L0ZsZXg+XG5cdFx0XHRcdFx0PEZsZXggZ2FwPXsgNCB9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT17ICd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXgnIH0ganVzdGlmeT1cInNwYWNlLWJldHdlZW5cIj5cblx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmJvcmRlcl9zaXplIH1cblx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQm9yZGVyU3R5bGUgPT09ICdub25lJyA/ICcnIDogcHJvcHMuYXR0cmlidXRlcy5idXR0b25Cb3JkZXJTaXplIH1cblx0XHRcdFx0XHRcdFx0XHRtaW49eyAwIH1cblx0XHRcdFx0XHRcdFx0XHRkaXNhYmxlZD17IHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQm9yZGVyU3R5bGUgPT09ICdub25lJyB9XG5cdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQm9yZGVyU2l6ZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlXG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQm9yZGVyUmFkaXVzJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmJvcmRlcl9yYWRpdXMgfVxuXHRcdFx0XHRcdFx0XHRcdG1pbj17IDAgfVxuXHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlXG5cdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlclJhZGl1cyB9IC8+XG5cdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHQ8L0ZsZXg+XG5cblx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29udHJvbC1sYWJlbFwiPnsgc3RyaW5ncy5jb2xvcnMgfTwvZGl2PlxuXHRcdFx0XHRcdFx0PFBhbmVsQ29sb3JTZXR0aW5nc1xuXHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0ZW5hYmxlQWxwaGFcblx0XHRcdFx0XHRcdFx0c2hvd1RpdGxlPXsgZmFsc2UgfVxuXHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9eyBmb3JtU2VsZWN0b3JDb21tb24uZ2V0Q29sb3JQYW5lbENsYXNzKCBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlclN0eWxlICkgfVxuXHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXsgW1xuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJhY2tncm91bmRDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQmFja2dyb3VuZENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmJhY2tncm91bmQsXG5cdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5idXR0b25Cb3JkZXJDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQm9yZGVyQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MuYm9yZGVyLFxuXHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU6IHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uVGV4dENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdidXR0b25UZXh0Q29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MudGV4dCxcblx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRdIH0gLz5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1sZWdlbmQgd3Bmb3Jtcy1idXR0b24tY29sb3Itbm90aWNlXCI+XG5cdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5idXR0b25fY29sb3Jfbm90aWNlIH1cblx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0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
},{}],17:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, catch: function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
/* global jconfirm, wpforms_gutenberg_form_selector, Choices, JSX, DOM, WPFormsUtils */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.copy_paste_error
 * @param strings.error_message
 * @param strings.form_edit
 * @param strings.form_entries
 * @param strings.form_keywords
 * @param strings.form_select
 * @param strings.form_selected
 * @param strings.form_settings
 * @param strings.label_styles
 * @param strings.other_styles
 * @param strings.page_break
 * @param strings.panel_notice_head
 * @param strings.panel_notice_link
 * @param strings.panel_notice_link_text
 * @param strings.panel_notice_text
 * @param strings.show_description
 * @param strings.show_title
 * @param strings.sublabel_hints
 * @param strings.form_not_available_message
 * @param urls.entries_url
 * @param urls.form_url
 * @param window.wpforms_choicesjs_config
 * @param wpforms_education.upgrade_bonus
 * @param wpforms_gutenberg_form_selector.block_empty_url
 * @param wpforms_gutenberg_form_selector.block_preview_url
 * @param wpforms_gutenberg_form_selector.get_started_url
 * @param wpforms_gutenberg_form_selector.is_full_styling
 * @param wpforms_gutenberg_form_selector.is_modern_markup
 * @param wpforms_gutenberg_form_selector.logo_url
 * @param wpforms_gutenberg_form_selector.wpforms_guide
 */
/**
 * Gutenberg editor block.
 *
 * Common module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function (document, window, $) {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _wp = wp,
    _wp$serverSideRender = _wp.serverSideRender,
    ServerSideRender = _wp$serverSideRender === void 0 ? wp.components.ServerSideRender : _wp$serverSideRender;
  var _wp$element = wp.element,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment,
    createInterpolateElement = _wp$element.createInterpolateElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var _ref = wp.blockEditor || wp.editor,
    InspectorControls = _ref.InspectorControls,
    PanelColorSettings = _ref.PanelColorSettings,
    useBlockProps = _ref.useBlockProps;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    ToggleControl = _wp$components.ToggleControl,
    PanelBody = _wp$components.PanelBody,
    Placeholder = _wp$components.Placeholder;
  var __ = wp.i18n.__;
  var _wp$element2 = wp.element,
    useState = _wp$element2.useState,
    useEffect = _wp$element2.useEffect;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults,
    sizes = _wpforms_gutenberg_fo.sizes,
    urls = _wpforms_gutenberg_fo.urls,
    isPro = _wpforms_gutenberg_fo.isPro,
    isLicenseActive = _wpforms_gutenberg_fo.isLicenseActive,
    isAdmin = _wpforms_gutenberg_fo.isAdmin;
  var defaultStyleSettings = defaults;

  // noinspection JSUnusedLocalSymbols
  /**
   * WPForms Education script.
   *
   * @since 1.8.8
   */
  var WPFormsEducation = window.WPFormsEducation || {}; // eslint-disable-line no-unused-vars

  /**
   * List of forms.
   *
   * The default value is localized in FormSelector.php.
   *
   * @since 1.8.4
   *
   * @type {Object}
   */
  var formList = wpforms_gutenberg_form_selector.forms;

  /**
   * Blocks runtime data.
   *
   * @since 1.8.1
   *
   * @type {Object}
   */
  var blocks = {};

  /**
   * Whether it is needed to trigger server rendering.
   *
   * @since 1.8.1
   *
   * @type {boolean}
   */
  var triggerServerRender = true;

  /**
   * Popup container.
   *
   * @since 1.8.3
   *
   * @type {Object}
   */
  var $popup = {};

  /**
   * Track fetch status.
   *
   * @since 1.8.4
   *
   * @type {boolean}
   */
  var isFetching = false;

  /**
   * Elements holder.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var el = {};

  /**
   * Common block attributes.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var commonAttributes = {
    clientId: {
      type: 'string',
      default: ''
    },
    formId: {
      type: 'string',
      default: defaultStyleSettings.formId
    },
    displayTitle: {
      type: 'boolean',
      default: defaultStyleSettings.displayTitle
    },
    displayDesc: {
      type: 'boolean',
      default: defaultStyleSettings.displayDesc
    },
    preview: {
      type: 'boolean'
    },
    theme: {
      type: 'string',
      default: defaultStyleSettings.theme
    },
    themeName: {
      type: 'string',
      default: defaultStyleSettings.themeName
    },
    labelSize: {
      type: 'string',
      default: defaultStyleSettings.labelSize
    },
    labelColor: {
      type: 'string',
      default: defaultStyleSettings.labelColor
    },
    labelSublabelColor: {
      type: 'string',
      default: defaultStyleSettings.labelSublabelColor
    },
    labelErrorColor: {
      type: 'string',
      default: defaultStyleSettings.labelErrorColor
    },
    pageBreakColor: {
      type: 'string',
      default: defaultStyleSettings.pageBreakColor
    },
    customCss: {
      type: 'string',
      default: defaultStyleSettings.customCss
    },
    copyPasteJsonValue: {
      type: 'string',
      default: defaultStyleSettings.copyPasteJsonValue
    }
  };

  /**
   * Handlers for custom styles settings, defined outside this module.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var customStylesHandlers = {};

  /**
   * Dropdown timeout.
   *
   * @since 1.8.8
   *
   * @type {number}
   */
  var dropdownTimeout;

  /**
   * Whether copy-paste content was generated on edit.
   *
   * @since 1.9.1
   *
   * @type {boolean}
   */
  var isCopyPasteGeneratedOnEdit = false;

  /**
   * Whether the background is selected.
   *
   * @since 1.9.3
   *
   * @type {boolean}
   */
  var backgroundSelected = false;

  /**
   * Public functions and properties.
   *
   * @since 1.8.1
   *
   * @type {Object}
   */
  var app = {
    /**
     * Panel modules.
     *
     * @since 1.8.8
     *
     * @type {Object}
     */
    panels: {},
    /**
     * Start the engine.
     *
     * @since 1.8.1
     *
     * @param {Object} blockOptions Block options.
     */
    init: function init(blockOptions) {
      el.$window = $(window);
      app.panels = blockOptions.panels;
      app.education = blockOptions.education;
      app.initDefaults(blockOptions);
      app.registerBlock(blockOptions);
      app.initJConfirm();
      $(app.ready);
    },
    /**
     * Document ready.
     *
     * @since 1.8.1
     */
    ready: function ready() {
      app.events();
    },
    /**
     * Events.
     *
     * @since 1.8.1
     */
    events: function events() {
      el.$window.on('wpformsFormSelectorEdit', _.debounce(app.blockEdit, 250)).on('wpformsFormSelectorFormLoaded', app.formLoaded);
    },
    /**
     * Init jConfirm.
     *
     * @since 1.8.8
     */
    initJConfirm: function initJConfirm() {
      // jquery-confirm defaults.
      jconfirm.defaults = {
        closeIcon: false,
        backgroundDismiss: false,
        escapeKey: true,
        animationBounce: 1,
        useBootstrap: false,
        theme: 'modern',
        boxWidth: '400px',
        animateFromElement: false
      };
    },
    /**
     * Get a fresh list of forms via REST-API.
     *
     * @since 1.8.4
     *
     * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
     */
    getForms: function getForms() {
      return _asyncToGenerator(/*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (!isFetching) {
                _context.next = 2;
                break;
              }
              return _context.abrupt("return");
            case 2:
              // Set the flag to true indicating a fetch is in progress.
              isFetching = true;
              _context.prev = 3;
              _context.next = 6;
              return wp.apiFetch({
                path: wpforms_gutenberg_form_selector.route_namespace + 'forms/',
                method: 'GET',
                cache: 'no-cache'
              });
            case 6:
              formList = _context.sent;
              _context.next = 12;
              break;
            case 9:
              _context.prev = 9;
              _context.t0 = _context["catch"](3);
              // eslint-disable-next-line no-console
              console.error(_context.t0);
            case 12:
              _context.prev = 12;
              isFetching = false;
              return _context.finish(12);
            case 15:
            case "end":
              return _context.stop();
          }
        }, _callee, null, [[3, 9, 12, 15]]);
      }))();
    },
    /**
     * Open builder popup.
     *
     * @since 1.6.2
     *
     * @param {string} clientID Block Client ID.
     */
    openBuilderPopup: function openBuilderPopup(clientID) {
      if ($.isEmptyObject($popup)) {
        var _parent = $('#wpwrap');
        var canvasIframe = $('iframe[name="editor-canvas"]');
        var isFseMode = Boolean(canvasIframe.length);
        var tmpl = isFseMode ? canvasIframe.contents().find('#wpforms-gutenberg-popup') : $('#wpforms-gutenberg-popup');
        _parent.after(tmpl);
        $popup = _parent.siblings('#wpforms-gutenberg-popup');
      }
      var url = wpforms_gutenberg_form_selector.get_started_url,
        $iframe = $popup.find('iframe');
      app.builderCloseButtonEvent(clientID);
      $iframe.attr('src', url);
      $popup.fadeIn();
    },
    /**
     * Close button (inside the form builder) click event.
     *
     * @since 1.8.3
     *
     * @param {string} clientID Block Client ID.
     */
    builderCloseButtonEvent: function builderCloseButtonEvent(clientID) {
      $popup.off('wpformsBuilderInPopupClose').on('wpformsBuilderInPopupClose', function (e, action, formId, formTitle) {
        if (action !== 'saved' || !formId) {
          return;
        }

        // Insert a new block when a new form is created from the popup to update the form list and attributes.
        var newBlock = wp.blocks.createBlock('wpforms/form-selector', {
          formId: formId.toString() // Expects string value, make sure we insert string.
        });

        // eslint-disable-next-line camelcase
        formList = [{
          ID: formId,
          post_title: formTitle
        }];

        // Insert a new block.
        wp.data.dispatch('core/block-editor').removeBlock(clientID);
        wp.data.dispatch('core/block-editor').insertBlocks(newBlock);
      });
    },
    /**
     * Register block.
     *
     * @since 1.8.1
     *
     * @param {Object} blockOptions Additional block options.
     */
    // eslint-disable-next-line max-lines-per-function
    registerBlock: function registerBlock(blockOptions) {
      registerBlockType('wpforms/form-selector', {
        title: strings.title,
        description: strings.description,
        icon: app.getIcon(),
        keywords: strings.form_keywords,
        category: 'widgets',
        attributes: app.getBlockAttributes(),
        supports: {
          customClassName: app.hasForms()
        },
        example: {
          attributes: {
            preview: true
          }
        },
        // eslint-disable-next-line max-lines-per-function,complexity
        edit: function edit(props) {
          var attributes = props.attributes;
          var formOptions = app.getFormOptions();
          var handlers = app.getSettingsFieldsHandlers(props);
          var _useState = useState(isPro && isLicenseActive),
            _useState2 = _slicedToArray(_useState, 1),
            isNotDisabled = _useState2[0]; // eslint-disable-line react-hooks/rules-of-hooks
          var _useState3 = useState(isPro),
            _useState4 = _slicedToArray(_useState3, 1),
            isProEnabled = _useState4[0]; // eslint-disable-line react-hooks/rules-of-hooks, no-unused-vars
          var _useState5 = useState(blockOptions.panels.background._showBackgroundPreview(props)),
            _useState6 = _slicedToArray(_useState5, 2),
            showBackgroundPreview = _useState6[0],
            setShowBackgroundPreview = _useState6[1]; // eslint-disable-line react-hooks/rules-of-hooks
          var _useState7 = useState(''),
            _useState8 = _slicedToArray(_useState7, 2),
            lastBgImage = _useState8[0],
            setLastBgImage = _useState8[1]; // eslint-disable-line react-hooks/rules-of-hooks

          var uiState = {
            isNotDisabled: isNotDisabled,
            isProEnabled: isProEnabled,
            showBackgroundPreview: showBackgroundPreview,
            setShowBackgroundPreview: setShowBackgroundPreview,
            lastBgImage: lastBgImage,
            setLastBgImage: setLastBgImage
          };
          useEffect(function () {
            // eslint-disable-line react-hooks/rules-of-hooks
            if (attributes.formId) {
              setShowBackgroundPreview(props.attributes.backgroundImage !== 'none' && props.attributes.backgroundUrl && props.attributes.backgroundUrl !== 'url()');
            }
          }, [backgroundSelected, props.attributes.backgroundImage, props.attributes.backgroundUrl]); // eslint-disable-line react-hooks/exhaustive-deps

          // Get block properties.
          var blockProps = useBlockProps(); // eslint-disable-line react-hooks/rules-of-hooks, no-unused-vars

          // Store block clientId in attributes.
          if (!attributes.clientId || !app.isClientIdAttrUnique(props)) {
            // We just want the client ID to update once.
            // The block editor doesn't have a fixed block ID, so we need to get it on the initial load, but only once.
            props.setAttributes({
              clientId: props.clientId
            });
          }

          // Main block settings.
          var jsx = [app.jsxParts.getMainSettings(attributes, handlers, formOptions)];

          // Block preview picture.
          if (!app.hasForms()) {
            jsx.push(app.jsxParts.getEmptyFormsPreview(props));
            return /*#__PURE__*/React.createElement("div", blockProps, jsx);
          }
          var sizeOptions = app.getSizeOptions();

          // Show placeholder when form is not available (trashed, deleted etc.).
          if (attributes && attributes.formId && app.isFormAvailable(attributes.formId) === false) {
            // Block placeholder (form selector).
            jsx.push(app.jsxParts.getBlockPlaceholder(props.attributes, handlers, formOptions));
            return /*#__PURE__*/React.createElement("div", blockProps, jsx);
          }

          // Form style settings & block content.
          if (attributes.formId) {
            // Subscribe to block events.
            app.maybeSubscribeToBlockEvents(props, handlers, blockOptions);
            jsx.push(app.jsxParts.getStyleSettings(props, handlers, sizeOptions, blockOptions, uiState), app.jsxParts.getBlockFormContent(props));
            if (!isCopyPasteGeneratedOnEdit) {
              handlers.updateCopyPasteContent();
              isCopyPasteGeneratedOnEdit = true;
            }
            el.$window.trigger('wpformsFormSelectorEdit', [props]);
            return /*#__PURE__*/React.createElement("div", blockProps, jsx);
          }

          // Block preview picture.
          if (attributes.preview) {
            jsx.push(app.jsxParts.getBlockPreview());
            return /*#__PURE__*/React.createElement("div", blockProps, jsx);
          }

          // Block placeholder (form selector).
          jsx.push(app.jsxParts.getBlockPlaceholder(props.attributes, handlers, formOptions));
          return /*#__PURE__*/React.createElement("div", blockProps, jsx);
        },
        save: function save() {
          return null;
        }
      });
    },
    /**
     * Init default style settings.
     *
     * @since 1.8.1
     * @since 1.8.8 Added blockOptions parameter.
     *
     * @param {Object} blockOptions Additional block options.
     */
    initDefaults: function initDefaults() {
      var blockOptions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      commonAttributes = _objectSpread(_objectSpread({}, commonAttributes), blockOptions.getCommonAttributes());
      customStylesHandlers = blockOptions.setStylesHandlers;
      ['formId', 'copyPasteJsonValue'].forEach(function (key) {
        return delete defaultStyleSettings[key];
      });
    },
    /**
     * Check if the site has forms.
     *
     * @since 1.8.3
     *
     * @return {boolean} Whether site has at least one form.
     */
    hasForms: function hasForms() {
      return formList.length > 0;
    },
    /**
     * Check if form is available to be previewed.
     *
     * @since 1.8.9
     *
     * @param {number} formId Form ID.
     *
     * @return {boolean} Whether form is available.
     */
    isFormAvailable: function isFormAvailable(formId) {
      return formList.find(function (_ref2) {
        var ID = _ref2.ID;
        return ID === Number(formId);
      }) !== undefined;
    },
    /**
     * Set triggerServerRender flag.
     *
     * @since 1.8.8
     *
     * @param {boolean} $flag The value of the triggerServerRender flag.
     */
    setTriggerServerRender: function setTriggerServerRender($flag) {
      triggerServerRender = Boolean($flag);
    },
    /**
     * Maybe subscribe to block events.
     *
     * @since 1.8.8
     *
     * @param {Object} subscriberProps        Subscriber block properties.
     * @param {Object} subscriberHandlers     Subscriber block event handlers.
     * @param {Object} subscriberBlockOptions Subscriber block options.
     */
    maybeSubscribeToBlockEvents: function maybeSubscribeToBlockEvents(subscriberProps, subscriberHandlers, subscriberBlockOptions) {
      var id = subscriberProps.clientId;

      // Unsubscribe from block events.
      // This is needed to avoid multiple subscriptions when the block is re-rendered.
      el.$window.off('wpformsFormSelectorDeleteTheme.' + id).off('wpformsFormSelectorUpdateTheme.' + id).off('wpformsFormSelectorSetTheme.' + id);

      // Subscribe to block events.
      el.$window.on('wpformsFormSelectorDeleteTheme.' + id, app.subscriberDeleteTheme(subscriberProps, subscriberBlockOptions)).on('wpformsFormSelectorUpdateTheme.' + id, app.subscriberUpdateTheme(subscriberProps, subscriberBlockOptions)).on('wpformsFormSelectorSetTheme.' + id, app.subscriberSetTheme(subscriberProps, subscriberBlockOptions));
    },
    /**
     * Block event `wpformsFormSelectorDeleteTheme` handler.
     *
     * @since 1.8.8
     *
     * @param {Object} subscriberProps        Subscriber block properties
     * @param {Object} subscriberBlockOptions Subscriber block options.
     *
     * @return {Function} Event handler.
     */
    subscriberDeleteTheme: function subscriberDeleteTheme(subscriberProps, subscriberBlockOptions) {
      return function (e, themeSlug, triggerProps) {
        var _subscriberProps$attr, _subscriberBlockOptio;
        if (subscriberProps.clientId === triggerProps.clientId) {
          return;
        }
        if ((subscriberProps === null || subscriberProps === void 0 || (_subscriberProps$attr = subscriberProps.attributes) === null || _subscriberProps$attr === void 0 ? void 0 : _subscriberProps$attr.theme) !== themeSlug) {
          return;
        }
        if (!(subscriberBlockOptions !== null && subscriberBlockOptions !== void 0 && (_subscriberBlockOptio = subscriberBlockOptions.panels) !== null && _subscriberBlockOptio !== void 0 && _subscriberBlockOptio.themes)) {
          return;
        }

        // Reset theme to default one.
        subscriberBlockOptions.panels.themes.setBlockTheme(subscriberProps, 'default');
      };
    },
    /**
     * Block event `wpformsFormSelectorDeleteTheme` handler.
     *
     * @since 1.8.8
     *
     * @param {Object} subscriberProps        Subscriber block properties
     * @param {Object} subscriberBlockOptions Subscriber block options.
     *
     * @return {Function} Event handler.
     */
    subscriberUpdateTheme: function subscriberUpdateTheme(subscriberProps, subscriberBlockOptions) {
      return function (e, themeSlug, themeData, triggerProps) {
        var _subscriberProps$attr2, _subscriberBlockOptio2;
        if (subscriberProps.clientId === triggerProps.clientId) {
          return;
        }
        if ((subscriberProps === null || subscriberProps === void 0 || (_subscriberProps$attr2 = subscriberProps.attributes) === null || _subscriberProps$attr2 === void 0 ? void 0 : _subscriberProps$attr2.theme) !== themeSlug) {
          return;
        }
        if (!(subscriberBlockOptions !== null && subscriberBlockOptions !== void 0 && (_subscriberBlockOptio2 = subscriberBlockOptions.panels) !== null && _subscriberBlockOptio2 !== void 0 && _subscriberBlockOptio2.themes)) {
          return;
        }

        // Reset theme to default one.
        subscriberBlockOptions.panels.themes.setBlockTheme(subscriberProps, themeSlug);
      };
    },
    /**
     * Block event `wpformsFormSelectorSetTheme` handler.
     *
     * @since 1.8.8
     *
     * @param {Object} subscriberProps        Subscriber block properties
     * @param {Object} subscriberBlockOptions Subscriber block options.
     *
     * @return {Function} Event handler.
     */
    subscriberSetTheme: function subscriberSetTheme(subscriberProps, subscriberBlockOptions) {
      // noinspection JSUnusedLocalSymbols
      return function (e, block, themeSlug, triggerProps) {
        var _subscriberBlockOptio3;
        // eslint-disable-line no-unused-vars
        if (subscriberProps.clientId === triggerProps.clientId) {
          return;
        }
        if (!(subscriberBlockOptions !== null && subscriberBlockOptions !== void 0 && (_subscriberBlockOptio3 = subscriberBlockOptions.panels) !== null && _subscriberBlockOptio3 !== void 0 && _subscriberBlockOptio3.themes)) {
          return;
        }

        // Set theme.
        app.onSetTheme(subscriberProps);
      };
    },
    /**
     * Block JSX parts.
     *
     * @since 1.8.1
     *
     * @type {Object}
     */
    jsxParts: {
      /**
       * Get main settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} attributes  Block attributes.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} formOptions Form selector options.
       *
       * @return {JSX.Element} Main setting JSX code.
       */
      getMainSettings: function getMainSettings(attributes, handlers, formOptions) {
        if (!app.hasForms()) {
          return app.jsxParts.printEmptyFormsNotice(attributes.clientId);
        }
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-inspector-main-settings"
        }, /*#__PURE__*/React.createElement(PanelBody, {
          className: "wpforms-gutenberg-panel wpforms-gutenberg-panel-form-settings",
          title: strings.form_settings
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.form_selected,
          value: attributes.formId,
          options: formOptions,
          onChange: function onChange(value) {
            return handlers.attrChange('formId', value);
          }
        }), attributes.formId ? /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-form-selector-actions"
        }, /*#__PURE__*/React.createElement("a", {
          href: urls.form_url.replace('{ID}', attributes.formId),
          rel: "noreferrer",
          target: "_blank"
        }, strings.form_edit), isPro && isLicenseActive && /*#__PURE__*/React.createElement(React.Fragment, null, "\xA0\xA0|\xA0\xA0", /*#__PURE__*/React.createElement("a", {
          href: urls.entries_url.replace('{ID}', attributes.formId),
          rel: "noreferrer",
          target: "_blank"
        }, strings.form_entries))), /*#__PURE__*/React.createElement(ToggleControl, {
          label: strings.show_title,
          checked: attributes.displayTitle,
          onChange: function onChange(value) {
            return handlers.attrChange('displayTitle', value);
          }
        }), /*#__PURE__*/React.createElement(ToggleControl, {
          label: strings.show_description,
          checked: attributes.displayDesc,
          onChange: function onChange(value) {
            return handlers.attrChange('displayDesc', value);
          }
        })) : null, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice"
        }, /*#__PURE__*/React.createElement("strong", null, strings.panel_notice_head), strings.panel_notice_text, /*#__PURE__*/React.createElement("a", {
          href: strings.panel_notice_link,
          rel: "noreferrer",
          target: "_blank"
        }, strings.panel_notice_link_text))));
      },
      /**
       * Print empty forms notice.
       *
       * @since 1.8.3
       *
       * @param {string} clientId Block client ID.
       *
       * @return {JSX.Element} Field styles JSX code.
       */
      printEmptyFormsNotice: function printEmptyFormsNotice(clientId) {
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-inspector-main-settings"
        }, /*#__PURE__*/React.createElement(PanelBody, {
          className: "wpforms-gutenberg-panel",
          title: strings.form_settings
        }, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-empty-form-notice",
          style: {
            display: 'block'
          }
        }, /*#__PURE__*/React.createElement("strong", null, __('You haven’t created a form, yet!', 'wpforms-lite')), __('What are you waiting for?', 'wpforms-lite')), /*#__PURE__*/React.createElement("button", {
          type: "button",
          className: "get-started-button components-button is-secondary",
          onClick: function onClick() {
            app.openBuilderPopup(clientId);
          }
        }, __('Get Started', 'wpforms-lite'))));
      },
      /**
       * Get Label styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props       Block properties.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} sizeOptions Size selector options.
       *
       * @return {Object} Label styles JSX code.
       */
      getLabelStyles: function getLabelStyles(props, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.label_styles
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: props.attributes.labelSize,
          className: "wpforms-gutenberg-form-selector-fix-bottom-margin",
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('labelSize', value);
          }
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.labelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelColor', value);
            },
            label: strings.label
          }, {
            value: props.attributes.labelSublabelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelSublabelColor', value);
            },
            label: strings.sublabel_hints.replace('&amp;', '&')
          }, {
            value: props.attributes.labelErrorColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelErrorColor', value);
            },
            label: strings.error_message
          }]
        })));
      },
      /**
       * Get Page Indicator styles JSX code.
       *
       * @since 1.8.7
       *
       * @param {Object} props    Block properties.
       * @param {Object} handlers Block event handlers.
       *
       * @return {Object} Page Indicator styles JSX code.
       */
      getPageIndicatorStyles: function getPageIndicatorStyles(props, handlers) {
        // eslint-disable-line complexity
        var hasPageBreak = app.hasPageBreak(formList, props.attributes.formId);
        var hasRating = app.hasRating(formList, props.attributes.formId);
        if (!hasPageBreak && !hasRating) {
          return null;
        }
        var label = '';
        if (hasPageBreak && hasRating) {
          label = "".concat(strings.page_break, " / ").concat(strings.rating);
        } else if (hasPageBreak) {
          label = strings.page_break;
        } else if (hasRating) {
          label = strings.rating;
        }
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(props),
          title: strings.other_styles
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: props.attributes.pageBreakColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('pageBreakColor', value);
            },
            label: label
          }]
        })));
      },
      /**
       * Get style settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props        Block properties.
       * @param {Object} handlers     Block event handlers.
       * @param {Object} sizeOptions  Size selector options.
       * @param {Object} blockOptions Block options loaded from external modules.
       *
       * @param {Object} uiState 	UI state.
       *
       * @return {Object} Inspector controls JSX code.
       */
      getStyleSettings: function getStyleSettings(props, handlers, sizeOptions, blockOptions, uiState) {
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-style-settings"
        }, blockOptions.getThemesPanel(props, app, blockOptions.stockPhotos), blockOptions.getFieldStyles(props, handlers, sizeOptions, app), app.jsxParts.getLabelStyles(props, handlers, sizeOptions), blockOptions.getButtonStyles(props, handlers, sizeOptions, app), blockOptions.getContainerStyles(props, handlers, app, uiState), blockOptions.getBackgroundStyles(props, handlers, app, blockOptions.stockPhotos, uiState), app.jsxParts.getPageIndicatorStyles(props, handlers));
      },
      /**
       * Get block content JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} props Block properties.
       *
       * @return {JSX.Element} Block content JSX code.
       */
      getBlockFormContent: function getBlockFormContent(props) {
        if (triggerServerRender) {
          return /*#__PURE__*/React.createElement(ServerSideRender, {
            key: "wpforms-gutenberg-form-selector-server-side-renderer",
            block: "wpforms/form-selector",
            attributes: props.attributes
          });
        }
        var clientId = props.clientId;
        var block = app.getBlockContainer(props);

        // In the case of empty content, use server side renderer.
        // This happens when the block is duplicated or converted to a reusable block.
        if (!(block !== null && block !== void 0 && block.innerHTML)) {
          triggerServerRender = true;
          return app.jsxParts.getBlockFormContent(props);
        }
        blocks[clientId] = blocks[clientId] || {};
        blocks[clientId].blockHTML = block.innerHTML;
        blocks[clientId].loadedFormId = props.attributes.formId;
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-form-html"
        }, /*#__PURE__*/React.createElement("div", {
          dangerouslySetInnerHTML: {
            __html: blocks[clientId].blockHTML
          }
        }));
      },
      /**
       * Get block preview JSX code.
       *
       * @since 1.8.1
       *
       * @return {JSX.Element} Block preview JSX code.
       */
      getBlockPreview: function getBlockPreview() {
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_preview_url,
          style: {
            width: '100%'
          },
          alt: ""
        }));
      },
      /**
       * Get block empty JSX code.
       *
       * @since 1.8.3
       *
       * @param {Object} props Block properties.
       * @return {JSX.Element} Block empty JSX code.
       */
      getEmptyFormsPreview: function getEmptyFormsPreview(props) {
        var clientId = props.clientId;
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-empty"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-no-form-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_empty_url,
          alt: ""
        }), /*#__PURE__*/React.createElement("p", null, createInterpolateElement(__('You can use <b>WPForms</b> to build contact forms, surveys, payment forms, and more with just a few clicks.', 'wpforms-lite'), {
          b: /*#__PURE__*/React.createElement("strong", null)
        })), /*#__PURE__*/React.createElement("button", {
          type: "button",
          className: "get-started-button components-button is-primary",
          onClick: function onClick() {
            app.openBuilderPopup(clientId);
          }
        }, __('Get Started', 'wpforms-lite')), /*#__PURE__*/React.createElement("p", {
          className: "empty-desc"
        }, createInterpolateElement(__('Need some help? Check out our <a>comprehensive guide.</a>', 'wpforms-lite'), {
          // eslint-disable-next-line jsx-a11y/anchor-has-content
          a: /*#__PURE__*/React.createElement("a", {
            href: wpforms_gutenberg_form_selector.wpforms_guide,
            target: "_blank",
            rel: "noopener noreferrer"
          })
        })), /*#__PURE__*/React.createElement("div", {
          id: "wpforms-gutenberg-popup",
          className: "wpforms-builder-popup"
        }, /*#__PURE__*/React.createElement("iframe", {
          src: "about:blank",
          width: "100%",
          height: "100%",
          id: "wpforms-builder-iframe",
          title: "WPForms Builder Popup"
        }))));
      },
      /**
       * Get block placeholder (form selector) JSX code.
       *
       * @since 1.8.1
       *
       * @param {Object} attributes  Block attributes.
       * @param {Object} handlers    Block event handlers.
       * @param {Object} formOptions Form selector options.
       *
       * @return {JSX.Element} Block placeholder JSX code.
       */
      getBlockPlaceholder: function getBlockPlaceholder(attributes, handlers, formOptions) {
        var isFormNotAvailable = attributes.formId && !app.isFormAvailable(attributes.formId);
        return /*#__PURE__*/React.createElement(Placeholder, {
          key: "wpforms-gutenberg-form-selector-wrap",
          className: "wpforms-gutenberg-form-selector-wrap"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.logo_url,
          alt: ""
        }), isFormNotAvailable && /*#__PURE__*/React.createElement("p", {
          style: {
            textAlign: 'center',
            marginTop: '0'
          }
        }, strings.form_not_available_message), /*#__PURE__*/React.createElement(SelectControl, {
          key: "wpforms-gutenberg-form-selector-select-control",
          value: attributes.formId,
          options: formOptions,
          onChange: function onChange(value) {
            return handlers.attrChange('formId', value);
          }
        }));
      }
    },
    /**
     * Determine if the form has a Page Break field.
     *
     * @since 1.8.7
     *
     * @param {Object}        forms  The forms' data object.
     * @param {number|string} formId Form ID.
     *
     * @return {boolean} True when the form has a Page Break field, false otherwise.
     */
    hasPageBreak: function hasPageBreak(forms, formId) {
      var _JSON$parse;
      var currentForm = forms.find(function (form) {
        return parseInt(form.ID, 10) === parseInt(formId, 10);
      });
      if (!currentForm.post_content) {
        return false;
      }
      var fields = (_JSON$parse = JSON.parse(currentForm.post_content)) === null || _JSON$parse === void 0 ? void 0 : _JSON$parse.fields;
      return Object.values(fields).some(function (field) {
        return field.type === 'pagebreak';
      });
    },
    hasRating: function hasRating(forms, formId) {
      var _JSON$parse2;
      var currentForm = forms.find(function (form) {
        return parseInt(form.ID, 10) === parseInt(formId, 10);
      });
      if (!currentForm.post_content || !isPro || !isLicenseActive) {
        return false;
      }
      var fields = (_JSON$parse2 = JSON.parse(currentForm.post_content)) === null || _JSON$parse2 === void 0 ? void 0 : _JSON$parse2.fields;
      return Object.values(fields).some(function (field) {
        return field.type === 'rating';
      });
    },
    /**
     * Get Style Settings panel class.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     * @param {string} panel Panel name.
     *
     * @return {string} Style Settings panel class.
     */
    getPanelClass: function getPanelClass(props) {
      var panel = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var cssClass = 'wpforms-gutenberg-panel wpforms-block-settings-' + props.clientId;
      if (!app.isFullStylingEnabled()) {
        cssClass += ' disabled_panel';
      }

      // Restrict styling panel for non-admins.
      if (!(isAdmin || panel === 'themes')) {
        cssClass += ' wpforms-gutenberg-panel-restricted';
      }
      return cssClass;
    },
    /**
     * Get color panel settings CSS class.
     *
     * @since 1.8.8
     *
     * @param {string} borderStyle Border style value.
     *
     * @return {string} Style Settings panel class.
     */
    getColorPanelClass: function getColorPanelClass(borderStyle) {
      var cssClass = 'wpforms-gutenberg-form-selector-color-panel';
      if (borderStyle === 'none') {
        cssClass += ' wpforms-gutenberg-form-selector-border-color-disabled';
      }
      return cssClass;
    },
    /**
     * Determine whether the full styling is enabled.
     *
     * @since 1.8.1
     *
     * @return {boolean} Whether the full styling is enabled.
     */
    isFullStylingEnabled: function isFullStylingEnabled() {
      return wpforms_gutenberg_form_selector.is_modern_markup && wpforms_gutenberg_form_selector.is_full_styling;
    },
    /**
     * Determine whether the block has lead forms enabled.
     *
     * @since 1.9.0
     *
     * @param {Object} block Gutenberg block
     *
     * @return {boolean} Whether the block has lead forms enabled
     */
    isLeadFormsEnabled: function isLeadFormsEnabled(block) {
      if (!block) {
        return false;
      }
      var $form = $(block.querySelector('.wpforms-container'));
      return $form.hasClass('wpforms-lead-forms-container');
    },
    /**
     * Get block container DOM element.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     *
     * @return {Element} Block container.
     */
    getBlockContainer: function getBlockContainer(props) {
      var blockSelector = "#block-".concat(props.clientId, " > div");
      var block = document.querySelector(blockSelector);

      // For FSE / Gutenberg plugin, we need to take a look inside the iframe.
      if (!block) {
        var editorCanvas = document.querySelector('iframe[name="editor-canvas"]');
        block = editorCanvas === null || editorCanvas === void 0 ? void 0 : editorCanvas.contentWindow.document.querySelector(blockSelector);
      }
      return block;
    },
    /**
     * Get form container in Block Editor.
     *
     * @since 1.9.3
     *
     * @param {number} formId Form ID.
     *
     * @return {Element|null} Form container.
     */
    getFormBlock: function getFormBlock(formId) {
      // First, try to find the iframe for blocks version 3.
      var editorCanvas = document.querySelector('iframe[name="editor-canvas"]');

      // If the iframe is found, try to find the form.
      return (editorCanvas === null || editorCanvas === void 0 ? void 0 : editorCanvas.contentWindow.document.querySelector("#wpforms-".concat(formId))) || $("#wpforms-".concat(formId));
    },
    /**
     * Update CSS variable(s) value(s) of the given attribute for given container on the preview.
     *
     * @since 1.8.8
     *
     * @param {string}  attribute Style attribute: field-size, label-size, button-size, etc.
     * @param {string}  value     Property new value.
     * @param {Element} container Form container.
     * @param {Object}  props     Block properties.
     */
    updatePreviewCSSVarValue: function updatePreviewCSSVarValue(attribute, value, container, props) {
      // eslint-disable-line complexity, max-lines-per-function
      if (!container || !attribute) {
        return;
      }
      var property = attribute.replace(/[A-Z]/g, function (letter) {
        return "-".concat(letter.toLowerCase());
      });
      if (typeof customStylesHandlers[property] === 'function') {
        customStylesHandlers[property](container, value);
        return;
      }
      switch (property) {
        case 'field-size':
        case 'label-size':
        case 'button-size':
        case 'container-shadow-size':
          for (var key in sizes[property][value]) {
            container.style.setProperty("--wpforms-".concat(property, "-").concat(key), sizes[property][value][key]);
          }
          break;
        case 'field-border-style':
          if (value === 'none') {
            app.toggleFieldBorderNoneCSSVarValue(container, true);
          } else {
            app.toggleFieldBorderNoneCSSVarValue(container, false);
            container.style.setProperty("--wpforms-".concat(property), value);
          }
          break;
        case 'button-background-color':
          app.maybeUpdateAccentColor(props.attributes.buttonBorderColor, value, container);
          value = app.maybeSetButtonAltBackgroundColor(value, props.attributes.buttonBorderColor, container);
          app.maybeSetButtonAltTextColor(props.attributes.buttonTextColor, value, props.attributes.buttonBorderColor, container);
          container.style.setProperty("--wpforms-".concat(property), value);
          break;
        case 'button-border-color':
          app.maybeUpdateAccentColor(value, props.attributes.buttonBackgroundColor, container);
          app.maybeSetButtonAltTextColor(props.attributes.buttonTextColor, props.attributes.buttonBackgroundColor, value, container);
          container.style.setProperty("--wpforms-".concat(property), value);
          break;
        case 'button-text-color':
          app.maybeSetButtonAltTextColor(value, props.attributes.buttonBackgroundColor, props.attributes.buttonBorderColor, container);
          container.style.setProperty("--wpforms-".concat(property), value);
          break;
        default:
          container.style.setProperty("--wpforms-".concat(property), value);
          container.style.setProperty("--wpforms-".concat(property, "-spare"), value);
      }
    },
    /**
     * Set/unset field border vars in case of border-style is none.
     *
     * @since 1.8.8
     *
     * @param {Object}  container Form container.
     * @param {boolean} set       True when set, false when unset.
     */
    toggleFieldBorderNoneCSSVarValue: function toggleFieldBorderNoneCSSVarValue(container, set) {
      var cont = container.querySelector('form');
      if (set) {
        cont.style.setProperty('--wpforms-field-border-style', 'solid');
        cont.style.setProperty('--wpforms-field-border-size', '1px');
        cont.style.setProperty('--wpforms-field-border-color', 'transparent');
        return;
      }
      cont.style.setProperty('--wpforms-field-border-style', null);
      cont.style.setProperty('--wpforms-field-border-size', null);
      cont.style.setProperty('--wpforms-field-border-color', null);
    },
    /**
     * Maybe set the button's alternative background color.
     *
     * @since 1.8.8
     *
     * @param {string} value             Attribute value.
     * @param {string} buttonBorderColor Button border color.
     * @param {Object} container         Form container.
     *
     * @return {string|*} New background color.
     */
    maybeSetButtonAltBackgroundColor: function maybeSetButtonAltBackgroundColor(value, buttonBorderColor, container) {
      // Setting css property value to child `form` element overrides the parent property value.
      var form = container.querySelector('form');
      form.style.setProperty('--wpforms-button-background-color-alt', value);
      if (WPFormsUtils.cssColorsUtils.isTransparentColor(value)) {
        return WPFormsUtils.cssColorsUtils.isTransparentColor(buttonBorderColor) ? defaultStyleSettings.buttonBackgroundColor : buttonBorderColor;
      }
      return value;
    },
    /**
     * Maybe set the button's alternative text color.
     *
     * @since 1.8.8
     *
     * @param {string} value                 Attribute value.
     * @param {string} buttonBackgroundColor Button background color.
     * @param {string} buttonBorderColor     Button border color.
     * @param {Object} container             Form container.
     */
    maybeSetButtonAltTextColor: function maybeSetButtonAltTextColor(value, buttonBackgroundColor, buttonBorderColor, container) {
      var form = container.querySelector('form');
      var altColor = null;
      value = value.toLowerCase();
      if (WPFormsUtils.cssColorsUtils.isTransparentColor(value) || value === buttonBackgroundColor || WPFormsUtils.cssColorsUtils.isTransparentColor(buttonBackgroundColor) && value === buttonBorderColor) {
        altColor = WPFormsUtils.cssColorsUtils.getContrastColor(buttonBackgroundColor);
      }
      container.style.setProperty("--wpforms-button-text-color-alt", value);
      form.style.setProperty("--wpforms-button-text-color-alt", altColor);
    },
    /**
     * Maybe update accent color.
     *
     * @since 1.8.8
     *
     * @param {string} color                 Color value.
     * @param {string} buttonBackgroundColor Button background color.
     * @param {Object} container             Form container.
     */
    maybeUpdateAccentColor: function maybeUpdateAccentColor(color, buttonBackgroundColor, container) {
      // Setting css property value to child `form` element overrides the parent property value.
      var form = container.querySelector('form');

      // Fallback to default color if the border color is transparent.
      color = WPFormsUtils.cssColorsUtils.isTransparentColor(color) ? defaultStyleSettings.buttonBackgroundColor : color;
      if (WPFormsUtils.cssColorsUtils.isTransparentColor(buttonBackgroundColor)) {
        form.style.setProperty('--wpforms-button-background-color-alt', 'rgba( 0, 0, 0, 0 )');
        form.style.setProperty('--wpforms-button-background-color', color);
      } else {
        container.style.setProperty('--wpforms-button-background-color-alt', buttonBackgroundColor);
        form.style.setProperty('--wpforms-button-background-color-alt', null);
        form.style.setProperty('--wpforms-button-background-color', null);
      }
    },
    /**
     * Get settings fields event handlers.
     *
     * @since 1.8.1
     *
     * @param {Object} props Block properties.
     *
     * @return {Object} Object that contains event handlers for the settings fields.
     */
    getSettingsFieldsHandlers: function getSettingsFieldsHandlers(props) {
      // eslint-disable-line max-lines-per-function
      return {
        /**
         * Field style attribute change event handler.
         *
         * @since 1.8.1
         *
         * @param {string} attribute Attribute name.
         * @param {string} value     New attribute value.
         */
        styleAttrChange: function styleAttrChange(attribute, value) {
          var block = app.getBlockContainer(props),
            container = block.querySelector("#wpforms-".concat(props.attributes.formId)),
            setAttr = {};

          // Unset the color means setting the transparent color.
          if (attribute.includes('Color')) {
            value = value !== null && value !== void 0 ? value : 'rgba( 0, 0, 0, 0 )';
          }
          app.updatePreviewCSSVarValue(attribute, value, container, props);
          setAttr[attribute] = value;
          app.setBlockRuntimeStateVar(props.clientId, 'prevAttributesState', props.attributes);
          props.setAttributes(setAttr);
          triggerServerRender = false;
          this.updateCopyPasteContent();
          app.panels.themes.updateCustomThemeAttribute(attribute, value, props);
          this.maybeToggleDropdown(props, attribute);

          // Trigger event for developers.
          el.$window.trigger('wpformsFormSelectorStyleAttrChange', [block, props, attribute, value]);
        },
        /**
         * Handles the toggling of the dropdown menu's visibility.
         *
         * @since 1.8.8
         *
         * @param {Object} props     The block properties.
         * @param {string} attribute The name of the attribute being changed.
         */
        maybeToggleDropdown: function maybeToggleDropdown(props, attribute) {
          var _this = this;
          // eslint-disable-line no-shadow
          var formId = props.attributes.formId;
          var menu = document.querySelector("#wpforms-form-".concat(formId, " .choices__list.choices__list--dropdown"));
          var classicMenu = document.querySelector("#wpforms-form-".concat(formId, " .wpforms-field-select-style-classic select"));
          if (attribute === 'fieldMenuColor') {
            if (menu) {
              menu.classList.add('is-active');
              menu.parentElement.classList.add('is-open');
            } else {
              this.showClassicMenu(classicMenu);
            }
            clearTimeout(dropdownTimeout);
            dropdownTimeout = setTimeout(function () {
              var toClose = document.querySelector("#wpforms-form-".concat(formId, " .choices__list.choices__list--dropdown"));
              if (toClose) {
                toClose.classList.remove('is-active');
                toClose.parentElement.classList.remove('is-open');
              } else {
                _this.hideClassicMenu(document.querySelector("#wpforms-form-".concat(formId, " .wpforms-field-select-style-classic select")));
              }
            }, 5000);
          } else if (menu) {
            menu.classList.remove('is-active');
          } else {
            this.hideClassicMenu(classicMenu);
          }
        },
        /**
         * Shows the classic menu.
         *
         * @since 1.8.8
         *
         * @param {Object} classicMenu The classic menu.
         */
        showClassicMenu: function showClassicMenu(classicMenu) {
          if (!classicMenu) {
            return;
          }
          classicMenu.size = 2;
          classicMenu.style.cssText = 'padding-top: 40px; padding-inline-end: 0; padding-inline-start: 0; position: relative;';
          classicMenu.querySelectorAll('option').forEach(function (option) {
            option.style.cssText = 'border-left: 1px solid #8c8f94; border-right: 1px solid #8c8f94; padding: 0 10px; z-index: 999999; position: relative;';
          });
          classicMenu.querySelector('option:last-child').style.cssText = 'border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; padding: 0 10px; border-left: 1px solid #8c8f94; border-right: 1px solid #8c8f94; border-bottom: 1px solid #8c8f94; z-index: 999999; position: relative;';
        },
        /**
         * Hides the classic menu.
         *
         * @since 1.8.8
         *
         * @param {Object} classicMenu The classic menu.
         */
        hideClassicMenu: function hideClassicMenu(classicMenu) {
          if (!classicMenu) {
            return;
          }
          classicMenu.size = 0;
          classicMenu.style.cssText = 'padding-top: 0; padding-inline-end: 24px; padding-inline-start: 12px; position: relative;';
          classicMenu.querySelectorAll('option').forEach(function (option) {
            option.style.cssText = 'border: none;';
          });
        },
        /**
         * Field regular attribute change event handler.
         *
         * @since 1.8.1
         *
         * @param {string} attribute Attribute name.
         * @param {string} value     New attribute value.
         */
        attrChange: function attrChange(attribute, value) {
          var setAttr = {};
          setAttr[attribute] = value;
          app.setBlockRuntimeStateVar(props.clientId, 'prevAttributesState', props.attributes);
          props.setAttributes(setAttr);
          triggerServerRender = true;
          this.updateCopyPasteContent();
        },
        /**
         * Update content of the "Copy/Paste" fields.
         *
         * @since 1.8.1
         */
        updateCopyPasteContent: function updateCopyPasteContent() {
          var content = {};
          var atts = wp.data.select('core/block-editor').getBlockAttributes(props.clientId);
          for (var key in defaultStyleSettings) {
            content[key] = atts[key];
          }
          props.setAttributes({
            copyPasteJsonValue: JSON.stringify(content)
          });
        },
        /**
         * Paste settings handler.
         *
         * @since 1.8.1
         *
         * @param {string} value New attribute value.
         */
        pasteSettings: function pasteSettings(value) {
          value = value.trim();
          var pasteAttributes = app.parseValidateJson(value);
          if (!pasteAttributes) {
            if (value) {
              wp.data.dispatch('core/notices').createErrorNotice(strings.copy_paste_error, {
                id: 'wpforms-json-parse-error'
              });
            }
            this.updateCopyPasteContent();
            return;
          }
          pasteAttributes.copyPasteJsonValue = value;
          var themeSlug = app.panels.themes.maybeCreateCustomThemeFromAttributes(pasteAttributes);
          app.setBlockRuntimeStateVar(props.clientId, 'prevAttributesState', props.attributes);
          props.setAttributes(pasteAttributes);
          app.panels.themes.setBlockTheme(props, themeSlug);
          triggerServerRender = false;
        }
      };
    },
    /**
     * Parse and validate JSON string.
     *
     * @since 1.8.1
     *
     * @param {string} value JSON string.
     *
     * @return {boolean|object} Parsed JSON object OR false on error.
     */
    parseValidateJson: function parseValidateJson(value) {
      if (typeof value !== 'string') {
        return false;
      }
      var atts;
      try {
        atts = JSON.parse(value.trim());
      } catch (error) {
        atts = false;
      }
      return atts;
    },
    /**
     * Get WPForms icon DOM element.
     *
     * @since 1.8.1
     *
     * @return {DOM.element} WPForms icon DOM element.
     */
    getIcon: function getIcon() {
      return createElement('svg', {
        width: 20,
        height: 20,
        viewBox: '0 0 612 612',
        className: 'dashicon'
      }, createElement('path', {
        fill: 'currentColor',
        d: 'M544,0H68C30.445,0,0,30.445,0,68v476c0,37.556,30.445,68,68,68h476c37.556,0,68-30.444,68-68V68 C612,30.445,581.556,0,544,0z M464.44,68L387.6,120.02L323.34,68H464.44z M288.66,68l-64.26,52.02L147.56,68H288.66z M544,544H68 V68h22.1l136,92.14l79.9-64.6l79.56,64.6l136-92.14H544V544z M114.24,263.16h95.88v-48.28h-95.88V263.16z M114.24,360.4h95.88 v-48.62h-95.88V360.4z M242.76,360.4h255v-48.62h-255V360.4L242.76,360.4z M242.76,263.16h255v-48.28h-255V263.16L242.76,263.16z M368.22,457.3h129.54V408H368.22V457.3z'
      }));
    },
    /**
     * Get WPForms blocks.
     *
     * @since 1.8.8
     *
     * @return {Array} Blocks array.
     */
    getWPFormsBlocks: function getWPFormsBlocks() {
      var wpformsBlocks = wp.data.select('core/block-editor').getBlocks();
      return wpformsBlocks.filter(function (props) {
        return props.name === 'wpforms/form-selector';
      });
    },
    /**
     * Get WPForms blocks.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @return {Object} Block attributes.
     */
    isClientIdAttrUnique: function isClientIdAttrUnique(props) {
      var wpformsBlocks = app.getWPFormsBlocks();
      for (var key in wpformsBlocks) {
        // Skip the current block.
        if (wpformsBlocks[key].clientId === props.clientId) {
          continue;
        }
        if (wpformsBlocks[key].attributes.clientId === props.attributes.clientId) {
          return false;
        }
      }
      return true;
    },
    /**
     * Get block attributes.
     *
     * @since 1.8.1
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      return commonAttributes;
    },
    /**
     * Get block runtime state variable.
     *
     * @since 1.8.8
     *
     * @param {string} clientId Block client ID.
     * @param {string} varName  Block runtime variable name.
     *
     * @return {*} Block runtime state variable value.
     */
    getBlockRuntimeStateVar: function getBlockRuntimeStateVar(clientId, varName) {
      var _blocks$clientId;
      return (_blocks$clientId = blocks[clientId]) === null || _blocks$clientId === void 0 ? void 0 : _blocks$clientId[varName];
    },
    /**
     * Set block runtime state variable value.
     *
     * @since 1.8.8
     *
     * @param {string} clientId Block client ID.
     * @param {string} varName  Block runtime state key.
     * @param {*}      value    State variable value.
     *
     * @return {boolean} True on success.
     */
    setBlockRuntimeStateVar: function setBlockRuntimeStateVar(clientId, varName, value) {
      // eslint-disable-line complexity
      if (!clientId || !varName) {
        return false;
      }
      blocks[clientId] = blocks[clientId] || {};
      blocks[clientId][varName] = value;

      // Prevent referencing to object.
      if (_typeof(value) === 'object' && !Array.isArray(value) && value !== null) {
        blocks[clientId][varName] = _objectSpread({}, value);
      }
      return true;
    },
    /**
     * Get form selector options.
     *
     * @since 1.8.1
     *
     * @return {Array} Form options.
     */
    getFormOptions: function getFormOptions() {
      var formOptions = formList.map(function (value) {
        return {
          value: value.ID,
          label: value.post_title
        };
      });
      formOptions.unshift({
        value: '',
        label: strings.form_select
      });
      return formOptions;
    },
    /**
     * Get size selector options.
     *
     * @since 1.8.1
     *
     * @return {Array} Size options.
     */
    getSizeOptions: function getSizeOptions() {
      return [{
        label: strings.small,
        value: 'small'
      }, {
        label: strings.medium,
        value: 'medium'
      }, {
        label: strings.large,
        value: 'large'
      }];
    },
    /**
     * Event `wpformsFormSelectorEdit` handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e     Event object.
     * @param {Object} props Block properties.
     */
    blockEdit: function blockEdit(e, props) {
      var block = app.getBlockContainer(props);
      if (!(block !== null && block !== void 0 && block.dataset)) {
        return;
      }
      app.initLeadFormSettings(block.parentElement);
    },
    /**
     * Init Lead Form Settings panels.
     *
     * @since 1.8.1
     *
     * @param {Element} block         Block element.
     * @param {Object}  block.dataset Block element.
     */
    initLeadFormSettings: function initLeadFormSettings(block) {
      if (!(block !== null && block !== void 0 && block.dataset)) {
        return;
      }
      if (!app.isFullStylingEnabled()) {
        return;
      }
      var clientId = block.dataset.block;
      var $panel = $(".wpforms-block-settings-".concat(clientId));
      if (app.isLeadFormsEnabled(block)) {
        $panel.addClass('disabled_panel').find('.wpforms-gutenberg-panel-notice.wpforms-lead-form-notice').css('display', 'block');
        $panel.find('.wpforms-gutenberg-panel-notice.wpforms-use-modern-notice').css('display', 'none');
        return;
      }
      $panel.removeClass('disabled_panel').find('.wpforms-gutenberg-panel-notice.wpforms-lead-form-notice').css('display', 'none');
      $panel.find('.wpforms-gutenberg-panel-notice.wpforms-use-modern-notice').css('display', null);
    },
    /**
     * Event `wpformsFormSelectorFormLoaded` handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e Event object.
     */
    formLoaded: function formLoaded(e) {
      app.initLeadFormSettings(e.detail.block);
      app.updateAccentColors(e.detail);
      app.loadChoicesJS(e.detail);
      app.initRichTextField(e.detail.formId);
      app.initRepeaterField(e.detail.formId);
      $(e.detail.block).off('click').on('click', app.blockClick);
    },
    /**
     * Click on the block event handler.
     *
     * @since 1.8.1
     *
     * @param {Object} e Event object.
     */
    blockClick: function blockClick(e) {
      app.initLeadFormSettings(e.currentTarget);
    },
    /**
     * Update accent colors of some fields in GB block in Modern Markup mode.
     *
     * @since 1.8.1
     *
     * @param {Object} detail Event details object.
     */
    updateAccentColors: function updateAccentColors(detail) {
      var _window$WPForms;
      if (!wpforms_gutenberg_form_selector.is_modern_markup || !((_window$WPForms = window.WPForms) !== null && _window$WPForms !== void 0 && _window$WPForms.FrontendModern) || !(detail !== null && detail !== void 0 && detail.block)) {
        return;
      }
      var $form = $(detail.block.querySelector("#wpforms-".concat(detail.formId))),
        FrontendModern = window.WPForms.FrontendModern;
      FrontendModern.updateGBBlockPageIndicatorColor($form);
      FrontendModern.updateGBBlockIconChoicesColor($form);
      FrontendModern.updateGBBlockRatingColor($form);
    },
    /**
     * Init Modern style Dropdown fields (<select>).
     *
     * @since 1.8.1
     *
     * @param {Object} detail Event details object.
     */
    loadChoicesJS: function loadChoicesJS(detail) {
      if (typeof window.Choices !== 'function') {
        return;
      }
      var $form = $(detail.block.querySelector("#wpforms-".concat(detail.formId)));
      $form.find('.choicesjs-select').each(function (idx, selectEl) {
        var $el = $(selectEl);
        if ($el.data('choice') === 'active') {
          return;
        }
        var args = window.wpforms_choicesjs_config || {},
          searchEnabled = $el.data('search-enabled'),
          $field = $el.closest('.wpforms-field');
        args.searchEnabled = 'undefined' !== typeof searchEnabled ? searchEnabled : true;
        args.callbackOnInit = function () {
          var self = this,
            $element = $(self.passedElement.element),
            $input = $(self.input.element),
            sizeClass = $element.data('size-class');

          // Add CSS-class for size.
          if (sizeClass) {
            $(self.containerOuter.element).addClass(sizeClass);
          }

          /**
           * If a multiple select has selected choices - hide a placeholder text.
           * In case if select is empty - we return placeholder text.
           */
          if ($element.prop('multiple')) {
            // On init event.
            $input.data('placeholder', $input.attr('placeholder'));
            if (self.getValue(true).length) {
              $input.hide();
            }
          }
          this.disable();
          $field.find('.is-disabled').removeClass('is-disabled');
        };
        try {
          if (!(selectEl instanceof parent.HTMLSelectElement)) {
            Object.setPrototypeOf(selectEl, parent.HTMLSelectElement.prototype);
          }
          $el.data('choicesjs', new parent.Choices(selectEl, args));
        } catch (e) {} // eslint-disable-line no-empty
      });
    },
    /**
     * Initialize RichText field.
     *
     * @since 1.8.1
     *
     * @param {number} formId Form ID.
     */
    initRichTextField: function initRichTextField(formId) {
      var form = app.getFormBlock(formId);
      if (!form) {
        return;
      }

      // Set default tab to `Visual`.
      $(form).find('.wp-editor-wrap').removeClass('html-active').addClass('tmce-active');
    },
    /**
     * Initialize Repeater field.
     *
     * @since 1.8.9
     *
     * @param {number} formId Form ID.
     */
    initRepeaterField: function initRepeaterField(formId) {
      var form = app.getFormBlock(formId);
      if (!form) {
        return;
      }
      var $rowButtons = $(form).find('.wpforms-field-repeater > .wpforms-field-repeater-display-rows .wpforms-field-repeater-display-rows-buttons');

      // Get the label height and set the button position.
      $rowButtons.each(function () {
        var $cont = $(this);
        var $labels = $cont.siblings('.wpforms-layout-column').find('.wpforms-field').find('.wpforms-field-label');
        if (!$labels.length) {
          return;
        }
        var $label = $labels.first();
        var labelStyle = window.getComputedStyle($label.get(0));
        var margin = (labelStyle === null || labelStyle === void 0 ? void 0 : labelStyle.getPropertyValue('--wpforms-field-size-input-spacing')) || 0;
        var height = $label.outerHeight() || 0;
        var top = height + parseInt(margin, 10) + 10;
        $cont.css({
          top: top
        });
      });

      // Init buttons and descriptions for each repeater in each form.
      $(".wpforms-form[data-formid=\"".concat(formId, "\"]")).each(function () {
        var $repeater = $(this).find('.wpforms-field-repeater');
        $repeater.find('.wpforms-field-repeater-display-rows-buttons').addClass('wpforms-init');
        $repeater.find('.wpforms-field-repeater-display-rows:last .wpforms-field-description').addClass('wpforms-init');
      });
    },
    /**
     * Handle theme change.
     *
     * @since 1.9.3
     *
     * @param {Object} props Block properties.
     */
    onSetTheme: function onSetTheme(props) {
      backgroundSelected = props.attributes.backgroundImage !== 'url()';
    }
  };

  // Provide access to public functions/properties.
  return app;
}(document, window, jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfcmVnZW5lcmF0b3JSdW50aW1lIiwiZSIsInQiLCJyIiwiT2JqZWN0IiwicHJvdG90eXBlIiwibiIsImhhc093blByb3BlcnR5IiwibyIsImRlZmluZVByb3BlcnR5IiwidmFsdWUiLCJpIiwiU3ltYm9sIiwiYSIsIml0ZXJhdG9yIiwiYyIsImFzeW5jSXRlcmF0b3IiLCJ1IiwidG9TdHJpbmdUYWciLCJkZWZpbmUiLCJlbnVtZXJhYmxlIiwiY29uZmlndXJhYmxlIiwid3JpdGFibGUiLCJ3cmFwIiwiR2VuZXJhdG9yIiwiY3JlYXRlIiwiQ29udGV4dCIsIm1ha2VJbnZva2VNZXRob2QiLCJ0cnlDYXRjaCIsInR5cGUiLCJhcmciLCJjYWxsIiwiaCIsImwiLCJmIiwicyIsInkiLCJHZW5lcmF0b3JGdW5jdGlvbiIsIkdlbmVyYXRvckZ1bmN0aW9uUHJvdG90eXBlIiwicCIsImQiLCJnZXRQcm90b3R5cGVPZiIsInYiLCJ2YWx1ZXMiLCJnIiwiZGVmaW5lSXRlcmF0b3JNZXRob2RzIiwiZm9yRWFjaCIsIl9pbnZva2UiLCJBc3luY0l0ZXJhdG9yIiwiaW52b2tlIiwiX3R5cGVvZiIsInJlc29sdmUiLCJfX2F3YWl0IiwidGhlbiIsImNhbGxJbnZva2VXaXRoTWV0aG9kQW5kQXJnIiwiRXJyb3IiLCJkb25lIiwibWV0aG9kIiwiZGVsZWdhdGUiLCJtYXliZUludm9rZURlbGVnYXRlIiwic2VudCIsIl9zZW50IiwiZGlzcGF0Y2hFeGNlcHRpb24iLCJhYnJ1cHQiLCJyZXR1cm4iLCJUeXBlRXJyb3IiLCJyZXN1bHROYW1lIiwibmV4dCIsIm5leHRMb2MiLCJwdXNoVHJ5RW50cnkiLCJ0cnlMb2MiLCJjYXRjaExvYyIsImZpbmFsbHlMb2MiLCJhZnRlckxvYyIsInRyeUVudHJpZXMiLCJwdXNoIiwicmVzZXRUcnlFbnRyeSIsImNvbXBsZXRpb24iLCJyZXNldCIsImlzTmFOIiwibGVuZ3RoIiwiZGlzcGxheU5hbWUiLCJpc0dlbmVyYXRvckZ1bmN0aW9uIiwiY29uc3RydWN0b3IiLCJuYW1lIiwibWFyayIsInNldFByb3RvdHlwZU9mIiwiX19wcm90b19fIiwiYXdyYXAiLCJhc3luYyIsIlByb21pc2UiLCJrZXlzIiwicmV2ZXJzZSIsInBvcCIsInByZXYiLCJjaGFyQXQiLCJzbGljZSIsInN0b3AiLCJydmFsIiwiaGFuZGxlIiwiY29tcGxldGUiLCJmaW5pc2giLCJjYXRjaCIsIl9jYXRjaCIsImRlbGVnYXRlWWllbGQiLCJhc3luY0dlbmVyYXRvclN0ZXAiLCJfYXN5bmNUb0dlbmVyYXRvciIsImFyZ3VtZW50cyIsImFwcGx5IiwiX25leHQiLCJfdGhyb3ciLCJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiZG9jdW1lbnQiLCJ3aW5kb3ciLCIkIiwiX3dwIiwid3AiLCJfd3Akc2VydmVyU2lkZVJlbmRlciIsInNlcnZlclNpZGVSZW5kZXIiLCJTZXJ2ZXJTaWRlUmVuZGVyIiwiY29tcG9uZW50cyIsIl93cCRlbGVtZW50IiwiZWxlbWVudCIsImNyZWF0ZUVsZW1lbnQiLCJGcmFnbWVudCIsImNyZWF0ZUludGVycG9sYXRlRWxlbWVudCIsInJlZ2lzdGVyQmxvY2tUeXBlIiwiYmxvY2tzIiwiX3JlZiIsImJsb2NrRWRpdG9yIiwiZWRpdG9yIiwiSW5zcGVjdG9yQ29udHJvbHMiLCJQYW5lbENvbG9yU2V0dGluZ3MiLCJ1c2VCbG9ja1Byb3BzIiwiX3dwJGNvbXBvbmVudHMiLCJTZWxlY3RDb250cm9sIiwiVG9nZ2xlQ29udHJvbCIsIlBhbmVsQm9keSIsIlBsYWNlaG9sZGVyIiwiX18iLCJpMThuIiwiX3dwJGVsZW1lbnQyIiwidXNlU3RhdGUiLCJ1c2VFZmZlY3QiLCJfd3Bmb3Jtc19ndXRlbmJlcmdfZm8iLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwic3RyaW5ncyIsImRlZmF1bHRzIiwic2l6ZXMiLCJ1cmxzIiwiaXNQcm8iLCJpc0xpY2Vuc2VBY3RpdmUiLCJpc0FkbWluIiwiZGVmYXVsdFN0eWxlU2V0dGluZ3MiLCJXUEZvcm1zRWR1Y2F0aW9uIiwiZm9ybUxpc3QiLCJmb3JtcyIsInRyaWdnZXJTZXJ2ZXJSZW5kZXIiLCIkcG9wdXAiLCJpc0ZldGNoaW5nIiwiZWwiLCJjb21tb25BdHRyaWJ1dGVzIiwiY2xpZW50SWQiLCJmb3JtSWQiLCJkaXNwbGF5VGl0bGUiLCJkaXNwbGF5RGVzYyIsInByZXZpZXciLCJ0aGVtZSIsInRoZW1lTmFtZSIsImxhYmVsU2l6ZSIsImxhYmVsQ29sb3IiLCJsYWJlbFN1YmxhYmVsQ29sb3IiLCJsYWJlbEVycm9yQ29sb3IiLCJwYWdlQnJlYWtDb2xvciIsImN1c3RvbUNzcyIsImNvcHlQYXN0ZUpzb25WYWx1ZSIsImN1c3RvbVN0eWxlc0hhbmRsZXJzIiwiZHJvcGRvd25UaW1lb3V0IiwiaXNDb3B5UGFzdGVHZW5lcmF0ZWRPbkVkaXQiLCJiYWNrZ3JvdW5kU2VsZWN0ZWQiLCJhcHAiLCJwYW5lbHMiLCJpbml0IiwiYmxvY2tPcHRpb25zIiwiJHdpbmRvdyIsImVkdWNhdGlvbiIsImluaXREZWZhdWx0cyIsInJlZ2lzdGVyQmxvY2siLCJpbml0SkNvbmZpcm0iLCJyZWFkeSIsImV2ZW50cyIsIm9uIiwiXyIsImRlYm91bmNlIiwiYmxvY2tFZGl0IiwiZm9ybUxvYWRlZCIsImpjb25maXJtIiwiY2xvc2VJY29uIiwiYmFja2dyb3VuZERpc21pc3MiLCJlc2NhcGVLZXkiLCJhbmltYXRpb25Cb3VuY2UiLCJ1c2VCb290c3RyYXAiLCJib3hXaWR0aCIsImFuaW1hdGVGcm9tRWxlbWVudCIsImdldEZvcm1zIiwiX2NhbGxlZSIsIl9jYWxsZWUkIiwiX2NvbnRleHQiLCJhcGlGZXRjaCIsInBhdGgiLCJyb3V0ZV9uYW1lc3BhY2UiLCJjYWNoZSIsInQwIiwiY29uc29sZSIsImVycm9yIiwib3BlbkJ1aWxkZXJQb3B1cCIsImNsaWVudElEIiwiaXNFbXB0eU9iamVjdCIsInBhcmVudCIsImNhbnZhc0lmcmFtZSIsImlzRnNlTW9kZSIsIkJvb2xlYW4iLCJ0bXBsIiwiY29udGVudHMiLCJmaW5kIiwiYWZ0ZXIiLCJzaWJsaW5ncyIsInVybCIsImdldF9zdGFydGVkX3VybCIsIiRpZnJhbWUiLCJidWlsZGVyQ2xvc2VCdXR0b25FdmVudCIsImF0dHIiLCJmYWRlSW4iLCJvZmYiLCJhY3Rpb24iLCJmb3JtVGl0bGUiLCJuZXdCbG9jayIsImNyZWF0ZUJsb2NrIiwidG9TdHJpbmciLCJJRCIsInBvc3RfdGl0bGUiLCJkYXRhIiwiZGlzcGF0Y2giLCJyZW1vdmVCbG9jayIsImluc2VydEJsb2NrcyIsInRpdGxlIiwiZGVzY3JpcHRpb24iLCJpY29uIiwiZ2V0SWNvbiIsImtleXdvcmRzIiwiZm9ybV9rZXl3b3JkcyIsImNhdGVnb3J5IiwiYXR0cmlidXRlcyIsImdldEJsb2NrQXR0cmlidXRlcyIsInN1cHBvcnRzIiwiY3VzdG9tQ2xhc3NOYW1lIiwiaGFzRm9ybXMiLCJleGFtcGxlIiwiZWRpdCIsInByb3BzIiwiZm9ybU9wdGlvbnMiLCJnZXRGb3JtT3B0aW9ucyIsImhhbmRsZXJzIiwiZ2V0U2V0dGluZ3NGaWVsZHNIYW5kbGVycyIsIl91c2VTdGF0ZSIsIl91c2VTdGF0ZTIiLCJfc2xpY2VkVG9BcnJheSIsImlzTm90RGlzYWJsZWQiLCJfdXNlU3RhdGUzIiwiX3VzZVN0YXRlNCIsImlzUHJvRW5hYmxlZCIsIl91c2VTdGF0ZTUiLCJiYWNrZ3JvdW5kIiwiX3Nob3dCYWNrZ3JvdW5kUHJldmlldyIsIl91c2VTdGF0ZTYiLCJzaG93QmFja2dyb3VuZFByZXZpZXciLCJzZXRTaG93QmFja2dyb3VuZFByZXZpZXciLCJfdXNlU3RhdGU3IiwiX3VzZVN0YXRlOCIsImxhc3RCZ0ltYWdlIiwic2V0TGFzdEJnSW1hZ2UiLCJ1aVN0YXRlIiwiYmFja2dyb3VuZEltYWdlIiwiYmFja2dyb3VuZFVybCIsImJsb2NrUHJvcHMiLCJpc0NsaWVudElkQXR0clVuaXF1ZSIsInNldEF0dHJpYnV0ZXMiLCJqc3giLCJqc3hQYXJ0cyIsImdldE1haW5TZXR0aW5ncyIsImdldEVtcHR5Rm9ybXNQcmV2aWV3IiwiUmVhY3QiLCJzaXplT3B0aW9ucyIsImdldFNpemVPcHRpb25zIiwiaXNGb3JtQXZhaWxhYmxlIiwiZ2V0QmxvY2tQbGFjZWhvbGRlciIsIm1heWJlU3Vic2NyaWJlVG9CbG9ja0V2ZW50cyIsImdldFN0eWxlU2V0dGluZ3MiLCJnZXRCbG9ja0Zvcm1Db250ZW50IiwidXBkYXRlQ29weVBhc3RlQ29udGVudCIsInRyaWdnZXIiLCJnZXRCbG9ja1ByZXZpZXciLCJzYXZlIiwidW5kZWZpbmVkIiwiX29iamVjdFNwcmVhZCIsImdldENvbW1vbkF0dHJpYnV0ZXMiLCJzZXRTdHlsZXNIYW5kbGVycyIsImtleSIsIl9yZWYyIiwiTnVtYmVyIiwic2V0VHJpZ2dlclNlcnZlclJlbmRlciIsIiRmbGFnIiwic3Vic2NyaWJlclByb3BzIiwic3Vic2NyaWJlckhhbmRsZXJzIiwic3Vic2NyaWJlckJsb2NrT3B0aW9ucyIsImlkIiwic3Vic2NyaWJlckRlbGV0ZVRoZW1lIiwic3Vic2NyaWJlclVwZGF0ZVRoZW1lIiwic3Vic2NyaWJlclNldFRoZW1lIiwidGhlbWVTbHVnIiwidHJpZ2dlclByb3BzIiwiX3N1YnNjcmliZXJQcm9wcyRhdHRyIiwiX3N1YnNjcmliZXJCbG9ja09wdGlvIiwidGhlbWVzIiwic2V0QmxvY2tUaGVtZSIsInRoZW1lRGF0YSIsIl9zdWJzY3JpYmVyUHJvcHMkYXR0cjIiLCJfc3Vic2NyaWJlckJsb2NrT3B0aW8yIiwiYmxvY2siLCJfc3Vic2NyaWJlckJsb2NrT3B0aW8zIiwib25TZXRUaGVtZSIsInByaW50RW1wdHlGb3Jtc05vdGljZSIsImNsYXNzTmFtZSIsImZvcm1fc2V0dGluZ3MiLCJsYWJlbCIsImZvcm1fc2VsZWN0ZWQiLCJvcHRpb25zIiwib25DaGFuZ2UiLCJhdHRyQ2hhbmdlIiwiaHJlZiIsImZvcm1fdXJsIiwicmVwbGFjZSIsInJlbCIsInRhcmdldCIsImZvcm1fZWRpdCIsImVudHJpZXNfdXJsIiwiZm9ybV9lbnRyaWVzIiwic2hvd190aXRsZSIsImNoZWNrZWQiLCJzaG93X2Rlc2NyaXB0aW9uIiwicGFuZWxfbm90aWNlX2hlYWQiLCJwYW5lbF9ub3RpY2VfdGV4dCIsInBhbmVsX25vdGljZV9saW5rIiwicGFuZWxfbm90aWNlX2xpbmtfdGV4dCIsInN0eWxlIiwiZGlzcGxheSIsIm9uQ2xpY2siLCJnZXRMYWJlbFN0eWxlcyIsImdldFBhbmVsQ2xhc3MiLCJsYWJlbF9zdHlsZXMiLCJzaXplIiwic3R5bGVBdHRyQ2hhbmdlIiwiY29sb3JzIiwiX19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyIiwiZW5hYmxlQWxwaGEiLCJzaG93VGl0bGUiLCJjb2xvclNldHRpbmdzIiwic3VibGFiZWxfaGludHMiLCJlcnJvcl9tZXNzYWdlIiwiZ2V0UGFnZUluZGljYXRvclN0eWxlcyIsImhhc1BhZ2VCcmVhayIsImhhc1JhdGluZyIsImNvbmNhdCIsInBhZ2VfYnJlYWsiLCJyYXRpbmciLCJvdGhlcl9zdHlsZXMiLCJnZXRUaGVtZXNQYW5lbCIsInN0b2NrUGhvdG9zIiwiZ2V0RmllbGRTdHlsZXMiLCJnZXRCdXR0b25TdHlsZXMiLCJnZXRDb250YWluZXJTdHlsZXMiLCJnZXRCYWNrZ3JvdW5kU3R5bGVzIiwiZ2V0QmxvY2tDb250YWluZXIiLCJpbm5lckhUTUwiLCJibG9ja0hUTUwiLCJsb2FkZWRGb3JtSWQiLCJkYW5nZXJvdXNseVNldElubmVySFRNTCIsIl9faHRtbCIsInNyYyIsImJsb2NrX3ByZXZpZXdfdXJsIiwid2lkdGgiLCJhbHQiLCJibG9ja19lbXB0eV91cmwiLCJiIiwid3Bmb3Jtc19ndWlkZSIsImhlaWdodCIsImlzRm9ybU5vdEF2YWlsYWJsZSIsImxvZ29fdXJsIiwidGV4dEFsaWduIiwibWFyZ2luVG9wIiwiZm9ybV9ub3RfYXZhaWxhYmxlX21lc3NhZ2UiLCJfSlNPTiRwYXJzZSIsImN1cnJlbnRGb3JtIiwiZm9ybSIsInBhcnNlSW50IiwicG9zdF9jb250ZW50IiwiZmllbGRzIiwiSlNPTiIsInBhcnNlIiwic29tZSIsImZpZWxkIiwiX0pTT04kcGFyc2UyIiwicGFuZWwiLCJjc3NDbGFzcyIsImlzRnVsbFN0eWxpbmdFbmFibGVkIiwiZ2V0Q29sb3JQYW5lbENsYXNzIiwiYm9yZGVyU3R5bGUiLCJpc19tb2Rlcm5fbWFya3VwIiwiaXNfZnVsbF9zdHlsaW5nIiwiaXNMZWFkRm9ybXNFbmFibGVkIiwiJGZvcm0iLCJxdWVyeVNlbGVjdG9yIiwiaGFzQ2xhc3MiLCJibG9ja1NlbGVjdG9yIiwiZWRpdG9yQ2FudmFzIiwiY29udGVudFdpbmRvdyIsImdldEZvcm1CbG9jayIsInVwZGF0ZVByZXZpZXdDU1NWYXJWYWx1ZSIsImF0dHJpYnV0ZSIsImNvbnRhaW5lciIsInByb3BlcnR5IiwibGV0dGVyIiwidG9Mb3dlckNhc2UiLCJzZXRQcm9wZXJ0eSIsInRvZ2dsZUZpZWxkQm9yZGVyTm9uZUNTU1ZhclZhbHVlIiwibWF5YmVVcGRhdGVBY2NlbnRDb2xvciIsImJ1dHRvbkJvcmRlckNvbG9yIiwibWF5YmVTZXRCdXR0b25BbHRCYWNrZ3JvdW5kQ29sb3IiLCJtYXliZVNldEJ1dHRvbkFsdFRleHRDb2xvciIsImJ1dHRvblRleHRDb2xvciIsImJ1dHRvbkJhY2tncm91bmRDb2xvciIsInNldCIsImNvbnQiLCJXUEZvcm1zVXRpbHMiLCJjc3NDb2xvcnNVdGlscyIsImlzVHJhbnNwYXJlbnRDb2xvciIsImFsdENvbG9yIiwiZ2V0Q29udHJhc3RDb2xvciIsImNvbG9yIiwic2V0QXR0ciIsImluY2x1ZGVzIiwic2V0QmxvY2tSdW50aW1lU3RhdGVWYXIiLCJ1cGRhdGVDdXN0b21UaGVtZUF0dHJpYnV0ZSIsIm1heWJlVG9nZ2xlRHJvcGRvd24iLCJfdGhpcyIsIm1lbnUiLCJjbGFzc2ljTWVudSIsImNsYXNzTGlzdCIsImFkZCIsInBhcmVudEVsZW1lbnQiLCJzaG93Q2xhc3NpY01lbnUiLCJjbGVhclRpbWVvdXQiLCJzZXRUaW1lb3V0IiwidG9DbG9zZSIsInJlbW92ZSIsImhpZGVDbGFzc2ljTWVudSIsImNzc1RleHQiLCJxdWVyeVNlbGVjdG9yQWxsIiwib3B0aW9uIiwiY29udGVudCIsImF0dHMiLCJzZWxlY3QiLCJzdHJpbmdpZnkiLCJwYXN0ZVNldHRpbmdzIiwidHJpbSIsInBhc3RlQXR0cmlidXRlcyIsInBhcnNlVmFsaWRhdGVKc29uIiwiY3JlYXRlRXJyb3JOb3RpY2UiLCJjb3B5X3Bhc3RlX2Vycm9yIiwibWF5YmVDcmVhdGVDdXN0b21UaGVtZUZyb21BdHRyaWJ1dGVzIiwidmlld0JveCIsImZpbGwiLCJnZXRXUEZvcm1zQmxvY2tzIiwid3Bmb3Jtc0Jsb2NrcyIsImdldEJsb2NrcyIsImZpbHRlciIsImdldEJsb2NrUnVudGltZVN0YXRlVmFyIiwidmFyTmFtZSIsIl9ibG9ja3MkY2xpZW50SWQiLCJBcnJheSIsImlzQXJyYXkiLCJtYXAiLCJ1bnNoaWZ0IiwiZm9ybV9zZWxlY3QiLCJzbWFsbCIsIm1lZGl1bSIsImxhcmdlIiwiZGF0YXNldCIsImluaXRMZWFkRm9ybVNldHRpbmdzIiwiJHBhbmVsIiwiYWRkQ2xhc3MiLCJjc3MiLCJyZW1vdmVDbGFzcyIsImRldGFpbCIsInVwZGF0ZUFjY2VudENvbG9ycyIsImxvYWRDaG9pY2VzSlMiLCJpbml0UmljaFRleHRGaWVsZCIsImluaXRSZXBlYXRlckZpZWxkIiwiYmxvY2tDbGljayIsImN1cnJlbnRUYXJnZXQiLCJfd2luZG93JFdQRm9ybXMiLCJXUEZvcm1zIiwiRnJvbnRlbmRNb2Rlcm4iLCJ1cGRhdGVHQkJsb2NrUGFnZUluZGljYXRvckNvbG9yIiwidXBkYXRlR0JCbG9ja0ljb25DaG9pY2VzQ29sb3IiLCJ1cGRhdGVHQkJsb2NrUmF0aW5nQ29sb3IiLCJDaG9pY2VzIiwiZWFjaCIsImlkeCIsInNlbGVjdEVsIiwiJGVsIiwiYXJncyIsIndwZm9ybXNfY2hvaWNlc2pzX2NvbmZpZyIsInNlYXJjaEVuYWJsZWQiLCIkZmllbGQiLCJjbG9zZXN0IiwiY2FsbGJhY2tPbkluaXQiLCJzZWxmIiwiJGVsZW1lbnQiLCJwYXNzZWRFbGVtZW50IiwiJGlucHV0IiwiaW5wdXQiLCJzaXplQ2xhc3MiLCJjb250YWluZXJPdXRlciIsInByb3AiLCJnZXRWYWx1ZSIsImhpZGUiLCJkaXNhYmxlIiwiSFRNTFNlbGVjdEVsZW1lbnQiLCIkcm93QnV0dG9ucyIsIiRjb250IiwiJGxhYmVscyIsIiRsYWJlbCIsImZpcnN0IiwibGFiZWxTdHlsZSIsImdldENvbXB1dGVkU3R5bGUiLCJnZXQiLCJtYXJnaW4iLCJnZXRQcm9wZXJ0eVZhbHVlIiwib3V0ZXJIZWlnaHQiLCJ0b3AiLCIkcmVwZWF0ZXIiLCJqUXVlcnkiXSwic291cmNlcyI6WyJjb21tb24uanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIGpjb25maXJtLCB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLCBDaG9pY2VzLCBKU1gsIERPTSwgV1BGb3Jtc1V0aWxzICovXG4vKiBqc2hpbnQgZXMzOiBmYWxzZSwgZXN2ZXJzaW9uOiA2ICovXG5cbi8qKlxuICogQHBhcmFtIHN0cmluZ3MuY29weV9wYXN0ZV9lcnJvclxuICogQHBhcmFtIHN0cmluZ3MuZXJyb3JfbWVzc2FnZVxuICogQHBhcmFtIHN0cmluZ3MuZm9ybV9lZGl0XG4gKiBAcGFyYW0gc3RyaW5ncy5mb3JtX2VudHJpZXNcbiAqIEBwYXJhbSBzdHJpbmdzLmZvcm1fa2V5d29yZHNcbiAqIEBwYXJhbSBzdHJpbmdzLmZvcm1fc2VsZWN0XG4gKiBAcGFyYW0gc3RyaW5ncy5mb3JtX3NlbGVjdGVkXG4gKiBAcGFyYW0gc3RyaW5ncy5mb3JtX3NldHRpbmdzXG4gKiBAcGFyYW0gc3RyaW5ncy5sYWJlbF9zdHlsZXNcbiAqIEBwYXJhbSBzdHJpbmdzLm90aGVyX3N0eWxlc1xuICogQHBhcmFtIHN0cmluZ3MucGFnZV9icmVha1xuICogQHBhcmFtIHN0cmluZ3MucGFuZWxfbm90aWNlX2hlYWRcbiAqIEBwYXJhbSBzdHJpbmdzLnBhbmVsX25vdGljZV9saW5rXG4gKiBAcGFyYW0gc3RyaW5ncy5wYW5lbF9ub3RpY2VfbGlua190ZXh0XG4gKiBAcGFyYW0gc3RyaW5ncy5wYW5lbF9ub3RpY2VfdGV4dFxuICogQHBhcmFtIHN0cmluZ3Muc2hvd19kZXNjcmlwdGlvblxuICogQHBhcmFtIHN0cmluZ3Muc2hvd190aXRsZVxuICogQHBhcmFtIHN0cmluZ3Muc3VibGFiZWxfaGludHNcbiAqIEBwYXJhbSBzdHJpbmdzLmZvcm1fbm90X2F2YWlsYWJsZV9tZXNzYWdlXG4gKiBAcGFyYW0gdXJscy5lbnRyaWVzX3VybFxuICogQHBhcmFtIHVybHMuZm9ybV91cmxcbiAqIEBwYXJhbSB3aW5kb3cud3Bmb3Jtc19jaG9pY2VzanNfY29uZmlnXG4gKiBAcGFyYW0gd3Bmb3Jtc19lZHVjYXRpb24udXBncmFkZV9ib251c1xuICogQHBhcmFtIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuYmxvY2tfZW1wdHlfdXJsXG4gKiBAcGFyYW0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5ibG9ja19wcmV2aWV3X3VybFxuICogQHBhcmFtIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuZ2V0X3N0YXJ0ZWRfdXJsXG4gKiBAcGFyYW0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5pc19mdWxsX3N0eWxpbmdcbiAqIEBwYXJhbSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmlzX21vZGVybl9tYXJrdXBcbiAqIEBwYXJhbSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmxvZ29fdXJsXG4gKiBAcGFyYW0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci53cGZvcm1zX2d1aWRlXG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIENvbW1vbiBtb2R1bGUuXG4gKlxuICogQHNpbmNlIDEuOC44XG4gKi9cbmV4cG9ydCBkZWZhdWx0ICggZnVuY3Rpb24oIGRvY3VtZW50LCB3aW5kb3csICQgKSB7XG5cdC8qKlxuXHQgKiBXUCBjb3JlIGNvbXBvbmVudHMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKi9cblx0Y29uc3QgeyBzZXJ2ZXJTaWRlUmVuZGVyOiBTZXJ2ZXJTaWRlUmVuZGVyID0gd3AuY29tcG9uZW50cy5TZXJ2ZXJTaWRlUmVuZGVyIH0gPSB3cDtcblx0Y29uc3QgeyBjcmVhdGVFbGVtZW50LCBGcmFnbWVudCwgY3JlYXRlSW50ZXJwb2xhdGVFbGVtZW50IH0gPSB3cC5lbGVtZW50O1xuXHRjb25zdCB7IHJlZ2lzdGVyQmxvY2tUeXBlIH0gPSB3cC5ibG9ja3M7XG5cdGNvbnN0IHsgSW5zcGVjdG9yQ29udHJvbHMsIFBhbmVsQ29sb3JTZXR0aW5ncywgdXNlQmxvY2tQcm9wcyB9ID0gd3AuYmxvY2tFZGl0b3IgfHwgd3AuZWRpdG9yO1xuXHRjb25zdCB7IFNlbGVjdENvbnRyb2wsIFRvZ2dsZUNvbnRyb2wsIFBhbmVsQm9keSwgUGxhY2Vob2xkZXIgfSA9IHdwLmNvbXBvbmVudHM7XG5cdGNvbnN0IHsgX18gfSA9IHdwLmkxOG47XG5cdGNvbnN0IHsgdXNlU3RhdGUsIHVzZUVmZmVjdCB9ID0gd3AuZWxlbWVudDtcblxuXHQvKipcblx0ICogTG9jYWxpemVkIGRhdGEgYWxpYXNlcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCB7IHN0cmluZ3MsIGRlZmF1bHRzLCBzaXplcywgdXJscywgaXNQcm8sIGlzTGljZW5zZUFjdGl2ZSwgaXNBZG1pbiB9ID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvcjtcblx0Y29uc3QgZGVmYXVsdFN0eWxlU2V0dGluZ3MgPSBkZWZhdWx0cztcblxuXHQvLyBub2luc3BlY3Rpb24gSlNVbnVzZWRMb2NhbFN5bWJvbHNcblx0LyoqXG5cdCAqIFdQRm9ybXMgRWR1Y2F0aW9uIHNjcmlwdC5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCBXUEZvcm1zRWR1Y2F0aW9uID0gd2luZG93LldQRm9ybXNFZHVjYXRpb24gfHwge307IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbm8tdW51c2VkLXZhcnNcblxuXHQvKipcblx0ICogTGlzdCBvZiBmb3Jtcy5cblx0ICpcblx0ICogVGhlIGRlZmF1bHQgdmFsdWUgaXMgbG9jYWxpemVkIGluIEZvcm1TZWxlY3Rvci5waHAuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguNFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0bGV0IGZvcm1MaXN0ID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5mb3JtcztcblxuXHQvKipcblx0ICogQmxvY2tzIHJ1bnRpbWUgZGF0YS5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBibG9ja3MgPSB7fTtcblxuXHQvKipcblx0ICogV2hldGhlciBpdCBpcyBuZWVkZWQgdG8gdHJpZ2dlciBzZXJ2ZXIgcmVuZGVyaW5nLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICpcblx0ICogQHR5cGUge2Jvb2xlYW59XG5cdCAqL1xuXHRsZXQgdHJpZ2dlclNlcnZlclJlbmRlciA9IHRydWU7XG5cblx0LyoqXG5cdCAqIFBvcHVwIGNvbnRhaW5lci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4zXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRsZXQgJHBvcHVwID0ge307XG5cblx0LyoqXG5cdCAqIFRyYWNrIGZldGNoIHN0YXR1cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC40XG5cdCAqXG5cdCAqIEB0eXBlIHtib29sZWFufVxuXHQgKi9cblx0bGV0IGlzRmV0Y2hpbmcgPSBmYWxzZTtcblxuXHQvKipcblx0ICogRWxlbWVudHMgaG9sZGVyLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGNvbnN0IGVsID0ge307XG5cblx0LyoqXG5cdCAqIENvbW1vbiBibG9jayBhdHRyaWJ1dGVzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGxldCBjb21tb25BdHRyaWJ1dGVzID0ge1xuXHRcdGNsaWVudElkOiB7XG5cdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdGRlZmF1bHQ6ICcnLFxuXHRcdH0sXG5cdFx0Zm9ybUlkOiB7XG5cdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRTdHlsZVNldHRpbmdzLmZvcm1JZCxcblx0XHR9LFxuXHRcdGRpc3BsYXlUaXRsZToge1xuXHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MuZGlzcGxheVRpdGxlLFxuXHRcdH0sXG5cdFx0ZGlzcGxheURlc2M6IHtcblx0XHRcdHR5cGU6ICdib29sZWFuJyxcblx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRTdHlsZVNldHRpbmdzLmRpc3BsYXlEZXNjLFxuXHRcdH0sXG5cdFx0cHJldmlldzoge1xuXHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdH0sXG5cdFx0dGhlbWU6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MudGhlbWUsXG5cdFx0fSxcblx0XHR0aGVtZU5hbWU6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MudGhlbWVOYW1lLFxuXHRcdH0sXG5cdFx0bGFiZWxTaXplOiB7XG5cdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRTdHlsZVNldHRpbmdzLmxhYmVsU2l6ZSxcblx0XHR9LFxuXHRcdGxhYmVsQ29sb3I6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MubGFiZWxDb2xvcixcblx0XHR9LFxuXHRcdGxhYmVsU3VibGFiZWxDb2xvcjoge1xuXHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRkZWZhdWx0OiBkZWZhdWx0U3R5bGVTZXR0aW5ncy5sYWJlbFN1YmxhYmVsQ29sb3IsXG5cdFx0fSxcblx0XHRsYWJlbEVycm9yQ29sb3I6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MubGFiZWxFcnJvckNvbG9yLFxuXHRcdH0sXG5cdFx0cGFnZUJyZWFrQ29sb3I6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MucGFnZUJyZWFrQ29sb3IsXG5cdFx0fSxcblx0XHRjdXN0b21Dc3M6IHtcblx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0ZGVmYXVsdDogZGVmYXVsdFN0eWxlU2V0dGluZ3MuY3VzdG9tQ3NzLFxuXHRcdH0sXG5cdFx0Y29weVBhc3RlSnNvblZhbHVlOiB7XG5cdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRTdHlsZVNldHRpbmdzLmNvcHlQYXN0ZUpzb25WYWx1ZSxcblx0XHR9LFxuXHR9O1xuXG5cdC8qKlxuXHQgKiBIYW5kbGVycyBmb3IgY3VzdG9tIHN0eWxlcyBzZXR0aW5ncywgZGVmaW5lZCBvdXRzaWRlIHRoaXMgbW9kdWxlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGxldCBjdXN0b21TdHlsZXNIYW5kbGVycyA9IHt9O1xuXG5cdC8qKlxuXHQgKiBEcm9wZG93biB0aW1lb3V0LlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge251bWJlcn1cblx0ICovXG5cdGxldCBkcm9wZG93blRpbWVvdXQ7XG5cblx0LyoqXG5cdCAqIFdoZXRoZXIgY29weS1wYXN0ZSBjb250ZW50IHdhcyBnZW5lcmF0ZWQgb24gZWRpdC5cblx0ICpcblx0ICogQHNpbmNlIDEuOS4xXG5cdCAqXG5cdCAqIEB0eXBlIHtib29sZWFufVxuXHQgKi9cblx0bGV0IGlzQ29weVBhc3RlR2VuZXJhdGVkT25FZGl0ID0gZmFsc2U7XG5cblx0LyoqXG5cdCAqIFdoZXRoZXIgdGhlIGJhY2tncm91bmQgaXMgc2VsZWN0ZWQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjkuM1xuXHQgKlxuXHQgKiBAdHlwZSB7Ym9vbGVhbn1cblx0ICovXG5cdGxldCBiYWNrZ3JvdW5kU2VsZWN0ZWQgPSBmYWxzZTtcblxuXHQvKipcblx0ICogUHVibGljIGZ1bmN0aW9ucyBhbmQgcHJvcGVydGllcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBhcHAgPSB7XG5cblx0XHQvKipcblx0XHQgKiBQYW5lbCBtb2R1bGVzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7T2JqZWN0fVxuXHRcdCAqL1xuXHRcdHBhbmVsczoge30sXG5cblx0XHQvKipcblx0XHQgKiBTdGFydCB0aGUgZW5naW5lLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gYmxvY2tPcHRpb25zIEJsb2NrIG9wdGlvbnMuXG5cdFx0ICovXG5cdFx0aW5pdCggYmxvY2tPcHRpb25zICkge1xuXHRcdFx0ZWwuJHdpbmRvdyA9ICQoIHdpbmRvdyApO1xuXHRcdFx0YXBwLnBhbmVscyA9IGJsb2NrT3B0aW9ucy5wYW5lbHM7XG5cdFx0XHRhcHAuZWR1Y2F0aW9uID0gYmxvY2tPcHRpb25zLmVkdWNhdGlvbjtcblxuXHRcdFx0YXBwLmluaXREZWZhdWx0cyggYmxvY2tPcHRpb25zICk7XG5cdFx0XHRhcHAucmVnaXN0ZXJCbG9jayggYmxvY2tPcHRpb25zICk7XG5cblx0XHRcdGFwcC5pbml0SkNvbmZpcm0oKTtcblxuXHRcdFx0JCggYXBwLnJlYWR5ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERvY3VtZW50IHJlYWR5LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0cmVhZHkoKSB7XG5cdFx0XHRhcHAuZXZlbnRzKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEV2ZW50cy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdGV2ZW50cygpIHtcblx0XHRcdGVsLiR3aW5kb3dcblx0XHRcdFx0Lm9uKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvckVkaXQnLCBfLmRlYm91bmNlKCBhcHAuYmxvY2tFZGl0LCAyNTAgKSApXG5cdFx0XHRcdC5vbiggJ3dwZm9ybXNGb3JtU2VsZWN0b3JGb3JtTG9hZGVkJywgYXBwLmZvcm1Mb2FkZWQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBqQ29uZmlybS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGluaXRKQ29uZmlybSgpIHtcblx0XHRcdC8vIGpxdWVyeS1jb25maXJtIGRlZmF1bHRzLlxuXHRcdFx0amNvbmZpcm0uZGVmYXVsdHMgPSB7XG5cdFx0XHRcdGNsb3NlSWNvbjogZmFsc2UsXG5cdFx0XHRcdGJhY2tncm91bmREaXNtaXNzOiBmYWxzZSxcblx0XHRcdFx0ZXNjYXBlS2V5OiB0cnVlLFxuXHRcdFx0XHRhbmltYXRpb25Cb3VuY2U6IDEsXG5cdFx0XHRcdHVzZUJvb3RzdHJhcDogZmFsc2UsXG5cdFx0XHRcdHRoZW1lOiAnbW9kZXJuJyxcblx0XHRcdFx0Ym94V2lkdGg6ICc0MDBweCcsXG5cdFx0XHRcdGFuaW1hdGVGcm9tRWxlbWVudDogZmFsc2UsXG5cdFx0XHR9O1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgYSBmcmVzaCBsaXN0IG9mIGZvcm1zIHZpYSBSRVNULUFQSS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguNFxuXHRcdCAqXG5cdFx0ICogQHNlZSBodHRwczovL2RldmVsb3Blci53b3JkcHJlc3Mub3JnL2Jsb2NrLWVkaXRvci9yZWZlcmVuY2UtZ3VpZGVzL3BhY2thZ2VzL3BhY2thZ2VzLWFwaS1mZXRjaC9cblx0XHQgKi9cblx0XHRhc3luYyBnZXRGb3JtcygpIHtcblx0XHRcdC8vIElmIGEgZmV0Y2ggaXMgYWxyZWFkeSBpbiBwcm9ncmVzcywgZXhpdCB0aGUgZnVuY3Rpb24uXG5cdFx0XHRpZiAoIGlzRmV0Y2hpbmcgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Ly8gU2V0IHRoZSBmbGFnIHRvIHRydWUgaW5kaWNhdGluZyBhIGZldGNoIGlzIGluIHByb2dyZXNzLlxuXHRcdFx0aXNGZXRjaGluZyA9IHRydWU7XG5cblx0XHRcdHRyeSB7XG5cdFx0XHRcdC8vIEZldGNoIGZvcm1zLlxuXHRcdFx0XHRmb3JtTGlzdCA9IGF3YWl0IHdwLmFwaUZldGNoKCB7XG5cdFx0XHRcdFx0cGF0aDogd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5yb3V0ZV9uYW1lc3BhY2UgKyAnZm9ybXMvJyxcblx0XHRcdFx0XHRtZXRob2Q6ICdHRVQnLFxuXHRcdFx0XHRcdGNhY2hlOiAnbm8tY2FjaGUnLFxuXHRcdFx0XHR9ICk7XG5cdFx0XHR9IGNhdGNoICggZXJyb3IgKSB7XG5cdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25zb2xlXG5cdFx0XHRcdGNvbnNvbGUuZXJyb3IoIGVycm9yICk7XG5cdFx0XHR9IGZpbmFsbHkge1xuXHRcdFx0XHRpc0ZldGNoaW5nID0gZmFsc2U7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE9wZW4gYnVpbGRlciBwb3B1cC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjYuMlxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElEIEJsb2NrIENsaWVudCBJRC5cblx0XHQgKi9cblx0XHRvcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJRCApIHtcblx0XHRcdGlmICggJC5pc0VtcHR5T2JqZWN0KCAkcG9wdXAgKSApIHtcblx0XHRcdFx0Y29uc3QgcGFyZW50ID0gJCggJyN3cHdyYXAnICk7XG5cdFx0XHRcdGNvbnN0IGNhbnZhc0lmcmFtZSA9ICQoICdpZnJhbWVbbmFtZT1cImVkaXRvci1jYW52YXNcIl0nICk7XG5cdFx0XHRcdGNvbnN0IGlzRnNlTW9kZSA9IEJvb2xlYW4oIGNhbnZhc0lmcmFtZS5sZW5ndGggKTtcblx0XHRcdFx0Y29uc3QgdG1wbCA9IGlzRnNlTW9kZSA/IGNhbnZhc0lmcmFtZS5jb250ZW50cygpLmZpbmQoICcjd3Bmb3Jtcy1ndXRlbmJlcmctcG9wdXAnICkgOiAkKCAnI3dwZm9ybXMtZ3V0ZW5iZXJnLXBvcHVwJyApO1xuXG5cdFx0XHRcdHBhcmVudC5hZnRlciggdG1wbCApO1xuXG5cdFx0XHRcdCRwb3B1cCA9IHBhcmVudC5zaWJsaW5ncyggJyN3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cCcgKTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgdXJsID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5nZXRfc3RhcnRlZF91cmwsXG5cdFx0XHRcdCRpZnJhbWUgPSAkcG9wdXAuZmluZCggJ2lmcmFtZScgKTtcblxuXHRcdFx0YXBwLmJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApO1xuXHRcdFx0JGlmcmFtZS5hdHRyKCAnc3JjJywgdXJsICk7XG5cdFx0XHQkcG9wdXAuZmFkZUluKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENsb3NlIGJ1dHRvbiAoaW5zaWRlIHRoZSBmb3JtIGJ1aWxkZXIpIGNsaWNrIGV2ZW50LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4zXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gY2xpZW50SUQgQmxvY2sgQ2xpZW50IElELlxuXHRcdCAqL1xuXHRcdGJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApIHtcblx0XHRcdCRwb3B1cFxuXHRcdFx0XHQub2ZmKCAnd3Bmb3Jtc0J1aWxkZXJJblBvcHVwQ2xvc2UnIClcblx0XHRcdFx0Lm9uKCAnd3Bmb3Jtc0J1aWxkZXJJblBvcHVwQ2xvc2UnLCBmdW5jdGlvbiggZSwgYWN0aW9uLCBmb3JtSWQsIGZvcm1UaXRsZSApIHtcblx0XHRcdFx0XHRpZiAoIGFjdGlvbiAhPT0gJ3NhdmVkJyB8fCAhIGZvcm1JZCApIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2sgd2hlbiBhIG5ldyBmb3JtIGlzIGNyZWF0ZWQgZnJvbSB0aGUgcG9wdXAgdG8gdXBkYXRlIHRoZSBmb3JtIGxpc3QgYW5kIGF0dHJpYnV0ZXMuXG5cdFx0XHRcdFx0Y29uc3QgbmV3QmxvY2sgPSB3cC5ibG9ja3MuY3JlYXRlQmxvY2soICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdFx0XHRmb3JtSWQ6IGZvcm1JZC50b1N0cmluZygpLCAvLyBFeHBlY3RzIHN0cmluZyB2YWx1ZSwgbWFrZSBzdXJlIHdlIGluc2VydCBzdHJpbmcuXG5cdFx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGNhbWVsY2FzZVxuXHRcdFx0XHRcdGZvcm1MaXN0ID0gWyB7IElEOiBmb3JtSWQsIHBvc3RfdGl0bGU6IGZvcm1UaXRsZSB9IF07XG5cblx0XHRcdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2suXG5cdFx0XHRcdFx0d3AuZGF0YS5kaXNwYXRjaCggJ2NvcmUvYmxvY2stZWRpdG9yJyApLnJlbW92ZUJsb2NrKCBjbGllbnRJRCApO1xuXHRcdFx0XHRcdHdwLmRhdGEuZGlzcGF0Y2goICdjb3JlL2Jsb2NrLWVkaXRvcicgKS5pbnNlcnRCbG9ja3MoIG5ld0Jsb2NrICk7XG5cdFx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUmVnaXN0ZXIgYmxvY2suXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBibG9ja09wdGlvbnMgQWRkaXRpb25hbCBibG9jayBvcHRpb25zLlxuXHRcdCAqL1xuXHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uXG5cdFx0cmVnaXN0ZXJCbG9jayggYmxvY2tPcHRpb25zICkge1xuXHRcdFx0cmVnaXN0ZXJCbG9ja1R5cGUoICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdHRpdGxlOiBzdHJpbmdzLnRpdGxlLFxuXHRcdFx0XHRkZXNjcmlwdGlvbjogc3RyaW5ncy5kZXNjcmlwdGlvbixcblx0XHRcdFx0aWNvbjogYXBwLmdldEljb24oKSxcblx0XHRcdFx0a2V5d29yZHM6IHN0cmluZ3MuZm9ybV9rZXl3b3Jkcyxcblx0XHRcdFx0Y2F0ZWdvcnk6ICd3aWRnZXRzJyxcblx0XHRcdFx0YXR0cmlidXRlczogYXBwLmdldEJsb2NrQXR0cmlidXRlcygpLFxuXHRcdFx0XHRzdXBwb3J0czoge1xuXHRcdFx0XHRcdGN1c3RvbUNsYXNzTmFtZTogYXBwLmhhc0Zvcm1zKCksXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGV4YW1wbGU6IHtcblx0XHRcdFx0XHRhdHRyaWJ1dGVzOiB7XG5cdFx0XHRcdFx0XHRwcmV2aWV3OiB0cnVlLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdH0sXG5cdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uLGNvbXBsZXhpdHlcblx0XHRcdFx0ZWRpdCggcHJvcHMgKSB7XG5cdFx0XHRcdFx0Y29uc3QgeyBhdHRyaWJ1dGVzIH0gPSBwcm9wcztcblx0XHRcdFx0XHRjb25zdCBmb3JtT3B0aW9ucyA9IGFwcC5nZXRGb3JtT3B0aW9ucygpO1xuXHRcdFx0XHRcdGNvbnN0IGhhbmRsZXJzID0gYXBwLmdldFNldHRpbmdzRmllbGRzSGFuZGxlcnMoIHByb3BzICk7XG5cblx0XHRcdFx0XHRjb25zdCBbIGlzTm90RGlzYWJsZWQgXSA9IHVzZVN0YXRlKCBpc1BybyAmJiBpc0xpY2Vuc2VBY3RpdmUgKTsgLy8gZXNsaW50LWRpc2FibGUtbGluZSByZWFjdC1ob29rcy9ydWxlcy1vZi1ob29rc1xuXHRcdFx0XHRcdGNvbnN0IFsgaXNQcm9FbmFibGVkIF0gPSB1c2VTdGF0ZSggaXNQcm8gKTsgLy8gZXNsaW50LWRpc2FibGUtbGluZSByZWFjdC1ob29rcy9ydWxlcy1vZi1ob29rcywgbm8tdW51c2VkLXZhcnNcblx0XHRcdFx0XHRjb25zdCBbIHNob3dCYWNrZ3JvdW5kUHJldmlldywgc2V0U2hvd0JhY2tncm91bmRQcmV2aWV3IF0gPSB1c2VTdGF0ZSggYmxvY2tPcHRpb25zLnBhbmVscy5iYWNrZ3JvdW5kLl9zaG93QmFja2dyb3VuZFByZXZpZXcoIHByb3BzICkgKTsgLy8gZXNsaW50LWRpc2FibGUtbGluZSByZWFjdC1ob29rcy9ydWxlcy1vZi1ob29rc1xuXHRcdFx0XHRcdGNvbnN0IFsgbGFzdEJnSW1hZ2UsIHNldExhc3RCZ0ltYWdlIF0gPSB1c2VTdGF0ZSggJycgKTsgLy8gZXNsaW50LWRpc2FibGUtbGluZSByZWFjdC1ob29rcy9ydWxlcy1vZi1ob29rc1xuXG5cdFx0XHRcdFx0Y29uc3QgdWlTdGF0ZSA9IHtcblx0XHRcdFx0XHRcdGlzTm90RGlzYWJsZWQsXG5cdFx0XHRcdFx0XHRpc1Byb0VuYWJsZWQsXG5cdFx0XHRcdFx0XHRzaG93QmFja2dyb3VuZFByZXZpZXcsXG5cdFx0XHRcdFx0XHRzZXRTaG93QmFja2dyb3VuZFByZXZpZXcsXG5cdFx0XHRcdFx0XHRsYXN0QmdJbWFnZSxcblx0XHRcdFx0XHRcdHNldExhc3RCZ0ltYWdlLFxuXHRcdFx0XHRcdH07XG5cblx0XHRcdFx0XHR1c2VFZmZlY3QoICgpID0+IHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSByZWFjdC1ob29rcy9ydWxlcy1vZi1ob29rc1xuXHRcdFx0XHRcdFx0aWYgKCBhdHRyaWJ1dGVzLmZvcm1JZCApIHtcblx0XHRcdFx0XHRcdFx0c2V0U2hvd0JhY2tncm91bmRQcmV2aWV3KFxuXHRcdFx0XHRcdFx0XHRcdHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZEltYWdlICE9PSAnbm9uZScgJiZcblx0XHRcdFx0XHRcdFx0XHRwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRVcmwgJiZcblx0XHRcdFx0XHRcdFx0XHRwcm9wcy5hdHRyaWJ1dGVzLmJhY2tncm91bmRVcmwgIT09ICd1cmwoKSdcblx0XHRcdFx0XHRcdFx0KTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9LCBbIGJhY2tncm91bmRTZWxlY3RlZCwgcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UsIHByb3BzLmF0dHJpYnV0ZXMuYmFja2dyb3VuZFVybCBdICk7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgcmVhY3QtaG9va3MvZXhoYXVzdGl2ZS1kZXBzXG5cblx0XHRcdFx0XHQvLyBHZXQgYmxvY2sgcHJvcGVydGllcy5cblx0XHRcdFx0XHRjb25zdCBibG9ja1Byb3BzID0gdXNlQmxvY2tQcm9wcygpOyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIHJlYWN0LWhvb2tzL3J1bGVzLW9mLWhvb2tzLCBuby11bnVzZWQtdmFyc1xuXG5cdFx0XHRcdFx0Ly8gU3RvcmUgYmxvY2sgY2xpZW50SWQgaW4gYXR0cmlidXRlcy5cblx0XHRcdFx0XHRpZiAoICEgYXR0cmlidXRlcy5jbGllbnRJZCB8fCAhIGFwcC5pc0NsaWVudElkQXR0clVuaXF1ZSggcHJvcHMgKSApIHtcblx0XHRcdFx0XHRcdC8vIFdlIGp1c3Qgd2FudCB0aGUgY2xpZW50IElEIHRvIHVwZGF0ZSBvbmNlLlxuXHRcdFx0XHRcdFx0Ly8gVGhlIGJsb2NrIGVkaXRvciBkb2Vzbid0IGhhdmUgYSBmaXhlZCBibG9jayBJRCwgc28gd2UgbmVlZCB0byBnZXQgaXQgb24gdGhlIGluaXRpYWwgbG9hZCwgYnV0IG9ubHkgb25jZS5cblx0XHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHsgY2xpZW50SWQ6IHByb3BzLmNsaWVudElkIH0gKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBNYWluIGJsb2NrIHNldHRpbmdzLlxuXHRcdFx0XHRcdGNvbnN0IGpzeCA9IFtcblx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRNYWluU2V0dGluZ3MoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApLFxuXHRcdFx0XHRcdF07XG5cblx0XHRcdFx0XHQvLyBCbG9jayBwcmV2aWV3IHBpY3R1cmUuXG5cdFx0XHRcdFx0aWYgKCAhIGFwcC5oYXNGb3JtcygpICkge1xuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiA8ZGl2IHsgLi4uYmxvY2tQcm9wcyB9PnsganN4IH08L2Rpdj47XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Y29uc3Qgc2l6ZU9wdGlvbnMgPSBhcHAuZ2V0U2l6ZU9wdGlvbnMoKTtcblxuXHRcdFx0XHRcdC8vIFNob3cgcGxhY2Vob2xkZXIgd2hlbiBmb3JtIGlzIG5vdCBhdmFpbGFibGUgKHRyYXNoZWQsIGRlbGV0ZWQgZXRjLikuXG5cdFx0XHRcdFx0aWYgKCBhdHRyaWJ1dGVzICYmIGF0dHJpYnV0ZXMuZm9ybUlkICYmIGFwcC5pc0Zvcm1BdmFpbGFibGUoIGF0dHJpYnV0ZXMuZm9ybUlkICkgPT09IGZhbHNlICkge1xuXHRcdFx0XHRcdFx0Ly8gQmxvY2sgcGxhY2Vob2xkZXIgKGZvcm0gc2VsZWN0b3IpLlxuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRCbG9ja1BsYWNlaG9sZGVyKCBwcm9wcy5hdHRyaWJ1dGVzLCBoYW5kbGVycywgZm9ybU9wdGlvbnMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiA8ZGl2IHsgLi4uYmxvY2tQcm9wcyB9PnsganN4IH08L2Rpdj47XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gRm9ybSBzdHlsZSBzZXR0aW5ncyAmIGJsb2NrIGNvbnRlbnQuXG5cdFx0XHRcdFx0aWYgKCBhdHRyaWJ1dGVzLmZvcm1JZCApIHtcblx0XHRcdFx0XHRcdC8vIFN1YnNjcmliZSB0byBibG9jayBldmVudHMuXG5cdFx0XHRcdFx0XHRhcHAubWF5YmVTdWJzY3JpYmVUb0Jsb2NrRXZlbnRzKCBwcm9wcywgaGFuZGxlcnMsIGJsb2NrT3B0aW9ucyApO1xuXG5cdFx0XHRcdFx0XHRqc3gucHVzaChcblx0XHRcdFx0XHRcdFx0YXBwLmpzeFBhcnRzLmdldFN0eWxlU2V0dGluZ3MoIHByb3BzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMsIGJsb2NrT3B0aW9ucywgdWlTdGF0ZSApLFxuXHRcdFx0XHRcdFx0XHRhcHAuanN4UGFydHMuZ2V0QmxvY2tGb3JtQ29udGVudCggcHJvcHMgKVxuXHRcdFx0XHRcdFx0KTtcblxuXHRcdFx0XHRcdFx0aWYgKCAhIGlzQ29weVBhc3RlR2VuZXJhdGVkT25FZGl0ICkge1xuXHRcdFx0XHRcdFx0XHRoYW5kbGVycy51cGRhdGVDb3B5UGFzdGVDb250ZW50KCk7XG5cblx0XHRcdFx0XHRcdFx0aXNDb3B5UGFzdGVHZW5lcmF0ZWRPbkVkaXQgPSB0cnVlO1xuXHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRlbC4kd2luZG93LnRyaWdnZXIoICd3cGZvcm1zRm9ybVNlbGVjdG9yRWRpdCcsIFsgcHJvcHMgXSApO1xuXG5cdFx0XHRcdFx0XHRyZXR1cm4gPGRpdiB7IC4uLmJsb2NrUHJvcHMgfT57IGpzeCB9PC9kaXY+O1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdC8vIEJsb2NrIHByZXZpZXcgcGljdHVyZS5cblx0XHRcdFx0XHRpZiAoIGF0dHJpYnV0ZXMucHJldmlldyApIHtcblx0XHRcdFx0XHRcdGpzeC5wdXNoKFxuXHRcdFx0XHRcdFx0XHRhcHAuanN4UGFydHMuZ2V0QmxvY2tQcmV2aWV3KCksXG5cdFx0XHRcdFx0XHQpO1xuXG5cdFx0XHRcdFx0XHRyZXR1cm4gPGRpdiB7IC4uLmJsb2NrUHJvcHMgfT57IGpzeCB9PC9kaXY+O1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdC8vIEJsb2NrIHBsYWNlaG9sZGVyIChmb3JtIHNlbGVjdG9yKS5cblx0XHRcdFx0XHRqc3gucHVzaChcblx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRCbG9ja1BsYWNlaG9sZGVyKCBwcm9wcy5hdHRyaWJ1dGVzLCBoYW5kbGVycywgZm9ybU9wdGlvbnMgKSxcblx0XHRcdFx0XHQpO1xuXG5cdFx0XHRcdFx0cmV0dXJuIDxkaXYgeyAuLi5ibG9ja1Byb3BzIH0+eyBqc3ggfTwvZGl2Pjtcblx0XHRcdFx0fSxcblx0XHRcdFx0c2F2ZTogKCkgPT4gbnVsbCxcblx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBkZWZhdWx0IHN0eWxlIHNldHRpbmdzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICogQHNpbmNlIDEuOC44IEFkZGVkIGJsb2NrT3B0aW9ucyBwYXJhbWV0ZXIuXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gYmxvY2tPcHRpb25zIEFkZGl0aW9uYWwgYmxvY2sgb3B0aW9ucy5cblx0XHQgKi9cblx0XHRpbml0RGVmYXVsdHMoIGJsb2NrT3B0aW9ucyA9IHt9ICkge1xuXHRcdFx0Y29tbW9uQXR0cmlidXRlcyA9IHtcblx0XHRcdFx0Li4uY29tbW9uQXR0cmlidXRlcyxcblx0XHRcdFx0Li4uYmxvY2tPcHRpb25zLmdldENvbW1vbkF0dHJpYnV0ZXMoKSxcblx0XHRcdH07XG5cdFx0XHRjdXN0b21TdHlsZXNIYW5kbGVycyA9IGJsb2NrT3B0aW9ucy5zZXRTdHlsZXNIYW5kbGVycztcblxuXHRcdFx0WyAnZm9ybUlkJywgJ2NvcHlQYXN0ZUpzb25WYWx1ZScgXS5mb3JFYWNoKCAoIGtleSApID0+IGRlbGV0ZSBkZWZhdWx0U3R5bGVTZXR0aW5nc1sga2V5IF0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQ2hlY2sgaWYgdGhlIHNpdGUgaGFzIGZvcm1zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4zXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBXaGV0aGVyIHNpdGUgaGFzIGF0IGxlYXN0IG9uZSBmb3JtLlxuXHRcdCAqL1xuXHRcdGhhc0Zvcm1zKCkge1xuXHRcdFx0cmV0dXJuIGZvcm1MaXN0Lmxlbmd0aCA+IDA7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENoZWNrIGlmIGZvcm0gaXMgYXZhaWxhYmxlIHRvIGJlIHByZXZpZXdlZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtudW1iZXJ9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciBmb3JtIGlzIGF2YWlsYWJsZS5cblx0XHQgKi9cblx0XHRpc0Zvcm1BdmFpbGFibGUoIGZvcm1JZCApIHtcblx0XHRcdHJldHVybiBmb3JtTGlzdC5maW5kKCAoIHsgSUQgfSApID0+IElEID09PSBOdW1iZXIoIGZvcm1JZCApICkgIT09IHVuZGVmaW5lZDtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2V0IHRyaWdnZXJTZXJ2ZXJSZW5kZXIgZmxhZy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtib29sZWFufSAkZmxhZyBUaGUgdmFsdWUgb2YgdGhlIHRyaWdnZXJTZXJ2ZXJSZW5kZXIgZmxhZy5cblx0XHQgKi9cblx0XHRzZXRUcmlnZ2VyU2VydmVyUmVuZGVyKCAkZmxhZyApIHtcblx0XHRcdHRyaWdnZXJTZXJ2ZXJSZW5kZXIgPSBCb29sZWFuKCAkZmxhZyApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBNYXliZSBzdWJzY3JpYmUgdG8gYmxvY2sgZXZlbnRzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gc3Vic2NyaWJlclByb3BzICAgICAgICBTdWJzY3JpYmVyIGJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHN1YnNjcmliZXJIYW5kbGVycyAgICAgU3Vic2NyaWJlciBibG9jayBldmVudCBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gc3Vic2NyaWJlckJsb2NrT3B0aW9ucyBTdWJzY3JpYmVyIGJsb2NrIG9wdGlvbnMuXG5cdFx0ICovXG5cdFx0bWF5YmVTdWJzY3JpYmVUb0Jsb2NrRXZlbnRzKCBzdWJzY3JpYmVyUHJvcHMsIHN1YnNjcmliZXJIYW5kbGVycywgc3Vic2NyaWJlckJsb2NrT3B0aW9ucyApIHtcblx0XHRcdGNvbnN0IGlkID0gc3Vic2NyaWJlclByb3BzLmNsaWVudElkO1xuXG5cdFx0XHQvLyBVbnN1YnNjcmliZSBmcm9tIGJsb2NrIGV2ZW50cy5cblx0XHRcdC8vIFRoaXMgaXMgbmVlZGVkIHRvIGF2b2lkIG11bHRpcGxlIHN1YnNjcmlwdGlvbnMgd2hlbiB0aGUgYmxvY2sgaXMgcmUtcmVuZGVyZWQuXG5cdFx0XHRlbC4kd2luZG93XG5cdFx0XHRcdC5vZmYoICd3cGZvcm1zRm9ybVNlbGVjdG9yRGVsZXRlVGhlbWUuJyArIGlkIClcblx0XHRcdFx0Lm9mZiggJ3dwZm9ybXNGb3JtU2VsZWN0b3JVcGRhdGVUaGVtZS4nICsgaWQgKVxuXHRcdFx0XHQub2ZmKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclNldFRoZW1lLicgKyBpZCApO1xuXG5cdFx0XHQvLyBTdWJzY3JpYmUgdG8gYmxvY2sgZXZlbnRzLlxuXHRcdFx0ZWwuJHdpbmRvd1xuXHRcdFx0XHQub24oICd3cGZvcm1zRm9ybVNlbGVjdG9yRGVsZXRlVGhlbWUuJyArIGlkLCBhcHAuc3Vic2NyaWJlckRlbGV0ZVRoZW1lKCBzdWJzY3JpYmVyUHJvcHMsIHN1YnNjcmliZXJCbG9ja09wdGlvbnMgKSApXG5cdFx0XHRcdC5vbiggJ3dwZm9ybXNGb3JtU2VsZWN0b3JVcGRhdGVUaGVtZS4nICsgaWQsIGFwcC5zdWJzY3JpYmVyVXBkYXRlVGhlbWUoIHN1YnNjcmliZXJQcm9wcywgc3Vic2NyaWJlckJsb2NrT3B0aW9ucyApIClcblx0XHRcdFx0Lm9uKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclNldFRoZW1lLicgKyBpZCwgYXBwLnN1YnNjcmliZXJTZXRUaGVtZSggc3Vic2NyaWJlclByb3BzLCBzdWJzY3JpYmVyQmxvY2tPcHRpb25zICkgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQmxvY2sgZXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JEZWxldGVUaGVtZWAgaGFuZGxlci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHN1YnNjcmliZXJQcm9wcyAgICAgICAgU3Vic2NyaWJlciBibG9jayBwcm9wZXJ0aWVzXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHN1YnNjcmliZXJCbG9ja09wdGlvbnMgU3Vic2NyaWJlciBibG9jayBvcHRpb25zLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7RnVuY3Rpb259IEV2ZW50IGhhbmRsZXIuXG5cdFx0ICovXG5cdFx0c3Vic2NyaWJlckRlbGV0ZVRoZW1lKCBzdWJzY3JpYmVyUHJvcHMsIHN1YnNjcmliZXJCbG9ja09wdGlvbnMgKSB7XG5cdFx0XHRyZXR1cm4gZnVuY3Rpb24oIGUsIHRoZW1lU2x1ZywgdHJpZ2dlclByb3BzICkge1xuXHRcdFx0XHRpZiAoIHN1YnNjcmliZXJQcm9wcy5jbGllbnRJZCA9PT0gdHJpZ2dlclByb3BzLmNsaWVudElkICkge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGlmICggc3Vic2NyaWJlclByb3BzPy5hdHRyaWJ1dGVzPy50aGVtZSAhPT0gdGhlbWVTbHVnICkge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGlmICggISBzdWJzY3JpYmVyQmxvY2tPcHRpb25zPy5wYW5lbHM/LnRoZW1lcyApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHQvLyBSZXNldCB0aGVtZSB0byBkZWZhdWx0IG9uZS5cblx0XHRcdFx0c3Vic2NyaWJlckJsb2NrT3B0aW9ucy5wYW5lbHMudGhlbWVzLnNldEJsb2NrVGhlbWUoIHN1YnNjcmliZXJQcm9wcywgJ2RlZmF1bHQnICk7XG5cdFx0XHR9O1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBCbG9jayBldmVudCBgd3Bmb3Jtc0Zvcm1TZWxlY3RvckRlbGV0ZVRoZW1lYCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gc3Vic2NyaWJlclByb3BzICAgICAgICBTdWJzY3JpYmVyIGJsb2NrIHByb3BlcnRpZXNcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gc3Vic2NyaWJlckJsb2NrT3B0aW9ucyBTdWJzY3JpYmVyIGJsb2NrIG9wdGlvbnMuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtGdW5jdGlvbn0gRXZlbnQgaGFuZGxlci5cblx0XHQgKi9cblx0XHRzdWJzY3JpYmVyVXBkYXRlVGhlbWUoIHN1YnNjcmliZXJQcm9wcywgc3Vic2NyaWJlckJsb2NrT3B0aW9ucyApIHtcblx0XHRcdHJldHVybiBmdW5jdGlvbiggZSwgdGhlbWVTbHVnLCB0aGVtZURhdGEsIHRyaWdnZXJQcm9wcyApIHtcblx0XHRcdFx0aWYgKCBzdWJzY3JpYmVyUHJvcHMuY2xpZW50SWQgPT09IHRyaWdnZXJQcm9wcy5jbGllbnRJZCApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRpZiAoIHN1YnNjcmliZXJQcm9wcz8uYXR0cmlidXRlcz8udGhlbWUgIT09IHRoZW1lU2x1ZyApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRpZiAoICEgc3Vic2NyaWJlckJsb2NrT3B0aW9ucz8ucGFuZWxzPy50aGVtZXMgKSB7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0Ly8gUmVzZXQgdGhlbWUgdG8gZGVmYXVsdCBvbmUuXG5cdFx0XHRcdHN1YnNjcmliZXJCbG9ja09wdGlvbnMucGFuZWxzLnRoZW1lcy5zZXRCbG9ja1RoZW1lKCBzdWJzY3JpYmVyUHJvcHMsIHRoZW1lU2x1ZyApO1xuXHRcdFx0fTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQmxvY2sgZXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JTZXRUaGVtZWAgaGFuZGxlci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHN1YnNjcmliZXJQcm9wcyAgICAgICAgU3Vic2NyaWJlciBibG9jayBwcm9wZXJ0aWVzXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHN1YnNjcmliZXJCbG9ja09wdGlvbnMgU3Vic2NyaWJlciBibG9jayBvcHRpb25zLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7RnVuY3Rpb259IEV2ZW50IGhhbmRsZXIuXG5cdFx0ICovXG5cdFx0c3Vic2NyaWJlclNldFRoZW1lKCBzdWJzY3JpYmVyUHJvcHMsIHN1YnNjcmliZXJCbG9ja09wdGlvbnMgKSB7XG5cdFx0XHQvLyBub2luc3BlY3Rpb24gSlNVbnVzZWRMb2NhbFN5bWJvbHNcblx0XHRcdHJldHVybiBmdW5jdGlvbiggZSwgYmxvY2ssIHRoZW1lU2x1ZywgdHJpZ2dlclByb3BzICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG5vLXVudXNlZC12YXJzXG5cdFx0XHRcdGlmICggc3Vic2NyaWJlclByb3BzLmNsaWVudElkID09PSB0cmlnZ2VyUHJvcHMuY2xpZW50SWQgKSB7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0aWYgKCAhIHN1YnNjcmliZXJCbG9ja09wdGlvbnM/LnBhbmVscz8udGhlbWVzICkge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdC8vIFNldCB0aGVtZS5cblx0XHRcdFx0YXBwLm9uU2V0VGhlbWUoIHN1YnNjcmliZXJQcm9wcyApO1xuXHRcdFx0fTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQmxvY2sgSlNYIHBhcnRzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7T2JqZWN0fVxuXHRcdCAqL1xuXHRcdGpzeFBhcnRzOiB7XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IG1haW4gc2V0dGluZ3MgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGF0dHJpYnV0ZXMgIEJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gZm9ybU9wdGlvbnMgRm9ybSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge0pTWC5FbGVtZW50fSBNYWluIHNldHRpbmcgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldE1haW5TZXR0aW5ncyggYXR0cmlidXRlcywgaGFuZGxlcnMsIGZvcm1PcHRpb25zICkge1xuXHRcdFx0XHRpZiAoICEgYXBwLmhhc0Zvcm1zKCkgKSB7XG5cdFx0XHRcdFx0cmV0dXJuIGFwcC5qc3hQYXJ0cy5wcmludEVtcHR5Rm9ybXNOb3RpY2UoIGF0dHJpYnV0ZXMuY2xpZW50SWQgKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEluc3BlY3RvckNvbnRyb2xzIGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItaW5zcGVjdG9yLW1haW4tc2V0dGluZ3NcIj5cblx0XHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwgd3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtZm9ybS1zZXR0aW5nc1wiIHRpdGxlPXsgc3RyaW5ncy5mb3JtX3NldHRpbmdzIH0+XG5cdFx0XHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmZvcm1fc2VsZWN0ZWQgfVxuXHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5mb3JtSWQgfVxuXHRcdFx0XHRcdFx0XHRcdG9wdGlvbnM9eyBmb3JtT3B0aW9ucyB9XG5cdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuYXR0ckNoYW5nZSggJ2Zvcm1JZCcsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHR7IGF0dHJpYnV0ZXMuZm9ybUlkID8gKFxuXHRcdFx0XHRcdFx0XHRcdDw+XG5cdFx0XHRcdFx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWFjdGlvbnNcIj5cblx0XHRcdFx0XHRcdFx0XHRcdFx0PGEgaHJlZj17IHVybHMuZm9ybV91cmwucmVwbGFjZSggJ3tJRH0nLCBhdHRyaWJ1dGVzLmZvcm1JZCApIH0gcmVsPVwibm9yZWZlcnJlclwiIHRhcmdldD1cIl9ibGFua1wiPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5mb3JtX2VkaXQgfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHQ8L2E+XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgaXNQcm8gJiYgaXNMaWNlbnNlQWN0aXZlICYmIChcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQ8PlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0Jm5ic3A7Jm5ic3A7fCZuYnNwOyZuYnNwO1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0PGFcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0aHJlZj17IHVybHMuZW50cmllc191cmwucmVwbGFjZSggJ3tJRH0nLCBhdHRyaWJ1dGVzLmZvcm1JZCApIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0cmVsPVwibm9yZWZlcnJlclwiXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdHRhcmdldD1cIl9ibGFua1wiXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHQ+eyBzdHJpbmdzLmZvcm1fZW50cmllcyB9PC9hPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdDwvPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHQpIH1cblx0XHRcdFx0XHRcdFx0XHRcdDwvcD5cblx0XHRcdFx0XHRcdFx0XHRcdDxUb2dnbGVDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5zaG93X3RpdGxlIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0Y2hlY2tlZD17IGF0dHJpYnV0ZXMuZGlzcGxheVRpdGxlIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuYXR0ckNoYW5nZSggJ2Rpc3BsYXlUaXRsZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0XHRcdDxUb2dnbGVDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5zaG93X2Rlc2NyaXB0aW9uIH1cblx0XHRcdFx0XHRcdFx0XHRcdFx0Y2hlY2tlZD17IGF0dHJpYnV0ZXMuZGlzcGxheURlc2MgfVxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5hdHRyQ2hhbmdlKCAnZGlzcGxheURlc2MnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdFx0PC8+XG5cdFx0XHRcdFx0XHRcdCkgOiBudWxsIH1cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlXCI+XG5cdFx0XHRcdFx0XHRcdFx0PHN0cm9uZz57IHN0cmluZ3MucGFuZWxfbm90aWNlX2hlYWQgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5wYW5lbF9ub3RpY2VfdGV4dCB9XG5cdFx0XHRcdFx0XHRcdFx0PGEgaHJlZj17IHN0cmluZ3MucGFuZWxfbm90aWNlX2xpbmsgfSByZWw9XCJub3JlZmVycmVyXCIgdGFyZ2V0PVwiX2JsYW5rXCI+eyBzdHJpbmdzLnBhbmVsX25vdGljZV9saW5rX3RleHQgfTwvYT5cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdFx0PC9JbnNwZWN0b3JDb250cm9scz5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogUHJpbnQgZW1wdHkgZm9ybXMgbm90aWNlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguM1xuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBjbGllbnRJZCBCbG9jayBjbGllbnQgSUQuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEZpZWxkIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0cHJpbnRFbXB0eUZvcm1zTm90aWNlKCBjbGllbnRJZCApIHtcblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8SW5zcGVjdG9yQ29udHJvbHMga2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1pbnNwZWN0b3ItbWFpbi1zZXR0aW5nc1wiPlxuXHRcdFx0XHRcdFx0PFBhbmVsQm9keSBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbFwiIHRpdGxlPXsgc3RyaW5ncy5mb3JtX3NldHRpbmdzIH0+XG5cdFx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZSB3cGZvcm1zLXdhcm5pbmcgd3Bmb3Jtcy1lbXB0eS1mb3JtLW5vdGljZVwiIHN0eWxlPXsgeyBkaXNwbGF5OiAnYmxvY2snIH0gfT5cblx0XHRcdFx0XHRcdFx0XHQ8c3Ryb25nPnsgX18oICdZb3UgaGF2ZW7igJl0IGNyZWF0ZWQgYSBmb3JtLCB5ZXQhJywgJ3dwZm9ybXMtbGl0ZScgKSB9PC9zdHJvbmc+XG5cdFx0XHRcdFx0XHRcdFx0eyBfXyggJ1doYXQgYXJlIHlvdSB3YWl0aW5nIGZvcj8nLCAnd3Bmb3Jtcy1saXRlJyApIH1cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzc05hbWU9XCJnZXQtc3RhcnRlZC1idXR0b24gY29tcG9uZW50cy1idXR0b24gaXMtc2Vjb25kYXJ5XCJcblx0XHRcdFx0XHRcdFx0XHRvbkNsaWNrPXtcblx0XHRcdFx0XHRcdFx0XHRcdCgpID0+IHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0YXBwLm9wZW5CdWlsZGVyUG9wdXAoIGNsaWVudElkICk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHQ+XG5cdFx0XHRcdFx0XHRcdFx0eyBfXyggJ0dldCBTdGFydGVkJywgJ3dwZm9ybXMtbGl0ZScgKSB9XG5cdFx0XHRcdFx0XHRcdDwvYnV0dG9uPlxuXHRcdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdFx0PC9JbnNwZWN0b3JDb250cm9scz5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IExhYmVsIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBoYW5kbGVycyAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzaXplT3B0aW9ucyBTaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7T2JqZWN0fSBMYWJlbCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldExhYmVsU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIHByb3BzICkgfSB0aXRsZT17IHN0cmluZ3MubGFiZWxfc3R5bGVzIH0+XG5cdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5sYWJlbFNpemUgfVxuXHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZpeC1ib3R0b20tbWFyZ2luXCJcblx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnbGFiZWxTaXplJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHQvPlxuXG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXsgW1xuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5sYWJlbENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2xhYmVsQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5sYWJlbCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmxhYmVsU3VibGFiZWxDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdsYWJlbFN1YmxhYmVsQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5zdWJsYWJlbF9oaW50cy5yZXBsYWNlKCAnJmFtcDsnLCAnJicgKSxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmxhYmVsRXJyb3JDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdsYWJlbEVycm9yQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5lcnJvcl9tZXNzYWdlLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDwvUGFuZWxCb2R5PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgUGFnZSBJbmRpY2F0b3Igc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguN1xuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge09iamVjdH0gUGFnZSBJbmRpY2F0b3Igc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRQYWdlSW5kaWNhdG9yU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgY29tcGxleGl0eVxuXHRcdFx0XHRjb25zdCBoYXNQYWdlQnJlYWsgPSBhcHAuaGFzUGFnZUJyZWFrKCBmb3JtTGlzdCwgcHJvcHMuYXR0cmlidXRlcy5mb3JtSWQgKTtcblx0XHRcdFx0Y29uc3QgaGFzUmF0aW5nID0gYXBwLmhhc1JhdGluZyggZm9ybUxpc3QsIHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkICk7XG5cblx0XHRcdFx0aWYgKCAhIGhhc1BhZ2VCcmVhayAmJiAhIGhhc1JhdGluZyApIHtcblx0XHRcdFx0XHRyZXR1cm4gbnVsbDtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGxldCBsYWJlbCA9ICcnO1xuXHRcdFx0XHRpZiAoIGhhc1BhZ2VCcmVhayAmJiBoYXNSYXRpbmcgKSB7XG5cdFx0XHRcdFx0bGFiZWwgPSBgJHsgc3RyaW5ncy5wYWdlX2JyZWFrIH0gLyAkeyBzdHJpbmdzLnJhdGluZyB9YDtcblx0XHRcdFx0fSBlbHNlIGlmICggaGFzUGFnZUJyZWFrICkge1xuXHRcdFx0XHRcdGxhYmVsID0gc3RyaW5ncy5wYWdlX2JyZWFrO1xuXHRcdFx0XHR9IGVsc2UgaWYgKCBoYXNSYXRpbmcgKSB7XG5cdFx0XHRcdFx0bGFiZWwgPSBzdHJpbmdzLnJhdGluZztcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PFBhbmVsQm9keSBjbGFzc05hbWU9eyBhcHAuZ2V0UGFuZWxDbGFzcyggcHJvcHMgKSB9IHRpdGxlPXsgc3RyaW5ncy5vdGhlcl9zdHlsZXMgfT5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb2xvci1waWNrZXJcIj5cblx0XHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbnRyb2wtbGFiZWxcIj57IHN0cmluZ3MuY29sb3JzIH08L2Rpdj5cblx0XHRcdFx0XHRcdFx0PFBhbmVsQ29sb3JTZXR0aW5nc1xuXHRcdFx0XHRcdFx0XHRcdF9fZXhwZXJpbWVudGFsSXNSZW5kZXJlZEluU2lkZWJhclxuXHRcdFx0XHRcdFx0XHRcdGVuYWJsZUFscGhhXG5cdFx0XHRcdFx0XHRcdFx0c2hvd1RpdGxlPXsgZmFsc2UgfVxuXHRcdFx0XHRcdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGFuZWxcIlxuXHRcdFx0XHRcdFx0XHRcdGNvbG9yU2V0dGluZ3M9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLnBhZ2VCcmVha0NvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ3BhZ2VCcmVha0NvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWwsXG5cdFx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRcdF0gfSAvPlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBzdHlsZSBzZXR0aW5ncyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHNpemVPcHRpb25zICBTaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gYmxvY2tPcHRpb25zIEJsb2NrIG9wdGlvbnMgbG9hZGVkIGZyb20gZXh0ZXJuYWwgbW9kdWxlcy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gdWlTdGF0ZSBcdFVJIHN0YXRlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge09iamVjdH0gSW5zcGVjdG9yIGNvbnRyb2xzIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRTdHlsZVNldHRpbmdzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zLCBibG9ja09wdGlvbnMsIHVpU3RhdGUgKSB7XG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEluc3BlY3RvckNvbnRyb2xzIGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3Itc3R5bGUtc2V0dGluZ3NcIj5cblx0XHRcdFx0XHRcdHsgYmxvY2tPcHRpb25zLmdldFRoZW1lc1BhbmVsKCBwcm9wcywgYXBwLCBibG9ja09wdGlvbnMuc3RvY2tQaG90b3MgKSB9XG5cdFx0XHRcdFx0XHR7IGJsb2NrT3B0aW9ucy5nZXRGaWVsZFN0eWxlcyggcHJvcHMsIGhhbmRsZXJzLCBzaXplT3B0aW9ucywgYXBwICkgfVxuXHRcdFx0XHRcdFx0eyBhcHAuanN4UGFydHMuZ2V0TGFiZWxTdHlsZXMoIHByb3BzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMgKSB9XG5cdFx0XHRcdFx0XHR7IGJsb2NrT3B0aW9ucy5nZXRCdXR0b25TdHlsZXMoIHByb3BzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMsIGFwcCApIH1cblx0XHRcdFx0XHRcdHsgYmxvY2tPcHRpb25zLmdldENvbnRhaW5lclN0eWxlcyggcHJvcHMsIGhhbmRsZXJzLCBhcHAsIHVpU3RhdGUgKSB9XG5cdFx0XHRcdFx0XHR7IGJsb2NrT3B0aW9ucy5nZXRCYWNrZ3JvdW5kU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIGFwcCwgYmxvY2tPcHRpb25zLnN0b2NrUGhvdG9zLCB1aVN0YXRlICkgfVxuXHRcdFx0XHRcdFx0eyBhcHAuanN4UGFydHMuZ2V0UGFnZUluZGljYXRvclN0eWxlcyggcHJvcHMsIGhhbmRsZXJzICkgfVxuXHRcdFx0XHRcdDwvSW5zcGVjdG9yQ29udHJvbHM+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBibG9jayBjb250ZW50IEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm4ge0pTWC5FbGVtZW50fSBCbG9jayBjb250ZW50IEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRCbG9ja0Zvcm1Db250ZW50KCBwcm9wcyApIHtcblx0XHRcdFx0aWYgKCB0cmlnZ2VyU2VydmVyUmVuZGVyICkge1xuXHRcdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0XHQ8U2VydmVyU2lkZVJlbmRlclxuXHRcdFx0XHRcdFx0XHRrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXNlcnZlci1zaWRlLXJlbmRlcmVyXCJcblx0XHRcdFx0XHRcdFx0YmxvY2s9XCJ3cGZvcm1zL2Zvcm0tc2VsZWN0b3JcIlxuXHRcdFx0XHRcdFx0XHRhdHRyaWJ1dGVzPXsgcHJvcHMuYXR0cmlidXRlcyB9XG5cdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRjb25zdCBjbGllbnRJZCA9IHByb3BzLmNsaWVudElkO1xuXHRcdFx0XHRjb25zdCBibG9jayA9IGFwcC5nZXRCbG9ja0NvbnRhaW5lciggcHJvcHMgKTtcblxuXHRcdFx0XHQvLyBJbiB0aGUgY2FzZSBvZiBlbXB0eSBjb250ZW50LCB1c2Ugc2VydmVyIHNpZGUgcmVuZGVyZXIuXG5cdFx0XHRcdC8vIFRoaXMgaGFwcGVucyB3aGVuIHRoZSBibG9jayBpcyBkdXBsaWNhdGVkIG9yIGNvbnZlcnRlZCB0byBhIHJldXNhYmxlIGJsb2NrLlxuXHRcdFx0XHRpZiAoICEgYmxvY2s/LmlubmVySFRNTCApIHtcblx0XHRcdFx0XHR0cmlnZ2VyU2VydmVyUmVuZGVyID0gdHJ1ZTtcblxuXHRcdFx0XHRcdHJldHVybiBhcHAuanN4UGFydHMuZ2V0QmxvY2tGb3JtQ29udGVudCggcHJvcHMgKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXSA9IGJsb2Nrc1sgY2xpZW50SWQgXSB8fCB7fTtcblx0XHRcdFx0YmxvY2tzWyBjbGllbnRJZCBdLmJsb2NrSFRNTCA9IGJsb2NrLmlubmVySFRNTDtcblx0XHRcdFx0YmxvY2tzWyBjbGllbnRJZCBdLmxvYWRlZEZvcm1JZCA9IHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkO1xuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PEZyYWdtZW50IGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtZm9ybS1odG1sXCI+XG5cdFx0XHRcdFx0XHQ8ZGl2IGRhbmdlcm91c2x5U2V0SW5uZXJIVE1MPXsgeyBfX2h0bWw6IGJsb2Nrc1sgY2xpZW50SWQgXS5ibG9ja0hUTUwgfSB9IC8+XG5cdFx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIHByZXZpZXcgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEJsb2NrIHByZXZpZXcgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrUHJldmlldygpIHtcblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stcHJldmlld1wiPlxuXHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX3ByZXZpZXdfdXJsIH0gc3R5bGU9eyB7IHdpZHRoOiAnMTAwJScgfSB9IGFsdD1cIlwiIC8+XG5cdFx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguM1xuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICogQHJldHVybiB7SlNYLkVsZW1lbnR9IEJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSB7XG5cdFx0XHRcdGNvbnN0IGNsaWVudElkID0gcHJvcHMuY2xpZW50SWQ7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stZW1wdHlcIj5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1uby1mb3JtLXByZXZpZXdcIj5cblx0XHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX2VtcHR5X3VybCB9IGFsdD1cIlwiIC8+XG5cdFx0XHRcdFx0XHRcdDxwPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J1lvdSBjYW4gdXNlIDxiPldQRm9ybXM8L2I+IHRvIGJ1aWxkIGNvbnRhY3QgZm9ybXMsIHN1cnZleXMsIHBheW1lbnQgZm9ybXMsIGFuZCBtb3JlIHdpdGgganVzdCBhIGZldyBjbGlja3MuJyxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQnd3Bmb3Jtcy1saXRlJ1xuXHRcdFx0XHRcdFx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0YjogPHN0cm9uZyAvPixcblx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0KVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzc05hbWU9XCJnZXQtc3RhcnRlZC1idXR0b24gY29tcG9uZW50cy1idXR0b24gaXMtcHJpbWFyeVwiXG5cdFx0XHRcdFx0XHRcdFx0b25DbGljaz17XG5cdFx0XHRcdFx0XHRcdFx0XHQoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGFwcC5vcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJZCApO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0XHRcdHsgX18oICdHZXQgU3RhcnRlZCcsICd3cGZvcm1zLWxpdGUnICkgfVxuXHRcdFx0XHRcdFx0XHQ8L2J1dHRvbj5cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwiZW1wdHktZGVzY1wiPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J05lZWQgc29tZSBoZWxwPyBDaGVjayBvdXQgb3VyIDxhPmNvbXByZWhlbnNpdmUgZ3VpZGUuPC9hPicsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J3dwZm9ybXMtbGl0ZSdcblx0XHRcdFx0XHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBqc3gtYTExeS9hbmNob3ItaGFzLWNvbnRlbnRcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRhOiA8YSBocmVmPXsgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci53cGZvcm1zX2d1aWRlIH0gdGFyZ2V0PVwiX2JsYW5rXCIgcmVsPVwibm9vcGVuZXIgbm9yZWZlcnJlclwiIC8+LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHQpXG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHQ8L3A+XG5cblx0XHRcdFx0XHRcdFx0eyAvKiBUZW1wbGF0ZSBmb3IgcG9wdXAgd2l0aCBidWlsZGVyIGlmcmFtZSAqLyB9XG5cdFx0XHRcdFx0XHRcdDxkaXYgaWQ9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cFwiIGNsYXNzTmFtZT1cIndwZm9ybXMtYnVpbGRlci1wb3B1cFwiPlxuXHRcdFx0XHRcdFx0XHRcdDxpZnJhbWUgc3JjPVwiYWJvdXQ6YmxhbmtcIiB3aWR0aD1cIjEwMCVcIiBoZWlnaHQ9XCIxMDAlXCIgaWQ9XCJ3cGZvcm1zLWJ1aWxkZXItaWZyYW1lXCIgdGl0bGU9XCJXUEZvcm1zIEJ1aWxkZXIgUG9wdXBcIj48L2lmcmFtZT5cblx0XHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0XHQ8L0ZyYWdtZW50PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgYmxvY2sgcGxhY2Vob2xkZXIgKGZvcm0gc2VsZWN0b3IpIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBhdHRyaWJ1dGVzICBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IGZvcm1PcHRpb25zIEZvcm0gc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJuIHtKU1guRWxlbWVudH0gQmxvY2sgcGxhY2Vob2xkZXIgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrUGxhY2Vob2xkZXIoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApIHtcblx0XHRcdFx0Y29uc3QgaXNGb3JtTm90QXZhaWxhYmxlID0gYXR0cmlidXRlcy5mb3JtSWQgJiYgISBhcHAuaXNGb3JtQXZhaWxhYmxlKCBhdHRyaWJ1dGVzLmZvcm1JZCApO1xuXG5cdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0PFBsYWNlaG9sZGVyXG5cdFx0XHRcdFx0XHRrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXdyYXBcIlxuXHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci13cmFwXCI+XG5cdFx0XHRcdFx0XHQ8aW1nIHNyYz17IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IubG9nb191cmwgfSBhbHQ9XCJcIiAvPlxuXHRcdFx0XHRcdFx0eyBpc0Zvcm1Ob3RBdmFpbGFibGUgJiYgKFxuXHRcdFx0XHRcdFx0XHQ8cCBzdHlsZT17IHsgdGV4dEFsaWduOiAnY2VudGVyJywgbWFyZ2luVG9wOiAnMCcgfSB9PlxuXHRcdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5mb3JtX25vdF9hdmFpbGFibGVfbWVzc2FnZSB9XG5cdFx0XHRcdFx0XHRcdDwvcD5cblx0XHRcdFx0XHRcdCkgfVxuXHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0a2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1zZWxlY3QtY29udHJvbFwiXG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5mb3JtSWQgfVxuXHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgZm9ybU9wdGlvbnMgfVxuXHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5hdHRyQ2hhbmdlKCAnZm9ybUlkJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdDwvUGxhY2Vob2xkZXI+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgaWYgdGhlIGZvcm0gaGFzIGEgUGFnZSBCcmVhayBmaWVsZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguN1xuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICAgICAgICBmb3JtcyAgVGhlIGZvcm1zJyBkYXRhIG9iamVjdC5cblx0XHQgKiBAcGFyYW0ge251bWJlcnxzdHJpbmd9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gVHJ1ZSB3aGVuIHRoZSBmb3JtIGhhcyBhIFBhZ2UgQnJlYWsgZmllbGQsIGZhbHNlIG90aGVyd2lzZS5cblx0XHQgKi9cblx0XHRoYXNQYWdlQnJlYWsoIGZvcm1zLCBmb3JtSWQgKSB7XG5cdFx0XHRjb25zdCBjdXJyZW50Rm9ybSA9IGZvcm1zLmZpbmQoICggZm9ybSApID0+IHBhcnNlSW50KCBmb3JtLklELCAxMCApID09PSBwYXJzZUludCggZm9ybUlkLCAxMCApICk7XG5cblx0XHRcdGlmICggISBjdXJyZW50Rm9ybS5wb3N0X2NvbnRlbnQgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgZmllbGRzID0gSlNPTi5wYXJzZSggY3VycmVudEZvcm0ucG9zdF9jb250ZW50ICk/LmZpZWxkcztcblxuXHRcdFx0cmV0dXJuIE9iamVjdC52YWx1ZXMoIGZpZWxkcyApLnNvbWUoICggZmllbGQgKSA9PiBmaWVsZC50eXBlID09PSAncGFnZWJyZWFrJyApO1xuXHRcdH0sXG5cblx0XHRoYXNSYXRpbmcoIGZvcm1zLCBmb3JtSWQgKSB7XG5cdFx0XHRjb25zdCBjdXJyZW50Rm9ybSA9IGZvcm1zLmZpbmQoICggZm9ybSApID0+IHBhcnNlSW50KCBmb3JtLklELCAxMCApID09PSBwYXJzZUludCggZm9ybUlkLCAxMCApICk7XG5cblx0XHRcdGlmICggISBjdXJyZW50Rm9ybS5wb3N0X2NvbnRlbnQgfHwgISBpc1BybyB8fCAhIGlzTGljZW5zZUFjdGl2ZSApIHtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCBmaWVsZHMgPSBKU09OLnBhcnNlKCBjdXJyZW50Rm9ybS5wb3N0X2NvbnRlbnQgKT8uZmllbGRzO1xuXG5cdFx0XHRyZXR1cm4gT2JqZWN0LnZhbHVlcyggZmllbGRzICkuc29tZSggKCBmaWVsZCApID0+IGZpZWxkLnR5cGUgPT09ICdyYXRpbmcnICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBTdHlsZSBTZXR0aW5ncyBwYW5lbCBjbGFzcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IHBhbmVsIFBhbmVsIG5hbWUuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtzdHJpbmd9IFN0eWxlIFNldHRpbmdzIHBhbmVsIGNsYXNzLlxuXHRcdCAqL1xuXHRcdGdldFBhbmVsQ2xhc3MoIHByb3BzLCBwYW5lbCA9ICcnICkge1xuXHRcdFx0bGV0IGNzc0NsYXNzID0gJ3dwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsIHdwZm9ybXMtYmxvY2stc2V0dGluZ3MtJyArIHByb3BzLmNsaWVudElkO1xuXG5cdFx0XHRpZiAoICEgYXBwLmlzRnVsbFN0eWxpbmdFbmFibGVkKCkgKSB7XG5cdFx0XHRcdGNzc0NsYXNzICs9ICcgZGlzYWJsZWRfcGFuZWwnO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBSZXN0cmljdCBzdHlsaW5nIHBhbmVsIGZvciBub24tYWRtaW5zLlxuXHRcdFx0aWYgKCAhICggaXNBZG1pbiB8fCBwYW5lbCA9PT0gJ3RoZW1lcycgKSApIHtcblx0XHRcdFx0Y3NzQ2xhc3MgKz0gJyB3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1yZXN0cmljdGVkJztcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGNzc0NsYXNzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgY29sb3IgcGFuZWwgc2V0dGluZ3MgQ1NTIGNsYXNzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gYm9yZGVyU3R5bGUgQm9yZGVyIHN0eWxlIHZhbHVlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7c3RyaW5nfSBTdHlsZSBTZXR0aW5ncyBwYW5lbCBjbGFzcy5cblx0XHQgKi9cblx0XHRnZXRDb2xvclBhbmVsQ2xhc3MoIGJvcmRlclN0eWxlICkge1xuXHRcdFx0bGV0IGNzc0NsYXNzID0gJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGFuZWwnO1xuXG5cdFx0XHRpZiAoIGJvcmRlclN0eWxlID09PSAnbm9uZScgKSB7XG5cdFx0XHRcdGNzc0NsYXNzICs9ICcgd3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1ib3JkZXItY29sb3ItZGlzYWJsZWQnO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gY3NzQ2xhc3M7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERldGVybWluZSB3aGV0aGVyIHRoZSBmdWxsIHN0eWxpbmcgaXMgZW5hYmxlZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciB0aGUgZnVsbCBzdHlsaW5nIGlzIGVuYWJsZWQuXG5cdFx0ICovXG5cdFx0aXNGdWxsU3R5bGluZ0VuYWJsZWQoKSB7XG5cdFx0XHRyZXR1cm4gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5pc19tb2Rlcm5fbWFya3VwICYmIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuaXNfZnVsbF9zdHlsaW5nO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgd2hldGhlciB0aGUgYmxvY2sgaGFzIGxlYWQgZm9ybXMgZW5hYmxlZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjkuMFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGJsb2NrIEd1dGVuYmVyZyBibG9ja1xuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciB0aGUgYmxvY2sgaGFzIGxlYWQgZm9ybXMgZW5hYmxlZFxuXHRcdCAqL1xuXHRcdGlzTGVhZEZvcm1zRW5hYmxlZCggYmxvY2sgKSB7XG5cdFx0XHRpZiAoICEgYmxvY2sgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgJGZvcm0gPSAkKCBibG9jay5xdWVyeVNlbGVjdG9yKCAnLndwZm9ybXMtY29udGFpbmVyJyApICk7XG5cblx0XHRcdHJldHVybiAkZm9ybS5oYXNDbGFzcyggJ3dwZm9ybXMtbGVhZC1mb3Jtcy1jb250YWluZXInICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBibG9jayBjb250YWluZXIgRE9NIGVsZW1lbnQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7RWxlbWVudH0gQmxvY2sgY29udGFpbmVyLlxuXHRcdCAqL1xuXHRcdGdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApIHtcblx0XHRcdGNvbnN0IGJsb2NrU2VsZWN0b3IgPSBgI2Jsb2NrLSR7IHByb3BzLmNsaWVudElkIH0gPiBkaXZgO1xuXHRcdFx0bGV0IGJsb2NrID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggYmxvY2tTZWxlY3RvciApO1xuXG5cdFx0XHQvLyBGb3IgRlNFIC8gR3V0ZW5iZXJnIHBsdWdpbiwgd2UgbmVlZCB0byB0YWtlIGEgbG9vayBpbnNpZGUgdGhlIGlmcmFtZS5cblx0XHRcdGlmICggISBibG9jayApIHtcblx0XHRcdFx0Y29uc3QgZWRpdG9yQ2FudmFzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggJ2lmcmFtZVtuYW1lPVwiZWRpdG9yLWNhbnZhc1wiXScgKTtcblxuXHRcdFx0XHRibG9jayA9IGVkaXRvckNhbnZhcz8uY29udGVudFdpbmRvdy5kb2N1bWVudC5xdWVyeVNlbGVjdG9yKCBibG9ja1NlbGVjdG9yICk7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBibG9jaztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGZvcm0gY29udGFpbmVyIGluIEJsb2NrIEVkaXRvci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjkuM1xuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtudW1iZXJ9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7RWxlbWVudHxudWxsfSBGb3JtIGNvbnRhaW5lci5cblx0XHQgKi9cblx0XHRnZXRGb3JtQmxvY2soIGZvcm1JZCApIHtcblx0XHRcdC8vIEZpcnN0LCB0cnkgdG8gZmluZCB0aGUgaWZyYW1lIGZvciBibG9ja3MgdmVyc2lvbiAzLlxuXHRcdFx0Y29uc3QgZWRpdG9yQ2FudmFzID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggJ2lmcmFtZVtuYW1lPVwiZWRpdG9yLWNhbnZhc1wiXScgKTtcblxuXHRcdFx0Ly8gSWYgdGhlIGlmcmFtZSBpcyBmb3VuZCwgdHJ5IHRvIGZpbmQgdGhlIGZvcm0uXG5cdFx0XHRyZXR1cm4gZWRpdG9yQ2FudmFzPy5jb250ZW50V2luZG93LmRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoIGAjd3Bmb3Jtcy0keyBmb3JtSWQgfWAgKSB8fCAkKCBgI3dwZm9ybXMtJHsgZm9ybUlkIH1gICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFVwZGF0ZSBDU1MgdmFyaWFibGUocykgdmFsdWUocykgb2YgdGhlIGdpdmVuIGF0dHJpYnV0ZSBmb3IgZ2l2ZW4gY29udGFpbmVyIG9uIHRoZSBwcmV2aWV3LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gIGF0dHJpYnV0ZSBTdHlsZSBhdHRyaWJ1dGU6IGZpZWxkLXNpemUsIGxhYmVsLXNpemUsIGJ1dHRvbi1zaXplLCBldGMuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9ICB2YWx1ZSAgICAgUHJvcGVydHkgbmV3IHZhbHVlLlxuXHRcdCAqIEBwYXJhbSB7RWxlbWVudH0gY29udGFpbmVyIEZvcm0gY29udGFpbmVyLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSAgcHJvcHMgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICovXG5cdFx0dXBkYXRlUHJldmlld0NTU1ZhclZhbHVlKCBhdHRyaWJ1dGUsIHZhbHVlLCBjb250YWluZXIsIHByb3BzICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIGNvbXBsZXhpdHksIG1heC1saW5lcy1wZXItZnVuY3Rpb25cblx0XHRcdGlmICggISBjb250YWluZXIgfHwgISBhdHRyaWJ1dGUgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgcHJvcGVydHkgPSBhdHRyaWJ1dGUucmVwbGFjZShcblx0XHRcdFx0L1tBLVpdL2csXG5cdFx0XHRcdCggbGV0dGVyICkgPT4gYC0keyBsZXR0ZXIudG9Mb3dlckNhc2UoKSB9YFxuXHRcdFx0KTtcblxuXHRcdFx0aWYgKCB0eXBlb2YgY3VzdG9tU3R5bGVzSGFuZGxlcnNbIHByb3BlcnR5IF0gPT09ICdmdW5jdGlvbicgKSB7XG5cdFx0XHRcdGN1c3RvbVN0eWxlc0hhbmRsZXJzWyBwcm9wZXJ0eSBdKCBjb250YWluZXIsIHZhbHVlICk7XG5cblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRzd2l0Y2ggKCBwcm9wZXJ0eSApIHtcblx0XHRcdFx0Y2FzZSAnZmllbGQtc2l6ZSc6XG5cdFx0XHRcdGNhc2UgJ2xhYmVsLXNpemUnOlxuXHRcdFx0XHRjYXNlICdidXR0b24tc2l6ZSc6XG5cdFx0XHRcdGNhc2UgJ2NvbnRhaW5lci1zaGFkb3ctc2l6ZSc6XG5cdFx0XHRcdFx0Zm9yICggY29uc3Qga2V5IGluIHNpemVzWyBwcm9wZXJ0eSBdWyB2YWx1ZSBdICkge1xuXHRcdFx0XHRcdFx0Y29udGFpbmVyLnN0eWxlLnNldFByb3BlcnR5KFxuXHRcdFx0XHRcdFx0XHRgLS13cGZvcm1zLSR7IHByb3BlcnR5IH0tJHsga2V5IH1gLFxuXHRcdFx0XHRcdFx0XHRzaXplc1sgcHJvcGVydHkgXVsgdmFsdWUgXVsga2V5IF0sXG5cdFx0XHRcdFx0XHQpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRjYXNlICdmaWVsZC1ib3JkZXItc3R5bGUnOlxuXHRcdFx0XHRcdGlmICggdmFsdWUgPT09ICdub25lJyApIHtcblx0XHRcdFx0XHRcdGFwcC50b2dnbGVGaWVsZEJvcmRlck5vbmVDU1NWYXJWYWx1ZSggY29udGFpbmVyLCB0cnVlICk7XG5cdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdGFwcC50b2dnbGVGaWVsZEJvcmRlck5vbmVDU1NWYXJWYWx1ZSggY29udGFpbmVyLCBmYWxzZSApO1xuXHRcdFx0XHRcdFx0Y29udGFpbmVyLnN0eWxlLnNldFByb3BlcnR5KCBgLS13cGZvcm1zLSR7IHByb3BlcnR5IH1gLCB2YWx1ZSApO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRjYXNlICdidXR0b24tYmFja2dyb3VuZC1jb2xvcic6XG5cdFx0XHRcdFx0YXBwLm1heWJlVXBkYXRlQWNjZW50Q29sb3IoIHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQm9yZGVyQ29sb3IsIHZhbHVlLCBjb250YWluZXIgKTtcblx0XHRcdFx0XHR2YWx1ZSA9IGFwcC5tYXliZVNldEJ1dHRvbkFsdEJhY2tncm91bmRDb2xvciggdmFsdWUsIHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQm9yZGVyQ29sb3IsIGNvbnRhaW5lciApO1xuXHRcdFx0XHRcdGFwcC5tYXliZVNldEJ1dHRvbkFsdFRleHRDb2xvciggcHJvcHMuYXR0cmlidXRlcy5idXR0b25UZXh0Q29sb3IsIHZhbHVlLCBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlckNvbG9yLCBjb250YWluZXIgKTtcblx0XHRcdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtJHsgcHJvcGVydHkgfWAsIHZhbHVlICk7XG5cblx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0Y2FzZSAnYnV0dG9uLWJvcmRlci1jb2xvcic6XG5cdFx0XHRcdFx0YXBwLm1heWJlVXBkYXRlQWNjZW50Q29sb3IoIHZhbHVlLCBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJhY2tncm91bmRDb2xvciwgY29udGFpbmVyICk7XG5cdFx0XHRcdFx0YXBwLm1heWJlU2V0QnV0dG9uQWx0VGV4dENvbG9yKCBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvblRleHRDb2xvciwgcHJvcHMuYXR0cmlidXRlcy5idXR0b25CYWNrZ3JvdW5kQ29sb3IsIHZhbHVlLCBjb250YWluZXIgKTtcblx0XHRcdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtJHsgcHJvcGVydHkgfWAsIHZhbHVlICk7XG5cblx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0Y2FzZSAnYnV0dG9uLXRleHQtY29sb3InOlxuXHRcdFx0XHRcdGFwcC5tYXliZVNldEJ1dHRvbkFsdFRleHRDb2xvciggdmFsdWUsIHByb3BzLmF0dHJpYnV0ZXMuYnV0dG9uQmFja2dyb3VuZENvbG9yLCBwcm9wcy5hdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlckNvbG9yLCBjb250YWluZXIgKTtcblx0XHRcdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtJHsgcHJvcGVydHkgfWAsIHZhbHVlICk7XG5cblx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0ZGVmYXVsdDpcblx0XHRcdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtJHsgcHJvcGVydHkgfWAsIHZhbHVlICk7XG5cdFx0XHRcdFx0Y29udGFpbmVyLnN0eWxlLnNldFByb3BlcnR5KCBgLS13cGZvcm1zLSR7IHByb3BlcnR5IH0tc3BhcmVgLCB2YWx1ZSApO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBTZXQvdW5zZXQgZmllbGQgYm9yZGVyIHZhcnMgaW4gY2FzZSBvZiBib3JkZXItc3R5bGUgaXMgbm9uZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICBjb250YWluZXIgRm9ybSBjb250YWluZXIuXG5cdFx0ICogQHBhcmFtIHtib29sZWFufSBzZXQgICAgICAgVHJ1ZSB3aGVuIHNldCwgZmFsc2Ugd2hlbiB1bnNldC5cblx0XHQgKi9cblx0XHR0b2dnbGVGaWVsZEJvcmRlck5vbmVDU1NWYXJWYWx1ZSggY29udGFpbmVyLCBzZXQgKSB7XG5cdFx0XHRjb25zdCBjb250ID0gY29udGFpbmVyLnF1ZXJ5U2VsZWN0b3IoICdmb3JtJyApO1xuXG5cdFx0XHRpZiAoIHNldCApIHtcblx0XHRcdFx0Y29udC5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1maWVsZC1ib3JkZXItc3R5bGUnLCAnc29saWQnICk7XG5cdFx0XHRcdGNvbnQuc3R5bGUuc2V0UHJvcGVydHkoICctLXdwZm9ybXMtZmllbGQtYm9yZGVyLXNpemUnLCAnMXB4JyApO1xuXHRcdFx0XHRjb250LnN0eWxlLnNldFByb3BlcnR5KCAnLS13cGZvcm1zLWZpZWxkLWJvcmRlci1jb2xvcicsICd0cmFuc3BhcmVudCcgKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnQuc3R5bGUuc2V0UHJvcGVydHkoICctLXdwZm9ybXMtZmllbGQtYm9yZGVyLXN0eWxlJywgbnVsbCApO1xuXHRcdFx0Y29udC5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1maWVsZC1ib3JkZXItc2l6ZScsIG51bGwgKTtcblx0XHRcdGNvbnQuc3R5bGUuc2V0UHJvcGVydHkoICctLXdwZm9ybXMtZmllbGQtYm9yZGVyLWNvbG9yJywgbnVsbCApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBNYXliZSBzZXQgdGhlIGJ1dHRvbidzIGFsdGVybmF0aXZlIGJhY2tncm91bmQgY29sb3IuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSAgICAgICAgICAgICBBdHRyaWJ1dGUgdmFsdWUuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGJ1dHRvbkJvcmRlckNvbG9yIEJ1dHRvbiBib3JkZXIgY29sb3IuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGNvbnRhaW5lciAgICAgICAgIEZvcm0gY29udGFpbmVyLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7c3RyaW5nfCp9IE5ldyBiYWNrZ3JvdW5kIGNvbG9yLlxuXHRcdCAqL1xuXHRcdG1heWJlU2V0QnV0dG9uQWx0QmFja2dyb3VuZENvbG9yKCB2YWx1ZSwgYnV0dG9uQm9yZGVyQ29sb3IsIGNvbnRhaW5lciApIHtcblx0XHRcdC8vIFNldHRpbmcgY3NzIHByb3BlcnR5IHZhbHVlIHRvIGNoaWxkIGBmb3JtYCBlbGVtZW50IG92ZXJyaWRlcyB0aGUgcGFyZW50IHByb3BlcnR5IHZhbHVlLlxuXHRcdFx0Y29uc3QgZm9ybSA9IGNvbnRhaW5lci5xdWVyeVNlbGVjdG9yKCAnZm9ybScgKTtcblxuXHRcdFx0Zm9ybS5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1idXR0b24tYmFja2dyb3VuZC1jb2xvci1hbHQnLCB2YWx1ZSApO1xuXG5cdFx0XHRpZiAoIFdQRm9ybXNVdGlscy5jc3NDb2xvcnNVdGlscy5pc1RyYW5zcGFyZW50Q29sb3IoIHZhbHVlICkgKSB7XG5cdFx0XHRcdHJldHVybiBXUEZvcm1zVXRpbHMuY3NzQ29sb3JzVXRpbHMuaXNUcmFuc3BhcmVudENvbG9yKCBidXR0b25Cb3JkZXJDb2xvciApID8gZGVmYXVsdFN0eWxlU2V0dGluZ3MuYnV0dG9uQmFja2dyb3VuZENvbG9yIDogYnV0dG9uQm9yZGVyQ29sb3I7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiB2YWx1ZTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogTWF5YmUgc2V0IHRoZSBidXR0b24ncyBhbHRlcm5hdGl2ZSB0ZXh0IGNvbG9yLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsdWUgICAgICAgICAgICAgICAgIEF0dHJpYnV0ZSB2YWx1ZS5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gYnV0dG9uQmFja2dyb3VuZENvbG9yIEJ1dHRvbiBiYWNrZ3JvdW5kIGNvbG9yLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBidXR0b25Cb3JkZXJDb2xvciAgICAgQnV0dG9uIGJvcmRlciBjb2xvci5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gY29udGFpbmVyICAgICAgICAgICAgIEZvcm0gY29udGFpbmVyLlxuXHRcdCAqL1xuXHRcdG1heWJlU2V0QnV0dG9uQWx0VGV4dENvbG9yKCB2YWx1ZSwgYnV0dG9uQmFja2dyb3VuZENvbG9yLCBidXR0b25Cb3JkZXJDb2xvciwgY29udGFpbmVyICkge1xuXHRcdFx0Y29uc3QgZm9ybSA9IGNvbnRhaW5lci5xdWVyeVNlbGVjdG9yKCAnZm9ybScgKTtcblxuXHRcdFx0bGV0IGFsdENvbG9yID0gbnVsbDtcblxuXHRcdFx0dmFsdWUgPSB2YWx1ZS50b0xvd2VyQ2FzZSgpO1xuXG5cdFx0XHRpZiAoXG5cdFx0XHRcdFdQRm9ybXNVdGlscy5jc3NDb2xvcnNVdGlscy5pc1RyYW5zcGFyZW50Q29sb3IoIHZhbHVlICkgfHxcblx0XHRcdFx0dmFsdWUgPT09IGJ1dHRvbkJhY2tncm91bmRDb2xvciB8fFxuXHRcdFx0XHQoXG5cdFx0XHRcdFx0V1BGb3Jtc1V0aWxzLmNzc0NvbG9yc1V0aWxzLmlzVHJhbnNwYXJlbnRDb2xvciggYnV0dG9uQmFja2dyb3VuZENvbG9yICkgJiZcblx0XHRcdFx0XHR2YWx1ZSA9PT0gYnV0dG9uQm9yZGVyQ29sb3Jcblx0XHRcdFx0KVxuXHRcdFx0KSB7XG5cdFx0XHRcdGFsdENvbG9yID0gV1BGb3Jtc1V0aWxzLmNzc0NvbG9yc1V0aWxzLmdldENvbnRyYXN0Q29sb3IoIGJ1dHRvbkJhY2tncm91bmRDb2xvciApO1xuXHRcdFx0fVxuXG5cdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtYnV0dG9uLXRleHQtY29sb3ItYWx0YCwgdmFsdWUgKTtcblx0XHRcdGZvcm0uc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtYnV0dG9uLXRleHQtY29sb3ItYWx0YCwgYWx0Q29sb3IgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogTWF5YmUgdXBkYXRlIGFjY2VudCBjb2xvci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNvbG9yICAgICAgICAgICAgICAgICBDb2xvciB2YWx1ZS5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gYnV0dG9uQmFja2dyb3VuZENvbG9yIEJ1dHRvbiBiYWNrZ3JvdW5kIGNvbG9yLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBjb250YWluZXIgICAgICAgICAgICAgRm9ybSBjb250YWluZXIuXG5cdFx0ICovXG5cdFx0bWF5YmVVcGRhdGVBY2NlbnRDb2xvciggY29sb3IsIGJ1dHRvbkJhY2tncm91bmRDb2xvciwgY29udGFpbmVyICkge1xuXHRcdFx0Ly8gU2V0dGluZyBjc3MgcHJvcGVydHkgdmFsdWUgdG8gY2hpbGQgYGZvcm1gIGVsZW1lbnQgb3ZlcnJpZGVzIHRoZSBwYXJlbnQgcHJvcGVydHkgdmFsdWUuXG5cdFx0XHRjb25zdCBmb3JtID0gY29udGFpbmVyLnF1ZXJ5U2VsZWN0b3IoICdmb3JtJyApO1xuXG5cdFx0XHQvLyBGYWxsYmFjayB0byBkZWZhdWx0IGNvbG9yIGlmIHRoZSBib3JkZXIgY29sb3IgaXMgdHJhbnNwYXJlbnQuXG5cdFx0XHRjb2xvciA9IFdQRm9ybXNVdGlscy5jc3NDb2xvcnNVdGlscy5pc1RyYW5zcGFyZW50Q29sb3IoIGNvbG9yICkgPyBkZWZhdWx0U3R5bGVTZXR0aW5ncy5idXR0b25CYWNrZ3JvdW5kQ29sb3IgOiBjb2xvcjtcblxuXHRcdFx0aWYgKCBXUEZvcm1zVXRpbHMuY3NzQ29sb3JzVXRpbHMuaXNUcmFuc3BhcmVudENvbG9yKCBidXR0b25CYWNrZ3JvdW5kQ29sb3IgKSApIHtcblx0XHRcdFx0Zm9ybS5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1idXR0b24tYmFja2dyb3VuZC1jb2xvci1hbHQnLCAncmdiYSggMCwgMCwgMCwgMCApJyApO1xuXHRcdFx0XHRmb3JtLnN0eWxlLnNldFByb3BlcnR5KCAnLS13cGZvcm1zLWJ1dHRvbi1iYWNrZ3JvdW5kLWNvbG9yJywgY29sb3IgKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGNvbnRhaW5lci5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1idXR0b24tYmFja2dyb3VuZC1jb2xvci1hbHQnLCBidXR0b25CYWNrZ3JvdW5kQ29sb3IgKTtcblx0XHRcdFx0Zm9ybS5zdHlsZS5zZXRQcm9wZXJ0eSggJy0td3Bmb3Jtcy1idXR0b24tYmFja2dyb3VuZC1jb2xvci1hbHQnLCBudWxsICk7XG5cdFx0XHRcdGZvcm0uc3R5bGUuc2V0UHJvcGVydHkoICctLXdwZm9ybXMtYnV0dG9uLWJhY2tncm91bmQtY29sb3InLCBudWxsICk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzZXR0aW5ncyBmaWVsZHMgZXZlbnQgaGFuZGxlcnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBPYmplY3QgdGhhdCBjb250YWlucyBldmVudCBoYW5kbGVycyBmb3IgdGhlIHNldHRpbmdzIGZpZWxkcy5cblx0XHQgKi9cblx0XHRnZXRTZXR0aW5nc0ZpZWxkc0hhbmRsZXJzKCBwcm9wcyApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uXG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogRmllbGQgc3R5bGUgYXR0cmlidXRlIGNoYW5nZSBldmVudCBoYW5kbGVyLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IGF0dHJpYnV0ZSBBdHRyaWJ1dGUgbmFtZS5cblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlICAgICBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0c3R5bGVBdHRyQ2hhbmdlKCBhdHRyaWJ1dGUsIHZhbHVlICkge1xuXHRcdFx0XHRcdGNvbnN0IGJsb2NrID0gYXBwLmdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApLFxuXHRcdFx0XHRcdFx0Y29udGFpbmVyID0gYmxvY2sucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLSR7IHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkIH1gICksXG5cdFx0XHRcdFx0XHRzZXRBdHRyID0ge307XG5cblx0XHRcdFx0XHQvLyBVbnNldCB0aGUgY29sb3IgbWVhbnMgc2V0dGluZyB0aGUgdHJhbnNwYXJlbnQgY29sb3IuXG5cdFx0XHRcdFx0aWYgKCBhdHRyaWJ1dGUuaW5jbHVkZXMoICdDb2xvcicgKSApIHtcblx0XHRcdFx0XHRcdHZhbHVlID0gdmFsdWUgPz8gJ3JnYmEoIDAsIDAsIDAsIDAgKSc7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0YXBwLnVwZGF0ZVByZXZpZXdDU1NWYXJWYWx1ZSggYXR0cmlidXRlLCB2YWx1ZSwgY29udGFpbmVyLCBwcm9wcyApO1xuXG5cdFx0XHRcdFx0c2V0QXR0clsgYXR0cmlidXRlIF0gPSB2YWx1ZTtcblxuXHRcdFx0XHRcdGFwcC5zZXRCbG9ja1J1bnRpbWVTdGF0ZVZhciggcHJvcHMuY2xpZW50SWQsICdwcmV2QXR0cmlidXRlc1N0YXRlJywgcHJvcHMuYXR0cmlidXRlcyApO1xuXHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHNldEF0dHIgKTtcblxuXHRcdFx0XHRcdHRyaWdnZXJTZXJ2ZXJSZW5kZXIgPSBmYWxzZTtcblxuXHRcdFx0XHRcdHRoaXMudXBkYXRlQ29weVBhc3RlQ29udGVudCgpO1xuXG5cdFx0XHRcdFx0YXBwLnBhbmVscy50aGVtZXMudXBkYXRlQ3VzdG9tVGhlbWVBdHRyaWJ1dGUoIGF0dHJpYnV0ZSwgdmFsdWUsIHByb3BzICk7XG5cblx0XHRcdFx0XHR0aGlzLm1heWJlVG9nZ2xlRHJvcGRvd24oIHByb3BzLCBhdHRyaWJ1dGUgKTtcblxuXHRcdFx0XHRcdC8vIFRyaWdnZXIgZXZlbnQgZm9yIGRldmVsb3BlcnMuXG5cdFx0XHRcdFx0ZWwuJHdpbmRvdy50cmlnZ2VyKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclN0eWxlQXR0ckNoYW5nZScsIFsgYmxvY2ssIHByb3BzLCBhdHRyaWJ1dGUsIHZhbHVlIF0gKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogSGFuZGxlcyB0aGUgdG9nZ2xpbmcgb2YgdGhlIGRyb3Bkb3duIG1lbnUncyB2aXNpYmlsaXR5LlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgICBUaGUgYmxvY2sgcHJvcGVydGllcy5cblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IGF0dHJpYnV0ZSBUaGUgbmFtZSBvZiB0aGUgYXR0cmlidXRlIGJlaW5nIGNoYW5nZWQuXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRtYXliZVRvZ2dsZURyb3Bkb3duKCBwcm9wcywgYXR0cmlidXRlICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG5vLXNoYWRvd1xuXHRcdFx0XHRcdGNvbnN0IGZvcm1JZCA9IHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkO1xuXHRcdFx0XHRcdGNvbnN0IG1lbnUgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCBgI3dwZm9ybXMtZm9ybS0keyBmb3JtSWQgfSAuY2hvaWNlc19fbGlzdC5jaG9pY2VzX19saXN0LS1kcm9wZG93bmAgKTtcblx0XHRcdFx0XHRjb25zdCBjbGFzc2ljTWVudSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoIGAjd3Bmb3Jtcy1mb3JtLSR7IGZvcm1JZCB9IC53cGZvcm1zLWZpZWxkLXNlbGVjdC1zdHlsZS1jbGFzc2ljIHNlbGVjdGAgKTtcblxuXHRcdFx0XHRcdGlmICggYXR0cmlidXRlID09PSAnZmllbGRNZW51Q29sb3InICkge1xuXHRcdFx0XHRcdFx0aWYgKCBtZW51ICkge1xuXHRcdFx0XHRcdFx0XHRtZW51LmNsYXNzTGlzdC5hZGQoICdpcy1hY3RpdmUnICk7XG5cdFx0XHRcdFx0XHRcdG1lbnUucGFyZW50RWxlbWVudC5jbGFzc0xpc3QuYWRkKCAnaXMtb3BlbicgKTtcblx0XHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRcdHRoaXMuc2hvd0NsYXNzaWNNZW51KCBjbGFzc2ljTWVudSApO1xuXHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRjbGVhclRpbWVvdXQoIGRyb3Bkb3duVGltZW91dCApO1xuXG5cdFx0XHRcdFx0XHRkcm9wZG93blRpbWVvdXQgPSBzZXRUaW1lb3V0KCAoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdGNvbnN0IHRvQ2xvc2UgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCBgI3dwZm9ybXMtZm9ybS0keyBmb3JtSWQgfSAuY2hvaWNlc19fbGlzdC5jaG9pY2VzX19saXN0LS1kcm9wZG93bmAgKTtcblxuXHRcdFx0XHRcdFx0XHRpZiAoIHRvQ2xvc2UgKSB7XG5cdFx0XHRcdFx0XHRcdFx0dG9DbG9zZS5jbGFzc0xpc3QucmVtb3ZlKCAnaXMtYWN0aXZlJyApO1xuXHRcdFx0XHRcdFx0XHRcdHRvQ2xvc2UucGFyZW50RWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCAnaXMtb3BlbicgKTtcblx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHR0aGlzLmhpZGVDbGFzc2ljTWVudSggZG9jdW1lbnQucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLWZvcm0tJHsgZm9ybUlkIH0gLndwZm9ybXMtZmllbGQtc2VsZWN0LXN0eWxlLWNsYXNzaWMgc2VsZWN0YCApICk7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdH0sIDUwMDAgKTtcblx0XHRcdFx0XHR9IGVsc2UgaWYgKCBtZW51ICkge1xuXHRcdFx0XHRcdFx0bWVudS5jbGFzc0xpc3QucmVtb3ZlKCAnaXMtYWN0aXZlJyApO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHR0aGlzLmhpZGVDbGFzc2ljTWVudSggY2xhc3NpY01lbnUgKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0sXG5cblx0XHRcdFx0LyoqXG5cdFx0XHRcdCAqIFNob3dzIHRoZSBjbGFzc2ljIG1lbnUuXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gY2xhc3NpY01lbnUgVGhlIGNsYXNzaWMgbWVudS5cblx0XHRcdFx0ICovXG5cdFx0XHRcdHNob3dDbGFzc2ljTWVudSggY2xhc3NpY01lbnUgKSB7XG5cdFx0XHRcdFx0aWYgKCAhIGNsYXNzaWNNZW51ICkge1xuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnNpemUgPSAyO1xuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnN0eWxlLmNzc1RleHQgPSAncGFkZGluZy10b3A6IDQwcHg7IHBhZGRpbmctaW5saW5lLWVuZDogMDsgcGFkZGluZy1pbmxpbmUtc3RhcnQ6IDA7IHBvc2l0aW9uOiByZWxhdGl2ZTsnO1xuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnF1ZXJ5U2VsZWN0b3JBbGwoICdvcHRpb24nICkuZm9yRWFjaCggKCBvcHRpb24gKSA9PiB7XG5cdFx0XHRcdFx0XHRvcHRpb24uc3R5bGUuY3NzVGV4dCA9ICdib3JkZXItbGVmdDogMXB4IHNvbGlkICM4YzhmOTQ7IGJvcmRlci1yaWdodDogMXB4IHNvbGlkICM4YzhmOTQ7IHBhZGRpbmc6IDAgMTBweDsgei1pbmRleDogOTk5OTk5OyBwb3NpdGlvbjogcmVsYXRpdmU7Jztcblx0XHRcdFx0XHR9ICk7XG5cdFx0XHRcdFx0Y2xhc3NpY01lbnUucXVlcnlTZWxlY3RvciggJ29wdGlvbjpsYXN0LWNoaWxkJyApLnN0eWxlLmNzc1RleHQgPSAnYm9yZGVyLWJvdHRvbS1sZWZ0LXJhZGl1czogNHB4OyBib3JkZXItYm90dG9tLXJpZ2h0LXJhZGl1czogNHB4OyBwYWRkaW5nOiAwIDEwcHg7IGJvcmRlci1sZWZ0OiAxcHggc29saWQgIzhjOGY5NDsgYm9yZGVyLXJpZ2h0OiAxcHggc29saWQgIzhjOGY5NDsgYm9yZGVyLWJvdHRvbTogMXB4IHNvbGlkICM4YzhmOTQ7IHotaW5kZXg6IDk5OTk5OTsgcG9zaXRpb246IHJlbGF0aXZlOyc7XG5cdFx0XHRcdH0sXG5cblx0XHRcdFx0LyoqXG5cdFx0XHRcdCAqIEhpZGVzIHRoZSBjbGFzc2ljIG1lbnUuXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAcGFyYW0ge09iamVjdH0gY2xhc3NpY01lbnUgVGhlIGNsYXNzaWMgbWVudS5cblx0XHRcdFx0ICovXG5cdFx0XHRcdGhpZGVDbGFzc2ljTWVudSggY2xhc3NpY01lbnUgKSB7XG5cdFx0XHRcdFx0aWYgKCAhIGNsYXNzaWNNZW51ICkge1xuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnNpemUgPSAwO1xuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnN0eWxlLmNzc1RleHQgPSAncGFkZGluZy10b3A6IDA7IHBhZGRpbmctaW5saW5lLWVuZDogMjRweDsgcGFkZGluZy1pbmxpbmUtc3RhcnQ6IDEycHg7IHBvc2l0aW9uOiByZWxhdGl2ZTsnO1xuXHRcdFx0XHRcdGNsYXNzaWNNZW51LnF1ZXJ5U2VsZWN0b3JBbGwoICdvcHRpb24nICkuZm9yRWFjaCggKCBvcHRpb24gKSA9PiB7XG5cdFx0XHRcdFx0XHRvcHRpb24uc3R5bGUuY3NzVGV4dCA9ICdib3JkZXI6IG5vbmU7Jztcblx0XHRcdFx0XHR9ICk7XG5cdFx0XHRcdH0sXG5cblx0XHRcdFx0LyoqXG5cdFx0XHRcdCAqIEZpZWxkIHJlZ3VsYXIgYXR0cmlidXRlIGNoYW5nZSBldmVudCBoYW5kbGVyLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IGF0dHJpYnV0ZSBBdHRyaWJ1dGUgbmFtZS5cblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlICAgICBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0YXR0ckNoYW5nZSggYXR0cmlidXRlLCB2YWx1ZSApIHtcblx0XHRcdFx0XHRjb25zdCBzZXRBdHRyID0ge307XG5cblx0XHRcdFx0XHRzZXRBdHRyWyBhdHRyaWJ1dGUgXSA9IHZhbHVlO1xuXG5cdFx0XHRcdFx0YXBwLnNldEJsb2NrUnVudGltZVN0YXRlVmFyKCBwcm9wcy5jbGllbnRJZCwgJ3ByZXZBdHRyaWJ1dGVzU3RhdGUnLCBwcm9wcy5hdHRyaWJ1dGVzICk7XG5cdFx0XHRcdFx0cHJvcHMuc2V0QXR0cmlidXRlcyggc2V0QXR0ciApO1xuXG5cdFx0XHRcdFx0dHJpZ2dlclNlcnZlclJlbmRlciA9IHRydWU7XG5cblx0XHRcdFx0XHR0aGlzLnVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQoKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogVXBkYXRlIGNvbnRlbnQgb2YgdGhlIFwiQ29weS9QYXN0ZVwiIGZpZWxkcy5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHR1cGRhdGVDb3B5UGFzdGVDb250ZW50KCkge1xuXHRcdFx0XHRcdGNvbnN0IGNvbnRlbnQgPSB7fTtcblx0XHRcdFx0XHRjb25zdCBhdHRzID0gd3AuZGF0YS5zZWxlY3QoICdjb3JlL2Jsb2NrLWVkaXRvcicgKS5nZXRCbG9ja0F0dHJpYnV0ZXMoIHByb3BzLmNsaWVudElkICk7XG5cblx0XHRcdFx0XHRmb3IgKCBjb25zdCBrZXkgaW4gZGVmYXVsdFN0eWxlU2V0dGluZ3MgKSB7XG5cdFx0XHRcdFx0XHRjb250ZW50WyBrZXkgXSA9IGF0dHNbIGtleSBdO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHsgY29weVBhc3RlSnNvblZhbHVlOiBKU09OLnN0cmluZ2lmeSggY29udGVudCApIH0gKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogUGFzdGUgc2V0dGluZ3MgaGFuZGxlci5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0cGFzdGVTZXR0aW5ncyggdmFsdWUgKSB7XG5cdFx0XHRcdFx0dmFsdWUgPSB2YWx1ZS50cmltKCk7XG5cblx0XHRcdFx0XHRjb25zdCBwYXN0ZUF0dHJpYnV0ZXMgPSBhcHAucGFyc2VWYWxpZGF0ZUpzb24oIHZhbHVlICk7XG5cblx0XHRcdFx0XHRpZiAoICEgcGFzdGVBdHRyaWJ1dGVzICkge1xuXHRcdFx0XHRcdFx0aWYgKCB2YWx1ZSApIHtcblx0XHRcdFx0XHRcdFx0d3AuZGF0YS5kaXNwYXRjaCggJ2NvcmUvbm90aWNlcycgKS5jcmVhdGVFcnJvck5vdGljZShcblx0XHRcdFx0XHRcdFx0XHRzdHJpbmdzLmNvcHlfcGFzdGVfZXJyb3IsXG5cdFx0XHRcdFx0XHRcdFx0eyBpZDogJ3dwZm9ybXMtanNvbi1wYXJzZS1lcnJvcicgfVxuXHRcdFx0XHRcdFx0XHQpO1xuXHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHR0aGlzLnVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQoKTtcblxuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHBhc3RlQXR0cmlidXRlcy5jb3B5UGFzdGVKc29uVmFsdWUgPSB2YWx1ZTtcblxuXHRcdFx0XHRcdGNvbnN0IHRoZW1lU2x1ZyA9IGFwcC5wYW5lbHMudGhlbWVzLm1heWJlQ3JlYXRlQ3VzdG9tVGhlbWVGcm9tQXR0cmlidXRlcyggcGFzdGVBdHRyaWJ1dGVzICk7XG5cblx0XHRcdFx0XHRhcHAuc2V0QmxvY2tSdW50aW1lU3RhdGVWYXIoIHByb3BzLmNsaWVudElkLCAncHJldkF0dHJpYnV0ZXNTdGF0ZScsIHByb3BzLmF0dHJpYnV0ZXMgKTtcblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCBwYXN0ZUF0dHJpYnV0ZXMgKTtcblx0XHRcdFx0XHRhcHAucGFuZWxzLnRoZW1lcy5zZXRCbG9ja1RoZW1lKCBwcm9wcywgdGhlbWVTbHVnICk7XG5cblx0XHRcdFx0XHR0cmlnZ2VyU2VydmVyUmVuZGVyID0gZmFsc2U7XG5cdFx0XHRcdH0sXG5cdFx0XHR9O1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBQYXJzZSBhbmQgdmFsaWRhdGUgSlNPTiBzdHJpbmcuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSBKU09OIHN0cmluZy5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW58b2JqZWN0fSBQYXJzZWQgSlNPTiBvYmplY3QgT1IgZmFsc2Ugb24gZXJyb3IuXG5cdFx0ICovXG5cdFx0cGFyc2VWYWxpZGF0ZUpzb24oIHZhbHVlICkge1xuXHRcdFx0aWYgKCB0eXBlb2YgdmFsdWUgIT09ICdzdHJpbmcnICkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdGxldCBhdHRzO1xuXG5cdFx0XHR0cnkge1xuXHRcdFx0XHRhdHRzID0gSlNPTi5wYXJzZSggdmFsdWUudHJpbSgpICk7XG5cdFx0XHR9IGNhdGNoICggZXJyb3IgKSB7XG5cdFx0XHRcdGF0dHMgPSBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGF0dHM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBXUEZvcm1zIGljb24gRE9NIGVsZW1lbnQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge0RPTS5lbGVtZW50fSBXUEZvcm1zIGljb24gRE9NIGVsZW1lbnQuXG5cdFx0ICovXG5cdFx0Z2V0SWNvbigpIHtcblx0XHRcdHJldHVybiBjcmVhdGVFbGVtZW50KFxuXHRcdFx0XHQnc3ZnJyxcblx0XHRcdFx0eyB3aWR0aDogMjAsIGhlaWdodDogMjAsIHZpZXdCb3g6ICcwIDAgNjEyIDYxMicsIGNsYXNzTmFtZTogJ2Rhc2hpY29uJyB9LFxuXHRcdFx0XHRjcmVhdGVFbGVtZW50KFxuXHRcdFx0XHRcdCdwYXRoJyxcblx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRmaWxsOiAnY3VycmVudENvbG9yJyxcblx0XHRcdFx0XHRcdGQ6ICdNNTQ0LDBINjhDMzAuNDQ1LDAsMCwzMC40NDUsMCw2OHY0NzZjMCwzNy41NTYsMzAuNDQ1LDY4LDY4LDY4aDQ3NmMzNy41NTYsMCw2OC0zMC40NDQsNjgtNjhWNjggQzYxMiwzMC40NDUsNTgxLjU1NiwwLDU0NCwweiBNNDY0LjQ0LDY4TDM4Ny42LDEyMC4wMkwzMjMuMzQsNjhINDY0LjQ0eiBNMjg4LjY2LDY4bC02NC4yNiw1Mi4wMkwxNDcuNTYsNjhIMjg4LjY2eiBNNTQ0LDU0NEg2OCBWNjhoMjIuMWwxMzYsOTIuMTRsNzkuOS02NC42bDc5LjU2LDY0LjZsMTM2LTkyLjE0SDU0NFY1NDR6IE0xMTQuMjQsMjYzLjE2aDk1Ljg4di00OC4yOGgtOTUuODhWMjYzLjE2eiBNMTE0LjI0LDM2MC40aDk1Ljg4IHYtNDguNjJoLTk1Ljg4VjM2MC40eiBNMjQyLjc2LDM2MC40aDI1NXYtNDguNjJoLTI1NVYzNjAuNEwyNDIuNzYsMzYwLjR6IE0yNDIuNzYsMjYzLjE2aDI1NXYtNDguMjhoLTI1NVYyNjMuMTZMMjQyLjc2LDI2My4xNnogTTM2OC4yMiw0NTcuM2gxMjkuNTRWNDA4SDM2OC4yMlY0NTcuM3onLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdCksXG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgV1BGb3JtcyBibG9ja3MuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge0FycmF5fSBCbG9ja3MgYXJyYXkuXG5cdFx0ICovXG5cdFx0Z2V0V1BGb3Jtc0Jsb2NrcygpIHtcblx0XHRcdGNvbnN0IHdwZm9ybXNCbG9ja3MgPSB3cC5kYXRhLnNlbGVjdCggJ2NvcmUvYmxvY2stZWRpdG9yJyApLmdldEJsb2NrcygpO1xuXG5cdFx0XHRyZXR1cm4gd3Bmb3Jtc0Jsb2Nrcy5maWx0ZXIoICggcHJvcHMgKSA9PiB7XG5cdFx0XHRcdHJldHVybiBwcm9wcy5uYW1lID09PSAnd3Bmb3Jtcy9mb3JtLXNlbGVjdG9yJztcblx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IFdQRm9ybXMgYmxvY2tzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gQmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKi9cblx0XHRpc0NsaWVudElkQXR0clVuaXF1ZSggcHJvcHMgKSB7XG5cdFx0XHRjb25zdCB3cGZvcm1zQmxvY2tzID0gYXBwLmdldFdQRm9ybXNCbG9ja3MoKTtcblxuXHRcdFx0Zm9yICggY29uc3Qga2V5IGluIHdwZm9ybXNCbG9ja3MgKSB7XG5cdFx0XHRcdC8vIFNraXAgdGhlIGN1cnJlbnQgYmxvY2suXG5cdFx0XHRcdGlmICggd3Bmb3Jtc0Jsb2Nrc1sga2V5IF0uY2xpZW50SWQgPT09IHByb3BzLmNsaWVudElkICkge1xuXHRcdFx0XHRcdGNvbnRpbnVlO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0aWYgKCB3cGZvcm1zQmxvY2tzWyBrZXkgXS5hdHRyaWJ1dGVzLmNsaWVudElkID09PSBwcm9wcy5hdHRyaWJ1dGVzLmNsaWVudElkICkge1xuXHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gQmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKi9cblx0XHRnZXRCbG9ja0F0dHJpYnV0ZXMoKSB7XG5cdFx0XHRyZXR1cm4gY29tbW9uQXR0cmlidXRlcztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIHJ1bnRpbWUgc3RhdGUgdmFyaWFibGUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBjbGllbnRJZCBCbG9jayBjbGllbnQgSUQuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhck5hbWUgIEJsb2NrIHJ1bnRpbWUgdmFyaWFibGUgbmFtZS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4geyp9IEJsb2NrIHJ1bnRpbWUgc3RhdGUgdmFyaWFibGUgdmFsdWUuXG5cdFx0ICovXG5cdFx0Z2V0QmxvY2tSdW50aW1lU3RhdGVWYXIoIGNsaWVudElkLCB2YXJOYW1lICkge1xuXHRcdFx0cmV0dXJuIGJsb2Nrc1sgY2xpZW50SWQgXT8uWyB2YXJOYW1lIF07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBibG9jayBydW50aW1lIHN0YXRlIHZhcmlhYmxlIHZhbHVlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gY2xpZW50SWQgQmxvY2sgY2xpZW50IElELlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YXJOYW1lICBCbG9jayBydW50aW1lIHN0YXRlIGtleS5cblx0XHQgKiBAcGFyYW0geyp9ICAgICAgdmFsdWUgICAgU3RhdGUgdmFyaWFibGUgdmFsdWUuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIG9uIHN1Y2Nlc3MuXG5cdFx0ICovXG5cdFx0c2V0QmxvY2tSdW50aW1lU3RhdGVWYXIoIGNsaWVudElkLCB2YXJOYW1lLCB2YWx1ZSApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBjb21wbGV4aXR5XG5cdFx0XHRpZiAoICEgY2xpZW50SWQgfHwgISB2YXJOYW1lICkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXSA9IGJsb2Nrc1sgY2xpZW50SWQgXSB8fCB7fTtcblx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXVsgdmFyTmFtZSBdID0gdmFsdWU7XG5cblx0XHRcdC8vIFByZXZlbnQgcmVmZXJlbmNpbmcgdG8gb2JqZWN0LlxuXHRcdFx0aWYgKCB0eXBlb2YgdmFsdWUgPT09ICdvYmplY3QnICYmICEgQXJyYXkuaXNBcnJheSggdmFsdWUgKSAmJiB2YWx1ZSAhPT0gbnVsbCApIHtcblx0XHRcdFx0YmxvY2tzWyBjbGllbnRJZCBdWyB2YXJOYW1lIF0gPSB7IC4uLnZhbHVlIH07XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgZm9ybSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtBcnJheX0gRm9ybSBvcHRpb25zLlxuXHRcdCAqL1xuXHRcdGdldEZvcm1PcHRpb25zKCkge1xuXHRcdFx0Y29uc3QgZm9ybU9wdGlvbnMgPSBmb3JtTGlzdC5tYXAoICggdmFsdWUgKSA9PiAoXG5cdFx0XHRcdHsgdmFsdWU6IHZhbHVlLklELCBsYWJlbDogdmFsdWUucG9zdF90aXRsZSB9XG5cdFx0XHQpICk7XG5cblx0XHRcdGZvcm1PcHRpb25zLnVuc2hpZnQoIHsgdmFsdWU6ICcnLCBsYWJlbDogc3RyaW5ncy5mb3JtX3NlbGVjdCB9ICk7XG5cblx0XHRcdHJldHVybiBmb3JtT3B0aW9ucztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IHNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7QXJyYXl9IFNpemUgb3B0aW9ucy5cblx0XHQgKi9cblx0XHRnZXRTaXplT3B0aW9ucygpIHtcblx0XHRcdHJldHVybiBbXG5cdFx0XHRcdHtcblx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5zbWFsbCxcblx0XHRcdFx0XHR2YWx1ZTogJ3NtYWxsJyxcblx0XHRcdFx0fSxcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLm1lZGl1bSxcblx0XHRcdFx0XHR2YWx1ZTogJ21lZGl1bScsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdHtcblx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5sYXJnZSxcblx0XHRcdFx0XHR2YWx1ZTogJ2xhcmdlJyxcblx0XHRcdFx0fSxcblx0XHRcdF07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEV2ZW50IGB3cGZvcm1zRm9ybVNlbGVjdG9yRWRpdGAgaGFuZGxlci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGUgICAgIEV2ZW50IG9iamVjdC5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKi9cblx0XHRibG9ja0VkaXQoIGUsIHByb3BzICkge1xuXHRcdFx0Y29uc3QgYmxvY2sgPSBhcHAuZ2V0QmxvY2tDb250YWluZXIoIHByb3BzICk7XG5cblx0XHRcdGlmICggISBibG9jaz8uZGF0YXNldCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRhcHAuaW5pdExlYWRGb3JtU2V0dGluZ3MoIGJsb2NrLnBhcmVudEVsZW1lbnQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBMZWFkIEZvcm0gU2V0dGluZ3MgcGFuZWxzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0VsZW1lbnR9IGJsb2NrICAgICAgICAgQmxvY2sgZWxlbWVudC5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gIGJsb2NrLmRhdGFzZXQgQmxvY2sgZWxlbWVudC5cblx0XHQgKi9cblx0XHRpbml0TGVhZEZvcm1TZXR0aW5ncyggYmxvY2sgKSB7XG5cdFx0XHRpZiAoICEgYmxvY2s/LmRhdGFzZXQgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAhIGFwcC5pc0Z1bGxTdHlsaW5nRW5hYmxlZCgpICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IGNsaWVudElkID0gYmxvY2suZGF0YXNldC5ibG9jaztcblx0XHRcdGNvbnN0ICRwYW5lbCA9ICQoIGAud3Bmb3Jtcy1ibG9jay1zZXR0aW5ncy0keyBjbGllbnRJZCB9YCApO1xuXG5cdFx0XHRpZiAoIGFwcC5pc0xlYWRGb3Jtc0VuYWJsZWQoIGJsb2NrICkgKSB7XG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5hZGRDbGFzcyggJ2Rpc2FibGVkX3BhbmVsJyApXG5cdFx0XHRcdFx0LmZpbmQoICcud3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlLndwZm9ybXMtbGVhZC1mb3JtLW5vdGljZScgKVxuXHRcdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ2Jsb2NrJyApO1xuXG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5maW5kKCAnLndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZS53cGZvcm1zLXVzZS1tb2Rlcm4tbm90aWNlJyApXG5cdFx0XHRcdFx0LmNzcyggJ2Rpc3BsYXknLCAnbm9uZScgKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQucmVtb3ZlQ2xhc3MoICdkaXNhYmxlZF9wYW5lbCcgKVxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy1sZWFkLWZvcm0tbm90aWNlJyApXG5cdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ25vbmUnICk7XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy11c2UtbW9kZXJuLW5vdGljZScgKVxuXHRcdFx0XHQuY3NzKCAnZGlzcGxheScsIG51bGwgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JGb3JtTG9hZGVkYCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZSBFdmVudCBvYmplY3QuXG5cdFx0ICovXG5cdFx0Zm9ybUxvYWRlZCggZSApIHtcblx0XHRcdGFwcC5pbml0TGVhZEZvcm1TZXR0aW5ncyggZS5kZXRhaWwuYmxvY2sgKTtcblx0XHRcdGFwcC51cGRhdGVBY2NlbnRDb2xvcnMoIGUuZGV0YWlsICk7XG5cdFx0XHRhcHAubG9hZENob2ljZXNKUyggZS5kZXRhaWwgKTtcblx0XHRcdGFwcC5pbml0UmljaFRleHRGaWVsZCggZS5kZXRhaWwuZm9ybUlkICk7XG5cdFx0XHRhcHAuaW5pdFJlcGVhdGVyRmllbGQoIGUuZGV0YWlsLmZvcm1JZCApO1xuXG5cdFx0XHQkKCBlLmRldGFpbC5ibG9jayApXG5cdFx0XHRcdC5vZmYoICdjbGljaycgKVxuXHRcdFx0XHQub24oICdjbGljaycsIGFwcC5ibG9ja0NsaWNrICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENsaWNrIG9uIHRoZSBibG9jayBldmVudCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZSBFdmVudCBvYmplY3QuXG5cdFx0ICovXG5cdFx0YmxvY2tDbGljayggZSApIHtcblx0XHRcdGFwcC5pbml0TGVhZEZvcm1TZXR0aW5ncyggZS5jdXJyZW50VGFyZ2V0ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFVwZGF0ZSBhY2NlbnQgY29sb3JzIG9mIHNvbWUgZmllbGRzIGluIEdCIGJsb2NrIGluIE1vZGVybiBNYXJrdXAgbW9kZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGRldGFpbCBFdmVudCBkZXRhaWxzIG9iamVjdC5cblx0XHQgKi9cblx0XHR1cGRhdGVBY2NlbnRDb2xvcnMoIGRldGFpbCApIHtcblx0XHRcdGlmIChcblx0XHRcdFx0ISB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmlzX21vZGVybl9tYXJrdXAgfHxcblx0XHRcdFx0ISB3aW5kb3cuV1BGb3Jtcz8uRnJvbnRlbmRNb2Rlcm4gfHxcblx0XHRcdFx0ISBkZXRhaWw/LmJsb2NrXG5cdFx0XHQpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCAkZm9ybSA9ICQoIGRldGFpbC5ibG9jay5xdWVyeVNlbGVjdG9yKCBgI3dwZm9ybXMtJHsgZGV0YWlsLmZvcm1JZCB9YCApICksXG5cdFx0XHRcdEZyb250ZW5kTW9kZXJuID0gd2luZG93LldQRm9ybXMuRnJvbnRlbmRNb2Rlcm47XG5cblx0XHRcdEZyb250ZW5kTW9kZXJuLnVwZGF0ZUdCQmxvY2tQYWdlSW5kaWNhdG9yQ29sb3IoICRmb3JtICk7XG5cdFx0XHRGcm9udGVuZE1vZGVybi51cGRhdGVHQkJsb2NrSWNvbkNob2ljZXNDb2xvciggJGZvcm0gKTtcblx0XHRcdEZyb250ZW5kTW9kZXJuLnVwZGF0ZUdCQmxvY2tSYXRpbmdDb2xvciggJGZvcm0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBNb2Rlcm4gc3R5bGUgRHJvcGRvd24gZmllbGRzICg8c2VsZWN0PikuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBkZXRhaWwgRXZlbnQgZGV0YWlscyBvYmplY3QuXG5cdFx0ICovXG5cdFx0bG9hZENob2ljZXNKUyggZGV0YWlsICkge1xuXHRcdFx0aWYgKCB0eXBlb2Ygd2luZG93LkNob2ljZXMgIT09ICdmdW5jdGlvbicgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgJGZvcm0gPSAkKCBkZXRhaWwuYmxvY2sucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLSR7IGRldGFpbC5mb3JtSWQgfWAgKSApO1xuXG5cdFx0XHQkZm9ybS5maW5kKCAnLmNob2ljZXNqcy1zZWxlY3QnICkuZWFjaCggZnVuY3Rpb24oIGlkeCwgc2VsZWN0RWwgKSB7XG5cdFx0XHRcdGNvbnN0ICRlbCA9ICQoIHNlbGVjdEVsICk7XG5cblx0XHRcdFx0aWYgKCAkZWwuZGF0YSggJ2Nob2ljZScgKSA9PT0gJ2FjdGl2ZScgKSB7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0Y29uc3QgYXJncyA9IHdpbmRvdy53cGZvcm1zX2Nob2ljZXNqc19jb25maWcgfHwge30sXG5cdFx0XHRcdFx0c2VhcmNoRW5hYmxlZCA9ICRlbC5kYXRhKCAnc2VhcmNoLWVuYWJsZWQnICksXG5cdFx0XHRcdFx0JGZpZWxkID0gJGVsLmNsb3Nlc3QoICcud3Bmb3Jtcy1maWVsZCcgKTtcblxuXHRcdFx0XHRhcmdzLnNlYXJjaEVuYWJsZWQgPSAndW5kZWZpbmVkJyAhPT0gdHlwZW9mIHNlYXJjaEVuYWJsZWQgPyBzZWFyY2hFbmFibGVkIDogdHJ1ZTtcblx0XHRcdFx0YXJncy5jYWxsYmFja09uSW5pdCA9IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdGNvbnN0IHNlbGYgPSB0aGlzLFxuXHRcdFx0XHRcdFx0JGVsZW1lbnQgPSAkKCBzZWxmLnBhc3NlZEVsZW1lbnQuZWxlbWVudCApLFxuXHRcdFx0XHRcdFx0JGlucHV0ID0gJCggc2VsZi5pbnB1dC5lbGVtZW50ICksXG5cdFx0XHRcdFx0XHRzaXplQ2xhc3MgPSAkZWxlbWVudC5kYXRhKCAnc2l6ZS1jbGFzcycgKTtcblxuXHRcdFx0XHRcdC8vIEFkZCBDU1MtY2xhc3MgZm9yIHNpemUuXG5cdFx0XHRcdFx0aWYgKCBzaXplQ2xhc3MgKSB7XG5cdFx0XHRcdFx0XHQkKCBzZWxmLmNvbnRhaW5lck91dGVyLmVsZW1lbnQgKS5hZGRDbGFzcyggc2l6ZUNsYXNzICk7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0LyoqXG5cdFx0XHRcdFx0ICogSWYgYSBtdWx0aXBsZSBzZWxlY3QgaGFzIHNlbGVjdGVkIGNob2ljZXMgLSBoaWRlIGEgcGxhY2Vob2xkZXIgdGV4dC5cblx0XHRcdFx0XHQgKiBJbiBjYXNlIGlmIHNlbGVjdCBpcyBlbXB0eSAtIHdlIHJldHVybiBwbGFjZWhvbGRlciB0ZXh0LlxuXHRcdFx0XHRcdCAqL1xuXHRcdFx0XHRcdGlmICggJGVsZW1lbnQucHJvcCggJ211bHRpcGxlJyApICkge1xuXHRcdFx0XHRcdFx0Ly8gT24gaW5pdCBldmVudC5cblx0XHRcdFx0XHRcdCRpbnB1dC5kYXRhKCAncGxhY2Vob2xkZXInLCAkaW5wdXQuYXR0ciggJ3BsYWNlaG9sZGVyJyApICk7XG5cblx0XHRcdFx0XHRcdGlmICggc2VsZi5nZXRWYWx1ZSggdHJ1ZSApLmxlbmd0aCApIHtcblx0XHRcdFx0XHRcdFx0JGlucHV0LmhpZGUoKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHR0aGlzLmRpc2FibGUoKTtcblx0XHRcdFx0XHQkZmllbGQuZmluZCggJy5pcy1kaXNhYmxlZCcgKS5yZW1vdmVDbGFzcyggJ2lzLWRpc2FibGVkJyApO1xuXHRcdFx0XHR9O1xuXG5cdFx0XHRcdHRyeSB7XG5cdFx0XHRcdFx0aWYgKCAhICggc2VsZWN0RWwgaW5zdGFuY2VvZiBwYXJlbnQuSFRNTFNlbGVjdEVsZW1lbnQgKSApIHtcblx0XHRcdFx0XHRcdE9iamVjdC5zZXRQcm90b3R5cGVPZiggc2VsZWN0RWwsIHBhcmVudC5IVE1MU2VsZWN0RWxlbWVudC5wcm90b3R5cGUgKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQkZWwuZGF0YSggJ2Nob2ljZXNqcycsIG5ldyBwYXJlbnQuQ2hvaWNlcyggc2VsZWN0RWwsIGFyZ3MgKSApO1xuXHRcdFx0XHR9IGNhdGNoICggZSApIHt9IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbm8tZW1wdHlcblx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdGlhbGl6ZSBSaWNoVGV4dCBmaWVsZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtudW1iZXJ9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqL1xuXHRcdGluaXRSaWNoVGV4dEZpZWxkKCBmb3JtSWQgKSB7XG5cdFx0XHRjb25zdCBmb3JtID0gYXBwLmdldEZvcm1CbG9jayggZm9ybUlkICk7XG5cblx0XHRcdGlmICggISBmb3JtICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFNldCBkZWZhdWx0IHRhYiB0byBgVmlzdWFsYC5cblx0XHRcdCQoIGZvcm0gKS5maW5kKCAnLndwLWVkaXRvci13cmFwJyApLnJlbW92ZUNsYXNzKCAnaHRtbC1hY3RpdmUnICkuYWRkQ2xhc3MoICd0bWNlLWFjdGl2ZScgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdGlhbGl6ZSBSZXBlYXRlciBmaWVsZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtudW1iZXJ9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqL1xuXHRcdGluaXRSZXBlYXRlckZpZWxkKCBmb3JtSWQgKSB7XG5cdFx0XHRjb25zdCBmb3JtID0gYXBwLmdldEZvcm1CbG9jayggZm9ybUlkICk7XG5cblx0XHRcdGlmICggISBmb3JtICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0ICRyb3dCdXR0b25zID0gJCggZm9ybSApLmZpbmQoICcud3Bmb3Jtcy1maWVsZC1yZXBlYXRlciA+IC53cGZvcm1zLWZpZWxkLXJlcGVhdGVyLWRpc3BsYXktcm93cyAud3Bmb3Jtcy1maWVsZC1yZXBlYXRlci1kaXNwbGF5LXJvd3MtYnV0dG9ucycgKTtcblxuXHRcdFx0Ly8gR2V0IHRoZSBsYWJlbCBoZWlnaHQgYW5kIHNldCB0aGUgYnV0dG9uIHBvc2l0aW9uLlxuXHRcdFx0JHJvd0J1dHRvbnMuZWFjaCggZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGNvbnN0ICRjb250ID0gJCggdGhpcyApO1xuXHRcdFx0XHRjb25zdCAkbGFiZWxzID0gJGNvbnQuc2libGluZ3MoICcud3Bmb3Jtcy1sYXlvdXQtY29sdW1uJyApXG5cdFx0XHRcdFx0LmZpbmQoICcud3Bmb3Jtcy1maWVsZCcgKVxuXHRcdFx0XHRcdC5maW5kKCAnLndwZm9ybXMtZmllbGQtbGFiZWwnICk7XG5cblx0XHRcdFx0aWYgKCAhICRsYWJlbHMubGVuZ3RoICkge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGNvbnN0ICRsYWJlbCA9ICRsYWJlbHMuZmlyc3QoKTtcblx0XHRcdFx0Y29uc3QgbGFiZWxTdHlsZSA9IHdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKCAkbGFiZWwuZ2V0KCAwICkgKTtcblx0XHRcdFx0Y29uc3QgbWFyZ2luID0gbGFiZWxTdHlsZT8uZ2V0UHJvcGVydHlWYWx1ZSggJy0td3Bmb3Jtcy1maWVsZC1zaXplLWlucHV0LXNwYWNpbmcnICkgfHwgMDtcblx0XHRcdFx0Y29uc3QgaGVpZ2h0ID0gJGxhYmVsLm91dGVySGVpZ2h0KCkgfHwgMDtcblx0XHRcdFx0Y29uc3QgdG9wID0gaGVpZ2h0ICsgcGFyc2VJbnQoIG1hcmdpbiwgMTAgKSArIDEwO1xuXG5cdFx0XHRcdCRjb250LmNzcyggeyB0b3AgfSApO1xuXHRcdFx0fSApO1xuXG5cdFx0XHQvLyBJbml0IGJ1dHRvbnMgYW5kIGRlc2NyaXB0aW9ucyBmb3IgZWFjaCByZXBlYXRlciBpbiBlYWNoIGZvcm0uXG5cdFx0XHQkKCBgLndwZm9ybXMtZm9ybVtkYXRhLWZvcm1pZD1cIiR7IGZvcm1JZCB9XCJdYCApLmVhY2goIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRjb25zdCAkcmVwZWF0ZXIgPSAkKCB0aGlzICkuZmluZCggJy53cGZvcm1zLWZpZWxkLXJlcGVhdGVyJyApO1xuXG5cdFx0XHRcdCRyZXBlYXRlci5maW5kKCAnLndwZm9ybXMtZmllbGQtcmVwZWF0ZXItZGlzcGxheS1yb3dzLWJ1dHRvbnMnICkuYWRkQ2xhc3MoICd3cGZvcm1zLWluaXQnICk7XG5cdFx0XHRcdCRyZXBlYXRlci5maW5kKCAnLndwZm9ybXMtZmllbGQtcmVwZWF0ZXItZGlzcGxheS1yb3dzOmxhc3QgLndwZm9ybXMtZmllbGQtZGVzY3JpcHRpb24nICkuYWRkQ2xhc3MoICd3cGZvcm1zLWluaXQnICk7XG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEhhbmRsZSB0aGVtZSBjaGFuZ2UuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS45LjNcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqL1xuXHRcdG9uU2V0VGhlbWUoIHByb3BzICkge1xuXHRcdFx0YmFja2dyb3VuZFNlbGVjdGVkID0gcHJvcHMuYXR0cmlidXRlcy5iYWNrZ3JvdW5kSW1hZ2UgIT09ICd1cmwoKSc7XG5cdFx0fSxcblx0fTtcblxuXHQvLyBQcm92aWRlIGFjY2VzcyB0byBwdWJsaWMgZnVuY3Rpb25zL3Byb3BlcnRpZXMuXG5cdHJldHVybiBhcHA7XG59KCBkb2N1bWVudCwgd2luZG93LCBqUXVlcnkgKSApO1xuIl0sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7K0NBQ0EscUpBQUFBLG1CQUFBLFlBQUFBLG9CQUFBLFdBQUFDLENBQUEsU0FBQUMsQ0FBQSxFQUFBRCxDQUFBLE9BQUFFLENBQUEsR0FBQUMsTUFBQSxDQUFBQyxTQUFBLEVBQUFDLENBQUEsR0FBQUgsQ0FBQSxDQUFBSSxjQUFBLEVBQUFDLENBQUEsR0FBQUosTUFBQSxDQUFBSyxjQUFBLGNBQUFQLENBQUEsRUFBQUQsQ0FBQSxFQUFBRSxDQUFBLElBQUFELENBQUEsQ0FBQUQsQ0FBQSxJQUFBRSxDQUFBLENBQUFPLEtBQUEsS0FBQUMsQ0FBQSx3QkFBQUMsTUFBQSxHQUFBQSxNQUFBLE9BQUFDLENBQUEsR0FBQUYsQ0FBQSxDQUFBRyxRQUFBLGtCQUFBQyxDQUFBLEdBQUFKLENBQUEsQ0FBQUssYUFBQSx1QkFBQUMsQ0FBQSxHQUFBTixDQUFBLENBQUFPLFdBQUEsOEJBQUFDLE9BQUFqQixDQUFBLEVBQUFELENBQUEsRUFBQUUsQ0FBQSxXQUFBQyxNQUFBLENBQUFLLGNBQUEsQ0FBQVAsQ0FBQSxFQUFBRCxDQUFBLElBQUFTLEtBQUEsRUFBQVAsQ0FBQSxFQUFBaUIsVUFBQSxNQUFBQyxZQUFBLE1BQUFDLFFBQUEsU0FBQXBCLENBQUEsQ0FBQUQsQ0FBQSxXQUFBa0IsTUFBQSxtQkFBQWpCLENBQUEsSUFBQWlCLE1BQUEsWUFBQUEsT0FBQWpCLENBQUEsRUFBQUQsQ0FBQSxFQUFBRSxDQUFBLFdBQUFELENBQUEsQ0FBQUQsQ0FBQSxJQUFBRSxDQUFBLGdCQUFBb0IsS0FBQXJCLENBQUEsRUFBQUQsQ0FBQSxFQUFBRSxDQUFBLEVBQUFHLENBQUEsUUFBQUssQ0FBQSxHQUFBVixDQUFBLElBQUFBLENB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
},{}],18:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.border_color
 * @param strings.border_style
 * @param strings.border_width
 * @param strings.container_styles
 * @param strings.shadow_size
 */
/**
 * Gutenberg editor block.
 *
 * Container styles panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function ($) {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _ref = wp.blockEditor || wp.editor,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    PanelBody = _wp$components.PanelBody,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults;

  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Start the engine.
     *
     * @since 1.8.8
     */
    init: function init() {
      $(app.ready);
    },
    /**
     * Document ready.
     *
     * @since 1.8.8
     */
    ready: function ready() {
      app.events();
    },
    /**
     * Events.
     *
     * @since 1.8.8
     */
    events: function events() {},
    /**
     * Get block attributes.
     *
     * @since 1.8.8
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      return {
        containerPadding: {
          type: 'string',
          default: defaults.containerPadding
        },
        containerBorderStyle: {
          type: 'string',
          default: defaults.containerBorderStyle
        },
        containerBorderWidth: {
          type: 'string',
          default: defaults.containerBorderWidth
        },
        containerBorderColor: {
          type: 'string',
          default: defaults.containerBorderColor
        },
        containerBorderRadius: {
          type: 'string',
          default: defaults.containerBorderRadius
        },
        containerShadowSize: {
          type: 'string',
          default: defaults.containerShadowSize
        }
      };
    },
    /**
     * Get Container Styles panel JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props              Block properties.
     * @param {Object} handlers           Block handlers.
     * @param {Object} formSelectorCommon Common form selector functions.
     *
     * @param {Object} uiState UI state.
     *
     * @return {Object} Field styles JSX code.
     */
    getContainerStyles: function getContainerStyles(props, handlers, formSelectorCommon, uiState) {
      // eslint-disable-line max-lines-per-function, complexity
      var cssClass = formSelectorCommon.getPanelClass(props);
      var isNotDisabled = uiState.isNotDisabled;
      var isProEnabled = uiState.isProEnabled;
      if (!isNotDisabled) {
        cssClass += ' wpforms-gutenberg-panel-disabled';
      }
      return /*#__PURE__*/React.createElement(PanelBody, {
        className: cssClass,
        title: strings.container_styles
      }, /*#__PURE__*/React.createElement("div", {
        // eslint-disable-line jsx-a11y/no-static-element-interactions
        className: "wpforms-gutenberg-form-selector-panel-body",
        onClick: function onClick(event) {
          if (isNotDisabled) {
            return;
          }
          event.stopPropagation();
          if (!isProEnabled) {
            return formSelectorCommon.education.showProModal('container', strings.container_styles);
          }
          formSelectorCommon.education.showLicenseModal('container', strings.container_styles, 'container-styles');
        },
        onKeyDown: function onKeyDown(event) {
          if (isNotDisabled) {
            return;
          }
          event.stopPropagation();
          if (!isProEnabled) {
            return formSelectorCommon.education.showProModal('container', strings.container_styles);
          }
          formSelectorCommon.education.showLicenseModal('container', strings.container_styles, 'container-styles');
        }
      }, /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: "wpforms-gutenberg-form-selector-flex",
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.padding,
        tabIndex: isNotDisabled ? 0 : -1,
        value: props.attributes.containerPadding,
        min: 0,
        isUnitSelectTabbable: isNotDisabled,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('containerPadding', value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.border_style,
        tabIndex: isNotDisabled ? 0 : -1,
        value: props.attributes.containerBorderStyle,
        options: [{
          label: strings.none,
          value: 'none'
        }, {
          label: strings.solid,
          value: 'solid'
        }, {
          label: strings.dotted,
          value: 'dotted'
        }, {
          label: strings.dashed,
          value: 'dashed'
        }, {
          label: strings.double,
          value: 'double'
        }],
        onChange: function onChange(value) {
          return handlers.styleAttrChange('containerBorderStyle', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: "wpforms-gutenberg-form-selector-flex",
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.border_width,
        tabIndex: isNotDisabled ? 0 : -1,
        value: props.attributes.containerBorderStyle === 'none' ? '' : props.attributes.containerBorderWidth,
        min: 0,
        disabled: props.attributes.containerBorderStyle === 'none',
        isUnitSelectTabbable: isNotDisabled,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('containerBorderWidth', value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.border_radius,
        tabIndex: isNotDisabled ? 0 : -1,
        value: props.attributes.containerBorderRadius,
        min: 0,
        isUnitSelectTabbable: isNotDisabled,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('containerBorderRadius', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: "wpforms-gutenberg-form-selector-flex",
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.shadow_size,
        tabIndex: isNotDisabled ? 0 : -1,
        value: props.attributes.containerShadowSize,
        options: [{
          label: strings.none,
          value: 'none'
        }, {
          label: strings.small,
          value: 'small'
        }, {
          label: strings.medium,
          value: 'medium'
        }, {
          label: strings.large,
          value: 'large'
        }],
        onChange: function onChange(value) {
          return handlers.styleAttrChange('containerShadowSize', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: "wpforms-gutenberg-form-selector-flex",
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-control-label"
      }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
        __experimentalIsRenderedInSidebar: true,
        enableAlpha: true,
        showTitle: false,
        tabIndex: isNotDisabled ? 0 : -1,
        className: props.attributes.containerBorderStyle === 'none' ? 'wpforms-gutenberg-form-selector-color-panel wpforms-gutenberg-form-selector-color-panel-disabled' : 'wpforms-gutenberg-form-selector-color-panel',
        colorSettings: [{
          value: props.attributes.containerBorderColor,
          onChange: function onChange(value) {
            if (!isNotDisabled) {
              return;
            }
            handlers.styleAttrChange('containerBorderColor', value);
          },
          label: strings.border_color
        }]
      })))));
    }
  };
  return app;
}(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiJCIsIl9yZWYiLCJ3cCIsImJsb2NrRWRpdG9yIiwiZWRpdG9yIiwiUGFuZWxDb2xvclNldHRpbmdzIiwiX3dwJGNvbXBvbmVudHMiLCJjb21wb25lbnRzIiwiU2VsZWN0Q29udHJvbCIsIlBhbmVsQm9keSIsIkZsZXgiLCJGbGV4QmxvY2siLCJfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sIiwiX3dwZm9ybXNfZ3V0ZW5iZXJnX2ZvIiwid3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciIsInN0cmluZ3MiLCJkZWZhdWx0cyIsImFwcCIsImluaXQiLCJyZWFkeSIsImV2ZW50cyIsImdldEJsb2NrQXR0cmlidXRlcyIsImNvbnRhaW5lclBhZGRpbmciLCJ0eXBlIiwiY29udGFpbmVyQm9yZGVyU3R5bGUiLCJjb250YWluZXJCb3JkZXJXaWR0aCIsImNvbnRhaW5lckJvcmRlckNvbG9yIiwiY29udGFpbmVyQm9yZGVyUmFkaXVzIiwiY29udGFpbmVyU2hhZG93U2l6ZSIsImdldENvbnRhaW5lclN0eWxlcyIsInByb3BzIiwiaGFuZGxlcnMiLCJmb3JtU2VsZWN0b3JDb21tb24iLCJ1aVN0YXRlIiwiY3NzQ2xhc3MiLCJnZXRQYW5lbENsYXNzIiwiaXNOb3REaXNhYmxlZCIsImlzUHJvRW5hYmxlZCIsIlJlYWN0IiwiY3JlYXRlRWxlbWVudCIsImNsYXNzTmFtZSIsInRpdGxlIiwiY29udGFpbmVyX3N0eWxlcyIsIm9uQ2xpY2siLCJldmVudCIsInN0b3BQcm9wYWdhdGlvbiIsImVkdWNhdGlvbiIsInNob3dQcm9Nb2RhbCIsInNob3dMaWNlbnNlTW9kYWwiLCJvbktleURvd24iLCJnYXAiLCJhbGlnbiIsImp1c3RpZnkiLCJsYWJlbCIsInBhZGRpbmciLCJ0YWJJbmRleCIsInZhbHVlIiwiYXR0cmlidXRlcyIsIm1pbiIsImlzVW5pdFNlbGVjdFRhYmJhYmxlIiwib25DaGFuZ2UiLCJzdHlsZUF0dHJDaGFuZ2UiLCJib3JkZXJfc3R5bGUiLCJvcHRpb25zIiwibm9uZSIsInNvbGlkIiwiZG90dGVkIiwiZGFzaGVkIiwiZG91YmxlIiwiYm9yZGVyX3dpZHRoIiwiZGlzYWJsZWQiLCJib3JkZXJfcmFkaXVzIiwic2hhZG93X3NpemUiLCJzbWFsbCIsIm1lZGl1bSIsImxhcmdlIiwiY29sb3JzIiwiX19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyIiwiZW5hYmxlQWxwaGEiLCJzaG93VGl0bGUiLCJjb2xvclNldHRpbmdzIiwiYm9yZGVyX2NvbG9yIiwialF1ZXJ5Il0sInNvdXJjZXMiOlsiY29udGFpbmVyLXN0eWxlcy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4vKipcbiAqIEBwYXJhbSBzdHJpbmdzLmJvcmRlcl9jb2xvclxuICogQHBhcmFtIHN0cmluZ3MuYm9yZGVyX3N0eWxlXG4gKiBAcGFyYW0gc3RyaW5ncy5ib3JkZXJfd2lkdGhcbiAqIEBwYXJhbSBzdHJpbmdzLmNvbnRhaW5lcl9zdHlsZXNcbiAqIEBwYXJhbSBzdHJpbmdzLnNoYWRvd19zaXplXG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIENvbnRhaW5lciBzdHlsZXMgcGFuZWwgbW9kdWxlLlxuICpcbiAqIEBzaW5jZSAxLjguOFxuICovXG5leHBvcnQgZGVmYXVsdCAoICggJCApID0+IHtcblx0LyoqXG5cdCAqIFdQIGNvcmUgY29tcG9uZW50cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqL1xuXHRjb25zdCB7IFBhbmVsQ29sb3JTZXR0aW5ncyB9ID0gd3AuYmxvY2tFZGl0b3IgfHwgd3AuZWRpdG9yO1xuXHRjb25zdCB7IFNlbGVjdENvbnRyb2wsIFBhbmVsQm9keSwgRmxleCwgRmxleEJsb2NrLCBfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sIH0gPSB3cC5jb21wb25lbnRzO1xuXG5cdC8qKlxuXHQgKiBMb2NhbGl6ZWQgZGF0YSBhbGlhc2VzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHsgc3RyaW5ncywgZGVmYXVsdHMgfSA9IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3I7XG5cblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXHRcdC8qKlxuXHRcdCAqIFN0YXJ0IHRoZSBlbmdpbmUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRpbml0KCkge1xuXHRcdFx0JCggYXBwLnJlYWR5ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERvY3VtZW50IHJlYWR5LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICovXG5cdFx0cmVhZHkoKSB7XG5cdFx0XHRhcHAuZXZlbnRzKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEV2ZW50cy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGV2ZW50cygpIHtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gQmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKi9cblx0XHRnZXRCbG9ja0F0dHJpYnV0ZXMoKSB7XG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRjb250YWluZXJQYWRkaW5nOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuY29udGFpbmVyUGFkZGluZyxcblx0XHRcdFx0fSxcblx0XHRcdFx0Y29udGFpbmVyQm9yZGVyU3R5bGU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5jb250YWluZXJCb3JkZXJTdHlsZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0Y29udGFpbmVyQm9yZGVyV2lkdGg6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5jb250YWluZXJCb3JkZXJXaWR0aCxcblx0XHRcdFx0fSxcblx0XHRcdFx0Y29udGFpbmVyQm9yZGVyQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5jb250YWluZXJCb3JkZXJDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0Y29udGFpbmVyQm9yZGVyUmFkaXVzOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuY29udGFpbmVyQm9yZGVyUmFkaXVzLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRjb250YWluZXJTaGFkb3dTaXplOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuY29udGFpbmVyU2hhZG93U2l6ZSxcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBDb250YWluZXIgU3R5bGVzIHBhbmVsIEpTWCBjb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGhhbmRsZXJzICAgICAgICAgICBCbG9jayBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gZm9ybVNlbGVjdG9yQ29tbW9uIENvbW1vbiBmb3JtIHNlbGVjdG9yIGZ1bmN0aW9ucy5cblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSB1aVN0YXRlIFVJIHN0YXRlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBGaWVsZCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0Q29udGFpbmVyU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIGZvcm1TZWxlY3RvckNvbW1vbiwgdWlTdGF0ZSApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uLCBjb21wbGV4aXR5XG5cdFx0XHRsZXQgY3NzQ2xhc3MgPSBmb3JtU2VsZWN0b3JDb21tb24uZ2V0UGFuZWxDbGFzcyggcHJvcHMgKTtcblx0XHRcdGNvbnN0IGlzTm90RGlzYWJsZWQgPSB1aVN0YXRlLmlzTm90RGlzYWJsZWQ7XG5cdFx0XHRjb25zdCBpc1Byb0VuYWJsZWQgPSB1aVN0YXRlLmlzUHJvRW5hYmxlZDtcblxuXHRcdFx0aWYgKCAhIGlzTm90RGlzYWJsZWQgKSB7XG5cdFx0XHRcdGNzc0NsYXNzICs9ICcgd3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtZGlzYWJsZWQnO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGNzc0NsYXNzIH0gdGl0bGU9eyBzdHJpbmdzLmNvbnRhaW5lcl9zdHlsZXMgfT5cblx0XHRcdFx0XHQ8ZGl2IC8vIGVzbGludC1kaXNhYmxlLWxpbmUganN4LWExMXkvbm8tc3RhdGljLWVsZW1lbnQtaW50ZXJhY3Rpb25zXG5cdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXBhbmVsLWJvZHlcIlxuXHRcdFx0XHRcdFx0b25DbGljaz17ICggZXZlbnQgKSA9PiB7XG5cdFx0XHRcdFx0XHRcdGlmICggaXNOb3REaXNhYmxlZCApIHtcblx0XHRcdFx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdFx0XHRldmVudC5zdG9wUHJvcGFnYXRpb24oKTtcblxuXHRcdFx0XHRcdFx0XHRpZiAoICEgaXNQcm9FbmFibGVkICkge1xuXHRcdFx0XHRcdFx0XHRcdHJldHVybiBmb3JtU2VsZWN0b3JDb21tb24uZWR1Y2F0aW9uLnNob3dQcm9Nb2RhbCggJ2NvbnRhaW5lcicsIHN0cmluZ3MuY29udGFpbmVyX3N0eWxlcyApO1xuXHRcdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdFx0Zm9ybVNlbGVjdG9yQ29tbW9uLmVkdWNhdGlvbi5zaG93TGljZW5zZU1vZGFsKCAnY29udGFpbmVyJywgc3RyaW5ncy5jb250YWluZXJfc3R5bGVzLCAnY29udGFpbmVyLXN0eWxlcycgKTtcblx0XHRcdFx0XHRcdH0gfVxuXHRcdFx0XHRcdFx0b25LZXlEb3duPXsgKCBldmVudCApID0+IHtcblx0XHRcdFx0XHRcdFx0aWYgKCBpc05vdERpc2FibGVkICkge1xuXHRcdFx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRcdGV2ZW50LnN0b3BQcm9wYWdhdGlvbigpO1xuXG5cdFx0XHRcdFx0XHRcdGlmICggISBpc1Byb0VuYWJsZWQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0cmV0dXJuIGZvcm1TZWxlY3RvckNvbW1vbi5lZHVjYXRpb24uc2hvd1Byb01vZGFsKCAnY29udGFpbmVyJywgc3RyaW5ncy5jb250YWluZXJfc3R5bGVzICk7XG5cdFx0XHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdFx0XHRmb3JtU2VsZWN0b3JDb21tb24uZWR1Y2F0aW9uLnNob3dMaWNlbnNlTW9kYWwoICdjb250YWluZXInLCBzdHJpbmdzLmNvbnRhaW5lcl9zdHlsZXMsICdjb250YWluZXItc3R5bGVzJyApO1xuXHRcdFx0XHRcdFx0fSB9XG5cdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0PEZsZXggZ2FwPXsgNCB9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleFwiIGp1c3RpZnk9XCJzcGFjZS1iZXR3ZWVuXCI+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PF9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5wYWRkaW5nIH1cblx0XHRcdFx0XHRcdFx0XHRcdHRhYkluZGV4PXsgaXNOb3REaXNhYmxlZCA/IDAgOiAtMSB9XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuY29udGFpbmVyUGFkZGluZyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRtaW49eyAwIH1cblx0XHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlPXsgaXNOb3REaXNhYmxlZCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdjb250YWluZXJQYWRkaW5nJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5ib3JkZXJfc3R5bGUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyBpc05vdERpc2FibGVkID8gMCA6IC0xIH1cblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5jb250YWluZXJCb3JkZXJTdHlsZSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgW1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLm5vbmUsIHZhbHVlOiAnbm9uZScgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5zb2xpZCwgdmFsdWU6ICdzb2xpZCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0eyBsYWJlbDogc3RyaW5ncy5kb3R0ZWQsIHZhbHVlOiAnZG90dGVkJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmRhc2hlZCwgdmFsdWU6ICdkYXNoZWQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3MuZG91YmxlLCB2YWx1ZTogJ2RvdWJsZScgfSxcblx0XHRcdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnY29udGFpbmVyQm9yZGVyU3R5bGUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdDxGbGV4IGdhcD17IDQgfSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXhcIiBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyX3dpZHRoIH1cblx0XHRcdFx0XHRcdFx0XHRcdHRhYkluZGV4PXsgaXNOb3REaXNhYmxlZCA/IDAgOiAtMSB9XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuY29udGFpbmVyQm9yZGVyU3R5bGUgPT09ICdub25lJyA/ICcnIDogcHJvcHMuYXR0cmlidXRlcy5jb250YWluZXJCb3JkZXJXaWR0aCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRtaW49eyAwIH1cblx0XHRcdFx0XHRcdFx0XHRcdGRpc2FibGVkPXsgcHJvcHMuYXR0cmlidXRlcy5jb250YWluZXJCb3JkZXJTdHlsZSA9PT0gJ25vbmUnIH1cblx0XHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlPXsgaXNOb3REaXNhYmxlZCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdjb250YWluZXJCb3JkZXJXaWR0aCcsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyX3JhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHR0YWJJbmRleD17IGlzTm90RGlzYWJsZWQgPyAwIDogLTEgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmNvbnRhaW5lckJvcmRlclJhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRtaW49eyAwIH1cblx0XHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlPXsgaXNOb3REaXNhYmxlZCB9XG5cdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdjb250YWluZXJCb3JkZXJSYWRpdXMnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdDxGbGV4IGdhcD17IDQgfSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXhcIiBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2hhZG93X3NpemUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyBpc05vdERpc2FibGVkID8gMCA6IC0xIH1cblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5jb250YWluZXJTaGFkb3dTaXplIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9wdGlvbnM9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3Mubm9uZSwgdmFsdWU6ICdub25lJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLnNtYWxsLCB2YWx1ZTogJ3NtYWxsJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLm1lZGl1bSwgdmFsdWU6ICdtZWRpdW0nIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3MubGFyZ2UsIHZhbHVlOiAnbGFyZ2UnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRdIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2NvbnRhaW5lclNoYWRvd1NpemUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHRcdDxGbGV4IGdhcD17IDQgfSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXhcIiBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdFx0PFBhbmVsQ29sb3JTZXR0aW5nc1xuXHRcdFx0XHRcdFx0XHRcdFx0X19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyXG5cdFx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdFx0c2hvd1RpdGxlPXsgZmFsc2UgfVxuXHRcdFx0XHRcdFx0XHRcdFx0dGFiSW5kZXg9eyBpc05vdERpc2FibGVkID8gMCA6IC0xIH1cblx0XHRcdFx0XHRcdFx0XHRcdGNsYXNzTmFtZT17IHByb3BzLmF0dHJpYnV0ZXMuY29udGFpbmVyQm9yZGVyU3R5bGUgPT09ICdub25lJyA/ICd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsIHdwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGFuZWwtZGlzYWJsZWQnIDogJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGFuZWwnIH1cblx0XHRcdFx0XHRcdFx0XHRcdGNvbG9yU2V0dGluZ3M9eyBbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5jb250YWluZXJCb3JkZXJDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogKCB2YWx1ZSApID0+IHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdGlmICggISBpc05vdERpc2FibGVkICkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHRoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdjb250YWluZXJCb3JkZXJDb2xvcicsIHZhbHVlICk7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5ib3JkZXJfY29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRdIH1cblx0XHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDwvRmxleD5cblx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHQpO1xuXHRcdH0sXG5cdH07XG5cblx0cmV0dXJuIGFwcDtcbn0gKSggalF1ZXJ5ICk7XG4iXSwibWFwcGluZ3MiOiI7Ozs7OztBQUFBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBTkEsSUFBQUEsUUFBQSxHQUFBQyxPQUFBLENBQUFDLE9BQUEsR0FPaUIsVUFBRUMsQ0FBQyxFQUFNO0VBQ3pCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxJQUFBQyxJQUFBLEdBQStCQyxFQUFFLENBQUNDLFdBQVcsSUFBSUQsRUFBRSxDQUFDRSxNQUFNO0lBQWxEQyxrQkFBa0IsR0FBQUosSUFBQSxDQUFsQkksa0JBQWtCO0VBQzFCLElBQUFDLGNBQUEsR0FBaUZKLEVBQUUsQ0FBQ0ssVUFBVTtJQUF0RkMsYUFBYSxHQUFBRixjQUFBLENBQWJFLGFBQWE7SUFBRUMsU0FBUyxHQUFBSCxjQUFBLENBQVRHLFNBQVM7SUFBRUMsSUFBSSxHQUFBSixjQUFBLENBQUpJLElBQUk7SUFBRUMsU0FBUyxHQUFBTCxjQUFBLENBQVRLLFNBQVM7SUFBRUMseUJBQXlCLEdBQUFOLGNBQUEsQ0FBekJNLHlCQUF5Qjs7RUFFNUU7QUFDRDtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQUFDLHFCQUFBLEdBQThCQywrQkFBK0I7SUFBckRDLE9BQU8sR0FBQUYscUJBQUEsQ0FBUEUsT0FBTztJQUFFQyxRQUFRLEdBQUFILHFCQUFBLENBQVJHLFFBQVE7O0VB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
},{}],19:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_education, WPFormsEducation */
/**
 * WPForms Education Modal module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function ($) {
  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Open educational popup for users with no Pro license.
     *
     * @since 1.8.8
     *
     * @param {string} panel   Panel slug.
     * @param {string} feature Feature name.
     */
    showProModal: function showProModal(panel, feature) {
      var type = 'pro';
      var message = wpforms_education.upgrade[type].message_plural.replace(/%name%/g, feature);
      var utmContent = {
        container: 'Upgrade to Pro - Container Styles',
        background: 'Upgrade to Pro - Background Styles',
        themes: 'Upgrade to Pro - Themes'
      };
      $.alert({
        backgroundDismiss: true,
        title: feature + ' ' + wpforms_education.upgrade[type].title_plural,
        icon: 'fa fa-lock',
        content: message,
        boxWidth: '550px',
        theme: 'modern,wpforms-education',
        closeIcon: true,
        onOpenBefore: function onOpenBefore() {
          // eslint-disable-line object-shorthand
          this.$btnc.after('<div class="discount-note">' + wpforms_education.upgrade_bonus + '</div>');
          this.$btnc.after(wpforms_education.upgrade[type].doc.replace(/%25name%25/g, 'AP - ' + feature));
          this.$body.find('.jconfirm-content').addClass('lite-upgrade');
        },
        buttons: {
          confirm: {
            text: wpforms_education.upgrade[type].button,
            btnClass: 'btn-confirm',
            keys: ['enter'],
            action: function action() {
              window.open(WPFormsEducation.core.getUpgradeURL(utmContent[panel], type), '_blank');
              WPFormsEducation.core.upgradeModalThankYou(type);
            }
          }
        }
      });
    },
    /**
     * Open license modal.
     *
     * @since 1.8.8
     *
     * @param {string} feature    Feature name.
     * @param {string} fieldName  Field name.
     * @param {string} utmContent UTM content.
     */
    showLicenseModal: function showLicenseModal(feature, fieldName, utmContent) {
      WPFormsEducation.proCore.licenseModal(feature, fieldName, utmContent);
    }
  };
  return app;
}(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiJCIsImFwcCIsInNob3dQcm9Nb2RhbCIsInBhbmVsIiwiZmVhdHVyZSIsInR5cGUiLCJtZXNzYWdlIiwid3Bmb3Jtc19lZHVjYXRpb24iLCJ1cGdyYWRlIiwibWVzc2FnZV9wbHVyYWwiLCJyZXBsYWNlIiwidXRtQ29udGVudCIsImNvbnRhaW5lciIsImJhY2tncm91bmQiLCJ0aGVtZXMiLCJhbGVydCIsImJhY2tncm91bmREaXNtaXNzIiwidGl0bGUiLCJ0aXRsZV9wbHVyYWwiLCJpY29uIiwiY29udGVudCIsImJveFdpZHRoIiwidGhlbWUiLCJjbG9zZUljb24iLCJvbk9wZW5CZWZvcmUiLCIkYnRuYyIsImFmdGVyIiwidXBncmFkZV9ib251cyIsImRvYyIsIiRib2R5IiwiZmluZCIsImFkZENsYXNzIiwiYnV0dG9ucyIsImNvbmZpcm0iLCJ0ZXh0IiwiYnV0dG9uIiwiYnRuQ2xhc3MiLCJrZXlzIiwiYWN0aW9uIiwid2luZG93Iiwib3BlbiIsIldQRm9ybXNFZHVjYXRpb24iLCJjb3JlIiwiZ2V0VXBncmFkZVVSTCIsInVwZ3JhZGVNb2RhbFRoYW5rWW91Iiwic2hvd0xpY2Vuc2VNb2RhbCIsImZpZWxkTmFtZSIsInByb0NvcmUiLCJsaWNlbnNlTW9kYWwiLCJqUXVlcnkiXSwic291cmNlcyI6WyJlZHVjYXRpb24uanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIHdwZm9ybXNfZWR1Y2F0aW9uLCBXUEZvcm1zRWR1Y2F0aW9uICovXG5cbi8qKlxuICogV1BGb3JtcyBFZHVjYXRpb24gTW9kYWwgbW9kdWxlLlxuICpcbiAqIEBzaW5jZSAxLjguOFxuICovXG5leHBvcnQgZGVmYXVsdCAoICggJCApID0+IHtcblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXHRcdC8qKlxuXHRcdCAqIE9wZW4gZWR1Y2F0aW9uYWwgcG9wdXAgZm9yIHVzZXJzIHdpdGggbm8gUHJvIGxpY2Vuc2UuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBwYW5lbCAgIFBhbmVsIHNsdWcuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGZlYXR1cmUgRmVhdHVyZSBuYW1lLlxuXHRcdCAqL1xuXHRcdHNob3dQcm9Nb2RhbCggcGFuZWwsIGZlYXR1cmUgKSB7XG5cdFx0XHRjb25zdCB0eXBlID0gJ3Bybyc7XG5cdFx0XHRjb25zdCBtZXNzYWdlID0gd3Bmb3Jtc19lZHVjYXRpb24udXBncmFkZVsgdHlwZSBdLm1lc3NhZ2VfcGx1cmFsLnJlcGxhY2UoIC8lbmFtZSUvZywgZmVhdHVyZSApO1xuXHRcdFx0Y29uc3QgdXRtQ29udGVudCA9IHtcblx0XHRcdFx0Y29udGFpbmVyOiAnVXBncmFkZSB0byBQcm8gLSBDb250YWluZXIgU3R5bGVzJyxcblx0XHRcdFx0YmFja2dyb3VuZDogJ1VwZ3JhZGUgdG8gUHJvIC0gQmFja2dyb3VuZCBTdHlsZXMnLFxuXHRcdFx0XHR0aGVtZXM6ICdVcGdyYWRlIHRvIFBybyAtIFRoZW1lcycsXG5cdFx0XHR9O1xuXG5cdFx0XHQkLmFsZXJ0KCB7XG5cdFx0XHRcdGJhY2tncm91bmREaXNtaXNzOiB0cnVlLFxuXHRcdFx0XHR0aXRsZTogZmVhdHVyZSArICcgJyArIHdwZm9ybXNfZWR1Y2F0aW9uLnVwZ3JhZGVbIHR5cGUgXS50aXRsZV9wbHVyYWwsXG5cdFx0XHRcdGljb246ICdmYSBmYS1sb2NrJyxcblx0XHRcdFx0Y29udGVudDogbWVzc2FnZSxcblx0XHRcdFx0Ym94V2lkdGg6ICc1NTBweCcsXG5cdFx0XHRcdHRoZW1lOiAnbW9kZXJuLHdwZm9ybXMtZWR1Y2F0aW9uJyxcblx0XHRcdFx0Y2xvc2VJY29uOiB0cnVlLFxuXHRcdFx0XHRvbk9wZW5CZWZvcmU6IGZ1bmN0aW9uKCkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG9iamVjdC1zaG9ydGhhbmRcblx0XHRcdFx0XHR0aGlzLiRidG5jLmFmdGVyKCAnPGRpdiBjbGFzcz1cImRpc2NvdW50LW5vdGVcIj4nICsgd3Bmb3Jtc19lZHVjYXRpb24udXBncmFkZV9ib251cyArICc8L2Rpdj4nICk7XG5cdFx0XHRcdFx0dGhpcy4kYnRuYy5hZnRlciggd3Bmb3Jtc19lZHVjYXRpb24udXBncmFkZVsgdHlwZSBdLmRvYy5yZXBsYWNlKCAvJTI1bmFtZSUyNS9nLCAnQVAgLSAnICsgZmVhdHVyZSApICk7XG5cdFx0XHRcdFx0dGhpcy4kYm9keS5maW5kKCAnLmpjb25maXJtLWNvbnRlbnQnICkuYWRkQ2xhc3MoICdsaXRlLXVwZ3JhZGUnICk7XG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJ1dHRvbnM6IHtcblx0XHRcdFx0XHRjb25maXJtOiB7XG5cdFx0XHRcdFx0XHR0ZXh0OiB3cGZvcm1zX2VkdWNhdGlvbi51cGdyYWRlWyB0eXBlIF0uYnV0dG9uLFxuXHRcdFx0XHRcdFx0YnRuQ2xhc3M6ICdidG4tY29uZmlybScsXG5cdFx0XHRcdFx0XHRrZXlzOiBbICdlbnRlcicgXSxcblx0XHRcdFx0XHRcdGFjdGlvbjogKCkgPT4ge1xuXHRcdFx0XHRcdFx0XHR3aW5kb3cub3BlbiggV1BGb3Jtc0VkdWNhdGlvbi5jb3JlLmdldFVwZ3JhZGVVUkwoIHV0bUNvbnRlbnRbIHBhbmVsIF0sIHR5cGUgKSwgJ19ibGFuaycgKTtcblx0XHRcdFx0XHRcdFx0V1BGb3Jtc0VkdWNhdGlvbi5jb3JlLnVwZ3JhZGVNb2RhbFRoYW5rWW91KCB0eXBlICk7XG5cdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdH0sXG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE9wZW4gbGljZW5zZSBtb2RhbC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGZlYXR1cmUgICAgRmVhdHVyZSBuYW1lLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBmaWVsZE5hbWUgIEZpZWxkIG5hbWUuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IHV0bUNvbnRlbnQgVVRNIGNvbnRlbnQuXG5cdFx0ICovXG5cdFx0c2hvd0xpY2Vuc2VNb2RhbCggZmVhdHVyZSwgZmllbGROYW1lLCB1dG1Db250ZW50ICkge1xuXHRcdFx0V1BGb3Jtc0VkdWNhdGlvbi5wcm9Db3JlLmxpY2Vuc2VNb2RhbCggZmVhdHVyZSwgZmllbGROYW1lLCB1dG1Db250ZW50ICk7XG5cdFx0fSxcblx0fTtcblxuXHRyZXR1cm4gYXBwO1xufSApKCBqUXVlcnkgKTtcbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQUE7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBSkEsSUFBQUEsUUFBQSxHQUFBQyxPQUFBLENBQUFDLE9BQUEsR0FLaUIsVUFBRUMsQ0FBQyxFQUFNO0VBQ3pCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBTUMsR0FBRyxHQUFHO0lBQ1g7QUFDRjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtJQUNFQyxZQUFZLFdBQVpBLFlBQVlBLENBQUVDLEtBQUssRUFBRUMsT0FBTyxFQUFHO01BQzlCLElBQU1DLElBQUksR0FBRyxLQUFLO01BQ2xCLElBQU1DLE9BQU8sR0FBR0MsaUJBQWlCLENBQUNDLE9BQU8sQ0FBRUgsSUFBSSxDQUFFLENBQUNJLGNBQWMsQ0FBQ0MsT0FBTyxDQUFFLFNBQVMsRUFBRU4sT0FBUSxDQUFDO01BQzlGLElBQU1PLFVBQVUsR0FBRztRQUNsQkMsU0FBUyxFQUFFLG1DQUFtQztRQUM5Q0MsVUFBVSxFQUFFLG9DQUFvQztRQUNoREMsTUFBTSxFQUFFO01BQ1QsQ0FBQztNQUVEZCxDQUFDLENBQUNlLEtBQUssQ0FBRTtRQUNSQyxpQkFBaUIsRUFBRSxJQUFJO1FBQ3ZCQyxLQUFLLEVBQUViLE9BQU8sR0FBRyxHQUFHLEdBQUdHLGlCQUFpQixDQUFDQyxPQUFPLENBQUVILElBQUksQ0FBRSxDQUFDYSxZQUFZO1FBQ3JFQyxJQUFJLEVBQUUsWUFBWTtRQUNsQkMsT0FBTyxFQUFFZCxPQUFPO1FBQ2hCZSxRQUFRLEVBQUUsT0FBTztRQUNqQkMsS0FBSyxFQUFFLDBCQUEwQjtRQUNqQ0MsU0FBUyxFQUFFLElBQUk7UUFDZkMsWUFBWSxFQUFFLFNBQWRBLFlBQVlBLENBQUEsRUFBYTtVQUFFO1VBQzFCLElBQUksQ0FBQ0MsS0FBSyxDQUFDQyxLQUFLLENBQUUsNkJBQTZCLEdBQUduQixpQkFBaUIsQ0FBQ29CLGFBQWEsR0FBRyxRQUFTLENBQUM7VUFDOUYsSUFBSSxDQUFDRixLQUFLLENBQUNDLEtBQUssQ0FBRW5CLGlCQUFpQixDQUFDQyxPQUFPLENBQUVILElBQUksQ0FBRSxDQUFDdUIsR0FBRyxDQUFDbEIsT0FBTyxDQUFFLGFBQWEsRUFBRSxPQUFPLEdBQUdOLE9BQVEsQ0FBRSxDQUFDO1VBQ3JHLElBQUksQ0FBQ3lCLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLG1CQUFvQixDQUFDLENBQUNDLFFBQVEsQ0FBRSxjQUFlLENBQUM7UUFDbEUsQ0FBQztRQUNEQyxPQUFPLEVBQUU7VUFDUkMsT0FBTyxFQUFFO1lBQ1JDLElBQUksRUFBRTNCLGlCQUFpQixDQUFDQyxPQUFPLENBQUVILElBQUksQ0FBRSxDQUFDOEIsTUFBTTtZQUM5Q0MsUUFBUSxFQUFFLGFBQWE7WUFDdkJDLElBQUksRUFBRSxDQUFFLE9BQU8sQ0FBRTtZQUNqQkMsTUFBTSxFQUFFLFNBQVJBLE1BQU1BLENBQUEsRUFBUTtjQUNiQyxNQUFNLENBQUNDLElBQUksQ0FBRUMsZ0JBQWdCLENBQUNDLElBQUksQ0FBQ0MsYUFBYSxDQUFFaEMsVUFBVSxDQUFFUixLQUFLLENBQUUsRUFBRUUsSUFBSyxDQUFDLEVBQUUsUUFBUyxDQUFDO2NBQ3pGb0MsZ0JBQWdCLENBQUNDLElBQUksQ0FBQ0Usb0JBQW9CLENBQUV2QyxJQUFLLENBQUM7WUFDbkQ7VUFDRDtRQUNEO01BQ0QsQ0FBRSxDQUFDO0lBQ0osQ0FBQztJQUVEO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtJQUNFd0MsZ0JBQWdCLFdBQWhCQSxnQkFBZ0JBLENBQUV6QyxPQUFPLEVBQUUwQyxTQUFTLEVBQUVuQyxVQUFVLEVBQUc7TUFDbEQ4QixnQkFBZ0IsQ0FBQ00sT0FBTyxDQUFDQyxZQUFZLENBQUU1QyxPQUFPLEVBQUUwQyxTQUFTLEVBQUVuQyxVQUFXLENBQUM7SUFDeEU7RUFDRCxDQUFDO0VBRUQsT0FBT1YsR0FBRztBQUNYLENBQUMsQ0FBSWdELE1BQU8sQ0FBQyIsImlnbm9yZUxpc3QiOltdfQ==
},{}],20:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param strings.field_styles
 * @param strings.lead_forms_panel_notice_head
 * @param strings.lead_forms_panel_notice_text
 * @param strings.learn_more
 * @param strings.use_modern_notice_head
 * @param strings.use_modern_notice_link
 * @param strings.use_modern_notice_text
 */
/**
 * Gutenberg editor block.
 *
 * Field styles panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function () {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _ref = wp.blockEditor || wp.editor,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    PanelBody = _wp$components.PanelBody,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults;

  // noinspection UnnecessaryLocalVariableJS
  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Get block attributes.
     *
     * @since 1.8.8
     *
     * @return {Object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      return {
        fieldSize: {
          type: 'string',
          default: defaults.fieldSize
        },
        fieldBorderStyle: {
          type: 'string',
          default: defaults.fieldBorderStyle
        },
        fieldBorderSize: {
          type: 'string',
          default: defaults.fieldBorderSize
        },
        fieldBorderRadius: {
          type: 'string',
          default: defaults.fieldBorderRadius
        },
        fieldBackgroundColor: {
          type: 'string',
          default: defaults.fieldBackgroundColor
        },
        fieldBorderColor: {
          type: 'string',
          default: defaults.fieldBorderColor
        },
        fieldTextColor: {
          type: 'string',
          default: defaults.fieldTextColor
        },
        fieldMenuColor: {
          type: 'string',
          default: defaults.fieldMenuColor
        }
      };
    },
    /**
     * Get Field styles JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props              Block properties.
     * @param {Object} handlers           Block event handlers.
     * @param {Object} sizeOptions        Size selector options.
     * @param {Object} formSelectorCommon Form selector common object.
     *
     * @return {Object}  Field styles JSX code.
     */
    getFieldStyles: function getFieldStyles(props, handlers, sizeOptions, formSelectorCommon) {
      // eslint-disable-line max-lines-per-function
      return /*#__PURE__*/React.createElement(PanelBody, {
        className: formSelectorCommon.getPanelClass(props),
        title: strings.field_styles
      }, /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.size,
        value: props.attributes.fieldSize,
        options: sizeOptions,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('fieldSize', value);
        }
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
        label: strings.border,
        value: props.attributes.fieldBorderStyle,
        options: [{
          label: strings.none,
          value: 'none'
        }, {
          label: strings.solid,
          value: 'solid'
        }, {
          label: strings.dashed,
          value: 'dashed'
        }, {
          label: strings.dotted,
          value: 'dotted'
        }],
        onChange: function onChange(value) {
          return handlers.styleAttrChange('fieldBorderStyle', value);
        }
      }))), /*#__PURE__*/React.createElement(Flex, {
        gap: 4,
        align: "flex-start",
        className: 'wpforms-gutenberg-form-selector-flex',
        justify: "space-between"
      }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.border_size,
        value: props.attributes.fieldBorderStyle === 'none' ? '' : props.attributes.fieldBorderSize,
        min: 0,
        disabled: props.attributes.fieldBorderStyle === 'none',
        onChange: function onChange(value) {
          return handlers.styleAttrChange('fieldBorderSize', value);
        },
        isUnitSelectTabbable: true
      })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
        label: strings.border_radius,
        value: props.attributes.fieldBorderRadius,
        min: 0,
        isUnitSelectTabbable: true,
        onChange: function onChange(value) {
          return handlers.styleAttrChange('fieldBorderRadius', value);
        }
      }))), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-color-picker"
      }, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-control-label"
      }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
        __experimentalIsRenderedInSidebar: true,
        enableAlpha: true,
        showTitle: false,
        className: formSelectorCommon.getColorPanelClass(props.attributes.fieldBorderStyle),
        colorSettings: [{
          value: props.attributes.fieldBackgroundColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldBackgroundColor', value);
          },
          label: strings.background
        }, {
          value: props.attributes.fieldBorderColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldBorderColor', value);
          },
          label: strings.border
        }, {
          value: props.attributes.fieldTextColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldTextColor', value);
          },
          label: strings.text
        }, {
          value: props.attributes.fieldMenuColor,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldMenuColor', value);
          },
          label: strings.menu
        }]
      })));
    }
  };
  return app;
}();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiX3JlZiIsIndwIiwiYmxvY2tFZGl0b3IiLCJlZGl0b3IiLCJQYW5lbENvbG9yU2V0dGluZ3MiLCJfd3AkY29tcG9uZW50cyIsImNvbXBvbmVudHMiLCJTZWxlY3RDb250cm9sIiwiUGFuZWxCb2R5IiwiRmxleCIsIkZsZXhCbG9jayIsIl9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2wiLCJfd3Bmb3Jtc19ndXRlbmJlcmdfZm8iLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwic3RyaW5ncyIsImRlZmF1bHRzIiwiYXBwIiwiZ2V0QmxvY2tBdHRyaWJ1dGVzIiwiZmllbGRTaXplIiwidHlwZSIsImZpZWxkQm9yZGVyU3R5bGUiLCJmaWVsZEJvcmRlclNpemUiLCJmaWVsZEJvcmRlclJhZGl1cyIsImZpZWxkQmFja2dyb3VuZENvbG9yIiwiZmllbGRCb3JkZXJDb2xvciIsImZpZWxkVGV4dENvbG9yIiwiZmllbGRNZW51Q29sb3IiLCJnZXRGaWVsZFN0eWxlcyIsInByb3BzIiwiaGFuZGxlcnMiLCJzaXplT3B0aW9ucyIsImZvcm1TZWxlY3RvckNvbW1vbiIsIlJlYWN0IiwiY3JlYXRlRWxlbWVudCIsImNsYXNzTmFtZSIsImdldFBhbmVsQ2xhc3MiLCJ0aXRsZSIsImZpZWxkX3N0eWxlcyIsImdhcCIsImFsaWduIiwianVzdGlmeSIsImxhYmVsIiwic2l6ZSIsInZhbHVlIiwiYXR0cmlidXRlcyIsIm9wdGlvbnMiLCJvbkNoYW5nZSIsInN0eWxlQXR0ckNoYW5nZSIsImJvcmRlciIsIm5vbmUiLCJzb2xpZCIsImRhc2hlZCIsImRvdHRlZCIsImJvcmRlcl9zaXplIiwibWluIiwiZGlzYWJsZWQiLCJpc1VuaXRTZWxlY3RUYWJiYWJsZSIsImJvcmRlcl9yYWRpdXMiLCJjb2xvcnMiLCJfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXIiLCJlbmFibGVBbHBoYSIsInNob3dUaXRsZSIsImdldENvbG9yUGFuZWxDbGFzcyIsImNvbG9yU2V0dGluZ3MiLCJiYWNrZ3JvdW5kIiwidGV4dCIsIm1lbnUiXSwic291cmNlcyI6WyJmaWVsZC1zdHlsZXMuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IgKi9cbi8qIGpzaGludCBlczM6IGZhbHNlLCBlc3ZlcnNpb246IDYgKi9cblxuLyoqXG4gKiBAcGFyYW0gc3RyaW5ncy5maWVsZF9zdHlsZXNcbiAqIEBwYXJhbSBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX2hlYWRcbiAqIEBwYXJhbSBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX3RleHRcbiAqIEBwYXJhbSBzdHJpbmdzLmxlYXJuX21vcmVcbiAqIEBwYXJhbSBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX2hlYWRcbiAqIEBwYXJhbSBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX2xpbmtcbiAqIEBwYXJhbSBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX3RleHRcbiAqL1xuXG4vKipcbiAqIEd1dGVuYmVyZyBlZGl0b3IgYmxvY2suXG4gKlxuICogRmllbGQgc3R5bGVzIHBhbmVsIG1vZHVsZS5cbiAqXG4gKiBAc2luY2UgMS44LjhcbiAqL1xuZXhwb3J0IGRlZmF1bHQgKCAoIGZ1bmN0aW9uKCkge1xuXHQvKipcblx0ICogV1AgY29yZSBjb21wb25lbnRzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHsgUGFuZWxDb2xvclNldHRpbmdzIH0gPSB3cC5ibG9ja0VkaXRvciB8fCB3cC5lZGl0b3I7XG5cdGNvbnN0IHsgU2VsZWN0Q29udHJvbCwgUGFuZWxCb2R5LCBGbGV4LCBGbGV4QmxvY2ssIF9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2wgfSA9IHdwLmNvbXBvbmVudHM7XG5cblx0LyoqXG5cdCAqIExvY2FsaXplZCBkYXRhIGFsaWFzZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKi9cblx0Y29uc3QgeyBzdHJpbmdzLCBkZWZhdWx0cyB9ID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvcjtcblxuXHQvLyBub2luc3BlY3Rpb24gVW5uZWNlc3NhcnlMb2NhbFZhcmlhYmxlSlNcblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXHRcdC8qKlxuXHRcdCAqIEdldCBibG9jayBhdHRyaWJ1dGVzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtPYmplY3R9IEJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0ICovXG5cdFx0Z2V0QmxvY2tBdHRyaWJ1dGVzKCkge1xuXHRcdFx0cmV0dXJuIHtcblx0XHRcdFx0ZmllbGRTaXplOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRTaXplLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZEJvcmRlclN0eWxlOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCb3JkZXJTdHlsZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0ZmllbGRCb3JkZXJTaXplOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCb3JkZXJTaXplLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZEJvcmRlclJhZGl1czoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmZpZWxkQm9yZGVyUmFkaXVzLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZEJhY2tncm91bmRDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmZpZWxkQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZEJvcmRlckNvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCb3JkZXJDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0ZmllbGRUZXh0Q29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZFRleHRDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0ZmllbGRNZW51Q29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZE1lbnVDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBGaWVsZCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyAgICAgICAgICAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgICAgICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzaXplT3B0aW9ucyAgICAgICAgU2l6ZSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBmb3JtU2VsZWN0b3JDb21tb24gRm9ybSBzZWxlY3RvciBjb21tb24gb2JqZWN0LlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSAgRmllbGQgc3R5bGVzIEpTWCBjb2RlLlxuXHRcdCAqL1xuXHRcdGdldEZpZWxkU3R5bGVzKCBwcm9wcywgaGFuZGxlcnMsIHNpemVPcHRpb25zLCBmb3JtU2VsZWN0b3JDb21tb24gKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXHRcdFx0cmV0dXJuIChcblx0XHRcdFx0PFBhbmVsQm9keSBjbGFzc05hbWU9eyBmb3JtU2VsZWN0b3JDb21tb24uZ2V0UGFuZWxDbGFzcyggcHJvcHMgKSB9IHRpdGxlPXsgc3RyaW5ncy5maWVsZF9zdHlsZXMgfT5cblx0XHRcdFx0XHQ8RmxleCBnYXA9eyA0IH0gYWxpZ249XCJmbGV4LXN0YXJ0XCIgY2xhc3NOYW1lPXsgJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCcgfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkU2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdmaWVsZFNpemUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PEZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyIH1cblx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IHByb3BzLmF0dHJpYnV0ZXMuZmllbGRCb3JkZXJTdHlsZSB9XG5cdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17XG5cdFx0XHRcdFx0XHRcdFx0XHRbXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3Mubm9uZSwgdmFsdWU6ICdub25lJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLnNvbGlkLCB2YWx1ZTogJ3NvbGlkJyB9LFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IGxhYmVsOiBzdHJpbmdzLmRhc2hlZCwgdmFsdWU6ICdkYXNoZWQnIH0sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHsgbGFiZWw6IHN0cmluZ3MuZG90dGVkLCB2YWx1ZTogJ2RvdHRlZCcgfSxcblx0XHRcdFx0XHRcdFx0XHRcdF1cblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRCb3JkZXJTdHlsZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0PC9GbGV4PlxuXHRcdFx0XHRcdDxGbGV4IGdhcD17IDQgfSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9eyAnd3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1mbGV4JyB9IGp1c3RpZnk9XCJzcGFjZS1iZXR3ZWVuXCI+XG5cdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHQ8X19leHBlcmltZW50YWxVbml0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5ib3JkZXJfc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkQm9yZGVyU3R5bGUgPT09ICdub25lJyA/ICcnIDogcHJvcHMuYXR0cmlidXRlcy5maWVsZEJvcmRlclNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdG1pbj17IDAgfVxuXHRcdFx0XHRcdFx0XHRcdGRpc2FibGVkPXsgcHJvcHMuYXR0cmlidXRlcy5maWVsZEJvcmRlclN0eWxlID09PSAnbm9uZScgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkQm9yZGVyU2l6ZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdGlzVW5pdFNlbGVjdFRhYmJhYmxlXG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHQ8L0ZsZXhCbG9jaz5cblx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmJvcmRlcl9yYWRpdXMgfVxuXHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy5maWVsZEJvcmRlclJhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0bWluPXsgMCB9XG5cdFx0XHRcdFx0XHRcdFx0aXNVbml0U2VsZWN0VGFiYmFibGVcblx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdmaWVsZEJvcmRlclJhZGl1cycsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0PC9GbGV4PlxuXG5cdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBpY2tlclwiPlxuXHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbnRyb2wtbGFiZWxcIj57IHN0cmluZ3MuY29sb3JzIH08L2Rpdj5cblx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0X19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyXG5cdFx0XHRcdFx0XHRcdGVuYWJsZUFscGhhXG5cdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPXsgZm9ybVNlbGVjdG9yQ29tbW9uLmdldENvbG9yUGFuZWxDbGFzcyggcHJvcHMuYXR0cmlidXRlcy5maWVsZEJvcmRlclN0eWxlICkgfVxuXHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXsgW1xuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdmaWVsZEJhY2tncm91bmRDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5iYWNrZ3JvdW5kLFxuXHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU6IHByb3BzLmF0dHJpYnV0ZXMuZmllbGRCb3JkZXJDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRCb3JkZXJDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5ib3JkZXIsXG5cdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogcHJvcHMuYXR0cmlidXRlcy5maWVsZFRleHRDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiAoIHZhbHVlICkgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRUZXh0Q29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MudGV4dCxcblx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBwcm9wcy5hdHRyaWJ1dGVzLmZpZWxkTWVudUNvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdmaWVsZE1lbnVDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5tZW51LFxuXHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdF0gfVxuXHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHQpO1xuXHRcdH0sXG5cdH07XG5cblx0cmV0dXJuIGFwcDtcbn0gKSgpICk7XG4iXSwibWFwcGluZ3MiOiI7Ozs7OztBQUFBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQU5BLElBQUFBLFFBQUEsR0FBQUMsT0FBQSxDQUFBQyxPQUFBLEdBT21CLFlBQVc7RUFDN0I7QUFDRDtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQUFDLElBQUEsR0FBK0JDLEVBQUUsQ0FBQ0MsV0FBVyxJQUFJRCxFQUFFLENBQUNFLE1BQU07SUFBbERDLGtCQUFrQixHQUFBSixJQUFBLENBQWxCSSxrQkFBa0I7RUFDMUIsSUFBQUMsY0FBQSxHQUFpRkosRUFBRSxDQUFDSyxVQUFVO0lBQXRGQyxhQUFhLEdBQUFGLGNBQUEsQ0FBYkUsYUFBYTtJQUFFQyxTQUFTLEdBQUFILGNBQUEsQ0FBVEcsU0FBUztJQUFFQyxJQUFJLEdBQUFKLGNBQUEsQ0FBSkksSUFBSTtJQUFFQyxTQUFTLEdBQUFMLGNBQUEsQ0FBVEssU0FBUztJQUFFQyx5QkFBeUIsR0FBQU4sY0FBQSxDQUF6Qk0seUJBQXlCOztFQUU1RTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBQUMscUJBQUEsR0FBOEJDLCtCQUErQjtJQUFyREMsT0FBTyxHQUFBRixxQkFBQSxDQUFQRSxPQUFPO0lBQUVD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
},{}],21:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param wpforms_gutenberg_form_selector.route_namespace
 * @param strings.theme_name
 * @param strings.theme_delete
 * @param strings.theme_delete_title
 * @param strings.theme_delete_confirm
 * @param strings.theme_delete_cant_undone
 * @param strings.theme_delete_yes
 * @param strings.theme_copy
 * @param strings.theme_custom
 * @param strings.theme_noname
 * @param strings.button_background
 * @param strings.button_text
 * @param strings.field_label
 * @param strings.field_sublabel
 * @param strings.field_border
 */
/**
 * Gutenberg editor block.
 *
 * Themes panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function (document, window, $) {
  /**
   * WP core components.
   *
   * @since 1.8.8
   */
  var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    ColorIndicator = _wp$components.ColorIndicator,
    TextControl = _wp$components.TextControl,
    Button = _wp$components.Button;
  var _wp$components2 = wp.components,
    Radio = _wp$components2.__experimentalRadio,
    RadioGroup = _wp$components2.__experimentalRadioGroup;

  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    isAdmin = _wpforms_gutenberg_fo.isAdmin,
    isPro = _wpforms_gutenberg_fo.isPro,
    isLicenseActive = _wpforms_gutenberg_fo.isLicenseActive,
    strings = _wpforms_gutenberg_fo.strings,
    routeNamespace = _wpforms_gutenberg_fo.route_namespace;

  /**
   * Form selector common module.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var formSelectorCommon = null;

  /**
   * Runtime state.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var state = {};

  /**
   * Themes data.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var themesData = {
    wpforms: null,
    custom: null
  };

  /**
   * Enabled themes.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var enabledThemes = null;

  /**
   * Elements holder.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var el = {};

  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Initialize panel.
     *
     * @since 1.8.8
     */
    init: function init() {
      el.$window = $(window);
      app.fetchThemesData();
      $(app.ready);
    },
    /**
     * Document ready.
     *
     * @since 1.8.8
     */
    ready: function ready() {
      app.events();
    },
    /**
     * Events.
     *
     * @since 1.8.8
     */
    events: function events() {
      wp.data.subscribe(function () {
        var _wp$data$select, _wp$data$select2, _wp$data$select3, _wp$data$select4, _currentPost$type, _currentPost$type2;
        // eslint-disable-line complexity
        if (!isAdmin) {
          return;
        }
        var isSavingPost = (_wp$data$select = wp.data.select('core/editor')) === null || _wp$data$select === void 0 ? void 0 : _wp$data$select.isSavingPost();
        var isAutosavingPost = (_wp$data$select2 = wp.data.select('core/editor')) === null || _wp$data$select2 === void 0 ? void 0 : _wp$data$select2.isAutosavingPost();
        var isSavingWidget = (_wp$data$select3 = wp.data.select('core/edit-widgets')) === null || _wp$data$select3 === void 0 ? void 0 : _wp$data$select3.isSavingWidgetAreas();
        var currentPost = (_wp$data$select4 = wp.data.select('core/editor')) === null || _wp$data$select4 === void 0 ? void 0 : _wp$data$select4.getCurrentPost();
        var isBlockOrTemplate = (currentPost === null || currentPost === void 0 || (_currentPost$type = currentPost.type) === null || _currentPost$type === void 0 ? void 0 : _currentPost$type.includes('wp_template')) || (currentPost === null || currentPost === void 0 || (_currentPost$type2 = currentPost.type) === null || _currentPost$type2 === void 0 ? void 0 : _currentPost$type2.includes('wp_block'));
        if (!isSavingPost && !isSavingWidget && !isBlockOrTemplate || isAutosavingPost) {
          return;
        }
        if (isBlockOrTemplate) {
          // Delay saving if this is FSE for better performance.
          _.debounce(app.saveCustomThemes, 500)();
          return;
        }
        app.saveCustomThemes();
      });
    },
    /**
     * Get all themes data.
     *
     * @since 1.8.8
     *
     * @return {Object} Themes data.
     */
    getAllThemes: function getAllThemes() {
      return _objectSpread(_objectSpread({}, themesData.custom || {}), themesData.wpforms || {});
    },
    /**
     * Get theme data.
     *
     * @since 1.8.8
     *
     * @param {string} slug Theme slug.
     *
     * @return {Object|null} Theme settings.
     */
    getTheme: function getTheme(slug) {
      return app.getAllThemes()[slug] || null;
    },
    /**
     * Get enabled themes data.
     *
     * @since 1.8.8
     *
     * @return {Object} Themes data.
     */
    getEnabledThemes: function getEnabledThemes() {
      if (enabledThemes) {
        return enabledThemes;
      }
      var allThemes = app.getAllThemes();
      if (isPro && isLicenseActive) {
        return allThemes;
      }
      enabledThemes = Object.keys(allThemes).reduce(function (acc, key) {
        var _allThemes$key$settin;
        if ((_allThemes$key$settin = allThemes[key].settings) !== null && _allThemes$key$settin !== void 0 && _allThemes$key$settin.fieldSize && !allThemes[key].disabled) {
          acc[key] = allThemes[key];
        }
        return acc;
      }, {});
      return enabledThemes;
    },
    /**
     * Update enabled themes.
     *
     * @since 1.8.8
     *
     * @param {string} slug  Theme slug.
     * @param {Object} theme Theme settings.
     */
    updateEnabledThemes: function updateEnabledThemes(slug, theme) {
      if (!enabledThemes) {
        return;
      }
      enabledThemes = _objectSpread(_objectSpread({}, enabledThemes), {}, _defineProperty({}, slug, theme));
    },
    /**
     * Whether the theme is disabled.
     *
     * @since 1.8.8
     *
     * @param {string} slug Theme slug.
     *
     * @return {boolean} True if the theme is disabled.
     */
    isDisabledTheme: function isDisabledTheme(slug) {
      var _app$getEnabledThemes;
      return !((_app$getEnabledThemes = app.getEnabledThemes()) !== null && _app$getEnabledThemes !== void 0 && _app$getEnabledThemes[slug]);
    },
    /**
     * Whether the theme is one of the WPForms themes.
     *
     * @since 1.8.8
     *
     * @param {string} slug Theme slug.
     *
     * @return {boolean} True if the theme is one of the WPForms themes.
     */
    isWPFormsTheme: function isWPFormsTheme(slug) {
      var _themesData$wpforms$s;
      return Boolean((_themesData$wpforms$s = themesData.wpforms[slug]) === null || _themesData$wpforms$s === void 0 ? void 0 : _themesData$wpforms$s.settings);
    },
    /**
     * Fetch themes data from API.
     *
     * @since 1.8.8
     */
    fetchThemesData: function fetchThemesData() {
      // If a fetch is already in progress, exit the function.
      if (state.isFetchingThemes || themesData.wpforms) {
        return;
      }

      // Set the flag to true indicating a fetch is in progress.
      state.isFetchingThemes = true;
      try {
        // Fetch themes data.
        wp.apiFetch({
          path: routeNamespace + 'themes/',
          method: 'GET',
          cache: 'no-cache'
        }).then(function (response) {
          themesData.wpforms = response.wpforms || {};
          themesData.custom = response.custom || {};
        }).catch(function (error) {
          // eslint-disable-next-line no-console
          console.error(error === null || error === void 0 ? void 0 : error.message);
        }).finally(function () {
          state.isFetchingThemes = false;
        });
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
      }
    },
    /**
     * Save custom themes.
     *
     * @since 1.8.8
     */
    saveCustomThemes: function saveCustomThemes() {
      // Custom themes do not exist.
      if (state.isSavingThemes || !themesData.custom) {
        return;
      }

      // Set the flag to true indicating a saving is in progress.
      state.isSavingThemes = true;
      try {
        // Save themes.
        wp.apiFetch({
          path: routeNamespace + 'themes/custom/',
          method: 'POST',
          data: {
            customThemes: themesData.custom
          }
        }).then(function (response) {
          if (!(response !== null && response !== void 0 && response.result)) {
            // eslint-disable-next-line no-console
            console.log(response === null || response === void 0 ? void 0 : response.error);
          }
        }).catch(function (error) {
          // eslint-disable-next-line no-console
          console.error(error === null || error === void 0 ? void 0 : error.message);
        }).finally(function () {
          state.isSavingThemes = false;
        });
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
      }
    },
    /**
     * Get the current style attributes state.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @return {boolean} Whether the custom theme is created.
     */
    getCurrentStyleAttributes: function getCurrentStyleAttributes(props) {
      var _themesData$wpforms$d;
      var defaultAttributes = Object.keys((_themesData$wpforms$d = themesData.wpforms.default) === null || _themesData$wpforms$d === void 0 ? void 0 : _themesData$wpforms$d.settings);
      var currentStyleAttributes = {};
      for (var key in defaultAttributes) {
        var _props$attributes$att;
        var attr = defaultAttributes[key];
        currentStyleAttributes[attr] = (_props$attributes$att = props.attributes[attr]) !== null && _props$attributes$att !== void 0 ? _props$attributes$att : '';
      }
      return currentStyleAttributes;
    },
    /**
     * Maybe create custom theme.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @return {boolean} Whether the custom theme is created.
     */
    maybeCreateCustomTheme: function maybeCreateCustomTheme(props) {
      var _themesData$wpforms$p;
      // eslint-disable-line complexity
      var currentStyles = app.getCurrentStyleAttributes(props);
      var isWPFormsTheme = !!themesData.wpforms[props.attributes.theme];
      var isCustomTheme = !!themesData.custom[props.attributes.theme];
      var migrateToCustomTheme = false;

      // It is one of the default themes without any changes.
      if (isWPFormsTheme && JSON.stringify((_themesData$wpforms$p = themesData.wpforms[props.attributes.theme]) === null || _themesData$wpforms$p === void 0 ? void 0 : _themesData$wpforms$p.settings) === JSON.stringify(currentStyles)) {
        return false;
      }
      var prevAttributes = formSelectorCommon.getBlockRuntimeStateVar(props.clientId, 'prevAttributesState');

      // It is a block added in FS 1.0, so it doesn't have a theme.
      // The `prevAttributes` is `undefined` means that we are in the first render of the existing block.
      if (props.attributes.theme === 'default' && props.attributes.themeName === '' && !prevAttributes) {
        migrateToCustomTheme = true;
      }

      // It is a modified default theme OR unknown custom theme.
      if (isWPFormsTheme || !isCustomTheme || migrateToCustomTheme) {
        app.createCustomTheme(props, currentStyles, migrateToCustomTheme);
      }
      return true;
    },
    /**
     * Create custom theme.
     *
     * @since 1.8.8
     *
     * @param {Object}  props                Block properties.
     * @param {Object}  currentStyles        Current style settings.
     * @param {boolean} migrateToCustomTheme Whether it is needed to migrate to custom theme.
     *
     * @return {boolean} Whether the custom theme is created.
     */
    createCustomTheme: function createCustomTheme(props) {
      var currentStyles = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      var migrateToCustomTheme = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      // eslint-disable-line complexity
      var counter = 0;
      var themeSlug = props.attributes.theme;
      var baseTheme = app.getTheme(props.attributes.theme) || themesData.wpforms.default;
      var themeName = baseTheme.name;
      themesData.custom = themesData.custom || {};
      if (migrateToCustomTheme) {
        themeSlug = 'custom';
        themeName = strings.theme_custom;
      }

      // Determine the theme slug and the number of copies.
      do {
        counter++;
        themeSlug = themeSlug + '-copy-' + counter;
      } while (themesData.custom[themeSlug] && counter < 10000);
      var copyStr = counter < 2 ? strings.theme_copy : strings.theme_copy + ' ' + counter;
      themeName += ' (' + copyStr + ')';

      // The first migrated Custom Theme should be without `(Copy)` suffix.
      themeName = migrateToCustomTheme && counter < 2 ? strings.theme_custom : themeName;

      // Add the new custom theme.
      themesData.custom[themeSlug] = {
        name: themeName,
        settings: currentStyles || app.getCurrentStyleAttributes(props)
      };
      app.updateEnabledThemes(themeSlug, themesData.custom[themeSlug]);

      // Update the block attributes with the new custom theme settings.
      props.setAttributes({
        theme: themeSlug,
        themeName: themeName
      });
      return true;
    },
    /**
     * Maybe create custom theme by given attributes.
     *
     * @since 1.8.8
     *
     * @param {Object} attributes Block properties.
     *
     * @return {string} New theme's slug.
     */
    maybeCreateCustomThemeFromAttributes: function maybeCreateCustomThemeFromAttributes(attributes) {
      var _attributes$themeName;
      // eslint-disable-line complexity
      var newThemeSlug = attributes.theme;
      var existingTheme = app.getTheme(attributes.theme);
      var keys = Object.keys(attributes);
      var isExistingTheme = Boolean(existingTheme === null || existingTheme === void 0 ? void 0 : existingTheme.settings);

      // Check if the theme already exists and has the same settings.
      if (isExistingTheme) {
        for (var i in keys) {
          var key = keys[i];
          if (!existingTheme.settings[key] || existingTheme.settings[key] !== attributes[key]) {
            isExistingTheme = false;
            break;
          }
        }
      }

      // The theme exists and has the same settings.
      if (isExistingTheme) {
        return newThemeSlug;
      }

      // The theme doesn't exist.
      // Normalize the attributes to the default theme settings.
      var defaultAttributes = Object.keys(themesData.wpforms.default.settings);
      var newSettings = {};
      for (var _i in defaultAttributes) {
        var _attributes$attr;
        var attr = defaultAttributes[_i];
        newSettings[attr] = (_attributes$attr = attributes[attr]) !== null && _attributes$attr !== void 0 ? _attributes$attr : '';
      }

      // Create a new custom theme.
      themesData.custom[newThemeSlug] = {
        name: (_attributes$themeName = attributes.themeName) !== null && _attributes$themeName !== void 0 ? _attributes$themeName : strings.theme_custom,
        settings: newSettings
      };
      app.updateEnabledThemes(newThemeSlug, themesData.custom[newThemeSlug]);
      return newThemeSlug;
    },
    /**
     * Update custom theme.
     *
     * @since 1.8.8
     *
     * @param {string} attribute Attribute name.
     * @param {string} value     New attribute value.
     * @param {Object} props     Block properties.
     */
    updateCustomThemeAttribute: function updateCustomThemeAttribute(attribute, value, props) {
      // eslint-disable-line complexity
      var themeSlug = props.attributes.theme;

      // Skip if it is one of the WPForms themes OR the attribute is not in the theme settings.
      if (themesData.wpforms[themeSlug] || attribute !== 'themeName' && !themesData.wpforms.default.settings[attribute]) {
        return;
      }

      // Skip if the custom theme doesn't exist.
      // It should never happen, only in some unique circumstances.
      if (!themesData.custom[themeSlug]) {
        return;
      }

      // Update theme data.
      if (attribute === 'themeName') {
        themesData.custom[themeSlug].name = value;
      } else {
        themesData.custom[themeSlug].settings = themesData.custom[themeSlug].settings || themesData.wpforms.default.settings;
        themesData.custom[themeSlug].settings[attribute] = value;
      }

      // Trigger event for developers.
      el.$window.trigger('wpformsFormSelectorUpdateTheme', [themeSlug, themesData.custom[themeSlug], props]);
    },
    /**
     * Get Themes panel JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props                    Block properties.
     * @param {Object} formSelectorCommonModule Common module.
     * @param {Object} stockPhotosModule        StockPhotos module.
     *
     * @return {Object} Themes panel JSX code.
     */
    getThemesPanel: function getThemesPanel(props, formSelectorCommonModule, stockPhotosModule) {
      // Store common module in app.
      formSelectorCommon = formSelectorCommonModule;
      state.stockPhotos = stockPhotosModule;

      // If there are no themes data, it is necessary to fetch it firstly.
      if (!themesData.wpforms) {
        app.fetchThemesData();

        // Return empty JSX code.
        return /*#__PURE__*/React.createElement(React.Fragment, null);
      }

      // Get event handlers.
      var handlers = app.getEventHandlers(props);
      var showCustomThemeOptions = isAdmin && formSelectorCommonModule.isFullStylingEnabled() && app.maybeCreateCustomTheme(props);
      var checked = formSelectorCommonModule.isFullStylingEnabled() ? props.attributes.theme : 'classic';
      var isLeadFormsEnabled = formSelectorCommonModule.isLeadFormsEnabled(formSelectorCommonModule.getBlockContainer(props));
      var displayLeadFormNotice = isLeadFormsEnabled ? 'block' : 'none';
      var modernNoticeStyles = displayLeadFormNotice === 'block' ? {
        display: 'none'
      } : {};
      var classes = formSelectorCommon.getPanelClass(props, 'themes');
      classes += isLeadFormsEnabled ? ' wpforms-lead-forms-enabled' : '';
      classes += app.isMac() ? ' wpforms-is-mac' : '';
      return /*#__PURE__*/React.createElement(PanelBody, {
        className: classes,
        title: strings.themes
      }, /*#__PURE__*/React.createElement("p", {
        className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-use-modern-notice",
        style: modernNoticeStyles
      }, /*#__PURE__*/React.createElement("strong", null, strings.use_modern_notice_head), strings.use_modern_notice_text, " ", /*#__PURE__*/React.createElement("a", {
        href: strings.use_modern_notice_link,
        rel: "noreferrer",
        target: "_blank"
      }, strings.learn_more)), /*#__PURE__*/React.createElement("p", {
        className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-lead-form-notice",
        style: {
          display: displayLeadFormNotice
        }
      }, /*#__PURE__*/React.createElement("strong", null, strings.lead_forms_panel_notice_head), strings.lead_forms_panel_notice_text), /*#__PURE__*/React.createElement(RadioGroup, {
        className: "wpforms-gutenberg-form-selector-themes-radio-group",
        label: strings.themes,
        checked: checked,
        defaultChecked: props.attributes.theme,
        onChange: function onChange(value) {
          return handlers.selectTheme(value);
        }
      }, app.getThemesItemsJSX(props)), showCustomThemeOptions && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(TextControl, {
        className: "wpforms-gutenberg-form-selector-themes-theme-name",
        label: strings.theme_name,
        value: props.attributes.themeName,
        onChange: function onChange(value) {
          return handlers.changeThemeName(value);
        }
      }), /*#__PURE__*/React.createElement(Button, {
        isSecondary: true,
        className: "wpforms-gutenberg-form-selector-themes-delete",
        onClick: handlers.deleteTheme,
        buttonSettings: ""
      }, strings.theme_delete)));
    },
    /**
     * Get the Themes panel items JSX code.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @return {Array} Themes items JSX code.
     */
    getThemesItemsJSX: function getThemesItemsJSX(props) {
      // eslint-disable-line complexity
      var allThemesData = app.getAllThemes();
      if (!allThemesData) {
        return [];
      }
      var itemsJsx = [];
      var themes = Object.keys(allThemesData);
      var theme, firstThemeSlug;

      // Display the current custom theme on the top of the list.
      if (!app.isWPFormsTheme(props.attributes.theme)) {
        firstThemeSlug = props.attributes.theme;
        itemsJsx.push(app.getThemesItemJSX(props.attributes.theme, app.getTheme(props.attributes.theme)));
      }
      for (var key in themes) {
        var slug = themes[key];

        // Skip the first theme.
        if (firstThemeSlug && firstThemeSlug === slug) {
          continue;
        }

        // Ensure that all the theme settings are present.
        theme = _objectSpread(_objectSpread({}, allThemesData.default), allThemesData[slug] || {});
        theme.settings = _objectSpread(_objectSpread({}, allThemesData.default.settings), theme.settings || {});
        itemsJsx.push(app.getThemesItemJSX(slug, theme));
      }
      return itemsJsx;
    },
    /**
     * Get the Themes panel's single item JSX code.
     *
     * @since 1.8.8
     *
     * @param {string} slug  Theme slug.
     * @param {Object} theme Theme data.
     *
     * @return {Object|null} Themes panel single item JSX code.
     */
    getThemesItemJSX: function getThemesItemJSX(slug, theme) {
      var _theme$name;
      if (!theme) {
        return null;
      }
      var title = ((_theme$name = theme.name) === null || _theme$name === void 0 ? void 0 : _theme$name.length) > 0 ? theme.name : strings.theme_noname;
      var radioClasses = 'wpforms-gutenberg-form-selector-themes-radio';
      radioClasses += app.isDisabledTheme(slug) ? ' wpforms-gutenberg-form-selector-themes-radio-disabled' : ' wpforms-gutenberg-form-selector-themes-radio-enabled';
      return /*#__PURE__*/React.createElement(Radio, {
        value: slug,
        title: title
      }, /*#__PURE__*/React.createElement("div", {
        className: radioClasses
      }, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-themes-radio-title"
      }, title)), /*#__PURE__*/React.createElement("div", {
        className: "wpforms-gutenberg-form-selector-themes-indicators"
      }, /*#__PURE__*/React.createElement(ColorIndicator, {
        colorValue: theme.settings.buttonBackgroundColor,
        title: strings.button_background,
        "data-index": "0"
      }), /*#__PURE__*/React.createElement(ColorIndicator, {
        colorValue: theme.settings.buttonTextColor,
        title: strings.button_text,
        "data-index": "1"
      }), /*#__PURE__*/React.createElement(ColorIndicator, {
        colorValue: theme.settings.labelColor,
        title: strings.field_label,
        "data-index": "2"
      }), /*#__PURE__*/React.createElement(ColorIndicator, {
        colorValue: theme.settings.labelSublabelColor,
        title: strings.field_sublabel,
        "data-index": "3"
      }), /*#__PURE__*/React.createElement(ColorIndicator, {
        colorValue: theme.settings.fieldBorderColor,
        title: strings.field_border,
        "data-index": "4"
      })));
    },
    /**
     * Set block theme.
     *
     * @since 1.8.8
     *
     * @param {Object} props     Block properties.
     * @param {string} themeSlug The theme slug.
     *
     * @return {boolean} True on success.
     */
    setBlockTheme: function setBlockTheme(props, themeSlug) {
      if (app.maybeDisplayUpgradeModal(themeSlug)) {
        return false;
      }
      var theme = app.getTheme(themeSlug);
      if (!(theme !== null && theme !== void 0 && theme.settings)) {
        return false;
      }
      var attributes = Object.keys(theme.settings);
      var block = formSelectorCommon.getBlockContainer(props);
      var container = block.querySelector("#wpforms-".concat(props.attributes.formId));

      // Overwrite block attributes with the new theme settings.
      // It is needed to rely on the theme settings only.
      var newProps = _objectSpread(_objectSpread({}, props), {}, {
        attributes: _objectSpread(_objectSpread({}, props.attributes), theme.settings)
      });

      // Update the preview with the new theme settings.
      for (var key in attributes) {
        var attr = attributes[key];
        theme.settings[attr] = theme.settings[attr] === '0' ? '0px' : theme.settings[attr];
        formSelectorCommon.updatePreviewCSSVarValue(attr, theme.settings[attr], container, newProps);
      }

      // Prepare the new attributes to be set.
      var setAttributes = _objectSpread({
        theme: themeSlug,
        themeName: theme.name
      }, theme.settings);
      if (props.setAttributes) {
        // Update the block attributes with the new theme settings.
        props.setAttributes(setAttributes);
      }

      // Trigger event for developers.
      el.$window.trigger('wpformsFormSelectorSetTheme', [block, themeSlug, props]);
      return true;
    },
    /**
     * Maybe display upgrades modal in Lite.
     *
     * @since 1.8.8
     *
     * @param {string} themeSlug The theme slug.
     *
     * @return {boolean} True if modal was displayed.
     */
    maybeDisplayUpgradeModal: function maybeDisplayUpgradeModal(themeSlug) {
      if (!app.isDisabledTheme(themeSlug)) {
        return false;
      }
      if (!isPro) {
        formSelectorCommon.education.showProModal('themes', strings.themes);
        return true;
      }
      if (!isLicenseActive) {
        formSelectorCommon.education.showLicenseModal('themes', strings.themes, 'select-theme');
        return true;
      }
      return false;
    },
    /**
     * Get themes panel event handlers.
     *
     * @since 1.8.8
     *
     * @param {Object} props Block properties.
     *
     * @type {Object}
     */
    getEventHandlers: function getEventHandlers(props) {
      // eslint-disable-line max-lines-per-function
      var commonHandlers = formSelectorCommon.getSettingsFieldsHandlers(props);
      var handlers = {
        /**
         * Select theme event handler.
         *
         * @since 1.8.8
         *
         * @param {string} value New attribute value.
         */
        selectTheme: function selectTheme(value) {
          var _state$stockPhotos;
          if (!app.setBlockTheme(props, value)) {
            return;
          }

          // Maybe open Stock Photo installation window.
          state === null || state === void 0 || (_state$stockPhotos = state.stockPhotos) === null || _state$stockPhotos === void 0 || _state$stockPhotos.onSelectTheme(value, props, app, commonHandlers);
          var block = formSelectorCommon.getBlockContainer(props);
          formSelectorCommon.setTriggerServerRender(false);
          commonHandlers.updateCopyPasteContent();

          // Trigger event for developers.
          el.$window.trigger('wpformsFormSelectorSelectTheme', [block, props, value]);
        },
        /**
         * Change theme name event handler.
         *
         * @since 1.8.8
         *
         * @param {string} value New attribute value.
         */
        changeThemeName: function changeThemeName(value) {
          formSelectorCommon.setTriggerServerRender(false);
          props.setAttributes({
            themeName: value
          });
          app.updateCustomThemeAttribute('themeName', value, props);
        },
        /**
         * Delete theme event handler.
         *
         * @since 1.8.8
         */
        deleteTheme: function deleteTheme() {
          var deleteThemeSlug = props.attributes.theme;

          // Remove theme from the theme storage.
          delete themesData.custom[deleteThemeSlug];

          // Open the confirmation modal window.
          app.deleteThemeModal(props, deleteThemeSlug, handlers);
        }
      };
      return handlers;
    },
    /**
     * Open the theme delete confirmation modal window.
     *
     * @since 1.8.8
     *
     * @param {Object} props           Block properties.
     * @param {string} deleteThemeSlug Theme slug.
     * @param {Object} handlers        Block event handlers.
     */
    deleteThemeModal: function deleteThemeModal(props, deleteThemeSlug, handlers) {
      var confirm = strings.theme_delete_confirm.replace('%1$s', "<b>".concat(props.attributes.themeName, "</b>"));
      var content = "<p class=\"wpforms-theme-delete-text\">".concat(confirm, " ").concat(strings.theme_delete_cant_undone, "</p>");
      $.confirm({
        title: strings.theme_delete_title,
        content: content,
        icon: 'wpforms-exclamation-circle',
        type: 'red',
        buttons: {
          confirm: {
            text: strings.theme_delete_yes,
            btnClass: 'btn-confirm',
            keys: ['enter'],
            action: function action() {
              // Switch to the default theme.
              handlers.selectTheme('default');

              // Trigger event for developers.
              el.$window.trigger('wpformsFormSelectorDeleteTheme', [deleteThemeSlug, props]);
            }
          },
          cancel: {
            text: strings.cancel,
            keys: ['esc']
          }
        }
      });
    },
    /**
     * Determine if the user is on a Mac.
     *
     * @return {boolean} True if the user is on a Mac.
     */
    isMac: function isMac() {
      return navigator.userAgent.includes('Macintosh');
    }
  };
  app.init();

  // Provide access to public functions/properties.
  return app;
}(document, window, jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiZG9jdW1lbnQiLCJ3aW5kb3ciLCIkIiwiX3dwJGNvbXBvbmVudHMiLCJ3cCIsImNvbXBvbmVudHMiLCJQYW5lbEJvZHkiLCJDb2xvckluZGljYXRvciIsIlRleHRDb250cm9sIiwiQnV0dG9uIiwiX3dwJGNvbXBvbmVudHMyIiwiUmFkaW8iLCJfX2V4cGVyaW1lbnRhbFJhZGlvIiwiUmFkaW9Hcm91cCIsIl9fZXhwZXJpbWVudGFsUmFkaW9Hcm91cCIsIl93cGZvcm1zX2d1dGVuYmVyZ19mbyIsIndwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IiLCJpc0FkbWluIiwiaXNQcm8iLCJpc0xpY2Vuc2VBY3RpdmUiLCJzdHJpbmdzIiwicm91dGVOYW1lc3BhY2UiLCJyb3V0ZV9uYW1lc3BhY2UiLCJmb3JtU2VsZWN0b3JDb21tb24iLCJzdGF0ZSIsInRoZW1lc0RhdGEiLCJ3cGZvcm1zIiwiY3VzdG9tIiwiZW5hYmxlZFRoZW1lcyIsImVsIiwiYXBwIiwiaW5pdCIsIiR3aW5kb3ciLCJmZXRjaFRoZW1lc0RhdGEiLCJyZWFkeSIsImV2ZW50cyIsImRhdGEiLCJzdWJzY3JpYmUiLCJfd3AkZGF0YSRzZWxlY3QiLCJfd3AkZGF0YSRzZWxlY3QyIiwiX3dwJGRhdGEkc2VsZWN0MyIsIl93cCRkYXRhJHNlbGVjdDQiLCJfY3VycmVudFBvc3QkdHlwZSIsIl9jdXJyZW50UG9zdCR0eXBlMiIsImlzU2F2aW5nUG9zdCIsInNlbGVjdCIsImlzQXV0b3NhdmluZ1Bvc3QiLCJpc1NhdmluZ1dpZGdldCIsImlzU2F2aW5nV2lkZ2V0QXJlYXMiLCJjdXJyZW50UG9zdCIsImdldEN1cnJlbnRQb3N0IiwiaXNCbG9ja09yVGVtcGxhdGUiLCJ0eXBlIiwiaW5jbHVkZXMiLCJfIiwiZGVib3VuY2UiLCJzYXZlQ3VzdG9tVGhlbWVzIiwiZ2V0QWxsVGhlbWVzIiwiX29iamVjdFNwcmVhZCIsImdldFRoZW1lIiwic2x1ZyIsImdldEVuYWJsZWRUaGVtZXMiLCJhbGxUaGVtZXMiLCJPYmplY3QiLCJrZXlzIiwicmVkdWNlIiwiYWNjIiwia2V5IiwiX2FsbFRoZW1lcyRrZXkkc2V0dGluIiwic2V0dGluZ3MiLCJmaWVsZFNpemUiLCJkaXNhYmxlZCIsInVwZGF0ZUVuYWJsZWRUaGVtZXMiLCJ0aGVtZSIsIl9kZWZpbmVQcm9wZXJ0eSIsImlzRGlzYWJsZWRUaGVtZSIsIl9hcHAkZ2V0RW5hYmxlZFRoZW1lcyIsImlzV1BGb3Jtc1RoZW1lIiwiX3RoZW1lc0RhdGEkd3Bmb3JtcyRzIiwiQm9vbGVhbiIsImlzRmV0Y2hpbmdUaGVtZXMiLCJhcGlGZXRjaCIsInBhdGgiLCJtZXRob2QiLCJjYWNoZSIsInRoZW4iLCJyZXNwb25zZSIsImNhdGNoIiwiZXJyb3IiLCJjb25zb2xlIiwibWVzc2FnZSIsImZpbmFsbHkiLCJpc1NhdmluZ1RoZW1lcyIsImN1c3RvbVRoZW1lcyIsInJlc3VsdCIsImxvZyIsImdldEN1cnJlbnRTdHlsZUF0dHJpYnV0ZXMiLCJwcm9wcyIsIl90aGVtZXNEYXRhJHdwZm9ybXMkZCIsImRlZmF1bHRBdHRyaWJ1dGVzIiwiY3VycmVudFN0eWxlQXR0cmlidXRlcyIsIl9wcm9wcyRhdHRyaWJ1dGVzJGF0dCIsImF0dHIiLCJhdHRyaWJ1dGVzIiwibWF5YmVDcmVhdGVDdXN0b21UaGVtZSIsIl90aGVtZXNEYXRhJHdwZm9ybXMkcCIsImN1cnJlbnRTdHlsZXMiLCJpc0N1c3RvbVRoZW1lIiwibWlncmF0ZVRvQ3VzdG9tVGhlbWUiLCJKU09OIiwic3RyaW5naWZ5IiwicHJldkF0dHJpYnV0ZXMiLCJnZXRCbG9ja1J1bnRpbWVTdGF0ZVZhciIsImNsaWVudElkIiwidGhlbWVOYW1lIiwiY3JlYXRlQ3VzdG9tVGhlbWUiLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bmRlZmluZWQiLCJjb3VudGVyIiwidGhlbWVTbHVnIiwiYmFzZVRoZW1lIiwibmFtZSIsInRoZW1lX2N1c3RvbSIsImNvcHlTdHIiLCJ0aGVtZV9jb3B5Iiwic2V0QXR0cmlidXRlcyIsIm1heWJlQ3JlYXRlQ3VzdG9tVGhlbWVGcm9tQXR0cmlidXRlcyIsIl9hdHRyaWJ1dGVzJHRoZW1lTmFtZSIsIm5ld1RoZW1lU2x1ZyIsImV4aXN0aW5nVGhlbWUiLCJpc0V4aXN0aW5nVGhlbWUiLCJpIiwibmV3U2V0dGluZ3MiLCJfYXR0cmlidXRlcyRhdHRyIiwidXBkYXRlQ3VzdG9tVGhlbWVBdHRyaWJ1dGUiLCJhdHRyaWJ1dGUiLCJ2YWx1ZSIsInRyaWdnZXIiLCJnZXRUaGVtZXNQYW5lbCIsImZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZSIsInN0b2NrUGhvdG9zTW9kdWxlIiwic3RvY2tQaG90b3MiLCJSZWFjdCIsImNyZWF0ZUVsZW1lbnQiLCJGcmFnbWVudCIsImhhbmRsZXJzIiwiZ2V0RXZlbnRIYW5kbGVycyIsInNob3dDdXN0b21UaGVtZU9wdGlvbnMiLCJpc0Z1bGxTdHlsaW5nRW5hYmxlZCIsImNoZWNrZWQiLCJpc0xlYWRGb3Jtc0VuYWJsZWQiLCJnZXRCbG9ja0NvbnRhaW5lciIsImRpc3BsYXlMZWFkRm9ybU5vdGljZSIsIm1vZGVybk5vdGljZVN0eWxlcyIsImRpc3BsYXkiLCJjbGFzc2VzIiwiZ2V0UGFuZWxDbGFzcyIsImlzTWFjIiwiY2xhc3NOYW1lIiwidGl0bGUiLCJ0aGVtZXMiLCJzdHlsZSIsInVzZV9tb2Rlcm5fbm90aWNlX2hlYWQiLCJ1c2VfbW9kZXJuX25vdGljZV90ZXh0IiwiaHJlZiIsInVzZV9tb2Rlcm5fbm90aWNlX2xpbmsiLCJyZWwiLCJ0YXJnZXQiLCJsZWFybl9tb3JlIiwibGVhZF9mb3Jtc19wYW5lbF9ub3RpY2VfaGVhZCIsImxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX3RleHQiLCJsYWJlbCIsImRlZmF1bHRDaGVja2VkIiwib25DaGFuZ2UiLCJzZWxlY3RUaGVtZSIsImdldFRoZW1lc0l0ZW1zSlNYIiwidGhlbWVfbmFtZSIsImNoYW5nZVRoZW1lTmFtZSIsImlzU2Vjb25kYXJ5Iiwib25DbGljayIsImRlbGV0ZVRoZW1lIiwiYnV0dG9uU2V0dGluZ3MiLCJ0aGVtZV9kZWxldGUiLCJhbGxUaGVtZXNEYXRhIiwiaXRlbXNKc3giLCJmaXJzdFRoZW1lU2x1ZyIsInB1c2giLCJnZXRUaGVtZXNJdGVtSlNYIiwiX3RoZW1lJG5hbWUiLCJ0aGVtZV9ub25hbWUiLCJyYWRpb0NsYXNzZXMiLCJjb2xvclZhbHVlIiwiYnV0dG9uQmFja2dyb3VuZENvbG9yIiwiYnV0dG9uX2JhY2tncm91bmQiLCJidXR0b25UZXh0Q29sb3IiLCJidXR0b25fdGV4dCIsImxhYmVsQ29sb3IiLCJmaWVsZF9sYWJlbCIsImxhYmVsU3VibGFiZWxDb2xvciIsImZpZWxkX3N1YmxhYmVsIiwiZmllbGRCb3JkZXJDb2xvciIsImZpZWxkX2JvcmRlciIsInNldEJsb2NrVGhlbWUiLCJtYXliZURpc3BsYXlVcGdyYWRlTW9kYWwiLCJibG9jayIsImNvbnRhaW5lciIsInF1ZXJ5U2VsZWN0b3IiLCJjb25jYXQiLCJmb3JtSWQiLCJuZXdQcm9wcyIsInVwZGF0ZVByZXZpZXdDU1NWYXJWYWx1ZSIsImVkdWNhdGlvbiIsInNob3dQcm9Nb2RhbCIsInNob3dMaWNlbnNlTW9kYWwiLCJjb21tb25IYW5kbGVycyIsImdldFNldHRpbmdzRmllbGRzSGFuZGxlcnMiLCJfc3RhdGUkc3RvY2tQaG90b3MiLCJvblNlbGVjdFRoZW1lIiwic2V0VHJpZ2dlclNlcnZlclJlbmRlciIsInVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQiLCJkZWxldGVUaGVtZVNsdWciLCJkZWxldGVUaGVtZU1vZGFsIiwiY29uZmlybSIsInRoZW1lX2RlbGV0ZV9jb25maXJtIiwicmVwbGFjZSIsImNvbnRlbnQiLCJ0aGVtZV9kZWxldGVfY2FudF91bmRvbmUiLCJ0aGVtZV9kZWxldGVfdGl0bGUiLCJpY29uIiwiYnV0dG9ucyIsInRleHQiLCJ0aGVtZV9kZWxldGVfeWVzIiwiYnRuQ2xhc3MiLCJhY3Rpb24iLCJjYW5jZWwiLCJuYXZpZ2F0b3IiLCJ1c2VyQWdlbnQiLCJqUXVlcnkiXSwic291cmNlcyI6WyJ0aGVtZXMtcGFuZWwuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IgKi9cbi8qIGpzaGludCBlczM6IGZhbHNlLCBlc3ZlcnNpb246IDYgKi9cblxuLyoqXG4gKiBAcGFyYW0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5yb3V0ZV9uYW1lc3BhY2VcbiAqIEBwYXJhbSBzdHJpbmdzLnRoZW1lX25hbWVcbiAqIEBwYXJhbSBzdHJpbmdzLnRoZW1lX2RlbGV0ZVxuICogQHBhcmFtIHN0cmluZ3MudGhlbWVfZGVsZXRlX3RpdGxlXG4gKiBAcGFyYW0gc3RyaW5ncy50aGVtZV9kZWxldGVfY29uZmlybVxuICogQHBhcmFtIHN0cmluZ3MudGhlbWVfZGVsZXRlX2NhbnRfdW5kb25lXG4gKiBAcGFyYW0gc3RyaW5ncy50aGVtZV9kZWxldGVfeWVzXG4gKiBAcGFyYW0gc3RyaW5ncy50aGVtZV9jb3B5XG4gKiBAcGFyYW0gc3RyaW5ncy50aGVtZV9jdXN0b21cbiAqIEBwYXJhbSBzdHJpbmdzLnRoZW1lX25vbmFtZVxuICogQHBhcmFtIHN0cmluZ3MuYnV0dG9uX2JhY2tncm91bmRcbiAqIEBwYXJhbSBzdHJpbmdzLmJ1dHRvbl90ZXh0XG4gKiBAcGFyYW0gc3RyaW5ncy5maWVsZF9sYWJlbFxuICogQHBhcmFtIHN0cmluZ3MuZmllbGRfc3VibGFiZWxcbiAqIEBwYXJhbSBzdHJpbmdzLmZpZWxkX2JvcmRlclxuICovXG5cbi8qKlxuICogR3V0ZW5iZXJnIGVkaXRvciBibG9jay5cbiAqXG4gKiBUaGVtZXMgcGFuZWwgbW9kdWxlLlxuICpcbiAqIEBzaW5jZSAxLjguOFxuICovXG5leHBvcnQgZGVmYXVsdCAoIGZ1bmN0aW9uKCBkb2N1bWVudCwgd2luZG93LCAkICkge1xuXHQvKipcblx0ICogV1AgY29yZSBjb21wb25lbnRzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHsgUGFuZWxCb2R5LCBDb2xvckluZGljYXRvciwgVGV4dENvbnRyb2wsIEJ1dHRvbiB9ID0gd3AuY29tcG9uZW50cztcblx0Y29uc3QgeyBfX2V4cGVyaW1lbnRhbFJhZGlvOiBSYWRpbywgX19leHBlcmltZW50YWxSYWRpb0dyb3VwOiBSYWRpb0dyb3VwIH0gPSB3cC5jb21wb25lbnRzO1xuXG5cdC8qKlxuXHQgKiBMb2NhbGl6ZWQgZGF0YSBhbGlhc2VzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHsgaXNBZG1pbiwgaXNQcm8sIGlzTGljZW5zZUFjdGl2ZSwgc3RyaW5ncywgcm91dGVfbmFtZXNwYWNlOiByb3V0ZU5hbWVzcGFjZSB9ID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvcjtcblxuXHQvKipcblx0ICogRm9ybSBzZWxlY3RvciBjb21tb24gbW9kdWxlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGxldCBmb3JtU2VsZWN0b3JDb21tb24gPSBudWxsO1xuXG5cdC8qKlxuXHQgKiBSdW50aW1lIHN0YXRlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGNvbnN0IHN0YXRlID0ge307XG5cblx0LyoqXG5cdCAqIFRoZW1lcyBkYXRhLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGNvbnN0IHRoZW1lc0RhdGEgPSB7XG5cdFx0d3Bmb3JtczogbnVsbCxcblx0XHRjdXN0b206IG51bGwsXG5cdH07XG5cblx0LyoqXG5cdCAqIEVuYWJsZWQgdGhlbWVzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGxldCBlbmFibGVkVGhlbWVzID0gbnVsbDtcblxuXHQvKipcblx0ICogRWxlbWVudHMgaG9sZGVyLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge09iamVjdH1cblx0ICovXG5cdGNvbnN0IGVsID0ge307XG5cblx0LyoqXG5cdCAqIFB1YmxpYyBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3QgYXBwID0ge1xuXHRcdC8qKlxuXHRcdCAqIEluaXRpYWxpemUgcGFuZWwuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRpbml0KCkge1xuXHRcdFx0ZWwuJHdpbmRvdyA9ICQoIHdpbmRvdyApO1xuXG5cdFx0XHRhcHAuZmV0Y2hUaGVtZXNEYXRhKCk7XG5cblx0XHRcdCQoIGFwcC5yZWFkeSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEb2N1bWVudCByZWFkeS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdHJlYWR5KCkge1xuXHRcdFx0YXBwLmV2ZW50cygpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFdmVudHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRldmVudHMoKSB7XG5cdFx0XHR3cC5kYXRhLnN1YnNjcmliZSggZnVuY3Rpb24oKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgY29tcGxleGl0eVxuXHRcdFx0XHRpZiAoICEgaXNBZG1pbiApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRjb25zdCBpc1NhdmluZ1Bvc3QgPSB3cC5kYXRhLnNlbGVjdCggJ2NvcmUvZWRpdG9yJyApPy5pc1NhdmluZ1Bvc3QoKTtcblx0XHRcdFx0Y29uc3QgaXNBdXRvc2F2aW5nUG9zdCA9IHdwLmRhdGEuc2VsZWN0KCAnY29yZS9lZGl0b3InICk/LmlzQXV0b3NhdmluZ1Bvc3QoKTtcblx0XHRcdFx0Y29uc3QgaXNTYXZpbmdXaWRnZXQgPSB3cC5kYXRhLnNlbGVjdCggJ2NvcmUvZWRpdC13aWRnZXRzJyApPy5pc1NhdmluZ1dpZGdldEFyZWFzKCk7XG5cdFx0XHRcdGNvbnN0IGN1cnJlbnRQb3N0ID0gd3AuZGF0YS5zZWxlY3QoICdjb3JlL2VkaXRvcicgKT8uZ2V0Q3VycmVudFBvc3QoKTtcblx0XHRcdFx0Y29uc3QgaXNCbG9ja09yVGVtcGxhdGUgPSBjdXJyZW50UG9zdD8udHlwZT8uaW5jbHVkZXMoICd3cF90ZW1wbGF0ZScgKSB8fCBjdXJyZW50UG9zdD8udHlwZT8uaW5jbHVkZXMoICd3cF9ibG9jaycgKTtcblxuXHRcdFx0XHRpZiAoICggISBpc1NhdmluZ1Bvc3QgJiYgISBpc1NhdmluZ1dpZGdldCAmJiAhIGlzQmxvY2tPclRlbXBsYXRlICkgfHwgaXNBdXRvc2F2aW5nUG9zdCApIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRpZiAoIGlzQmxvY2tPclRlbXBsYXRlICkge1xuXHRcdFx0XHRcdC8vIERlbGF5IHNhdmluZyBpZiB0aGlzIGlzIEZTRSBmb3IgYmV0dGVyIHBlcmZvcm1hbmNlLlxuXHRcdFx0XHRcdF8uZGVib3VuY2UoIGFwcC5zYXZlQ3VzdG9tVGhlbWVzLCA1MDAgKSgpO1xuXG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0YXBwLnNhdmVDdXN0b21UaGVtZXMoKTtcblx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGFsbCB0aGVtZXMgZGF0YS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBUaGVtZXMgZGF0YS5cblx0XHQgKi9cblx0XHRnZXRBbGxUaGVtZXMoKSB7XG5cdFx0XHRyZXR1cm4geyAuLi4oIHRoZW1lc0RhdGEuY3VzdG9tIHx8IHt9ICksIC4uLiggdGhlbWVzRGF0YS53cGZvcm1zIHx8IHt9ICkgfTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IHRoZW1lIGRhdGEuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBzbHVnIFRoZW1lIHNsdWcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtPYmplY3R8bnVsbH0gVGhlbWUgc2V0dGluZ3MuXG5cdFx0ICovXG5cdFx0Z2V0VGhlbWUoIHNsdWcgKSB7XG5cdFx0XHRyZXR1cm4gYXBwLmdldEFsbFRoZW1lcygpWyBzbHVnIF0gfHwgbnVsbDtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGVuYWJsZWQgdGhlbWVzIGRhdGEuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdH0gVGhlbWVzIGRhdGEuXG5cdFx0ICovXG5cdFx0Z2V0RW5hYmxlZFRoZW1lcygpIHtcblx0XHRcdGlmICggZW5hYmxlZFRoZW1lcyApIHtcblx0XHRcdFx0cmV0dXJuIGVuYWJsZWRUaGVtZXM7XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IGFsbFRoZW1lcyA9IGFwcC5nZXRBbGxUaGVtZXMoKTtcblxuXHRcdFx0aWYgKCBpc1BybyAmJiBpc0xpY2Vuc2VBY3RpdmUgKSB7XG5cdFx0XHRcdHJldHVybiBhbGxUaGVtZXM7XG5cdFx0XHR9XG5cblx0XHRcdGVuYWJsZWRUaGVtZXMgPSBPYmplY3Qua2V5cyggYWxsVGhlbWVzICkucmVkdWNlKCAoIGFjYywga2V5ICkgPT4ge1xuXHRcdFx0XHRpZiAoIGFsbFRoZW1lc1sga2V5IF0uc2V0dGluZ3M/LmZpZWxkU2l6ZSAmJiAhIGFsbFRoZW1lc1sga2V5IF0uZGlzYWJsZWQgKSB7XG5cdFx0XHRcdFx0YWNjWyBrZXkgXSA9IGFsbFRoZW1lc1sga2V5IF07XG5cdFx0XHRcdH1cblx0XHRcdFx0cmV0dXJuIGFjYztcblx0XHRcdH0sIHt9ICk7XG5cblx0XHRcdHJldHVybiBlbmFibGVkVGhlbWVzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBVcGRhdGUgZW5hYmxlZCB0aGVtZXMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBzbHVnICBUaGVtZSBzbHVnLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSB0aGVtZSBUaGVtZSBzZXR0aW5ncy5cblx0XHQgKi9cblx0XHR1cGRhdGVFbmFibGVkVGhlbWVzKCBzbHVnLCB0aGVtZSApIHtcblx0XHRcdGlmICggISBlbmFibGVkVGhlbWVzICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGVuYWJsZWRUaGVtZXMgPSB7XG5cdFx0XHRcdC4uLmVuYWJsZWRUaGVtZXMsXG5cdFx0XHRcdFsgc2x1ZyBdOiB0aGVtZSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFdoZXRoZXIgdGhlIHRoZW1lIGlzIGRpc2FibGVkLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gc2x1ZyBUaGVtZSBzbHVnLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgdGhlbWUgaXMgZGlzYWJsZWQuXG5cdFx0ICovXG5cdFx0aXNEaXNhYmxlZFRoZW1lKCBzbHVnICkge1xuXHRcdFx0cmV0dXJuICEgYXBwLmdldEVuYWJsZWRUaGVtZXMoKT8uWyBzbHVnIF07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFdoZXRoZXIgdGhlIHRoZW1lIGlzIG9uZSBvZiB0aGUgV1BGb3JtcyB0aGVtZXMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBzbHVnIFRoZW1lIHNsdWcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIGlmIHRoZSB0aGVtZSBpcyBvbmUgb2YgdGhlIFdQRm9ybXMgdGhlbWVzLlxuXHRcdCAqL1xuXHRcdGlzV1BGb3Jtc1RoZW1lKCBzbHVnICkge1xuXHRcdFx0cmV0dXJuIEJvb2xlYW4oIHRoZW1lc0RhdGEud3Bmb3Jtc1sgc2x1ZyBdPy5zZXR0aW5ncyApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBGZXRjaCB0aGVtZXMgZGF0YSBmcm9tIEFQSS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGZldGNoVGhlbWVzRGF0YSgpIHtcblx0XHRcdC8vIElmIGEgZmV0Y2ggaXMgYWxyZWFkeSBpbiBwcm9ncmVzcywgZXhpdCB0aGUgZnVuY3Rpb24uXG5cdFx0XHRpZiAoIHN0YXRlLmlzRmV0Y2hpbmdUaGVtZXMgfHwgdGhlbWVzRGF0YS53cGZvcm1zICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFNldCB0aGUgZmxhZyB0byB0cnVlIGluZGljYXRpbmcgYSBmZXRjaCBpcyBpbiBwcm9ncmVzcy5cblx0XHRcdHN0YXRlLmlzRmV0Y2hpbmdUaGVtZXMgPSB0cnVlO1xuXG5cdFx0XHR0cnkge1xuXHRcdFx0XHQvLyBGZXRjaCB0aGVtZXMgZGF0YS5cblx0XHRcdFx0d3AuYXBpRmV0Y2goIHtcblx0XHRcdFx0XHRwYXRoOiByb3V0ZU5hbWVzcGFjZSArICd0aGVtZXMvJyxcblx0XHRcdFx0XHRtZXRob2Q6ICdHRVQnLFxuXHRcdFx0XHRcdGNhY2hlOiAnbm8tY2FjaGUnLFxuXHRcdFx0XHR9IClcblx0XHRcdFx0XHQudGhlbiggKCByZXNwb25zZSApID0+IHtcblx0XHRcdFx0XHRcdHRoZW1lc0RhdGEud3Bmb3JtcyA9IHJlc3BvbnNlLndwZm9ybXMgfHwge307XG5cdFx0XHRcdFx0XHR0aGVtZXNEYXRhLmN1c3RvbSA9IHJlc3BvbnNlLmN1c3RvbSB8fCB7fTtcblx0XHRcdFx0XHR9IClcblx0XHRcdFx0XHQuY2F0Y2goICggZXJyb3IgKSA9PiB7XG5cdFx0XHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tY29uc29sZVxuXHRcdFx0XHRcdFx0Y29uc29sZS5lcnJvciggZXJyb3I/Lm1lc3NhZ2UgKTtcblx0XHRcdFx0XHR9IClcblx0XHRcdFx0XHQuZmluYWxseSggKCkgPT4ge1xuXHRcdFx0XHRcdFx0c3RhdGUuaXNGZXRjaGluZ1RoZW1lcyA9IGZhbHNlO1xuXHRcdFx0XHRcdH0gKTtcblx0XHRcdH0gY2F0Y2ggKCBlcnJvciApIHtcblx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnNvbGVcblx0XHRcdFx0Y29uc29sZS5lcnJvciggZXJyb3IgKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2F2ZSBjdXN0b20gdGhlbWVzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICovXG5cdFx0c2F2ZUN1c3RvbVRoZW1lcygpIHtcblx0XHRcdC8vIEN1c3RvbSB0aGVtZXMgZG8gbm90IGV4aXN0LlxuXHRcdFx0aWYgKCBzdGF0ZS5pc1NhdmluZ1RoZW1lcyB8fCAhIHRoZW1lc0RhdGEuY3VzdG9tICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFNldCB0aGUgZmxhZyB0byB0cnVlIGluZGljYXRpbmcgYSBzYXZpbmcgaXMgaW4gcHJvZ3Jlc3MuXG5cdFx0XHRzdGF0ZS5pc1NhdmluZ1RoZW1lcyA9IHRydWU7XG5cblx0XHRcdHRyeSB7XG5cdFx0XHRcdC8vIFNhdmUgdGhlbWVzLlxuXHRcdFx0XHR3cC5hcGlGZXRjaCgge1xuXHRcdFx0XHRcdHBhdGg6IHJvdXRlTmFtZXNwYWNlICsgJ3RoZW1lcy9jdXN0b20vJyxcblx0XHRcdFx0XHRtZXRob2Q6ICdQT1NUJyxcblx0XHRcdFx0XHRkYXRhOiB7IGN1c3RvbVRoZW1lczogdGhlbWVzRGF0YS5jdXN0b20gfSxcblx0XHRcdFx0fSApXG5cdFx0XHRcdFx0LnRoZW4oICggcmVzcG9uc2UgKSA9PiB7XG5cdFx0XHRcdFx0XHRpZiAoICEgcmVzcG9uc2U/LnJlc3VsdCApIHtcblx0XHRcdFx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnNvbGVcblx0XHRcdFx0XHRcdFx0Y29uc29sZS5sb2coIHJlc3BvbnNlPy5lcnJvciApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0gKVxuXHRcdFx0XHRcdC5jYXRjaCggKCBlcnJvciApID0+IHtcblx0XHRcdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25zb2xlXG5cdFx0XHRcdFx0XHRjb25zb2xlLmVycm9yKCBlcnJvcj8ubWVzc2FnZSApO1xuXHRcdFx0XHRcdH0gKVxuXHRcdFx0XHRcdC5maW5hbGx5KCAoKSA9PiB7XG5cdFx0XHRcdFx0XHRzdGF0ZS5pc1NhdmluZ1RoZW1lcyA9IGZhbHNlO1xuXHRcdFx0XHRcdH0gKTtcblx0XHRcdH0gY2F0Y2ggKCBlcnJvciApIHtcblx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnNvbGVcblx0XHRcdFx0Y29uc29sZS5lcnJvciggZXJyb3IgKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IHRoZSBjdXJyZW50IHN0eWxlIGF0dHJpYnV0ZXMgc3RhdGUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciB0aGUgY3VzdG9tIHRoZW1lIGlzIGNyZWF0ZWQuXG5cdFx0ICovXG5cdFx0Z2V0Q3VycmVudFN0eWxlQXR0cmlidXRlcyggcHJvcHMgKSB7XG5cdFx0XHRjb25zdCBkZWZhdWx0QXR0cmlidXRlcyA9IE9iamVjdC5rZXlzKCB0aGVtZXNEYXRhLndwZm9ybXMuZGVmYXVsdD8uc2V0dGluZ3MgKTtcblx0XHRcdGNvbnN0IGN1cnJlbnRTdHlsZUF0dHJpYnV0ZXMgPSB7fTtcblxuXHRcdFx0Zm9yICggY29uc3Qga2V5IGluIGRlZmF1bHRBdHRyaWJ1dGVzICkge1xuXHRcdFx0XHRjb25zdCBhdHRyID0gZGVmYXVsdEF0dHJpYnV0ZXNbIGtleSBdO1xuXG5cdFx0XHRcdGN1cnJlbnRTdHlsZUF0dHJpYnV0ZXNbIGF0dHIgXSA9IHByb3BzLmF0dHJpYnV0ZXNbIGF0dHIgXSA/PyAnJztcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGN1cnJlbnRTdHlsZUF0dHJpYnV0ZXM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE1heWJlIGNyZWF0ZSBjdXN0b20gdGhlbWUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbn0gV2hldGhlciB0aGUgY3VzdG9tIHRoZW1lIGlzIGNyZWF0ZWQuXG5cdFx0ICovXG5cdFx0bWF5YmVDcmVhdGVDdXN0b21UaGVtZSggcHJvcHMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgY29tcGxleGl0eVxuXHRcdFx0Y29uc3QgY3VycmVudFN0eWxlcyA9IGFwcC5nZXRDdXJyZW50U3R5bGVBdHRyaWJ1dGVzKCBwcm9wcyApO1xuXHRcdFx0Y29uc3QgaXNXUEZvcm1zVGhlbWUgPSAhISB0aGVtZXNEYXRhLndwZm9ybXNbIHByb3BzLmF0dHJpYnV0ZXMudGhlbWUgXTtcblx0XHRcdGNvbnN0IGlzQ3VzdG9tVGhlbWUgPSAhISB0aGVtZXNEYXRhLmN1c3RvbVsgcHJvcHMuYXR0cmlidXRlcy50aGVtZSBdO1xuXG5cdFx0XHRsZXQgbWlncmF0ZVRvQ3VzdG9tVGhlbWUgPSBmYWxzZTtcblxuXHRcdFx0Ly8gSXQgaXMgb25lIG9mIHRoZSBkZWZhdWx0IHRoZW1lcyB3aXRob3V0IGFueSBjaGFuZ2VzLlxuXHRcdFx0aWYgKFxuXHRcdFx0XHRpc1dQRm9ybXNUaGVtZSAmJlxuXHRcdFx0XHRKU09OLnN0cmluZ2lmeSggdGhlbWVzRGF0YS53cGZvcm1zWyBwcm9wcy5hdHRyaWJ1dGVzLnRoZW1lIF0/LnNldHRpbmdzICkgPT09IEpTT04uc3RyaW5naWZ5KCBjdXJyZW50U3R5bGVzIClcblx0XHRcdCkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IHByZXZBdHRyaWJ1dGVzID0gZm9ybVNlbGVjdG9yQ29tbW9uLmdldEJsb2NrUnVudGltZVN0YXRlVmFyKCBwcm9wcy5jbGllbnRJZCwgJ3ByZXZBdHRyaWJ1dGVzU3RhdGUnICk7XG5cblx0XHRcdC8vIEl0IGlzIGEgYmxvY2sgYWRkZWQgaW4gRlMgMS4wLCBzbyBpdCBkb2Vzbid0IGhhdmUgYSB0aGVtZS5cblx0XHRcdC8vIFRoZSBgcHJldkF0dHJpYnV0ZXNgIGlzIGB1bmRlZmluZWRgIG1lYW5zIHRoYXQgd2UgYXJlIGluIHRoZSBmaXJzdCByZW5kZXIgb2YgdGhlIGV4aXN0aW5nIGJsb2NrLlxuXHRcdFx0aWYgKCBwcm9wcy5hdHRyaWJ1dGVzLnRoZW1lID09PSAnZGVmYXVsdCcgJiYgcHJvcHMuYXR0cmlidXRlcy50aGVtZU5hbWUgPT09ICcnICYmICEgcHJldkF0dHJpYnV0ZXMgKSB7XG5cdFx0XHRcdG1pZ3JhdGVUb0N1c3RvbVRoZW1lID0gdHJ1ZTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gSXQgaXMgYSBtb2RpZmllZCBkZWZhdWx0IHRoZW1lIE9SIHVua25vd24gY3VzdG9tIHRoZW1lLlxuXHRcdFx0aWYgKCBpc1dQRm9ybXNUaGVtZSB8fCAhIGlzQ3VzdG9tVGhlbWUgfHwgbWlncmF0ZVRvQ3VzdG9tVGhlbWUgKSB7XG5cdFx0XHRcdGFwcC5jcmVhdGVDdXN0b21UaGVtZSggcHJvcHMsIGN1cnJlbnRTdHlsZXMsIG1pZ3JhdGVUb0N1c3RvbVRoZW1lICk7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBDcmVhdGUgY3VzdG9tIHRoZW1lLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gIHByb3BzICAgICAgICAgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9ICBjdXJyZW50U3R5bGVzICAgICAgICBDdXJyZW50IHN0eWxlIHNldHRpbmdzLlxuXHRcdCAqIEBwYXJhbSB7Ym9vbGVhbn0gbWlncmF0ZVRvQ3VzdG9tVGhlbWUgV2hldGhlciBpdCBpcyBuZWVkZWQgdG8gbWlncmF0ZSB0byBjdXN0b20gdGhlbWUuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBXaGV0aGVyIHRoZSBjdXN0b20gdGhlbWUgaXMgY3JlYXRlZC5cblx0XHQgKi9cblx0XHRjcmVhdGVDdXN0b21UaGVtZSggcHJvcHMsIGN1cnJlbnRTdHlsZXMgPSBudWxsLCBtaWdyYXRlVG9DdXN0b21UaGVtZSA9IGZhbHNlICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIGNvbXBsZXhpdHlcblx0XHRcdGxldCBjb3VudGVyID0gMDtcblx0XHRcdGxldCB0aGVtZVNsdWcgPSBwcm9wcy5hdHRyaWJ1dGVzLnRoZW1lO1xuXG5cdFx0XHRjb25zdCBiYXNlVGhlbWUgPSBhcHAuZ2V0VGhlbWUoIHByb3BzLmF0dHJpYnV0ZXMudGhlbWUgKSB8fCB0aGVtZXNEYXRhLndwZm9ybXMuZGVmYXVsdDtcblx0XHRcdGxldCB0aGVtZU5hbWUgPSBiYXNlVGhlbWUubmFtZTtcblxuXHRcdFx0dGhlbWVzRGF0YS5jdXN0b20gPSB0aGVtZXNEYXRhLmN1c3RvbSB8fCB7fTtcblxuXHRcdFx0aWYgKCBtaWdyYXRlVG9DdXN0b21UaGVtZSApIHtcblx0XHRcdFx0dGhlbWVTbHVnID0gJ2N1c3RvbSc7XG5cdFx0XHRcdHRoZW1lTmFtZSA9IHN0cmluZ3MudGhlbWVfY3VzdG9tO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBEZXRlcm1pbmUgdGhlIHRoZW1lIHNsdWcgYW5kIHRoZSBudW1iZXIgb2YgY29waWVzLlxuXHRcdFx0ZG8ge1xuXHRcdFx0XHRjb3VudGVyKys7XG5cdFx0XHRcdHRoZW1lU2x1ZyA9IHRoZW1lU2x1ZyArICctY29weS0nICsgY291bnRlcjtcblx0XHRcdH0gd2hpbGUgKCB0aGVtZXNEYXRhLmN1c3RvbVsgdGhlbWVTbHVnIF0gJiYgY291bnRlciA8IDEwMDAwICk7XG5cblx0XHRcdGNvbnN0IGNvcHlTdHIgPSBjb3VudGVyIDwgMiA/IHN0cmluZ3MudGhlbWVfY29weSA6IHN0cmluZ3MudGhlbWVfY29weSArICcgJyArIGNvdW50ZXI7XG5cblx0XHRcdHRoZW1lTmFtZSArPSAnICgnICsgY29weVN0ciArICcpJztcblxuXHRcdFx0Ly8gVGhlIGZpcnN0IG1pZ3JhdGVkIEN1c3RvbSBUaGVtZSBzaG91bGQgYmUgd2l0aG91dCBgKENvcHkpYCBzdWZmaXguXG5cdFx0XHR0aGVtZU5hbWUgPSBtaWdyYXRlVG9DdXN0b21UaGVtZSAmJiBjb3VudGVyIDwgMiA/IHN0cmluZ3MudGhlbWVfY3VzdG9tIDogdGhlbWVOYW1lO1xuXG5cdFx0XHQvLyBBZGQgdGhlIG5ldyBjdXN0b20gdGhlbWUuXG5cdFx0XHR0aGVtZXNEYXRhLmN1c3RvbVsgdGhlbWVTbHVnIF0gPSB7XG5cdFx0XHRcdG5hbWU6IHRoZW1lTmFtZSxcblx0XHRcdFx0c2V0dGluZ3M6IGN1cnJlbnRTdHlsZXMgfHwgYXBwLmdldEN1cnJlbnRTdHlsZUF0dHJpYnV0ZXMoIHByb3BzICksXG5cdFx0XHR9O1xuXG5cdFx0XHRhcHAudXBkYXRlRW5hYmxlZFRoZW1lcyggdGhlbWVTbHVnLCB0aGVtZXNEYXRhLmN1c3RvbVsgdGhlbWVTbHVnIF0gKTtcblxuXHRcdFx0Ly8gVXBkYXRlIHRoZSBibG9jayBhdHRyaWJ1dGVzIHdpdGggdGhlIG5ldyBjdXN0b20gdGhlbWUgc2V0dGluZ3MuXG5cdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCB7XG5cdFx0XHRcdHRoZW1lOiB0aGVtZVNsdWcsXG5cdFx0XHRcdHRoZW1lTmFtZSxcblx0XHRcdH0gKTtcblxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE1heWJlIGNyZWF0ZSBjdXN0b20gdGhlbWUgYnkgZ2l2ZW4gYXR0cmlidXRlcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGF0dHJpYnV0ZXMgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge3N0cmluZ30gTmV3IHRoZW1lJ3Mgc2x1Zy5cblx0XHQgKi9cblx0XHRtYXliZUNyZWF0ZUN1c3RvbVRoZW1lRnJvbUF0dHJpYnV0ZXMoIGF0dHJpYnV0ZXMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgY29tcGxleGl0eVxuXHRcdFx0Y29uc3QgbmV3VGhlbWVTbHVnID0gYXR0cmlidXRlcy50aGVtZTtcblx0XHRcdGNvbnN0IGV4aXN0aW5nVGhlbWUgPSBhcHAuZ2V0VGhlbWUoIGF0dHJpYnV0ZXMudGhlbWUgKTtcblx0XHRcdGNvbnN0IGtleXMgPSBPYmplY3Qua2V5cyggYXR0cmlidXRlcyApO1xuXG5cdFx0XHRsZXQgaXNFeGlzdGluZ1RoZW1lID0gQm9vbGVhbiggZXhpc3RpbmdUaGVtZT8uc2V0dGluZ3MgKTtcblxuXHRcdFx0Ly8gQ2hlY2sgaWYgdGhlIHRoZW1lIGFscmVhZHkgZXhpc3RzIGFuZCBoYXMgdGhlIHNhbWUgc2V0dGluZ3MuXG5cdFx0XHRpZiAoIGlzRXhpc3RpbmdUaGVtZSApIHtcblx0XHRcdFx0Zm9yICggY29uc3QgaSBpbiBrZXlzICkge1xuXHRcdFx0XHRcdGNvbnN0IGtleSA9IGtleXNbIGkgXTtcblxuXHRcdFx0XHRcdGlmICggISBleGlzdGluZ1RoZW1lLnNldHRpbmdzWyBrZXkgXSB8fCBleGlzdGluZ1RoZW1lLnNldHRpbmdzWyBrZXkgXSAhPT0gYXR0cmlidXRlc1sga2V5IF0gKSB7XG5cdFx0XHRcdFx0XHRpc0V4aXN0aW5nVGhlbWUgPSBmYWxzZTtcblxuXHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9XG5cblx0XHRcdC8vIFRoZSB0aGVtZSBleGlzdHMgYW5kIGhhcyB0aGUgc2FtZSBzZXR0aW5ncy5cblx0XHRcdGlmICggaXNFeGlzdGluZ1RoZW1lICkge1xuXHRcdFx0XHRyZXR1cm4gbmV3VGhlbWVTbHVnO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBUaGUgdGhlbWUgZG9lc24ndCBleGlzdC5cblx0XHRcdC8vIE5vcm1hbGl6ZSB0aGUgYXR0cmlidXRlcyB0byB0aGUgZGVmYXVsdCB0aGVtZSBzZXR0aW5ncy5cblx0XHRcdGNvbnN0IGRlZmF1bHRBdHRyaWJ1dGVzID0gT2JqZWN0LmtleXMoIHRoZW1lc0RhdGEud3Bmb3Jtcy5kZWZhdWx0LnNldHRpbmdzICk7XG5cdFx0XHRjb25zdCBuZXdTZXR0aW5ncyA9IHt9O1xuXG5cdFx0XHRmb3IgKCBjb25zdCBpIGluIGRlZmF1bHRBdHRyaWJ1dGVzICkge1xuXHRcdFx0XHRjb25zdCBhdHRyID0gZGVmYXVsdEF0dHJpYnV0ZXNbIGkgXTtcblxuXHRcdFx0XHRuZXdTZXR0aW5nc1sgYXR0ciBdID0gYXR0cmlidXRlc1sgYXR0ciBdID8/ICcnO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBDcmVhdGUgYSBuZXcgY3VzdG9tIHRoZW1lLlxuXHRcdFx0dGhlbWVzRGF0YS5jdXN0b21bIG5ld1RoZW1lU2x1ZyBdID0ge1xuXHRcdFx0XHRuYW1lOiBhdHRyaWJ1dGVzLnRoZW1lTmFtZSA/PyBzdHJpbmdzLnRoZW1lX2N1c3RvbSxcblx0XHRcdFx0c2V0dGluZ3M6IG5ld1NldHRpbmdzLFxuXHRcdFx0fTtcblxuXHRcdFx0YXBwLnVwZGF0ZUVuYWJsZWRUaGVtZXMoIG5ld1RoZW1lU2x1ZywgdGhlbWVzRGF0YS5jdXN0b21bIG5ld1RoZW1lU2x1ZyBdICk7XG5cblx0XHRcdHJldHVybiBuZXdUaGVtZVNsdWc7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFVwZGF0ZSBjdXN0b20gdGhlbWUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBhdHRyaWJ1dGUgQXR0cmlidXRlIG5hbWUuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlICAgICBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKi9cblx0XHR1cGRhdGVDdXN0b21UaGVtZUF0dHJpYnV0ZSggYXR0cmlidXRlLCB2YWx1ZSwgcHJvcHMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgY29tcGxleGl0eVxuXHRcdFx0Y29uc3QgdGhlbWVTbHVnID0gcHJvcHMuYXR0cmlidXRlcy50aGVtZTtcblxuXHRcdFx0Ly8gU2tpcCBpZiBpdCBpcyBvbmUgb2YgdGhlIFdQRm9ybXMgdGhlbWVzIE9SIHRoZSBhdHRyaWJ1dGUgaXMgbm90IGluIHRoZSB0aGVtZSBzZXR0aW5ncy5cblx0XHRcdGlmIChcblx0XHRcdFx0dGhlbWVzRGF0YS53cGZvcm1zWyB0aGVtZVNsdWcgXSB8fFxuXHRcdFx0XHQoXG5cdFx0XHRcdFx0YXR0cmlidXRlICE9PSAndGhlbWVOYW1lJyAmJlxuXHRcdFx0XHRcdCEgdGhlbWVzRGF0YS53cGZvcm1zLmRlZmF1bHQuc2V0dGluZ3NbIGF0dHJpYnV0ZSBdXG5cdFx0XHRcdClcblx0XHRcdCkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFNraXAgaWYgdGhlIGN1c3RvbSB0aGVtZSBkb2Vzbid0IGV4aXN0LlxuXHRcdFx0Ly8gSXQgc2hvdWxkIG5ldmVyIGhhcHBlbiwgb25seSBpbiBzb21lIHVuaXF1ZSBjaXJjdW1zdGFuY2VzLlxuXHRcdFx0aWYgKCAhIHRoZW1lc0RhdGEuY3VzdG9tWyB0aGVtZVNsdWcgXSApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBVcGRhdGUgdGhlbWUgZGF0YS5cblx0XHRcdGlmICggYXR0cmlidXRlID09PSAndGhlbWVOYW1lJyApIHtcblx0XHRcdFx0dGhlbWVzRGF0YS5jdXN0b21bIHRoZW1lU2x1ZyBdLm5hbWUgPSB2YWx1ZTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHRoZW1lc0RhdGEuY3VzdG9tWyB0aGVtZVNsdWcgXS5zZXR0aW5ncyA9IHRoZW1lc0RhdGEuY3VzdG9tWyB0aGVtZVNsdWcgXS5zZXR0aW5ncyB8fCB0aGVtZXNEYXRhLndwZm9ybXMuZGVmYXVsdC5zZXR0aW5ncztcblx0XHRcdFx0dGhlbWVzRGF0YS5jdXN0b21bIHRoZW1lU2x1ZyBdLnNldHRpbmdzWyBhdHRyaWJ1dGUgXSA9IHZhbHVlO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBUcmlnZ2VyIGV2ZW50IGZvciBkZXZlbG9wZXJzLlxuXHRcdFx0ZWwuJHdpbmRvdy50cmlnZ2VyKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclVwZGF0ZVRoZW1lJywgWyB0aGVtZVNsdWcsIHRoZW1lc0RhdGEuY3VzdG9tWyB0aGVtZVNsdWcgXSwgcHJvcHMgXSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgVGhlbWVzIHBhbmVsIEpTWCBjb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gcHJvcHMgICAgICAgICAgICAgICAgICAgIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IGZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZSBDb21tb24gbW9kdWxlLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBzdG9ja1Bob3Rvc01vZHVsZSAgICAgICAgU3RvY2tQaG90b3MgbW9kdWxlLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7T2JqZWN0fSBUaGVtZXMgcGFuZWwgSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0VGhlbWVzUGFuZWwoIHByb3BzLCBmb3JtU2VsZWN0b3JDb21tb25Nb2R1bGUsIHN0b2NrUGhvdG9zTW9kdWxlICkge1xuXHRcdFx0Ly8gU3RvcmUgY29tbW9uIG1vZHVsZSBpbiBhcHAuXG5cdFx0XHRmb3JtU2VsZWN0b3JDb21tb24gPSBmb3JtU2VsZWN0b3JDb21tb25Nb2R1bGU7XG5cdFx0XHRzdGF0ZS5zdG9ja1Bob3RvcyA9IHN0b2NrUGhvdG9zTW9kdWxlO1xuXG5cdFx0XHQvLyBJZiB0aGVyZSBhcmUgbm8gdGhlbWVzIGRhdGEsIGl0IGlzIG5lY2Vzc2FyeSB0byBmZXRjaCBpdCBmaXJzdGx5LlxuXHRcdFx0aWYgKCAhIHRoZW1lc0RhdGEud3Bmb3JtcyApIHtcblx0XHRcdFx0YXBwLmZldGNoVGhlbWVzRGF0YSgpO1xuXG5cdFx0XHRcdC8vIFJldHVybiBlbXB0eSBKU1ggY29kZS5cblx0XHRcdFx0cmV0dXJuICggPD48Lz4gKTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gR2V0IGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0Y29uc3QgaGFuZGxlcnMgPSBhcHAuZ2V0RXZlbnRIYW5kbGVycyggcHJvcHMgKTtcblx0XHRcdGNvbnN0IHNob3dDdXN0b21UaGVtZU9wdGlvbnMgPSBpc0FkbWluICYmIGZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZS5pc0Z1bGxTdHlsaW5nRW5hYmxlZCgpICYmIGFwcC5tYXliZUNyZWF0ZUN1c3RvbVRoZW1lKCBwcm9wcyApO1xuXHRcdFx0Y29uc3QgY2hlY2tlZCA9IGZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZS5pc0Z1bGxTdHlsaW5nRW5hYmxlZCgpID8gcHJvcHMuYXR0cmlidXRlcy50aGVtZSA6ICdjbGFzc2ljJztcblx0XHRcdGNvbnN0IGlzTGVhZEZvcm1zRW5hYmxlZCA9IGZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZS5pc0xlYWRGb3Jtc0VuYWJsZWQoIGZvcm1TZWxlY3RvckNvbW1vbk1vZHVsZS5nZXRCbG9ja0NvbnRhaW5lciggcHJvcHMgKSApO1xuXHRcdFx0Y29uc3QgZGlzcGxheUxlYWRGb3JtTm90aWNlID0gaXNMZWFkRm9ybXNFbmFibGVkID8gJ2Jsb2NrJyA6ICdub25lJztcblx0XHRcdGNvbnN0IG1vZGVybk5vdGljZVN0eWxlcyA9IGRpc3BsYXlMZWFkRm9ybU5vdGljZSA9PT0gJ2Jsb2NrJyA/IHsgZGlzcGxheTogJ25vbmUnIH0gOiB7fTtcblxuXHRcdFx0bGV0IGNsYXNzZXMgPSBmb3JtU2VsZWN0b3JDb21tb24uZ2V0UGFuZWxDbGFzcyggcHJvcHMsICd0aGVtZXMnICk7XG5cblx0XHRcdGNsYXNzZXMgKz0gaXNMZWFkRm9ybXNFbmFibGVkID8gJyB3cGZvcm1zLWxlYWQtZm9ybXMtZW5hYmxlZCcgOiAnJztcblx0XHRcdGNsYXNzZXMgKz0gYXBwLmlzTWFjKCkgPyAnIHdwZm9ybXMtaXMtbWFjJyA6ICcnO1xuXG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGNsYXNzZXMgfSB0aXRsZT17IHN0cmluZ3MudGhlbWVzIH0+XG5cdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlIHdwZm9ybXMtd2FybmluZyB3cGZvcm1zLXVzZS1tb2Rlcm4tbm90aWNlXCIgc3R5bGU9eyBtb2Rlcm5Ob3RpY2VTdHlsZXMgfT5cblx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX2hlYWQgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0eyBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX3RleHQgfSA8YSBocmVmPXsgc3RyaW5ncy51c2VfbW9kZXJuX25vdGljZV9saW5rIH0gcmVsPVwibm9yZWZlcnJlclwiIHRhcmdldD1cIl9ibGFua1wiPnsgc3RyaW5ncy5sZWFybl9tb3JlIH08L2E+XG5cdFx0XHRcdFx0PC9wPlxuXG5cdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlIHdwZm9ybXMtd2FybmluZyB3cGZvcm1zLWxlYWQtZm9ybS1ub3RpY2VcIiBzdHlsZT17IHsgZGlzcGxheTogZGlzcGxheUxlYWRGb3JtTm90aWNlIH0gfT5cblx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX2hlYWQgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0eyBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX3RleHQgfVxuXHRcdFx0XHRcdDwvcD5cblxuXHRcdFx0XHRcdDxSYWRpb0dyb3VwXG5cdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXRoZW1lcy1yYWRpby1ncm91cFwiXG5cdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MudGhlbWVzIH1cblx0XHRcdFx0XHRcdGNoZWNrZWQ9eyBjaGVja2VkIH1cblx0XHRcdFx0XHRcdGRlZmF1bHRDaGVja2VkPXsgcHJvcHMuYXR0cmlidXRlcy50aGVtZSB9XG5cdFx0XHRcdFx0XHRvbkNoYW5nZT17ICggdmFsdWUgKSA9PiBoYW5kbGVycy5zZWxlY3RUaGVtZSggdmFsdWUgKSB9XG5cdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0eyBhcHAuZ2V0VGhlbWVzSXRlbXNKU1goIHByb3BzICkgfVxuXHRcdFx0XHRcdDwvUmFkaW9Hcm91cD5cblx0XHRcdFx0XHR7IHNob3dDdXN0b21UaGVtZU9wdGlvbnMgJiYgKFxuXHRcdFx0XHRcdFx0PD5cblx0XHRcdFx0XHRcdFx0PFRleHRDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci10aGVtZXMtdGhlbWUtbmFtZVwiXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLnRoZW1lX25hbWUgfVxuXHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgcHJvcHMuYXR0cmlidXRlcy50aGVtZU5hbWUgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgKCB2YWx1ZSApID0+IGhhbmRsZXJzLmNoYW5nZVRoZW1lTmFtZSggdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cblx0XHRcdFx0XHRcdFx0PEJ1dHRvbiBpc1NlY29uZGFyeVxuXHRcdFx0XHRcdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItdGhlbWVzLWRlbGV0ZVwiXG5cdFx0XHRcdFx0XHRcdFx0b25DbGljaz17IGhhbmRsZXJzLmRlbGV0ZVRoZW1lIH1cblx0XHRcdFx0XHRcdFx0XHRidXR0b25TZXR0aW5ncz1cIlwiXG5cdFx0XHRcdFx0XHRcdD5cblx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MudGhlbWVfZGVsZXRlIH1cblx0XHRcdFx0XHRcdFx0PC9CdXR0b24+XG5cdFx0XHRcdFx0XHQ8Lz5cblx0XHRcdFx0XHQpIH1cblx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgdGhlIFRoZW1lcyBwYW5lbCBpdGVtcyBKU1ggY29kZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtBcnJheX0gVGhlbWVzIGl0ZW1zIEpTWCBjb2RlLlxuXHRcdCAqL1xuXHRcdGdldFRoZW1lc0l0ZW1zSlNYKCBwcm9wcyApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBjb21wbGV4aXR5XG5cdFx0XHRjb25zdCBhbGxUaGVtZXNEYXRhID0gYXBwLmdldEFsbFRoZW1lcygpO1xuXG5cdFx0XHRpZiAoICEgYWxsVGhlbWVzRGF0YSApIHtcblx0XHRcdFx0cmV0dXJuIFtdO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCBpdGVtc0pzeCA9IFtdO1xuXHRcdFx0Y29uc3QgdGhlbWVzID0gT2JqZWN0LmtleXMoIGFsbFRoZW1lc0RhdGEgKTtcblx0XHRcdGxldCB0aGVtZSwgZmlyc3RUaGVtZVNsdWc7XG5cblx0XHRcdC8vIERpc3BsYXkgdGhlIGN1cnJlbnQgY3VzdG9tIHRoZW1lIG9uIHRoZSB0b3Agb2YgdGhlIGxpc3QuXG5cdFx0XHRpZiAoICEgYXBwLmlzV1BGb3Jtc1RoZW1lKCBwcm9wcy5hdHRyaWJ1dGVzLnRoZW1lICkgKSB7XG5cdFx0XHRcdGZpcnN0VGhlbWVTbHVnID0gcHJvcHMuYXR0cmlidXRlcy50aGVtZTtcblxuXHRcdFx0XHRpdGVtc0pzeC5wdXNoKFxuXHRcdFx0XHRcdGFwcC5nZXRUaGVtZXNJdGVtSlNYKFxuXHRcdFx0XHRcdFx0cHJvcHMuYXR0cmlidXRlcy50aGVtZSxcblx0XHRcdFx0XHRcdGFwcC5nZXRUaGVtZSggcHJvcHMuYXR0cmlidXRlcy50aGVtZSApXG5cdFx0XHRcdFx0KVxuXHRcdFx0XHQpO1xuXHRcdFx0fVxuXG5cdFx0XHRmb3IgKCBjb25zdCBrZXkgaW4gdGhlbWVzICkge1xuXHRcdFx0XHRjb25zdCBzbHVnID0gdGhlbWVzWyBrZXkgXTtcblxuXHRcdFx0XHQvLyBTa2lwIHRoZSBmaXJzdCB0aGVtZS5cblx0XHRcdFx0aWYgKCBmaXJzdFRoZW1lU2x1ZyAmJiBmaXJzdFRoZW1lU2x1ZyA9PT0gc2x1ZyApIHtcblx0XHRcdFx0XHRjb250aW51ZTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdC8vIEVuc3VyZSB0aGF0IGFsbCB0aGUgdGhlbWUgc2V0dGluZ3MgYXJlIHByZXNlbnQuXG5cdFx0XHRcdHRoZW1lID0geyAuLi5hbGxUaGVtZXNEYXRhLmRlZmF1bHQsIC4uLiggYWxsVGhlbWVzRGF0YVsgc2x1ZyBdIHx8IHt9ICkgfTtcblx0XHRcdFx0dGhlbWUuc2V0dGluZ3MgPSB7IC4uLmFsbFRoZW1lc0RhdGEuZGVmYXVsdC5zZXR0aW5ncywgLi4uKCB0aGVtZS5zZXR0aW5ncyB8fCB7fSApIH07XG5cblx0XHRcdFx0aXRlbXNKc3gucHVzaCggYXBwLmdldFRoZW1lc0l0ZW1KU1goIHNsdWcsIHRoZW1lICkgKTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGl0ZW1zSnN4O1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgdGhlIFRoZW1lcyBwYW5lbCdzIHNpbmdsZSBpdGVtIEpTWCBjb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gc2x1ZyAgVGhlbWUgc2x1Zy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gdGhlbWUgVGhlbWUgZGF0YS5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge09iamVjdHxudWxsfSBUaGVtZXMgcGFuZWwgc2luZ2xlIGl0ZW0gSlNYIGNvZGUuXG5cdFx0ICovXG5cdFx0Z2V0VGhlbWVzSXRlbUpTWCggc2x1ZywgdGhlbWUgKSB7XG5cdFx0XHRpZiAoICEgdGhlbWUgKSB7XG5cdFx0XHRcdHJldHVybiBudWxsO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCB0aXRsZSA9IHRoZW1lLm5hbWU/Lmxlbmd0aCA+IDAgPyB0aGVtZS5uYW1lIDogc3RyaW5ncy50aGVtZV9ub25hbWU7XG5cdFx0XHRsZXQgcmFkaW9DbGFzc2VzID0gJ3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItdGhlbWVzLXJhZGlvJztcblxuXHRcdFx0cmFkaW9DbGFzc2VzICs9IGFwcC5pc0Rpc2FibGVkVGhlbWUoIHNsdWcgKSA/ICcgd3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci10aGVtZXMtcmFkaW8tZGlzYWJsZWQnIDogJyB3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXRoZW1lcy1yYWRpby1lbmFibGVkJztcblxuXHRcdFx0cmV0dXJuIChcblx0XHRcdFx0PFJhZGlvXG5cdFx0XHRcdFx0dmFsdWU9eyBzbHVnIH1cblx0XHRcdFx0XHR0aXRsZT17IHRpdGxlIH1cblx0XHRcdFx0PlxuXHRcdFx0XHRcdDxkaXZcblx0XHRcdFx0XHRcdGNsYXNzTmFtZT17IHJhZGlvQ2xhc3NlcyB9XG5cdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXRoZW1lcy1yYWRpby10aXRsZVwiPnsgdGl0bGUgfTwvZGl2PlxuXHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci10aGVtZXMtaW5kaWNhdG9yc1wiPlxuXHRcdFx0XHRcdFx0PENvbG9ySW5kaWNhdG9yIGNvbG9yVmFsdWU9eyB0aGVtZS5zZXR0aW5ncy5idXR0b25CYWNrZ3JvdW5kQ29sb3IgfSB0aXRsZT17IHN0cmluZ3MuYnV0dG9uX2JhY2tncm91bmQgfSBkYXRhLWluZGV4PVwiMFwiIC8+XG5cdFx0XHRcdFx0XHQ8Q29sb3JJbmRpY2F0b3IgY29sb3JWYWx1ZT17IHRoZW1lLnNldHRpbmdzLmJ1dHRvblRleHRDb2xvciB9IHRpdGxlPXsgc3RyaW5ncy5idXR0b25fdGV4dCB9IGRhdGEtaW5kZXg9XCIxXCIgLz5cblx0XHRcdFx0XHRcdDxDb2xvckluZGljYXRvciBjb2xvclZhbHVlPXsgdGhlbWUuc2V0dGluZ3MubGFiZWxDb2xvciB9IHRpdGxlPXsgc3RyaW5ncy5maWVsZF9sYWJlbCB9IGRhdGEtaW5kZXg9XCIyXCIgLz5cblx0XHRcdFx0XHRcdDxDb2xvckluZGljYXRvciBjb2xvclZhbHVlPXsgdGhlbWUuc2V0dGluZ3MubGFiZWxTdWJsYWJlbENvbG9yIH0gdGl0bGU9eyBzdHJpbmdzLmZpZWxkX3N1YmxhYmVsIH0gZGF0YS1pbmRleD1cIjNcIiAvPlxuXHRcdFx0XHRcdFx0PENvbG9ySW5kaWNhdG9yIGNvbG9yVmFsdWU9eyB0aGVtZS5zZXR0aW5ncy5maWVsZEJvcmRlckNvbG9yIH0gdGl0bGU9eyBzdHJpbmdzLmZpZWxkX2JvcmRlciB9IGRhdGEtaW5kZXg9XCI0XCIgLz5cblx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0PC9SYWRpbz5cblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCBibG9jayB0aGVtZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB0aGVtZVNsdWcgVGhlIHRoZW1lIHNsdWcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIG9uIHN1Y2Nlc3MuXG5cdFx0ICovXG5cdFx0c2V0QmxvY2tUaGVtZSggcHJvcHMsIHRoZW1lU2x1ZyApIHtcblx0XHRcdGlmICggYXBwLm1heWJlRGlzcGxheVVwZ3JhZGVNb2RhbCggdGhlbWVTbHVnICkgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgdGhlbWUgPSBhcHAuZ2V0VGhlbWUoIHRoZW1lU2x1ZyApO1xuXG5cdFx0XHRpZiAoICEgdGhlbWU/LnNldHRpbmdzICkge1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IGF0dHJpYnV0ZXMgPSBPYmplY3Qua2V5cyggdGhlbWUuc2V0dGluZ3MgKTtcblx0XHRcdGNvbnN0IGJsb2NrID0gZm9ybVNlbGVjdG9yQ29tbW9uLmdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApO1xuXHRcdFx0Y29uc3QgY29udGFpbmVyID0gYmxvY2sucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLSR7IHByb3BzLmF0dHJpYnV0ZXMuZm9ybUlkIH1gICk7XG5cblx0XHRcdC8vIE92ZXJ3cml0ZSBibG9jayBhdHRyaWJ1dGVzIHdpdGggdGhlIG5ldyB0aGVtZSBzZXR0aW5ncy5cblx0XHRcdC8vIEl0IGlzIG5lZWRlZCB0byByZWx5IG9uIHRoZSB0aGVtZSBzZXR0aW5ncyBvbmx5LlxuXHRcdFx0Y29uc3QgbmV3UHJvcHMgPSB7IC4uLnByb3BzLCBhdHRyaWJ1dGVzOiB7IC4uLnByb3BzLmF0dHJpYnV0ZXMsIC4uLnRoZW1lLnNldHRpbmdzIH0gfTtcblxuXHRcdFx0Ly8gVXBkYXRlIHRoZSBwcmV2aWV3IHdpdGggdGhlIG5ldyB0aGVtZSBzZXR0aW5ncy5cblx0XHRcdGZvciAoIGNvbnN0IGtleSBpbiBhdHRyaWJ1dGVzICkge1xuXHRcdFx0XHRjb25zdCBhdHRyID0gYXR0cmlidXRlc1sga2V5IF07XG5cblx0XHRcdFx0dGhlbWUuc2V0dGluZ3NbIGF0dHIgXSA9IHRoZW1lLnNldHRpbmdzWyBhdHRyIF0gPT09ICcwJyA/ICcwcHgnIDogdGhlbWUuc2V0dGluZ3NbIGF0dHIgXTtcblxuXHRcdFx0XHRmb3JtU2VsZWN0b3JDb21tb24udXBkYXRlUHJldmlld0NTU1ZhclZhbHVlKFxuXHRcdFx0XHRcdGF0dHIsXG5cdFx0XHRcdFx0dGhlbWUuc2V0dGluZ3NbIGF0dHIgXSxcblx0XHRcdFx0XHRjb250YWluZXIsXG5cdFx0XHRcdFx0bmV3UHJvcHNcblx0XHRcdFx0KTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gUHJlcGFyZSB0aGUgbmV3IGF0dHJpYnV0ZXMgdG8gYmUgc2V0LlxuXHRcdFx0Y29uc3Qgc2V0QXR0cmlidXRlcyA9IHtcblx0XHRcdFx0dGhlbWU6IHRoZW1lU2x1Zyxcblx0XHRcdFx0dGhlbWVOYW1lOiB0aGVtZS5uYW1lLFxuXHRcdFx0XHQuLi50aGVtZS5zZXR0aW5ncyxcblx0XHRcdH07XG5cblx0XHRcdGlmICggcHJvcHMuc2V0QXR0cmlidXRlcyApIHtcblx0XHRcdFx0Ly8gVXBkYXRlIHRoZSBibG9jayBhdHRyaWJ1dGVzIHdpdGggdGhlIG5ldyB0aGVtZSBzZXR0aW5ncy5cblx0XHRcdFx0cHJvcHMuc2V0QXR0cmlidXRlcyggc2V0QXR0cmlidXRlcyApO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBUcmlnZ2VyIGV2ZW50IGZvciBkZXZlbG9wZXJzLlxuXHRcdFx0ZWwuJHdpbmRvdy50cmlnZ2VyKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvclNldFRoZW1lJywgWyBibG9jaywgdGhlbWVTbHVnLCBwcm9wcyBdICk7XG5cblx0XHRcdHJldHVybiB0cnVlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBNYXliZSBkaXNwbGF5IHVwZ3JhZGVzIG1vZGFsIGluIExpdGUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB0aGVtZVNsdWcgVGhlIHRoZW1lIHNsdWcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIGlmIG1vZGFsIHdhcyBkaXNwbGF5ZWQuXG5cdFx0ICovXG5cdFx0bWF5YmVEaXNwbGF5VXBncmFkZU1vZGFsKCB0aGVtZVNsdWcgKSB7XG5cdFx0XHRpZiAoICEgYXBwLmlzRGlzYWJsZWRUaGVtZSggdGhlbWVTbHVnICkgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAhIGlzUHJvICkge1xuXHRcdFx0XHRmb3JtU2VsZWN0b3JDb21tb24uZWR1Y2F0aW9uLnNob3dQcm9Nb2RhbCggJ3RoZW1lcycsIHN0cmluZ3MudGhlbWVzICk7XG5cblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cblx0XHRcdGlmICggISBpc0xpY2Vuc2VBY3RpdmUgKSB7XG5cdFx0XHRcdGZvcm1TZWxlY3RvckNvbW1vbi5lZHVjYXRpb24uc2hvd0xpY2Vuc2VNb2RhbCggJ3RoZW1lcycsIHN0cmluZ3MudGhlbWVzLCAnc2VsZWN0LXRoZW1lJyApO1xuXG5cdFx0XHRcdHJldHVybiB0cnVlO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCB0aGVtZXMgcGFuZWwgZXZlbnQgaGFuZGxlcnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHR5cGUge09iamVjdH1cblx0XHQgKi9cblx0XHRnZXRFdmVudEhhbmRsZXJzKCBwcm9wcyApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBtYXgtbGluZXMtcGVyLWZ1bmN0aW9uXG5cdFx0XHRjb25zdCBjb21tb25IYW5kbGVycyA9IGZvcm1TZWxlY3RvckNvbW1vbi5nZXRTZXR0aW5nc0ZpZWxkc0hhbmRsZXJzKCBwcm9wcyApO1xuXG5cdFx0XHRjb25zdCBoYW5kbGVycyA9IHtcblx0XHRcdFx0LyoqXG5cdFx0XHRcdCAqIFNlbGVjdCB0aGVtZSBldmVudCBoYW5kbGVyLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlIE5ldyBhdHRyaWJ1dGUgdmFsdWUuXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRzZWxlY3RUaGVtZSggdmFsdWUgKSB7XG5cdFx0XHRcdFx0aWYgKCAhIGFwcC5zZXRCbG9ja1RoZW1lKCBwcm9wcywgdmFsdWUgKSApIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBNYXliZSBvcGVuIFN0b2NrIFBob3RvIGluc3RhbGxhdGlvbiB3aW5kb3cuXG5cdFx0XHRcdFx0c3RhdGU/LnN0b2NrUGhvdG9zPy5vblNlbGVjdFRoZW1lKCB2YWx1ZSwgcHJvcHMsIGFwcCwgY29tbW9uSGFuZGxlcnMgKTtcblxuXHRcdFx0XHRcdGNvbnN0IGJsb2NrID0gZm9ybVNlbGVjdG9yQ29tbW9uLmdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApO1xuXG5cdFx0XHRcdFx0Zm9ybVNlbGVjdG9yQ29tbW9uLnNldFRyaWdnZXJTZXJ2ZXJSZW5kZXIoIGZhbHNlICk7XG5cdFx0XHRcdFx0Y29tbW9uSGFuZGxlcnMudXBkYXRlQ29weVBhc3RlQ29udGVudCgpO1xuXG5cdFx0XHRcdFx0Ly8gVHJpZ2dlciBldmVudCBmb3IgZGV2ZWxvcGVycy5cblx0XHRcdFx0XHRlbC4kd2luZG93LnRyaWdnZXIoICd3cGZvcm1zRm9ybVNlbGVjdG9yU2VsZWN0VGhlbWUnLCBbIGJsb2NrLCBwcm9wcywgdmFsdWUgXSApO1xuXHRcdFx0XHR9LFxuXG5cdFx0XHRcdC8qKlxuXHRcdFx0XHQgKiBDaGFuZ2UgdGhlbWUgbmFtZSBldmVudCBoYW5kbGVyLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlIE5ldyBhdHRyaWJ1dGUgdmFsdWUuXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHRjaGFuZ2VUaGVtZU5hbWUoIHZhbHVlICkge1xuXHRcdFx0XHRcdGZvcm1TZWxlY3RvckNvbW1vbi5zZXRUcmlnZ2VyU2VydmVyUmVuZGVyKCBmYWxzZSApO1xuXHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHsgdGhlbWVOYW1lOiB2YWx1ZSB9ICk7XG5cblx0XHRcdFx0XHRhcHAudXBkYXRlQ3VzdG9tVGhlbWVBdHRyaWJ1dGUoICd0aGVtZU5hbWUnLCB2YWx1ZSwgcHJvcHMgKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogRGVsZXRlIHRoZW1lIGV2ZW50IGhhbmRsZXIuXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdFx0XHQgKi9cblx0XHRcdFx0ZGVsZXRlVGhlbWUoKSB7XG5cdFx0XHRcdFx0Y29uc3QgZGVsZXRlVGhlbWVTbHVnID0gcHJvcHMuYXR0cmlidXRlcy50aGVtZTtcblxuXHRcdFx0XHRcdC8vIFJlbW92ZSB0aGVtZSBmcm9tIHRoZSB0aGVtZSBzdG9yYWdlLlxuXHRcdFx0XHRcdGRlbGV0ZSB0aGVtZXNEYXRhLmN1c3RvbVsgZGVsZXRlVGhlbWVTbHVnIF07XG5cblx0XHRcdFx0XHQvLyBPcGVuIHRoZSBjb25maXJtYXRpb24gbW9kYWwgd2luZG93LlxuXHRcdFx0XHRcdGFwcC5kZWxldGVUaGVtZU1vZGFsKCBwcm9wcywgZGVsZXRlVGhlbWVTbHVnLCBoYW5kbGVycyApO1xuXHRcdFx0XHR9LFxuXHRcdFx0fTtcblxuXHRcdFx0cmV0dXJuIGhhbmRsZXJzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBPcGVuIHRoZSB0aGVtZSBkZWxldGUgY29uZmlybWF0aW9uIG1vZGFsIHdpbmRvdy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtPYmplY3R9IHByb3BzICAgICAgICAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBkZWxldGVUaGVtZVNsdWcgVGhlbWUgc2x1Zy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gaGFuZGxlcnMgICAgICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdCAqL1xuXHRcdGRlbGV0ZVRoZW1lTW9kYWwoIHByb3BzLCBkZWxldGVUaGVtZVNsdWcsIGhhbmRsZXJzICkge1xuXHRcdFx0Y29uc3QgY29uZmlybSA9IHN0cmluZ3MudGhlbWVfZGVsZXRlX2NvbmZpcm0ucmVwbGFjZSggJyUxJHMnLCBgPGI+JHsgcHJvcHMuYXR0cmlidXRlcy50aGVtZU5hbWUgfTwvYj5gICk7XG5cdFx0XHRjb25zdCBjb250ZW50ID0gYDxwIGNsYXNzPVwid3Bmb3Jtcy10aGVtZS1kZWxldGUtdGV4dFwiPiR7IGNvbmZpcm0gfSAkeyBzdHJpbmdzLnRoZW1lX2RlbGV0ZV9jYW50X3VuZG9uZSB9PC9wPmA7XG5cblx0XHRcdCQuY29uZmlybSgge1xuXHRcdFx0XHR0aXRsZTogc3RyaW5ncy50aGVtZV9kZWxldGVfdGl0bGUsXG5cdFx0XHRcdGNvbnRlbnQsXG5cdFx0XHRcdGljb246ICd3cGZvcm1zLWV4Y2xhbWF0aW9uLWNpcmNsZScsXG5cdFx0XHRcdHR5cGU6ICdyZWQnLFxuXHRcdFx0XHRidXR0b25zOiB7XG5cdFx0XHRcdFx0Y29uZmlybToge1xuXHRcdFx0XHRcdFx0dGV4dDogc3RyaW5ncy50aGVtZV9kZWxldGVfeWVzLFxuXHRcdFx0XHRcdFx0YnRuQ2xhc3M6ICdidG4tY29uZmlybScsXG5cdFx0XHRcdFx0XHRrZXlzOiBbICdlbnRlcicgXSxcblx0XHRcdFx0XHRcdGFjdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0Ly8gU3dpdGNoIHRvIHRoZSBkZWZhdWx0IHRoZW1lLlxuXHRcdFx0XHRcdFx0XHRoYW5kbGVycy5zZWxlY3RUaGVtZSggJ2RlZmF1bHQnICk7XG5cblx0XHRcdFx0XHRcdFx0Ly8gVHJpZ2dlciBldmVudCBmb3IgZGV2ZWxvcGVycy5cblx0XHRcdFx0XHRcdFx0ZWwuJHdpbmRvdy50cmlnZ2VyKCAnd3Bmb3Jtc0Zvcm1TZWxlY3RvckRlbGV0ZVRoZW1lJywgWyBkZWxldGVUaGVtZVNsdWcsIHByb3BzIF0gKTtcblx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRjYW5jZWw6IHtcblx0XHRcdFx0XHRcdHRleHQ6IHN0cmluZ3MuY2FuY2VsLFxuXHRcdFx0XHRcdFx0a2V5czogWyAnZXNjJyBdLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdH0sXG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERldGVybWluZSBpZiB0aGUgdXNlciBpcyBvbiBhIE1hYy5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdGhlIHVzZXIgaXMgb24gYSBNYWMuXG5cdFx0ICovXG5cdFx0aXNNYWMoKSB7XG5cdFx0XHRyZXR1cm4gbmF2aWdhdG9yLnVzZXJBZ2VudC5pbmNsdWRlcyggJ01hY2ludG9zaCcgKTtcblx0XHR9LFxuXHR9O1xuXG5cdGFwcC5pbml0KCk7XG5cblx0Ly8gUHJvdmlkZSBhY2Nlc3MgdG8gcHVibGljIGZ1bmN0aW9ucy9wcm9wZXJ0aWVzLlxuXHRyZXR1cm4gYXBwO1xufSggZG9jdW1lbnQsIHdpbmRvdywgalF1ZXJ5ICkgKTtcbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7O0FBQUE7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQU5BLElBQUFBLFFBQUEsR0FB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
},{}],22:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */
/**
 * @param wpforms_gutenberg_form_selector.stockPhotos.pictures
 * @param wpforms_gutenberg_form_selector.stockPhotos.urlPath
 * @param strings.stockInstallTheme
 * @param strings.stockInstallBg
 * @param strings.stockInstall
 * @param strings.heads_up
 * @param strings.uhoh
 * @param strings.commonError
 * @param strings.picturesTitle
 * @param strings.picturesSubTitle
 */
/**
 * Gutenberg editor block.
 *
 * Themes panel module.
 *
 * @since 1.8.8
 */
var _default = exports.default = function (document, window, $, _wpforms_gutenberg_fo, _wpforms_gutenberg_fo2) {
  /**
   * Localized data aliases.
   *
   * @since 1.8.8
   */
  var strings = wpforms_gutenberg_form_selector.strings;
  var routeNamespace = wpforms_gutenberg_form_selector.route_namespace;
  var pictureUrlPath = (_wpforms_gutenberg_fo = wpforms_gutenberg_form_selector.stockPhotos) === null || _wpforms_gutenberg_fo === void 0 ? void 0 : _wpforms_gutenberg_fo.urlPath;

  /**
   * Spinner markup.
   *
   * @since 1.8.8
   *
   * @type {string}
   */
  var spinner = '<i class="wpforms-loading-spinner wpforms-loading-white wpforms-loading-inline"></i>';

  /**
   * Runtime state.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var state = {};

  /**
   * Stock photos pictures' list.
   *
   * @since 1.8.8
   *
   * @type {Array}
   */
  var pictures = (_wpforms_gutenberg_fo2 = wpforms_gutenberg_form_selector.stockPhotos) === null || _wpforms_gutenberg_fo2 === void 0 ? void 0 : _wpforms_gutenberg_fo2.pictures;

  /**
   * Stock photos picture selector markup.
   *
   * @since 1.8.8
   *
   * @type {string}
   */
  var picturesMarkup = '';

  /**
   * Public functions and properties.
   *
   * @since 1.8.8
   *
   * @type {Object}
   */
  var app = {
    /**
     * Initialize.
     *
     * @since 1.8.8
     */
    init: function init() {
      $(app.ready);
    },
    /**
     * Document ready.
     *
     * @since 1.8.8
     */
    ready: function ready() {},
    /**
     * Open stock photos modal.
     *
     * @since 1.8.8
     *
     * @param {Object}   props                    Block properties.
     * @param {Object}   handlers                 Block handlers.
     * @param {string}   from                     From where the modal was triggered, `themes` or `bg-styles`.
     * @param {Function} setShowBackgroundPreview Function to show/hide the background preview.
     */
    openModal: function openModal(props, handlers, from, setShowBackgroundPreview) {
      // Set opener block properties.
      state.blockProps = props;
      state.blockHandlers = handlers;
      state.setShowBackgroundPreview = setShowBackgroundPreview;
      if (app.isPicturesAvailable()) {
        app.picturesModal();
        return;
      }
      app.installModal(from);
    },
    /**
     * Open stock photos install modal on select theme.
     *
     * @since 1.8.8
     *
     * @param {string} themeSlug      The theme slug.
     * @param {Object} blockProps     Block properties.
     * @param {Object} themesModule   Block properties.
     * @param {Object} commonHandlers Common handlers.
     */
    onSelectTheme: function onSelectTheme(themeSlug, blockProps, themesModule, commonHandlers) {
      var _theme$settings;
      state.themesModule = themesModule;
      state.commonHandlers = commonHandlers;
      state.themeSlug = themeSlug;
      state.blockProps = blockProps;
      if (app.isPicturesAvailable()) {
        return;
      }

      // Check only WPForms themes.
      if (!(themesModule !== null && themesModule !== void 0 && themesModule.isWPFormsTheme(themeSlug))) {
        return;
      }
      var theme = themesModule === null || themesModule === void 0 ? void 0 : themesModule.getTheme(themeSlug);
      var bgUrl = (_theme$settings = theme.settings) === null || _theme$settings === void 0 ? void 0 : _theme$settings.backgroundUrl;
      if (bgUrl !== null && bgUrl !== void 0 && bgUrl.length && bgUrl !== 'url()') {
        app.installModal('themes');
      }
    },
    /**
     * Open a modal prompting to download and install the Stock Photos.
     *
     * @since 1.8.8
     *
     * @param {string} from From where the modal was triggered, `themes` or `bg-styles`.
     */
    installModal: function installModal(from) {
      var installStr = from === 'themes' ? strings.stockInstallTheme : strings.stockInstallBg;
      $.confirm({
        title: strings.heads_up,
        content: installStr + ' ' + strings.stockInstall,
        icon: 'wpforms-exclamation-circle',
        type: 'orange',
        buttons: {
          continue: {
            text: strings.continue,
            btnClass: 'btn-confirm',
            keys: ['enter'],
            action: function action() {
              // noinspection JSUnresolvedReference
              this.$$continue.prop('disabled', true).html(spinner + strings.installing);

              // noinspection JSUnresolvedReference
              this.$$cancel.prop('disabled', true);
              app.install(this, from);
              return false;
            }
          },
          cancel: {
            text: strings.cancel,
            keys: ['esc']
          }
        }
      });
    },
    /**
     * Display the modal window with an error message.
     *
     * @since 1.8.8
     *
     * @param {string} error Error message.
     */
    errorModal: function errorModal(error) {
      $.alert({
        title: strings.uhoh,
        content: error || strings.commonError,
        icon: 'fa fa-exclamation-circle',
        type: 'red',
        buttons: {
          cancel: {
            text: strings.close,
            btnClass: 'btn-confirm',
            keys: ['enter']
          }
        }
      });
    },
    /**
     * Display the modal window with pictures.
     *
     * @since 1.8.8
     */
    picturesModal: function picturesModal() {
      state.picturesModal = $.alert({
        title: "".concat(strings.picturesTitle, "<p>").concat(strings.picturesSubTitle, "</p>"),
        content: app.getPictureMarkup(),
        type: 'picture-selector',
        boxWidth: '800px',
        closeIcon: true,
        animation: 'opacity',
        closeAnimation: 'opacity',
        buttons: false,
        onOpen: function onOpen() {
          this.$content.off('click').on('click', '.wpforms-gutenberg-stock-photos-picture', app.selectPicture);
        }
      });
    },
    /**
     * Install stock photos.
     *
     * @since 1.8.8
     *
     * @param {Object} modal The jQuery-confirm modal window object.
     * @param {string} from  From where the modal was triggered, `themes` or `bg-styles`.
     */
    install: function install(modal, from) {
      // If a fetch is already in progress, exit the function.
      if (state.isInstalling) {
        return;
      }

      // Set the flag to true indicating a fetch is in progress.
      state.isInstalling = true;
      try {
        // Fetch themes data.
        wp.apiFetch({
          path: routeNamespace + 'stock-photos/install/',
          method: 'POST',
          cache: 'no-cache'
        }).then(function (response) {
          if (!response.result) {
            app.errorModal(response.error);
            return;
          }

          // Store the pictures' data.
          pictures = response.pictures || [];

          // Update block theme or open the picture selector modal.
          if (from === 'themes') {
            var _state$themesModule;
            state.commonHandlers.styleAttrChange('backgroundUrl', '');
            (_state$themesModule = state.themesModule) === null || _state$themesModule === void 0 || _state$themesModule.setBlockTheme(state.blockProps, state.themeSlug);
          } else {
            app.picturesModal();
          }
        }).catch(function (error) {
          // eslint-disable-next-line no-console
          console.error(error === null || error === void 0 ? void 0 : error.message);
          app.errorModal("<p>".concat(strings.commonError, "</p><p>").concat(error === null || error === void 0 ? void 0 : error.message, "</p>"));
        }).finally(function () {
          state.isInstalling = false;

          // Close the modal window.
          modal.close();
        });
      } catch (error) {
        state.isInstalling = false;
        // eslint-disable-next-line no-console
        console.error(error);
        app.errorModal(strings.commonError + '<br>' + error);
      }
    },
    /**
     * Detect whether pictures' data available.
     *
     * @since 1.8.8
     *
     * @return {boolean} True if pictures' data available, false otherwise.
     */
    isPicturesAvailable: function isPicturesAvailable() {
      var _pictures;
      return Boolean((_pictures = pictures) === null || _pictures === void 0 ? void 0 : _pictures.length);
    },
    /**
     * Generate the pictures' selector markup.
     *
     * @since 1.8.8
     *
     * @return {string} Pictures' selector markup.
     */
    getPictureMarkup: function getPictureMarkup() {
      if (!app.isPicturesAvailable()) {
        return '';
      }
      if (picturesMarkup !== '') {
        return picturesMarkup;
      }
      pictures.forEach(function (picture) {
        var pictureUrl = pictureUrlPath + picture;
        picturesMarkup += "<div class=\"wpforms-gutenberg-stock-photos-picture\"\n\t\t\t\t\tdata-url=\"".concat(pictureUrl, "\"\n\t\t\t\t\tstyle=\"background-image: url( '").concat(pictureUrl, "' )\"\n\t\t\t\t></div>");
      });
      picturesMarkup = "<div class=\"wpforms-gutenberg-stock-photos-pictures-wrap\">".concat(picturesMarkup, "</div>");
      return picturesMarkup;
    },
    /**
     * Select picture event handler.
     *
     * @since 1.8.8
     */
    selectPicture: function selectPicture() {
      var _state$picturesModal;
      var pictureUrl = $(this).data('url');
      var bgUrl = "url( ".concat(pictureUrl, " )");

      // Update the block properties.
      state.blockHandlers.styleAttrChange('backgroundUrl', bgUrl);

      // Close the modal window.
      (_state$picturesModal = state.picturesModal) === null || _state$picturesModal === void 0 || _state$picturesModal.close();

      // Show the background preview.
      state.setShowBackgroundPreview(true);
    }
  };
  app.init();

  // Provide access to public functions/properties.
  return app;
}(document, window, jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfZGVmYXVsdCIsImV4cG9ydHMiLCJkZWZhdWx0IiwiZG9jdW1lbnQiLCJ3aW5kb3ciLCIkIiwiX3dwZm9ybXNfZ3V0ZW5iZXJnX2ZvIiwiX3dwZm9ybXNfZ3V0ZW5iZXJnX2ZvMiIsInN0cmluZ3MiLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwicm91dGVOYW1lc3BhY2UiLCJyb3V0ZV9uYW1lc3BhY2UiLCJwaWN0dXJlVXJsUGF0aCIsInN0b2NrUGhvdG9zIiwidXJsUGF0aCIsInNwaW5uZXIiLCJzdGF0ZSIsInBpY3R1cmVzIiwicGljdHVyZXNNYXJrdXAiLCJhcHAiLCJpbml0IiwicmVhZHkiLCJvcGVuTW9kYWwiLCJwcm9wcyIsImhhbmRsZXJzIiwiZnJvbSIsInNldFNob3dCYWNrZ3JvdW5kUHJldmlldyIsImJsb2NrUHJvcHMiLCJibG9ja0hhbmRsZXJzIiwiaXNQaWN0dXJlc0F2YWlsYWJsZSIsInBpY3R1cmVzTW9kYWwiLCJpbnN0YWxsTW9kYWwiLCJvblNlbGVjdFRoZW1lIiwidGhlbWVTbHVnIiwidGhlbWVzTW9kdWxlIiwiY29tbW9uSGFuZGxlcnMiLCJfdGhlbWUkc2V0dGluZ3MiLCJpc1dQRm9ybXNUaGVtZSIsInRoZW1lIiwiZ2V0VGhlbWUiLCJiZ1VybCIsInNldHRpbmdzIiwiYmFja2dyb3VuZFVybCIsImxlbmd0aCIsImluc3RhbGxTdHIiLCJzdG9ja0luc3RhbGxUaGVtZSIsInN0b2NrSW5zdGFsbEJnIiwiY29uZmlybSIsInRpdGxlIiwiaGVhZHNfdXAiLCJjb250ZW50Iiwic3RvY2tJbnN0YWxsIiwiaWNvbiIsInR5cGUiLCJidXR0b25zIiwiY29udGludWUiLCJ0ZXh0IiwiYnRuQ2xhc3MiLCJrZXlzIiwiYWN0aW9uIiwiJCRjb250aW51ZSIsInByb3AiLCJodG1sIiwiaW5zdGFsbGluZyIsIiQkY2FuY2VsIiwiaW5zdGFsbCIsImNhbmNlbCIsImVycm9yTW9kYWwiLCJlcnJvciIsImFsZXJ0IiwidWhvaCIsImNvbW1vbkVycm9yIiwiY2xvc2UiLCJjb25jYXQiLCJwaWN0dXJlc1RpdGxlIiwicGljdHVyZXNTdWJUaXRsZSIsImdldFBpY3R1cmVNYXJrdXAiLCJib3hXaWR0aCIsImNsb3NlSWNvbiIsImFuaW1hdGlvbiIsImNsb3NlQW5pbWF0aW9uIiwib25PcGVuIiwiJGNvbnRlbnQiLCJvZmYiLCJvbiIsInNlbGVjdFBpY3R1cmUiLCJtb2RhbCIsImlzSW5zdGFsbGluZyIsIndwIiwiYXBpRmV0Y2giLCJwYXRoIiwibWV0aG9kIiwiY2FjaGUiLCJ0aGVuIiwicmVzcG9uc2UiLCJyZXN1bHQiLCJfc3RhdGUkdGhlbWVzTW9kdWxlIiwic3R5bGVBdHRyQ2hhbmdlIiwic2V0QmxvY2tUaGVtZSIsImNhdGNoIiwiY29uc29sZSIsIm1lc3NhZ2UiLCJmaW5hbGx5IiwiX3BpY3R1cmVzIiwiQm9vbGVhbiIsImZvckVhY2giLCJwaWN0dXJlIiwicGljdHVyZVVybCIsIl9zdGF0ZSRwaWN0dXJlc01vZGFsIiwiZGF0YSIsImpRdWVyeSJdLCJzb3VyY2VzIjpbInN0b2NrLXBob3Rvcy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4vKipcbiAqIEBwYXJhbSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLnN0b2NrUGhvdG9zLnBpY3R1cmVzXG4gKiBAcGFyYW0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdG9ja1Bob3Rvcy51cmxQYXRoXG4gKiBAcGFyYW0gc3RyaW5ncy5zdG9ja0luc3RhbGxUaGVtZVxuICogQHBhcmFtIHN0cmluZ3Muc3RvY2tJbnN0YWxsQmdcbiAqIEBwYXJhbSBzdHJpbmdzLnN0b2NrSW5zdGFsbFxuICogQHBhcmFtIHN0cmluZ3MuaGVhZHNfdXBcbiAqIEBwYXJhbSBzdHJpbmdzLnVob2hcbiAqIEBwYXJhbSBzdHJpbmdzLmNvbW1vbkVycm9yXG4gKiBAcGFyYW0gc3RyaW5ncy5waWN0dXJlc1RpdGxlXG4gKiBAcGFyYW0gc3RyaW5ncy5waWN0dXJlc1N1YlRpdGxlXG4gKi9cblxuLyoqXG4gKiBHdXRlbmJlcmcgZWRpdG9yIGJsb2NrLlxuICpcbiAqIFRoZW1lcyBwYW5lbCBtb2R1bGUuXG4gKlxuICogQHNpbmNlIDEuOC44XG4gKi9cbmV4cG9ydCBkZWZhdWx0ICggZnVuY3Rpb24oIGRvY3VtZW50LCB3aW5kb3csICQgKSB7XG5cdC8qKlxuXHQgKiBMb2NhbGl6ZWQgZGF0YSBhbGlhc2VzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICovXG5cdGNvbnN0IHN0cmluZ3MgPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLnN0cmluZ3M7XG5cdGNvbnN0IHJvdXRlTmFtZXNwYWNlID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5yb3V0ZV9uYW1lc3BhY2U7XG5cdGNvbnN0IHBpY3R1cmVVcmxQYXRoID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdG9ja1Bob3Rvcz8udXJsUGF0aDtcblxuXHQvKipcblx0ICogU3Bpbm5lciBtYXJrdXAuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7c3RyaW5nfVxuXHQgKi9cblx0Y29uc3Qgc3Bpbm5lciA9ICc8aSBjbGFzcz1cIndwZm9ybXMtbG9hZGluZy1zcGlubmVyIHdwZm9ybXMtbG9hZGluZy13aGl0ZSB3cGZvcm1zLWxvYWRpbmctaW5saW5lXCI+PC9pPic7XG5cblx0LyoqXG5cdCAqIFJ1bnRpbWUgc3RhdGUuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguOFxuXHQgKlxuXHQgKiBAdHlwZSB7T2JqZWN0fVxuXHQgKi9cblx0Y29uc3Qgc3RhdGUgPSB7fTtcblxuXHQvKipcblx0ICogU3RvY2sgcGhvdG9zIHBpY3R1cmVzJyBsaXN0LlxuXHQgKlxuXHQgKiBAc2luY2UgMS44Ljhcblx0ICpcblx0ICogQHR5cGUge0FycmF5fVxuXHQgKi9cblx0bGV0IHBpY3R1cmVzID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdG9ja1Bob3Rvcz8ucGljdHVyZXM7XG5cblx0LyoqXG5cdCAqIFN0b2NrIHBob3RvcyBwaWN0dXJlIHNlbGVjdG9yIG1hcmt1cC5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqXG5cdCAqIEB0eXBlIHtzdHJpbmd9XG5cdCAqL1xuXHRsZXQgcGljdHVyZXNNYXJrdXAgPSAnJztcblxuXHQvKipcblx0ICogUHVibGljIGZ1bmN0aW9ucyBhbmQgcHJvcGVydGllcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC44XG5cdCAqXG5cdCAqIEB0eXBlIHtPYmplY3R9XG5cdCAqL1xuXHRjb25zdCBhcHAgPSB7XG5cdFx0LyoqXG5cdFx0ICogSW5pdGlhbGl6ZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqL1xuXHRcdGluaXQoKSB7XG5cdFx0XHQkKCBhcHAucmVhZHkgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRG9jdW1lbnQgcmVhZHkuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRyZWFkeSgpIHt9LFxuXG5cdFx0LyoqXG5cdFx0ICogT3BlbiBzdG9jayBwaG90b3MgbW9kYWwuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSAgIHByb3BzICAgICAgICAgICAgICAgICAgICBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqIEBwYXJhbSB7T2JqZWN0fSAgIGhhbmRsZXJzICAgICAgICAgICAgICAgICBCbG9jayBoYW5kbGVycy5cblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gICBmcm9tICAgICAgICAgICAgICAgICAgICAgRnJvbSB3aGVyZSB0aGUgbW9kYWwgd2FzIHRyaWdnZXJlZCwgYHRoZW1lc2Agb3IgYGJnLXN0eWxlc2AuXG5cdFx0ICogQHBhcmFtIHtGdW5jdGlvbn0gc2V0U2hvd0JhY2tncm91bmRQcmV2aWV3IEZ1bmN0aW9uIHRvIHNob3cvaGlkZSB0aGUgYmFja2dyb3VuZCBwcmV2aWV3LlxuXHRcdCAqL1xuXHRcdG9wZW5Nb2RhbCggcHJvcHMsIGhhbmRsZXJzLCBmcm9tLCBzZXRTaG93QmFja2dyb3VuZFByZXZpZXcgKSB7XG5cdFx0XHQvLyBTZXQgb3BlbmVyIGJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHRzdGF0ZS5ibG9ja1Byb3BzID0gcHJvcHM7XG5cdFx0XHRzdGF0ZS5ibG9ja0hhbmRsZXJzID0gaGFuZGxlcnM7XG5cdFx0XHRzdGF0ZS5zZXRTaG93QmFja2dyb3VuZFByZXZpZXcgPSBzZXRTaG93QmFja2dyb3VuZFByZXZpZXc7XG5cblx0XHRcdGlmICggYXBwLmlzUGljdHVyZXNBdmFpbGFibGUoKSApIHtcblx0XHRcdFx0YXBwLnBpY3R1cmVzTW9kYWwoKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGFwcC5pbnN0YWxsTW9kYWwoIGZyb20gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogT3BlbiBzdG9jayBwaG90b3MgaW5zdGFsbCBtb2RhbCBvbiBzZWxlY3QgdGhlbWUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB0aGVtZVNsdWcgICAgICBUaGUgdGhlbWUgc2x1Zy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gYmxvY2tQcm9wcyAgICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gdGhlbWVzTW9kdWxlICAgQmxvY2sgcHJvcGVydGllcy5cblx0XHQgKiBAcGFyYW0ge09iamVjdH0gY29tbW9uSGFuZGxlcnMgQ29tbW9uIGhhbmRsZXJzLlxuXHRcdCAqL1xuXHRcdG9uU2VsZWN0VGhlbWUoIHRoZW1lU2x1ZywgYmxvY2tQcm9wcywgdGhlbWVzTW9kdWxlLCBjb21tb25IYW5kbGVycyApIHtcblx0XHRcdHN0YXRlLnRoZW1lc01vZHVsZSA9IHRoZW1lc01vZHVsZTtcblx0XHRcdHN0YXRlLmNvbW1vbkhhbmRsZXJzID0gY29tbW9uSGFuZGxlcnM7XG5cdFx0XHRzdGF0ZS50aGVtZVNsdWcgPSB0aGVtZVNsdWc7XG5cdFx0XHRzdGF0ZS5ibG9ja1Byb3BzID0gYmxvY2tQcm9wcztcblxuXHRcdFx0aWYgKCBhcHAuaXNQaWN0dXJlc0F2YWlsYWJsZSgpICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIENoZWNrIG9ubHkgV1BGb3JtcyB0aGVtZXMuXG5cdFx0XHRpZiAoICEgdGhlbWVzTW9kdWxlPy5pc1dQRm9ybXNUaGVtZSggdGhlbWVTbHVnICkgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgdGhlbWUgPSB0aGVtZXNNb2R1bGU/LmdldFRoZW1lKCB0aGVtZVNsdWcgKTtcblx0XHRcdGNvbnN0IGJnVXJsID0gdGhlbWUuc2V0dGluZ3M/LmJhY2tncm91bmRVcmw7XG5cblx0XHRcdGlmICggYmdVcmw/Lmxlbmd0aCAmJiBiZ1VybCAhPT0gJ3VybCgpJyApIHtcblx0XHRcdFx0YXBwLmluc3RhbGxNb2RhbCggJ3RoZW1lcycgKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogT3BlbiBhIG1vZGFsIHByb21wdGluZyB0byBkb3dubG9hZCBhbmQgaW5zdGFsbCB0aGUgU3RvY2sgUGhvdG9zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gZnJvbSBGcm9tIHdoZXJlIHRoZSBtb2RhbCB3YXMgdHJpZ2dlcmVkLCBgdGhlbWVzYCBvciBgYmctc3R5bGVzYC5cblx0XHQgKi9cblx0XHRpbnN0YWxsTW9kYWwoIGZyb20gKSB7XG5cdFx0XHRjb25zdCBpbnN0YWxsU3RyID0gZnJvbSA9PT0gJ3RoZW1lcycgPyBzdHJpbmdzLnN0b2NrSW5zdGFsbFRoZW1lIDogc3RyaW5ncy5zdG9ja0luc3RhbGxCZztcblxuXHRcdFx0JC5jb25maXJtKCB7XG5cdFx0XHRcdHRpdGxlOiBzdHJpbmdzLmhlYWRzX3VwLFxuXHRcdFx0XHRjb250ZW50OiBpbnN0YWxsU3RyICsgJyAnICsgc3RyaW5ncy5zdG9ja0luc3RhbGwsXG5cdFx0XHRcdGljb246ICd3cGZvcm1zLWV4Y2xhbWF0aW9uLWNpcmNsZScsXG5cdFx0XHRcdHR5cGU6ICdvcmFuZ2UnLFxuXHRcdFx0XHRidXR0b25zOiB7XG5cdFx0XHRcdFx0Y29udGludWU6IHtcblx0XHRcdFx0XHRcdHRleHQ6IHN0cmluZ3MuY29udGludWUsXG5cdFx0XHRcdFx0XHRidG5DbGFzczogJ2J0bi1jb25maXJtJyxcblx0XHRcdFx0XHRcdGtleXM6IFsgJ2VudGVyJyBdLFxuXHRcdFx0XHRcdFx0YWN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHQvLyBub2luc3BlY3Rpb24gSlNVbnJlc29sdmVkUmVmZXJlbmNlXG5cdFx0XHRcdFx0XHRcdHRoaXMuJCRjb250aW51ZS5wcm9wKCAnZGlzYWJsZWQnLCB0cnVlIClcblx0XHRcdFx0XHRcdFx0XHQuaHRtbCggc3Bpbm5lciArIHN0cmluZ3MuaW5zdGFsbGluZyApO1xuXG5cdFx0XHRcdFx0XHRcdC8vIG5vaW5zcGVjdGlvbiBKU1VucmVzb2x2ZWRSZWZlcmVuY2Vcblx0XHRcdFx0XHRcdFx0dGhpcy4kJGNhbmNlbFxuXHRcdFx0XHRcdFx0XHRcdC5wcm9wKCAnZGlzYWJsZWQnLCB0cnVlICk7XG5cblx0XHRcdFx0XHRcdFx0YXBwLmluc3RhbGwoIHRoaXMsIGZyb20gKTtcblxuXHRcdFx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0Y2FuY2VsOiB7XG5cdFx0XHRcdFx0XHR0ZXh0OiBzdHJpbmdzLmNhbmNlbCxcblx0XHRcdFx0XHRcdGtleXM6IFsgJ2VzYycgXSxcblx0XHRcdFx0XHR9LFxuXHRcdFx0XHR9LFxuXHRcdFx0fSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEaXNwbGF5IHRoZSBtb2RhbCB3aW5kb3cgd2l0aCBhbiBlcnJvciBtZXNzYWdlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gZXJyb3IgRXJyb3IgbWVzc2FnZS5cblx0XHQgKi9cblx0XHRlcnJvck1vZGFsKCBlcnJvciApIHtcblx0XHRcdCQuYWxlcnQoIHtcblx0XHRcdFx0dGl0bGU6IHN0cmluZ3MudWhvaCxcblx0XHRcdFx0Y29udGVudDogZXJyb3IgfHwgc3RyaW5ncy5jb21tb25FcnJvcixcblx0XHRcdFx0aWNvbjogJ2ZhIGZhLWV4Y2xhbWF0aW9uLWNpcmNsZScsXG5cdFx0XHRcdHR5cGU6ICdyZWQnLFxuXHRcdFx0XHRidXR0b25zOiB7XG5cdFx0XHRcdFx0Y2FuY2VsOiB7XG5cdFx0XHRcdFx0XHR0ZXh0ICAgIDogc3RyaW5ncy5jbG9zZSxcblx0XHRcdFx0XHRcdGJ0bkNsYXNzOiAnYnRuLWNvbmZpcm0nLFxuXHRcdFx0XHRcdFx0a2V5cyAgICA6IFsgJ2VudGVyJyBdLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdH0sXG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERpc3BsYXkgdGhlIG1vZGFsIHdpbmRvdyB3aXRoIHBpY3R1cmVzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICovXG5cdFx0cGljdHVyZXNNb2RhbCgpIHtcblx0XHRcdHN0YXRlLnBpY3R1cmVzTW9kYWwgPSAkLmFsZXJ0KCB7XG5cdFx0XHRcdHRpdGxlIDogYCR7IHN0cmluZ3MucGljdHVyZXNUaXRsZSB9PHA+JHsgc3RyaW5ncy5waWN0dXJlc1N1YlRpdGxlIH08L3A+YCxcblx0XHRcdFx0Y29udGVudDogYXBwLmdldFBpY3R1cmVNYXJrdXAoKSxcblx0XHRcdFx0dHlwZTogJ3BpY3R1cmUtc2VsZWN0b3InLFxuXHRcdFx0XHRib3hXaWR0aDogJzgwMHB4Jyxcblx0XHRcdFx0Y2xvc2VJY29uOiB0cnVlLFxuXHRcdFx0XHRhbmltYXRpb246ICdvcGFjaXR5Jyxcblx0XHRcdFx0Y2xvc2VBbmltYXRpb246ICdvcGFjaXR5Jyxcblx0XHRcdFx0YnV0dG9uczogZmFsc2UsXG5cdFx0XHRcdG9uT3BlbigpIHtcblx0XHRcdFx0XHR0aGlzLiRjb250ZW50XG5cdFx0XHRcdFx0XHQub2ZmKCAnY2xpY2snIClcblx0XHRcdFx0XHRcdC5vbiggJ2NsaWNrJywgJy53cGZvcm1zLWd1dGVuYmVyZy1zdG9jay1waG90b3MtcGljdHVyZScsIGFwcC5zZWxlY3RQaWN0dXJlICk7XG5cdFx0XHRcdH0sXG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEluc3RhbGwgc3RvY2sgcGhvdG9zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge09iamVjdH0gbW9kYWwgVGhlIGpRdWVyeS1jb25maXJtIG1vZGFsIHdpbmRvdyBvYmplY3QuXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGZyb20gIEZyb20gd2hlcmUgdGhlIG1vZGFsIHdhcyB0cmlnZ2VyZWQsIGB0aGVtZXNgIG9yIGBiZy1zdHlsZXNgLlxuXHRcdCAqL1xuXHRcdGluc3RhbGwoIG1vZGFsLCBmcm9tICkge1xuXHRcdFx0Ly8gSWYgYSBmZXRjaCBpcyBhbHJlYWR5IGluIHByb2dyZXNzLCBleGl0IHRoZSBmdW5jdGlvbi5cblx0XHRcdGlmICggc3RhdGUuaXNJbnN0YWxsaW5nICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFNldCB0aGUgZmxhZyB0byB0cnVlIGluZGljYXRpbmcgYSBmZXRjaCBpcyBpbiBwcm9ncmVzcy5cblx0XHRcdHN0YXRlLmlzSW5zdGFsbGluZyA9IHRydWU7XG5cblx0XHRcdHRyeSB7XG5cdFx0XHRcdC8vIEZldGNoIHRoZW1lcyBkYXRhLlxuXHRcdFx0XHR3cC5hcGlGZXRjaCgge1xuXHRcdFx0XHRcdHBhdGg6IHJvdXRlTmFtZXNwYWNlICsgJ3N0b2NrLXBob3Rvcy9pbnN0YWxsLycsXG5cdFx0XHRcdFx0bWV0aG9kOiAnUE9TVCcsXG5cdFx0XHRcdFx0Y2FjaGU6ICduby1jYWNoZScsXG5cdFx0XHRcdH0gKS50aGVuKCAoIHJlc3BvbnNlICkgPT4ge1xuXHRcdFx0XHRcdGlmICggISByZXNwb25zZS5yZXN1bHQgKSB7XG5cdFx0XHRcdFx0XHRhcHAuZXJyb3JNb2RhbCggcmVzcG9uc2UuZXJyb3IgKTtcblxuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdC8vIFN0b3JlIHRoZSBwaWN0dXJlcycgZGF0YS5cblx0XHRcdFx0XHRwaWN0dXJlcyA9IHJlc3BvbnNlLnBpY3R1cmVzIHx8IFtdO1xuXG5cdFx0XHRcdFx0Ly8gVXBkYXRlIGJsb2NrIHRoZW1lIG9yIG9wZW4gdGhlIHBpY3R1cmUgc2VsZWN0b3IgbW9kYWwuXG5cdFx0XHRcdFx0aWYgKCBmcm9tID09PSAndGhlbWVzJyApIHtcblx0XHRcdFx0XHRcdHN0YXRlLmNvbW1vbkhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2JhY2tncm91bmRVcmwnLCAnJyApO1xuXHRcdFx0XHRcdFx0c3RhdGUudGhlbWVzTW9kdWxlPy5zZXRCbG9ja1RoZW1lKCBzdGF0ZS5ibG9ja1Byb3BzLCBzdGF0ZS50aGVtZVNsdWcgKTtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0YXBwLnBpY3R1cmVzTW9kYWwoKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0gKS5jYXRjaCggKCBlcnJvciApID0+IHtcblx0XHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tY29uc29sZVxuXHRcdFx0XHRcdGNvbnNvbGUuZXJyb3IoIGVycm9yPy5tZXNzYWdlICk7XG5cdFx0XHRcdFx0YXBwLmVycm9yTW9kYWwoIGA8cD4keyBzdHJpbmdzLmNvbW1vbkVycm9yIH08L3A+PHA+JHsgZXJyb3I/Lm1lc3NhZ2UgfTwvcD5gICk7XG5cdFx0XHRcdH0gKS5maW5hbGx5KCAoKSA9PiB7XG5cdFx0XHRcdFx0c3RhdGUuaXNJbnN0YWxsaW5nID0gZmFsc2U7XG5cblx0XHRcdFx0XHQvLyBDbG9zZSB0aGUgbW9kYWwgd2luZG93LlxuXHRcdFx0XHRcdG1vZGFsLmNsb3NlKCk7XG5cdFx0XHRcdH0gKTtcblx0XHRcdH0gY2F0Y2ggKCBlcnJvciApIHtcblx0XHRcdFx0c3RhdGUuaXNJbnN0YWxsaW5nID0gZmFsc2U7XG5cdFx0XHRcdC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1jb25zb2xlXG5cdFx0XHRcdGNvbnNvbGUuZXJyb3IoIGVycm9yICk7XG5cdFx0XHRcdGFwcC5lcnJvck1vZGFsKCBzdHJpbmdzLmNvbW1vbkVycm9yICsgJzxicj4nICsgZXJyb3IgKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRGV0ZWN0IHdoZXRoZXIgcGljdHVyZXMnIGRhdGEgYXZhaWxhYmxlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC44XG5cdFx0ICpcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSBUcnVlIGlmIHBpY3R1cmVzJyBkYXRhIGF2YWlsYWJsZSwgZmFsc2Ugb3RoZXJ3aXNlLlxuXHRcdCAqL1xuXHRcdGlzUGljdHVyZXNBdmFpbGFibGUoKSB7XG5cdFx0XHRyZXR1cm4gQm9vbGVhbiggcGljdHVyZXM/Lmxlbmd0aCApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZW5lcmF0ZSB0aGUgcGljdHVyZXMnIHNlbGVjdG9yIG1hcmt1cC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguOFxuXHRcdCAqXG5cdFx0ICogQHJldHVybiB7c3RyaW5nfSBQaWN0dXJlcycgc2VsZWN0b3IgbWFya3VwLlxuXHRcdCAqL1xuXHRcdGdldFBpY3R1cmVNYXJrdXAoKSB7XG5cdFx0XHRpZiAoICEgYXBwLmlzUGljdHVyZXNBdmFpbGFibGUoKSApIHtcblx0XHRcdFx0cmV0dXJuICcnO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHBpY3R1cmVzTWFya3VwICE9PSAnJyApIHtcblx0XHRcdFx0cmV0dXJuIHBpY3R1cmVzTWFya3VwO1xuXHRcdFx0fVxuXG5cdFx0XHRwaWN0dXJlcy5mb3JFYWNoKCAoIHBpY3R1cmUgKSA9PiB7XG5cdFx0XHRcdGNvbnN0IHBpY3R1cmVVcmwgPSBwaWN0dXJlVXJsUGF0aCArIHBpY3R1cmU7XG5cblx0XHRcdFx0cGljdHVyZXNNYXJrdXAgKz0gYDxkaXYgY2xhc3M9XCJ3cGZvcm1zLWd1dGVuYmVyZy1zdG9jay1waG90b3MtcGljdHVyZVwiXG5cdFx0XHRcdFx0ZGF0YS11cmw9XCIkeyBwaWN0dXJlVXJsIH1cIlxuXHRcdFx0XHRcdHN0eWxlPVwiYmFja2dyb3VuZC1pbWFnZTogdXJsKCAnJHsgcGljdHVyZVVybCB9JyApXCJcblx0XHRcdFx0PjwvZGl2PmA7XG5cdFx0XHR9ICk7XG5cblx0XHRcdHBpY3R1cmVzTWFya3VwID0gYDxkaXYgY2xhc3M9XCJ3cGZvcm1zLWd1dGVuYmVyZy1zdG9jay1waG90b3MtcGljdHVyZXMtd3JhcFwiPiR7IHBpY3R1cmVzTWFya3VwIH08L2Rpdj5gO1xuXG5cdFx0XHRyZXR1cm4gcGljdHVyZXNNYXJrdXA7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNlbGVjdCBwaWN0dXJlIGV2ZW50IGhhbmRsZXIuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44Ljhcblx0XHQgKi9cblx0XHRzZWxlY3RQaWN0dXJlKCkge1xuXHRcdFx0Y29uc3QgcGljdHVyZVVybCA9ICQoIHRoaXMgKS5kYXRhKCAndXJsJyApO1xuXHRcdFx0Y29uc3QgYmdVcmwgPSBgdXJsKCAkeyBwaWN0dXJlVXJsIH0gKWA7XG5cblx0XHRcdC8vIFVwZGF0ZSB0aGUgYmxvY2sgcHJvcGVydGllcy5cblx0XHRcdHN0YXRlLmJsb2NrSGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYmFja2dyb3VuZFVybCcsIGJnVXJsICk7XG5cblx0XHRcdC8vIENsb3NlIHRoZSBtb2RhbCB3aW5kb3cuXG5cdFx0XHRzdGF0ZS5waWN0dXJlc01vZGFsPy5jbG9zZSgpO1xuXG5cdFx0XHQvLyBTaG93IHRoZSBiYWNrZ3JvdW5kIHByZXZpZXcuXG5cdFx0XHRzdGF0ZS5zZXRTaG93QmFja2dyb3VuZFByZXZpZXcoIHRydWUgKTtcblx0XHR9LFxuXHR9O1xuXG5cdGFwcC5pbml0KCk7XG5cblx0Ly8gUHJvdmlkZSBhY2Nlc3MgdG8gcHVibGljIGZ1bmN0aW9ucy9wcm9wZXJ0aWVzLlxuXHRyZXR1cm4gYXBwO1xufSggZG9jdW1lbnQsIHdpbmRvdywgalF1ZXJ5ICkgKTtcbiJdLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQUE7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBTkEsSUFBQUEsUUFBQSxHQUFBQyxPQUFBLENBQUFDLE9BQUEsR0FPaUIsVUFBVUMsUUFBUSxFQUFFQyxNQUFNLEVBQUVDLENBQUMsRUFBQUMscUJBQUEsRUFBQUMsc0JBQUEsRUFBRztFQUNoRDtBQUNEO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBTUMsT0FBTyxHQUFHQywrQkFBK0IsQ0FBQ0QsT0FBTztFQUN2RCxJQUFNRSxjQUFjLEdBQUdELCtCQUErQixDQUFDRSxlQUFlO0VBQ3RFLElBQU1DLGNBQWMsSUFBQU4scUJBQUEsR0FBR0csK0JBQStCLENBQUNJLFdBQVcsY0FBQVAscUJBQUEsdUJBQTNDQSxxQkFBQSxDQUE2Q1EsT0FBTzs7RUFFM0U7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxJQUFNQyxPQUFPLEdBQUcsc0ZBQXNGOztFQUV0RztBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQU1DLEtBQUssR0FBRyxDQUFDLENBQUM7O0VBRWhCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBSUMsUUFBUSxJQUFBVixzQkFBQSxHQUFHRSwrQkFBK0IsQ0FBQ0ksV0FBVyxjQUFBTixzQkFBQSx1QkFBM0NBLHNCQUFBLENBQTZDVSxRQUFROztFQUVwRTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQUlDLGNBQWMsR0FBRyxFQUFFOztFQUV2QjtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQU1DLEdBQUcsR0FBRztJQUNYO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7SUFDRUMsSUFBSSxXQUFKQSxJQUFJQSxDQUFBLEVBQUc7TUFDTmYsQ0FBQyxDQUFFYyxHQUFHLENBQUNFLEtBQU0sQ0FBQztJQUNmLENBQUM7SUFFRDtBQUNGO0FBQ0E7QUFDQTtBQUNBO0lBQ0VBLEtBQUssV0FBTEEsS0FBS0EsQ0FBQSxFQUFHLENBQUMsQ0FBQztJQUVWO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0lBQ0VDLFNBQVMsV0FBVEEsU0FBU0EsQ0FBRUMsS0FBSyxFQUFFQyxRQUFRLEVBQUVDLElBQUksRUFBRUMsd0JBQXdCLEVBQUc7TUFDNUQ7TUFDQVYsS0FBSyxDQUFDVyxVQUFVLEdBQUdKLEtBQUs7TUFDeEJQLEtBQUssQ0FBQ1ksYUFBYSxHQUFHSixRQUFRO01BQzlCUixLQUFLLENBQUNVLHdCQUF3QixHQUFHQSx3QkFBd0I7TUFFekQsSUFBS1AsR0FBRyxDQUFDVSxtQkFBbUIsQ0FBQyxDQUFDLEVBQUc7UUFDaENWLEdBQUcsQ0FBQ1csYUFBYSxDQUFDLENBQUM7UUFFbkI7TUFDRDtNQUVBWCxHQUFHLENBQUNZLFlBQVksQ0FBRU4sSUFBSyxDQUFDO0lBQ3pCLENBQUM7SUFFRDtBQUNGO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtJQUNFTyxhQUFhLFdBQWJBLGFBQWFBLENBQUVDLFNBQVMsRUFBRU4sVUFBVSxFQUFFTyxZQUFZLEVBQUVDLGNBQWMsRUFBRztNQUFBLElBQUFDLGVBQUE7TUFDcEVwQixLQUFLLENBQUNrQixZQUFZLEdBQUdBLFlBQVk7TUFDakNsQixLQUFLLENBQUNtQixjQUFjLEdBQUdBLGNBQWM7TUFDckNuQixLQUFLLENBQUNpQixTQUFTLEdBQUdBLFNBQVM7TUFDM0JqQixLQUFLLENBQUNXLFVBQVUsR0FBR0EsVUFBVTtNQUU3QixJQUFLUixHQUFHLENBQUNVLG1CQUFtQixDQUFDLENBQUMsRUFBRztRQUNoQztNQUNEOztNQUVBO01BQ0EsSUFBSyxFQUFFSyxZQUFZLGFBQVpBLFlBQVksZUFBWkEsWUFBWSxDQUFFRyxjQUFjLENBQUVKLFNBQVUsQ0FBQyxHQUFHO1FBQ2xEO01BQ0Q7TUFFQSxJQUFNSyxLQUFLLEdBQUdKLFlBQVksYUFBWkEsWUFBWSx1QkFBWkEsWUFBWSxDQUFFSyxRQUFRLENBQUVOLFNBQVUsQ0FBQztNQUNqRCxJQUFNTyxLQUFLLElBQUFKLGVBQUEsR0FBR0UsS0FBSyxDQUFDRyxRQUFRLGNBQUFMLGVBQUEsdUJBQWRBLGVBQUEsQ0FBZ0JNLGFBQWE7TUFFM0MsSUFBS0YsS0FBSyxhQUFMQSxLQUFLLGVBQUxBLEtBQUssQ0FBRUcsTUFBTSxJQUFJSCxLQUFLLEtBQUssT0FBTyxFQUFHO1FBQ3pDckIsR0FBRyxDQUFDWSxZQUFZLENBQUUsUUFBUyxDQUFDO01BQzdCO0lBQ0QsQ0FBQztJQUVEO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0lBQ0VBLFlBQVksV0FBWkEsWUFBWUEsQ0FBRU4sSUFBSSxFQUFHO01BQ3BCLElBQU1tQixVQUFVLEdBQUduQixJQUFJLEtBQUssUUFBUSxHQUFHakIsT0FBTyxDQUFDcUMsaUJBQWlCLEdBQUdyQyxPQUFPLENBQUNzQyxjQUFjO01BRXpGekMsQ0FBQyxDQUFDMEMsT0FBTyxDQUFFO1FBQ1ZDLEtBQUssRUFBRXhDLE9BQU8sQ0FBQ3lDLFFBQVE7UUFDdkJDLE9BQU8sRUFBRU4sVUFBVSxHQUFHLEdBQUcsR0FBR3BDLE9BQU8sQ0FBQzJDLFlBQVk7UUFDaERDLElBQUksRUFBRSw0QkFBNEI7UUFDbENDLElBQUksRUFBRSxRQUFRO1FBQ2RDLE9BQU8sRUFBRTtVQUNSQyxRQUFRLEVBQUU7WUFDVEMsSUFBSSxFQUFFaEQsT0FBTyxDQUFDK0MsUUFBUTtZQUN0QkUsUUFBUSxFQUFFLGFBQWE7WUFDdkJDLElBQUksRUFBRSxDQUFFLE9BQU8sQ0FBRTtZQUNqQkMsTUFBTSxXQUFOQSxNQUFNQSxDQUFBLEVBQUc7Y0FDUjtjQUNBLElBQUksQ0FBQ0MsVUFBVSxDQUFDQyxJQUFJLENBQUUsVUFBVSxFQUFFLElBQUssQ0FBQyxDQUN0Q0MsSUFBSSxDQUFFL0MsT0FBTyxHQUFHUCxPQUFPLENBQUN1RCxVQUFXLENBQUM7O2NBRXRDO2NBQ0EsSUFBSSxDQUFDQyxRQUFRLENBQ1hILElBQUksQ0FBRSxVQUFVLEVBQUUsSUFBSyxDQUFDO2NBRTFCMUMsR0FBRyxDQUFDOEMsT0FBTyxDQUFFLElBQUksRUFBRXhDLElBQUssQ0FBQztjQUV6QixPQUFPLEtBQUs7WUFDYjtVQUNELENBQUM7VUFDRHlDLE1BQU0sRUFBRTtZQUNQVixJQUFJLEVBQUVoRCxPQUFPLENBQUMwRCxNQUFNO1lBQ3BCUixJQUFJLEVBQUUsQ0FBRSxLQUFLO1VBQ2Q7UUFDRDtNQUNELENBQUUsQ0FBQztJQUNKLENBQUM7SUFFRDtBQUNGO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtJQUNFUyxVQUFVLFdBQVZBLFVBQVVBLENBQUVDLEtBQUssRUFBRztNQUNuQi9ELENBQUMsQ0FBQ2dFLEtBQUssQ0FBRTtRQUNSckIsS0FBSyxFQUFFeEMsT0FBTyxDQUFDOEQsSUFBSTtRQUNuQnBCLE9BQU8sRUFBRWtCLEtBQUssSUFBSTVELE9BQU8sQ0FBQytELFdBQVc7UUFDckNuQixJQUFJLEVBQUUsMEJBQTBCO1FBQ2hDQyxJQUFJLEVBQUUsS0FBSztRQUNYQyxPQUFPLEVBQUU7VUFDUlksTUFBTSxFQUFFO1lBQ1BWLElBQUksRUFBTWhELE9BQU8sQ0FBQ2dFLEtBQUs7WUFDdkJmLFFBQVEsRUFBRSxhQUFhO1lBQ3ZCQyxJQUFJLEVBQU0sQ0FBRSxPQUFPO1VBQ3BCO1FBQ0Q7TUFDRCxDQUFFLENBQUM7SUFDSixDQUFDO0lBRUQ7QUFDRjtBQUNBO0FBQ0E7QUFDQTtJQUNFNUIsYUFBYSxXQUFiQSxhQUFhQSxDQUFBLEVBQUc7TUFDZmQsS0FBSyxDQUFDYyxhQUFhLEdBQUd6QixDQUFDLENBQUNnRSxLQUFLLENBQUU7UUFDOUJyQixLQUFLLEtBQUF5QixNQUFBLENBQU9qRSxPQUFPLENBQUNrRSxhQUFhLFNBQUFELE1BQUEsQ0FBUWpFLE9BQU8sQ0FBQ21FLGdCQUFnQixTQUFPO1FBQ3hFekIsT0FBTyxFQUFFL0IsR0FBRyxDQUFDeUQsZ0JBQWdCLENBQUMsQ0FBQztRQUMvQnZCLElBQUksRUFBRSxrQkFBa0I7UUFDeEJ3QixRQUFRLEVBQUUsT0FBTztRQUNqQkMsU0FBUyxFQUFFLElBQUk7UUFDZkMsU0FBUyxFQUFFLFNBQVM7UUFDcEJDLGNBQWMsRUFBRSxTQUFTO1FBQ3pCMUIsT0FBTyxFQUFFLEtBQUs7UUFDZDJCLE1BQU0sV0FBTkEsTUFBTUEsQ0FBQSxFQUFHO1VBQ1IsSUFBSSxDQUFDQyxRQUFRLENBQ1hDLEdBQUcsQ0FBRSxPQUFRLENBQUMsQ0FDZEMsRUFBRSxDQUFFLE9BQU8sRUFBRSx5Q0FBeUMsRUFBRWpFLEdBQUcsQ0FBQ2tFLGFBQWMsQ0FBQztRQUM5RTtNQUNELENBQUUsQ0FBQztJQUNKLENBQUM7SUFFRDtBQUNGO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0lBQ0VwQixPQUFPLFdBQVBBLE9BQU9BLENBQUVxQixLQUFLLEVBQUU3RCxJQUFJLEVBQUc7TUFDdEI7TUFDQSxJQUFLVCxLQUFLLENBQUN1RSxZQUFZLEVBQUc7UUFDekI7TUFDRDs7TUFFQTtNQUNBdkUsS0FBSyxDQUFDdUUsWUFBWSxHQUFHLElBQUk7TUFFekIsSUFBSTtRQUNIO1FBQ0FDLEVBQUUsQ0FBQ0MsUUFBUSxDQUFFO1VBQ1pDLElBQUksRUFBRWhGLGNBQWMsR0FBRyx1QkFBdUI7VUFDOUNpRixNQUFNLEVBQUUsTUFBTTtVQUNkQyxLQUFLLEVBQUU7UUFDUixDQUFFLENBQUMsQ0FBQ0MsSUFBSSxDQUFFLFVBQUVDLFFBQVEsRUFBTTtVQUN6QixJQUFLLENBQUVBLFFBQVEsQ0FBQ0MsTUFBTSxFQUFHO1lBQ3hCNUUsR0FBRyxDQUFDZ0QsVUFBVSxDQUFFMkIsUUFBUSxDQUFDMUIsS0FBTSxDQUFDO1lBRWhDO1VBQ0Q7O1VBRUE7VUFDQW5ELFFBQVEsR0FBRzZFLFFBQVEsQ0FBQzdFLFFBQVEsSUFBSSxFQUFFOztVQUVsQztVQUNBLElBQUtRLElBQUksS0FBSyxRQUFRLEVBQUc7WUFBQSxJQUFBdUUsbUJBQUE7WUFDeEJoRixLQUFLLENBQUNtQixjQUFjLENBQUM4RCxlQUFlLENBQUUsZUFBZSxFQUFFLEVBQUcsQ0FBQztZQUMzRCxDQUFBRCxtQkFBQSxHQUFBaEYsS0FBSyxDQUFDa0IsWUFBWSxjQUFBOEQsbUJBQUEsZUFBbEJBLG1CQUFBLENBQW9CRSxhQUFhLENBQUVsRixLQUFLLENBQUNXLFVBQVUsRUFBRVgsS0FBSyxDQUFDaUIsU0FBVSxDQUFDO1VBQ3ZFLENBQUMsTUFBTTtZQUNOZCxHQUFHLENBQUNXLGFBQWEsQ0FBQyxDQUFDO1VBQ3BCO1FBQ0QsQ0FBRSxDQUFDLENBQUNxRSxLQUFLLENBQUUsVUFBRS9CLEtBQUssRUFBTTtVQUN2QjtVQUNBZ0MsT0FBTyxDQUFDaEMsS0FBSyxDQUFFQSxLQUFLLGFBQUxBLEtBQUssdUJBQUxBLEtBQUssQ0FBRWlDLE9BQVEsQ0FBQztVQUMvQmxGLEdBQUcsQ0FBQ2dELFVBQVUsT0FBQU0sTUFBQSxDQUFTakUsT0FBTyxDQUFDK0QsV0FBVyxhQUFBRSxNQUFBLENBQVlMLEtBQUssYUFBTEEsS0FBSyx1QkFBTEEsS0FBSyxDQUFFaUMsT0FBTyxTQUFRLENBQUM7UUFDOUUsQ0FBRSxDQUFDLENBQUNDLE9BQU8sQ0FBRSxZQUFNO1VBQ2xCdEYsS0FBSyxDQUFDdUUsWUFBWSxHQUFHLEtBQUs7O1VBRTFCO1VBQ0FELEtBQUssQ0FBQ2QsS0FBSyxDQUFDLENBQUM7UUFDZCxDQUFFLENBQUM7TUFDSixDQUFDLENBQUMsT0FBUUosS0FBSyxFQUFHO1FBQ2pCcEQsS0FBSyxDQUFDdUUsWUFBWSxHQUFHLEtBQUs7UUFDMUI7UUFDQWEsT0FBTyxDQUFDaEMsS0FBSyxDQUFFQSxLQUFNLENBQUM7UUFDdEJqRCxHQUFHLENBQUNnRCxVQUFVLENBQUUzRCxPQUFPLENBQUMrRCxXQUFXLEdBQUcsTUFBTSxHQUFHSCxLQUFNLENBQUM7TUFDdkQ7SUFDRCxDQUFDO0lBRUQ7QUFDRjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7SUFDRXZDLG1CQUFtQixXQUFuQkEsbUJBQW1CQSxDQUFBLEVBQUc7TUFBQSxJQUFBMEUsU0FBQTtNQUNyQixPQUFPQyxPQUFPLEVBQUFELFNBQUEsR0FBRXRGLFFBQVEsY0FBQXNGLFNBQUEsdUJBQVJBLFNBQUEsQ0FBVTVELE1BQU8sQ0FBQztJQUNuQyxDQUFDO0lBRUQ7QUFDRjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7SUFDRWlDLGdCQUFnQixXQUFoQkEsZ0JBQWdCQSxDQUFBLEVBQUc7TUFDbEIsSUFBSyxDQUFFekQsR0FBRyxDQUFDVSxtQkFBbUIsQ0FBQyxDQUFDLEVBQUc7UUFDbEMsT0FBTyxFQUFFO01BQ1Y7TUFFQSxJQUFLWCxjQUFjLEtBQUssRUFBRSxFQUFHO1FBQzVCLE9BQU9BLGNBQWM7TUFDdEI7TUFFQUQsUUFBUSxDQUFDd0YsT0FBTyxDQUFFLFVBQUVDLE9BQU8sRUFBTTtRQUNoQyxJQUFNQyxVQUFVLEdBQUcvRixjQUFjLEdBQUc4RixPQUFPO1FBRTNDeEYsY0FBYyxtRkFBQXVELE1BQUEsQ0FDQWtDLFVBQVUsb0RBQUFsQyxNQUFBLENBQ1drQyxVQUFVLDJCQUNyQztNQUNULENBQUUsQ0FBQztNQUVIekYsY0FBYyxrRUFBQXVELE1BQUEsQ0FBaUV2RCxjQUFjLFdBQVM7TUFFdEcsT0FBT0EsY0FBYztJQUN0QixDQUFDO0lBRUQ7QUFDRjtBQUNBO0FBQ0E7QUFDQTtJQUNFbUUsYUFBYSxXQUFiQSxhQUFhQSxDQUFBLEVBQUc7TUFBQSxJQUFBdUIsb0JBQUE7TUFDZixJQUFNRCxVQUFVLEdBQUd0RyxDQUFDLENBQUUsSUFBSyxDQUFDLENBQUN3RyxJQUFJLENBQUUsS0FBTSxDQUFDO01BQzFDLElBQU1yRSxLQUFLLFdBQUFpQyxNQUFBLENBQVlrQyxVQUFVLE9BQUs7O01BRXRDO01BQ0EzRixLQUFLLENBQUNZLGFBQWEsQ0FBQ3FFLGVBQWUsQ0FBRSxlQUFlLEVBQUV6RCxLQUFNLENBQUM7O01BRTdEO01BQ0EsQ0FBQW9FLG9CQUFBLEdBQUE1RixLQUFLLENBQUNjLGFBQWEsY0FBQThFLG9CQUFBLGVBQW5CQSxvQkFBQSxDQUFxQnBDLEtBQUssQ0FBQyxDQUFDOztNQUU1QjtNQUNBeEQsS0FBSyxDQUFDVSx3QkFBd0IsQ0FBRSxJQUFLLENBQUM7SUFDdkM7RUFDRCxDQUFDO0VBRURQLEdBQUcsQ0FBQ0MsSUFBSSxDQUFDLENBQUM7O0VBRVY7RUFDQSxPQUFPRCxHQUFHO0FBQ1gsQ0FBQyxDQUFFaEIsUUFBUSxFQUFFQyxNQUFNLEVBQUUwRyxNQUFPLENBQUMiLCJpZ25vcmVMaXN0IjpbXX0=
},{}]},{},[12])