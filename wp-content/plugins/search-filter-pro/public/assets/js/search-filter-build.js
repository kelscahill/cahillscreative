(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
(function (global){

var state = require('./includes/state');
var plugin = require('./includes/plugin');


(function ( $ ) {

	"use strict";

	$(function () {

		if (!Object.keys) {
		  Object.keys = (function () {
			'use strict';
			var hasOwnProperty = Object.prototype.hasOwnProperty,
				hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
				dontEnums = [
				  'toString',
				  'toLocaleString',
				  'valueOf',
				  'hasOwnProperty',
				  'isPrototypeOf',
				  'propertyIsEnumerable',
				  'constructor'
				],
				dontEnumsLength = dontEnums.length;

			return function (obj) {
			  if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			  }

			  var result = [], prop, i;

			  for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
				  result.push(prop);
				}
			  }

			  if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
				  if (hasOwnProperty.call(obj, dontEnums[i])) {
					result.push(dontEnums[i]);
				  }
				}
			  }
			  return result;
			};
		  }());
		}

		/* Search & Filter jQuery Plugin */
		$.fn.searchAndFilter = plugin;

		/* init */
		$(".searchandfilter").searchAndFilter();

		/* external controls */
		$(document).on("click", ".search-filter-reset", function(e){

			e.preventDefault();

			var searchFormID = typeof($(this).attr("data-search-form-id"))!="undefined" ? $(this).attr("data-search-form-id") : "";
			var submitForm = typeof($(this).attr("data-sf-submit-form"))!="undefined" ? $(this).attr("data-sf-submit-form") : "";

			state.getSearchForm(searchFormID).reset(submitForm);

			//var $linked = $("#search-filter-form-"+searchFormID).searchFilterForm({action: "reset"});

			return false;

		});

	});


/*
 * jQuery Easing v1.4.1 - http://gsgd.co.uk/sandbox/jquery/easing/
 * Open source under the BSD License.
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 * https://raw.github.com/gdsmith/jquery.easing/master/LICENSE
*/

/* globals jQuery, define, module, require */
(function (factory) {
	if (typeof define === "function" && define.amd) {
		define(['jquery'], function ($) {
			return factory($);
		});
	} else if (typeof module === "object" && typeof module.exports === "object") {
		module.exports = factory((typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null));
	} else {
		factory(jQuery);
	}
})(function($){

	// Preserve the original jQuery "swing" easing as "jswing"
	if (typeof $.easing !== 'undefined') {
		$.easing['jswing'] = $.easing['swing'];
	}

	var pow = Math.pow,
		sqrt = Math.sqrt,
		sin = Math.sin,
		cos = Math.cos,
		PI = Math.PI,
		c1 = 1.70158,
		c2 = c1 * 1.525,
		c3 = c1 + 1,
		c4 = ( 2 * PI ) / 3,
		c5 = ( 2 * PI ) / 4.5;

	// x is the fraction of animation progress, in the range 0..1
	function bounceOut(x) {
		var n1 = 7.5625,
			d1 = 2.75;
		if ( x < 1/d1 ) {
			return n1*x*x;
		} else if ( x < 2/d1 ) {
			return n1*(x-=(1.5/d1))*x + .75;
		} else if ( x < 2.5/d1 ) {
			return n1*(x-=(2.25/d1))*x + .9375;
		} else {
			return n1*(x-=(2.625/d1))*x + .984375;
		}
	}

	$.extend( $.easing, {
		def: 'easeOutQuad',
		swing: function (x) {
			return $.easing[$.easing.def](x);
		},
		easeInQuad: function (x) {
			return x * x;
		},
		easeOutQuad: function (x) {
			return 1 - ( 1 - x ) * ( 1 - x );
		},
		easeInOutQuad: function (x) {
			return x < 0.5 ?
				2 * x * x :
				1 - pow( -2 * x + 2, 2 ) / 2;
		},
		easeInCubic: function (x) {
			return x * x * x;
		},
		easeOutCubic: function (x) {
			return 1 - pow( 1 - x, 3 );
		},
		easeInOutCubic: function (x) {
			return x < 0.5 ?
				4 * x * x * x :
				1 - pow( -2 * x + 2, 3 ) / 2;
		},
		easeInQuart: function (x) {
			return x * x * x * x;
		},
		easeOutQuart: function (x) {
			return 1 - pow( 1 - x, 4 );
		},
		easeInOutQuart: function (x) {
			return x < 0.5 ?
				8 * x * x * x * x :
				1 - pow( -2 * x + 2, 4 ) / 2;
		},
		easeInQuint: function (x) {
			return x * x * x * x * x;
		},
		easeOutQuint: function (x) {
			return 1 - pow( 1 - x, 5 );
		},
		easeInOutQuint: function (x) {
			return x < 0.5 ?
				16 * x * x * x * x * x :
				1 - pow( -2 * x + 2, 5 ) / 2;
		},
		easeInSine: function (x) {
			return 1 - cos( x * PI/2 );
		},
		easeOutSine: function (x) {
			return sin( x * PI/2 );
		},
		easeInOutSine: function (x) {
			return -( cos( PI * x ) - 1 ) / 2;
		},
		easeInExpo: function (x) {
			return x === 0 ? 0 : pow( 2, 10 * x - 10 );
		},
		easeOutExpo: function (x) {
			return x === 1 ? 1 : 1 - pow( 2, -10 * x );
		},
		easeInOutExpo: function (x) {
			return x === 0 ? 0 : x === 1 ? 1 : x < 0.5 ?
				pow( 2, 20 * x - 10 ) / 2 :
				( 2 - pow( 2, -20 * x + 10 ) ) / 2;
		},
		easeInCirc: function (x) {
			return 1 - sqrt( 1 - pow( x, 2 ) );
		},
		easeOutCirc: function (x) {
			return sqrt( 1 - pow( x - 1, 2 ) );
		},
		easeInOutCirc: function (x) {
			return x < 0.5 ?
				( 1 - sqrt( 1 - pow( 2 * x, 2 ) ) ) / 2 :
				( sqrt( 1 - pow( -2 * x + 2, 2 ) ) + 1 ) / 2;
		},
		easeInElastic: function (x) {
			return x === 0 ? 0 : x === 1 ? 1 :
				-pow( 2, 10 * x - 10 ) * sin( ( x * 10 - 10.75 ) * c4 );
		},
		easeOutElastic: function (x) {
			return x === 0 ? 0 : x === 1 ? 1 :
				pow( 2, -10 * x ) * sin( ( x * 10 - 0.75 ) * c4 ) + 1;
		},
		easeInOutElastic: function (x) {
			return x === 0 ? 0 : x === 1 ? 1 : x < 0.5 ?
				-( pow( 2, 20 * x - 10 ) * sin( ( 20 * x - 11.125 ) * c5 )) / 2 :
				pow( 2, -20 * x + 10 ) * sin( ( 20 * x - 11.125 ) * c5 ) / 2 + 1;
		},
		easeInBack: function (x) {
			return c3 * x * x * x - c1 * x * x;
		},
		easeOutBack: function (x) {
			return 1 + c3 * pow( x - 1, 3 ) + c1 * pow( x - 1, 2 );
		},
		easeInOutBack: function (x) {
			return x < 0.5 ?
				( pow( 2 * x, 2 ) * ( ( c2 + 1 ) * 2 * x - c2 ) ) / 2 :
				( pow( 2 * x - 2, 2 ) *( ( c2 + 1 ) * ( x * 2 - 2 ) + c2 ) + 2 ) / 2;
		},
		easeInBounce: function (x) {
			return 1 - bounceOut( 1 - x );
		},
		easeOutBounce: bounceOut,
		easeInOutBounce: function (x) {
			return x < 0.5 ?
				( 1 - bounceOut( 1 - 2 * x ) ) / 2 :
				( 1 + bounceOut( 2 * x - 1 ) ) / 2;
		}
	});
	return $;
});

}(jQuery));

//safari back button fix
jQuery( window ).on( "pageshow", function(event) {
    if (event.originalEvent.persisted) {
        jQuery(".searchandfilter").off();
        jQuery(".searchandfilter").searchAndFilter();
    }
});

/* wpnumb - nouislider number formatting */
!function(){"use strict";function e(e){return e.split("").reverse().join("")}function n(e,n){return e.substring(0,n.length)===n}function r(e,n){return e.slice(-1*n.length)===n}function t(e,n,r){if((e[n]||e[r])&&e[n]===e[r])throw new Error(n)}function i(e){return"number"==typeof e&&isFinite(e)}function o(e,n){var r=Math.pow(10,n);return(Math.round(e*r)/r).toFixed(n)}function u(n,r,t,u,f,a,c,s,p,d,l,h){var g,v,w,m=h,x="",b="";return a&&(h=a(h)),i(h)?(n!==!1&&0===parseFloat(h.toFixed(n))&&(h=0),0>h&&(g=!0,h=Math.abs(h)),n!==!1&&(h=o(h,n)),h=h.toString(),-1!==h.indexOf(".")?(v=h.split("."),w=v[0],t&&(x=t+v[1])):w=h,r&&(w=e(w).match(/.{1,3}/g),w=e(w.join(e(r)))),g&&s&&(b+=s),u&&(b+=u),g&&p&&(b+=p),b+=w,b+=x,f&&(b+=f),d&&(b=d(b,m)),b):!1}function f(e,t,o,u,f,a,c,s,p,d,l,h){var g,v="";return l&&(h=l(h)),h&&"string"==typeof h?(s&&n(h,s)&&(h=h.replace(s,""),g=!0),u&&n(h,u)&&(h=h.replace(u,"")),p&&n(h,p)&&(h=h.replace(p,""),g=!0),f&&r(h,f)&&(h=h.slice(0,-1*f.length)),t&&(h=h.split(t).join("")),o&&(h=h.replace(o,".")),g&&(v+="-"),v+=h,v=v.replace(/[^0-9\.\-.]/g,""),""===v?!1:(v=Number(v),c&&(v=c(v)),i(v)?v:!1)):!1}function a(e){var n,r,i,o={};for(n=0;n<p.length;n+=1)if(r=p[n],i=e[r],void 0===i)"negative"!==r||o.negativeBefore?"mark"===r&&"."!==o.thousand?o[r]=".":o[r]=!1:o[r]="-";else if("decimals"===r){if(!(i>=0&&8>i))throw new Error(r);o[r]=i}else if("encoder"===r||"decoder"===r||"edit"===r||"undo"===r){if("function"!=typeof i)throw new Error(r);o[r]=i}else{if("string"!=typeof i)throw new Error(r);o[r]=i}return t(o,"mark","thousand"),t(o,"prefix","negative"),t(o,"prefix","negativeBefore"),o}function c(e,n,r){var t,i=[];for(t=0;t<p.length;t+=1)i.push(e[p[t]]);return i.push(r),n.apply("",i)}function s(e){return this instanceof s?void("object"==typeof e&&(e=a(e),this.to=function(n){return c(e,u,n)},this.from=function(n){return c(e,f,n)})):new s(e)}var p=["decimals","thousand","mark","prefix","postfix","encoder","decoder","negativeBefore","negative","edit","undo"];window.wNumb=s}();


}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
//# sourceMappingURL=data:application/json;charset:utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9wdWJsaWMvYXNzZXRzL2pzL2FwcC5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiXHJcbnZhciBzdGF0ZSA9IHJlcXVpcmUoJy4vaW5jbHVkZXMvc3RhdGUnKTtcclxudmFyIHBsdWdpbiA9IHJlcXVpcmUoJy4vaW5jbHVkZXMvcGx1Z2luJyk7XHJcblxyXG5cclxuKGZ1bmN0aW9uICggJCApIHtcclxuXHJcblx0XCJ1c2Ugc3RyaWN0XCI7XHJcblxyXG5cdCQoZnVuY3Rpb24gKCkge1xyXG5cclxuXHRcdGlmICghT2JqZWN0LmtleXMpIHtcclxuXHRcdCAgT2JqZWN0LmtleXMgPSAoZnVuY3Rpb24gKCkge1xyXG5cdFx0XHQndXNlIHN0cmljdCc7XHJcblx0XHRcdHZhciBoYXNPd25Qcm9wZXJ0eSA9IE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHksXHJcblx0XHRcdFx0aGFzRG9udEVudW1CdWcgPSAhKHt0b1N0cmluZzogbnVsbH0pLnByb3BlcnR5SXNFbnVtZXJhYmxlKCd0b1N0cmluZycpLFxyXG5cdFx0XHRcdGRvbnRFbnVtcyA9IFtcclxuXHRcdFx0XHQgICd0b1N0cmluZycsXHJcblx0XHRcdFx0ICAndG9Mb2NhbGVTdHJpbmcnLFxyXG5cdFx0XHRcdCAgJ3ZhbHVlT2YnLFxyXG5cdFx0XHRcdCAgJ2hhc093blByb3BlcnR5JyxcclxuXHRcdFx0XHQgICdpc1Byb3RvdHlwZU9mJyxcclxuXHRcdFx0XHQgICdwcm9wZXJ0eUlzRW51bWVyYWJsZScsXHJcblx0XHRcdFx0ICAnY29uc3RydWN0b3InXHJcblx0XHRcdFx0XSxcclxuXHRcdFx0XHRkb250RW51bXNMZW5ndGggPSBkb250RW51bXMubGVuZ3RoO1xyXG5cclxuXHRcdFx0cmV0dXJuIGZ1bmN0aW9uIChvYmopIHtcclxuXHRcdFx0ICBpZiAodHlwZW9mIG9iaiAhPT0gJ29iamVjdCcgJiYgKHR5cGVvZiBvYmogIT09ICdmdW5jdGlvbicgfHwgb2JqID09PSBudWxsKSkge1xyXG5cdFx0XHRcdHRocm93IG5ldyBUeXBlRXJyb3IoJ09iamVjdC5rZXlzIGNhbGxlZCBvbiBub24tb2JqZWN0Jyk7XHJcblx0XHRcdCAgfVxyXG5cclxuXHRcdFx0ICB2YXIgcmVzdWx0ID0gW10sIHByb3AsIGk7XHJcblxyXG5cdFx0XHQgIGZvciAocHJvcCBpbiBvYmopIHtcclxuXHRcdFx0XHRpZiAoaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApKSB7XHJcblx0XHRcdFx0ICByZXN1bHQucHVzaChwcm9wKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdCAgfVxyXG5cclxuXHRcdFx0ICBpZiAoaGFzRG9udEVudW1CdWcpIHtcclxuXHRcdFx0XHRmb3IgKGkgPSAwOyBpIDwgZG9udEVudW1zTGVuZ3RoOyBpKyspIHtcclxuXHRcdFx0XHQgIGlmIChoYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgZG9udEVudW1zW2ldKSkge1xyXG5cdFx0XHRcdFx0cmVzdWx0LnB1c2goZG9udEVudW1zW2ldKTtcclxuXHRcdFx0XHQgIH1cclxuXHRcdFx0XHR9XHJcblx0XHRcdCAgfVxyXG5cdFx0XHQgIHJldHVybiByZXN1bHQ7XHJcblx0XHRcdH07XHJcblx0XHQgIH0oKSk7XHJcblx0XHR9XHJcblxyXG5cdFx0LyogU2VhcmNoICYgRmlsdGVyIGpRdWVyeSBQbHVnaW4gKi9cclxuXHRcdCQuZm4uc2VhcmNoQW5kRmlsdGVyID0gcGx1Z2luO1xyXG5cclxuXHRcdC8qIGluaXQgKi9cclxuXHRcdCQoXCIuc2VhcmNoYW5kZmlsdGVyXCIpLnNlYXJjaEFuZEZpbHRlcigpO1xyXG5cclxuXHRcdC8qIGV4dGVybmFsIGNvbnRyb2xzICovXHJcblx0XHQkKGRvY3VtZW50KS5vbihcImNsaWNrXCIsIFwiLnNlYXJjaC1maWx0ZXItcmVzZXRcIiwgZnVuY3Rpb24oZSl7XHJcblxyXG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XHJcblxyXG5cdFx0XHR2YXIgc2VhcmNoRm9ybUlEID0gdHlwZW9mKCQodGhpcykuYXR0cihcImRhdGEtc2VhcmNoLWZvcm0taWRcIikpIT1cInVuZGVmaW5lZFwiID8gJCh0aGlzKS5hdHRyKFwiZGF0YS1zZWFyY2gtZm9ybS1pZFwiKSA6IFwiXCI7XHJcblx0XHRcdHZhciBzdWJtaXRGb3JtID0gdHlwZW9mKCQodGhpcykuYXR0cihcImRhdGEtc2Ytc3VibWl0LWZvcm1cIikpIT1cInVuZGVmaW5lZFwiID8gJCh0aGlzKS5hdHRyKFwiZGF0YS1zZi1zdWJtaXQtZm9ybVwiKSA6IFwiXCI7XHJcblxyXG5cdFx0XHRzdGF0ZS5nZXRTZWFyY2hGb3JtKHNlYXJjaEZvcm1JRCkucmVzZXQoc3VibWl0Rm9ybSk7XHJcblxyXG5cdFx0XHQvL3ZhciAkbGlua2VkID0gJChcIiNzZWFyY2gtZmlsdGVyLWZvcm0tXCIrc2VhcmNoRm9ybUlEKS5zZWFyY2hGaWx0ZXJGb3JtKHthY3Rpb246IFwicmVzZXRcIn0pO1xyXG5cclxuXHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cclxuXHRcdH0pO1xyXG5cclxuXHR9KTtcclxuXHJcblxyXG4vKlxyXG4gKiBqUXVlcnkgRWFzaW5nIHYxLjQuMSAtIGh0dHA6Ly9nc2dkLmNvLnVrL3NhbmRib3gvanF1ZXJ5L2Vhc2luZy9cclxuICogT3BlbiBzb3VyY2UgdW5kZXIgdGhlIEJTRCBMaWNlbnNlLlxyXG4gKiBDb3B5cmlnaHQgwqkgMjAwOCBHZW9yZ2UgTWNHaW5sZXkgU21pdGhcclxuICogQWxsIHJpZ2h0cyByZXNlcnZlZC5cclxuICogaHR0cHM6Ly9yYXcuZ2l0aHViLmNvbS9nZHNtaXRoL2pxdWVyeS5lYXNpbmcvbWFzdGVyL0xJQ0VOU0VcclxuKi9cclxuXHJcbi8qIGdsb2JhbHMgalF1ZXJ5LCBkZWZpbmUsIG1vZHVsZSwgcmVxdWlyZSAqL1xyXG4oZnVuY3Rpb24gKGZhY3RvcnkpIHtcclxuXHRpZiAodHlwZW9mIGRlZmluZSA9PT0gXCJmdW5jdGlvblwiICYmIGRlZmluZS5hbWQpIHtcclxuXHRcdGRlZmluZShbJ2pxdWVyeSddLCBmdW5jdGlvbiAoJCkge1xyXG5cdFx0XHRyZXR1cm4gZmFjdG9yeSgkKTtcclxuXHRcdH0pO1xyXG5cdH0gZWxzZSBpZiAodHlwZW9mIG1vZHVsZSA9PT0gXCJvYmplY3RcIiAmJiB0eXBlb2YgbW9kdWxlLmV4cG9ydHMgPT09IFwib2JqZWN0XCIpIHtcclxuXHRcdG1vZHVsZS5leHBvcnRzID0gZmFjdG9yeSgodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvd1snalF1ZXJ5J10gOiB0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsWydqUXVlcnknXSA6IG51bGwpKTtcclxuXHR9IGVsc2Uge1xyXG5cdFx0ZmFjdG9yeShqUXVlcnkpO1xyXG5cdH1cclxufSkoZnVuY3Rpb24oJCl7XHJcblxyXG5cdC8vIFByZXNlcnZlIHRoZSBvcmlnaW5hbCBqUXVlcnkgXCJzd2luZ1wiIGVhc2luZyBhcyBcImpzd2luZ1wiXHJcblx0aWYgKHR5cGVvZiAkLmVhc2luZyAhPT0gJ3VuZGVmaW5lZCcpIHtcclxuXHRcdCQuZWFzaW5nWydqc3dpbmcnXSA9ICQuZWFzaW5nWydzd2luZyddO1xyXG5cdH1cclxuXHJcblx0dmFyIHBvdyA9IE1hdGgucG93LFxyXG5cdFx0c3FydCA9IE1hdGguc3FydCxcclxuXHRcdHNpbiA9IE1hdGguc2luLFxyXG5cdFx0Y29zID0gTWF0aC5jb3MsXHJcblx0XHRQSSA9IE1hdGguUEksXHJcblx0XHRjMSA9IDEuNzAxNTgsXHJcblx0XHRjMiA9IGMxICogMS41MjUsXHJcblx0XHRjMyA9IGMxICsgMSxcclxuXHRcdGM0ID0gKCAyICogUEkgKSAvIDMsXHJcblx0XHRjNSA9ICggMiAqIFBJICkgLyA0LjU7XHJcblxyXG5cdC8vIHggaXMgdGhlIGZyYWN0aW9uIG9mIGFuaW1hdGlvbiBwcm9ncmVzcywgaW4gdGhlIHJhbmdlIDAuLjFcclxuXHRmdW5jdGlvbiBib3VuY2VPdXQoeCkge1xyXG5cdFx0dmFyIG4xID0gNy41NjI1LFxyXG5cdFx0XHRkMSA9IDIuNzU7XHJcblx0XHRpZiAoIHggPCAxL2QxICkge1xyXG5cdFx0XHRyZXR1cm4gbjEqeCp4O1xyXG5cdFx0fSBlbHNlIGlmICggeCA8IDIvZDEgKSB7XHJcblx0XHRcdHJldHVybiBuMSooeC09KDEuNS9kMSkpKnggKyAuNzU7XHJcblx0XHR9IGVsc2UgaWYgKCB4IDwgMi41L2QxICkge1xyXG5cdFx0XHRyZXR1cm4gbjEqKHgtPSgyLjI1L2QxKSkqeCArIC45Mzc1O1xyXG5cdFx0fSBlbHNlIHtcclxuXHRcdFx0cmV0dXJuIG4xKih4LT0oMi42MjUvZDEpKSp4ICsgLjk4NDM3NTtcclxuXHRcdH1cclxuXHR9XHJcblxyXG5cdCQuZXh0ZW5kKCAkLmVhc2luZywge1xyXG5cdFx0ZGVmOiAnZWFzZU91dFF1YWQnLFxyXG5cdFx0c3dpbmc6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAkLmVhc2luZ1skLmVhc2luZy5kZWZdKHgpO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJblF1YWQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ICogeDtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0UXVhZDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSAoIDEgLSB4ICkgKiAoIDEgLSB4ICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluT3V0UXVhZDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPCAwLjUgP1xyXG5cdFx0XHRcdDIgKiB4ICogeCA6XHJcblx0XHRcdFx0MSAtIHBvdyggLTIgKiB4ICsgMiwgMiApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5DdWJpYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggKiB4ICogeDtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0Q3ViaWM6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAxIC0gcG93KCAxIC0geCwgMyApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dEN1YmljOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA8IDAuNSA/XHJcblx0XHRcdFx0NCAqIHggKiB4ICogeCA6XHJcblx0XHRcdFx0MSAtIHBvdyggLTIgKiB4ICsgMiwgMyApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5RdWFydDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggKiB4ICogeCAqIHg7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dFF1YXJ0OiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gMSAtIHBvdyggMSAtIHgsIDQgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRRdWFydDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPCAwLjUgP1xyXG5cdFx0XHRcdDggKiB4ICogeCAqIHggKiB4IDpcclxuXHRcdFx0XHQxIC0gcG93KCAtMiAqIHggKyAyLCA0ICkgLyAyO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJblF1aW50OiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCAqIHggKiB4ICogeCAqIHg7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dFF1aW50OiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gMSAtIHBvdyggMSAtIHgsIDUgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRRdWludDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPCAwLjUgP1xyXG5cdFx0XHRcdDE2ICogeCAqIHggKiB4ICogeCAqIHggOlxyXG5cdFx0XHRcdDEgLSBwb3coIC0yICogeCArIDIsIDUgKSAvIDI7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluU2luZTogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSBjb3MoIHggKiBQSS8yICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dFNpbmU6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiBzaW4oIHggKiBQSS8yICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluT3V0U2luZTogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIC0oIGNvcyggUEkgKiB4ICkgLSAxICkgLyAyO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbkV4cG86IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ID09PSAwID8gMCA6IHBvdyggMiwgMTAgKiB4IC0gMTAgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0RXhwbzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPT09IDEgPyAxIDogMSAtIHBvdyggMiwgLTEwICogeCApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dEV4cG86IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ID09PSAwID8gMCA6IHggPT09IDEgPyAxIDogeCA8IDAuNSA/XHJcblx0XHRcdFx0cG93KCAyLCAyMCAqIHggLSAxMCApIC8gMiA6XHJcblx0XHRcdFx0KCAyIC0gcG93KCAyLCAtMjAgKiB4ICsgMTAgKSApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5DaXJjOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gMSAtIHNxcnQoIDEgLSBwb3coIHgsIDIgKSApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VPdXRDaXJjOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gc3FydCggMSAtIHBvdyggeCAtIDEsIDIgKSApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dENpcmM6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQoIDEgLSBzcXJ0KCAxIC0gcG93KCAyICogeCwgMiApICkgKSAvIDIgOlxyXG5cdFx0XHRcdCggc3FydCggMSAtIHBvdyggLTIgKiB4ICsgMiwgMiApICkgKyAxICkgLyAyO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbkVsYXN0aWM6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ID09PSAwID8gMCA6IHggPT09IDEgPyAxIDpcclxuXHRcdFx0XHQtcG93KCAyLCAxMCAqIHggLSAxMCApICogc2luKCAoIHggKiAxMCAtIDEwLjc1ICkgKiBjNCApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VPdXRFbGFzdGljOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA9PT0gMCA/IDAgOiB4ID09PSAxID8gMSA6XHJcblx0XHRcdFx0cG93KCAyLCAtMTAgKiB4ICkgKiBzaW4oICggeCAqIDEwIC0gMC43NSApICogYzQgKSArIDE7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluT3V0RWxhc3RpYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPT09IDAgPyAwIDogeCA9PT0gMSA/IDEgOiB4IDwgMC41ID9cclxuXHRcdFx0XHQtKCBwb3coIDIsIDIwICogeCAtIDEwICkgKiBzaW4oICggMjAgKiB4IC0gMTEuMTI1ICkgKiBjNSApKSAvIDIgOlxyXG5cdFx0XHRcdHBvdyggMiwgLTIwICogeCArIDEwICkgKiBzaW4oICggMjAgKiB4IC0gMTEuMTI1ICkgKiBjNSApIC8gMiArIDE7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluQmFjazogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIGMzICogeCAqIHggKiB4IC0gYzEgKiB4ICogeDtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0QmFjazogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgKyBjMyAqIHBvdyggeCAtIDEsIDMgKSArIGMxICogcG93KCB4IC0gMSwgMiApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dEJhY2s6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQoIHBvdyggMiAqIHgsIDIgKSAqICggKCBjMiArIDEgKSAqIDIgKiB4IC0gYzIgKSApIC8gMiA6XHJcblx0XHRcdFx0KCBwb3coIDIgKiB4IC0gMiwgMiApICooICggYzIgKyAxICkgKiAoIHggKiAyIC0gMiApICsgYzIgKSArIDIgKSAvIDI7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluQm91bmNlOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gMSAtIGJvdW5jZU91dCggMSAtIHggKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0Qm91bmNlOiBib3VuY2VPdXQsXHJcblx0XHRlYXNlSW5PdXRCb3VuY2U6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQoIDEgLSBib3VuY2VPdXQoIDEgLSAyICogeCApICkgLyAyIDpcclxuXHRcdFx0XHQoIDEgKyBib3VuY2VPdXQoIDIgKiB4IC0gMSApICkgLyAyO1xyXG5cdFx0fVxyXG5cdH0pO1xyXG5cdHJldHVybiAkO1xyXG59KTtcclxuXHJcbn0oalF1ZXJ5KSk7XHJcblxyXG4vL3NhZmFyaSBiYWNrIGJ1dHRvbiBmaXhcclxualF1ZXJ5KCB3aW5kb3cgKS5vbiggXCJwYWdlc2hvd1wiLCBmdW5jdGlvbihldmVudCkge1xyXG4gICAgaWYgKGV2ZW50Lm9yaWdpbmFsRXZlbnQucGVyc2lzdGVkKSB7XHJcbiAgICAgICAgalF1ZXJ5KFwiLnNlYXJjaGFuZGZpbHRlclwiKS5vZmYoKTtcclxuICAgICAgICBqUXVlcnkoXCIuc2VhcmNoYW5kZmlsdGVyXCIpLnNlYXJjaEFuZEZpbHRlcigpO1xyXG4gICAgfVxyXG59KTtcclxuXHJcbi8qIHdwbnVtYiAtIG5vdWlzbGlkZXIgbnVtYmVyIGZvcm1hdHRpbmcgKi9cclxuIWZ1bmN0aW9uKCl7XCJ1c2Ugc3RyaWN0XCI7ZnVuY3Rpb24gZShlKXtyZXR1cm4gZS5zcGxpdChcIlwiKS5yZXZlcnNlKCkuam9pbihcIlwiKX1mdW5jdGlvbiBuKGUsbil7cmV0dXJuIGUuc3Vic3RyaW5nKDAsbi5sZW5ndGgpPT09bn1mdW5jdGlvbiByKGUsbil7cmV0dXJuIGUuc2xpY2UoLTEqbi5sZW5ndGgpPT09bn1mdW5jdGlvbiB0KGUsbixyKXtpZigoZVtuXXx8ZVtyXSkmJmVbbl09PT1lW3JdKXRocm93IG5ldyBFcnJvcihuKX1mdW5jdGlvbiBpKGUpe3JldHVyblwibnVtYmVyXCI9PXR5cGVvZiBlJiZpc0Zpbml0ZShlKX1mdW5jdGlvbiBvKGUsbil7dmFyIHI9TWF0aC5wb3coMTAsbik7cmV0dXJuKE1hdGgucm91bmQoZSpyKS9yKS50b0ZpeGVkKG4pfWZ1bmN0aW9uIHUobixyLHQsdSxmLGEsYyxzLHAsZCxsLGgpe3ZhciBnLHYsdyxtPWgseD1cIlwiLGI9XCJcIjtyZXR1cm4gYSYmKGg9YShoKSksaShoKT8obiE9PSExJiYwPT09cGFyc2VGbG9hdChoLnRvRml4ZWQobikpJiYoaD0wKSwwPmgmJihnPSEwLGg9TWF0aC5hYnMoaCkpLG4hPT0hMSYmKGg9byhoLG4pKSxoPWgudG9TdHJpbmcoKSwtMSE9PWguaW5kZXhPZihcIi5cIik/KHY9aC5zcGxpdChcIi5cIiksdz12WzBdLHQmJih4PXQrdlsxXSkpOnc9aCxyJiYodz1lKHcpLm1hdGNoKC8uezEsM30vZyksdz1lKHcuam9pbihlKHIpKSkpLGcmJnMmJihiKz1zKSx1JiYoYis9dSksZyYmcCYmKGIrPXApLGIrPXcsYis9eCxmJiYoYis9ZiksZCYmKGI9ZChiLG0pKSxiKTohMX1mdW5jdGlvbiBmKGUsdCxvLHUsZixhLGMscyxwLGQsbCxoKXt2YXIgZyx2PVwiXCI7cmV0dXJuIGwmJihoPWwoaCkpLGgmJlwic3RyaW5nXCI9PXR5cGVvZiBoPyhzJiZuKGgscykmJihoPWgucmVwbGFjZShzLFwiXCIpLGc9ITApLHUmJm4oaCx1KSYmKGg9aC5yZXBsYWNlKHUsXCJcIikpLHAmJm4oaCxwKSYmKGg9aC5yZXBsYWNlKHAsXCJcIiksZz0hMCksZiYmcihoLGYpJiYoaD1oLnNsaWNlKDAsLTEqZi5sZW5ndGgpKSx0JiYoaD1oLnNwbGl0KHQpLmpvaW4oXCJcIikpLG8mJihoPWgucmVwbGFjZShvLFwiLlwiKSksZyYmKHYrPVwiLVwiKSx2Kz1oLHY9di5yZXBsYWNlKC9bXjAtOVxcLlxcLS5dL2csXCJcIiksXCJcIj09PXY/ITE6KHY9TnVtYmVyKHYpLGMmJih2PWModikpLGkodik/djohMSkpOiExfWZ1bmN0aW9uIGEoZSl7dmFyIG4scixpLG89e307Zm9yKG49MDtuPHAubGVuZ3RoO24rPTEpaWYocj1wW25dLGk9ZVtyXSx2b2lkIDA9PT1pKVwibmVnYXRpdmVcIiE9PXJ8fG8ubmVnYXRpdmVCZWZvcmU/XCJtYXJrXCI9PT1yJiZcIi5cIiE9PW8udGhvdXNhbmQ/b1tyXT1cIi5cIjpvW3JdPSExOm9bcl09XCItXCI7ZWxzZSBpZihcImRlY2ltYWxzXCI9PT1yKXtpZighKGk+PTAmJjg+aSkpdGhyb3cgbmV3IEVycm9yKHIpO29bcl09aX1lbHNlIGlmKFwiZW5jb2RlclwiPT09cnx8XCJkZWNvZGVyXCI9PT1yfHxcImVkaXRcIj09PXJ8fFwidW5kb1wiPT09cil7aWYoXCJmdW5jdGlvblwiIT10eXBlb2YgaSl0aHJvdyBuZXcgRXJyb3Iocik7b1tyXT1pfWVsc2V7aWYoXCJzdHJpbmdcIiE9dHlwZW9mIGkpdGhyb3cgbmV3IEVycm9yKHIpO29bcl09aX1yZXR1cm4gdChvLFwibWFya1wiLFwidGhvdXNhbmRcIiksdChvLFwicHJlZml4XCIsXCJuZWdhdGl2ZVwiKSx0KG8sXCJwcmVmaXhcIixcIm5lZ2F0aXZlQmVmb3JlXCIpLG99ZnVuY3Rpb24gYyhlLG4scil7dmFyIHQsaT1bXTtmb3IodD0wO3Q8cC5sZW5ndGg7dCs9MSlpLnB1c2goZVtwW3RdXSk7cmV0dXJuIGkucHVzaChyKSxuLmFwcGx5KFwiXCIsaSl9ZnVuY3Rpb24gcyhlKXtyZXR1cm4gdGhpcyBpbnN0YW5jZW9mIHM/dm9pZChcIm9iamVjdFwiPT10eXBlb2YgZSYmKGU9YShlKSx0aGlzLnRvPWZ1bmN0aW9uKG4pe3JldHVybiBjKGUsdSxuKX0sdGhpcy5mcm9tPWZ1bmN0aW9uKG4pe3JldHVybiBjKGUsZixuKX0pKTpuZXcgcyhlKX12YXIgcD1bXCJkZWNpbWFsc1wiLFwidGhvdXNhbmRcIixcIm1hcmtcIixcInByZWZpeFwiLFwicG9zdGZpeFwiLFwiZW5jb2RlclwiLFwiZGVjb2RlclwiLFwibmVnYXRpdmVCZWZvcmVcIixcIm5lZ2F0aXZlXCIsXCJlZGl0XCIsXCJ1bmRvXCJdO3dpbmRvdy53TnVtYj1zfSgpO1xyXG5cclxuIl19
},{"./includes/plugin":3,"./includes/state":5}],2:[function(require,module,exports){
/*! nouislider - 11.1.0 - 2018-04-02 11:18:13 */

(function (factory) {

    if ( typeof define === 'function' && define.amd ) {

        // AMD. Register as an anonymous module.
        define([], factory);

    } else if ( typeof exports === 'object' ) {

        // Node/CommonJS
        module.exports = factory();

    } else {

        // Browser globals
        window.noUiSlider = factory();
    }

}(function( ){

	'use strict';

	var VERSION = '11.1.0';


	function isValidFormatter ( entry ) {
		return typeof entry === 'object' && typeof entry.to === 'function' && typeof entry.from === 'function';
	}

	function removeElement ( el ) {
		el.parentElement.removeChild(el);
	}

	function isSet ( value ) {
		return value !== null && value !== undefined;
	}

	// Bindable version
	function preventDefault ( e ) {
		e.preventDefault();
	}

	// Removes duplicates from an array.
	function unique ( array ) {
		return array.filter(function(a){
			return !this[a] ? this[a] = true : false;
		}, {});
	}

	// Round a value to the closest 'to'.
	function closest ( value, to ) {
		return Math.round(value / to) * to;
	}

	// Current position of an element relative to the document.
	function offset ( elem, orientation ) {

		var rect = elem.getBoundingClientRect();
		var doc = elem.ownerDocument;
		var docElem = doc.documentElement;
		var pageOffset = getPageOffset(doc);

		// getBoundingClientRect contains left scroll in Chrome on Android.
		// I haven't found a feature detection that proves this. Worst case
		// scenario on mis-match: the 'tap' feature on horizontal sliders breaks.
		if ( /webkit.*Chrome.*Mobile/i.test(navigator.userAgent) ) {
			pageOffset.x = 0;
		}

		return orientation ? (rect.top + pageOffset.y - docElem.clientTop) : (rect.left + pageOffset.x - docElem.clientLeft);
	}

	// Checks whether a value is numerical.
	function isNumeric ( a ) {
		return typeof a === 'number' && !isNaN( a ) && isFinite( a );
	}

	// Sets a class and removes it after [duration] ms.
	function addClassFor ( element, className, duration ) {
		if (duration > 0) {
		addClass(element, className);
			setTimeout(function(){
				removeClass(element, className);
			}, duration);
		}
	}

	// Limits a value to 0 - 100
	function limit ( a ) {
		return Math.max(Math.min(a, 100), 0);
	}

	// Wraps a variable as an array, if it isn't one yet.
	// Note that an input array is returned by reference!
	function asArray ( a ) {
		return Array.isArray(a) ? a : [a];
	}

	// Counts decimals
	function countDecimals ( numStr ) {
		numStr = String(numStr);
		var pieces = numStr.split(".");
		return pieces.length > 1 ? pieces[1].length : 0;
	}

	// http://youmightnotneedjquery.com/#add_class
	function addClass ( el, className ) {
		if ( el.classList ) {
			el.classList.add(className);
		} else {
			el.className += ' ' + className;
		}
	}

	// http://youmightnotneedjquery.com/#remove_class
	function removeClass ( el, className ) {
		if ( el.classList ) {
			el.classList.remove(className);
		} else {
			el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
		}
	}

	// https://plainjs.com/javascript/attributes/adding-removing-and-testing-for-classes-9/
	function hasClass ( el, className ) {
		return el.classList ? el.classList.contains(className) : new RegExp('\\b' + className + '\\b').test(el.className);
	}

	// https://developer.mozilla.org/en-US/docs/Web/API/Window/scrollY#Notes
	function getPageOffset ( doc ) {

		var supportPageOffset = window.pageXOffset !== undefined;
		var isCSS1Compat = ((doc.compatMode || "") === "CSS1Compat");
		var x = supportPageOffset ? window.pageXOffset : isCSS1Compat ? doc.documentElement.scrollLeft : doc.body.scrollLeft;
		var y = supportPageOffset ? window.pageYOffset : isCSS1Compat ? doc.documentElement.scrollTop : doc.body.scrollTop;

		return {
			x: x,
			y: y
		};
	}

	// we provide a function to compute constants instead
	// of accessing window.* as soon as the module needs it
	// so that we do not compute anything if not needed
	function getActions ( ) {

		// Determine the events to bind. IE11 implements pointerEvents without
		// a prefix, which breaks compatibility with the IE10 implementation.
		return window.navigator.pointerEnabled ? {
			start: 'pointerdown',
			move: 'pointermove',
			end: 'pointerup'
		} : window.navigator.msPointerEnabled ? {
			start: 'MSPointerDown',
			move: 'MSPointerMove',
			end: 'MSPointerUp'
		} : {
			start: 'mousedown touchstart',
			move: 'mousemove touchmove',
			end: 'mouseup touchend'
		};
	}

	// https://github.com/WICG/EventListenerOptions/blob/gh-pages/explainer.md
	// Issue #785
	function getSupportsPassive ( ) {

		var supportsPassive = false;

		try {

			var opts = Object.defineProperty({}, 'passive', {
				get: function() {
					supportsPassive = true;
				}
			});

			window.addEventListener('test', null, opts);

		} catch (e) {}

		return supportsPassive;
	}

	function getSupportsTouchActionNone ( ) {
		return window.CSS && CSS.supports && CSS.supports('touch-action', 'none');
	}


// Value calculation

	// Determine the size of a sub-range in relation to a full range.
	function subRangeRatio ( pa, pb ) {
		return (100 / (pb - pa));
	}

	// (percentage) How many percent is this value of this range?
	function fromPercentage ( range, value ) {
		return (value * 100) / ( range[1] - range[0] );
	}

	// (percentage) Where is this value on this range?
	function toPercentage ( range, value ) {
		return fromPercentage( range, range[0] < 0 ?
			value + Math.abs(range[0]) :
				value - range[0] );
	}

	// (value) How much is this percentage on this range?
	function isPercentage ( range, value ) {
		return ((value * ( range[1] - range[0] )) / 100) + range[0];
	}


// Range conversion

	function getJ ( value, arr ) {

		var j = 1;

		while ( value >= arr[j] ){
			j += 1;
		}

		return j;
	}

	// (percentage) Input a value, find where, on a scale of 0-100, it applies.
	function toStepping ( xVal, xPct, value ) {

		if ( value >= xVal.slice(-1)[0] ){
			return 100;
		}

		var j = getJ( value, xVal );
		var va = xVal[j-1];
		var vb = xVal[j];
		var pa = xPct[j-1];
		var pb = xPct[j];

		return pa + (toPercentage([va, vb], value) / subRangeRatio (pa, pb));
	}

	// (value) Input a percentage, find where it is on the specified range.
	function fromStepping ( xVal, xPct, value ) {

		// There is no range group that fits 100
		if ( value >= 100 ){
			return xVal.slice(-1)[0];
		}

		var j = getJ( value, xPct );
		var va = xVal[j-1];
		var vb = xVal[j];
		var pa = xPct[j-1];
		var pb = xPct[j];

		return isPercentage([va, vb], (value - pa) * subRangeRatio (pa, pb));
	}

	// (percentage) Get the step that applies at a certain value.
	function getStep ( xPct, xSteps, snap, value ) {

		if ( value === 100 ) {
			return value;
		}

		var j = getJ( value, xPct );
		var a = xPct[j-1];
		var b = xPct[j];

		// If 'snap' is set, steps are used as fixed points on the slider.
		if ( snap ) {

			// Find the closest position, a or b.
			if ((value - a) > ((b-a)/2)){
				return b;
			}

			return a;
		}

		if ( !xSteps[j-1] ){
			return value;
		}

		return xPct[j-1] + closest(
			value - xPct[j-1],
			xSteps[j-1]
		);
	}


// Entry parsing

	function handleEntryPoint ( index, value, that ) {

		var percentage;

		// Wrap numerical input in an array.
		if ( typeof value === "number" ) {
			value = [value];
		}

		// Reject any invalid input, by testing whether value is an array.
		if ( !Array.isArray(value) ){
			throw new Error("noUiSlider (" + VERSION + "): 'range' contains invalid value.");
		}

		// Covert min/max syntax to 0 and 100.
		if ( index === 'min' ) {
			percentage = 0;
		} else if ( index === 'max' ) {
			percentage = 100;
		} else {
			percentage = parseFloat( index );
		}

		// Check for correct input.
		if ( !isNumeric( percentage ) || !isNumeric( value[0] ) ) {
			throw new Error("noUiSlider (" + VERSION + "): 'range' value isn't numeric.");
		}

		// Store values.
		that.xPct.push( percentage );
		that.xVal.push( value[0] );

		// NaN will evaluate to false too, but to keep
		// logging clear, set step explicitly. Make sure
		// not to override the 'step' setting with false.
		if ( !percentage ) {
			if ( !isNaN( value[1] ) ) {
				that.xSteps[0] = value[1];
			}
		} else {
			that.xSteps.push( isNaN(value[1]) ? false : value[1] );
		}

		that.xHighestCompleteStep.push(0);
	}

	function handleStepPoint ( i, n, that ) {

		// Ignore 'false' stepping.
		if ( !n ) {
			return true;
		}

		// Factor to range ratio
		that.xSteps[i] = fromPercentage([that.xVal[i], that.xVal[i+1]], n) / subRangeRatio(that.xPct[i], that.xPct[i+1]);

		var totalSteps = (that.xVal[i+1] - that.xVal[i]) / that.xNumSteps[i];
		var highestStep = Math.ceil(Number(totalSteps.toFixed(3)) - 1);
		var step = that.xVal[i] + (that.xNumSteps[i] * highestStep);

		that.xHighestCompleteStep[i] = step;
	}


// Interface

	function Spectrum ( entry, snap, singleStep ) {

		this.xPct = [];
		this.xVal = [];
		this.xSteps = [ singleStep || false ];
		this.xNumSteps = [ false ];
		this.xHighestCompleteStep = [];

		this.snap = snap;

		var index;
		var ordered = []; // [0, 'min'], [1, '50%'], [2, 'max']

		// Map the object keys to an array.
		for ( index in entry ) {
			if ( entry.hasOwnProperty(index) ) {
				ordered.push([entry[index], index]);
			}
		}

		// Sort all entries by value (numeric sort).
		if ( ordered.length && typeof ordered[0][0] === "object" ) {
			ordered.sort(function(a, b) { return a[0][0] - b[0][0]; });
		} else {
			ordered.sort(function(a, b) { return a[0] - b[0]; });
		}


		// Convert all entries to subranges.
		for ( index = 0; index < ordered.length; index++ ) {
			handleEntryPoint(ordered[index][1], ordered[index][0], this);
		}

		// Store the actual step values.
		// xSteps is sorted in the same order as xPct and xVal.
		this.xNumSteps = this.xSteps.slice(0);

		// Convert all numeric steps to the percentage of the subrange they represent.
		for ( index = 0; index < this.xNumSteps.length; index++ ) {
			handleStepPoint(index, this.xNumSteps[index], this);
		}
	}

	Spectrum.prototype.getMargin = function ( value ) {

		var step = this.xNumSteps[0];

		if ( step && ((value / step) % 1) !== 0 ) {
			throw new Error("noUiSlider (" + VERSION + "): 'limit', 'margin' and 'padding' must be divisible by step.");
		}

		return this.xPct.length === 2 ? fromPercentage(this.xVal, value) : false;
	};

	Spectrum.prototype.toStepping = function ( value ) {

		value = toStepping( this.xVal, this.xPct, value );

		return value;
	};

	Spectrum.prototype.fromStepping = function ( value ) {

		return fromStepping( this.xVal, this.xPct, value );
	};

	Spectrum.prototype.getStep = function ( value ) {

		value = getStep(this.xPct, this.xSteps, this.snap, value );

		return value;
	};

	Spectrum.prototype.getNearbySteps = function ( value ) {

		var j = getJ(value, this.xPct);

		return {
			stepBefore: { startValue: this.xVal[j-2], step: this.xNumSteps[j-2], highestStep: this.xHighestCompleteStep[j-2] },
			thisStep: { startValue: this.xVal[j-1], step: this.xNumSteps[j-1], highestStep: this.xHighestCompleteStep[j-1] },
			stepAfter: { startValue: this.xVal[j-0], step: this.xNumSteps[j-0], highestStep: this.xHighestCompleteStep[j-0] }
		};
	};

	Spectrum.prototype.countStepDecimals = function () {
		var stepDecimals = this.xNumSteps.map(countDecimals);
		return Math.max.apply(null, stepDecimals);
	};

	// Outside testing
	Spectrum.prototype.convert = function ( value ) {
		return this.getStep(this.toStepping(value));
	};

/*	Every input option is tested and parsed. This'll prevent
	endless validation in internal methods. These tests are
	structured with an item for every option available. An
	option can be marked as required by setting the 'r' flag.
	The testing function is provided with three arguments:
		- The provided value for the option;
		- A reference to the options object;
		- The name for the option;

	The testing function returns false when an error is detected,
	or true when everything is OK. It can also modify the option
	object, to make sure all values can be correctly looped elsewhere. */

	var defaultFormatter = { 'to': function( value ){
		return value !== undefined && value.toFixed(2);
	}, 'from': Number };

	function validateFormat ( entry ) {

		// Any object with a to and from method is supported.
		if ( isValidFormatter(entry) ) {
			return true;
		}

		throw new Error("noUiSlider (" + VERSION + "): 'format' requires 'to' and 'from' methods.");
	}

	function testStep ( parsed, entry ) {

		if ( !isNumeric( entry ) ) {
			throw new Error("noUiSlider (" + VERSION + "): 'step' is not numeric.");
		}

		// The step option can still be used to set stepping
		// for linear sliders. Overwritten if set in 'range'.
		parsed.singleStep = entry;
	}

	function testRange ( parsed, entry ) {

		// Filter incorrect input.
		if ( typeof entry !== 'object' || Array.isArray(entry) ) {
			throw new Error("noUiSlider (" + VERSION + "): 'range' is not an object.");
		}

		// Catch missing start or end.
		if ( entry.min === undefined || entry.max === undefined ) {
			throw new Error("noUiSlider (" + VERSION + "): Missing 'min' or 'max' in 'range'.");
		}

		// Catch equal start or end.
		if ( entry.min === entry.max ) {
			throw new Error("noUiSlider (" + VERSION + "): 'range' 'min' and 'max' cannot be equal.");
		}

		parsed.spectrum = new Spectrum(entry, parsed.snap, parsed.singleStep);
	}

	function testStart ( parsed, entry ) {

		entry = asArray(entry);

		// Validate input. Values aren't tested, as the public .val method
		// will always provide a valid location.
		if ( !Array.isArray( entry ) || !entry.length ) {
			throw new Error("noUiSlider (" + VERSION + "): 'start' option is incorrect.");
		}

		// Store the number of handles.
		parsed.handles = entry.length;

		// When the slider is initialized, the .val method will
		// be called with the start options.
		parsed.start = entry;
	}

	function testSnap ( parsed, entry ) {

		// Enforce 100% stepping within subranges.
		parsed.snap = entry;

		if ( typeof entry !== 'boolean' ){
			throw new Error("noUiSlider (" + VERSION + "): 'snap' option must be a boolean.");
		}
	}

	function testAnimate ( parsed, entry ) {

		// Enforce 100% stepping within subranges.
		parsed.animate = entry;

		if ( typeof entry !== 'boolean' ){
			throw new Error("noUiSlider (" + VERSION + "): 'animate' option must be a boolean.");
		}
	}

	function testAnimationDuration ( parsed, entry ) {

		parsed.animationDuration = entry;

		if ( typeof entry !== 'number' ){
			throw new Error("noUiSlider (" + VERSION + "): 'animationDuration' option must be a number.");
		}
	}

	function testConnect ( parsed, entry ) {

		var connect = [false];
		var i;

		// Map legacy options
		if ( entry === 'lower' ) {
			entry = [true, false];
		}

		else if ( entry === 'upper' ) {
			entry = [false, true];
		}

		// Handle boolean options
		if ( entry === true || entry === false ) {

			for ( i = 1; i < parsed.handles; i++ ) {
				connect.push(entry);
			}

			connect.push(false);
		}

		// Reject invalid input
		else if ( !Array.isArray( entry ) || !entry.length || entry.length !== parsed.handles + 1 ) {
			throw new Error("noUiSlider (" + VERSION + "): 'connect' option doesn't match handle count.");
		}

		else {
			connect = entry;
		}

		parsed.connect = connect;
	}

	function testOrientation ( parsed, entry ) {

		// Set orientation to an a numerical value for easy
		// array selection.
		switch ( entry ){
			case 'horizontal':
				parsed.ort = 0;
				break;
			case 'vertical':
				parsed.ort = 1;
				break;
			default:
				throw new Error("noUiSlider (" + VERSION + "): 'orientation' option is invalid.");
		}
	}

	function testMargin ( parsed, entry ) {

		if ( !isNumeric(entry) ){
			throw new Error("noUiSlider (" + VERSION + "): 'margin' option must be numeric.");
		}

		// Issue #582
		if ( entry === 0 ) {
			return;
		}

		parsed.margin = parsed.spectrum.getMargin(entry);

		if ( !parsed.margin ) {
			throw new Error("noUiSlider (" + VERSION + "): 'margin' option is only supported on linear sliders.");
		}
	}

	function testLimit ( parsed, entry ) {

		if ( !isNumeric(entry) ){
			throw new Error("noUiSlider (" + VERSION + "): 'limit' option must be numeric.");
		}

		parsed.limit = parsed.spectrum.getMargin(entry);

		if ( !parsed.limit || parsed.handles < 2 ) {
			throw new Error("noUiSlider (" + VERSION + "): 'limit' option is only supported on linear sliders with 2 or more handles.");
		}
	}

	function testPadding ( parsed, entry ) {

		if ( !isNumeric(entry) && !Array.isArray(entry) ){
			throw new Error("noUiSlider (" + VERSION + "): 'padding' option must be numeric or array of exactly 2 numbers.");
		}

		if ( Array.isArray(entry) && !(entry.length === 2 || isNumeric(entry[0]) || isNumeric(entry[1])) ) {
			throw new Error("noUiSlider (" + VERSION + "): 'padding' option must be numeric or array of exactly 2 numbers.");
		}

		if ( entry === 0 ) {
			return;
		}

		if ( !Array.isArray(entry) ) {
			entry = [entry, entry];
		}

		// 'getMargin' returns false for invalid values.
		parsed.padding = [parsed.spectrum.getMargin(entry[0]), parsed.spectrum.getMargin(entry[1])];

		if ( parsed.padding[0] === false || parsed.padding[1] === false ) {
			throw new Error("noUiSlider (" + VERSION + "): 'padding' option is only supported on linear sliders.");
		}

		if ( parsed.padding[0] < 0 || parsed.padding[1] < 0 ) {
			throw new Error("noUiSlider (" + VERSION + "): 'padding' option must be a positive number(s).");
		}

		if ( parsed.padding[0] + parsed.padding[1] >= 100 ) {
			throw new Error("noUiSlider (" + VERSION + "): 'padding' option must not exceed 100% of the range.");
		}
	}

	function testDirection ( parsed, entry ) {

		// Set direction as a numerical value for easy parsing.
		// Invert connection for RTL sliders, so that the proper
		// handles get the connect/background classes.
		switch ( entry ) {
			case 'ltr':
				parsed.dir = 0;
				break;
			case 'rtl':
				parsed.dir = 1;
				break;
			default:
				throw new Error("noUiSlider (" + VERSION + "): 'direction' option was not recognized.");
		}
	}

	function testBehaviour ( parsed, entry ) {

		// Make sure the input is a string.
		if ( typeof entry !== 'string' ) {
			throw new Error("noUiSlider (" + VERSION + "): 'behaviour' must be a string containing options.");
		}

		// Check if the string contains any keywords.
		// None are required.
		var tap = entry.indexOf('tap') >= 0;
		var drag = entry.indexOf('drag') >= 0;
		var fixed = entry.indexOf('fixed') >= 0;
		var snap = entry.indexOf('snap') >= 0;
		var hover = entry.indexOf('hover') >= 0;

		if ( fixed ) {

			if ( parsed.handles !== 2 ) {
				throw new Error("noUiSlider (" + VERSION + "): 'fixed' behaviour must be used with 2 handles");
			}

			// Use margin to enforce fixed state
			testMargin(parsed, parsed.start[1] - parsed.start[0]);
		}

		parsed.events = {
			tap: tap || snap,
			drag: drag,
			fixed: fixed,
			snap: snap,
			hover: hover
		};
	}

	function testTooltips ( parsed, entry ) {

		if ( entry === false ) {
			return;
		}

		else if ( entry === true ) {

			parsed.tooltips = [];

			for ( var i = 0; i < parsed.handles; i++ ) {
				parsed.tooltips.push(true);
			}
		}

		else {

			parsed.tooltips = asArray(entry);

			if ( parsed.tooltips.length !== parsed.handles ) {
				throw new Error("noUiSlider (" + VERSION + "): must pass a formatter for all handles.");
			}

			parsed.tooltips.forEach(function(formatter){
				if ( typeof formatter !== 'boolean' && (typeof formatter !== 'object' || typeof formatter.to !== 'function') ) {
					throw new Error("noUiSlider (" + VERSION + "): 'tooltips' must be passed a formatter or 'false'.");
				}
			});
		}
	}

	function testAriaFormat ( parsed, entry ) {
		parsed.ariaFormat = entry;
		validateFormat(entry);
	}

	function testFormat ( parsed, entry ) {
		parsed.format = entry;
		validateFormat(entry);
	}

	function testCssPrefix ( parsed, entry ) {

		if ( typeof entry !== 'string' && entry !== false ) {
			throw new Error("noUiSlider (" + VERSION + "): 'cssPrefix' must be a string or `false`.");
		}

		parsed.cssPrefix = entry;
	}

	function testCssClasses ( parsed, entry ) {

		if ( typeof entry !== 'object' ) {
			throw new Error("noUiSlider (" + VERSION + "): 'cssClasses' must be an object.");
		}

		if ( typeof parsed.cssPrefix === 'string' ) {
			parsed.cssClasses = {};

			for ( var key in entry ) {
				if ( !entry.hasOwnProperty(key) ) { continue; }

				parsed.cssClasses[key] = parsed.cssPrefix + entry[key];
			}
		} else {
			parsed.cssClasses = entry;
		}
	}

	// Test all developer settings and parse to assumption-safe values.
	function testOptions ( options ) {

		// To prove a fix for #537, freeze options here.
		// If the object is modified, an error will be thrown.
		// Object.freeze(options);

		var parsed = {
			margin: 0,
			limit: 0,
			padding: 0,
			animate: true,
			animationDuration: 300,
			ariaFormat: defaultFormatter,
			format: defaultFormatter
		};

		// Tests are executed in the order they are presented here.
		var tests = {
			'step': { r: false, t: testStep },
			'start': { r: true, t: testStart },
			'connect': { r: true, t: testConnect },
			'direction': { r: true, t: testDirection },
			'snap': { r: false, t: testSnap },
			'animate': { r: false, t: testAnimate },
			'animationDuration': { r: false, t: testAnimationDuration },
			'range': { r: true, t: testRange },
			'orientation': { r: false, t: testOrientation },
			'margin': { r: false, t: testMargin },
			'limit': { r: false, t: testLimit },
			'padding': { r: false, t: testPadding },
			'behaviour': { r: true, t: testBehaviour },
			'ariaFormat': { r: false, t: testAriaFormat },
			'format': { r: false, t: testFormat },
			'tooltips': { r: false, t: testTooltips },
			'cssPrefix': { r: true, t: testCssPrefix },
			'cssClasses': { r: true, t: testCssClasses }
		};

		var defaults = {
			'connect': false,
			'direction': 'ltr',
			'behaviour': 'tap',
			'orientation': 'horizontal',
			'cssPrefix' : 'noUi-',
			'cssClasses': {
				target: 'target',
				base: 'base',
				origin: 'origin',
				handle: 'handle',
				handleLower: 'handle-lower',
				handleUpper: 'handle-upper',
				horizontal: 'horizontal',
				vertical: 'vertical',
				background: 'background',
				connect: 'connect',
				connects: 'connects',
				ltr: 'ltr',
				rtl: 'rtl',
				draggable: 'draggable',
				drag: 'state-drag',
				tap: 'state-tap',
				active: 'active',
				tooltip: 'tooltip',
				pips: 'pips',
				pipsHorizontal: 'pips-horizontal',
				pipsVertical: 'pips-vertical',
				marker: 'marker',
				markerHorizontal: 'marker-horizontal',
				markerVertical: 'marker-vertical',
				markerNormal: 'marker-normal',
				markerLarge: 'marker-large',
				markerSub: 'marker-sub',
				value: 'value',
				valueHorizontal: 'value-horizontal',
				valueVertical: 'value-vertical',
				valueNormal: 'value-normal',
				valueLarge: 'value-large',
				valueSub: 'value-sub'
			}
		};

		// AriaFormat defaults to regular format, if any.
		if ( options.format && !options.ariaFormat ) {
			options.ariaFormat = options.format;
		}

		// Run all options through a testing mechanism to ensure correct
		// input. It should be noted that options might get modified to
		// be handled properly. E.g. wrapping integers in arrays.
		Object.keys(tests).forEach(function( name ){

			// If the option isn't set, but it is required, throw an error.
			if ( !isSet(options[name]) && defaults[name] === undefined ) {

				if ( tests[name].r ) {
					throw new Error("noUiSlider (" + VERSION + "): '" + name + "' is required.");
				}

				return true;
			}

			tests[name].t( parsed, !isSet(options[name]) ? defaults[name] : options[name] );
		});

		// Forward pips options
		parsed.pips = options.pips;

		// All recent browsers accept unprefixed transform.
		// We need -ms- for IE9 and -webkit- for older Android;
		// Assume use of -webkit- if unprefixed and -ms- are not supported.
		// https://caniuse.com/#feat=transforms2d
		var d = document.createElement("div");
		var msPrefix = d.style.msTransform !== undefined;
		var noPrefix = d.style.transform !== undefined;

		parsed.transformRule = noPrefix ? 'transform' : (msPrefix ? 'msTransform' : 'webkitTransform');

		// Pips don't move, so we can place them using left/top.
		var styles = [['left', 'top'], ['right', 'bottom']];

		parsed.style = styles[parsed.dir][parsed.ort];

		return parsed;
	}


function scope ( target, options, originalOptions ){

	var actions = getActions();
	var supportsTouchActionNone = getSupportsTouchActionNone();
	var supportsPassive = supportsTouchActionNone && getSupportsPassive();

	// All variables local to 'scope' are prefixed with 'scope_'
	var scope_Target = target;
	var scope_Locations = [];
	var scope_Base;
	var scope_Handles;
	var scope_HandleNumbers = [];
	var scope_ActiveHandlesCount = 0;
	var scope_Connects;
	var scope_Spectrum = options.spectrum;
	var scope_Values = [];
	var scope_Events = {};
	var scope_Self;
	var scope_Pips;
	var scope_Document = target.ownerDocument;
	var scope_DocumentElement = scope_Document.documentElement;
	var scope_Body = scope_Document.body;


	// For horizontal sliders in standard ltr documents,
	// make .noUi-origin overflow to the left so the document doesn't scroll.
	var scope_DirOffset = (scope_Document.dir === 'rtl') || (options.ort === 1) ? 0 : 100;

/*! In this file: Construction of DOM elements; */

	// Creates a node, adds it to target, returns the new node.
	function addNodeTo ( addTarget, className ) {

		var div = scope_Document.createElement('div');

		if ( className ) {
			addClass(div, className);
		}

		addTarget.appendChild(div);

		return div;
	}

	// Append a origin to the base
	function addOrigin ( base, handleNumber ) {

		var origin = addNodeTo(base, options.cssClasses.origin);
		var handle = addNodeTo(origin, options.cssClasses.handle);

		handle.setAttribute('data-handle', handleNumber);

		// https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/tabindex
		// 0 = focusable and reachable
		handle.setAttribute('tabindex', '0');
		handle.setAttribute('role', 'slider');
		handle.setAttribute('aria-orientation', options.ort ? 'vertical' : 'horizontal');

		if ( handleNumber === 0 ) {
			addClass(handle, options.cssClasses.handleLower);
		}

		else if ( handleNumber === options.handles - 1 ) {
			addClass(handle, options.cssClasses.handleUpper);
		}

		return origin;
	}

	// Insert nodes for connect elements
	function addConnect ( base, add ) {

		if ( !add ) {
			return false;
		}

		return addNodeTo(base, options.cssClasses.connect);
	}

	// Add handles to the slider base.
	function addElements ( connectOptions, base ) {

		var connectBase = addNodeTo(base, options.cssClasses.connects);

		scope_Handles = [];
		scope_Connects = [];

		scope_Connects.push(addConnect(connectBase, connectOptions[0]));

		// [::::O====O====O====]
		// connectOptions = [0, 1, 1, 1]

		for ( var i = 0; i < options.handles; i++ ) {
			// Keep a list of all added handles.
			scope_Handles.push(addOrigin(base, i));
			scope_HandleNumbers[i] = i;
			scope_Connects.push(addConnect(connectBase, connectOptions[i + 1]));
		}
	}

	// Initialize a single slider.
	function addSlider ( addTarget ) {

		// Apply classes and data to the target.
		addClass(addTarget, options.cssClasses.target);

		if ( options.dir === 0 ) {
			addClass(addTarget, options.cssClasses.ltr);
		} else {
			addClass(addTarget, options.cssClasses.rtl);
		}

		if ( options.ort === 0 ) {
			addClass(addTarget, options.cssClasses.horizontal);
		} else {
			addClass(addTarget, options.cssClasses.vertical);
		}

		scope_Base = addNodeTo(addTarget, options.cssClasses.base);
	}


	function addTooltip ( handle, handleNumber ) {

		if ( !options.tooltips[handleNumber] ) {
			return false;
		}

		return addNodeTo(handle.firstChild, options.cssClasses.tooltip);
	}

	// The tooltips option is a shorthand for using the 'update' event.
	function tooltips ( ) {

		// Tooltips are added with options.tooltips in original order.
		var tips = scope_Handles.map(addTooltip);

		bindEvent('update', function(values, handleNumber, unencoded) {

			if ( !tips[handleNumber] ) {
				return;
			}

			var formattedValue = values[handleNumber];

			if ( options.tooltips[handleNumber] !== true ) {
				formattedValue = options.tooltips[handleNumber].to(unencoded[handleNumber]);
			}

			tips[handleNumber].innerHTML = formattedValue;
		});
	}


	function aria ( ) {

		bindEvent('update', function ( values, handleNumber, unencoded, tap, positions ) {

			// Update Aria Values for all handles, as a change in one changes min and max values for the next.
			scope_HandleNumbers.forEach(function( index ){

				var handle = scope_Handles[index];

				var min = checkHandlePosition(scope_Locations, index, 0, true, true, true);
				var max = checkHandlePosition(scope_Locations, index, 100, true, true, true);

				var now = positions[index];
				var text = options.ariaFormat.to(unencoded[index]);

				handle.children[0].setAttribute('aria-valuemin', min.toFixed(1));
				handle.children[0].setAttribute('aria-valuemax', max.toFixed(1));
				handle.children[0].setAttribute('aria-valuenow', now.toFixed(1));
				handle.children[0].setAttribute('aria-valuetext', text);
			});
		});
	}


	function getGroup ( mode, values, stepped ) {

		// Use the range.
		if ( mode === 'range' || mode === 'steps' ) {
			return scope_Spectrum.xVal;
		}

		if ( mode === 'count' ) {

			if ( values < 2 ) {
				throw new Error("noUiSlider (" + VERSION + "): 'values' (>= 2) required for mode 'count'.");
			}

			// Divide 0 - 100 in 'count' parts.
			var interval = values - 1;
			var spread = ( 100 / interval );

			values = [];

			// List these parts and have them handled as 'positions'.
			while ( interval-- ) {
				values[ interval ] = ( interval * spread );
			}

			values.push(100);

			mode = 'positions';
		}

		if ( mode === 'positions' ) {

			// Map all percentages to on-range values.
			return values.map(function( value ){
				return scope_Spectrum.fromStepping( stepped ? scope_Spectrum.getStep( value ) : value );
			});
		}

		if ( mode === 'values' ) {

			// If the value must be stepped, it needs to be converted to a percentage first.
			if ( stepped ) {

				return values.map(function( value ){

					// Convert to percentage, apply step, return to value.
					return scope_Spectrum.fromStepping( scope_Spectrum.getStep( scope_Spectrum.toStepping( value ) ) );
				});

			}

			// Otherwise, we can simply use the values.
			return values;
		}
	}

	function generateSpread ( density, mode, group ) {

		function safeIncrement(value, increment) {
			// Avoid floating point variance by dropping the smallest decimal places.
			return (value + increment).toFixed(7) / 1;
		}

		var indexes = {};
		var firstInRange = scope_Spectrum.xVal[0];
		var lastInRange = scope_Spectrum.xVal[scope_Spectrum.xVal.length-1];
		var ignoreFirst = false;
		var ignoreLast = false;
		var prevPct = 0;

		// Create a copy of the group, sort it and filter away all duplicates.
		group = unique(group.slice().sort(function(a, b){ return a - b; }));

		// Make sure the range starts with the first element.
		if ( group[0] !== firstInRange ) {
			group.unshift(firstInRange);
			ignoreFirst = true;
		}

		// Likewise for the last one.
		if ( group[group.length - 1] !== lastInRange ) {
			group.push(lastInRange);
			ignoreLast = true;
		}

		group.forEach(function ( current, index ) {

			// Get the current step and the lower + upper positions.
			var step;
			var i;
			var q;
			var low = current;
			var high = group[index+1];
			var newPct;
			var pctDifference;
			var pctPos;
			var type;
			var steps;
			var realSteps;
			var stepsize;

			// When using 'steps' mode, use the provided steps.
			// Otherwise, we'll step on to the next subrange.
			if ( mode === 'steps' ) {
				step = scope_Spectrum.xNumSteps[ index ];
			}

			// Default to a 'full' step.
			if ( !step ) {
				step = high-low;
			}

			// Low can be 0, so test for false. If high is undefined,
			// we are at the last subrange. Index 0 is already handled.
			if ( low === false || high === undefined ) {
				return;
			}

			// Make sure step isn't 0, which would cause an infinite loop (#654)
			step = Math.max(step, 0.0000001);

			// Find all steps in the subrange.
			for ( i = low; i <= high; i = safeIncrement(i, step) ) {

				// Get the percentage value for the current step,
				// calculate the size for the subrange.
				newPct = scope_Spectrum.toStepping( i );
				pctDifference = newPct - prevPct;

				steps = pctDifference / density;
				realSteps = Math.round(steps);

				// This ratio represents the amount of percentage-space a point indicates.
				// For a density 1 the points/percentage = 1. For density 2, that percentage needs to be re-devided.
				// Round the percentage offset to an even number, then divide by two
				// to spread the offset on both sides of the range.
				stepsize = pctDifference/realSteps;

				// Divide all points evenly, adding the correct number to this subrange.
				// Run up to <= so that 100% gets a point, event if ignoreLast is set.
				for ( q = 1; q <= realSteps; q += 1 ) {

					// The ratio between the rounded value and the actual size might be ~1% off.
					// Correct the percentage offset by the number of points
					// per subrange. density = 1 will result in 100 points on the
					// full range, 2 for 50, 4 for 25, etc.
					pctPos = prevPct + ( q * stepsize );
					indexes[pctPos.toFixed(5)] = ['x', 0];
				}

				// Determine the point type.
				type = (group.indexOf(i) > -1) ? 1 : ( mode === 'steps' ? 2 : 0 );

				// Enforce the 'ignoreFirst' option by overwriting the type for 0.
				if ( !index && ignoreFirst ) {
					type = 0;
				}

				if ( !(i === high && ignoreLast)) {
					// Mark the 'type' of this point. 0 = plain, 1 = real value, 2 = step value.
					indexes[newPct.toFixed(5)] = [i, type];
				}

				// Update the percentage count.
				prevPct = newPct;
			}
		});

		return indexes;
	}

	function addMarking ( spread, filterFunc, formatter ) {

		var element = scope_Document.createElement('div');

		var valueSizeClasses = [
			options.cssClasses.valueNormal,
			options.cssClasses.valueLarge,
			options.cssClasses.valueSub
		];
		var markerSizeClasses = [
			options.cssClasses.markerNormal,
			options.cssClasses.markerLarge,
			options.cssClasses.markerSub
		];
		var valueOrientationClasses = [
			options.cssClasses.valueHorizontal,
			options.cssClasses.valueVertical
		];
		var markerOrientationClasses = [
			options.cssClasses.markerHorizontal,
			options.cssClasses.markerVertical
		];

		addClass(element, options.cssClasses.pips);
		addClass(element, options.ort === 0 ? options.cssClasses.pipsHorizontal : options.cssClasses.pipsVertical);

		function getClasses( type, source ){
			var a = source === options.cssClasses.value;
			var orientationClasses = a ? valueOrientationClasses : markerOrientationClasses;
			var sizeClasses = a ? valueSizeClasses : markerSizeClasses;

			return source + ' ' + orientationClasses[options.ort] + ' ' + sizeClasses[type];
		}

		function addSpread ( offset, values ){

			// Apply the filter function, if it is set.
			values[1] = (values[1] && filterFunc) ? filterFunc(values[0], values[1]) : values[1];

			// Add a marker for every point
			var node = addNodeTo(element, false);
				node.className = getClasses(values[1], options.cssClasses.marker);
				node.style[options.style] = offset + '%';

			// Values are only appended for points marked '1' or '2'.
			if ( values[1] ) {
				node = addNodeTo(element, false);
				node.className = getClasses(values[1], options.cssClasses.value);
				node.setAttribute('data-value', values[0]);
				node.style[options.style] = offset + '%';
				node.innerText = formatter.to(values[0]);
			}
		}

		// Append all points.
		Object.keys(spread).forEach(function(a){
			addSpread(a, spread[a]);
		});

		return element;
	}

	function removePips ( ) {
		if ( scope_Pips ) {
			removeElement(scope_Pips);
			scope_Pips = null;
		}
	}

	function pips ( grid ) {

		// Fix #669
		removePips();

		var mode = grid.mode;
		var density = grid.density || 1;
		var filter = grid.filter || false;
		var values = grid.values || false;
		var stepped = grid.stepped || false;
		var group = getGroup( mode, values, stepped );
		var spread = generateSpread( density, mode, group );
		var format = grid.format || {
			to: Math.round
		};

		scope_Pips = scope_Target.appendChild(addMarking(
			spread,
			filter,
			format
		));

		return scope_Pips;
	}

/*! In this file: Browser events (not slider events like slide, change); */

	// Shorthand for base dimensions.
	function baseSize ( ) {
		var rect = scope_Base.getBoundingClientRect();
		var alt = 'offset' + ['Width', 'Height'][options.ort];
		return options.ort === 0 ? (rect.width||scope_Base[alt]) : (rect.height||scope_Base[alt]);
	}

	// Handler for attaching events trough a proxy.
	function attachEvent ( events, element, callback, data ) {

		// This function can be used to 'filter' events to the slider.
		// element is a node, not a nodeList

		var method = function ( e ){

			e = fixEvent(e, data.pageOffset, data.target || element);

			// fixEvent returns false if this event has a different target
			// when handling (multi-) touch events;
			if ( !e ) {
				return false;
			}

			// doNotReject is passed by all end events to make sure released touches
			// are not rejected, leaving the slider "stuck" to the cursor;
			if ( scope_Target.hasAttribute('disabled') && !data.doNotReject ) {
				return false;
			}

			// Stop if an active 'tap' transition is taking place.
			if ( hasClass(scope_Target, options.cssClasses.tap) && !data.doNotReject ) {
				return false;
			}

			// Ignore right or middle clicks on start #454
			if ( events === actions.start && e.buttons !== undefined && e.buttons > 1 ) {
				return false;
			}

			// Ignore right or middle clicks on start #454
			if ( data.hover && e.buttons ) {
				return false;
			}

			// 'supportsPassive' is only true if a browser also supports touch-action: none in CSS.
			// iOS safari does not, so it doesn't get to benefit from passive scrolling. iOS does support
			// touch-action: manipulation, but that allows panning, which breaks
			// sliders after zooming/on non-responsive pages.
			// See: https://bugs.webkit.org/show_bug.cgi?id=133112
			if ( !supportsPassive ) {
				e.preventDefault();
			}

			e.calcPoint = e.points[ options.ort ];

			// Call the event handler with the event [ and additional data ].
			callback ( e, data );
		};

		var methods = [];

		// Bind a closure on the target for every event type.
		events.split(' ').forEach(function( eventName ){
			element.addEventListener(eventName, method, supportsPassive ? { passive: true } : false);
			methods.push([eventName, method]);
		});

		return methods;
	}

	// Provide a clean event with standardized offset values.
	function fixEvent ( e, pageOffset, eventTarget ) {

		// Filter the event to register the type, which can be
		// touch, mouse or pointer. Offset changes need to be
		// made on an event specific basis.
		var touch = e.type.indexOf('touch') === 0;
		var mouse = e.type.indexOf('mouse') === 0;
		var pointer = e.type.indexOf('pointer') === 0;

		var x;
		var y;

		// IE10 implemented pointer events with a prefix;
		if ( e.type.indexOf('MSPointer') === 0 ) {
			pointer = true;
		}

		// In the event that multitouch is activated, the only thing one handle should be concerned
		// about is the touches that originated on top of it.
		if ( touch ) {

			// Returns true if a touch originated on the target.
			var isTouchOnTarget = function (checkTouch) {
				return checkTouch.target === eventTarget || eventTarget.contains(checkTouch.target);
			};

			// In the case of touchstart events, we need to make sure there is still no more than one
			// touch on the target so we look amongst all touches.
			if (e.type === 'touchstart') {

				var targetTouches = Array.prototype.filter.call(e.touches, isTouchOnTarget);

				// Do not support more than one touch per handle.
				if ( targetTouches.length > 1 ) {
					return false;
				}

				x = targetTouches[0].pageX;
				y = targetTouches[0].pageY;

			} else {

				// In the other cases, find on changedTouches is enough.
				var targetTouch = Array.prototype.find.call(e.changedTouches, isTouchOnTarget);

				// Cancel if the target touch has not moved.
				if ( !targetTouch ) {
					return false;
				}

				x = targetTouch.pageX;
				y = targetTouch.pageY;
			}
		}

		pageOffset = pageOffset || getPageOffset(scope_Document);

		if ( mouse || pointer ) {
			x = e.clientX + pageOffset.x;
			y = e.clientY + pageOffset.y;
		}

		e.pageOffset = pageOffset;
		e.points = [x, y];
		e.cursor = mouse || pointer; // Fix #435

		return e;
	}

	// Translate a coordinate in the document to a percentage on the slider
	function calcPointToPercentage ( calcPoint ) {
		var location = calcPoint - offset(scope_Base, options.ort);
		var proposal = ( location * 100 ) / baseSize();

		// Clamp proposal between 0% and 100%
		// Out-of-bound coordinates may occur when .noUi-base pseudo-elements
		// are used (e.g. contained handles feature)
		proposal = limit(proposal);

		return options.dir ? 100 - proposal : proposal;
	}

	// Find handle closest to a certain percentage on the slider
	function getClosestHandle ( proposal ) {

		var closest = 100;
		var handleNumber = false;

		scope_Handles.forEach(function(handle, index){

			// Disabled handles are ignored
			if ( handle.hasAttribute('disabled') ) {
				return;
			}

			var pos = Math.abs(scope_Locations[index] - proposal);

			if ( pos < closest || (pos === 100 && closest === 100) ) {
				handleNumber = index;
				closest = pos;
			}
		});

		return handleNumber;
	}

	// Fire 'end' when a mouse or pen leaves the document.
	function documentLeave ( event, data ) {
		if ( event.type === "mouseout" && event.target.nodeName === "HTML" && event.relatedTarget === null ){
			eventEnd (event, data);
		}
	}

	// Handle movement on document for handle and range drag.
	function eventMove ( event, data ) {

		// Fix #498
		// Check value of .buttons in 'start' to work around a bug in IE10 mobile (data.buttonsProperty).
		// https://connect.microsoft.com/IE/feedback/details/927005/mobile-ie10-windows-phone-buttons-property-of-pointermove-event-always-zero
		// IE9 has .buttons and .which zero on mousemove.
		// Firefox breaks the spec MDN defines.
		if ( navigator.appVersion.indexOf("MSIE 9") === -1 && event.buttons === 0 && data.buttonsProperty !== 0 ) {
			return eventEnd(event, data);
		}

		// Check if we are moving up or down
		var movement = (options.dir ? -1 : 1) * (event.calcPoint - data.startCalcPoint);

		// Convert the movement into a percentage of the slider width/height
		var proposal = (movement * 100) / data.baseSize;

		moveHandles(movement > 0, proposal, data.locations, data.handleNumbers);
	}

	// Unbind move events on document, call callbacks.
	function eventEnd ( event, data ) {

		// The handle is no longer active, so remove the class.
		if ( data.handle ) {
			removeClass(data.handle, options.cssClasses.active);
			scope_ActiveHandlesCount -= 1;
		}

		// Unbind the move and end events, which are added on 'start'.
		data.listeners.forEach(function( c ) {
			scope_DocumentElement.removeEventListener(c[0], c[1]);
		});

		if ( scope_ActiveHandlesCount === 0 ) {
			// Remove dragging class.
			removeClass(scope_Target, options.cssClasses.drag);
			setZindex();

			// Remove cursor styles and text-selection events bound to the body.
			if ( event.cursor ) {
				scope_Body.style.cursor = '';
				scope_Body.removeEventListener('selectstart', preventDefault);
			}
		}

		data.handleNumbers.forEach(function(handleNumber){
			fireEvent('change', handleNumber);
			fireEvent('set', handleNumber);
			fireEvent('end', handleNumber);
		});
	}

	// Bind move events on document.
	function eventStart ( event, data ) {

		var handle;
		if ( data.handleNumbers.length === 1 ) {

			var handleOrigin = scope_Handles[data.handleNumbers[0]];

			// Ignore 'disabled' handles
			if ( handleOrigin.hasAttribute('disabled') ) {
				return false;
			}

			handle = handleOrigin.children[0];
			scope_ActiveHandlesCount += 1;

			// Mark the handle as 'active' so it can be styled.
			addClass(handle, options.cssClasses.active);
		}

		// A drag should never propagate up to the 'tap' event.
		event.stopPropagation();

		// Record the event listeners.
		var listeners = [];

		// Attach the move and end events.
		var moveEvent = attachEvent(actions.move, scope_DocumentElement, eventMove, {
			// The event target has changed so we need to propagate the original one so that we keep
			// relying on it to extract target touches.
			target: event.target,
			handle: handle,
			listeners: listeners,
			startCalcPoint: event.calcPoint,
			baseSize: baseSize(),
			pageOffset: event.pageOffset,
			handleNumbers: data.handleNumbers,
			buttonsProperty: event.buttons,
			locations: scope_Locations.slice()
		});

		var endEvent = attachEvent(actions.end, scope_DocumentElement, eventEnd, {
			target: event.target,
			handle: handle,
			listeners: listeners,
			doNotReject: true,
			handleNumbers: data.handleNumbers
		});

		var outEvent = attachEvent("mouseout", scope_DocumentElement, documentLeave, {
			target: event.target,
			handle: handle,
			listeners: listeners,
			doNotReject: true,
			handleNumbers: data.handleNumbers
		});

		// We want to make sure we pushed the listeners in the listener list rather than creating
		// a new one as it has already been passed to the event handlers.
		listeners.push.apply(listeners, moveEvent.concat(endEvent, outEvent));

		// Text selection isn't an issue on touch devices,
		// so adding cursor styles can be skipped.
		if ( event.cursor ) {

			// Prevent the 'I' cursor and extend the range-drag cursor.
			scope_Body.style.cursor = getComputedStyle(event.target).cursor;

			// Mark the target with a dragging state.
			if ( scope_Handles.length > 1 ) {
				addClass(scope_Target, options.cssClasses.drag);
			}

			// Prevent text selection when dragging the handles.
			// In noUiSlider <= 9.2.0, this was handled by calling preventDefault on mouse/touch start/move,
			// which is scroll blocking. The selectstart event is supported by FireFox starting from version 52,
			// meaning the only holdout is iOS Safari. This doesn't matter: text selection isn't triggered there.
			// The 'cursor' flag is false.
			// See: http://caniuse.com/#search=selectstart
			scope_Body.addEventListener('selectstart', preventDefault, false);
		}

		data.handleNumbers.forEach(function(handleNumber){
			fireEvent('start', handleNumber);
		});
	}

	// Move closest handle to tapped location.
	function eventTap ( event ) {

		// The tap event shouldn't propagate up
		event.stopPropagation();

		var proposal = calcPointToPercentage(event.calcPoint);
		var handleNumber = getClosestHandle(proposal);

		// Tackle the case that all handles are 'disabled'.
		if ( handleNumber === false ) {
			return false;
		}

		// Flag the slider as it is now in a transitional state.
		// Transition takes a configurable amount of ms (default 300). Re-enable the slider after that.
		if ( !options.events.snap ) {
			addClassFor(scope_Target, options.cssClasses.tap, options.animationDuration);
		}

		setHandle(handleNumber, proposal, true, true);

		setZindex();

		fireEvent('slide', handleNumber, true);
		fireEvent('update', handleNumber, true);
		fireEvent('change', handleNumber, true);
		fireEvent('set', handleNumber, true);

		if ( options.events.snap ) {
			eventStart(event, { handleNumbers: [handleNumber] });
		}
	}

	// Fires a 'hover' event for a hovered mouse/pen position.
	function eventHover ( event ) {

		var proposal = calcPointToPercentage(event.calcPoint);

		var to = scope_Spectrum.getStep(proposal);
		var value = scope_Spectrum.fromStepping(to);

		Object.keys(scope_Events).forEach(function( targetEvent ) {
			if ( 'hover' === targetEvent.split('.')[0] ) {
				scope_Events[targetEvent].forEach(function( callback ) {
					callback.call( scope_Self, value );
				});
			}
		});
	}

	// Attach events to several slider parts.
	function bindSliderEvents ( behaviour ) {

		// Attach the standard drag event to the handles.
		if ( !behaviour.fixed ) {

			scope_Handles.forEach(function( handle, index ){

				// These events are only bound to the visual handle
				// element, not the 'real' origin element.
				attachEvent ( actions.start, handle.children[0], eventStart, {
					handleNumbers: [index]
				});
			});
		}

		// Attach the tap event to the slider base.
		if ( behaviour.tap ) {
			attachEvent (actions.start, scope_Base, eventTap, {});
		}

		// Fire hover events
		if ( behaviour.hover ) {
			attachEvent (actions.move, scope_Base, eventHover, { hover: true });
		}

		// Make the range draggable.
		if ( behaviour.drag ){

			scope_Connects.forEach(function( connect, index ){

				if ( connect === false || index === 0 || index === scope_Connects.length - 1 ) {
					return;
				}

				var handleBefore = scope_Handles[index - 1];
				var handleAfter = scope_Handles[index];
				var eventHolders = [connect];

				addClass(connect, options.cssClasses.draggable);

				// When the range is fixed, the entire range can
				// be dragged by the handles. The handle in the first
				// origin will propagate the start event upward,
				// but it needs to be bound manually on the other.
				if ( behaviour.fixed ) {
					eventHolders.push(handleBefore.children[0]);
					eventHolders.push(handleAfter.children[0]);
				}

				eventHolders.forEach(function( eventHolder ) {
					attachEvent ( actions.start, eventHolder, eventStart, {
						handles: [handleBefore, handleAfter],
						handleNumbers: [index - 1, index]
					});
				});
			});
		}
	}

/*! In this file: Slider events (not browser events); */

	// Attach an event to this slider, possibly including a namespace
	function bindEvent ( namespacedEvent, callback ) {
		scope_Events[namespacedEvent] = scope_Events[namespacedEvent] || [];
		scope_Events[namespacedEvent].push(callback);

		// If the event bound is 'update,' fire it immediately for all handles.
		if ( namespacedEvent.split('.')[0] === 'update' ) {
			scope_Handles.forEach(function(a, index){
				fireEvent('update', index);
			});
		}
	}

	// Undo attachment of event
	function removeEvent ( namespacedEvent ) {

		var event = namespacedEvent && namespacedEvent.split('.')[0];
		var namespace = event && namespacedEvent.substring(event.length);

		Object.keys(scope_Events).forEach(function( bind ){

			var tEvent = bind.split('.')[0];
			var tNamespace = bind.substring(tEvent.length);

			if ( (!event || event === tEvent) && (!namespace || namespace === tNamespace) ) {
				delete scope_Events[bind];
			}
		});
	}

	// External event handling
	function fireEvent ( eventName, handleNumber, tap ) {

		Object.keys(scope_Events).forEach(function( targetEvent ) {

			var eventType = targetEvent.split('.')[0];

			if ( eventName === eventType ) {
				scope_Events[targetEvent].forEach(function( callback ) {

					callback.call(
						// Use the slider public API as the scope ('this')
						scope_Self,
						// Return values as array, so arg_1[arg_2] is always valid.
						scope_Values.map(options.format.to),
						// Handle index, 0 or 1
						handleNumber,
						// Unformatted slider values
						scope_Values.slice(),
						// Event is fired by tap, true or false
						tap || false,
						// Left offset of the handle, in relation to the slider
						scope_Locations.slice()
					);
				});
			}
		});
	}

/*! In this file: Mechanics for slider operation */

	function toPct ( pct ) {
		return pct + '%';
	}

	// Split out the handle positioning logic so the Move event can use it, too
	function checkHandlePosition ( reference, handleNumber, to, lookBackward, lookForward, getValue ) {

		// For sliders with multiple handles, limit movement to the other handle.
		// Apply the margin option by adding it to the handle positions.
		if ( scope_Handles.length > 1 ) {

			if ( lookBackward && handleNumber > 0 ) {
				to = Math.max(to, reference[handleNumber - 1] + options.margin);
			}

			if ( lookForward && handleNumber < scope_Handles.length - 1 ) {
				to = Math.min(to, reference[handleNumber + 1] - options.margin);
			}
		}

		// The limit option has the opposite effect, limiting handles to a
		// maximum distance from another. Limit must be > 0, as otherwise
		// handles would be unmoveable.
		if ( scope_Handles.length > 1 && options.limit ) {

			if ( lookBackward && handleNumber > 0 ) {
				to = Math.min(to, reference[handleNumber - 1] + options.limit);
			}

			if ( lookForward && handleNumber < scope_Handles.length - 1 ) {
				to = Math.max(to, reference[handleNumber + 1] - options.limit);
			}
		}

		// The padding option keeps the handles a certain distance from the
		// edges of the slider. Padding must be > 0.
		if ( options.padding ) {

			if ( handleNumber === 0 ) {
				to = Math.max(to, options.padding[0]);
			}

			if ( handleNumber === scope_Handles.length - 1 ) {
				to = Math.min(to, 100 - options.padding[1]);
			}
		}

		to = scope_Spectrum.getStep(to);

		// Limit percentage to the 0 - 100 range
		to = limit(to);

		// Return false if handle can't move
		if ( to === reference[handleNumber] && !getValue ) {
			return false;
		}

		return to;
	}

	// Uses slider orientation to create CSS rules. a = base value;
	function inRuleOrder ( v, a ) {
		var o = options.ort;
		return (o?a:v) + ', ' + (o?v:a);
	}

	// Moves handle(s) by a percentage
	// (bool, % to move, [% where handle started, ...], [index in scope_Handles, ...])
	function moveHandles ( upward, proposal, locations, handleNumbers ) {

		var proposals = locations.slice();

		var b = [!upward, upward];
		var f = [upward, !upward];

		// Copy handleNumbers so we don't change the dataset
		handleNumbers = handleNumbers.slice();

		// Check to see which handle is 'leading'.
		// If that one can't move the second can't either.
		if ( upward ) {
			handleNumbers.reverse();
		}

		// Step 1: get the maximum percentage that any of the handles can move
		if ( handleNumbers.length > 1 ) {

			handleNumbers.forEach(function(handleNumber, o) {

				var to = checkHandlePosition(proposals, handleNumber, proposals[handleNumber] + proposal, b[o], f[o], false);

				// Stop if one of the handles can't move.
				if ( to === false ) {
					proposal = 0;
				} else {
					proposal = to - proposals[handleNumber];
					proposals[handleNumber] = to;
				}
			});
		}

		// If using one handle, check backward AND forward
		else {
			b = f = [true];
		}

		var state = false;

		// Step 2: Try to set the handles with the found percentage
		handleNumbers.forEach(function(handleNumber, o) {
			state = setHandle(handleNumber, locations[handleNumber] + proposal, b[o], f[o]) || state;
		});

		// Step 3: If a handle moved, fire events
		if ( state ) {
			handleNumbers.forEach(function(handleNumber){
				fireEvent('update', handleNumber);
				fireEvent('slide', handleNumber);
			});
		}
	}

	// Takes a base value and an offset. This offset is used for the connect bar size.
	// In the initial design for this feature, the origin element was 1% wide.
	// Unfortunately, a rounding bug in Chrome makes it impossible to implement this feature
	// in this manner: https://bugs.chromium.org/p/chromium/issues/detail?id=798223
	function transformDirection ( a, b ) {
		return options.dir ? 100 - a - b : a;
	}

	// Updates scope_Locations and scope_Values, updates visual state
	function updateHandlePosition ( handleNumber, to ) {

		// Update locations.
		scope_Locations[handleNumber] = to;

		// Convert the value to the slider stepping/range.
		scope_Values[handleNumber] = scope_Spectrum.fromStepping(to);

		var rule = 'translate(' + inRuleOrder(toPct(transformDirection(to, 0) - scope_DirOffset), '0') + ')';
		scope_Handles[handleNumber].style[options.transformRule] = rule;

		updateConnect(handleNumber);
		updateConnect(handleNumber + 1);
	}

	// Handles before the slider middle are stacked later = higher,
	// Handles after the middle later is lower
	// [[7] [8] .......... | .......... [5] [4]
	function setZindex ( ) {

		scope_HandleNumbers.forEach(function(handleNumber){
			var dir = (scope_Locations[handleNumber] > 50 ? -1 : 1);
			var zIndex = 3 + (scope_Handles.length + (dir * handleNumber));
			scope_Handles[handleNumber].style.zIndex = zIndex;
		});
	}

	// Test suggested values and apply margin, step.
	function setHandle ( handleNumber, to, lookBackward, lookForward ) {

		to = checkHandlePosition(scope_Locations, handleNumber, to, lookBackward, lookForward, false);

		if ( to === false ) {
			return false;
		}

		updateHandlePosition(handleNumber, to);

		return true;
	}

	// Updates style attribute for connect nodes
	function updateConnect ( index ) {

		// Skip connects set to false
		if ( !scope_Connects[index] ) {
			return;
		}

		var l = 0;
		var h = 100;

		if ( index !== 0 ) {
			l = scope_Locations[index - 1];
		}

		if ( index !== scope_Connects.length - 1 ) {
			h = scope_Locations[index];
		}

		// We use two rules:
		// 'translate' to change the left/top offset;
		// 'scale' to change the width of the element;
		// As the element has a width of 100%, a translation of 100% is equal to 100% of the parent (.noUi-base)
		var connectWidth = h - l;
		var translateRule = 'translate(' + inRuleOrder(toPct(transformDirection(l, connectWidth)), '0') + ')';
		var scaleRule = 'scale(' + inRuleOrder(connectWidth / 100, '1') + ')';

		scope_Connects[index].style[options.transformRule] = translateRule + ' ' + scaleRule;
	}

/*! In this file: All methods eventually exposed in slider.noUiSlider... */

	// Parses value passed to .set method. Returns current value if not parse-able.
	function resolveToValue ( to, handleNumber ) {

		// Setting with null indicates an 'ignore'.
		// Inputting 'false' is invalid.
		if ( to === null || to === false || to === undefined ) {
			return scope_Locations[handleNumber];
		}

		// If a formatted number was passed, attempt to decode it.
		if ( typeof to === 'number' ) {
			to = String(to);
		}

		to = options.format.from(to);
		to = scope_Spectrum.toStepping(to);

		// If parsing the number failed, use the current value.
		if ( to === false || isNaN(to) ) {
			return scope_Locations[handleNumber];
		}

		return to;
	}

	// Set the slider value.
	function valueSet ( input, fireSetEvent ) {

		var values = asArray(input);
		var isInit = scope_Locations[0] === undefined;

		// Event fires by default
		fireSetEvent = (fireSetEvent === undefined ? true : !!fireSetEvent);

		// Animation is optional.
		// Make sure the initial values were set before using animated placement.
		if ( options.animate && !isInit ) {
			addClassFor(scope_Target, options.cssClasses.tap, options.animationDuration);
		}

		// First pass, without lookAhead but with lookBackward. Values are set from left to right.
		scope_HandleNumbers.forEach(function(handleNumber){
			setHandle(handleNumber, resolveToValue(values[handleNumber], handleNumber), true, false);
		});

		// Second pass. Now that all base values are set, apply constraints
		scope_HandleNumbers.forEach(function(handleNumber){
			setHandle(handleNumber, scope_Locations[handleNumber], true, true);
		});

		setZindex();

		scope_HandleNumbers.forEach(function(handleNumber){

			fireEvent('update', handleNumber);

			// Fire the event only for handles that received a new value, as per #579
			if ( values[handleNumber] !== null && fireSetEvent ) {
				fireEvent('set', handleNumber);
			}
		});
	}

	// Reset slider to initial values
	function valueReset ( fireSetEvent ) {
		valueSet(options.start, fireSetEvent);
	}

	// Get the slider value.
	function valueGet ( ) {

		var values = scope_Values.map(options.format.to);

		// If only one handle is used, return a single value.
		if ( values.length === 1 ){
			return values[0];
		}

		return values;
	}

	// Removes classes from the root and empties it.
	function destroy ( ) {

		for ( var key in options.cssClasses ) {
			if ( !options.cssClasses.hasOwnProperty(key) ) { continue; }
			removeClass(scope_Target, options.cssClasses[key]);
		}

		while (scope_Target.firstChild) {
			scope_Target.removeChild(scope_Target.firstChild);
		}

		delete scope_Target.noUiSlider;
	}

	// Get the current step size for the slider.
	function getCurrentStep ( ) {

		// Check all locations, map them to their stepping point.
		// Get the step point, then find it in the input list.
		return scope_Locations.map(function( location, index ){

			var nearbySteps = scope_Spectrum.getNearbySteps( location );
			var value = scope_Values[index];
			var increment = nearbySteps.thisStep.step;
			var decrement = null;

			// If the next value in this step moves into the next step,
			// the increment is the start of the next step - the current value
			if ( increment !== false ) {
				if ( value + increment > nearbySteps.stepAfter.startValue ) {
					increment = nearbySteps.stepAfter.startValue - value;
				}
			}


			// If the value is beyond the starting point
			if ( value > nearbySteps.thisStep.startValue ) {
				decrement = nearbySteps.thisStep.step;
			}

			else if ( nearbySteps.stepBefore.step === false ) {
				decrement = false;
			}

			// If a handle is at the start of a step, it always steps back into the previous step first
			else {
				decrement = value - nearbySteps.stepBefore.highestStep;
			}


			// Now, if at the slider edges, there is not in/decrement
			if ( location === 100 ) {
				increment = null;
			}

			else if ( location === 0 ) {
				decrement = null;
			}

			// As per #391, the comparison for the decrement step can have some rounding issues.
			var stepDecimals = scope_Spectrum.countStepDecimals();

			// Round per #391
			if ( increment !== null && increment !== false ) {
				increment = Number(increment.toFixed(stepDecimals));
			}

			if ( decrement !== null && decrement !== false ) {
				decrement = Number(decrement.toFixed(stepDecimals));
			}

			return [decrement, increment];
		});
	}

	// Updateable: margin, limit, padding, step, range, animate, snap
	function updateOptions ( optionsToUpdate, fireSetEvent ) {

		// Spectrum is created using the range, snap, direction and step options.
		// 'snap' and 'step' can be updated.
		// If 'snap' and 'step' are not passed, they should remain unchanged.
		var v = valueGet();

		var updateAble = ['margin', 'limit', 'padding', 'range', 'animate', 'snap', 'step', 'format'];

		// Only change options that we're actually passed to update.
		updateAble.forEach(function(name){
			if ( optionsToUpdate[name] !== undefined ) {
				originalOptions[name] = optionsToUpdate[name];
			}
		});

		var newOptions = testOptions(originalOptions);

		// Load new options into the slider state
		updateAble.forEach(function(name){
			if ( optionsToUpdate[name] !== undefined ) {
				options[name] = newOptions[name];
			}
		});

		scope_Spectrum = newOptions.spectrum;

		// Limit, margin and padding depend on the spectrum but are stored outside of it. (#677)
		options.margin = newOptions.margin;
		options.limit = newOptions.limit;
		options.padding = newOptions.padding;

		// Update pips, removes existing.
		if ( options.pips ) {
			pips(options.pips);
		}

		// Invalidate the current positioning so valueSet forces an update.
		scope_Locations = [];
		valueSet(optionsToUpdate.start || v, fireSetEvent);
	}

/*! In this file: Calls to functions. All other scope_ files define functions only; */

	// Create the base element, initialize HTML and set classes.
	// Add handles and connect elements.
	addSlider(scope_Target);
	addElements(options.connect, scope_Base);

	// Attach user events.
	bindSliderEvents(options.events);

	// Use the public value method to set the start values.
	valueSet(options.start);

	scope_Self = {
		destroy: destroy,
		steps: getCurrentStep,
		on: bindEvent,
		off: removeEvent,
		get: valueGet,
		set: valueSet,
		reset: valueReset,
		// Exposed for unit testing, don't use this in your application.
		__moveHandles: function(a, b, c) { moveHandles(a, b, scope_Locations, c); },
		options: originalOptions, // Issue #600, #678
		updateOptions: updateOptions,
		target: scope_Target, // Issue #597
		removePips: removePips,
		pips: pips // Issue #594
	};

	if ( options.pips ) {
		pips(options.pips);
	}

	if ( options.tooltips ) {
		tooltips();
	}

	aria();

	return scope_Self;

}


	// Run the standard initializer
	function initialize ( target, originalOptions ) {

		if ( !target || !target.nodeName ) {
			throw new Error("noUiSlider (" + VERSION + "): create requires a single element, got: " + target);
		}

		// Throw an error if the slider was already initialized.
		if ( target.noUiSlider ) {
			throw new Error("noUiSlider (" + VERSION + "): Slider was already initialized.");
		}

		// Test the options and create the slider environment;
		var options = testOptions( originalOptions, target );
		var api = scope( target, options, originalOptions );

		target.noUiSlider = api;

		return api;
	}

	// Use an object instead of a function for future expandability;
	return {
		version: VERSION,
		create: initialize
	};

}));
},{}],3:[function(require,module,exports){
(function (global){

var $ 				= (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null);
var state 			= require('./state');
var process_form 	= require('./process_form');
var noUiSlider		= require('nouislider');
//var cookies         = require('js-cookie');
var thirdParty      = require('./thirdparty');

window.searchAndFilter = {
    extensions: [],
    registerExtension: function( extensionName ) {
        this.extensions.push( extensionName );
    }
};

module.exports = function(options)
{
    var defaults = {
        startOpened: false,
        isInit: true,
        action: ""
    };

    var opts = jQuery.extend(defaults, options);
    
    thirdParty.init();
    
    //loop through each item matched
    this.each(function()
    {

        var $this = $(this);
        var self = this;
        this.sfid = $this.attr("data-sf-form-id");

        state.addSearchForm(this.sfid, this);

        this.$fields = $this.find("> ul > li"); //a reference to each fields parent LI

        this.enable_taxonomy_archives = $this.attr('data-taxonomy-archives');
        this.current_taxonomy_archive = $this.attr('data-current-taxonomy-archive');

        if(typeof(this.enable_taxonomy_archives)=="undefined")
        {
            this.enable_taxonomy_archives = "0";
        }
        if(typeof(this.current_taxonomy_archive)=="undefined")
        {
            this.current_taxonomy_archive = "";
        }

        process_form.init(self.enable_taxonomy_archives, self.current_taxonomy_archive);
        //process_form.setTaxArchiveResultsUrl(self);
        process_form.enableInputs(self);

        if(typeof(this.extra_query_params)=="undefined")
        {
            this.extra_query_params = {all: {}, results: {}, ajax: {}};
        }


        this.template_is_loaded = $this.attr("data-template-loaded");
        this.is_ajax = $this.attr("data-ajax");
        this.instance_number = $this.attr('data-instance-count');
        this.$ajax_results_container = jQuery($this.attr("data-ajax-target"));

        this.ajax_update_sections = $this.attr("data-ajax-update-sections") ? JSON.parse( $this.attr("data-ajax-update-sections") ) : [];
        this.replace_results = $this.attr("data-replace-results") === "0" ? false : true;
        
        this.results_url = $this.attr("data-results-url");
        this.debug_mode = $this.attr("data-debug-mode");
        this.update_ajax_url = $this.attr("data-update-ajax-url");
        this.pagination_type = $this.attr("data-ajax-pagination-type");
        this.auto_count = $this.attr("data-auto-count");
        this.auto_count_refresh_mode = $this.attr("data-auto-count-refresh-mode");
        this.only_results_ajax = $this.attr("data-only-results-ajax"); //if we are not on the results page, redirect rather than try to load via ajax
        this.scroll_to_pos = $this.attr("data-scroll-to-pos");
        this.custom_scroll_to = $this.attr("data-custom-scroll-to");
        this.scroll_on_action = $this.attr("data-scroll-on-action");
        this.lang_code = $this.attr("data-lang-code");
        this.ajax_url = $this.attr('data-ajax-url');
        this.ajax_form_url = $this.attr('data-ajax-form-url');
        this.is_rtl = $this.attr('data-is-rtl');

        this.display_result_method = $this.attr('data-display-result-method');
        this.maintain_state = $this.attr('data-maintain-state');
        this.ajax_action = "";
        this.last_submit_query_params = "";

        this.current_paged = parseInt($this.attr('data-init-paged'));
        this.last_load_more_html = "";
        this.load_more_html = "";
        this.ajax_data_type = $this.attr('data-ajax-data-type');
        this.ajax_target_attr = $this.attr("data-ajax-target");
        this.use_history_api = $this.attr("data-use-history-api");
        this.is_submitting = false;

        this.last_ajax_request = null;

        if(typeof(this.results_html)=="undefined")
        {
            this.results_html = "";
        }

        if(typeof(this.use_history_api)=="undefined")
        {
            this.use_history_api = "";
        }

        if(typeof(this.pagination_type)=="undefined")
        {
            this.pagination_type = "normal";
        }
        if(typeof(this.current_paged)=="undefined")
        {
            this.current_paged = 1;
        }

        if(typeof(this.ajax_target_attr)=="undefined")
        {
            this.ajax_target_attr = "";
        }

        if(typeof(this.ajax_url)=="undefined")
        {
            this.ajax_url = "";
        }

        if(typeof(this.ajax_form_url)=="undefined")
        {
            this.ajax_form_url = "";
        }

        if(typeof(this.results_url)=="undefined")
        {
            this.results_url = "";
        }

        if(typeof(this.scroll_to_pos)=="undefined")
        {
            this.scroll_to_pos = "";
        }

        if(typeof(this.scroll_on_action)=="undefined")
        {
            this.scroll_on_action = "";
        }
        if(typeof(this.custom_scroll_to)=="undefined")
        {
            this.custom_scroll_to = "";
        }
        this.$custom_scroll_to = jQuery(this.custom_scroll_to);

        if(typeof(this.update_ajax_url)=="undefined")
        {
            this.update_ajax_url = "";
        }

        if(typeof(this.debug_mode)=="undefined")
        {
            this.debug_mode = "";
        }

        if(typeof(this.ajax_target_object)=="undefined")
        {
            this.ajax_target_object = "";
        }

        if(typeof(this.template_is_loaded)=="undefined")
        {
            this.template_is_loaded = "0";
        }

        if(typeof(this.auto_count_refresh_mode)=="undefined")
        {
            this.auto_count_refresh_mode = "0";
        }

        this.ajax_links_selector = $this.attr("data-ajax-links-selector");


        this.auto_update = $this.attr("data-auto-update");
        this.inputTimer = 0;

        this.setInfiniteScrollContainer = function()
        {
            // When we navigate away from search results, and then press back,
            // is_max_paged is retained, so we only want to set it to false if
            // we are initalizing the results page the first time - so just 
            // check if this var is undefined (as it should be on first use);
            if ( typeof ( this.is_max_paged ) === 'undefined' ) {
                this.is_max_paged = false; //for load more only, once we detect we're at the end set this to true
            }

            this.use_scroll_loader = $this.attr('data-show-scroll-loader');
            this.infinite_scroll_container = $this.attr('data-infinite-scroll-container');
            this.infinite_scroll_trigger_amount = $this.attr('data-infinite-scroll-trigger');
            this.infinite_scroll_result_class = $this.attr('data-infinite-scroll-result-class');
            this.$infinite_scroll_container = this.$ajax_results_container;

            if(typeof(this.infinite_scroll_container)=="undefined")
            {
                this.infinite_scroll_container = "";
            }
            else
            {
                this.$infinite_scroll_container = jQuery($this.attr('data-infinite-scroll-container'));
            }

            if(typeof(this.infinite_scroll_result_class)=="undefined")
            {
                this.infinite_scroll_result_class = "";
            }

            if(typeof(this.use_scroll_loader)=="undefined")
            {
                this.use_scroll_loader = 1;
            }

        };
        this.setInfiniteScrollContainer();

        /* functions */

        this.reset = function(submit_form)
        {

            this.resetForm(submit_form);
            return true;
        }

        this.inputUpdate = function(delayDuration)
        {
            if(typeof(delayDuration)=="undefined")
            {
                var delayDuration = 300;
            }

            self.resetTimer(delayDuration);
        }

        this.scrollToPos = function() {
            var offset = 0;
            var canScroll = true;

            if(self.is_ajax==1)
            {
                if(self.scroll_to_pos=="window")
                {
                    offset = 0;

                }
                else if(self.scroll_to_pos=="form")
                {
                    offset = $this.offset().top;
                }
                else if(self.scroll_to_pos=="results")
                {
                    if(self.$ajax_results_container.length>0)
                    {
                        offset = self.$ajax_results_container.offset().top;
                    }
                }
                else if(self.scroll_to_pos=="custom")
                {
                    //custom_scroll_to
                    if(self.$custom_scroll_to.length>0)
                    {
                        offset = self.$custom_scroll_to.offset().top;
                    }
                }
                else
                {
                    canScroll = false;
                }

                if(canScroll)
                {
                    $("html, body").stop().animate({
                        scrollTop: offset
                    }, "normal", "easeOutQuad" );
                }
            }

        };

        this.attachActiveClass = function(){

            //check to see if we are using ajax & auto count
            //if not, the search form does not get reloaded, so we need to update the sf-option-active class on all fields

            $this.on('change', 'input[type="radio"], input[type="checkbox"], select', function(e)
            {
                var $cthis = $(this);
                var $cthis_parent = $cthis.closest("li[data-sf-field-name]");
                var this_tag = $cthis.prop("tagName").toLowerCase();
                var input_type = $cthis.attr("type");
                var parent_tag = $cthis_parent.prop("tagName").toLowerCase();

                if((this_tag=="input")&&((input_type=="radio")||(input_type=="checkbox")) && (parent_tag=="li"))
                {
                    var $all_options = $cthis_parent.parent().find('li');
                    var $all_options_fields = $cthis_parent.parent().find('input:checked');

                    $all_options.removeClass("sf-option-active");
                    $all_options_fields.each(function(){

                        var $parent = $(this).closest("li");
                        $parent.addClass("sf-option-active");

                    });

                }
                else if(this_tag=="select")
                {
                    var $all_options = $cthis.children();
                    $all_options.removeClass("sf-option-active");
                    var this_val = $cthis.val();

                    var this_arr_val = (typeof this_val == 'string' || this_val instanceof String) ? [this_val] : this_val;

                    $(this_arr_val).each(function(i, value){
                        $cthis.find("option[value='"+value+"']").addClass("sf-option-active");
                    });


                }
            });

        };
        this.initAutoUpdateEvents = function(){

            /* auto update */
            if((self.auto_update==1)||(self.auto_count_refresh_mode==1))
            {
                $this.on('change', 'input[type="radio"], input[type="checkbox"], select', function(e) {
                    self.inputUpdate(200);
                });

                $this.on('input', 'input[type="number"]', function(e) {
                    self.inputUpdate(800);
                });

                var $textInput = $this.find('input[type="text"]:not(.sf-datepicker)');
                var lastValue = $textInput.val();

                $this.on('input', 'input[type="text"]:not(.sf-datepicker)', function()
                {
                    if(lastValue!=$textInput.val())
                    {
                        self.inputUpdate(1200);
                    }

                    lastValue = $textInput.val();
                });


                $this.on('keypress', 'input[type="text"]:not(.sf-datepicker)', function(e)
                {
                    if (e.which == 13){

                        e.preventDefault();
                        self.submitForm();
                        return false;
                    }

                });

                //$this.on('input', 'input.sf-datepicker', self.dateInputType);

            }
        };

        //this.initAutoUpdateEvents();


        this.clearTimer = function()
        {
            clearTimeout(self.inputTimer);
        };
        this.resetTimer = function(delayDuration)
        {
            clearTimeout(self.inputTimer);
            self.inputTimer = setTimeout(self.formUpdated, delayDuration);

        };

        this.addDatePickers = function()
        {
            var $date_picker = $this.find(".sf-datepicker");

            if($date_picker.length>0)
            {
                $date_picker.each(function(){

                    var $this = $(this);
                    var dateFormat = "";
                    var dateDropdownYear = false;
                    var dateDropdownMonth = false;

                    var $closest_date_wrap = $this.closest(".sf_date_field");
                    if($closest_date_wrap.length>0)
                    {
                        dateFormat = $closest_date_wrap.attr("data-date-format");

                        if($closest_date_wrap.attr("data-date-use-year-dropdown")==1)
                        {
                            dateDropdownYear = true;
                        }
                        if($closest_date_wrap.attr("data-date-use-month-dropdown")==1)
                        {
                            dateDropdownMonth = true;
                        }
                    }

                    var datePickerOptions = {
                        inline: true,
                        showOtherMonths: true,
                        onSelect: function(e, from_field){ self.dateSelect(e, from_field, $(this)); },
                        dateFormat: dateFormat,

                        changeMonth: dateDropdownMonth,
                        changeYear: dateDropdownYear
                    };

                    if(self.is_rtl==1)
                    {
                        datePickerOptions.direction = "rtl";
                    }

                    $this.datepicker(datePickerOptions);

                    if(self.lang_code!="")
                    {
                        $.datepicker.setDefaults(
                            $.extend(
                                {'dateFormat':dateFormat},
                                $.datepicker.regional[ self.lang_code]
                            )
                        );

                    }
                    else
                    {
                        $.datepicker.setDefaults(
                            $.extend(
                                {'dateFormat':dateFormat},
                                $.datepicker.regional["en"]
                            )
                        );

                    }

                });

                if($('.ll-skin-melon').length==0){

                    $date_picker.datepicker('widget').wrap('<div class="ll-skin-melon searchandfilter-date-picker"/>');
                }

            }
        };

        this.dateSelect = function(e, from_field, $this)
        {
            var $input_field = $(from_field.input.get(0));
            var $this = $(this);

            var $date_fields = $input_field.closest('[data-sf-field-input-type="daterange"], [data-sf-field-input-type="date"]');
            $date_fields.each(function(e, index){
                
                var $tf_date_pickers = $(this).find(".sf-datepicker");
                var no_date_pickers = $tf_date_pickers.length;
                
                if(no_date_pickers>1)
                {
                    //then it is a date range, so make sure both fields are filled before updating
                    var dp_counter = 0;
                    var dp_empty_field_count = 0;
                    $tf_date_pickers.each(function(){

                        if($(this).val()=="")
                        {
                            dp_empty_field_count++;
                        }

                        dp_counter++;
                    });

                    if(dp_empty_field_count==0)
                    {
                        self.inputUpdate(1);
                    }
                }
                else
                {
                    self.inputUpdate(1);
                }

            });
        };

        this.addRangeSliders = function()
        {
            var $meta_range = $this.find(".sf-meta-range-slider");

            if($meta_range.length>0)
            {
                $meta_range.each(function(){

                    var $this = $(this);
                    var min = $this.attr("data-min");
                    var max = $this.attr("data-max");
                    var smin = $this.attr("data-start-min");
                    var smax = $this.attr("data-start-max");
                    var display_value_as = $this.attr("data-display-values-as");
                    var step = $this.attr("data-step");
                    var $start_val = $this.find('.sf-range-min');
                    var $end_val = $this.find('.sf-range-max');


                    var decimal_places = $this.attr("data-decimal-places");
                    var thousand_seperator = $this.attr("data-thousand-seperator");
                    var decimal_seperator = $this.attr("data-decimal-seperator");

                    var field_format = wNumb({
                        mark: decimal_seperator,
                        decimals: parseFloat(decimal_places),
                        thousand: thousand_seperator
                    });



                    var min_unformatted = parseFloat(smin);
                    var min_formatted = field_format.to(parseFloat(smin));
                    var max_formatted = field_format.to(parseFloat(smax));
                    var max_unformatted = parseFloat(smax);
                    //alert(min_formatted);
                    //alert(max_formatted);
                    //alert(display_value_as);


                    if(display_value_as=="textinput")
                    {
                        $start_val.val(min_formatted);
                        $end_val.val(max_formatted);
                    }
                    else if(display_value_as=="text")
                    {
                        $start_val.html(min_formatted);
                        $end_val.html(max_formatted);
                    }


                    var noUIOptions = {
                        range: {
                            'min': [ parseFloat(min) ],
                            'max': [ parseFloat(max) ]
                        },
                        start: [min_formatted, max_formatted],
                        handles: 2,
                        connect: true,
                        step: parseFloat(step),

                        behaviour: 'extend-tap',
                        format: field_format
                    };



                    if(self.is_rtl==1)
                    {
                        noUIOptions.direction = "rtl";
                    }

                    var slider_object = $(this).find(".meta-slider")[0];

                    if( "undefined" !== typeof( slider_object.noUiSlider ) ) {
                        //destroy if it exists.. this means somehow another instance had initialised it..
                        slider_object.noUiSlider.destroy();
                    }

                    noUiSlider.create(slider_object, noUIOptions);

                    $start_val.off();
                    $start_val.on('change', function(){
                        slider_object.noUiSlider.set([$(this).val(), null]);
                    });

                    $end_val.off();
                    $end_val.on('change', function(){
                        slider_object.noUiSlider.set([null, $(this).val()]);
                    });

                    //$start_val.html(min_formatted);
                    //$end_val.html(max_formatted);

                    slider_object.noUiSlider.off('update');
                    slider_object.noUiSlider.on('update', function( values, handle ) {

                        var slider_start_val  = min_formatted;
                        var slider_end_val  = max_formatted;

                        var value = values[handle];


                        if ( handle ) {
                            max_formatted = value;
                        } else {
                            min_formatted = value;
                        }

                        if(display_value_as=="textinput")
                        {
                            $start_val.val(min_formatted);
                            $end_val.val(max_formatted);
                        }
                        else if(display_value_as=="text")
                        {
                            $start_val.html(min_formatted);
                            $end_val.html(max_formatted);
                        }


                        //i think the function that builds the URL needs to decode the formatted string before adding to the url
                        if((self.auto_update==1)||(self.auto_count_refresh_mode==1))
                        {
                            //only try to update if the values have actually changed
                            if((slider_start_val!=min_formatted)||(slider_end_val!=max_formatted)) {

                                self.inputUpdate(800);
                            }


                        }

                    });

                });

                self.clearTimer(); //ignore any changes recently made by the slider (this was just init shouldn't count as an update event)
            }
        };

        this.init = function(keep_pagination)
        {
            if(typeof(keep_pagination)=="undefined")
            {
                var keep_pagination = false;
            }

            this.initAutoUpdateEvents();
            this.attachActiveClass();

            this.addDatePickers();
            this.addRangeSliders();

            //init combo boxes
            var $combobox = $this.find("select[data-combobox='1']");

            if($combobox.length>0)
            {
                $combobox.each(function(index ){
                    var $thiscb = $( this );
                    var nrm = $thiscb.attr("data-combobox-nrm");

                    if (typeof $thiscb.chosen != "undefined")
                    {
                        var chosenoptions = {
                            search_contains: true
                        };

                        if((typeof(nrm)!=="undefined")&&(nrm)){
                            chosenoptions.no_results_text = nrm;
                        }
                        // safe to use the function
                        //search_contains
                        if(self.is_rtl==1)
                        {
                            $thiscb.addClass("chosen-rtl");
                        }

                        $thiscb.chosen(chosenoptions);
                    }
                    else
                    {

                        var select2options = {};

                        if(self.is_rtl==1)
                        {
                            select2options.dir = "rtl";
                        }
                        if((typeof(nrm)!=="undefined")&&(nrm)){
                            select2options.language= {
                                "noResults": function(){
                                    return nrm;
                                }
                            };
                        }

                        $thiscb.select2(select2options);
                    }

                });


            }

            self.isSubmitting = false;

            //if ajax is enabled init the pagination
            if(self.is_ajax==1)
            {
                self.setupAjaxPagination();
            }

            $this.on("submit", this.submitForm);

            self.initWooCommerceControls(); //woocommerce orderby

            if(keep_pagination==false)
            {
                self.last_submit_query_params = self.getUrlParams(false);
            }
        }

        this.onWindowScroll = function(event)
        {
            if((!self.is_loading_more) && (!self.is_max_paged))
            {
                var window_scroll = $(window).scrollTop();
                var window_scroll_bottom = $(window).scrollTop() + $(window).height();
                var scroll_offset = parseInt(self.infinite_scroll_trigger_amount);

                if(self.$infinite_scroll_container.length==1)
                {
                    var results_scroll_bottom = self.$infinite_scroll_container.offset().top + self.$infinite_scroll_container.height();

                    var offset = (self.$infinite_scroll_container.offset().top + self.$infinite_scroll_container.height()) - window_scroll;

                    if(window_scroll_bottom > results_scroll_bottom + scroll_offset)
                    {
                        self.loadMoreResults();
                    }
                    else
                    {//dont load more

                    }
                }
            }
        }

        this.stripQueryStringAndHashFromPath = function(url) {
            return url.split("?")[0].split("#")[0];
        }

        this.gup = function( name, url ) {
            if (!url) url = location.href
            name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
            var regexS = "[\\?&]"+name+"=([^&#]*)";
            var regex = new RegExp( regexS );
            var results = regex.exec( url );
            return results == null ? null : results[1];
        };


        this.getUrlParams = function(keep_pagination, type, exclude)
        {
            if(typeof(keep_pagination)=="undefined")
            {
                var keep_pagination = true;
            }

            if(typeof(type)=="undefined")
            {
                var type = "";
            }

            var url_params_str = "";

            // get all params from fields
            var url_params_array = process_form.getUrlParams(self);

            var length = Object.keys(url_params_array).length;
            var count = 0;

            if(typeof(exclude)!="undefined") {
                if (url_params_array.hasOwnProperty(exclude)) {
                    length--;
                }
            }

            if(length>0)
            {
                for (var k in url_params_array) {
                    if (url_params_array.hasOwnProperty(k)) {

                        var can_add = true;
                        if(typeof(exclude)!="undefined")
                        {
                            if(k==exclude) {
                                can_add = false;
                            }
                        }

                        if(can_add) {
                            url_params_str += k + "=" + url_params_array[k];

                            if (count < length - 1) {
                                url_params_str += "&";
                            }

                            count++;
                        }
                    }
                }
            }

            var query_params = "";

            //form params as url query string
            var form_params = url_params_str;

            //get url params from the form itself (what the user has selected)
            query_params = self.joinUrlParam(query_params, form_params);

            //add pagination
            if(keep_pagination==true)
            {
                var pageNumber = self.$ajax_results_container.attr("data-paged");

                if(typeof(pageNumber)=="undefined")
                {
                    pageNumber = 1;
                }

                if(pageNumber>1)
                {
                    query_params = self.joinUrlParam(query_params, "sf_paged="+pageNumber);
                }
            }

            //add sfid
            //query_params = self.joinUrlParam(query_params, "sfid="+self.sfid);

            // loop through any extra params (from ext plugins) and add to the url (ie woocommerce `orderby`)
            /*var extra_query_param = "";
             var length = Object.keys(self.extra_query_params).length;
             var count = 0;

             if(length>0)
             {

             for (var k in self.extra_query_params) {
             if (self.extra_query_params.hasOwnProperty(k)) {

             if(self.extra_query_params[k]!="")
             {
             extra_query_param = k+"="+self.extra_query_params[k];
             query_params = self.joinUrlParam(query_params, extra_query_param);
             }
             */
            query_params = self.addQueryParams(query_params, self.extra_query_params.all);

            if(type!="")
            {
                //query_params = self.addQueryParams(query_params, self.extra_query_params[type]);
            }

            return query_params;
        }
        this.addQueryParams = function(query_params, new_params)
        {
            var extra_query_param = "";
            var length = Object.keys(new_params).length;
            var count = 0;

            if(length>0)
            {

                for (var k in new_params) {
                    if (new_params.hasOwnProperty(k)) {

                        if(new_params[k]!="")
                        {
                            extra_query_param = k+"="+new_params[k];
                            query_params = self.joinUrlParam(query_params, extra_query_param);
                        }
                    }
                }
            }

            return query_params;
        }
        this.addUrlParam = function(url, string)
        {
            var add_params = "";

            if(url!="")
            {
                if(url.indexOf("?") != -1)
                {
                    add_params += "&";
                }
                else
                {
                    //url = this.trailingSlashIt(url);
                    add_params += "?";
                }
            }

            if(string!="")
            {

                return url + add_params + string;
            }
            else
            {
                return url;
            }
        };

        this.joinUrlParam = function(params, string)
        {
            var add_params = "";

            if(params!="")
            {
                add_params += "&";
            }

            if(string!="")
            {

                return params + add_params + string;
            }
            else
            {
                return params;
            }
        };

        this.setAjaxResultsURLs = function(query_params)
        {
            if(typeof(self.ajax_results_conf)=="undefined")
            {
                self.ajax_results_conf = new Array();
            }

            self.ajax_results_conf['processing_url'] = "";
            self.ajax_results_conf['results_url'] = "";
            self.ajax_results_conf['data_type'] = "";

            //if(self.ajax_url!="")
            if(self.display_result_method=="shortcode")
            {//then we want to do a request to the ajax endpoint
                self.ajax_results_conf['results_url'] = self.addUrlParam(self.results_url, query_params);

                //add lang code to ajax api request, lang code should already be in there for other requests (ie, supplied in the Results URL)

                if(self.lang_code!="")
                {
                    //so add it
                    query_params = self.joinUrlParam(query_params, "lang="+self.lang_code);
                }

                self.ajax_results_conf['processing_url'] = self.addUrlParam(self.ajax_url, query_params);
                //self.ajax_results_conf['data_type'] = 'json';

            }
            else if(self.display_result_method=="post_type_archive")
            {
                process_form.setTaxArchiveResultsUrl(self, self.results_url);
                var results_url = process_form.getResultsUrl(self, self.results_url);

                self.ajax_results_conf['results_url'] = self.addUrlParam(results_url, query_params);
                self.ajax_results_conf['processing_url'] = self.addUrlParam(results_url, query_params);

            }
            else if(self.display_result_method=="custom_woocommerce_store")
            {
                process_form.setTaxArchiveResultsUrl(self, self.results_url);
                var results_url = process_form.getResultsUrl(self, self.results_url);

                self.ajax_results_conf['results_url'] = self.addUrlParam(results_url, query_params);
                self.ajax_results_conf['processing_url'] = self.addUrlParam(results_url, query_params);

            }
            else
            {//otherwise we want to pull the results directly from the results page
                self.ajax_results_conf['results_url'] = self.addUrlParam(self.results_url, query_params);
                self.ajax_results_conf['processing_url'] = self.addUrlParam(self.ajax_url, query_params);
                //self.ajax_results_conf['data_type'] = 'html';
            }

            self.ajax_results_conf['processing_url'] = self.addQueryParams(self.ajax_results_conf['processing_url'], self.extra_query_params['ajax']);

            self.ajax_results_conf['data_type'] = self.ajax_data_type;
        };



        this.updateLoaderTag = function($object) {

            var $parent;

            if(self.infinite_scroll_result_class!="")
            {
                $parent = self.$infinite_scroll_container.find(self.infinite_scroll_result_class).last().parent();
            }
            else
            {
                $parent = self.$infinite_scroll_container;
            }

            var tagName = $parent.prop("tagName");

            var tagType = 'div';
            if( ( tagName.toLowerCase() == 'ol' ) || ( tagName.toLowerCase() == 'ul' ) ){
                tagType = 'li';
            }

            var $new = $('<'+tagType+' />').html($object.html());
            var attributes = $object.prop("attributes");

            // loop through <select> attributes and apply them on <div>
            $.each(attributes, function() {
                $new.attr(this.name, this.value);
            });

            return $new;

        }


        this.loadMoreResults = function()
        {
            if ( this.is_max_paged === true ) {
                return;
            }
            self.is_loading_more = true;

            //trigger start event
            var event_data = {
                sfid: self.sfid,
                targetSelector: self.ajax_target_attr,
                type: "load_more",
                object: self
            };

            self.triggerEvent("sf:ajaxstart", event_data);
            process_form.setTaxArchiveResultsUrl(self, self.results_url);
            var query_params = self.getUrlParams(true);
            self.last_submit_query_params = self.getUrlParams(false); //grab a copy of hte URL params without pagination already added

            var ajax_processing_url = "";
            var ajax_results_url = "";
            var data_type = "";


            //now add the new pagination
            var next_paged_number = this.current_paged + 1;
            query_params = self.joinUrlParam(query_params, "sf_paged="+next_paged_number);

            self.setAjaxResultsURLs(query_params);
            ajax_processing_url = self.ajax_results_conf['processing_url'];
            ajax_results_url = self.ajax_results_conf['results_url'];
            data_type = self.ajax_results_conf['data_type'];

            //abort any previous ajax requests
            if(self.last_ajax_request)
            {
                self.last_ajax_request.abort();
            }

            if(self.use_scroll_loader==1)
            {
                var $loader = $('<div/>',{
                    'class': 'search-filter-scroll-loading'
                });//.appendTo(self.$ajax_results_container);

                $loader = self.updateLoaderTag($loader);

                self.infiniteScrollAppend($loader);
            }
            self.last_ajax_request = $.get(ajax_processing_url, function(data, status, request)
            {
                self.current_paged++;
                self.last_ajax_request = null;

                // **************
                // TODO - PASTE THIS AND WATCH THE REDIRECT - ONLY HAPPENS WITH WC (CPT AND TAX DOES NOT)
                // https://search-filter.test/product-category/clothing/tshirts/page/3/?sf_paged=3

                //updates the resutls & form html
                self.addResults(data, data_type);

            }, data_type).fail(function(jqXHR, textStatus, errorThrown)
            {
                var data = {};
                data.sfid = self.sfid;
                data.object = self;
                data.targetSelector = self.ajax_target_attr;
                data.ajaxURL = ajax_processing_url;
                data.jqXHR = jqXHR;
                data.textStatus = textStatus;
                data.errorThrown = errorThrown;
                self.triggerEvent("sf:ajaxerror", data);

            }).always(function()
            {
                var data = {};
                data.sfid = self.sfid;
                data.targetSelector = self.ajax_target_attr;
                data.object = self;

                if(self.use_scroll_loader==1)
                {
                    $loader.detach();
                }

                self.is_loading_more = false;

                self.triggerEvent("sf:ajaxfinish", data);
            });

        }
        this.fetchAjaxResults = function()
        {
            //trigger start event
            var event_data = {
                sfid: self.sfid,
                targetSelector: self.ajax_target_attr,
                type: "load_results",
                object: self
            };

            self.triggerEvent("sf:ajaxstart", event_data);

            //refocus any input fields after the form has been updated
            var $last_active_input_text = $this.find('input[type="text"]:focus').not(".sf-datepicker");
            if($last_active_input_text.length==1)
            {
                var last_active_input_text = $last_active_input_text.attr("name");
            }

            $this.addClass("search-filter-disabled");
            process_form.disableInputs(self);

            //fade out results
            self.$ajax_results_container.animate({ opacity: 0.5 }, "fast"); //loading
            self.fadeContentAreas( "out" );

            if(self.ajax_action=="pagination")
            {
                //need to remove active filter from URL

                //query_params = self.last_submit_query_params;

                //now add the new pagination
                var pageNumber = self.$ajax_results_container.attr("data-paged");

                if(typeof(pageNumber)=="undefined")
                {
                    pageNumber = 1;
                }
                process_form.setTaxArchiveResultsUrl(self, self.results_url);
                query_params = self.getUrlParams(false);

                if(pageNumber>1)
                {
                    query_params = self.joinUrlParam(query_params, "sf_paged="+pageNumber);
                }

            }
            else if(self.ajax_action=="submit")
            {
                var query_params = self.getUrlParams(true);
                self.last_submit_query_params = self.getUrlParams(false); //grab a copy of hte URL params without pagination already added
            }

            var ajax_processing_url = "";
            var ajax_results_url = "";
            var data_type = "";

            self.setAjaxResultsURLs(query_params);
            ajax_processing_url = self.ajax_results_conf['processing_url'];
            ajax_results_url = self.ajax_results_conf['results_url'];
            data_type = self.ajax_results_conf['data_type'];


            //abort any previous ajax requests
            if(self.last_ajax_request)
            {
                self.last_ajax_request.abort();
            }
            var ajax_action = self.ajax_action;
            self.last_ajax_request = $.get(ajax_processing_url, function(data, status, request)
            {
                self.last_ajax_request = null;

                //updates the resutls & form html
                self.updateResults(data, data_type);

                // scroll 
                // set the var back to what it was before the ajax request nad the form re-init
                self.ajax_action = ajax_action;
                self.scrollResults( self.ajax_action );

                /* update URL */
                //update url before pagination, because we need to do some checks agains the URL for infinite scroll
                self.updateUrlHistory(ajax_results_url);

                //setup pagination
                self.setupAjaxPagination();

                self.isSubmitting = false;

                /* user def */
                self.initWooCommerceControls(); //woocommerce orderby


            }, data_type).fail(function(jqXHR, textStatus, errorThrown)
            {
                var data = {};
                data.sfid = self.sfid;
                data.targetSelector = self.ajax_target_attr;
                data.object = self;
                data.ajaxURL = ajax_processing_url;
                data.jqXHR = jqXHR;
                data.textStatus = textStatus;
                data.errorThrown = errorThrown;
                self.isSubmitting = false;
                self.triggerEvent("sf:ajaxerror", data);

            }).always(function()
            {
                self.$ajax_results_container.stop(true,true).animate({ opacity: 1}, "fast"); //finished loading
                self.fadeContentAreas( "in" );
                var data = {};
                data.sfid = self.sfid;
                data.targetSelector = self.ajax_target_attr;
                data.object = self;
                $this.removeClass("search-filter-disabled");
                process_form.enableInputs(self);

                //refocus the last active text field
                if(last_active_input_text!="")
                {
                    var $input = [];
                    self.$fields.each(function(){

                        var $active_input = $(this).find("input[name='"+last_active_input_text+"']");
                        if($active_input.length==1)
                        {
                            $input = $active_input;
                        }

                    });
                    if($input.length==1) {

                        $input.focus().val($input.val());
                        self.focusCampo($input[0]);
                    }
                }

                $this.find("input[name='_sf_search']").trigger('focus');
                self.triggerEvent("sf:ajaxfinish",  data );

            });
        };

        this.focusCampo = function(inputField){
            //var inputField = document.getElementById(id);
            if (inputField != null && inputField.value.length != 0){
                if (inputField.createTextRange){
                    var FieldRange = inputField.createTextRange();
                    FieldRange.moveStart('character',inputField.value.length);
                    FieldRange.collapse();
                    FieldRange.select();
                }else if (inputField.selectionStart || inputField.selectionStart == '0') {
                    var elemLen = inputField.value.length;
                    inputField.selectionStart = elemLen;
                    inputField.selectionEnd = elemLen;
                }
                inputField.blur();
                inputField.focus();
            } else{
                if ( inputField ) {
                    inputField.focus();
                }
                
            }
        }

        this.triggerEvent = function(eventname, data)
        {
            var $event_container = $(".searchandfilter[data-sf-form-id='"+self.sfid+"']");
            $event_container.trigger(eventname, [ data ]);
        }

        this.fetchAjaxForm = function()
        {
            //trigger start event
            var event_data = {
                sfid: self.sfid,
                targetSelector: self.ajax_target_attr,
                type: "form",
                object: self
            };

            self.triggerEvent("sf:ajaxformstart", [ event_data ]);

            $this.addClass("search-filter-disabled");
            process_form.disableInputs(self);

            var query_params = self.getUrlParams();

            if(self.lang_code!="")
            {
                //so add it
                query_params = self.joinUrlParam(query_params, "lang="+self.lang_code);
            }

            var ajax_processing_url = self.addUrlParam(self.ajax_form_url, query_params);
            var data_type = "json";


            //abort any previous ajax requests
            /*if(self.last_ajax_request)
             {
             self.last_ajax_request.abort();
             }*/


            //self.last_ajax_request =

            $.get(ajax_processing_url, function(data, status, request)
            {
                //self.last_ajax_request = null;

                //updates the resutls & form html
                self.updateForm(data, data_type);


            }, data_type).fail(function(jqXHR, textStatus, errorThrown)
            {
                var data = {};
                data.sfid = self.sfid;
                data.targetSelector = self.ajax_target_attr;
                data.object = self;
                data.ajaxURL = ajax_processing_url;
                data.jqXHR = jqXHR;
                data.textStatus = textStatus;
                data.errorThrown = errorThrown;
                self.triggerEvent("sf:ajaxerror", [ data ]);

            }).always(function()
            {
                var data = {};
                data.sfid = self.sfid;
                data.targetSelector = self.ajax_target_attr;
                data.object = self;

                $this.removeClass("search-filter-disabled");
                process_form.enableInputs(self);

                self.triggerEvent("sf:ajaxformfinish", [ data ]);
            });
        };

        this.copyListItemsContents = function($list_from, $list_to)
        {
            //copy over child list items
            var li_contents_array = new Array();
            var from_attributes = new Array();

            var $from_fields = $list_from.find("> ul > li");

            $from_fields.each(function(i){

                li_contents_array.push($(this).html());

                var attributes = $(this).prop("attributes");
                from_attributes.push(attributes);

                //var field_name = $(this).attr("data-sf-field-name");
                //var to_field = $list_to.find("> ul > li[data-sf-field-name='"+field_name+"']");

                //self.copyAttributes($(this), $list_to, "data-sf-");

            });

            var li_it = 0;
            var $to_fields = $list_to.find("> ul > li");
            $to_fields.each(function(i){
                $(this).html(li_contents_array[li_it]);

                var $from_field = $($from_fields.get(li_it));

                var $to_field = $(this);
                $to_field.removeAttr("data-sf-taxonomy-archive");
                self.copyAttributes($from_field, $to_field);

                li_it++;
            });

            /*var $from_fields = $list_from.find(" ul > li");
             var $to_fields = $list_to.find(" > li");
             $from_fields.each(function(index, val){
             if($(this).hasAttribute("data-sf-taxonomy-archive"))
             {

             }
             });

             this.copyAttributes($list_from, $list_to);*/
        }

        this.updateFormAttributes = function($list_from, $list_to)
        {
            var from_attributes = $list_from.prop("attributes");
            // loop through <select> attributes and apply them on <div>

            var to_attributes = $list_to.prop("attributes");
            $.each(to_attributes, function() {
                $list_to.removeAttr(this.name);
            });

            $.each(from_attributes, function() {
                $list_to.attr(this.name, this.value);
            });

        }

        this.copyAttributes = function($from, $to, prefix)
        {
            if(typeof(prefix)=="undefined")
            {
                var prefix = "";
            }

            var from_attributes = $from.prop("attributes");

            var to_attributes = $to.prop("attributes");
            $.each(to_attributes, function() {

                if(prefix!="") {
                    if (this.name.indexOf(prefix) == 0) {
                        $to.removeAttr(this.name);
                    }
                }
                else
                {
                    //$to.removeAttr(this.name);
                }
            });

            $.each(from_attributes, function() {
                $to.attr(this.name, this.value);
            });
        }

        this.copyFormAttributes = function($from, $to)
        {
            $to.removeAttr("data-current-taxonomy-archive");
            this.copyAttributes($from, $to);

        }

        this.updateForm = function(data, data_type)
        {
            if(data_type=="json")
            {//then we did a request to the ajax endpoint, so expect an object back

                if(typeof(data['form'])!=="undefined")
                {
                    //remove all events from S&F form
                    $this.off();

                    //refresh the form (auto count)
                    self.copyListItemsContents($(data['form']), $this);

                    //re init S&F class on the form
                    //$this.searchAndFilter();

                    //if ajax is enabled init the pagination

                    this.init(true);

                    if(self.is_ajax==1)
                    {
                        self.setupAjaxPagination();
                    }



                }
            }


        }
        this.addResults = function(data, data_type)
        {
            if(data_type=="json")
            {//then we did a request to the ajax endpoint, so expect an object back
                //grab the results and load in
                //self.$ajax_results_container.append(data['results']);
                self.load_more_html = data['results'];
            }
            else if(data_type=="html")
            {//we are expecting the html of the results page back, so extract the html we need

                var $data_obj = $(data);

                //self.$infinite_scroll_container.append($data_obj.find(self.ajax_target_attr).html());
                self.load_more_html = $data_obj.find(self.ajax_target_attr).html();
            }

            var infinite_scroll_end = false;

            if($("<div>"+self.load_more_html+"</div>").find("[data-search-filter-action='infinite-scroll-end']").length>0)
            {
                infinite_scroll_end = true;
            }

            //if there is another selector for infinite scroll, find the contents of that instead
            if(self.infinite_scroll_container!="")
            {
                self.load_more_html = $("<div>"+self.load_more_html+"</div>").find(self.infinite_scroll_container).html();
            }
            if(self.infinite_scroll_result_class!="")
            {
                var $result_items = $("<div>"+self.load_more_html+"</div>").find(self.infinite_scroll_result_class);
                var $result_items_container = $('<div/>', {});
                $result_items_container.append($result_items);

                self.load_more_html = $result_items_container.html();
            }

            if(infinite_scroll_end)
            {//we found a data attribute signalling the last page so finish here

                self.is_max_paged = true;
                self.last_load_more_html = self.load_more_html;

                self.infiniteScrollAppend(self.load_more_html);

            }
            else if(self.last_load_more_html!==self.load_more_html)
            {
                //check to make sure the new html fetched is different
                self.last_load_more_html = self.load_more_html;
                self.infiniteScrollAppend(self.load_more_html);

            }
            else
            {//we received the same message again so don't add, and tell S&F that we're at the end..
                self.is_max_paged = true;
            }
        }


        this.infiniteScrollAppend = function($object)
        {
            if(self.infinite_scroll_result_class!="")
            {
                self.$infinite_scroll_container.find(self.infinite_scroll_result_class).last().after($object);
            }
            else
            {
               self.$infinite_scroll_container.append($object);
            }
        }


        this.updateResults = function(data, data_type)
        {
            if(data_type=="json")
            {//then we did a request to the ajax endpoint, so expect an object back
                //grab the results and load in
                this.results_html = data['results'];

                if ( this.replace_results ) {
                    self.$ajax_results_container.html(this.results_html);
                }

                if(typeof(data['form'])!=="undefined")
                {
                    //remove all events from S&F form
                    $this.off();

                    //remove pagination
                    self.removeAjaxPagination();

                    //refresh the form (auto count)
                    self.copyListItemsContents($(data['form']), $this);

                    //update attributes on form
                    self.copyFormAttributes($(data['form']), $this);

                    //re init S&F class on the form
                    $this.searchAndFilter({'isInit': false});
                }
                else
                {
                    //$this.find("input").removeAttr("disabled");
                }
            }
            else if(data_type=="html") {//we are expecting the html of the results page back, so extract the html we need

                var $data_obj = $(data);

                this.results_html = $data_obj.find( this.ajax_target_attr ).html();

                if ( this.replace_results ) {
                    self.$ajax_results_container.html(this.results_html);
                }

                self.updateContentAreas( $data_obj );

                if (self.$ajax_results_container.find(".searchandfilter").length > 0)
                {//then there are search form(s) inside the results container, so re-init them

                    self.$ajax_results_container.find(".searchandfilter").searchAndFilter();
                }

                //if the current search form is not inside the results container, then proceed as normal and update the form
                if(self.$ajax_results_container.find(".searchandfilter[data-sf-form-id='" + self.sfid + "']").length==0) {

                    var $new_search_form = $data_obj.find(".searchandfilter[data-sf-form-id='" + self.sfid + "']");

                    if ($new_search_form.length == 1) {//then replace the search form with the new one

                        //remove all events from S&F form
                        $this.off();

                        //remove pagination
                        self.removeAjaxPagination();

                        //refresh the form (auto count)
                        self.copyListItemsContents($new_search_form, $this);

                        //update attributes on form
                        self.copyFormAttributes($new_search_form, $this);

                        //re init S&F class on the form
                        $this.searchAndFilter({'isInit': false});

                    }
                    else {

                        //$this.find("input").removeAttr("disabled");
                    }
                }
            }

            self.is_max_paged = false; //for infinite scroll
            self.current_paged = 1; //for infinite scroll
            self.setInfiniteScrollContainer();

        }

        this.updateContentAreas = function( $html_data ) {
            
            // add additional content areas
            if ( this.ajax_update_sections && this.ajax_update_sections.length ) {
                for (index = 0; index < this.ajax_update_sections.length; ++index) {
                    var selector = this.ajax_update_sections[index];
                    $( selector ).html( $html_data.find( selector ).html() );
                }
            }
        }
        this.fadeContentAreas = function( direction ) {
            
            var opacity = 0.5;
            if ( direction === "in" ) {
                opacity = 1;
            }

            if ( this.ajax_update_sections && this.ajax_update_sections.length ) {
                for (index = 0; index < this.ajax_update_sections.length; ++index) {
                    var selector = this.ajax_update_sections[index];
                    $( selector ).stop(true,true).animate( { opacity: opacity}, "fast" );
                }
            }
           
            
        }

        this.removeWooCommerceControls = function(){
            var $woo_orderby = $('.woocommerce-ordering .orderby');
            var $woo_orderby_form = $('.woocommerce-ordering');

            $woo_orderby_form.off();
            $woo_orderby.off();
        };

        this.addQueryParam = function(name, value, url_type){

            if(typeof(url_type)=="undefined")
            {
                var url_type = "all";
            }
            self.extra_query_params[url_type][name] = value;

        };

        this.initWooCommerceControls = function(){

            self.removeWooCommerceControls();

            var $woo_orderby = $('.woocommerce-ordering .orderby');
            var $woo_orderby_form = $('.woocommerce-ordering');

            var order_val = "";
            if($woo_orderby.length>0)
            {
                order_val = $woo_orderby.val();
            }
            else
            {
                order_val = self.getQueryParamFromURL("orderby", window.location.href);
            }

            if(order_val=="menu_order")
            {
                order_val = "";
            }

            if((order_val!="")&&(!!order_val))
            {
                self.extra_query_params.all.orderby = order_val;
            }


            $woo_orderby_form.on('submit', function(e)
            {
                e.preventDefault();
                //var form = e.target;
                return false;
            });

            $woo_orderby.on("change", function(e)
            {
                e.preventDefault();

                var val = $(this).val();
                if(val=="menu_order")
                {
                    val = "";
                }

                self.extra_query_params.all.orderby = val;

                $this.trigger("submit")

                return false;
            });

        }

        this.scrollResults = function()
        {
            if((self.scroll_on_action==self.ajax_action)||(self.scroll_on_action=="all"))
            {
                self.scrollToPos(); //scroll the window if it has been set
                //self.ajax_action = "";
            }
        }

        this.updateUrlHistory = function(ajax_results_url)
        {
            var use_history_api = 0;
            if (window.history && window.history.pushState)
            {
                use_history_api = $this.attr("data-use-history-api");
            }

            if((self.update_ajax_url==1)&&(use_history_api==1))
            {
                //now check if the browser supports history state push :)
                if (window.history && window.history.pushState)
                {
                    history.pushState(null, null, ajax_results_url);
                }
            }
        }
        this.removeAjaxPagination = function()
        {
            if(typeof(self.ajax_links_selector)!="undefined")
            {
                var $ajax_links_object = jQuery(self.ajax_links_selector);

                if($ajax_links_object.length>0)
                {
                    $ajax_links_object.off();
                }
            }
        }

        this.getBaseUrl = function( url ) {
            //now see if we are on the URL we think...
            var url_parts = url.split("?");
            var url_base = "";

            if(url_parts.length>0)
            {
                url_base = url_parts[0];
            }
            else {
                url_base = url;
            }
            return url_base;
        }
        this.canFetchAjaxResults = function(fetch_type)
        {
            if(typeof(fetch_type)=="undefined")
            {
                var fetch_type = "";
            }

            var fetch_ajax_results = false;

            if(self.is_ajax==1)
            {//then we will ajax submit the form

                //and if we can find the results container
                if(self.$ajax_results_container.length==1)
                {
                    fetch_ajax_results = true;
                }

                var results_url = self.results_url;  //
                var results_url_encoded = '';  //
                var current_url = window.location.href;

                //ignore # and everything after
                var hash_pos = window.location.href.indexOf('#');
                if(hash_pos!==-1){
                    current_url = window.location.href.substr(0, window.location.href.indexOf('#'));
                }

                if( ( ( self.display_result_method=="custom_woocommerce_store" ) || ( self.display_result_method=="post_type_archive" ) ) && ( self.enable_taxonomy_archives == 1 ) )
                {
                    if( self.current_taxonomy_archive !=="" )
                    {
                        fetch_ajax_results = true;
                        return fetch_ajax_results;
                    }

                    /*var results_url = process_form.getResultsUrl(self, self.results_url);
                     var active_tax = process_form.getActiveTax();
                     var query_params = self.getUrlParams(true, '', active_tax);*/
                }




                //now see if we are on the URL we think...
                var url_base = this.getBaseUrl( current_url );
                //var results_url_base = this.getBaseUrl( current_url );

                var lang = self.getQueryParamFromURL("lang", window.location.href);
                if((typeof(lang)!=="undefined")&&(lang!==null))
                {
                    url_base = self.addUrlParam(url_base, "lang="+lang);
                }

                var sfid = self.getQueryParamFromURL("sfid", window.location.href);

                //if sfid is a number
                if(Number(parseFloat(sfid)) == sfid)
                {
                    url_base = self.addUrlParam(url_base, "sfid="+sfid);
                }

                //if any of the 3 conditions are true, then its good to go
                // - 1 | if the url base == results_url
                // - 2 | if url base+ "/"  == results_url - in case of user error in the results URL
                // - 3 | if the results URL has url params, and the current url starts with the results URL 

                //trim any trailing slash for easier comparison:
                url_base = url_base.replace(/\/$/, '');
                results_url = results_url.replace(/\/$/, '');
                results_url_encoded = encodeURI(results_url);
                

                var current_url_contains_results_url = -1;
                if((url_base==results_url)||(url_base.toLowerCase()==results_url_encoded.toLowerCase())  ){
                    current_url_contains_results_url = 1;
                } else {
                    if ( results_url.indexOf( '?' ) !== -1 && current_url.lastIndexOf(results_url, 0) === 0 ) {
                        current_url_contains_results_url = 1;
                    }
                }

                if(self.only_results_ajax==1)
                {//if a user has chosen to only allow ajax on results pages (default behaviour)

                    if( current_url_contains_results_url > -1)
                    {//this means the current URL contains the results url, which means we can do ajax
                        fetch_ajax_results = true;
                    }
                    else
                    {
                        fetch_ajax_results = false;
                    }
                }
                else
                {
                    if(fetch_type=="pagination")
                    {
                        if( current_url_contains_results_url > -1)
                        {//this means the current URL contains the results url, which means we can do ajax

                        }
                        else
                        {
                            //don't ajax pagination when not on a S&F page
                            fetch_ajax_results = false;
                        }


                    }

                }
            }

            return fetch_ajax_results;
        }

        this.setupAjaxPagination = function()
        {
            //infinite scroll
            if(this.pagination_type==="infinite_scroll")
            {
                var infinite_scroll_end = false;
                if(self.$ajax_results_container.find("[data-search-filter-action='infinite-scroll-end']").length>0)
                {
                    infinite_scroll_end = true;
                    self.is_max_paged = true;
                }

                if(parseInt(this.instance_number)===1) {
                    $(window).off("scroll", self.onWindowScroll);

                    if (self.canFetchAjaxResults("pagination")) {
                        $(window).on("scroll", self.onWindowScroll);
                    }
                }
            }
            else if(typeof(self.ajax_links_selector)=="undefined") {
                return;
            }
            else {
                $(document).off('click', self.ajax_links_selector);
                $(document).off(self.ajax_links_selector);
                $(self.ajax_links_selector).off();

                $(document).on('click', self.ajax_links_selector, function(e){

                    if(self.canFetchAjaxResults("pagination"))
                    {
                        e.preventDefault();

                        var link = jQuery(this).attr('href');
                        self.ajax_action = "pagination";

                        var pageNumber = self.getPagedFromURL(link);

                        self.$ajax_results_container.attr("data-paged", pageNumber);

                        self.fetchAjaxResults();

                        return false;
                    }
                });
            }
        };

        this.getPagedFromURL = function(URL){

            var pagedVal = 1;
            //first test to see if we have "/page/4/" in the URL
            var tpVal = self.getQueryParamFromURL("sf_paged", URL);
            if((typeof(tpVal)=="string")||(typeof(tpVal)=="number"))
            {
                pagedVal = tpVal;
            }

            return pagedVal;
        };

        this.getQueryParamFromURL = function(name, URL){

            var qstring = "?"+URL.split('?')[1];
            if(typeof(qstring)!="undefined")
            {
                var val = decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(qstring)||[,""])[1].replace(/\+/g, '%20'))||null;
                return val;
            }
            return "";
        };



        this.formUpdated = function(e){

            //e.preventDefault();
            if(self.auto_update==1) {
                self.submitForm();
            }
            else if((self.auto_update==0)&&(self.auto_count_refresh_mode==1))
            {
                self.formUpdatedFetchAjax();
            }

            return false;
        };

        this.formUpdatedFetchAjax = function(){

            //loop through all the fields and build the URL
            self.fetchAjaxForm();


            return false;
        };

        //make any corrections/updates to fields before the submit completes
        this.setFields = function(e){

            //if(self.is_ajax==0) {

                //sometimes the form is submitted without the slider yet having updated, and as we get our values from
                //the slider and not inputs, we need to check it if needs to be set
                //only occurs if ajax is off, and autosubmit on
                self.$fields.each(function() {

                    var $field = $(this);

                    var range_display_values = $field.find('.sf-meta-range-slider').attr("data-display-values-as");//data-display-values-as="text"

                    if(range_display_values==="textinput") {

                        if($field.find(".meta-slider").length>0){

                        }
                        $field.find(".meta-slider").each(function (index) {

                            var slider_object = $(this)[0];
                            var $slider_el = $(this).closest(".sf-meta-range-slider");
                            //var minVal = $slider_el.attr("data-min");
                            //var maxVal = $slider_el.attr("data-max");
                            var minVal = $slider_el.find(".sf-range-min").val();
                            var maxVal = $slider_el.find(".sf-range-max").val();
                            slider_object.noUiSlider.set([minVal, maxVal]);

                        });
                    }
                });
            //}

        }

        //submit
        this.submitForm = function(e){

            //loop through all the fields and build the URL
            if(self.isSubmitting == true) {
                return false;
            }

            self.setFields();
            self.clearTimer();

            self.isSubmitting = true;

            process_form.setTaxArchiveResultsUrl(self, self.results_url);

            self.$ajax_results_container.attr("data-paged", 1); //init paged

            if(self.canFetchAjaxResults())
            {//then we will ajax submit the form

                self.ajax_action = "submit"; //so we know it wasn't pagination
                self.fetchAjaxResults();
            }
            else
            {//then we will simply redirect to the Results URL

                var results_url = process_form.getResultsUrl(self, self.results_url);
                var query_params = self.getUrlParams(true, '');
                results_url = self.addUrlParam(results_url, query_params);

                window.location.href = results_url;
            }

            return false;
        };
        this.resetForm = function(submit_form)
        {
            //unset all fields
            self.$fields.each(function(){

                var $field = $(this);
				
				$field.removeAttr("data-sf-taxonomy-archive");
				
                //standard field types
                $field.find("select:not([multiple='multiple']) > option:first-child").prop("selected", true);
                $field.find("select[multiple='multiple'] > option").prop("selected", false);
                $field.find("input[type='checkbox']").prop("checked", false);
                $field.find("> ul > li:first-child input[type='radio']").prop("checked", true);
                $field.find("input[type='text']").val("");
                $field.find(".sf-option-active").removeClass("sf-option-active");
                $field.find("> ul > li:first-child input[type='radio']").parent().addClass("sf-option-active"); //re add active class to first "default" option

                //number range - 2 number input fields
                $field.find("input[type='number']").each(function(index){

                    var $thisInput = $(this);

                    if($thisInput.parent().parent().hasClass("sf-meta-range")) {

                        if(index==0) {
                            $thisInput.val($thisInput.attr("min"));
                        }
                        else if(index==1) {
                            $thisInput.val($thisInput.attr("max"));
                        }
                    }

                });

                //meta / numbers with 2 inputs (from / to fields) - second input must be reset to max value
                var $meta_select_from_to = $field.find(".sf-meta-range-select-fromto");

                if($meta_select_from_to.length>0) {

                    var start_min = $meta_select_from_to.attr("data-min");
                    var start_max = $meta_select_from_to.attr("data-max");

                    $meta_select_from_to.find("select").each(function(index){

                        var $thisInput = $(this);

                        if(index==0) {

                            $thisInput.val(start_min);
                        }
                        else if(index==1) {
                            $thisInput.val(start_max);
                        }

                    });
                }

                var $meta_radio_from_to = $field.find(".sf-meta-range-radio-fromto");

                if($meta_radio_from_to.length>0)
                {
                    var start_min = $meta_radio_from_to.attr("data-min");
                    var start_max = $meta_radio_from_to.attr("data-max");

                    var $radio_groups = $meta_radio_from_to.find('.sf-input-range-radio');

                    $radio_groups.each(function(index){


                        var $radios = $(this).find(".sf-input-radio");
                        $radios.prop("checked", false);

                        if(index==0)
                        {
                            $radios.filter('[value="'+start_min+'"]').prop("checked", true);
                        }
                        else if(index==1)
                        {
                            $radios.filter('[value="'+start_max+'"]').prop("checked", true);
                        }

                    });

                }

                //number slider - noUiSlider
                $field.find(".meta-slider").each(function(index){

                    var slider_object = $(this)[0];
                    /*var slider_object = $container.find(".meta-slider")[0];
                     var slider_val = slider_object.noUiSlider.get();*/

                    var $slider_el = $(this).closest(".sf-meta-range-slider");
                    var minVal = $slider_el.attr("data-min");
                    var maxVal = $slider_el.attr("data-max");
                    slider_object.noUiSlider.set([minVal, maxVal]);

                });

                //need to see if any are combobox and act accordingly
                var $combobox = $field.find("select[data-combobox='1']");
                if($combobox.length>0)
                {
                    if (typeof $combobox.chosen != "undefined")
                    {
                        $combobox.trigger("chosen:updated"); //for chosen only
                    }
                    else
                    {
                        $combobox.val('');
                        $combobox.trigger('change.select2');
                    }
                }


            });
            self.clearTimer();



            if(submit_form=="always")
            {
                self.submitForm();
            }
            else if(submit_form=="never")
            {
                if(this.auto_count_refresh_mode==1)
                {
                    self.formUpdatedFetchAjax();
                }
            }
            else if(submit_form=="auto")
            {
                if(this.auto_update==true)
                {
                    self.submitForm();
                }
                else
                {
                    if(this.auto_count_refresh_mode==1)
                    {
                        self.formUpdatedFetchAjax();
                    }
                }
            }

        };

        this.init();

        var event_data = {};
        event_data.sfid = self.sfid;
        event_data.targetSelector = self.ajax_target_attr;
        event_data.object = this;
        if(opts.isInit)
        {
            self.triggerEvent("sf:init", event_data);
        }

    });
};

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
//# sourceMappingURL=data:application/json;charset:utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9wdWJsaWMvYXNzZXRzL2pzL2luY2x1ZGVzL3BsdWdpbi5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6ImdlbmVyYXRlZC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzQ29udGVudCI6WyJcclxudmFyICQgXHRcdFx0XHQ9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbCk7XHJcbnZhciBzdGF0ZSBcdFx0XHQ9IHJlcXVpcmUoJy4vc3RhdGUnKTtcclxudmFyIHByb2Nlc3NfZm9ybSBcdD0gcmVxdWlyZSgnLi9wcm9jZXNzX2Zvcm0nKTtcclxudmFyIG5vVWlTbGlkZXJcdFx0PSByZXF1aXJlKCdub3Vpc2xpZGVyJyk7XHJcbi8vdmFyIGNvb2tpZXMgICAgICAgICA9IHJlcXVpcmUoJ2pzLWNvb2tpZScpO1xyXG52YXIgdGhpcmRQYXJ0eSAgICAgID0gcmVxdWlyZSgnLi90aGlyZHBhcnR5Jyk7XHJcblxyXG53aW5kb3cuc2VhcmNoQW5kRmlsdGVyID0ge1xyXG4gICAgZXh0ZW5zaW9uczogW10sXHJcbiAgICByZWdpc3RlckV4dGVuc2lvbjogZnVuY3Rpb24oIGV4dGVuc2lvbk5hbWUgKSB7XHJcbiAgICAgICAgdGhpcy5leHRlbnNpb25zLnB1c2goIGV4dGVuc2lvbk5hbWUgKTtcclxuICAgIH1cclxufTtcclxuXHJcbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24ob3B0aW9ucylcclxue1xyXG4gICAgdmFyIGRlZmF1bHRzID0ge1xyXG4gICAgICAgIHN0YXJ0T3BlbmVkOiBmYWxzZSxcclxuICAgICAgICBpc0luaXQ6IHRydWUsXHJcbiAgICAgICAgYWN0aW9uOiBcIlwiXHJcbiAgICB9O1xyXG5cclxuICAgIHZhciBvcHRzID0galF1ZXJ5LmV4dGVuZChkZWZhdWx0cywgb3B0aW9ucyk7XHJcbiAgICBcclxuICAgIHRoaXJkUGFydHkuaW5pdCgpO1xyXG4gICAgXHJcbiAgICAvL2xvb3AgdGhyb3VnaCBlYWNoIGl0ZW0gbWF0Y2hlZFxyXG4gICAgdGhpcy5lYWNoKGZ1bmN0aW9uKClcclxuICAgIHtcclxuXHJcbiAgICAgICAgdmFyICR0aGlzID0gJCh0aGlzKTtcclxuICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XHJcbiAgICAgICAgdGhpcy5zZmlkID0gJHRoaXMuYXR0cihcImRhdGEtc2YtZm9ybS1pZFwiKTtcclxuXHJcbiAgICAgICAgc3RhdGUuYWRkU2VhcmNoRm9ybSh0aGlzLnNmaWQsIHRoaXMpO1xyXG5cclxuICAgICAgICB0aGlzLiRmaWVsZHMgPSAkdGhpcy5maW5kKFwiPiB1bCA+IGxpXCIpOyAvL2EgcmVmZXJlbmNlIHRvIGVhY2ggZmllbGRzIHBhcmVudCBMSVxyXG5cclxuICAgICAgICB0aGlzLmVuYWJsZV90YXhvbm9teV9hcmNoaXZlcyA9ICR0aGlzLmF0dHIoJ2RhdGEtdGF4b25vbXktYXJjaGl2ZXMnKTtcclxuICAgICAgICB0aGlzLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSA9ICR0aGlzLmF0dHIoJ2RhdGEtY3VycmVudC10YXhvbm9teS1hcmNoaXZlJyk7XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmVuYWJsZV90YXhvbm9teV9hcmNoaXZlcyk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmVuYWJsZV90YXhvbm9teV9hcmNoaXZlcyA9IFwiMFwiO1xyXG4gICAgICAgIH1cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5jdXJyZW50X3RheG9ub215X2FyY2hpdmUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5jdXJyZW50X3RheG9ub215X2FyY2hpdmUgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcHJvY2Vzc19mb3JtLmluaXQoc2VsZi5lbmFibGVfdGF4b25vbXlfYXJjaGl2ZXMsIHNlbGYuY3VycmVudF90YXhvbm9teV9hcmNoaXZlKTtcclxuICAgICAgICAvL3Byb2Nlc3NfZm9ybS5zZXRUYXhBcmNoaXZlUmVzdWx0c1VybChzZWxmKTtcclxuICAgICAgICBwcm9jZXNzX2Zvcm0uZW5hYmxlSW5wdXRzKHNlbGYpO1xyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5leHRyYV9xdWVyeV9wYXJhbXMpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5leHRyYV9xdWVyeV9wYXJhbXMgPSB7YWxsOiB7fSwgcmVzdWx0czoge30sIGFqYXg6IHt9fTtcclxuICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICB0aGlzLnRlbXBsYXRlX2lzX2xvYWRlZCA9ICR0aGlzLmF0dHIoXCJkYXRhLXRlbXBsYXRlLWxvYWRlZFwiKTtcclxuICAgICAgICB0aGlzLmlzX2FqYXggPSAkdGhpcy5hdHRyKFwiZGF0YS1hamF4XCIpO1xyXG4gICAgICAgIHRoaXMuaW5zdGFuY2VfbnVtYmVyID0gJHRoaXMuYXR0cignZGF0YS1pbnN0YW5jZS1jb3VudCcpO1xyXG4gICAgICAgIHRoaXMuJGFqYXhfcmVzdWx0c19jb250YWluZXIgPSBqUXVlcnkoJHRoaXMuYXR0cihcImRhdGEtYWpheC10YXJnZXRcIikpO1xyXG5cclxuICAgICAgICB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zID0gJHRoaXMuYXR0cihcImRhdGEtYWpheC11cGRhdGUtc2VjdGlvbnNcIikgPyBKU09OLnBhcnNlKCAkdGhpcy5hdHRyKFwiZGF0YS1hamF4LXVwZGF0ZS1zZWN0aW9uc1wiKSApIDogW107XHJcbiAgICAgICAgdGhpcy5yZXBsYWNlX3Jlc3VsdHMgPSAkdGhpcy5hdHRyKFwiZGF0YS1yZXBsYWNlLXJlc3VsdHNcIikgPT09IFwiMFwiID8gZmFsc2UgOiB0cnVlO1xyXG4gICAgICAgIFxyXG4gICAgICAgIHRoaXMucmVzdWx0c191cmwgPSAkdGhpcy5hdHRyKFwiZGF0YS1yZXN1bHRzLXVybFwiKTtcclxuICAgICAgICB0aGlzLmRlYnVnX21vZGUgPSAkdGhpcy5hdHRyKFwiZGF0YS1kZWJ1Zy1tb2RlXCIpO1xyXG4gICAgICAgIHRoaXMudXBkYXRlX2FqYXhfdXJsID0gJHRoaXMuYXR0cihcImRhdGEtdXBkYXRlLWFqYXgtdXJsXCIpO1xyXG4gICAgICAgIHRoaXMucGFnaW5hdGlvbl90eXBlID0gJHRoaXMuYXR0cihcImRhdGEtYWpheC1wYWdpbmF0aW9uLXR5cGVcIik7XHJcbiAgICAgICAgdGhpcy5hdXRvX2NvdW50ID0gJHRoaXMuYXR0cihcImRhdGEtYXV0by1jb3VudFwiKTtcclxuICAgICAgICB0aGlzLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlID0gJHRoaXMuYXR0cihcImRhdGEtYXV0by1jb3VudC1yZWZyZXNoLW1vZGVcIik7XHJcbiAgICAgICAgdGhpcy5vbmx5X3Jlc3VsdHNfYWpheCA9ICR0aGlzLmF0dHIoXCJkYXRhLW9ubHktcmVzdWx0cy1hamF4XCIpOyAvL2lmIHdlIGFyZSBub3Qgb24gdGhlIHJlc3VsdHMgcGFnZSwgcmVkaXJlY3QgcmF0aGVyIHRoYW4gdHJ5IHRvIGxvYWQgdmlhIGFqYXhcclxuICAgICAgICB0aGlzLnNjcm9sbF90b19wb3MgPSAkdGhpcy5hdHRyKFwiZGF0YS1zY3JvbGwtdG8tcG9zXCIpO1xyXG4gICAgICAgIHRoaXMuY3VzdG9tX3Njcm9sbF90byA9ICR0aGlzLmF0dHIoXCJkYXRhLWN1c3RvbS1zY3JvbGwtdG9cIik7XHJcbiAgICAgICAgdGhpcy5zY3JvbGxfb25fYWN0aW9uID0gJHRoaXMuYXR0cihcImRhdGEtc2Nyb2xsLW9uLWFjdGlvblwiKTtcclxuICAgICAgICB0aGlzLmxhbmdfY29kZSA9ICR0aGlzLmF0dHIoXCJkYXRhLWxhbmctY29kZVwiKTtcclxuICAgICAgICB0aGlzLmFqYXhfdXJsID0gJHRoaXMuYXR0cignZGF0YS1hamF4LXVybCcpO1xyXG4gICAgICAgIHRoaXMuYWpheF9mb3JtX3VybCA9ICR0aGlzLmF0dHIoJ2RhdGEtYWpheC1mb3JtLXVybCcpO1xyXG4gICAgICAgIHRoaXMuaXNfcnRsID0gJHRoaXMuYXR0cignZGF0YS1pcy1ydGwnKTtcclxuXHJcbiAgICAgICAgdGhpcy5kaXNwbGF5X3Jlc3VsdF9tZXRob2QgPSAkdGhpcy5hdHRyKCdkYXRhLWRpc3BsYXktcmVzdWx0LW1ldGhvZCcpO1xyXG4gICAgICAgIHRoaXMubWFpbnRhaW5fc3RhdGUgPSAkdGhpcy5hdHRyKCdkYXRhLW1haW50YWluLXN0YXRlJyk7XHJcbiAgICAgICAgdGhpcy5hamF4X2FjdGlvbiA9IFwiXCI7XHJcbiAgICAgICAgdGhpcy5sYXN0X3N1Ym1pdF9xdWVyeV9wYXJhbXMgPSBcIlwiO1xyXG5cclxuICAgICAgICB0aGlzLmN1cnJlbnRfcGFnZWQgPSBwYXJzZUludCgkdGhpcy5hdHRyKCdkYXRhLWluaXQtcGFnZWQnKSk7XHJcbiAgICAgICAgdGhpcy5sYXN0X2xvYWRfbW9yZV9odG1sID0gXCJcIjtcclxuICAgICAgICB0aGlzLmxvYWRfbW9yZV9odG1sID0gXCJcIjtcclxuICAgICAgICB0aGlzLmFqYXhfZGF0YV90eXBlID0gJHRoaXMuYXR0cignZGF0YS1hamF4LWRhdGEtdHlwZScpO1xyXG4gICAgICAgIHRoaXMuYWpheF90YXJnZXRfYXR0ciA9ICR0aGlzLmF0dHIoXCJkYXRhLWFqYXgtdGFyZ2V0XCIpO1xyXG4gICAgICAgIHRoaXMudXNlX2hpc3RvcnlfYXBpID0gJHRoaXMuYXR0cihcImRhdGEtdXNlLWhpc3RvcnktYXBpXCIpO1xyXG4gICAgICAgIHRoaXMuaXNfc3VibWl0dGluZyA9IGZhbHNlO1xyXG5cclxuICAgICAgICB0aGlzLmxhc3RfYWpheF9yZXF1ZXN0ID0gbnVsbDtcclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMucmVzdWx0c19odG1sKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMucmVzdWx0c19odG1sID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLnVzZV9oaXN0b3J5X2FwaSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLnVzZV9oaXN0b3J5X2FwaSA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5wYWdpbmF0aW9uX3R5cGUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5wYWdpbmF0aW9uX3R5cGUgPSBcIm5vcm1hbFwiO1xyXG4gICAgICAgIH1cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5jdXJyZW50X3BhZ2VkKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuY3VycmVudF9wYWdlZCA9IDE7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5hamF4X3RhcmdldF9hdHRyKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuYWpheF90YXJnZXRfYXR0ciA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5hamF4X3VybCk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmFqYXhfdXJsID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmFqYXhfZm9ybV91cmwpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5hamF4X2Zvcm1fdXJsID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLnJlc3VsdHNfdXJsKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMucmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuc2Nyb2xsX3RvX3Bvcyk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLnNjcm9sbF90b19wb3MgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuc2Nyb2xsX29uX2FjdGlvbik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLnNjcm9sbF9vbl9hY3Rpb24gPSBcIlwiO1xyXG4gICAgICAgIH1cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5jdXN0b21fc2Nyb2xsX3RvKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuY3VzdG9tX3Njcm9sbF90byA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuJGN1c3RvbV9zY3JvbGxfdG8gPSBqUXVlcnkodGhpcy5jdXN0b21fc2Nyb2xsX3RvKTtcclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMudXBkYXRlX2FqYXhfdXJsKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMudXBkYXRlX2FqYXhfdXJsID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmRlYnVnX21vZGUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5kZWJ1Z19tb2RlID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmFqYXhfdGFyZ2V0X29iamVjdCk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmFqYXhfdGFyZ2V0X29iamVjdCA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy50ZW1wbGF0ZV9pc19sb2FkZWQpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy50ZW1wbGF0ZV9pc19sb2FkZWQgPSBcIjBcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuYXV0b19jb3VudF9yZWZyZXNoX21vZGUgPSBcIjBcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuYWpheF9saW5rc19zZWxlY3RvciA9ICR0aGlzLmF0dHIoXCJkYXRhLWFqYXgtbGlua3Mtc2VsZWN0b3JcIik7XHJcblxyXG5cclxuICAgICAgICB0aGlzLmF1dG9fdXBkYXRlID0gJHRoaXMuYXR0cihcImRhdGEtYXV0by11cGRhdGVcIik7XHJcbiAgICAgICAgdGhpcy5pbnB1dFRpbWVyID0gMDtcclxuXHJcbiAgICAgICAgdGhpcy5zZXRJbmZpbml0ZVNjcm9sbENvbnRhaW5lciA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIC8vIFdoZW4gd2UgbmF2aWdhdGUgYXdheSBmcm9tIHNlYXJjaCByZXN1bHRzLCBhbmQgdGhlbiBwcmVzcyBiYWNrLFxyXG4gICAgICAgICAgICAvLyBpc19tYXhfcGFnZWQgaXMgcmV0YWluZWQsIHNvIHdlIG9ubHkgd2FudCB0byBzZXQgaXQgdG8gZmFsc2UgaWZcclxuICAgICAgICAgICAgLy8gd2UgYXJlIGluaXRhbGl6aW5nIHRoZSByZXN1bHRzIHBhZ2UgdGhlIGZpcnN0IHRpbWUgLSBzbyBqdXN0IFxyXG4gICAgICAgICAgICAvLyBjaGVjayBpZiB0aGlzIHZhciBpcyB1bmRlZmluZWQgKGFzIGl0IHNob3VsZCBiZSBvbiBmaXJzdCB1c2UpO1xyXG4gICAgICAgICAgICBpZiAoIHR5cGVvZiAoIHRoaXMuaXNfbWF4X3BhZ2VkICkgPT09ICd1bmRlZmluZWQnICkge1xyXG4gICAgICAgICAgICAgICAgdGhpcy5pc19tYXhfcGFnZWQgPSBmYWxzZTsgLy9mb3IgbG9hZCBtb3JlIG9ubHksIG9uY2Ugd2UgZGV0ZWN0IHdlJ3JlIGF0IHRoZSBlbmQgc2V0IHRoaXMgdG8gdHJ1ZVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB0aGlzLnVzZV9zY3JvbGxfbG9hZGVyID0gJHRoaXMuYXR0cignZGF0YS1zaG93LXNjcm9sbC1sb2FkZXInKTtcclxuICAgICAgICAgICAgdGhpcy5pbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyID0gJHRoaXMuYXR0cignZGF0YS1pbmZpbml0ZS1zY3JvbGwtY29udGFpbmVyJyk7XHJcbiAgICAgICAgICAgIHRoaXMuaW5maW5pdGVfc2Nyb2xsX3RyaWdnZXJfYW1vdW50ID0gJHRoaXMuYXR0cignZGF0YS1pbmZpbml0ZS1zY3JvbGwtdHJpZ2dlcicpO1xyXG4gICAgICAgICAgICB0aGlzLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MgPSAkdGhpcy5hdHRyKCdkYXRhLWluZmluaXRlLXNjcm9sbC1yZXN1bHQtY2xhc3MnKTtcclxuICAgICAgICAgICAgdGhpcy4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lciA9IHRoaXMuJGFqYXhfcmVzdWx0c19jb250YWluZXI7XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlb2YodGhpcy5pbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdGhpcy5pbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyID0gXCJcIjtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHRoaXMuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIgPSBqUXVlcnkoJHRoaXMuYXR0cignZGF0YS1pbmZpbml0ZS1zY3JvbGwtY29udGFpbmVyJykpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlb2YodGhpcy5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdGhpcy5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzID0gXCJcIjtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYodHlwZW9mKHRoaXMudXNlX3Njcm9sbF9sb2FkZXIpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB0aGlzLnVzZV9zY3JvbGxfbG9hZGVyID0gMTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICB9O1xyXG4gICAgICAgIHRoaXMuc2V0SW5maW5pdGVTY3JvbGxDb250YWluZXIoKTtcclxuXHJcbiAgICAgICAgLyogZnVuY3Rpb25zICovXHJcblxyXG4gICAgICAgIHRoaXMucmVzZXQgPSBmdW5jdGlvbihzdWJtaXRfZm9ybSlcclxuICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICB0aGlzLnJlc2V0Rm9ybShzdWJtaXRfZm9ybSk7XHJcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5pbnB1dFVwZGF0ZSA9IGZ1bmN0aW9uKGRlbGF5RHVyYXRpb24pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2YoZGVsYXlEdXJhdGlvbik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBkZWxheUR1cmF0aW9uID0gMzAwO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBzZWxmLnJlc2V0VGltZXIoZGVsYXlEdXJhdGlvbik7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnNjcm9sbFRvUG9zID0gZnVuY3Rpb24oKSB7XHJcbiAgICAgICAgICAgIHZhciBvZmZzZXQgPSAwO1xyXG4gICAgICAgICAgICB2YXIgY2FuU2Nyb2xsID0gdHJ1ZTtcclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYuaXNfYWpheD09MSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgaWYoc2VsZi5zY3JvbGxfdG9fcG9zPT1cIndpbmRvd1wiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIG9mZnNldCA9IDA7XHJcblxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZSBpZihzZWxmLnNjcm9sbF90b19wb3M9PVwiZm9ybVwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIG9mZnNldCA9ICR0aGlzLm9mZnNldCgpLnRvcDtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2UgaWYoc2VsZi5zY3JvbGxfdG9fcG9zPT1cInJlc3VsdHNcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZihzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgb2Zmc2V0ID0gc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5vZmZzZXQoKS50b3A7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZSBpZihzZWxmLnNjcm9sbF90b19wb3M9PVwiY3VzdG9tXCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgLy9jdXN0b21fc2Nyb2xsX3RvXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi4kY3VzdG9tX3Njcm9sbF90by5sZW5ndGg+MClcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIG9mZnNldCA9IHNlbGYuJGN1c3RvbV9zY3JvbGxfdG8ub2Zmc2V0KCkudG9wO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBjYW5TY3JvbGwgPSBmYWxzZTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBpZihjYW5TY3JvbGwpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgJChcImh0bWwsIGJvZHlcIikuc3RvcCgpLmFuaW1hdGUoe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzY3JvbGxUb3A6IG9mZnNldFxyXG4gICAgICAgICAgICAgICAgICAgIH0sIFwibm9ybWFsXCIsIFwiZWFzZU91dFF1YWRcIiApO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuYXR0YWNoQWN0aXZlQ2xhc3MgPSBmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgLy9jaGVjayB0byBzZWUgaWYgd2UgYXJlIHVzaW5nIGFqYXggJiBhdXRvIGNvdW50XHJcbiAgICAgICAgICAgIC8vaWYgbm90LCB0aGUgc2VhcmNoIGZvcm0gZG9lcyBub3QgZ2V0IHJlbG9hZGVkLCBzbyB3ZSBuZWVkIHRvIHVwZGF0ZSB0aGUgc2Ytb3B0aW9uLWFjdGl2ZSBjbGFzcyBvbiBhbGwgZmllbGRzXHJcblxyXG4gICAgICAgICAgICAkdGhpcy5vbignY2hhbmdlJywgJ2lucHV0W3R5cGU9XCJyYWRpb1wiXSwgaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdLCBzZWxlY3QnLCBmdW5jdGlvbihlKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgJGN0aGlzID0gJCh0aGlzKTtcclxuICAgICAgICAgICAgICAgIHZhciAkY3RoaXNfcGFyZW50ID0gJGN0aGlzLmNsb3Nlc3QoXCJsaVtkYXRhLXNmLWZpZWxkLW5hbWVdXCIpO1xyXG4gICAgICAgICAgICAgICAgdmFyIHRoaXNfdGFnID0gJGN0aGlzLnByb3AoXCJ0YWdOYW1lXCIpLnRvTG93ZXJDYXNlKCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgaW5wdXRfdHlwZSA9ICRjdGhpcy5hdHRyKFwidHlwZVwiKTtcclxuICAgICAgICAgICAgICAgIHZhciBwYXJlbnRfdGFnID0gJGN0aGlzX3BhcmVudC5wcm9wKFwidGFnTmFtZVwiKS50b0xvd2VyQ2FzZSgpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKCh0aGlzX3RhZz09XCJpbnB1dFwiKSYmKChpbnB1dF90eXBlPT1cInJhZGlvXCIpfHwoaW5wdXRfdHlwZT09XCJjaGVja2JveFwiKSkgJiYgKHBhcmVudF90YWc9PVwibGlcIikpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICRhbGxfb3B0aW9ucyA9ICRjdGhpc19wYXJlbnQucGFyZW50KCkuZmluZCgnbGknKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGFsbF9vcHRpb25zX2ZpZWxkcyA9ICRjdGhpc19wYXJlbnQucGFyZW50KCkuZmluZCgnaW5wdXQ6Y2hlY2tlZCcpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAkYWxsX29wdGlvbnMucmVtb3ZlQ2xhc3MoXCJzZi1vcHRpb24tYWN0aXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICRhbGxfb3B0aW9uc19maWVsZHMuZWFjaChmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRwYXJlbnQgPSAkKHRoaXMpLmNsb3Nlc3QoXCJsaVwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJHBhcmVudC5hZGRDbGFzcyhcInNmLW9wdGlvbi1hY3RpdmVcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2UgaWYodGhpc190YWc9PVwic2VsZWN0XCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICRhbGxfb3B0aW9ucyA9ICRjdGhpcy5jaGlsZHJlbigpO1xyXG4gICAgICAgICAgICAgICAgICAgICRhbGxfb3B0aW9ucy5yZW1vdmVDbGFzcyhcInNmLW9wdGlvbi1hY3RpdmVcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHRoaXNfdmFsID0gJGN0aGlzLnZhbCgpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgdGhpc19hcnJfdmFsID0gKHR5cGVvZiB0aGlzX3ZhbCA9PSAnc3RyaW5nJyB8fCB0aGlzX3ZhbCBpbnN0YW5jZW9mIFN0cmluZykgPyBbdGhpc192YWxdIDogdGhpc192YWw7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICQodGhpc19hcnJfdmFsKS5lYWNoKGZ1bmN0aW9uKGksIHZhbHVlKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJGN0aGlzLmZpbmQoXCJvcHRpb25bdmFsdWU9J1wiK3ZhbHVlK1wiJ11cIikuYWRkQ2xhc3MoXCJzZi1vcHRpb24tYWN0aXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICB9O1xyXG4gICAgICAgIHRoaXMuaW5pdEF1dG9VcGRhdGVFdmVudHMgPSBmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgLyogYXV0byB1cGRhdGUgKi9cclxuICAgICAgICAgICAgaWYoKHNlbGYuYXV0b191cGRhdGU9PTEpfHwoc2VsZi5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZT09MSkpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICR0aGlzLm9uKCdjaGFuZ2UnLCAnaW5wdXRbdHlwZT1cInJhZGlvXCJdLCBpbnB1dFt0eXBlPVwiY2hlY2tib3hcIl0sIHNlbGVjdCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLmlucHV0VXBkYXRlKDIwMCk7XHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICAkdGhpcy5vbignaW5wdXQnLCAnaW5wdXRbdHlwZT1cIm51bWJlclwiXScsIGZ1bmN0aW9uKGUpIHtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLmlucHV0VXBkYXRlKDgwMCk7XHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgJHRleHRJbnB1dCA9ICR0aGlzLmZpbmQoJ2lucHV0W3R5cGU9XCJ0ZXh0XCJdOm5vdCguc2YtZGF0ZXBpY2tlciknKTtcclxuICAgICAgICAgICAgICAgIHZhciBsYXN0VmFsdWUgPSAkdGV4dElucHV0LnZhbCgpO1xyXG5cclxuICAgICAgICAgICAgICAgICR0aGlzLm9uKCdpbnB1dCcsICdpbnB1dFt0eXBlPVwidGV4dFwiXTpub3QoLnNmLWRhdGVwaWNrZXIpJywgZnVuY3Rpb24oKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKGxhc3RWYWx1ZSE9JHRleHRJbnB1dC52YWwoKSlcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoMTIwMCk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICBsYXN0VmFsdWUgPSAkdGV4dElucHV0LnZhbCgpO1xyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICR0aGlzLm9uKCdrZXlwcmVzcycsICdpbnB1dFt0eXBlPVwidGV4dFwiXTpub3QoLnNmLWRhdGVwaWNrZXIpJywgZnVuY3Rpb24oZSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiAoZS53aGljaCA9PSAxMyl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc3VibWl0Rm9ybSgpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vJHRoaXMub24oJ2lucHV0JywgJ2lucHV0LnNmLWRhdGVwaWNrZXInLCBzZWxmLmRhdGVJbnB1dFR5cGUpO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIC8vdGhpcy5pbml0QXV0b1VwZGF0ZUV2ZW50cygpO1xyXG5cclxuXHJcbiAgICAgICAgdGhpcy5jbGVhclRpbWVyID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHNlbGYuaW5wdXRUaW1lcik7XHJcbiAgICAgICAgfTtcclxuICAgICAgICB0aGlzLnJlc2V0VGltZXIgPSBmdW5jdGlvbihkZWxheUR1cmF0aW9uKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHNlbGYuaW5wdXRUaW1lcik7XHJcbiAgICAgICAgICAgIHNlbGYuaW5wdXRUaW1lciA9IHNldFRpbWVvdXQoc2VsZi5mb3JtVXBkYXRlZCwgZGVsYXlEdXJhdGlvbik7XHJcblxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuYWRkRGF0ZVBpY2tlcnMgPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgJGRhdGVfcGlja2VyID0gJHRoaXMuZmluZChcIi5zZi1kYXRlcGlja2VyXCIpO1xyXG5cclxuICAgICAgICAgICAgaWYoJGRhdGVfcGlja2VyLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAkZGF0ZV9waWNrZXIuZWFjaChmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkYXRlRm9ybWF0ID0gXCJcIjtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgZGF0ZURyb3Bkb3duWWVhciA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkYXRlRHJvcGRvd25Nb250aCA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGNsb3Nlc3RfZGF0ZV93cmFwID0gJHRoaXMuY2xvc2VzdChcIi5zZl9kYXRlX2ZpZWxkXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKCRjbG9zZXN0X2RhdGVfd3JhcC5sZW5ndGg+MClcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGVGb3JtYXQgPSAkY2xvc2VzdF9kYXRlX3dyYXAuYXR0cihcImRhdGEtZGF0ZS1mb3JtYXRcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigkY2xvc2VzdF9kYXRlX3dyYXAuYXR0cihcImRhdGEtZGF0ZS11c2UteWVhci1kcm9wZG93blwiKT09MSlcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZGF0ZURyb3Bkb3duWWVhciA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoJGNsb3Nlc3RfZGF0ZV93cmFwLmF0dHIoXCJkYXRhLWRhdGUtdXNlLW1vbnRoLWRyb3Bkb3duXCIpPT0xKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlRHJvcGRvd25Nb250aCA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkYXRlUGlja2VyT3B0aW9ucyA9IHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgaW5saW5lOiB0cnVlLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICBzaG93T3RoZXJNb250aHM6IHRydWUsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIG9uU2VsZWN0OiBmdW5jdGlvbihlLCBmcm9tX2ZpZWxkKXsgc2VsZi5kYXRlU2VsZWN0KGUsIGZyb21fZmllbGQsICQodGhpcykpOyB9LFxyXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRlRm9ybWF0OiBkYXRlRm9ybWF0LFxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgY2hhbmdlTW9udGg6IGRhdGVEcm9wZG93bk1vbnRoLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICBjaGFuZ2VZZWFyOiBkYXRlRHJvcGRvd25ZZWFyXHJcbiAgICAgICAgICAgICAgICAgICAgfTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5pc19ydGw9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBkYXRlUGlja2VyT3B0aW9ucy5kaXJlY3Rpb24gPSBcInJ0bFwiO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgJHRoaXMuZGF0ZXBpY2tlcihkYXRlUGlja2VyT3B0aW9ucyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHNlbGYubGFuZ19jb2RlIT1cIlwiKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJC5kYXRlcGlja2VyLnNldERlZmF1bHRzKFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJC5leHRlbmQoXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgeydkYXRlRm9ybWF0JzpkYXRlRm9ybWF0fSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkLmRhdGVwaWNrZXIucmVnaW9uYWxbIHNlbGYubGFuZ19jb2RlXVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICApO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJC5kYXRlcGlja2VyLnNldERlZmF1bHRzKFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJC5leHRlbmQoXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgeydkYXRlRm9ybWF0JzpkYXRlRm9ybWF0fSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkLmRhdGVwaWNrZXIucmVnaW9uYWxbXCJlblwiXVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICApO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYoJCgnLmxsLXNraW4tbWVsb24nKS5sZW5ndGg9PTApe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAkZGF0ZV9waWNrZXIuZGF0ZXBpY2tlcignd2lkZ2V0Jykud3JhcCgnPGRpdiBjbGFzcz1cImxsLXNraW4tbWVsb24gc2VhcmNoYW5kZmlsdGVyLWRhdGUtcGlja2VyXCIvPicpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuZGF0ZVNlbGVjdCA9IGZ1bmN0aW9uKGUsIGZyb21fZmllbGQsICR0aGlzKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyICRpbnB1dF9maWVsZCA9ICQoZnJvbV9maWVsZC5pbnB1dC5nZXQoMCkpO1xyXG4gICAgICAgICAgICB2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgdmFyICRkYXRlX2ZpZWxkcyA9ICRpbnB1dF9maWVsZC5jbG9zZXN0KCdbZGF0YS1zZi1maWVsZC1pbnB1dC10eXBlPVwiZGF0ZXJhbmdlXCJdLCBbZGF0YS1zZi1maWVsZC1pbnB1dC10eXBlPVwiZGF0ZVwiXScpO1xyXG4gICAgICAgICAgICAkZGF0ZV9maWVsZHMuZWFjaChmdW5jdGlvbihlLCBpbmRleCl7XHJcbiAgICAgICAgICAgICAgICBcclxuICAgICAgICAgICAgICAgIHZhciAkdGZfZGF0ZV9waWNrZXJzID0gJCh0aGlzKS5maW5kKFwiLnNmLWRhdGVwaWNrZXJcIik7XHJcbiAgICAgICAgICAgICAgICB2YXIgbm9fZGF0ZV9waWNrZXJzID0gJHRmX2RhdGVfcGlja2Vycy5sZW5ndGg7XHJcbiAgICAgICAgICAgICAgICBcclxuICAgICAgICAgICAgICAgIGlmKG5vX2RhdGVfcGlja2Vycz4xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vdGhlbiBpdCBpcyBhIGRhdGUgcmFuZ2UsIHNvIG1ha2Ugc3VyZSBib3RoIGZpZWxkcyBhcmUgZmlsbGVkIGJlZm9yZSB1cGRhdGluZ1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkcF9jb3VudGVyID0gMDtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgZHBfZW1wdHlfZmllbGRfY291bnQgPSAwO1xyXG4gICAgICAgICAgICAgICAgICAgICR0Zl9kYXRlX3BpY2tlcnMuZWFjaChmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoJCh0aGlzKS52YWwoKT09XCJcIilcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZHBfZW1wdHlfZmllbGRfY291bnQrKztcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgZHBfY291bnRlcisrO1xyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZihkcF9lbXB0eV9maWVsZF9jb3VudD09MClcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoMSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoMSk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB9KTtcclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmFkZFJhbmdlU2xpZGVycyA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHZhciAkbWV0YV9yYW5nZSA9ICR0aGlzLmZpbmQoXCIuc2YtbWV0YS1yYW5nZS1zbGlkZXJcIik7XHJcblxyXG4gICAgICAgICAgICBpZigkbWV0YV9yYW5nZS5sZW5ndGg+MClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgJG1ldGFfcmFuZ2UuZWFjaChmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBtaW4gPSAkdGhpcy5hdHRyKFwiZGF0YS1taW5cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1heCA9ICR0aGlzLmF0dHIoXCJkYXRhLW1heFwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc21pbiA9ICR0aGlzLmF0dHIoXCJkYXRhLXN0YXJ0LW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc21heCA9ICR0aGlzLmF0dHIoXCJkYXRhLXN0YXJ0LW1heFwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgZGlzcGxheV92YWx1ZV9hcyA9ICR0aGlzLmF0dHIoXCJkYXRhLWRpc3BsYXktdmFsdWVzLWFzXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzdGVwID0gJHRoaXMuYXR0cihcImRhdGEtc3RlcFwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJHN0YXJ0X3ZhbCA9ICR0aGlzLmZpbmQoJy5zZi1yYW5nZS1taW4nKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGVuZF92YWwgPSAkdGhpcy5maW5kKCcuc2YtcmFuZ2UtbWF4Jyk7XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgZGVjaW1hbF9wbGFjZXMgPSAkdGhpcy5hdHRyKFwiZGF0YS1kZWNpbWFsLXBsYWNlc1wiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgdGhvdXNhbmRfc2VwZXJhdG9yID0gJHRoaXMuYXR0cihcImRhdGEtdGhvdXNhbmQtc2VwZXJhdG9yXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkZWNpbWFsX3NlcGVyYXRvciA9ICR0aGlzLmF0dHIoXCJkYXRhLWRlY2ltYWwtc2VwZXJhdG9yXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgZmllbGRfZm9ybWF0ID0gd051bWIoe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBtYXJrOiBkZWNpbWFsX3NlcGVyYXRvcixcclxuICAgICAgICAgICAgICAgICAgICAgICAgZGVjaW1hbHM6IHBhcnNlRmxvYXQoZGVjaW1hbF9wbGFjZXMpLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICB0aG91c2FuZDogdGhvdXNhbmRfc2VwZXJhdG9yXHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1pbl91bmZvcm1hdHRlZCA9IHBhcnNlRmxvYXQoc21pbik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1pbl9mb3JtYXR0ZWQgPSBmaWVsZF9mb3JtYXQudG8ocGFyc2VGbG9hdChzbWluKSk7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1heF9mb3JtYXR0ZWQgPSBmaWVsZF9mb3JtYXQudG8ocGFyc2VGbG9hdChzbWF4KSk7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1heF91bmZvcm1hdHRlZCA9IHBhcnNlRmxvYXQoc21heCk7XHJcbiAgICAgICAgICAgICAgICAgICAgLy9hbGVydChtaW5fZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAvL2FsZXJ0KG1heF9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgIC8vYWxlcnQoZGlzcGxheV92YWx1ZV9hcyk7XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZihkaXNwbGF5X3ZhbHVlX2FzPT1cInRleHRpbnB1dFwiKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC52YWwobWluX2Zvcm1hdHRlZCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRlbmRfdmFsLnZhbChtYXhfZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgZWxzZSBpZihkaXNwbGF5X3ZhbHVlX2FzPT1cInRleHRcIilcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRzdGFydF92YWwuaHRtbChtaW5fZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJGVuZF92YWwuaHRtbChtYXhfZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgbm9VSU9wdGlvbnMgPSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHJhbmdlOiB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbWluJzogWyBwYXJzZUZsb2F0KG1pbikgXSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdtYXgnOiBbIHBhcnNlRmxvYXQobWF4KSBdXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHN0YXJ0OiBbbWluX2Zvcm1hdHRlZCwgbWF4X2Zvcm1hdHRlZF0sXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGhhbmRsZXM6IDIsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbm5lY3Q6IHRydWUsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHN0ZXA6IHBhcnNlRmxvYXQoc3RlcCksXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBiZWhhdmlvdXI6ICdleHRlbmQtdGFwJyxcclxuICAgICAgICAgICAgICAgICAgICAgICAgZm9ybWF0OiBmaWVsZF9mb3JtYXRcclxuICAgICAgICAgICAgICAgICAgICB9O1xyXG5cclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHNlbGYuaXNfcnRsPT0xKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgbm9VSU9wdGlvbnMuZGlyZWN0aW9uID0gXCJydGxcIjtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzbGlkZXJfb2JqZWN0ID0gJCh0aGlzKS5maW5kKFwiLm1ldGEtc2xpZGVyXCIpWzBdO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZiggXCJ1bmRlZmluZWRcIiAhPT0gdHlwZW9mKCBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIgKSApIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9kZXN0cm95IGlmIGl0IGV4aXN0cy4uIHRoaXMgbWVhbnMgc29tZWhvdyBhbm90aGVyIGluc3RhbmNlIGhhZCBpbml0aWFsaXNlZCBpdC4uXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5kZXN0cm95KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICBub1VpU2xpZGVyLmNyZWF0ZShzbGlkZXJfb2JqZWN0LCBub1VJT3B0aW9ucyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICRzdGFydF92YWwub2ZmKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC5vbignY2hhbmdlJywgZnVuY3Rpb24oKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLnNldChbJCh0aGlzKS52YWwoKSwgbnVsbF0pO1xyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAkZW5kX3ZhbC5vZmYoKTtcclxuICAgICAgICAgICAgICAgICAgICAkZW5kX3ZhbC5vbignY2hhbmdlJywgZnVuY3Rpb24oKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLnNldChbbnVsbCwgJCh0aGlzKS52YWwoKV0pO1xyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvLyRzdGFydF92YWwuaHRtbChtaW5fZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAvLyRlbmRfdmFsLmh0bWwobWF4X2Zvcm1hdHRlZCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5vZmYoJ3VwZGF0ZScpO1xyXG4gICAgICAgICAgICAgICAgICAgIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5vbigndXBkYXRlJywgZnVuY3Rpb24oIHZhbHVlcywgaGFuZGxlICkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNsaWRlcl9zdGFydF92YWwgID0gbWluX2Zvcm1hdHRlZDtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNsaWRlcl9lbmRfdmFsICA9IG1heF9mb3JtYXR0ZWQ7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgdmFsdWUgPSB2YWx1ZXNbaGFuZGxlXTtcclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIGhhbmRsZSApIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1heF9mb3JtYXR0ZWQgPSB2YWx1ZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1pbl9mb3JtYXR0ZWQgPSB2YWx1ZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoZGlzcGxheV92YWx1ZV9hcz09XCJ0ZXh0aW5wdXRcIilcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC52YWwobWluX2Zvcm1hdHRlZCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZW5kX3ZhbC52YWwobWF4X2Zvcm1hdHRlZCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgZWxzZSBpZihkaXNwbGF5X3ZhbHVlX2FzPT1cInRleHRcIilcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC5odG1sKG1pbl9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGVuZF92YWwuaHRtbChtYXhfZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vaSB0aGluayB0aGUgZnVuY3Rpb24gdGhhdCBidWlsZHMgdGhlIFVSTCBuZWVkcyB0byBkZWNvZGUgdGhlIGZvcm1hdHRlZCBzdHJpbmcgYmVmb3JlIGFkZGluZyB0byB0aGUgdXJsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKChzZWxmLmF1dG9fdXBkYXRlPT0xKXx8KHNlbGYuYXV0b19jb3VudF9yZWZyZXNoX21vZGU9PTEpKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL29ubHkgdHJ5IHRvIHVwZGF0ZSBpZiB0aGUgdmFsdWVzIGhhdmUgYWN0dWFsbHkgY2hhbmdlZFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYoKHNsaWRlcl9zdGFydF92YWwhPW1pbl9mb3JtYXR0ZWQpfHwoc2xpZGVyX2VuZF92YWwhPW1heF9mb3JtYXR0ZWQpKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoODAwKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuY2xlYXJUaW1lcigpOyAvL2lnbm9yZSBhbnkgY2hhbmdlcyByZWNlbnRseSBtYWRlIGJ5IHRoZSBzbGlkZXIgKHRoaXMgd2FzIGp1c3QgaW5pdCBzaG91bGRuJ3QgY291bnQgYXMgYW4gdXBkYXRlIGV2ZW50KVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5pbml0ID0gZnVuY3Rpb24oa2VlcF9wYWdpbmF0aW9uKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYodHlwZW9mKGtlZXBfcGFnaW5hdGlvbik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBrZWVwX3BhZ2luYXRpb24gPSBmYWxzZTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdGhpcy5pbml0QXV0b1VwZGF0ZUV2ZW50cygpO1xyXG4gICAgICAgICAgICB0aGlzLmF0dGFjaEFjdGl2ZUNsYXNzKCk7XHJcblxyXG4gICAgICAgICAgICB0aGlzLmFkZERhdGVQaWNrZXJzKCk7XHJcbiAgICAgICAgICAgIHRoaXMuYWRkUmFuZ2VTbGlkZXJzKCk7XHJcblxyXG4gICAgICAgICAgICAvL2luaXQgY29tYm8gYm94ZXNcclxuICAgICAgICAgICAgdmFyICRjb21ib2JveCA9ICR0aGlzLmZpbmQoXCJzZWxlY3RbZGF0YS1jb21ib2JveD0nMSddXCIpO1xyXG5cclxuICAgICAgICAgICAgaWYoJGNvbWJvYm94Lmxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAkY29tYm9ib3guZWFjaChmdW5jdGlvbihpbmRleCApe1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkdGhpc2NiID0gJCggdGhpcyApO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBucm0gPSAkdGhpc2NiLmF0dHIoXCJkYXRhLWNvbWJvYm94LW5ybVwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAkdGhpc2NiLmNob3NlbiAhPSBcInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGNob3Nlbm9wdGlvbnMgPSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWFyY2hfY29udGFpbnM6IHRydWVcclxuICAgICAgICAgICAgICAgICAgICAgICAgfTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKCh0eXBlb2YobnJtKSE9PVwidW5kZWZpbmVkXCIpJiYobnJtKSl7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjaG9zZW5vcHRpb25zLm5vX3Jlc3VsdHNfdGV4dCA9IG5ybTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBzYWZlIHRvIHVzZSB0aGUgZnVuY3Rpb25cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9zZWFyY2hfY29udGFpbnNcclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5pc19ydGw9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzY2IuYWRkQ2xhc3MoXCJjaG9zZW4tcnRsXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGhpc2NiLmNob3NlbihjaG9zZW5vcHRpb25zKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzZWxlY3Qyb3B0aW9ucyA9IHt9O1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5pc19ydGw9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGVjdDJvcHRpb25zLmRpciA9IFwicnRsXCI7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoKHR5cGVvZihucm0pIT09XCJ1bmRlZmluZWRcIikmJihucm0pKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGVjdDJvcHRpb25zLmxhbmd1YWdlPSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXCJub1Jlc3VsdHNcIjogZnVuY3Rpb24oKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG5ybTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9O1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGhpc2NiLnNlbGVjdDIoc2VsZWN0Mm9wdGlvbnMpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcblxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBzZWxmLmlzU3VibWl0dGluZyA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgLy9pZiBhamF4IGlzIGVuYWJsZWQgaW5pdCB0aGUgcGFnaW5hdGlvblxyXG4gICAgICAgICAgICBpZihzZWxmLmlzX2FqYXg9PTEpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuc2V0dXBBamF4UGFnaW5hdGlvbigpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAkdGhpcy5vbihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdEZvcm0pO1xyXG5cclxuICAgICAgICAgICAgc2VsZi5pbml0V29vQ29tbWVyY2VDb250cm9scygpOyAvL3dvb2NvbW1lcmNlIG9yZGVyYnlcclxuXHJcbiAgICAgICAgICAgIGlmKGtlZXBfcGFnaW5hdGlvbj09ZmFsc2UpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9zdWJtaXRfcXVlcnlfcGFyYW1zID0gc2VsZi5nZXRVcmxQYXJhbXMoZmFsc2UpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLm9uV2luZG93U2Nyb2xsID0gZnVuY3Rpb24oZXZlbnQpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZigoIXNlbGYuaXNfbG9hZGluZ19tb3JlKSAmJiAoIXNlbGYuaXNfbWF4X3BhZ2VkKSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIHdpbmRvd19zY3JvbGwgPSAkKHdpbmRvdykuc2Nyb2xsVG9wKCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgd2luZG93X3Njcm9sbF9ib3R0b20gPSAkKHdpbmRvdykuc2Nyb2xsVG9wKCkgKyAkKHdpbmRvdykuaGVpZ2h0KCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgc2Nyb2xsX29mZnNldCA9IHBhcnNlSW50KHNlbGYuaW5maW5pdGVfc2Nyb2xsX3RyaWdnZXJfYW1vdW50KTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLmxlbmd0aD09MSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgcmVzdWx0c19zY3JvbGxfYm90dG9tID0gc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5vZmZzZXQoKS50b3AgKyBzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLmhlaWdodCgpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgb2Zmc2V0ID0gKHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIub2Zmc2V0KCkudG9wICsgc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5oZWlnaHQoKSkgLSB3aW5kb3dfc2Nyb2xsO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZih3aW5kb3dfc2Nyb2xsX2JvdHRvbSA+IHJlc3VsdHNfc2Nyb2xsX2JvdHRvbSArIHNjcm9sbF9vZmZzZXQpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmxvYWRNb3JlUmVzdWx0cygpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICAgICAgey8vZG9udCBsb2FkIG1vcmVcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnN0cmlwUXVlcnlTdHJpbmdBbmRIYXNoRnJvbVBhdGggPSBmdW5jdGlvbih1cmwpIHtcclxuICAgICAgICAgICAgcmV0dXJuIHVybC5zcGxpdChcIj9cIilbMF0uc3BsaXQoXCIjXCIpWzBdO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5ndXAgPSBmdW5jdGlvbiggbmFtZSwgdXJsICkge1xyXG4gICAgICAgICAgICBpZiAoIXVybCkgdXJsID0gbG9jYXRpb24uaHJlZlxyXG4gICAgICAgICAgICBuYW1lID0gbmFtZS5yZXBsYWNlKC9bXFxbXS8sXCJcXFxcXFxbXCIpLnJlcGxhY2UoL1tcXF1dLyxcIlxcXFxcXF1cIik7XHJcbiAgICAgICAgICAgIHZhciByZWdleFMgPSBcIltcXFxcPyZdXCIrbmFtZStcIj0oW14mI10qKVwiO1xyXG4gICAgICAgICAgICB2YXIgcmVnZXggPSBuZXcgUmVnRXhwKCByZWdleFMgKTtcclxuICAgICAgICAgICAgdmFyIHJlc3VsdHMgPSByZWdleC5leGVjKCB1cmwgKTtcclxuICAgICAgICAgICAgcmV0dXJuIHJlc3VsdHMgPT0gbnVsbCA/IG51bGwgOiByZXN1bHRzWzFdO1xyXG4gICAgICAgIH07XHJcblxyXG5cclxuICAgICAgICB0aGlzLmdldFVybFBhcmFtcyA9IGZ1bmN0aW9uKGtlZXBfcGFnaW5hdGlvbiwgdHlwZSwgZXhjbHVkZSlcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmKHR5cGVvZihrZWVwX3BhZ2luYXRpb24pPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIga2VlcF9wYWdpbmF0aW9uID0gdHJ1ZTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYodHlwZW9mKHR5cGUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgdHlwZSA9IFwiXCI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciB1cmxfcGFyYW1zX3N0ciA9IFwiXCI7XHJcblxyXG4gICAgICAgICAgICAvLyBnZXQgYWxsIHBhcmFtcyBmcm9tIGZpZWxkc1xyXG4gICAgICAgICAgICB2YXIgdXJsX3BhcmFtc19hcnJheSA9IHByb2Nlc3NfZm9ybS5nZXRVcmxQYXJhbXMoc2VsZik7XHJcblxyXG4gICAgICAgICAgICB2YXIgbGVuZ3RoID0gT2JqZWN0LmtleXModXJsX3BhcmFtc19hcnJheSkubGVuZ3RoO1xyXG4gICAgICAgICAgICB2YXIgY291bnQgPSAwO1xyXG5cclxuICAgICAgICAgICAgaWYodHlwZW9mKGV4Y2x1ZGUpIT1cInVuZGVmaW5lZFwiKSB7XHJcbiAgICAgICAgICAgICAgICBpZiAodXJsX3BhcmFtc19hcnJheS5oYXNPd25Qcm9wZXJ0eShleGNsdWRlKSkge1xyXG4gICAgICAgICAgICAgICAgICAgIGxlbmd0aC0tO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZihsZW5ndGg+MClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgZm9yICh2YXIgayBpbiB1cmxfcGFyYW1zX2FycmF5KSB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKHVybF9wYXJhbXNfYXJyYXkuaGFzT3duUHJvcGVydHkoaykpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBjYW5fYWRkID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYodHlwZW9mKGV4Y2x1ZGUpIT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZihrPT1leGNsdWRlKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY2FuX2FkZCA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZihjYW5fYWRkKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmxfcGFyYW1zX3N0ciArPSBrICsgXCI9XCIgKyB1cmxfcGFyYW1zX2FycmF5W2tdO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChjb3VudCA8IGxlbmd0aCAtIDEpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmxfcGFyYW1zX3N0ciArPSBcIiZcIjtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb3VudCsrO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB2YXIgcXVlcnlfcGFyYW1zID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIC8vZm9ybSBwYXJhbXMgYXMgdXJsIHF1ZXJ5IHN0cmluZ1xyXG4gICAgICAgICAgICB2YXIgZm9ybV9wYXJhbXMgPSB1cmxfcGFyYW1zX3N0cjtcclxuXHJcbiAgICAgICAgICAgIC8vZ2V0IHVybCBwYXJhbXMgZnJvbSB0aGUgZm9ybSBpdHNlbGYgKHdoYXQgdGhlIHVzZXIgaGFzIHNlbGVjdGVkKVxyXG4gICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIGZvcm1fcGFyYW1zKTtcclxuXHJcbiAgICAgICAgICAgIC8vYWRkIHBhZ2luYXRpb25cclxuICAgICAgICAgICAgaWYoa2VlcF9wYWdpbmF0aW9uPT10cnVlKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgcGFnZU51bWJlciA9IHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuYXR0cihcImRhdGEtcGFnZWRcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYodHlwZW9mKHBhZ2VOdW1iZXIpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHBhZ2VOdW1iZXIgPSAxO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKHBhZ2VOdW1iZXI+MSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIFwic2ZfcGFnZWQ9XCIrcGFnZU51bWJlcik7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIC8vYWRkIHNmaWRcclxuICAgICAgICAgICAgLy9xdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIFwic2ZpZD1cIitzZWxmLnNmaWQpO1xyXG5cclxuICAgICAgICAgICAgLy8gbG9vcCB0aHJvdWdoIGFueSBleHRyYSBwYXJhbXMgKGZyb20gZXh0IHBsdWdpbnMpIGFuZCBhZGQgdG8gdGhlIHVybCAoaWUgd29vY29tbWVyY2UgYG9yZGVyYnlgKVxyXG4gICAgICAgICAgICAvKnZhciBleHRyYV9xdWVyeV9wYXJhbSA9IFwiXCI7XHJcbiAgICAgICAgICAgICB2YXIgbGVuZ3RoID0gT2JqZWN0LmtleXMoc2VsZi5leHRyYV9xdWVyeV9wYXJhbXMpLmxlbmd0aDtcclxuICAgICAgICAgICAgIHZhciBjb3VudCA9IDA7XHJcblxyXG4gICAgICAgICAgICAgaWYobGVuZ3RoPjApXHJcbiAgICAgICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICAgZm9yICh2YXIgayBpbiBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtcykge1xyXG4gICAgICAgICAgICAgaWYgKHNlbGYuZXh0cmFfcXVlcnlfcGFyYW1zLmhhc093blByb3BlcnR5KGspKSB7XHJcblxyXG4gICAgICAgICAgICAgaWYoc2VsZi5leHRyYV9xdWVyeV9wYXJhbXNba10hPVwiXCIpXHJcbiAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICBleHRyYV9xdWVyeV9wYXJhbSA9IGsrXCI9XCIrc2VsZi5leHRyYV9xdWVyeV9wYXJhbXNba107XHJcbiAgICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIGV4dHJhX3F1ZXJ5X3BhcmFtKTtcclxuICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICovXHJcbiAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuYWRkUXVlcnlQYXJhbXMocXVlcnlfcGFyYW1zLCBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtcy5hbGwpO1xyXG5cclxuICAgICAgICAgICAgaWYodHlwZSE9XCJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgLy9xdWVyeV9wYXJhbXMgPSBzZWxmLmFkZFF1ZXJ5UGFyYW1zKHF1ZXJ5X3BhcmFtcywgc2VsZi5leHRyYV9xdWVyeV9wYXJhbXNbdHlwZV0pO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICByZXR1cm4gcXVlcnlfcGFyYW1zO1xyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLmFkZFF1ZXJ5UGFyYW1zID0gZnVuY3Rpb24ocXVlcnlfcGFyYW1zLCBuZXdfcGFyYW1zKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyIGV4dHJhX3F1ZXJ5X3BhcmFtID0gXCJcIjtcclxuICAgICAgICAgICAgdmFyIGxlbmd0aCA9IE9iamVjdC5rZXlzKG5ld19wYXJhbXMpLmxlbmd0aDtcclxuICAgICAgICAgICAgdmFyIGNvdW50ID0gMDtcclxuXHJcbiAgICAgICAgICAgIGlmKGxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICAgICAgZm9yICh2YXIgayBpbiBuZXdfcGFyYW1zKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKG5ld19wYXJhbXMuaGFzT3duUHJvcGVydHkoaykpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKG5ld19wYXJhbXNba10hPVwiXCIpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV4dHJhX3F1ZXJ5X3BhcmFtID0gaytcIj1cIituZXdfcGFyYW1zW2tdO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5qb2luVXJsUGFyYW0ocXVlcnlfcGFyYW1zLCBleHRyYV9xdWVyeV9wYXJhbSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHJldHVybiBxdWVyeV9wYXJhbXM7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuYWRkVXJsUGFyYW0gPSBmdW5jdGlvbih1cmwsIHN0cmluZylcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHZhciBhZGRfcGFyYW1zID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIGlmKHVybCE9XCJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgaWYodXJsLmluZGV4T2YoXCI/XCIpICE9IC0xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGFkZF9wYXJhbXMgKz0gXCImXCI7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgLy91cmwgPSB0aGlzLnRyYWlsaW5nU2xhc2hJdCh1cmwpO1xyXG4gICAgICAgICAgICAgICAgICAgIGFkZF9wYXJhbXMgKz0gXCI/XCI7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKHN0cmluZyE9XCJcIilcclxuICAgICAgICAgICAge1xyXG5cclxuICAgICAgICAgICAgICAgIHJldHVybiB1cmwgKyBhZGRfcGFyYW1zICsgc3RyaW5nO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgcmV0dXJuIHVybDtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuam9pblVybFBhcmFtID0gZnVuY3Rpb24ocGFyYW1zLCBzdHJpbmcpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgYWRkX3BhcmFtcyA9IFwiXCI7XHJcblxyXG4gICAgICAgICAgICBpZihwYXJhbXMhPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIGFkZF9wYXJhbXMgKz0gXCImXCI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKHN0cmluZyE9XCJcIilcclxuICAgICAgICAgICAge1xyXG5cclxuICAgICAgICAgICAgICAgIHJldHVybiBwYXJhbXMgKyBhZGRfcGFyYW1zICsgc3RyaW5nO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgcmV0dXJuIHBhcmFtcztcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuc2V0QWpheFJlc3VsdHNVUkxzID0gZnVuY3Rpb24ocXVlcnlfcGFyYW1zKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYodHlwZW9mKHNlbGYuYWpheF9yZXN1bHRzX2NvbmYpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mID0gbmV3IEFycmF5KCk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10gPSBcIlwiO1xyXG4gICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddID0gXCJcIjtcclxuICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsnZGF0YV90eXBlJ10gPSBcIlwiO1xyXG5cclxuICAgICAgICAgICAgLy9pZihzZWxmLmFqYXhfdXJsIT1cIlwiKVxyXG4gICAgICAgICAgICBpZihzZWxmLmRpc3BsYXlfcmVzdWx0X21ldGhvZD09XCJzaG9ydGNvZGVcIilcclxuICAgICAgICAgICAgey8vdGhlbiB3ZSB3YW50IHRvIGRvIGEgcmVxdWVzdCB0byB0aGUgYWpheCBlbmRwb2ludFxyXG4gICAgICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncmVzdWx0c191cmwnXSA9IHNlbGYuYWRkVXJsUGFyYW0oc2VsZi5yZXN1bHRzX3VybCwgcXVlcnlfcGFyYW1zKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL2FkZCBsYW5nIGNvZGUgdG8gYWpheCBhcGkgcmVxdWVzdCwgbGFuZyBjb2RlIHNob3VsZCBhbHJlYWR5IGJlIGluIHRoZXJlIGZvciBvdGhlciByZXF1ZXN0cyAoaWUsIHN1cHBsaWVkIGluIHRoZSBSZXN1bHRzIFVSTClcclxuXHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLmxhbmdfY29kZSE9XCJcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvL3NvIGFkZCBpdFxyXG4gICAgICAgICAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgXCJsYW5nPVwiK3NlbGYubGFuZ19jb2RlKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydwcm9jZXNzaW5nX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShzZWxmLmFqYXhfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICAgICAgLy9zZWxmLmFqYXhfcmVzdWx0c19jb25mWydkYXRhX3R5cGUnXSA9ICdqc29uJztcclxuXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihzZWxmLmRpc3BsYXlfcmVzdWx0X21ldGhvZD09XCJwb3N0X3R5cGVfYXJjaGl2ZVwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBwcm9jZXNzX2Zvcm0uc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmwoc2VsZiwgc2VsZi5yZXN1bHRzX3VybCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgcmVzdWx0c191cmwgPSBwcm9jZXNzX2Zvcm0uZ2V0UmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShyZXN1bHRzX3VybCwgcXVlcnlfcGFyYW1zKTtcclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHNlbGYuZGlzcGxheV9yZXN1bHRfbWV0aG9kPT1cImN1c3RvbV93b29jb21tZXJjZV9zdG9yZVwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBwcm9jZXNzX2Zvcm0uc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmwoc2VsZiwgc2VsZi5yZXN1bHRzX3VybCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgcmVzdWx0c191cmwgPSBwcm9jZXNzX2Zvcm0uZ2V0UmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShyZXN1bHRzX3VybCwgcXVlcnlfcGFyYW1zKTtcclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHsvL290aGVyd2lzZSB3ZSB3YW50IHRvIHB1bGwgdGhlIHJlc3VsdHMgZGlyZWN0bHkgZnJvbSB0aGUgcmVzdWx0cyBwYWdlXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShzZWxmLnJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSA9IHNlbGYuYWRkVXJsUGFyYW0oc2VsZi5hamF4X3VybCwgcXVlcnlfcGFyYW1zKTtcclxuICAgICAgICAgICAgICAgIC8vc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsnZGF0YV90eXBlJ10gPSAnaHRtbCc7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10gPSBzZWxmLmFkZFF1ZXJ5UGFyYW1zKHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10sIHNlbGYuZXh0cmFfcXVlcnlfcGFyYW1zWydhamF4J10pO1xyXG5cclxuICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsnZGF0YV90eXBlJ10gPSBzZWxmLmFqYXhfZGF0YV90eXBlO1xyXG4gICAgICAgIH07XHJcblxyXG5cclxuXHJcbiAgICAgICAgdGhpcy51cGRhdGVMb2FkZXJUYWcgPSBmdW5jdGlvbigkb2JqZWN0KSB7XHJcblxyXG4gICAgICAgICAgICB2YXIgJHBhcmVudDtcclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYuaW5maW5pdGVfc2Nyb2xsX3Jlc3VsdF9jbGFzcyE9XCJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgJHBhcmVudCA9IHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIuZmluZChzZWxmLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MpLmxhc3QoKS5wYXJlbnQoKTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICRwYXJlbnQgPSBzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB2YXIgdGFnTmFtZSA9ICRwYXJlbnQucHJvcChcInRhZ05hbWVcIik7XHJcblxyXG4gICAgICAgICAgICB2YXIgdGFnVHlwZSA9ICdkaXYnO1xyXG4gICAgICAgICAgICBpZiggKCB0YWdOYW1lLnRvTG93ZXJDYXNlKCkgPT0gJ29sJyApIHx8ICggdGFnTmFtZS50b0xvd2VyQ2FzZSgpID09ICd1bCcgKSApe1xyXG4gICAgICAgICAgICAgICAgdGFnVHlwZSA9ICdsaSc7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciAkbmV3ID0gJCgnPCcrdGFnVHlwZSsnIC8+JykuaHRtbCgkb2JqZWN0Lmh0bWwoKSk7XHJcbiAgICAgICAgICAgIHZhciBhdHRyaWJ1dGVzID0gJG9iamVjdC5wcm9wKFwiYXR0cmlidXRlc1wiKTtcclxuXHJcbiAgICAgICAgICAgIC8vIGxvb3AgdGhyb3VnaCA8c2VsZWN0PiBhdHRyaWJ1dGVzIGFuZCBhcHBseSB0aGVtIG9uIDxkaXY+XHJcbiAgICAgICAgICAgICQuZWFjaChhdHRyaWJ1dGVzLCBmdW5jdGlvbigpIHtcclxuICAgICAgICAgICAgICAgICRuZXcuYXR0cih0aGlzLm5hbWUsIHRoaXMudmFsdWUpO1xyXG4gICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgIHJldHVybiAkbmV3O1xyXG5cclxuICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICB0aGlzLmxvYWRNb3JlUmVzdWx0cyA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmICggdGhpcy5pc19tYXhfcGFnZWQgPT09IHRydWUgKSB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgc2VsZi5pc19sb2FkaW5nX21vcmUgPSB0cnVlO1xyXG5cclxuICAgICAgICAgICAgLy90cmlnZ2VyIHN0YXJ0IGV2ZW50XHJcbiAgICAgICAgICAgIHZhciBldmVudF9kYXRhID0ge1xyXG4gICAgICAgICAgICAgICAgc2ZpZDogc2VsZi5zZmlkLFxyXG4gICAgICAgICAgICAgICAgdGFyZ2V0U2VsZWN0b3I6IHNlbGYuYWpheF90YXJnZXRfYXR0cixcclxuICAgICAgICAgICAgICAgIHR5cGU6IFwibG9hZF9tb3JlXCIsXHJcbiAgICAgICAgICAgICAgICBvYmplY3Q6IHNlbGZcclxuICAgICAgICAgICAgfTtcclxuXHJcbiAgICAgICAgICAgIHNlbGYudHJpZ2dlckV2ZW50KFwic2Y6YWpheHN0YXJ0XCIsIGV2ZW50X2RhdGEpO1xyXG4gICAgICAgICAgICBwcm9jZXNzX2Zvcm0uc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmwoc2VsZiwgc2VsZi5yZXN1bHRzX3VybCk7XHJcbiAgICAgICAgICAgIHZhciBxdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyh0cnVlKTtcclxuICAgICAgICAgICAgc2VsZi5sYXN0X3N1Ym1pdF9xdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyhmYWxzZSk7IC8vZ3JhYiBhIGNvcHkgb2YgaHRlIFVSTCBwYXJhbXMgd2l0aG91dCBwYWdpbmF0aW9uIGFscmVhZHkgYWRkZWRcclxuXHJcbiAgICAgICAgICAgIHZhciBhamF4X3Byb2Nlc3NpbmdfdXJsID0gXCJcIjtcclxuICAgICAgICAgICAgdmFyIGFqYXhfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgICAgICB2YXIgZGF0YV90eXBlID0gXCJcIjtcclxuXHJcblxyXG4gICAgICAgICAgICAvL25vdyBhZGQgdGhlIG5ldyBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgIHZhciBuZXh0X3BhZ2VkX251bWJlciA9IHRoaXMuY3VycmVudF9wYWdlZCArIDE7XHJcbiAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgXCJzZl9wYWdlZD1cIituZXh0X3BhZ2VkX251bWJlcik7XHJcblxyXG4gICAgICAgICAgICBzZWxmLnNldEFqYXhSZXN1bHRzVVJMcyhxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICBhamF4X3Byb2Nlc3NpbmdfdXJsID0gc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXTtcclxuICAgICAgICAgICAgYWpheF9yZXN1bHRzX3VybCA9IHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Jlc3VsdHNfdXJsJ107XHJcbiAgICAgICAgICAgIGRhdGFfdHlwZSA9IHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ2RhdGFfdHlwZSddO1xyXG5cclxuICAgICAgICAgICAgLy9hYm9ydCBhbnkgcHJldmlvdXMgYWpheCByZXF1ZXN0c1xyXG4gICAgICAgICAgICBpZihzZWxmLmxhc3RfYWpheF9yZXF1ZXN0KVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0LmFib3J0KCk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYudXNlX3Njcm9sbF9sb2FkZXI9PTEpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciAkbG9hZGVyID0gJCgnPGRpdi8+Jyx7XHJcbiAgICAgICAgICAgICAgICAgICAgJ2NsYXNzJzogJ3NlYXJjaC1maWx0ZXItc2Nyb2xsLWxvYWRpbmcnXHJcbiAgICAgICAgICAgICAgICB9KTsvLy5hcHBlbmRUbyhzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyKTtcclxuXHJcbiAgICAgICAgICAgICAgICAkbG9hZGVyID0gc2VsZi51cGRhdGVMb2FkZXJUYWcoJGxvYWRlcik7XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5pbmZpbml0ZVNjcm9sbEFwcGVuZCgkbG9hZGVyKTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0ID0gJC5nZXQoYWpheF9wcm9jZXNzaW5nX3VybCwgZnVuY3Rpb24oZGF0YSwgc3RhdHVzLCByZXF1ZXN0KVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmN1cnJlbnRfcGFnZWQrKztcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9hamF4X3JlcXVlc3QgPSBudWxsO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vICoqKioqKioqKioqKioqXHJcbiAgICAgICAgICAgICAgICAvLyBUT0RPIC0gUEFTVEUgVEhJUyBBTkQgV0FUQ0ggVEhFIFJFRElSRUNUIC0gT05MWSBIQVBQRU5TIFdJVEggV0MgKENQVCBBTkQgVEFYIERPRVMgTk9UKVxyXG4gICAgICAgICAgICAgICAgLy8gaHR0cHM6Ly9zZWFyY2gtZmlsdGVyLnRlc3QvcHJvZHVjdC1jYXRlZ29yeS9jbG90aGluZy90c2hpcnRzL3BhZ2UvMy8/c2ZfcGFnZWQ9M1xyXG5cclxuICAgICAgICAgICAgICAgIC8vdXBkYXRlcyB0aGUgcmVzdXRscyAmIGZvcm0gaHRtbFxyXG4gICAgICAgICAgICAgICAgc2VsZi5hZGRSZXN1bHRzKGRhdGEsIGRhdGFfdHlwZSk7XHJcblxyXG4gICAgICAgICAgICB9LCBkYXRhX3R5cGUpLmZhaWwoZnVuY3Rpb24oanFYSFIsIHRleHRTdGF0dXMsIGVycm9yVGhyb3duKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgZGF0YSA9IHt9O1xyXG4gICAgICAgICAgICAgICAgZGF0YS5zZmlkID0gc2VsZi5zZmlkO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5vYmplY3QgPSBzZWxmO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50YXJnZXRTZWxlY3RvciA9IHNlbGYuYWpheF90YXJnZXRfYXR0cjtcclxuICAgICAgICAgICAgICAgIGRhdGEuYWpheFVSTCA9IGFqYXhfcHJvY2Vzc2luZ191cmw7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmpxWEhSID0ganFYSFI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRleHRTdGF0dXMgPSB0ZXh0U3RhdHVzO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5lcnJvclRocm93biA9IGVycm9yVGhyb3duO1xyXG4gICAgICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4ZXJyb3JcIiwgZGF0YSk7XHJcblxyXG4gICAgICAgICAgICB9KS5hbHdheXMoZnVuY3Rpb24oKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgZGF0YSA9IHt9O1xyXG4gICAgICAgICAgICAgICAgZGF0YS5zZmlkID0gc2VsZi5zZmlkO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50YXJnZXRTZWxlY3RvciA9IHNlbGYuYWpheF90YXJnZXRfYXR0cjtcclxuICAgICAgICAgICAgICAgIGRhdGEub2JqZWN0ID0gc2VsZjtcclxuXHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLnVzZV9zY3JvbGxfbG9hZGVyPT0xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICRsb2FkZXIuZGV0YWNoKCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5pc19sb2FkaW5nX21vcmUgPSBmYWxzZTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmFqYXhmaW5pc2hcIiwgZGF0YSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICB9XHJcbiAgICAgICAgdGhpcy5mZXRjaEFqYXhSZXN1bHRzID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgLy90cmlnZ2VyIHN0YXJ0IGV2ZW50XHJcbiAgICAgICAgICAgIHZhciBldmVudF9kYXRhID0ge1xyXG4gICAgICAgICAgICAgICAgc2ZpZDogc2VsZi5zZmlkLFxyXG4gICAgICAgICAgICAgICAgdGFyZ2V0U2VsZWN0b3I6IHNlbGYuYWpheF90YXJnZXRfYXR0cixcclxuICAgICAgICAgICAgICAgIHR5cGU6IFwibG9hZF9yZXN1bHRzXCIsXHJcbiAgICAgICAgICAgICAgICBvYmplY3Q6IHNlbGZcclxuICAgICAgICAgICAgfTtcclxuXHJcbiAgICAgICAgICAgIHNlbGYudHJpZ2dlckV2ZW50KFwic2Y6YWpheHN0YXJ0XCIsIGV2ZW50X2RhdGEpO1xyXG5cclxuICAgICAgICAgICAgLy9yZWZvY3VzIGFueSBpbnB1dCBmaWVsZHMgYWZ0ZXIgdGhlIGZvcm0gaGFzIGJlZW4gdXBkYXRlZFxyXG4gICAgICAgICAgICB2YXIgJGxhc3RfYWN0aXZlX2lucHV0X3RleHQgPSAkdGhpcy5maW5kKCdpbnB1dFt0eXBlPVwidGV4dFwiXTpmb2N1cycpLm5vdChcIi5zZi1kYXRlcGlja2VyXCIpO1xyXG4gICAgICAgICAgICBpZigkbGFzdF9hY3RpdmVfaW5wdXRfdGV4dC5sZW5ndGg9PTEpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBsYXN0X2FjdGl2ZV9pbnB1dF90ZXh0ID0gJGxhc3RfYWN0aXZlX2lucHV0X3RleHQuYXR0cihcIm5hbWVcIik7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICR0aGlzLmFkZENsYXNzKFwic2VhcmNoLWZpbHRlci1kaXNhYmxlZFwiKTtcclxuICAgICAgICAgICAgcHJvY2Vzc19mb3JtLmRpc2FibGVJbnB1dHMoc2VsZik7XHJcblxyXG4gICAgICAgICAgICAvL2ZhZGUgb3V0IHJlc3VsdHNcclxuICAgICAgICAgICAgc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5hbmltYXRlKHsgb3BhY2l0eTogMC41IH0sIFwiZmFzdFwiKTsgLy9sb2FkaW5nXHJcbiAgICAgICAgICAgIHNlbGYuZmFkZUNvbnRlbnRBcmVhcyggXCJvdXRcIiApO1xyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi5hamF4X2FjdGlvbj09XCJwYWdpbmF0aW9uXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIC8vbmVlZCB0byByZW1vdmUgYWN0aXZlIGZpbHRlciBmcm9tIFVSTFxyXG5cclxuICAgICAgICAgICAgICAgIC8vcXVlcnlfcGFyYW1zID0gc2VsZi5sYXN0X3N1Ym1pdF9xdWVyeV9wYXJhbXM7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9ub3cgYWRkIHRoZSBuZXcgcGFnaW5hdGlvblxyXG4gICAgICAgICAgICAgICAgdmFyIHBhZ2VOdW1iZXIgPSBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmF0dHIoXCJkYXRhLXBhZ2VkXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHR5cGVvZihwYWdlTnVtYmVyKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBwYWdlTnVtYmVyID0gMTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5zZXRUYXhBcmNoaXZlUmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKGZhbHNlKTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZihwYWdlTnVtYmVyPjEpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5qb2luVXJsUGFyYW0ocXVlcnlfcGFyYW1zLCBcInNmX3BhZ2VkPVwiK3BhZ2VOdW1iZXIpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHNlbGYuYWpheF9hY3Rpb249PVwic3VibWl0XCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBxdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyh0cnVlKTtcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9zdWJtaXRfcXVlcnlfcGFyYW1zID0gc2VsZi5nZXRVcmxQYXJhbXMoZmFsc2UpOyAvL2dyYWIgYSBjb3B5IG9mIGh0ZSBVUkwgcGFyYW1zIHdpdGhvdXQgcGFnaW5hdGlvbiBhbHJlYWR5IGFkZGVkXHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBhamF4X3Byb2Nlc3NpbmdfdXJsID0gXCJcIjtcclxuICAgICAgICAgICAgdmFyIGFqYXhfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgICAgICB2YXIgZGF0YV90eXBlID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIHNlbGYuc2V0QWpheFJlc3VsdHNVUkxzKHF1ZXJ5X3BhcmFtcyk7XHJcbiAgICAgICAgICAgIGFqYXhfcHJvY2Vzc2luZ191cmwgPSBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydwcm9jZXNzaW5nX3VybCddO1xyXG4gICAgICAgICAgICBhamF4X3Jlc3VsdHNfdXJsID0gc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncmVzdWx0c191cmwnXTtcclxuICAgICAgICAgICAgZGF0YV90eXBlID0gc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsnZGF0YV90eXBlJ107XHJcblxyXG5cclxuICAgICAgICAgICAgLy9hYm9ydCBhbnkgcHJldmlvdXMgYWpheCByZXF1ZXN0c1xyXG4gICAgICAgICAgICBpZihzZWxmLmxhc3RfYWpheF9yZXF1ZXN0KVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0LmFib3J0KCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgdmFyIGFqYXhfYWN0aW9uID0gc2VsZi5hamF4X2FjdGlvbjtcclxuICAgICAgICAgICAgc2VsZi5sYXN0X2FqYXhfcmVxdWVzdCA9ICQuZ2V0KGFqYXhfcHJvY2Vzc2luZ191cmwsIGZ1bmN0aW9uKGRhdGEsIHN0YXR1cywgcmVxdWVzdClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X2FqYXhfcmVxdWVzdCA9IG51bGw7XHJcblxyXG4gICAgICAgICAgICAgICAgLy91cGRhdGVzIHRoZSByZXN1dGxzICYgZm9ybSBodG1sXHJcbiAgICAgICAgICAgICAgICBzZWxmLnVwZGF0ZVJlc3VsdHMoZGF0YSwgZGF0YV90eXBlKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvLyBzY3JvbGwgXHJcbiAgICAgICAgICAgICAgICAvLyBzZXQgdGhlIHZhciBiYWNrIHRvIHdoYXQgaXQgd2FzIGJlZm9yZSB0aGUgYWpheCByZXF1ZXN0IG5hZCB0aGUgZm9ybSByZS1pbml0XHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfYWN0aW9uID0gYWpheF9hY3Rpb247XHJcbiAgICAgICAgICAgICAgICBzZWxmLnNjcm9sbFJlc3VsdHMoIHNlbGYuYWpheF9hY3Rpb24gKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvKiB1cGRhdGUgVVJMICovXHJcbiAgICAgICAgICAgICAgICAvL3VwZGF0ZSB1cmwgYmVmb3JlIHBhZ2luYXRpb24sIGJlY2F1c2Ugd2UgbmVlZCB0byBkbyBzb21lIGNoZWNrcyBhZ2FpbnMgdGhlIFVSTCBmb3IgaW5maW5pdGUgc2Nyb2xsXHJcbiAgICAgICAgICAgICAgICBzZWxmLnVwZGF0ZVVybEhpc3RvcnkoYWpheF9yZXN1bHRzX3VybCk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9zZXR1cCBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgICAgICBzZWxmLnNldHVwQWpheFBhZ2luYXRpb24oKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmlzU3VibWl0dGluZyA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgICAgIC8qIHVzZXIgZGVmICovXHJcbiAgICAgICAgICAgICAgICBzZWxmLmluaXRXb29Db21tZXJjZUNvbnRyb2xzKCk7IC8vd29vY29tbWVyY2Ugb3JkZXJieVxyXG5cclxuXHJcbiAgICAgICAgICAgIH0sIGRhdGFfdHlwZSkuZmFpbChmdW5jdGlvbihqcVhIUiwgdGV4dFN0YXR1cywgZXJyb3JUaHJvd24pXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBkYXRhID0ge307XHJcbiAgICAgICAgICAgICAgICBkYXRhLnNmaWQgPSBzZWxmLnNmaWQ7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRhcmdldFNlbGVjdG9yID0gc2VsZi5hamF4X3RhcmdldF9hdHRyO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5vYmplY3QgPSBzZWxmO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5hamF4VVJMID0gYWpheF9wcm9jZXNzaW5nX3VybDtcclxuICAgICAgICAgICAgICAgIGRhdGEuanFYSFIgPSBqcVhIUjtcclxuICAgICAgICAgICAgICAgIGRhdGEudGV4dFN0YXR1cyA9IHRleHRTdGF0dXM7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmVycm9yVGhyb3duID0gZXJyb3JUaHJvd247XHJcbiAgICAgICAgICAgICAgICBzZWxmLmlzU3VibWl0dGluZyA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4ZXJyb3JcIiwgZGF0YSk7XHJcblxyXG4gICAgICAgICAgICB9KS5hbHdheXMoZnVuY3Rpb24oKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLnN0b3AodHJ1ZSx0cnVlKS5hbmltYXRlKHsgb3BhY2l0eTogMX0sIFwiZmFzdFwiKTsgLy9maW5pc2hlZCBsb2FkaW5nXHJcbiAgICAgICAgICAgICAgICBzZWxmLmZhZGVDb250ZW50QXJlYXMoIFwiaW5cIiApO1xyXG4gICAgICAgICAgICAgICAgdmFyIGRhdGEgPSB7fTtcclxuICAgICAgICAgICAgICAgIGRhdGEuc2ZpZCA9IHNlbGYuc2ZpZDtcclxuICAgICAgICAgICAgICAgIGRhdGEudGFyZ2V0U2VsZWN0b3IgPSBzZWxmLmFqYXhfdGFyZ2V0X2F0dHI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLm9iamVjdCA9IHNlbGY7XHJcbiAgICAgICAgICAgICAgICAkdGhpcy5yZW1vdmVDbGFzcyhcInNlYXJjaC1maWx0ZXItZGlzYWJsZWRcIik7XHJcbiAgICAgICAgICAgICAgICBwcm9jZXNzX2Zvcm0uZW5hYmxlSW5wdXRzKHNlbGYpO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vcmVmb2N1cyB0aGUgbGFzdCBhY3RpdmUgdGV4dCBmaWVsZFxyXG4gICAgICAgICAgICAgICAgaWYobGFzdF9hY3RpdmVfaW5wdXRfdGV4dCE9XCJcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGlucHV0ID0gW107XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi4kZmllbGRzLmVhY2goZnVuY3Rpb24oKXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkYWN0aXZlX2lucHV0ID0gJCh0aGlzKS5maW5kKFwiaW5wdXRbbmFtZT0nXCIrbGFzdF9hY3RpdmVfaW5wdXRfdGV4dCtcIiddXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigkYWN0aXZlX2lucHV0Lmxlbmd0aD09MSlcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGlucHV0ID0gJGFjdGl2ZV9pbnB1dDtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICB9KTtcclxuICAgICAgICAgICAgICAgICAgICBpZigkaW5wdXQubGVuZ3RoPT0xKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkaW5wdXQuZm9jdXMoKS52YWwoJGlucHV0LnZhbCgpKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5mb2N1c0NhbXBvKCRpbnB1dFswXSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICR0aGlzLmZpbmQoXCJpbnB1dFtuYW1lPSdfc2Zfc2VhcmNoJ11cIikudHJpZ2dlcignZm9jdXMnKTtcclxuICAgICAgICAgICAgICAgIHNlbGYudHJpZ2dlckV2ZW50KFwic2Y6YWpheGZpbmlzaFwiLCAgZGF0YSApO1xyXG5cclxuICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5mb2N1c0NhbXBvID0gZnVuY3Rpb24oaW5wdXRGaWVsZCl7XHJcbiAgICAgICAgICAgIC8vdmFyIGlucHV0RmllbGQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChpZCk7XHJcbiAgICAgICAgICAgIGlmIChpbnB1dEZpZWxkICE9IG51bGwgJiYgaW5wdXRGaWVsZC52YWx1ZS5sZW5ndGggIT0gMCl7XHJcbiAgICAgICAgICAgICAgICBpZiAoaW5wdXRGaWVsZC5jcmVhdGVUZXh0UmFuZ2Upe1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBGaWVsZFJhbmdlID0gaW5wdXRGaWVsZC5jcmVhdGVUZXh0UmFuZ2UoKTtcclxuICAgICAgICAgICAgICAgICAgICBGaWVsZFJhbmdlLm1vdmVTdGFydCgnY2hhcmFjdGVyJyxpbnB1dEZpZWxkLnZhbHVlLmxlbmd0aCk7XHJcbiAgICAgICAgICAgICAgICAgICAgRmllbGRSYW5nZS5jb2xsYXBzZSgpO1xyXG4gICAgICAgICAgICAgICAgICAgIEZpZWxkUmFuZ2Uuc2VsZWN0KCk7XHJcbiAgICAgICAgICAgICAgICB9ZWxzZSBpZiAoaW5wdXRGaWVsZC5zZWxlY3Rpb25TdGFydCB8fCBpbnB1dEZpZWxkLnNlbGVjdGlvblN0YXJ0ID09ICcwJykge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBlbGVtTGVuID0gaW5wdXRGaWVsZC52YWx1ZS5sZW5ndGg7XHJcbiAgICAgICAgICAgICAgICAgICAgaW5wdXRGaWVsZC5zZWxlY3Rpb25TdGFydCA9IGVsZW1MZW47XHJcbiAgICAgICAgICAgICAgICAgICAgaW5wdXRGaWVsZC5zZWxlY3Rpb25FbmQgPSBlbGVtTGVuO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgaW5wdXRGaWVsZC5ibHVyKCk7XHJcbiAgICAgICAgICAgICAgICBpbnB1dEZpZWxkLmZvY3VzKCk7XHJcbiAgICAgICAgICAgIH0gZWxzZXtcclxuICAgICAgICAgICAgICAgIGlmICggaW5wdXRGaWVsZCApIHtcclxuICAgICAgICAgICAgICAgICAgICBpbnB1dEZpZWxkLmZvY3VzKCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy50cmlnZ2VyRXZlbnQgPSBmdW5jdGlvbihldmVudG5hbWUsIGRhdGEpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgJGV2ZW50X2NvbnRhaW5lciA9ICQoXCIuc2VhcmNoYW5kZmlsdGVyW2RhdGEtc2YtZm9ybS1pZD0nXCIrc2VsZi5zZmlkK1wiJ11cIik7XHJcbiAgICAgICAgICAgICRldmVudF9jb250YWluZXIudHJpZ2dlcihldmVudG5hbWUsIFsgZGF0YSBdKTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuZmV0Y2hBamF4Rm9ybSA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIC8vdHJpZ2dlciBzdGFydCBldmVudFxyXG4gICAgICAgICAgICB2YXIgZXZlbnRfZGF0YSA9IHtcclxuICAgICAgICAgICAgICAgIHNmaWQ6IHNlbGYuc2ZpZCxcclxuICAgICAgICAgICAgICAgIHRhcmdldFNlbGVjdG9yOiBzZWxmLmFqYXhfdGFyZ2V0X2F0dHIsXHJcbiAgICAgICAgICAgICAgICB0eXBlOiBcImZvcm1cIixcclxuICAgICAgICAgICAgICAgIG9iamVjdDogc2VsZlxyXG4gICAgICAgICAgICB9O1xyXG5cclxuICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4Zm9ybXN0YXJ0XCIsIFsgZXZlbnRfZGF0YSBdKTtcclxuXHJcbiAgICAgICAgICAgICR0aGlzLmFkZENsYXNzKFwic2VhcmNoLWZpbHRlci1kaXNhYmxlZFwiKTtcclxuICAgICAgICAgICAgcHJvY2Vzc19mb3JtLmRpc2FibGVJbnB1dHMoc2VsZik7XHJcblxyXG4gICAgICAgICAgICB2YXIgcXVlcnlfcGFyYW1zID0gc2VsZi5nZXRVcmxQYXJhbXMoKTtcclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYubGFuZ19jb2RlIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAvL3NvIGFkZCBpdFxyXG4gICAgICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5qb2luVXJsUGFyYW0ocXVlcnlfcGFyYW1zLCBcImxhbmc9XCIrc2VsZi5sYW5nX2NvZGUpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB2YXIgYWpheF9wcm9jZXNzaW5nX3VybCA9IHNlbGYuYWRkVXJsUGFyYW0oc2VsZi5hamF4X2Zvcm1fdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICB2YXIgZGF0YV90eXBlID0gXCJqc29uXCI7XHJcblxyXG5cclxuICAgICAgICAgICAgLy9hYm9ydCBhbnkgcHJldmlvdXMgYWpheCByZXF1ZXN0c1xyXG4gICAgICAgICAgICAvKmlmKHNlbGYubGFzdF9hamF4X3JlcXVlc3QpXHJcbiAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0LmFib3J0KCk7XHJcbiAgICAgICAgICAgICB9Ki9cclxuXHJcblxyXG4gICAgICAgICAgICAvL3NlbGYubGFzdF9hamF4X3JlcXVlc3QgPVxyXG5cclxuICAgICAgICAgICAgJC5nZXQoYWpheF9wcm9jZXNzaW5nX3VybCwgZnVuY3Rpb24oZGF0YSwgc3RhdHVzLCByZXF1ZXN0KVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAvL3NlbGYubGFzdF9hamF4X3JlcXVlc3QgPSBudWxsO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vdXBkYXRlcyB0aGUgcmVzdXRscyAmIGZvcm0gaHRtbFxyXG4gICAgICAgICAgICAgICAgc2VsZi51cGRhdGVGb3JtKGRhdGEsIGRhdGFfdHlwZSk7XHJcblxyXG5cclxuICAgICAgICAgICAgfSwgZGF0YV90eXBlKS5mYWlsKGZ1bmN0aW9uKGpxWEhSLCB0ZXh0U3RhdHVzLCBlcnJvclRocm93bilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGRhdGEgPSB7fTtcclxuICAgICAgICAgICAgICAgIGRhdGEuc2ZpZCA9IHNlbGYuc2ZpZDtcclxuICAgICAgICAgICAgICAgIGRhdGEudGFyZ2V0U2VsZWN0b3IgPSBzZWxmLmFqYXhfdGFyZ2V0X2F0dHI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLm9iamVjdCA9IHNlbGY7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmFqYXhVUkwgPSBhamF4X3Byb2Nlc3NpbmdfdXJsO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5qcVhIUiA9IGpxWEhSO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50ZXh0U3RhdHVzID0gdGV4dFN0YXR1cztcclxuICAgICAgICAgICAgICAgIGRhdGEuZXJyb3JUaHJvd24gPSBlcnJvclRocm93bjtcclxuICAgICAgICAgICAgICAgIHNlbGYudHJpZ2dlckV2ZW50KFwic2Y6YWpheGVycm9yXCIsIFsgZGF0YSBdKTtcclxuXHJcbiAgICAgICAgICAgIH0pLmFsd2F5cyhmdW5jdGlvbigpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBkYXRhID0ge307XHJcbiAgICAgICAgICAgICAgICBkYXRhLnNmaWQgPSBzZWxmLnNmaWQ7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRhcmdldFNlbGVjdG9yID0gc2VsZi5hamF4X3RhcmdldF9hdHRyO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5vYmplY3QgPSBzZWxmO1xyXG5cclxuICAgICAgICAgICAgICAgICR0aGlzLnJlbW92ZUNsYXNzKFwic2VhcmNoLWZpbHRlci1kaXNhYmxlZFwiKTtcclxuICAgICAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5lbmFibGVJbnB1dHMoc2VsZik7XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4Zm9ybWZpbmlzaFwiLCBbIGRhdGEgXSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuY29weUxpc3RJdGVtc0NvbnRlbnRzID0gZnVuY3Rpb24oJGxpc3RfZnJvbSwgJGxpc3RfdG8pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAvL2NvcHkgb3ZlciBjaGlsZCBsaXN0IGl0ZW1zXHJcbiAgICAgICAgICAgIHZhciBsaV9jb250ZW50c19hcnJheSA9IG5ldyBBcnJheSgpO1xyXG4gICAgICAgICAgICB2YXIgZnJvbV9hdHRyaWJ1dGVzID0gbmV3IEFycmF5KCk7XHJcblxyXG4gICAgICAgICAgICB2YXIgJGZyb21fZmllbGRzID0gJGxpc3RfZnJvbS5maW5kKFwiPiB1bCA+IGxpXCIpO1xyXG5cclxuICAgICAgICAgICAgJGZyb21fZmllbGRzLmVhY2goZnVuY3Rpb24oaSl7XHJcblxyXG4gICAgICAgICAgICAgICAgbGlfY29udGVudHNfYXJyYXkucHVzaCgkKHRoaXMpLmh0bWwoKSk7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZXMgPSAkKHRoaXMpLnByb3AoXCJhdHRyaWJ1dGVzXCIpO1xyXG4gICAgICAgICAgICAgICAgZnJvbV9hdHRyaWJ1dGVzLnB1c2goYXR0cmlidXRlcyk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy92YXIgZmllbGRfbmFtZSA9ICQodGhpcykuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuICAgICAgICAgICAgICAgIC8vdmFyIHRvX2ZpZWxkID0gJGxpc3RfdG8uZmluZChcIj4gdWwgPiBsaVtkYXRhLXNmLWZpZWxkLW5hbWU9J1wiK2ZpZWxkX25hbWUrXCInXVwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL3NlbGYuY29weUF0dHJpYnV0ZXMoJCh0aGlzKSwgJGxpc3RfdG8sIFwiZGF0YS1zZi1cIik7XHJcblxyXG4gICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgIHZhciBsaV9pdCA9IDA7XHJcbiAgICAgICAgICAgIHZhciAkdG9fZmllbGRzID0gJGxpc3RfdG8uZmluZChcIj4gdWwgPiBsaVwiKTtcclxuICAgICAgICAgICAgJHRvX2ZpZWxkcy5lYWNoKGZ1bmN0aW9uKGkpe1xyXG4gICAgICAgICAgICAgICAgJCh0aGlzKS5odG1sKGxpX2NvbnRlbnRzX2FycmF5W2xpX2l0XSk7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICRmcm9tX2ZpZWxkID0gJCgkZnJvbV9maWVsZHMuZ2V0KGxpX2l0KSk7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICR0b19maWVsZCA9ICQodGhpcyk7XHJcbiAgICAgICAgICAgICAgICAkdG9fZmllbGQucmVtb3ZlQXR0cihcImRhdGEtc2YtdGF4b25vbXktYXJjaGl2ZVwiKTtcclxuICAgICAgICAgICAgICAgIHNlbGYuY29weUF0dHJpYnV0ZXMoJGZyb21fZmllbGQsICR0b19maWVsZCk7XHJcblxyXG4gICAgICAgICAgICAgICAgbGlfaXQrKztcclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAvKnZhciAkZnJvbV9maWVsZHMgPSAkbGlzdF9mcm9tLmZpbmQoXCIgdWwgPiBsaVwiKTtcclxuICAgICAgICAgICAgIHZhciAkdG9fZmllbGRzID0gJGxpc3RfdG8uZmluZChcIiA+IGxpXCIpO1xyXG4gICAgICAgICAgICAgJGZyb21fZmllbGRzLmVhY2goZnVuY3Rpb24oaW5kZXgsIHZhbCl7XHJcbiAgICAgICAgICAgICBpZigkKHRoaXMpLmhhc0F0dHJpYnV0ZShcImRhdGEtc2YtdGF4b25vbXktYXJjaGl2ZVwiKSlcclxuICAgICAgICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICB0aGlzLmNvcHlBdHRyaWJ1dGVzKCRsaXN0X2Zyb20sICRsaXN0X3RvKTsqL1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy51cGRhdGVGb3JtQXR0cmlidXRlcyA9IGZ1bmN0aW9uKCRsaXN0X2Zyb20sICRsaXN0X3RvKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyIGZyb21fYXR0cmlidXRlcyA9ICRsaXN0X2Zyb20ucHJvcChcImF0dHJpYnV0ZXNcIik7XHJcbiAgICAgICAgICAgIC8vIGxvb3AgdGhyb3VnaCA8c2VsZWN0PiBhdHRyaWJ1dGVzIGFuZCBhcHBseSB0aGVtIG9uIDxkaXY+XHJcblxyXG4gICAgICAgICAgICB2YXIgdG9fYXR0cmlidXRlcyA9ICRsaXN0X3RvLnByb3AoXCJhdHRyaWJ1dGVzXCIpO1xyXG4gICAgICAgICAgICAkLmVhY2godG9fYXR0cmlidXRlcywgZnVuY3Rpb24oKSB7XHJcbiAgICAgICAgICAgICAgICAkbGlzdF90by5yZW1vdmVBdHRyKHRoaXMubmFtZSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgJC5lYWNoKGZyb21fYXR0cmlidXRlcywgZnVuY3Rpb24oKSB7XHJcbiAgICAgICAgICAgICAgICAkbGlzdF90by5hdHRyKHRoaXMubmFtZSwgdGhpcy52YWx1ZSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuY29weUF0dHJpYnV0ZXMgPSBmdW5jdGlvbigkZnJvbSwgJHRvLCBwcmVmaXgpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2YocHJlZml4KT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIHByZWZpeCA9IFwiXCI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBmcm9tX2F0dHJpYnV0ZXMgPSAkZnJvbS5wcm9wKFwiYXR0cmlidXRlc1wiKTtcclxuXHJcbiAgICAgICAgICAgIHZhciB0b19hdHRyaWJ1dGVzID0gJHRvLnByb3AoXCJhdHRyaWJ1dGVzXCIpO1xyXG4gICAgICAgICAgICAkLmVhY2godG9fYXR0cmlidXRlcywgZnVuY3Rpb24oKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYocHJlZml4IT1cIlwiKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKHRoaXMubmFtZS5pbmRleE9mKHByZWZpeCkgPT0gMCkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkdG8ucmVtb3ZlQXR0cih0aGlzLm5hbWUpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvLyR0by5yZW1vdmVBdHRyKHRoaXMubmFtZSk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgJC5lYWNoKGZyb21fYXR0cmlidXRlcywgZnVuY3Rpb24oKSB7XHJcbiAgICAgICAgICAgICAgICAkdG8uYXR0cih0aGlzLm5hbWUsIHRoaXMudmFsdWUpO1xyXG4gICAgICAgICAgICB9KTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuY29weUZvcm1BdHRyaWJ1dGVzID0gZnVuY3Rpb24oJGZyb20sICR0bylcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgICR0by5yZW1vdmVBdHRyKFwiZGF0YS1jdXJyZW50LXRheG9ub215LWFyY2hpdmVcIik7XHJcbiAgICAgICAgICAgIHRoaXMuY29weUF0dHJpYnV0ZXMoJGZyb20sICR0byk7XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy51cGRhdGVGb3JtID0gZnVuY3Rpb24oZGF0YSwgZGF0YV90eXBlKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYoZGF0YV90eXBlPT1cImpzb25cIilcclxuICAgICAgICAgICAgey8vdGhlbiB3ZSBkaWQgYSByZXF1ZXN0IHRvIHRoZSBhamF4IGVuZHBvaW50LCBzbyBleHBlY3QgYW4gb2JqZWN0IGJhY2tcclxuXHJcbiAgICAgICAgICAgICAgICBpZih0eXBlb2YoZGF0YVsnZm9ybSddKSE9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZW1vdmUgYWxsIGV2ZW50cyBmcm9tIFMmRiBmb3JtXHJcbiAgICAgICAgICAgICAgICAgICAgJHRoaXMub2ZmKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vcmVmcmVzaCB0aGUgZm9ybSAoYXV0byBjb3VudClcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLmNvcHlMaXN0SXRlbXNDb250ZW50cygkKGRhdGFbJ2Zvcm0nXSksICR0aGlzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZSBpbml0IFMmRiBjbGFzcyBvbiB0aGUgZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgIC8vJHRoaXMuc2VhcmNoQW5kRmlsdGVyKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vaWYgYWpheCBpcyBlbmFibGVkIGluaXQgdGhlIHBhZ2luYXRpb25cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5pbml0KHRydWUpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZihzZWxmLmlzX2FqYXg9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLnNldHVwQWpheFBhZ2luYXRpb24oKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLmFkZFJlc3VsdHMgPSBmdW5jdGlvbihkYXRhLCBkYXRhX3R5cGUpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZihkYXRhX3R5cGU9PVwianNvblwiKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIGRpZCBhIHJlcXVlc3QgdG8gdGhlIGFqYXggZW5kcG9pbnQsIHNvIGV4cGVjdCBhbiBvYmplY3QgYmFja1xyXG4gICAgICAgICAgICAgICAgLy9ncmFiIHRoZSByZXN1bHRzIGFuZCBsb2FkIGluXHJcbiAgICAgICAgICAgICAgICAvL3NlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuYXBwZW5kKGRhdGFbJ3Jlc3VsdHMnXSk7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxvYWRfbW9yZV9odG1sID0gZGF0YVsncmVzdWx0cyddO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoZGF0YV90eXBlPT1cImh0bWxcIilcclxuICAgICAgICAgICAgey8vd2UgYXJlIGV4cGVjdGluZyB0aGUgaHRtbCBvZiB0aGUgcmVzdWx0cyBwYWdlIGJhY2ssIHNvIGV4dHJhY3QgdGhlIGh0bWwgd2UgbmVlZFxyXG5cclxuICAgICAgICAgICAgICAgIHZhciAkZGF0YV9vYmogPSAkKGRhdGEpO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5hcHBlbmQoJGRhdGFfb2JqLmZpbmQoc2VsZi5hamF4X3RhcmdldF9hdHRyKS5odG1sKCkpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sb2FkX21vcmVfaHRtbCA9ICRkYXRhX29iai5maW5kKHNlbGYuYWpheF90YXJnZXRfYXR0cikuaHRtbCgpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB2YXIgaW5maW5pdGVfc2Nyb2xsX2VuZCA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgaWYoJChcIjxkaXY+XCIrc2VsZi5sb2FkX21vcmVfaHRtbCtcIjwvZGl2PlwiKS5maW5kKFwiW2RhdGEtc2VhcmNoLWZpbHRlci1hY3Rpb249J2luZmluaXRlLXNjcm9sbC1lbmQnXVwiKS5sZW5ndGg+MClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgaW5maW5pdGVfc2Nyb2xsX2VuZCA9IHRydWU7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIC8vaWYgdGhlcmUgaXMgYW5vdGhlciBzZWxlY3RvciBmb3IgaW5maW5pdGUgc2Nyb2xsLCBmaW5kIHRoZSBjb250ZW50cyBvZiB0aGF0IGluc3RlYWRcclxuICAgICAgICAgICAgaWYoc2VsZi5pbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxvYWRfbW9yZV9odG1sID0gJChcIjxkaXY+XCIrc2VsZi5sb2FkX21vcmVfaHRtbCtcIjwvZGl2PlwiKS5maW5kKHNlbGYuaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lcikuaHRtbCgpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGlmKHNlbGYuaW5maW5pdGVfc2Nyb2xsX3Jlc3VsdF9jbGFzcyE9XCJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyICRyZXN1bHRfaXRlbXMgPSAkKFwiPGRpdj5cIitzZWxmLmxvYWRfbW9yZV9odG1sK1wiPC9kaXY+XCIpLmZpbmQoc2VsZi5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzKTtcclxuICAgICAgICAgICAgICAgIHZhciAkcmVzdWx0X2l0ZW1zX2NvbnRhaW5lciA9ICQoJzxkaXYvPicsIHt9KTtcclxuICAgICAgICAgICAgICAgICRyZXN1bHRfaXRlbXNfY29udGFpbmVyLmFwcGVuZCgkcmVzdWx0X2l0ZW1zKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmxvYWRfbW9yZV9odG1sID0gJHJlc3VsdF9pdGVtc19jb250YWluZXIuaHRtbCgpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZihpbmZpbml0ZV9zY3JvbGxfZW5kKVxyXG4gICAgICAgICAgICB7Ly93ZSBmb3VuZCBhIGRhdGEgYXR0cmlidXRlIHNpZ25hbGxpbmcgdGhlIGxhc3QgcGFnZSBzbyBmaW5pc2ggaGVyZVxyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuaXNfbWF4X3BhZ2VkID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9sb2FkX21vcmVfaHRtbCA9IHNlbGYubG9hZF9tb3JlX2h0bWw7XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5pbmZpbml0ZVNjcm9sbEFwcGVuZChzZWxmLmxvYWRfbW9yZV9odG1sKTtcclxuXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihzZWxmLmxhc3RfbG9hZF9tb3JlX2h0bWwhPT1zZWxmLmxvYWRfbW9yZV9odG1sKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAvL2NoZWNrIHRvIG1ha2Ugc3VyZSB0aGUgbmV3IGh0bWwgZmV0Y2hlZCBpcyBkaWZmZXJlbnRcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9sb2FkX21vcmVfaHRtbCA9IHNlbGYubG9hZF9tb3JlX2h0bWw7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmluZmluaXRlU2Nyb2xsQXBwZW5kKHNlbGYubG9hZF9tb3JlX2h0bWwpO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHsvL3dlIHJlY2VpdmVkIHRoZSBzYW1lIG1lc3NhZ2UgYWdhaW4gc28gZG9uJ3QgYWRkLCBhbmQgdGVsbCBTJkYgdGhhdCB3ZSdyZSBhdCB0aGUgZW5kLi5cclxuICAgICAgICAgICAgICAgIHNlbGYuaXNfbWF4X3BhZ2VkID0gdHJ1ZTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgIHRoaXMuaW5maW5pdGVTY3JvbGxBcHBlbmQgPSBmdW5jdGlvbigkb2JqZWN0KVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYoc2VsZi5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLmZpbmQoc2VsZi5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzKS5sYXN0KCkuYWZ0ZXIoJG9iamVjdCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgIHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIuYXBwZW5kKCRvYmplY3QpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgdGhpcy51cGRhdGVSZXN1bHRzID0gZnVuY3Rpb24oZGF0YSwgZGF0YV90eXBlKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYoZGF0YV90eXBlPT1cImpzb25cIilcclxuICAgICAgICAgICAgey8vdGhlbiB3ZSBkaWQgYSByZXF1ZXN0IHRvIHRoZSBhamF4IGVuZHBvaW50LCBzbyBleHBlY3QgYW4gb2JqZWN0IGJhY2tcclxuICAgICAgICAgICAgICAgIC8vZ3JhYiB0aGUgcmVzdWx0cyBhbmQgbG9hZCBpblxyXG4gICAgICAgICAgICAgICAgdGhpcy5yZXN1bHRzX2h0bWwgPSBkYXRhWydyZXN1bHRzJ107XHJcblxyXG4gICAgICAgICAgICAgICAgaWYgKCB0aGlzLnJlcGxhY2VfcmVzdWx0cyApIHtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmh0bWwodGhpcy5yZXN1bHRzX2h0bWwpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKHR5cGVvZihkYXRhWydmb3JtJ10pIT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvL3JlbW92ZSBhbGwgZXZlbnRzIGZyb20gUyZGIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICAkdGhpcy5vZmYoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZW1vdmUgcGFnaW5hdGlvblxyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYucmVtb3ZlQWpheFBhZ2luYXRpb24oKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZWZyZXNoIHRoZSBmb3JtIChhdXRvIGNvdW50KVxyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuY29weUxpc3RJdGVtc0NvbnRlbnRzKCQoZGF0YVsnZm9ybSddKSwgJHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvL3VwZGF0ZSBhdHRyaWJ1dGVzIG9uIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICBzZWxmLmNvcHlGb3JtQXR0cmlidXRlcygkKGRhdGFbJ2Zvcm0nXSksICR0aGlzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZSBpbml0IFMmRiBjbGFzcyBvbiB0aGUgZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgICR0aGlzLnNlYXJjaEFuZEZpbHRlcih7J2lzSW5pdCc6IGZhbHNlfSk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgLy8kdGhpcy5maW5kKFwiaW5wdXRcIikucmVtb3ZlQXR0cihcImRpc2FibGVkXCIpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoZGF0YV90eXBlPT1cImh0bWxcIikgey8vd2UgYXJlIGV4cGVjdGluZyB0aGUgaHRtbCBvZiB0aGUgcmVzdWx0cyBwYWdlIGJhY2ssIHNvIGV4dHJhY3QgdGhlIGh0bWwgd2UgbmVlZFxyXG5cclxuICAgICAgICAgICAgICAgIHZhciAkZGF0YV9vYmogPSAkKGRhdGEpO1xyXG5cclxuICAgICAgICAgICAgICAgIHRoaXMucmVzdWx0c19odG1sID0gJGRhdGFfb2JqLmZpbmQoIHRoaXMuYWpheF90YXJnZXRfYXR0ciApLmh0bWwoKTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZiAoIHRoaXMucmVwbGFjZV9yZXN1bHRzICkge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuaHRtbCh0aGlzLnJlc3VsdHNfaHRtbCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi51cGRhdGVDb250ZW50QXJlYXMoICRkYXRhX29iaiApO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmIChzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmZpbmQoXCIuc2VhcmNoYW5kZmlsdGVyXCIpLmxlbmd0aCA+IDApXHJcbiAgICAgICAgICAgICAgICB7Ly90aGVuIHRoZXJlIGFyZSBzZWFyY2ggZm9ybShzKSBpbnNpZGUgdGhlIHJlc3VsdHMgY29udGFpbmVyLCBzbyByZS1pbml0IHRoZW1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5maW5kKFwiLnNlYXJjaGFuZGZpbHRlclwiKS5zZWFyY2hBbmRGaWx0ZXIoKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAvL2lmIHRoZSBjdXJyZW50IHNlYXJjaCBmb3JtIGlzIG5vdCBpbnNpZGUgdGhlIHJlc3VsdHMgY29udGFpbmVyLCB0aGVuIHByb2NlZWQgYXMgbm9ybWFsIGFuZCB1cGRhdGUgdGhlIGZvcm1cclxuICAgICAgICAgICAgICAgIGlmKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuZmluZChcIi5zZWFyY2hhbmRmaWx0ZXJbZGF0YS1zZi1mb3JtLWlkPSdcIiArIHNlbGYuc2ZpZCArIFwiJ11cIikubGVuZ3RoPT0wKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkbmV3X3NlYXJjaF9mb3JtID0gJGRhdGFfb2JqLmZpbmQoXCIuc2VhcmNoYW5kZmlsdGVyW2RhdGEtc2YtZm9ybS1pZD0nXCIgKyBzZWxmLnNmaWQgKyBcIiddXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZiAoJG5ld19zZWFyY2hfZm9ybS5sZW5ndGggPT0gMSkgey8vdGhlbiByZXBsYWNlIHRoZSBzZWFyY2ggZm9ybSB3aXRoIHRoZSBuZXcgb25lXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3JlbW92ZSBhbGwgZXZlbnRzIGZyb20gUyZGIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICAgICAgJHRoaXMub2ZmKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3JlbW92ZSBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYucmVtb3ZlQWpheFBhZ2luYXRpb24oKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vcmVmcmVzaCB0aGUgZm9ybSAoYXV0byBjb3VudClcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5jb3B5TGlzdEl0ZW1zQ29udGVudHMoJG5ld19zZWFyY2hfZm9ybSwgJHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy91cGRhdGUgYXR0cmlidXRlcyBvbiBmb3JtXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuY29weUZvcm1BdHRyaWJ1dGVzKCRuZXdfc2VhcmNoX2Zvcm0sICR0aGlzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vcmUgaW5pdCBTJkYgY2xhc3Mgb24gdGhlIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICAgICAgJHRoaXMuc2VhcmNoQW5kRmlsdGVyKHsnaXNJbml0JzogZmFsc2V9KTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2Uge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy8kdGhpcy5maW5kKFwiaW5wdXRcIikucmVtb3ZlQXR0cihcImRpc2FibGVkXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgc2VsZi5pc19tYXhfcGFnZWQgPSBmYWxzZTsgLy9mb3IgaW5maW5pdGUgc2Nyb2xsXHJcbiAgICAgICAgICAgIHNlbGYuY3VycmVudF9wYWdlZCA9IDE7IC8vZm9yIGluZmluaXRlIHNjcm9sbFxyXG4gICAgICAgICAgICBzZWxmLnNldEluZmluaXRlU2Nyb2xsQ29udGFpbmVyKCk7XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy51cGRhdGVDb250ZW50QXJlYXMgPSBmdW5jdGlvbiggJGh0bWxfZGF0YSApIHtcclxuICAgICAgICAgICAgXHJcbiAgICAgICAgICAgIC8vIGFkZCBhZGRpdGlvbmFsIGNvbnRlbnQgYXJlYXNcclxuICAgICAgICAgICAgaWYgKCB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zICYmIHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnMubGVuZ3RoICkge1xyXG4gICAgICAgICAgICAgICAgZm9yIChpbmRleCA9IDA7IGluZGV4IDwgdGhpcy5hamF4X3VwZGF0ZV9zZWN0aW9ucy5sZW5ndGg7ICsraW5kZXgpIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zW2luZGV4XTtcclxuICAgICAgICAgICAgICAgICAgICAkKCBzZWxlY3RvciApLmh0bWwoICRodG1sX2RhdGEuZmluZCggc2VsZWN0b3IgKS5odG1sKCkgKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLmZhZGVDb250ZW50QXJlYXMgPSBmdW5jdGlvbiggZGlyZWN0aW9uICkge1xyXG4gICAgICAgICAgICBcclxuICAgICAgICAgICAgdmFyIG9wYWNpdHkgPSAwLjU7XHJcbiAgICAgICAgICAgIGlmICggZGlyZWN0aW9uID09PSBcImluXCIgKSB7XHJcbiAgICAgICAgICAgICAgICBvcGFjaXR5ID0gMTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYgKCB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zICYmIHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnMubGVuZ3RoICkge1xyXG4gICAgICAgICAgICAgICAgZm9yIChpbmRleCA9IDA7IGluZGV4IDwgdGhpcy5hamF4X3VwZGF0ZV9zZWN0aW9ucy5sZW5ndGg7ICsraW5kZXgpIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zW2luZGV4XTtcclxuICAgICAgICAgICAgICAgICAgICAkKCBzZWxlY3RvciApLnN0b3AodHJ1ZSx0cnVlKS5hbmltYXRlKCB7IG9wYWNpdHk6IG9wYWNpdHl9LCBcImZhc3RcIiApO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgXHJcbiAgICAgICAgICAgIFxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5yZW1vdmVXb29Db21tZXJjZUNvbnRyb2xzID0gZnVuY3Rpb24oKXtcclxuICAgICAgICAgICAgdmFyICR3b29fb3JkZXJieSA9ICQoJy53b29jb21tZXJjZS1vcmRlcmluZyAub3JkZXJieScpO1xyXG4gICAgICAgICAgICB2YXIgJHdvb19vcmRlcmJ5X2Zvcm0gPSAkKCcud29vY29tbWVyY2Utb3JkZXJpbmcnKTtcclxuXHJcbiAgICAgICAgICAgICR3b29fb3JkZXJieV9mb3JtLm9mZigpO1xyXG4gICAgICAgICAgICAkd29vX29yZGVyYnkub2ZmKCk7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5hZGRRdWVyeVBhcmFtID0gZnVuY3Rpb24obmFtZSwgdmFsdWUsIHVybF90eXBlKXtcclxuXHJcbiAgICAgICAgICAgIGlmKHR5cGVvZih1cmxfdHlwZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciB1cmxfdHlwZSA9IFwiYWxsXCI7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgc2VsZi5leHRyYV9xdWVyeV9wYXJhbXNbdXJsX3R5cGVdW25hbWVdID0gdmFsdWU7XHJcblxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuaW5pdFdvb0NvbW1lcmNlQ29udHJvbHMgPSBmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgc2VsZi5yZW1vdmVXb29Db21tZXJjZUNvbnRyb2xzKCk7XHJcblxyXG4gICAgICAgICAgICB2YXIgJHdvb19vcmRlcmJ5ID0gJCgnLndvb2NvbW1lcmNlLW9yZGVyaW5nIC5vcmRlcmJ5Jyk7XHJcbiAgICAgICAgICAgIHZhciAkd29vX29yZGVyYnlfZm9ybSA9ICQoJy53b29jb21tZXJjZS1vcmRlcmluZycpO1xyXG5cclxuICAgICAgICAgICAgdmFyIG9yZGVyX3ZhbCA9IFwiXCI7XHJcbiAgICAgICAgICAgIGlmKCR3b29fb3JkZXJieS5sZW5ndGg+MClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgb3JkZXJfdmFsID0gJHdvb19vcmRlcmJ5LnZhbCgpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgb3JkZXJfdmFsID0gc2VsZi5nZXRRdWVyeVBhcmFtRnJvbVVSTChcIm9yZGVyYnlcIiwgd2luZG93LmxvY2F0aW9uLmhyZWYpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZihvcmRlcl92YWw9PVwibWVudV9vcmRlclwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBvcmRlcl92YWwgPSBcIlwiO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZigob3JkZXJfdmFsIT1cIlwiKSYmKCEhb3JkZXJfdmFsKSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5leHRyYV9xdWVyeV9wYXJhbXMuYWxsLm9yZGVyYnkgPSBvcmRlcl92YWw7XHJcbiAgICAgICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgICAgICAkd29vX29yZGVyYnlfZm9ybS5vbignc3VibWl0JywgZnVuY3Rpb24oZSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgICAgICAgICAgICAgLy92YXIgZm9ybSA9IGUudGFyZ2V0O1xyXG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xyXG4gICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICR3b29fb3JkZXJieS5vbihcImNoYW5nZVwiLCBmdW5jdGlvbihlKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHZhbCA9ICQodGhpcykudmFsKCk7XHJcbiAgICAgICAgICAgICAgICBpZih2YWw9PVwibWVudV9vcmRlclwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhbCA9IFwiXCI7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5leHRyYV9xdWVyeV9wYXJhbXMuYWxsLm9yZGVyYnkgPSB2YWw7XHJcblxyXG4gICAgICAgICAgICAgICAgJHRoaXMudHJpZ2dlcihcInN1Ym1pdFwiKVxyXG5cclxuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5zY3JvbGxSZXN1bHRzID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYoKHNlbGYuc2Nyb2xsX29uX2FjdGlvbj09c2VsZi5hamF4X2FjdGlvbil8fChzZWxmLnNjcm9sbF9vbl9hY3Rpb249PVwiYWxsXCIpKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLnNjcm9sbFRvUG9zKCk7IC8vc2Nyb2xsIHRoZSB3aW5kb3cgaWYgaXQgaGFzIGJlZW4gc2V0XHJcbiAgICAgICAgICAgICAgICAvL3NlbGYuYWpheF9hY3Rpb24gPSBcIlwiO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZVVybEhpc3RvcnkgPSBmdW5jdGlvbihhamF4X3Jlc3VsdHNfdXJsKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyIHVzZV9oaXN0b3J5X2FwaSA9IDA7XHJcbiAgICAgICAgICAgIGlmICh3aW5kb3cuaGlzdG9yeSAmJiB3aW5kb3cuaGlzdG9yeS5wdXNoU3RhdGUpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHVzZV9oaXN0b3J5X2FwaSA9ICR0aGlzLmF0dHIoXCJkYXRhLXVzZS1oaXN0b3J5LWFwaVwiKTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoKHNlbGYudXBkYXRlX2FqYXhfdXJsPT0xKSYmKHVzZV9oaXN0b3J5X2FwaT09MSkpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIC8vbm93IGNoZWNrIGlmIHRoZSBicm93c2VyIHN1cHBvcnRzIGhpc3Rvcnkgc3RhdGUgcHVzaCA6KVxyXG4gICAgICAgICAgICAgICAgaWYgKHdpbmRvdy5oaXN0b3J5ICYmIHdpbmRvdy5oaXN0b3J5LnB1c2hTdGF0ZSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBoaXN0b3J5LnB1c2hTdGF0ZShudWxsLCBudWxsLCBhamF4X3Jlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLnJlbW92ZUFqYXhQYWdpbmF0aW9uID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYodHlwZW9mKHNlbGYuYWpheF9saW5rc19zZWxlY3RvcikhPVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciAkYWpheF9saW5rc19vYmplY3QgPSBqUXVlcnkoc2VsZi5hamF4X2xpbmtzX3NlbGVjdG9yKTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZigkYWpheF9saW5rc19vYmplY3QubGVuZ3RoPjApXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgJGFqYXhfbGlua3Nfb2JqZWN0Lm9mZigpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLmdldEJhc2VVcmwgPSBmdW5jdGlvbiggdXJsICkge1xyXG4gICAgICAgICAgICAvL25vdyBzZWUgaWYgd2UgYXJlIG9uIHRoZSBVUkwgd2UgdGhpbmsuLi5cclxuICAgICAgICAgICAgdmFyIHVybF9wYXJ0cyA9IHVybC5zcGxpdChcIj9cIik7XHJcbiAgICAgICAgICAgIHZhciB1cmxfYmFzZSA9IFwiXCI7XHJcblxyXG4gICAgICAgICAgICBpZih1cmxfcGFydHMubGVuZ3RoPjApXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHVybF9iYXNlID0gdXJsX3BhcnRzWzBdO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2Uge1xyXG4gICAgICAgICAgICAgICAgdXJsX2Jhc2UgPSB1cmw7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgcmV0dXJuIHVybF9iYXNlO1xyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLmNhbkZldGNoQWpheFJlc3VsdHMgPSBmdW5jdGlvbihmZXRjaF90eXBlKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYodHlwZW9mKGZldGNoX3R5cGUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgZmV0Y2hfdHlwZSA9IFwiXCI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBmZXRjaF9hamF4X3Jlc3VsdHMgPSBmYWxzZTtcclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYuaXNfYWpheD09MSlcclxuICAgICAgICAgICAgey8vdGhlbiB3ZSB3aWxsIGFqYXggc3VibWl0IHRoZSBmb3JtXHJcblxyXG4gICAgICAgICAgICAgICAgLy9hbmQgaWYgd2UgY2FuIGZpbmQgdGhlIHJlc3VsdHMgY29udGFpbmVyXHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmxlbmd0aD09MSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBmZXRjaF9hamF4X3Jlc3VsdHMgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIHZhciByZXN1bHRzX3VybCA9IHNlbGYucmVzdWx0c191cmw7ICAvL1xyXG4gICAgICAgICAgICAgICAgdmFyIHJlc3VsdHNfdXJsX2VuY29kZWQgPSAnJzsgIC8vXHJcbiAgICAgICAgICAgICAgICB2YXIgY3VycmVudF91cmwgPSB3aW5kb3cubG9jYXRpb24uaHJlZjtcclxuXHJcbiAgICAgICAgICAgICAgICAvL2lnbm9yZSAjIGFuZCBldmVyeXRoaW5nIGFmdGVyXHJcbiAgICAgICAgICAgICAgICB2YXIgaGFzaF9wb3MgPSB3aW5kb3cubG9jYXRpb24uaHJlZi5pbmRleE9mKCcjJyk7XHJcbiAgICAgICAgICAgICAgICBpZihoYXNoX3BvcyE9PS0xKXtcclxuICAgICAgICAgICAgICAgICAgICBjdXJyZW50X3VybCA9IHdpbmRvdy5sb2NhdGlvbi5ocmVmLnN1YnN0cigwLCB3aW5kb3cubG9jYXRpb24uaHJlZi5pbmRleE9mKCcjJykpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKCAoICggc2VsZi5kaXNwbGF5X3Jlc3VsdF9tZXRob2Q9PVwiY3VzdG9tX3dvb2NvbW1lcmNlX3N0b3JlXCIgKSB8fCAoIHNlbGYuZGlzcGxheV9yZXN1bHRfbWV0aG9kPT1cInBvc3RfdHlwZV9hcmNoaXZlXCIgKSApICYmICggc2VsZi5lbmFibGVfdGF4b25vbXlfYXJjaGl2ZXMgPT0gMSApIClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiggc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUgIT09XCJcIiApXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBmZXRjaF9hamF4X3Jlc3VsdHMgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmV0Y2hfYWpheF9yZXN1bHRzO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLyp2YXIgcmVzdWx0c191cmwgPSBwcm9jZXNzX2Zvcm0uZ2V0UmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgICAgICAgdmFyIGFjdGl2ZV90YXggPSBwcm9jZXNzX2Zvcm0uZ2V0QWN0aXZlVGF4KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgIHZhciBxdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyh0cnVlLCAnJywgYWN0aXZlX3RheCk7Ki9cclxuICAgICAgICAgICAgICAgIH1cclxuXHJcblxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAvL25vdyBzZWUgaWYgd2UgYXJlIG9uIHRoZSBVUkwgd2UgdGhpbmsuLi5cclxuICAgICAgICAgICAgICAgIHZhciB1cmxfYmFzZSA9IHRoaXMuZ2V0QmFzZVVybCggY3VycmVudF91cmwgKTtcclxuICAgICAgICAgICAgICAgIC8vdmFyIHJlc3VsdHNfdXJsX2Jhc2UgPSB0aGlzLmdldEJhc2VVcmwoIGN1cnJlbnRfdXJsICk7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIGxhbmcgPSBzZWxmLmdldFF1ZXJ5UGFyYW1Gcm9tVVJMKFwibGFuZ1wiLCB3aW5kb3cubG9jYXRpb24uaHJlZik7XHJcbiAgICAgICAgICAgICAgICBpZigodHlwZW9mKGxhbmcpIT09XCJ1bmRlZmluZWRcIikmJihsYW5nIT09bnVsbCkpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgdXJsX2Jhc2UgPSBzZWxmLmFkZFVybFBhcmFtKHVybF9iYXNlLCBcImxhbmc9XCIrbGFuZyk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHNmaWQgPSBzZWxmLmdldFF1ZXJ5UGFyYW1Gcm9tVVJMKFwic2ZpZFwiLCB3aW5kb3cubG9jYXRpb24uaHJlZik7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9pZiBzZmlkIGlzIGEgbnVtYmVyXHJcbiAgICAgICAgICAgICAgICBpZihOdW1iZXIocGFyc2VGbG9hdChzZmlkKSkgPT0gc2ZpZClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB1cmxfYmFzZSA9IHNlbGYuYWRkVXJsUGFyYW0odXJsX2Jhc2UsIFwic2ZpZD1cIitzZmlkKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAvL2lmIGFueSBvZiB0aGUgMyBjb25kaXRpb25zIGFyZSB0cnVlLCB0aGVuIGl0cyBnb29kIHRvIGdvXHJcbiAgICAgICAgICAgICAgICAvLyAtIDEgfCBpZiB0aGUgdXJsIGJhc2UgPT0gcmVzdWx0c191cmxcclxuICAgICAgICAgICAgICAgIC8vIC0gMiB8IGlmIHVybCBiYXNlKyBcIi9cIiAgPT0gcmVzdWx0c191cmwgLSBpbiBjYXNlIG9mIHVzZXIgZXJyb3IgaW4gdGhlIHJlc3VsdHMgVVJMXHJcbiAgICAgICAgICAgICAgICAvLyAtIDMgfCBpZiB0aGUgcmVzdWx0cyBVUkwgaGFzIHVybCBwYXJhbXMsIGFuZCB0aGUgY3VycmVudCB1cmwgc3RhcnRzIHdpdGggdGhlIHJlc3VsdHMgVVJMIFxyXG5cclxuICAgICAgICAgICAgICAgIC8vdHJpbSBhbnkgdHJhaWxpbmcgc2xhc2ggZm9yIGVhc2llciBjb21wYXJpc29uOlxyXG4gICAgICAgICAgICAgICAgdXJsX2Jhc2UgPSB1cmxfYmFzZS5yZXBsYWNlKC9cXC8kLywgJycpO1xyXG4gICAgICAgICAgICAgICAgcmVzdWx0c191cmwgPSByZXN1bHRzX3VybC5yZXBsYWNlKC9cXC8kLywgJycpO1xyXG4gICAgICAgICAgICAgICAgcmVzdWx0c191cmxfZW5jb2RlZCA9IGVuY29kZVVSSShyZXN1bHRzX3VybCk7XHJcbiAgICAgICAgICAgICAgICBcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgY3VycmVudF91cmxfY29udGFpbnNfcmVzdWx0c191cmwgPSAtMTtcclxuICAgICAgICAgICAgICAgIGlmKCh1cmxfYmFzZT09cmVzdWx0c191cmwpfHwodXJsX2Jhc2UudG9Mb3dlckNhc2UoKT09cmVzdWx0c191cmxfZW5jb2RlZC50b0xvd2VyQ2FzZSgpKSAgKXtcclxuICAgICAgICAgICAgICAgICAgICBjdXJyZW50X3VybF9jb250YWluc19yZXN1bHRzX3VybCA9IDE7XHJcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmICggcmVzdWx0c191cmwuaW5kZXhPZiggJz8nICkgIT09IC0xICYmIGN1cnJlbnRfdXJsLmxhc3RJbmRleE9mKHJlc3VsdHNfdXJsLCAwKSA9PT0gMCApIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgY3VycmVudF91cmxfY29udGFpbnNfcmVzdWx0c191cmwgPSAxO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLm9ubHlfcmVzdWx0c19hamF4PT0xKVxyXG4gICAgICAgICAgICAgICAgey8vaWYgYSB1c2VyIGhhcyBjaG9zZW4gdG8gb25seSBhbGxvdyBhamF4IG9uIHJlc3VsdHMgcGFnZXMgKGRlZmF1bHQgYmVoYXZpb3VyKVxyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZiggY3VycmVudF91cmxfY29udGFpbnNfcmVzdWx0c191cmwgPiAtMSlcclxuICAgICAgICAgICAgICAgICAgICB7Ly90aGlzIG1lYW5zIHRoZSBjdXJyZW50IFVSTCBjb250YWlucyB0aGUgcmVzdWx0cyB1cmwsIHdoaWNoIG1lYW5zIHdlIGNhbiBkbyBhamF4XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZldGNoX2FqYXhfcmVzdWx0cyA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZldGNoX2FqYXhfcmVzdWx0cyA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZihmZXRjaF90eXBlPT1cInBhZ2luYXRpb25cIilcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKCBjdXJyZW50X3VybF9jb250YWluc19yZXN1bHRzX3VybCA+IC0xKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7Ly90aGlzIG1lYW5zIHRoZSBjdXJyZW50IFVSTCBjb250YWlucyB0aGUgcmVzdWx0cyB1cmwsIHdoaWNoIG1lYW5zIHdlIGNhbiBkbyBhamF4XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy9kb24ndCBhamF4IHBhZ2luYXRpb24gd2hlbiBub3Qgb24gYSBTJkYgcGFnZVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZmV0Y2hfYWpheF9yZXN1bHRzID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHJldHVybiBmZXRjaF9hamF4X3Jlc3VsdHM7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnNldHVwQWpheFBhZ2luYXRpb24gPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAvL2luZmluaXRlIHNjcm9sbFxyXG4gICAgICAgICAgICBpZih0aGlzLnBhZ2luYXRpb25fdHlwZT09PVwiaW5maW5pdGVfc2Nyb2xsXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBpbmZpbml0ZV9zY3JvbGxfZW5kID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmZpbmQoXCJbZGF0YS1zZWFyY2gtZmlsdGVyLWFjdGlvbj0naW5maW5pdGUtc2Nyb2xsLWVuZCddXCIpLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGluZmluaXRlX3Njcm9sbF9lbmQgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuaXNfbWF4X3BhZ2VkID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBpZihwYXJzZUludCh0aGlzLmluc3RhbmNlX251bWJlcik9PT0xKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgJCh3aW5kb3cpLm9mZihcInNjcm9sbFwiLCBzZWxmLm9uV2luZG93U2Nyb2xsKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKHNlbGYuY2FuRmV0Y2hBamF4UmVzdWx0cyhcInBhZ2luYXRpb25cIikpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJCh3aW5kb3cpLm9uKFwic2Nyb2xsXCIsIHNlbGYub25XaW5kb3dTY3JvbGwpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHR5cGVvZihzZWxmLmFqYXhfbGlua3Nfc2VsZWN0b3IpPT1cInVuZGVmaW5lZFwiKSB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSB7XHJcbiAgICAgICAgICAgICAgICAkKGRvY3VtZW50KS5vZmYoJ2NsaWNrJywgc2VsZi5hamF4X2xpbmtzX3NlbGVjdG9yKTtcclxuICAgICAgICAgICAgICAgICQoZG9jdW1lbnQpLm9mZihzZWxmLmFqYXhfbGlua3Nfc2VsZWN0b3IpO1xyXG4gICAgICAgICAgICAgICAgJChzZWxmLmFqYXhfbGlua3Nfc2VsZWN0b3IpLm9mZigpO1xyXG5cclxuICAgICAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsIHNlbGYuYWpheF9saW5rc19zZWxlY3RvciwgZnVuY3Rpb24oZSl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHNlbGYuY2FuRmV0Y2hBamF4UmVzdWx0cyhcInBhZ2luYXRpb25cIikpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgbGluayA9IGpRdWVyeSh0aGlzKS5hdHRyKCdocmVmJyk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuYWpheF9hY3Rpb24gPSBcInBhZ2luYXRpb25cIjtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBwYWdlTnVtYmVyID0gc2VsZi5nZXRQYWdlZEZyb21VUkwobGluayk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmF0dHIoXCJkYXRhLXBhZ2VkXCIsIHBhZ2VOdW1iZXIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5mZXRjaEFqYXhSZXN1bHRzKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmdldFBhZ2VkRnJvbVVSTCA9IGZ1bmN0aW9uKFVSTCl7XHJcblxyXG4gICAgICAgICAgICB2YXIgcGFnZWRWYWwgPSAxO1xyXG4gICAgICAgICAgICAvL2ZpcnN0IHRlc3QgdG8gc2VlIGlmIHdlIGhhdmUgXCIvcGFnZS80L1wiIGluIHRoZSBVUkxcclxuICAgICAgICAgICAgdmFyIHRwVmFsID0gc2VsZi5nZXRRdWVyeVBhcmFtRnJvbVVSTChcInNmX3BhZ2VkXCIsIFVSTCk7XHJcbiAgICAgICAgICAgIGlmKCh0eXBlb2YodHBWYWwpPT1cInN0cmluZ1wiKXx8KHR5cGVvZih0cFZhbCk9PVwibnVtYmVyXCIpKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBwYWdlZFZhbCA9IHRwVmFsO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICByZXR1cm4gcGFnZWRWYWw7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5nZXRRdWVyeVBhcmFtRnJvbVVSTCA9IGZ1bmN0aW9uKG5hbWUsIFVSTCl7XHJcblxyXG4gICAgICAgICAgICB2YXIgcXN0cmluZyA9IFwiP1wiK1VSTC5zcGxpdCgnPycpWzFdO1xyXG4gICAgICAgICAgICBpZih0eXBlb2YocXN0cmluZykhPVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciB2YWwgPSBkZWNvZGVVUklDb21wb25lbnQoKG5ldyBSZWdFeHAoJ1s/fCZdJyArIG5hbWUgKyAnPScgKyAnKFteJjtdKz8pKCZ8I3w7fCQpJykuZXhlYyhxc3RyaW5nKXx8WyxcIlwiXSlbMV0ucmVwbGFjZSgvXFwrL2csICclMjAnKSl8fG51bGw7XHJcbiAgICAgICAgICAgICAgICByZXR1cm4gdmFsO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIHJldHVybiBcIlwiO1xyXG4gICAgICAgIH07XHJcblxyXG5cclxuXHJcbiAgICAgICAgdGhpcy5mb3JtVXBkYXRlZCA9IGZ1bmN0aW9uKGUpe1xyXG5cclxuICAgICAgICAgICAgLy9lLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAgICAgICAgIGlmKHNlbGYuYXV0b191cGRhdGU9PTEpIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuc3VibWl0Rm9ybSgpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoKHNlbGYuYXV0b191cGRhdGU9PTApJiYoc2VsZi5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZT09MSkpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuZm9ybVVwZGF0ZWRGZXRjaEFqYXgoKTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuZm9ybVVwZGF0ZWRGZXRjaEFqYXggPSBmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgLy9sb29wIHRocm91Z2ggYWxsIHRoZSBmaWVsZHMgYW5kIGJ1aWxkIHRoZSBVUkxcclxuICAgICAgICAgICAgc2VsZi5mZXRjaEFqYXhGb3JtKCk7XHJcblxyXG5cclxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIC8vbWFrZSBhbnkgY29ycmVjdGlvbnMvdXBkYXRlcyB0byBmaWVsZHMgYmVmb3JlIHRoZSBzdWJtaXQgY29tcGxldGVzXHJcbiAgICAgICAgdGhpcy5zZXRGaWVsZHMgPSBmdW5jdGlvbihlKXtcclxuXHJcbiAgICAgICAgICAgIC8vaWYoc2VsZi5pc19hamF4PT0wKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9zb21ldGltZXMgdGhlIGZvcm0gaXMgc3VibWl0dGVkIHdpdGhvdXQgdGhlIHNsaWRlciB5ZXQgaGF2aW5nIHVwZGF0ZWQsIGFuZCBhcyB3ZSBnZXQgb3VyIHZhbHVlcyBmcm9tXHJcbiAgICAgICAgICAgICAgICAvL3RoZSBzbGlkZXIgYW5kIG5vdCBpbnB1dHMsIHdlIG5lZWQgdG8gY2hlY2sgaXQgaWYgbmVlZHMgdG8gYmUgc2V0XHJcbiAgICAgICAgICAgICAgICAvL29ubHkgb2NjdXJzIGlmIGFqYXggaXMgb2ZmLCBhbmQgYXV0b3N1Ym1pdCBvblxyXG4gICAgICAgICAgICAgICAgc2VsZi4kZmllbGRzLmVhY2goZnVuY3Rpb24oKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkZmllbGQgPSAkKHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgcmFuZ2VfZGlzcGxheV92YWx1ZXMgPSAkZmllbGQuZmluZCgnLnNmLW1ldGEtcmFuZ2Utc2xpZGVyJykuYXR0cihcImRhdGEtZGlzcGxheS12YWx1ZXMtYXNcIik7Ly9kYXRhLWRpc3BsYXktdmFsdWVzLWFzPVwidGV4dFwiXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHJhbmdlX2Rpc3BsYXlfdmFsdWVzPT09XCJ0ZXh0aW5wdXRcIikge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoJGZpZWxkLmZpbmQoXCIubWV0YS1zbGlkZXJcIikubGVuZ3RoPjApe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcIi5tZXRhLXNsaWRlclwiKS5lYWNoKGZ1bmN0aW9uIChpbmRleCkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzbGlkZXJfb2JqZWN0ID0gJCh0aGlzKVswXTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkc2xpZGVyX2VsID0gJCh0aGlzKS5jbG9zZXN0KFwiLnNmLW1ldGEtcmFuZ2Utc2xpZGVyXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy92YXIgbWluVmFsID0gJHNsaWRlcl9lbC5hdHRyKFwiZGF0YS1taW5cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL3ZhciBtYXhWYWwgPSAkc2xpZGVyX2VsLmF0dHIoXCJkYXRhLW1heFwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBtaW5WYWwgPSAkc2xpZGVyX2VsLmZpbmQoXCIuc2YtcmFuZ2UtbWluXCIpLnZhbCgpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIG1heFZhbCA9ICRzbGlkZXJfZWwuZmluZChcIi5zZi1yYW5nZS1tYXhcIikudmFsKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIuc2V0KFttaW5WYWwsIG1heFZhbF0pO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgIC8vfVxyXG5cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIC8vc3VibWl0XHJcbiAgICAgICAgdGhpcy5zdWJtaXRGb3JtID0gZnVuY3Rpb24oZSl7XHJcblxyXG4gICAgICAgICAgICAvL2xvb3AgdGhyb3VnaCBhbGwgdGhlIGZpZWxkcyBhbmQgYnVpbGQgdGhlIFVSTFxyXG4gICAgICAgICAgICBpZihzZWxmLmlzU3VibWl0dGluZyA9PSB0cnVlKSB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHNlbGYuc2V0RmllbGRzKCk7XHJcbiAgICAgICAgICAgIHNlbGYuY2xlYXJUaW1lcigpO1xyXG5cclxuICAgICAgICAgICAgc2VsZi5pc1N1Ym1pdHRpbmcgPSB0cnVlO1xyXG5cclxuICAgICAgICAgICAgcHJvY2Vzc19mb3JtLnNldFRheEFyY2hpdmVSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG5cclxuICAgICAgICAgICAgc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5hdHRyKFwiZGF0YS1wYWdlZFwiLCAxKTsgLy9pbml0IHBhZ2VkXHJcblxyXG4gICAgICAgICAgICBpZihzZWxmLmNhbkZldGNoQWpheFJlc3VsdHMoKSlcclxuICAgICAgICAgICAgey8vdGhlbiB3ZSB3aWxsIGFqYXggc3VibWl0IHRoZSBmb3JtXHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5hamF4X2FjdGlvbiA9IFwic3VibWl0XCI7IC8vc28gd2Uga25vdyBpdCB3YXNuJ3QgcGFnaW5hdGlvblxyXG4gICAgICAgICAgICAgICAgc2VsZi5mZXRjaEFqYXhSZXN1bHRzKCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIHdpbGwgc2ltcGx5IHJlZGlyZWN0IHRvIHRoZSBSZXN1bHRzIFVSTFxyXG5cclxuICAgICAgICAgICAgICAgIHZhciByZXN1bHRzX3VybCA9IHByb2Nlc3NfZm9ybS5nZXRSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG4gICAgICAgICAgICAgICAgdmFyIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKHRydWUsICcnKTtcclxuICAgICAgICAgICAgICAgIHJlc3VsdHNfdXJsID0gc2VsZi5hZGRVcmxQYXJhbShyZXN1bHRzX3VybCwgcXVlcnlfcGFyYW1zKTtcclxuXHJcbiAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IHJlc3VsdHNfdXJsO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgfTtcclxuICAgICAgICB0aGlzLnJlc2V0Rm9ybSA9IGZ1bmN0aW9uKHN1Ym1pdF9mb3JtKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgLy91bnNldCBhbGwgZmllbGRzXHJcbiAgICAgICAgICAgIHNlbGYuJGZpZWxkcy5lYWNoKGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICRmaWVsZCA9ICQodGhpcyk7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0JGZpZWxkLnJlbW92ZUF0dHIoXCJkYXRhLXNmLXRheG9ub215LWFyY2hpdmVcIik7XHJcblx0XHRcdFx0XHJcbiAgICAgICAgICAgICAgICAvL3N0YW5kYXJkIGZpZWxkIHR5cGVzXHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcInNlbGVjdDpub3QoW211bHRpcGxlPSdtdWx0aXBsZSddKSA+IG9wdGlvbjpmaXJzdC1jaGlsZFwiKS5wcm9wKFwic2VsZWN0ZWRcIiwgdHJ1ZSk7XHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcInNlbGVjdFttdWx0aXBsZT0nbXVsdGlwbGUnXSA+IG9wdGlvblwiKS5wcm9wKFwic2VsZWN0ZWRcIiwgZmFsc2UpO1xyXG4gICAgICAgICAgICAgICAgJGZpZWxkLmZpbmQoXCJpbnB1dFt0eXBlPSdjaGVja2JveCddXCIpLnByb3AoXCJjaGVja2VkXCIsIGZhbHNlKTtcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiPiB1bCA+IGxpOmZpcnN0LWNoaWxkIGlucHV0W3R5cGU9J3JhZGlvJ11cIikucHJvcChcImNoZWNrZWRcIiwgdHJ1ZSk7XHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcImlucHV0W3R5cGU9J3RleHQnXVwiKS52YWwoXCJcIik7XHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcIi5zZi1vcHRpb24tYWN0aXZlXCIpLnJlbW92ZUNsYXNzKFwic2Ytb3B0aW9uLWFjdGl2ZVwiKTtcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiPiB1bCA+IGxpOmZpcnN0LWNoaWxkIGlucHV0W3R5cGU9J3JhZGlvJ11cIikucGFyZW50KCkuYWRkQ2xhc3MoXCJzZi1vcHRpb24tYWN0aXZlXCIpOyAvL3JlIGFkZCBhY3RpdmUgY2xhc3MgdG8gZmlyc3QgXCJkZWZhdWx0XCIgb3B0aW9uXHJcblxyXG4gICAgICAgICAgICAgICAgLy9udW1iZXIgcmFuZ2UgLSAyIG51bWJlciBpbnB1dCBmaWVsZHNcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiaW5wdXRbdHlwZT0nbnVtYmVyJ11cIikuZWFjaChmdW5jdGlvbihpbmRleCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkdGhpc0lucHV0ID0gJCh0aGlzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoJHRoaXNJbnB1dC5wYXJlbnQoKS5wYXJlbnQoKS5oYXNDbGFzcyhcInNmLW1ldGEtcmFuZ2VcIikpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKGluZGV4PT0wKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkdGhpc0lucHV0LnZhbCgkdGhpc0lucHV0LmF0dHIoXCJtaW5cIikpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGVsc2UgaWYoaW5kZXg9PTEpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzSW5wdXQudmFsKCR0aGlzSW5wdXQuYXR0cihcIm1heFwiKSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9tZXRhIC8gbnVtYmVycyB3aXRoIDIgaW5wdXRzIChmcm9tIC8gdG8gZmllbGRzKSAtIHNlY29uZCBpbnB1dCBtdXN0IGJlIHJlc2V0IHRvIG1heCB2YWx1ZVxyXG4gICAgICAgICAgICAgICAgdmFyICRtZXRhX3NlbGVjdF9mcm9tX3RvID0gJGZpZWxkLmZpbmQoXCIuc2YtbWV0YS1yYW5nZS1zZWxlY3QtZnJvbXRvXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKCRtZXRhX3NlbGVjdF9mcm9tX3RvLmxlbmd0aD4wKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzdGFydF9taW4gPSAkbWV0YV9zZWxlY3RfZnJvbV90by5hdHRyKFwiZGF0YS1taW5cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHN0YXJ0X21heCA9ICRtZXRhX3NlbGVjdF9mcm9tX3RvLmF0dHIoXCJkYXRhLW1heFwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgJG1ldGFfc2VsZWN0X2Zyb21fdG8uZmluZChcInNlbGVjdFwiKS5lYWNoKGZ1bmN0aW9uKGluZGV4KXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkdGhpc0lucHV0ID0gJCh0aGlzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKGluZGV4PT0wKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHRoaXNJbnB1dC52YWwoc3RhcnRfbWluKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBlbHNlIGlmKGluZGV4PT0xKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkdGhpc0lucHV0LnZhbChzdGFydF9tYXgpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIHZhciAkbWV0YV9yYWRpb19mcm9tX3RvID0gJGZpZWxkLmZpbmQoXCIuc2YtbWV0YS1yYW5nZS1yYWRpby1mcm9tdG9cIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYoJG1ldGFfcmFkaW9fZnJvbV90by5sZW5ndGg+MClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc3RhcnRfbWluID0gJG1ldGFfcmFkaW9fZnJvbV90by5hdHRyKFwiZGF0YS1taW5cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHN0YXJ0X21heCA9ICRtZXRhX3JhZGlvX2Zyb21fdG8uYXR0cihcImRhdGEtbWF4XCIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgJHJhZGlvX2dyb3VwcyA9ICRtZXRhX3JhZGlvX2Zyb21fdG8uZmluZCgnLnNmLWlucHV0LXJhbmdlLXJhZGlvJyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICRyYWRpb19ncm91cHMuZWFjaChmdW5jdGlvbihpbmRleCl7XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRyYWRpb3MgPSAkKHRoaXMpLmZpbmQoXCIuc2YtaW5wdXQtcmFkaW9cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRyYWRpb3MucHJvcChcImNoZWNrZWRcIiwgZmFsc2UpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoaW5kZXg9PTApXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRyYWRpb3MuZmlsdGVyKCdbdmFsdWU9XCInK3N0YXJ0X21pbisnXCJdJykucHJvcChcImNoZWNrZWRcIiwgdHJ1ZSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgZWxzZSBpZihpbmRleD09MSlcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHJhZGlvcy5maWx0ZXIoJ1t2YWx1ZT1cIicrc3RhcnRfbWF4KydcIl0nKS5wcm9wKFwiY2hlY2tlZFwiLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgLy9udW1iZXIgc2xpZGVyIC0gbm9VaVNsaWRlclxyXG4gICAgICAgICAgICAgICAgJGZpZWxkLmZpbmQoXCIubWV0YS1zbGlkZXJcIikuZWFjaChmdW5jdGlvbihpbmRleCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzbGlkZXJfb2JqZWN0ID0gJCh0aGlzKVswXTtcclxuICAgICAgICAgICAgICAgICAgICAvKnZhciBzbGlkZXJfb2JqZWN0ID0gJGNvbnRhaW5lci5maW5kKFwiLm1ldGEtc2xpZGVyXCIpWzBdO1xyXG4gICAgICAgICAgICAgICAgICAgICB2YXIgc2xpZGVyX3ZhbCA9IHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5nZXQoKTsqL1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgJHNsaWRlcl9lbCA9ICQodGhpcykuY2xvc2VzdChcIi5zZi1tZXRhLXJhbmdlLXNsaWRlclwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWluVmFsID0gJHNsaWRlcl9lbC5hdHRyKFwiZGF0YS1taW5cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1heFZhbCA9ICRzbGlkZXJfZWwuYXR0cihcImRhdGEtbWF4XCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5zZXQoW21pblZhbCwgbWF4VmFsXSk7XHJcblxyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9uZWVkIHRvIHNlZSBpZiBhbnkgYXJlIGNvbWJvYm94IGFuZCBhY3QgYWNjb3JkaW5nbHlcclxuICAgICAgICAgICAgICAgIHZhciAkY29tYm9ib3ggPSAkZmllbGQuZmluZChcInNlbGVjdFtkYXRhLWNvbWJvYm94PScxJ11cIik7XHJcbiAgICAgICAgICAgICAgICBpZigkY29tYm9ib3gubGVuZ3RoPjApXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAkY29tYm9ib3guY2hvc2VuICE9IFwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkY29tYm9ib3gudHJpZ2dlcihcImNob3Nlbjp1cGRhdGVkXCIpOyAvL2ZvciBjaG9zZW4gb25seVxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkY29tYm9ib3gudmFsKCcnKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJGNvbWJvYm94LnRyaWdnZXIoJ2NoYW5nZS5zZWxlY3QyJyk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICBzZWxmLmNsZWFyVGltZXIoKTtcclxuXHJcblxyXG5cclxuICAgICAgICAgICAgaWYoc3VibWl0X2Zvcm09PVwiYWx3YXlzXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuc3VibWl0Rm9ybSgpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoc3VibWl0X2Zvcm09PVwibmV2ZXJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgaWYodGhpcy5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZT09MSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLmZvcm1VcGRhdGVkRmV0Y2hBamF4KCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihzdWJtaXRfZm9ybT09XCJhdXRvXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIGlmKHRoaXMuYXV0b191cGRhdGU9PXRydWUpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5zdWJtaXRGb3JtKCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYodGhpcy5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZT09MSlcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuZm9ybVVwZGF0ZWRGZXRjaEFqYXgoKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5pbml0KCk7XHJcblxyXG4gICAgICAgIHZhciBldmVudF9kYXRhID0ge307XHJcbiAgICAgICAgZXZlbnRfZGF0YS5zZmlkID0gc2VsZi5zZmlkO1xyXG4gICAgICAgIGV2ZW50X2RhdGEudGFyZ2V0U2VsZWN0b3IgPSBzZWxmLmFqYXhfdGFyZ2V0X2F0dHI7XHJcbiAgICAgICAgZXZlbnRfZGF0YS5vYmplY3QgPSB0aGlzO1xyXG4gICAgICAgIGlmKG9wdHMuaXNJbml0KVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjppbml0XCIsIGV2ZW50X2RhdGEpO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICB9KTtcclxufTtcclxuIl19
},{"./process_form":4,"./state":5,"./thirdparty":6,"nouislider":2}],4:[function(require,module,exports){
(function (global){

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null);

module.exports = {

	taxonomy_archives: 0,
    url_params: {},
    tax_archive_results_url: "",
    active_tax: "",
    fields: {},
	init: function(taxonomy_archives, current_taxonomy_archive){

        this.taxonomy_archives = 0;
        this.url_params = {};
        this.tax_archive_results_url = "";
        this.active_tax = "";

		//this.$fields = $fields;
        this.taxonomy_archives = taxonomy_archives;
        this.current_taxonomy_archive = current_taxonomy_archive;

		this.clearUrlComponents();

	},
    setTaxArchiveResultsUrl: function($form, current_results_url, get_active) {

        var self = this;
		this.clearTaxArchiveResultsUrl();
        //var current_results_url = "";
        if(this.taxonomy_archives!=1)
        {
            return;
        }

        if(typeof(get_active)=="undefined")
		{
			var get_active = false;
		}

        //check to see if we have any taxonomies selected
        //if so, check their rewrites and use those as the results url
        var $field = false;
        var field_name = "";
        var field_value = "";

        var $active_taxonomy = $form.$fields.parent().find("[data-sf-taxonomy-archive='1']");
        if($active_taxonomy.length==1)
        {
            $field = $active_taxonomy;

            var fieldType = $field.attr("data-sf-field-type");

            if ((fieldType == "tag") || (fieldType == "category") || (fieldType == "taxonomy")) {
                var taxonomy_value = self.processTaxonomy($field, true);
                field_name = $field.attr("data-sf-field-name");
                var taxonomy_name = field_name.replace("_sft_", "");

                if (taxonomy_value) {
                    field_value = taxonomy_value.value;
                }
            }

            if(field_value=="")
            {
                $field = false;
            }
        }

        if((self.current_taxonomy_archive!="")&&(self.current_taxonomy_archive!=taxonomy_name))
        {

            this.tax_archive_results_url = current_results_url;
            return;
        }

        if(((field_value=="")||(!$field) ))
        {
            $form.$fields.each(function () {

                if (!$field) {

                    var fieldType = $(this).attr("data-sf-field-type");

                    if ((fieldType == "tag") || (fieldType == "category") || (fieldType == "taxonomy")) {
                        var taxonomy_value = self.processTaxonomy($(this), true);
                        field_name = $(this).attr("data-sf-field-name");

                        if (taxonomy_value) {

                            field_value = taxonomy_value.value;

                            if (field_value != "") {

                                $field = $(this);
                            }

                        }
                    }
                }
            });
        }

        if( ($field) && (field_value != "" )) {
            //if we found a field
			var rewrite_attr = ($field.attr("data-sf-term-rewrite"));

            if(rewrite_attr!="") {

                var rewrite = JSON.parse(rewrite_attr);
                var input_type = $field.attr("data-sf-field-input-type");
                self.active_tax = field_name;

                //find the active element
                if ((input_type == "radio") || (input_type == "checkbox")) {

                    //var $active = $field.find(".sf-option-active");
                    //explode the values if there is a delim
                    //field_value

                    var is_single_value = true;
                    var field_values = field_value.split(",").join("+").split("+");
                    if (field_values.length > 1) {
                        is_single_value = false;
                    }

                    if (is_single_value) {

                        var $input = $field.find("input[value='" + field_value + "']");
                        var $active = $input.parent();
                        var depth = $active.attr("data-sf-depth");

                        //now loop through parents to grab their names
                        var values = new Array();
                        values.push(field_value);

                        for (var i = depth; i > 0; i--) {
                            $active = $active.parent().parent();
                            values.push($active.find("input").val());
                        }

                        values.reverse();

                        //grab the rewrite for this depth
                        var active_rewrite = rewrite[depth];
                        var url = active_rewrite;


                        //then map from the parents to the depth
                        $(values).each(function (index, value) {

                            url = url.replace("[" + index + "]", value);

                        });
                        this.tax_archive_results_url = url;
                    }
                    else {

                        //if there are multiple values,
                        //then we need to check for 3 things:

                        //if the values selected are all in the same tree then we can do some clever rewrite stuff
                        //merge all values in same level, then combine the levels

                        //if they are from different trees then just combine them or just use `field_value`
                        /*

                         var depths = new Array();
                         $(field_values).each(function (index, val) {

                         var $input = $field.find("input[value='" + field_value + "']");
                         var $active = $input.parent();

                         var depth = $active.attr("data-sf-depth");
                         //depths.push(depth);

                         });*/

                    }
                }
                else if ((input_type == "select") || (input_type == "multiselect")) {

                    var is_single_value = true;
                    var field_values = field_value.split(",").join("+").split("+");
                    if (field_values.length > 1) {
                        is_single_value = false;
                    }

                    if (is_single_value) {

                        var $active = $field.find("option[value='" + field_value + "']");
                        var depth = $active.attr("data-sf-depth");

                        var values = new Array();
                        values.push(field_value);

                        for (var i = depth; i > 0; i--) {
                            $active = $active.prevAll("option[data-sf-depth='" + (i - 1) + "']");
                            values.push($active.val());
                        }

                        values.reverse();
                        var active_rewrite = rewrite[depth];
                        var url = active_rewrite;
                        $(values).each(function (index, value) {

                            url = url.replace("[" + index + "]", value);

                        });
                        this.tax_archive_results_url = url;
                    }

                }
            }

        }
        //this.tax_archive_results_url = current_results_url;
    },
    getResultsUrl: function($form, current_results_url) {

        //this.setTaxArchiveResultsUrl($form, current_results_url);

        if(this.tax_archive_results_url=="")
        {
            return current_results_url;
        }

        return this.tax_archive_results_url;
    },
	getUrlParams: function($form){

		this.buildUrlComponents($form, true);

        if(this.tax_archive_results_url!="")
        {

            if(this.active_tax!="")
            {
                var field_name = this.active_tax;

                if(typeof(this.url_params[field_name])!="undefined")
                {
                    delete this.url_params[field_name];
                }
            }
        }

		return this.url_params;
	},
	clearUrlComponents: function(){
		//this.url_components = "";
		this.url_params = {};
	},
	clearTaxArchiveResultsUrl: function() {
		this.tax_archive_results_url = '';
	},
	disableInputs: function($form){
		var self = this;
		
		$form.$fields.each(function(){
			
			var $inputs = $(this).find("input, select, .meta-slider");
			$inputs.attr("disabled", "disabled");
			$inputs.attr("disabled", true);
			$inputs.prop("disabled", true);
			$inputs.trigger("chosen:updated");
			
		});
		
		
	},
	enableInputs: function($form){
		var self = this;
		$form.$fields.each(function(){
			var $inputs = $(this).find("input, select, .meta-slider");
			$inputs.prop("disabled", false);
			$inputs.attr("disabled", false);
			$inputs.trigger("chosen:updated");			
		});
		
		
	},
	buildUrlComponents: function($form, clear_components){
		
		var self = this;
		
		if(typeof(clear_components)!="undefined")
		{
			if(clear_components==true)
			{
				this.clearUrlComponents();
			}
		}
		
		$form.$fields.each(function(){
			
			var fieldName = $(this).attr("data-sf-field-name");
			var fieldType = $(this).attr("data-sf-field-type");
			
			if(fieldType=="search")
			{
				self.processSearchField($(this));
			}
			else if((fieldType=="tag")||(fieldType=="category")||(fieldType=="taxonomy"))
			{
				self.processTaxonomy($(this));
			}
			else if(fieldType=="sort_order")
			{
				self.processSortOrderField($(this));
			}
			else if(fieldType=="posts_per_page")
			{
				self.processResultsPerPageField($(this));
			}
			else if(fieldType=="author")
			{
				self.processAuthor($(this));
			}
			else if(fieldType=="post_type")
			{
				self.processPostType($(this));
			}
			else if(fieldType=="post_date")
			{
				self.processPostDate($(this));
			}
			else if(fieldType=="post_meta")
			{
				self.processPostMeta($(this));
				
			}
			else
			{
				
			}
			
		});
		
	},
	processSearchField: function($container)
	{
		var self = this;
		
		var $field = $container.find("input[name^='_sf_search']");
		
		if($field.length>0)
		{
			var fieldName = $field.attr("name").replace('[]', '');
			var fieldVal = $field.val();
			
			if(fieldVal!="")
			{
				//self.url_components += "&_sf_s="+encodeURIComponent(fieldVal);
				self.url_params['_sf_s'] = encodeURIComponent(fieldVal);
			}
		}
	},
	processSortOrderField: function($container)
	{
		this.processAuthor($container);
		
	},
	processResultsPerPageField: function($container)
	{
		this.processAuthor($container);
		
	},
	getActiveTax: function($field) {
		return this.active_tax;
	},
	getSelectVal: function($field){

		var fieldVal = "";
		
		if($field.val()!=0)
		{
			fieldVal = $field.val();
		}
		
		if(fieldVal==null)
		{
			fieldVal = "";
		}
		
		return fieldVal;
	},
	getMetaSelectVal: function($field){
		
		var fieldVal = "";
		
		fieldVal = $field.val();
						
		if(fieldVal==null)
		{
			fieldVal = "";
		}
		
		return fieldVal;
	},
	getMultiSelectVal: function($field, operator){
		
		var delim = "+";
		if(operator=="or")
		{
			delim = ",";
		}
		
		if(typeof($field.val())=="object")
		{
			if($field.val()!=null)
			{
				return $field.val().join(delim);
			}
		}
		
	},
	getMetaMultiSelectVal: function($field, operator){
		
		var delim = "-+-";
		if(operator=="or")
		{
			delim = "-,-";
		}
				
		if(typeof($field.val())=="object")
		{
			if($field.val()!=null)
			{
				
				var fieldval = [];
				
				$($field.val()).each(function(index,value){
					
					fieldval.push((value));
				});
				
				return fieldval.join(delim);
			}
		}
		
		return "";
		
	},
	getCheckboxVal: function($field, operator){
		
		
		var fieldVal = $field.map(function(){
			if($(this).prop("checked")==true)
			{
				return $(this).val();
			}
		}).get();
		
		var delim = "+";
		if(operator=="or")
		{
			delim = ",";
		}
		
		return fieldVal.join(delim);
	},
	getMetaCheckboxVal: function($field, operator){
		
		
		var fieldVal = $field.map(function(){
			if($(this).prop("checked")==true)
			{
				return ($(this).val());
			}
		}).get();
		
		var delim = "-+-";
		if(operator=="or")
		{
			delim = "-,-";
		}
		
		return fieldVal.join(delim);
	},
	getRadioVal: function($field){
							
		var fieldVal = $field.map(function()
		{
			if($(this).prop("checked")==true)
			{
				return $(this).val();
			}
			
		}).get();
		
		
		if(fieldVal[0]!=0)
		{
			return fieldVal[0];
		}
	},
	getMetaRadioVal: function($field){
							
		var fieldVal = $field.map(function()
		{
			if($(this).prop("checked")==true)
			{
				return $(this).val();
			}
			
		}).get();
		
		return fieldVal[0];
	},
	processAuthor: function($container)
	{
		var self = this;
		
		
		var fieldType = $container.attr("data-sf-field-type");
		var inputType = $container.attr("data-sf-field-input-type");
		
		var $field;
		var fieldName = "";
		var fieldVal = "";
		
		if(inputType=="select")
		{
			$field = $container.find("select");
			fieldName = $field.attr("name").replace('[]', '');
			
			fieldVal = self.getSelectVal($field); 
		}
		else if(inputType=="multiselect")
		{
			$field = $container.find("select");
			fieldName = $field.attr("name").replace('[]', '');
			var operator = $field.attr("data-operator");
			
			fieldVal = self.getMultiSelectVal($field, "or");
			
		}
		else if(inputType=="checkbox")
		{
			$field = $container.find("ul > li input:checkbox");
			
			if($field.length>0)
			{
				fieldName = $field.attr("name").replace('[]', '');
										
				var operator = $container.find("> ul").attr("data-operator");
				fieldVal = self.getCheckboxVal($field, "or");
			}
			
		}
		else if(inputType=="radio")
		{
			
			$field = $container.find("ul > li input:radio");
						
			if($field.length>0)
			{
				fieldName = $field.attr("name").replace('[]', '');
				
				fieldVal = self.getRadioVal($field);
			}
		}
		
		if(typeof(fieldVal)!="undefined")
		{
			if(fieldVal!="")
			{
				var fieldSlug = "";
				
				if(fieldName=="_sf_author")
				{
					fieldSlug = "authors";
				}
				else if(fieldName=="_sf_sort_order")
				{
					fieldSlug = "sort_order";
				}
				else if(fieldName=="_sf_ppp")
				{
					fieldSlug = "_sf_ppp";
				}
				else if(fieldName=="_sf_post_type")
				{
					fieldSlug = "post_types";
				}
				else
				{
				
				}
				
				if(fieldSlug!="")
				{
					//self.url_components += "&"+fieldSlug+"="+fieldVal;
					self.url_params[fieldSlug] = fieldVal;
				}
			}
		}
		
	},
	processPostType : function($this){
		
		this.processAuthor($this);
		
	},
	processPostMeta: function($container)
	{
		var self = this;
		
		var fieldType = $container.attr("data-sf-field-type");
		var inputType = $container.attr("data-sf-field-input-type");
		var metaType = $container.attr("data-sf-meta-type");

		var fieldVal = "";
		var $field;
		var fieldName = "";
		
		if(metaType=="number")
		{
			if(inputType=="range-number")
			{
				$field = $container.find(".sf-meta-range-number input");
				
				var values = [];
				$field.each(function(){
					
					values.push($(this).val());
				
				});
				
				fieldVal = values.join("+");
				
			}
			else if(inputType=="range-slider")
			{
				$field = $container.find(".sf-meta-range-slider input");
				
				//get any number formatting stuff
				var $meta_range = $container.find(".sf-meta-range-slider");
				
				var decimal_places = $meta_range.attr("data-decimal-places");
				var thousand_seperator = $meta_range.attr("data-thousand-seperator");
				var decimal_seperator = $meta_range.attr("data-decimal-seperator");

				var field_format = wNumb({
					mark: decimal_seperator,
					decimals: parseFloat(decimal_places),
					thousand: thousand_seperator
				});
				
				var values = [];


				var slider_object = $container.find(".meta-slider")[0];
				//val from slider object
				var slider_val = slider_object.noUiSlider.get();

				values.push(field_format.from(slider_val[0]));
				values.push(field_format.from(slider_val[1]));
				
				fieldVal = values.join("+");
				
				fieldName = $meta_range.attr("data-sf-field-name");
				
				
			}
			else if(inputType=="range-radio")
			{
				$field = $container.find(".sf-input-range-radio");
				
				if($field.length==0)
				{
					//then try again, we must be using a single field
					$field = $container.find("> ul");
				}

				var $meta_range = $container.find(".sf-meta-range");
				
				//there is an element with a from/to class - so we need to get the values of the from & to input fields seperately
				if($field.length>0)
				{	
					var field_vals = [];
					
					$field.each(function(){
						
						var $radios = $(this).find(".sf-input-radio");
						field_vals.push(self.getMetaRadioVal($radios));
						
					});
					
					//prevent second number from being lower than the first
					if(field_vals.length==2)
					{
						if(Number(field_vals[1])<Number(field_vals[0]))
						{
							field_vals[1] = field_vals[0];
						}
					}
					
					fieldVal = field_vals.join("+");
				}
								
				if($field.length==1)
				{
					fieldName = $field.find(".sf-input-radio").attr("name").replace('[]', '');
				}
				else
				{
					fieldName = $meta_range.attr("data-sf-field-name");
				}

			}
			else if(inputType=="range-select")
			{
				$field = $container.find(".sf-input-select");
				var $meta_range = $container.find(".sf-meta-range");
				
				//there is an element with a from/to class - so we need to get the values of the from & to input fields seperately
				
				if($field.length>0)
				{
					var field_vals = [];
					
					$field.each(function(){
						
						var $this = $(this);
						field_vals.push(self.getMetaSelectVal($this));
						
					});
					
					//prevent second number from being lower than the first
					if(field_vals.length==2)
					{
						if(Number(field_vals[1])<Number(field_vals[0]))
						{
							field_vals[1] = field_vals[0];
						}
					}
					
					
					fieldVal = field_vals.join("+");
				}
								
				if($field.length==1)
				{
					fieldName = $field.attr("name").replace('[]', '');
				}
				else
				{
					fieldName = $meta_range.attr("data-sf-field-name");
				}
				
			}
			else if(inputType=="range-checkbox")
			{
				$field = $container.find("ul > li input:checkbox");
				
				if($field.length>0)
				{
					fieldVal = self.getCheckboxVal($field, "and");
				}
			}
			
			if(fieldName=="")
			{
				fieldName = $field.attr("name").replace('[]', '');
			}
		}
		else if(metaType=="choice")
		{
			if(inputType=="select")
			{
				$field = $container.find("select");
				
				fieldVal = self.getMetaSelectVal($field); 
				
			}
			else if(inputType=="multiselect")
			{
				$field = $container.find("select");
				var operator = $field.attr("data-operator");
				
				fieldVal = self.getMetaMultiSelectVal($field, operator);
			}
			else if(inputType=="checkbox")
			{
				$field = $container.find("ul > li input:checkbox");
				
				if($field.length>0)
				{
					var operator = $container.find("> ul").attr("data-operator");
					fieldVal = self.getMetaCheckboxVal($field, operator);
				}
			}
			else if(inputType=="radio")
			{
				$field = $container.find("ul > li input:radio");
				
				if($field.length>0)
				{
					fieldVal = self.getMetaRadioVal($field);
				}
			}
			
			fieldVal = encodeURIComponent(fieldVal);
			if(typeof($field)!=="undefined")
			{
				if($field.length>0)
				{
					fieldName = $field.attr("name").replace('[]', '');
					
					//for those who insist on using & ampersands in the name of the custom field (!)
					fieldName = (fieldName);
				}
			}
			
		}
		else if(metaType=="date")
		{
			self.processPostDate($container);
		}
		
		if(typeof(fieldVal)!="undefined")
		{
			if(fieldVal!="")
			{
				//self.url_components += "&"+encodeURIComponent(fieldName)+"="+(fieldVal);
				self.url_params[encodeURIComponent(fieldName)] = (fieldVal);
			}
		}
	},
	processPostDate: function($container)
	{
		var self = this;
		
		var fieldType = $container.attr("data-sf-field-type");
		var inputType = $container.attr("data-sf-field-input-type");
		
		var $field;
		var fieldName = "";
		var fieldVal = "";
		
		$field = $container.find("ul > li input:text");
		fieldName = $field.attr("name").replace('[]', '');
		
		var dates = [];
		$field.each(function(){
			
			dates.push($(this).val());
		
		});
		
		if($field.length==2)
		{
			if((dates[0]!="")||(dates[1]!=""))
			{
				fieldVal = dates.join("+");
				fieldVal = fieldVal.replace(/\//g,'');
			}
		}
		else if($field.length==1)
		{
			if(dates[0]!="")
			{
				fieldVal = dates.join("+");
				fieldVal = fieldVal.replace(/\//g,'');
			}
		}
		
		if(typeof(fieldVal)!="undefined")
		{
			if(fieldVal!="")
			{
				var fieldSlug = "";
				
				if(fieldName=="_sf_post_date")
				{
					fieldSlug = "post_date";
				}
				else
				{
					fieldSlug = fieldName;
				}
				
				if(fieldSlug!="")
				{
					//self.url_components += "&"+fieldSlug+"="+fieldVal;
					self.url_params[fieldSlug] = fieldVal;
				}
			}
		}
		
	},
	processTaxonomy: function($container, return_object)
	{
        if(typeof(return_object)=="undefined")
        {
            return_object = false;
        }

		//if()					
		//var fieldName = $(this).attr("data-sf-field-name");
		var self = this;
	
		var fieldType = $container.attr("data-sf-field-type");
		var inputType = $container.attr("data-sf-field-input-type");
		
		var $field;
		var fieldName = "";
		var fieldVal = "";
		
		if(inputType=="select")
		{
			$field = $container.find("select");
			fieldName = $field.attr("name").replace('[]', '');
			
			fieldVal = self.getSelectVal($field); 
		}
		else if(inputType=="multiselect")
		{
			$field = $container.find("select");
			fieldName = $field.attr("name").replace('[]', '');
			var operator = $field.attr("data-operator");
			
			fieldVal = self.getMultiSelectVal($field, operator);
		}
		else if(inputType=="checkbox")
		{
			$field = $container.find("ul > li input:checkbox");
			if($field.length>0)
			{
				fieldName = $field.attr("name").replace('[]', '');
										
				var operator = $container.find("> ul").attr("data-operator");
				fieldVal = self.getCheckboxVal($field, operator);
			}
		}
		else if(inputType=="radio")
		{
			$field = $container.find("ul > li input:radio");
			if($field.length>0)
			{
				fieldName = $field.attr("name").replace('[]', '');
				
				fieldVal = self.getRadioVal($field);
			}
		}
		
		if(typeof(fieldVal)!="undefined")
		{
			if(fieldVal!="")
			{
                if(return_object==true)
                {
                    return {name: fieldName, value: fieldVal};
                }
                else
                {
                    //self.url_components += "&"+fieldName+"="+fieldVal;
                    self.url_params[fieldName] = fieldVal;
                }

			}
		}

        if(return_object==true)
        {
            return false;
        }
	}
};
}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
//# sourceMappingURL=data:application/json;charset:utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9wdWJsaWMvYXNzZXRzL2pzL2luY2x1ZGVzL3Byb2Nlc3NfZm9ybS5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EiLCJmaWxlIjoiZ2VuZXJhdGVkLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXNDb250ZW50IjpbIlxyXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbCk7XHJcblxyXG5tb2R1bGUuZXhwb3J0cyA9IHtcclxuXHJcblx0dGF4b25vbXlfYXJjaGl2ZXM6IDAsXHJcbiAgICB1cmxfcGFyYW1zOiB7fSxcclxuICAgIHRheF9hcmNoaXZlX3Jlc3VsdHNfdXJsOiBcIlwiLFxyXG4gICAgYWN0aXZlX3RheDogXCJcIixcclxuICAgIGZpZWxkczoge30sXHJcblx0aW5pdDogZnVuY3Rpb24odGF4b25vbXlfYXJjaGl2ZXMsIGN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSl7XHJcblxyXG4gICAgICAgIHRoaXMudGF4b25vbXlfYXJjaGl2ZXMgPSAwO1xyXG4gICAgICAgIHRoaXMudXJsX3BhcmFtcyA9IHt9O1xyXG4gICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgIHRoaXMuYWN0aXZlX3RheCA9IFwiXCI7XHJcblxyXG5cdFx0Ly90aGlzLiRmaWVsZHMgPSAkZmllbGRzO1xyXG4gICAgICAgIHRoaXMudGF4b25vbXlfYXJjaGl2ZXMgPSB0YXhvbm9teV9hcmNoaXZlcztcclxuICAgICAgICB0aGlzLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSA9IGN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZTtcclxuXHJcblx0XHR0aGlzLmNsZWFyVXJsQ29tcG9uZW50cygpO1xyXG5cclxuXHR9LFxyXG4gICAgc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmw6IGZ1bmN0aW9uKCRmb3JtLCBjdXJyZW50X3Jlc3VsdHNfdXJsLCBnZXRfYWN0aXZlKSB7XHJcblxyXG4gICAgICAgIHZhciBzZWxmID0gdGhpcztcclxuXHRcdHRoaXMuY2xlYXJUYXhBcmNoaXZlUmVzdWx0c1VybCgpO1xyXG4gICAgICAgIC8vdmFyIGN1cnJlbnRfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgIGlmKHRoaXMudGF4b25vbXlfYXJjaGl2ZXMhPTEpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YoZ2V0X2FjdGl2ZSk9PVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdHZhciBnZXRfYWN0aXZlID0gZmFsc2U7XHJcblx0XHR9XHJcblxyXG4gICAgICAgIC8vY2hlY2sgdG8gc2VlIGlmIHdlIGhhdmUgYW55IHRheG9ub21pZXMgc2VsZWN0ZWRcclxuICAgICAgICAvL2lmIHNvLCBjaGVjayB0aGVpciByZXdyaXRlcyBhbmQgdXNlIHRob3NlIGFzIHRoZSByZXN1bHRzIHVybFxyXG4gICAgICAgIHZhciAkZmllbGQgPSBmYWxzZTtcclxuICAgICAgICB2YXIgZmllbGRfbmFtZSA9IFwiXCI7XHJcbiAgICAgICAgdmFyIGZpZWxkX3ZhbHVlID0gXCJcIjtcclxuXHJcbiAgICAgICAgdmFyICRhY3RpdmVfdGF4b25vbXkgPSAkZm9ybS4kZmllbGRzLnBhcmVudCgpLmZpbmQoXCJbZGF0YS1zZi10YXhvbm9teS1hcmNoaXZlPScxJ11cIik7XHJcbiAgICAgICAgaWYoJGFjdGl2ZV90YXhvbm9teS5sZW5ndGg9PTEpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAkZmllbGQgPSAkYWN0aXZlX3RheG9ub215O1xyXG5cclxuICAgICAgICAgICAgdmFyIGZpZWxkVHlwZSA9ICRmaWVsZC5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cclxuICAgICAgICAgICAgaWYgKChmaWVsZFR5cGUgPT0gXCJ0YWdcIikgfHwgKGZpZWxkVHlwZSA9PSBcImNhdGVnb3J5XCIpIHx8IChmaWVsZFR5cGUgPT0gXCJ0YXhvbm9teVwiKSkge1xyXG4gICAgICAgICAgICAgICAgdmFyIHRheG9ub215X3ZhbHVlID0gc2VsZi5wcm9jZXNzVGF4b25vbXkoJGZpZWxkLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgIGZpZWxkX25hbWUgPSAkZmllbGQuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuICAgICAgICAgICAgICAgIHZhciB0YXhvbm9teV9uYW1lID0gZmllbGRfbmFtZS5yZXBsYWNlKFwiX3NmdF9cIiwgXCJcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYgKHRheG9ub215X3ZhbHVlKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgZmllbGRfdmFsdWUgPSB0YXhvbm9teV92YWx1ZS52YWx1ZTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoZmllbGRfdmFsdWU9PVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICRmaWVsZCA9IGZhbHNlO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZigoc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUhPVwiXCIpJiYoc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUhPXRheG9ub215X25hbWUpKVxyXG4gICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSBjdXJyZW50X3Jlc3VsdHNfdXJsO1xyXG4gICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZigoKGZpZWxkX3ZhbHVlPT1cIlwiKXx8KCEkZmllbGQpICkpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAkZm9ybS4kZmllbGRzLmVhY2goZnVuY3Rpb24gKCkge1xyXG5cclxuICAgICAgICAgICAgICAgIGlmICghJGZpZWxkKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBmaWVsZFR5cGUgPSAkKHRoaXMpLmF0dHIoXCJkYXRhLXNmLWZpZWxkLXR5cGVcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmICgoZmllbGRUeXBlID09IFwidGFnXCIpIHx8IChmaWVsZFR5cGUgPT0gXCJjYXRlZ29yeVwiKSB8fCAoZmllbGRUeXBlID09IFwidGF4b25vbXlcIikpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHRheG9ub215X3ZhbHVlID0gc2VsZi5wcm9jZXNzVGF4b25vbXkoJCh0aGlzKSwgdHJ1ZSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZpZWxkX25hbWUgPSAkKHRoaXMpLmF0dHIoXCJkYXRhLXNmLWZpZWxkLW5hbWVcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodGF4b25vbXlfdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBmaWVsZF92YWx1ZSA9IHRheG9ub215X3ZhbHVlLnZhbHVlO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChmaWVsZF92YWx1ZSAhPSBcIlwiKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRmaWVsZCA9ICQodGhpcyk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9KTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKCAoJGZpZWxkKSAmJiAoZmllbGRfdmFsdWUgIT0gXCJcIiApKSB7XHJcbiAgICAgICAgICAgIC8vaWYgd2UgZm91bmQgYSBmaWVsZFxyXG5cdFx0XHR2YXIgcmV3cml0ZV9hdHRyID0gKCRmaWVsZC5hdHRyKFwiZGF0YS1zZi10ZXJtLXJld3JpdGVcIikpO1xyXG5cclxuICAgICAgICAgICAgaWYocmV3cml0ZV9hdHRyIT1cIlwiKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHJld3JpdGUgPSBKU09OLnBhcnNlKHJld3JpdGVfYXR0cik7XHJcbiAgICAgICAgICAgICAgICB2YXIgaW5wdXRfdHlwZSA9ICRmaWVsZC5hdHRyKFwiZGF0YS1zZi1maWVsZC1pbnB1dC10eXBlXCIpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5hY3RpdmVfdGF4ID0gZmllbGRfbmFtZTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL2ZpbmQgdGhlIGFjdGl2ZSBlbGVtZW50XHJcbiAgICAgICAgICAgICAgICBpZiAoKGlucHV0X3R5cGUgPT0gXCJyYWRpb1wiKSB8fCAoaW5wdXRfdHlwZSA9PSBcImNoZWNrYm94XCIpKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vdmFyICRhY3RpdmUgPSAkZmllbGQuZmluZChcIi5zZi1vcHRpb24tYWN0aXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIC8vZXhwbG9kZSB0aGUgdmFsdWVzIGlmIHRoZXJlIGlzIGEgZGVsaW1cclxuICAgICAgICAgICAgICAgICAgICAvL2ZpZWxkX3ZhbHVlXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBpc19zaW5nbGVfdmFsdWUgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBmaWVsZF92YWx1ZXMgPSBmaWVsZF92YWx1ZS5zcGxpdChcIixcIikuam9pbihcIitcIikuc3BsaXQoXCIrXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIGlmIChmaWVsZF92YWx1ZXMubGVuZ3RoID4gMSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpc19zaW5nbGVfdmFsdWUgPSBmYWxzZTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmIChpc19zaW5nbGVfdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkaW5wdXQgPSAkZmllbGQuZmluZChcImlucHV0W3ZhbHVlPSdcIiArIGZpZWxkX3ZhbHVlICsgXCInXVwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkaW5wdXQucGFyZW50KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aCA9ICRhY3RpdmUuYXR0cihcImRhdGEtc2YtZGVwdGhcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL25vdyBsb29wIHRocm91Z2ggcGFyZW50cyB0byBncmFiIHRoZWlyIG5hbWVzXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciB2YWx1ZXMgPSBuZXcgQXJyYXkoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnB1c2goZmllbGRfdmFsdWUpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgZm9yICh2YXIgaSA9IGRlcHRoOyBpID4gMDsgaS0tKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkYWN0aXZlID0gJGFjdGl2ZS5wYXJlbnQoKS5wYXJlbnQoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5wdXNoKCRhY3RpdmUuZmluZChcImlucHV0XCIpLnZhbCgpKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnJldmVyc2UoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vZ3JhYiB0aGUgcmV3cml0ZSBmb3IgdGhpcyBkZXB0aFxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgYWN0aXZlX3Jld3JpdGUgPSByZXdyaXRlW2RlcHRoXTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHVybCA9IGFjdGl2ZV9yZXdyaXRlO1xyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vdGhlbiBtYXAgZnJvbSB0aGUgcGFyZW50cyB0byB0aGUgZGVwdGhcclxuICAgICAgICAgICAgICAgICAgICAgICAgJCh2YWx1ZXMpLmVhY2goZnVuY3Rpb24gKGluZGV4LCB2YWx1ZSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHVybCA9IHVybC5yZXBsYWNlKFwiW1wiICsgaW5kZXggKyBcIl1cIiwgdmFsdWUpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSB1cmw7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2Uge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9pZiB0aGVyZSBhcmUgbXVsdGlwbGUgdmFsdWVzLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3RoZW4gd2UgbmVlZCB0byBjaGVjayBmb3IgMyB0aGluZ3M6XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL2lmIHRoZSB2YWx1ZXMgc2VsZWN0ZWQgYXJlIGFsbCBpbiB0aGUgc2FtZSB0cmVlIHRoZW4gd2UgY2FuIGRvIHNvbWUgY2xldmVyIHJld3JpdGUgc3R1ZmZcclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9tZXJnZSBhbGwgdmFsdWVzIGluIHNhbWUgbGV2ZWwsIHRoZW4gY29tYmluZSB0aGUgbGV2ZWxzXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL2lmIHRoZXkgYXJlIGZyb20gZGlmZmVyZW50IHRyZWVzIHRoZW4ganVzdCBjb21iaW5lIHRoZW0gb3IganVzdCB1c2UgYGZpZWxkX3ZhbHVlYFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvKlxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aHMgPSBuZXcgQXJyYXkoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICQoZmllbGRfdmFsdWVzKS5lYWNoKGZ1bmN0aW9uIChpbmRleCwgdmFsKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRpbnB1dCA9ICRmaWVsZC5maW5kKFwiaW5wdXRbdmFsdWU9J1wiICsgZmllbGRfdmFsdWUgKyBcIiddXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkaW5wdXQucGFyZW50KCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGRlcHRoID0gJGFjdGl2ZS5hdHRyKFwiZGF0YS1zZi1kZXB0aFwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgIC8vZGVwdGhzLnB1c2goZGVwdGgpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgIH0pOyovXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2UgaWYgKChpbnB1dF90eXBlID09IFwic2VsZWN0XCIpIHx8IChpbnB1dF90eXBlID09IFwibXVsdGlzZWxlY3RcIikpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGlzX3NpbmdsZV92YWx1ZSA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZpZWxkX3ZhbHVlcyA9IGZpZWxkX3ZhbHVlLnNwbGl0KFwiLFwiKS5qb2luKFwiK1wiKS5zcGxpdChcIitcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKGZpZWxkX3ZhbHVlcy5sZW5ndGggPiAxKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlzX3NpbmdsZV92YWx1ZSA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKGlzX3NpbmdsZV92YWx1ZSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkZmllbGQuZmluZChcIm9wdGlvblt2YWx1ZT0nXCIgKyBmaWVsZF92YWx1ZSArIFwiJ11cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aCA9ICRhY3RpdmUuYXR0cihcImRhdGEtc2YtZGVwdGhcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgdmFsdWVzID0gbmV3IEFycmF5KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5wdXNoKGZpZWxkX3ZhbHVlKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGkgPSBkZXB0aDsgaSA+IDA7IGktLSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGFjdGl2ZSA9ICRhY3RpdmUucHJldkFsbChcIm9wdGlvbltkYXRhLXNmLWRlcHRoPSdcIiArIChpIC0gMSkgKyBcIiddXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnB1c2goJGFjdGl2ZS52YWwoKSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5yZXZlcnNlKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBhY3RpdmVfcmV3cml0ZSA9IHJld3JpdGVbZGVwdGhdO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgdXJsID0gYWN0aXZlX3Jld3JpdGU7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICQodmFsdWVzKS5lYWNoKGZ1bmN0aW9uIChpbmRleCwgdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmwgPSB1cmwucmVwbGFjZShcIltcIiArIGluZGV4ICsgXCJdXCIsIHZhbHVlKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnRheF9hcmNoaXZlX3Jlc3VsdHNfdXJsID0gdXJsO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgfVxyXG4gICAgICAgIC8vdGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCA9IGN1cnJlbnRfcmVzdWx0c191cmw7XHJcbiAgICB9LFxyXG4gICAgZ2V0UmVzdWx0c1VybDogZnVuY3Rpb24oJGZvcm0sIGN1cnJlbnRfcmVzdWx0c191cmwpIHtcclxuXHJcbiAgICAgICAgLy90aGlzLnNldFRheEFyY2hpdmVSZXN1bHRzVXJsKCRmb3JtLCBjdXJyZW50X3Jlc3VsdHNfdXJsKTtcclxuXHJcbiAgICAgICAgaWYodGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybD09XCJcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybiBjdXJyZW50X3Jlc3VsdHNfdXJsO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcmV0dXJuIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmw7XHJcbiAgICB9LFxyXG5cdGdldFVybFBhcmFtczogZnVuY3Rpb24oJGZvcm0pe1xyXG5cclxuXHRcdHRoaXMuYnVpbGRVcmxDb21wb25lbnRzKCRmb3JtLCB0cnVlKTtcclxuXHJcbiAgICAgICAgaWYodGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCE9XCJcIilcclxuICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICBpZih0aGlzLmFjdGl2ZV90YXghPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBmaWVsZF9uYW1lID0gdGhpcy5hY3RpdmVfdGF4O1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHR5cGVvZih0aGlzLnVybF9wYXJhbXNbZmllbGRfbmFtZV0pIT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGRlbGV0ZSB0aGlzLnVybF9wYXJhbXNbZmllbGRfbmFtZV07XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG5cdFx0cmV0dXJuIHRoaXMudXJsX3BhcmFtcztcclxuXHR9LFxyXG5cdGNsZWFyVXJsQ29tcG9uZW50czogZnVuY3Rpb24oKXtcclxuXHRcdC8vdGhpcy51cmxfY29tcG9uZW50cyA9IFwiXCI7XHJcblx0XHR0aGlzLnVybF9wYXJhbXMgPSB7fTtcclxuXHR9LFxyXG5cdGNsZWFyVGF4QXJjaGl2ZVJlc3VsdHNVcmw6IGZ1bmN0aW9uKCkge1xyXG5cdFx0dGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCA9ICcnO1xyXG5cdH0sXHJcblx0ZGlzYWJsZUlucHV0czogZnVuY3Rpb24oJGZvcm0pe1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHQkZm9ybS4kZmllbGRzLmVhY2goZnVuY3Rpb24oKXtcclxuXHRcdFx0XHJcblx0XHRcdHZhciAkaW5wdXRzID0gJCh0aGlzKS5maW5kKFwiaW5wdXQsIHNlbGVjdCwgLm1ldGEtc2xpZGVyXCIpO1xyXG5cdFx0XHQkaW5wdXRzLmF0dHIoXCJkaXNhYmxlZFwiLCBcImRpc2FibGVkXCIpO1xyXG5cdFx0XHQkaW5wdXRzLmF0dHIoXCJkaXNhYmxlZFwiLCB0cnVlKTtcclxuXHRcdFx0JGlucHV0cy5wcm9wKFwiZGlzYWJsZWRcIiwgdHJ1ZSk7XHJcblx0XHRcdCRpbnB1dHMudHJpZ2dlcihcImNob3Nlbjp1cGRhdGVkXCIpO1xyXG5cdFx0XHRcclxuXHRcdH0pO1xyXG5cdFx0XHJcblx0XHRcclxuXHR9LFxyXG5cdGVuYWJsZUlucHV0czogZnVuY3Rpb24oJGZvcm0pe1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0JGZvcm0uJGZpZWxkcy5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdHZhciAkaW5wdXRzID0gJCh0aGlzKS5maW5kKFwiaW5wdXQsIHNlbGVjdCwgLm1ldGEtc2xpZGVyXCIpO1xyXG5cdFx0XHQkaW5wdXRzLnByb3AoXCJkaXNhYmxlZFwiLCBmYWxzZSk7XHJcblx0XHRcdCRpbnB1dHMuYXR0cihcImRpc2FibGVkXCIsIGZhbHNlKTtcclxuXHRcdFx0JGlucHV0cy50cmlnZ2VyKFwiY2hvc2VuOnVwZGF0ZWRcIik7XHRcdFx0XHJcblx0XHR9KTtcclxuXHRcdFxyXG5cdFx0XHJcblx0fSxcclxuXHRidWlsZFVybENvbXBvbmVudHM6IGZ1bmN0aW9uKCRmb3JtLCBjbGVhcl9jb21wb25lbnRzKXtcclxuXHRcdFxyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHRpZih0eXBlb2YoY2xlYXJfY29tcG9uZW50cykhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGNsZWFyX2NvbXBvbmVudHM9PXRydWUpXHJcblx0XHRcdHtcclxuXHRcdFx0XHR0aGlzLmNsZWFyVXJsQ29tcG9uZW50cygpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdCRmb3JtLiRmaWVsZHMuZWFjaChmdW5jdGlvbigpe1xyXG5cdFx0XHRcclxuXHRcdFx0dmFyIGZpZWxkTmFtZSA9ICQodGhpcykuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuXHRcdFx0dmFyIGZpZWxkVHlwZSA9ICQodGhpcykuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdFx0XHJcblx0XHRcdGlmKGZpZWxkVHlwZT09XCJzZWFyY2hcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc1NlYXJjaEZpZWxkKCQodGhpcykpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoKGZpZWxkVHlwZT09XCJ0YWdcIil8fChmaWVsZFR5cGU9PVwiY2F0ZWdvcnlcIil8fChmaWVsZFR5cGU9PVwidGF4b25vbXlcIikpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NUYXhvbm9teSgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJzb3J0X29yZGVyXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NTb3J0T3JkZXJGaWVsZCgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJwb3N0c19wZXJfcGFnZVwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0c2VsZi5wcm9jZXNzUmVzdWx0c1BlclBhZ2VGaWVsZCgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJhdXRob3JcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc0F1dGhvcigkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJwb3N0X3R5cGVcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc1Bvc3RUeXBlKCQodGhpcykpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoZmllbGRUeXBlPT1cInBvc3RfZGF0ZVwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0c2VsZi5wcm9jZXNzUG9zdERhdGUoJCh0aGlzKSk7XHJcblx0XHRcdH1cclxuXHRcdFx0ZWxzZSBpZihmaWVsZFR5cGU9PVwicG9zdF9tZXRhXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NQb3N0TWV0YSgkKHRoaXMpKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlXHJcblx0XHRcdHtcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH0pO1xyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzU2VhcmNoRmllbGQ6IGZ1bmN0aW9uKCRjb250YWluZXIpXHJcblx0e1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHR2YXIgJGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiaW5wdXRbbmFtZV49J19zZl9zZWFyY2gnXVwiKTtcclxuXHRcdFxyXG5cdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0e1xyXG5cdFx0XHR2YXIgZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHR2YXIgZmllbGRWYWwgPSAkZmllbGQudmFsKCk7XHJcblx0XHRcdFxyXG5cdFx0XHRpZihmaWVsZFZhbCE9XCJcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZfc2Zfcz1cIitlbmNvZGVVUklDb21wb25lbnQoZmllbGRWYWwpO1xyXG5cdFx0XHRcdHNlbGYudXJsX3BhcmFtc1snX3NmX3MnXSA9IGVuY29kZVVSSUNvbXBvbmVudChmaWVsZFZhbCk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHR9LFxyXG5cdHByb2Nlc3NTb3J0T3JkZXJGaWVsZDogZnVuY3Rpb24oJGNvbnRhaW5lcilcclxuXHR7XHJcblx0XHR0aGlzLnByb2Nlc3NBdXRob3IoJGNvbnRhaW5lcik7XHJcblx0XHRcclxuXHR9LFxyXG5cdHByb2Nlc3NSZXN1bHRzUGVyUGFnZUZpZWxkOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHRoaXMucHJvY2Vzc0F1dGhvcigkY29udGFpbmVyKTtcclxuXHRcdFxyXG5cdH0sXHJcblx0Z2V0QWN0aXZlVGF4OiBmdW5jdGlvbigkZmllbGQpIHtcclxuXHRcdHJldHVybiB0aGlzLmFjdGl2ZV90YXg7XHJcblx0fSxcclxuXHRnZXRTZWxlY3RWYWw6IGZ1bmN0aW9uKCRmaWVsZCl7XHJcblxyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0aWYoJGZpZWxkLnZhbCgpIT0wKVxyXG5cdFx0e1xyXG5cdFx0XHRmaWVsZFZhbCA9ICRmaWVsZC52YWwoKTtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0aWYoZmllbGRWYWw9PW51bGwpXHJcblx0XHR7XHJcblx0XHRcdGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0cmV0dXJuIGZpZWxkVmFsO1xyXG5cdH0sXHJcblx0Z2V0TWV0YVNlbGVjdFZhbDogZnVuY3Rpb24oJGZpZWxkKXtcclxuXHRcdFxyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0ZmllbGRWYWwgPSAkZmllbGQudmFsKCk7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0aWYoZmllbGRWYWw9PW51bGwpXHJcblx0XHR7XHJcblx0XHRcdGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0cmV0dXJuIGZpZWxkVmFsO1xyXG5cdH0sXHJcblx0Z2V0TXVsdGlTZWxlY3RWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHR2YXIgZGVsaW0gPSBcIitcIjtcclxuXHRcdGlmKG9wZXJhdG9yPT1cIm9yXCIpXHJcblx0XHR7XHJcblx0XHRcdGRlbGltID0gXCIsXCI7XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZigkZmllbGQudmFsKCkpPT1cIm9iamVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZigkZmllbGQudmFsKCkhPW51bGwpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRyZXR1cm4gJGZpZWxkLnZhbCgpLmpvaW4oZGVsaW0pO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHR9LFxyXG5cdGdldE1ldGFNdWx0aVNlbGVjdFZhbDogZnVuY3Rpb24oJGZpZWxkLCBvcGVyYXRvcil7XHJcblx0XHRcclxuXHRcdHZhciBkZWxpbSA9IFwiLSstXCI7XHJcblx0XHRpZihvcGVyYXRvcj09XCJvclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRkZWxpbSA9IFwiLSwtXCI7XHJcblx0XHR9XHJcblx0XHRcdFx0XHJcblx0XHRpZih0eXBlb2YoJGZpZWxkLnZhbCgpKT09XCJvYmplY3RcIilcclxuXHRcdHtcclxuXHRcdFx0aWYoJGZpZWxkLnZhbCgpIT1udWxsKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIGZpZWxkdmFsID0gW107XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0JCgkZmllbGQudmFsKCkpLmVhY2goZnVuY3Rpb24oaW5kZXgsdmFsdWUpe1xyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZHZhbC5wdXNoKCh2YWx1ZSkpO1xyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdHJldHVybiBmaWVsZHZhbC5qb2luKGRlbGltKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gXCJcIjtcclxuXHRcdFxyXG5cdH0sXHJcblx0Z2V0Q2hlY2tib3hWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKXtcclxuXHRcdFx0aWYoJCh0aGlzKS5wcm9wKFwiY2hlY2tlZFwiKT09dHJ1ZSlcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHJldHVybiAkKHRoaXMpLnZhbCgpO1xyXG5cdFx0XHR9XHJcblx0XHR9KS5nZXQoKTtcclxuXHRcdFxyXG5cdFx0dmFyIGRlbGltID0gXCIrXCI7XHJcblx0XHRpZihvcGVyYXRvcj09XCJvclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRkZWxpbSA9IFwiLFwiO1xyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWwuam9pbihkZWxpbSk7XHJcblx0fSxcclxuXHRnZXRNZXRhQ2hlY2tib3hWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKXtcclxuXHRcdFx0aWYoJCh0aGlzKS5wcm9wKFwiY2hlY2tlZFwiKT09dHJ1ZSlcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHJldHVybiAoJCh0aGlzKS52YWwoKSk7XHJcblx0XHRcdH1cclxuXHRcdH0pLmdldCgpO1xyXG5cdFx0XHJcblx0XHR2YXIgZGVsaW0gPSBcIi0rLVwiO1xyXG5cdFx0aWYob3BlcmF0b3I9PVwib3JcIilcclxuXHRcdHtcclxuXHRcdFx0ZGVsaW0gPSBcIi0sLVwiO1xyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWwuam9pbihkZWxpbSk7XHJcblx0fSxcclxuXHRnZXRSYWRpb1ZhbDogZnVuY3Rpb24oJGZpZWxkKXtcclxuXHRcdFx0XHRcdFx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKVxyXG5cdFx0e1xyXG5cdFx0XHRpZigkKHRoaXMpLnByb3AoXCJjaGVja2VkXCIpPT10cnVlKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0cmV0dXJuICQodGhpcykudmFsKCk7XHJcblx0XHRcdH1cclxuXHRcdFx0XHJcblx0XHR9KS5nZXQoKTtcclxuXHRcdFxyXG5cdFx0XHJcblx0XHRpZihmaWVsZFZhbFswXSE9MClcclxuXHRcdHtcclxuXHRcdFx0cmV0dXJuIGZpZWxkVmFsWzBdO1xyXG5cdFx0fVxyXG5cdH0sXHJcblx0Z2V0TWV0YVJhZGlvVmFsOiBmdW5jdGlvbigkZmllbGQpe1xyXG5cdFx0XHRcdFx0XHRcdFxyXG5cdFx0dmFyIGZpZWxkVmFsID0gJGZpZWxkLm1hcChmdW5jdGlvbigpXHJcblx0XHR7XHJcblx0XHRcdGlmKCQodGhpcykucHJvcChcImNoZWNrZWRcIik9PXRydWUpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRyZXR1cm4gJCh0aGlzKS52YWwoKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH0pLmdldCgpO1xyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWxbMF07XHJcblx0fSxcclxuXHRwcm9jZXNzQXV0aG9yOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHZhciBzZWxmID0gdGhpcztcclxuXHRcdFxyXG5cdFx0XHJcblx0XHR2YXIgZmllbGRUeXBlID0gJGNvbnRhaW5lci5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cdFx0dmFyIGlucHV0VHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtaW5wdXQtdHlwZVwiKTtcclxuXHRcdFxyXG5cdFx0dmFyICRmaWVsZDtcclxuXHRcdHZhciBmaWVsZE5hbWUgPSBcIlwiO1xyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0aWYoaW5wdXRUeXBlPT1cInNlbGVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJzZWxlY3RcIik7XHJcblx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRTZWxlY3RWYWwoJGZpZWxkKTsgXHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJtdWx0aXNlbGVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJzZWxlY3RcIik7XHJcblx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0dmFyIG9wZXJhdG9yID0gJGZpZWxkLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcclxuXHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldE11bHRpU2VsZWN0VmFsKCRmaWVsZCwgXCJvclwiKTtcclxuXHRcdFx0XHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJjaGVja2JveFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OmNoZWNrYm94XCIpO1xyXG5cdFx0XHRcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdHZhciBvcGVyYXRvciA9ICRjb250YWluZXIuZmluZChcIj4gdWxcIikuYXR0cihcImRhdGEtb3BlcmF0b3JcIik7XHJcblx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldENoZWNrYm94VmFsKCRmaWVsZCwgXCJvclwiKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cInJhZGlvXCIpXHJcblx0XHR7XHJcblx0XHRcdFxyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OnJhZGlvXCIpO1xyXG5cdFx0XHRcdFx0XHRcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRSYWRpb1ZhbCgkZmllbGQpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZihmaWVsZFZhbCkhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGZpZWxkVmFsIT1cIlwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0dmFyIGZpZWxkU2x1ZyA9IFwiXCI7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoZmllbGROYW1lPT1cIl9zZl9hdXRob3JcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZFNsdWcgPSBcImF1dGhvcnNcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZSBpZihmaWVsZE5hbWU9PVwiX3NmX3NvcnRfb3JkZXJcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZFNsdWcgPSBcInNvcnRfb3JkZXJcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZSBpZihmaWVsZE5hbWU9PVwiX3NmX3BwcFwiKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkU2x1ZyA9IFwiX3NmX3BwcFwiO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0XHRlbHNlIGlmKGZpZWxkTmFtZT09XCJfc2ZfcG9zdF90eXBlXCIpXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRTbHVnID0gXCJwb3N0X3R5cGVzXCI7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdGVsc2VcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKGZpZWxkU2x1ZyE9XCJcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHQvL3NlbGYudXJsX2NvbXBvbmVudHMgKz0gXCImXCIrZmllbGRTbHVnK1wiPVwiK2ZpZWxkVmFsO1xyXG5cdFx0XHRcdFx0c2VsZi51cmxfcGFyYW1zW2ZpZWxkU2x1Z10gPSBmaWVsZFZhbDtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHRcdFxyXG5cdH0sXHJcblx0cHJvY2Vzc1Bvc3RUeXBlIDogZnVuY3Rpb24oJHRoaXMpe1xyXG5cdFx0XHJcblx0XHR0aGlzLnByb2Nlc3NBdXRob3IoJHRoaXMpO1xyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzUG9zdE1ldGE6IGZ1bmN0aW9uKCRjb250YWluZXIpXHJcblx0e1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHR2YXIgZmllbGRUeXBlID0gJGNvbnRhaW5lci5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cdFx0dmFyIGlucHV0VHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtaW5wdXQtdHlwZVwiKTtcclxuXHRcdHZhciBtZXRhVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtbWV0YS10eXBlXCIpO1xyXG5cclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHR2YXIgJGZpZWxkO1xyXG5cdFx0dmFyIGZpZWxkTmFtZSA9IFwiXCI7XHJcblx0XHRcclxuXHRcdGlmKG1ldGFUeXBlPT1cIm51bWJlclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZihpbnB1dFR5cGU9PVwicmFuZ2UtbnVtYmVyXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCIuc2YtbWV0YS1yYW5nZS1udW1iZXIgaW5wdXRcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIHZhbHVlcyA9IFtdO1xyXG5cdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdHZhbHVlcy5wdXNoKCQodGhpcykudmFsKCkpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gdmFsdWVzLmpvaW4oXCIrXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cInJhbmdlLXNsaWRlclwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLW1ldGEtcmFuZ2Utc2xpZGVyIGlucHV0XCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdC8vZ2V0IGFueSBudW1iZXIgZm9ybWF0dGluZyBzdHVmZlxyXG5cdFx0XHRcdHZhciAkbWV0YV9yYW5nZSA9ICRjb250YWluZXIuZmluZChcIi5zZi1tZXRhLXJhbmdlLXNsaWRlclwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHR2YXIgZGVjaW1hbF9wbGFjZXMgPSAkbWV0YV9yYW5nZS5hdHRyKFwiZGF0YS1kZWNpbWFsLXBsYWNlc1wiKTtcclxuXHRcdFx0XHR2YXIgdGhvdXNhbmRfc2VwZXJhdG9yID0gJG1ldGFfcmFuZ2UuYXR0cihcImRhdGEtdGhvdXNhbmQtc2VwZXJhdG9yXCIpO1xyXG5cdFx0XHRcdHZhciBkZWNpbWFsX3NlcGVyYXRvciA9ICRtZXRhX3JhbmdlLmF0dHIoXCJkYXRhLWRlY2ltYWwtc2VwZXJhdG9yXCIpO1xyXG5cclxuXHRcdFx0XHR2YXIgZmllbGRfZm9ybWF0ID0gd051bWIoe1xyXG5cdFx0XHRcdFx0bWFyazogZGVjaW1hbF9zZXBlcmF0b3IsXHJcblx0XHRcdFx0XHRkZWNpbWFsczogcGFyc2VGbG9hdChkZWNpbWFsX3BsYWNlcyksXHJcblx0XHRcdFx0XHR0aG91c2FuZDogdGhvdXNhbmRfc2VwZXJhdG9yXHJcblx0XHRcdFx0fSk7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIHZhbHVlcyA9IFtdO1xyXG5cclxuXHJcblx0XHRcdFx0dmFyIHNsaWRlcl9vYmplY3QgPSAkY29udGFpbmVyLmZpbmQoXCIubWV0YS1zbGlkZXJcIilbMF07XHJcblx0XHRcdFx0Ly92YWwgZnJvbSBzbGlkZXIgb2JqZWN0XHJcblx0XHRcdFx0dmFyIHNsaWRlcl92YWwgPSBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIuZ2V0KCk7XHJcblxyXG5cdFx0XHRcdHZhbHVlcy5wdXNoKGZpZWxkX2Zvcm1hdC5mcm9tKHNsaWRlcl92YWxbMF0pKTtcclxuXHRcdFx0XHR2YWx1ZXMucHVzaChmaWVsZF9mb3JtYXQuZnJvbShzbGlkZXJfdmFsWzFdKSk7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0ZmllbGRWYWwgPSB2YWx1ZXMuam9pbihcIitcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJG1ldGFfcmFuZ2UuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1yYWRpb1wiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLWlucHV0LXJhbmdlLXJhZGlvXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg9PTApXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0Ly90aGVuIHRyeSBhZ2Fpbiwgd2UgbXVzdCBiZSB1c2luZyBhIHNpbmdsZSBmaWVsZFxyXG5cdFx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiPiB1bFwiKTtcclxuXHRcdFx0XHR9XHJcblxyXG5cdFx0XHRcdHZhciAkbWV0YV9yYW5nZSA9ICRjb250YWluZXIuZmluZChcIi5zZi1tZXRhLXJhbmdlXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdC8vdGhlcmUgaXMgYW4gZWxlbWVudCB3aXRoIGEgZnJvbS90byBjbGFzcyAtIHNvIHdlIG5lZWQgdG8gZ2V0IHRoZSB2YWx1ZXMgb2YgdGhlIGZyb20gJiB0byBpbnB1dCBmaWVsZHMgc2VwZXJhdGVseVxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0XHR7XHRcclxuXHRcdFx0XHRcdHZhciBmaWVsZF92YWxzID0gW107XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHR2YXIgJHJhZGlvcyA9ICQodGhpcykuZmluZChcIi5zZi1pbnB1dC1yYWRpb1wiKTtcclxuXHRcdFx0XHRcdFx0ZmllbGRfdmFscy5wdXNoKHNlbGYuZ2V0TWV0YVJhZGlvVmFsKCRyYWRpb3MpKTtcclxuXHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0XHR9KTtcclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0Ly9wcmV2ZW50IHNlY29uZCBudW1iZXIgZnJvbSBiZWluZyBsb3dlciB0aGFuIHRoZSBmaXJzdFxyXG5cdFx0XHRcdFx0aWYoZmllbGRfdmFscy5sZW5ndGg9PTIpXHJcblx0XHRcdFx0XHR7XHJcblx0XHRcdFx0XHRcdGlmKE51bWJlcihmaWVsZF92YWxzWzFdKTxOdW1iZXIoZmllbGRfdmFsc1swXSkpXHJcblx0XHRcdFx0XHRcdHtcclxuXHRcdFx0XHRcdFx0XHRmaWVsZF92YWxzWzFdID0gZmllbGRfdmFsc1swXTtcclxuXHRcdFx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkX3ZhbHMuam9pbihcIitcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD09MSlcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuZmluZChcIi5zZi1pbnB1dC1yYWRpb1wiKS5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9ICRtZXRhX3JhbmdlLmF0dHIoXCJkYXRhLXNmLWZpZWxkLW5hbWVcIik7XHJcblx0XHRcdFx0fVxyXG5cclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1zZWxlY3RcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcIi5zZi1pbnB1dC1zZWxlY3RcIik7XHJcblx0XHRcdFx0dmFyICRtZXRhX3JhbmdlID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLW1ldGEtcmFuZ2VcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0Ly90aGVyZSBpcyBhbiBlbGVtZW50IHdpdGggYSBmcm9tL3RvIGNsYXNzIC0gc28gd2UgbmVlZCB0byBnZXQgdGhlIHZhbHVlcyBvZiB0aGUgZnJvbSAmIHRvIGlucHV0IGZpZWxkcyBzZXBlcmF0ZWx5XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdHZhciBmaWVsZF92YWxzID0gW107XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHR2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG5cdFx0XHRcdFx0XHRmaWVsZF92YWxzLnB1c2goc2VsZi5nZXRNZXRhU2VsZWN0VmFsKCR0aGlzKSk7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0fSk7XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdC8vcHJldmVudCBzZWNvbmQgbnVtYmVyIGZyb20gYmVpbmcgbG93ZXIgdGhhbiB0aGUgZmlyc3RcclxuXHRcdFx0XHRcdGlmKGZpZWxkX3ZhbHMubGVuZ3RoPT0yKVxyXG5cdFx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0XHRpZihOdW1iZXIoZmllbGRfdmFsc1sxXSk8TnVtYmVyKGZpZWxkX3ZhbHNbMF0pKVxyXG5cdFx0XHRcdFx0XHR7XHJcblx0XHRcdFx0XHRcdFx0ZmllbGRfdmFsc1sxXSA9IGZpZWxkX3ZhbHNbMF07XHJcblx0XHRcdFx0XHRcdH1cclxuXHRcdFx0XHRcdH1cclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkX3ZhbHMuam9pbihcIitcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD09MSlcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdGVsc2VcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkbWV0YV9yYW5nZS5hdHRyKFwiZGF0YS1zZi1maWVsZC1uYW1lXCIpO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1jaGVja2JveFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwidWwgPiBsaSBpbnB1dDpjaGVja2JveFwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRpZigkZmllbGQubGVuZ3RoPjApXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldENoZWNrYm94VmFsKCRmaWVsZCwgXCJhbmRcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHR9XHJcblx0XHRcdFxyXG5cdFx0XHRpZihmaWVsZE5hbWU9PVwiXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHRcdGVsc2UgaWYobWV0YVR5cGU9PVwiY2hvaWNlXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGlucHV0VHlwZT09XCJzZWxlY3RcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInNlbGVjdFwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0TWV0YVNlbGVjdFZhbCgkZmllbGQpOyBcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJtdWx0aXNlbGVjdFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRcdHZhciBvcGVyYXRvciA9ICRmaWVsZC5hdHRyKFwiZGF0YS1vcGVyYXRvclwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0TWV0YU11bHRpU2VsZWN0VmFsKCRmaWVsZCwgb3BlcmF0b3IpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cImNoZWNrYm94XCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OmNoZWNrYm94XCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHR2YXIgb3BlcmF0b3IgPSAkY29udGFpbmVyLmZpbmQoXCI+IHVsXCIpLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldE1ldGFDaGVja2JveFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwicmFkaW9cIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6cmFkaW9cIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRNZXRhUmFkaW9WYWwoJGZpZWxkKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkVmFsKTtcclxuXHRcdFx0aWYodHlwZW9mKCRmaWVsZCkhPT1cInVuZGVmaW5lZFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0Ly9mb3IgdGhvc2Ugd2hvIGluc2lzdCBvbiB1c2luZyAmIGFtcGVyc2FuZHMgaW4gdGhlIG5hbWUgb2YgdGhlIGN1c3RvbSBmaWVsZCAoISlcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9IChmaWVsZE5hbWUpO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYobWV0YVR5cGU9PVwiZGF0ZVwiKVxyXG5cdFx0e1xyXG5cdFx0XHRzZWxmLnByb2Nlc3NQb3N0RGF0ZSgkY29udGFpbmVyKTtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0aWYodHlwZW9mKGZpZWxkVmFsKSE9XCJ1bmRlZmluZWRcIilcclxuXHRcdHtcclxuXHRcdFx0aWYoZmllbGRWYWwhPVwiXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQvL3NlbGYudXJsX2NvbXBvbmVudHMgKz0gXCImXCIrZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkTmFtZSkrXCI9XCIrKGZpZWxkVmFsKTtcclxuXHRcdFx0XHRzZWxmLnVybF9wYXJhbXNbZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkTmFtZSldID0gKGZpZWxkVmFsKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdH0sXHJcblx0cHJvY2Vzc1Bvc3REYXRlOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHZhciBzZWxmID0gdGhpcztcclxuXHRcdFxyXG5cdFx0dmFyIGZpZWxkVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdHZhciBpbnB1dFR5cGUgPSAkY29udGFpbmVyLmF0dHIoXCJkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGVcIik7XHJcblx0XHRcclxuXHRcdHZhciAkZmllbGQ7XHJcblx0XHR2YXIgZmllbGROYW1lID0gXCJcIjtcclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHRcclxuXHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6dGV4dFwiKTtcclxuXHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFxyXG5cdFx0dmFyIGRhdGVzID0gW107XHJcblx0XHQkZmllbGQuZWFjaChmdW5jdGlvbigpe1xyXG5cdFx0XHRcclxuXHRcdFx0ZGF0ZXMucHVzaCgkKHRoaXMpLnZhbCgpKTtcclxuXHRcdFxyXG5cdFx0fSk7XHJcblx0XHRcclxuXHRcdGlmKCRmaWVsZC5sZW5ndGg9PTIpXHJcblx0XHR7XHJcblx0XHRcdGlmKChkYXRlc1swXSE9XCJcIil8fChkYXRlc1sxXSE9XCJcIikpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRmaWVsZFZhbCA9IGRhdGVzLmpvaW4oXCIrXCIpO1xyXG5cdFx0XHRcdGZpZWxkVmFsID0gZmllbGRWYWwucmVwbGFjZSgvXFwvL2csJycpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKCRmaWVsZC5sZW5ndGg9PTEpXHJcblx0XHR7XHJcblx0XHRcdGlmKGRhdGVzWzBdIT1cIlwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGRWYWwgPSBkYXRlcy5qb2luKFwiK1wiKTtcclxuXHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkVmFsLnJlcGxhY2UoL1xcLy9nLCcnKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRpZih0eXBlb2YoZmllbGRWYWwpIT1cInVuZGVmaW5lZFwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZihmaWVsZFZhbCE9XCJcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHZhciBmaWVsZFNsdWcgPSBcIlwiO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKGZpZWxkTmFtZT09XCJfc2ZfcG9zdF9kYXRlXCIpXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRTbHVnID0gXCJwb3N0X2RhdGVcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkU2x1ZyA9IGZpZWxkTmFtZTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoZmllbGRTbHVnIT1cIlwiKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZcIitmaWVsZFNsdWcrXCI9XCIrZmllbGRWYWw7XHJcblx0XHRcdFx0XHRzZWxmLnVybF9wYXJhbXNbZmllbGRTbHVnXSA9IGZpZWxkVmFsO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzVGF4b25vbXk6IGZ1bmN0aW9uKCRjb250YWluZXIsIHJldHVybl9vYmplY3QpXHJcblx0e1xyXG4gICAgICAgIGlmKHR5cGVvZihyZXR1cm5fb2JqZWN0KT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybl9vYmplY3QgPSBmYWxzZTtcclxuICAgICAgICB9XHJcblxyXG5cdFx0Ly9pZigpXHRcdFx0XHRcdFxyXG5cdFx0Ly92YXIgZmllbGROYW1lID0gJCh0aGlzKS5hdHRyKFwiZGF0YS1zZi1maWVsZC1uYW1lXCIpO1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFxyXG5cdFx0dmFyIGZpZWxkVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdHZhciBpbnB1dFR5cGUgPSAkY29udGFpbmVyLmF0dHIoXCJkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGVcIik7XHJcblx0XHRcclxuXHRcdHZhciAkZmllbGQ7XHJcblx0XHR2YXIgZmllbGROYW1lID0gXCJcIjtcclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHRcclxuXHRcdGlmKGlucHV0VHlwZT09XCJzZWxlY3RcIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdFxyXG5cdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0U2VsZWN0VmFsKCRmaWVsZCk7IFxyXG5cdFx0fVxyXG5cdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwibXVsdGlzZWxlY3RcIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdHZhciBvcGVyYXRvciA9ICRmaWVsZC5hdHRyKFwiZGF0YS1vcGVyYXRvclwiKTtcclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRNdWx0aVNlbGVjdFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cImNoZWNrYm94XCIpXHJcblx0XHR7XHJcblx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6Y2hlY2tib3hcIik7XHJcblx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0e1xyXG5cdFx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcclxuXHRcdFx0XHR2YXIgb3BlcmF0b3IgPSAkY29udGFpbmVyLmZpbmQoXCI+IHVsXCIpLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRDaGVja2JveFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwicmFkaW9cIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwidWwgPiBsaSBpbnB1dDpyYWRpb1wiKTtcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRSYWRpb1ZhbCgkZmllbGQpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZihmaWVsZFZhbCkhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGZpZWxkVmFsIT1cIlwiKVxyXG5cdFx0XHR7XHJcbiAgICAgICAgICAgICAgICBpZihyZXR1cm5fb2JqZWN0PT10cnVlKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB7bmFtZTogZmllbGROYW1lLCB2YWx1ZTogZmllbGRWYWx9O1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZcIitmaWVsZE5hbWUrXCI9XCIrZmllbGRWYWw7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi51cmxfcGFyYW1zW2ZpZWxkTmFtZV0gPSBmaWVsZFZhbDtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcbiAgICAgICAgaWYocmV0dXJuX29iamVjdD09dHJ1ZSlcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICB9XHJcblx0fVxyXG59OyJdfQ==
},{}],5:[function(require,module,exports){

module.exports = {
	
	searchForms: {},
	
	init: function(){
		
		
	},
	addSearchForm: function(id, object){
		
		this.searchForms[id] = object;
	},
	getSearchForm: function(id)
	{
		return this.searchForms[id];	
	}
	
};
},{}],6:[function(require,module,exports){
(function (global){

var $ 				= (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null);

module.exports = {
	
	init: function(){
		$(document).on("sf:ajaxfinish", ".searchandfilter", function( e, data ) {
			var display_method = data.object.display_result_method;
			if ( display_method === 'custom_edd_store' ) {
				$('input.edd-add-to-cart').css('display', "none");
				$('a.edd-add-to-cart').addClass('edd-has-js');
			} else if ( display_method === 'custom_layouts' ) {
				if ( $('.cl-layout').hasClass( 'cl-layout--masonry' ) ) {
					//then re-init masonry
					const masonryContainer = document.querySelectorAll( '.cl-layout--masonry' );
					if ( masonryContainer.length > 0 ) {
						const customLayoutGrid = new Masonry( '.cl-layout--masonry', {
							// options...
							itemSelector: '.cl-layout__item',
							//columnWidth: 319
							percentPosition: true,
							//gutter: 10,
							transitionDuration: 0,
						} );
						imagesLoaded( masonryContainer ).on( 'progress', function() {
							customLayoutGrid.layout();
						} );
					}
				}
			}
		});
	},

};
}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
//# sourceMappingURL=data:application/json;charset:utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9wdWJsaWMvYXNzZXRzL2pzL2luY2x1ZGVzL3RoaXJkcGFydHkuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IjtBQUFBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6ImdlbmVyYXRlZC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzQ29udGVudCI6WyJcbnZhciAkIFx0XHRcdFx0PSAodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvd1snalF1ZXJ5J10gOiB0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsWydqUXVlcnknXSA6IG51bGwpO1xuXG5tb2R1bGUuZXhwb3J0cyA9IHtcblx0XG5cdGluaXQ6IGZ1bmN0aW9uKCl7XG5cdFx0JChkb2N1bWVudCkub24oXCJzZjphamF4ZmluaXNoXCIsIFwiLnNlYXJjaGFuZGZpbHRlclwiLCBmdW5jdGlvbiggZSwgZGF0YSApIHtcblx0XHRcdHZhciBkaXNwbGF5X21ldGhvZCA9IGRhdGEub2JqZWN0LmRpc3BsYXlfcmVzdWx0X21ldGhvZDtcblx0XHRcdGlmICggZGlzcGxheV9tZXRob2QgPT09ICdjdXN0b21fZWRkX3N0b3JlJyApIHtcblx0XHRcdFx0JCgnaW5wdXQuZWRkLWFkZC10by1jYXJ0JykuY3NzKCdkaXNwbGF5JywgXCJub25lXCIpO1xuXHRcdFx0XHQkKCdhLmVkZC1hZGQtdG8tY2FydCcpLmFkZENsYXNzKCdlZGQtaGFzLWpzJyk7XG5cdFx0XHR9IGVsc2UgaWYgKCBkaXNwbGF5X21ldGhvZCA9PT0gJ2N1c3RvbV9sYXlvdXRzJyApIHtcblx0XHRcdFx0aWYgKCAkKCcuY2wtbGF5b3V0JykuaGFzQ2xhc3MoICdjbC1sYXlvdXQtLW1hc29ucnknICkgKSB7XG5cdFx0XHRcdFx0Ly90aGVuIHJlLWluaXQgbWFzb25yeVxuXHRcdFx0XHRcdGNvbnN0IG1hc29ucnlDb250YWluZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCAnLmNsLWxheW91dC0tbWFzb25yeScgKTtcblx0XHRcdFx0XHRpZiAoIG1hc29ucnlDb250YWluZXIubGVuZ3RoID4gMCApIHtcblx0XHRcdFx0XHRcdGNvbnN0IGN1c3RvbUxheW91dEdyaWQgPSBuZXcgTWFzb25yeSggJy5jbC1sYXlvdXQtLW1hc29ucnknLCB7XG5cdFx0XHRcdFx0XHRcdC8vIG9wdGlvbnMuLi5cblx0XHRcdFx0XHRcdFx0aXRlbVNlbGVjdG9yOiAnLmNsLWxheW91dF9faXRlbScsXG5cdFx0XHRcdFx0XHRcdC8vY29sdW1uV2lkdGg6IDMxOVxuXHRcdFx0XHRcdFx0XHRwZXJjZW50UG9zaXRpb246IHRydWUsXG5cdFx0XHRcdFx0XHRcdC8vZ3V0dGVyOiAxMCxcblx0XHRcdFx0XHRcdFx0dHJhbnNpdGlvbkR1cmF0aW9uOiAwLFxuXHRcdFx0XHRcdFx0fSApO1xuXHRcdFx0XHRcdFx0aW1hZ2VzTG9hZGVkKCBtYXNvbnJ5Q29udGFpbmVyICkub24oICdwcm9ncmVzcycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRjdXN0b21MYXlvdXRHcmlkLmxheW91dCgpO1xuXHRcdFx0XHRcdFx0fSApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH0pO1xuXHR9LFxuXG59OyJdfQ==
},{}]},{},[1])
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJzcmMvcHVibGljL2Fzc2V0cy9qcy9hcHAuanMiLCJub2RlX21vZHVsZXMvbm91aXNsaWRlci9kaXN0cmlidXRlL25vdWlzbGlkZXIuanMiLCJzcmMvcHVibGljL2Fzc2V0cy9qcy9pbmNsdWRlcy9wbHVnaW4uanMiLCJzcmMvcHVibGljL2Fzc2V0cy9qcy9pbmNsdWRlcy9wcm9jZXNzX2Zvcm0uanMiLCJzcmMvcHVibGljL2Fzc2V0cy9qcy9pbmNsdWRlcy9zdGF0ZS5qcyIsInNyYy9wdWJsaWMvYXNzZXRzL2pzL2luY2x1ZGVzL3RoaXJkcGFydHkuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUNBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ3RRQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUMxeUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUMzdEVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQzU4QkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDbEJBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6ImdlbmVyYXRlZC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzQ29udGVudCI6WyIoZnVuY3Rpb24gZSh0LG4scil7ZnVuY3Rpb24gcyhvLHUpe2lmKCFuW29dKXtpZighdFtvXSl7dmFyIGE9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtpZighdSYmYSlyZXR1cm4gYShvLCEwKTtpZihpKXJldHVybiBpKG8sITApO3ZhciBmPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIrbytcIidcIik7dGhyb3cgZi5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGZ9dmFyIGw9bltvXT17ZXhwb3J0czp7fX07dFtvXVswXS5jYWxsKGwuZXhwb3J0cyxmdW5jdGlvbihlKXt2YXIgbj10W29dWzFdW2VdO3JldHVybiBzKG4/bjplKX0sbCxsLmV4cG9ydHMsZSx0LG4scil9cmV0dXJuIG5bb10uZXhwb3J0c312YXIgaT10eXBlb2YgcmVxdWlyZT09XCJmdW5jdGlvblwiJiZyZXF1aXJlO2Zvcih2YXIgbz0wO288ci5sZW5ndGg7bysrKXMocltvXSk7cmV0dXJuIHN9KSIsIihmdW5jdGlvbiAoZ2xvYmFsKXtcblxyXG52YXIgc3RhdGUgPSByZXF1aXJlKCcuL2luY2x1ZGVzL3N0YXRlJyk7XHJcbnZhciBwbHVnaW4gPSByZXF1aXJlKCcuL2luY2x1ZGVzL3BsdWdpbicpO1xyXG5cclxuXHJcbihmdW5jdGlvbiAoICQgKSB7XHJcblxyXG5cdFwidXNlIHN0cmljdFwiO1xyXG5cclxuXHQkKGZ1bmN0aW9uICgpIHtcclxuXHJcblx0XHRpZiAoIU9iamVjdC5rZXlzKSB7XHJcblx0XHQgIE9iamVjdC5rZXlzID0gKGZ1bmN0aW9uICgpIHtcclxuXHRcdFx0J3VzZSBzdHJpY3QnO1xyXG5cdFx0XHR2YXIgaGFzT3duUHJvcGVydHkgPSBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LFxyXG5cdFx0XHRcdGhhc0RvbnRFbnVtQnVnID0gISh7dG9TdHJpbmc6IG51bGx9KS5wcm9wZXJ0eUlzRW51bWVyYWJsZSgndG9TdHJpbmcnKSxcclxuXHRcdFx0XHRkb250RW51bXMgPSBbXHJcblx0XHRcdFx0ICAndG9TdHJpbmcnLFxyXG5cdFx0XHRcdCAgJ3RvTG9jYWxlU3RyaW5nJyxcclxuXHRcdFx0XHQgICd2YWx1ZU9mJyxcclxuXHRcdFx0XHQgICdoYXNPd25Qcm9wZXJ0eScsXHJcblx0XHRcdFx0ICAnaXNQcm90b3R5cGVPZicsXHJcblx0XHRcdFx0ICAncHJvcGVydHlJc0VudW1lcmFibGUnLFxyXG5cdFx0XHRcdCAgJ2NvbnN0cnVjdG9yJ1xyXG5cdFx0XHRcdF0sXHJcblx0XHRcdFx0ZG9udEVudW1zTGVuZ3RoID0gZG9udEVudW1zLmxlbmd0aDtcclxuXHJcblx0XHRcdHJldHVybiBmdW5jdGlvbiAob2JqKSB7XHJcblx0XHRcdCAgaWYgKHR5cGVvZiBvYmogIT09ICdvYmplY3QnICYmICh0eXBlb2Ygb2JqICE9PSAnZnVuY3Rpb24nIHx8IG9iaiA9PT0gbnVsbCkpIHtcclxuXHRcdFx0XHR0aHJvdyBuZXcgVHlwZUVycm9yKCdPYmplY3Qua2V5cyBjYWxsZWQgb24gbm9uLW9iamVjdCcpO1xyXG5cdFx0XHQgIH1cclxuXHJcblx0XHRcdCAgdmFyIHJlc3VsdCA9IFtdLCBwcm9wLCBpO1xyXG5cclxuXHRcdFx0ICBmb3IgKHByb3AgaW4gb2JqKSB7XHJcblx0XHRcdFx0aWYgKGhhc093blByb3BlcnR5LmNhbGwob2JqLCBwcm9wKSkge1xyXG5cdFx0XHRcdCAgcmVzdWx0LnB1c2gocHJvcCk7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHQgIH1cclxuXHJcblx0XHRcdCAgaWYgKGhhc0RvbnRFbnVtQnVnKSB7XHJcblx0XHRcdFx0Zm9yIChpID0gMDsgaSA8IGRvbnRFbnVtc0xlbmd0aDsgaSsrKSB7XHJcblx0XHRcdFx0ICBpZiAoaGFzT3duUHJvcGVydHkuY2FsbChvYmosIGRvbnRFbnVtc1tpXSkpIHtcclxuXHRcdFx0XHRcdHJlc3VsdC5wdXNoKGRvbnRFbnVtc1tpXSk7XHJcblx0XHRcdFx0ICB9XHJcblx0XHRcdFx0fVxyXG5cdFx0XHQgIH1cclxuXHRcdFx0ICByZXR1cm4gcmVzdWx0O1xyXG5cdFx0XHR9O1xyXG5cdFx0ICB9KCkpO1xyXG5cdFx0fVxyXG5cclxuXHRcdC8qIFNlYXJjaCAmIEZpbHRlciBqUXVlcnkgUGx1Z2luICovXHJcblx0XHQkLmZuLnNlYXJjaEFuZEZpbHRlciA9IHBsdWdpbjtcclxuXHJcblx0XHQvKiBpbml0ICovXHJcblx0XHQkKFwiLnNlYXJjaGFuZGZpbHRlclwiKS5zZWFyY2hBbmRGaWx0ZXIoKTtcclxuXHJcblx0XHQvKiBleHRlcm5hbCBjb250cm9scyAqL1xyXG5cdFx0JChkb2N1bWVudCkub24oXCJjbGlja1wiLCBcIi5zZWFyY2gtZmlsdGVyLXJlc2V0XCIsIGZ1bmN0aW9uKGUpe1xyXG5cclxuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG5cclxuXHRcdFx0dmFyIHNlYXJjaEZvcm1JRCA9IHR5cGVvZigkKHRoaXMpLmF0dHIoXCJkYXRhLXNlYXJjaC1mb3JtLWlkXCIpKSE9XCJ1bmRlZmluZWRcIiA/ICQodGhpcykuYXR0cihcImRhdGEtc2VhcmNoLWZvcm0taWRcIikgOiBcIlwiO1xyXG5cdFx0XHR2YXIgc3VibWl0Rm9ybSA9IHR5cGVvZigkKHRoaXMpLmF0dHIoXCJkYXRhLXNmLXN1Ym1pdC1mb3JtXCIpKSE9XCJ1bmRlZmluZWRcIiA/ICQodGhpcykuYXR0cihcImRhdGEtc2Ytc3VibWl0LWZvcm1cIikgOiBcIlwiO1xyXG5cclxuXHRcdFx0c3RhdGUuZ2V0U2VhcmNoRm9ybShzZWFyY2hGb3JtSUQpLnJlc2V0KHN1Ym1pdEZvcm0pO1xyXG5cclxuXHRcdFx0Ly92YXIgJGxpbmtlZCA9ICQoXCIjc2VhcmNoLWZpbHRlci1mb3JtLVwiK3NlYXJjaEZvcm1JRCkuc2VhcmNoRmlsdGVyRm9ybSh7YWN0aW9uOiBcInJlc2V0XCJ9KTtcclxuXHJcblx0XHRcdHJldHVybiBmYWxzZTtcclxuXHJcblx0XHR9KTtcclxuXHJcblx0fSk7XHJcblxyXG5cclxuLypcclxuICogalF1ZXJ5IEVhc2luZyB2MS40LjEgLSBodHRwOi8vZ3NnZC5jby51ay9zYW5kYm94L2pxdWVyeS9lYXNpbmcvXHJcbiAqIE9wZW4gc291cmNlIHVuZGVyIHRoZSBCU0QgTGljZW5zZS5cclxuICogQ29weXJpZ2h0IMKpIDIwMDggR2VvcmdlIE1jR2lubGV5IFNtaXRoXHJcbiAqIEFsbCByaWdodHMgcmVzZXJ2ZWQuXHJcbiAqIGh0dHBzOi8vcmF3LmdpdGh1Yi5jb20vZ2RzbWl0aC9qcXVlcnkuZWFzaW5nL21hc3Rlci9MSUNFTlNFXHJcbiovXHJcblxyXG4vKiBnbG9iYWxzIGpRdWVyeSwgZGVmaW5lLCBtb2R1bGUsIHJlcXVpcmUgKi9cclxuKGZ1bmN0aW9uIChmYWN0b3J5KSB7XHJcblx0aWYgKHR5cGVvZiBkZWZpbmUgPT09IFwiZnVuY3Rpb25cIiAmJiBkZWZpbmUuYW1kKSB7XHJcblx0XHRkZWZpbmUoWydqcXVlcnknXSwgZnVuY3Rpb24gKCQpIHtcclxuXHRcdFx0cmV0dXJuIGZhY3RvcnkoJCk7XHJcblx0XHR9KTtcclxuXHR9IGVsc2UgaWYgKHR5cGVvZiBtb2R1bGUgPT09IFwib2JqZWN0XCIgJiYgdHlwZW9mIG1vZHVsZS5leHBvcnRzID09PSBcIm9iamVjdFwiKSB7XHJcblx0XHRtb2R1bGUuZXhwb3J0cyA9IGZhY3RvcnkoKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKSk7XHJcblx0fSBlbHNlIHtcclxuXHRcdGZhY3RvcnkoalF1ZXJ5KTtcclxuXHR9XHJcbn0pKGZ1bmN0aW9uKCQpe1xyXG5cclxuXHQvLyBQcmVzZXJ2ZSB0aGUgb3JpZ2luYWwgalF1ZXJ5IFwic3dpbmdcIiBlYXNpbmcgYXMgXCJqc3dpbmdcIlxyXG5cdGlmICh0eXBlb2YgJC5lYXNpbmcgIT09ICd1bmRlZmluZWQnKSB7XHJcblx0XHQkLmVhc2luZ1snanN3aW5nJ10gPSAkLmVhc2luZ1snc3dpbmcnXTtcclxuXHR9XHJcblxyXG5cdHZhciBwb3cgPSBNYXRoLnBvdyxcclxuXHRcdHNxcnQgPSBNYXRoLnNxcnQsXHJcblx0XHRzaW4gPSBNYXRoLnNpbixcclxuXHRcdGNvcyA9IE1hdGguY29zLFxyXG5cdFx0UEkgPSBNYXRoLlBJLFxyXG5cdFx0YzEgPSAxLjcwMTU4LFxyXG5cdFx0YzIgPSBjMSAqIDEuNTI1LFxyXG5cdFx0YzMgPSBjMSArIDEsXHJcblx0XHRjNCA9ICggMiAqIFBJICkgLyAzLFxyXG5cdFx0YzUgPSAoIDIgKiBQSSApIC8gNC41O1xyXG5cclxuXHQvLyB4IGlzIHRoZSBmcmFjdGlvbiBvZiBhbmltYXRpb24gcHJvZ3Jlc3MsIGluIHRoZSByYW5nZSAwLi4xXHJcblx0ZnVuY3Rpb24gYm91bmNlT3V0KHgpIHtcclxuXHRcdHZhciBuMSA9IDcuNTYyNSxcclxuXHRcdFx0ZDEgPSAyLjc1O1xyXG5cdFx0aWYgKCB4IDwgMS9kMSApIHtcclxuXHRcdFx0cmV0dXJuIG4xKngqeDtcclxuXHRcdH0gZWxzZSBpZiAoIHggPCAyL2QxICkge1xyXG5cdFx0XHRyZXR1cm4gbjEqKHgtPSgxLjUvZDEpKSp4ICsgLjc1O1xyXG5cdFx0fSBlbHNlIGlmICggeCA8IDIuNS9kMSApIHtcclxuXHRcdFx0cmV0dXJuIG4xKih4LT0oMi4yNS9kMSkpKnggKyAuOTM3NTtcclxuXHRcdH0gZWxzZSB7XHJcblx0XHRcdHJldHVybiBuMSooeC09KDIuNjI1L2QxKSkqeCArIC45ODQzNzU7XHJcblx0XHR9XHJcblx0fVxyXG5cclxuXHQkLmV4dGVuZCggJC5lYXNpbmcsIHtcclxuXHRcdGRlZjogJ2Vhc2VPdXRRdWFkJyxcclxuXHRcdHN3aW5nOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gJC5lYXNpbmdbJC5lYXNpbmcuZGVmXSh4KTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5RdWFkOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCAqIHg7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dFF1YWQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAxIC0gKCAxIC0geCApICogKCAxIC0geCApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dFF1YWQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQyICogeCAqIHggOlxyXG5cdFx0XHRcdDEgLSBwb3coIC0yICogeCArIDIsIDIgKSAvIDI7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluQ3ViaWM6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ICogeCAqIHg7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dEN1YmljOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gMSAtIHBvdyggMSAtIHgsIDMgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRDdWJpYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPCAwLjUgP1xyXG5cdFx0XHRcdDQgKiB4ICogeCAqIHggOlxyXG5cdFx0XHRcdDEgLSBwb3coIC0yICogeCArIDIsIDMgKSAvIDI7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluUXVhcnQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ICogeCAqIHggKiB4O1xyXG5cdFx0fSxcclxuXHRcdGVhc2VPdXRRdWFydDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSBwb3coIDEgLSB4LCA0ICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluT3V0UXVhcnQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQ4ICogeCAqIHggKiB4ICogeCA6XHJcblx0XHRcdFx0MSAtIHBvdyggLTIgKiB4ICsgMiwgNCApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5RdWludDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggKiB4ICogeCAqIHggKiB4O1xyXG5cdFx0fSxcclxuXHRcdGVhc2VPdXRRdWludDogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSBwb3coIDEgLSB4LCA1ICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluT3V0UXVpbnQ6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4IDwgMC41ID9cclxuXHRcdFx0XHQxNiAqIHggKiB4ICogeCAqIHggKiB4IDpcclxuXHRcdFx0XHQxIC0gcG93KCAtMiAqIHggKyAyLCA1ICkgLyAyO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJblNpbmU6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAxIC0gY29zKCB4ICogUEkvMiApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VPdXRTaW5lOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4gc2luKCB4ICogUEkvMiApO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dFNpbmU6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAtKCBjb3MoIFBJICogeCApIC0gMSApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5FeHBvOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA9PT0gMCA/IDAgOiBwb3coIDIsIDEwICogeCAtIDEwICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dEV4cG86IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ID09PSAxID8gMSA6IDEgLSBwb3coIDIsIC0xMCAqIHggKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRFeHBvOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA9PT0gMCA/IDAgOiB4ID09PSAxID8gMSA6IHggPCAwLjUgP1xyXG5cdFx0XHRcdHBvdyggMiwgMjAgKiB4IC0gMTAgKSAvIDIgOlxyXG5cdFx0XHRcdCggMiAtIHBvdyggMiwgLTIwICogeCArIDEwICkgKSAvIDI7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZUluQ2lyYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSBzcXJ0KCAxIC0gcG93KCB4LCAyICkgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0Q2lyYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHNxcnQoIDEgLSBwb3coIHggLSAxLCAyICkgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRDaXJjOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA8IDAuNSA/XHJcblx0XHRcdFx0KCAxIC0gc3FydCggMSAtIHBvdyggMiAqIHgsIDIgKSApICkgLyAyIDpcclxuXHRcdFx0XHQoIHNxcnQoIDEgLSBwb3coIC0yICogeCArIDIsIDIgKSApICsgMSApIC8gMjtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5FbGFzdGljOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA9PT0gMCA/IDAgOiB4ID09PSAxID8gMSA6XHJcblx0XHRcdFx0LXBvdyggMiwgMTAgKiB4IC0gMTAgKSAqIHNpbiggKCB4ICogMTAgLSAxMC43NSApICogYzQgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlT3V0RWxhc3RpYzogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIHggPT09IDAgPyAwIDogeCA9PT0gMSA/IDEgOlxyXG5cdFx0XHRcdHBvdyggMiwgLTEwICogeCApICogc2luKCAoIHggKiAxMCAtIDAuNzUgKSAqIGM0ICkgKyAxO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbk91dEVsYXN0aWM6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiB4ID09PSAwID8gMCA6IHggPT09IDEgPyAxIDogeCA8IDAuNSA/XHJcblx0XHRcdFx0LSggcG93KCAyLCAyMCAqIHggLSAxMCApICogc2luKCAoIDIwICogeCAtIDExLjEyNSApICogYzUgKSkgLyAyIDpcclxuXHRcdFx0XHRwb3coIDIsIC0yMCAqIHggKyAxMCApICogc2luKCAoIDIwICogeCAtIDExLjEyNSApICogYzUgKSAvIDIgKyAxO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbkJhY2s6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiBjMyAqIHggKiB4ICogeCAtIGMxICogeCAqIHg7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dEJhY2s6IGZ1bmN0aW9uICh4KSB7XHJcblx0XHRcdHJldHVybiAxICsgYzMgKiBwb3coIHggLSAxLCAzICkgKyBjMSAqIHBvdyggeCAtIDEsIDIgKTtcclxuXHRcdH0sXHJcblx0XHRlYXNlSW5PdXRCYWNrOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA8IDAuNSA/XHJcblx0XHRcdFx0KCBwb3coIDIgKiB4LCAyICkgKiAoICggYzIgKyAxICkgKiAyICogeCAtIGMyICkgKSAvIDIgOlxyXG5cdFx0XHRcdCggcG93KCAyICogeCAtIDIsIDIgKSAqKCAoIGMyICsgMSApICogKCB4ICogMiAtIDIgKSArIGMyICkgKyAyICkgLyAyO1xyXG5cdFx0fSxcclxuXHRcdGVhc2VJbkJvdW5jZTogZnVuY3Rpb24gKHgpIHtcclxuXHRcdFx0cmV0dXJuIDEgLSBib3VuY2VPdXQoIDEgLSB4ICk7XHJcblx0XHR9LFxyXG5cdFx0ZWFzZU91dEJvdW5jZTogYm91bmNlT3V0LFxyXG5cdFx0ZWFzZUluT3V0Qm91bmNlOiBmdW5jdGlvbiAoeCkge1xyXG5cdFx0XHRyZXR1cm4geCA8IDAuNSA/XHJcblx0XHRcdFx0KCAxIC0gYm91bmNlT3V0KCAxIC0gMiAqIHggKSApIC8gMiA6XHJcblx0XHRcdFx0KCAxICsgYm91bmNlT3V0KCAyICogeCAtIDEgKSApIC8gMjtcclxuXHRcdH1cclxuXHR9KTtcclxuXHRyZXR1cm4gJDtcclxufSk7XHJcblxyXG59KGpRdWVyeSkpO1xyXG5cclxuLy9zYWZhcmkgYmFjayBidXR0b24gZml4XHJcbmpRdWVyeSggd2luZG93ICkub24oIFwicGFnZXNob3dcIiwgZnVuY3Rpb24oZXZlbnQpIHtcclxuICAgIGlmIChldmVudC5vcmlnaW5hbEV2ZW50LnBlcnNpc3RlZCkge1xyXG4gICAgICAgIGpRdWVyeShcIi5zZWFyY2hhbmRmaWx0ZXJcIikub2ZmKCk7XHJcbiAgICAgICAgalF1ZXJ5KFwiLnNlYXJjaGFuZGZpbHRlclwiKS5zZWFyY2hBbmRGaWx0ZXIoKTtcclxuICAgIH1cclxufSk7XHJcblxyXG4vKiB3cG51bWIgLSBub3Vpc2xpZGVyIG51bWJlciBmb3JtYXR0aW5nICovXHJcbiFmdW5jdGlvbigpe1widXNlIHN0cmljdFwiO2Z1bmN0aW9uIGUoZSl7cmV0dXJuIGUuc3BsaXQoXCJcIikucmV2ZXJzZSgpLmpvaW4oXCJcIil9ZnVuY3Rpb24gbihlLG4pe3JldHVybiBlLnN1YnN0cmluZygwLG4ubGVuZ3RoKT09PW59ZnVuY3Rpb24gcihlLG4pe3JldHVybiBlLnNsaWNlKC0xKm4ubGVuZ3RoKT09PW59ZnVuY3Rpb24gdChlLG4scil7aWYoKGVbbl18fGVbcl0pJiZlW25dPT09ZVtyXSl0aHJvdyBuZXcgRXJyb3Iobil9ZnVuY3Rpb24gaShlKXtyZXR1cm5cIm51bWJlclwiPT10eXBlb2YgZSYmaXNGaW5pdGUoZSl9ZnVuY3Rpb24gbyhlLG4pe3ZhciByPU1hdGgucG93KDEwLG4pO3JldHVybihNYXRoLnJvdW5kKGUqcikvcikudG9GaXhlZChuKX1mdW5jdGlvbiB1KG4scix0LHUsZixhLGMscyxwLGQsbCxoKXt2YXIgZyx2LHcsbT1oLHg9XCJcIixiPVwiXCI7cmV0dXJuIGEmJihoPWEoaCkpLGkoaCk/KG4hPT0hMSYmMD09PXBhcnNlRmxvYXQoaC50b0ZpeGVkKG4pKSYmKGg9MCksMD5oJiYoZz0hMCxoPU1hdGguYWJzKGgpKSxuIT09ITEmJihoPW8oaCxuKSksaD1oLnRvU3RyaW5nKCksLTEhPT1oLmluZGV4T2YoXCIuXCIpPyh2PWguc3BsaXQoXCIuXCIpLHc9dlswXSx0JiYoeD10K3ZbMV0pKTp3PWgsciYmKHc9ZSh3KS5tYXRjaCgvLnsxLDN9L2cpLHc9ZSh3LmpvaW4oZShyKSkpKSxnJiZzJiYoYis9cyksdSYmKGIrPXUpLGcmJnAmJihiKz1wKSxiKz13LGIrPXgsZiYmKGIrPWYpLGQmJihiPWQoYixtKSksYik6ITF9ZnVuY3Rpb24gZihlLHQsbyx1LGYsYSxjLHMscCxkLGwsaCl7dmFyIGcsdj1cIlwiO3JldHVybiBsJiYoaD1sKGgpKSxoJiZcInN0cmluZ1wiPT10eXBlb2YgaD8ocyYmbihoLHMpJiYoaD1oLnJlcGxhY2UocyxcIlwiKSxnPSEwKSx1JiZuKGgsdSkmJihoPWgucmVwbGFjZSh1LFwiXCIpKSxwJiZuKGgscCkmJihoPWgucmVwbGFjZShwLFwiXCIpLGc9ITApLGYmJnIoaCxmKSYmKGg9aC5zbGljZSgwLC0xKmYubGVuZ3RoKSksdCYmKGg9aC5zcGxpdCh0KS5qb2luKFwiXCIpKSxvJiYoaD1oLnJlcGxhY2UobyxcIi5cIikpLGcmJih2Kz1cIi1cIiksdis9aCx2PXYucmVwbGFjZSgvW14wLTlcXC5cXC0uXS9nLFwiXCIpLFwiXCI9PT12PyExOih2PU51bWJlcih2KSxjJiYodj1jKHYpKSxpKHYpP3Y6ITEpKTohMX1mdW5jdGlvbiBhKGUpe3ZhciBuLHIsaSxvPXt9O2ZvcihuPTA7bjxwLmxlbmd0aDtuKz0xKWlmKHI9cFtuXSxpPWVbcl0sdm9pZCAwPT09aSlcIm5lZ2F0aXZlXCIhPT1yfHxvLm5lZ2F0aXZlQmVmb3JlP1wibWFya1wiPT09ciYmXCIuXCIhPT1vLnRob3VzYW5kP29bcl09XCIuXCI6b1tyXT0hMTpvW3JdPVwiLVwiO2Vsc2UgaWYoXCJkZWNpbWFsc1wiPT09cil7aWYoIShpPj0wJiY4PmkpKXRocm93IG5ldyBFcnJvcihyKTtvW3JdPWl9ZWxzZSBpZihcImVuY29kZXJcIj09PXJ8fFwiZGVjb2RlclwiPT09cnx8XCJlZGl0XCI9PT1yfHxcInVuZG9cIj09PXIpe2lmKFwiZnVuY3Rpb25cIiE9dHlwZW9mIGkpdGhyb3cgbmV3IEVycm9yKHIpO29bcl09aX1lbHNle2lmKFwic3RyaW5nXCIhPXR5cGVvZiBpKXRocm93IG5ldyBFcnJvcihyKTtvW3JdPWl9cmV0dXJuIHQobyxcIm1hcmtcIixcInRob3VzYW5kXCIpLHQobyxcInByZWZpeFwiLFwibmVnYXRpdmVcIiksdChvLFwicHJlZml4XCIsXCJuZWdhdGl2ZUJlZm9yZVwiKSxvfWZ1bmN0aW9uIGMoZSxuLHIpe3ZhciB0LGk9W107Zm9yKHQ9MDt0PHAubGVuZ3RoO3QrPTEpaS5wdXNoKGVbcFt0XV0pO3JldHVybiBpLnB1c2gociksbi5hcHBseShcIlwiLGkpfWZ1bmN0aW9uIHMoZSl7cmV0dXJuIHRoaXMgaW5zdGFuY2VvZiBzP3ZvaWQoXCJvYmplY3RcIj09dHlwZW9mIGUmJihlPWEoZSksdGhpcy50bz1mdW5jdGlvbihuKXtyZXR1cm4gYyhlLHUsbil9LHRoaXMuZnJvbT1mdW5jdGlvbihuKXtyZXR1cm4gYyhlLGYsbil9KSk6bmV3IHMoZSl9dmFyIHA9W1wiZGVjaW1hbHNcIixcInRob3VzYW5kXCIsXCJtYXJrXCIsXCJwcmVmaXhcIixcInBvc3RmaXhcIixcImVuY29kZXJcIixcImRlY29kZXJcIixcIm5lZ2F0aXZlQmVmb3JlXCIsXCJuZWdhdGl2ZVwiLFwiZWRpdFwiLFwidW5kb1wiXTt3aW5kb3cud051bWI9c30oKTtcclxuXHJcblxufSkuY2FsbCh0aGlzLHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWwgOiB0eXBlb2Ygc2VsZiAhPT0gXCJ1bmRlZmluZWRcIiA/IHNlbGYgOiB0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93IDoge30pXG4vLyMgc291cmNlTWFwcGluZ1VSTD1kYXRhOmFwcGxpY2F0aW9uL2pzb247Y2hhcnNldDp1dGYtODtiYXNlNjQsZXlKMlpYSnphVzl1SWpvekxDSnpiM1Z5WTJWeklqcGJJbk55WXk5d2RXSnNhV012WVhOelpYUnpMMnB6TDJGd2NDNXFjeUpkTENKdVlXMWxjeUk2VzEwc0ltMWhjSEJwYm1keklqb2lPMEZCUVVFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVNJc0ltWnBiR1VpT2lKblpXNWxjbUYwWldRdWFuTWlMQ0p6YjNWeVkyVlNiMjkwSWpvaUlpd2ljMjkxY21ObGMwTnZiblJsYm5RaU9sc2lYSEpjYm5aaGNpQnpkR0YwWlNBOUlISmxjWFZwY21Vb0p5NHZhVzVqYkhWa1pYTXZjM1JoZEdVbktUdGNjbHh1ZG1GeUlIQnNkV2RwYmlBOUlISmxjWFZwY21Vb0p5NHZhVzVqYkhWa1pYTXZjR3gxWjJsdUp5azdYSEpjYmx4eVhHNWNjbHh1S0daMWJtTjBhVzl1SUNnZ0pDQXBJSHRjY2x4dVhISmNibHgwWENKMWMyVWdjM1J5YVdOMFhDSTdYSEpjYmx4eVhHNWNkQ1FvWm5WdVkzUnBiMjRnS0NrZ2UxeHlYRzVjY2x4dVhIUmNkR2xtSUNnaFQySnFaV04wTG10bGVYTXBJSHRjY2x4dVhIUmNkQ0FnVDJKcVpXTjBMbXRsZVhNZ1BTQW9ablZ1WTNScGIyNGdLQ2tnZTF4eVhHNWNkRngwWEhRbmRYTmxJSE4wY21samRDYzdYSEpjYmx4MFhIUmNkSFpoY2lCb1lYTlBkMjVRY205d1pYSjBlU0E5SUU5aWFtVmpkQzV3Y205MGIzUjVjR1V1YUdGelQzZHVVSEp2Y0dWeWRIa3NYSEpjYmx4MFhIUmNkRngwYUdGelJHOXVkRVZ1ZFcxQ2RXY2dQU0FoS0h0MGIxTjBjbWx1WnpvZ2JuVnNiSDBwTG5CeWIzQmxjblI1U1hORmJuVnRaWEpoWW14bEtDZDBiMU4wY21sdVp5Y3BMRnh5WEc1Y2RGeDBYSFJjZEdSdmJuUkZiblZ0Y3lBOUlGdGNjbHh1WEhSY2RGeDBYSFFnSUNkMGIxTjBjbWx1Wnljc1hISmNibHgwWEhSY2RGeDBJQ0FuZEc5TWIyTmhiR1ZUZEhKcGJtY25MRnh5WEc1Y2RGeDBYSFJjZENBZ0ozWmhiSFZsVDJZbkxGeHlYRzVjZEZ4MFhIUmNkQ0FnSjJoaGMwOTNibEJ5YjNCbGNuUjVKeXhjY2x4dVhIUmNkRngwWEhRZ0lDZHBjMUJ5YjNSdmRIbHdaVTltSnl4Y2NseHVYSFJjZEZ4MFhIUWdJQ2R3Y205d1pYSjBlVWx6Ulc1MWJXVnlZV0pzWlNjc1hISmNibHgwWEhSY2RGeDBJQ0FuWTI5dWMzUnlkV04wYjNJblhISmNibHgwWEhSY2RGeDBYU3hjY2x4dVhIUmNkRngwWEhSa2IyNTBSVzUxYlhOTVpXNW5kR2dnUFNCa2IyNTBSVzUxYlhNdWJHVnVaM1JvTzF4eVhHNWNjbHh1WEhSY2RGeDBjbVYwZFhKdUlHWjFibU4wYVc5dUlDaHZZbW9wSUh0Y2NseHVYSFJjZEZ4MElDQnBaaUFvZEhsd1pXOW1JRzlpYWlBaFBUMGdKMjlpYW1WamRDY2dKaVlnS0hSNWNHVnZaaUJ2WW1vZ0lUMDlJQ2RtZFc1amRHbHZiaWNnZkh3Z2IySnFJRDA5UFNCdWRXeHNLU2tnZTF4eVhHNWNkRngwWEhSY2RIUm9jbTkzSUc1bGR5QlVlWEJsUlhKeWIzSW9KMDlpYW1WamRDNXJaWGx6SUdOaGJHeGxaQ0J2YmlCdWIyNHRiMkpxWldOMEp5azdYSEpjYmx4MFhIUmNkQ0FnZlZ4eVhHNWNjbHh1WEhSY2RGeDBJQ0IyWVhJZ2NtVnpkV3gwSUQwZ1cxMHNJSEJ5YjNBc0lHazdYSEpjYmx4eVhHNWNkRngwWEhRZ0lHWnZjaUFvY0hKdmNDQnBiaUJ2WW1vcElIdGNjbHh1WEhSY2RGeDBYSFJwWmlBb2FHRnpUM2R1VUhKdmNHVnlkSGt1WTJGc2JDaHZZbW9zSUhCeWIzQXBLU0I3WEhKY2JseDBYSFJjZEZ4MElDQnlaWE4xYkhRdWNIVnphQ2h3Y205d0tUdGNjbHh1WEhSY2RGeDBYSFI5WEhKY2JseDBYSFJjZENBZ2ZWeHlYRzVjY2x4dVhIUmNkRngwSUNCcFppQW9hR0Z6Ukc5dWRFVnVkVzFDZFdjcElIdGNjbHh1WEhSY2RGeDBYSFJtYjNJZ0tHa2dQU0F3T3lCcElEd2daRzl1ZEVWdWRXMXpUR1Z1WjNSb095QnBLeXNwSUh0Y2NseHVYSFJjZEZ4MFhIUWdJR2xtSUNob1lYTlBkMjVRY205d1pYSjBlUzVqWVd4c0tHOWlhaXdnWkc5dWRFVnVkVzF6VzJsZEtTa2dlMXh5WEc1Y2RGeDBYSFJjZEZ4MGNtVnpkV3gwTG5CMWMyZ29aRzl1ZEVWdWRXMXpXMmxkS1R0Y2NseHVYSFJjZEZ4MFhIUWdJSDFjY2x4dVhIUmNkRngwWEhSOVhISmNibHgwWEhSY2RDQWdmVnh5WEc1Y2RGeDBYSFFnSUhKbGRIVnliaUJ5WlhOMWJIUTdYSEpjYmx4MFhIUmNkSDA3WEhKY2JseDBYSFFnSUgwb0tTazdYSEpjYmx4MFhIUjlYSEpjYmx4eVhHNWNkRngwTHlvZ1UyVmhjbU5vSUNZZ1JtbHNkR1Z5SUdwUmRXVnllU0JRYkhWbmFXNGdLaTljY2x4dVhIUmNkQ1F1Wm00dWMyVmhjbU5vUVc1a1JtbHNkR1Z5SUQwZ2NHeDFaMmx1TzF4eVhHNWNjbHh1WEhSY2RDOHFJR2x1YVhRZ0tpOWNjbHh1WEhSY2RDUW9YQ0l1YzJWaGNtTm9ZVzVrWm1sc2RHVnlYQ0lwTG5ObFlYSmphRUZ1WkVacGJIUmxjaWdwTzF4eVhHNWNjbHh1WEhSY2RDOHFJR1Y0ZEdWeWJtRnNJR052Ym5SeWIyeHpJQ292WEhKY2JseDBYSFFrS0dSdlkzVnRaVzUwS1M1dmJpaGNJbU5zYVdOclhDSXNJRndpTG5ObFlYSmphQzFtYVd4MFpYSXRjbVZ6WlhSY0lpd2dablZ1WTNScGIyNG9aU2w3WEhKY2JseHlYRzVjZEZ4MFhIUmxMbkJ5WlhabGJuUkVaV1poZFd4MEtDazdYSEpjYmx4eVhHNWNkRngwWEhSMllYSWdjMlZoY21Ob1JtOXliVWxFSUQwZ2RIbHdaVzltS0NRb2RHaHBjeWt1WVhSMGNpaGNJbVJoZEdFdGMyVmhjbU5vTFdadmNtMHRhV1JjSWlrcElUMWNJblZ1WkdWbWFXNWxaRndpSUQ4Z0pDaDBhR2x6S1M1aGRIUnlLRndpWkdGMFlTMXpaV0Z5WTJndFptOXliUzFwWkZ3aUtTQTZJRndpWENJN1hISmNibHgwWEhSY2RIWmhjaUJ6ZFdKdGFYUkdiM0p0SUQwZ2RIbHdaVzltS0NRb2RHaHBjeWt1WVhSMGNpaGNJbVJoZEdFdGMyWXRjM1ZpYldsMExXWnZjbTFjSWlrcElUMWNJblZ1WkdWbWFXNWxaRndpSUQ4Z0pDaDBhR2x6S1M1aGRIUnlLRndpWkdGMFlTMXpaaTF6ZFdKdGFYUXRabTl5YlZ3aUtTQTZJRndpWENJN1hISmNibHh5WEc1Y2RGeDBYSFJ6ZEdGMFpTNW5aWFJUWldGeVkyaEdiM0p0S0hObFlYSmphRVp2Y20xSlJDa3VjbVZ6WlhRb2MzVmliV2wwUm05eWJTazdYSEpjYmx4eVhHNWNkRngwWEhRdkwzWmhjaUFrYkdsdWEyVmtJRDBnSkNoY0lpTnpaV0Z5WTJndFptbHNkR1Z5TFdadmNtMHRYQ0lyYzJWaGNtTm9SbTl5YlVsRUtTNXpaV0Z5WTJoR2FXeDBaWEpHYjNKdEtIdGhZM1JwYjI0NklGd2ljbVZ6WlhSY0luMHBPMXh5WEc1Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUdaaGJITmxPMXh5WEc1Y2NseHVYSFJjZEgwcE8xeHlYRzVjY2x4dVhIUjlLVHRjY2x4dVhISmNibHh5WEc0dktseHlYRzRnS2lCcVVYVmxjbmtnUldGemFXNW5JSFl4TGpRdU1TQXRJR2gwZEhBNkx5OW5jMmRrTG1OdkxuVnJMM05oYm1SaWIzZ3ZhbkYxWlhKNUwyVmhjMmx1Wnk5Y2NseHVJQ29nVDNCbGJpQnpiM1Z5WTJVZ2RXNWtaWElnZEdobElFSlRSQ0JNYVdObGJuTmxMbHh5WEc0Z0tpQkRiM0I1Y21sbmFIUWd3cWtnTWpBd09DQkhaVzl5WjJVZ1RXTkhhVzVzWlhrZ1UyMXBkR2hjY2x4dUlDb2dRV3hzSUhKcFoyaDBjeUJ5WlhObGNuWmxaQzVjY2x4dUlDb2dhSFIwY0hNNkx5OXlZWGN1WjJsMGFIVmlMbU52YlM5blpITnRhWFJvTDJweGRXVnllUzVsWVhOcGJtY3ZiV0Z6ZEdWeUwweEpRMFZPVTBWY2NseHVLaTljY2x4dVhISmNiaThxSUdkc2IySmhiSE1nYWxGMVpYSjVMQ0JrWldacGJtVXNJRzF2WkhWc1pTd2djbVZ4ZFdseVpTQXFMMXh5WEc0b1puVnVZM1JwYjI0Z0tHWmhZM1J2Y25rcElIdGNjbHh1WEhScFppQW9kSGx3Wlc5bUlHUmxabWx1WlNBOVBUMGdYQ0ptZFc1amRHbHZibHdpSUNZbUlHUmxabWx1WlM1aGJXUXBJSHRjY2x4dVhIUmNkR1JsWm1sdVpTaGJKMnB4ZFdWeWVTZGRMQ0JtZFc1amRHbHZiaUFvSkNrZ2UxeHlYRzVjZEZ4MFhIUnlaWFIxY200Z1ptRmpkRzl5ZVNna0tUdGNjbHh1WEhSY2RIMHBPMXh5WEc1Y2RIMGdaV3h6WlNCcFppQW9kSGx3Wlc5bUlHMXZaSFZzWlNBOVBUMGdYQ0p2WW1wbFkzUmNJaUFtSmlCMGVYQmxiMllnYlc5a2RXeGxMbVY0Y0c5eWRITWdQVDA5SUZ3aWIySnFaV04wWENJcElIdGNjbHh1WEhSY2RHMXZaSFZzWlM1bGVIQnZjblJ6SUQwZ1ptRmpkRzl5ZVNnb2RIbHdaVzltSUhkcGJtUnZkeUFoUFQwZ1hDSjFibVJsWm1sdVpXUmNJaUEvSUhkcGJtUnZkMXNuYWxGMVpYSjVKMTBnT2lCMGVYQmxiMllnWjJ4dlltRnNJQ0U5UFNCY0luVnVaR1ZtYVc1bFpGd2lJRDhnWjJ4dlltRnNXeWRxVVhWbGNua25YU0E2SUc1MWJHd3BLVHRjY2x4dVhIUjlJR1ZzYzJVZ2UxeHlYRzVjZEZ4MFptRmpkRzl5ZVNocVVYVmxjbmtwTzF4eVhHNWNkSDFjY2x4dWZTa29ablZ1WTNScGIyNG9KQ2w3WEhKY2JseHlYRzVjZEM4dklGQnlaWE5sY25abElIUm9aU0J2Y21sbmFXNWhiQ0JxVVhWbGNua2dYQ0p6ZDJsdVoxd2lJR1ZoYzJsdVp5QmhjeUJjSW1wemQybHVaMXdpWEhKY2JseDBhV1lnS0hSNWNHVnZaaUFrTG1WaGMybHVaeUFoUFQwZ0ozVnVaR1ZtYVc1bFpDY3BJSHRjY2x4dVhIUmNkQ1F1WldGemFXNW5XeWRxYzNkcGJtY25YU0E5SUNRdVpXRnphVzVuV3lkemQybHVaeWRkTzF4eVhHNWNkSDFjY2x4dVhISmNibHgwZG1GeUlIQnZkeUE5SUUxaGRHZ3VjRzkzTEZ4eVhHNWNkRngwYzNGeWRDQTlJRTFoZEdndWMzRnlkQ3hjY2x4dVhIUmNkSE5wYmlBOUlFMWhkR2d1YzJsdUxGeHlYRzVjZEZ4MFkyOXpJRDBnVFdGMGFDNWpiM01zWEhKY2JseDBYSFJRU1NBOUlFMWhkR2d1VUVrc1hISmNibHgwWEhSak1TQTlJREV1TnpBeE5UZ3NYSEpjYmx4MFhIUmpNaUE5SUdNeElDb2dNUzQxTWpVc1hISmNibHgwWEhSak15QTlJR014SUNzZ01TeGNjbHh1WEhSY2RHTTBJRDBnS0NBeUlDb2dVRWtnS1NBdklETXNYSEpjYmx4MFhIUmpOU0E5SUNnZ01pQXFJRkJKSUNrZ0x5QTBMalU3WEhKY2JseHlYRzVjZEM4dklIZ2dhWE1nZEdobElHWnlZV04wYVc5dUlHOW1JR0Z1YVcxaGRHbHZiaUJ3Y205bmNtVnpjeXdnYVc0Z2RHaGxJSEpoYm1kbElEQXVMakZjY2x4dVhIUm1kVzVqZEdsdmJpQmliM1Z1WTJWUGRYUW9lQ2tnZTF4eVhHNWNkRngwZG1GeUlHNHhJRDBnTnk0MU5qSTFMRnh5WEc1Y2RGeDBYSFJrTVNBOUlESXVOelU3WEhKY2JseDBYSFJwWmlBb0lIZ2dQQ0F4TDJReElDa2dlMXh5WEc1Y2RGeDBYSFJ5WlhSMWNtNGdiakVxZUNwNE8xeHlYRzVjZEZ4MGZTQmxiSE5sSUdsbUlDZ2dlQ0E4SURJdlpERWdLU0I3WEhKY2JseDBYSFJjZEhKbGRIVnliaUJ1TVNvb2VDMDlLREV1TlM5a01Ta3BLbmdnS3lBdU56VTdYSEpjYmx4MFhIUjlJR1ZzYzJVZ2FXWWdLQ0I0SUR3Z01pNDFMMlF4SUNrZ2UxeHlYRzVjZEZ4MFhIUnlaWFIxY200Z2JqRXFLSGd0UFNneUxqSTFMMlF4S1NrcWVDQXJJQzQ1TXpjMU8xeHlYRzVjZEZ4MGZTQmxiSE5sSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUc0eEtpaDRMVDBvTWk0Mk1qVXZaREVwS1NwNElDc2dMams0TkRNM05UdGNjbHh1WEhSY2RIMWNjbHh1WEhSOVhISmNibHh5WEc1Y2RDUXVaWGgwWlc1a0tDQWtMbVZoYzJsdVp5d2dlMXh5WEc1Y2RGeDBaR1ZtT2lBblpXRnpaVTkxZEZGMVlXUW5MRnh5WEc1Y2RGeDBjM2RwYm1jNklHWjFibU4wYVc5dUlDaDRLU0I3WEhKY2JseDBYSFJjZEhKbGRIVnliaUFrTG1WaGMybHVaMXNrTG1WaGMybHVaeTVrWldaZEtIZ3BPMXh5WEc1Y2RGeDBmU3hjY2x4dVhIUmNkR1ZoYzJWSmJsRjFZV1E2SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJQ29nZUR0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxUM1YwVVhWaFpEb2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SURFZ0xTQW9JREVnTFNCNElDa2dLaUFvSURFZ0xTQjRJQ2s3WEhKY2JseDBYSFI5TEZ4eVhHNWNkRngwWldGelpVbHVUM1YwVVhWaFpEb2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUhnZ1BDQXdMalVnUDF4eVhHNWNkRngwWEhSY2RESWdLaUI0SUNvZ2VDQTZYSEpjYmx4MFhIUmNkRngwTVNBdElIQnZkeWdnTFRJZ0tpQjRJQ3NnTWl3Z01pQXBJQzhnTWp0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxTVzVEZFdKcFl6b2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUhnZ0tpQjRJQ29nZUR0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxUM1YwUTNWaWFXTTZJR1oxYm1OMGFXOXVJQ2g0S1NCN1hISmNibHgwWEhSY2RISmxkSFZ5YmlBeElDMGdjRzkzS0NBeElDMGdlQ3dnTXlBcE8xeHlYRzVjZEZ4MGZTeGNjbHh1WEhSY2RHVmhjMlZKYms5MWRFTjFZbWxqT2lCbWRXNWpkR2x2YmlBb2VDa2dlMXh5WEc1Y2RGeDBYSFJ5WlhSMWNtNGdlQ0E4SURBdU5TQS9YSEpjYmx4MFhIUmNkRngwTkNBcUlIZ2dLaUI0SUNvZ2VDQTZYSEpjYmx4MFhIUmNkRngwTVNBdElIQnZkeWdnTFRJZ0tpQjRJQ3NnTWl3Z015QXBJQzhnTWp0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxTVzVSZFdGeWREb2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUhnZ0tpQjRJQ29nZUNBcUlIZzdYSEpjYmx4MFhIUjlMRnh5WEc1Y2RGeDBaV0Z6WlU5MWRGRjFZWEowT2lCbWRXNWpkR2x2YmlBb2VDa2dlMXh5WEc1Y2RGeDBYSFJ5WlhSMWNtNGdNU0F0SUhCdmR5Z2dNU0F0SUhnc0lEUWdLVHRjY2x4dVhIUmNkSDBzWEhKY2JseDBYSFJsWVhObFNXNVBkWFJSZFdGeWREb2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUhnZ1BDQXdMalVnUDF4eVhHNWNkRngwWEhSY2REZ2dLaUI0SUNvZ2VDQXFJSGdnS2lCNElEcGNjbHh1WEhSY2RGeDBYSFF4SUMwZ2NHOTNLQ0F0TWlBcUlIZ2dLeUF5TENBMElDa2dMeUF5TzF4eVhHNWNkRngwZlN4Y2NseHVYSFJjZEdWaGMyVkpibEYxYVc1ME9pQm1kVzVqZEdsdmJpQW9lQ2tnZTF4eVhHNWNkRngwWEhSeVpYUjFjbTRnZUNBcUlIZ2dLaUI0SUNvZ2VDQXFJSGc3WEhKY2JseDBYSFI5TEZ4eVhHNWNkRngwWldGelpVOTFkRkYxYVc1ME9pQm1kVzVqZEdsdmJpQW9lQ2tnZTF4eVhHNWNkRngwWEhSeVpYUjFjbTRnTVNBdElIQnZkeWdnTVNBdElIZ3NJRFVnS1R0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxTVzVQZFhSUmRXbHVkRG9nWm5WdVkzUnBiMjRnS0hncElIdGNjbHh1WEhSY2RGeDBjbVYwZFhKdUlIZ2dQQ0F3TGpVZ1AxeHlYRzVjZEZ4MFhIUmNkREUySUNvZ2VDQXFJSGdnS2lCNElDb2dlQ0FxSUhnZ09seHlYRzVjZEZ4MFhIUmNkREVnTFNCd2IzY29JQzB5SUNvZ2VDQXJJRElzSURVZ0tTQXZJREk3WEhKY2JseDBYSFI5TEZ4eVhHNWNkRngwWldGelpVbHVVMmx1WlRvZ1puVnVZM1JwYjI0Z0tIZ3BJSHRjY2x4dVhIUmNkRngwY21WMGRYSnVJREVnTFNCamIzTW9JSGdnS2lCUVNTOHlJQ2s3WEhKY2JseDBYSFI5TEZ4eVhHNWNkRngwWldGelpVOTFkRk5wYm1VNklHWjFibU4wYVc5dUlDaDRLU0I3WEhKY2JseDBYSFJjZEhKbGRIVnliaUJ6YVc0b0lIZ2dLaUJRU1M4eUlDazdYSEpjYmx4MFhIUjlMRnh5WEc1Y2RGeDBaV0Z6WlVsdVQzVjBVMmx1WlRvZ1puVnVZM1JwYjI0Z0tIZ3BJSHRjY2x4dVhIUmNkRngwY21WMGRYSnVJQzBvSUdOdmN5Z2dVRWtnS2lCNElDa2dMU0F4SUNrZ0x5QXlPMXh5WEc1Y2RGeDBmU3hjY2x4dVhIUmNkR1ZoYzJWSmJrVjRjRzg2SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJRDA5UFNBd0lEOGdNQ0E2SUhCdmR5Z2dNaXdnTVRBZ0tpQjRJQzBnTVRBZ0tUdGNjbHh1WEhSY2RIMHNYSEpjYmx4MFhIUmxZWE5sVDNWMFJYaHdiem9nWm5WdVkzUnBiMjRnS0hncElIdGNjbHh1WEhSY2RGeDBjbVYwZFhKdUlIZ2dQVDA5SURFZ1B5QXhJRG9nTVNBdElIQnZkeWdnTWl3Z0xURXdJQ29nZUNBcE8xeHlYRzVjZEZ4MGZTeGNjbHh1WEhSY2RHVmhjMlZKYms5MWRFVjRjRzg2SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJRDA5UFNBd0lEOGdNQ0E2SUhnZ1BUMDlJREVnUHlBeElEb2dlQ0E4SURBdU5TQS9YSEpjYmx4MFhIUmNkRngwY0c5M0tDQXlMQ0F5TUNBcUlIZ2dMU0F4TUNBcElDOGdNaUE2WEhKY2JseDBYSFJjZEZ4MEtDQXlJQzBnY0c5M0tDQXlMQ0F0TWpBZ0tpQjRJQ3NnTVRBZ0tTQXBJQzhnTWp0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxTVzVEYVhKak9pQm1kVzVqZEdsdmJpQW9lQ2tnZTF4eVhHNWNkRngwWEhSeVpYUjFjbTRnTVNBdElITnhjblFvSURFZ0xTQndiM2NvSUhnc0lESWdLU0FwTzF4eVhHNWNkRngwZlN4Y2NseHVYSFJjZEdWaGMyVlBkWFJEYVhKak9pQm1kVzVqZEdsdmJpQW9lQ2tnZTF4eVhHNWNkRngwWEhSeVpYUjFjbTRnYzNGeWRDZ2dNU0F0SUhCdmR5Z2dlQ0F0SURFc0lESWdLU0FwTzF4eVhHNWNkRngwZlN4Y2NseHVYSFJjZEdWaGMyVkpiazkxZEVOcGNtTTZJR1oxYm1OMGFXOXVJQ2g0S1NCN1hISmNibHgwWEhSY2RISmxkSFZ5YmlCNElEd2dNQzQxSUQ5Y2NseHVYSFJjZEZ4MFhIUW9JREVnTFNCemNYSjBLQ0F4SUMwZ2NHOTNLQ0F5SUNvZ2VDd2dNaUFwSUNrZ0tTQXZJRElnT2x4eVhHNWNkRngwWEhSY2RDZ2djM0Z5ZENnZ01TQXRJSEJ2ZHlnZ0xUSWdLaUI0SUNzZ01pd2dNaUFwSUNrZ0t5QXhJQ2tnTHlBeU8xeHlYRzVjZEZ4MGZTeGNjbHh1WEhSY2RHVmhjMlZKYmtWc1lYTjBhV002SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJRDA5UFNBd0lEOGdNQ0E2SUhnZ1BUMDlJREVnUHlBeElEcGNjbHh1WEhSY2RGeDBYSFF0Y0c5M0tDQXlMQ0F4TUNBcUlIZ2dMU0F4TUNBcElDb2djMmx1S0NBb0lIZ2dLaUF4TUNBdElERXdMamMxSUNrZ0tpQmpOQ0FwTzF4eVhHNWNkRngwZlN4Y2NseHVYSFJjZEdWaGMyVlBkWFJGYkdGemRHbGpPaUJtZFc1amRHbHZiaUFvZUNrZ2UxeHlYRzVjZEZ4MFhIUnlaWFIxY200Z2VDQTlQVDBnTUNBL0lEQWdPaUI0SUQwOVBTQXhJRDhnTVNBNlhISmNibHgwWEhSY2RGeDBjRzkzS0NBeUxDQXRNVEFnS2lCNElDa2dLaUJ6YVc0b0lDZ2dlQ0FxSURFd0lDMGdNQzQzTlNBcElDb2dZelFnS1NBcklERTdYSEpjYmx4MFhIUjlMRnh5WEc1Y2RGeDBaV0Z6WlVsdVQzVjBSV3hoYzNScFl6b2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUhnZ1BUMDlJREFnUHlBd0lEb2dlQ0E5UFQwZ01TQS9JREVnT2lCNElEd2dNQzQxSUQ5Y2NseHVYSFJjZEZ4MFhIUXRLQ0J3YjNjb0lESXNJREl3SUNvZ2VDQXRJREV3SUNrZ0tpQnphVzRvSUNnZ01qQWdLaUI0SUMwZ01URXVNVEkxSUNrZ0tpQmpOU0FwS1NBdklESWdPbHh5WEc1Y2RGeDBYSFJjZEhCdmR5Z2dNaXdnTFRJd0lDb2dlQ0FySURFd0lDa2dLaUJ6YVc0b0lDZ2dNakFnS2lCNElDMGdNVEV1TVRJMUlDa2dLaUJqTlNBcElDOGdNaUFySURFN1hISmNibHgwWEhSOUxGeHlYRzVjZEZ4MFpXRnpaVWx1UW1GamF6b2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SUdNeklDb2dlQ0FxSUhnZ0tpQjRJQzBnWXpFZ0tpQjRJQ29nZUR0Y2NseHVYSFJjZEgwc1hISmNibHgwWEhSbFlYTmxUM1YwUW1GamF6b2dablZ1WTNScGIyNGdLSGdwSUh0Y2NseHVYSFJjZEZ4MGNtVjBkWEp1SURFZ0t5QmpNeUFxSUhCdmR5Z2dlQ0F0SURFc0lETWdLU0FySUdNeElDb2djRzkzS0NCNElDMGdNU3dnTWlBcE8xeHlYRzVjZEZ4MGZTeGNjbHh1WEhSY2RHVmhjMlZKYms5MWRFSmhZMnM2SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJRHdnTUM0MUlEOWNjbHh1WEhSY2RGeDBYSFFvSUhCdmR5Z2dNaUFxSUhnc0lESWdLU0FxSUNnZ0tDQmpNaUFySURFZ0tTQXFJRElnS2lCNElDMGdZeklnS1NBcElDOGdNaUE2WEhKY2JseDBYSFJjZEZ4MEtDQndiM2NvSURJZ0tpQjRJQzBnTWl3Z01pQXBJQ29vSUNnZ1l6SWdLeUF4SUNrZ0tpQW9JSGdnS2lBeUlDMGdNaUFwSUNzZ1l6SWdLU0FySURJZ0tTQXZJREk3WEhKY2JseDBYSFI5TEZ4eVhHNWNkRngwWldGelpVbHVRbTkxYm1ObE9pQm1kVzVqZEdsdmJpQW9lQ2tnZTF4eVhHNWNkRngwWEhSeVpYUjFjbTRnTVNBdElHSnZkVzVqWlU5MWRDZ2dNU0F0SUhnZ0tUdGNjbHh1WEhSY2RIMHNYSEpjYmx4MFhIUmxZWE5sVDNWMFFtOTFibU5sT2lCaWIzVnVZMlZQZFhRc1hISmNibHgwWEhSbFlYTmxTVzVQZFhSQ2IzVnVZMlU2SUdaMWJtTjBhVzl1SUNoNEtTQjdYSEpjYmx4MFhIUmNkSEpsZEhWeWJpQjRJRHdnTUM0MUlEOWNjbHh1WEhSY2RGeDBYSFFvSURFZ0xTQmliM1Z1WTJWUGRYUW9JREVnTFNBeUlDb2dlQ0FwSUNrZ0x5QXlJRHBjY2x4dVhIUmNkRngwWEhRb0lERWdLeUJpYjNWdVkyVlBkWFFvSURJZ0tpQjRJQzBnTVNBcElDa2dMeUF5TzF4eVhHNWNkRngwZlZ4eVhHNWNkSDBwTzF4eVhHNWNkSEpsZEhWeWJpQWtPMXh5WEc1OUtUdGNjbHh1WEhKY2JuMG9hbEYxWlhKNUtTazdYSEpjYmx4eVhHNHZMM05oWm1GeWFTQmlZV05ySUdKMWRIUnZiaUJtYVhoY2NseHVhbEYxWlhKNUtDQjNhVzVrYjNjZ0tTNXZiaWdnWENKd1lXZGxjMmh2ZDF3aUxDQm1kVzVqZEdsdmJpaGxkbVZ1ZENrZ2UxeHlYRzRnSUNBZ2FXWWdLR1YyWlc1MExtOXlhV2RwYm1Gc1JYWmxiblF1Y0dWeWMybHpkR1ZrS1NCN1hISmNiaUFnSUNBZ0lDQWdhbEYxWlhKNUtGd2lMbk5sWVhKamFHRnVaR1pwYkhSbGNsd2lLUzV2Wm1Zb0tUdGNjbHh1SUNBZ0lDQWdJQ0JxVVhWbGNua29YQ0l1YzJWaGNtTm9ZVzVrWm1sc2RHVnlYQ0lwTG5ObFlYSmphRUZ1WkVacGJIUmxjaWdwTzF4eVhHNGdJQ0FnZlZ4eVhHNTlLVHRjY2x4dVhISmNiaThxSUhkd2JuVnRZaUF0SUc1dmRXbHpiR2xrWlhJZ2JuVnRZbVZ5SUdadmNtMWhkSFJwYm1jZ0tpOWNjbHh1SVdaMWJtTjBhVzl1S0NsN1hDSjFjMlVnYzNSeWFXTjBYQ0k3Wm5WdVkzUnBiMjRnWlNobEtYdHlaWFIxY200Z1pTNXpjR3hwZENoY0lsd2lLUzV5WlhabGNuTmxLQ2t1YW05cGJpaGNJbHdpS1gxbWRXNWpkR2x2YmlCdUtHVXNiaWw3Y21WMGRYSnVJR1V1YzNWaWMzUnlhVzVuS0RBc2JpNXNaVzVuZEdncFBUMDlibjFtZFc1amRHbHZiaUJ5S0dVc2JpbDdjbVYwZFhKdUlHVXVjMnhwWTJVb0xURXFiaTVzWlc1bmRHZ3BQVDA5Ym4xbWRXNWpkR2x2YmlCMEtHVXNiaXh5S1h0cFppZ29aVnR1WFh4OFpWdHlYU2ttSm1WYmJsMDlQVDFsVzNKZEtYUm9jbTkzSUc1bGR5QkZjbkp2Y2lodUtYMW1kVzVqZEdsdmJpQnBLR1VwZTNKbGRIVnlibHdpYm5WdFltVnlYQ0k5UFhSNWNHVnZaaUJsSmlacGMwWnBibWwwWlNobEtYMW1kVzVqZEdsdmJpQnZLR1VzYmlsN2RtRnlJSEk5VFdGMGFDNXdiM2NvTVRBc2JpazdjbVYwZFhKdUtFMWhkR2d1Y205MWJtUW9aU3B5S1M5eUtTNTBiMFpwZUdWa0tHNHBmV1oxYm1OMGFXOXVJSFVvYml4eUxIUXNkU3htTEdFc1l5eHpMSEFzWkN4c0xHZ3BlM1poY2lCbkxIWXNkeXh0UFdnc2VEMWNJbHdpTEdJOVhDSmNJanR5WlhSMWNtNGdZU1ltS0dnOVlTaG9LU2tzYVNob0tUOG9iaUU5UFNFeEppWXdQVDA5Y0dGeWMyVkdiRzloZENob0xuUnZSbWw0WldRb2Jpa3BKaVlvYUQwd0tTd3dQbWdtSmloblBTRXdMR2c5VFdGMGFDNWhZbk1vYUNrcExHNGhQVDBoTVNZbUtHZzlieWhvTEc0cEtTeG9QV2d1ZEc5VGRISnBibWNvS1N3dE1TRTlQV2d1YVc1a1pYaFBaaWhjSWk1Y0lpay9LSFk5YUM1emNHeHBkQ2hjSWk1Y0lpa3NkejEyV3pCZExIUW1KaWg0UFhRcmRsc3hYU2twT25jOWFDeHlKaVlvZHoxbEtIY3BMbTFoZEdOb0tDOHVlekVzTTMwdlp5a3NkejFsS0hjdWFtOXBiaWhsS0hJcEtTa3BMR2NtSm5NbUppaGlLejF6S1N4MUppWW9ZaXM5ZFNrc1p5WW1jQ1ltS0dJclBYQXBMR0lyUFhjc1lpczllQ3htSmlZb1lpczlaaWtzWkNZbUtHSTlaQ2hpTEcwcEtTeGlLVG9oTVgxbWRXNWpkR2x2YmlCbUtHVXNkQ3h2TEhVc1ppeGhMR01zY3l4d0xHUXNiQ3hvS1h0MllYSWdaeXgyUFZ3aVhDSTdjbVYwZFhKdUlHd21KaWhvUFd3b2FDa3BMR2dtSmx3aWMzUnlhVzVuWENJOVBYUjVjR1Z2WmlCb1B5aHpKaVp1S0dnc2N5a21KaWhvUFdndWNtVndiR0ZqWlNoekxGd2lYQ0lwTEdjOUlUQXBMSFVtSm00b2FDeDFLU1ltS0dnOWFDNXlaWEJzWVdObEtIVXNYQ0pjSWlrcExIQW1KbTRvYUN4d0tTWW1LR2c5YUM1eVpYQnNZV05sS0hBc1hDSmNJaWtzWnowaE1Da3NaaVltY2lob0xHWXBKaVlvYUQxb0xuTnNhV05sS0RBc0xURXFaaTVzWlc1bmRHZ3BLU3gwSmlZb2FEMW9Mbk53YkdsMEtIUXBMbXB2YVc0b1hDSmNJaWtwTEc4bUppaG9QV2d1Y21Wd2JHRmpaU2h2TEZ3aUxsd2lLU2tzWnlZbUtIWXJQVndpTFZ3aUtTeDJLejFvTEhZOWRpNXlaWEJzWVdObEtDOWJYakF0T1Z4Y0xseGNMUzVkTDJjc1hDSmNJaWtzWENKY0lqMDlQWFkvSVRFNktIWTlUblZ0WW1WeUtIWXBMR01tSmloMlBXTW9kaWtwTEdrb2Rpay9kam9oTVNrcE9pRXhmV1oxYm1OMGFXOXVJR0VvWlNsN2RtRnlJRzRzY2l4cExHODllMzA3Wm05eUtHNDlNRHR1UEhBdWJHVnVaM1JvTzI0clBURXBhV1lvY2oxd1cyNWRMR2s5WlZ0eVhTeDJiMmxrSURBOVBUMXBLVndpYm1WbllYUnBkbVZjSWlFOVBYSjhmRzh1Ym1WbllYUnBkbVZDWldadmNtVS9YQ0p0WVhKclhDSTlQVDF5SmlaY0lpNWNJaUU5UFc4dWRHaHZkWE5oYm1RL2IxdHlYVDFjSWk1Y0lqcHZXM0pkUFNFeE9tOWJjbDA5WENJdFhDSTdaV3h6WlNCcFppaGNJbVJsWTJsdFlXeHpYQ0k5UFQxeUtYdHBaaWdoS0drK1BUQW1KamcrYVNrcGRHaHliM2NnYm1WM0lFVnljbTl5S0hJcE8yOWJjbDA5YVgxbGJITmxJR2xtS0Z3aVpXNWpiMlJsY2x3aVBUMDljbng4WENKa1pXTnZaR1Z5WENJOVBUMXlmSHhjSW1Wa2FYUmNJajA5UFhKOGZGd2lkVzVrYjF3aVBUMDljaWw3YVdZb1hDSm1kVzVqZEdsdmJsd2lJVDEwZVhCbGIyWWdhU2wwYUhKdmR5QnVaWGNnUlhKeWIzSW9jaWs3YjF0eVhUMXBmV1ZzYzJWN2FXWW9YQ0p6ZEhKcGJtZGNJaUU5ZEhsd1pXOW1JR2twZEdoeWIzY2dibVYzSUVWeWNtOXlLSElwTzI5YmNsMDlhWDF5WlhSMWNtNGdkQ2h2TEZ3aWJXRnlhMXdpTEZ3aWRHaHZkWE5oYm1SY0lpa3NkQ2h2TEZ3aWNISmxabWw0WENJc1hDSnVaV2RoZEdsMlpWd2lLU3gwS0c4c1hDSndjbVZtYVhoY0lpeGNJbTVsWjJGMGFYWmxRbVZtYjNKbFhDSXBMRzk5Wm5WdVkzUnBiMjRnWXlobExHNHNjaWw3ZG1GeUlIUXNhVDFiWFR0bWIzSW9kRDB3TzNROGNDNXNaVzVuZEdnN2RDczlNU2xwTG5CMWMyZ29aVnR3VzNSZFhTazdjbVYwZFhKdUlHa3VjSFZ6YUNoeUtTeHVMbUZ3Y0d4NUtGd2lYQ0lzYVNsOVpuVnVZM1JwYjI0Z2N5aGxLWHR5WlhSMWNtNGdkR2hwY3lCcGJuTjBZVzVqWlc5bUlITS9kbTlwWkNoY0ltOWlhbVZqZEZ3aVBUMTBlWEJsYjJZZ1pTWW1LR1U5WVNobEtTeDBhR2x6TG5SdlBXWjFibU4wYVc5dUtHNHBlM0psZEhWeWJpQmpLR1VzZFN4dUtYMHNkR2hwY3k1bWNtOXRQV1oxYm1OMGFXOXVLRzRwZTNKbGRIVnliaUJqS0dVc1ppeHVLWDBwS1RwdVpYY2djeWhsS1gxMllYSWdjRDFiWENKa1pXTnBiV0ZzYzF3aUxGd2lkR2h2ZFhOaGJtUmNJaXhjSW0xaGNtdGNJaXhjSW5CeVpXWnBlRndpTEZ3aWNHOXpkR1pwZUZ3aUxGd2laVzVqYjJSbGNsd2lMRndpWkdWamIyUmxjbHdpTEZ3aWJtVm5ZWFJwZG1WQ1pXWnZjbVZjSWl4Y0ltNWxaMkYwYVhabFhDSXNYQ0psWkdsMFhDSXNYQ0oxYm1SdlhDSmRPM2RwYm1SdmR5NTNUblZ0WWoxemZTZ3BPMXh5WEc1Y2NseHVJbDE5IiwiLyohIG5vdWlzbGlkZXIgLSAxMS4xLjAgLSAyMDE4LTA0LTAyIDExOjE4OjEzICovXHJcblxyXG4oZnVuY3Rpb24gKGZhY3RvcnkpIHtcclxuXHJcbiAgICBpZiAoIHR5cGVvZiBkZWZpbmUgPT09ICdmdW5jdGlvbicgJiYgZGVmaW5lLmFtZCApIHtcclxuXHJcbiAgICAgICAgLy8gQU1ELiBSZWdpc3RlciBhcyBhbiBhbm9ueW1vdXMgbW9kdWxlLlxyXG4gICAgICAgIGRlZmluZShbXSwgZmFjdG9yeSk7XHJcblxyXG4gICAgfSBlbHNlIGlmICggdHlwZW9mIGV4cG9ydHMgPT09ICdvYmplY3QnICkge1xyXG5cclxuICAgICAgICAvLyBOb2RlL0NvbW1vbkpTXHJcbiAgICAgICAgbW9kdWxlLmV4cG9ydHMgPSBmYWN0b3J5KCk7XHJcblxyXG4gICAgfSBlbHNlIHtcclxuXHJcbiAgICAgICAgLy8gQnJvd3NlciBnbG9iYWxzXHJcbiAgICAgICAgd2luZG93Lm5vVWlTbGlkZXIgPSBmYWN0b3J5KCk7XHJcbiAgICB9XHJcblxyXG59KGZ1bmN0aW9uKCApe1xyXG5cclxuXHQndXNlIHN0cmljdCc7XHJcblxyXG5cdHZhciBWRVJTSU9OID0gJzExLjEuMCc7XHJcblxyXG5cblx0ZnVuY3Rpb24gaXNWYWxpZEZvcm1hdHRlciAoIGVudHJ5ICkge1xuXHRcdHJldHVybiB0eXBlb2YgZW50cnkgPT09ICdvYmplY3QnICYmIHR5cGVvZiBlbnRyeS50byA9PT0gJ2Z1bmN0aW9uJyAmJiB0eXBlb2YgZW50cnkuZnJvbSA9PT0gJ2Z1bmN0aW9uJztcblx0fVxuXG5cdGZ1bmN0aW9uIHJlbW92ZUVsZW1lbnQgKCBlbCApIHtcblx0XHRlbC5wYXJlbnRFbGVtZW50LnJlbW92ZUNoaWxkKGVsKTtcblx0fVxuXG5cdGZ1bmN0aW9uIGlzU2V0ICggdmFsdWUgKSB7XG5cdFx0cmV0dXJuIHZhbHVlICE9PSBudWxsICYmIHZhbHVlICE9PSB1bmRlZmluZWQ7XG5cdH1cblxuXHQvLyBCaW5kYWJsZSB2ZXJzaW9uXG5cdGZ1bmN0aW9uIHByZXZlbnREZWZhdWx0ICggZSApIHtcblx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdH1cblxuXHQvLyBSZW1vdmVzIGR1cGxpY2F0ZXMgZnJvbSBhbiBhcnJheS5cblx0ZnVuY3Rpb24gdW5pcXVlICggYXJyYXkgKSB7XG5cdFx0cmV0dXJuIGFycmF5LmZpbHRlcihmdW5jdGlvbihhKXtcblx0XHRcdHJldHVybiAhdGhpc1thXSA/IHRoaXNbYV0gPSB0cnVlIDogZmFsc2U7XG5cdFx0fSwge30pO1xuXHR9XG5cblx0Ly8gUm91bmQgYSB2YWx1ZSB0byB0aGUgY2xvc2VzdCAndG8nLlxuXHRmdW5jdGlvbiBjbG9zZXN0ICggdmFsdWUsIHRvICkge1xuXHRcdHJldHVybiBNYXRoLnJvdW5kKHZhbHVlIC8gdG8pICogdG87XG5cdH1cblxuXHQvLyBDdXJyZW50IHBvc2l0aW9uIG9mIGFuIGVsZW1lbnQgcmVsYXRpdmUgdG8gdGhlIGRvY3VtZW50LlxuXHRmdW5jdGlvbiBvZmZzZXQgKCBlbGVtLCBvcmllbnRhdGlvbiApIHtcblxuXHRcdHZhciByZWN0ID0gZWxlbS5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcblx0XHR2YXIgZG9jID0gZWxlbS5vd25lckRvY3VtZW50O1xuXHRcdHZhciBkb2NFbGVtID0gZG9jLmRvY3VtZW50RWxlbWVudDtcblx0XHR2YXIgcGFnZU9mZnNldCA9IGdldFBhZ2VPZmZzZXQoZG9jKTtcblxuXHRcdC8vIGdldEJvdW5kaW5nQ2xpZW50UmVjdCBjb250YWlucyBsZWZ0IHNjcm9sbCBpbiBDaHJvbWUgb24gQW5kcm9pZC5cblx0XHQvLyBJIGhhdmVuJ3QgZm91bmQgYSBmZWF0dXJlIGRldGVjdGlvbiB0aGF0IHByb3ZlcyB0aGlzLiBXb3JzdCBjYXNlXG5cdFx0Ly8gc2NlbmFyaW8gb24gbWlzLW1hdGNoOiB0aGUgJ3RhcCcgZmVhdHVyZSBvbiBob3Jpem9udGFsIHNsaWRlcnMgYnJlYWtzLlxuXHRcdGlmICggL3dlYmtpdC4qQ2hyb21lLipNb2JpbGUvaS50ZXN0KG5hdmlnYXRvci51c2VyQWdlbnQpICkge1xuXHRcdFx0cGFnZU9mZnNldC54ID0gMDtcblx0XHR9XG5cblx0XHRyZXR1cm4gb3JpZW50YXRpb24gPyAocmVjdC50b3AgKyBwYWdlT2Zmc2V0LnkgLSBkb2NFbGVtLmNsaWVudFRvcCkgOiAocmVjdC5sZWZ0ICsgcGFnZU9mZnNldC54IC0gZG9jRWxlbS5jbGllbnRMZWZ0KTtcblx0fVxuXG5cdC8vIENoZWNrcyB3aGV0aGVyIGEgdmFsdWUgaXMgbnVtZXJpY2FsLlxuXHRmdW5jdGlvbiBpc051bWVyaWMgKCBhICkge1xuXHRcdHJldHVybiB0eXBlb2YgYSA9PT0gJ251bWJlcicgJiYgIWlzTmFOKCBhICkgJiYgaXNGaW5pdGUoIGEgKTtcblx0fVxuXG5cdC8vIFNldHMgYSBjbGFzcyBhbmQgcmVtb3ZlcyBpdCBhZnRlciBbZHVyYXRpb25dIG1zLlxuXHRmdW5jdGlvbiBhZGRDbGFzc0ZvciAoIGVsZW1lbnQsIGNsYXNzTmFtZSwgZHVyYXRpb24gKSB7XG5cdFx0aWYgKGR1cmF0aW9uID4gMCkge1xuXHRcdGFkZENsYXNzKGVsZW1lbnQsIGNsYXNzTmFtZSk7XG5cdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCl7XG5cdFx0XHRcdHJlbW92ZUNsYXNzKGVsZW1lbnQsIGNsYXNzTmFtZSk7XG5cdFx0XHR9LCBkdXJhdGlvbik7XG5cdFx0fVxuXHR9XG5cblx0Ly8gTGltaXRzIGEgdmFsdWUgdG8gMCAtIDEwMFxuXHRmdW5jdGlvbiBsaW1pdCAoIGEgKSB7XG5cdFx0cmV0dXJuIE1hdGgubWF4KE1hdGgubWluKGEsIDEwMCksIDApO1xuXHR9XG5cblx0Ly8gV3JhcHMgYSB2YXJpYWJsZSBhcyBhbiBhcnJheSwgaWYgaXQgaXNuJ3Qgb25lIHlldC5cblx0Ly8gTm90ZSB0aGF0IGFuIGlucHV0IGFycmF5IGlzIHJldHVybmVkIGJ5IHJlZmVyZW5jZSFcblx0ZnVuY3Rpb24gYXNBcnJheSAoIGEgKSB7XG5cdFx0cmV0dXJuIEFycmF5LmlzQXJyYXkoYSkgPyBhIDogW2FdO1xuXHR9XG5cblx0Ly8gQ291bnRzIGRlY2ltYWxzXG5cdGZ1bmN0aW9uIGNvdW50RGVjaW1hbHMgKCBudW1TdHIgKSB7XG5cdFx0bnVtU3RyID0gU3RyaW5nKG51bVN0cik7XG5cdFx0dmFyIHBpZWNlcyA9IG51bVN0ci5zcGxpdChcIi5cIik7XG5cdFx0cmV0dXJuIHBpZWNlcy5sZW5ndGggPiAxID8gcGllY2VzWzFdLmxlbmd0aCA6IDA7XG5cdH1cblxuXHQvLyBodHRwOi8veW91bWlnaHRub3RuZWVkanF1ZXJ5LmNvbS8jYWRkX2NsYXNzXG5cdGZ1bmN0aW9uIGFkZENsYXNzICggZWwsIGNsYXNzTmFtZSApIHtcblx0XHRpZiAoIGVsLmNsYXNzTGlzdCApIHtcblx0XHRcdGVsLmNsYXNzTGlzdC5hZGQoY2xhc3NOYW1lKTtcblx0XHR9IGVsc2Uge1xuXHRcdFx0ZWwuY2xhc3NOYW1lICs9ICcgJyArIGNsYXNzTmFtZTtcblx0XHR9XG5cdH1cblxuXHQvLyBodHRwOi8veW91bWlnaHRub3RuZWVkanF1ZXJ5LmNvbS8jcmVtb3ZlX2NsYXNzXG5cdGZ1bmN0aW9uIHJlbW92ZUNsYXNzICggZWwsIGNsYXNzTmFtZSApIHtcblx0XHRpZiAoIGVsLmNsYXNzTGlzdCApIHtcblx0XHRcdGVsLmNsYXNzTGlzdC5yZW1vdmUoY2xhc3NOYW1lKTtcblx0XHR9IGVsc2Uge1xuXHRcdFx0ZWwuY2xhc3NOYW1lID0gZWwuY2xhc3NOYW1lLnJlcGxhY2UobmV3IFJlZ0V4cCgnKF58XFxcXGIpJyArIGNsYXNzTmFtZS5zcGxpdCgnICcpLmpvaW4oJ3wnKSArICcoXFxcXGJ8JCknLCAnZ2knKSwgJyAnKTtcblx0XHR9XG5cdH1cblxuXHQvLyBodHRwczovL3BsYWluanMuY29tL2phdmFzY3JpcHQvYXR0cmlidXRlcy9hZGRpbmctcmVtb3ZpbmctYW5kLXRlc3RpbmctZm9yLWNsYXNzZXMtOS9cblx0ZnVuY3Rpb24gaGFzQ2xhc3MgKCBlbCwgY2xhc3NOYW1lICkge1xuXHRcdHJldHVybiBlbC5jbGFzc0xpc3QgPyBlbC5jbGFzc0xpc3QuY29udGFpbnMoY2xhc3NOYW1lKSA6IG5ldyBSZWdFeHAoJ1xcXFxiJyArIGNsYXNzTmFtZSArICdcXFxcYicpLnRlc3QoZWwuY2xhc3NOYW1lKTtcblx0fVxuXG5cdC8vIGh0dHBzOi8vZGV2ZWxvcGVyLm1vemlsbGEub3JnL2VuLVVTL2RvY3MvV2ViL0FQSS9XaW5kb3cvc2Nyb2xsWSNOb3Rlc1xuXHRmdW5jdGlvbiBnZXRQYWdlT2Zmc2V0ICggZG9jICkge1xuXG5cdFx0dmFyIHN1cHBvcnRQYWdlT2Zmc2V0ID0gd2luZG93LnBhZ2VYT2Zmc2V0ICE9PSB1bmRlZmluZWQ7XG5cdFx0dmFyIGlzQ1NTMUNvbXBhdCA9ICgoZG9jLmNvbXBhdE1vZGUgfHwgXCJcIikgPT09IFwiQ1NTMUNvbXBhdFwiKTtcblx0XHR2YXIgeCA9IHN1cHBvcnRQYWdlT2Zmc2V0ID8gd2luZG93LnBhZ2VYT2Zmc2V0IDogaXNDU1MxQ29tcGF0ID8gZG9jLmRvY3VtZW50RWxlbWVudC5zY3JvbGxMZWZ0IDogZG9jLmJvZHkuc2Nyb2xsTGVmdDtcblx0XHR2YXIgeSA9IHN1cHBvcnRQYWdlT2Zmc2V0ID8gd2luZG93LnBhZ2VZT2Zmc2V0IDogaXNDU1MxQ29tcGF0ID8gZG9jLmRvY3VtZW50RWxlbWVudC5zY3JvbGxUb3AgOiBkb2MuYm9keS5zY3JvbGxUb3A7XG5cblx0XHRyZXR1cm4ge1xuXHRcdFx0eDogeCxcblx0XHRcdHk6IHlcblx0XHR9O1xuXHR9XG5cclxuXHQvLyB3ZSBwcm92aWRlIGEgZnVuY3Rpb24gdG8gY29tcHV0ZSBjb25zdGFudHMgaW5zdGVhZFxyXG5cdC8vIG9mIGFjY2Vzc2luZyB3aW5kb3cuKiBhcyBzb29uIGFzIHRoZSBtb2R1bGUgbmVlZHMgaXRcclxuXHQvLyBzbyB0aGF0IHdlIGRvIG5vdCBjb21wdXRlIGFueXRoaW5nIGlmIG5vdCBuZWVkZWRcclxuXHRmdW5jdGlvbiBnZXRBY3Rpb25zICggKSB7XHJcblxyXG5cdFx0Ly8gRGV0ZXJtaW5lIHRoZSBldmVudHMgdG8gYmluZC4gSUUxMSBpbXBsZW1lbnRzIHBvaW50ZXJFdmVudHMgd2l0aG91dFxyXG5cdFx0Ly8gYSBwcmVmaXgsIHdoaWNoIGJyZWFrcyBjb21wYXRpYmlsaXR5IHdpdGggdGhlIElFMTAgaW1wbGVtZW50YXRpb24uXHJcblx0XHRyZXR1cm4gd2luZG93Lm5hdmlnYXRvci5wb2ludGVyRW5hYmxlZCA/IHtcclxuXHRcdFx0c3RhcnQ6ICdwb2ludGVyZG93bicsXHJcblx0XHRcdG1vdmU6ICdwb2ludGVybW92ZScsXHJcblx0XHRcdGVuZDogJ3BvaW50ZXJ1cCdcclxuXHRcdH0gOiB3aW5kb3cubmF2aWdhdG9yLm1zUG9pbnRlckVuYWJsZWQgPyB7XHJcblx0XHRcdHN0YXJ0OiAnTVNQb2ludGVyRG93bicsXHJcblx0XHRcdG1vdmU6ICdNU1BvaW50ZXJNb3ZlJyxcclxuXHRcdFx0ZW5kOiAnTVNQb2ludGVyVXAnXHJcblx0XHR9IDoge1xyXG5cdFx0XHRzdGFydDogJ21vdXNlZG93biB0b3VjaHN0YXJ0JyxcclxuXHRcdFx0bW92ZTogJ21vdXNlbW92ZSB0b3VjaG1vdmUnLFxyXG5cdFx0XHRlbmQ6ICdtb3VzZXVwIHRvdWNoZW5kJ1xyXG5cdFx0fTtcclxuXHR9XHJcblxyXG5cdC8vIGh0dHBzOi8vZ2l0aHViLmNvbS9XSUNHL0V2ZW50TGlzdGVuZXJPcHRpb25zL2Jsb2IvZ2gtcGFnZXMvZXhwbGFpbmVyLm1kXHJcblx0Ly8gSXNzdWUgIzc4NVxyXG5cdGZ1bmN0aW9uIGdldFN1cHBvcnRzUGFzc2l2ZSAoICkge1xyXG5cclxuXHRcdHZhciBzdXBwb3J0c1Bhc3NpdmUgPSBmYWxzZTtcclxuXHJcblx0XHR0cnkge1xyXG5cclxuXHRcdFx0dmFyIG9wdHMgPSBPYmplY3QuZGVmaW5lUHJvcGVydHkoe30sICdwYXNzaXZlJywge1xyXG5cdFx0XHRcdGdldDogZnVuY3Rpb24oKSB7XHJcblx0XHRcdFx0XHRzdXBwb3J0c1Bhc3NpdmUgPSB0cnVlO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fSk7XHJcblxyXG5cdFx0XHR3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcigndGVzdCcsIG51bGwsIG9wdHMpO1xyXG5cclxuXHRcdH0gY2F0Y2ggKGUpIHt9XHJcblxyXG5cdFx0cmV0dXJuIHN1cHBvcnRzUGFzc2l2ZTtcclxuXHR9XHJcblxyXG5cdGZ1bmN0aW9uIGdldFN1cHBvcnRzVG91Y2hBY3Rpb25Ob25lICggKSB7XHJcblx0XHRyZXR1cm4gd2luZG93LkNTUyAmJiBDU1Muc3VwcG9ydHMgJiYgQ1NTLnN1cHBvcnRzKCd0b3VjaC1hY3Rpb24nLCAnbm9uZScpO1xyXG5cdH1cclxuXHJcblxyXG4vLyBWYWx1ZSBjYWxjdWxhdGlvblxyXG5cclxuXHQvLyBEZXRlcm1pbmUgdGhlIHNpemUgb2YgYSBzdWItcmFuZ2UgaW4gcmVsYXRpb24gdG8gYSBmdWxsIHJhbmdlLlxyXG5cdGZ1bmN0aW9uIHN1YlJhbmdlUmF0aW8gKCBwYSwgcGIgKSB7XHJcblx0XHRyZXR1cm4gKDEwMCAvIChwYiAtIHBhKSk7XHJcblx0fVxyXG5cclxuXHQvLyAocGVyY2VudGFnZSkgSG93IG1hbnkgcGVyY2VudCBpcyB0aGlzIHZhbHVlIG9mIHRoaXMgcmFuZ2U/XHJcblx0ZnVuY3Rpb24gZnJvbVBlcmNlbnRhZ2UgKCByYW5nZSwgdmFsdWUgKSB7XHJcblx0XHRyZXR1cm4gKHZhbHVlICogMTAwKSAvICggcmFuZ2VbMV0gLSByYW5nZVswXSApO1xyXG5cdH1cclxuXHJcblx0Ly8gKHBlcmNlbnRhZ2UpIFdoZXJlIGlzIHRoaXMgdmFsdWUgb24gdGhpcyByYW5nZT9cclxuXHRmdW5jdGlvbiB0b1BlcmNlbnRhZ2UgKCByYW5nZSwgdmFsdWUgKSB7XHJcblx0XHRyZXR1cm4gZnJvbVBlcmNlbnRhZ2UoIHJhbmdlLCByYW5nZVswXSA8IDAgP1xyXG5cdFx0XHR2YWx1ZSArIE1hdGguYWJzKHJhbmdlWzBdKSA6XHJcblx0XHRcdFx0dmFsdWUgLSByYW5nZVswXSApO1xyXG5cdH1cclxuXHJcblx0Ly8gKHZhbHVlKSBIb3cgbXVjaCBpcyB0aGlzIHBlcmNlbnRhZ2Ugb24gdGhpcyByYW5nZT9cclxuXHRmdW5jdGlvbiBpc1BlcmNlbnRhZ2UgKCByYW5nZSwgdmFsdWUgKSB7XHJcblx0XHRyZXR1cm4gKCh2YWx1ZSAqICggcmFuZ2VbMV0gLSByYW5nZVswXSApKSAvIDEwMCkgKyByYW5nZVswXTtcclxuXHR9XHJcblxyXG5cclxuLy8gUmFuZ2UgY29udmVyc2lvblxyXG5cclxuXHRmdW5jdGlvbiBnZXRKICggdmFsdWUsIGFyciApIHtcclxuXHJcblx0XHR2YXIgaiA9IDE7XHJcblxyXG5cdFx0d2hpbGUgKCB2YWx1ZSA+PSBhcnJbal0gKXtcclxuXHRcdFx0aiArPSAxO1xyXG5cdFx0fVxyXG5cclxuXHRcdHJldHVybiBqO1xyXG5cdH1cclxuXHJcblx0Ly8gKHBlcmNlbnRhZ2UpIElucHV0IGEgdmFsdWUsIGZpbmQgd2hlcmUsIG9uIGEgc2NhbGUgb2YgMC0xMDAsIGl0IGFwcGxpZXMuXHJcblx0ZnVuY3Rpb24gdG9TdGVwcGluZyAoIHhWYWwsIHhQY3QsIHZhbHVlICkge1xyXG5cclxuXHRcdGlmICggdmFsdWUgPj0geFZhbC5zbGljZSgtMSlbMF0gKXtcclxuXHRcdFx0cmV0dXJuIDEwMDtcclxuXHRcdH1cclxuXHJcblx0XHR2YXIgaiA9IGdldEooIHZhbHVlLCB4VmFsICk7XHJcblx0XHR2YXIgdmEgPSB4VmFsW2otMV07XHJcblx0XHR2YXIgdmIgPSB4VmFsW2pdO1xyXG5cdFx0dmFyIHBhID0geFBjdFtqLTFdO1xyXG5cdFx0dmFyIHBiID0geFBjdFtqXTtcclxuXHJcblx0XHRyZXR1cm4gcGEgKyAodG9QZXJjZW50YWdlKFt2YSwgdmJdLCB2YWx1ZSkgLyBzdWJSYW5nZVJhdGlvIChwYSwgcGIpKTtcclxuXHR9XHJcblxyXG5cdC8vICh2YWx1ZSkgSW5wdXQgYSBwZXJjZW50YWdlLCBmaW5kIHdoZXJlIGl0IGlzIG9uIHRoZSBzcGVjaWZpZWQgcmFuZ2UuXHJcblx0ZnVuY3Rpb24gZnJvbVN0ZXBwaW5nICggeFZhbCwgeFBjdCwgdmFsdWUgKSB7XHJcblxyXG5cdFx0Ly8gVGhlcmUgaXMgbm8gcmFuZ2UgZ3JvdXAgdGhhdCBmaXRzIDEwMFxyXG5cdFx0aWYgKCB2YWx1ZSA+PSAxMDAgKXtcclxuXHRcdFx0cmV0dXJuIHhWYWwuc2xpY2UoLTEpWzBdO1xyXG5cdFx0fVxyXG5cclxuXHRcdHZhciBqID0gZ2V0SiggdmFsdWUsIHhQY3QgKTtcclxuXHRcdHZhciB2YSA9IHhWYWxbai0xXTtcclxuXHRcdHZhciB2YiA9IHhWYWxbal07XHJcblx0XHR2YXIgcGEgPSB4UGN0W2otMV07XHJcblx0XHR2YXIgcGIgPSB4UGN0W2pdO1xyXG5cclxuXHRcdHJldHVybiBpc1BlcmNlbnRhZ2UoW3ZhLCB2Yl0sICh2YWx1ZSAtIHBhKSAqIHN1YlJhbmdlUmF0aW8gKHBhLCBwYikpO1xyXG5cdH1cclxuXHJcblx0Ly8gKHBlcmNlbnRhZ2UpIEdldCB0aGUgc3RlcCB0aGF0IGFwcGxpZXMgYXQgYSBjZXJ0YWluIHZhbHVlLlxyXG5cdGZ1bmN0aW9uIGdldFN0ZXAgKCB4UGN0LCB4U3RlcHMsIHNuYXAsIHZhbHVlICkge1xyXG5cclxuXHRcdGlmICggdmFsdWUgPT09IDEwMCApIHtcclxuXHRcdFx0cmV0dXJuIHZhbHVlO1xyXG5cdFx0fVxyXG5cclxuXHRcdHZhciBqID0gZ2V0SiggdmFsdWUsIHhQY3QgKTtcclxuXHRcdHZhciBhID0geFBjdFtqLTFdO1xyXG5cdFx0dmFyIGIgPSB4UGN0W2pdO1xyXG5cclxuXHRcdC8vIElmICdzbmFwJyBpcyBzZXQsIHN0ZXBzIGFyZSB1c2VkIGFzIGZpeGVkIHBvaW50cyBvbiB0aGUgc2xpZGVyLlxyXG5cdFx0aWYgKCBzbmFwICkge1xyXG5cclxuXHRcdFx0Ly8gRmluZCB0aGUgY2xvc2VzdCBwb3NpdGlvbiwgYSBvciBiLlxyXG5cdFx0XHRpZiAoKHZhbHVlIC0gYSkgPiAoKGItYSkvMikpe1xyXG5cdFx0XHRcdHJldHVybiBiO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHRyZXR1cm4gYTtcclxuXHRcdH1cclxuXHJcblx0XHRpZiAoICF4U3RlcHNbai0xXSApe1xyXG5cdFx0XHRyZXR1cm4gdmFsdWU7XHJcblx0XHR9XHJcblxyXG5cdFx0cmV0dXJuIHhQY3Rbai0xXSArIGNsb3Nlc3QoXHJcblx0XHRcdHZhbHVlIC0geFBjdFtqLTFdLFxyXG5cdFx0XHR4U3RlcHNbai0xXVxyXG5cdFx0KTtcclxuXHR9XHJcblxyXG5cclxuLy8gRW50cnkgcGFyc2luZ1xyXG5cclxuXHRmdW5jdGlvbiBoYW5kbGVFbnRyeVBvaW50ICggaW5kZXgsIHZhbHVlLCB0aGF0ICkge1xyXG5cclxuXHRcdHZhciBwZXJjZW50YWdlO1xyXG5cclxuXHRcdC8vIFdyYXAgbnVtZXJpY2FsIGlucHV0IGluIGFuIGFycmF5LlxyXG5cdFx0aWYgKCB0eXBlb2YgdmFsdWUgPT09IFwibnVtYmVyXCIgKSB7XHJcblx0XHRcdHZhbHVlID0gW3ZhbHVlXTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBSZWplY3QgYW55IGludmFsaWQgaW5wdXQsIGJ5IHRlc3Rpbmcgd2hldGhlciB2YWx1ZSBpcyBhbiBhcnJheS5cclxuXHRcdGlmICggIUFycmF5LmlzQXJyYXkodmFsdWUpICl7XHJcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3JhbmdlJyBjb250YWlucyBpbnZhbGlkIHZhbHVlLlwiKTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBDb3ZlcnQgbWluL21heCBzeW50YXggdG8gMCBhbmQgMTAwLlxyXG5cdFx0aWYgKCBpbmRleCA9PT0gJ21pbicgKSB7XHJcblx0XHRcdHBlcmNlbnRhZ2UgPSAwO1xyXG5cdFx0fSBlbHNlIGlmICggaW5kZXggPT09ICdtYXgnICkge1xyXG5cdFx0XHRwZXJjZW50YWdlID0gMTAwO1xyXG5cdFx0fSBlbHNlIHtcclxuXHRcdFx0cGVyY2VudGFnZSA9IHBhcnNlRmxvYXQoIGluZGV4ICk7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gQ2hlY2sgZm9yIGNvcnJlY3QgaW5wdXQuXHJcblx0XHRpZiAoICFpc051bWVyaWMoIHBlcmNlbnRhZ2UgKSB8fCAhaXNOdW1lcmljKCB2YWx1ZVswXSApICkge1xyXG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdyYW5nZScgdmFsdWUgaXNuJ3QgbnVtZXJpYy5cIik7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gU3RvcmUgdmFsdWVzLlxyXG5cdFx0dGhhdC54UGN0LnB1c2goIHBlcmNlbnRhZ2UgKTtcclxuXHRcdHRoYXQueFZhbC5wdXNoKCB2YWx1ZVswXSApO1xyXG5cclxuXHRcdC8vIE5hTiB3aWxsIGV2YWx1YXRlIHRvIGZhbHNlIHRvbywgYnV0IHRvIGtlZXBcclxuXHRcdC8vIGxvZ2dpbmcgY2xlYXIsIHNldCBzdGVwIGV4cGxpY2l0bHkuIE1ha2Ugc3VyZVxyXG5cdFx0Ly8gbm90IHRvIG92ZXJyaWRlIHRoZSAnc3RlcCcgc2V0dGluZyB3aXRoIGZhbHNlLlxyXG5cdFx0aWYgKCAhcGVyY2VudGFnZSApIHtcclxuXHRcdFx0aWYgKCAhaXNOYU4oIHZhbHVlWzFdICkgKSB7XHJcblx0XHRcdFx0dGhhdC54U3RlcHNbMF0gPSB2YWx1ZVsxXTtcclxuXHRcdFx0fVxyXG5cdFx0fSBlbHNlIHtcclxuXHRcdFx0dGhhdC54U3RlcHMucHVzaCggaXNOYU4odmFsdWVbMV0pID8gZmFsc2UgOiB2YWx1ZVsxXSApO1xyXG5cdFx0fVxyXG5cclxuXHRcdHRoYXQueEhpZ2hlc3RDb21wbGV0ZVN0ZXAucHVzaCgwKTtcclxuXHR9XHJcblxyXG5cdGZ1bmN0aW9uIGhhbmRsZVN0ZXBQb2ludCAoIGksIG4sIHRoYXQgKSB7XHJcblxyXG5cdFx0Ly8gSWdub3JlICdmYWxzZScgc3RlcHBpbmcuXHJcblx0XHRpZiAoICFuICkge1xyXG5cdFx0XHRyZXR1cm4gdHJ1ZTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBGYWN0b3IgdG8gcmFuZ2UgcmF0aW9cclxuXHRcdHRoYXQueFN0ZXBzW2ldID0gZnJvbVBlcmNlbnRhZ2UoW3RoYXQueFZhbFtpXSwgdGhhdC54VmFsW2krMV1dLCBuKSAvIHN1YlJhbmdlUmF0aW8odGhhdC54UGN0W2ldLCB0aGF0LnhQY3RbaSsxXSk7XHJcblxyXG5cdFx0dmFyIHRvdGFsU3RlcHMgPSAodGhhdC54VmFsW2krMV0gLSB0aGF0LnhWYWxbaV0pIC8gdGhhdC54TnVtU3RlcHNbaV07XHJcblx0XHR2YXIgaGlnaGVzdFN0ZXAgPSBNYXRoLmNlaWwoTnVtYmVyKHRvdGFsU3RlcHMudG9GaXhlZCgzKSkgLSAxKTtcclxuXHRcdHZhciBzdGVwID0gdGhhdC54VmFsW2ldICsgKHRoYXQueE51bVN0ZXBzW2ldICogaGlnaGVzdFN0ZXApO1xyXG5cclxuXHRcdHRoYXQueEhpZ2hlc3RDb21wbGV0ZVN0ZXBbaV0gPSBzdGVwO1xyXG5cdH1cclxuXHJcblxyXG4vLyBJbnRlcmZhY2VcclxuXHJcblx0ZnVuY3Rpb24gU3BlY3RydW0gKCBlbnRyeSwgc25hcCwgc2luZ2xlU3RlcCApIHtcclxuXHJcblx0XHR0aGlzLnhQY3QgPSBbXTtcclxuXHRcdHRoaXMueFZhbCA9IFtdO1xyXG5cdFx0dGhpcy54U3RlcHMgPSBbIHNpbmdsZVN0ZXAgfHwgZmFsc2UgXTtcclxuXHRcdHRoaXMueE51bVN0ZXBzID0gWyBmYWxzZSBdO1xyXG5cdFx0dGhpcy54SGlnaGVzdENvbXBsZXRlU3RlcCA9IFtdO1xyXG5cclxuXHRcdHRoaXMuc25hcCA9IHNuYXA7XHJcblxyXG5cdFx0dmFyIGluZGV4O1xyXG5cdFx0dmFyIG9yZGVyZWQgPSBbXTsgLy8gWzAsICdtaW4nXSwgWzEsICc1MCUnXSwgWzIsICdtYXgnXVxyXG5cclxuXHRcdC8vIE1hcCB0aGUgb2JqZWN0IGtleXMgdG8gYW4gYXJyYXkuXHJcblx0XHRmb3IgKCBpbmRleCBpbiBlbnRyeSApIHtcclxuXHRcdFx0aWYgKCBlbnRyeS5oYXNPd25Qcm9wZXJ0eShpbmRleCkgKSB7XHJcblx0XHRcdFx0b3JkZXJlZC5wdXNoKFtlbnRyeVtpbmRleF0sIGluZGV4XSk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHQvLyBTb3J0IGFsbCBlbnRyaWVzIGJ5IHZhbHVlIChudW1lcmljIHNvcnQpLlxyXG5cdFx0aWYgKCBvcmRlcmVkLmxlbmd0aCAmJiB0eXBlb2Ygb3JkZXJlZFswXVswXSA9PT0gXCJvYmplY3RcIiApIHtcclxuXHRcdFx0b3JkZXJlZC5zb3J0KGZ1bmN0aW9uKGEsIGIpIHsgcmV0dXJuIGFbMF1bMF0gLSBiWzBdWzBdOyB9KTtcclxuXHRcdH0gZWxzZSB7XHJcblx0XHRcdG9yZGVyZWQuc29ydChmdW5jdGlvbihhLCBiKSB7IHJldHVybiBhWzBdIC0gYlswXTsgfSk7XHJcblx0XHR9XHJcblxyXG5cclxuXHRcdC8vIENvbnZlcnQgYWxsIGVudHJpZXMgdG8gc3VicmFuZ2VzLlxyXG5cdFx0Zm9yICggaW5kZXggPSAwOyBpbmRleCA8IG9yZGVyZWQubGVuZ3RoOyBpbmRleCsrICkge1xyXG5cdFx0XHRoYW5kbGVFbnRyeVBvaW50KG9yZGVyZWRbaW5kZXhdWzFdLCBvcmRlcmVkW2luZGV4XVswXSwgdGhpcyk7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gU3RvcmUgdGhlIGFjdHVhbCBzdGVwIHZhbHVlcy5cclxuXHRcdC8vIHhTdGVwcyBpcyBzb3J0ZWQgaW4gdGhlIHNhbWUgb3JkZXIgYXMgeFBjdCBhbmQgeFZhbC5cclxuXHRcdHRoaXMueE51bVN0ZXBzID0gdGhpcy54U3RlcHMuc2xpY2UoMCk7XHJcblxyXG5cdFx0Ly8gQ29udmVydCBhbGwgbnVtZXJpYyBzdGVwcyB0byB0aGUgcGVyY2VudGFnZSBvZiB0aGUgc3VicmFuZ2UgdGhleSByZXByZXNlbnQuXHJcblx0XHRmb3IgKCBpbmRleCA9IDA7IGluZGV4IDwgdGhpcy54TnVtU3RlcHMubGVuZ3RoOyBpbmRleCsrICkge1xyXG5cdFx0XHRoYW5kbGVTdGVwUG9pbnQoaW5kZXgsIHRoaXMueE51bVN0ZXBzW2luZGV4XSwgdGhpcyk7XHJcblx0XHR9XHJcblx0fVxyXG5cclxuXHRTcGVjdHJ1bS5wcm90b3R5cGUuZ2V0TWFyZ2luID0gZnVuY3Rpb24gKCB2YWx1ZSApIHtcclxuXHJcblx0XHR2YXIgc3RlcCA9IHRoaXMueE51bVN0ZXBzWzBdO1xyXG5cclxuXHRcdGlmICggc3RlcCAmJiAoKHZhbHVlIC8gc3RlcCkgJSAxKSAhPT0gMCApIHtcclxuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnbGltaXQnLCAnbWFyZ2luJyBhbmQgJ3BhZGRpbmcnIG11c3QgYmUgZGl2aXNpYmxlIGJ5IHN0ZXAuXCIpO1xyXG5cdFx0fVxyXG5cclxuXHRcdHJldHVybiB0aGlzLnhQY3QubGVuZ3RoID09PSAyID8gZnJvbVBlcmNlbnRhZ2UodGhpcy54VmFsLCB2YWx1ZSkgOiBmYWxzZTtcclxuXHR9O1xyXG5cclxuXHRTcGVjdHJ1bS5wcm90b3R5cGUudG9TdGVwcGluZyA9IGZ1bmN0aW9uICggdmFsdWUgKSB7XHJcblxyXG5cdFx0dmFsdWUgPSB0b1N0ZXBwaW5nKCB0aGlzLnhWYWwsIHRoaXMueFBjdCwgdmFsdWUgKTtcclxuXHJcblx0XHRyZXR1cm4gdmFsdWU7XHJcblx0fTtcclxuXHJcblx0U3BlY3RydW0ucHJvdG90eXBlLmZyb21TdGVwcGluZyA9IGZ1bmN0aW9uICggdmFsdWUgKSB7XHJcblxyXG5cdFx0cmV0dXJuIGZyb21TdGVwcGluZyggdGhpcy54VmFsLCB0aGlzLnhQY3QsIHZhbHVlICk7XHJcblx0fTtcclxuXHJcblx0U3BlY3RydW0ucHJvdG90eXBlLmdldFN0ZXAgPSBmdW5jdGlvbiAoIHZhbHVlICkge1xyXG5cclxuXHRcdHZhbHVlID0gZ2V0U3RlcCh0aGlzLnhQY3QsIHRoaXMueFN0ZXBzLCB0aGlzLnNuYXAsIHZhbHVlICk7XHJcblxyXG5cdFx0cmV0dXJuIHZhbHVlO1xyXG5cdH07XHJcblxyXG5cdFNwZWN0cnVtLnByb3RvdHlwZS5nZXROZWFyYnlTdGVwcyA9IGZ1bmN0aW9uICggdmFsdWUgKSB7XHJcblxyXG5cdFx0dmFyIGogPSBnZXRKKHZhbHVlLCB0aGlzLnhQY3QpO1xyXG5cclxuXHRcdHJldHVybiB7XHJcblx0XHRcdHN0ZXBCZWZvcmU6IHsgc3RhcnRWYWx1ZTogdGhpcy54VmFsW2otMl0sIHN0ZXA6IHRoaXMueE51bVN0ZXBzW2otMl0sIGhpZ2hlc3RTdGVwOiB0aGlzLnhIaWdoZXN0Q29tcGxldGVTdGVwW2otMl0gfSxcclxuXHRcdFx0dGhpc1N0ZXA6IHsgc3RhcnRWYWx1ZTogdGhpcy54VmFsW2otMV0sIHN0ZXA6IHRoaXMueE51bVN0ZXBzW2otMV0sIGhpZ2hlc3RTdGVwOiB0aGlzLnhIaWdoZXN0Q29tcGxldGVTdGVwW2otMV0gfSxcclxuXHRcdFx0c3RlcEFmdGVyOiB7IHN0YXJ0VmFsdWU6IHRoaXMueFZhbFtqLTBdLCBzdGVwOiB0aGlzLnhOdW1TdGVwc1tqLTBdLCBoaWdoZXN0U3RlcDogdGhpcy54SGlnaGVzdENvbXBsZXRlU3RlcFtqLTBdIH1cclxuXHRcdH07XHJcblx0fTtcclxuXHJcblx0U3BlY3RydW0ucHJvdG90eXBlLmNvdW50U3RlcERlY2ltYWxzID0gZnVuY3Rpb24gKCkge1xyXG5cdFx0dmFyIHN0ZXBEZWNpbWFscyA9IHRoaXMueE51bVN0ZXBzLm1hcChjb3VudERlY2ltYWxzKTtcclxuXHRcdHJldHVybiBNYXRoLm1heC5hcHBseShudWxsLCBzdGVwRGVjaW1hbHMpO1xyXG5cdH07XHJcblxyXG5cdC8vIE91dHNpZGUgdGVzdGluZ1xyXG5cdFNwZWN0cnVtLnByb3RvdHlwZS5jb252ZXJ0ID0gZnVuY3Rpb24gKCB2YWx1ZSApIHtcclxuXHRcdHJldHVybiB0aGlzLmdldFN0ZXAodGhpcy50b1N0ZXBwaW5nKHZhbHVlKSk7XHJcblx0fTtcclxuXHJcbi8qXHRFdmVyeSBpbnB1dCBvcHRpb24gaXMgdGVzdGVkIGFuZCBwYXJzZWQuIFRoaXMnbGwgcHJldmVudFxuXHRlbmRsZXNzIHZhbGlkYXRpb24gaW4gaW50ZXJuYWwgbWV0aG9kcy4gVGhlc2UgdGVzdHMgYXJlXG5cdHN0cnVjdHVyZWQgd2l0aCBhbiBpdGVtIGZvciBldmVyeSBvcHRpb24gYXZhaWxhYmxlLiBBblxuXHRvcHRpb24gY2FuIGJlIG1hcmtlZCBhcyByZXF1aXJlZCBieSBzZXR0aW5nIHRoZSAncicgZmxhZy5cblx0VGhlIHRlc3RpbmcgZnVuY3Rpb24gaXMgcHJvdmlkZWQgd2l0aCB0aHJlZSBhcmd1bWVudHM6XG5cdFx0LSBUaGUgcHJvdmlkZWQgdmFsdWUgZm9yIHRoZSBvcHRpb247XG5cdFx0LSBBIHJlZmVyZW5jZSB0byB0aGUgb3B0aW9ucyBvYmplY3Q7XG5cdFx0LSBUaGUgbmFtZSBmb3IgdGhlIG9wdGlvbjtcblxuXHRUaGUgdGVzdGluZyBmdW5jdGlvbiByZXR1cm5zIGZhbHNlIHdoZW4gYW4gZXJyb3IgaXMgZGV0ZWN0ZWQsXG5cdG9yIHRydWUgd2hlbiBldmVyeXRoaW5nIGlzIE9LLiBJdCBjYW4gYWxzbyBtb2RpZnkgdGhlIG9wdGlvblxuXHRvYmplY3QsIHRvIG1ha2Ugc3VyZSBhbGwgdmFsdWVzIGNhbiBiZSBjb3JyZWN0bHkgbG9vcGVkIGVsc2V3aGVyZS4gKi9cblxuXHR2YXIgZGVmYXVsdEZvcm1hdHRlciA9IHsgJ3RvJzogZnVuY3Rpb24oIHZhbHVlICl7XG5cdFx0cmV0dXJuIHZhbHVlICE9PSB1bmRlZmluZWQgJiYgdmFsdWUudG9GaXhlZCgyKTtcblx0fSwgJ2Zyb20nOiBOdW1iZXIgfTtcblxuXHRmdW5jdGlvbiB2YWxpZGF0ZUZvcm1hdCAoIGVudHJ5ICkge1xuXG5cdFx0Ly8gQW55IG9iamVjdCB3aXRoIGEgdG8gYW5kIGZyb20gbWV0aG9kIGlzIHN1cHBvcnRlZC5cblx0XHRpZiAoIGlzVmFsaWRGb3JtYXR0ZXIoZW50cnkpICkge1xuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fVxuXG5cdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnZm9ybWF0JyByZXF1aXJlcyAndG8nIGFuZCAnZnJvbScgbWV0aG9kcy5cIik7XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0U3RlcCAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHRpZiAoICFpc051bWVyaWMoIGVudHJ5ICkgKSB7XG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdzdGVwJyBpcyBub3QgbnVtZXJpYy5cIik7XG5cdFx0fVxuXG5cdFx0Ly8gVGhlIHN0ZXAgb3B0aW9uIGNhbiBzdGlsbCBiZSB1c2VkIHRvIHNldCBzdGVwcGluZ1xuXHRcdC8vIGZvciBsaW5lYXIgc2xpZGVycy4gT3ZlcndyaXR0ZW4gaWYgc2V0IGluICdyYW5nZScuXG5cdFx0cGFyc2VkLnNpbmdsZVN0ZXAgPSBlbnRyeTtcblx0fVxuXG5cdGZ1bmN0aW9uIHRlc3RSYW5nZSAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHQvLyBGaWx0ZXIgaW5jb3JyZWN0IGlucHV0LlxuXHRcdGlmICggdHlwZW9mIGVudHJ5ICE9PSAnb2JqZWN0JyB8fCBBcnJheS5pc0FycmF5KGVudHJ5KSApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3JhbmdlJyBpcyBub3QgYW4gb2JqZWN0LlwiKTtcblx0XHR9XG5cblx0XHQvLyBDYXRjaCBtaXNzaW5nIHN0YXJ0IG9yIGVuZC5cblx0XHRpZiAoIGVudHJ5Lm1pbiA9PT0gdW5kZWZpbmVkIHx8IGVudHJ5Lm1heCA9PT0gdW5kZWZpbmVkICkge1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiBNaXNzaW5nICdtaW4nIG9yICdtYXgnIGluICdyYW5nZScuXCIpO1xuXHRcdH1cblxuXHRcdC8vIENhdGNoIGVxdWFsIHN0YXJ0IG9yIGVuZC5cblx0XHRpZiAoIGVudHJ5Lm1pbiA9PT0gZW50cnkubWF4ICkge1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAncmFuZ2UnICdtaW4nIGFuZCAnbWF4JyBjYW5ub3QgYmUgZXF1YWwuXCIpO1xuXHRcdH1cblxuXHRcdHBhcnNlZC5zcGVjdHJ1bSA9IG5ldyBTcGVjdHJ1bShlbnRyeSwgcGFyc2VkLnNuYXAsIHBhcnNlZC5zaW5nbGVTdGVwKTtcblx0fVxuXG5cdGZ1bmN0aW9uIHRlc3RTdGFydCAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHRlbnRyeSA9IGFzQXJyYXkoZW50cnkpO1xuXG5cdFx0Ly8gVmFsaWRhdGUgaW5wdXQuIFZhbHVlcyBhcmVuJ3QgdGVzdGVkLCBhcyB0aGUgcHVibGljIC52YWwgbWV0aG9kXG5cdFx0Ly8gd2lsbCBhbHdheXMgcHJvdmlkZSBhIHZhbGlkIGxvY2F0aW9uLlxuXHRcdGlmICggIUFycmF5LmlzQXJyYXkoIGVudHJ5ICkgfHwgIWVudHJ5Lmxlbmd0aCApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3N0YXJ0JyBvcHRpb24gaXMgaW5jb3JyZWN0LlwiKTtcblx0XHR9XG5cblx0XHQvLyBTdG9yZSB0aGUgbnVtYmVyIG9mIGhhbmRsZXMuXG5cdFx0cGFyc2VkLmhhbmRsZXMgPSBlbnRyeS5sZW5ndGg7XG5cblx0XHQvLyBXaGVuIHRoZSBzbGlkZXIgaXMgaW5pdGlhbGl6ZWQsIHRoZSAudmFsIG1ldGhvZCB3aWxsXG5cdFx0Ly8gYmUgY2FsbGVkIHdpdGggdGhlIHN0YXJ0IG9wdGlvbnMuXG5cdFx0cGFyc2VkLnN0YXJ0ID0gZW50cnk7XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0U25hcCAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHQvLyBFbmZvcmNlIDEwMCUgc3RlcHBpbmcgd2l0aGluIHN1YnJhbmdlcy5cblx0XHRwYXJzZWQuc25hcCA9IGVudHJ5O1xuXG5cdFx0aWYgKCB0eXBlb2YgZW50cnkgIT09ICdib29sZWFuJyApe1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnc25hcCcgb3B0aW9uIG11c3QgYmUgYSBib29sZWFuLlwiKTtcblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0QW5pbWF0ZSAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHQvLyBFbmZvcmNlIDEwMCUgc3RlcHBpbmcgd2l0aGluIHN1YnJhbmdlcy5cblx0XHRwYXJzZWQuYW5pbWF0ZSA9IGVudHJ5O1xuXG5cdFx0aWYgKCB0eXBlb2YgZW50cnkgIT09ICdib29sZWFuJyApe1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnYW5pbWF0ZScgb3B0aW9uIG11c3QgYmUgYSBib29sZWFuLlwiKTtcblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0QW5pbWF0aW9uRHVyYXRpb24gKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0cGFyc2VkLmFuaW1hdGlvbkR1cmF0aW9uID0gZW50cnk7XG5cblx0XHRpZiAoIHR5cGVvZiBlbnRyeSAhPT0gJ251bWJlcicgKXtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ2FuaW1hdGlvbkR1cmF0aW9uJyBvcHRpb24gbXVzdCBiZSBhIG51bWJlci5cIik7XG5cdFx0fVxuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdENvbm5lY3QgKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0dmFyIGNvbm5lY3QgPSBbZmFsc2VdO1xuXHRcdHZhciBpO1xuXG5cdFx0Ly8gTWFwIGxlZ2FjeSBvcHRpb25zXG5cdFx0aWYgKCBlbnRyeSA9PT0gJ2xvd2VyJyApIHtcblx0XHRcdGVudHJ5ID0gW3RydWUsIGZhbHNlXTtcblx0XHR9XG5cblx0XHRlbHNlIGlmICggZW50cnkgPT09ICd1cHBlcicgKSB7XG5cdFx0XHRlbnRyeSA9IFtmYWxzZSwgdHJ1ZV07XG5cdFx0fVxuXG5cdFx0Ly8gSGFuZGxlIGJvb2xlYW4gb3B0aW9uc1xuXHRcdGlmICggZW50cnkgPT09IHRydWUgfHwgZW50cnkgPT09IGZhbHNlICkge1xuXG5cdFx0XHRmb3IgKCBpID0gMTsgaSA8IHBhcnNlZC5oYW5kbGVzOyBpKysgKSB7XG5cdFx0XHRcdGNvbm5lY3QucHVzaChlbnRyeSk7XG5cdFx0XHR9XG5cblx0XHRcdGNvbm5lY3QucHVzaChmYWxzZSk7XG5cdFx0fVxuXG5cdFx0Ly8gUmVqZWN0IGludmFsaWQgaW5wdXRcblx0XHRlbHNlIGlmICggIUFycmF5LmlzQXJyYXkoIGVudHJ5ICkgfHwgIWVudHJ5Lmxlbmd0aCB8fCBlbnRyeS5sZW5ndGggIT09IHBhcnNlZC5oYW5kbGVzICsgMSApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ2Nvbm5lY3QnIG9wdGlvbiBkb2Vzbid0IG1hdGNoIGhhbmRsZSBjb3VudC5cIik7XG5cdFx0fVxuXG5cdFx0ZWxzZSB7XG5cdFx0XHRjb25uZWN0ID0gZW50cnk7XG5cdFx0fVxuXG5cdFx0cGFyc2VkLmNvbm5lY3QgPSBjb25uZWN0O1xuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdE9yaWVudGF0aW9uICggcGFyc2VkLCBlbnRyeSApIHtcblxuXHRcdC8vIFNldCBvcmllbnRhdGlvbiB0byBhbiBhIG51bWVyaWNhbCB2YWx1ZSBmb3IgZWFzeVxuXHRcdC8vIGFycmF5IHNlbGVjdGlvbi5cblx0XHRzd2l0Y2ggKCBlbnRyeSApe1xuXHRcdFx0Y2FzZSAnaG9yaXpvbnRhbCc6XG5cdFx0XHRcdHBhcnNlZC5vcnQgPSAwO1xuXHRcdFx0XHRicmVhaztcblx0XHRcdGNhc2UgJ3ZlcnRpY2FsJzpcblx0XHRcdFx0cGFyc2VkLm9ydCA9IDE7XG5cdFx0XHRcdGJyZWFrO1xuXHRcdFx0ZGVmYXVsdDpcblx0XHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnb3JpZW50YXRpb24nIG9wdGlvbiBpcyBpbnZhbGlkLlwiKTtcblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0TWFyZ2luICggcGFyc2VkLCBlbnRyeSApIHtcblxuXHRcdGlmICggIWlzTnVtZXJpYyhlbnRyeSkgKXtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ21hcmdpbicgb3B0aW9uIG11c3QgYmUgbnVtZXJpYy5cIik7XG5cdFx0fVxuXG5cdFx0Ly8gSXNzdWUgIzU4MlxuXHRcdGlmICggZW50cnkgPT09IDAgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0cGFyc2VkLm1hcmdpbiA9IHBhcnNlZC5zcGVjdHJ1bS5nZXRNYXJnaW4oZW50cnkpO1xuXG5cdFx0aWYgKCAhcGFyc2VkLm1hcmdpbiApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ21hcmdpbicgb3B0aW9uIGlzIG9ubHkgc3VwcG9ydGVkIG9uIGxpbmVhciBzbGlkZXJzLlwiKTtcblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0TGltaXQgKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0aWYgKCAhaXNOdW1lcmljKGVudHJ5KSApe1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnbGltaXQnIG9wdGlvbiBtdXN0IGJlIG51bWVyaWMuXCIpO1xuXHRcdH1cblxuXHRcdHBhcnNlZC5saW1pdCA9IHBhcnNlZC5zcGVjdHJ1bS5nZXRNYXJnaW4oZW50cnkpO1xuXG5cdFx0aWYgKCAhcGFyc2VkLmxpbWl0IHx8IHBhcnNlZC5oYW5kbGVzIDwgMiApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ2xpbWl0JyBvcHRpb24gaXMgb25seSBzdXBwb3J0ZWQgb24gbGluZWFyIHNsaWRlcnMgd2l0aCAyIG9yIG1vcmUgaGFuZGxlcy5cIik7XG5cdFx0fVxuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdFBhZGRpbmcgKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0aWYgKCAhaXNOdW1lcmljKGVudHJ5KSAmJiAhQXJyYXkuaXNBcnJheShlbnRyeSkgKXtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3BhZGRpbmcnIG9wdGlvbiBtdXN0IGJlIG51bWVyaWMgb3IgYXJyYXkgb2YgZXhhY3RseSAyIG51bWJlcnMuXCIpO1xuXHRcdH1cblxuXHRcdGlmICggQXJyYXkuaXNBcnJheShlbnRyeSkgJiYgIShlbnRyeS5sZW5ndGggPT09IDIgfHwgaXNOdW1lcmljKGVudHJ5WzBdKSB8fCBpc051bWVyaWMoZW50cnlbMV0pKSApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3BhZGRpbmcnIG9wdGlvbiBtdXN0IGJlIG51bWVyaWMgb3IgYXJyYXkgb2YgZXhhY3RseSAyIG51bWJlcnMuXCIpO1xuXHRcdH1cblxuXHRcdGlmICggZW50cnkgPT09IDAgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0aWYgKCAhQXJyYXkuaXNBcnJheShlbnRyeSkgKSB7XG5cdFx0XHRlbnRyeSA9IFtlbnRyeSwgZW50cnldO1xuXHRcdH1cblxuXHRcdC8vICdnZXRNYXJnaW4nIHJldHVybnMgZmFsc2UgZm9yIGludmFsaWQgdmFsdWVzLlxuXHRcdHBhcnNlZC5wYWRkaW5nID0gW3BhcnNlZC5zcGVjdHJ1bS5nZXRNYXJnaW4oZW50cnlbMF0pLCBwYXJzZWQuc3BlY3RydW0uZ2V0TWFyZ2luKGVudHJ5WzFdKV07XG5cblx0XHRpZiAoIHBhcnNlZC5wYWRkaW5nWzBdID09PSBmYWxzZSB8fCBwYXJzZWQucGFkZGluZ1sxXSA9PT0gZmFsc2UgKSB7XG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdwYWRkaW5nJyBvcHRpb24gaXMgb25seSBzdXBwb3J0ZWQgb24gbGluZWFyIHNsaWRlcnMuXCIpO1xuXHRcdH1cblxuXHRcdGlmICggcGFyc2VkLnBhZGRpbmdbMF0gPCAwIHx8IHBhcnNlZC5wYWRkaW5nWzFdIDwgMCApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3BhZGRpbmcnIG9wdGlvbiBtdXN0IGJlIGEgcG9zaXRpdmUgbnVtYmVyKHMpLlwiKTtcblx0XHR9XG5cblx0XHRpZiAoIHBhcnNlZC5wYWRkaW5nWzBdICsgcGFyc2VkLnBhZGRpbmdbMV0gPj0gMTAwICkge1xuXHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAncGFkZGluZycgb3B0aW9uIG11c3Qgbm90IGV4Y2VlZCAxMDAlIG9mIHRoZSByYW5nZS5cIik7XG5cdFx0fVxuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdERpcmVjdGlvbiAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHQvLyBTZXQgZGlyZWN0aW9uIGFzIGEgbnVtZXJpY2FsIHZhbHVlIGZvciBlYXN5IHBhcnNpbmcuXG5cdFx0Ly8gSW52ZXJ0IGNvbm5lY3Rpb24gZm9yIFJUTCBzbGlkZXJzLCBzbyB0aGF0IHRoZSBwcm9wZXJcblx0XHQvLyBoYW5kbGVzIGdldCB0aGUgY29ubmVjdC9iYWNrZ3JvdW5kIGNsYXNzZXMuXG5cdFx0c3dpdGNoICggZW50cnkgKSB7XG5cdFx0XHRjYXNlICdsdHInOlxuXHRcdFx0XHRwYXJzZWQuZGlyID0gMDtcblx0XHRcdFx0YnJlYWs7XG5cdFx0XHRjYXNlICdydGwnOlxuXHRcdFx0XHRwYXJzZWQuZGlyID0gMTtcblx0XHRcdFx0YnJlYWs7XG5cdFx0XHRkZWZhdWx0OlxuXHRcdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdkaXJlY3Rpb24nIG9wdGlvbiB3YXMgbm90IHJlY29nbml6ZWQuXCIpO1xuXHRcdH1cblx0fVxuXG5cdGZ1bmN0aW9uIHRlc3RCZWhhdmlvdXIgKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0Ly8gTWFrZSBzdXJlIHRoZSBpbnB1dCBpcyBhIHN0cmluZy5cblx0XHRpZiAoIHR5cGVvZiBlbnRyeSAhPT0gJ3N0cmluZycgKSB7XG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdiZWhhdmlvdXInIG11c3QgYmUgYSBzdHJpbmcgY29udGFpbmluZyBvcHRpb25zLlwiKTtcblx0XHR9XG5cblx0XHQvLyBDaGVjayBpZiB0aGUgc3RyaW5nIGNvbnRhaW5zIGFueSBrZXl3b3Jkcy5cblx0XHQvLyBOb25lIGFyZSByZXF1aXJlZC5cblx0XHR2YXIgdGFwID0gZW50cnkuaW5kZXhPZigndGFwJykgPj0gMDtcblx0XHR2YXIgZHJhZyA9IGVudHJ5LmluZGV4T2YoJ2RyYWcnKSA+PSAwO1xuXHRcdHZhciBmaXhlZCA9IGVudHJ5LmluZGV4T2YoJ2ZpeGVkJykgPj0gMDtcblx0XHR2YXIgc25hcCA9IGVudHJ5LmluZGV4T2YoJ3NuYXAnKSA+PSAwO1xuXHRcdHZhciBob3ZlciA9IGVudHJ5LmluZGV4T2YoJ2hvdmVyJykgPj0gMDtcblxuXHRcdGlmICggZml4ZWQgKSB7XG5cblx0XHRcdGlmICggcGFyc2VkLmhhbmRsZXMgIT09IDIgKSB7XG5cdFx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ2ZpeGVkJyBiZWhhdmlvdXIgbXVzdCBiZSB1c2VkIHdpdGggMiBoYW5kbGVzXCIpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBVc2UgbWFyZ2luIHRvIGVuZm9yY2UgZml4ZWQgc3RhdGVcblx0XHRcdHRlc3RNYXJnaW4ocGFyc2VkLCBwYXJzZWQuc3RhcnRbMV0gLSBwYXJzZWQuc3RhcnRbMF0pO1xuXHRcdH1cblxuXHRcdHBhcnNlZC5ldmVudHMgPSB7XG5cdFx0XHR0YXA6IHRhcCB8fCBzbmFwLFxuXHRcdFx0ZHJhZzogZHJhZyxcblx0XHRcdGZpeGVkOiBmaXhlZCxcblx0XHRcdHNuYXA6IHNuYXAsXG5cdFx0XHRob3ZlcjogaG92ZXJcblx0XHR9O1xuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdFRvb2x0aXBzICggcGFyc2VkLCBlbnRyeSApIHtcblxuXHRcdGlmICggZW50cnkgPT09IGZhbHNlICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdGVsc2UgaWYgKCBlbnRyeSA9PT0gdHJ1ZSApIHtcblxuXHRcdFx0cGFyc2VkLnRvb2x0aXBzID0gW107XG5cblx0XHRcdGZvciAoIHZhciBpID0gMDsgaSA8IHBhcnNlZC5oYW5kbGVzOyBpKysgKSB7XG5cdFx0XHRcdHBhcnNlZC50b29sdGlwcy5wdXNoKHRydWUpO1xuXHRcdFx0fVxuXHRcdH1cblxuXHRcdGVsc2Uge1xuXG5cdFx0XHRwYXJzZWQudG9vbHRpcHMgPSBhc0FycmF5KGVudHJ5KTtcblxuXHRcdFx0aWYgKCBwYXJzZWQudG9vbHRpcHMubGVuZ3RoICE9PSBwYXJzZWQuaGFuZGxlcyApIHtcblx0XHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiBtdXN0IHBhc3MgYSBmb3JtYXR0ZXIgZm9yIGFsbCBoYW5kbGVzLlwiKTtcblx0XHRcdH1cblxuXHRcdFx0cGFyc2VkLnRvb2x0aXBzLmZvckVhY2goZnVuY3Rpb24oZm9ybWF0dGVyKXtcblx0XHRcdFx0aWYgKCB0eXBlb2YgZm9ybWF0dGVyICE9PSAnYm9vbGVhbicgJiYgKHR5cGVvZiBmb3JtYXR0ZXIgIT09ICdvYmplY3QnIHx8IHR5cGVvZiBmb3JtYXR0ZXIudG8gIT09ICdmdW5jdGlvbicpICkge1xuXHRcdFx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ3Rvb2x0aXBzJyBtdXN0IGJlIHBhc3NlZCBhIGZvcm1hdHRlciBvciAnZmFsc2UnLlwiKTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0fVxuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdEFyaWFGb3JtYXQgKCBwYXJzZWQsIGVudHJ5ICkge1xuXHRcdHBhcnNlZC5hcmlhRm9ybWF0ID0gZW50cnk7XG5cdFx0dmFsaWRhdGVGb3JtYXQoZW50cnkpO1xuXHR9XG5cblx0ZnVuY3Rpb24gdGVzdEZvcm1hdCAoIHBhcnNlZCwgZW50cnkgKSB7XG5cdFx0cGFyc2VkLmZvcm1hdCA9IGVudHJ5O1xuXHRcdHZhbGlkYXRlRm9ybWF0KGVudHJ5KTtcblx0fVxuXG5cdGZ1bmN0aW9uIHRlc3RDc3NQcmVmaXggKCBwYXJzZWQsIGVudHJ5ICkge1xuXG5cdFx0aWYgKCB0eXBlb2YgZW50cnkgIT09ICdzdHJpbmcnICYmIGVudHJ5ICE9PSBmYWxzZSApIHtcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogJ2Nzc1ByZWZpeCcgbXVzdCBiZSBhIHN0cmluZyBvciBgZmFsc2VgLlwiKTtcblx0XHR9XG5cblx0XHRwYXJzZWQuY3NzUHJlZml4ID0gZW50cnk7XG5cdH1cblxuXHRmdW5jdGlvbiB0ZXN0Q3NzQ2xhc3NlcyAoIHBhcnNlZCwgZW50cnkgKSB7XG5cblx0XHRpZiAoIHR5cGVvZiBlbnRyeSAhPT0gJ29iamVjdCcgKSB7XG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6ICdjc3NDbGFzc2VzJyBtdXN0IGJlIGFuIG9iamVjdC5cIik7XG5cdFx0fVxuXG5cdFx0aWYgKCB0eXBlb2YgcGFyc2VkLmNzc1ByZWZpeCA9PT0gJ3N0cmluZycgKSB7XG5cdFx0XHRwYXJzZWQuY3NzQ2xhc3NlcyA9IHt9O1xuXG5cdFx0XHRmb3IgKCB2YXIga2V5IGluIGVudHJ5ICkge1xuXHRcdFx0XHRpZiAoICFlbnRyeS5oYXNPd25Qcm9wZXJ0eShrZXkpICkgeyBjb250aW51ZTsgfVxuXG5cdFx0XHRcdHBhcnNlZC5jc3NDbGFzc2VzW2tleV0gPSBwYXJzZWQuY3NzUHJlZml4ICsgZW50cnlba2V5XTtcblx0XHRcdH1cblx0XHR9IGVsc2Uge1xuXHRcdFx0cGFyc2VkLmNzc0NsYXNzZXMgPSBlbnRyeTtcblx0XHR9XG5cdH1cblxuXHQvLyBUZXN0IGFsbCBkZXZlbG9wZXIgc2V0dGluZ3MgYW5kIHBhcnNlIHRvIGFzc3VtcHRpb24tc2FmZSB2YWx1ZXMuXG5cdGZ1bmN0aW9uIHRlc3RPcHRpb25zICggb3B0aW9ucyApIHtcblxuXHRcdC8vIFRvIHByb3ZlIGEgZml4IGZvciAjNTM3LCBmcmVlemUgb3B0aW9ucyBoZXJlLlxuXHRcdC8vIElmIHRoZSBvYmplY3QgaXMgbW9kaWZpZWQsIGFuIGVycm9yIHdpbGwgYmUgdGhyb3duLlxuXHRcdC8vIE9iamVjdC5mcmVlemUob3B0aW9ucyk7XG5cblx0XHR2YXIgcGFyc2VkID0ge1xuXHRcdFx0bWFyZ2luOiAwLFxuXHRcdFx0bGltaXQ6IDAsXG5cdFx0XHRwYWRkaW5nOiAwLFxuXHRcdFx0YW5pbWF0ZTogdHJ1ZSxcblx0XHRcdGFuaW1hdGlvbkR1cmF0aW9uOiAzMDAsXG5cdFx0XHRhcmlhRm9ybWF0OiBkZWZhdWx0Rm9ybWF0dGVyLFxuXHRcdFx0Zm9ybWF0OiBkZWZhdWx0Rm9ybWF0dGVyXG5cdFx0fTtcblxuXHRcdC8vIFRlc3RzIGFyZSBleGVjdXRlZCBpbiB0aGUgb3JkZXIgdGhleSBhcmUgcHJlc2VudGVkIGhlcmUuXG5cdFx0dmFyIHRlc3RzID0ge1xuXHRcdFx0J3N0ZXAnOiB7IHI6IGZhbHNlLCB0OiB0ZXN0U3RlcCB9LFxuXHRcdFx0J3N0YXJ0JzogeyByOiB0cnVlLCB0OiB0ZXN0U3RhcnQgfSxcblx0XHRcdCdjb25uZWN0JzogeyByOiB0cnVlLCB0OiB0ZXN0Q29ubmVjdCB9LFxuXHRcdFx0J2RpcmVjdGlvbic6IHsgcjogdHJ1ZSwgdDogdGVzdERpcmVjdGlvbiB9LFxuXHRcdFx0J3NuYXAnOiB7IHI6IGZhbHNlLCB0OiB0ZXN0U25hcCB9LFxuXHRcdFx0J2FuaW1hdGUnOiB7IHI6IGZhbHNlLCB0OiB0ZXN0QW5pbWF0ZSB9LFxuXHRcdFx0J2FuaW1hdGlvbkR1cmF0aW9uJzogeyByOiBmYWxzZSwgdDogdGVzdEFuaW1hdGlvbkR1cmF0aW9uIH0sXG5cdFx0XHQncmFuZ2UnOiB7IHI6IHRydWUsIHQ6IHRlc3RSYW5nZSB9LFxuXHRcdFx0J29yaWVudGF0aW9uJzogeyByOiBmYWxzZSwgdDogdGVzdE9yaWVudGF0aW9uIH0sXG5cdFx0XHQnbWFyZ2luJzogeyByOiBmYWxzZSwgdDogdGVzdE1hcmdpbiB9LFxuXHRcdFx0J2xpbWl0JzogeyByOiBmYWxzZSwgdDogdGVzdExpbWl0IH0sXG5cdFx0XHQncGFkZGluZyc6IHsgcjogZmFsc2UsIHQ6IHRlc3RQYWRkaW5nIH0sXG5cdFx0XHQnYmVoYXZpb3VyJzogeyByOiB0cnVlLCB0OiB0ZXN0QmVoYXZpb3VyIH0sXG5cdFx0XHQnYXJpYUZvcm1hdCc6IHsgcjogZmFsc2UsIHQ6IHRlc3RBcmlhRm9ybWF0IH0sXG5cdFx0XHQnZm9ybWF0JzogeyByOiBmYWxzZSwgdDogdGVzdEZvcm1hdCB9LFxuXHRcdFx0J3Rvb2x0aXBzJzogeyByOiBmYWxzZSwgdDogdGVzdFRvb2x0aXBzIH0sXG5cdFx0XHQnY3NzUHJlZml4JzogeyByOiB0cnVlLCB0OiB0ZXN0Q3NzUHJlZml4IH0sXG5cdFx0XHQnY3NzQ2xhc3Nlcyc6IHsgcjogdHJ1ZSwgdDogdGVzdENzc0NsYXNzZXMgfVxuXHRcdH07XG5cblx0XHR2YXIgZGVmYXVsdHMgPSB7XG5cdFx0XHQnY29ubmVjdCc6IGZhbHNlLFxuXHRcdFx0J2RpcmVjdGlvbic6ICdsdHInLFxuXHRcdFx0J2JlaGF2aW91cic6ICd0YXAnLFxuXHRcdFx0J29yaWVudGF0aW9uJzogJ2hvcml6b250YWwnLFxuXHRcdFx0J2Nzc1ByZWZpeCcgOiAnbm9VaS0nLFxuXHRcdFx0J2Nzc0NsYXNzZXMnOiB7XG5cdFx0XHRcdHRhcmdldDogJ3RhcmdldCcsXG5cdFx0XHRcdGJhc2U6ICdiYXNlJyxcblx0XHRcdFx0b3JpZ2luOiAnb3JpZ2luJyxcblx0XHRcdFx0aGFuZGxlOiAnaGFuZGxlJyxcblx0XHRcdFx0aGFuZGxlTG93ZXI6ICdoYW5kbGUtbG93ZXInLFxuXHRcdFx0XHRoYW5kbGVVcHBlcjogJ2hhbmRsZS11cHBlcicsXG5cdFx0XHRcdGhvcml6b250YWw6ICdob3Jpem9udGFsJyxcblx0XHRcdFx0dmVydGljYWw6ICd2ZXJ0aWNhbCcsXG5cdFx0XHRcdGJhY2tncm91bmQ6ICdiYWNrZ3JvdW5kJyxcblx0XHRcdFx0Y29ubmVjdDogJ2Nvbm5lY3QnLFxuXHRcdFx0XHRjb25uZWN0czogJ2Nvbm5lY3RzJyxcblx0XHRcdFx0bHRyOiAnbHRyJyxcblx0XHRcdFx0cnRsOiAncnRsJyxcblx0XHRcdFx0ZHJhZ2dhYmxlOiAnZHJhZ2dhYmxlJyxcblx0XHRcdFx0ZHJhZzogJ3N0YXRlLWRyYWcnLFxuXHRcdFx0XHR0YXA6ICdzdGF0ZS10YXAnLFxuXHRcdFx0XHRhY3RpdmU6ICdhY3RpdmUnLFxuXHRcdFx0XHR0b29sdGlwOiAndG9vbHRpcCcsXG5cdFx0XHRcdHBpcHM6ICdwaXBzJyxcblx0XHRcdFx0cGlwc0hvcml6b250YWw6ICdwaXBzLWhvcml6b250YWwnLFxuXHRcdFx0XHRwaXBzVmVydGljYWw6ICdwaXBzLXZlcnRpY2FsJyxcblx0XHRcdFx0bWFya2VyOiAnbWFya2VyJyxcblx0XHRcdFx0bWFya2VySG9yaXpvbnRhbDogJ21hcmtlci1ob3Jpem9udGFsJyxcblx0XHRcdFx0bWFya2VyVmVydGljYWw6ICdtYXJrZXItdmVydGljYWwnLFxuXHRcdFx0XHRtYXJrZXJOb3JtYWw6ICdtYXJrZXItbm9ybWFsJyxcblx0XHRcdFx0bWFya2VyTGFyZ2U6ICdtYXJrZXItbGFyZ2UnLFxuXHRcdFx0XHRtYXJrZXJTdWI6ICdtYXJrZXItc3ViJyxcblx0XHRcdFx0dmFsdWU6ICd2YWx1ZScsXG5cdFx0XHRcdHZhbHVlSG9yaXpvbnRhbDogJ3ZhbHVlLWhvcml6b250YWwnLFxuXHRcdFx0XHR2YWx1ZVZlcnRpY2FsOiAndmFsdWUtdmVydGljYWwnLFxuXHRcdFx0XHR2YWx1ZU5vcm1hbDogJ3ZhbHVlLW5vcm1hbCcsXG5cdFx0XHRcdHZhbHVlTGFyZ2U6ICd2YWx1ZS1sYXJnZScsXG5cdFx0XHRcdHZhbHVlU3ViOiAndmFsdWUtc3ViJ1xuXHRcdFx0fVxuXHRcdH07XG5cblx0XHQvLyBBcmlhRm9ybWF0IGRlZmF1bHRzIHRvIHJlZ3VsYXIgZm9ybWF0LCBpZiBhbnkuXG5cdFx0aWYgKCBvcHRpb25zLmZvcm1hdCAmJiAhb3B0aW9ucy5hcmlhRm9ybWF0ICkge1xuXHRcdFx0b3B0aW9ucy5hcmlhRm9ybWF0ID0gb3B0aW9ucy5mb3JtYXQ7XG5cdFx0fVxuXG5cdFx0Ly8gUnVuIGFsbCBvcHRpb25zIHRocm91Z2ggYSB0ZXN0aW5nIG1lY2hhbmlzbSB0byBlbnN1cmUgY29ycmVjdFxuXHRcdC8vIGlucHV0LiBJdCBzaG91bGQgYmUgbm90ZWQgdGhhdCBvcHRpb25zIG1pZ2h0IGdldCBtb2RpZmllZCB0b1xuXHRcdC8vIGJlIGhhbmRsZWQgcHJvcGVybHkuIEUuZy4gd3JhcHBpbmcgaW50ZWdlcnMgaW4gYXJyYXlzLlxuXHRcdE9iamVjdC5rZXlzKHRlc3RzKS5mb3JFYWNoKGZ1bmN0aW9uKCBuYW1lICl7XG5cblx0XHRcdC8vIElmIHRoZSBvcHRpb24gaXNuJ3Qgc2V0LCBidXQgaXQgaXMgcmVxdWlyZWQsIHRocm93IGFuIGVycm9yLlxuXHRcdFx0aWYgKCAhaXNTZXQob3B0aW9uc1tuYW1lXSkgJiYgZGVmYXVsdHNbbmFtZV0gPT09IHVuZGVmaW5lZCApIHtcblxuXHRcdFx0XHRpZiAoIHRlc3RzW25hbWVdLnIgKSB7XG5cdFx0XHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAnXCIgKyBuYW1lICsgXCInIGlzIHJlcXVpcmVkLlwiKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHJldHVybiB0cnVlO1xuXHRcdFx0fVxuXG5cdFx0XHR0ZXN0c1tuYW1lXS50KCBwYXJzZWQsICFpc1NldChvcHRpb25zW25hbWVdKSA/IGRlZmF1bHRzW25hbWVdIDogb3B0aW9uc1tuYW1lXSApO1xuXHRcdH0pO1xuXG5cdFx0Ly8gRm9yd2FyZCBwaXBzIG9wdGlvbnNcblx0XHRwYXJzZWQucGlwcyA9IG9wdGlvbnMucGlwcztcblxuXHRcdC8vIEFsbCByZWNlbnQgYnJvd3NlcnMgYWNjZXB0IHVucHJlZml4ZWQgdHJhbnNmb3JtLlxuXHRcdC8vIFdlIG5lZWQgLW1zLSBmb3IgSUU5IGFuZCAtd2Via2l0LSBmb3Igb2xkZXIgQW5kcm9pZDtcblx0XHQvLyBBc3N1bWUgdXNlIG9mIC13ZWJraXQtIGlmIHVucHJlZml4ZWQgYW5kIC1tcy0gYXJlIG5vdCBzdXBwb3J0ZWQuXG5cdFx0Ly8gaHR0cHM6Ly9jYW5pdXNlLmNvbS8jZmVhdD10cmFuc2Zvcm1zMmRcblx0XHR2YXIgZCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIik7XG5cdFx0dmFyIG1zUHJlZml4ID0gZC5zdHlsZS5tc1RyYW5zZm9ybSAhPT0gdW5kZWZpbmVkO1xuXHRcdHZhciBub1ByZWZpeCA9IGQuc3R5bGUudHJhbnNmb3JtICE9PSB1bmRlZmluZWQ7XG5cblx0XHRwYXJzZWQudHJhbnNmb3JtUnVsZSA9IG5vUHJlZml4ID8gJ3RyYW5zZm9ybScgOiAobXNQcmVmaXggPyAnbXNUcmFuc2Zvcm0nIDogJ3dlYmtpdFRyYW5zZm9ybScpO1xuXG5cdFx0Ly8gUGlwcyBkb24ndCBtb3ZlLCBzbyB3ZSBjYW4gcGxhY2UgdGhlbSB1c2luZyBsZWZ0L3RvcC5cblx0XHR2YXIgc3R5bGVzID0gW1snbGVmdCcsICd0b3AnXSwgWydyaWdodCcsICdib3R0b20nXV07XG5cblx0XHRwYXJzZWQuc3R5bGUgPSBzdHlsZXNbcGFyc2VkLmRpcl1bcGFyc2VkLm9ydF07XG5cblx0XHRyZXR1cm4gcGFyc2VkO1xuXHR9XG5cclxuXHJcbmZ1bmN0aW9uIHNjb3BlICggdGFyZ2V0LCBvcHRpb25zLCBvcmlnaW5hbE9wdGlvbnMgKXtcclxuXHJcblx0dmFyIGFjdGlvbnMgPSBnZXRBY3Rpb25zKCk7XHJcblx0dmFyIHN1cHBvcnRzVG91Y2hBY3Rpb25Ob25lID0gZ2V0U3VwcG9ydHNUb3VjaEFjdGlvbk5vbmUoKTtcclxuXHR2YXIgc3VwcG9ydHNQYXNzaXZlID0gc3VwcG9ydHNUb3VjaEFjdGlvbk5vbmUgJiYgZ2V0U3VwcG9ydHNQYXNzaXZlKCk7XHJcblxyXG5cdC8vIEFsbCB2YXJpYWJsZXMgbG9jYWwgdG8gJ3Njb3BlJyBhcmUgcHJlZml4ZWQgd2l0aCAnc2NvcGVfJ1xyXG5cdHZhciBzY29wZV9UYXJnZXQgPSB0YXJnZXQ7XHJcblx0dmFyIHNjb3BlX0xvY2F0aW9ucyA9IFtdO1xyXG5cdHZhciBzY29wZV9CYXNlO1xyXG5cdHZhciBzY29wZV9IYW5kbGVzO1xyXG5cdHZhciBzY29wZV9IYW5kbGVOdW1iZXJzID0gW107XHJcblx0dmFyIHNjb3BlX0FjdGl2ZUhhbmRsZXNDb3VudCA9IDA7XHJcblx0dmFyIHNjb3BlX0Nvbm5lY3RzO1xyXG5cdHZhciBzY29wZV9TcGVjdHJ1bSA9IG9wdGlvbnMuc3BlY3RydW07XHJcblx0dmFyIHNjb3BlX1ZhbHVlcyA9IFtdO1xyXG5cdHZhciBzY29wZV9FdmVudHMgPSB7fTtcclxuXHR2YXIgc2NvcGVfU2VsZjtcclxuXHR2YXIgc2NvcGVfUGlwcztcclxuXHR2YXIgc2NvcGVfRG9jdW1lbnQgPSB0YXJnZXQub3duZXJEb2N1bWVudDtcclxuXHR2YXIgc2NvcGVfRG9jdW1lbnRFbGVtZW50ID0gc2NvcGVfRG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50O1xyXG5cdHZhciBzY29wZV9Cb2R5ID0gc2NvcGVfRG9jdW1lbnQuYm9keTtcclxuXHJcblxyXG5cdC8vIEZvciBob3Jpem9udGFsIHNsaWRlcnMgaW4gc3RhbmRhcmQgbHRyIGRvY3VtZW50cyxcclxuXHQvLyBtYWtlIC5ub1VpLW9yaWdpbiBvdmVyZmxvdyB0byB0aGUgbGVmdCBzbyB0aGUgZG9jdW1lbnQgZG9lc24ndCBzY3JvbGwuXHJcblx0dmFyIHNjb3BlX0Rpck9mZnNldCA9IChzY29wZV9Eb2N1bWVudC5kaXIgPT09ICdydGwnKSB8fCAob3B0aW9ucy5vcnQgPT09IDEpID8gMCA6IDEwMDtcclxuXHJcbi8qISBJbiB0aGlzIGZpbGU6IENvbnN0cnVjdGlvbiBvZiBET00gZWxlbWVudHM7ICovXHJcblxyXG5cdC8vIENyZWF0ZXMgYSBub2RlLCBhZGRzIGl0IHRvIHRhcmdldCwgcmV0dXJucyB0aGUgbmV3IG5vZGUuXHJcblx0ZnVuY3Rpb24gYWRkTm9kZVRvICggYWRkVGFyZ2V0LCBjbGFzc05hbWUgKSB7XHJcblxyXG5cdFx0dmFyIGRpdiA9IHNjb3BlX0RvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xyXG5cclxuXHRcdGlmICggY2xhc3NOYW1lICkge1xyXG5cdFx0XHRhZGRDbGFzcyhkaXYsIGNsYXNzTmFtZSk7XHJcblx0XHR9XHJcblxyXG5cdFx0YWRkVGFyZ2V0LmFwcGVuZENoaWxkKGRpdik7XHJcblxyXG5cdFx0cmV0dXJuIGRpdjtcclxuXHR9XHJcblxyXG5cdC8vIEFwcGVuZCBhIG9yaWdpbiB0byB0aGUgYmFzZVxyXG5cdGZ1bmN0aW9uIGFkZE9yaWdpbiAoIGJhc2UsIGhhbmRsZU51bWJlciApIHtcclxuXHJcblx0XHR2YXIgb3JpZ2luID0gYWRkTm9kZVRvKGJhc2UsIG9wdGlvbnMuY3NzQ2xhc3Nlcy5vcmlnaW4pO1xyXG5cdFx0dmFyIGhhbmRsZSA9IGFkZE5vZGVUbyhvcmlnaW4sIG9wdGlvbnMuY3NzQ2xhc3Nlcy5oYW5kbGUpO1xyXG5cclxuXHRcdGhhbmRsZS5zZXRBdHRyaWJ1dGUoJ2RhdGEtaGFuZGxlJywgaGFuZGxlTnVtYmVyKTtcclxuXHJcblx0XHQvLyBodHRwczovL2RldmVsb3Blci5tb3ppbGxhLm9yZy9lbi1VUy9kb2NzL1dlYi9IVE1ML0dsb2JhbF9hdHRyaWJ1dGVzL3RhYmluZGV4XHJcblx0XHQvLyAwID0gZm9jdXNhYmxlIGFuZCByZWFjaGFibGVcclxuXHRcdGhhbmRsZS5zZXRBdHRyaWJ1dGUoJ3RhYmluZGV4JywgJzAnKTtcclxuXHRcdGhhbmRsZS5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAnc2xpZGVyJyk7XHJcblx0XHRoYW5kbGUuc2V0QXR0cmlidXRlKCdhcmlhLW9yaWVudGF0aW9uJywgb3B0aW9ucy5vcnQgPyAndmVydGljYWwnIDogJ2hvcml6b250YWwnKTtcclxuXHJcblx0XHRpZiAoIGhhbmRsZU51bWJlciA9PT0gMCApIHtcclxuXHRcdFx0YWRkQ2xhc3MoaGFuZGxlLCBvcHRpb25zLmNzc0NsYXNzZXMuaGFuZGxlTG93ZXIpO1xyXG5cdFx0fVxyXG5cclxuXHRcdGVsc2UgaWYgKCBoYW5kbGVOdW1iZXIgPT09IG9wdGlvbnMuaGFuZGxlcyAtIDEgKSB7XHJcblx0XHRcdGFkZENsYXNzKGhhbmRsZSwgb3B0aW9ucy5jc3NDbGFzc2VzLmhhbmRsZVVwcGVyKTtcclxuXHRcdH1cclxuXHJcblx0XHRyZXR1cm4gb3JpZ2luO1xyXG5cdH1cclxuXHJcblx0Ly8gSW5zZXJ0IG5vZGVzIGZvciBjb25uZWN0IGVsZW1lbnRzXHJcblx0ZnVuY3Rpb24gYWRkQ29ubmVjdCAoIGJhc2UsIGFkZCApIHtcclxuXHJcblx0XHRpZiAoICFhZGQgKSB7XHJcblx0XHRcdHJldHVybiBmYWxzZTtcclxuXHRcdH1cclxuXHJcblx0XHRyZXR1cm4gYWRkTm9kZVRvKGJhc2UsIG9wdGlvbnMuY3NzQ2xhc3Nlcy5jb25uZWN0KTtcclxuXHR9XHJcblxyXG5cdC8vIEFkZCBoYW5kbGVzIHRvIHRoZSBzbGlkZXIgYmFzZS5cclxuXHRmdW5jdGlvbiBhZGRFbGVtZW50cyAoIGNvbm5lY3RPcHRpb25zLCBiYXNlICkge1xyXG5cclxuXHRcdHZhciBjb25uZWN0QmFzZSA9IGFkZE5vZGVUbyhiYXNlLCBvcHRpb25zLmNzc0NsYXNzZXMuY29ubmVjdHMpO1xyXG5cclxuXHRcdHNjb3BlX0hhbmRsZXMgPSBbXTtcclxuXHRcdHNjb3BlX0Nvbm5lY3RzID0gW107XHJcblxyXG5cdFx0c2NvcGVfQ29ubmVjdHMucHVzaChhZGRDb25uZWN0KGNvbm5lY3RCYXNlLCBjb25uZWN0T3B0aW9uc1swXSkpO1xyXG5cclxuXHRcdC8vIFs6Ojo6Tz09PT1PPT09PU89PT09XVxyXG5cdFx0Ly8gY29ubmVjdE9wdGlvbnMgPSBbMCwgMSwgMSwgMV1cclxuXHJcblx0XHRmb3IgKCB2YXIgaSA9IDA7IGkgPCBvcHRpb25zLmhhbmRsZXM7IGkrKyApIHtcclxuXHRcdFx0Ly8gS2VlcCBhIGxpc3Qgb2YgYWxsIGFkZGVkIGhhbmRsZXMuXHJcblx0XHRcdHNjb3BlX0hhbmRsZXMucHVzaChhZGRPcmlnaW4oYmFzZSwgaSkpO1xyXG5cdFx0XHRzY29wZV9IYW5kbGVOdW1iZXJzW2ldID0gaTtcclxuXHRcdFx0c2NvcGVfQ29ubmVjdHMucHVzaChhZGRDb25uZWN0KGNvbm5lY3RCYXNlLCBjb25uZWN0T3B0aW9uc1tpICsgMV0pKTtcclxuXHRcdH1cclxuXHR9XHJcblxyXG5cdC8vIEluaXRpYWxpemUgYSBzaW5nbGUgc2xpZGVyLlxyXG5cdGZ1bmN0aW9uIGFkZFNsaWRlciAoIGFkZFRhcmdldCApIHtcclxuXHJcblx0XHQvLyBBcHBseSBjbGFzc2VzIGFuZCBkYXRhIHRvIHRoZSB0YXJnZXQuXHJcblx0XHRhZGRDbGFzcyhhZGRUYXJnZXQsIG9wdGlvbnMuY3NzQ2xhc3Nlcy50YXJnZXQpO1xyXG5cclxuXHRcdGlmICggb3B0aW9ucy5kaXIgPT09IDAgKSB7XHJcblx0XHRcdGFkZENsYXNzKGFkZFRhcmdldCwgb3B0aW9ucy5jc3NDbGFzc2VzLmx0cik7XHJcblx0XHR9IGVsc2Uge1xyXG5cdFx0XHRhZGRDbGFzcyhhZGRUYXJnZXQsIG9wdGlvbnMuY3NzQ2xhc3Nlcy5ydGwpO1xyXG5cdFx0fVxyXG5cclxuXHRcdGlmICggb3B0aW9ucy5vcnQgPT09IDAgKSB7XHJcblx0XHRcdGFkZENsYXNzKGFkZFRhcmdldCwgb3B0aW9ucy5jc3NDbGFzc2VzLmhvcml6b250YWwpO1xyXG5cdFx0fSBlbHNlIHtcclxuXHRcdFx0YWRkQ2xhc3MoYWRkVGFyZ2V0LCBvcHRpb25zLmNzc0NsYXNzZXMudmVydGljYWwpO1xyXG5cdFx0fVxyXG5cclxuXHRcdHNjb3BlX0Jhc2UgPSBhZGROb2RlVG8oYWRkVGFyZ2V0LCBvcHRpb25zLmNzc0NsYXNzZXMuYmFzZSk7XHJcblx0fVxyXG5cclxuXHJcblx0ZnVuY3Rpb24gYWRkVG9vbHRpcCAoIGhhbmRsZSwgaGFuZGxlTnVtYmVyICkge1xyXG5cclxuXHRcdGlmICggIW9wdGlvbnMudG9vbHRpcHNbaGFuZGxlTnVtYmVyXSApIHtcclxuXHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0fVxyXG5cclxuXHRcdHJldHVybiBhZGROb2RlVG8oaGFuZGxlLmZpcnN0Q2hpbGQsIG9wdGlvbnMuY3NzQ2xhc3Nlcy50b29sdGlwKTtcclxuXHR9XHJcblxyXG5cdC8vIFRoZSB0b29sdGlwcyBvcHRpb24gaXMgYSBzaG9ydGhhbmQgZm9yIHVzaW5nIHRoZSAndXBkYXRlJyBldmVudC5cclxuXHRmdW5jdGlvbiB0b29sdGlwcyAoICkge1xyXG5cclxuXHRcdC8vIFRvb2x0aXBzIGFyZSBhZGRlZCB3aXRoIG9wdGlvbnMudG9vbHRpcHMgaW4gb3JpZ2luYWwgb3JkZXIuXHJcblx0XHR2YXIgdGlwcyA9IHNjb3BlX0hhbmRsZXMubWFwKGFkZFRvb2x0aXApO1xyXG5cclxuXHRcdGJpbmRFdmVudCgndXBkYXRlJywgZnVuY3Rpb24odmFsdWVzLCBoYW5kbGVOdW1iZXIsIHVuZW5jb2RlZCkge1xyXG5cclxuXHRcdFx0aWYgKCAhdGlwc1toYW5kbGVOdW1iZXJdICkge1xyXG5cdFx0XHRcdHJldHVybjtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0dmFyIGZvcm1hdHRlZFZhbHVlID0gdmFsdWVzW2hhbmRsZU51bWJlcl07XHJcblxyXG5cdFx0XHRpZiAoIG9wdGlvbnMudG9vbHRpcHNbaGFuZGxlTnVtYmVyXSAhPT0gdHJ1ZSApIHtcclxuXHRcdFx0XHRmb3JtYXR0ZWRWYWx1ZSA9IG9wdGlvbnMudG9vbHRpcHNbaGFuZGxlTnVtYmVyXS50byh1bmVuY29kZWRbaGFuZGxlTnVtYmVyXSk7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdHRpcHNbaGFuZGxlTnVtYmVyXS5pbm5lckhUTUwgPSBmb3JtYXR0ZWRWYWx1ZTtcclxuXHRcdH0pO1xyXG5cdH1cclxuXHJcblxyXG5cdGZ1bmN0aW9uIGFyaWEgKCApIHtcclxuXHJcblx0XHRiaW5kRXZlbnQoJ3VwZGF0ZScsIGZ1bmN0aW9uICggdmFsdWVzLCBoYW5kbGVOdW1iZXIsIHVuZW5jb2RlZCwgdGFwLCBwb3NpdGlvbnMgKSB7XHJcblxyXG5cdFx0XHQvLyBVcGRhdGUgQXJpYSBWYWx1ZXMgZm9yIGFsbCBoYW5kbGVzLCBhcyBhIGNoYW5nZSBpbiBvbmUgY2hhbmdlcyBtaW4gYW5kIG1heCB2YWx1ZXMgZm9yIHRoZSBuZXh0LlxyXG5cdFx0XHRzY29wZV9IYW5kbGVOdW1iZXJzLmZvckVhY2goZnVuY3Rpb24oIGluZGV4ICl7XHJcblxyXG5cdFx0XHRcdHZhciBoYW5kbGUgPSBzY29wZV9IYW5kbGVzW2luZGV4XTtcclxuXHJcblx0XHRcdFx0dmFyIG1pbiA9IGNoZWNrSGFuZGxlUG9zaXRpb24oc2NvcGVfTG9jYXRpb25zLCBpbmRleCwgMCwgdHJ1ZSwgdHJ1ZSwgdHJ1ZSk7XHJcblx0XHRcdFx0dmFyIG1heCA9IGNoZWNrSGFuZGxlUG9zaXRpb24oc2NvcGVfTG9jYXRpb25zLCBpbmRleCwgMTAwLCB0cnVlLCB0cnVlLCB0cnVlKTtcclxuXHJcblx0XHRcdFx0dmFyIG5vdyA9IHBvc2l0aW9uc1tpbmRleF07XHJcblx0XHRcdFx0dmFyIHRleHQgPSBvcHRpb25zLmFyaWFGb3JtYXQudG8odW5lbmNvZGVkW2luZGV4XSk7XHJcblxyXG5cdFx0XHRcdGhhbmRsZS5jaGlsZHJlblswXS5zZXRBdHRyaWJ1dGUoJ2FyaWEtdmFsdWVtaW4nLCBtaW4udG9GaXhlZCgxKSk7XHJcblx0XHRcdFx0aGFuZGxlLmNoaWxkcmVuWzBdLnNldEF0dHJpYnV0ZSgnYXJpYS12YWx1ZW1heCcsIG1heC50b0ZpeGVkKDEpKTtcclxuXHRcdFx0XHRoYW5kbGUuY2hpbGRyZW5bMF0uc2V0QXR0cmlidXRlKCdhcmlhLXZhbHVlbm93Jywgbm93LnRvRml4ZWQoMSkpO1xyXG5cdFx0XHRcdGhhbmRsZS5jaGlsZHJlblswXS5zZXRBdHRyaWJ1dGUoJ2FyaWEtdmFsdWV0ZXh0JywgdGV4dCk7XHJcblx0XHRcdH0pO1xyXG5cdFx0fSk7XHJcblx0fVxyXG5cclxuXHJcblx0ZnVuY3Rpb24gZ2V0R3JvdXAgKCBtb2RlLCB2YWx1ZXMsIHN0ZXBwZWQgKSB7XHJcblxyXG5cdFx0Ly8gVXNlIHRoZSByYW5nZS5cclxuXHRcdGlmICggbW9kZSA9PT0gJ3JhbmdlJyB8fCBtb2RlID09PSAnc3RlcHMnICkge1xyXG5cdFx0XHRyZXR1cm4gc2NvcGVfU3BlY3RydW0ueFZhbDtcclxuXHRcdH1cclxuXHJcblx0XHRpZiAoIG1vZGUgPT09ICdjb3VudCcgKSB7XHJcblxyXG5cdFx0XHRpZiAoIHZhbHVlcyA8IDIgKSB7XHJcblx0XHRcdFx0dGhyb3cgbmV3IEVycm9yKFwibm9VaVNsaWRlciAoXCIgKyBWRVJTSU9OICsgXCIpOiAndmFsdWVzJyAoPj0gMikgcmVxdWlyZWQgZm9yIG1vZGUgJ2NvdW50Jy5cIik7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdC8vIERpdmlkZSAwIC0gMTAwIGluICdjb3VudCcgcGFydHMuXHJcblx0XHRcdHZhciBpbnRlcnZhbCA9IHZhbHVlcyAtIDE7XHJcblx0XHRcdHZhciBzcHJlYWQgPSAoIDEwMCAvIGludGVydmFsICk7XHJcblxyXG5cdFx0XHR2YWx1ZXMgPSBbXTtcclxuXHJcblx0XHRcdC8vIExpc3QgdGhlc2UgcGFydHMgYW5kIGhhdmUgdGhlbSBoYW5kbGVkIGFzICdwb3NpdGlvbnMnLlxyXG5cdFx0XHR3aGlsZSAoIGludGVydmFsLS0gKSB7XHJcblx0XHRcdFx0dmFsdWVzWyBpbnRlcnZhbCBdID0gKCBpbnRlcnZhbCAqIHNwcmVhZCApO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHR2YWx1ZXMucHVzaCgxMDApO1xyXG5cclxuXHRcdFx0bW9kZSA9ICdwb3NpdGlvbnMnO1xyXG5cdFx0fVxyXG5cclxuXHRcdGlmICggbW9kZSA9PT0gJ3Bvc2l0aW9ucycgKSB7XHJcblxyXG5cdFx0XHQvLyBNYXAgYWxsIHBlcmNlbnRhZ2VzIHRvIG9uLXJhbmdlIHZhbHVlcy5cclxuXHRcdFx0cmV0dXJuIHZhbHVlcy5tYXAoZnVuY3Rpb24oIHZhbHVlICl7XHJcblx0XHRcdFx0cmV0dXJuIHNjb3BlX1NwZWN0cnVtLmZyb21TdGVwcGluZyggc3RlcHBlZCA/IHNjb3BlX1NwZWN0cnVtLmdldFN0ZXAoIHZhbHVlICkgOiB2YWx1ZSApO1xyXG5cdFx0XHR9KTtcclxuXHRcdH1cclxuXHJcblx0XHRpZiAoIG1vZGUgPT09ICd2YWx1ZXMnICkge1xyXG5cclxuXHRcdFx0Ly8gSWYgdGhlIHZhbHVlIG11c3QgYmUgc3RlcHBlZCwgaXQgbmVlZHMgdG8gYmUgY29udmVydGVkIHRvIGEgcGVyY2VudGFnZSBmaXJzdC5cclxuXHRcdFx0aWYgKCBzdGVwcGVkICkge1xyXG5cclxuXHRcdFx0XHRyZXR1cm4gdmFsdWVzLm1hcChmdW5jdGlvbiggdmFsdWUgKXtcclxuXHJcblx0XHRcdFx0XHQvLyBDb252ZXJ0IHRvIHBlcmNlbnRhZ2UsIGFwcGx5IHN0ZXAsIHJldHVybiB0byB2YWx1ZS5cclxuXHRcdFx0XHRcdHJldHVybiBzY29wZV9TcGVjdHJ1bS5mcm9tU3RlcHBpbmcoIHNjb3BlX1NwZWN0cnVtLmdldFN0ZXAoIHNjb3BlX1NwZWN0cnVtLnRvU3RlcHBpbmcoIHZhbHVlICkgKSApO1xyXG5cdFx0XHRcdH0pO1xyXG5cclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0Ly8gT3RoZXJ3aXNlLCB3ZSBjYW4gc2ltcGx5IHVzZSB0aGUgdmFsdWVzLlxyXG5cdFx0XHRyZXR1cm4gdmFsdWVzO1xyXG5cdFx0fVxyXG5cdH1cclxuXHJcblx0ZnVuY3Rpb24gZ2VuZXJhdGVTcHJlYWQgKCBkZW5zaXR5LCBtb2RlLCBncm91cCApIHtcclxuXHJcblx0XHRmdW5jdGlvbiBzYWZlSW5jcmVtZW50KHZhbHVlLCBpbmNyZW1lbnQpIHtcclxuXHRcdFx0Ly8gQXZvaWQgZmxvYXRpbmcgcG9pbnQgdmFyaWFuY2UgYnkgZHJvcHBpbmcgdGhlIHNtYWxsZXN0IGRlY2ltYWwgcGxhY2VzLlxyXG5cdFx0XHRyZXR1cm4gKHZhbHVlICsgaW5jcmVtZW50KS50b0ZpeGVkKDcpIC8gMTtcclxuXHRcdH1cclxuXHJcblx0XHR2YXIgaW5kZXhlcyA9IHt9O1xyXG5cdFx0dmFyIGZpcnN0SW5SYW5nZSA9IHNjb3BlX1NwZWN0cnVtLnhWYWxbMF07XHJcblx0XHR2YXIgbGFzdEluUmFuZ2UgPSBzY29wZV9TcGVjdHJ1bS54VmFsW3Njb3BlX1NwZWN0cnVtLnhWYWwubGVuZ3RoLTFdO1xyXG5cdFx0dmFyIGlnbm9yZUZpcnN0ID0gZmFsc2U7XHJcblx0XHR2YXIgaWdub3JlTGFzdCA9IGZhbHNlO1xyXG5cdFx0dmFyIHByZXZQY3QgPSAwO1xyXG5cclxuXHRcdC8vIENyZWF0ZSBhIGNvcHkgb2YgdGhlIGdyb3VwLCBzb3J0IGl0IGFuZCBmaWx0ZXIgYXdheSBhbGwgZHVwbGljYXRlcy5cclxuXHRcdGdyb3VwID0gdW5pcXVlKGdyb3VwLnNsaWNlKCkuc29ydChmdW5jdGlvbihhLCBiKXsgcmV0dXJuIGEgLSBiOyB9KSk7XHJcblxyXG5cdFx0Ly8gTWFrZSBzdXJlIHRoZSByYW5nZSBzdGFydHMgd2l0aCB0aGUgZmlyc3QgZWxlbWVudC5cclxuXHRcdGlmICggZ3JvdXBbMF0gIT09IGZpcnN0SW5SYW5nZSApIHtcclxuXHRcdFx0Z3JvdXAudW5zaGlmdChmaXJzdEluUmFuZ2UpO1xyXG5cdFx0XHRpZ25vcmVGaXJzdCA9IHRydWU7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gTGlrZXdpc2UgZm9yIHRoZSBsYXN0IG9uZS5cclxuXHRcdGlmICggZ3JvdXBbZ3JvdXAubGVuZ3RoIC0gMV0gIT09IGxhc3RJblJhbmdlICkge1xyXG5cdFx0XHRncm91cC5wdXNoKGxhc3RJblJhbmdlKTtcclxuXHRcdFx0aWdub3JlTGFzdCA9IHRydWU7XHJcblx0XHR9XHJcblxyXG5cdFx0Z3JvdXAuZm9yRWFjaChmdW5jdGlvbiAoIGN1cnJlbnQsIGluZGV4ICkge1xyXG5cclxuXHRcdFx0Ly8gR2V0IHRoZSBjdXJyZW50IHN0ZXAgYW5kIHRoZSBsb3dlciArIHVwcGVyIHBvc2l0aW9ucy5cclxuXHRcdFx0dmFyIHN0ZXA7XHJcblx0XHRcdHZhciBpO1xyXG5cdFx0XHR2YXIgcTtcclxuXHRcdFx0dmFyIGxvdyA9IGN1cnJlbnQ7XHJcblx0XHRcdHZhciBoaWdoID0gZ3JvdXBbaW5kZXgrMV07XHJcblx0XHRcdHZhciBuZXdQY3Q7XHJcblx0XHRcdHZhciBwY3REaWZmZXJlbmNlO1xyXG5cdFx0XHR2YXIgcGN0UG9zO1xyXG5cdFx0XHR2YXIgdHlwZTtcclxuXHRcdFx0dmFyIHN0ZXBzO1xyXG5cdFx0XHR2YXIgcmVhbFN0ZXBzO1xyXG5cdFx0XHR2YXIgc3RlcHNpemU7XHJcblxyXG5cdFx0XHQvLyBXaGVuIHVzaW5nICdzdGVwcycgbW9kZSwgdXNlIHRoZSBwcm92aWRlZCBzdGVwcy5cclxuXHRcdFx0Ly8gT3RoZXJ3aXNlLCB3ZSdsbCBzdGVwIG9uIHRvIHRoZSBuZXh0IHN1YnJhbmdlLlxyXG5cdFx0XHRpZiAoIG1vZGUgPT09ICdzdGVwcycgKSB7XHJcblx0XHRcdFx0c3RlcCA9IHNjb3BlX1NwZWN0cnVtLnhOdW1TdGVwc1sgaW5kZXggXTtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0Ly8gRGVmYXVsdCB0byBhICdmdWxsJyBzdGVwLlxyXG5cdFx0XHRpZiAoICFzdGVwICkge1xyXG5cdFx0XHRcdHN0ZXAgPSBoaWdoLWxvdztcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0Ly8gTG93IGNhbiBiZSAwLCBzbyB0ZXN0IGZvciBmYWxzZS4gSWYgaGlnaCBpcyB1bmRlZmluZWQsXHJcblx0XHRcdC8vIHdlIGFyZSBhdCB0aGUgbGFzdCBzdWJyYW5nZS4gSW5kZXggMCBpcyBhbHJlYWR5IGhhbmRsZWQuXHJcblx0XHRcdGlmICggbG93ID09PSBmYWxzZSB8fCBoaWdoID09PSB1bmRlZmluZWQgKSB7XHJcblx0XHRcdFx0cmV0dXJuO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHQvLyBNYWtlIHN1cmUgc3RlcCBpc24ndCAwLCB3aGljaCB3b3VsZCBjYXVzZSBhbiBpbmZpbml0ZSBsb29wICgjNjU0KVxyXG5cdFx0XHRzdGVwID0gTWF0aC5tYXgoc3RlcCwgMC4wMDAwMDAxKTtcclxuXHJcblx0XHRcdC8vIEZpbmQgYWxsIHN0ZXBzIGluIHRoZSBzdWJyYW5nZS5cclxuXHRcdFx0Zm9yICggaSA9IGxvdzsgaSA8PSBoaWdoOyBpID0gc2FmZUluY3JlbWVudChpLCBzdGVwKSApIHtcclxuXHJcblx0XHRcdFx0Ly8gR2V0IHRoZSBwZXJjZW50YWdlIHZhbHVlIGZvciB0aGUgY3VycmVudCBzdGVwLFxyXG5cdFx0XHRcdC8vIGNhbGN1bGF0ZSB0aGUgc2l6ZSBmb3IgdGhlIHN1YnJhbmdlLlxyXG5cdFx0XHRcdG5ld1BjdCA9IHNjb3BlX1NwZWN0cnVtLnRvU3RlcHBpbmcoIGkgKTtcclxuXHRcdFx0XHRwY3REaWZmZXJlbmNlID0gbmV3UGN0IC0gcHJldlBjdDtcclxuXHJcblx0XHRcdFx0c3RlcHMgPSBwY3REaWZmZXJlbmNlIC8gZGVuc2l0eTtcclxuXHRcdFx0XHRyZWFsU3RlcHMgPSBNYXRoLnJvdW5kKHN0ZXBzKTtcclxuXHJcblx0XHRcdFx0Ly8gVGhpcyByYXRpbyByZXByZXNlbnRzIHRoZSBhbW91bnQgb2YgcGVyY2VudGFnZS1zcGFjZSBhIHBvaW50IGluZGljYXRlcy5cclxuXHRcdFx0XHQvLyBGb3IgYSBkZW5zaXR5IDEgdGhlIHBvaW50cy9wZXJjZW50YWdlID0gMS4gRm9yIGRlbnNpdHkgMiwgdGhhdCBwZXJjZW50YWdlIG5lZWRzIHRvIGJlIHJlLWRldmlkZWQuXHJcblx0XHRcdFx0Ly8gUm91bmQgdGhlIHBlcmNlbnRhZ2Ugb2Zmc2V0IHRvIGFuIGV2ZW4gbnVtYmVyLCB0aGVuIGRpdmlkZSBieSB0d29cclxuXHRcdFx0XHQvLyB0byBzcHJlYWQgdGhlIG9mZnNldCBvbiBib3RoIHNpZGVzIG9mIHRoZSByYW5nZS5cclxuXHRcdFx0XHRzdGVwc2l6ZSA9IHBjdERpZmZlcmVuY2UvcmVhbFN0ZXBzO1xyXG5cclxuXHRcdFx0XHQvLyBEaXZpZGUgYWxsIHBvaW50cyBldmVubHksIGFkZGluZyB0aGUgY29ycmVjdCBudW1iZXIgdG8gdGhpcyBzdWJyYW5nZS5cclxuXHRcdFx0XHQvLyBSdW4gdXAgdG8gPD0gc28gdGhhdCAxMDAlIGdldHMgYSBwb2ludCwgZXZlbnQgaWYgaWdub3JlTGFzdCBpcyBzZXQuXHJcblx0XHRcdFx0Zm9yICggcSA9IDE7IHEgPD0gcmVhbFN0ZXBzOyBxICs9IDEgKSB7XHJcblxyXG5cdFx0XHRcdFx0Ly8gVGhlIHJhdGlvIGJldHdlZW4gdGhlIHJvdW5kZWQgdmFsdWUgYW5kIHRoZSBhY3R1YWwgc2l6ZSBtaWdodCBiZSB+MSUgb2ZmLlxyXG5cdFx0XHRcdFx0Ly8gQ29ycmVjdCB0aGUgcGVyY2VudGFnZSBvZmZzZXQgYnkgdGhlIG51bWJlciBvZiBwb2ludHNcclxuXHRcdFx0XHRcdC8vIHBlciBzdWJyYW5nZS4gZGVuc2l0eSA9IDEgd2lsbCByZXN1bHQgaW4gMTAwIHBvaW50cyBvbiB0aGVcclxuXHRcdFx0XHRcdC8vIGZ1bGwgcmFuZ2UsIDIgZm9yIDUwLCA0IGZvciAyNSwgZXRjLlxyXG5cdFx0XHRcdFx0cGN0UG9zID0gcHJldlBjdCArICggcSAqIHN0ZXBzaXplICk7XHJcblx0XHRcdFx0XHRpbmRleGVzW3BjdFBvcy50b0ZpeGVkKDUpXSA9IFsneCcsIDBdO1xyXG5cdFx0XHRcdH1cclxuXHJcblx0XHRcdFx0Ly8gRGV0ZXJtaW5lIHRoZSBwb2ludCB0eXBlLlxyXG5cdFx0XHRcdHR5cGUgPSAoZ3JvdXAuaW5kZXhPZihpKSA+IC0xKSA/IDEgOiAoIG1vZGUgPT09ICdzdGVwcycgPyAyIDogMCApO1xyXG5cclxuXHRcdFx0XHQvLyBFbmZvcmNlIHRoZSAnaWdub3JlRmlyc3QnIG9wdGlvbiBieSBvdmVyd3JpdGluZyB0aGUgdHlwZSBmb3IgMC5cclxuXHRcdFx0XHRpZiAoICFpbmRleCAmJiBpZ25vcmVGaXJzdCApIHtcclxuXHRcdFx0XHRcdHR5cGUgPSAwO1xyXG5cdFx0XHRcdH1cclxuXHJcblx0XHRcdFx0aWYgKCAhKGkgPT09IGhpZ2ggJiYgaWdub3JlTGFzdCkpIHtcclxuXHRcdFx0XHRcdC8vIE1hcmsgdGhlICd0eXBlJyBvZiB0aGlzIHBvaW50LiAwID0gcGxhaW4sIDEgPSByZWFsIHZhbHVlLCAyID0gc3RlcCB2YWx1ZS5cclxuXHRcdFx0XHRcdGluZGV4ZXNbbmV3UGN0LnRvRml4ZWQoNSldID0gW2ksIHR5cGVdO1xyXG5cdFx0XHRcdH1cclxuXHJcblx0XHRcdFx0Ly8gVXBkYXRlIHRoZSBwZXJjZW50YWdlIGNvdW50LlxyXG5cdFx0XHRcdHByZXZQY3QgPSBuZXdQY3Q7XHJcblx0XHRcdH1cclxuXHRcdH0pO1xyXG5cclxuXHRcdHJldHVybiBpbmRleGVzO1xyXG5cdH1cclxuXHJcblx0ZnVuY3Rpb24gYWRkTWFya2luZyAoIHNwcmVhZCwgZmlsdGVyRnVuYywgZm9ybWF0dGVyICkge1xyXG5cclxuXHRcdHZhciBlbGVtZW50ID0gc2NvcGVfRG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XHJcblxyXG5cdFx0dmFyIHZhbHVlU2l6ZUNsYXNzZXMgPSBbXHJcblx0XHRcdG9wdGlvbnMuY3NzQ2xhc3Nlcy52YWx1ZU5vcm1hbCxcclxuXHRcdFx0b3B0aW9ucy5jc3NDbGFzc2VzLnZhbHVlTGFyZ2UsXHJcblx0XHRcdG9wdGlvbnMuY3NzQ2xhc3Nlcy52YWx1ZVN1YlxyXG5cdFx0XTtcclxuXHRcdHZhciBtYXJrZXJTaXplQ2xhc3NlcyA9IFtcclxuXHRcdFx0b3B0aW9ucy5jc3NDbGFzc2VzLm1hcmtlck5vcm1hbCxcclxuXHRcdFx0b3B0aW9ucy5jc3NDbGFzc2VzLm1hcmtlckxhcmdlLFxyXG5cdFx0XHRvcHRpb25zLmNzc0NsYXNzZXMubWFya2VyU3ViXHJcblx0XHRdO1xyXG5cdFx0dmFyIHZhbHVlT3JpZW50YXRpb25DbGFzc2VzID0gW1xyXG5cdFx0XHRvcHRpb25zLmNzc0NsYXNzZXMudmFsdWVIb3Jpem9udGFsLFxyXG5cdFx0XHRvcHRpb25zLmNzc0NsYXNzZXMudmFsdWVWZXJ0aWNhbFxyXG5cdFx0XTtcclxuXHRcdHZhciBtYXJrZXJPcmllbnRhdGlvbkNsYXNzZXMgPSBbXHJcblx0XHRcdG9wdGlvbnMuY3NzQ2xhc3Nlcy5tYXJrZXJIb3Jpem9udGFsLFxyXG5cdFx0XHRvcHRpb25zLmNzc0NsYXNzZXMubWFya2VyVmVydGljYWxcclxuXHRcdF07XHJcblxyXG5cdFx0YWRkQ2xhc3MoZWxlbWVudCwgb3B0aW9ucy5jc3NDbGFzc2VzLnBpcHMpO1xyXG5cdFx0YWRkQ2xhc3MoZWxlbWVudCwgb3B0aW9ucy5vcnQgPT09IDAgPyBvcHRpb25zLmNzc0NsYXNzZXMucGlwc0hvcml6b250YWwgOiBvcHRpb25zLmNzc0NsYXNzZXMucGlwc1ZlcnRpY2FsKTtcclxuXHJcblx0XHRmdW5jdGlvbiBnZXRDbGFzc2VzKCB0eXBlLCBzb3VyY2UgKXtcclxuXHRcdFx0dmFyIGEgPSBzb3VyY2UgPT09IG9wdGlvbnMuY3NzQ2xhc3Nlcy52YWx1ZTtcclxuXHRcdFx0dmFyIG9yaWVudGF0aW9uQ2xhc3NlcyA9IGEgPyB2YWx1ZU9yaWVudGF0aW9uQ2xhc3NlcyA6IG1hcmtlck9yaWVudGF0aW9uQ2xhc3NlcztcclxuXHRcdFx0dmFyIHNpemVDbGFzc2VzID0gYSA/IHZhbHVlU2l6ZUNsYXNzZXMgOiBtYXJrZXJTaXplQ2xhc3NlcztcclxuXHJcblx0XHRcdHJldHVybiBzb3VyY2UgKyAnICcgKyBvcmllbnRhdGlvbkNsYXNzZXNbb3B0aW9ucy5vcnRdICsgJyAnICsgc2l6ZUNsYXNzZXNbdHlwZV07XHJcblx0XHR9XHJcblxyXG5cdFx0ZnVuY3Rpb24gYWRkU3ByZWFkICggb2Zmc2V0LCB2YWx1ZXMgKXtcclxuXHJcblx0XHRcdC8vIEFwcGx5IHRoZSBmaWx0ZXIgZnVuY3Rpb24sIGlmIGl0IGlzIHNldC5cclxuXHRcdFx0dmFsdWVzWzFdID0gKHZhbHVlc1sxXSAmJiBmaWx0ZXJGdW5jKSA/IGZpbHRlckZ1bmModmFsdWVzWzBdLCB2YWx1ZXNbMV0pIDogdmFsdWVzWzFdO1xyXG5cclxuXHRcdFx0Ly8gQWRkIGEgbWFya2VyIGZvciBldmVyeSBwb2ludFxyXG5cdFx0XHR2YXIgbm9kZSA9IGFkZE5vZGVUbyhlbGVtZW50LCBmYWxzZSk7XHJcblx0XHRcdFx0bm9kZS5jbGFzc05hbWUgPSBnZXRDbGFzc2VzKHZhbHVlc1sxXSwgb3B0aW9ucy5jc3NDbGFzc2VzLm1hcmtlcik7XHJcblx0XHRcdFx0bm9kZS5zdHlsZVtvcHRpb25zLnN0eWxlXSA9IG9mZnNldCArICclJztcclxuXHJcblx0XHRcdC8vIFZhbHVlcyBhcmUgb25seSBhcHBlbmRlZCBmb3IgcG9pbnRzIG1hcmtlZCAnMScgb3IgJzInLlxyXG5cdFx0XHRpZiAoIHZhbHVlc1sxXSApIHtcclxuXHRcdFx0XHRub2RlID0gYWRkTm9kZVRvKGVsZW1lbnQsIGZhbHNlKTtcclxuXHRcdFx0XHRub2RlLmNsYXNzTmFtZSA9IGdldENsYXNzZXModmFsdWVzWzFdLCBvcHRpb25zLmNzc0NsYXNzZXMudmFsdWUpO1xyXG5cdFx0XHRcdG5vZGUuc2V0QXR0cmlidXRlKCdkYXRhLXZhbHVlJywgdmFsdWVzWzBdKTtcclxuXHRcdFx0XHRub2RlLnN0eWxlW29wdGlvbnMuc3R5bGVdID0gb2Zmc2V0ICsgJyUnO1xyXG5cdFx0XHRcdG5vZGUuaW5uZXJUZXh0ID0gZm9ybWF0dGVyLnRvKHZhbHVlc1swXSk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHQvLyBBcHBlbmQgYWxsIHBvaW50cy5cclxuXHRcdE9iamVjdC5rZXlzKHNwcmVhZCkuZm9yRWFjaChmdW5jdGlvbihhKXtcclxuXHRcdFx0YWRkU3ByZWFkKGEsIHNwcmVhZFthXSk7XHJcblx0XHR9KTtcclxuXHJcblx0XHRyZXR1cm4gZWxlbWVudDtcclxuXHR9XHJcblxyXG5cdGZ1bmN0aW9uIHJlbW92ZVBpcHMgKCApIHtcclxuXHRcdGlmICggc2NvcGVfUGlwcyApIHtcclxuXHRcdFx0cmVtb3ZlRWxlbWVudChzY29wZV9QaXBzKTtcclxuXHRcdFx0c2NvcGVfUGlwcyA9IG51bGw7XHJcblx0XHR9XHJcblx0fVxyXG5cclxuXHRmdW5jdGlvbiBwaXBzICggZ3JpZCApIHtcclxuXHJcblx0XHQvLyBGaXggIzY2OVxyXG5cdFx0cmVtb3ZlUGlwcygpO1xyXG5cclxuXHRcdHZhciBtb2RlID0gZ3JpZC5tb2RlO1xyXG5cdFx0dmFyIGRlbnNpdHkgPSBncmlkLmRlbnNpdHkgfHwgMTtcclxuXHRcdHZhciBmaWx0ZXIgPSBncmlkLmZpbHRlciB8fCBmYWxzZTtcclxuXHRcdHZhciB2YWx1ZXMgPSBncmlkLnZhbHVlcyB8fCBmYWxzZTtcclxuXHRcdHZhciBzdGVwcGVkID0gZ3JpZC5zdGVwcGVkIHx8IGZhbHNlO1xyXG5cdFx0dmFyIGdyb3VwID0gZ2V0R3JvdXAoIG1vZGUsIHZhbHVlcywgc3RlcHBlZCApO1xyXG5cdFx0dmFyIHNwcmVhZCA9IGdlbmVyYXRlU3ByZWFkKCBkZW5zaXR5LCBtb2RlLCBncm91cCApO1xyXG5cdFx0dmFyIGZvcm1hdCA9IGdyaWQuZm9ybWF0IHx8IHtcclxuXHRcdFx0dG86IE1hdGgucm91bmRcclxuXHRcdH07XHJcblxyXG5cdFx0c2NvcGVfUGlwcyA9IHNjb3BlX1RhcmdldC5hcHBlbmRDaGlsZChhZGRNYXJraW5nKFxyXG5cdFx0XHRzcHJlYWQsXHJcblx0XHRcdGZpbHRlcixcclxuXHRcdFx0Zm9ybWF0XHJcblx0XHQpKTtcclxuXHJcblx0XHRyZXR1cm4gc2NvcGVfUGlwcztcclxuXHR9XHJcblxyXG4vKiEgSW4gdGhpcyBmaWxlOiBCcm93c2VyIGV2ZW50cyAobm90IHNsaWRlciBldmVudHMgbGlrZSBzbGlkZSwgY2hhbmdlKTsgKi9cclxuXHJcblx0Ly8gU2hvcnRoYW5kIGZvciBiYXNlIGRpbWVuc2lvbnMuXHJcblx0ZnVuY3Rpb24gYmFzZVNpemUgKCApIHtcclxuXHRcdHZhciByZWN0ID0gc2NvcGVfQmFzZS5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcclxuXHRcdHZhciBhbHQgPSAnb2Zmc2V0JyArIFsnV2lkdGgnLCAnSGVpZ2h0J11bb3B0aW9ucy5vcnRdO1xyXG5cdFx0cmV0dXJuIG9wdGlvbnMub3J0ID09PSAwID8gKHJlY3Qud2lkdGh8fHNjb3BlX0Jhc2VbYWx0XSkgOiAocmVjdC5oZWlnaHR8fHNjb3BlX0Jhc2VbYWx0XSk7XHJcblx0fVxyXG5cclxuXHQvLyBIYW5kbGVyIGZvciBhdHRhY2hpbmcgZXZlbnRzIHRyb3VnaCBhIHByb3h5LlxyXG5cdGZ1bmN0aW9uIGF0dGFjaEV2ZW50ICggZXZlbnRzLCBlbGVtZW50LCBjYWxsYmFjaywgZGF0YSApIHtcclxuXHJcblx0XHQvLyBUaGlzIGZ1bmN0aW9uIGNhbiBiZSB1c2VkIHRvICdmaWx0ZXInIGV2ZW50cyB0byB0aGUgc2xpZGVyLlxyXG5cdFx0Ly8gZWxlbWVudCBpcyBhIG5vZGUsIG5vdCBhIG5vZGVMaXN0XHJcblxyXG5cdFx0dmFyIG1ldGhvZCA9IGZ1bmN0aW9uICggZSApe1xyXG5cclxuXHRcdFx0ZSA9IGZpeEV2ZW50KGUsIGRhdGEucGFnZU9mZnNldCwgZGF0YS50YXJnZXQgfHwgZWxlbWVudCk7XHJcblxyXG5cdFx0XHQvLyBmaXhFdmVudCByZXR1cm5zIGZhbHNlIGlmIHRoaXMgZXZlbnQgaGFzIGEgZGlmZmVyZW50IHRhcmdldFxyXG5cdFx0XHQvLyB3aGVuIGhhbmRsaW5nIChtdWx0aS0pIHRvdWNoIGV2ZW50cztcclxuXHRcdFx0aWYgKCAhZSApIHtcclxuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdC8vIGRvTm90UmVqZWN0IGlzIHBhc3NlZCBieSBhbGwgZW5kIGV2ZW50cyB0byBtYWtlIHN1cmUgcmVsZWFzZWQgdG91Y2hlc1xyXG5cdFx0XHQvLyBhcmUgbm90IHJlamVjdGVkLCBsZWF2aW5nIHRoZSBzbGlkZXIgXCJzdHVja1wiIHRvIHRoZSBjdXJzb3I7XHJcblx0XHRcdGlmICggc2NvcGVfVGFyZ2V0Lmhhc0F0dHJpYnV0ZSgnZGlzYWJsZWQnKSAmJiAhZGF0YS5kb05vdFJlamVjdCApIHtcclxuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdC8vIFN0b3AgaWYgYW4gYWN0aXZlICd0YXAnIHRyYW5zaXRpb24gaXMgdGFraW5nIHBsYWNlLlxyXG5cdFx0XHRpZiAoIGhhc0NsYXNzKHNjb3BlX1RhcmdldCwgb3B0aW9ucy5jc3NDbGFzc2VzLnRhcCkgJiYgIWRhdGEuZG9Ob3RSZWplY3QgKSB7XHJcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHQvLyBJZ25vcmUgcmlnaHQgb3IgbWlkZGxlIGNsaWNrcyBvbiBzdGFydCAjNDU0XHJcblx0XHRcdGlmICggZXZlbnRzID09PSBhY3Rpb25zLnN0YXJ0ICYmIGUuYnV0dG9ucyAhPT0gdW5kZWZpbmVkICYmIGUuYnV0dG9ucyA+IDEgKSB7XHJcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHQvLyBJZ25vcmUgcmlnaHQgb3IgbWlkZGxlIGNsaWNrcyBvbiBzdGFydCAjNDU0XHJcblx0XHRcdGlmICggZGF0YS5ob3ZlciAmJiBlLmJ1dHRvbnMgKSB7XHJcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHQvLyAnc3VwcG9ydHNQYXNzaXZlJyBpcyBvbmx5IHRydWUgaWYgYSBicm93c2VyIGFsc28gc3VwcG9ydHMgdG91Y2gtYWN0aW9uOiBub25lIGluIENTUy5cclxuXHRcdFx0Ly8gaU9TIHNhZmFyaSBkb2VzIG5vdCwgc28gaXQgZG9lc24ndCBnZXQgdG8gYmVuZWZpdCBmcm9tIHBhc3NpdmUgc2Nyb2xsaW5nLiBpT1MgZG9lcyBzdXBwb3J0XHJcblx0XHRcdC8vIHRvdWNoLWFjdGlvbjogbWFuaXB1bGF0aW9uLCBidXQgdGhhdCBhbGxvd3MgcGFubmluZywgd2hpY2ggYnJlYWtzXHJcblx0XHRcdC8vIHNsaWRlcnMgYWZ0ZXIgem9vbWluZy9vbiBub24tcmVzcG9uc2l2ZSBwYWdlcy5cclxuXHRcdFx0Ly8gU2VlOiBodHRwczovL2J1Z3Mud2Via2l0Lm9yZy9zaG93X2J1Zy5jZ2k/aWQ9MTMzMTEyXHJcblx0XHRcdGlmICggIXN1cHBvcnRzUGFzc2l2ZSApIHtcclxuXHRcdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdGUuY2FsY1BvaW50ID0gZS5wb2ludHNbIG9wdGlvbnMub3J0IF07XHJcblxyXG5cdFx0XHQvLyBDYWxsIHRoZSBldmVudCBoYW5kbGVyIHdpdGggdGhlIGV2ZW50IFsgYW5kIGFkZGl0aW9uYWwgZGF0YSBdLlxyXG5cdFx0XHRjYWxsYmFjayAoIGUsIGRhdGEgKTtcclxuXHRcdH07XHJcblxyXG5cdFx0dmFyIG1ldGhvZHMgPSBbXTtcclxuXHJcblx0XHQvLyBCaW5kIGEgY2xvc3VyZSBvbiB0aGUgdGFyZ2V0IGZvciBldmVyeSBldmVudCB0eXBlLlxyXG5cdFx0ZXZlbnRzLnNwbGl0KCcgJykuZm9yRWFjaChmdW5jdGlvbiggZXZlbnROYW1lICl7XHJcblx0XHRcdGVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihldmVudE5hbWUsIG1ldGhvZCwgc3VwcG9ydHNQYXNzaXZlID8geyBwYXNzaXZlOiB0cnVlIH0gOiBmYWxzZSk7XHJcblx0XHRcdG1ldGhvZHMucHVzaChbZXZlbnROYW1lLCBtZXRob2RdKTtcclxuXHRcdH0pO1xyXG5cclxuXHRcdHJldHVybiBtZXRob2RzO1xyXG5cdH1cclxuXHJcblx0Ly8gUHJvdmlkZSBhIGNsZWFuIGV2ZW50IHdpdGggc3RhbmRhcmRpemVkIG9mZnNldCB2YWx1ZXMuXHJcblx0ZnVuY3Rpb24gZml4RXZlbnQgKCBlLCBwYWdlT2Zmc2V0LCBldmVudFRhcmdldCApIHtcclxuXHJcblx0XHQvLyBGaWx0ZXIgdGhlIGV2ZW50IHRvIHJlZ2lzdGVyIHRoZSB0eXBlLCB3aGljaCBjYW4gYmVcclxuXHRcdC8vIHRvdWNoLCBtb3VzZSBvciBwb2ludGVyLiBPZmZzZXQgY2hhbmdlcyBuZWVkIHRvIGJlXHJcblx0XHQvLyBtYWRlIG9uIGFuIGV2ZW50IHNwZWNpZmljIGJhc2lzLlxyXG5cdFx0dmFyIHRvdWNoID0gZS50eXBlLmluZGV4T2YoJ3RvdWNoJykgPT09IDA7XHJcblx0XHR2YXIgbW91c2UgPSBlLnR5cGUuaW5kZXhPZignbW91c2UnKSA9PT0gMDtcclxuXHRcdHZhciBwb2ludGVyID0gZS50eXBlLmluZGV4T2YoJ3BvaW50ZXInKSA9PT0gMDtcclxuXHJcblx0XHR2YXIgeDtcclxuXHRcdHZhciB5O1xyXG5cclxuXHRcdC8vIElFMTAgaW1wbGVtZW50ZWQgcG9pbnRlciBldmVudHMgd2l0aCBhIHByZWZpeDtcclxuXHRcdGlmICggZS50eXBlLmluZGV4T2YoJ01TUG9pbnRlcicpID09PSAwICkge1xyXG5cdFx0XHRwb2ludGVyID0gdHJ1ZTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBJbiB0aGUgZXZlbnQgdGhhdCBtdWx0aXRvdWNoIGlzIGFjdGl2YXRlZCwgdGhlIG9ubHkgdGhpbmcgb25lIGhhbmRsZSBzaG91bGQgYmUgY29uY2VybmVkXHJcblx0XHQvLyBhYm91dCBpcyB0aGUgdG91Y2hlcyB0aGF0IG9yaWdpbmF0ZWQgb24gdG9wIG9mIGl0LlxyXG5cdFx0aWYgKCB0b3VjaCApIHtcclxuXHJcblx0XHRcdC8vIFJldHVybnMgdHJ1ZSBpZiBhIHRvdWNoIG9yaWdpbmF0ZWQgb24gdGhlIHRhcmdldC5cclxuXHRcdFx0dmFyIGlzVG91Y2hPblRhcmdldCA9IGZ1bmN0aW9uIChjaGVja1RvdWNoKSB7XHJcblx0XHRcdFx0cmV0dXJuIGNoZWNrVG91Y2gudGFyZ2V0ID09PSBldmVudFRhcmdldCB8fCBldmVudFRhcmdldC5jb250YWlucyhjaGVja1RvdWNoLnRhcmdldCk7XHJcblx0XHRcdH07XHJcblxyXG5cdFx0XHQvLyBJbiB0aGUgY2FzZSBvZiB0b3VjaHN0YXJ0IGV2ZW50cywgd2UgbmVlZCB0byBtYWtlIHN1cmUgdGhlcmUgaXMgc3RpbGwgbm8gbW9yZSB0aGFuIG9uZVxyXG5cdFx0XHQvLyB0b3VjaCBvbiB0aGUgdGFyZ2V0IHNvIHdlIGxvb2sgYW1vbmdzdCBhbGwgdG91Y2hlcy5cclxuXHRcdFx0aWYgKGUudHlwZSA9PT0gJ3RvdWNoc3RhcnQnKSB7XHJcblxyXG5cdFx0XHRcdHZhciB0YXJnZXRUb3VjaGVzID0gQXJyYXkucHJvdG90eXBlLmZpbHRlci5jYWxsKGUudG91Y2hlcywgaXNUb3VjaE9uVGFyZ2V0KTtcclxuXHJcblx0XHRcdFx0Ly8gRG8gbm90IHN1cHBvcnQgbW9yZSB0aGFuIG9uZSB0b3VjaCBwZXIgaGFuZGxlLlxyXG5cdFx0XHRcdGlmICggdGFyZ2V0VG91Y2hlcy5sZW5ndGggPiAxICkge1xyXG5cdFx0XHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0XHRcdH1cclxuXHJcblx0XHRcdFx0eCA9IHRhcmdldFRvdWNoZXNbMF0ucGFnZVg7XHJcblx0XHRcdFx0eSA9IHRhcmdldFRvdWNoZXNbMF0ucGFnZVk7XHJcblxyXG5cdFx0XHR9IGVsc2Uge1xyXG5cclxuXHRcdFx0XHQvLyBJbiB0aGUgb3RoZXIgY2FzZXMsIGZpbmQgb24gY2hhbmdlZFRvdWNoZXMgaXMgZW5vdWdoLlxyXG5cdFx0XHRcdHZhciB0YXJnZXRUb3VjaCA9IEFycmF5LnByb3RvdHlwZS5maW5kLmNhbGwoZS5jaGFuZ2VkVG91Y2hlcywgaXNUb3VjaE9uVGFyZ2V0KTtcclxuXHJcblx0XHRcdFx0Ly8gQ2FuY2VsIGlmIHRoZSB0YXJnZXQgdG91Y2ggaGFzIG5vdCBtb3ZlZC5cclxuXHRcdFx0XHRpZiAoICF0YXJnZXRUb3VjaCApIHtcclxuXHRcdFx0XHRcdHJldHVybiBmYWxzZTtcclxuXHRcdFx0XHR9XHJcblxyXG5cdFx0XHRcdHggPSB0YXJnZXRUb3VjaC5wYWdlWDtcclxuXHRcdFx0XHR5ID0gdGFyZ2V0VG91Y2gucGFnZVk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHRwYWdlT2Zmc2V0ID0gcGFnZU9mZnNldCB8fCBnZXRQYWdlT2Zmc2V0KHNjb3BlX0RvY3VtZW50KTtcclxuXHJcblx0XHRpZiAoIG1vdXNlIHx8IHBvaW50ZXIgKSB7XHJcblx0XHRcdHggPSBlLmNsaWVudFggKyBwYWdlT2Zmc2V0Lng7XHJcblx0XHRcdHkgPSBlLmNsaWVudFkgKyBwYWdlT2Zmc2V0Lnk7XHJcblx0XHR9XHJcblxyXG5cdFx0ZS5wYWdlT2Zmc2V0ID0gcGFnZU9mZnNldDtcclxuXHRcdGUucG9pbnRzID0gW3gsIHldO1xyXG5cdFx0ZS5jdXJzb3IgPSBtb3VzZSB8fCBwb2ludGVyOyAvLyBGaXggIzQzNVxyXG5cclxuXHRcdHJldHVybiBlO1xyXG5cdH1cclxuXHJcblx0Ly8gVHJhbnNsYXRlIGEgY29vcmRpbmF0ZSBpbiB0aGUgZG9jdW1lbnQgdG8gYSBwZXJjZW50YWdlIG9uIHRoZSBzbGlkZXJcclxuXHRmdW5jdGlvbiBjYWxjUG9pbnRUb1BlcmNlbnRhZ2UgKCBjYWxjUG9pbnQgKSB7XHJcblx0XHR2YXIgbG9jYXRpb24gPSBjYWxjUG9pbnQgLSBvZmZzZXQoc2NvcGVfQmFzZSwgb3B0aW9ucy5vcnQpO1xyXG5cdFx0dmFyIHByb3Bvc2FsID0gKCBsb2NhdGlvbiAqIDEwMCApIC8gYmFzZVNpemUoKTtcclxuXHJcblx0XHQvLyBDbGFtcCBwcm9wb3NhbCBiZXR3ZWVuIDAlIGFuZCAxMDAlXHJcblx0XHQvLyBPdXQtb2YtYm91bmQgY29vcmRpbmF0ZXMgbWF5IG9jY3VyIHdoZW4gLm5vVWktYmFzZSBwc2V1ZG8tZWxlbWVudHNcclxuXHRcdC8vIGFyZSB1c2VkIChlLmcuIGNvbnRhaW5lZCBoYW5kbGVzIGZlYXR1cmUpXHJcblx0XHRwcm9wb3NhbCA9IGxpbWl0KHByb3Bvc2FsKTtcclxuXHJcblx0XHRyZXR1cm4gb3B0aW9ucy5kaXIgPyAxMDAgLSBwcm9wb3NhbCA6IHByb3Bvc2FsO1xyXG5cdH1cclxuXHJcblx0Ly8gRmluZCBoYW5kbGUgY2xvc2VzdCB0byBhIGNlcnRhaW4gcGVyY2VudGFnZSBvbiB0aGUgc2xpZGVyXHJcblx0ZnVuY3Rpb24gZ2V0Q2xvc2VzdEhhbmRsZSAoIHByb3Bvc2FsICkge1xyXG5cclxuXHRcdHZhciBjbG9zZXN0ID0gMTAwO1xyXG5cdFx0dmFyIGhhbmRsZU51bWJlciA9IGZhbHNlO1xyXG5cclxuXHRcdHNjb3BlX0hhbmRsZXMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGUsIGluZGV4KXtcclxuXHJcblx0XHRcdC8vIERpc2FibGVkIGhhbmRsZXMgYXJlIGlnbm9yZWRcclxuXHRcdFx0aWYgKCBoYW5kbGUuaGFzQXR0cmlidXRlKCdkaXNhYmxlZCcpICkge1xyXG5cdFx0XHRcdHJldHVybjtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0dmFyIHBvcyA9IE1hdGguYWJzKHNjb3BlX0xvY2F0aW9uc1tpbmRleF0gLSBwcm9wb3NhbCk7XHJcblxyXG5cdFx0XHRpZiAoIHBvcyA8IGNsb3Nlc3QgfHwgKHBvcyA9PT0gMTAwICYmIGNsb3Nlc3QgPT09IDEwMCkgKSB7XHJcblx0XHRcdFx0aGFuZGxlTnVtYmVyID0gaW5kZXg7XHJcblx0XHRcdFx0Y2xvc2VzdCA9IHBvcztcclxuXHRcdFx0fVxyXG5cdFx0fSk7XHJcblxyXG5cdFx0cmV0dXJuIGhhbmRsZU51bWJlcjtcclxuXHR9XHJcblxyXG5cdC8vIEZpcmUgJ2VuZCcgd2hlbiBhIG1vdXNlIG9yIHBlbiBsZWF2ZXMgdGhlIGRvY3VtZW50LlxyXG5cdGZ1bmN0aW9uIGRvY3VtZW50TGVhdmUgKCBldmVudCwgZGF0YSApIHtcclxuXHRcdGlmICggZXZlbnQudHlwZSA9PT0gXCJtb3VzZW91dFwiICYmIGV2ZW50LnRhcmdldC5ub2RlTmFtZSA9PT0gXCJIVE1MXCIgJiYgZXZlbnQucmVsYXRlZFRhcmdldCA9PT0gbnVsbCApe1xyXG5cdFx0XHRldmVudEVuZCAoZXZlbnQsIGRhdGEpO1xyXG5cdFx0fVxyXG5cdH1cclxuXHJcblx0Ly8gSGFuZGxlIG1vdmVtZW50IG9uIGRvY3VtZW50IGZvciBoYW5kbGUgYW5kIHJhbmdlIGRyYWcuXHJcblx0ZnVuY3Rpb24gZXZlbnRNb3ZlICggZXZlbnQsIGRhdGEgKSB7XHJcblxyXG5cdFx0Ly8gRml4ICM0OThcclxuXHRcdC8vIENoZWNrIHZhbHVlIG9mIC5idXR0b25zIGluICdzdGFydCcgdG8gd29yayBhcm91bmQgYSBidWcgaW4gSUUxMCBtb2JpbGUgKGRhdGEuYnV0dG9uc1Byb3BlcnR5KS5cclxuXHRcdC8vIGh0dHBzOi8vY29ubmVjdC5taWNyb3NvZnQuY29tL0lFL2ZlZWRiYWNrL2RldGFpbHMvOTI3MDA1L21vYmlsZS1pZTEwLXdpbmRvd3MtcGhvbmUtYnV0dG9ucy1wcm9wZXJ0eS1vZi1wb2ludGVybW92ZS1ldmVudC1hbHdheXMtemVyb1xyXG5cdFx0Ly8gSUU5IGhhcyAuYnV0dG9ucyBhbmQgLndoaWNoIHplcm8gb24gbW91c2Vtb3ZlLlxyXG5cdFx0Ly8gRmlyZWZveCBicmVha3MgdGhlIHNwZWMgTUROIGRlZmluZXMuXHJcblx0XHRpZiAoIG5hdmlnYXRvci5hcHBWZXJzaW9uLmluZGV4T2YoXCJNU0lFIDlcIikgPT09IC0xICYmIGV2ZW50LmJ1dHRvbnMgPT09IDAgJiYgZGF0YS5idXR0b25zUHJvcGVydHkgIT09IDAgKSB7XHJcblx0XHRcdHJldHVybiBldmVudEVuZChldmVudCwgZGF0YSk7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gQ2hlY2sgaWYgd2UgYXJlIG1vdmluZyB1cCBvciBkb3duXHJcblx0XHR2YXIgbW92ZW1lbnQgPSAob3B0aW9ucy5kaXIgPyAtMSA6IDEpICogKGV2ZW50LmNhbGNQb2ludCAtIGRhdGEuc3RhcnRDYWxjUG9pbnQpO1xyXG5cclxuXHRcdC8vIENvbnZlcnQgdGhlIG1vdmVtZW50IGludG8gYSBwZXJjZW50YWdlIG9mIHRoZSBzbGlkZXIgd2lkdGgvaGVpZ2h0XHJcblx0XHR2YXIgcHJvcG9zYWwgPSAobW92ZW1lbnQgKiAxMDApIC8gZGF0YS5iYXNlU2l6ZTtcclxuXHJcblx0XHRtb3ZlSGFuZGxlcyhtb3ZlbWVudCA+IDAsIHByb3Bvc2FsLCBkYXRhLmxvY2F0aW9ucywgZGF0YS5oYW5kbGVOdW1iZXJzKTtcclxuXHR9XHJcblxyXG5cdC8vIFVuYmluZCBtb3ZlIGV2ZW50cyBvbiBkb2N1bWVudCwgY2FsbCBjYWxsYmFja3MuXHJcblx0ZnVuY3Rpb24gZXZlbnRFbmQgKCBldmVudCwgZGF0YSApIHtcclxuXHJcblx0XHQvLyBUaGUgaGFuZGxlIGlzIG5vIGxvbmdlciBhY3RpdmUsIHNvIHJlbW92ZSB0aGUgY2xhc3MuXHJcblx0XHRpZiAoIGRhdGEuaGFuZGxlICkge1xyXG5cdFx0XHRyZW1vdmVDbGFzcyhkYXRhLmhhbmRsZSwgb3B0aW9ucy5jc3NDbGFzc2VzLmFjdGl2ZSk7XHJcblx0XHRcdHNjb3BlX0FjdGl2ZUhhbmRsZXNDb3VudCAtPSAxO1xyXG5cdFx0fVxyXG5cclxuXHRcdC8vIFVuYmluZCB0aGUgbW92ZSBhbmQgZW5kIGV2ZW50cywgd2hpY2ggYXJlIGFkZGVkIG9uICdzdGFydCcuXHJcblx0XHRkYXRhLmxpc3RlbmVycy5mb3JFYWNoKGZ1bmN0aW9uKCBjICkge1xyXG5cdFx0XHRzY29wZV9Eb2N1bWVudEVsZW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcihjWzBdLCBjWzFdKTtcclxuXHRcdH0pO1xyXG5cclxuXHRcdGlmICggc2NvcGVfQWN0aXZlSGFuZGxlc0NvdW50ID09PSAwICkge1xyXG5cdFx0XHQvLyBSZW1vdmUgZHJhZ2dpbmcgY2xhc3MuXHJcblx0XHRcdHJlbW92ZUNsYXNzKHNjb3BlX1RhcmdldCwgb3B0aW9ucy5jc3NDbGFzc2VzLmRyYWcpO1xyXG5cdFx0XHRzZXRaaW5kZXgoKTtcclxuXHJcblx0XHRcdC8vIFJlbW92ZSBjdXJzb3Igc3R5bGVzIGFuZCB0ZXh0LXNlbGVjdGlvbiBldmVudHMgYm91bmQgdG8gdGhlIGJvZHkuXHJcblx0XHRcdGlmICggZXZlbnQuY3Vyc29yICkge1xyXG5cdFx0XHRcdHNjb3BlX0JvZHkuc3R5bGUuY3Vyc29yID0gJyc7XHJcblx0XHRcdFx0c2NvcGVfQm9keS5yZW1vdmVFdmVudExpc3RlbmVyKCdzZWxlY3RzdGFydCcsIHByZXZlbnREZWZhdWx0KTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cclxuXHRcdGRhdGEuaGFuZGxlTnVtYmVycy5mb3JFYWNoKGZ1bmN0aW9uKGhhbmRsZU51bWJlcil7XHJcblx0XHRcdGZpcmVFdmVudCgnY2hhbmdlJywgaGFuZGxlTnVtYmVyKTtcclxuXHRcdFx0ZmlyZUV2ZW50KCdzZXQnLCBoYW5kbGVOdW1iZXIpO1xyXG5cdFx0XHRmaXJlRXZlbnQoJ2VuZCcsIGhhbmRsZU51bWJlcik7XHJcblx0XHR9KTtcclxuXHR9XHJcblxyXG5cdC8vIEJpbmQgbW92ZSBldmVudHMgb24gZG9jdW1lbnQuXHJcblx0ZnVuY3Rpb24gZXZlbnRTdGFydCAoIGV2ZW50LCBkYXRhICkge1xyXG5cclxuXHRcdHZhciBoYW5kbGU7XHJcblx0XHRpZiAoIGRhdGEuaGFuZGxlTnVtYmVycy5sZW5ndGggPT09IDEgKSB7XHJcblxyXG5cdFx0XHR2YXIgaGFuZGxlT3JpZ2luID0gc2NvcGVfSGFuZGxlc1tkYXRhLmhhbmRsZU51bWJlcnNbMF1dO1xyXG5cclxuXHRcdFx0Ly8gSWdub3JlICdkaXNhYmxlZCcgaGFuZGxlc1xyXG5cdFx0XHRpZiAoIGhhbmRsZU9yaWdpbi5oYXNBdHRyaWJ1dGUoJ2Rpc2FibGVkJykgKSB7XHJcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHRoYW5kbGUgPSBoYW5kbGVPcmlnaW4uY2hpbGRyZW5bMF07XHJcblx0XHRcdHNjb3BlX0FjdGl2ZUhhbmRsZXNDb3VudCArPSAxO1xyXG5cclxuXHRcdFx0Ly8gTWFyayB0aGUgaGFuZGxlIGFzICdhY3RpdmUnIHNvIGl0IGNhbiBiZSBzdHlsZWQuXHJcblx0XHRcdGFkZENsYXNzKGhhbmRsZSwgb3B0aW9ucy5jc3NDbGFzc2VzLmFjdGl2ZSk7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gQSBkcmFnIHNob3VsZCBuZXZlciBwcm9wYWdhdGUgdXAgdG8gdGhlICd0YXAnIGV2ZW50LlxyXG5cdFx0ZXZlbnQuc3RvcFByb3BhZ2F0aW9uKCk7XHJcblxyXG5cdFx0Ly8gUmVjb3JkIHRoZSBldmVudCBsaXN0ZW5lcnMuXHJcblx0XHR2YXIgbGlzdGVuZXJzID0gW107XHJcblxyXG5cdFx0Ly8gQXR0YWNoIHRoZSBtb3ZlIGFuZCBlbmQgZXZlbnRzLlxyXG5cdFx0dmFyIG1vdmVFdmVudCA9IGF0dGFjaEV2ZW50KGFjdGlvbnMubW92ZSwgc2NvcGVfRG9jdW1lbnRFbGVtZW50LCBldmVudE1vdmUsIHtcclxuXHRcdFx0Ly8gVGhlIGV2ZW50IHRhcmdldCBoYXMgY2hhbmdlZCBzbyB3ZSBuZWVkIHRvIHByb3BhZ2F0ZSB0aGUgb3JpZ2luYWwgb25lIHNvIHRoYXQgd2Uga2VlcFxyXG5cdFx0XHQvLyByZWx5aW5nIG9uIGl0IHRvIGV4dHJhY3QgdGFyZ2V0IHRvdWNoZXMuXHJcblx0XHRcdHRhcmdldDogZXZlbnQudGFyZ2V0LFxyXG5cdFx0XHRoYW5kbGU6IGhhbmRsZSxcclxuXHRcdFx0bGlzdGVuZXJzOiBsaXN0ZW5lcnMsXHJcblx0XHRcdHN0YXJ0Q2FsY1BvaW50OiBldmVudC5jYWxjUG9pbnQsXHJcblx0XHRcdGJhc2VTaXplOiBiYXNlU2l6ZSgpLFxyXG5cdFx0XHRwYWdlT2Zmc2V0OiBldmVudC5wYWdlT2Zmc2V0LFxyXG5cdFx0XHRoYW5kbGVOdW1iZXJzOiBkYXRhLmhhbmRsZU51bWJlcnMsXHJcblx0XHRcdGJ1dHRvbnNQcm9wZXJ0eTogZXZlbnQuYnV0dG9ucyxcclxuXHRcdFx0bG9jYXRpb25zOiBzY29wZV9Mb2NhdGlvbnMuc2xpY2UoKVxyXG5cdFx0fSk7XHJcblxyXG5cdFx0dmFyIGVuZEV2ZW50ID0gYXR0YWNoRXZlbnQoYWN0aW9ucy5lbmQsIHNjb3BlX0RvY3VtZW50RWxlbWVudCwgZXZlbnRFbmQsIHtcclxuXHRcdFx0dGFyZ2V0OiBldmVudC50YXJnZXQsXHJcblx0XHRcdGhhbmRsZTogaGFuZGxlLFxyXG5cdFx0XHRsaXN0ZW5lcnM6IGxpc3RlbmVycyxcclxuXHRcdFx0ZG9Ob3RSZWplY3Q6IHRydWUsXHJcblx0XHRcdGhhbmRsZU51bWJlcnM6IGRhdGEuaGFuZGxlTnVtYmVyc1xyXG5cdFx0fSk7XHJcblxyXG5cdFx0dmFyIG91dEV2ZW50ID0gYXR0YWNoRXZlbnQoXCJtb3VzZW91dFwiLCBzY29wZV9Eb2N1bWVudEVsZW1lbnQsIGRvY3VtZW50TGVhdmUsIHtcclxuXHRcdFx0dGFyZ2V0OiBldmVudC50YXJnZXQsXHJcblx0XHRcdGhhbmRsZTogaGFuZGxlLFxyXG5cdFx0XHRsaXN0ZW5lcnM6IGxpc3RlbmVycyxcclxuXHRcdFx0ZG9Ob3RSZWplY3Q6IHRydWUsXHJcblx0XHRcdGhhbmRsZU51bWJlcnM6IGRhdGEuaGFuZGxlTnVtYmVyc1xyXG5cdFx0fSk7XHJcblxyXG5cdFx0Ly8gV2Ugd2FudCB0byBtYWtlIHN1cmUgd2UgcHVzaGVkIHRoZSBsaXN0ZW5lcnMgaW4gdGhlIGxpc3RlbmVyIGxpc3QgcmF0aGVyIHRoYW4gY3JlYXRpbmdcclxuXHRcdC8vIGEgbmV3IG9uZSBhcyBpdCBoYXMgYWxyZWFkeSBiZWVuIHBhc3NlZCB0byB0aGUgZXZlbnQgaGFuZGxlcnMuXHJcblx0XHRsaXN0ZW5lcnMucHVzaC5hcHBseShsaXN0ZW5lcnMsIG1vdmVFdmVudC5jb25jYXQoZW5kRXZlbnQsIG91dEV2ZW50KSk7XHJcblxyXG5cdFx0Ly8gVGV4dCBzZWxlY3Rpb24gaXNuJ3QgYW4gaXNzdWUgb24gdG91Y2ggZGV2aWNlcyxcclxuXHRcdC8vIHNvIGFkZGluZyBjdXJzb3Igc3R5bGVzIGNhbiBiZSBza2lwcGVkLlxyXG5cdFx0aWYgKCBldmVudC5jdXJzb3IgKSB7XHJcblxyXG5cdFx0XHQvLyBQcmV2ZW50IHRoZSAnSScgY3Vyc29yIGFuZCBleHRlbmQgdGhlIHJhbmdlLWRyYWcgY3Vyc29yLlxyXG5cdFx0XHRzY29wZV9Cb2R5LnN0eWxlLmN1cnNvciA9IGdldENvbXB1dGVkU3R5bGUoZXZlbnQudGFyZ2V0KS5jdXJzb3I7XHJcblxyXG5cdFx0XHQvLyBNYXJrIHRoZSB0YXJnZXQgd2l0aCBhIGRyYWdnaW5nIHN0YXRlLlxyXG5cdFx0XHRpZiAoIHNjb3BlX0hhbmRsZXMubGVuZ3RoID4gMSApIHtcclxuXHRcdFx0XHRhZGRDbGFzcyhzY29wZV9UYXJnZXQsIG9wdGlvbnMuY3NzQ2xhc3Nlcy5kcmFnKTtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0Ly8gUHJldmVudCB0ZXh0IHNlbGVjdGlvbiB3aGVuIGRyYWdnaW5nIHRoZSBoYW5kbGVzLlxyXG5cdFx0XHQvLyBJbiBub1VpU2xpZGVyIDw9IDkuMi4wLCB0aGlzIHdhcyBoYW5kbGVkIGJ5IGNhbGxpbmcgcHJldmVudERlZmF1bHQgb24gbW91c2UvdG91Y2ggc3RhcnQvbW92ZSxcclxuXHRcdFx0Ly8gd2hpY2ggaXMgc2Nyb2xsIGJsb2NraW5nLiBUaGUgc2VsZWN0c3RhcnQgZXZlbnQgaXMgc3VwcG9ydGVkIGJ5IEZpcmVGb3ggc3RhcnRpbmcgZnJvbSB2ZXJzaW9uIDUyLFxyXG5cdFx0XHQvLyBtZWFuaW5nIHRoZSBvbmx5IGhvbGRvdXQgaXMgaU9TIFNhZmFyaS4gVGhpcyBkb2Vzbid0IG1hdHRlcjogdGV4dCBzZWxlY3Rpb24gaXNuJ3QgdHJpZ2dlcmVkIHRoZXJlLlxyXG5cdFx0XHQvLyBUaGUgJ2N1cnNvcicgZmxhZyBpcyBmYWxzZS5cclxuXHRcdFx0Ly8gU2VlOiBodHRwOi8vY2FuaXVzZS5jb20vI3NlYXJjaD1zZWxlY3RzdGFydFxyXG5cdFx0XHRzY29wZV9Cb2R5LmFkZEV2ZW50TGlzdGVuZXIoJ3NlbGVjdHN0YXJ0JywgcHJldmVudERlZmF1bHQsIGZhbHNlKTtcclxuXHRcdH1cclxuXHJcblx0XHRkYXRhLmhhbmRsZU51bWJlcnMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGVOdW1iZXIpe1xyXG5cdFx0XHRmaXJlRXZlbnQoJ3N0YXJ0JywgaGFuZGxlTnVtYmVyKTtcclxuXHRcdH0pO1xyXG5cdH1cclxuXHJcblx0Ly8gTW92ZSBjbG9zZXN0IGhhbmRsZSB0byB0YXBwZWQgbG9jYXRpb24uXHJcblx0ZnVuY3Rpb24gZXZlbnRUYXAgKCBldmVudCApIHtcclxuXHJcblx0XHQvLyBUaGUgdGFwIGV2ZW50IHNob3VsZG4ndCBwcm9wYWdhdGUgdXBcclxuXHRcdGV2ZW50LnN0b3BQcm9wYWdhdGlvbigpO1xyXG5cclxuXHRcdHZhciBwcm9wb3NhbCA9IGNhbGNQb2ludFRvUGVyY2VudGFnZShldmVudC5jYWxjUG9pbnQpO1xyXG5cdFx0dmFyIGhhbmRsZU51bWJlciA9IGdldENsb3Nlc3RIYW5kbGUocHJvcG9zYWwpO1xyXG5cclxuXHRcdC8vIFRhY2tsZSB0aGUgY2FzZSB0aGF0IGFsbCBoYW5kbGVzIGFyZSAnZGlzYWJsZWQnLlxyXG5cdFx0aWYgKCBoYW5kbGVOdW1iZXIgPT09IGZhbHNlICkge1xyXG5cdFx0XHRyZXR1cm4gZmFsc2U7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gRmxhZyB0aGUgc2xpZGVyIGFzIGl0IGlzIG5vdyBpbiBhIHRyYW5zaXRpb25hbCBzdGF0ZS5cclxuXHRcdC8vIFRyYW5zaXRpb24gdGFrZXMgYSBjb25maWd1cmFibGUgYW1vdW50IG9mIG1zIChkZWZhdWx0IDMwMCkuIFJlLWVuYWJsZSB0aGUgc2xpZGVyIGFmdGVyIHRoYXQuXHJcblx0XHRpZiAoICFvcHRpb25zLmV2ZW50cy5zbmFwICkge1xyXG5cdFx0XHRhZGRDbGFzc0ZvcihzY29wZV9UYXJnZXQsIG9wdGlvbnMuY3NzQ2xhc3Nlcy50YXAsIG9wdGlvbnMuYW5pbWF0aW9uRHVyYXRpb24pO1xyXG5cdFx0fVxyXG5cclxuXHRcdHNldEhhbmRsZShoYW5kbGVOdW1iZXIsIHByb3Bvc2FsLCB0cnVlLCB0cnVlKTtcclxuXHJcblx0XHRzZXRaaW5kZXgoKTtcclxuXHJcblx0XHRmaXJlRXZlbnQoJ3NsaWRlJywgaGFuZGxlTnVtYmVyLCB0cnVlKTtcclxuXHRcdGZpcmVFdmVudCgndXBkYXRlJywgaGFuZGxlTnVtYmVyLCB0cnVlKTtcclxuXHRcdGZpcmVFdmVudCgnY2hhbmdlJywgaGFuZGxlTnVtYmVyLCB0cnVlKTtcclxuXHRcdGZpcmVFdmVudCgnc2V0JywgaGFuZGxlTnVtYmVyLCB0cnVlKTtcclxuXHJcblx0XHRpZiAoIG9wdGlvbnMuZXZlbnRzLnNuYXAgKSB7XHJcblx0XHRcdGV2ZW50U3RhcnQoZXZlbnQsIHsgaGFuZGxlTnVtYmVyczogW2hhbmRsZU51bWJlcl0gfSk7XHJcblx0XHR9XHJcblx0fVxyXG5cclxuXHQvLyBGaXJlcyBhICdob3ZlcicgZXZlbnQgZm9yIGEgaG92ZXJlZCBtb3VzZS9wZW4gcG9zaXRpb24uXHJcblx0ZnVuY3Rpb24gZXZlbnRIb3ZlciAoIGV2ZW50ICkge1xyXG5cclxuXHRcdHZhciBwcm9wb3NhbCA9IGNhbGNQb2ludFRvUGVyY2VudGFnZShldmVudC5jYWxjUG9pbnQpO1xyXG5cclxuXHRcdHZhciB0byA9IHNjb3BlX1NwZWN0cnVtLmdldFN0ZXAocHJvcG9zYWwpO1xyXG5cdFx0dmFyIHZhbHVlID0gc2NvcGVfU3BlY3RydW0uZnJvbVN0ZXBwaW5nKHRvKTtcclxuXHJcblx0XHRPYmplY3Qua2V5cyhzY29wZV9FdmVudHMpLmZvckVhY2goZnVuY3Rpb24oIHRhcmdldEV2ZW50ICkge1xyXG5cdFx0XHRpZiAoICdob3ZlcicgPT09IHRhcmdldEV2ZW50LnNwbGl0KCcuJylbMF0gKSB7XHJcblx0XHRcdFx0c2NvcGVfRXZlbnRzW3RhcmdldEV2ZW50XS5mb3JFYWNoKGZ1bmN0aW9uKCBjYWxsYmFjayApIHtcclxuXHRcdFx0XHRcdGNhbGxiYWNrLmNhbGwoIHNjb3BlX1NlbGYsIHZhbHVlICk7XHJcblx0XHRcdFx0fSk7XHJcblx0XHRcdH1cclxuXHRcdH0pO1xyXG5cdH1cclxuXHJcblx0Ly8gQXR0YWNoIGV2ZW50cyB0byBzZXZlcmFsIHNsaWRlciBwYXJ0cy5cclxuXHRmdW5jdGlvbiBiaW5kU2xpZGVyRXZlbnRzICggYmVoYXZpb3VyICkge1xyXG5cclxuXHRcdC8vIEF0dGFjaCB0aGUgc3RhbmRhcmQgZHJhZyBldmVudCB0byB0aGUgaGFuZGxlcy5cclxuXHRcdGlmICggIWJlaGF2aW91ci5maXhlZCApIHtcclxuXHJcblx0XHRcdHNjb3BlX0hhbmRsZXMuZm9yRWFjaChmdW5jdGlvbiggaGFuZGxlLCBpbmRleCApe1xyXG5cclxuXHRcdFx0XHQvLyBUaGVzZSBldmVudHMgYXJlIG9ubHkgYm91bmQgdG8gdGhlIHZpc3VhbCBoYW5kbGVcclxuXHRcdFx0XHQvLyBlbGVtZW50LCBub3QgdGhlICdyZWFsJyBvcmlnaW4gZWxlbWVudC5cclxuXHRcdFx0XHRhdHRhY2hFdmVudCAoIGFjdGlvbnMuc3RhcnQsIGhhbmRsZS5jaGlsZHJlblswXSwgZXZlbnRTdGFydCwge1xyXG5cdFx0XHRcdFx0aGFuZGxlTnVtYmVyczogW2luZGV4XVxyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHR9KTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBBdHRhY2ggdGhlIHRhcCBldmVudCB0byB0aGUgc2xpZGVyIGJhc2UuXHJcblx0XHRpZiAoIGJlaGF2aW91ci50YXAgKSB7XHJcblx0XHRcdGF0dGFjaEV2ZW50IChhY3Rpb25zLnN0YXJ0LCBzY29wZV9CYXNlLCBldmVudFRhcCwge30pO1xyXG5cdFx0fVxyXG5cclxuXHRcdC8vIEZpcmUgaG92ZXIgZXZlbnRzXHJcblx0XHRpZiAoIGJlaGF2aW91ci5ob3ZlciApIHtcclxuXHRcdFx0YXR0YWNoRXZlbnQgKGFjdGlvbnMubW92ZSwgc2NvcGVfQmFzZSwgZXZlbnRIb3ZlciwgeyBob3ZlcjogdHJ1ZSB9KTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBNYWtlIHRoZSByYW5nZSBkcmFnZ2FibGUuXHJcblx0XHRpZiAoIGJlaGF2aW91ci5kcmFnICl7XHJcblxyXG5cdFx0XHRzY29wZV9Db25uZWN0cy5mb3JFYWNoKGZ1bmN0aW9uKCBjb25uZWN0LCBpbmRleCApe1xyXG5cclxuXHRcdFx0XHRpZiAoIGNvbm5lY3QgPT09IGZhbHNlIHx8IGluZGV4ID09PSAwIHx8IGluZGV4ID09PSBzY29wZV9Db25uZWN0cy5sZW5ndGggLSAxICkge1xyXG5cdFx0XHRcdFx0cmV0dXJuO1xyXG5cdFx0XHRcdH1cclxuXHJcblx0XHRcdFx0dmFyIGhhbmRsZUJlZm9yZSA9IHNjb3BlX0hhbmRsZXNbaW5kZXggLSAxXTtcclxuXHRcdFx0XHR2YXIgaGFuZGxlQWZ0ZXIgPSBzY29wZV9IYW5kbGVzW2luZGV4XTtcclxuXHRcdFx0XHR2YXIgZXZlbnRIb2xkZXJzID0gW2Nvbm5lY3RdO1xyXG5cclxuXHRcdFx0XHRhZGRDbGFzcyhjb25uZWN0LCBvcHRpb25zLmNzc0NsYXNzZXMuZHJhZ2dhYmxlKTtcclxuXHJcblx0XHRcdFx0Ly8gV2hlbiB0aGUgcmFuZ2UgaXMgZml4ZWQsIHRoZSBlbnRpcmUgcmFuZ2UgY2FuXHJcblx0XHRcdFx0Ly8gYmUgZHJhZ2dlZCBieSB0aGUgaGFuZGxlcy4gVGhlIGhhbmRsZSBpbiB0aGUgZmlyc3RcclxuXHRcdFx0XHQvLyBvcmlnaW4gd2lsbCBwcm9wYWdhdGUgdGhlIHN0YXJ0IGV2ZW50IHVwd2FyZCxcclxuXHRcdFx0XHQvLyBidXQgaXQgbmVlZHMgdG8gYmUgYm91bmQgbWFudWFsbHkgb24gdGhlIG90aGVyLlxyXG5cdFx0XHRcdGlmICggYmVoYXZpb3VyLmZpeGVkICkge1xyXG5cdFx0XHRcdFx0ZXZlbnRIb2xkZXJzLnB1c2goaGFuZGxlQmVmb3JlLmNoaWxkcmVuWzBdKTtcclxuXHRcdFx0XHRcdGV2ZW50SG9sZGVycy5wdXNoKGhhbmRsZUFmdGVyLmNoaWxkcmVuWzBdKTtcclxuXHRcdFx0XHR9XHJcblxyXG5cdFx0XHRcdGV2ZW50SG9sZGVycy5mb3JFYWNoKGZ1bmN0aW9uKCBldmVudEhvbGRlciApIHtcclxuXHRcdFx0XHRcdGF0dGFjaEV2ZW50ICggYWN0aW9ucy5zdGFydCwgZXZlbnRIb2xkZXIsIGV2ZW50U3RhcnQsIHtcclxuXHRcdFx0XHRcdFx0aGFuZGxlczogW2hhbmRsZUJlZm9yZSwgaGFuZGxlQWZ0ZXJdLFxyXG5cdFx0XHRcdFx0XHRoYW5kbGVOdW1iZXJzOiBbaW5kZXggLSAxLCBpbmRleF1cclxuXHRcdFx0XHRcdH0pO1xyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHR9KTtcclxuXHRcdH1cclxuXHR9XHJcblxyXG4vKiEgSW4gdGhpcyBmaWxlOiBTbGlkZXIgZXZlbnRzIChub3QgYnJvd3NlciBldmVudHMpOyAqL1xyXG5cclxuXHQvLyBBdHRhY2ggYW4gZXZlbnQgdG8gdGhpcyBzbGlkZXIsIHBvc3NpYmx5IGluY2x1ZGluZyBhIG5hbWVzcGFjZVxyXG5cdGZ1bmN0aW9uIGJpbmRFdmVudCAoIG5hbWVzcGFjZWRFdmVudCwgY2FsbGJhY2sgKSB7XHJcblx0XHRzY29wZV9FdmVudHNbbmFtZXNwYWNlZEV2ZW50XSA9IHNjb3BlX0V2ZW50c1tuYW1lc3BhY2VkRXZlbnRdIHx8IFtdO1xyXG5cdFx0c2NvcGVfRXZlbnRzW25hbWVzcGFjZWRFdmVudF0ucHVzaChjYWxsYmFjayk7XHJcblxyXG5cdFx0Ly8gSWYgdGhlIGV2ZW50IGJvdW5kIGlzICd1cGRhdGUsJyBmaXJlIGl0IGltbWVkaWF0ZWx5IGZvciBhbGwgaGFuZGxlcy5cclxuXHRcdGlmICggbmFtZXNwYWNlZEV2ZW50LnNwbGl0KCcuJylbMF0gPT09ICd1cGRhdGUnICkge1xyXG5cdFx0XHRzY29wZV9IYW5kbGVzLmZvckVhY2goZnVuY3Rpb24oYSwgaW5kZXgpe1xyXG5cdFx0XHRcdGZpcmVFdmVudCgndXBkYXRlJywgaW5kZXgpO1xyXG5cdFx0XHR9KTtcclxuXHRcdH1cclxuXHR9XHJcblxyXG5cdC8vIFVuZG8gYXR0YWNobWVudCBvZiBldmVudFxyXG5cdGZ1bmN0aW9uIHJlbW92ZUV2ZW50ICggbmFtZXNwYWNlZEV2ZW50ICkge1xyXG5cclxuXHRcdHZhciBldmVudCA9IG5hbWVzcGFjZWRFdmVudCAmJiBuYW1lc3BhY2VkRXZlbnQuc3BsaXQoJy4nKVswXTtcclxuXHRcdHZhciBuYW1lc3BhY2UgPSBldmVudCAmJiBuYW1lc3BhY2VkRXZlbnQuc3Vic3RyaW5nKGV2ZW50Lmxlbmd0aCk7XHJcblxyXG5cdFx0T2JqZWN0LmtleXMoc2NvcGVfRXZlbnRzKS5mb3JFYWNoKGZ1bmN0aW9uKCBiaW5kICl7XHJcblxyXG5cdFx0XHR2YXIgdEV2ZW50ID0gYmluZC5zcGxpdCgnLicpWzBdO1xyXG5cdFx0XHR2YXIgdE5hbWVzcGFjZSA9IGJpbmQuc3Vic3RyaW5nKHRFdmVudC5sZW5ndGgpO1xyXG5cclxuXHRcdFx0aWYgKCAoIWV2ZW50IHx8IGV2ZW50ID09PSB0RXZlbnQpICYmICghbmFtZXNwYWNlIHx8IG5hbWVzcGFjZSA9PT0gdE5hbWVzcGFjZSkgKSB7XHJcblx0XHRcdFx0ZGVsZXRlIHNjb3BlX0V2ZW50c1tiaW5kXTtcclxuXHRcdFx0fVxyXG5cdFx0fSk7XHJcblx0fVxyXG5cclxuXHQvLyBFeHRlcm5hbCBldmVudCBoYW5kbGluZ1xyXG5cdGZ1bmN0aW9uIGZpcmVFdmVudCAoIGV2ZW50TmFtZSwgaGFuZGxlTnVtYmVyLCB0YXAgKSB7XHJcblxyXG5cdFx0T2JqZWN0LmtleXMoc2NvcGVfRXZlbnRzKS5mb3JFYWNoKGZ1bmN0aW9uKCB0YXJnZXRFdmVudCApIHtcclxuXHJcblx0XHRcdHZhciBldmVudFR5cGUgPSB0YXJnZXRFdmVudC5zcGxpdCgnLicpWzBdO1xyXG5cclxuXHRcdFx0aWYgKCBldmVudE5hbWUgPT09IGV2ZW50VHlwZSApIHtcclxuXHRcdFx0XHRzY29wZV9FdmVudHNbdGFyZ2V0RXZlbnRdLmZvckVhY2goZnVuY3Rpb24oIGNhbGxiYWNrICkge1xyXG5cclxuXHRcdFx0XHRcdGNhbGxiYWNrLmNhbGwoXHJcblx0XHRcdFx0XHRcdC8vIFVzZSB0aGUgc2xpZGVyIHB1YmxpYyBBUEkgYXMgdGhlIHNjb3BlICgndGhpcycpXHJcblx0XHRcdFx0XHRcdHNjb3BlX1NlbGYsXHJcblx0XHRcdFx0XHRcdC8vIFJldHVybiB2YWx1ZXMgYXMgYXJyYXksIHNvIGFyZ18xW2FyZ18yXSBpcyBhbHdheXMgdmFsaWQuXHJcblx0XHRcdFx0XHRcdHNjb3BlX1ZhbHVlcy5tYXAob3B0aW9ucy5mb3JtYXQudG8pLFxyXG5cdFx0XHRcdFx0XHQvLyBIYW5kbGUgaW5kZXgsIDAgb3IgMVxyXG5cdFx0XHRcdFx0XHRoYW5kbGVOdW1iZXIsXHJcblx0XHRcdFx0XHRcdC8vIFVuZm9ybWF0dGVkIHNsaWRlciB2YWx1ZXNcclxuXHRcdFx0XHRcdFx0c2NvcGVfVmFsdWVzLnNsaWNlKCksXHJcblx0XHRcdFx0XHRcdC8vIEV2ZW50IGlzIGZpcmVkIGJ5IHRhcCwgdHJ1ZSBvciBmYWxzZVxyXG5cdFx0XHRcdFx0XHR0YXAgfHwgZmFsc2UsXHJcblx0XHRcdFx0XHRcdC8vIExlZnQgb2Zmc2V0IG9mIHRoZSBoYW5kbGUsIGluIHJlbGF0aW9uIHRvIHRoZSBzbGlkZXJcclxuXHRcdFx0XHRcdFx0c2NvcGVfTG9jYXRpb25zLnNsaWNlKClcclxuXHRcdFx0XHRcdCk7XHJcblx0XHRcdFx0fSk7XHJcblx0XHRcdH1cclxuXHRcdH0pO1xyXG5cdH1cclxuXHJcbi8qISBJbiB0aGlzIGZpbGU6IE1lY2hhbmljcyBmb3Igc2xpZGVyIG9wZXJhdGlvbiAqL1xyXG5cclxuXHRmdW5jdGlvbiB0b1BjdCAoIHBjdCApIHtcclxuXHRcdHJldHVybiBwY3QgKyAnJSc7XHJcblx0fVxyXG5cclxuXHQvLyBTcGxpdCBvdXQgdGhlIGhhbmRsZSBwb3NpdGlvbmluZyBsb2dpYyBzbyB0aGUgTW92ZSBldmVudCBjYW4gdXNlIGl0LCB0b29cclxuXHRmdW5jdGlvbiBjaGVja0hhbmRsZVBvc2l0aW9uICggcmVmZXJlbmNlLCBoYW5kbGVOdW1iZXIsIHRvLCBsb29rQmFja3dhcmQsIGxvb2tGb3J3YXJkLCBnZXRWYWx1ZSApIHtcclxuXHJcblx0XHQvLyBGb3Igc2xpZGVycyB3aXRoIG11bHRpcGxlIGhhbmRsZXMsIGxpbWl0IG1vdmVtZW50IHRvIHRoZSBvdGhlciBoYW5kbGUuXHJcblx0XHQvLyBBcHBseSB0aGUgbWFyZ2luIG9wdGlvbiBieSBhZGRpbmcgaXQgdG8gdGhlIGhhbmRsZSBwb3NpdGlvbnMuXHJcblx0XHRpZiAoIHNjb3BlX0hhbmRsZXMubGVuZ3RoID4gMSApIHtcclxuXHJcblx0XHRcdGlmICggbG9va0JhY2t3YXJkICYmIGhhbmRsZU51bWJlciA+IDAgKSB7XHJcblx0XHRcdFx0dG8gPSBNYXRoLm1heCh0bywgcmVmZXJlbmNlW2hhbmRsZU51bWJlciAtIDFdICsgb3B0aW9ucy5tYXJnaW4pO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHRpZiAoIGxvb2tGb3J3YXJkICYmIGhhbmRsZU51bWJlciA8IHNjb3BlX0hhbmRsZXMubGVuZ3RoIC0gMSApIHtcclxuXHRcdFx0XHR0byA9IE1hdGgubWluKHRvLCByZWZlcmVuY2VbaGFuZGxlTnVtYmVyICsgMV0gLSBvcHRpb25zLm1hcmdpbik7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHQvLyBUaGUgbGltaXQgb3B0aW9uIGhhcyB0aGUgb3Bwb3NpdGUgZWZmZWN0LCBsaW1pdGluZyBoYW5kbGVzIHRvIGFcclxuXHRcdC8vIG1heGltdW0gZGlzdGFuY2UgZnJvbSBhbm90aGVyLiBMaW1pdCBtdXN0IGJlID4gMCwgYXMgb3RoZXJ3aXNlXHJcblx0XHQvLyBoYW5kbGVzIHdvdWxkIGJlIHVubW92ZWFibGUuXHJcblx0XHRpZiAoIHNjb3BlX0hhbmRsZXMubGVuZ3RoID4gMSAmJiBvcHRpb25zLmxpbWl0ICkge1xyXG5cclxuXHRcdFx0aWYgKCBsb29rQmFja3dhcmQgJiYgaGFuZGxlTnVtYmVyID4gMCApIHtcclxuXHRcdFx0XHR0byA9IE1hdGgubWluKHRvLCByZWZlcmVuY2VbaGFuZGxlTnVtYmVyIC0gMV0gKyBvcHRpb25zLmxpbWl0KTtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0aWYgKCBsb29rRm9yd2FyZCAmJiBoYW5kbGVOdW1iZXIgPCBzY29wZV9IYW5kbGVzLmxlbmd0aCAtIDEgKSB7XHJcblx0XHRcdFx0dG8gPSBNYXRoLm1heCh0bywgcmVmZXJlbmNlW2hhbmRsZU51bWJlciArIDFdIC0gb3B0aW9ucy5saW1pdCk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHQvLyBUaGUgcGFkZGluZyBvcHRpb24ga2VlcHMgdGhlIGhhbmRsZXMgYSBjZXJ0YWluIGRpc3RhbmNlIGZyb20gdGhlXHJcblx0XHQvLyBlZGdlcyBvZiB0aGUgc2xpZGVyLiBQYWRkaW5nIG11c3QgYmUgPiAwLlxyXG5cdFx0aWYgKCBvcHRpb25zLnBhZGRpbmcgKSB7XHJcblxyXG5cdFx0XHRpZiAoIGhhbmRsZU51bWJlciA9PT0gMCApIHtcclxuXHRcdFx0XHR0byA9IE1hdGgubWF4KHRvLCBvcHRpb25zLnBhZGRpbmdbMF0pO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHRpZiAoIGhhbmRsZU51bWJlciA9PT0gc2NvcGVfSGFuZGxlcy5sZW5ndGggLSAxICkge1xyXG5cdFx0XHRcdHRvID0gTWF0aC5taW4odG8sIDEwMCAtIG9wdGlvbnMucGFkZGluZ1sxXSk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcblx0XHR0byA9IHNjb3BlX1NwZWN0cnVtLmdldFN0ZXAodG8pO1xyXG5cclxuXHRcdC8vIExpbWl0IHBlcmNlbnRhZ2UgdG8gdGhlIDAgLSAxMDAgcmFuZ2VcclxuXHRcdHRvID0gbGltaXQodG8pO1xyXG5cclxuXHRcdC8vIFJldHVybiBmYWxzZSBpZiBoYW5kbGUgY2FuJ3QgbW92ZVxyXG5cdFx0aWYgKCB0byA9PT0gcmVmZXJlbmNlW2hhbmRsZU51bWJlcl0gJiYgIWdldFZhbHVlICkge1xyXG5cdFx0XHRyZXR1cm4gZmFsc2U7XHJcblx0XHR9XHJcblxyXG5cdFx0cmV0dXJuIHRvO1xyXG5cdH1cclxuXHJcblx0Ly8gVXNlcyBzbGlkZXIgb3JpZW50YXRpb24gdG8gY3JlYXRlIENTUyBydWxlcy4gYSA9IGJhc2UgdmFsdWU7XHJcblx0ZnVuY3Rpb24gaW5SdWxlT3JkZXIgKCB2LCBhICkge1xyXG5cdFx0dmFyIG8gPSBvcHRpb25zLm9ydDtcclxuXHRcdHJldHVybiAobz9hOnYpICsgJywgJyArIChvP3Y6YSk7XHJcblx0fVxyXG5cclxuXHQvLyBNb3ZlcyBoYW5kbGUocykgYnkgYSBwZXJjZW50YWdlXHJcblx0Ly8gKGJvb2wsICUgdG8gbW92ZSwgWyUgd2hlcmUgaGFuZGxlIHN0YXJ0ZWQsIC4uLl0sIFtpbmRleCBpbiBzY29wZV9IYW5kbGVzLCAuLi5dKVxyXG5cdGZ1bmN0aW9uIG1vdmVIYW5kbGVzICggdXB3YXJkLCBwcm9wb3NhbCwgbG9jYXRpb25zLCBoYW5kbGVOdW1iZXJzICkge1xyXG5cclxuXHRcdHZhciBwcm9wb3NhbHMgPSBsb2NhdGlvbnMuc2xpY2UoKTtcclxuXHJcblx0XHR2YXIgYiA9IFshdXB3YXJkLCB1cHdhcmRdO1xyXG5cdFx0dmFyIGYgPSBbdXB3YXJkLCAhdXB3YXJkXTtcclxuXHJcblx0XHQvLyBDb3B5IGhhbmRsZU51bWJlcnMgc28gd2UgZG9uJ3QgY2hhbmdlIHRoZSBkYXRhc2V0XHJcblx0XHRoYW5kbGVOdW1iZXJzID0gaGFuZGxlTnVtYmVycy5zbGljZSgpO1xyXG5cclxuXHRcdC8vIENoZWNrIHRvIHNlZSB3aGljaCBoYW5kbGUgaXMgJ2xlYWRpbmcnLlxyXG5cdFx0Ly8gSWYgdGhhdCBvbmUgY2FuJ3QgbW92ZSB0aGUgc2Vjb25kIGNhbid0IGVpdGhlci5cclxuXHRcdGlmICggdXB3YXJkICkge1xyXG5cdFx0XHRoYW5kbGVOdW1iZXJzLnJldmVyc2UoKTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBTdGVwIDE6IGdldCB0aGUgbWF4aW11bSBwZXJjZW50YWdlIHRoYXQgYW55IG9mIHRoZSBoYW5kbGVzIGNhbiBtb3ZlXHJcblx0XHRpZiAoIGhhbmRsZU51bWJlcnMubGVuZ3RoID4gMSApIHtcclxuXHJcblx0XHRcdGhhbmRsZU51bWJlcnMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGVOdW1iZXIsIG8pIHtcclxuXHJcblx0XHRcdFx0dmFyIHRvID0gY2hlY2tIYW5kbGVQb3NpdGlvbihwcm9wb3NhbHMsIGhhbmRsZU51bWJlciwgcHJvcG9zYWxzW2hhbmRsZU51bWJlcl0gKyBwcm9wb3NhbCwgYltvXSwgZltvXSwgZmFsc2UpO1xyXG5cclxuXHRcdFx0XHQvLyBTdG9wIGlmIG9uZSBvZiB0aGUgaGFuZGxlcyBjYW4ndCBtb3ZlLlxyXG5cdFx0XHRcdGlmICggdG8gPT09IGZhbHNlICkge1xyXG5cdFx0XHRcdFx0cHJvcG9zYWwgPSAwO1xyXG5cdFx0XHRcdH0gZWxzZSB7XHJcblx0XHRcdFx0XHRwcm9wb3NhbCA9IHRvIC0gcHJvcG9zYWxzW2hhbmRsZU51bWJlcl07XHJcblx0XHRcdFx0XHRwcm9wb3NhbHNbaGFuZGxlTnVtYmVyXSA9IHRvO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fSk7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gSWYgdXNpbmcgb25lIGhhbmRsZSwgY2hlY2sgYmFja3dhcmQgQU5EIGZvcndhcmRcclxuXHRcdGVsc2Uge1xyXG5cdFx0XHRiID0gZiA9IFt0cnVlXTtcclxuXHRcdH1cclxuXHJcblx0XHR2YXIgc3RhdGUgPSBmYWxzZTtcclxuXHJcblx0XHQvLyBTdGVwIDI6IFRyeSB0byBzZXQgdGhlIGhhbmRsZXMgd2l0aCB0aGUgZm91bmQgcGVyY2VudGFnZVxyXG5cdFx0aGFuZGxlTnVtYmVycy5mb3JFYWNoKGZ1bmN0aW9uKGhhbmRsZU51bWJlciwgbykge1xyXG5cdFx0XHRzdGF0ZSA9IHNldEhhbmRsZShoYW5kbGVOdW1iZXIsIGxvY2F0aW9uc1toYW5kbGVOdW1iZXJdICsgcHJvcG9zYWwsIGJbb10sIGZbb10pIHx8IHN0YXRlO1xyXG5cdFx0fSk7XHJcblxyXG5cdFx0Ly8gU3RlcCAzOiBJZiBhIGhhbmRsZSBtb3ZlZCwgZmlyZSBldmVudHNcclxuXHRcdGlmICggc3RhdGUgKSB7XHJcblx0XHRcdGhhbmRsZU51bWJlcnMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGVOdW1iZXIpe1xyXG5cdFx0XHRcdGZpcmVFdmVudCgndXBkYXRlJywgaGFuZGxlTnVtYmVyKTtcclxuXHRcdFx0XHRmaXJlRXZlbnQoJ3NsaWRlJywgaGFuZGxlTnVtYmVyKTtcclxuXHRcdFx0fSk7XHJcblx0XHR9XHJcblx0fVxyXG5cclxuXHQvLyBUYWtlcyBhIGJhc2UgdmFsdWUgYW5kIGFuIG9mZnNldC4gVGhpcyBvZmZzZXQgaXMgdXNlZCBmb3IgdGhlIGNvbm5lY3QgYmFyIHNpemUuXHJcblx0Ly8gSW4gdGhlIGluaXRpYWwgZGVzaWduIGZvciB0aGlzIGZlYXR1cmUsIHRoZSBvcmlnaW4gZWxlbWVudCB3YXMgMSUgd2lkZS5cclxuXHQvLyBVbmZvcnR1bmF0ZWx5LCBhIHJvdW5kaW5nIGJ1ZyBpbiBDaHJvbWUgbWFrZXMgaXQgaW1wb3NzaWJsZSB0byBpbXBsZW1lbnQgdGhpcyBmZWF0dXJlXHJcblx0Ly8gaW4gdGhpcyBtYW5uZXI6IGh0dHBzOi8vYnVncy5jaHJvbWl1bS5vcmcvcC9jaHJvbWl1bS9pc3N1ZXMvZGV0YWlsP2lkPTc5ODIyM1xyXG5cdGZ1bmN0aW9uIHRyYW5zZm9ybURpcmVjdGlvbiAoIGEsIGIgKSB7XHJcblx0XHRyZXR1cm4gb3B0aW9ucy5kaXIgPyAxMDAgLSBhIC0gYiA6IGE7XHJcblx0fVxyXG5cclxuXHQvLyBVcGRhdGVzIHNjb3BlX0xvY2F0aW9ucyBhbmQgc2NvcGVfVmFsdWVzLCB1cGRhdGVzIHZpc3VhbCBzdGF0ZVxyXG5cdGZ1bmN0aW9uIHVwZGF0ZUhhbmRsZVBvc2l0aW9uICggaGFuZGxlTnVtYmVyLCB0byApIHtcclxuXHJcblx0XHQvLyBVcGRhdGUgbG9jYXRpb25zLlxyXG5cdFx0c2NvcGVfTG9jYXRpb25zW2hhbmRsZU51bWJlcl0gPSB0bztcclxuXHJcblx0XHQvLyBDb252ZXJ0IHRoZSB2YWx1ZSB0byB0aGUgc2xpZGVyIHN0ZXBwaW5nL3JhbmdlLlxyXG5cdFx0c2NvcGVfVmFsdWVzW2hhbmRsZU51bWJlcl0gPSBzY29wZV9TcGVjdHJ1bS5mcm9tU3RlcHBpbmcodG8pO1xyXG5cclxuXHRcdHZhciBydWxlID0gJ3RyYW5zbGF0ZSgnICsgaW5SdWxlT3JkZXIodG9QY3QodHJhbnNmb3JtRGlyZWN0aW9uKHRvLCAwKSAtIHNjb3BlX0Rpck9mZnNldCksICcwJykgKyAnKSc7XHJcblx0XHRzY29wZV9IYW5kbGVzW2hhbmRsZU51bWJlcl0uc3R5bGVbb3B0aW9ucy50cmFuc2Zvcm1SdWxlXSA9IHJ1bGU7XHJcblxyXG5cdFx0dXBkYXRlQ29ubmVjdChoYW5kbGVOdW1iZXIpO1xyXG5cdFx0dXBkYXRlQ29ubmVjdChoYW5kbGVOdW1iZXIgKyAxKTtcclxuXHR9XHJcblxyXG5cdC8vIEhhbmRsZXMgYmVmb3JlIHRoZSBzbGlkZXIgbWlkZGxlIGFyZSBzdGFja2VkIGxhdGVyID0gaGlnaGVyLFxyXG5cdC8vIEhhbmRsZXMgYWZ0ZXIgdGhlIG1pZGRsZSBsYXRlciBpcyBsb3dlclxyXG5cdC8vIFtbN10gWzhdIC4uLi4uLi4uLi4gfCAuLi4uLi4uLi4uIFs1XSBbNF1cclxuXHRmdW5jdGlvbiBzZXRaaW5kZXggKCApIHtcclxuXHJcblx0XHRzY29wZV9IYW5kbGVOdW1iZXJzLmZvckVhY2goZnVuY3Rpb24oaGFuZGxlTnVtYmVyKXtcclxuXHRcdFx0dmFyIGRpciA9IChzY29wZV9Mb2NhdGlvbnNbaGFuZGxlTnVtYmVyXSA+IDUwID8gLTEgOiAxKTtcclxuXHRcdFx0dmFyIHpJbmRleCA9IDMgKyAoc2NvcGVfSGFuZGxlcy5sZW5ndGggKyAoZGlyICogaGFuZGxlTnVtYmVyKSk7XHJcblx0XHRcdHNjb3BlX0hhbmRsZXNbaGFuZGxlTnVtYmVyXS5zdHlsZS56SW5kZXggPSB6SW5kZXg7XHJcblx0XHR9KTtcclxuXHR9XHJcblxyXG5cdC8vIFRlc3Qgc3VnZ2VzdGVkIHZhbHVlcyBhbmQgYXBwbHkgbWFyZ2luLCBzdGVwLlxyXG5cdGZ1bmN0aW9uIHNldEhhbmRsZSAoIGhhbmRsZU51bWJlciwgdG8sIGxvb2tCYWNrd2FyZCwgbG9va0ZvcndhcmQgKSB7XHJcblxyXG5cdFx0dG8gPSBjaGVja0hhbmRsZVBvc2l0aW9uKHNjb3BlX0xvY2F0aW9ucywgaGFuZGxlTnVtYmVyLCB0bywgbG9va0JhY2t3YXJkLCBsb29rRm9yd2FyZCwgZmFsc2UpO1xyXG5cclxuXHRcdGlmICggdG8gPT09IGZhbHNlICkge1xyXG5cdFx0XHRyZXR1cm4gZmFsc2U7XHJcblx0XHR9XHJcblxyXG5cdFx0dXBkYXRlSGFuZGxlUG9zaXRpb24oaGFuZGxlTnVtYmVyLCB0byk7XHJcblxyXG5cdFx0cmV0dXJuIHRydWU7XHJcblx0fVxyXG5cclxuXHQvLyBVcGRhdGVzIHN0eWxlIGF0dHJpYnV0ZSBmb3IgY29ubmVjdCBub2Rlc1xyXG5cdGZ1bmN0aW9uIHVwZGF0ZUNvbm5lY3QgKCBpbmRleCApIHtcclxuXHJcblx0XHQvLyBTa2lwIGNvbm5lY3RzIHNldCB0byBmYWxzZVxyXG5cdFx0aWYgKCAhc2NvcGVfQ29ubmVjdHNbaW5kZXhdICkge1xyXG5cdFx0XHRyZXR1cm47XHJcblx0XHR9XHJcblxyXG5cdFx0dmFyIGwgPSAwO1xyXG5cdFx0dmFyIGggPSAxMDA7XHJcblxyXG5cdFx0aWYgKCBpbmRleCAhPT0gMCApIHtcclxuXHRcdFx0bCA9IHNjb3BlX0xvY2F0aW9uc1tpbmRleCAtIDFdO1xyXG5cdFx0fVxyXG5cclxuXHRcdGlmICggaW5kZXggIT09IHNjb3BlX0Nvbm5lY3RzLmxlbmd0aCAtIDEgKSB7XHJcblx0XHRcdGggPSBzY29wZV9Mb2NhdGlvbnNbaW5kZXhdO1xyXG5cdFx0fVxyXG5cclxuXHRcdC8vIFdlIHVzZSB0d28gcnVsZXM6XHJcblx0XHQvLyAndHJhbnNsYXRlJyB0byBjaGFuZ2UgdGhlIGxlZnQvdG9wIG9mZnNldDtcclxuXHRcdC8vICdzY2FsZScgdG8gY2hhbmdlIHRoZSB3aWR0aCBvZiB0aGUgZWxlbWVudDtcclxuXHRcdC8vIEFzIHRoZSBlbGVtZW50IGhhcyBhIHdpZHRoIG9mIDEwMCUsIGEgdHJhbnNsYXRpb24gb2YgMTAwJSBpcyBlcXVhbCB0byAxMDAlIG9mIHRoZSBwYXJlbnQgKC5ub1VpLWJhc2UpXHJcblx0XHR2YXIgY29ubmVjdFdpZHRoID0gaCAtIGw7XHJcblx0XHR2YXIgdHJhbnNsYXRlUnVsZSA9ICd0cmFuc2xhdGUoJyArIGluUnVsZU9yZGVyKHRvUGN0KHRyYW5zZm9ybURpcmVjdGlvbihsLCBjb25uZWN0V2lkdGgpKSwgJzAnKSArICcpJztcclxuXHRcdHZhciBzY2FsZVJ1bGUgPSAnc2NhbGUoJyArIGluUnVsZU9yZGVyKGNvbm5lY3RXaWR0aCAvIDEwMCwgJzEnKSArICcpJztcclxuXHJcblx0XHRzY29wZV9Db25uZWN0c1tpbmRleF0uc3R5bGVbb3B0aW9ucy50cmFuc2Zvcm1SdWxlXSA9IHRyYW5zbGF0ZVJ1bGUgKyAnICcgKyBzY2FsZVJ1bGU7XHJcblx0fVxyXG5cclxuLyohIEluIHRoaXMgZmlsZTogQWxsIG1ldGhvZHMgZXZlbnR1YWxseSBleHBvc2VkIGluIHNsaWRlci5ub1VpU2xpZGVyLi4uICovXHJcblxyXG5cdC8vIFBhcnNlcyB2YWx1ZSBwYXNzZWQgdG8gLnNldCBtZXRob2QuIFJldHVybnMgY3VycmVudCB2YWx1ZSBpZiBub3QgcGFyc2UtYWJsZS5cclxuXHRmdW5jdGlvbiByZXNvbHZlVG9WYWx1ZSAoIHRvLCBoYW5kbGVOdW1iZXIgKSB7XHJcblxyXG5cdFx0Ly8gU2V0dGluZyB3aXRoIG51bGwgaW5kaWNhdGVzIGFuICdpZ25vcmUnLlxyXG5cdFx0Ly8gSW5wdXR0aW5nICdmYWxzZScgaXMgaW52YWxpZC5cclxuXHRcdGlmICggdG8gPT09IG51bGwgfHwgdG8gPT09IGZhbHNlIHx8IHRvID09PSB1bmRlZmluZWQgKSB7XHJcblx0XHRcdHJldHVybiBzY29wZV9Mb2NhdGlvbnNbaGFuZGxlTnVtYmVyXTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBJZiBhIGZvcm1hdHRlZCBudW1iZXIgd2FzIHBhc3NlZCwgYXR0ZW1wdCB0byBkZWNvZGUgaXQuXHJcblx0XHRpZiAoIHR5cGVvZiB0byA9PT0gJ251bWJlcicgKSB7XHJcblx0XHRcdHRvID0gU3RyaW5nKHRvKTtcclxuXHRcdH1cclxuXHJcblx0XHR0byA9IG9wdGlvbnMuZm9ybWF0LmZyb20odG8pO1xyXG5cdFx0dG8gPSBzY29wZV9TcGVjdHJ1bS50b1N0ZXBwaW5nKHRvKTtcclxuXHJcblx0XHQvLyBJZiBwYXJzaW5nIHRoZSBudW1iZXIgZmFpbGVkLCB1c2UgdGhlIGN1cnJlbnQgdmFsdWUuXHJcblx0XHRpZiAoIHRvID09PSBmYWxzZSB8fCBpc05hTih0bykgKSB7XHJcblx0XHRcdHJldHVybiBzY29wZV9Mb2NhdGlvbnNbaGFuZGxlTnVtYmVyXTtcclxuXHRcdH1cclxuXHJcblx0XHRyZXR1cm4gdG87XHJcblx0fVxyXG5cclxuXHQvLyBTZXQgdGhlIHNsaWRlciB2YWx1ZS5cclxuXHRmdW5jdGlvbiB2YWx1ZVNldCAoIGlucHV0LCBmaXJlU2V0RXZlbnQgKSB7XHJcblxyXG5cdFx0dmFyIHZhbHVlcyA9IGFzQXJyYXkoaW5wdXQpO1xyXG5cdFx0dmFyIGlzSW5pdCA9IHNjb3BlX0xvY2F0aW9uc1swXSA9PT0gdW5kZWZpbmVkO1xyXG5cclxuXHRcdC8vIEV2ZW50IGZpcmVzIGJ5IGRlZmF1bHRcclxuXHRcdGZpcmVTZXRFdmVudCA9IChmaXJlU2V0RXZlbnQgPT09IHVuZGVmaW5lZCA/IHRydWUgOiAhIWZpcmVTZXRFdmVudCk7XHJcblxyXG5cdFx0Ly8gQW5pbWF0aW9uIGlzIG9wdGlvbmFsLlxyXG5cdFx0Ly8gTWFrZSBzdXJlIHRoZSBpbml0aWFsIHZhbHVlcyB3ZXJlIHNldCBiZWZvcmUgdXNpbmcgYW5pbWF0ZWQgcGxhY2VtZW50LlxyXG5cdFx0aWYgKCBvcHRpb25zLmFuaW1hdGUgJiYgIWlzSW5pdCApIHtcclxuXHRcdFx0YWRkQ2xhc3NGb3Ioc2NvcGVfVGFyZ2V0LCBvcHRpb25zLmNzc0NsYXNzZXMudGFwLCBvcHRpb25zLmFuaW1hdGlvbkR1cmF0aW9uKTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBGaXJzdCBwYXNzLCB3aXRob3V0IGxvb2tBaGVhZCBidXQgd2l0aCBsb29rQmFja3dhcmQuIFZhbHVlcyBhcmUgc2V0IGZyb20gbGVmdCB0byByaWdodC5cclxuXHRcdHNjb3BlX0hhbmRsZU51bWJlcnMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGVOdW1iZXIpe1xyXG5cdFx0XHRzZXRIYW5kbGUoaGFuZGxlTnVtYmVyLCByZXNvbHZlVG9WYWx1ZSh2YWx1ZXNbaGFuZGxlTnVtYmVyXSwgaGFuZGxlTnVtYmVyKSwgdHJ1ZSwgZmFsc2UpO1xyXG5cdFx0fSk7XHJcblxyXG5cdFx0Ly8gU2Vjb25kIHBhc3MuIE5vdyB0aGF0IGFsbCBiYXNlIHZhbHVlcyBhcmUgc2V0LCBhcHBseSBjb25zdHJhaW50c1xyXG5cdFx0c2NvcGVfSGFuZGxlTnVtYmVycy5mb3JFYWNoKGZ1bmN0aW9uKGhhbmRsZU51bWJlcil7XHJcblx0XHRcdHNldEhhbmRsZShoYW5kbGVOdW1iZXIsIHNjb3BlX0xvY2F0aW9uc1toYW5kbGVOdW1iZXJdLCB0cnVlLCB0cnVlKTtcclxuXHRcdH0pO1xyXG5cclxuXHRcdHNldFppbmRleCgpO1xyXG5cclxuXHRcdHNjb3BlX0hhbmRsZU51bWJlcnMuZm9yRWFjaChmdW5jdGlvbihoYW5kbGVOdW1iZXIpe1xyXG5cclxuXHRcdFx0ZmlyZUV2ZW50KCd1cGRhdGUnLCBoYW5kbGVOdW1iZXIpO1xyXG5cclxuXHRcdFx0Ly8gRmlyZSB0aGUgZXZlbnQgb25seSBmb3IgaGFuZGxlcyB0aGF0IHJlY2VpdmVkIGEgbmV3IHZhbHVlLCBhcyBwZXIgIzU3OVxyXG5cdFx0XHRpZiAoIHZhbHVlc1toYW5kbGVOdW1iZXJdICE9PSBudWxsICYmIGZpcmVTZXRFdmVudCApIHtcclxuXHRcdFx0XHRmaXJlRXZlbnQoJ3NldCcsIGhhbmRsZU51bWJlcik7XHJcblx0XHRcdH1cclxuXHRcdH0pO1xyXG5cdH1cclxuXHJcblx0Ly8gUmVzZXQgc2xpZGVyIHRvIGluaXRpYWwgdmFsdWVzXHJcblx0ZnVuY3Rpb24gdmFsdWVSZXNldCAoIGZpcmVTZXRFdmVudCApIHtcclxuXHRcdHZhbHVlU2V0KG9wdGlvbnMuc3RhcnQsIGZpcmVTZXRFdmVudCk7XHJcblx0fVxyXG5cclxuXHQvLyBHZXQgdGhlIHNsaWRlciB2YWx1ZS5cclxuXHRmdW5jdGlvbiB2YWx1ZUdldCAoICkge1xyXG5cclxuXHRcdHZhciB2YWx1ZXMgPSBzY29wZV9WYWx1ZXMubWFwKG9wdGlvbnMuZm9ybWF0LnRvKTtcclxuXHJcblx0XHQvLyBJZiBvbmx5IG9uZSBoYW5kbGUgaXMgdXNlZCwgcmV0dXJuIGEgc2luZ2xlIHZhbHVlLlxyXG5cdFx0aWYgKCB2YWx1ZXMubGVuZ3RoID09PSAxICl7XHJcblx0XHRcdHJldHVybiB2YWx1ZXNbMF07XHJcblx0XHR9XHJcblxyXG5cdFx0cmV0dXJuIHZhbHVlcztcclxuXHR9XHJcblxyXG5cdC8vIFJlbW92ZXMgY2xhc3NlcyBmcm9tIHRoZSByb290IGFuZCBlbXB0aWVzIGl0LlxyXG5cdGZ1bmN0aW9uIGRlc3Ryb3kgKCApIHtcclxuXHJcblx0XHRmb3IgKCB2YXIga2V5IGluIG9wdGlvbnMuY3NzQ2xhc3NlcyApIHtcclxuXHRcdFx0aWYgKCAhb3B0aW9ucy5jc3NDbGFzc2VzLmhhc093blByb3BlcnR5KGtleSkgKSB7IGNvbnRpbnVlOyB9XHJcblx0XHRcdHJlbW92ZUNsYXNzKHNjb3BlX1RhcmdldCwgb3B0aW9ucy5jc3NDbGFzc2VzW2tleV0pO1xyXG5cdFx0fVxyXG5cclxuXHRcdHdoaWxlIChzY29wZV9UYXJnZXQuZmlyc3RDaGlsZCkge1xyXG5cdFx0XHRzY29wZV9UYXJnZXQucmVtb3ZlQ2hpbGQoc2NvcGVfVGFyZ2V0LmZpcnN0Q2hpbGQpO1xyXG5cdFx0fVxyXG5cclxuXHRcdGRlbGV0ZSBzY29wZV9UYXJnZXQubm9VaVNsaWRlcjtcclxuXHR9XHJcblxyXG5cdC8vIEdldCB0aGUgY3VycmVudCBzdGVwIHNpemUgZm9yIHRoZSBzbGlkZXIuXHJcblx0ZnVuY3Rpb24gZ2V0Q3VycmVudFN0ZXAgKCApIHtcclxuXHJcblx0XHQvLyBDaGVjayBhbGwgbG9jYXRpb25zLCBtYXAgdGhlbSB0byB0aGVpciBzdGVwcGluZyBwb2ludC5cclxuXHRcdC8vIEdldCB0aGUgc3RlcCBwb2ludCwgdGhlbiBmaW5kIGl0IGluIHRoZSBpbnB1dCBsaXN0LlxyXG5cdFx0cmV0dXJuIHNjb3BlX0xvY2F0aW9ucy5tYXAoZnVuY3Rpb24oIGxvY2F0aW9uLCBpbmRleCApe1xyXG5cclxuXHRcdFx0dmFyIG5lYXJieVN0ZXBzID0gc2NvcGVfU3BlY3RydW0uZ2V0TmVhcmJ5U3RlcHMoIGxvY2F0aW9uICk7XHJcblx0XHRcdHZhciB2YWx1ZSA9IHNjb3BlX1ZhbHVlc1tpbmRleF07XHJcblx0XHRcdHZhciBpbmNyZW1lbnQgPSBuZWFyYnlTdGVwcy50aGlzU3RlcC5zdGVwO1xyXG5cdFx0XHR2YXIgZGVjcmVtZW50ID0gbnVsbDtcclxuXHJcblx0XHRcdC8vIElmIHRoZSBuZXh0IHZhbHVlIGluIHRoaXMgc3RlcCBtb3ZlcyBpbnRvIHRoZSBuZXh0IHN0ZXAsXHJcblx0XHRcdC8vIHRoZSBpbmNyZW1lbnQgaXMgdGhlIHN0YXJ0IG9mIHRoZSBuZXh0IHN0ZXAgLSB0aGUgY3VycmVudCB2YWx1ZVxyXG5cdFx0XHRpZiAoIGluY3JlbWVudCAhPT0gZmFsc2UgKSB7XHJcblx0XHRcdFx0aWYgKCB2YWx1ZSArIGluY3JlbWVudCA+IG5lYXJieVN0ZXBzLnN0ZXBBZnRlci5zdGFydFZhbHVlICkge1xyXG5cdFx0XHRcdFx0aW5jcmVtZW50ID0gbmVhcmJ5U3RlcHMuc3RlcEFmdGVyLnN0YXJ0VmFsdWUgLSB2YWx1ZTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHJcblxyXG5cdFx0XHQvLyBJZiB0aGUgdmFsdWUgaXMgYmV5b25kIHRoZSBzdGFydGluZyBwb2ludFxyXG5cdFx0XHRpZiAoIHZhbHVlID4gbmVhcmJ5U3RlcHMudGhpc1N0ZXAuc3RhcnRWYWx1ZSApIHtcclxuXHRcdFx0XHRkZWNyZW1lbnQgPSBuZWFyYnlTdGVwcy50aGlzU3RlcC5zdGVwO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHRlbHNlIGlmICggbmVhcmJ5U3RlcHMuc3RlcEJlZm9yZS5zdGVwID09PSBmYWxzZSApIHtcclxuXHRcdFx0XHRkZWNyZW1lbnQgPSBmYWxzZTtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0Ly8gSWYgYSBoYW5kbGUgaXMgYXQgdGhlIHN0YXJ0IG9mIGEgc3RlcCwgaXQgYWx3YXlzIHN0ZXBzIGJhY2sgaW50byB0aGUgcHJldmlvdXMgc3RlcCBmaXJzdFxyXG5cdFx0XHRlbHNlIHtcclxuXHRcdFx0XHRkZWNyZW1lbnQgPSB2YWx1ZSAtIG5lYXJieVN0ZXBzLnN0ZXBCZWZvcmUuaGlnaGVzdFN0ZXA7XHJcblx0XHRcdH1cclxuXHJcblxyXG5cdFx0XHQvLyBOb3csIGlmIGF0IHRoZSBzbGlkZXIgZWRnZXMsIHRoZXJlIGlzIG5vdCBpbi9kZWNyZW1lbnRcclxuXHRcdFx0aWYgKCBsb2NhdGlvbiA9PT0gMTAwICkge1xyXG5cdFx0XHRcdGluY3JlbWVudCA9IG51bGw7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdGVsc2UgaWYgKCBsb2NhdGlvbiA9PT0gMCApIHtcclxuXHRcdFx0XHRkZWNyZW1lbnQgPSBudWxsO1xyXG5cdFx0XHR9XHJcblxyXG5cdFx0XHQvLyBBcyBwZXIgIzM5MSwgdGhlIGNvbXBhcmlzb24gZm9yIHRoZSBkZWNyZW1lbnQgc3RlcCBjYW4gaGF2ZSBzb21lIHJvdW5kaW5nIGlzc3Vlcy5cclxuXHRcdFx0dmFyIHN0ZXBEZWNpbWFscyA9IHNjb3BlX1NwZWN0cnVtLmNvdW50U3RlcERlY2ltYWxzKCk7XHJcblxyXG5cdFx0XHQvLyBSb3VuZCBwZXIgIzM5MVxyXG5cdFx0XHRpZiAoIGluY3JlbWVudCAhPT0gbnVsbCAmJiBpbmNyZW1lbnQgIT09IGZhbHNlICkge1xyXG5cdFx0XHRcdGluY3JlbWVudCA9IE51bWJlcihpbmNyZW1lbnQudG9GaXhlZChzdGVwRGVjaW1hbHMpKTtcclxuXHRcdFx0fVxyXG5cclxuXHRcdFx0aWYgKCBkZWNyZW1lbnQgIT09IG51bGwgJiYgZGVjcmVtZW50ICE9PSBmYWxzZSApIHtcclxuXHRcdFx0XHRkZWNyZW1lbnQgPSBOdW1iZXIoZGVjcmVtZW50LnRvRml4ZWQoc3RlcERlY2ltYWxzKSk7XHJcblx0XHRcdH1cclxuXHJcblx0XHRcdHJldHVybiBbZGVjcmVtZW50LCBpbmNyZW1lbnRdO1xyXG5cdFx0fSk7XHJcblx0fVxyXG5cclxuXHQvLyBVcGRhdGVhYmxlOiBtYXJnaW4sIGxpbWl0LCBwYWRkaW5nLCBzdGVwLCByYW5nZSwgYW5pbWF0ZSwgc25hcFxyXG5cdGZ1bmN0aW9uIHVwZGF0ZU9wdGlvbnMgKCBvcHRpb25zVG9VcGRhdGUsIGZpcmVTZXRFdmVudCApIHtcclxuXHJcblx0XHQvLyBTcGVjdHJ1bSBpcyBjcmVhdGVkIHVzaW5nIHRoZSByYW5nZSwgc25hcCwgZGlyZWN0aW9uIGFuZCBzdGVwIG9wdGlvbnMuXHJcblx0XHQvLyAnc25hcCcgYW5kICdzdGVwJyBjYW4gYmUgdXBkYXRlZC5cclxuXHRcdC8vIElmICdzbmFwJyBhbmQgJ3N0ZXAnIGFyZSBub3QgcGFzc2VkLCB0aGV5IHNob3VsZCByZW1haW4gdW5jaGFuZ2VkLlxyXG5cdFx0dmFyIHYgPSB2YWx1ZUdldCgpO1xyXG5cclxuXHRcdHZhciB1cGRhdGVBYmxlID0gWydtYXJnaW4nLCAnbGltaXQnLCAncGFkZGluZycsICdyYW5nZScsICdhbmltYXRlJywgJ3NuYXAnLCAnc3RlcCcsICdmb3JtYXQnXTtcclxuXHJcblx0XHQvLyBPbmx5IGNoYW5nZSBvcHRpb25zIHRoYXQgd2UncmUgYWN0dWFsbHkgcGFzc2VkIHRvIHVwZGF0ZS5cclxuXHRcdHVwZGF0ZUFibGUuZm9yRWFjaChmdW5jdGlvbihuYW1lKXtcclxuXHRcdFx0aWYgKCBvcHRpb25zVG9VcGRhdGVbbmFtZV0gIT09IHVuZGVmaW5lZCApIHtcclxuXHRcdFx0XHRvcmlnaW5hbE9wdGlvbnNbbmFtZV0gPSBvcHRpb25zVG9VcGRhdGVbbmFtZV07XHJcblx0XHRcdH1cclxuXHRcdH0pO1xyXG5cclxuXHRcdHZhciBuZXdPcHRpb25zID0gdGVzdE9wdGlvbnMob3JpZ2luYWxPcHRpb25zKTtcclxuXHJcblx0XHQvLyBMb2FkIG5ldyBvcHRpb25zIGludG8gdGhlIHNsaWRlciBzdGF0ZVxyXG5cdFx0dXBkYXRlQWJsZS5mb3JFYWNoKGZ1bmN0aW9uKG5hbWUpe1xyXG5cdFx0XHRpZiAoIG9wdGlvbnNUb1VwZGF0ZVtuYW1lXSAhPT0gdW5kZWZpbmVkICkge1xyXG5cdFx0XHRcdG9wdGlvbnNbbmFtZV0gPSBuZXdPcHRpb25zW25hbWVdO1xyXG5cdFx0XHR9XHJcblx0XHR9KTtcclxuXHJcblx0XHRzY29wZV9TcGVjdHJ1bSA9IG5ld09wdGlvbnMuc3BlY3RydW07XHJcblxyXG5cdFx0Ly8gTGltaXQsIG1hcmdpbiBhbmQgcGFkZGluZyBkZXBlbmQgb24gdGhlIHNwZWN0cnVtIGJ1dCBhcmUgc3RvcmVkIG91dHNpZGUgb2YgaXQuICgjNjc3KVxyXG5cdFx0b3B0aW9ucy5tYXJnaW4gPSBuZXdPcHRpb25zLm1hcmdpbjtcclxuXHRcdG9wdGlvbnMubGltaXQgPSBuZXdPcHRpb25zLmxpbWl0O1xyXG5cdFx0b3B0aW9ucy5wYWRkaW5nID0gbmV3T3B0aW9ucy5wYWRkaW5nO1xyXG5cclxuXHRcdC8vIFVwZGF0ZSBwaXBzLCByZW1vdmVzIGV4aXN0aW5nLlxyXG5cdFx0aWYgKCBvcHRpb25zLnBpcHMgKSB7XHJcblx0XHRcdHBpcHMob3B0aW9ucy5waXBzKTtcclxuXHRcdH1cclxuXHJcblx0XHQvLyBJbnZhbGlkYXRlIHRoZSBjdXJyZW50IHBvc2l0aW9uaW5nIHNvIHZhbHVlU2V0IGZvcmNlcyBhbiB1cGRhdGUuXHJcblx0XHRzY29wZV9Mb2NhdGlvbnMgPSBbXTtcclxuXHRcdHZhbHVlU2V0KG9wdGlvbnNUb1VwZGF0ZS5zdGFydCB8fCB2LCBmaXJlU2V0RXZlbnQpO1xyXG5cdH1cclxuXHJcbi8qISBJbiB0aGlzIGZpbGU6IENhbGxzIHRvIGZ1bmN0aW9ucy4gQWxsIG90aGVyIHNjb3BlXyBmaWxlcyBkZWZpbmUgZnVuY3Rpb25zIG9ubHk7ICovXHJcblxyXG5cdC8vIENyZWF0ZSB0aGUgYmFzZSBlbGVtZW50LCBpbml0aWFsaXplIEhUTUwgYW5kIHNldCBjbGFzc2VzLlxyXG5cdC8vIEFkZCBoYW5kbGVzIGFuZCBjb25uZWN0IGVsZW1lbnRzLlxyXG5cdGFkZFNsaWRlcihzY29wZV9UYXJnZXQpO1xyXG5cdGFkZEVsZW1lbnRzKG9wdGlvbnMuY29ubmVjdCwgc2NvcGVfQmFzZSk7XHJcblxyXG5cdC8vIEF0dGFjaCB1c2VyIGV2ZW50cy5cclxuXHRiaW5kU2xpZGVyRXZlbnRzKG9wdGlvbnMuZXZlbnRzKTtcclxuXHJcblx0Ly8gVXNlIHRoZSBwdWJsaWMgdmFsdWUgbWV0aG9kIHRvIHNldCB0aGUgc3RhcnQgdmFsdWVzLlxyXG5cdHZhbHVlU2V0KG9wdGlvbnMuc3RhcnQpO1xyXG5cclxuXHRzY29wZV9TZWxmID0ge1xyXG5cdFx0ZGVzdHJveTogZGVzdHJveSxcclxuXHRcdHN0ZXBzOiBnZXRDdXJyZW50U3RlcCxcclxuXHRcdG9uOiBiaW5kRXZlbnQsXHJcblx0XHRvZmY6IHJlbW92ZUV2ZW50LFxyXG5cdFx0Z2V0OiB2YWx1ZUdldCxcclxuXHRcdHNldDogdmFsdWVTZXQsXHJcblx0XHRyZXNldDogdmFsdWVSZXNldCxcclxuXHRcdC8vIEV4cG9zZWQgZm9yIHVuaXQgdGVzdGluZywgZG9uJ3QgdXNlIHRoaXMgaW4geW91ciBhcHBsaWNhdGlvbi5cclxuXHRcdF9fbW92ZUhhbmRsZXM6IGZ1bmN0aW9uKGEsIGIsIGMpIHsgbW92ZUhhbmRsZXMoYSwgYiwgc2NvcGVfTG9jYXRpb25zLCBjKTsgfSxcclxuXHRcdG9wdGlvbnM6IG9yaWdpbmFsT3B0aW9ucywgLy8gSXNzdWUgIzYwMCwgIzY3OFxyXG5cdFx0dXBkYXRlT3B0aW9uczogdXBkYXRlT3B0aW9ucyxcclxuXHRcdHRhcmdldDogc2NvcGVfVGFyZ2V0LCAvLyBJc3N1ZSAjNTk3XHJcblx0XHRyZW1vdmVQaXBzOiByZW1vdmVQaXBzLFxyXG5cdFx0cGlwczogcGlwcyAvLyBJc3N1ZSAjNTk0XHJcblx0fTtcclxuXHJcblx0aWYgKCBvcHRpb25zLnBpcHMgKSB7XHJcblx0XHRwaXBzKG9wdGlvbnMucGlwcyk7XHJcblx0fVxyXG5cclxuXHRpZiAoIG9wdGlvbnMudG9vbHRpcHMgKSB7XHJcblx0XHR0b29sdGlwcygpO1xyXG5cdH1cclxuXHJcblx0YXJpYSgpO1xyXG5cclxuXHRyZXR1cm4gc2NvcGVfU2VsZjtcclxuXHJcbn1cclxuXHJcblxyXG5cdC8vIFJ1biB0aGUgc3RhbmRhcmQgaW5pdGlhbGl6ZXJcclxuXHRmdW5jdGlvbiBpbml0aWFsaXplICggdGFyZ2V0LCBvcmlnaW5hbE9wdGlvbnMgKSB7XHJcblxyXG5cdFx0aWYgKCAhdGFyZ2V0IHx8ICF0YXJnZXQubm9kZU5hbWUgKSB7XHJcblx0XHRcdHRocm93IG5ldyBFcnJvcihcIm5vVWlTbGlkZXIgKFwiICsgVkVSU0lPTiArIFwiKTogY3JlYXRlIHJlcXVpcmVzIGEgc2luZ2xlIGVsZW1lbnQsIGdvdDogXCIgKyB0YXJnZXQpO1xyXG5cdFx0fVxyXG5cclxuXHRcdC8vIFRocm93IGFuIGVycm9yIGlmIHRoZSBzbGlkZXIgd2FzIGFscmVhZHkgaW5pdGlhbGl6ZWQuXHJcblx0XHRpZiAoIHRhcmdldC5ub1VpU2xpZGVyICkge1xyXG5cdFx0XHR0aHJvdyBuZXcgRXJyb3IoXCJub1VpU2xpZGVyIChcIiArIFZFUlNJT04gKyBcIik6IFNsaWRlciB3YXMgYWxyZWFkeSBpbml0aWFsaXplZC5cIik7XHJcblx0XHR9XHJcblxyXG5cdFx0Ly8gVGVzdCB0aGUgb3B0aW9ucyBhbmQgY3JlYXRlIHRoZSBzbGlkZXIgZW52aXJvbm1lbnQ7XHJcblx0XHR2YXIgb3B0aW9ucyA9IHRlc3RPcHRpb25zKCBvcmlnaW5hbE9wdGlvbnMsIHRhcmdldCApO1xyXG5cdFx0dmFyIGFwaSA9IHNjb3BlKCB0YXJnZXQsIG9wdGlvbnMsIG9yaWdpbmFsT3B0aW9ucyApO1xyXG5cclxuXHRcdHRhcmdldC5ub1VpU2xpZGVyID0gYXBpO1xyXG5cclxuXHRcdHJldHVybiBhcGk7XHJcblx0fVxyXG5cclxuXHQvLyBVc2UgYW4gb2JqZWN0IGluc3RlYWQgb2YgYSBmdW5jdGlvbiBmb3IgZnV0dXJlIGV4cGFuZGFiaWxpdHk7XHJcblx0cmV0dXJuIHtcclxuXHRcdHZlcnNpb246IFZFUlNJT04sXHJcblx0XHRjcmVhdGU6IGluaXRpYWxpemVcclxuXHR9O1xyXG5cclxufSkpOyIsIihmdW5jdGlvbiAoZ2xvYmFsKXtcblxyXG52YXIgJCBcdFx0XHRcdD0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKTtcclxudmFyIHN0YXRlIFx0XHRcdD0gcmVxdWlyZSgnLi9zdGF0ZScpO1xyXG52YXIgcHJvY2Vzc19mb3JtIFx0PSByZXF1aXJlKCcuL3Byb2Nlc3NfZm9ybScpO1xyXG52YXIgbm9VaVNsaWRlclx0XHQ9IHJlcXVpcmUoJ25vdWlzbGlkZXInKTtcclxuLy92YXIgY29va2llcyAgICAgICAgID0gcmVxdWlyZSgnanMtY29va2llJyk7XHJcbnZhciB0aGlyZFBhcnR5ICAgICAgPSByZXF1aXJlKCcuL3RoaXJkcGFydHknKTtcclxuXHJcbndpbmRvdy5zZWFyY2hBbmRGaWx0ZXIgPSB7XHJcbiAgICBleHRlbnNpb25zOiBbXSxcclxuICAgIHJlZ2lzdGVyRXh0ZW5zaW9uOiBmdW5jdGlvbiggZXh0ZW5zaW9uTmFtZSApIHtcclxuICAgICAgICB0aGlzLmV4dGVuc2lvbnMucHVzaCggZXh0ZW5zaW9uTmFtZSApO1xyXG4gICAgfVxyXG59O1xyXG5cclxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbihvcHRpb25zKVxyXG57XHJcbiAgICB2YXIgZGVmYXVsdHMgPSB7XHJcbiAgICAgICAgc3RhcnRPcGVuZWQ6IGZhbHNlLFxyXG4gICAgICAgIGlzSW5pdDogdHJ1ZSxcclxuICAgICAgICBhY3Rpb246IFwiXCJcclxuICAgIH07XHJcblxyXG4gICAgdmFyIG9wdHMgPSBqUXVlcnkuZXh0ZW5kKGRlZmF1bHRzLCBvcHRpb25zKTtcclxuICAgIFxyXG4gICAgdGhpcmRQYXJ0eS5pbml0KCk7XHJcbiAgICBcclxuICAgIC8vbG9vcCB0aHJvdWdoIGVhY2ggaXRlbSBtYXRjaGVkXHJcbiAgICB0aGlzLmVhY2goZnVuY3Rpb24oKVxyXG4gICAge1xyXG5cclxuICAgICAgICB2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG4gICAgICAgIHZhciBzZWxmID0gdGhpcztcclxuICAgICAgICB0aGlzLnNmaWQgPSAkdGhpcy5hdHRyKFwiZGF0YS1zZi1mb3JtLWlkXCIpO1xyXG5cclxuICAgICAgICBzdGF0ZS5hZGRTZWFyY2hGb3JtKHRoaXMuc2ZpZCwgdGhpcyk7XHJcblxyXG4gICAgICAgIHRoaXMuJGZpZWxkcyA9ICR0aGlzLmZpbmQoXCI+IHVsID4gbGlcIik7IC8vYSByZWZlcmVuY2UgdG8gZWFjaCBmaWVsZHMgcGFyZW50IExJXHJcblxyXG4gICAgICAgIHRoaXMuZW5hYmxlX3RheG9ub215X2FyY2hpdmVzID0gJHRoaXMuYXR0cignZGF0YS10YXhvbm9teS1hcmNoaXZlcycpO1xyXG4gICAgICAgIHRoaXMuY3VycmVudF90YXhvbm9teV9hcmNoaXZlID0gJHRoaXMuYXR0cignZGF0YS1jdXJyZW50LXRheG9ub215LWFyY2hpdmUnKTtcclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuZW5hYmxlX3RheG9ub215X2FyY2hpdmVzKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuZW5hYmxlX3RheG9ub215X2FyY2hpdmVzID0gXCIwXCI7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBwcm9jZXNzX2Zvcm0uaW5pdChzZWxmLmVuYWJsZV90YXhvbm9teV9hcmNoaXZlcywgc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUpO1xyXG4gICAgICAgIC8vcHJvY2Vzc19mb3JtLnNldFRheEFyY2hpdmVSZXN1bHRzVXJsKHNlbGYpO1xyXG4gICAgICAgIHByb2Nlc3NfZm9ybS5lbmFibGVJbnB1dHMoc2VsZik7XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmV4dHJhX3F1ZXJ5X3BhcmFtcyk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmV4dHJhX3F1ZXJ5X3BhcmFtcyA9IHthbGw6IHt9LCByZXN1bHRzOiB7fSwgYWpheDoge319O1xyXG4gICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgIHRoaXMudGVtcGxhdGVfaXNfbG9hZGVkID0gJHRoaXMuYXR0cihcImRhdGEtdGVtcGxhdGUtbG9hZGVkXCIpO1xyXG4gICAgICAgIHRoaXMuaXNfYWpheCA9ICR0aGlzLmF0dHIoXCJkYXRhLWFqYXhcIik7XHJcbiAgICAgICAgdGhpcy5pbnN0YW5jZV9udW1iZXIgPSAkdGhpcy5hdHRyKCdkYXRhLWluc3RhbmNlLWNvdW50Jyk7XHJcbiAgICAgICAgdGhpcy4kYWpheF9yZXN1bHRzX2NvbnRhaW5lciA9IGpRdWVyeSgkdGhpcy5hdHRyKFwiZGF0YS1hamF4LXRhcmdldFwiKSk7XHJcblxyXG4gICAgICAgIHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnMgPSAkdGhpcy5hdHRyKFwiZGF0YS1hamF4LXVwZGF0ZS1zZWN0aW9uc1wiKSA/IEpTT04ucGFyc2UoICR0aGlzLmF0dHIoXCJkYXRhLWFqYXgtdXBkYXRlLXNlY3Rpb25zXCIpICkgOiBbXTtcclxuICAgICAgICB0aGlzLnJlcGxhY2VfcmVzdWx0cyA9ICR0aGlzLmF0dHIoXCJkYXRhLXJlcGxhY2UtcmVzdWx0c1wiKSA9PT0gXCIwXCIgPyBmYWxzZSA6IHRydWU7XHJcbiAgICAgICAgXHJcbiAgICAgICAgdGhpcy5yZXN1bHRzX3VybCA9ICR0aGlzLmF0dHIoXCJkYXRhLXJlc3VsdHMtdXJsXCIpO1xyXG4gICAgICAgIHRoaXMuZGVidWdfbW9kZSA9ICR0aGlzLmF0dHIoXCJkYXRhLWRlYnVnLW1vZGVcIik7XHJcbiAgICAgICAgdGhpcy51cGRhdGVfYWpheF91cmwgPSAkdGhpcy5hdHRyKFwiZGF0YS11cGRhdGUtYWpheC11cmxcIik7XHJcbiAgICAgICAgdGhpcy5wYWdpbmF0aW9uX3R5cGUgPSAkdGhpcy5hdHRyKFwiZGF0YS1hamF4LXBhZ2luYXRpb24tdHlwZVwiKTtcclxuICAgICAgICB0aGlzLmF1dG9fY291bnQgPSAkdGhpcy5hdHRyKFwiZGF0YS1hdXRvLWNvdW50XCIpO1xyXG4gICAgICAgIHRoaXMuYXV0b19jb3VudF9yZWZyZXNoX21vZGUgPSAkdGhpcy5hdHRyKFwiZGF0YS1hdXRvLWNvdW50LXJlZnJlc2gtbW9kZVwiKTtcclxuICAgICAgICB0aGlzLm9ubHlfcmVzdWx0c19hamF4ID0gJHRoaXMuYXR0cihcImRhdGEtb25seS1yZXN1bHRzLWFqYXhcIik7IC8vaWYgd2UgYXJlIG5vdCBvbiB0aGUgcmVzdWx0cyBwYWdlLCByZWRpcmVjdCByYXRoZXIgdGhhbiB0cnkgdG8gbG9hZCB2aWEgYWpheFxyXG4gICAgICAgIHRoaXMuc2Nyb2xsX3RvX3BvcyA9ICR0aGlzLmF0dHIoXCJkYXRhLXNjcm9sbC10by1wb3NcIik7XHJcbiAgICAgICAgdGhpcy5jdXN0b21fc2Nyb2xsX3RvID0gJHRoaXMuYXR0cihcImRhdGEtY3VzdG9tLXNjcm9sbC10b1wiKTtcclxuICAgICAgICB0aGlzLnNjcm9sbF9vbl9hY3Rpb24gPSAkdGhpcy5hdHRyKFwiZGF0YS1zY3JvbGwtb24tYWN0aW9uXCIpO1xyXG4gICAgICAgIHRoaXMubGFuZ19jb2RlID0gJHRoaXMuYXR0cihcImRhdGEtbGFuZy1jb2RlXCIpO1xyXG4gICAgICAgIHRoaXMuYWpheF91cmwgPSAkdGhpcy5hdHRyKCdkYXRhLWFqYXgtdXJsJyk7XHJcbiAgICAgICAgdGhpcy5hamF4X2Zvcm1fdXJsID0gJHRoaXMuYXR0cignZGF0YS1hamF4LWZvcm0tdXJsJyk7XHJcbiAgICAgICAgdGhpcy5pc19ydGwgPSAkdGhpcy5hdHRyKCdkYXRhLWlzLXJ0bCcpO1xyXG5cclxuICAgICAgICB0aGlzLmRpc3BsYXlfcmVzdWx0X21ldGhvZCA9ICR0aGlzLmF0dHIoJ2RhdGEtZGlzcGxheS1yZXN1bHQtbWV0aG9kJyk7XHJcbiAgICAgICAgdGhpcy5tYWludGFpbl9zdGF0ZSA9ICR0aGlzLmF0dHIoJ2RhdGEtbWFpbnRhaW4tc3RhdGUnKTtcclxuICAgICAgICB0aGlzLmFqYXhfYWN0aW9uID0gXCJcIjtcclxuICAgICAgICB0aGlzLmxhc3Rfc3VibWl0X3F1ZXJ5X3BhcmFtcyA9IFwiXCI7XHJcblxyXG4gICAgICAgIHRoaXMuY3VycmVudF9wYWdlZCA9IHBhcnNlSW50KCR0aGlzLmF0dHIoJ2RhdGEtaW5pdC1wYWdlZCcpKTtcclxuICAgICAgICB0aGlzLmxhc3RfbG9hZF9tb3JlX2h0bWwgPSBcIlwiO1xyXG4gICAgICAgIHRoaXMubG9hZF9tb3JlX2h0bWwgPSBcIlwiO1xyXG4gICAgICAgIHRoaXMuYWpheF9kYXRhX3R5cGUgPSAkdGhpcy5hdHRyKCdkYXRhLWFqYXgtZGF0YS10eXBlJyk7XHJcbiAgICAgICAgdGhpcy5hamF4X3RhcmdldF9hdHRyID0gJHRoaXMuYXR0cihcImRhdGEtYWpheC10YXJnZXRcIik7XHJcbiAgICAgICAgdGhpcy51c2VfaGlzdG9yeV9hcGkgPSAkdGhpcy5hdHRyKFwiZGF0YS11c2UtaGlzdG9yeS1hcGlcIik7XHJcbiAgICAgICAgdGhpcy5pc19zdWJtaXR0aW5nID0gZmFsc2U7XHJcblxyXG4gICAgICAgIHRoaXMubGFzdF9hamF4X3JlcXVlc3QgPSBudWxsO1xyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5yZXN1bHRzX2h0bWwpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5yZXN1bHRzX2h0bWwgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMudXNlX2hpc3RvcnlfYXBpKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMudXNlX2hpc3RvcnlfYXBpID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLnBhZ2luYXRpb25fdHlwZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLnBhZ2luYXRpb25fdHlwZSA9IFwibm9ybWFsXCI7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmN1cnJlbnRfcGFnZWQpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5jdXJyZW50X3BhZ2VkID0gMTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmFqYXhfdGFyZ2V0X2F0dHIpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5hamF4X3RhcmdldF9hdHRyID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmFqYXhfdXJsKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuYWpheF91cmwgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuYWpheF9mb3JtX3VybCk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmFqYXhfZm9ybV91cmwgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMucmVzdWx0c191cmwpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5yZXN1bHRzX3VybCA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5zY3JvbGxfdG9fcG9zKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsX3RvX3BvcyA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy5zY3JvbGxfb25fYWN0aW9uKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsX29uX2FjdGlvbiA9IFwiXCI7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLmN1c3RvbV9zY3JvbGxfdG8pPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5jdXN0b21fc2Nyb2xsX3RvID0gXCJcIjtcclxuICAgICAgICB9XHJcbiAgICAgICAgdGhpcy4kY3VzdG9tX3Njcm9sbF90byA9IGpRdWVyeSh0aGlzLmN1c3RvbV9zY3JvbGxfdG8pO1xyXG5cclxuICAgICAgICBpZih0eXBlb2YodGhpcy51cGRhdGVfYWpheF91cmwpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy51cGRhdGVfYWpheF91cmwgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuZGVidWdfbW9kZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLmRlYnVnX21vZGUgPSBcIlwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuYWpheF90YXJnZXRfb2JqZWN0KT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHRoaXMuYWpheF90YXJnZXRfb2JqZWN0ID0gXCJcIjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKHR5cGVvZih0aGlzLnRlbXBsYXRlX2lzX2xvYWRlZCk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB0aGlzLnRlbXBsYXRlX2lzX2xvYWRlZCA9IFwiMFwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgaWYodHlwZW9mKHRoaXMuYXV0b19jb3VudF9yZWZyZXNoX21vZGUpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdGhpcy5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZSA9IFwiMFwiO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5hamF4X2xpbmtzX3NlbGVjdG9yID0gJHRoaXMuYXR0cihcImRhdGEtYWpheC1saW5rcy1zZWxlY3RvclwiKTtcclxuXHJcblxyXG4gICAgICAgIHRoaXMuYXV0b191cGRhdGUgPSAkdGhpcy5hdHRyKFwiZGF0YS1hdXRvLXVwZGF0ZVwiKTtcclxuICAgICAgICB0aGlzLmlucHV0VGltZXIgPSAwO1xyXG5cclxuICAgICAgICB0aGlzLnNldEluZmluaXRlU2Nyb2xsQ29udGFpbmVyID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgLy8gV2hlbiB3ZSBuYXZpZ2F0ZSBhd2F5IGZyb20gc2VhcmNoIHJlc3VsdHMsIGFuZCB0aGVuIHByZXNzIGJhY2ssXHJcbiAgICAgICAgICAgIC8vIGlzX21heF9wYWdlZCBpcyByZXRhaW5lZCwgc28gd2Ugb25seSB3YW50IHRvIHNldCBpdCB0byBmYWxzZSBpZlxyXG4gICAgICAgICAgICAvLyB3ZSBhcmUgaW5pdGFsaXppbmcgdGhlIHJlc3VsdHMgcGFnZSB0aGUgZmlyc3QgdGltZSAtIHNvIGp1c3QgXHJcbiAgICAgICAgICAgIC8vIGNoZWNrIGlmIHRoaXMgdmFyIGlzIHVuZGVmaW5lZCAoYXMgaXQgc2hvdWxkIGJlIG9uIGZpcnN0IHVzZSk7XHJcbiAgICAgICAgICAgIGlmICggdHlwZW9mICggdGhpcy5pc19tYXhfcGFnZWQgKSA9PT0gJ3VuZGVmaW5lZCcgKSB7XHJcbiAgICAgICAgICAgICAgICB0aGlzLmlzX21heF9wYWdlZCA9IGZhbHNlOyAvL2ZvciBsb2FkIG1vcmUgb25seSwgb25jZSB3ZSBkZXRlY3Qgd2UncmUgYXQgdGhlIGVuZCBzZXQgdGhpcyB0byB0cnVlXHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHRoaXMudXNlX3Njcm9sbF9sb2FkZXIgPSAkdGhpcy5hdHRyKCdkYXRhLXNob3ctc2Nyb2xsLWxvYWRlcicpO1xyXG4gICAgICAgICAgICB0aGlzLmluZmluaXRlX3Njcm9sbF9jb250YWluZXIgPSAkdGhpcy5hdHRyKCdkYXRhLWluZmluaXRlLXNjcm9sbC1jb250YWluZXInKTtcclxuICAgICAgICAgICAgdGhpcy5pbmZpbml0ZV9zY3JvbGxfdHJpZ2dlcl9hbW91bnQgPSAkdGhpcy5hdHRyKCdkYXRhLWluZmluaXRlLXNjcm9sbC10cmlnZ2VyJyk7XHJcbiAgICAgICAgICAgIHRoaXMuaW5maW5pdGVfc2Nyb2xsX3Jlc3VsdF9jbGFzcyA9ICR0aGlzLmF0dHIoJ2RhdGEtaW5maW5pdGUtc2Nyb2xsLXJlc3VsdC1jbGFzcycpO1xyXG4gICAgICAgICAgICB0aGlzLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyID0gdGhpcy4kYWpheF9yZXN1bHRzX2NvbnRhaW5lcjtcclxuXHJcbiAgICAgICAgICAgIGlmKHR5cGVvZih0aGlzLmluZmluaXRlX3Njcm9sbF9jb250YWluZXIpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB0aGlzLmluZmluaXRlX3Njcm9sbF9jb250YWluZXIgPSBcIlwiO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdGhpcy4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lciA9IGpRdWVyeSgkdGhpcy5hdHRyKCdkYXRhLWluZmluaXRlLXNjcm9sbC1jb250YWluZXInKSk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKHR5cGVvZih0aGlzLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB0aGlzLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MgPSBcIlwiO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlb2YodGhpcy51c2Vfc2Nyb2xsX2xvYWRlcik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHRoaXMudXNlX3Njcm9sbF9sb2FkZXIgPSAxO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgIH07XHJcbiAgICAgICAgdGhpcy5zZXRJbmZpbml0ZVNjcm9sbENvbnRhaW5lcigpO1xyXG5cclxuICAgICAgICAvKiBmdW5jdGlvbnMgKi9cclxuXHJcbiAgICAgICAgdGhpcy5yZXNldCA9IGZ1bmN0aW9uKHN1Ym1pdF9mb3JtKVxyXG4gICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgIHRoaXMucmVzZXRGb3JtKHN1Ym1pdF9mb3JtKTtcclxuICAgICAgICAgICAgcmV0dXJuIHRydWU7XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLmlucHV0VXBkYXRlID0gZnVuY3Rpb24oZGVsYXlEdXJhdGlvbilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmKHR5cGVvZihkZWxheUR1cmF0aW9uKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGRlbGF5RHVyYXRpb24gPSAzMDA7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHNlbGYucmVzZXRUaW1lcihkZWxheUR1cmF0aW9uKTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuc2Nyb2xsVG9Qb3MgPSBmdW5jdGlvbigpIHtcclxuICAgICAgICAgICAgdmFyIG9mZnNldCA9IDA7XHJcbiAgICAgICAgICAgIHZhciBjYW5TY3JvbGwgPSB0cnVlO1xyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi5pc19hamF4PT0xKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBpZihzZWxmLnNjcm9sbF90b19wb3M9PVwid2luZG93XCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgb2Zmc2V0ID0gMDtcclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlIGlmKHNlbGYuc2Nyb2xsX3RvX3Bvcz09XCJmb3JtXCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgb2Zmc2V0ID0gJHRoaXMub2Zmc2V0KCkudG9wO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZSBpZihzZWxmLnNjcm9sbF90b19wb3M9PVwicmVzdWx0c1wiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIubGVuZ3RoPjApXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBvZmZzZXQgPSBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLm9mZnNldCgpLnRvcDtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlIGlmKHNlbGYuc2Nyb2xsX3RvX3Bvcz09XCJjdXN0b21cIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvL2N1c3RvbV9zY3JvbGxfdG9cclxuICAgICAgICAgICAgICAgICAgICBpZihzZWxmLiRjdXN0b21fc2Nyb2xsX3RvLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgb2Zmc2V0ID0gc2VsZi4kY3VzdG9tX3Njcm9sbF90by5vZmZzZXQoKS50b3A7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGNhblNjcm9sbCA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKGNhblNjcm9sbClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAkKFwiaHRtbCwgYm9keVwiKS5zdG9wKCkuYW5pbWF0ZSh7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNjcm9sbFRvcDogb2Zmc2V0XHJcbiAgICAgICAgICAgICAgICAgICAgfSwgXCJub3JtYWxcIiwgXCJlYXNlT3V0UXVhZFwiICk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5hdHRhY2hBY3RpdmVDbGFzcyA9IGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAvL2NoZWNrIHRvIHNlZSBpZiB3ZSBhcmUgdXNpbmcgYWpheCAmIGF1dG8gY291bnRcclxuICAgICAgICAgICAgLy9pZiBub3QsIHRoZSBzZWFyY2ggZm9ybSBkb2VzIG5vdCBnZXQgcmVsb2FkZWQsIHNvIHdlIG5lZWQgdG8gdXBkYXRlIHRoZSBzZi1vcHRpb24tYWN0aXZlIGNsYXNzIG9uIGFsbCBmaWVsZHNcclxuXHJcbiAgICAgICAgICAgICR0aGlzLm9uKCdjaGFuZ2UnLCAnaW5wdXRbdHlwZT1cInJhZGlvXCJdLCBpbnB1dFt0eXBlPVwiY2hlY2tib3hcIl0sIHNlbGVjdCcsIGZ1bmN0aW9uKGUpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciAkY3RoaXMgPSAkKHRoaXMpO1xyXG4gICAgICAgICAgICAgICAgdmFyICRjdGhpc19wYXJlbnQgPSAkY3RoaXMuY2xvc2VzdChcImxpW2RhdGEtc2YtZmllbGQtbmFtZV1cIik7XHJcbiAgICAgICAgICAgICAgICB2YXIgdGhpc190YWcgPSAkY3RoaXMucHJvcChcInRhZ05hbWVcIikudG9Mb3dlckNhc2UoKTtcclxuICAgICAgICAgICAgICAgIHZhciBpbnB1dF90eXBlID0gJGN0aGlzLmF0dHIoXCJ0eXBlXCIpO1xyXG4gICAgICAgICAgICAgICAgdmFyIHBhcmVudF90YWcgPSAkY3RoaXNfcGFyZW50LnByb3AoXCJ0YWdOYW1lXCIpLnRvTG93ZXJDYXNlKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYoKHRoaXNfdGFnPT1cImlucHV0XCIpJiYoKGlucHV0X3R5cGU9PVwicmFkaW9cIil8fChpbnB1dF90eXBlPT1cImNoZWNrYm94XCIpKSAmJiAocGFyZW50X3RhZz09XCJsaVwiKSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGFsbF9vcHRpb25zID0gJGN0aGlzX3BhcmVudC5wYXJlbnQoKS5maW5kKCdsaScpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkYWxsX29wdGlvbnNfZmllbGRzID0gJGN0aGlzX3BhcmVudC5wYXJlbnQoKS5maW5kKCdpbnB1dDpjaGVja2VkJyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICRhbGxfb3B0aW9ucy5yZW1vdmVDbGFzcyhcInNmLW9wdGlvbi1hY3RpdmVcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgJGFsbF9vcHRpb25zX2ZpZWxkcy5lYWNoKGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgJHBhcmVudCA9ICQodGhpcykuY2xvc2VzdChcImxpXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkcGFyZW50LmFkZENsYXNzKFwic2Ytb3B0aW9uLWFjdGl2ZVwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZSBpZih0aGlzX3RhZz09XCJzZWxlY3RcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgJGFsbF9vcHRpb25zID0gJGN0aGlzLmNoaWxkcmVuKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgJGFsbF9vcHRpb25zLnJlbW92ZUNsYXNzKFwic2Ytb3B0aW9uLWFjdGl2ZVwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgdGhpc192YWwgPSAkY3RoaXMudmFsKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciB0aGlzX2Fycl92YWwgPSAodHlwZW9mIHRoaXNfdmFsID09ICdzdHJpbmcnIHx8IHRoaXNfdmFsIGluc3RhbmNlb2YgU3RyaW5nKSA/IFt0aGlzX3ZhbF0gOiB0aGlzX3ZhbDtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgJCh0aGlzX2Fycl92YWwpLmVhY2goZnVuY3Rpb24oaSwgdmFsdWUpe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkY3RoaXMuZmluZChcIm9wdGlvblt2YWx1ZT0nXCIrdmFsdWUrXCInXVwiKS5hZGRDbGFzcyhcInNmLW9wdGlvbi1hY3RpdmVcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG5cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgIH07XHJcbiAgICAgICAgdGhpcy5pbml0QXV0b1VwZGF0ZUV2ZW50cyA9IGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAvKiBhdXRvIHVwZGF0ZSAqL1xyXG4gICAgICAgICAgICBpZigoc2VsZi5hdXRvX3VwZGF0ZT09MSl8fChzZWxmLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlPT0xKSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgJHRoaXMub24oJ2NoYW5nZScsICdpbnB1dFt0eXBlPVwicmFkaW9cIl0sIGlucHV0W3R5cGU9XCJjaGVja2JveFwiXSwgc2VsZWN0JywgZnVuY3Rpb24oZSkge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoMjAwKTtcclxuICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgICR0aGlzLm9uKCdpbnB1dCcsICdpbnB1dFt0eXBlPVwibnVtYmVyXCJdJywgZnVuY3Rpb24oZSkge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuaW5wdXRVcGRhdGUoODAwKTtcclxuICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIHZhciAkdGV4dElucHV0ID0gJHRoaXMuZmluZCgnaW5wdXRbdHlwZT1cInRleHRcIl06bm90KC5zZi1kYXRlcGlja2VyKScpO1xyXG4gICAgICAgICAgICAgICAgdmFyIGxhc3RWYWx1ZSA9ICR0ZXh0SW5wdXQudmFsKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgJHRoaXMub24oJ2lucHV0JywgJ2lucHV0W3R5cGU9XCJ0ZXh0XCJdOm5vdCguc2YtZGF0ZXBpY2tlciknLCBmdW5jdGlvbigpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYobGFzdFZhbHVlIT0kdGV4dElucHV0LnZhbCgpKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5pbnB1dFVwZGF0ZSgxMjAwKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGxhc3RWYWx1ZSA9ICR0ZXh0SW5wdXQudmFsKCk7XHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcblxyXG4gICAgICAgICAgICAgICAgJHRoaXMub24oJ2tleXByZXNzJywgJ2lucHV0W3R5cGU9XCJ0ZXh0XCJdOm5vdCguc2YtZGF0ZXBpY2tlciknLCBmdW5jdGlvbihlKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmIChlLndoaWNoID09IDEzKXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5zdWJtaXRGb3JtKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy8kdGhpcy5vbignaW5wdXQnLCAnaW5wdXQuc2YtZGF0ZXBpY2tlcicsIHNlbGYuZGF0ZUlucHV0VHlwZSk7XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgLy90aGlzLmluaXRBdXRvVXBkYXRlRXZlbnRzKCk7XHJcblxyXG5cclxuICAgICAgICB0aGlzLmNsZWFyVGltZXIgPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBjbGVhclRpbWVvdXQoc2VsZi5pbnB1dFRpbWVyKTtcclxuICAgICAgICB9O1xyXG4gICAgICAgIHRoaXMucmVzZXRUaW1lciA9IGZ1bmN0aW9uKGRlbGF5RHVyYXRpb24pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBjbGVhclRpbWVvdXQoc2VsZi5pbnB1dFRpbWVyKTtcclxuICAgICAgICAgICAgc2VsZi5pbnB1dFRpbWVyID0gc2V0VGltZW91dChzZWxmLmZvcm1VcGRhdGVkLCBkZWxheUR1cmF0aW9uKTtcclxuXHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5hZGREYXRlUGlja2VycyA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHZhciAkZGF0ZV9waWNrZXIgPSAkdGhpcy5maW5kKFwiLnNmLWRhdGVwaWNrZXJcIik7XHJcblxyXG4gICAgICAgICAgICBpZigkZGF0ZV9waWNrZXIubGVuZ3RoPjApXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICRkYXRlX3BpY2tlci5lYWNoKGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkdGhpcyA9ICQodGhpcyk7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGRhdGVGb3JtYXQgPSBcIlwiO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkYXRlRHJvcGRvd25ZZWFyID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGRhdGVEcm9wZG93bk1vbnRoID0gZmFsc2U7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkY2xvc2VzdF9kYXRlX3dyYXAgPSAkdGhpcy5jbG9zZXN0KFwiLnNmX2RhdGVfZmllbGRcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYoJGNsb3Nlc3RfZGF0ZV93cmFwLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgZGF0ZUZvcm1hdCA9ICRjbG9zZXN0X2RhdGVfd3JhcC5hdHRyKFwiZGF0YS1kYXRlLWZvcm1hdFwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKCRjbG9zZXN0X2RhdGVfd3JhcC5hdHRyKFwiZGF0YS1kYXRlLXVzZS15ZWFyLWRyb3Bkb3duXCIpPT0xKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlRHJvcGRvd25ZZWFyID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigkY2xvc2VzdF9kYXRlX3dyYXAuYXR0cihcImRhdGEtZGF0ZS11c2UtbW9udGgtZHJvcGRvd25cIik9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGVEcm9wZG93bk1vbnRoID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGRhdGVQaWNrZXJPcHRpb25zID0ge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpbmxpbmU6IHRydWUsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNob3dPdGhlck1vbnRoczogdHJ1ZSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgb25TZWxlY3Q6IGZ1bmN0aW9uKGUsIGZyb21fZmllbGQpeyBzZWxmLmRhdGVTZWxlY3QoZSwgZnJvbV9maWVsZCwgJCh0aGlzKSk7IH0sXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGVGb3JtYXQ6IGRhdGVGb3JtYXQsXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBjaGFuZ2VNb250aDogZGF0ZURyb3Bkb3duTW9udGgsXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGNoYW5nZVllYXI6IGRhdGVEcm9wZG93blllYXJcclxuICAgICAgICAgICAgICAgICAgICB9O1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZihzZWxmLmlzX3J0bD09MSlcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGVQaWNrZXJPcHRpb25zLmRpcmVjdGlvbiA9IFwicnRsXCI7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAkdGhpcy5kYXRlcGlja2VyKGRhdGVQaWNrZXJPcHRpb25zKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5sYW5nX2NvZGUhPVwiXCIpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkLmRhdGVwaWNrZXIuc2V0RGVmYXVsdHMoXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkLmV4dGVuZChcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB7J2RhdGVGb3JtYXQnOmRhdGVGb3JtYXR9LFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQuZGF0ZXBpY2tlci5yZWdpb25hbFsgc2VsZi5sYW5nX2NvZGVdXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICApXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkLmRhdGVwaWNrZXIuc2V0RGVmYXVsdHMoXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkLmV4dGVuZChcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB7J2RhdGVGb3JtYXQnOmRhdGVGb3JtYXR9LFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQuZGF0ZXBpY2tlci5yZWdpb25hbFtcImVuXCJdXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICApXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZigkKCcubGwtc2tpbi1tZWxvbicpLmxlbmd0aD09MCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICRkYXRlX3BpY2tlci5kYXRlcGlja2VyKCd3aWRnZXQnKS53cmFwKCc8ZGl2IGNsYXNzPVwibGwtc2tpbi1tZWxvbiBzZWFyY2hhbmRmaWx0ZXItZGF0ZS1waWNrZXJcIi8+Jyk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5kYXRlU2VsZWN0ID0gZnVuY3Rpb24oZSwgZnJvbV9maWVsZCwgJHRoaXMpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgJGlucHV0X2ZpZWxkID0gJChmcm9tX2ZpZWxkLmlucHV0LmdldCgwKSk7XHJcbiAgICAgICAgICAgIHZhciAkdGhpcyA9ICQodGhpcyk7XHJcblxyXG4gICAgICAgICAgICB2YXIgJGRhdGVfZmllbGRzID0gJGlucHV0X2ZpZWxkLmNsb3Nlc3QoJ1tkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGU9XCJkYXRlcmFuZ2VcIl0sIFtkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGU9XCJkYXRlXCJdJyk7XHJcbiAgICAgICAgICAgICRkYXRlX2ZpZWxkcy5lYWNoKGZ1bmN0aW9uKGUsIGluZGV4KXtcclxuICAgICAgICAgICAgICAgIFxyXG4gICAgICAgICAgICAgICAgdmFyICR0Zl9kYXRlX3BpY2tlcnMgPSAkKHRoaXMpLmZpbmQoXCIuc2YtZGF0ZXBpY2tlclwiKTtcclxuICAgICAgICAgICAgICAgIHZhciBub19kYXRlX3BpY2tlcnMgPSAkdGZfZGF0ZV9waWNrZXJzLmxlbmd0aDtcclxuICAgICAgICAgICAgICAgIFxyXG4gICAgICAgICAgICAgICAgaWYobm9fZGF0ZV9waWNrZXJzPjEpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgLy90aGVuIGl0IGlzIGEgZGF0ZSByYW5nZSwgc28gbWFrZSBzdXJlIGJvdGggZmllbGRzIGFyZSBmaWxsZWQgYmVmb3JlIHVwZGF0aW5nXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGRwX2NvdW50ZXIgPSAwO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkcF9lbXB0eV9maWVsZF9jb3VudCA9IDA7XHJcbiAgICAgICAgICAgICAgICAgICAgJHRmX2RhdGVfcGlja2Vycy5lYWNoKGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigkKHRoaXMpLnZhbCgpPT1cIlwiKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkcF9lbXB0eV9maWVsZF9jb3VudCsrO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBkcF9jb3VudGVyKys7XHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKGRwX2VtcHR5X2ZpZWxkX2NvdW50PT0wKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5pbnB1dFVwZGF0ZSgxKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5pbnB1dFVwZGF0ZSgxKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIH0pO1xyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuYWRkUmFuZ2VTbGlkZXJzID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyICRtZXRhX3JhbmdlID0gJHRoaXMuZmluZChcIi5zZi1tZXRhLXJhbmdlLXNsaWRlclwiKTtcclxuXHJcbiAgICAgICAgICAgIGlmKCRtZXRhX3JhbmdlLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAkbWV0YV9yYW5nZS5lYWNoKGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkdGhpcyA9ICQodGhpcyk7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG1pbiA9ICR0aGlzLmF0dHIoXCJkYXRhLW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWF4ID0gJHRoaXMuYXR0cihcImRhdGEtbWF4XCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzbWluID0gJHRoaXMuYXR0cihcImRhdGEtc3RhcnQtbWluXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzbWF4ID0gJHRoaXMuYXR0cihcImRhdGEtc3RhcnQtbWF4XCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkaXNwbGF5X3ZhbHVlX2FzID0gJHRoaXMuYXR0cihcImRhdGEtZGlzcGxheS12YWx1ZXMtYXNcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHN0ZXAgPSAkdGhpcy5hdHRyKFwiZGF0YS1zdGVwXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkc3RhcnRfdmFsID0gJHRoaXMuZmluZCgnLnNmLXJhbmdlLW1pbicpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkZW5kX3ZhbCA9ICR0aGlzLmZpbmQoJy5zZi1yYW5nZS1tYXgnKTtcclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBkZWNpbWFsX3BsYWNlcyA9ICR0aGlzLmF0dHIoXCJkYXRhLWRlY2ltYWwtcGxhY2VzXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciB0aG91c2FuZF9zZXBlcmF0b3IgPSAkdGhpcy5hdHRyKFwiZGF0YS10aG91c2FuZC1zZXBlcmF0b3JcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGRlY2ltYWxfc2VwZXJhdG9yID0gJHRoaXMuYXR0cihcImRhdGEtZGVjaW1hbC1zZXBlcmF0b3JcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBmaWVsZF9mb3JtYXQgPSB3TnVtYih7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIG1hcms6IGRlY2ltYWxfc2VwZXJhdG9yLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICBkZWNpbWFsczogcGFyc2VGbG9hdChkZWNpbWFsX3BsYWNlcyksXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHRob3VzYW5kOiB0aG91c2FuZF9zZXBlcmF0b3JcclxuICAgICAgICAgICAgICAgICAgICB9KTtcclxuXHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWluX3VuZm9ybWF0dGVkID0gcGFyc2VGbG9hdChzbWluKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWluX2Zvcm1hdHRlZCA9IGZpZWxkX2Zvcm1hdC50byhwYXJzZUZsb2F0KHNtaW4pKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWF4X2Zvcm1hdHRlZCA9IGZpZWxkX2Zvcm1hdC50byhwYXJzZUZsb2F0KHNtYXgpKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWF4X3VuZm9ybWF0dGVkID0gcGFyc2VGbG9hdChzbWF4KTtcclxuICAgICAgICAgICAgICAgICAgICAvL2FsZXJ0KG1pbl9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgIC8vYWxlcnQobWF4X2Zvcm1hdHRlZCk7XHJcbiAgICAgICAgICAgICAgICAgICAgLy9hbGVydChkaXNwbGF5X3ZhbHVlX2FzKTtcclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKGRpc3BsYXlfdmFsdWVfYXM9PVwidGV4dGlucHV0XCIpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkc3RhcnRfdmFsLnZhbChtaW5fZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJGVuZF92YWwudmFsKG1heF9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICBlbHNlIGlmKGRpc3BsYXlfdmFsdWVfYXM9PVwidGV4dFwiKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC5odG1sKG1pbl9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkZW5kX3ZhbC5odG1sKG1heF9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBub1VJT3B0aW9ucyA9IHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgcmFuZ2U6IHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdtaW4nOiBbIHBhcnNlRmxvYXQobWluKSBdLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ21heCc6IFsgcGFyc2VGbG9hdChtYXgpIF1cclxuICAgICAgICAgICAgICAgICAgICAgICAgfSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgc3RhcnQ6IFttaW5fZm9ybWF0dGVkLCBtYXhfZm9ybWF0dGVkXSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgaGFuZGxlczogMixcclxuICAgICAgICAgICAgICAgICAgICAgICAgY29ubmVjdDogdHJ1ZSxcclxuICAgICAgICAgICAgICAgICAgICAgICAgc3RlcDogcGFyc2VGbG9hdChzdGVwKSxcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGJlaGF2aW91cjogJ2V4dGVuZC10YXAnLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICBmb3JtYXQ6IGZpZWxkX2Zvcm1hdFxyXG4gICAgICAgICAgICAgICAgICAgIH07XHJcblxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5pc19ydGw9PTEpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBub1VJT3B0aW9ucy5kaXJlY3Rpb24gPSBcInJ0bFwiO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHNsaWRlcl9vYmplY3QgPSAkKHRoaXMpLmZpbmQoXCIubWV0YS1zbGlkZXJcIilbMF07XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKCBcInVuZGVmaW5lZFwiICE9PSB0eXBlb2YoIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlciApICkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL2Rlc3Ryb3kgaWYgaXQgZXhpc3RzLi4gdGhpcyBtZWFucyBzb21laG93IGFub3RoZXIgaW5zdGFuY2UgaGFkIGluaXRpYWxpc2VkIGl0Li5cclxuICAgICAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLmRlc3Ryb3koKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIG5vVWlTbGlkZXIuY3JlYXRlKHNsaWRlcl9vYmplY3QsIG5vVUlPcHRpb25zKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgJHN0YXJ0X3ZhbC5vZmYoKTtcclxuICAgICAgICAgICAgICAgICAgICAkc3RhcnRfdmFsLm9uKCdjaGFuZ2UnLCBmdW5jdGlvbigpe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIuc2V0KFskKHRoaXMpLnZhbCgpLCBudWxsXSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICRlbmRfdmFsLm9mZigpO1xyXG4gICAgICAgICAgICAgICAgICAgICRlbmRfdmFsLm9uKCdjaGFuZ2UnLCBmdW5jdGlvbigpe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIuc2V0KFtudWxsLCAkKHRoaXMpLnZhbCgpXSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vJHN0YXJ0X3ZhbC5odG1sKG1pbl9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgIC8vJGVuZF92YWwuaHRtbChtYXhfZm9ybWF0dGVkKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLm9mZigndXBkYXRlJyk7XHJcbiAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLm9uKCd1cGRhdGUnLCBmdW5jdGlvbiggdmFsdWVzLCBoYW5kbGUgKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2xpZGVyX3N0YXJ0X3ZhbCAgPSBtaW5fZm9ybWF0dGVkO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2xpZGVyX2VuZF92YWwgID0gbWF4X2Zvcm1hdHRlZDtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciB2YWx1ZSA9IHZhbHVlc1toYW5kbGVdO1xyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICggaGFuZGxlICkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbWF4X2Zvcm1hdHRlZCA9IHZhbHVlO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbWluX2Zvcm1hdHRlZCA9IHZhbHVlO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZihkaXNwbGF5X3ZhbHVlX2FzPT1cInRleHRpbnB1dFwiKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc3RhcnRfdmFsLnZhbChtaW5fZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRlbmRfdmFsLnZhbChtYXhfZm9ybWF0dGVkKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBlbHNlIGlmKGRpc3BsYXlfdmFsdWVfYXM9PVwidGV4dFwiKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkc3RhcnRfdmFsLmh0bWwobWluX2Zvcm1hdHRlZCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZW5kX3ZhbC5odG1sKG1heF9mb3JtYXR0ZWQpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9pIHRoaW5rIHRoZSBmdW5jdGlvbiB0aGF0IGJ1aWxkcyB0aGUgVVJMIG5lZWRzIHRvIGRlY29kZSB0aGUgZm9ybWF0dGVkIHN0cmluZyBiZWZvcmUgYWRkaW5nIHRvIHRoZSB1cmxcclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoKHNlbGYuYXV0b191cGRhdGU9PTEpfHwoc2VsZi5hdXRvX2NvdW50X3JlZnJlc2hfbW9kZT09MSkpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vb25seSB0cnkgdG8gdXBkYXRlIGlmIHRoZSB2YWx1ZXMgaGF2ZSBhY3R1YWxseSBjaGFuZ2VkXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZigoc2xpZGVyX3N0YXJ0X3ZhbCE9bWluX2Zvcm1hdHRlZCl8fChzbGlkZXJfZW5kX3ZhbCE9bWF4X2Zvcm1hdHRlZCkpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5pbnB1dFVwZGF0ZSg4MDApO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5jbGVhclRpbWVyKCk7IC8vaWdub3JlIGFueSBjaGFuZ2VzIHJlY2VudGx5IG1hZGUgYnkgdGhlIHNsaWRlciAodGhpcyB3YXMganVzdCBpbml0IHNob3VsZG4ndCBjb3VudCBhcyBhbiB1cGRhdGUgZXZlbnQpXHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmluaXQgPSBmdW5jdGlvbihrZWVwX3BhZ2luYXRpb24pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2Yoa2VlcF9wYWdpbmF0aW9uKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGtlZXBfcGFnaW5hdGlvbiA9IGZhbHNlO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB0aGlzLmluaXRBdXRvVXBkYXRlRXZlbnRzKCk7XHJcbiAgICAgICAgICAgIHRoaXMuYXR0YWNoQWN0aXZlQ2xhc3MoKTtcclxuXHJcbiAgICAgICAgICAgIHRoaXMuYWRkRGF0ZVBpY2tlcnMoKTtcclxuICAgICAgICAgICAgdGhpcy5hZGRSYW5nZVNsaWRlcnMoKTtcclxuXHJcbiAgICAgICAgICAgIC8vaW5pdCBjb21ibyBib3hlc1xyXG4gICAgICAgICAgICB2YXIgJGNvbWJvYm94ID0gJHRoaXMuZmluZChcInNlbGVjdFtkYXRhLWNvbWJvYm94PScxJ11cIik7XHJcblxyXG4gICAgICAgICAgICBpZigkY29tYm9ib3gubGVuZ3RoPjApXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICRjb21ib2JveC5lYWNoKGZ1bmN0aW9uKGluZGV4ICl7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICR0aGlzY2IgPSAkKCB0aGlzICk7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIG5ybSA9ICR0aGlzY2IuYXR0cihcImRhdGEtY29tYm9ib3gtbnJtXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mICR0aGlzY2IuY2hvc2VuICE9IFwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgY2hvc2Vub3B0aW9ucyA9IHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlYXJjaF9jb250YWluczogdHJ1ZVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9O1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoKHR5cGVvZihucm0pIT09XCJ1bmRlZmluZWRcIikmJihucm0pKXtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNob3Nlbm9wdGlvbnMubm9fcmVzdWx0c190ZXh0ID0gbnJtO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHNhZmUgdG8gdXNlIHRoZSBmdW5jdGlvblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3NlYXJjaF9jb250YWluc1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZihzZWxmLmlzX3J0bD09MSlcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHRoaXNjYi5hZGRDbGFzcyhcImNob3Nlbi1ydGxcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzY2IuY2hvc2VuKGNob3Nlbm9wdGlvbnMpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgICAgICAgICAge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNlbGVjdDJvcHRpb25zID0ge307XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZihzZWxmLmlzX3J0bD09MSlcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0Mm9wdGlvbnMuZGlyID0gXCJydGxcIjtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigodHlwZW9mKG5ybSkhPT1cInVuZGVmaW5lZFwiKSYmKG5ybSkpe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0Mm9wdGlvbnMubGFuZ3VhZ2U9IHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBcIm5vUmVzdWx0c1wiOiBmdW5jdGlvbigpe1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gbnJtO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH07XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzY2Iuc2VsZWN0MihzZWxlY3Qyb3B0aW9ucyk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuXHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHNlbGYuaXNTdWJtaXR0aW5nID0gZmFsc2U7XHJcblxyXG4gICAgICAgICAgICAvL2lmIGFqYXggaXMgZW5hYmxlZCBpbml0IHRoZSBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgIGlmKHNlbGYuaXNfYWpheD09MSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5zZXR1cEFqYXhQYWdpbmF0aW9uKCk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICR0aGlzLm9uKFwic3VibWl0XCIsIHRoaXMuc3VibWl0Rm9ybSk7XHJcblxyXG4gICAgICAgICAgICBzZWxmLmluaXRXb29Db21tZXJjZUNvbnRyb2xzKCk7IC8vd29vY29tbWVyY2Ugb3JkZXJieVxyXG5cclxuICAgICAgICAgICAgaWYoa2VlcF9wYWdpbmF0aW9uPT1mYWxzZSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X3N1Ym1pdF9xdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyhmYWxzZSk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMub25XaW5kb3dTY3JvbGwgPSBmdW5jdGlvbihldmVudClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmKCghc2VsZi5pc19sb2FkaW5nX21vcmUpICYmICghc2VsZi5pc19tYXhfcGFnZWQpKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgd2luZG93X3Njcm9sbCA9ICQod2luZG93KS5zY3JvbGxUb3AoKTtcclxuICAgICAgICAgICAgICAgIHZhciB3aW5kb3dfc2Nyb2xsX2JvdHRvbSA9ICQod2luZG93KS5zY3JvbGxUb3AoKSArICQod2luZG93KS5oZWlnaHQoKTtcclxuICAgICAgICAgICAgICAgIHZhciBzY3JvbGxfb2Zmc2V0ID0gcGFyc2VJbnQoc2VsZi5pbmZpbml0ZV9zY3JvbGxfdHJpZ2dlcl9hbW91bnQpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIubGVuZ3RoPT0xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciByZXN1bHRzX3Njcm9sbF9ib3R0b20gPSBzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLm9mZnNldCgpLnRvcCArIHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIuaGVpZ2h0KCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBvZmZzZXQgPSAoc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5vZmZzZXQoKS50b3AgKyBzZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLmhlaWdodCgpKSAtIHdpbmRvd19zY3JvbGw7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHdpbmRvd19zY3JvbGxfYm90dG9tID4gcmVzdWx0c19zY3JvbGxfYm90dG9tICsgc2Nyb2xsX29mZnNldClcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYubG9hZE1vcmVSZXN1bHRzKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgICAgICB7Ly9kb250IGxvYWQgbW9yZVxyXG5cclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuc3RyaXBRdWVyeVN0cmluZ0FuZEhhc2hGcm9tUGF0aCA9IGZ1bmN0aW9uKHVybCkge1xyXG4gICAgICAgICAgICByZXR1cm4gdXJsLnNwbGl0KFwiP1wiKVswXS5zcGxpdChcIiNcIilbMF07XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLmd1cCA9IGZ1bmN0aW9uKCBuYW1lLCB1cmwgKSB7XHJcbiAgICAgICAgICAgIGlmICghdXJsKSB1cmwgPSBsb2NhdGlvbi5ocmVmXHJcbiAgICAgICAgICAgIG5hbWUgPSBuYW1lLnJlcGxhY2UoL1tcXFtdLyxcIlxcXFxcXFtcIikucmVwbGFjZSgvW1xcXV0vLFwiXFxcXFxcXVwiKTtcclxuICAgICAgICAgICAgdmFyIHJlZ2V4UyA9IFwiW1xcXFw/Jl1cIituYW1lK1wiPShbXiYjXSopXCI7XHJcbiAgICAgICAgICAgIHZhciByZWdleCA9IG5ldyBSZWdFeHAoIHJlZ2V4UyApO1xyXG4gICAgICAgICAgICB2YXIgcmVzdWx0cyA9IHJlZ2V4LmV4ZWMoIHVybCApO1xyXG4gICAgICAgICAgICByZXR1cm4gcmVzdWx0cyA9PSBudWxsID8gbnVsbCA6IHJlc3VsdHNbMV07XHJcbiAgICAgICAgfTtcclxuXHJcblxyXG4gICAgICAgIHRoaXMuZ2V0VXJsUGFyYW1zID0gZnVuY3Rpb24oa2VlcF9wYWdpbmF0aW9uLCB0eXBlLCBleGNsdWRlKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYodHlwZW9mKGtlZXBfcGFnaW5hdGlvbik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBrZWVwX3BhZ2luYXRpb24gPSB0cnVlO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlb2YodHlwZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciB0eXBlID0gXCJcIjtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdmFyIHVybF9wYXJhbXNfc3RyID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIC8vIGdldCBhbGwgcGFyYW1zIGZyb20gZmllbGRzXHJcbiAgICAgICAgICAgIHZhciB1cmxfcGFyYW1zX2FycmF5ID0gcHJvY2Vzc19mb3JtLmdldFVybFBhcmFtcyhzZWxmKTtcclxuXHJcbiAgICAgICAgICAgIHZhciBsZW5ndGggPSBPYmplY3Qua2V5cyh1cmxfcGFyYW1zX2FycmF5KS5sZW5ndGg7XHJcbiAgICAgICAgICAgIHZhciBjb3VudCA9IDA7XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlb2YoZXhjbHVkZSkhPVwidW5kZWZpbmVkXCIpIHtcclxuICAgICAgICAgICAgICAgIGlmICh1cmxfcGFyYW1zX2FycmF5Lmhhc093blByb3BlcnR5KGV4Y2x1ZGUpKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgbGVuZ3RoLS07XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKGxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBmb3IgKHZhciBrIGluIHVybF9wYXJhbXNfYXJyYXkpIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiAodXJsX3BhcmFtc19hcnJheS5oYXNPd25Qcm9wZXJ0eShrKSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGNhbl9hZGQgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZih0eXBlb2YoZXhjbHVkZSkhPVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKGs9PWV4Y2x1ZGUpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYW5fYWRkID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKGNhbl9hZGQpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHVybF9wYXJhbXNfc3RyICs9IGsgKyBcIj1cIiArIHVybF9wYXJhbXNfYXJyYXlba107XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGNvdW50IDwgbGVuZ3RoIC0gMSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHVybF9wYXJhbXNfc3RyICs9IFwiJlwiO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50Kys7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBxdWVyeV9wYXJhbXMgPSBcIlwiO1xyXG5cclxuICAgICAgICAgICAgLy9mb3JtIHBhcmFtcyBhcyB1cmwgcXVlcnkgc3RyaW5nXHJcbiAgICAgICAgICAgIHZhciBmb3JtX3BhcmFtcyA9IHVybF9wYXJhbXNfc3RyO1xyXG5cclxuICAgICAgICAgICAgLy9nZXQgdXJsIHBhcmFtcyBmcm9tIHRoZSBmb3JtIGl0c2VsZiAod2hhdCB0aGUgdXNlciBoYXMgc2VsZWN0ZWQpXHJcbiAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgZm9ybV9wYXJhbXMpO1xyXG5cclxuICAgICAgICAgICAgLy9hZGQgcGFnaW5hdGlvblxyXG4gICAgICAgICAgICBpZihrZWVwX3BhZ2luYXRpb249PXRydWUpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBwYWdlTnVtYmVyID0gc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5hdHRyKFwiZGF0YS1wYWdlZFwiKTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZih0eXBlb2YocGFnZU51bWJlcik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgcGFnZU51bWJlciA9IDE7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgaWYocGFnZU51bWJlcj4xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgXCJzZl9wYWdlZD1cIitwYWdlTnVtYmVyKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgLy9hZGQgc2ZpZFxyXG4gICAgICAgICAgICAvL3F1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgXCJzZmlkPVwiK3NlbGYuc2ZpZCk7XHJcblxyXG4gICAgICAgICAgICAvLyBsb29wIHRocm91Z2ggYW55IGV4dHJhIHBhcmFtcyAoZnJvbSBleHQgcGx1Z2lucykgYW5kIGFkZCB0byB0aGUgdXJsIChpZSB3b29jb21tZXJjZSBgb3JkZXJieWApXHJcbiAgICAgICAgICAgIC8qdmFyIGV4dHJhX3F1ZXJ5X3BhcmFtID0gXCJcIjtcclxuICAgICAgICAgICAgIHZhciBsZW5ndGggPSBPYmplY3Qua2V5cyhzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtcykubGVuZ3RoO1xyXG4gICAgICAgICAgICAgdmFyIGNvdW50ID0gMDtcclxuXHJcbiAgICAgICAgICAgICBpZihsZW5ndGg+MClcclxuICAgICAgICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgICBmb3IgKHZhciBrIGluIHNlbGYuZXh0cmFfcXVlcnlfcGFyYW1zKSB7XHJcbiAgICAgICAgICAgICBpZiAoc2VsZi5leHRyYV9xdWVyeV9wYXJhbXMuaGFzT3duUHJvcGVydHkoaykpIHtcclxuXHJcbiAgICAgICAgICAgICBpZihzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtc1trXSE9XCJcIilcclxuICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgIGV4dHJhX3F1ZXJ5X3BhcmFtID0gaytcIj1cIitzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtc1trXTtcclxuICAgICAgICAgICAgIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuam9pblVybFBhcmFtKHF1ZXJ5X3BhcmFtcywgZXh0cmFfcXVlcnlfcGFyYW0pO1xyXG4gICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgKi9cclxuICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5hZGRRdWVyeVBhcmFtcyhxdWVyeV9wYXJhbXMsIHNlbGYuZXh0cmFfcXVlcnlfcGFyYW1zLmFsbCk7XHJcblxyXG4gICAgICAgICAgICBpZih0eXBlIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAvL3F1ZXJ5X3BhcmFtcyA9IHNlbGYuYWRkUXVlcnlQYXJhbXMocXVlcnlfcGFyYW1zLCBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtc1t0eXBlXSk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHJldHVybiBxdWVyeV9wYXJhbXM7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuYWRkUXVlcnlQYXJhbXMgPSBmdW5jdGlvbihxdWVyeV9wYXJhbXMsIG5ld19wYXJhbXMpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgZXh0cmFfcXVlcnlfcGFyYW0gPSBcIlwiO1xyXG4gICAgICAgICAgICB2YXIgbGVuZ3RoID0gT2JqZWN0LmtleXMobmV3X3BhcmFtcykubGVuZ3RoO1xyXG4gICAgICAgICAgICB2YXIgY291bnQgPSAwO1xyXG5cclxuICAgICAgICAgICAgaWYobGVuZ3RoPjApXHJcbiAgICAgICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgICAgICBmb3IgKHZhciBrIGluIG5ld19wYXJhbXMpIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiAobmV3X3BhcmFtcy5oYXNPd25Qcm9wZXJ0eShrKSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYobmV3X3BhcmFtc1trXSE9XCJcIilcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZXh0cmFfcXVlcnlfcGFyYW0gPSBrK1wiPVwiK25ld19wYXJhbXNba107XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIGV4dHJhX3F1ZXJ5X3BhcmFtKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgcmV0dXJuIHF1ZXJ5X3BhcmFtcztcclxuICAgICAgICB9XHJcbiAgICAgICAgdGhpcy5hZGRVcmxQYXJhbSA9IGZ1bmN0aW9uKHVybCwgc3RyaW5nKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgdmFyIGFkZF9wYXJhbXMgPSBcIlwiO1xyXG5cclxuICAgICAgICAgICAgaWYodXJsIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBpZih1cmwuaW5kZXhPZihcIj9cIikgIT0gLTEpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgYWRkX3BhcmFtcyArPSBcIiZcIjtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvL3VybCA9IHRoaXMudHJhaWxpbmdTbGFzaEl0KHVybCk7XHJcbiAgICAgICAgICAgICAgICAgICAgYWRkX3BhcmFtcyArPSBcIj9cIjtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoc3RyaW5nIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICAgICAgcmV0dXJuIHVybCArIGFkZF9wYXJhbXMgKyBzdHJpbmc7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm4gdXJsO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5qb2luVXJsUGFyYW0gPSBmdW5jdGlvbihwYXJhbXMsIHN0cmluZylcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHZhciBhZGRfcGFyYW1zID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIGlmKHBhcmFtcyE9XCJcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgYWRkX3BhcmFtcyArPSBcIiZcIjtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoc3RyaW5nIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICAgICAgcmV0dXJuIHBhcmFtcyArIGFkZF9wYXJhbXMgKyBzdHJpbmc7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm4gcGFyYW1zO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5zZXRBamF4UmVzdWx0c1VSTHMgPSBmdW5jdGlvbihxdWVyeV9wYXJhbXMpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2Yoc2VsZi5hamF4X3Jlc3VsdHNfY29uZik9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmYgPSBuZXcgQXJyYXkoKTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSA9IFwiXCI7XHJcbiAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Jlc3VsdHNfdXJsJ10gPSBcIlwiO1xyXG4gICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydkYXRhX3R5cGUnXSA9IFwiXCI7XHJcblxyXG4gICAgICAgICAgICAvL2lmKHNlbGYuYWpheF91cmwhPVwiXCIpXHJcbiAgICAgICAgICAgIGlmKHNlbGYuZGlzcGxheV9yZXN1bHRfbWV0aG9kPT1cInNob3J0Y29kZVwiKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIHdhbnQgdG8gZG8gYSByZXF1ZXN0IHRvIHRoZSBhamF4IGVuZHBvaW50XHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShzZWxmLnJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vYWRkIGxhbmcgY29kZSB0byBhamF4IGFwaSByZXF1ZXN0LCBsYW5nIGNvZGUgc2hvdWxkIGFscmVhZHkgYmUgaW4gdGhlcmUgZm9yIG90aGVyIHJlcXVlc3RzIChpZSwgc3VwcGxpZWQgaW4gdGhlIFJlc3VsdHMgVVJMKVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKHNlbGYubGFuZ19jb2RlIT1cIlwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vc28gYWRkIGl0XHJcbiAgICAgICAgICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5qb2luVXJsUGFyYW0ocXVlcnlfcGFyYW1zLCBcImxhbmc9XCIrc2VsZi5sYW5nX2NvZGUpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHNlbGYuYWpheF91cmwsIHF1ZXJ5X3BhcmFtcyk7XHJcbiAgICAgICAgICAgICAgICAvL3NlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ2RhdGFfdHlwZSddID0gJ2pzb24nO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHNlbGYuZGlzcGxheV9yZXN1bHRfbWV0aG9kPT1cInBvc3RfdHlwZV9hcmNoaXZlXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5zZXRUYXhBcmNoaXZlUmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgIHZhciByZXN1bHRzX3VybCA9IHByb2Nlc3NfZm9ybS5nZXRSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Jlc3VsdHNfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSA9IHNlbGYuYWRkVXJsUGFyYW0ocmVzdWx0c191cmwsIHF1ZXJ5X3BhcmFtcyk7XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoc2VsZi5kaXNwbGF5X3Jlc3VsdF9tZXRob2Q9PVwiY3VzdG9tX3dvb2NvbW1lcmNlX3N0b3JlXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5zZXRUYXhBcmNoaXZlUmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgIHZhciByZXN1bHRzX3VybCA9IHByb2Nlc3NfZm9ybS5nZXRSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Jlc3VsdHNfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSA9IHNlbGYuYWRkVXJsUGFyYW0ocmVzdWx0c191cmwsIHF1ZXJ5X3BhcmFtcyk7XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgey8vb3RoZXJ3aXNlIHdlIHdhbnQgdG8gcHVsbCB0aGUgcmVzdWx0cyBkaXJlY3RseSBmcm9tIHRoZSByZXN1bHRzIHBhZ2VcclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Jlc3VsdHNfdXJsJ10gPSBzZWxmLmFkZFVybFBhcmFtKHNlbGYucmVzdWx0c191cmwsIHF1ZXJ5X3BhcmFtcyk7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydwcm9jZXNzaW5nX3VybCddID0gc2VsZi5hZGRVcmxQYXJhbShzZWxmLmFqYXhfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG4gICAgICAgICAgICAgICAgLy9zZWxmLmFqYXhfcmVzdWx0c19jb25mWydkYXRhX3R5cGUnXSA9ICdodG1sJztcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSA9IHNlbGYuYWRkUXVlcnlQYXJhbXMoc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncHJvY2Vzc2luZ191cmwnXSwgc2VsZi5leHRyYV9xdWVyeV9wYXJhbXNbJ2FqYXgnXSk7XHJcblxyXG4gICAgICAgICAgICBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydkYXRhX3R5cGUnXSA9IHNlbGYuYWpheF9kYXRhX3R5cGU7XHJcbiAgICAgICAgfTtcclxuXHJcblxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZUxvYWRlclRhZyA9IGZ1bmN0aW9uKCRvYmplY3QpIHtcclxuXHJcbiAgICAgICAgICAgIHZhciAkcGFyZW50O1xyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAkcGFyZW50ID0gc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5maW5kKHNlbGYuaW5maW5pdGVfc2Nyb2xsX3Jlc3VsdF9jbGFzcykubGFzdCgpLnBhcmVudCgpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgJHBhcmVudCA9IHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciB0YWdOYW1lID0gJHBhcmVudC5wcm9wKFwidGFnTmFtZVwiKTtcclxuXHJcbiAgICAgICAgICAgIHZhciB0YWdUeXBlID0gJ2Rpdic7XHJcbiAgICAgICAgICAgIGlmKCAoIHRhZ05hbWUudG9Mb3dlckNhc2UoKSA9PSAnb2wnICkgfHwgKCB0YWdOYW1lLnRvTG93ZXJDYXNlKCkgPT0gJ3VsJyApICl7XHJcbiAgICAgICAgICAgICAgICB0YWdUeXBlID0gJ2xpJztcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdmFyICRuZXcgPSAkKCc8Jyt0YWdUeXBlKycgLz4nKS5odG1sKCRvYmplY3QuaHRtbCgpKTtcclxuICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZXMgPSAkb2JqZWN0LnByb3AoXCJhdHRyaWJ1dGVzXCIpO1xyXG5cclxuICAgICAgICAgICAgLy8gbG9vcCB0aHJvdWdoIDxzZWxlY3Q+IGF0dHJpYnV0ZXMgYW5kIGFwcGx5IHRoZW0gb24gPGRpdj5cclxuICAgICAgICAgICAgJC5lYWNoKGF0dHJpYnV0ZXMsIGZ1bmN0aW9uKCkge1xyXG4gICAgICAgICAgICAgICAgJG5ldy5hdHRyKHRoaXMubmFtZSwgdGhpcy52YWx1ZSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgcmV0dXJuICRuZXc7XHJcblxyXG4gICAgICAgIH1cclxuXHJcblxyXG4gICAgICAgIHRoaXMubG9hZE1vcmVSZXN1bHRzID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgaWYgKCB0aGlzLmlzX21heF9wYWdlZCA9PT0gdHJ1ZSApIHtcclxuICAgICAgICAgICAgICAgIHJldHVybjtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBzZWxmLmlzX2xvYWRpbmdfbW9yZSA9IHRydWU7XHJcblxyXG4gICAgICAgICAgICAvL3RyaWdnZXIgc3RhcnQgZXZlbnRcclxuICAgICAgICAgICAgdmFyIGV2ZW50X2RhdGEgPSB7XHJcbiAgICAgICAgICAgICAgICBzZmlkOiBzZWxmLnNmaWQsXHJcbiAgICAgICAgICAgICAgICB0YXJnZXRTZWxlY3Rvcjogc2VsZi5hamF4X3RhcmdldF9hdHRyLFxyXG4gICAgICAgICAgICAgICAgdHlwZTogXCJsb2FkX21vcmVcIixcclxuICAgICAgICAgICAgICAgIG9iamVjdDogc2VsZlxyXG4gICAgICAgICAgICB9O1xyXG5cclxuICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4c3RhcnRcIiwgZXZlbnRfZGF0YSk7XHJcbiAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5zZXRUYXhBcmNoaXZlUmVzdWx0c1VybChzZWxmLCBzZWxmLnJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgdmFyIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKHRydWUpO1xyXG4gICAgICAgICAgICBzZWxmLmxhc3Rfc3VibWl0X3F1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKGZhbHNlKTsgLy9ncmFiIGEgY29weSBvZiBodGUgVVJMIHBhcmFtcyB3aXRob3V0IHBhZ2luYXRpb24gYWxyZWFkeSBhZGRlZFxyXG5cclxuICAgICAgICAgICAgdmFyIGFqYXhfcHJvY2Vzc2luZ191cmwgPSBcIlwiO1xyXG4gICAgICAgICAgICB2YXIgYWpheF9yZXN1bHRzX3VybCA9IFwiXCI7XHJcbiAgICAgICAgICAgIHZhciBkYXRhX3R5cGUgPSBcIlwiO1xyXG5cclxuXHJcbiAgICAgICAgICAgIC8vbm93IGFkZCB0aGUgbmV3IHBhZ2luYXRpb25cclxuICAgICAgICAgICAgdmFyIG5leHRfcGFnZWRfbnVtYmVyID0gdGhpcy5jdXJyZW50X3BhZ2VkICsgMTtcclxuICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5qb2luVXJsUGFyYW0ocXVlcnlfcGFyYW1zLCBcInNmX3BhZ2VkPVwiK25leHRfcGFnZWRfbnVtYmVyKTtcclxuXHJcbiAgICAgICAgICAgIHNlbGYuc2V0QWpheFJlc3VsdHNVUkxzKHF1ZXJ5X3BhcmFtcyk7XHJcbiAgICAgICAgICAgIGFqYXhfcHJvY2Vzc2luZ191cmwgPSBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydwcm9jZXNzaW5nX3VybCddO1xyXG4gICAgICAgICAgICBhamF4X3Jlc3VsdHNfdXJsID0gc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsncmVzdWx0c191cmwnXTtcclxuICAgICAgICAgICAgZGF0YV90eXBlID0gc2VsZi5hamF4X3Jlc3VsdHNfY29uZlsnZGF0YV90eXBlJ107XHJcblxyXG4gICAgICAgICAgICAvL2Fib3J0IGFueSBwcmV2aW91cyBhamF4IHJlcXVlc3RzXHJcbiAgICAgICAgICAgIGlmKHNlbGYubGFzdF9hamF4X3JlcXVlc3QpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9hamF4X3JlcXVlc3QuYWJvcnQoKTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi51c2Vfc2Nyb2xsX2xvYWRlcj09MSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyICRsb2FkZXIgPSAkKCc8ZGl2Lz4nLHtcclxuICAgICAgICAgICAgICAgICAgICAnY2xhc3MnOiAnc2VhcmNoLWZpbHRlci1zY3JvbGwtbG9hZGluZydcclxuICAgICAgICAgICAgICAgIH0pOy8vLmFwcGVuZFRvKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIpO1xyXG5cclxuICAgICAgICAgICAgICAgICRsb2FkZXIgPSBzZWxmLnVwZGF0ZUxvYWRlclRhZygkbG9hZGVyKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmluZmluaXRlU2Nyb2xsQXBwZW5kKCRsb2FkZXIpO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIHNlbGYubGFzdF9hamF4X3JlcXVlc3QgPSAkLmdldChhamF4X3Byb2Nlc3NpbmdfdXJsLCBmdW5jdGlvbihkYXRhLCBzdGF0dXMsIHJlcXVlc3QpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuY3VycmVudF9wYWdlZCsrO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X2FqYXhfcmVxdWVzdCA9IG51bGw7XHJcblxyXG4gICAgICAgICAgICAgICAgLy8gKioqKioqKioqKioqKipcclxuICAgICAgICAgICAgICAgIC8vIFRPRE8gLSBQQVNURSBUSElTIEFORCBXQVRDSCBUSEUgUkVESVJFQ1QgLSBPTkxZIEhBUFBFTlMgV0lUSCBXQyAoQ1BUIEFORCBUQVggRE9FUyBOT1QpXHJcbiAgICAgICAgICAgICAgICAvLyBodHRwczovL3NlYXJjaC1maWx0ZXIudGVzdC9wcm9kdWN0LWNhdGVnb3J5L2Nsb3RoaW5nL3RzaGlydHMvcGFnZS8zLz9zZl9wYWdlZD0zXHJcblxyXG4gICAgICAgICAgICAgICAgLy91cGRhdGVzIHRoZSByZXN1dGxzICYgZm9ybSBodG1sXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFkZFJlc3VsdHMoZGF0YSwgZGF0YV90eXBlKTtcclxuXHJcbiAgICAgICAgICAgIH0sIGRhdGFfdHlwZSkuZmFpbChmdW5jdGlvbihqcVhIUiwgdGV4dFN0YXR1cywgZXJyb3JUaHJvd24pXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBkYXRhID0ge307XHJcbiAgICAgICAgICAgICAgICBkYXRhLnNmaWQgPSBzZWxmLnNmaWQ7XHJcbiAgICAgICAgICAgICAgICBkYXRhLm9iamVjdCA9IHNlbGY7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRhcmdldFNlbGVjdG9yID0gc2VsZi5hamF4X3RhcmdldF9hdHRyO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5hamF4VVJMID0gYWpheF9wcm9jZXNzaW5nX3VybDtcclxuICAgICAgICAgICAgICAgIGRhdGEuanFYSFIgPSBqcVhIUjtcclxuICAgICAgICAgICAgICAgIGRhdGEudGV4dFN0YXR1cyA9IHRleHRTdGF0dXM7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmVycm9yVGhyb3duID0gZXJyb3JUaHJvd247XHJcbiAgICAgICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmFqYXhlcnJvclwiLCBkYXRhKTtcclxuXHJcbiAgICAgICAgICAgIH0pLmFsd2F5cyhmdW5jdGlvbigpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBkYXRhID0ge307XHJcbiAgICAgICAgICAgICAgICBkYXRhLnNmaWQgPSBzZWxmLnNmaWQ7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRhcmdldFNlbGVjdG9yID0gc2VsZi5hamF4X3RhcmdldF9hdHRyO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5vYmplY3QgPSBzZWxmO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHNlbGYudXNlX3Njcm9sbF9sb2FkZXI9PTEpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgJGxvYWRlci5kZXRhY2goKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmlzX2xvYWRpbmdfbW9yZSA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYudHJpZ2dlckV2ZW50KFwic2Y6YWpheGZpbmlzaFwiLCBkYXRhKTtcclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgIH1cclxuICAgICAgICB0aGlzLmZldGNoQWpheFJlc3VsdHMgPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAvL3RyaWdnZXIgc3RhcnQgZXZlbnRcclxuICAgICAgICAgICAgdmFyIGV2ZW50X2RhdGEgPSB7XHJcbiAgICAgICAgICAgICAgICBzZmlkOiBzZWxmLnNmaWQsXHJcbiAgICAgICAgICAgICAgICB0YXJnZXRTZWxlY3Rvcjogc2VsZi5hamF4X3RhcmdldF9hdHRyLFxyXG4gICAgICAgICAgICAgICAgdHlwZTogXCJsb2FkX3Jlc3VsdHNcIixcclxuICAgICAgICAgICAgICAgIG9iamVjdDogc2VsZlxyXG4gICAgICAgICAgICB9O1xyXG5cclxuICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4c3RhcnRcIiwgZXZlbnRfZGF0YSk7XHJcblxyXG4gICAgICAgICAgICAvL3JlZm9jdXMgYW55IGlucHV0IGZpZWxkcyBhZnRlciB0aGUgZm9ybSBoYXMgYmVlbiB1cGRhdGVkXHJcbiAgICAgICAgICAgIHZhciAkbGFzdF9hY3RpdmVfaW5wdXRfdGV4dCA9ICR0aGlzLmZpbmQoJ2lucHV0W3R5cGU9XCJ0ZXh0XCJdOmZvY3VzJykubm90KFwiLnNmLWRhdGVwaWNrZXJcIik7XHJcbiAgICAgICAgICAgIGlmKCRsYXN0X2FjdGl2ZV9pbnB1dF90ZXh0Lmxlbmd0aD09MSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGxhc3RfYWN0aXZlX2lucHV0X3RleHQgPSAkbGFzdF9hY3RpdmVfaW5wdXRfdGV4dC5hdHRyKFwibmFtZVwiKTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgJHRoaXMuYWRkQ2xhc3MoXCJzZWFyY2gtZmlsdGVyLWRpc2FibGVkXCIpO1xyXG4gICAgICAgICAgICBwcm9jZXNzX2Zvcm0uZGlzYWJsZUlucHV0cyhzZWxmKTtcclxuXHJcbiAgICAgICAgICAgIC8vZmFkZSBvdXQgcmVzdWx0c1xyXG4gICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmFuaW1hdGUoeyBvcGFjaXR5OiAwLjUgfSwgXCJmYXN0XCIpOyAvL2xvYWRpbmdcclxuICAgICAgICAgICAgc2VsZi5mYWRlQ29udGVudEFyZWFzKCBcIm91dFwiICk7XHJcblxyXG4gICAgICAgICAgICBpZihzZWxmLmFqYXhfYWN0aW9uPT1cInBhZ2luYXRpb25cIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgLy9uZWVkIHRvIHJlbW92ZSBhY3RpdmUgZmlsdGVyIGZyb20gVVJMXHJcblxyXG4gICAgICAgICAgICAgICAgLy9xdWVyeV9wYXJhbXMgPSBzZWxmLmxhc3Rfc3VibWl0X3F1ZXJ5X3BhcmFtcztcclxuXHJcbiAgICAgICAgICAgICAgICAvL25vdyBhZGQgdGhlIG5ldyBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgICAgICB2YXIgcGFnZU51bWJlciA9IHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuYXR0cihcImRhdGEtcGFnZWRcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYodHlwZW9mKHBhZ2VOdW1iZXIpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHBhZ2VOdW1iZXIgPSAxO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgcHJvY2Vzc19mb3JtLnNldFRheEFyY2hpdmVSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG4gICAgICAgICAgICAgICAgcXVlcnlfcGFyYW1zID0gc2VsZi5nZXRVcmxQYXJhbXMoZmFsc2UpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHBhZ2VOdW1iZXI+MSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIFwic2ZfcGFnZWQ9XCIrcGFnZU51bWJlcik7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYoc2VsZi5hamF4X2FjdGlvbj09XCJzdWJtaXRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKHRydWUpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X3N1Ym1pdF9xdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcyhmYWxzZSk7IC8vZ3JhYiBhIGNvcHkgb2YgaHRlIFVSTCBwYXJhbXMgd2l0aG91dCBwYWdpbmF0aW9uIGFscmVhZHkgYWRkZWRcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdmFyIGFqYXhfcHJvY2Vzc2luZ191cmwgPSBcIlwiO1xyXG4gICAgICAgICAgICB2YXIgYWpheF9yZXN1bHRzX3VybCA9IFwiXCI7XHJcbiAgICAgICAgICAgIHZhciBkYXRhX3R5cGUgPSBcIlwiO1xyXG5cclxuICAgICAgICAgICAgc2VsZi5zZXRBamF4UmVzdWx0c1VSTHMocXVlcnlfcGFyYW1zKTtcclxuICAgICAgICAgICAgYWpheF9wcm9jZXNzaW5nX3VybCA9IHNlbGYuYWpheF9yZXN1bHRzX2NvbmZbJ3Byb2Nlc3NpbmdfdXJsJ107XHJcbiAgICAgICAgICAgIGFqYXhfcmVzdWx0c191cmwgPSBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydyZXN1bHRzX3VybCddO1xyXG4gICAgICAgICAgICBkYXRhX3R5cGUgPSBzZWxmLmFqYXhfcmVzdWx0c19jb25mWydkYXRhX3R5cGUnXTtcclxuXHJcblxyXG4gICAgICAgICAgICAvL2Fib3J0IGFueSBwcmV2aW91cyBhamF4IHJlcXVlc3RzXHJcbiAgICAgICAgICAgIGlmKHNlbGYubGFzdF9hamF4X3JlcXVlc3QpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYubGFzdF9hamF4X3JlcXVlc3QuYWJvcnQoKTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB2YXIgYWpheF9hY3Rpb24gPSBzZWxmLmFqYXhfYWN0aW9uO1xyXG4gICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0ID0gJC5nZXQoYWpheF9wcm9jZXNzaW5nX3VybCwgZnVuY3Rpb24oZGF0YSwgc3RhdHVzLCByZXF1ZXN0KVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxhc3RfYWpheF9yZXF1ZXN0ID0gbnVsbDtcclxuXHJcbiAgICAgICAgICAgICAgICAvL3VwZGF0ZXMgdGhlIHJlc3V0bHMgJiBmb3JtIGh0bWxcclxuICAgICAgICAgICAgICAgIHNlbGYudXBkYXRlUmVzdWx0cyhkYXRhLCBkYXRhX3R5cGUpO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vIHNjcm9sbCBcclxuICAgICAgICAgICAgICAgIC8vIHNldCB0aGUgdmFyIGJhY2sgdG8gd2hhdCBpdCB3YXMgYmVmb3JlIHRoZSBhamF4IHJlcXVlc3QgbmFkIHRoZSBmb3JtIHJlLWluaXRcclxuICAgICAgICAgICAgICAgIHNlbGYuYWpheF9hY3Rpb24gPSBhamF4X2FjdGlvbjtcclxuICAgICAgICAgICAgICAgIHNlbGYuc2Nyb2xsUmVzdWx0cyggc2VsZi5hamF4X2FjdGlvbiApO1xyXG5cclxuICAgICAgICAgICAgICAgIC8qIHVwZGF0ZSBVUkwgKi9cclxuICAgICAgICAgICAgICAgIC8vdXBkYXRlIHVybCBiZWZvcmUgcGFnaW5hdGlvbiwgYmVjYXVzZSB3ZSBuZWVkIHRvIGRvIHNvbWUgY2hlY2tzIGFnYWlucyB0aGUgVVJMIGZvciBpbmZpbml0ZSBzY3JvbGxcclxuICAgICAgICAgICAgICAgIHNlbGYudXBkYXRlVXJsSGlzdG9yeShhamF4X3Jlc3VsdHNfdXJsKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL3NldHVwIHBhZ2luYXRpb25cclxuICAgICAgICAgICAgICAgIHNlbGYuc2V0dXBBamF4UGFnaW5hdGlvbigpO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYuaXNTdWJtaXR0aW5nID0gZmFsc2U7XHJcblxyXG4gICAgICAgICAgICAgICAgLyogdXNlciBkZWYgKi9cclxuICAgICAgICAgICAgICAgIHNlbGYuaW5pdFdvb0NvbW1lcmNlQ29udHJvbHMoKTsgLy93b29jb21tZXJjZSBvcmRlcmJ5XHJcblxyXG5cclxuICAgICAgICAgICAgfSwgZGF0YV90eXBlKS5mYWlsKGZ1bmN0aW9uKGpxWEhSLCB0ZXh0U3RhdHVzLCBlcnJvclRocm93bilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGRhdGEgPSB7fTtcclxuICAgICAgICAgICAgICAgIGRhdGEuc2ZpZCA9IHNlbGYuc2ZpZDtcclxuICAgICAgICAgICAgICAgIGRhdGEudGFyZ2V0U2VsZWN0b3IgPSBzZWxmLmFqYXhfdGFyZ2V0X2F0dHI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLm9iamVjdCA9IHNlbGY7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmFqYXhVUkwgPSBhamF4X3Byb2Nlc3NpbmdfdXJsO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5qcVhIUiA9IGpxWEhSO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50ZXh0U3RhdHVzID0gdGV4dFN0YXR1cztcclxuICAgICAgICAgICAgICAgIGRhdGEuZXJyb3JUaHJvd24gPSBlcnJvclRocm93bjtcclxuICAgICAgICAgICAgICAgIHNlbGYuaXNTdWJtaXR0aW5nID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmFqYXhlcnJvclwiLCBkYXRhKTtcclxuXHJcbiAgICAgICAgICAgIH0pLmFsd2F5cyhmdW5jdGlvbigpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuc3RvcCh0cnVlLHRydWUpLmFuaW1hdGUoeyBvcGFjaXR5OiAxfSwgXCJmYXN0XCIpOyAvL2ZpbmlzaGVkIGxvYWRpbmdcclxuICAgICAgICAgICAgICAgIHNlbGYuZmFkZUNvbnRlbnRBcmVhcyggXCJpblwiICk7XHJcbiAgICAgICAgICAgICAgICB2YXIgZGF0YSA9IHt9O1xyXG4gICAgICAgICAgICAgICAgZGF0YS5zZmlkID0gc2VsZi5zZmlkO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50YXJnZXRTZWxlY3RvciA9IHNlbGYuYWpheF90YXJnZXRfYXR0cjtcclxuICAgICAgICAgICAgICAgIGRhdGEub2JqZWN0ID0gc2VsZjtcclxuICAgICAgICAgICAgICAgICR0aGlzLnJlbW92ZUNsYXNzKFwic2VhcmNoLWZpbHRlci1kaXNhYmxlZFwiKTtcclxuICAgICAgICAgICAgICAgIHByb2Nlc3NfZm9ybS5lbmFibGVJbnB1dHMoc2VsZik7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9yZWZvY3VzIHRoZSBsYXN0IGFjdGl2ZSB0ZXh0IGZpZWxkXHJcbiAgICAgICAgICAgICAgICBpZihsYXN0X2FjdGl2ZV9pbnB1dF90ZXh0IT1cIlwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkaW5wdXQgPSBbXTtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLiRmaWVsZHMuZWFjaChmdW5jdGlvbigpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmVfaW5wdXQgPSAkKHRoaXMpLmZpbmQoXCJpbnB1dFtuYW1lPSdcIitsYXN0X2FjdGl2ZV9pbnB1dF90ZXh0K1wiJ11cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmKCRhY3RpdmVfaW5wdXQubGVuZ3RoPT0xKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkaW5wdXQgPSAkYWN0aXZlX2lucHV0O1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKCRpbnB1dC5sZW5ndGg9PTEpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRpbnB1dC5mb2N1cygpLnZhbCgkaW5wdXQudmFsKCkpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmZvY3VzQ2FtcG8oJGlucHV0WzBdKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgJHRoaXMuZmluZChcImlucHV0W25hbWU9J19zZl9zZWFyY2gnXVwiKS50cmlnZ2VyKCdmb2N1cycpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4ZmluaXNoXCIsICBkYXRhICk7XHJcblxyXG4gICAgICAgICAgICB9KTtcclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmZvY3VzQ2FtcG8gPSBmdW5jdGlvbihpbnB1dEZpZWxkKXtcclxuICAgICAgICAgICAgLy92YXIgaW5wdXRGaWVsZCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKGlkKTtcclxuICAgICAgICAgICAgaWYgKGlucHV0RmllbGQgIT0gbnVsbCAmJiBpbnB1dEZpZWxkLnZhbHVlLmxlbmd0aCAhPSAwKXtcclxuICAgICAgICAgICAgICAgIGlmIChpbnB1dEZpZWxkLmNyZWF0ZVRleHRSYW5nZSl7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIEZpZWxkUmFuZ2UgPSBpbnB1dEZpZWxkLmNyZWF0ZVRleHRSYW5nZSgpO1xyXG4gICAgICAgICAgICAgICAgICAgIEZpZWxkUmFuZ2UubW92ZVN0YXJ0KCdjaGFyYWN0ZXInLGlucHV0RmllbGQudmFsdWUubGVuZ3RoKTtcclxuICAgICAgICAgICAgICAgICAgICBGaWVsZFJhbmdlLmNvbGxhcHNlKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgRmllbGRSYW5nZS5zZWxlY3QoKTtcclxuICAgICAgICAgICAgICAgIH1lbHNlIGlmIChpbnB1dEZpZWxkLnNlbGVjdGlvblN0YXJ0IHx8IGlucHV0RmllbGQuc2VsZWN0aW9uU3RhcnQgPT0gJzAnKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGVsZW1MZW4gPSBpbnB1dEZpZWxkLnZhbHVlLmxlbmd0aDtcclxuICAgICAgICAgICAgICAgICAgICBpbnB1dEZpZWxkLnNlbGVjdGlvblN0YXJ0ID0gZWxlbUxlbjtcclxuICAgICAgICAgICAgICAgICAgICBpbnB1dEZpZWxkLnNlbGVjdGlvbkVuZCA9IGVsZW1MZW47XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICBpbnB1dEZpZWxkLmJsdXIoKTtcclxuICAgICAgICAgICAgICAgIGlucHV0RmllbGQuZm9jdXMoKTtcclxuICAgICAgICAgICAgfSBlbHNle1xyXG4gICAgICAgICAgICAgICAgaWYgKCBpbnB1dEZpZWxkICkge1xyXG4gICAgICAgICAgICAgICAgICAgIGlucHV0RmllbGQuZm9jdXMoKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIFxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnRyaWdnZXJFdmVudCA9IGZ1bmN0aW9uKGV2ZW50bmFtZSwgZGF0YSlcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHZhciAkZXZlbnRfY29udGFpbmVyID0gJChcIi5zZWFyY2hhbmRmaWx0ZXJbZGF0YS1zZi1mb3JtLWlkPSdcIitzZWxmLnNmaWQrXCInXVwiKTtcclxuICAgICAgICAgICAgJGV2ZW50X2NvbnRhaW5lci50cmlnZ2VyKGV2ZW50bmFtZSwgWyBkYXRhIF0pO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5mZXRjaEFqYXhGb3JtID0gZnVuY3Rpb24oKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgLy90cmlnZ2VyIHN0YXJ0IGV2ZW50XHJcbiAgICAgICAgICAgIHZhciBldmVudF9kYXRhID0ge1xyXG4gICAgICAgICAgICAgICAgc2ZpZDogc2VsZi5zZmlkLFxyXG4gICAgICAgICAgICAgICAgdGFyZ2V0U2VsZWN0b3I6IHNlbGYuYWpheF90YXJnZXRfYXR0cixcclxuICAgICAgICAgICAgICAgIHR5cGU6IFwiZm9ybVwiLFxyXG4gICAgICAgICAgICAgICAgb2JqZWN0OiBzZWxmXHJcbiAgICAgICAgICAgIH07XHJcblxyXG4gICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmFqYXhmb3Jtc3RhcnRcIiwgWyBldmVudF9kYXRhIF0pO1xyXG5cclxuICAgICAgICAgICAgJHRoaXMuYWRkQ2xhc3MoXCJzZWFyY2gtZmlsdGVyLWRpc2FibGVkXCIpO1xyXG4gICAgICAgICAgICBwcm9jZXNzX2Zvcm0uZGlzYWJsZUlucHV0cyhzZWxmKTtcclxuXHJcbiAgICAgICAgICAgIHZhciBxdWVyeV9wYXJhbXMgPSBzZWxmLmdldFVybFBhcmFtcygpO1xyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi5sYW5nX2NvZGUhPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIC8vc28gYWRkIGl0XHJcbiAgICAgICAgICAgICAgICBxdWVyeV9wYXJhbXMgPSBzZWxmLmpvaW5VcmxQYXJhbShxdWVyeV9wYXJhbXMsIFwibGFuZz1cIitzZWxmLmxhbmdfY29kZSk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBhamF4X3Byb2Nlc3NpbmdfdXJsID0gc2VsZi5hZGRVcmxQYXJhbShzZWxmLmFqYXhfZm9ybV91cmwsIHF1ZXJ5X3BhcmFtcyk7XHJcbiAgICAgICAgICAgIHZhciBkYXRhX3R5cGUgPSBcImpzb25cIjtcclxuXHJcblxyXG4gICAgICAgICAgICAvL2Fib3J0IGFueSBwcmV2aW91cyBhamF4IHJlcXVlc3RzXHJcbiAgICAgICAgICAgIC8qaWYoc2VsZi5sYXN0X2FqYXhfcmVxdWVzdClcclxuICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgIHNlbGYubGFzdF9hamF4X3JlcXVlc3QuYWJvcnQoKTtcclxuICAgICAgICAgICAgIH0qL1xyXG5cclxuXHJcbiAgICAgICAgICAgIC8vc2VsZi5sYXN0X2FqYXhfcmVxdWVzdCA9XHJcblxyXG4gICAgICAgICAgICAkLmdldChhamF4X3Byb2Nlc3NpbmdfdXJsLCBmdW5jdGlvbihkYXRhLCBzdGF0dXMsIHJlcXVlc3QpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIC8vc2VsZi5sYXN0X2FqYXhfcmVxdWVzdCA9IG51bGw7XHJcblxyXG4gICAgICAgICAgICAgICAgLy91cGRhdGVzIHRoZSByZXN1dGxzICYgZm9ybSBodG1sXHJcbiAgICAgICAgICAgICAgICBzZWxmLnVwZGF0ZUZvcm0oZGF0YSwgZGF0YV90eXBlKTtcclxuXHJcblxyXG4gICAgICAgICAgICB9LCBkYXRhX3R5cGUpLmZhaWwoZnVuY3Rpb24oanFYSFIsIHRleHRTdGF0dXMsIGVycm9yVGhyb3duKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgZGF0YSA9IHt9O1xyXG4gICAgICAgICAgICAgICAgZGF0YS5zZmlkID0gc2VsZi5zZmlkO1xyXG4gICAgICAgICAgICAgICAgZGF0YS50YXJnZXRTZWxlY3RvciA9IHNlbGYuYWpheF90YXJnZXRfYXR0cjtcclxuICAgICAgICAgICAgICAgIGRhdGEub2JqZWN0ID0gc2VsZjtcclxuICAgICAgICAgICAgICAgIGRhdGEuYWpheFVSTCA9IGFqYXhfcHJvY2Vzc2luZ191cmw7XHJcbiAgICAgICAgICAgICAgICBkYXRhLmpxWEhSID0ganFYSFI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLnRleHRTdGF0dXMgPSB0ZXh0U3RhdHVzO1xyXG4gICAgICAgICAgICAgICAgZGF0YS5lcnJvclRocm93biA9IGVycm9yVGhyb3duO1xyXG4gICAgICAgICAgICAgICAgc2VsZi50cmlnZ2VyRXZlbnQoXCJzZjphamF4ZXJyb3JcIiwgWyBkYXRhIF0pO1xyXG5cclxuICAgICAgICAgICAgfSkuYWx3YXlzKGZ1bmN0aW9uKClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGRhdGEgPSB7fTtcclxuICAgICAgICAgICAgICAgIGRhdGEuc2ZpZCA9IHNlbGYuc2ZpZDtcclxuICAgICAgICAgICAgICAgIGRhdGEudGFyZ2V0U2VsZWN0b3IgPSBzZWxmLmFqYXhfdGFyZ2V0X2F0dHI7XHJcbiAgICAgICAgICAgICAgICBkYXRhLm9iamVjdCA9IHNlbGY7XHJcblxyXG4gICAgICAgICAgICAgICAgJHRoaXMucmVtb3ZlQ2xhc3MoXCJzZWFyY2gtZmlsdGVyLWRpc2FibGVkXCIpO1xyXG4gICAgICAgICAgICAgICAgcHJvY2Vzc19mb3JtLmVuYWJsZUlucHV0cyhzZWxmKTtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmFqYXhmb3JtZmluaXNoXCIsIFsgZGF0YSBdKTtcclxuICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5jb3B5TGlzdEl0ZW1zQ29udGVudHMgPSBmdW5jdGlvbigkbGlzdF9mcm9tLCAkbGlzdF90bylcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIC8vY29weSBvdmVyIGNoaWxkIGxpc3QgaXRlbXNcclxuICAgICAgICAgICAgdmFyIGxpX2NvbnRlbnRzX2FycmF5ID0gbmV3IEFycmF5KCk7XHJcbiAgICAgICAgICAgIHZhciBmcm9tX2F0dHJpYnV0ZXMgPSBuZXcgQXJyYXkoKTtcclxuXHJcbiAgICAgICAgICAgIHZhciAkZnJvbV9maWVsZHMgPSAkbGlzdF9mcm9tLmZpbmQoXCI+IHVsID4gbGlcIik7XHJcblxyXG4gICAgICAgICAgICAkZnJvbV9maWVsZHMuZWFjaChmdW5jdGlvbihpKXtcclxuXHJcbiAgICAgICAgICAgICAgICBsaV9jb250ZW50c19hcnJheS5wdXNoKCQodGhpcykuaHRtbCgpKTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgYXR0cmlidXRlcyA9ICQodGhpcykucHJvcChcImF0dHJpYnV0ZXNcIik7XHJcbiAgICAgICAgICAgICAgICBmcm9tX2F0dHJpYnV0ZXMucHVzaChhdHRyaWJ1dGVzKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL3ZhciBmaWVsZF9uYW1lID0gJCh0aGlzKS5hdHRyKFwiZGF0YS1zZi1maWVsZC1uYW1lXCIpO1xyXG4gICAgICAgICAgICAgICAgLy92YXIgdG9fZmllbGQgPSAkbGlzdF90by5maW5kKFwiPiB1bCA+IGxpW2RhdGEtc2YtZmllbGQtbmFtZT0nXCIrZmllbGRfbmFtZStcIiddXCIpO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vc2VsZi5jb3B5QXR0cmlidXRlcygkKHRoaXMpLCAkbGlzdF90bywgXCJkYXRhLXNmLVwiKTtcclxuXHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgdmFyIGxpX2l0ID0gMDtcclxuICAgICAgICAgICAgdmFyICR0b19maWVsZHMgPSAkbGlzdF90by5maW5kKFwiPiB1bCA+IGxpXCIpO1xyXG4gICAgICAgICAgICAkdG9fZmllbGRzLmVhY2goZnVuY3Rpb24oaSl7XHJcbiAgICAgICAgICAgICAgICAkKHRoaXMpLmh0bWwobGlfY29udGVudHNfYXJyYXlbbGlfaXRdKTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgJGZyb21fZmllbGQgPSAkKCRmcm9tX2ZpZWxkcy5nZXQobGlfaXQpKTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgJHRvX2ZpZWxkID0gJCh0aGlzKTtcclxuICAgICAgICAgICAgICAgICR0b19maWVsZC5yZW1vdmVBdHRyKFwiZGF0YS1zZi10YXhvbm9teS1hcmNoaXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5jb3B5QXR0cmlidXRlcygkZnJvbV9maWVsZCwgJHRvX2ZpZWxkKTtcclxuXHJcbiAgICAgICAgICAgICAgICBsaV9pdCsrO1xyXG4gICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgIC8qdmFyICRmcm9tX2ZpZWxkcyA9ICRsaXN0X2Zyb20uZmluZChcIiB1bCA+IGxpXCIpO1xyXG4gICAgICAgICAgICAgdmFyICR0b19maWVsZHMgPSAkbGlzdF90by5maW5kKFwiID4gbGlcIik7XHJcbiAgICAgICAgICAgICAkZnJvbV9maWVsZHMuZWFjaChmdW5jdGlvbihpbmRleCwgdmFsKXtcclxuICAgICAgICAgICAgIGlmKCQodGhpcykuaGFzQXR0cmlidXRlKFwiZGF0YS1zZi10YXhvbm9teS1hcmNoaXZlXCIpKVxyXG4gICAgICAgICAgICAge1xyXG5cclxuICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgIHRoaXMuY29weUF0dHJpYnV0ZXMoJGxpc3RfZnJvbSwgJGxpc3RfdG8pOyovXHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZUZvcm1BdHRyaWJ1dGVzID0gZnVuY3Rpb24oJGxpc3RfZnJvbSwgJGxpc3RfdG8pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgZnJvbV9hdHRyaWJ1dGVzID0gJGxpc3RfZnJvbS5wcm9wKFwiYXR0cmlidXRlc1wiKTtcclxuICAgICAgICAgICAgLy8gbG9vcCB0aHJvdWdoIDxzZWxlY3Q+IGF0dHJpYnV0ZXMgYW5kIGFwcGx5IHRoZW0gb24gPGRpdj5cclxuXHJcbiAgICAgICAgICAgIHZhciB0b19hdHRyaWJ1dGVzID0gJGxpc3RfdG8ucHJvcChcImF0dHJpYnV0ZXNcIik7XHJcbiAgICAgICAgICAgICQuZWFjaCh0b19hdHRyaWJ1dGVzLCBmdW5jdGlvbigpIHtcclxuICAgICAgICAgICAgICAgICRsaXN0X3RvLnJlbW92ZUF0dHIodGhpcy5uYW1lKTtcclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAkLmVhY2goZnJvbV9hdHRyaWJ1dGVzLCBmdW5jdGlvbigpIHtcclxuICAgICAgICAgICAgICAgICRsaXN0X3RvLmF0dHIodGhpcy5uYW1lLCB0aGlzLnZhbHVlKTtcclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5jb3B5QXR0cmlidXRlcyA9IGZ1bmN0aW9uKCRmcm9tLCAkdG8sIHByZWZpeClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmKHR5cGVvZihwcmVmaXgpPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgcHJlZml4ID0gXCJcIjtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdmFyIGZyb21fYXR0cmlidXRlcyA9ICRmcm9tLnByb3AoXCJhdHRyaWJ1dGVzXCIpO1xyXG5cclxuICAgICAgICAgICAgdmFyIHRvX2F0dHJpYnV0ZXMgPSAkdG8ucHJvcChcImF0dHJpYnV0ZXNcIik7XHJcbiAgICAgICAgICAgICQuZWFjaCh0b19hdHRyaWJ1dGVzLCBmdW5jdGlvbigpIHtcclxuXHJcbiAgICAgICAgICAgICAgICBpZihwcmVmaXghPVwiXCIpIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiAodGhpcy5uYW1lLmluZGV4T2YocHJlZml4KSA9PSAwKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICR0by5yZW1vdmVBdHRyKHRoaXMubmFtZSk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vJHRvLnJlbW92ZUF0dHIodGhpcy5uYW1lKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfSk7XHJcblxyXG4gICAgICAgICAgICAkLmVhY2goZnJvbV9hdHRyaWJ1dGVzLCBmdW5jdGlvbigpIHtcclxuICAgICAgICAgICAgICAgICR0by5hdHRyKHRoaXMubmFtZSwgdGhpcy52YWx1ZSk7XHJcbiAgICAgICAgICAgIH0pO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5jb3B5Rm9ybUF0dHJpYnV0ZXMgPSBmdW5jdGlvbigkZnJvbSwgJHRvKVxyXG4gICAgICAgIHtcclxuICAgICAgICAgICAgJHRvLnJlbW92ZUF0dHIoXCJkYXRhLWN1cnJlbnQtdGF4b25vbXktYXJjaGl2ZVwiKTtcclxuICAgICAgICAgICAgdGhpcy5jb3B5QXR0cmlidXRlcygkZnJvbSwgJHRvKTtcclxuXHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZUZvcm0gPSBmdW5jdGlvbihkYXRhLCBkYXRhX3R5cGUpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZihkYXRhX3R5cGU9PVwianNvblwiKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIGRpZCBhIHJlcXVlc3QgdG8gdGhlIGFqYXggZW5kcG9pbnQsIHNvIGV4cGVjdCBhbiBvYmplY3QgYmFja1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHR5cGVvZihkYXRhWydmb3JtJ10pIT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvL3JlbW92ZSBhbGwgZXZlbnRzIGZyb20gUyZGIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICAkdGhpcy5vZmYoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9yZWZyZXNoIHRoZSBmb3JtIChhdXRvIGNvdW50KVxyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuY29weUxpc3RJdGVtc0NvbnRlbnRzKCQoZGF0YVsnZm9ybSddKSwgJHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvL3JlIGluaXQgUyZGIGNsYXNzIG9uIHRoZSBmb3JtXHJcbiAgICAgICAgICAgICAgICAgICAgLy8kdGhpcy5zZWFyY2hBbmRGaWx0ZXIoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgLy9pZiBhamF4IGlzIGVuYWJsZWQgaW5pdCB0aGUgcGFnaW5hdGlvblxyXG5cclxuICAgICAgICAgICAgICAgICAgICB0aGlzLmluaXQodHJ1ZSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKHNlbGYuaXNfYWpheD09MSlcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc2V0dXBBamF4UGFnaW5hdGlvbigpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcblxyXG5cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuYWRkUmVzdWx0cyA9IGZ1bmN0aW9uKGRhdGEsIGRhdGFfdHlwZSlcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIGlmKGRhdGFfdHlwZT09XCJqc29uXCIpXHJcbiAgICAgICAgICAgIHsvL3RoZW4gd2UgZGlkIGEgcmVxdWVzdCB0byB0aGUgYWpheCBlbmRwb2ludCwgc28gZXhwZWN0IGFuIG9iamVjdCBiYWNrXHJcbiAgICAgICAgICAgICAgICAvL2dyYWIgdGhlIHJlc3VsdHMgYW5kIGxvYWQgaW5cclxuICAgICAgICAgICAgICAgIC8vc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5hcHBlbmQoZGF0YVsncmVzdWx0cyddKTtcclxuICAgICAgICAgICAgICAgIHNlbGYubG9hZF9tb3JlX2h0bWwgPSBkYXRhWydyZXN1bHRzJ107XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihkYXRhX3R5cGU9PVwiaHRtbFwiKVxyXG4gICAgICAgICAgICB7Ly93ZSBhcmUgZXhwZWN0aW5nIHRoZSBodG1sIG9mIHRoZSByZXN1bHRzIHBhZ2UgYmFjaywgc28gZXh0cmFjdCB0aGUgaHRtbCB3ZSBuZWVkXHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICRkYXRhX29iaiA9ICQoZGF0YSk7XHJcblxyXG4gICAgICAgICAgICAgICAgLy9zZWxmLiRpbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyLmFwcGVuZCgkZGF0YV9vYmouZmluZChzZWxmLmFqYXhfdGFyZ2V0X2F0dHIpLmh0bWwoKSk7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmxvYWRfbW9yZV9odG1sID0gJGRhdGFfb2JqLmZpbmQoc2VsZi5hamF4X3RhcmdldF9hdHRyKS5odG1sKCk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHZhciBpbmZpbml0ZV9zY3JvbGxfZW5kID0gZmFsc2U7XHJcblxyXG4gICAgICAgICAgICBpZigkKFwiPGRpdj5cIitzZWxmLmxvYWRfbW9yZV9odG1sK1wiPC9kaXY+XCIpLmZpbmQoXCJbZGF0YS1zZWFyY2gtZmlsdGVyLWFjdGlvbj0naW5maW5pdGUtc2Nyb2xsLWVuZCddXCIpLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBpbmZpbml0ZV9zY3JvbGxfZW5kID0gdHJ1ZTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgLy9pZiB0aGVyZSBpcyBhbm90aGVyIHNlbGVjdG9yIGZvciBpbmZpbml0ZSBzY3JvbGwsIGZpbmQgdGhlIGNvbnRlbnRzIG9mIHRoYXQgaW5zdGVhZFxyXG4gICAgICAgICAgICBpZihzZWxmLmluZmluaXRlX3Njcm9sbF9jb250YWluZXIhPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYubG9hZF9tb3JlX2h0bWwgPSAkKFwiPGRpdj5cIitzZWxmLmxvYWRfbW9yZV9odG1sK1wiPC9kaXY+XCIpLmZpbmQoc2VsZi5pbmZpbml0ZV9zY3JvbGxfY29udGFpbmVyKS5odG1sKCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgaWYoc2VsZi5pbmZpbml0ZV9zY3JvbGxfcmVzdWx0X2NsYXNzIT1cIlwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICB2YXIgJHJlc3VsdF9pdGVtcyA9ICQoXCI8ZGl2PlwiK3NlbGYubG9hZF9tb3JlX2h0bWwrXCI8L2Rpdj5cIikuZmluZChzZWxmLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MpO1xyXG4gICAgICAgICAgICAgICAgdmFyICRyZXN1bHRfaXRlbXNfY29udGFpbmVyID0gJCgnPGRpdi8+Jywge30pO1xyXG4gICAgICAgICAgICAgICAgJHJlc3VsdF9pdGVtc19jb250YWluZXIuYXBwZW5kKCRyZXN1bHRfaXRlbXMpO1xyXG5cclxuICAgICAgICAgICAgICAgIHNlbGYubG9hZF9tb3JlX2h0bWwgPSAkcmVzdWx0X2l0ZW1zX2NvbnRhaW5lci5odG1sKCk7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKGluZmluaXRlX3Njcm9sbF9lbmQpXHJcbiAgICAgICAgICAgIHsvL3dlIGZvdW5kIGEgZGF0YSBhdHRyaWJ1dGUgc2lnbmFsbGluZyB0aGUgbGFzdCBwYWdlIHNvIGZpbmlzaCBoZXJlXHJcblxyXG4gICAgICAgICAgICAgICAgc2VsZi5pc19tYXhfcGFnZWQgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X2xvYWRfbW9yZV9odG1sID0gc2VsZi5sb2FkX21vcmVfaHRtbDtcclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmluZmluaXRlU2Nyb2xsQXBwZW5kKHNlbGYubG9hZF9tb3JlX2h0bWwpO1xyXG5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHNlbGYubGFzdF9sb2FkX21vcmVfaHRtbCE9PXNlbGYubG9hZF9tb3JlX2h0bWwpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIC8vY2hlY2sgdG8gbWFrZSBzdXJlIHRoZSBuZXcgaHRtbCBmZXRjaGVkIGlzIGRpZmZlcmVudFxyXG4gICAgICAgICAgICAgICAgc2VsZi5sYXN0X2xvYWRfbW9yZV9odG1sID0gc2VsZi5sb2FkX21vcmVfaHRtbDtcclxuICAgICAgICAgICAgICAgIHNlbGYuaW5maW5pdGVTY3JvbGxBcHBlbmQoc2VsZi5sb2FkX21vcmVfaHRtbCk7XHJcblxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgey8vd2UgcmVjZWl2ZWQgdGhlIHNhbWUgbWVzc2FnZSBhZ2FpbiBzbyBkb24ndCBhZGQsIGFuZCB0ZWxsIFMmRiB0aGF0IHdlJ3JlIGF0IHRoZSBlbmQuLlxyXG4gICAgICAgICAgICAgICAgc2VsZi5pc19tYXhfcGFnZWQgPSB0cnVlO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgdGhpcy5pbmZpbml0ZVNjcm9sbEFwcGVuZCA9IGZ1bmN0aW9uKCRvYmplY3QpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZihzZWxmLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MhPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuJGluZmluaXRlX3Njcm9sbF9jb250YWluZXIuZmluZChzZWxmLmluZmluaXRlX3Njcm9sbF9yZXN1bHRfY2xhc3MpLmxhc3QoKS5hZnRlcigkb2JqZWN0KTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgc2VsZi4kaW5maW5pdGVfc2Nyb2xsX2NvbnRhaW5lci5hcHBlbmQoJG9iamVjdCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZVJlc3VsdHMgPSBmdW5jdGlvbihkYXRhLCBkYXRhX3R5cGUpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZihkYXRhX3R5cGU9PVwianNvblwiKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIGRpZCBhIHJlcXVlc3QgdG8gdGhlIGFqYXggZW5kcG9pbnQsIHNvIGV4cGVjdCBhbiBvYmplY3QgYmFja1xyXG4gICAgICAgICAgICAgICAgLy9ncmFiIHRoZSByZXN1bHRzIGFuZCBsb2FkIGluXHJcbiAgICAgICAgICAgICAgICB0aGlzLnJlc3VsdHNfaHRtbCA9IGRhdGFbJ3Jlc3VsdHMnXTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZiAoIHRoaXMucmVwbGFjZV9yZXN1bHRzICkge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuaHRtbCh0aGlzLnJlc3VsdHNfaHRtbCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgaWYodHlwZW9mKGRhdGFbJ2Zvcm0nXSkhPT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vcmVtb3ZlIGFsbCBldmVudHMgZnJvbSBTJkYgZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgICR0aGlzLm9mZigpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvL3JlbW92ZSBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5yZW1vdmVBamF4UGFnaW5hdGlvbigpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvL3JlZnJlc2ggdGhlIGZvcm0gKGF1dG8gY291bnQpXHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5jb3B5TGlzdEl0ZW1zQ29udGVudHMoJChkYXRhWydmb3JtJ10pLCAkdGhpcyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vdXBkYXRlIGF0dHJpYnV0ZXMgb24gZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuY29weUZvcm1BdHRyaWJ1dGVzKCQoZGF0YVsnZm9ybSddKSwgJHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAvL3JlIGluaXQgUyZGIGNsYXNzIG9uIHRoZSBmb3JtXHJcbiAgICAgICAgICAgICAgICAgICAgJHRoaXMuc2VhcmNoQW5kRmlsdGVyKHsnaXNJbml0JzogZmFsc2V9KTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAvLyR0aGlzLmZpbmQoXCJpbnB1dFwiKS5yZW1vdmVBdHRyKFwiZGlzYWJsZWRcIik7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihkYXRhX3R5cGU9PVwiaHRtbFwiKSB7Ly93ZSBhcmUgZXhwZWN0aW5nIHRoZSBodG1sIG9mIHRoZSByZXN1bHRzIHBhZ2UgYmFjaywgc28gZXh0cmFjdCB0aGUgaHRtbCB3ZSBuZWVkXHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICRkYXRhX29iaiA9ICQoZGF0YSk7XHJcblxyXG4gICAgICAgICAgICAgICAgdGhpcy5yZXN1bHRzX2h0bWwgPSAkZGF0YV9vYmouZmluZCggdGhpcy5hamF4X3RhcmdldF9hdHRyICkuaHRtbCgpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmICggdGhpcy5yZXBsYWNlX3Jlc3VsdHMgKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5odG1sKHRoaXMucmVzdWx0c19odG1sKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLnVwZGF0ZUNvbnRlbnRBcmVhcyggJGRhdGFfb2JqICk7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYgKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuZmluZChcIi5zZWFyY2hhbmRmaWx0ZXJcIikubGVuZ3RoID4gMClcclxuICAgICAgICAgICAgICAgIHsvL3RoZW4gdGhlcmUgYXJlIHNlYXJjaCBmb3JtKHMpIGluc2lkZSB0aGUgcmVzdWx0cyBjb250YWluZXIsIHNvIHJlLWluaXQgdGhlbVxyXG5cclxuICAgICAgICAgICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmZpbmQoXCIuc2VhcmNoYW5kZmlsdGVyXCIpLnNlYXJjaEFuZEZpbHRlcigpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIC8vaWYgdGhlIGN1cnJlbnQgc2VhcmNoIGZvcm0gaXMgbm90IGluc2lkZSB0aGUgcmVzdWx0cyBjb250YWluZXIsIHRoZW4gcHJvY2VlZCBhcyBub3JtYWwgYW5kIHVwZGF0ZSB0aGUgZm9ybVxyXG4gICAgICAgICAgICAgICAgaWYoc2VsZi4kYWpheF9yZXN1bHRzX2NvbnRhaW5lci5maW5kKFwiLnNlYXJjaGFuZGZpbHRlcltkYXRhLXNmLWZvcm0taWQ9J1wiICsgc2VsZi5zZmlkICsgXCInXVwiKS5sZW5ndGg9PTApIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICRuZXdfc2VhcmNoX2Zvcm0gPSAkZGF0YV9vYmouZmluZChcIi5zZWFyY2hhbmRmaWx0ZXJbZGF0YS1zZi1mb3JtLWlkPSdcIiArIHNlbGYuc2ZpZCArIFwiJ11cIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmICgkbmV3X3NlYXJjaF9mb3JtLmxlbmd0aCA9PSAxKSB7Ly90aGVuIHJlcGxhY2UgdGhlIHNlYXJjaCBmb3JtIHdpdGggdGhlIG5ldyBvbmVcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vcmVtb3ZlIGFsbCBldmVudHMgZnJvbSBTJkYgZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGhpcy5vZmYoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vcmVtb3ZlIHBhZ2luYXRpb25cclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5yZW1vdmVBamF4UGFnaW5hdGlvbigpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9yZWZyZXNoIHRoZSBmb3JtIChhdXRvIGNvdW50KVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmNvcHlMaXN0SXRlbXNDb250ZW50cygkbmV3X3NlYXJjaF9mb3JtLCAkdGhpcyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3VwZGF0ZSBhdHRyaWJ1dGVzIG9uIGZvcm1cclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5jb3B5Rm9ybUF0dHJpYnV0ZXMoJG5ld19zZWFyY2hfZm9ybSwgJHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9yZSBpbml0IFMmRiBjbGFzcyBvbiB0aGUgZm9ybVxyXG4gICAgICAgICAgICAgICAgICAgICAgICAkdGhpcy5zZWFyY2hBbmRGaWx0ZXIoeydpc0luaXQnOiBmYWxzZX0pO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgZWxzZSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyR0aGlzLmZpbmQoXCJpbnB1dFwiKS5yZW1vdmVBdHRyKFwiZGlzYWJsZWRcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBzZWxmLmlzX21heF9wYWdlZCA9IGZhbHNlOyAvL2ZvciBpbmZpbml0ZSBzY3JvbGxcclxuICAgICAgICAgICAgc2VsZi5jdXJyZW50X3BhZ2VkID0gMTsgLy9mb3IgaW5maW5pdGUgc2Nyb2xsXHJcbiAgICAgICAgICAgIHNlbGYuc2V0SW5maW5pdGVTY3JvbGxDb250YWluZXIoKTtcclxuXHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnVwZGF0ZUNvbnRlbnRBcmVhcyA9IGZ1bmN0aW9uKCAkaHRtbF9kYXRhICkge1xyXG4gICAgICAgICAgICBcclxuICAgICAgICAgICAgLy8gYWRkIGFkZGl0aW9uYWwgY29udGVudCBhcmVhc1xyXG4gICAgICAgICAgICBpZiAoIHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnMgJiYgdGhpcy5hamF4X3VwZGF0ZV9zZWN0aW9ucy5sZW5ndGggKSB7XHJcbiAgICAgICAgICAgICAgICBmb3IgKGluZGV4ID0gMDsgaW5kZXggPCB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zLmxlbmd0aDsgKytpbmRleCkge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnNbaW5kZXhdO1xyXG4gICAgICAgICAgICAgICAgICAgICQoIHNlbGVjdG9yICkuaHRtbCggJGh0bWxfZGF0YS5maW5kKCBzZWxlY3RvciApLmh0bWwoKSApO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuZmFkZUNvbnRlbnRBcmVhcyA9IGZ1bmN0aW9uKCBkaXJlY3Rpb24gKSB7XHJcbiAgICAgICAgICAgIFxyXG4gICAgICAgICAgICB2YXIgb3BhY2l0eSA9IDAuNTtcclxuICAgICAgICAgICAgaWYgKCBkaXJlY3Rpb24gPT09IFwiaW5cIiApIHtcclxuICAgICAgICAgICAgICAgIG9wYWNpdHkgPSAxO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZiAoIHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnMgJiYgdGhpcy5hamF4X3VwZGF0ZV9zZWN0aW9ucy5sZW5ndGggKSB7XHJcbiAgICAgICAgICAgICAgICBmb3IgKGluZGV4ID0gMDsgaW5kZXggPCB0aGlzLmFqYXhfdXBkYXRlX3NlY3Rpb25zLmxlbmd0aDsgKytpbmRleCkge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuYWpheF91cGRhdGVfc2VjdGlvbnNbaW5kZXhdO1xyXG4gICAgICAgICAgICAgICAgICAgICQoIHNlbGVjdG9yICkuc3RvcCh0cnVlLHRydWUpLmFuaW1hdGUoIHsgb3BhY2l0eTogb3BhY2l0eX0sIFwiZmFzdFwiICk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICBcclxuICAgICAgICAgICAgXHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnJlbW92ZVdvb0NvbW1lcmNlQ29udHJvbHMgPSBmdW5jdGlvbigpe1xyXG4gICAgICAgICAgICB2YXIgJHdvb19vcmRlcmJ5ID0gJCgnLndvb2NvbW1lcmNlLW9yZGVyaW5nIC5vcmRlcmJ5Jyk7XHJcbiAgICAgICAgICAgIHZhciAkd29vX29yZGVyYnlfZm9ybSA9ICQoJy53b29jb21tZXJjZS1vcmRlcmluZycpO1xyXG5cclxuICAgICAgICAgICAgJHdvb19vcmRlcmJ5X2Zvcm0ub2ZmKCk7XHJcbiAgICAgICAgICAgICR3b29fb3JkZXJieS5vZmYoKTtcclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmFkZFF1ZXJ5UGFyYW0gPSBmdW5jdGlvbihuYW1lLCB2YWx1ZSwgdXJsX3R5cGUpe1xyXG5cclxuICAgICAgICAgICAgaWYodHlwZW9mKHVybF90eXBlKT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIHVybF90eXBlID0gXCJhbGxcIjtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtc1t1cmxfdHlwZV1bbmFtZV0gPSB2YWx1ZTtcclxuXHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5pbml0V29vQ29tbWVyY2VDb250cm9scyA9IGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICBzZWxmLnJlbW92ZVdvb0NvbW1lcmNlQ29udHJvbHMoKTtcclxuXHJcbiAgICAgICAgICAgIHZhciAkd29vX29yZGVyYnkgPSAkKCcud29vY29tbWVyY2Utb3JkZXJpbmcgLm9yZGVyYnknKTtcclxuICAgICAgICAgICAgdmFyICR3b29fb3JkZXJieV9mb3JtID0gJCgnLndvb2NvbW1lcmNlLW9yZGVyaW5nJyk7XHJcblxyXG4gICAgICAgICAgICB2YXIgb3JkZXJfdmFsID0gXCJcIjtcclxuICAgICAgICAgICAgaWYoJHdvb19vcmRlcmJ5Lmxlbmd0aD4wKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBvcmRlcl92YWwgPSAkd29vX29yZGVyYnkudmFsKCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBvcmRlcl92YWwgPSBzZWxmLmdldFF1ZXJ5UGFyYW1Gcm9tVVJMKFwib3JkZXJieVwiLCB3aW5kb3cubG9jYXRpb24uaHJlZik7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKG9yZGVyX3ZhbD09XCJtZW51X29yZGVyXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIG9yZGVyX3ZhbCA9IFwiXCI7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIGlmKChvcmRlcl92YWwhPVwiXCIpJiYoISFvcmRlcl92YWwpKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtcy5hbGwub3JkZXJieSA9IG9yZGVyX3ZhbDtcclxuICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgICAgICR3b29fb3JkZXJieV9mb3JtLm9uKCdzdWJtaXQnLCBmdW5jdGlvbihlKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAgICAgICAgICAgICAvL3ZhciBmb3JtID0gZS50YXJnZXQ7XHJcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgJHdvb19vcmRlcmJ5Lm9uKFwiY2hhbmdlXCIsIGZ1bmN0aW9uKGUpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgdmFsID0gJCh0aGlzKS52YWwoKTtcclxuICAgICAgICAgICAgICAgIGlmKHZhbD09XCJtZW51X29yZGVyXCIpXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFsID0gXCJcIjtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmV4dHJhX3F1ZXJ5X3BhcmFtcy5hbGwub3JkZXJieSA9IHZhbDtcclxuXHJcbiAgICAgICAgICAgICAgICAkdGhpcy50cmlnZ2VyKFwic3VibWl0XCIpXHJcblxyXG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xyXG4gICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICB0aGlzLnNjcm9sbFJlc3VsdHMgPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZigoc2VsZi5zY3JvbGxfb25fYWN0aW9uPT1zZWxmLmFqYXhfYWN0aW9uKXx8KHNlbGYuc2Nyb2xsX29uX2FjdGlvbj09XCJhbGxcIikpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHNlbGYuc2Nyb2xsVG9Qb3MoKTsgLy9zY3JvbGwgdGhlIHdpbmRvdyBpZiBpdCBoYXMgYmVlbiBzZXRcclxuICAgICAgICAgICAgICAgIC8vc2VsZi5hamF4X2FjdGlvbiA9IFwiXCI7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMudXBkYXRlVXJsSGlzdG9yeSA9IGZ1bmN0aW9uKGFqYXhfcmVzdWx0c191cmwpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICB2YXIgdXNlX2hpc3RvcnlfYXBpID0gMDtcclxuICAgICAgICAgICAgaWYgKHdpbmRvdy5oaXN0b3J5ICYmIHdpbmRvdy5oaXN0b3J5LnB1c2hTdGF0ZSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdXNlX2hpc3RvcnlfYXBpID0gJHRoaXMuYXR0cihcImRhdGEtdXNlLWhpc3RvcnktYXBpXCIpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICBpZigoc2VsZi51cGRhdGVfYWpheF91cmw9PTEpJiYodXNlX2hpc3RvcnlfYXBpPT0xKSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgLy9ub3cgY2hlY2sgaWYgdGhlIGJyb3dzZXIgc3VwcG9ydHMgaGlzdG9yeSBzdGF0ZSBwdXNoIDopXHJcbiAgICAgICAgICAgICAgICBpZiAod2luZG93Lmhpc3RvcnkgJiYgd2luZG93Lmhpc3RvcnkucHVzaFN0YXRlKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGhpc3RvcnkucHVzaFN0YXRlKG51bGwsIG51bGwsIGFqYXhfcmVzdWx0c191cmwpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMucmVtb3ZlQWpheFBhZ2luYXRpb24gPSBmdW5jdGlvbigpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2Yoc2VsZi5hamF4X2xpbmtzX3NlbGVjdG9yKSE9XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyICRhamF4X2xpbmtzX29iamVjdCA9IGpRdWVyeShzZWxmLmFqYXhfbGlua3Nfc2VsZWN0b3IpO1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKCRhamF4X2xpbmtzX29iamVjdC5sZW5ndGg+MClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAkYWpheF9saW5rc19vYmplY3Qub2ZmKCk7XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuZ2V0QmFzZVVybCA9IGZ1bmN0aW9uKCB1cmwgKSB7XHJcbiAgICAgICAgICAgIC8vbm93IHNlZSBpZiB3ZSBhcmUgb24gdGhlIFVSTCB3ZSB0aGluay4uLlxyXG4gICAgICAgICAgICB2YXIgdXJsX3BhcnRzID0gdXJsLnNwbGl0KFwiP1wiKTtcclxuICAgICAgICAgICAgdmFyIHVybF9iYXNlID0gXCJcIjtcclxuXHJcbiAgICAgICAgICAgIGlmKHVybF9wYXJ0cy5sZW5ndGg+MClcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdXJsX2Jhc2UgPSB1cmxfcGFydHNbMF07XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSB7XHJcbiAgICAgICAgICAgICAgICB1cmxfYmFzZSA9IHVybDtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICByZXR1cm4gdXJsX2Jhc2U7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIHRoaXMuY2FuRmV0Y2hBamF4UmVzdWx0cyA9IGZ1bmN0aW9uKGZldGNoX3R5cGUpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBpZih0eXBlb2YoZmV0Y2hfdHlwZSk9PVwidW5kZWZpbmVkXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBmZXRjaF90eXBlID0gXCJcIjtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgdmFyIGZldGNoX2FqYXhfcmVzdWx0cyA9IGZhbHNlO1xyXG5cclxuICAgICAgICAgICAgaWYoc2VsZi5pc19hamF4PT0xKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIHdpbGwgYWpheCBzdWJtaXQgdGhlIGZvcm1cclxuXHJcbiAgICAgICAgICAgICAgICAvL2FuZCBpZiB3ZSBjYW4gZmluZCB0aGUgcmVzdWx0cyBjb250YWluZXJcclxuICAgICAgICAgICAgICAgIGlmKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIubGVuZ3RoPT0xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGZldGNoX2FqYXhfcmVzdWx0cyA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHJlc3VsdHNfdXJsID0gc2VsZi5yZXN1bHRzX3VybDsgIC8vXHJcbiAgICAgICAgICAgICAgICB2YXIgcmVzdWx0c191cmxfZW5jb2RlZCA9ICcnOyAgLy9cclxuICAgICAgICAgICAgICAgIHZhciBjdXJyZW50X3VybCA9IHdpbmRvdy5sb2NhdGlvbi5ocmVmO1xyXG5cclxuICAgICAgICAgICAgICAgIC8vaWdub3JlICMgYW5kIGV2ZXJ5dGhpbmcgYWZ0ZXJcclxuICAgICAgICAgICAgICAgIHZhciBoYXNoX3BvcyA9IHdpbmRvdy5sb2NhdGlvbi5ocmVmLmluZGV4T2YoJyMnKTtcclxuICAgICAgICAgICAgICAgIGlmKGhhc2hfcG9zIT09LTEpe1xyXG4gICAgICAgICAgICAgICAgICAgIGN1cnJlbnRfdXJsID0gd2luZG93LmxvY2F0aW9uLmhyZWYuc3Vic3RyKDAsIHdpbmRvdy5sb2NhdGlvbi5ocmVmLmluZGV4T2YoJyMnKSk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgaWYoICggKCBzZWxmLmRpc3BsYXlfcmVzdWx0X21ldGhvZD09XCJjdXN0b21fd29vY29tbWVyY2Vfc3RvcmVcIiApIHx8ICggc2VsZi5kaXNwbGF5X3Jlc3VsdF9tZXRob2Q9PVwicG9zdF90eXBlX2FyY2hpdmVcIiApICkgJiYgKCBzZWxmLmVuYWJsZV90YXhvbm9teV9hcmNoaXZlcyA9PSAxICkgKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKCBzZWxmLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSAhPT1cIlwiIClcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZldGNoX2FqYXhfcmVzdWx0cyA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmZXRjaF9hamF4X3Jlc3VsdHM7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAvKnZhciByZXN1bHRzX3VybCA9IHByb2Nlc3NfZm9ybS5nZXRSZXN1bHRzVXJsKHNlbGYsIHNlbGYucmVzdWx0c191cmwpO1xyXG4gICAgICAgICAgICAgICAgICAgICB2YXIgYWN0aXZlX3RheCA9IHByb2Nlc3NfZm9ybS5nZXRBY3RpdmVUYXgoKTtcclxuICAgICAgICAgICAgICAgICAgICAgdmFyIHF1ZXJ5X3BhcmFtcyA9IHNlbGYuZ2V0VXJsUGFyYW1zKHRydWUsICcnLCBhY3RpdmVfdGF4KTsqL1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuXHJcblxyXG5cclxuICAgICAgICAgICAgICAgIC8vbm93IHNlZSBpZiB3ZSBhcmUgb24gdGhlIFVSTCB3ZSB0aGluay4uLlxyXG4gICAgICAgICAgICAgICAgdmFyIHVybF9iYXNlID0gdGhpcy5nZXRCYXNlVXJsKCBjdXJyZW50X3VybCApO1xyXG4gICAgICAgICAgICAgICAgLy92YXIgcmVzdWx0c191cmxfYmFzZSA9IHRoaXMuZ2V0QmFzZVVybCggY3VycmVudF91cmwgKTtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgbGFuZyA9IHNlbGYuZ2V0UXVlcnlQYXJhbUZyb21VUkwoXCJsYW5nXCIsIHdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcclxuICAgICAgICAgICAgICAgIGlmKCh0eXBlb2YobGFuZykhPT1cInVuZGVmaW5lZFwiKSYmKGxhbmchPT1udWxsKSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICB1cmxfYmFzZSA9IHNlbGYuYWRkVXJsUGFyYW0odXJsX2Jhc2UsIFwibGFuZz1cIitsYW5nKTtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgc2ZpZCA9IHNlbGYuZ2V0UXVlcnlQYXJhbUZyb21VUkwoXCJzZmlkXCIsIHdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL2lmIHNmaWQgaXMgYSBudW1iZXJcclxuICAgICAgICAgICAgICAgIGlmKE51bWJlcihwYXJzZUZsb2F0KHNmaWQpKSA9PSBzZmlkKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHVybF9iYXNlID0gc2VsZi5hZGRVcmxQYXJhbSh1cmxfYmFzZSwgXCJzZmlkPVwiK3NmaWQpO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIC8vaWYgYW55IG9mIHRoZSAzIGNvbmRpdGlvbnMgYXJlIHRydWUsIHRoZW4gaXRzIGdvb2QgdG8gZ29cclxuICAgICAgICAgICAgICAgIC8vIC0gMSB8IGlmIHRoZSB1cmwgYmFzZSA9PSByZXN1bHRzX3VybFxyXG4gICAgICAgICAgICAgICAgLy8gLSAyIHwgaWYgdXJsIGJhc2UrIFwiL1wiICA9PSByZXN1bHRzX3VybCAtIGluIGNhc2Ugb2YgdXNlciBlcnJvciBpbiB0aGUgcmVzdWx0cyBVUkxcclxuICAgICAgICAgICAgICAgIC8vIC0gMyB8IGlmIHRoZSByZXN1bHRzIFVSTCBoYXMgdXJsIHBhcmFtcywgYW5kIHRoZSBjdXJyZW50IHVybCBzdGFydHMgd2l0aCB0aGUgcmVzdWx0cyBVUkwgXHJcblxyXG4gICAgICAgICAgICAgICAgLy90cmltIGFueSB0cmFpbGluZyBzbGFzaCBmb3IgZWFzaWVyIGNvbXBhcmlzb246XHJcbiAgICAgICAgICAgICAgICB1cmxfYmFzZSA9IHVybF9iYXNlLnJlcGxhY2UoL1xcLyQvLCAnJyk7XHJcbiAgICAgICAgICAgICAgICByZXN1bHRzX3VybCA9IHJlc3VsdHNfdXJsLnJlcGxhY2UoL1xcLyQvLCAnJyk7XHJcbiAgICAgICAgICAgICAgICByZXN1bHRzX3VybF9lbmNvZGVkID0gZW5jb2RlVVJJKHJlc3VsdHNfdXJsKTtcclxuICAgICAgICAgICAgICAgIFxyXG5cclxuICAgICAgICAgICAgICAgIHZhciBjdXJyZW50X3VybF9jb250YWluc19yZXN1bHRzX3VybCA9IC0xO1xyXG4gICAgICAgICAgICAgICAgaWYoKHVybF9iYXNlPT1yZXN1bHRzX3VybCl8fCh1cmxfYmFzZS50b0xvd2VyQ2FzZSgpPT1yZXN1bHRzX3VybF9lbmNvZGVkLnRvTG93ZXJDYXNlKCkpICApe1xyXG4gICAgICAgICAgICAgICAgICAgIGN1cnJlbnRfdXJsX2NvbnRhaW5zX3Jlc3VsdHNfdXJsID0gMTtcclxuICAgICAgICAgICAgICAgIH0gZWxzZSB7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKCByZXN1bHRzX3VybC5pbmRleE9mKCAnPycgKSAhPT0gLTEgJiYgY3VycmVudF91cmwubGFzdEluZGV4T2YocmVzdWx0c191cmwsIDApID09PSAwICkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBjdXJyZW50X3VybF9jb250YWluc19yZXN1bHRzX3VybCA9IDE7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKHNlbGYub25seV9yZXN1bHRzX2FqYXg9PTEpXHJcbiAgICAgICAgICAgICAgICB7Ly9pZiBhIHVzZXIgaGFzIGNob3NlbiB0byBvbmx5IGFsbG93IGFqYXggb24gcmVzdWx0cyBwYWdlcyAoZGVmYXVsdCBiZWhhdmlvdXIpXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmKCBjdXJyZW50X3VybF9jb250YWluc19yZXN1bHRzX3VybCA+IC0xKVxyXG4gICAgICAgICAgICAgICAgICAgIHsvL3RoaXMgbWVhbnMgdGhlIGN1cnJlbnQgVVJMIGNvbnRhaW5zIHRoZSByZXN1bHRzIHVybCwgd2hpY2ggbWVhbnMgd2UgY2FuIGRvIGFqYXhcclxuICAgICAgICAgICAgICAgICAgICAgICAgZmV0Y2hfYWpheF9yZXN1bHRzID0gdHJ1ZTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgZmV0Y2hfYWpheF9yZXN1bHRzID0gZmFsc2U7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGlmKGZldGNoX3R5cGU9PVwicGFnaW5hdGlvblwiKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoIGN1cnJlbnRfdXJsX2NvbnRhaW5zX3Jlc3VsdHNfdXJsID4gLTEpXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHsvL3RoaXMgbWVhbnMgdGhlIGN1cnJlbnQgVVJMIGNvbnRhaW5zIHRoZSByZXN1bHRzIHVybCwgd2hpY2ggbWVhbnMgd2UgY2FuIGRvIGFqYXhcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL2Rvbid0IGFqYXggcGFnaW5hdGlvbiB3aGVuIG5vdCBvbiBhIFMmRiBwYWdlXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBmZXRjaF9hamF4X3Jlc3VsdHMgPSBmYWxzZTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgcmV0dXJuIGZldGNoX2FqYXhfcmVzdWx0cztcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIHRoaXMuc2V0dXBBamF4UGFnaW5hdGlvbiA9IGZ1bmN0aW9uKClcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIC8vaW5maW5pdGUgc2Nyb2xsXHJcbiAgICAgICAgICAgIGlmKHRoaXMucGFnaW5hdGlvbl90eXBlPT09XCJpbmZpbml0ZV9zY3JvbGxcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIGluZmluaXRlX3Njcm9sbF9lbmQgPSBmYWxzZTtcclxuICAgICAgICAgICAgICAgIGlmKHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuZmluZChcIltkYXRhLXNlYXJjaC1maWx0ZXItYWN0aW9uPSdpbmZpbml0ZS1zY3JvbGwtZW5kJ11cIikubGVuZ3RoPjApXHJcbiAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgaW5maW5pdGVfc2Nyb2xsX2VuZCA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5pc19tYXhfcGFnZWQgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgIGlmKHBhcnNlSW50KHRoaXMuaW5zdGFuY2VfbnVtYmVyKT09PTEpIHtcclxuICAgICAgICAgICAgICAgICAgICAkKHdpbmRvdykub2ZmKFwic2Nyb2xsXCIsIHNlbGYub25XaW5kb3dTY3JvbGwpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZiAoc2VsZi5jYW5GZXRjaEFqYXhSZXN1bHRzKFwicGFnaW5hdGlvblwiKSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkKHdpbmRvdykub24oXCJzY3JvbGxcIiwgc2VsZi5vbldpbmRvd1Njcm9sbCk7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIGVsc2UgaWYodHlwZW9mKHNlbGYuYWpheF9saW5rc19zZWxlY3Rvcik9PVwidW5kZWZpbmVkXCIpIHtcclxuICAgICAgICAgICAgICAgIHJldHVybjtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIHtcclxuICAgICAgICAgICAgICAgICQoZG9jdW1lbnQpLm9mZignY2xpY2snLCBzZWxmLmFqYXhfbGlua3Nfc2VsZWN0b3IpO1xyXG4gICAgICAgICAgICAgICAgJChkb2N1bWVudCkub2ZmKHNlbGYuYWpheF9saW5rc19zZWxlY3Rvcik7XHJcbiAgICAgICAgICAgICAgICAkKHNlbGYuYWpheF9saW5rc19zZWxlY3Rvcikub2ZmKCk7XHJcblxyXG4gICAgICAgICAgICAgICAgJChkb2N1bWVudCkub24oJ2NsaWNrJywgc2VsZi5hamF4X2xpbmtzX3NlbGVjdG9yLCBmdW5jdGlvbihlKXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYoc2VsZi5jYW5GZXRjaEFqYXhSZXN1bHRzKFwicGFnaW5hdGlvblwiKSlcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBsaW5rID0galF1ZXJ5KHRoaXMpLmF0dHIoJ2hyZWYnKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5hamF4X2FjdGlvbiA9IFwicGFnaW5hdGlvblwiO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHBhZ2VOdW1iZXIgPSBzZWxmLmdldFBhZ2VkRnJvbVVSTChsaW5rKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuJGFqYXhfcmVzdWx0c19jb250YWluZXIuYXR0cihcImRhdGEtcGFnZWRcIiwgcGFnZU51bWJlcik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmZldGNoQWpheFJlc3VsdHMoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9KTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH07XHJcblxyXG4gICAgICAgIHRoaXMuZ2V0UGFnZWRGcm9tVVJMID0gZnVuY3Rpb24oVVJMKXtcclxuXHJcbiAgICAgICAgICAgIHZhciBwYWdlZFZhbCA9IDE7XHJcbiAgICAgICAgICAgIC8vZmlyc3QgdGVzdCB0byBzZWUgaWYgd2UgaGF2ZSBcIi9wYWdlLzQvXCIgaW4gdGhlIFVSTFxyXG4gICAgICAgICAgICB2YXIgdHBWYWwgPSBzZWxmLmdldFF1ZXJ5UGFyYW1Gcm9tVVJMKFwic2ZfcGFnZWRcIiwgVVJMKTtcclxuICAgICAgICAgICAgaWYoKHR5cGVvZih0cFZhbCk9PVwic3RyaW5nXCIpfHwodHlwZW9mKHRwVmFsKT09XCJudW1iZXJcIikpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHBhZ2VkVmFsID0gdHBWYWw7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHJldHVybiBwYWdlZFZhbDtcclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmdldFF1ZXJ5UGFyYW1Gcm9tVVJMID0gZnVuY3Rpb24obmFtZSwgVVJMKXtcclxuXHJcbiAgICAgICAgICAgIHZhciBxc3RyaW5nID0gXCI/XCIrVVJMLnNwbGl0KCc/JylbMV07XHJcbiAgICAgICAgICAgIGlmKHR5cGVvZihxc3RyaW5nKSE9XCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgdmFyIHZhbCA9IGRlY29kZVVSSUNvbXBvbmVudCgobmV3IFJlZ0V4cCgnWz98Jl0nICsgbmFtZSArICc9JyArICcoW14mO10rPykoJnwjfDt8JCknKS5leGVjKHFzdHJpbmcpfHxbLFwiXCJdKVsxXS5yZXBsYWNlKC9cXCsvZywgJyUyMCcpKXx8bnVsbDtcclxuICAgICAgICAgICAgICAgIHJldHVybiB2YWw7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgcmV0dXJuIFwiXCI7XHJcbiAgICAgICAgfTtcclxuXHJcblxyXG5cclxuICAgICAgICB0aGlzLmZvcm1VcGRhdGVkID0gZnVuY3Rpb24oZSl7XHJcblxyXG4gICAgICAgICAgICAvL2UucHJldmVudERlZmF1bHQoKTtcclxuICAgICAgICAgICAgaWYoc2VsZi5hdXRvX3VwZGF0ZT09MSkge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5zdWJtaXRGb3JtKCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZigoc2VsZi5hdXRvX3VwZGF0ZT09MCkmJihzZWxmLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlPT0xKSlcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5mb3JtVXBkYXRlZEZldGNoQWpheCgpO1xyXG4gICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgdGhpcy5mb3JtVXBkYXRlZEZldGNoQWpheCA9IGZ1bmN0aW9uKCl7XHJcblxyXG4gICAgICAgICAgICAvL2xvb3AgdGhyb3VnaCBhbGwgdGhlIGZpZWxkcyBhbmQgYnVpbGQgdGhlIFVSTFxyXG4gICAgICAgICAgICBzZWxmLmZldGNoQWpheEZvcm0oKTtcclxuXHJcblxyXG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XHJcbiAgICAgICAgfTtcclxuXHJcbiAgICAgICAgLy9tYWtlIGFueSBjb3JyZWN0aW9ucy91cGRhdGVzIHRvIGZpZWxkcyBiZWZvcmUgdGhlIHN1Ym1pdCBjb21wbGV0ZXNcclxuICAgICAgICB0aGlzLnNldEZpZWxkcyA9IGZ1bmN0aW9uKGUpe1xyXG5cclxuICAgICAgICAgICAgLy9pZihzZWxmLmlzX2FqYXg9PTApIHtcclxuXHJcbiAgICAgICAgICAgICAgICAvL3NvbWV0aW1lcyB0aGUgZm9ybSBpcyBzdWJtaXR0ZWQgd2l0aG91dCB0aGUgc2xpZGVyIHlldCBoYXZpbmcgdXBkYXRlZCwgYW5kIGFzIHdlIGdldCBvdXIgdmFsdWVzIGZyb21cclxuICAgICAgICAgICAgICAgIC8vdGhlIHNsaWRlciBhbmQgbm90IGlucHV0cywgd2UgbmVlZCB0byBjaGVjayBpdCBpZiBuZWVkcyB0byBiZSBzZXRcclxuICAgICAgICAgICAgICAgIC8vb25seSBvY2N1cnMgaWYgYWpheCBpcyBvZmYsIGFuZCBhdXRvc3VibWl0IG9uXHJcbiAgICAgICAgICAgICAgICBzZWxmLiRmaWVsZHMuZWFjaChmdW5jdGlvbigpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICRmaWVsZCA9ICQodGhpcyk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciByYW5nZV9kaXNwbGF5X3ZhbHVlcyA9ICRmaWVsZC5maW5kKCcuc2YtbWV0YS1yYW5nZS1zbGlkZXInKS5hdHRyKFwiZGF0YS1kaXNwbGF5LXZhbHVlcy1hc1wiKTsvL2RhdGEtZGlzcGxheS12YWx1ZXMtYXM9XCJ0ZXh0XCJcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYocmFuZ2VfZGlzcGxheV92YWx1ZXM9PT1cInRleHRpbnB1dFwiKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZigkZmllbGQuZmluZChcIi5tZXRhLXNsaWRlclwiKS5sZW5ndGg+MCl7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiLm1ldGEtc2xpZGVyXCIpLmVhY2goZnVuY3Rpb24gKGluZGV4KSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNsaWRlcl9vYmplY3QgPSAkKHRoaXMpWzBdO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRzbGlkZXJfZWwgPSAkKHRoaXMpLmNsb3Nlc3QoXCIuc2YtbWV0YS1yYW5nZS1zbGlkZXJcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL3ZhciBtaW5WYWwgPSAkc2xpZGVyX2VsLmF0dHIoXCJkYXRhLW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vdmFyIG1heFZhbCA9ICRzbGlkZXJfZWwuYXR0cihcImRhdGEtbWF4XCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIG1pblZhbCA9ICRzbGlkZXJfZWwuZmluZChcIi5zZi1yYW5nZS1taW5cIikudmFsKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgbWF4VmFsID0gJHNsaWRlcl9lbC5maW5kKFwiLnNmLXJhbmdlLW1heFwiKS52YWwoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNsaWRlcl9vYmplY3Qubm9VaVNsaWRlci5zZXQoW21pblZhbCwgbWF4VmFsXSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9KTtcclxuICAgICAgICAgICAgLy99XHJcblxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgLy9zdWJtaXRcclxuICAgICAgICB0aGlzLnN1Ym1pdEZvcm0gPSBmdW5jdGlvbihlKXtcclxuXHJcbiAgICAgICAgICAgIC8vbG9vcCB0aHJvdWdoIGFsbCB0aGUgZmllbGRzIGFuZCBidWlsZCB0aGUgVVJMXHJcbiAgICAgICAgICAgIGlmKHNlbGYuaXNTdWJtaXR0aW5nID09IHRydWUpIHtcclxuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgc2VsZi5zZXRGaWVsZHMoKTtcclxuICAgICAgICAgICAgc2VsZi5jbGVhclRpbWVyKCk7XHJcblxyXG4gICAgICAgICAgICBzZWxmLmlzU3VibWl0dGluZyA9IHRydWU7XHJcblxyXG4gICAgICAgICAgICBwcm9jZXNzX2Zvcm0uc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmwoc2VsZiwgc2VsZi5yZXN1bHRzX3VybCk7XHJcblxyXG4gICAgICAgICAgICBzZWxmLiRhamF4X3Jlc3VsdHNfY29udGFpbmVyLmF0dHIoXCJkYXRhLXBhZ2VkXCIsIDEpOyAvL2luaXQgcGFnZWRcclxuXHJcbiAgICAgICAgICAgIGlmKHNlbGYuY2FuRmV0Y2hBamF4UmVzdWx0cygpKVxyXG4gICAgICAgICAgICB7Ly90aGVuIHdlIHdpbGwgYWpheCBzdWJtaXQgdGhlIGZvcm1cclxuXHJcbiAgICAgICAgICAgICAgICBzZWxmLmFqYXhfYWN0aW9uID0gXCJzdWJtaXRcIjsgLy9zbyB3ZSBrbm93IGl0IHdhc24ndCBwYWdpbmF0aW9uXHJcbiAgICAgICAgICAgICAgICBzZWxmLmZldGNoQWpheFJlc3VsdHMoKTtcclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlXHJcbiAgICAgICAgICAgIHsvL3RoZW4gd2Ugd2lsbCBzaW1wbHkgcmVkaXJlY3QgdG8gdGhlIFJlc3VsdHMgVVJMXHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHJlc3VsdHNfdXJsID0gcHJvY2Vzc19mb3JtLmdldFJlc3VsdHNVcmwoc2VsZiwgc2VsZi5yZXN1bHRzX3VybCk7XHJcbiAgICAgICAgICAgICAgICB2YXIgcXVlcnlfcGFyYW1zID0gc2VsZi5nZXRVcmxQYXJhbXModHJ1ZSwgJycpO1xyXG4gICAgICAgICAgICAgICAgcmVzdWx0c191cmwgPSBzZWxmLmFkZFVybFBhcmFtKHJlc3VsdHNfdXJsLCBxdWVyeV9wYXJhbXMpO1xyXG5cclxuICAgICAgICAgICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gcmVzdWx0c191cmw7XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICB9O1xyXG4gICAgICAgIHRoaXMucmVzZXRGb3JtID0gZnVuY3Rpb24oc3VibWl0X2Zvcm0pXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAvL3Vuc2V0IGFsbCBmaWVsZHNcclxuICAgICAgICAgICAgc2VsZi4kZmllbGRzLmVhY2goZnVuY3Rpb24oKXtcclxuXHJcbiAgICAgICAgICAgICAgICB2YXIgJGZpZWxkID0gJCh0aGlzKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHQkZmllbGQucmVtb3ZlQXR0cihcImRhdGEtc2YtdGF4b25vbXktYXJjaGl2ZVwiKTtcclxuXHRcdFx0XHRcclxuICAgICAgICAgICAgICAgIC8vc3RhbmRhcmQgZmllbGQgdHlwZXNcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwic2VsZWN0Om5vdChbbXVsdGlwbGU9J211bHRpcGxlJ10pID4gb3B0aW9uOmZpcnN0LWNoaWxkXCIpLnByb3AoXCJzZWxlY3RlZFwiLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwic2VsZWN0W211bHRpcGxlPSdtdWx0aXBsZSddID4gb3B0aW9uXCIpLnByb3AoXCJzZWxlY3RlZFwiLCBmYWxzZSk7XHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcImlucHV0W3R5cGU9J2NoZWNrYm94J11cIikucHJvcChcImNoZWNrZWRcIiwgZmFsc2UpO1xyXG4gICAgICAgICAgICAgICAgJGZpZWxkLmZpbmQoXCI+IHVsID4gbGk6Zmlyc3QtY2hpbGQgaW5wdXRbdHlwZT0ncmFkaW8nXVwiKS5wcm9wKFwiY2hlY2tlZFwiLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiaW5wdXRbdHlwZT0ndGV4dCddXCIpLnZhbChcIlwiKTtcclxuICAgICAgICAgICAgICAgICRmaWVsZC5maW5kKFwiLnNmLW9wdGlvbi1hY3RpdmVcIikucmVtb3ZlQ2xhc3MoXCJzZi1vcHRpb24tYWN0aXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgJGZpZWxkLmZpbmQoXCI+IHVsID4gbGk6Zmlyc3QtY2hpbGQgaW5wdXRbdHlwZT0ncmFkaW8nXVwiKS5wYXJlbnQoKS5hZGRDbGFzcyhcInNmLW9wdGlvbi1hY3RpdmVcIik7IC8vcmUgYWRkIGFjdGl2ZSBjbGFzcyB0byBmaXJzdCBcImRlZmF1bHRcIiBvcHRpb25cclxuXHJcbiAgICAgICAgICAgICAgICAvL251bWJlciByYW5nZSAtIDIgbnVtYmVyIGlucHV0IGZpZWxkc1xyXG4gICAgICAgICAgICAgICAgJGZpZWxkLmZpbmQoXCJpbnB1dFt0eXBlPSdudW1iZXInXVwiKS5lYWNoKGZ1bmN0aW9uKGluZGV4KXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyICR0aGlzSW5wdXQgPSAkKHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICBpZigkdGhpc0lucHV0LnBhcmVudCgpLnBhcmVudCgpLmhhc0NsYXNzKFwic2YtbWV0YS1yYW5nZVwiKSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoaW5kZXg9PTApIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzSW5wdXQudmFsKCR0aGlzSW5wdXQuYXR0cihcIm1pblwiKSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgICAgICAgICAgZWxzZSBpZihpbmRleD09MSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHRoaXNJbnB1dC52YWwoJHRoaXNJbnB1dC5hdHRyKFwibWF4XCIpKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL21ldGEgLyBudW1iZXJzIHdpdGggMiBpbnB1dHMgKGZyb20gLyB0byBmaWVsZHMpIC0gc2Vjb25kIGlucHV0IG11c3QgYmUgcmVzZXQgdG8gbWF4IHZhbHVlXHJcbiAgICAgICAgICAgICAgICB2YXIgJG1ldGFfc2VsZWN0X2Zyb21fdG8gPSAkZmllbGQuZmluZChcIi5zZi1tZXRhLXJhbmdlLXNlbGVjdC1mcm9tdG9cIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYoJG1ldGFfc2VsZWN0X2Zyb21fdG8ubGVuZ3RoPjApIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHN0YXJ0X21pbiA9ICRtZXRhX3NlbGVjdF9mcm9tX3RvLmF0dHIoXCJkYXRhLW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc3RhcnRfbWF4ID0gJG1ldGFfc2VsZWN0X2Zyb21fdG8uYXR0cihcImRhdGEtbWF4XCIpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAkbWV0YV9zZWxlY3RfZnJvbV90by5maW5kKFwic2VsZWN0XCIpLmVhY2goZnVuY3Rpb24oaW5kZXgpe1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICR0aGlzSW5wdXQgPSAkKHRoaXMpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgaWYoaW5kZXg9PTApIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkdGhpc0lucHV0LnZhbChzdGFydF9taW4pO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGVsc2UgaWYoaW5kZXg9PTEpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICR0aGlzSW5wdXQudmFsKHN0YXJ0X21heCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyICRtZXRhX3JhZGlvX2Zyb21fdG8gPSAkZmllbGQuZmluZChcIi5zZi1tZXRhLXJhbmdlLXJhZGlvLWZyb210b1wiKTtcclxuXHJcbiAgICAgICAgICAgICAgICBpZigkbWV0YV9yYWRpb19mcm9tX3RvLmxlbmd0aD4wKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBzdGFydF9taW4gPSAkbWV0YV9yYWRpb19mcm9tX3RvLmF0dHIoXCJkYXRhLW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgc3RhcnRfbWF4ID0gJG1ldGFfcmFkaW9fZnJvbV90by5hdHRyKFwiZGF0YS1tYXhcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkcmFkaW9fZ3JvdXBzID0gJG1ldGFfcmFkaW9fZnJvbV90by5maW5kKCcuc2YtaW5wdXQtcmFuZ2UtcmFkaW8nKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgJHJhZGlvX2dyb3Vwcy5lYWNoKGZ1bmN0aW9uKGluZGV4KXtcclxuXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgJHJhZGlvcyA9ICQodGhpcykuZmluZChcIi5zZi1pbnB1dC1yYWRpb1wiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgJHJhZGlvcy5wcm9wKFwiY2hlY2tlZFwiLCBmYWxzZSk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZihpbmRleD09MClcclxuICAgICAgICAgICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJHJhZGlvcy5maWx0ZXIoJ1t2YWx1ZT1cIicrc3RhcnRfbWluKydcIl0nKS5wcm9wKFwiY2hlY2tlZFwiLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgICAgICBlbHNlIGlmKGluZGV4PT0xKVxyXG4gICAgICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkcmFkaW9zLmZpbHRlcignW3ZhbHVlPVwiJytzdGFydF9tYXgrJ1wiXScpLnByb3AoXCJjaGVja2VkXCIsIHRydWUpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAvL251bWJlciBzbGlkZXIgLSBub1VpU2xpZGVyXHJcbiAgICAgICAgICAgICAgICAkZmllbGQuZmluZChcIi5tZXRhLXNsaWRlclwiKS5lYWNoKGZ1bmN0aW9uKGluZGV4KXtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIHNsaWRlcl9vYmplY3QgPSAkKHRoaXMpWzBdO1xyXG4gICAgICAgICAgICAgICAgICAgIC8qdmFyIHNsaWRlcl9vYmplY3QgPSAkY29udGFpbmVyLmZpbmQoXCIubWV0YS1zbGlkZXJcIilbMF07XHJcbiAgICAgICAgICAgICAgICAgICAgIHZhciBzbGlkZXJfdmFsID0gc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLmdldCgpOyovXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciAkc2xpZGVyX2VsID0gJCh0aGlzKS5jbG9zZXN0KFwiLnNmLW1ldGEtcmFuZ2Utc2xpZGVyXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBtaW5WYWwgPSAkc2xpZGVyX2VsLmF0dHIoXCJkYXRhLW1pblwiKTtcclxuICAgICAgICAgICAgICAgICAgICB2YXIgbWF4VmFsID0gJHNsaWRlcl9lbC5hdHRyKFwiZGF0YS1tYXhcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgc2xpZGVyX29iamVjdC5ub1VpU2xpZGVyLnNldChbbWluVmFsLCBtYXhWYWxdKTtcclxuXHJcbiAgICAgICAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL25lZWQgdG8gc2VlIGlmIGFueSBhcmUgY29tYm9ib3ggYW5kIGFjdCBhY2NvcmRpbmdseVxyXG4gICAgICAgICAgICAgICAgdmFyICRjb21ib2JveCA9ICRmaWVsZC5maW5kKFwic2VsZWN0W2RhdGEtY29tYm9ib3g9JzEnXVwiKTtcclxuICAgICAgICAgICAgICAgIGlmKCRjb21ib2JveC5sZW5ndGg+MClcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mICRjb21ib2JveC5jaG9zZW4gIT0gXCJ1bmRlZmluZWRcIilcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRjb21ib2JveC50cmlnZ2VyKFwiY2hvc2VuOnVwZGF0ZWRcIik7IC8vZm9yIGNob3NlbiBvbmx5XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICRjb21ib2JveC52YWwoJycpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAkY29tYm9ib3gudHJpZ2dlcignY2hhbmdlLnNlbGVjdDInKTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICB9XHJcblxyXG5cclxuICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgIHNlbGYuY2xlYXJUaW1lcigpO1xyXG5cclxuXHJcblxyXG4gICAgICAgICAgICBpZihzdWJtaXRfZm9ybT09XCJhbHdheXNcIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgc2VsZi5zdWJtaXRGb3JtKCk7XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgZWxzZSBpZihzdWJtaXRfZm9ybT09XCJuZXZlclwiKVxyXG4gICAgICAgICAgICB7XHJcbiAgICAgICAgICAgICAgICBpZih0aGlzLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlPT0xKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHNlbGYuZm9ybVVwZGF0ZWRGZXRjaEFqYXgoKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICBlbHNlIGlmKHN1Ym1pdF9mb3JtPT1cImF1dG9cIilcclxuICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgaWYodGhpcy5hdXRvX3VwZGF0ZT09dHJ1ZSlcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBzZWxmLnN1Ym1pdEZvcm0oKTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2VcclxuICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICBpZih0aGlzLmF1dG9fY291bnRfcmVmcmVzaF9tb2RlPT0xKVxyXG4gICAgICAgICAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5mb3JtVXBkYXRlZEZldGNoQWpheCgpO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICB9O1xyXG5cclxuICAgICAgICB0aGlzLmluaXQoKTtcclxuXHJcbiAgICAgICAgdmFyIGV2ZW50X2RhdGEgPSB7fTtcclxuICAgICAgICBldmVudF9kYXRhLnNmaWQgPSBzZWxmLnNmaWQ7XHJcbiAgICAgICAgZXZlbnRfZGF0YS50YXJnZXRTZWxlY3RvciA9IHNlbGYuYWpheF90YXJnZXRfYXR0cjtcclxuICAgICAgICBldmVudF9kYXRhLm9iamVjdCA9IHRoaXM7XHJcbiAgICAgICAgaWYob3B0cy5pc0luaXQpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICBzZWxmLnRyaWdnZXJFdmVudChcInNmOmluaXRcIiwgZXZlbnRfZGF0YSk7XHJcbiAgICAgICAgfVxyXG5cclxuICAgIH0pO1xyXG59O1xyXG5cbn0pLmNhbGwodGhpcyx0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsIDogdHlwZW9mIHNlbGYgIT09IFwidW5kZWZpbmVkXCIgPyBzZWxmIDogdHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvdyA6IHt9KVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZGF0YTphcHBsaWNhdGlvbi9qc29uO2NoYXJzZXQ6dXRmLTg7YmFzZTY0LGV5SjJaWEp6YVc5dUlqb3pMQ0p6YjNWeVkyVnpJanBiSW5OeVl5OXdkV0pzYVdNdllYTnpaWFJ6TDJwekwybHVZMngxWkdWekwzQnNkV2RwYmk1cWN5SmRMQ0p1WVcxbGN5STZXMTBzSW0xaGNIQnBibWR6SWpvaU8wRkJRVUU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJJaXdpWm1sc1pTSTZJbWRsYm1WeVlYUmxaQzVxY3lJc0luTnZkWEpqWlZKdmIzUWlPaUlpTENKemIzVnlZMlZ6UTI5dWRHVnVkQ0k2V3lKY2NseHVkbUZ5SUNRZ1hIUmNkRngwWEhROUlDaDBlWEJsYjJZZ2QybHVaRzkzSUNFOVBTQmNJblZ1WkdWbWFXNWxaRndpSUQ4Z2QybHVaRzkzV3lkcVVYVmxjbmtuWFNBNklIUjVjR1Z2WmlCbmJHOWlZV3dnSVQwOUlGd2lkVzVrWldacGJtVmtYQ0lnUHlCbmJHOWlZV3hiSjJwUmRXVnllU2RkSURvZ2JuVnNiQ2s3WEhKY2JuWmhjaUJ6ZEdGMFpTQmNkRngwWEhROUlISmxjWFZwY21Vb0p5NHZjM1JoZEdVbktUdGNjbHh1ZG1GeUlIQnliMk5sYzNOZlptOXliU0JjZEQwZ2NtVnhkV2x5WlNnbkxpOXdjbTlqWlhOelgyWnZjbTBuS1R0Y2NseHVkbUZ5SUc1dlZXbFRiR2xrWlhKY2RGeDBQU0J5WlhGMWFYSmxLQ2R1YjNWcGMyeHBaR1Z5SnlrN1hISmNiaTh2ZG1GeUlHTnZiMnRwWlhNZ0lDQWdJQ0FnSUNBOUlISmxjWFZwY21Vb0oycHpMV052YjJ0cFpTY3BPMXh5WEc1MllYSWdkR2hwY21SUVlYSjBlU0FnSUNBZ0lEMGdjbVZ4ZFdseVpTZ25MaTkwYUdseVpIQmhjblI1SnlrN1hISmNibHh5WEc1M2FXNWtiM2N1YzJWaGNtTm9RVzVrUm1sc2RHVnlJRDBnZTF4eVhHNGdJQ0FnWlhoMFpXNXphVzl1Y3pvZ1cxMHNYSEpjYmlBZ0lDQnlaV2RwYzNSbGNrVjRkR1Z1YzJsdmJqb2dablZ1WTNScGIyNG9JR1Y0ZEdWdWMybHZiazVoYldVZ0tTQjdYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NWxlSFJsYm5OcGIyNXpMbkIxYzJnb0lHVjRkR1Z1YzJsdmJrNWhiV1VnS1R0Y2NseHVJQ0FnSUgxY2NseHVmVHRjY2x4dVhISmNibTF2WkhWc1pTNWxlSEJ2Y25SeklEMGdablZ1WTNScGIyNG9iM0IwYVc5dWN5bGNjbHh1ZTF4eVhHNGdJQ0FnZG1GeUlHUmxabUYxYkhSeklEMGdlMXh5WEc0Z0lDQWdJQ0FnSUhOMFlYSjBUM0JsYm1Wa09pQm1ZV3h6WlN4Y2NseHVJQ0FnSUNBZ0lDQnBjMGx1YVhRNklIUnlkV1VzWEhKY2JpQWdJQ0FnSUNBZ1lXTjBhVzl1T2lCY0lsd2lYSEpjYmlBZ0lDQjlPMXh5WEc1Y2NseHVJQ0FnSUhaaGNpQnZjSFJ6SUQwZ2FsRjFaWEo1TG1WNGRHVnVaQ2hrWldaaGRXeDBjeXdnYjNCMGFXOXVjeWs3WEhKY2JpQWdJQ0JjY2x4dUlDQWdJSFJvYVhKa1VHRnlkSGt1YVc1cGRDZ3BPMXh5WEc0Z0lDQWdYSEpjYmlBZ0lDQXZMMnh2YjNBZ2RHaHliM1ZuYUNCbFlXTm9JR2wwWlcwZ2JXRjBZMmhsWkZ4eVhHNGdJQ0FnZEdocGN5NWxZV05vS0daMWJtTjBhVzl1S0NsY2NseHVJQ0FnSUh0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnZG1GeUlDUjBhR2x6SUQwZ0pDaDBhR2x6S1R0Y2NseHVJQ0FnSUNBZ0lDQjJZWElnYzJWc1ppQTlJSFJvYVhNN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1elptbGtJRDBnSkhSb2FYTXVZWFIwY2loY0ltUmhkR0V0YzJZdFptOXliUzFwWkZ3aUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2MzUmhkR1V1WVdSa1UyVmhjbU5vUm05eWJTaDBhR2x6TG5ObWFXUXNJSFJvYVhNcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMaVJtYVdWc1pITWdQU0FrZEdocGN5NW1hVzVrS0Z3aVBpQjFiQ0ErSUd4cFhDSXBPeUF2TDJFZ2NtVm1aWEpsYm1ObElIUnZJR1ZoWTJnZ1ptbGxiR1J6SUhCaGNtVnVkQ0JNU1Z4eVhHNWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtVnVZV0pzWlY5MFlYaHZibTl0ZVY5aGNtTm9hWFpsY3lBOUlDUjBhR2x6TG1GMGRISW9KMlJoZEdFdGRHRjRiMjV2YlhrdFlYSmphR2wyWlhNbktUdGNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtTjFjbkpsYm5SZmRHRjRiMjV2YlhsZllYSmphR2wyWlNBOUlDUjBhR2x6TG1GMGRISW9KMlJoZEdFdFkzVnljbVZ1ZEMxMFlYaHZibTl0ZVMxaGNtTm9hWFpsSnlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloMGFHbHpMbVZ1WVdKc1pWOTBZWGh2Ym05dGVWOWhjbU5vYVhabGN5azlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG1WdVlXSnNaVjkwWVhodmJtOXRlVjloY21Ob2FYWmxjeUE5SUZ3aU1Gd2lPMXh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQnBaaWgwZVhCbGIyWW9kR2hwY3k1amRYSnlaVzUwWDNSaGVHOXViMjE1WDJGeVkyaHBkbVVwUFQxY0luVnVaR1ZtYVc1bFpGd2lLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZEdocGN5NWpkWEp5Wlc1MFgzUmhlRzl1YjIxNVgyRnlZMmhwZG1VZ1BTQmNJbHdpTzF4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2NISnZZMlZ6YzE5bWIzSnRMbWx1YVhRb2MyVnNaaTVsYm1GaWJHVmZkR0Y0YjI1dmJYbGZZWEpqYUdsMlpYTXNJSE5sYkdZdVkzVnljbVZ1ZEY5MFlYaHZibTl0ZVY5aGNtTm9hWFpsS1R0Y2NseHVJQ0FnSUNBZ0lDQXZMM0J5YjJObGMzTmZabTl5YlM1elpYUlVZWGhCY21Ob2FYWmxVbVZ6ZFd4MGMxVnliQ2h6Wld4bUtUdGNjbHh1SUNBZ0lDQWdJQ0J3Y205alpYTnpYMlp2Y20wdVpXNWhZbXhsU1c1d2RYUnpLSE5sYkdZcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNCcFppaDBlWEJsYjJZb2RHaHBjeTVsZUhSeVlWOXhkV1Z5ZVY5d1lYSmhiWE1wUFQxY0luVnVaR1ZtYVc1bFpGd2lLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZEdocGN5NWxlSFJ5WVY5eGRXVnllVjl3WVhKaGJYTWdQU0I3WVd4c09pQjdmU3dnY21WemRXeDBjem9nZTMwc0lHRnFZWGc2SUh0OWZUdGNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMblJsYlhCc1lYUmxYMmx6WDJ4dllXUmxaQ0E5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFhSbGJYQnNZWFJsTFd4dllXUmxaRndpS1R0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1selgyRnFZWGdnUFNBa2RHaHBjeTVoZEhSeUtGd2laR0YwWVMxaGFtRjRYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11YVc1emRHRnVZMlZmYm5WdFltVnlJRDBnSkhSb2FYTXVZWFIwY2lnblpHRjBZUzFwYm5OMFlXNWpaUzFqYjNWdWRDY3BPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVKR0ZxWVhoZmNtVnpkV3gwYzE5amIyNTBZV2x1WlhJZ1BTQnFVWFZsY25rb0pIUm9hWE11WVhSMGNpaGNJbVJoZEdFdFlXcGhlQzEwWVhKblpYUmNJaWtwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtRnFZWGhmZFhCa1lYUmxYM05sWTNScGIyNXpJRDBnSkhSb2FYTXVZWFIwY2loY0ltUmhkR0V0WVdwaGVDMTFjR1JoZEdVdGMyVmpkR2x2Ym5OY0lpa2dQeUJLVTA5T0xuQmhjbk5sS0NBa2RHaHBjeTVoZEhSeUtGd2laR0YwWVMxaGFtRjRMWFZ3WkdGMFpTMXpaV04wYVc5dWMxd2lLU0FwSURvZ1cxMDdYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NXlaWEJzWVdObFgzSmxjM1ZzZEhNZ1BTQWtkR2hwY3k1aGRIUnlLRndpWkdGMFlTMXlaWEJzWVdObExYSmxjM1ZzZEhOY0lpa2dQVDA5SUZ3aU1Gd2lJRDhnWm1Gc2MyVWdPaUIwY25WbE8xeHlYRzRnSUNBZ0lDQWdJRnh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjbVZ6ZFd4MGMxOTFjbXdnUFNBa2RHaHBjeTVoZEhSeUtGd2laR0YwWVMxeVpYTjFiSFJ6TFhWeWJGd2lLVHRjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbVJsWW5WblgyMXZaR1VnUFNBa2RHaHBjeTVoZEhSeUtGd2laR0YwWVMxa1pXSjFaeTF0YjJSbFhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVkWEJrWVhSbFgyRnFZWGhmZFhKc0lEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRkWEJrWVhSbExXRnFZWGd0ZFhKc1hDSXBPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjR0ZuYVc1aGRHbHZibDkwZVhCbElEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRZV3BoZUMxd1lXZHBibUYwYVc5dUxYUjVjR1ZjSWlrN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1aGRYUnZYMk52ZFc1MElEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRZWFYwYnkxamIzVnVkRndpS1R0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1GMWRHOWZZMjkxYm5SZmNtVm1jbVZ6YUY5dGIyUmxJRDBnSkhSb2FYTXVZWFIwY2loY0ltUmhkR0V0WVhWMGJ5MWpiM1Z1ZEMxeVpXWnlaWE5vTFcxdlpHVmNJaWs3WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTV2Ym14NVgzSmxjM1ZzZEhOZllXcGhlQ0E5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFc5dWJIa3RjbVZ6ZFd4MGN5MWhhbUY0WENJcE95QXZMMmxtSUhkbElHRnlaU0J1YjNRZ2IyNGdkR2hsSUhKbGMzVnNkSE1nY0dGblpTd2djbVZrYVhKbFkzUWdjbUYwYUdWeUlIUm9ZVzRnZEhKNUlIUnZJR3h2WVdRZ2RtbGhJR0ZxWVhoY2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG5OamNtOXNiRjkwYjE5d2IzTWdQU0FrZEdocGN5NWhkSFJ5S0Z3aVpHRjBZUzF6WTNKdmJHd3RkRzh0Y0c5elhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZM1Z6ZEc5dFgzTmpjbTlzYkY5MGJ5QTlJQ1IwYUdsekxtRjBkSElvWENKa1lYUmhMV04xYzNSdmJTMXpZM0p2Ykd3dGRHOWNJaWs3WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTV6WTNKdmJHeGZiMjVmWVdOMGFXOXVJRDBnSkhSb2FYTXVZWFIwY2loY0ltUmhkR0V0YzJOeWIyeHNMVzl1TFdGamRHbHZibHdpS1R0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG14aGJtZGZZMjlrWlNBOUlDUjBhR2x6TG1GMGRISW9YQ0prWVhSaExXeGhibWN0WTI5a1pWd2lLVHRjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbUZxWVhoZmRYSnNJRDBnSkhSb2FYTXVZWFIwY2lnblpHRjBZUzFoYW1GNExYVnliQ2NwTzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11WVdwaGVGOW1iM0p0WDNWeWJDQTlJQ1IwYUdsekxtRjBkSElvSjJSaGRHRXRZV3BoZUMxbWIzSnRMWFZ5YkNjcE8xeHlYRzRnSUNBZ0lDQWdJSFJvYVhNdWFYTmZjblJzSUQwZ0pIUm9hWE11WVhSMGNpZ25aR0YwWVMxcGN5MXlkR3duS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NWthWE53YkdGNVgzSmxjM1ZzZEY5dFpYUm9iMlFnUFNBa2RHaHBjeTVoZEhSeUtDZGtZWFJoTFdScGMzQnNZWGt0Y21WemRXeDBMVzFsZEdodlpDY3BPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXViV0ZwYm5SaGFXNWZjM1JoZEdVZ1BTQWtkR2hwY3k1aGRIUnlLQ2RrWVhSaExXMWhhVzUwWVdsdUxYTjBZWFJsSnlrN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1aGFtRjRYMkZqZEdsdmJpQTlJRndpWENJN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1c1lYTjBYM04xWW0xcGRGOXhkV1Z5ZVY5d1lYSmhiWE1nUFNCY0lsd2lPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1OMWNuSmxiblJmY0dGblpXUWdQU0J3WVhKelpVbHVkQ2drZEdocGN5NWhkSFJ5S0Nka1lYUmhMV2x1YVhRdGNHRm5aV1FuS1NrN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1c1lYTjBYMnh2WVdSZmJXOXlaVjlvZEcxc0lEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG14dllXUmZiVzl5WlY5b2RHMXNJRDBnWENKY0lqdGNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtRnFZWGhmWkdGMFlWOTBlWEJsSUQwZ0pIUm9hWE11WVhSMGNpZ25aR0YwWVMxaGFtRjRMV1JoZEdFdGRIbHdaU2NwTzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11WVdwaGVGOTBZWEpuWlhSZllYUjBjaUE5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFdGcVlYZ3RkR0Z5WjJWMFhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVkWE5sWDJocGMzUnZjbmxmWVhCcElEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRkWE5sTFdocGMzUnZjbmt0WVhCcFhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVhWE5mYzNWaWJXbDBkR2x1WnlBOUlHWmhiSE5sTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxteGhjM1JmWVdwaGVGOXlaWEYxWlhOMElEMGdiblZzYkR0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnYVdZb2RIbHdaVzltS0hSb2FYTXVjbVZ6ZFd4MGMxOW9kRzFzS1QwOVhDSjFibVJsWm1sdVpXUmNJaWxjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVjbVZ6ZFd4MGMxOW9kRzFzSUQwZ1hDSmNJanRjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloMGFHbHpMblZ6WlY5b2FYTjBiM0o1WDJGd2FTazlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG5WelpWOW9hWE4wYjNKNVgyRndhU0E5SUZ3aVhDSTdYSEpjYmlBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvZEdocGN5NXdZV2RwYm1GMGFXOXVYM1I1Y0dVcFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RHaHBjeTV3WVdkcGJtRjBhVzl1WDNSNWNHVWdQU0JjSW01dmNtMWhiRndpTzF4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvZEdocGN5NWpkWEp5Wlc1MFgzQmhaMlZrS1QwOVhDSjFibVJsWm1sdVpXUmNJaWxjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVZM1Z5Y21WdWRGOXdZV2RsWkNBOUlERTdYSEpjYmlBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvZEdocGN5NWhhbUY0WDNSaGNtZGxkRjloZEhSeUtUMDlYQ0oxYm1SbFptbHVaV1JjSWlsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIUm9hWE11WVdwaGVGOTBZWEpuWlhSZllYUjBjaUE5SUZ3aVhDSTdYSEpjYmlBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvZEdocGN5NWhhbUY0WDNWeWJDazlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG1GcVlYaGZkWEpzSUQwZ1hDSmNJanRjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloMGFHbHpMbUZxWVhoZlptOXliVjkxY213cFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RHaHBjeTVoYW1GNFgyWnZjbTFmZFhKc0lEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lHbG1LSFI1Y0dWdlppaDBhR2x6TG5KbGMzVnNkSE5mZFhKc0tUMDlYQ0oxYm1SbFptbHVaV1JjSWlsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIUm9hWE11Y21WemRXeDBjMTkxY213Z1BTQmNJbHdpTzF4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2FXWW9kSGx3Wlc5bUtIUm9hWE11YzJOeWIyeHNYM1J2WDNCdmN5azlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG5OamNtOXNiRjkwYjE5d2IzTWdQU0JjSWx3aU8xeHlYRzRnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LSFJvYVhNdWMyTnliMnhzWDI5dVgyRmpkR2x2YmlrOVBWd2lkVzVrWldacGJtVmtYQ0lwWEhKY2JpQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMGFHbHpMbk5qY205c2JGOXZibDloWTNScGIyNGdQU0JjSWx3aU8xeHlYRzRnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNCcFppaDBlWEJsYjJZb2RHaHBjeTVqZFhOMGIyMWZjMk55YjJ4c1gzUnZLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFJvYVhNdVkzVnpkRzl0WDNOamNtOXNiRjkwYnlBOUlGd2lYQ0k3WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSFJvYVhNdUpHTjFjM1J2YlY5elkzSnZiR3hmZEc4Z1BTQnFVWFZsY25rb2RHaHBjeTVqZFhOMGIyMWZjMk55YjJ4c1gzUnZLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LSFJvYVhNdWRYQmtZWFJsWDJGcVlYaGZkWEpzS1QwOVhDSjFibVJsWm1sdVpXUmNJaWxjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVkWEJrWVhSbFgyRnFZWGhmZFhKc0lEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lHbG1LSFI1Y0dWdlppaDBhR2x6TG1SbFluVm5YMjF2WkdVcFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RHaHBjeTVrWldKMVoxOXRiMlJsSUQwZ1hDSmNJanRjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloMGFHbHpMbUZxWVhoZmRHRnlaMlYwWDI5aWFtVmpkQ2s5UFZ3aWRXNWtaV1pwYm1Wa1hDSXBYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IwYUdsekxtRnFZWGhmZEdGeVoyVjBYMjlpYW1WamRDQTlJRndpWENJN1hISmNiaUFnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQnBaaWgwZVhCbGIyWW9kR2hwY3k1MFpXMXdiR0YwWlY5cGMxOXNiMkZrWldRcFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RHaHBjeTUwWlcxd2JHRjBaVjlwYzE5c2IyRmtaV1FnUFNCY0lqQmNJanRjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloMGFHbHpMbUYxZEc5ZlkyOTFiblJmY21WbWNtVnphRjl0YjJSbEtUMDlYQ0oxYm1SbFptbHVaV1JjSWlsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIUm9hWE11WVhWMGIxOWpiM1Z1ZEY5eVpXWnlaWE5vWDIxdlpHVWdQU0JjSWpCY0lqdGNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJSFJvYVhNdVlXcGhlRjlzYVc1cmMxOXpaV3hsWTNSdmNpQTlJQ1IwYUdsekxtRjBkSElvWENKa1lYUmhMV0ZxWVhndGJHbHVhM010YzJWc1pXTjBiM0pjSWlrN1hISmNibHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1GMWRHOWZkWEJrWVhSbElEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRZWFYwYnkxMWNHUmhkR1ZjSWlrN1hISmNiaUFnSUNBZ0lDQWdkR2hwY3k1cGJuQjFkRlJwYldWeUlEMGdNRHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1elpYUkpibVpwYm1sMFpWTmpjbTlzYkVOdmJuUmhhVzVsY2lBOUlHWjFibU4wYVc5dUtDbGNjbHh1SUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQzh2SUZkb1pXNGdkMlVnYm1GMmFXZGhkR1VnWVhkaGVTQm1jbTl0SUhObFlYSmphQ0J5WlhOMWJIUnpMQ0JoYm1RZ2RHaGxiaUJ3Y21WemN5QmlZV05yTEZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2THlCcGMxOXRZWGhmY0dGblpXUWdhWE1nY21WMFlXbHVaV1FzSUhOdklIZGxJRzl1YkhrZ2QyRnVkQ0IwYnlCelpYUWdhWFFnZEc4Z1ptRnNjMlVnYVdaY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk4Z2QyVWdZWEpsSUdsdWFYUmhiR2w2YVc1bklIUm9aU0J5WlhOMWJIUnpJSEJoWjJVZ2RHaGxJR1pwY25OMElIUnBiV1VnTFNCemJ5QnFkWE4wSUZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2THlCamFHVmpheUJwWmlCMGFHbHpJSFpoY2lCcGN5QjFibVJsWm1sdVpXUWdLR0Z6SUdsMElITm9iM1ZzWkNCaVpTQnZiaUJtYVhKemRDQjFjMlVwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmlBb0lIUjVjR1Z2WmlBb0lIUm9hWE11YVhOZmJXRjRYM0JoWjJWa0lDa2dQVDA5SUNkMWJtUmxabWx1WldRbklDa2dlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZEdocGN5NXBjMTl0WVhoZmNHRm5aV1FnUFNCbVlXeHpaVHNnTHk5bWIzSWdiRzloWkNCdGIzSmxJRzl1Ykhrc0lHOXVZMlVnZDJVZ1pHVjBaV04wSUhkbEozSmxJR0YwSUhSb1pTQmxibVFnYzJWMElIUm9hWE1nZEc4Z2RISjFaVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IwYUdsekxuVnpaVjl6WTNKdmJHeGZiRzloWkdWeUlEMGdKSFJvYVhNdVlYUjBjaWduWkdGMFlTMXphRzkzTFhOamNtOXNiQzFzYjJGa1pYSW5LVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1cGJtWnBibWwwWlY5elkzSnZiR3hmWTI5dWRHRnBibVZ5SUQwZ0pIUm9hWE11WVhSMGNpZ25aR0YwWVMxcGJtWnBibWwwWlMxelkzSnZiR3d0WTI5dWRHRnBibVZ5SnlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVhVzVtYVc1cGRHVmZjMk55YjJ4c1gzUnlhV2RuWlhKZllXMXZkVzUwSUQwZ0pIUm9hWE11WVhSMGNpZ25aR0YwWVMxcGJtWnBibWwwWlMxelkzSnZiR3d0ZEhKcFoyZGxjaWNwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IwYUdsekxtbHVabWx1YVhSbFgzTmpjbTlzYkY5eVpYTjFiSFJmWTJ4aGMzTWdQU0FrZEdocGN5NWhkSFJ5S0Nka1lYUmhMV2x1Wm1sdWFYUmxMWE5qY205c2JDMXlaWE4xYkhRdFkyeGhjM01uS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZEdocGN5NGthVzVtYVc1cGRHVmZjMk55YjJ4c1gyTnZiblJoYVc1bGNpQTlJSFJvYVhNdUpHRnFZWGhmY21WemRXeDBjMTlqYjI1MFlXbHVaWEk3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaDBlWEJsYjJZb2RHaHBjeTVwYm1acGJtbDBaVjl6WTNKdmJHeGZZMjl1ZEdGcGJtVnlLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1cGJtWnBibWwwWlY5elkzSnZiR3hmWTI5dWRHRnBibVZ5SUQwZ1hDSmNJanRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQmxiSE5sWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVKR2x1Wm1sdWFYUmxYM05qY205c2JGOWpiMjUwWVdsdVpYSWdQU0JxVVhWbGNua29KSFJvYVhNdVlYUjBjaWduWkdGMFlTMXBibVpwYm1sMFpTMXpZM0p2Ykd3dFkyOXVkR0ZwYm1WeUp5a3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvZEdocGN5NXBibVpwYm1sMFpWOXpZM0p2Ykd4ZmNtVnpkV3gwWDJOc1lYTnpLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1cGJtWnBibWwwWlY5elkzSnZiR3hmY21WemRXeDBYMk5zWVhOeklEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9kSGx3Wlc5bUtIUm9hWE11ZFhObFgzTmpjbTlzYkY5c2IyRmtaWElwUFQxY0luVnVaR1ZtYVc1bFpGd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IwYUdsekxuVnpaVjl6WTNKdmJHeGZiRzloWkdWeUlEMGdNVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjlPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjMlYwU1c1bWFXNXBkR1ZUWTNKdmJHeERiMjUwWVdsdVpYSW9LVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdMeW9nWm5WdVkzUnBiMjV6SUNvdlhISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjbVZ6WlhRZ1BTQm1kVzVqZEdsdmJpaHpkV0p0YVhSZlptOXliU2xjY2x4dUlDQWdJQ0FnSUNCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG5KbGMyVjBSbTl5YlNoemRXSnRhWFJmWm05eWJTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lISmxkSFZ5YmlCMGNuVmxPMXh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NXBibkIxZEZWd1pHRjBaU0E5SUdaMWJtTjBhVzl1S0dSbGJHRjVSSFZ5WVhScGIyNHBYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvWkdWc1lYbEVkWEpoZEdsdmJpazlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJrWld4aGVVUjFjbUYwYVc5dUlEMGdNekF3TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbkpsYzJWMFZHbHRaWElvWkdWc1lYbEVkWEpoZEdsdmJpazdYSEpjYmlBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxuTmpjbTlzYkZSdlVHOXpJRDBnWm5WdVkzUnBiMjRvS1NCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhaaGNpQnZabVp6WlhRZ1BTQXdPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnWTJGdVUyTnliMnhzSUQwZ2RISjFaVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUtITmxiR1l1YVhOZllXcGhlRDA5TVNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWW9jMlZzWmk1elkzSnZiR3hmZEc5ZmNHOXpQVDFjSW5kcGJtUnZkMXdpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUc5bVpuTmxkQ0E5SURBN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pXeHpaU0JwWmloelpXeG1Mbk5qY205c2JGOTBiMTl3YjNNOVBWd2labTl5YlZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHOW1abk5sZENBOUlDUjBhR2x6TG05bVpuTmxkQ2dwTG5SdmNEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdWc2MyVWdhV1lvYzJWc1ppNXpZM0p2Ykd4ZmRHOWZjRzl6UFQxY0luSmxjM1ZzZEhOY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWh6Wld4bUxpUmhhbUY0WDNKbGMzVnNkSE5mWTI5dWRHRnBibVZ5TG14bGJtZDBhRDR3S1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYjJabWMyVjBJRDBnYzJWc1ppNGtZV3BoZUY5eVpYTjFiSFJ6WDJOdmJuUmhhVzVsY2k1dlptWnpaWFFvS1M1MGIzQTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWld4elpTQnBaaWh6Wld4bUxuTmpjbTlzYkY5MGIxOXdiM005UFZ3aVkzVnpkRzl0WENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OWpkWE4wYjIxZmMyTnliMnhzWDNSdlhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2MyVnNaaTRrWTNWemRHOXRYM05qY205c2JGOTBieTVzWlc1bmRHZytNQ2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJRzltWm5ObGRDQTlJSE5sYkdZdUpHTjFjM1J2YlY5elkzSnZiR3hmZEc4dWIyWm1jMlYwS0NrdWRHOXdPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdWc2MyVmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmpZVzVUWTNKdmJHd2dQU0JtWVd4elpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWhqWVc1VFkzSnZiR3dwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkNoY0ltaDBiV3dzSUdKdlpIbGNJaWt1YzNSdmNDZ3BMbUZ1YVcxaGRHVW9lMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6WTNKdmJHeFViM0E2SUc5bVpuTmxkRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMHNJRndpYm05eWJXRnNYQ0lzSUZ3aVpXRnpaVTkxZEZGMVlXUmNJaUFwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUgwN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZWFIwWVdOb1FXTjBhWFpsUTJ4aGMzTWdQU0JtZFc1amRHbHZiaWdwZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OWphR1ZqYXlCMGJ5QnpaV1VnYVdZZ2QyVWdZWEpsSUhWemFXNW5JR0ZxWVhnZ0ppQmhkWFJ2SUdOdmRXNTBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDOHZhV1lnYm05MExDQjBhR1VnYzJWaGNtTm9JR1p2Y20wZ1pHOWxjeUJ1YjNRZ1oyVjBJSEpsYkc5aFpHVmtMQ0J6YnlCM1pTQnVaV1ZrSUhSdklIVndaR0YwWlNCMGFHVWdjMll0YjNCMGFXOXVMV0ZqZEdsMlpTQmpiR0Z6Y3lCdmJpQmhiR3dnWm1sbGJHUnpYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FrZEdocGN5NXZiaWduWTJoaGJtZGxKeXdnSjJsdWNIVjBXM1I1Y0dVOVhDSnlZV1JwYjF3aVhTd2dhVzV3ZFhSYmRIbHdaVDFjSW1Ob1pXTnJZbTk0WENKZExDQnpaV3hsWTNRbkxDQm1kVzVqZEdsdmJpaGxLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pHTjBhR2x6SUQwZ0pDaDBhR2x6S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrWTNSb2FYTmZjR0Z5Wlc1MElEMGdKR04wYUdsekxtTnNiM05sYzNRb1hDSnNhVnRrWVhSaExYTm1MV1pwWld4a0xXNWhiV1ZkWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhSb2FYTmZkR0ZuSUQwZ0pHTjBhR2x6TG5CeWIzQW9YQ0owWVdkT1lXMWxYQ0lwTG5SdlRHOTNaWEpEWVhObEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2FXNXdkWFJmZEhsd1pTQTlJQ1JqZEdocGN5NWhkSFJ5S0Z3aWRIbHdaVndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ3WVhKbGJuUmZkR0ZuSUQwZ0pHTjBhR2x6WDNCaGNtVnVkQzV3Y205d0tGd2lkR0ZuVG1GdFpWd2lLUzUwYjB4dmQyVnlRMkZ6WlNncE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtDaDBhR2x6WDNSaFp6MDlYQ0pwYm5CMWRGd2lLU1ltS0NocGJuQjFkRjkwZVhCbFBUMWNJbkpoWkdsdlhDSXBmSHdvYVc1d2RYUmZkSGx3WlQwOVhDSmphR1ZqYTJKdmVGd2lLU2tnSmlZZ0tIQmhjbVZ1ZEY5MFlXYzlQVndpYkdsY0lpa3BYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSaGJHeGZiM0IwYVc5dWN5QTlJQ1JqZEdocGMxOXdZWEpsYm5RdWNHRnlaVzUwS0NrdVptbHVaQ2duYkdrbktUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKR0ZzYkY5dmNIUnBiMjV6WDJacFpXeGtjeUE5SUNSamRHaHBjMTl3WVhKbGJuUXVjR0Z5Wlc1MEtDa3VabWx1WkNnbmFXNXdkWFE2WTJobFkydGxaQ2NwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa1lXeHNYMjl3ZEdsdmJuTXVjbVZ0YjNabFEyeGhjM01vWENKelppMXZjSFJwYjI0dFlXTjBhWFpsWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSaGJHeGZiM0IwYVc5dWMxOW1hV1ZzWkhNdVpXRmphQ2htZFc1amRHbHZiaWdwZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSd1lYSmxiblFnUFNBa0tIUm9hWE1wTG1Oc2IzTmxjM1FvWENKc2FWd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkhCaGNtVnVkQzVoWkdSRGJHRnpjeWhjSW5ObUxXOXdkR2x2YmkxaFkzUnBkbVZjSWlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJVZ2FXWW9kR2hwYzE5MFlXYzlQVndpYzJWc1pXTjBYQ0lwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUmhiR3hmYjNCMGFXOXVjeUE5SUNSamRHaHBjeTVqYUdsc1pISmxiaWdwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1JoYkd4ZmIzQjBhVzl1Y3k1eVpXMXZkbVZEYkdGemN5aGNJbk5tTFc5d2RHbHZiaTFoWTNScGRtVmNJaWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhSb2FYTmZkbUZzSUQwZ0pHTjBhR2x6TG5aaGJDZ3BPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2RHaHBjMTloY25KZmRtRnNJRDBnS0hSNWNHVnZaaUIwYUdselgzWmhiQ0E5UFNBbmMzUnlhVzVuSnlCOGZDQjBhR2x6WDNaaGJDQnBibk4wWVc1alpXOW1JRk4wY21sdVp5a2dQeUJiZEdocGMxOTJZV3hkSURvZ2RHaHBjMTkyWVd3N1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUW9kR2hwYzE5aGNuSmZkbUZzS1M1bFlXTm9LR1oxYm1OMGFXOXVLR2tzSUhaaGJIVmxLWHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkdOMGFHbHpMbVpwYm1Rb1hDSnZjSFJwYjI1YmRtRnNkV1U5SjF3aUszWmhiSFZsSzF3aUoxMWNJaWt1WVdSa1EyeGhjM01vWENKelppMXZjSFJwYjI0dFlXTjBhWFpsWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgwcE8xeHlYRzVjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjlPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVhVzVwZEVGMWRHOVZjR1JoZEdWRmRtVnVkSE1nUFNCbWRXNWpkR2x2YmlncGUxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdMeW9nWVhWMGJ5QjFjR1JoZEdVZ0tpOWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9LSE5sYkdZdVlYVjBiMTkxY0dSaGRHVTlQVEVwZkh3b2MyVnNaaTVoZFhSdlgyTnZkVzUwWDNKbFpuSmxjMmhmYlc5a1pUMDlNU2twWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSMGFHbHpMbTl1S0NkamFHRnVaMlVuTENBbmFXNXdkWFJiZEhsd1pUMWNJbkpoWkdsdlhDSmRMQ0JwYm5CMWRGdDBlWEJsUFZ3aVkyaGxZMnRpYjNoY0lsMHNJSE5sYkdWamRDY3NJR1oxYm1OMGFXOXVLR1VwSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxtbHVjSFYwVlhCa1lYUmxLREl3TUNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtkR2hwY3k1dmJpZ25hVzV3ZFhRbkxDQW5hVzV3ZFhSYmRIbHdaVDFjSW01MWJXSmxjbHdpWFNjc0lHWjFibU4wYVc5dUtHVXBJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1sdWNIVjBWWEJrWVhSbEtEZ3dNQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKSFJsZUhSSmJuQjFkQ0E5SUNSMGFHbHpMbVpwYm1Rb0oybHVjSFYwVzNSNWNHVTlYQ0owWlhoMFhDSmRPbTV2ZENndWMyWXRaR0YwWlhCcFkydGxjaWtuS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJzWVhOMFZtRnNkV1VnUFNBa2RHVjRkRWx1Y0hWMExuWmhiQ2dwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1IwYUdsekxtOXVLQ2RwYm5CMWRDY3NJQ2RwYm5CMWRGdDBlWEJsUFZ3aWRHVjRkRndpWFRwdWIzUW9Mbk5tTFdSaGRHVndhV05yWlhJcEp5d2dablZ1WTNScGIyNG9LVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0d4aGMzUldZV3gxWlNFOUpIUmxlSFJKYm5CMWRDNTJZV3dvS1NsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhVzV3ZFhSVmNHUmhkR1VvTVRJd01DazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnNZWE4wVm1Gc2RXVWdQU0FrZEdWNGRFbHVjSFYwTG5aaGJDZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hISmNibHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUjBhR2x6TG05dUtDZHJaWGx3Y21WemN5Y3NJQ2RwYm5CMWRGdDBlWEJsUFZ3aWRHVjRkRndpWFRwdWIzUW9Mbk5tTFdSaGRHVndhV05yWlhJcEp5d2dablZ1WTNScGIyNG9aU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlBb1pTNTNhR2xqYUNBOVBTQXhNeWw3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmxMbkJ5WlhabGJuUkVaV1poZFd4MEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWMzVmliV2wwUm05eWJTZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J5WlhSMWNtNGdabUZzYzJVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDBwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2SkhSb2FYTXViMjRvSjJsdWNIVjBKeXdnSjJsdWNIVjBMbk5tTFdSaGRHVndhV05yWlhJbkxDQnpaV3htTG1SaGRHVkpibkIxZEZSNWNHVXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lIMDdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDOHZkR2hwY3k1cGJtbDBRWFYwYjFWd1pHRjBaVVYyWlc1MGN5Z3BPMXh5WEc1Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NWpiR1ZoY2xScGJXVnlJRDBnWm5WdVkzUnBiMjRvS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ1kyeGxZWEpVYVcxbGIzVjBLSE5sYkdZdWFXNXdkWFJVYVcxbGNpazdYSEpjYmlBZ0lDQWdJQ0FnZlR0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG5KbGMyVjBWR2x0WlhJZ1BTQm1kVzVqZEdsdmJpaGtaV3hoZVVSMWNtRjBhVzl1S1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ1kyeGxZWEpVYVcxbGIzVjBLSE5sYkdZdWFXNXdkWFJVYVcxbGNpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1YVc1d2RYUlVhVzFsY2lBOUlITmxkRlJwYldWdmRYUW9jMlZzWmk1bWIzSnRWWEJrWVhSbFpDd2daR1ZzWVhsRWRYSmhkR2x2YmlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUgwN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZV1JrUkdGMFpWQnBZMnRsY25NZ1BTQm1kVzVqZEdsdmJpZ3BYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pHUmhkR1ZmY0dsamEyVnlJRDBnSkhSb2FYTXVabWx1WkNoY0lpNXpaaTFrWVhSbGNHbGphMlZ5WENJcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvSkdSaGRHVmZjR2xqYTJWeUxteGxibWQwYUQ0d0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtaR0YwWlY5d2FXTnJaWEl1WldGamFDaG1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pIUm9hWE1nUFNBa0tIUm9hWE1wTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCa1lYUmxSbTl5YldGMElEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1pHRjBaVVJ5YjNCa2IzZHVXV1ZoY2lBOUlHWmhiSE5sTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCa1lYUmxSSEp2Y0dSdmQyNU5iMjUwYUNBOUlHWmhiSE5sTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKR05zYjNObGMzUmZaR0YwWlY5M2NtRndJRDBnSkhSb2FYTXVZMnh2YzJWemRDaGNJaTV6Wmw5a1lYUmxYMlpwWld4a1hDSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LQ1JqYkc5elpYTjBYMlJoZEdWZmQzSmhjQzVzWlc1bmRHZytNQ2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdWR2IzSnRZWFFnUFNBa1kyeHZjMlZ6ZEY5a1lYUmxYM2R5WVhBdVlYUjBjaWhjSW1SaGRHRXRaR0YwWlMxbWIzSnRZWFJjSWlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlna1kyeHZjMlZ6ZEY5a1lYUmxYM2R5WVhBdVlYUjBjaWhjSW1SaGRHRXRaR0YwWlMxMWMyVXRlV1ZoY2kxa2NtOXdaRzkzYmx3aUtUMDlNU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaR0YwWlVSeWIzQmtiM2R1V1dWaGNpQTlJSFJ5ZFdVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvSkdOc2IzTmxjM1JmWkdGMFpWOTNjbUZ3TG1GMGRISW9YQ0prWVhSaExXUmhkR1V0ZFhObExXMXZiblJvTFdSeWIzQmtiM2R1WENJcFBUMHhLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJsUkhKdmNHUnZkMjVOYjI1MGFDQTlJSFJ5ZFdVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJrWVhSbFVHbGphMlZ5VDNCMGFXOXVjeUE5SUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXNXNhVzVsT2lCMGNuVmxMRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6YUc5M1QzUm9aWEpOYjI1MGFITTZJSFJ5ZFdVc1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHOXVVMlZzWldOME9pQm1kVzVqZEdsdmJpaGxMQ0JtY205dFgyWnBaV3hrS1hzZ2MyVnNaaTVrWVhSbFUyVnNaV04wS0dVc0lHWnliMjFmWm1sbGJHUXNJQ1FvZEdocGN5a3BPeUI5TEZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCa1lYUmxSbTl5YldGME9pQmtZWFJsUm05eWJXRjBMRnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1kyaGhibWRsVFc5dWRHZzZJR1JoZEdWRWNtOXdaRzkzYmsxdmJuUm9MRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JqYUdGdVoyVlpaV0Z5T2lCa1lYUmxSSEp2Y0dSdmQyNVpaV0Z5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2MyVnNaaTVwYzE5eWRHdzlQVEVwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JrWVhSbFVHbGphMlZ5VDNCMGFXOXVjeTVrYVhKbFkzUnBiMjRnUFNCY0luSjBiRndpTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkhSb2FYTXVaR0YwWlhCcFkydGxjaWhrWVhSbFVHbGphMlZ5VDNCMGFXOXVjeWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtITmxiR1l1YkdGdVoxOWpiMlJsSVQxY0lsd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKQzVrWVhSbGNHbGphMlZ5TG5ObGRFUmxabUYxYkhSektGeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkM1bGVIUmxibVFvWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZXlka1lYUmxSbTl5YldGMEp6cGtZWFJsUm05eWJXRjBmU3hjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FrTG1SaGRHVndhV05yWlhJdWNtVm5hVzl1WVd4YklITmxiR1l1YkdGdVoxOWpiMlJsWFZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWld4elpWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pDNWtZWFJsY0dsamEyVnlMbk5sZEVSbFptRjFiSFJ6S0Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKQzVsZUhSbGJtUW9YSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdleWRrWVhSbFJtOXliV0YwSnpwa1lYUmxSbTl5YldGMGZTeGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtMbVJoZEdWd2FXTnJaWEl1Y21WbmFXOXVZV3hiWENKbGJsd2lYVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmU2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvSkNnbkxteHNMWE5yYVc0dGJXVnNiMjRuS1M1c1pXNW5kR2c5UFRBcGUxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtaR0YwWlY5d2FXTnJaWEl1WkdGMFpYQnBZMnRsY2lnbmQybGtaMlYwSnlrdWQzSmhjQ2duUEdScGRpQmpiR0Z6Y3oxY0lteHNMWE5yYVc0dGJXVnNiMjRnYzJWaGNtTm9ZVzVrWm1sc2RHVnlMV1JoZEdVdGNHbGphMlZ5WENJdlBpY3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSDA3WEhKY2JseHlYRzRnSUNBZ0lDQWdJSFJvYVhNdVpHRjBaVk5sYkdWamRDQTlJR1oxYm1OMGFXOXVLR1VzSUdaeWIyMWZabWxsYkdRc0lDUjBhR2x6S1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJQ1JwYm5CMWRGOW1hV1ZzWkNBOUlDUW9abkp2YlY5bWFXVnNaQzVwYm5CMWRDNW5aWFFvTUNrcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKSFJvYVhNZ1BTQWtLSFJvYVhNcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSa1lYUmxYMlpwWld4a2N5QTlJQ1JwYm5CMWRGOW1hV1ZzWkM1amJHOXpaWE4wS0NkYlpHRjBZUzF6WmkxbWFXVnNaQzFwYm5CMWRDMTBlWEJsUFZ3aVpHRjBaWEpoYm1kbFhDSmRMQ0JiWkdGMFlTMXpaaTFtYVdWc1pDMXBibkIxZEMxMGVYQmxQVndpWkdGMFpWd2lYU2NwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FrWkdGMFpWOW1hV1ZzWkhNdVpXRmphQ2htZFc1amRHbHZiaWhsTENCcGJtUmxlQ2w3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrZEdaZlpHRjBaVjl3YVdOclpYSnpJRDBnSkNoMGFHbHpLUzVtYVc1a0tGd2lMbk5tTFdSaGRHVndhV05yWlhKY0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2JtOWZaR0YwWlY5d2FXTnJaWEp6SUQwZ0pIUm1YMlJoZEdWZmNHbGphMlZ5Y3k1c1pXNW5kR2c3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LRzV2WDJSaGRHVmZjR2xqYTJWeWN6NHhLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2ZEdobGJpQnBkQ0JwY3lCaElHUmhkR1VnY21GdVoyVXNJSE52SUcxaGEyVWdjM1Z5WlNCaWIzUm9JR1pwWld4a2N5QmhjbVVnWm1sc2JHVmtJR0psWm05eVpTQjFjR1JoZEdsdVoxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQmtjRjlqYjNWdWRHVnlJRDBnTUR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1pIQmZaVzF3ZEhsZlptbGxiR1JmWTI5MWJuUWdQU0F3TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1IwWmw5a1lYUmxYM0JwWTJ0bGNuTXVaV0ZqYUNobWRXNWpkR2x2YmlncGUxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0pDaDBhR2x6S1M1MllXd29LVDA5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pIQmZaVzF3ZEhsZlptbGxiR1JmWTI5MWJuUXJLenRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaSEJmWTI5MWJuUmxjaXNyTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDBwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaGtjRjlsYlhCMGVWOW1hV1ZzWkY5amIzVnVkRDA5TUNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhVzV3ZFhSVmNHUmhkR1VvTVNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaV3h6WlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhVzV3ZFhSVmNHUmhkR1VvTVNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5S1R0Y2NseHVJQ0FnSUNBZ0lDQjlPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1Ga1pGSmhibWRsVTJ4cFpHVnljeUE5SUdaMWJtTjBhVzl1S0NsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrYldWMFlWOXlZVzVuWlNBOUlDUjBhR2x6TG1acGJtUW9YQ0l1YzJZdGJXVjBZUzF5WVc1blpTMXpiR2xrWlhKY0lpazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmlna2JXVjBZVjl5WVc1blpTNXNaVzVuZEdnK01DbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKRzFsZEdGZmNtRnVaMlV1WldGamFDaG1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pIUm9hWE1nUFNBa0tIUm9hWE1wTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCdGFXNGdQU0FrZEdocGN5NWhkSFJ5S0Z3aVpHRjBZUzF0YVc1Y0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJRzFoZUNBOUlDUjBhR2x6TG1GMGRISW9YQ0prWVhSaExXMWhlRndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2MyMXBiaUE5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFhOMFlYSjBMVzFwYmx3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjMjFoZUNBOUlDUjBhR2x6TG1GMGRISW9YQ0prWVhSaExYTjBZWEowTFcxaGVGd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnWkdsemNHeGhlVjkyWVd4MVpWOWhjeUE5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFdScGMzQnNZWGt0ZG1Gc2RXVnpMV0Z6WENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnpkR1Z3SUQwZ0pIUm9hWE11WVhSMGNpaGNJbVJoZEdFdGMzUmxjRndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pITjBZWEowWDNaaGJDQTlJQ1IwYUdsekxtWnBibVFvSnk1elppMXlZVzVuWlMxdGFXNG5LVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnSkdWdVpGOTJZV3dnUFNBa2RHaHBjeTVtYVc1a0tDY3VjMll0Y21GdVoyVXRiV0Y0SnlrN1hISmNibHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1pHVmphVzFoYkY5d2JHRmpaWE1nUFNBa2RHaHBjeTVoZEhSeUtGd2laR0YwWVMxa1pXTnBiV0ZzTFhCc1lXTmxjMXdpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2RHaHZkWE5oYm1SZmMyVndaWEpoZEc5eUlEMGdKSFJvYVhNdVlYUjBjaWhjSW1SaGRHRXRkR2h2ZFhOaGJtUXRjMlZ3WlhKaGRHOXlYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCa1pXTnBiV0ZzWDNObGNHVnlZWFJ2Y2lBOUlDUjBhR2x6TG1GMGRISW9YQ0prWVhSaExXUmxZMmx0WVd3dGMyVndaWEpoZEc5eVhDSXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1ptbGxiR1JmWm05eWJXRjBJRDBnZDA1MWJXSW9lMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J0WVhKck9pQmtaV05wYldGc1gzTmxjR1Z5WVhSdmNpeGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaR1ZqYVcxaGJITTZJSEJoY25ObFJteHZZWFFvWkdWamFXMWhiRjl3YkdGalpYTXBMRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IwYUc5MWMyRnVaRG9nZEdodmRYTmhibVJmYzJWd1pYSmhkRzl5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmU2s3WEhKY2JseHlYRzVjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHMXBibDkxYm1admNtMWhkSFJsWkNBOUlIQmhjbk5sUm14dllYUW9jMjFwYmlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHMXBibDltYjNKdFlYUjBaV1FnUFNCbWFXVnNaRjltYjNKdFlYUXVkRzhvY0dGeWMyVkdiRzloZENoemJXbHVLU2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUcxaGVGOW1iM0p0WVhSMFpXUWdQU0JtYVdWc1pGOW1iM0p0WVhRdWRHOG9jR0Z5YzJWR2JHOWhkQ2h6YldGNEtTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJRzFoZUY5MWJtWnZjbTFoZEhSbFpDQTlJSEJoY25ObFJteHZZWFFvYzIxaGVDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OWhiR1Z5ZENodGFXNWZabTl5YldGMGRHVmtLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMMkZzWlhKMEtHMWhlRjltYjNKdFlYUjBaV1FwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2WVd4bGNuUW9aR2x6Y0d4aGVWOTJZV3gxWlY5aGN5azdYSEpjYmx4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaGthWE53YkdGNVgzWmhiSFZsWDJGelBUMWNJblJsZUhScGJuQjFkRndpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkhOMFlYSjBYM1poYkM1MllXd29iV2x1WDJadmNtMWhkSFJsWkNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUmxibVJmZG1Gc0xuWmhiQ2h0WVhoZlptOXliV0YwZEdWa0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWld4elpTQnBaaWhrYVhOd2JHRjVYM1poYkhWbFgyRnpQVDFjSW5SbGVIUmNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1J6ZEdGeWRGOTJZV3d1YUhSdGJDaHRhVzVmWm05eWJXRjBkR1ZrS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pHVnVaRjkyWVd3dWFIUnRiQ2h0WVhoZlptOXliV0YwZEdWa0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2JtOVZTVTl3ZEdsdmJuTWdQU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhKaGJtZGxPaUI3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQW5iV2x1SnpvZ1d5QndZWEp6WlVac2IyRjBLRzFwYmlrZ1hTeGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNkdFlYZ25PaUJiSUhCaGNuTmxSbXh2WVhRb2JXRjRLU0JkWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgwc1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITjBZWEowT2lCYmJXbHVYMlp2Y20xaGRIUmxaQ3dnYldGNFgyWnZjbTFoZEhSbFpGMHNYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2hoYm1Sc1pYTTZJRElzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdOdmJtNWxZM1E2SUhSeWRXVXNYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE4wWlhBNklIQmhjbk5sUm14dllYUW9jM1JsY0Nrc1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JpWldoaGRtbHZkWEk2SUNkbGVIUmxibVF0ZEdGd0p5eGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdabTl5YldGME9pQm1hV1ZzWkY5bWIzSnRZWFJjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlPMXh5WEc1Y2NseHVYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0hObGJHWXVhWE5mY25Sc1BUMHhLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdibTlWU1U5d2RHbHZibk11WkdseVpXTjBhVzl1SUQwZ1hDSnlkR3hjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnpiR2xrWlhKZmIySnFaV04wSUQwZ0pDaDBhR2x6S1M1bWFXNWtLRndpTG0xbGRHRXRjMnhwWkdWeVhDSXBXekJkTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppZ2dYQ0oxYm1SbFptbHVaV1JjSWlBaFBUMGdkSGx3Wlc5bUtDQnpiR2xrWlhKZmIySnFaV04wTG01dlZXbFRiR2xrWlhJZ0tTQXBJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5a1pYTjBjbTk1SUdsbUlHbDBJR1Y0YVhOMGN5NHVJSFJvYVhNZ2JXVmhibk1nYzI5dFpXaHZkeUJoYm05MGFHVnlJR2x1YzNSaGJtTmxJR2hoWkNCcGJtbDBhV0ZzYVhObFpDQnBkQzR1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhOc2FXUmxjbDl2WW1wbFkzUXVibTlWYVZOc2FXUmxjaTVrWlhOMGNtOTVLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J1YjFWcFUyeHBaR1Z5TG1OeVpXRjBaU2h6Ykdsa1pYSmZiMkpxWldOMExDQnViMVZKVDNCMGFXOXVjeWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSemRHRnlkRjkyWVd3dWIyWm1LQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKSE4wWVhKMFgzWmhiQzV2YmlnblkyaGhibWRsSnl3Z1puVnVZM1JwYjI0b0tYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMnhwWkdWeVgyOWlhbVZqZEM1dWIxVnBVMnhwWkdWeUxuTmxkQ2hiSkNoMGFHbHpLUzUyWVd3b0tTd2diblZzYkYwcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgwcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtaVzVrWDNaaGJDNXZabVlvS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FrWlc1a1gzWmhiQzV2YmlnblkyaGhibWRsSnl3Z1puVnVZM1JwYjI0b0tYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMnhwWkdWeVgyOWlhbVZqZEM1dWIxVnBVMnhwWkdWeUxuTmxkQ2hiYm5Wc2JDd2dKQ2gwYUdsektTNTJZV3dvS1YwcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgwcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMeVJ6ZEdGeWRGOTJZV3d1YUhSdGJDaHRhVzVmWm05eWJXRjBkR1ZrS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2THlSbGJtUmZkbUZzTG1oMGJXd29iV0Y0WDJadmNtMWhkSFJsWkNrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITnNhV1JsY2w5dlltcGxZM1F1Ym05VmFWTnNhV1JsY2k1dlptWW9KM1Z3WkdGMFpTY3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITnNhV1JsY2w5dlltcGxZM1F1Ym05VmFWTnNhV1JsY2k1dmJpZ25kWEJrWVhSbEp5d2dablZ1WTNScGIyNG9JSFpoYkhWbGN5d2dhR0Z1Wkd4bElDa2dlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSE5zYVdSbGNsOXpkR0Z5ZEY5MllXd2dJRDBnYldsdVgyWnZjbTFoZEhSbFpEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhOc2FXUmxjbDlsYm1SZmRtRnNJQ0E5SUcxaGVGOW1iM0p0WVhSMFpXUTdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdkbUZzZFdVZ1BTQjJZV3gxWlhOYmFHRnVaR3hsWFR0Y2NseHVYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppQW9JR2hoYm1Sc1pTQXBJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHMWhlRjltYjNKdFlYUjBaV1FnUFNCMllXeDFaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlNCbGJITmxJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHMXBibDltYjNKdFlYUjBaV1FnUFNCMllXeDFaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvWkdsemNHeGhlVjkyWVd4MVpWOWhjejA5WENKMFpYaDBhVzV3ZFhSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pITjBZWEowWDNaaGJDNTJZV3dvYldsdVgyWnZjbTFoZEhSbFpDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa1pXNWtYM1poYkM1MllXd29iV0Y0WDJadmNtMWhkSFJsWkNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaV3h6WlNCcFppaGthWE53YkdGNVgzWmhiSFZsWDJGelBUMWNJblJsZUhSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pITjBZWEowWDNaaGJDNW9kRzFzS0cxcGJsOW1iM0p0WVhSMFpXUXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pHVnVaRjkyWVd3dWFIUnRiQ2h0WVhoZlptOXliV0YwZEdWa0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2YVNCMGFHbHVheUIwYUdVZ1puVnVZM1JwYjI0Z2RHaGhkQ0JpZFdsc1pITWdkR2hsSUZWU1RDQnVaV1ZrY3lCMGJ5QmtaV052WkdVZ2RHaGxJR1p2Y20xaGRIUmxaQ0J6ZEhKcGJtY2dZbVZtYjNKbElHRmtaR2x1WnlCMGJ5QjBhR1VnZFhKc1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LQ2h6Wld4bUxtRjFkRzlmZFhCa1lYUmxQVDB4S1h4OEtITmxiR1l1WVhWMGIxOWpiM1Z1ZEY5eVpXWnlaWE5vWDIxdlpHVTlQVEVwS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDI5dWJIa2dkSEo1SUhSdklIVndaR0YwWlNCcFppQjBhR1VnZG1Gc2RXVnpJR2hoZG1VZ1lXTjBkV0ZzYkhrZ1kyaGhibWRsWkZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvS0hOc2FXUmxjbDl6ZEdGeWRGOTJZV3doUFcxcGJsOW1iM0p0WVhSMFpXUXBmSHdvYzJ4cFpHVnlYMlZ1WkY5MllXd2hQVzFoZUY5bWIzSnRZWFIwWldRcEtTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhVzV3ZFhSVmNHUmhkR1VvT0RBd0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WTJ4bFlYSlVhVzFsY2lncE95QXZMMmxuYm05eVpTQmhibmtnWTJoaGJtZGxjeUJ5WldObGJuUnNlU0J0WVdSbElHSjVJSFJvWlNCemJHbGtaWElnS0hSb2FYTWdkMkZ6SUdwMWMzUWdhVzVwZENCemFHOTFiR1J1SjNRZ1kyOTFiblFnWVhNZ1lXNGdkWEJrWVhSbElHVjJaVzUwS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTVwYm1sMElEMGdablZ1WTNScGIyNG9hMlZsY0Y5d1lXZHBibUYwYVc5dUtWeHlYRzRnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LR3RsWlhCZmNHRm5hVzVoZEdsdmJpazlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJyWldWd1gzQmhaMmx1WVhScGIyNGdQU0JtWVd4elpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1cGJtbDBRWFYwYjFWd1pHRjBaVVYyWlc1MGN5Z3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG1GMGRHRmphRUZqZEdsMlpVTnNZWE56S0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG1Ga1pFUmhkR1ZRYVdOclpYSnpLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFJvYVhNdVlXUmtVbUZ1WjJWVGJHbGtaWEp6S0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZMMmx1YVhRZ1kyOXRZbThnWW05NFpYTmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJQ1JqYjIxaWIySnZlQ0E5SUNSMGFHbHpMbVpwYm1Rb1hDSnpaV3hsWTNSYlpHRjBZUzFqYjIxaWIySnZlRDBuTVNkZFhDSXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0pHTnZiV0p2WW05NExteGxibWQwYUQ0d0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtZMjl0WW05aWIzZ3VaV0ZqYUNobWRXNWpkR2x2YmlocGJtUmxlQ0FwZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2RHaHBjMk5pSUQwZ0pDZ2dkR2hwY3lBcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnVjbTBnUFNBa2RHaHBjMk5pTG1GMGRISW9YQ0prWVhSaExXTnZiV0p2WW05NExXNXliVndpS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLSFI1Y0dWdlppQWtkR2hwYzJOaUxtTm9iM05sYmlBaFBTQmNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHTm9iM05sYm05d2RHbHZibk1nUFNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6WldGeVkyaGZZMjl1ZEdGcGJuTTZJSFJ5ZFdWY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtDaDBlWEJsYjJZb2JuSnRLU0U5UFZ3aWRXNWtaV1pwYm1Wa1hDSXBKaVlvYm5KdEtTbDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCamFHOXpaVzV2Y0hScGIyNXpMbTV2WDNKbGMzVnNkSE5mZEdWNGRDQTlJRzV5YlR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMeUJ6WVdabElIUnZJSFZ6WlNCMGFHVWdablZ1WTNScGIyNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTl6WldGeVkyaGZZMjl1ZEdGcGJuTmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvYzJWc1ppNXBjMTl5ZEd3OVBURXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUjBhR2x6WTJJdVlXUmtRMnhoYzNNb1hDSmphRzl6Wlc0dGNuUnNYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FrZEdocGMyTmlMbU5vYjNObGJpaGphRzl6Wlc1dmNIUnBiMjV6S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaV3h6WlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ6Wld4bFkzUXliM0IwYVc5dWN5QTlJSHQ5TzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvYzJWc1ppNXBjMTl5ZEd3OVBURXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1ZqZERKdmNIUnBiMjV6TG1ScGNpQTlJRndpY25Sc1hDSTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0tIUjVjR1Z2WmlodWNtMHBJVDA5WENKMWJtUmxabWx1WldSY0lpa21KaWh1Y20wcEtYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHVmpkREp2Y0hScGIyNXpMbXhoYm1kMVlXZGxQU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWENKdWIxSmxjM1ZzZEhOY0lqb2dablZ1WTNScGIyNG9LWHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2NtVjBkWEp1SUc1eWJUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2RHaHBjMk5pTG5ObGJHVmpkRElvYzJWc1pXTjBNbTl3ZEdsdmJuTXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOUtUdGNjbHh1WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1selUzVmliV2wwZEdsdVp5QTlJR1poYkhObE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdMeTlwWmlCaGFtRjRJR2x6SUdWdVlXSnNaV1FnYVc1cGRDQjBhR1VnY0dGbmFXNWhkR2x2Ymx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloelpXeG1MbWx6WDJGcVlYZzlQVEVwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVjMlYwZFhCQmFtRjRVR0ZuYVc1aGRHbHZiaWdwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBa2RHaHBjeTV2YmloY0luTjFZbTFwZEZ3aUxDQjBhR2x6TG5OMVltMXBkRVp2Y20wcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGJtbDBWMjl2UTI5dGJXVnlZMlZEYjI1MGNtOXNjeWdwT3lBdkwzZHZiMk52YlcxbGNtTmxJRzl5WkdWeVlubGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0d0bFpYQmZjR0ZuYVc1aGRHbHZiajA5Wm1Gc2MyVXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWJHRnpkRjl6ZFdKdGFYUmZjWFZsY25sZmNHRnlZVzF6SUQwZ2MyVnNaaTVuWlhSVmNteFFZWEpoYlhNb1ptRnNjMlVwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbTl1VjJsdVpHOTNVMk55YjJ4c0lEMGdablZ1WTNScGIyNG9aWFpsYm5RcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWdvSVhObGJHWXVhWE5mYkc5aFpHbHVaMTl0YjNKbEtTQW1KaUFvSVhObGJHWXVhWE5mYldGNFgzQmhaMlZrS1NsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSGRwYm1SdmQxOXpZM0p2Ykd3Z1BTQWtLSGRwYm1SdmR5a3VjMk55YjJ4c1ZHOXdLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdkMmx1Wkc5M1gzTmpjbTlzYkY5aWIzUjBiMjBnUFNBa0tIZHBibVJ2ZHlrdWMyTnliMnhzVkc5d0tDa2dLeUFrS0hkcGJtUnZkeWt1YUdWcFoyaDBLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjMk55YjJ4c1gyOW1abk5sZENBOUlIQmhjbk5sU1c1MEtITmxiR1l1YVc1bWFXNXBkR1ZmYzJOeWIyeHNYM1J5YVdkblpYSmZZVzF2ZFc1MEtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTGlScGJtWnBibWwwWlY5elkzSnZiR3hmWTI5dWRHRnBibVZ5TG14bGJtZDBhRDA5TVNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjbVZ6ZFd4MGMxOXpZM0p2Ykd4ZlltOTBkRzl0SUQwZ2MyVnNaaTRrYVc1bWFXNXBkR1ZmYzJOeWIyeHNYMk52Ym5SaGFXNWxjaTV2Wm1aelpYUW9LUzUwYjNBZ0t5QnpaV3htTGlScGJtWnBibWwwWlY5elkzSnZiR3hmWTI5dWRHRnBibVZ5TG1obGFXZG9kQ2dwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdiMlptYzJWMElEMGdLSE5sYkdZdUpHbHVabWx1YVhSbFgzTmpjbTlzYkY5amIyNTBZV2x1WlhJdWIyWm1jMlYwS0NrdWRHOXdJQ3NnYzJWc1ppNGthVzVtYVc1cGRHVmZjMk55YjJ4c1gyTnZiblJoYVc1bGNpNW9aV2xuYUhRb0tTa2dMU0IzYVc1a2IzZGZjMk55YjJ4c08xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWgzYVc1a2IzZGZjMk55YjJ4c1gySnZkSFJ2YlNBK0lISmxjM1ZzZEhOZmMyTnliMnhzWDJKdmRIUnZiU0FySUhOamNtOXNiRjl2Wm1aelpYUXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG14dllXUk5iM0psVW1WemRXeDBjeWdwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmxiSE5sWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdleTh2Wkc5dWRDQnNiMkZrSUcxdmNtVmNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbk4wY21sd1VYVmxjbmxUZEhKcGJtZEJibVJJWVhOb1JuSnZiVkJoZEdnZ1BTQm1kVzVqZEdsdmJpaDFjbXdwSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnY21WMGRYSnVJSFZ5YkM1emNHeHBkQ2hjSWo5Y0lpbGJNRjB1YzNCc2FYUW9YQ0lqWENJcFd6QmRPMXh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NW5kWEFnUFNCbWRXNWpkR2x2YmlnZ2JtRnRaU3dnZFhKc0lDa2dlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaUFvSVhWeWJDa2dkWEpzSUQwZ2JHOWpZWFJwYjI0dWFISmxabHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnVZVzFsSUQwZ2JtRnRaUzV5WlhCc1lXTmxLQzliWEZ4YlhTOHNYQ0pjWEZ4Y1hGeGJYQ0lwTG5KbGNHeGhZMlVvTDF0Y1hGMWRMeXhjSWx4Y1hGeGNYRjFjSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhaaGNpQnlaV2RsZUZNZ1BTQmNJbHRjWEZ4Y1B5WmRYQ0lyYm1GdFpTdGNJajBvVzE0bUkxMHFLVndpTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2NtVm5aWGdnUFNCdVpYY2dVbVZuUlhod0tDQnlaV2RsZUZNZ0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSEpsYzNWc2RITWdQU0J5WldkbGVDNWxlR1ZqS0NCMWNtd2dLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjbVYwZFhKdUlISmxjM1ZzZEhNZ1BUMGdiblZzYkNBL0lHNTFiR3dnT2lCeVpYTjFiSFJ6V3pGZE8xeHlYRzRnSUNBZ0lDQWdJSDA3WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbWRsZEZWeWJGQmhjbUZ0Y3lBOUlHWjFibU4wYVc5dUtHdGxaWEJmY0dGbmFXNWhkR2x2Yml3Z2RIbHdaU3dnWlhoamJIVmtaU2xjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloclpXVndYM0JoWjJsdVlYUnBiMjRwUFQxY0luVnVaR1ZtYVc1bFpGd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2EyVmxjRjl3WVdkcGJtRjBhVzl1SUQwZ2RISjFaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2RIbHdaVzltS0hSNWNHVXBQVDFjSW5WdVpHVm1hVzVsWkZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnZEhsd1pTQTlJRndpWENJN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUIxY214ZmNHRnlZVzF6WDNOMGNpQTlJRndpWENJN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZMeUJuWlhRZ1lXeHNJSEJoY21GdGN5Qm1jbTl0SUdacFpXeGtjMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnZFhKc1gzQmhjbUZ0YzE5aGNuSmhlU0E5SUhCeWIyTmxjM05mWm05eWJTNW5aWFJWY214UVlYSmhiWE1vYzJWc1ppazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2JHVnVaM1JvSUQwZ1QySnFaV04wTG10bGVYTW9kWEpzWDNCaGNtRnRjMTloY25KaGVTa3ViR1Z1WjNSb08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdZMjkxYm5RZ1BTQXdPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2RIbHdaVzltS0dWNFkyeDFaR1VwSVQxY0luVnVaR1ZtYVc1bFpGd2lLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppQW9kWEpzWDNCaGNtRnRjMTloY25KaGVTNW9ZWE5QZDI1UWNtOXdaWEowZVNobGVHTnNkV1JsS1NrZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUd4bGJtZDBhQzB0TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWhzWlc1bmRHZytNQ2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWm05eUlDaDJZWElnYXlCcGJpQjFjbXhmY0dGeVlXMXpYMkZ5Y21GNUtTQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLSFZ5YkY5d1lYSmhiWE5mWVhKeVlYa3VhR0Z6VDNkdVVISnZjR1Z5ZEhrb2F5a3BJSHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJqWVc1ZllXUmtJRDBnZEhKMVpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LR1Y0WTJ4MVpHVXBJVDFjSW5WdVpHVm1hVzVsWkZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHJQVDFsZUdOc2RXUmxLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWTJGdVgyRmtaQ0E5SUdaaGJITmxPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaGpZVzVmWVdSa0tTQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMWNteGZjR0Z5WVcxelgzTjBjaUFyUFNCcklDc2dYQ0k5WENJZ0t5QjFjbXhmY0dGeVlXMXpYMkZ5Y21GNVcydGRPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNoamIzVnVkQ0E4SUd4bGJtZDBhQ0F0SURFcElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjFjbXhmY0dGeVlXMXpYM04wY2lBclBTQmNJaVpjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JqYjNWdWRDc3JPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjWFZsY25sZmNHRnlZVzF6SUQwZ1hDSmNJanRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dlptOXliU0J3WVhKaGJYTWdZWE1nZFhKc0lIRjFaWEo1SUhOMGNtbHVaMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnWm05eWJWOXdZWEpoYlhNZ1BTQjFjbXhmY0dGeVlXMXpYM04wY2p0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDOHZaMlYwSUhWeWJDQndZWEpoYlhNZ1puSnZiU0IwYUdVZ1ptOXliU0JwZEhObGJHWWdLSGRvWVhRZ2RHaGxJSFZ6WlhJZ2FHRnpJSE5sYkdWamRHVmtLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnhkV1Z5ZVY5d1lYSmhiWE1nUFNCelpXeG1MbXB2YVc1VmNteFFZWEpoYlNoeGRXVnllVjl3WVhKaGJYTXNJR1p2Y20xZmNHRnlZVzF6S1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDOHZZV1JrSUhCaFoybHVZWFJwYjI1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2EyVmxjRjl3WVdkcGJtRjBhVzl1UFQxMGNuVmxLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2NHRm5aVTUxYldKbGNpQTlJSE5sYkdZdUpHRnFZWGhmY21WemRXeDBjMTlqYjI1MFlXbHVaWEl1WVhSMGNpaGNJbVJoZEdFdGNHRm5aV1JjSWlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2RIbHdaVzltS0hCaFoyVk9kVzFpWlhJcFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhCaFoyVk9kVzFpWlhJZ1BTQXhPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0hCaFoyVk9kVzFpWlhJK01TbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnhkV1Z5ZVY5d1lYSmhiWE1nUFNCelpXeG1MbXB2YVc1VmNteFFZWEpoYlNoeGRXVnllVjl3WVhKaGJYTXNJRndpYzJaZmNHRm5aV1E5WENJcmNHRm5aVTUxYldKbGNpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dllXUmtJSE5tYVdSY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk5eGRXVnllVjl3WVhKaGJYTWdQU0J6Wld4bUxtcHZhVzVWY214UVlYSmhiU2h4ZFdWeWVWOXdZWEpoYlhNc0lGd2ljMlpwWkQxY0lpdHpaV3htTG5ObWFXUXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk4Z2JHOXZjQ0IwYUhKdmRXZG9JR0Z1ZVNCbGVIUnlZU0J3WVhKaGJYTWdLR1p5YjIwZ1pYaDBJSEJzZFdkcGJuTXBJR0Z1WkNCaFpHUWdkRzhnZEdobElIVnliQ0FvYVdVZ2QyOXZZMjl0YldWeVkyVWdZRzl5WkdWeVlubGdLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZLblpoY2lCbGVIUnlZVjl4ZFdWeWVWOXdZWEpoYlNBOUlGd2lYQ0k3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2JHVnVaM1JvSUQwZ1QySnFaV04wTG10bGVYTW9jMlZzWmk1bGVIUnlZVjl4ZFdWeWVWOXdZWEpoYlhNcExteGxibWQwYUR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQmpiM1Z1ZENBOUlEQTdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2JHVnVaM1JvUGpBcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdabTl5SUNoMllYSWdheUJwYmlCelpXeG1MbVY0ZEhKaFgzRjFaWEo1WDNCaGNtRnRjeWtnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnYVdZZ0tITmxiR1l1WlhoMGNtRmZjWFZsY25sZmNHRnlZVzF6TG1oaGMwOTNibEJ5YjNCbGNuUjVLR3NwS1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdhV1lvYzJWc1ppNWxlSFJ5WVY5eGRXVnllVjl3WVhKaGJYTmJhMTBoUFZ3aVhDSXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQmxlSFJ5WVY5eGRXVnllVjl3WVhKaGJTQTlJR3NyWENJOVhDSXJjMlZzWmk1bGVIUnlZVjl4ZFdWeWVWOXdZWEpoYlhOYmExMDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQnhkV1Z5ZVY5d1lYSmhiWE1nUFNCelpXeG1MbXB2YVc1VmNteFFZWEpoYlNoeGRXVnllVjl3WVhKaGJYTXNJR1Y0ZEhKaFgzRjFaWEo1WDNCaGNtRnRLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ292WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSEYxWlhKNVgzQmhjbUZ0Y3lBOUlITmxiR1l1WVdSa1VYVmxjbmxRWVhKaGJYTW9jWFZsY25sZmNHRnlZVzF6TENCelpXeG1MbVY0ZEhKaFgzRjFaWEo1WDNCaGNtRnRjeTVoYkd3cE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvZEhsd1pTRTlYQ0pjSWlsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXhkV1Z5ZVY5d1lYSmhiWE1nUFNCelpXeG1MbUZrWkZGMVpYSjVVR0Z5WVcxektIRjFaWEo1WDNCaGNtRnRjeXdnYzJWc1ppNWxlSFJ5WVY5eGRXVnllVjl3WVhKaGJYTmJkSGx3WlYwcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnlaWFIxY200Z2NYVmxjbmxmY0dGeVlXMXpPMXh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1Ga1pGRjFaWEo1VUdGeVlXMXpJRDBnWm5WdVkzUnBiMjRvY1hWbGNubGZjR0Z5WVcxekxDQnVaWGRmY0dGeVlXMXpLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHVjRkSEpoWDNGMVpYSjVYM0JoY21GdElEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHeGxibWQwYUNBOUlFOWlhbVZqZEM1clpYbHpLRzVsZDE5d1lYSmhiWE1wTG14bGJtZDBhRHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUdOdmRXNTBJRDBnTUR0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lHbG1LR3hsYm1kMGFENHdLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1ptOXlJQ2gyWVhJZ2F5QnBiaUJ1WlhkZmNHRnlZVzF6S1NCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZZ0tHNWxkMTl3WVhKaGJYTXVhR0Z6VDNkdVVISnZjR1Z5ZEhrb2F5a3BJSHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LRzVsZDE5d1lYSmhiWE5iYTEwaFBWd2lYQ0lwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1Y0ZEhKaFgzRjFaWEo1WDNCaGNtRnRJRDBnYXl0Y0lqMWNJaXR1WlhkZmNHRnlZVzF6VzJ0ZE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnY1hWbGNubGZjR0Z5WVcxeklEMGdjMlZzWmk1cWIybHVWWEpzVUdGeVlXMG9jWFZsY25sZmNHRnlZVzF6TENCbGVIUnlZVjl4ZFdWeWVWOXdZWEpoYlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSEpsZEhWeWJpQnhkV1Z5ZVY5d1lYSmhiWE03WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSFJvYVhNdVlXUmtWWEpzVUdGeVlXMGdQU0JtZFc1amRHbHZiaWgxY213c0lITjBjbWx1WnlsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJoWkdSZmNHRnlZVzF6SUQwZ1hDSmNJanRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUtIVnliQ0U5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvZFhKc0xtbHVaR1Y0VDJZb1hDSS9YQ0lwSUNFOUlDMHhLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR0ZrWkY5d1lYSmhiWE1nS3owZ1hDSW1YQ0k3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmxiSE5sWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5MWNtd2dQU0IwYUdsekxuUnlZV2xzYVc1blUyeGhjMmhKZENoMWNtd3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHRmtaRjl3WVhKaGJYTWdLejBnWENJL1hDSTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUtITjBjbWx1WnlFOVhDSmNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lISmxkSFZ5YmlCMWNtd2dLeUJoWkdSZmNHRnlZVzF6SUNzZ2MzUnlhVzVuTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJWY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2NtVjBkWEp1SUhWeWJEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSDA3WEhKY2JseHlYRzRnSUNBZ0lDQWdJSFJvYVhNdWFtOXBibFZ5YkZCaGNtRnRJRDBnWm5WdVkzUnBiMjRvY0dGeVlXMXpMQ0J6ZEhKcGJtY3BYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1lXUmtYM0JoY21GdGN5QTlJRndpWENJN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWh3WVhKaGJYTWhQVndpWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHRmtaRjl3WVhKaGJYTWdLejBnWENJbVhDSTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0hOMGNtbHVaeUU5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJ3WVhKaGJYTWdLeUJoWkdSZmNHRnlZVzF6SUNzZ2MzUnlhVzVuTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJWY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2NtVjBkWEp1SUhCaGNtRnRjenRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUgwN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVjMlYwUVdwaGVGSmxjM1ZzZEhOVlVreHpJRDBnWm5WdVkzUnBiMjRvY1hWbGNubGZjR0Z5WVcxektWeHlYRzRnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LSE5sYkdZdVlXcGhlRjl5WlhOMWJIUnpYMk52Ym1ZcFBUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbUZxWVhoZmNtVnpkV3gwYzE5amIyNW1JRDBnYm1WM0lFRnljbUY1S0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WVdwaGVGOXlaWE4xYkhSelgyTnZibVpiSjNCeWIyTmxjM05wYm1kZmRYSnNKMTBnUFNCY0lsd2lPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1GcVlYaGZjbVZ6ZFd4MGMxOWpiMjVtV3lkeVpYTjFiSFJ6WDNWeWJDZGRJRDBnWENKY0lqdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVoYW1GNFgzSmxjM1ZzZEhOZlkyOXVabHNuWkdGMFlWOTBlWEJsSjEwZ1BTQmNJbHdpTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OXBaaWh6Wld4bUxtRnFZWGhmZFhKc0lUMWNJbHdpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloelpXeG1MbVJwYzNCc1lYbGZjbVZ6ZFd4MFgyMWxkR2h2WkQwOVhDSnphRzl5ZEdOdlpHVmNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdleTh2ZEdobGJpQjNaU0IzWVc1MElIUnZJR1J2SUdFZ2NtVnhkV1Z6ZENCMGJ5QjBhR1VnWVdwaGVDQmxibVJ3YjJsdWRGeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1aGFtRjRYM0psYzNWc2RITmZZMjl1WmxzbmNtVnpkV3gwYzE5MWNtd25YU0E5SUhObGJHWXVZV1JrVlhKc1VHRnlZVzBvYzJWc1ppNXlaWE4xYkhSelgzVnliQ3dnY1hWbGNubGZjR0Z5WVcxektUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwyRmtaQ0JzWVc1bklHTnZaR1VnZEc4Z1lXcGhlQ0JoY0drZ2NtVnhkV1Z6ZEN3Z2JHRnVaeUJqYjJSbElITm9iM1ZzWkNCaGJISmxZV1I1SUdKbElHbHVJSFJvWlhKbElHWnZjaUJ2ZEdobGNpQnlaWEYxWlhOMGN5QW9hV1VzSUhOMWNIQnNhV1ZrSUdsdUlIUm9aU0JTWlhOMWJIUnpJRlZTVENsY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmloelpXeG1MbXhoYm1kZlkyOWtaU0U5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM052SUdGa1pDQnBkRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIRjFaWEo1WDNCaGNtRnRjeUE5SUhObGJHWXVhbTlwYmxWeWJGQmhjbUZ0S0hGMVpYSjVYM0JoY21GdGN5d2dYQ0pzWVc1blBWd2lLM05sYkdZdWJHRnVaMTlqYjJSbEtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1GcVlYaGZjbVZ6ZFd4MGMxOWpiMjVtV3lkd2NtOWpaWE56YVc1blgzVnliQ2RkSUQwZ2MyVnNaaTVoWkdSVmNteFFZWEpoYlNoelpXeG1MbUZxWVhoZmRYSnNMQ0J4ZFdWeWVWOXdZWEpoYlhNcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTl6Wld4bUxtRnFZWGhmY21WemRXeDBjMTlqYjI1bVd5ZGtZWFJoWDNSNWNHVW5YU0E5SUNkcWMyOXVKenRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnWld4elpTQnBaaWh6Wld4bUxtUnBjM0JzWVhsZmNtVnpkV3gwWDIxbGRHaHZaRDA5WENKd2IzTjBYM1I1Y0dWZllYSmphR2wyWlZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQndjbTlqWlhOelgyWnZjbTB1YzJWMFZHRjRRWEpqYUdsMlpWSmxjM1ZzZEhOVmNtd29jMlZzWml3Z2MyVnNaaTV5WlhOMWJIUnpYM1Z5YkNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnY21WemRXeDBjMTkxY213Z1BTQndjbTlqWlhOelgyWnZjbTB1WjJWMFVtVnpkV3gwYzFWeWJDaHpaV3htTENCelpXeG1MbkpsYzNWc2RITmZkWEpzS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxtRnFZWGhmY21WemRXeDBjMTlqYjI1bVd5ZHlaWE4xYkhSelgzVnliQ2RkSUQwZ2MyVnNaaTVoWkdSVmNteFFZWEpoYlNoeVpYTjFiSFJ6WDNWeWJDd2djWFZsY25sZmNHRnlZVzF6S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WVdwaGVGOXlaWE4xYkhSelgyTnZibVpiSjNCeWIyTmxjM05wYm1kZmRYSnNKMTBnUFNCelpXeG1MbUZrWkZWeWJGQmhjbUZ0S0hKbGMzVnNkSE5mZFhKc0xDQnhkV1Z5ZVY5d1lYSmhiWE1wTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCbGJITmxJR2xtS0hObGJHWXVaR2x6Y0d4aGVWOXlaWE4xYkhSZmJXVjBhRzlrUFQxY0ltTjFjM1J2YlY5M2IyOWpiMjF0WlhKalpWOXpkRzl5WlZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQndjbTlqWlhOelgyWnZjbTB1YzJWMFZHRjRRWEpqYUdsMlpWSmxjM1ZzZEhOVmNtd29jMlZzWml3Z2MyVnNaaTV5WlhOMWJIUnpYM1Z5YkNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnY21WemRXeDBjMTkxY213Z1BTQndjbTlqWlhOelgyWnZjbTB1WjJWMFVtVnpkV3gwYzFWeWJDaHpaV3htTENCelpXeG1MbkpsYzNWc2RITmZkWEpzS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxtRnFZWGhmY21WemRXeDBjMTlqYjI1bVd5ZHlaWE4xYkhSelgzVnliQ2RkSUQwZ2MyVnNaaTVoWkdSVmNteFFZWEpoYlNoeVpYTjFiSFJ6WDNWeWJDd2djWFZsY25sZmNHRnlZVzF6S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WVdwaGVGOXlaWE4xYkhSelgyTnZibVpiSjNCeWIyTmxjM05wYm1kZmRYSnNKMTBnUFNCelpXeG1MbUZrWkZWeWJGQmhjbUZ0S0hKbGMzVnNkSE5mZFhKc0xDQnhkV1Z5ZVY5d1lYSmhiWE1wTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCbGJITmxYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIc3ZMMjkwYUdWeWQybHpaU0IzWlNCM1lXNTBJSFJ2SUhCMWJHd2dkR2hsSUhKbGMzVnNkSE1nWkdseVpXTjBiSGtnWm5KdmJTQjBhR1VnY21WemRXeDBjeUJ3WVdkbFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1GcVlYaGZjbVZ6ZFd4MGMxOWpiMjVtV3lkeVpYTjFiSFJ6WDNWeWJDZGRJRDBnYzJWc1ppNWhaR1JWY214UVlYSmhiU2h6Wld4bUxuSmxjM1ZzZEhOZmRYSnNMQ0J4ZFdWeWVWOXdZWEpoYlhNcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1aGFtRjRYM0psYzNWc2RITmZZMjl1WmxzbmNISnZZMlZ6YzJsdVoxOTFjbXduWFNBOUlITmxiR1l1WVdSa1ZYSnNVR0Z5WVcwb2MyVnNaaTVoYW1GNFgzVnliQ3dnY1hWbGNubGZjR0Z5WVcxektUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2YzJWc1ppNWhhbUY0WDNKbGMzVnNkSE5mWTI5dVpsc25aR0YwWVY5MGVYQmxKMTBnUFNBbmFIUnRiQ2M3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhObGJHWXVZV3BoZUY5eVpYTjFiSFJ6WDJOdmJtWmJKM0J5YjJObGMzTnBibWRmZFhKc0oxMGdQU0J6Wld4bUxtRmtaRkYxWlhKNVVHRnlZVzF6S0hObGJHWXVZV3BoZUY5eVpYTjFiSFJ6WDJOdmJtWmJKM0J5YjJObGMzTnBibWRmZFhKc0oxMHNJSE5sYkdZdVpYaDBjbUZmY1hWbGNubGZjR0Z5WVcxeld5ZGhhbUY0SjEwcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1aGFtRjRYM0psYzNWc2RITmZZMjl1WmxzblpHRjBZVjkwZVhCbEoxMGdQU0J6Wld4bUxtRnFZWGhmWkdGMFlWOTBlWEJsTzF4eVhHNGdJQ0FnSUNBZ0lIMDdYSEpjYmx4eVhHNWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTUxY0dSaGRHVk1iMkZrWlhKVVlXY2dQU0JtZFc1amRHbHZiaWdrYjJKcVpXTjBLU0I3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKSEJoY21WdWREdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0hObGJHWXVhVzVtYVc1cGRHVmZjMk55YjJ4c1gzSmxjM1ZzZEY5amJHRnpjeUU5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKSEJoY21WdWRDQTlJSE5sYkdZdUpHbHVabWx1YVhSbFgzTmpjbTlzYkY5amIyNTBZV2x1WlhJdVptbHVaQ2h6Wld4bUxtbHVabWx1YVhSbFgzTmpjbTlzYkY5eVpYTjFiSFJmWTJ4aGMzTXBMbXhoYzNRb0tTNXdZWEpsYm5Rb0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCbGJITmxYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1J3WVhKbGJuUWdQU0J6Wld4bUxpUnBibVpwYm1sMFpWOXpZM0p2Ykd4ZlkyOXVkR0ZwYm1WeU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnZEdGblRtRnRaU0E5SUNSd1lYSmxiblF1Y0hKdmNDaGNJblJoWjA1aGJXVmNJaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdkR0ZuVkhsd1pTQTlJQ2RrYVhZbk8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppZ2dLQ0IwWVdkT1lXMWxMblJ2VEc5M1pYSkRZWE5sS0NrZ1BUMGdKMjlzSnlBcElIeDhJQ2dnZEdGblRtRnRaUzUwYjB4dmQyVnlRMkZ6WlNncElEMDlJQ2QxYkNjZ0tTQXBlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZEdGblZIbHdaU0E5SUNkc2FTYzdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2JtVjNJRDBnSkNnblBDY3JkR0ZuVkhsd1pTc25JQzgrSnlrdWFIUnRiQ2drYjJKcVpXTjBMbWgwYld3b0tTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJoZEhSeWFXSjFkR1Z6SUQwZ0pHOWlhbVZqZEM1d2NtOXdLRndpWVhSMGNtbGlkWFJsYzF3aUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQzh2SUd4dmIzQWdkR2h5YjNWbmFDQThjMlZzWldOMFBpQmhkSFJ5YVdKMWRHVnpJR0Z1WkNCaGNIQnNlU0IwYUdWdElHOXVJRHhrYVhZK1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNRdVpXRmphQ2hoZEhSeWFXSjFkR1Z6TENCbWRXNWpkR2x2YmlncElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1J1WlhjdVlYUjBjaWgwYUdsekxtNWhiV1VzSUhSb2FYTXVkbUZzZFdVcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSEpsZEhWeWJpQWtibVYzTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbXh2WVdSTmIzSmxVbVZ6ZFd4MGN5QTlJR1oxYm1OMGFXOXVLQ2xjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUlDZ2dkR2hwY3k1cGMxOXRZWGhmY0dGblpXUWdQVDA5SUhSeWRXVWdLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCeVpYUjFjbTQ3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGMxOXNiMkZrYVc1blgyMXZjbVVnUFNCMGNuVmxPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk5MGNtbG5aMlZ5SUhOMFlYSjBJR1YyWlc1MFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhaaGNpQmxkbVZ1ZEY5a1lYUmhJRDBnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyWnBaRG9nYzJWc1ppNXpabWxrTEZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RHRnlaMlYwVTJWc1pXTjBiM0k2SUhObGJHWXVZV3BoZUY5MFlYSm5aWFJmWVhSMGNpeGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFI1Y0dVNklGd2liRzloWkY5dGIzSmxYQ0lzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCdlltcGxZM1E2SUhObGJHWmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWRISnBaMmRsY2tWMlpXNTBLRndpYzJZNllXcGhlSE4wWVhKMFhDSXNJR1YyWlc1MFgyUmhkR0VwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0J3Y205alpYTnpYMlp2Y20wdWMyVjBWR0Y0UVhKamFHbDJaVkpsYzNWc2RITlZjbXdvYzJWc1ppd2djMlZzWmk1eVpYTjFiSFJ6WDNWeWJDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ4ZFdWeWVWOXdZWEpoYlhNZ1BTQnpaV3htTG1kbGRGVnliRkJoY21GdGN5aDBjblZsS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNXNZWE4wWDNOMVltMXBkRjl4ZFdWeWVWOXdZWEpoYlhNZ1BTQnpaV3htTG1kbGRGVnliRkJoY21GdGN5aG1ZV3h6WlNrN0lDOHZaM0poWWlCaElHTnZjSGtnYjJZZ2FIUmxJRlZTVENCd1lYSmhiWE1nZDJsMGFHOTFkQ0J3WVdkcGJtRjBhVzl1SUdGc2NtVmhaSGtnWVdSa1pXUmNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCaGFtRjRYM0J5YjJObGMzTnBibWRmZFhKc0lEMGdYQ0pjSWp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHRnFZWGhmY21WemRXeDBjMTkxY213Z1BTQmNJbHdpTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1pHRjBZVjkwZVhCbElEMGdYQ0pjSWp0Y2NseHVYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2TDI1dmR5QmhaR1FnZEdobElHNWxkeUJ3WVdkcGJtRjBhVzl1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCdVpYaDBYM0JoWjJWa1gyNTFiV0psY2lBOUlIUm9hWE11WTNWeWNtVnVkRjl3WVdkbFpDQXJJREU3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSEYxWlhKNVgzQmhjbUZ0Y3lBOUlITmxiR1l1YW05cGJsVnliRkJoY21GdEtIRjFaWEo1WDNCaGNtRnRjeXdnWENKelpsOXdZV2RsWkQxY0lpdHVaWGgwWDNCaFoyVmtYMjUxYldKbGNpazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxuTmxkRUZxWVhoU1pYTjFiSFJ6VlZKTWN5aHhkV1Z5ZVY5d1lYSmhiWE1wTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JoYW1GNFgzQnliMk5sYzNOcGJtZGZkWEpzSUQwZ2MyVnNaaTVoYW1GNFgzSmxjM1ZzZEhOZlkyOXVabHNuY0hKdlkyVnpjMmx1WjE5MWNtd25YVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdZV3BoZUY5eVpYTjFiSFJ6WDNWeWJDQTlJSE5sYkdZdVlXcGhlRjl5WlhOMWJIUnpYMk52Ym1aYkozSmxjM1ZzZEhOZmRYSnNKMTA3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1JoZEdGZmRIbHdaU0E5SUhObGJHWXVZV3BoZUY5eVpYTjFiSFJ6WDJOdmJtWmJKMlJoZEdGZmRIbHdaU2RkTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OWhZbTl5ZENCaGJua2djSEpsZG1sdmRYTWdZV3BoZUNCeVpYRjFaWE4wYzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloelpXeG1MbXhoYzNSZllXcGhlRjl5WlhGMVpYTjBLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxteGhjM1JmWVdwaGVGOXlaWEYxWlhOMExtRmliM0owS0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lHbG1LSE5sYkdZdWRYTmxYM05qY205c2JGOXNiMkZrWlhJOVBURXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2JHOWhaR1Z5SUQwZ0pDZ25QR1JwZGk4K0p5eDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0oyTnNZWE56SnpvZ0ozTmxZWEpqYUMxbWFXeDBaWEl0YzJOeWIyeHNMV3h2WVdScGJtY25YSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5S1Rzdkx5NWhjSEJsYm1SVWJ5aHpaV3htTGlSaGFtRjRYM0psYzNWc2RITmZZMjl1ZEdGcGJtVnlLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtiRzloWkdWeUlEMGdjMlZzWmk1MWNHUmhkR1ZNYjJGa1pYSlVZV2NvSkd4dllXUmxjaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGJtWnBibWwwWlZOamNtOXNiRUZ3Y0dWdVpDZ2tiRzloWkdWeUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbXhoYzNSZllXcGhlRjl5WlhGMVpYTjBJRDBnSkM1blpYUW9ZV3BoZUY5d2NtOWpaWE56YVc1blgzVnliQ3dnWm5WdVkzUnBiMjRvWkdGMFlTd2djM1JoZEhWekxDQnlaWEYxWlhOMEtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1OMWNuSmxiblJmY0dGblpXUXJLenRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXViR0Z6ZEY5aGFtRjRYM0psY1hWbGMzUWdQU0J1ZFd4c08xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dklDb3FLaW9xS2lvcUtpb3FLaW9xWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkx5QlVUMFJQSUMwZ1VFRlRWRVVnVkVoSlV5QkJUa1FnVjBGVVEwZ2dWRWhGSUZKRlJFbFNSVU5VSUMwZ1QwNU1XU0JJUVZCUVJVNVRJRmRKVkVnZ1YwTWdLRU5RVkNCQlRrUWdWRUZZSUVSUFJWTWdUazlVS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OGdhSFIwY0hNNkx5OXpaV0Z5WTJndFptbHNkR1Z5TG5SbGMzUXZjSEp2WkhWamRDMWpZWFJsWjI5eWVTOWpiRzkwYUdsdVp5OTBjMmhwY25SekwzQmhaMlV2TXk4L2MyWmZjR0ZuWldROU0xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmRYQmtZWFJsY3lCMGFHVWdjbVZ6ZFhSc2N5QW1JR1p2Y20wZ2FIUnRiRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNWhaR1JTWlhOMWJIUnpLR1JoZEdFc0lHUmhkR0ZmZEhsd1pTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5TENCa1lYUmhYM1I1Y0dVcExtWmhhV3dvWm5WdVkzUnBiMjRvYW5GWVNGSXNJSFJsZUhSVGRHRjBkWE1zSUdWeWNtOXlWR2h5YjNkdUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnWkdGMFlTQTlJSHQ5TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pHRjBZUzV6Wm1sa0lEMGdjMlZzWmk1elptbGtPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWkdGMFlTNXZZbXBsWTNRZ1BTQnpaV3htTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pHRjBZUzUwWVhKblpYUlRaV3hsWTNSdmNpQTlJSE5sYkdZdVlXcGhlRjkwWVhKblpYUmZZWFIwY2p0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHUmhkR0V1WVdwaGVGVlNUQ0E5SUdGcVlYaGZjSEp2WTJWemMybHVaMTkxY213N1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJoTG1weFdFaFNJRDBnYW5GWVNGSTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JrWVhSaExuUmxlSFJUZEdGMGRYTWdQU0IwWlhoMFUzUmhkSFZ6TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pHRjBZUzVsY25KdmNsUm9jbTkzYmlBOUlHVnljbTl5VkdoeWIzZHVPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNTBjbWxuWjJWeVJYWmxiblFvWENKelpqcGhhbUY0WlhKeWIzSmNJaXdnWkdGMFlTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5S1M1aGJIZGhlWE1vWm5WdVkzUnBiMjRvS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdaR0YwWVNBOUlIdDlPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWkdGMFlTNXpabWxrSUQwZ2MyVnNaaTV6Wm1sa08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaR0YwWVM1MFlYSm5aWFJUWld4bFkzUnZjaUE5SUhObGJHWXVZV3BoZUY5MFlYSm5aWFJmWVhSMGNqdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdWIySnFaV04wSUQwZ2MyVnNaanRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWh6Wld4bUxuVnpaVjl6WTNKdmJHeGZiRzloWkdWeVBUMHhLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1JzYjJGa1pYSXVaR1YwWVdOb0tDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGMxOXNiMkZrYVc1blgyMXZjbVVnUFNCbVlXeHpaVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG5SeWFXZG5aWEpGZG1WdWRDaGNJbk5tT21GcVlYaG1hVzVwYzJoY0lpd2daR0YwWVNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgwcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1bVpYUmphRUZxWVhoU1pYTjFiSFJ6SUQwZ1puVnVZM1JwYjI0b0tWeHlYRzRnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdMeTkwY21sbloyVnlJSE4wWVhKMElHVjJaVzUwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCbGRtVnVkRjlrWVhSaElEMGdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJacFpEb2djMlZzWmk1elptbGtMRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZEdGeVoyVjBVMlZzWldOMGIzSTZJSE5sYkdZdVlXcGhlRjkwWVhKblpYUmZZWFIwY2l4Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIUjVjR1U2SUZ3aWJHOWhaRjl5WlhOMWJIUnpYQ0lzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCdlltcGxZM1E2SUhObGJHWmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWRISnBaMmRsY2tWMlpXNTBLRndpYzJZNllXcGhlSE4wWVhKMFhDSXNJR1YyWlc1MFgyUmhkR0VwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OXlaV1p2WTNWeklHRnVlU0JwYm5CMWRDQm1hV1ZzWkhNZ1lXWjBaWElnZEdobElHWnZjbTBnYUdGeklHSmxaVzRnZFhCa1lYUmxaRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnSkd4aGMzUmZZV04wYVhabFgybHVjSFYwWDNSbGVIUWdQU0FrZEdocGN5NW1hVzVrS0NkcGJuQjFkRnQwZVhCbFBWd2lkR1Y0ZEZ3aVhUcG1iMk4xY3ljcExtNXZkQ2hjSWk1elppMWtZWFJsY0dsamEyVnlYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmlna2JHRnpkRjloWTNScGRtVmZhVzV3ZFhSZmRHVjRkQzVzWlc1bmRHZzlQVEVwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnNZWE4wWDJGamRHbDJaVjlwYm5CMWRGOTBaWGgwSUQwZ0pHeGhjM1JmWVdOMGFYWmxYMmx1Y0hWMFgzUmxlSFF1WVhSMGNpaGNJbTVoYldWY0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ1IwYUdsekxtRmtaRU5zWVhOektGd2ljMlZoY21Ob0xXWnBiSFJsY2kxa2FYTmhZbXhsWkZ3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2NISnZZMlZ6YzE5bWIzSnRMbVJwYzJGaWJHVkpibkIxZEhNb2MyVnNaaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBdkwyWmhaR1VnYjNWMElISmxjM1ZzZEhOY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNGtZV3BoZUY5eVpYTjFiSFJ6WDJOdmJuUmhhVzVsY2k1aGJtbHRZWFJsS0hzZ2IzQmhZMmwwZVRvZ01DNDFJSDBzSUZ3aVptRnpkRndpS1RzZ0x5OXNiMkZrYVc1blhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhObGJHWXVabUZrWlVOdmJuUmxiblJCY21WaGN5Z2dYQ0p2ZFhSY0lpQXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2MyVnNaaTVoYW1GNFgyRmpkR2x2YmowOVhDSndZV2RwYm1GMGFXOXVYQ0lwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmJtVmxaQ0IwYnlCeVpXMXZkbVVnWVdOMGFYWmxJR1pwYkhSbGNpQm1jbTl0SUZWU1RGeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmNYVmxjbmxmY0dGeVlXMXpJRDBnYzJWc1ppNXNZWE4wWDNOMVltMXBkRjl4ZFdWeWVWOXdZWEpoYlhNN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5dWIzY2dZV1JrSUhSb1pTQnVaWGNnY0dGbmFXNWhkR2x2Ymx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSEJoWjJWT2RXMWlaWElnUFNCelpXeG1MaVJoYW1GNFgzSmxjM1ZzZEhOZlkyOXVkR0ZwYm1WeUxtRjBkSElvWENKa1lYUmhMWEJoWjJWa1hDSXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LSFI1Y0dWdlppaHdZV2RsVG5WdFltVnlLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQndZV2RsVG5WdFltVnlJRDBnTVR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSEJ5YjJObGMzTmZabTl5YlM1elpYUlVZWGhCY21Ob2FYWmxVbVZ6ZFd4MGMxVnliQ2h6Wld4bUxDQnpaV3htTG5KbGMzVnNkSE5mZFhKc0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSEYxWlhKNVgzQmhjbUZ0Y3lBOUlITmxiR1l1WjJWMFZYSnNVR0Z5WVcxektHWmhiSE5sS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlod1lXZGxUblZ0WW1WeVBqRXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjWFZsY25sZmNHRnlZVzF6SUQwZ2MyVnNaaTVxYjJsdVZYSnNVR0Z5WVcwb2NYVmxjbmxmY0dGeVlXMXpMQ0JjSW5ObVgzQmhaMlZrUFZ3aUszQmhaMlZPZFcxaVpYSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCbGJITmxJR2xtS0hObGJHWXVZV3BoZUY5aFkzUnBiMjQ5UFZ3aWMzVmliV2wwWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ4ZFdWeWVWOXdZWEpoYlhNZ1BTQnpaV3htTG1kbGRGVnliRkJoY21GdGN5aDBjblZsS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1YkdGemRGOXpkV0p0YVhSZmNYVmxjbmxmY0dGeVlXMXpJRDBnYzJWc1ppNW5aWFJWY214UVlYSmhiWE1vWm1Gc2MyVXBPeUF2TDJkeVlXSWdZU0JqYjNCNUlHOW1JR2gwWlNCVlVrd2djR0Z5WVcxeklIZHBkR2h2ZFhRZ2NHRm5hVzVoZEdsdmJpQmhiSEpsWVdSNUlHRmtaR1ZrWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhaaGNpQmhhbUY0WDNCeWIyTmxjM05wYm1kZmRYSnNJRDBnWENKY0lqdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR0ZxWVhoZmNtVnpkV3gwYzE5MWNtd2dQU0JjSWx3aU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdaR0YwWVY5MGVYQmxJRDBnWENKY0lqdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWMyVjBRV3BoZUZKbGMzVnNkSE5WVWt4ektIRjFaWEo1WDNCaGNtRnRjeWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR0ZxWVhoZmNISnZZMlZ6YzJsdVoxOTFjbXdnUFNCelpXeG1MbUZxWVhoZmNtVnpkV3gwYzE5amIyNW1XeWR3Y205alpYTnphVzVuWDNWeWJDZGRPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQmhhbUY0WDNKbGMzVnNkSE5mZFhKc0lEMGdjMlZzWmk1aGFtRjRYM0psYzNWc2RITmZZMjl1WmxzbmNtVnpkV3gwYzE5MWNtd25YVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdaR0YwWVY5MGVYQmxJRDBnYzJWc1ppNWhhbUY0WDNKbGMzVnNkSE5mWTI5dVpsc25aR0YwWVY5MGVYQmxKMTA3WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdMeTloWW05eWRDQmhibmtnY0hKbGRtbHZkWE1nWVdwaGVDQnlaWEYxWlhOMGMxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTG14aGMzUmZZV3BoZUY5eVpYRjFaWE4wS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbXhoYzNSZllXcGhlRjl5WlhGMVpYTjBMbUZpYjNKMEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR0ZxWVhoZllXTjBhVzl1SUQwZ2MyVnNaaTVoYW1GNFgyRmpkR2x2Ymp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNXNZWE4wWDJGcVlYaGZjbVZ4ZFdWemRDQTlJQ1F1WjJWMEtHRnFZWGhmY0hKdlkyVnpjMmx1WjE5MWNtd3NJR1oxYm1OMGFXOXVLR1JoZEdFc0lITjBZWFIxY3l3Z2NtVnhkV1Z6ZENsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVzWVhOMFgyRnFZWGhmY21WeGRXVnpkQ0E5SUc1MWJHdzdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OTFjR1JoZEdWeklIUm9aU0J5WlhOMWRHeHpJQ1lnWm05eWJTQm9kRzFzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MblZ3WkdGMFpWSmxjM1ZzZEhNb1pHRjBZU3dnWkdGMFlWOTBlWEJsS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2THlCelkzSnZiR3dnWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkx5QnpaWFFnZEdobElIWmhjaUJpWVdOcklIUnZJSGRvWVhRZ2FYUWdkMkZ6SUdKbFptOXlaU0IwYUdVZ1lXcGhlQ0J5WlhGMVpYTjBJRzVoWkNCMGFHVWdabTl5YlNCeVpTMXBibWwwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbUZxWVhoZllXTjBhVzl1SUQwZ1lXcGhlRjloWTNScGIyNDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxuTmpjbTlzYkZKbGMzVnNkSE1vSUhObGJHWXVZV3BoZUY5aFkzUnBiMjRnS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2S2lCMWNHUmhkR1VnVlZKTUlDb3ZYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNWd1pHRjBaU0IxY213Z1ltVm1iM0psSUhCaFoybHVZWFJwYjI0c0lHSmxZMkYxYzJVZ2QyVWdibVZsWkNCMGJ5QmtieUJ6YjIxbElHTm9aV05yY3lCaFoyRnBibk1nZEdobElGVlNUQ0JtYjNJZ2FXNW1hVzVwZEdVZ2MyTnliMnhzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MblZ3WkdGMFpWVnliRWhwYzNSdmNua29ZV3BoZUY5eVpYTjFiSFJ6WDNWeWJDazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXpaWFIxY0NCd1lXZHBibUYwYVc5dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG5ObGRIVndRV3BoZUZCaFoybHVZWFJwYjI0b0tUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbWx6VTNWaWJXbDBkR2x1WnlBOUlHWmhiSE5sTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzhxSUhWelpYSWdaR1ZtSUNvdlhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1sdWFYUlhiMjlEYjIxdFpYSmpaVU52Ym5SeWIyeHpLQ2s3SUM4dmQyOXZZMjl0YldWeVkyVWdiM0prWlhKaWVWeHlYRzVjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUgwc0lHUmhkR0ZmZEhsd1pTa3VabUZwYkNobWRXNWpkR2x2YmlocWNWaElVaXdnZEdWNGRGTjBZWFIxY3l3Z1pYSnliM0pVYUhKdmQyNHBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCa1lYUmhJRDBnZTMwN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJoTG5ObWFXUWdQU0J6Wld4bUxuTm1hV1E3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCa1lYUmhMblJoY21kbGRGTmxiR1ZqZEc5eUlEMGdjMlZzWmk1aGFtRjRYM1JoY21kbGRGOWhkSFJ5TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pHRjBZUzV2WW1wbFkzUWdQU0J6Wld4bU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaR0YwWVM1aGFtRjRWVkpNSUQwZ1lXcGhlRjl3Y205alpYTnphVzVuWDNWeWJEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdWFuRllTRklnUFNCcWNWaElVanRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdSaGRHRXVkR1Y0ZEZOMFlYUjFjeUE5SUhSbGVIUlRkR0YwZFhNN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJoTG1WeWNtOXlWR2h5YjNkdUlEMGdaWEp5YjNKVWFISnZkMjQ3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbWx6VTNWaWJXbDBkR2x1WnlBOUlHWmhiSE5sTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTUwY21sbloyVnlSWFpsYm5Rb1hDSnpaanBoYW1GNFpYSnliM0pjSWl3Z1pHRjBZU2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOUtTNWhiSGRoZVhNb1puVnVZM1JwYjI0b0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTGlSaGFtRjRYM0psYzNWc2RITmZZMjl1ZEdGcGJtVnlMbk4wYjNBb2RISjFaU3gwY25WbEtTNWhibWx0WVhSbEtIc2diM0JoWTJsMGVUb2dNWDBzSUZ3aVptRnpkRndpS1RzZ0x5OW1hVzVwYzJobFpDQnNiMkZrYVc1blhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1aaFpHVkRiMjUwWlc1MFFYSmxZWE1vSUZ3aWFXNWNJaUFwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR1JoZEdFZ1BTQjdmVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdSaGRHRXVjMlpwWkNBOUlITmxiR1l1YzJacFpEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdWRHRnlaMlYwVTJWc1pXTjBiM0lnUFNCelpXeG1MbUZxWVhoZmRHRnlaMlYwWDJGMGRISTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JrWVhSaExtOWlhbVZqZENBOUlITmxiR1k3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2RHaHBjeTV5WlcxdmRtVkRiR0Z6Y3loY0luTmxZWEpqYUMxbWFXeDBaWEl0WkdsellXSnNaV1JjSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQndjbTlqWlhOelgyWnZjbTB1Wlc1aFlteGxTVzV3ZFhSektITmxiR1lwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2Y21WbWIyTjFjeUIwYUdVZ2JHRnpkQ0JoWTNScGRtVWdkR1Y0ZENCbWFXVnNaRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2JHRnpkRjloWTNScGRtVmZhVzV3ZFhSZmRHVjRkQ0U5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnSkdsdWNIVjBJRDBnVzEwN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNGtabWxsYkdSekxtVmhZMmdvWm5WdVkzUnBiMjRvS1h0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa1lXTjBhWFpsWDJsdWNIVjBJRDBnSkNoMGFHbHpLUzVtYVc1a0tGd2lhVzV3ZFhSYmJtRnRaVDBuWENJcmJHRnpkRjloWTNScGRtVmZhVzV3ZFhSZmRHVjRkQ3RjSWlkZFhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlna1lXTjBhWFpsWDJsdWNIVjBMbXhsYm1kMGFEMDlNU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKR2x1Y0hWMElEMGdKR0ZqZEdsMlpWOXBibkIxZER0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWdrYVc1d2RYUXViR1Z1WjNSb1BUMHhLU0I3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWthVzV3ZFhRdVptOWpkWE1vS1M1MllXd29KR2x1Y0hWMExuWmhiQ2dwS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVtYjJOMWMwTmhiWEJ2S0NScGJuQjFkRnN3WFNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSMGFHbHpMbVpwYm1Rb1hDSnBibkIxZEZ0dVlXMWxQU2RmYzJaZmMyVmhjbU5vSjExY0lpa3VkSEpwWjJkbGNpZ25abTlqZFhNbktUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWRISnBaMmRsY2tWMlpXNTBLRndpYzJZNllXcGhlR1pwYm1semFGd2lMQ0FnWkdGMFlTQXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hISmNiaUFnSUNBZ0lDQWdmVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1bWIyTjFjME5oYlhCdklEMGdablZ1WTNScGIyNG9hVzV3ZFhSR2FXVnNaQ2w3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQzh2ZG1GeUlHbHVjSFYwUm1sbGJHUWdQU0JrYjJOMWJXVnVkQzVuWlhSRmJHVnRaVzUwUW5sSlpDaHBaQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtSUNocGJuQjFkRVpwWld4a0lDRTlJRzUxYkd3Z0ppWWdhVzV3ZFhSR2FXVnNaQzUyWVd4MVpTNXNaVzVuZEdnZ0lUMGdNQ2w3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppQW9hVzV3ZFhSR2FXVnNaQzVqY21WaGRHVlVaWGgwVW1GdVoyVXBlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJHYVdWc1pGSmhibWRsSUQwZ2FXNXdkWFJHYVdWc1pDNWpjbVZoZEdWVVpYaDBVbUZ1WjJVb0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCR2FXVnNaRkpoYm1kbExtMXZkbVZUZEdGeWRDZ25ZMmhoY21GamRHVnlKeXhwYm5CMWRFWnBaV3hrTG5aaGJIVmxMbXhsYm1kMGFDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1JtbGxiR1JTWVc1blpTNWpiMnhzWVhCelpTZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lFWnBaV3hrVW1GdVoyVXVjMlZzWldOMEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5Wld4elpTQnBaaUFvYVc1d2RYUkdhV1ZzWkM1elpXeGxZM1JwYjI1VGRHRnlkQ0I4ZkNCcGJuQjFkRVpwWld4a0xuTmxiR1ZqZEdsdmJsTjBZWEowSUQwOUlDY3dKeWtnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCbGJHVnRUR1Z1SUQwZ2FXNXdkWFJHYVdWc1pDNTJZV3gxWlM1c1pXNW5kR2c3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhVzV3ZFhSR2FXVnNaQzV6Wld4bFkzUnBiMjVUZEdGeWRDQTlJR1ZzWlcxTVpXNDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXNXdkWFJHYVdWc1pDNXpaV3hsWTNScGIyNUZibVFnUFNCbGJHVnRUR1Z1TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhVzV3ZFhSR2FXVnNaQzVpYkhWeUtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwYm5CMWRFWnBaV3hrTG1adlkzVnpLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDBnWld4elpYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNnZ2FXNXdkWFJHYVdWc1pDQXBJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBibkIxZEVacFpXeGtMbVp2WTNWektDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTUwY21sbloyVnlSWFpsYm5RZ1BTQm1kVzVqZEdsdmJpaGxkbVZ1ZEc1aGJXVXNJR1JoZEdFcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnSkdWMlpXNTBYMk52Ym5SaGFXNWxjaUE5SUNRb1hDSXVjMlZoY21Ob1lXNWtabWxzZEdWeVcyUmhkR0V0YzJZdFptOXliUzFwWkQwblhDSXJjMlZzWmk1elptbGtLMXdpSjExY0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDUmxkbVZ1ZEY5amIyNTBZV2x1WlhJdWRISnBaMmRsY2lobGRtVnVkRzVoYldVc0lGc2daR0YwWVNCZEtUdGNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJSFJvYVhNdVptVjBZMmhCYW1GNFJtOXliU0E5SUdaMWJtTjBhVzl1S0NsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDOHZkSEpwWjJkbGNpQnpkR0Z5ZENCbGRtVnVkRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnWlhabGJuUmZaR0YwWVNBOUlIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5tYVdRNklITmxiR1l1YzJacFpDeGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFJoY21kbGRGTmxiR1ZqZEc5eU9pQnpaV3htTG1GcVlYaGZkR0Z5WjJWMFgyRjBkSElzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMGVYQmxPaUJjSW1admNtMWNJaXhjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUc5aWFtVmpkRG9nYzJWc1pseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOU8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1MGNtbG5aMlZ5UlhabGJuUW9YQ0p6WmpwaGFtRjRabTl5YlhOMFlYSjBYQ0lzSUZzZ1pYWmxiblJmWkdGMFlTQmRLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNSMGFHbHpMbUZrWkVOc1lYTnpLRndpYzJWaGNtTm9MV1pwYkhSbGNpMWthWE5oWW14bFpGd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjSEp2WTJWemMxOW1iM0p0TG1ScGMyRmliR1ZKYm5CMWRITW9jMlZzWmlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnY1hWbGNubGZjR0Z5WVcxeklEMGdjMlZzWmk1blpYUlZjbXhRWVhKaGJYTW9LVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdsbUtITmxiR1l1YkdGdVoxOWpiMlJsSVQxY0lsd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNOdklHRmtaQ0JwZEZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2NYVmxjbmxmY0dGeVlXMXpJRDBnYzJWc1ppNXFiMmx1VlhKc1VHRnlZVzBvY1hWbGNubGZjR0Z5WVcxekxDQmNJbXhoYm1jOVhDSXJjMlZzWmk1c1lXNW5YMk52WkdVcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnWVdwaGVGOXdjbTlqWlhOemFXNW5YM1Z5YkNBOUlITmxiR1l1WVdSa1ZYSnNVR0Z5WVcwb2MyVnNaaTVoYW1GNFgyWnZjbTFmZFhKc0xDQnhkV1Z5ZVY5d1lYSmhiWE1wTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ1pHRjBZVjkwZVhCbElEMGdYQ0pxYzI5dVhDSTdYSEpjYmx4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OWhZbTl5ZENCaGJua2djSEpsZG1sdmRYTWdZV3BoZUNCeVpYRjFaWE4wYzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2S21sbUtITmxiR1l1YkdGemRGOWhhbUY0WDNKbGNYVmxjM1FwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxteGhjM1JmWVdwaGVGOXlaWEYxWlhOMExtRmliM0owS0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNCOUtpOWNjbHh1WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBdkwzTmxiR1l1YkdGemRGOWhhbUY0WDNKbGNYVmxjM1FnUFZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0pDNW5aWFFvWVdwaGVGOXdjbTlqWlhOemFXNW5YM1Z5YkN3Z1puVnVZM1JwYjI0b1pHRjBZU3dnYzNSaGRIVnpMQ0J5WlhGMVpYTjBLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNObGJHWXViR0Z6ZEY5aGFtRjRYM0psY1hWbGMzUWdQU0J1ZFd4c08xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmRYQmtZWFJsY3lCMGFHVWdjbVZ6ZFhSc2N5QW1JR1p2Y20wZ2FIUnRiRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNTFjR1JoZEdWR2IzSnRLR1JoZEdFc0lHUmhkR0ZmZEhsd1pTazdYSEpjYmx4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZTd2daR0YwWVY5MGVYQmxLUzVtWVdsc0tHWjFibU4wYVc5dUtHcHhXRWhTTENCMFpYaDBVM1JoZEhWekxDQmxjbkp2Y2xSb2NtOTNiaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHUmhkR0VnUFNCN2ZUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdWMyWnBaQ0E5SUhObGJHWXVjMlpwWkR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHUmhkR0V1ZEdGeVoyVjBVMlZzWldOMGIzSWdQU0J6Wld4bUxtRnFZWGhmZEdGeVoyVjBYMkYwZEhJN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJoTG05aWFtVmpkQ0E5SUhObGJHWTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JrWVhSaExtRnFZWGhWVWt3Z1BTQmhhbUY0WDNCeWIyTmxjM05wYm1kZmRYSnNPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWkdGMFlTNXFjVmhJVWlBOUlHcHhXRWhTTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1pHRjBZUzUwWlhoMFUzUmhkSFZ6SUQwZ2RHVjRkRk4wWVhSMWN6dGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JoZEdFdVpYSnliM0pVYUhKdmQyNGdQU0JsY25KdmNsUm9jbTkzYmp0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1ZEhKcFoyZGxja1YyWlc1MEtGd2ljMlk2WVdwaGVHVnljbTl5WENJc0lGc2daR0YwWVNCZEtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDBwTG1Gc2QyRjVjeWhtZFc1amRHbHZiaWdwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQmtZWFJoSUQwZ2UzMDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JrWVhSaExuTm1hV1FnUFNCelpXeG1Mbk5tYVdRN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmtZWFJoTG5SaGNtZGxkRk5sYkdWamRHOXlJRDBnYzJWc1ppNWhhbUY0WDNSaGNtZGxkRjloZEhSeU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaR0YwWVM1dlltcGxZM1FnUFNCelpXeG1PMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUjBhR2x6TG5KbGJXOTJaVU5zWVhOektGd2ljMlZoY21Ob0xXWnBiSFJsY2kxa2FYTmhZbXhsWkZ3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSEJ5YjJObGMzTmZabTl5YlM1bGJtRmliR1ZKYm5CMWRITW9jMlZzWmlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNTBjbWxuWjJWeVJYWmxiblFvWENKelpqcGhhbUY0Wm05eWJXWnBibWx6YUZ3aUxDQmJJR1JoZEdFZ1hTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc0Z0lDQWdJQ0FnSUgwN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZMjl3ZVV4cGMzUkpkR1Z0YzBOdmJuUmxiblJ6SUQwZ1puVnVZM1JwYjI0b0pHeHBjM1JmWm5KdmJTd2dKR3hwYzNSZmRHOHBYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2TDJOdmNIa2diM1psY2lCamFHbHNaQ0JzYVhOMElHbDBaVzF6WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCc2FWOWpiMjUwWlc1MGMxOWhjbkpoZVNBOUlHNWxkeUJCY25KaGVTZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnWm5KdmJWOWhkSFJ5YVdKMWRHVnpJRDBnYm1WM0lFRnljbUY1S0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjJZWElnSkdaeWIyMWZabWxsYkdSeklEMGdKR3hwYzNSZlpuSnZiUzVtYVc1a0tGd2lQaUIxYkNBK0lHeHBYQ0lwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0pHWnliMjFmWm1sbGJHUnpMbVZoWTJnb1puVnVZM1JwYjI0b2FTbDdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2JHbGZZMjl1ZEdWdWRITmZZWEp5WVhrdWNIVnphQ2drS0hSb2FYTXBMbWgwYld3b0tTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR0YwZEhKcFluVjBaWE1nUFNBa0tIUm9hWE1wTG5CeWIzQW9YQ0poZEhSeWFXSjFkR1Z6WENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdabkp2YlY5aGRIUnlhV0oxZEdWekxuQjFjMmdvWVhSMGNtbGlkWFJsY3lrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5MllYSWdabWxsYkdSZmJtRnRaU0E5SUNRb2RHaHBjeWt1WVhSMGNpaGNJbVJoZEdFdGMyWXRabWxsYkdRdGJtRnRaVndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZkbUZ5SUhSdlgyWnBaV3hrSUQwZ0pHeHBjM1JmZEc4dVptbHVaQ2hjSWo0Z2RXd2dQaUJzYVZ0a1lYUmhMWE5tTFdacFpXeGtMVzVoYldVOUoxd2lLMlpwWld4a1gyNWhiV1VyWENJblhWd2lLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM05sYkdZdVkyOXdlVUYwZEhKcFluVjBaWE1vSkNoMGFHbHpLU3dnSkd4cGMzUmZkRzhzSUZ3aVpHRjBZUzF6WmkxY0lpazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5S1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJzYVY5cGRDQTlJREE3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2RHOWZabWxsYkdSeklEMGdKR3hwYzNSZmRHOHVabWx1WkNoY0lqNGdkV3dnUGlCc2FWd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdKSFJ2WDJacFpXeGtjeTVsWVdOb0tHWjFibU4wYVc5dUtHa3BlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkNoMGFHbHpLUzVvZEcxc0tHeHBYMk52Ym5SbGJuUnpYMkZ5Y21GNVcyeHBYMmwwWFNrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUm1jbTl0WDJacFpXeGtJRDBnSkNna1puSnZiVjltYVdWc1pITXVaMlYwS0d4cFgybDBLU2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSMGIxOW1hV1ZzWkNBOUlDUW9kR2hwY3lrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtkRzlmWm1sbGJHUXVjbVZ0YjNabFFYUjBjaWhjSW1SaGRHRXRjMll0ZEdGNGIyNXZiWGt0WVhKamFHbDJaVndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WTI5d2VVRjBkSEpwWW5WMFpYTW9KR1p5YjIxZlptbGxiR1FzSUNSMGIxOW1hV1ZzWkNrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYkdsZmFYUXJLenRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmU2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBdktuWmhjaUFrWm5KdmJWOW1hV1ZzWkhNZ1BTQWtiR2x6ZEY5bWNtOXRMbVpwYm1Rb1hDSWdkV3dnUGlCc2FWd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2RHOWZabWxsYkdSeklEMGdKR3hwYzNSZmRHOHVabWx1WkNoY0lpQStJR3hwWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0pHWnliMjFmWm1sbGJHUnpMbVZoWTJnb1puVnVZM1JwYjI0b2FXNWtaWGdzSUhaaGJDbDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWdrS0hSb2FYTXBMbWhoYzBGMGRISnBZblYwWlNoY0ltUmhkR0V0YzJZdGRHRjRiMjV2YlhrdFlYSmphR2wyWlZ3aUtTbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0I5S1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQjBhR2x6TG1OdmNIbEJkSFJ5YVdKMWRHVnpLQ1JzYVhOMFgyWnliMjBzSUNSc2FYTjBYM1J2S1RzcUwxeHlYRzRnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1MWNHUmhkR1ZHYjNKdFFYUjBjbWxpZFhSbGN5QTlJR1oxYm1OMGFXOXVLQ1JzYVhOMFgyWnliMjBzSUNSc2FYTjBYM1J2S1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR1p5YjIxZllYUjBjbWxpZFhSbGN5QTlJQ1JzYVhOMFgyWnliMjB1Y0hKdmNDaGNJbUYwZEhKcFluVjBaWE5jSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dklHeHZiM0FnZEdoeWIzVm5hQ0E4YzJWc1pXTjBQaUJoZEhSeWFXSjFkR1Z6SUdGdVpDQmhjSEJzZVNCMGFHVnRJRzl1SUR4a2FYWStYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2RHOWZZWFIwY21saWRYUmxjeUE5SUNSc2FYTjBYM1J2TG5CeWIzQW9YQ0poZEhSeWFXSjFkR1Z6WENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBa0xtVmhZMmdvZEc5ZllYUjBjbWxpZFhSbGN5d2dablZ1WTNScGIyNG9LU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2JHbHpkRjkwYnk1eVpXMXZkbVZCZEhSeUtIUm9hWE11Ym1GdFpTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSkM1bFlXTm9LR1p5YjIxZllYUjBjbWxpZFhSbGN5d2dablZ1WTNScGIyNG9LU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2JHbHpkRjkwYnk1aGRIUnlLSFJvYVhNdWJtRnRaU3dnZEdocGN5NTJZV3gxWlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgwcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZMjl3ZVVGMGRISnBZblYwWlhNZ1BTQm1kVzVqZEdsdmJpZ2tabkp2YlN3Z0pIUnZMQ0J3Y21WbWFYZ3BYSEpjYmlBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvY0hKbFptbDRLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhCeVpXWnBlQ0E5SUZ3aVhDSTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCbWNtOXRYMkYwZEhKcFluVjBaWE1nUFNBa1puSnZiUzV3Y205d0tGd2lZWFIwY21saWRYUmxjMXdpS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUIwYjE5aGRIUnlhV0oxZEdWeklEMGdKSFJ2TG5CeWIzQW9YQ0poZEhSeWFXSjFkR1Z6WENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBa0xtVmhZMmdvZEc5ZllYUjBjbWxpZFhSbGN5d2dablZ1WTNScGIyNG9LU0I3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvY0hKbFptbDRJVDFjSWx3aUtTQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLSFJvYVhNdWJtRnRaUzVwYm1SbGVFOW1LSEJ5WldacGVDa2dQVDBnTUNrZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtkRzh1Y21WdGIzWmxRWFIwY2loMGFHbHpMbTVoYldVcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJWY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkx5UjBieTV5WlcxdmRtVkJkSFJ5S0hSb2FYTXVibUZ0WlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSkM1bFlXTm9LR1p5YjIxZllYUjBjbWxpZFhSbGN5d2dablZ1WTNScGIyNG9LU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2RHOHVZWFIwY2loMGFHbHpMbTVoYldVc0lIUm9hWE11ZG1Gc2RXVXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlLVHRjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVZMjl3ZVVadmNtMUJkSFJ5YVdKMWRHVnpJRDBnWm5WdVkzUnBiMjRvSkdaeWIyMHNJQ1IwYnlsY2NseHVJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDUjBieTV5WlcxdmRtVkJkSFJ5S0Z3aVpHRjBZUzFqZFhKeVpXNTBMWFJoZUc5dWIyMTVMV0Z5WTJocGRtVmNJaWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFJvYVhNdVkyOXdlVUYwZEhKcFluVjBaWE1vSkdaeWIyMHNJQ1IwYnlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NTFjR1JoZEdWR2IzSnRJRDBnWm5WdVkzUnBiMjRvWkdGMFlTd2daR0YwWVY5MGVYQmxLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb1pHRjBZVjkwZVhCbFBUMWNJbXB6YjI1Y0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2V5OHZkR2hsYmlCM1pTQmthV1FnWVNCeVpYRjFaWE4wSUhSdklIUm9aU0JoYW1GNElHVnVaSEJ2YVc1MExDQnpieUJsZUhCbFkzUWdZVzRnYjJKcVpXTjBJR0poWTJ0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmloMGVYQmxiMllvWkdGMFlWc25abTl5YlNkZEtTRTlQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXlaVzF2ZG1VZ1lXeHNJR1YyWlc1MGN5Qm1jbTl0SUZNbVJpQm1iM0p0WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKSFJvYVhNdWIyWm1LQ2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmNtVm1jbVZ6YUNCMGFHVWdabTl5YlNBb1lYVjBieUJqYjNWdWRDbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbU52Y0hsTWFYTjBTWFJsYlhORGIyNTBaVzUwY3lna0tHUmhkR0ZiSjJadmNtMG5YU2tzSUNSMGFHbHpLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5eVpTQnBibWwwSUZNbVJpQmpiR0Z6Y3lCdmJpQjBhR1VnWm05eWJWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dkpIUm9hWE11YzJWaGNtTm9RVzVrUm1sc2RHVnlLQ2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmFXWWdZV3BoZUNCcGN5QmxibUZpYkdWa0lHbHVhWFFnZEdobElIQmhaMmx1WVhScGIyNWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkR2hwY3k1cGJtbDBLSFJ5ZFdVcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWh6Wld4bUxtbHpYMkZxWVhnOVBURXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG5ObGRIVndRV3BoZUZCaFoybHVZWFJwYjI0b0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc1Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNibHh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1Ga1pGSmxjM1ZzZEhNZ1BTQm1kVzVqZEdsdmJpaGtZWFJoTENCa1lYUmhYM1I1Y0dVcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWhrWVhSaFgzUjVjR1U5UFZ3aWFuTnZibHdpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3THk5MGFHVnVJSGRsSUdScFpDQmhJSEpsY1hWbGMzUWdkRzhnZEdobElHRnFZWGdnWlc1a2NHOXBiblFzSUhOdklHVjRjR1ZqZENCaGJpQnZZbXBsWTNRZ1ltRmphMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5bmNtRmlJSFJvWlNCeVpYTjFiSFJ6SUdGdVpDQnNiMkZrSUdsdVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM05sYkdZdUpHRnFZWGhmY21WemRXeDBjMTlqYjI1MFlXbHVaWEl1WVhCd1pXNWtLR1JoZEdGYkozSmxjM1ZzZEhNblhTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxteHZZV1JmYlc5eVpWOW9kRzFzSUQwZ1pHRjBZVnNuY21WemRXeDBjeWRkTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJVZ2FXWW9aR0YwWVY5MGVYQmxQVDFjSW1oMGJXeGNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdleTh2ZDJVZ1lYSmxJR1Y0Y0dWamRHbHVaeUIwYUdVZ2FIUnRiQ0J2WmlCMGFHVWdjbVZ6ZFd4MGN5QndZV2RsSUdKaFkyc3NJSE52SUdWNGRISmhZM1FnZEdobElHaDBiV3dnZDJVZ2JtVmxaRnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrWkdGMFlWOXZZbW9nUFNBa0tHUmhkR0VwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2YzJWc1ppNGthVzVtYVc1cGRHVmZjMk55YjJ4c1gyTnZiblJoYVc1bGNpNWhjSEJsYm1Rb0pHUmhkR0ZmYjJKcUxtWnBibVFvYzJWc1ppNWhhbUY0WDNSaGNtZGxkRjloZEhSeUtTNW9kRzFzS0NrcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1c2IyRmtYMjF2Y21WZmFIUnRiQ0E5SUNSa1lYUmhYMjlpYWk1bWFXNWtLSE5sYkdZdVlXcGhlRjkwWVhKblpYUmZZWFIwY2lrdWFIUnRiQ2dwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdhVzVtYVc1cGRHVmZjMk55YjJ4c1gyVnVaQ0E5SUdaaGJITmxPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0pDaGNJanhrYVhZK1hDSXJjMlZzWmk1c2IyRmtYMjF2Y21WZmFIUnRiQ3RjSWp3dlpHbDJQbHdpS1M1bWFXNWtLRndpVzJSaGRHRXRjMlZoY21Ob0xXWnBiSFJsY2kxaFkzUnBiMjQ5SjJsdVptbHVhWFJsTFhOamNtOXNiQzFsYm1RblhWd2lLUzVzWlc1bmRHZytNQ2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVc1bWFXNXBkR1ZmYzJOeWIyeHNYMlZ1WkNBOUlIUnlkV1U3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dmFXWWdkR2hsY21VZ2FYTWdZVzV2ZEdobGNpQnpaV3hsWTNSdmNpQm1iM0lnYVc1bWFXNXBkR1VnYzJOeWIyeHNMQ0JtYVc1a0lIUm9aU0JqYjI1MFpXNTBjeUJ2WmlCMGFHRjBJR2x1YzNSbFlXUmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9jMlZzWmk1cGJtWnBibWwwWlY5elkzSnZiR3hmWTI5dWRHRnBibVZ5SVQxY0lsd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxteHZZV1JmYlc5eVpWOW9kRzFzSUQwZ0pDaGNJanhrYVhZK1hDSXJjMlZzWmk1c2IyRmtYMjF2Y21WZmFIUnRiQ3RjSWp3dlpHbDJQbHdpS1M1bWFXNWtLSE5sYkdZdWFXNW1hVzVwZEdWZmMyTnliMnhzWDJOdmJuUmhhVzVsY2lrdWFIUnRiQ2dwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0hObGJHWXVhVzVtYVc1cGRHVmZjMk55YjJ4c1gzSmxjM1ZzZEY5amJHRnpjeUU5WENKY0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSeVpYTjFiSFJmYVhSbGJYTWdQU0FrS0Z3aVBHUnBkajVjSWl0elpXeG1MbXh2WVdSZmJXOXlaVjlvZEcxc0sxd2lQQzlrYVhZK1hDSXBMbVpwYm1Rb2MyVnNaaTVwYm1acGJtbDBaVjl6WTNKdmJHeGZjbVZ6ZFd4MFgyTnNZWE56S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrY21WemRXeDBYMmwwWlcxelgyTnZiblJoYVc1bGNpQTlJQ1FvSnp4a2FYWXZQaWNzSUh0OUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1J5WlhOMWJIUmZhWFJsYlhOZlkyOXVkR0ZwYm1WeUxtRndjR1Z1WkNna2NtVnpkV3gwWDJsMFpXMXpLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG14dllXUmZiVzl5WlY5b2RHMXNJRDBnSkhKbGMzVnNkRjlwZEdWdGMxOWpiMjUwWVdsdVpYSXVhSFJ0YkNncE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWhwYm1acGJtbDBaVjl6WTNKdmJHeGZaVzVrS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3THk5M1pTQm1iM1Z1WkNCaElHUmhkR0VnWVhSMGNtbGlkWFJsSUhOcFoyNWhiR3hwYm1jZ2RHaGxJR3hoYzNRZ2NHRm5aU0J6YnlCbWFXNXBjMmdnYUdWeVpWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhWE5mYldGNFgzQmhaMlZrSUQwZ2RISjFaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXViR0Z6ZEY5c2IyRmtYMjF2Y21WZmFIUnRiQ0E5SUhObGJHWXViRzloWkY5dGIzSmxYMmgwYld3N1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNXBibVpwYm1sMFpWTmpjbTlzYkVGd2NHVnVaQ2h6Wld4bUxteHZZV1JmYlc5eVpWOW9kRzFzS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ1pXeHpaU0JwWmloelpXeG1MbXhoYzNSZmJHOWhaRjl0YjNKbFgyaDBiV3doUFQxelpXeG1MbXh2WVdSZmJXOXlaVjlvZEcxc0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMMk5vWldOcklIUnZJRzFoYTJVZ2MzVnlaU0IwYUdVZ2JtVjNJR2gwYld3Z1ptVjBZMmhsWkNCcGN5QmthV1ptWlhKbGJuUmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWJHRnpkRjlzYjJGa1gyMXZjbVZmYUhSdGJDQTlJSE5sYkdZdWJHOWhaRjl0YjNKbFgyaDBiV3c3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbWx1Wm1sdWFYUmxVMk55YjJ4c1FYQndaVzVrS0hObGJHWXViRzloWkY5dGIzSmxYMmgwYld3cE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQmxiSE5sWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHN2TDNkbElISmxZMlZwZG1Wa0lIUm9aU0J6WVcxbElHMWxjM05oWjJVZ1lXZGhhVzRnYzI4Z1pHOXVKM1FnWVdSa0xDQmhibVFnZEdWc2JDQlRKa1lnZEdoaGRDQjNaU2R5WlNCaGRDQjBhR1VnWlc1a0xpNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWFYTmZiV0Y0WDNCaFoyVmtJRDBnZEhKMVpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSDFjY2x4dVhISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVhVzVtYVc1cGRHVlRZM0p2Ykd4QmNIQmxibVFnUFNCbWRXNWpkR2x2Ymlna2IySnFaV04wS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9jMlZzWmk1cGJtWnBibWwwWlY5elkzSnZiR3hmY21WemRXeDBYMk5zWVhOeklUMWNJbHdpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MaVJwYm1acGJtbDBaVjl6WTNKdmJHeGZZMjl1ZEdGcGJtVnlMbVpwYm1Rb2MyVnNaaTVwYm1acGJtbDBaVjl6WTNKdmJHeGZjbVZ6ZFd4MFgyTnNZWE56S1M1c1lYTjBLQ2t1WVdaMFpYSW9KRzlpYW1WamRDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ1pXeHpaVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdUpHbHVabWx1YVhSbFgzTmpjbTlzYkY5amIyNTBZV2x1WlhJdVlYQndaVzVrS0NSdlltcGxZM1FwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1MWNHUmhkR1ZTWlhOMWJIUnpJRDBnWm5WdVkzUnBiMjRvWkdGMFlTd2daR0YwWVY5MGVYQmxLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb1pHRjBZVjkwZVhCbFBUMWNJbXB6YjI1Y0lpbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2V5OHZkR2hsYmlCM1pTQmthV1FnWVNCeVpYRjFaWE4wSUhSdklIUm9aU0JoYW1GNElHVnVaSEJ2YVc1MExDQnpieUJsZUhCbFkzUWdZVzRnYjJKcVpXTjBJR0poWTJ0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZaM0poWWlCMGFHVWdjbVZ6ZFd4MGN5QmhibVFnYkc5aFpDQnBibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZEdocGN5NXlaWE4xYkhSelgyaDBiV3dnUFNCa1lYUmhXeWR5WlhOMWJIUnpKMTA3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lnS0NCMGFHbHpMbkpsY0d4aFkyVmZjbVZ6ZFd4MGN5QXBJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpaV3htTGlSaGFtRjRYM0psYzNWc2RITmZZMjl1ZEdGcGJtVnlMbWgwYld3b2RHaHBjeTV5WlhOMWJIUnpYMmgwYld3cE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LSFI1Y0dWdlppaGtZWFJoV3lkbWIzSnRKMTBwSVQwOVhDSjFibVJsWm1sdVpXUmNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNKbGJXOTJaU0JoYkd3Z1pYWmxiblJ6SUdaeWIyMGdVeVpHSUdadmNtMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa2RHaHBjeTV2Wm1Zb0tUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTl5WlcxdmRtVWdjR0ZuYVc1aGRHbHZibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1Y21WdGIzWmxRV3BoZUZCaFoybHVZWFJwYjI0b0tUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTl5WldaeVpYTm9JSFJvWlNCbWIzSnRJQ2hoZFhSdklHTnZkVzUwS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdVkyOXdlVXhwYzNSSmRHVnRjME52Ym5SbGJuUnpLQ1FvWkdGMFlWc25abTl5YlNkZEtTd2dKSFJvYVhNcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM1Z3WkdGMFpTQmhkSFJ5YVdKMWRHVnpJRzl1SUdadmNtMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MbU52Y0hsR2IzSnRRWFIwY21saWRYUmxjeWdrS0dSaGRHRmJKMlp2Y20wblhTa3NJQ1IwYUdsektUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTl5WlNCcGJtbDBJRk1tUmlCamJHRnpjeUJ2YmlCMGFHVWdabTl5YlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1IwYUdsekxuTmxZWEpqYUVGdVpFWnBiSFJsY2loN0oybHpTVzVwZENjNklHWmhiSE5sZlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JsYkhObFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OGtkR2hwY3k1bWFXNWtLRndpYVc1d2RYUmNJaWt1Y21WdGIzWmxRWFIwY2loY0ltUnBjMkZpYkdWa1hDSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJVZ2FXWW9aR0YwWVY5MGVYQmxQVDFjSW1oMGJXeGNJaWtnZXk4dmQyVWdZWEpsSUdWNGNHVmpkR2x1WnlCMGFHVWdhSFJ0YkNCdlppQjBhR1VnY21WemRXeDBjeUJ3WVdkbElHSmhZMnNzSUhOdklHVjRkSEpoWTNRZ2RHaGxJR2gwYld3Z2QyVWdibVZsWkZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa1pHRjBZVjl2WW1vZ1BTQWtLR1JoZEdFcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhSb2FYTXVjbVZ6ZFd4MGMxOW9kRzFzSUQwZ0pHUmhkR0ZmYjJKcUxtWnBibVFvSUhSb2FYTXVZV3BoZUY5MFlYSm5aWFJmWVhSMGNpQXBMbWgwYld3b0tUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppQW9JSFJvYVhNdWNtVndiR0ZqWlY5eVpYTjFiSFJ6SUNrZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVKR0ZxWVhoZmNtVnpkV3gwYzE5amIyNTBZV2x1WlhJdWFIUnRiQ2gwYUdsekxuSmxjM1ZzZEhOZmFIUnRiQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNTFjR1JoZEdWRGIyNTBaVzUwUVhKbFlYTW9JQ1JrWVhSaFgyOWlhaUFwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNoelpXeG1MaVJoYW1GNFgzSmxjM1ZzZEhOZlkyOXVkR0ZwYm1WeUxtWnBibVFvWENJdWMyVmhjbU5vWVc1a1ptbHNkR1Z5WENJcExteGxibWQwYUNBK0lEQXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3THk5MGFHVnVJSFJvWlhKbElHRnlaU0J6WldGeVkyZ2dabTl5YlNoektTQnBibk5wWkdVZ2RHaGxJSEpsYzNWc2RITWdZMjl1ZEdGcGJtVnlMQ0J6YnlCeVpTMXBibWwwSUhSb1pXMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk0a1lXcGhlRjl5WlhOMWJIUnpYMk52Ym5SaGFXNWxjaTVtYVc1a0tGd2lMbk5sWVhKamFHRnVaR1pwYkhSbGNsd2lLUzV6WldGeVkyaEJibVJHYVd4MFpYSW9LVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDJsbUlIUm9aU0JqZFhKeVpXNTBJSE5sWVhKamFDQm1iM0p0SUdseklHNXZkQ0JwYm5OcFpHVWdkR2hsSUhKbGMzVnNkSE1nWTI5dWRHRnBibVZ5TENCMGFHVnVJSEJ5YjJObFpXUWdZWE1nYm05eWJXRnNJR0Z1WkNCMWNHUmhkR1VnZEdobElHWnZjbTFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtITmxiR1l1SkdGcVlYaGZjbVZ6ZFd4MGMxOWpiMjUwWVdsdVpYSXVabWx1WkNoY0lpNXpaV0Z5WTJoaGJtUm1hV3gwWlhKYlpHRjBZUzF6WmkxbWIzSnRMV2xrUFNkY0lpQXJJSE5sYkdZdWMyWnBaQ0FySUZ3aUoxMWNJaWt1YkdWdVozUm9QVDB3S1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrYm1WM1gzTmxZWEpqYUY5bWIzSnRJRDBnSkdSaGRHRmZiMkpxTG1acGJtUW9YQ0l1YzJWaGNtTm9ZVzVrWm1sc2RHVnlXMlJoZEdFdGMyWXRabTl5YlMxcFpEMG5YQ0lnS3lCelpXeG1Mbk5tYVdRZ0t5QmNJaWRkWENJcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaUFvSkc1bGQxOXpaV0Z5WTJoZlptOXliUzVzWlc1bmRHZ2dQVDBnTVNrZ2V5OHZkR2hsYmlCeVpYQnNZV05sSUhSb1pTQnpaV0Z5WTJnZ1ptOXliU0IzYVhSb0lIUm9aU0J1WlhjZ2IyNWxYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwzSmxiVzkyWlNCaGJHd2daWFpsYm5SeklHWnliMjBnVXlaR0lHWnZjbTFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkhSb2FYTXViMlptS0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNKbGJXOTJaU0J3WVdkcGJtRjBhVzl1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVjbVZ0YjNabFFXcGhlRkJoWjJsdVlYUnBiMjRvS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2Y21WbWNtVnphQ0IwYUdVZ1ptOXliU0FvWVhWMGJ5QmpiM1Z1ZENsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVqYjNCNVRHbHpkRWwwWlcxelEyOXVkR1Z1ZEhNb0pHNWxkMTl6WldGeVkyaGZabTl5YlN3Z0pIUm9hWE1wTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTkxY0dSaGRHVWdZWFIwY21saWRYUmxjeUJ2YmlCbWIzSnRYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdVkyOXdlVVp2Y20xQmRIUnlhV0oxZEdWektDUnVaWGRmYzJWaGNtTm9YMlp2Y20wc0lDUjBhR2x6S1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2Y21VZ2FXNXBkQ0JUSmtZZ1kyeGhjM01nYjI0Z2RHaGxJR1p2Y20xY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pIUm9hWE11YzJWaGNtTm9RVzVrUm1sc2RHVnlLSHNuYVhOSmJtbDBKem9nWm1Gc2MyVjlLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJVZ2UxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk4a2RHaHBjeTVtYVc1a0tGd2lhVzV3ZFhSY0lpa3VjbVZ0YjNabFFYUjBjaWhjSW1ScGMyRmliR1ZrWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGMxOXRZWGhmY0dGblpXUWdQU0JtWVd4elpUc2dMeTltYjNJZ2FXNW1hVzVwZEdVZ2MyTnliMnhzWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdVkzVnljbVZ1ZEY5d1lXZGxaQ0E5SURFN0lDOHZabTl5SUdsdVptbHVhWFJsSUhOamNtOXNiRnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG5ObGRFbHVabWx1YVhSbFUyTnliMnhzUTI5dWRHRnBibVZ5S0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NTFjR1JoZEdWRGIyNTBaVzUwUVhKbFlYTWdQU0JtZFc1amRHbHZiaWdnSkdoMGJXeGZaR0YwWVNBcElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dklHRmtaQ0JoWkdScGRHbHZibUZzSUdOdmJuUmxiblFnWVhKbFlYTmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLQ0IwYUdsekxtRnFZWGhmZFhCa1lYUmxYM05sWTNScGIyNXpJQ1ltSUhSb2FYTXVZV3BoZUY5MWNHUmhkR1ZmYzJWamRHbHZibk11YkdWdVozUm9JQ2tnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1ptOXlJQ2hwYm1SbGVDQTlJREE3SUdsdVpHVjRJRHdnZEdocGN5NWhhbUY0WDNWd1pHRjBaVjl6WldOMGFXOXVjeTVzWlc1bmRHZzdJQ3NyYVc1a1pYZ3BJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnYzJWc1pXTjBiM0lnUFNCMGFHbHpMbUZxWVhoZmRYQmtZWFJsWDNObFkzUnBiMjV6VzJsdVpHVjRYVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtLQ0J6Wld4bFkzUnZjaUFwTG1oMGJXd29JQ1JvZEcxc1gyUmhkR0V1Wm1sdVpDZ2djMlZzWldOMGIzSWdLUzVvZEcxc0tDa2dLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtWmhaR1ZEYjI1MFpXNTBRWEpsWVhNZ1BTQm1kVzVqZEdsdmJpZ2daR2x5WldOMGFXOXVJQ2tnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUc5d1lXTnBkSGtnUFNBd0xqVTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lHbG1JQ2dnWkdseVpXTjBhVzl1SUQwOVBTQmNJbWx1WENJZ0tTQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J2Y0dGamFYUjVJRDBnTVR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLQ0IwYUdsekxtRnFZWGhmZFhCa1lYUmxYM05sWTNScGIyNXpJQ1ltSUhSb2FYTXVZV3BoZUY5MWNHUmhkR1ZmYzJWamRHbHZibk11YkdWdVozUm9JQ2tnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1ptOXlJQ2hwYm1SbGVDQTlJREE3SUdsdVpHVjRJRHdnZEdocGN5NWhhbUY0WDNWd1pHRjBaVjl6WldOMGFXOXVjeTVzWlc1bmRHZzdJQ3NyYVc1a1pYZ3BJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnYzJWc1pXTjBiM0lnUFNCMGFHbHpMbUZxWVhoZmRYQmtZWFJsWDNObFkzUnBiMjV6VzJsdVpHVjRYVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtLQ0J6Wld4bFkzUnZjaUFwTG5OMGIzQW9kSEoxWlN4MGNuVmxLUzVoYm1sdFlYUmxLQ0I3SUc5d1lXTnBkSGs2SUc5d1lXTnBkSGw5TENCY0ltWmhjM1JjSWlBcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUZ4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTV5WlcxdmRtVlhiMjlEYjIxdFpYSmpaVU52Ym5SeWIyeHpJRDBnWm5WdVkzUnBiMjRvS1h0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUjNiMjlmYjNKa1pYSmllU0E5SUNRb0p5NTNiMjlqYjIxdFpYSmpaUzF2Y21SbGNtbHVaeUF1YjNKa1pYSmllU2NwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pIZHZiMTl2Y21SbGNtSjVYMlp2Y20wZ1BTQWtLQ2N1ZDI5dlkyOXRiV1Z5WTJVdGIzSmtaWEpwYm1jbktUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ1IzYjI5ZmIzSmtaWEppZVY5bWIzSnRMbTltWmlncE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBa2QyOXZYMjl5WkdWeVlua3ViMlptS0NrN1hISmNiaUFnSUNBZ0lDQWdmVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1aFpHUlJkV1Z5ZVZCaGNtRnRJRDBnWm5WdVkzUnBiMjRvYm1GdFpTd2dkbUZzZFdVc0lIVnliRjkwZVhCbEtYdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0hSNWNHVnZaaWgxY214ZmRIbHdaU2s5UFZ3aWRXNWtaV1pwYm1Wa1hDSXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCMWNteGZkSGx3WlNBOUlGd2lZV3hzWENJN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNWxlSFJ5WVY5eGRXVnllVjl3WVhKaGJYTmJkWEpzWDNSNWNHVmRXMjVoYldWZElEMGdkbUZzZFdVN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUgwN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVhVzVwZEZkdmIwTnZiVzFsY21ObFEyOXVkSEp2YkhNZ1BTQm1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNXlaVzF2ZG1WWGIyOURiMjF0WlhKalpVTnZiblJ5YjJ4ektDazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ0pIZHZiMTl2Y21SbGNtSjVJRDBnSkNnbkxuZHZiMk52YlcxbGNtTmxMVzl5WkdWeWFXNW5JQzV2Y21SbGNtSjVKeWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2QyOXZYMjl5WkdWeVlubGZabTl5YlNBOUlDUW9KeTUzYjI5amIyMXRaWEpqWlMxdmNtUmxjbWx1WnljcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUc5eVpHVnlYM1poYkNBOUlGd2lYQ0k3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0NSM2IyOWZiM0prWlhKaWVTNXNaVzVuZEdnK01DbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdiM0prWlhKZmRtRnNJRDBnSkhkdmIxOXZjbVJsY21KNUxuWmhiQ2dwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJWY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2IzSmtaWEpmZG1Gc0lEMGdjMlZzWmk1blpYUlJkV1Z5ZVZCaGNtRnRSbkp2YlZWU1RDaGNJbTl5WkdWeVlubGNJaXdnZDJsdVpHOTNMbXh2WTJGMGFXOXVMbWh5WldZcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWh2Y21SbGNsOTJZV3c5UFZ3aWJXVnVkVjl2Y21SbGNsd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J2Y21SbGNsOTJZV3dnUFNCY0lsd2lPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0JwWmlnb2IzSmtaWEpmZG1Gc0lUMWNJbHdpS1NZbUtDRWhiM0prWlhKZmRtRnNLU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNWxlSFJ5WVY5eGRXVnllVjl3WVhKaGJYTXVZV3hzTG05eVpHVnlZbmtnUFNCdmNtUmxjbDkyWVd3N1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FrZDI5dlgyOXlaR1Z5WW5sZlptOXliUzV2YmlnbmMzVmliV2wwSnl3Z1puVnVZM1JwYjI0b1pTbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaUzV3Y21WMlpXNTBSR1ZtWVhWc2RDZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5MllYSWdabTl5YlNBOUlHVXVkR0Z5WjJWME8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjbVYwZFhKdUlHWmhiSE5sTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5S1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDUjNiMjlmYjNKa1pYSmllUzV2YmloY0ltTm9ZVzVuWlZ3aUxDQm1kVzVqZEdsdmJpaGxLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JsTG5CeVpYWmxiblJFWldaaGRXeDBLQ2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhaaGJDQTlJQ1FvZEdocGN5a3VkbUZzS0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWgyWVd3OVBWd2liV1Z1ZFY5dmNtUmxjbHdpS1Z4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGJDQTlJRndpWENJN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVsZUhSeVlWOXhkV1Z5ZVY5d1lYSmhiWE11WVd4c0xtOXlaR1Z5WW5rZ1BTQjJZV3c3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKSFJvYVhNdWRISnBaMmRsY2loY0luTjFZbTFwZEZ3aUtWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJtWVd4elpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2RHaHBjeTV6WTNKdmJHeFNaWE4xYkhSeklEMGdablZ1WTNScGIyNG9LVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0tITmxiR1l1YzJOeWIyeHNYMjl1WDJGamRHbHZiajA5YzJWc1ppNWhhbUY0WDJGamRHbHZiaWw4ZkNoelpXeG1Mbk5qY205c2JGOXZibDloWTNScGIyNDlQVndpWVd4c1hDSXBLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxuTmpjbTlzYkZSdlVHOXpLQ2s3SUM4dmMyTnliMnhzSUhSb1pTQjNhVzVrYjNjZ2FXWWdhWFFnYUdGeklHSmxaVzRnYzJWMFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM05sYkdZdVlXcGhlRjloWTNScGIyNGdQU0JjSWx3aU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG5Wd1pHRjBaVlZ5YkVocGMzUnZjbmtnUFNCbWRXNWpkR2x2YmloaGFtRjRYM0psYzNWc2RITmZkWEpzS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSFZ6WlY5b2FYTjBiM0o1WDJGd2FTQTlJREE3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtSUNoM2FXNWtiM2N1YUdsemRHOXllU0FtSmlCM2FXNWtiM2N1YUdsemRHOXllUzV3ZFhOb1UzUmhkR1VwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhWelpWOW9hWE4wYjNKNVgyRndhU0E5SUNSMGFHbHpMbUYwZEhJb1hDSmtZWFJoTFhWelpTMW9hWE4wYjNKNUxXRndhVndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9LSE5sYkdZdWRYQmtZWFJsWDJGcVlYaGZkWEpzUFQweEtTWW1LSFZ6WlY5b2FYTjBiM0o1WDJGd2FUMDlNU2twWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmJtOTNJR05vWldOcklHbG1JSFJvWlNCaWNtOTNjMlZ5SUhOMWNIQnZjblJ6SUdocGMzUnZjbmtnYzNSaGRHVWdjSFZ6YUNBNktWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lnS0hkcGJtUnZkeTVvYVhOMGIzSjVJQ1ltSUhkcGJtUnZkeTVvYVhOMGIzSjVMbkIxYzJoVGRHRjBaU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JvYVhOMGIzSjVMbkIxYzJoVGRHRjBaU2h1ZFd4c0xDQnVkV3hzTENCaGFtRjRYM0psYzNWc2RITmZkWEpzS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbkpsYlc5MlpVRnFZWGhRWVdkcGJtRjBhVzl1SUQwZ1puVnVZM1JwYjI0b0tWeHlYRzRnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvZEhsd1pXOW1LSE5sYkdZdVlXcGhlRjlzYVc1cmMxOXpaV3hsWTNSdmNpa2hQVndpZFc1a1pXWnBibVZrWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrWVdwaGVGOXNhVzVyYzE5dlltcGxZM1FnUFNCcVVYVmxjbmtvYzJWc1ppNWhhbUY0WDJ4cGJtdHpYM05sYkdWamRHOXlLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWdrWVdwaGVGOXNhVzVyYzE5dlltcGxZM1F1YkdWdVozUm9QakFwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkdGcVlYaGZiR2x1YTNOZmIySnFaV04wTG05bVppZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbWRsZEVKaGMyVlZjbXdnUFNCbWRXNWpkR2x2YmlnZ2RYSnNJQ2tnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2TDI1dmR5QnpaV1VnYVdZZ2QyVWdZWEpsSUc5dUlIUm9aU0JWVWt3Z2QyVWdkR2hwYm1zdUxpNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSFZ5YkY5d1lYSjBjeUE5SUhWeWJDNXpjR3hwZENoY0lqOWNJaWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCMWNteGZZbUZ6WlNBOUlGd2lYQ0k3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaDFjbXhmY0dGeWRITXViR1Z1WjNSb1BqQXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFZ5YkY5aVlYTmxJRDBnZFhKc1gzQmhjblJ6V3pCZE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdWc2MyVWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZFhKc1gySmhjMlVnUFNCMWNtdzdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2NtVjBkWEp1SUhWeWJGOWlZWE5sTzF4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtTmhia1psZEdOb1FXcGhlRkpsYzNWc2RITWdQU0JtZFc1amRHbHZiaWhtWlhSamFGOTBlWEJsS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWW9kSGx3Wlc5bUtHWmxkR05vWDNSNWNHVXBQVDFjSW5WdVpHVm1hVzVsWkZ3aUtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnWm1WMFkyaGZkSGx3WlNBOUlGd2lYQ0k3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhaaGNpQm1aWFJqYUY5aGFtRjRYM0psYzNWc2RITWdQU0JtWVd4elpUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0hObGJHWXVhWE5mWVdwaGVEMDlNU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdleTh2ZEdobGJpQjNaU0IzYVd4c0lHRnFZWGdnYzNWaWJXbDBJSFJvWlNCbWIzSnRYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OWhibVFnYVdZZ2QyVWdZMkZ1SUdacGJtUWdkR2hsSUhKbGMzVnNkSE1nWTI5dWRHRnBibVZ5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTGlSaGFtRjRYM0psYzNWc2RITmZZMjl1ZEdGcGJtVnlMbXhsYm1kMGFEMDlNU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JtWlhSamFGOWhhbUY0WDNKbGMzVnNkSE1nUFNCMGNuVmxPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCeVpYTjFiSFJ6WDNWeWJDQTlJSE5sYkdZdWNtVnpkV3gwYzE5MWNtdzdJQ0F2TDF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSEpsYzNWc2RITmZkWEpzWDJWdVkyOWtaV1FnUFNBbkp6c2dJQzh2WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdZM1Z5Y21WdWRGOTFjbXdnUFNCM2FXNWtiM2N1Ykc5allYUnBiMjR1YUhKbFpqdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwybG5ibTl5WlNBaklHRnVaQ0JsZG1WeWVYUm9hVzVuSUdGbWRHVnlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2FHRnphRjl3YjNNZ1BTQjNhVzVrYjNjdWJHOWpZWFJwYjI0dWFISmxaaTVwYm1SbGVFOW1LQ2NqSnlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWhvWVhOb1gzQnZjeUU5UFMweEtYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCamRYSnlaVzUwWDNWeWJDQTlJSGRwYm1SdmR5NXNiMk5oZEdsdmJpNW9jbVZtTG5OMVluTjBjaWd3TENCM2FXNWtiM2N1Ykc5allYUnBiMjR1YUhKbFppNXBibVJsZUU5bUtDY2pKeWtwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtDQW9JQ2dnYzJWc1ppNWthWE53YkdGNVgzSmxjM1ZzZEY5dFpYUm9iMlE5UFZ3aVkzVnpkRzl0WDNkdmIyTnZiVzFsY21ObFgzTjBiM0psWENJZ0tTQjhmQ0FvSUhObGJHWXVaR2x6Y0d4aGVWOXlaWE4xYkhSZmJXVjBhRzlrUFQxY0luQnZjM1JmZEhsd1pWOWhjbU5vYVhabFhDSWdLU0FwSUNZbUlDZ2djMlZzWmk1bGJtRmliR1ZmZEdGNGIyNXZiWGxmWVhKamFHbDJaWE1nUFQwZ01TQXBJQ2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlnZ2MyVnNaaTVqZFhKeVpXNTBYM1JoZUc5dWIyMTVYMkZ5WTJocGRtVWdJVDA5WENKY0lpQXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQm1aWFJqYUY5aGFtRjRYM0psYzNWc2RITWdQU0IwY25WbE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnlaWFIxY200Z1ptVjBZMmhmWVdwaGVGOXlaWE4xYkhSek8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5cDJZWElnY21WemRXeDBjMTkxY213Z1BTQndjbTlqWlhOelgyWnZjbTB1WjJWMFVtVnpkV3gwYzFWeWJDaHpaV3htTENCelpXeG1MbkpsYzNWc2RITmZkWEpzS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHRmpkR2wyWlY5MFlYZ2dQU0J3Y205alpYTnpYMlp2Y20wdVoyVjBRV04wYVhabFZHRjRLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCeGRXVnllVjl3WVhKaGJYTWdQU0J6Wld4bUxtZGxkRlZ5YkZCaGNtRnRjeWgwY25WbExDQW5KeXdnWVdOMGFYWmxYM1JoZUNrN0tpOWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNibHh5WEc1Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDI1dmR5QnpaV1VnYVdZZ2QyVWdZWEpsSUc5dUlIUm9aU0JWVWt3Z2QyVWdkR2hwYm1zdUxpNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCMWNteGZZbUZ6WlNBOUlIUm9hWE11WjJWMFFtRnpaVlZ5YkNnZ1kzVnljbVZ1ZEY5MWNtd2dLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmRtRnlJSEpsYzNWc2RITmZkWEpzWDJKaGMyVWdQU0IwYUdsekxtZGxkRUpoYzJWVmNtd29JR04xY25KbGJuUmZkWEpzSUNrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHeGhibWNnUFNCelpXeG1MbWRsZEZGMVpYSjVVR0Z5WVcxR2NtOXRWVkpNS0Z3aWJHRnVaMXdpTENCM2FXNWtiM2N1Ykc5allYUnBiMjR1YUhKbFppazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlnb2RIbHdaVzltS0d4aGJtY3BJVDA5WENKMWJtUmxabWx1WldSY0lpa21KaWhzWVc1bklUMDliblZzYkNrcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RYSnNYMkpoYzJVZ1BTQnpaV3htTG1Ga1pGVnliRkJoY21GdEtIVnliRjlpWVhObExDQmNJbXhoYm1jOVhDSXJiR0Z1WnlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSE5tYVdRZ1BTQnpaV3htTG1kbGRGRjFaWEo1VUdGeVlXMUdjbTl0VlZKTUtGd2ljMlpwWkZ3aUxDQjNhVzVrYjNjdWJHOWpZWFJwYjI0dWFISmxaaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTlwWmlCelptbGtJR2x6SUdFZ2JuVnRZbVZ5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaE9kVzFpWlhJb2NHRnljMlZHYkc5aGRDaHpabWxrS1NrZ1BUMGdjMlpwWkNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMWNteGZZbUZ6WlNBOUlITmxiR1l1WVdSa1ZYSnNVR0Z5WVcwb2RYSnNYMkpoYzJVc0lGd2ljMlpwWkQxY0lpdHpabWxrS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwybG1JR0Z1ZVNCdlppQjBhR1VnTXlCamIyNWthWFJwYjI1eklHRnlaU0IwY25WbExDQjBhR1Z1SUdsMGN5Qm5iMjlrSUhSdklHZHZYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2THlBdElERWdmQ0JwWmlCMGFHVWdkWEpzSUdKaGMyVWdQVDBnY21WemRXeDBjMTkxY214Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZJQzBnTWlCOElHbG1JSFZ5YkNCaVlYTmxLeUJjSWk5Y0lpQWdQVDBnY21WemRXeDBjMTkxY213Z0xTQnBiaUJqWVhObElHOW1JSFZ6WlhJZ1pYSnliM0lnYVc0Z2RHaGxJSEpsYzNWc2RITWdWVkpNWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkx5QXRJRE1nZkNCcFppQjBhR1VnY21WemRXeDBjeUJWVWt3Z2FHRnpJSFZ5YkNCd1lYSmhiWE1zSUdGdVpDQjBhR1VnWTNWeWNtVnVkQ0IxY213Z2MzUmhjblJ6SUhkcGRHZ2dkR2hsSUhKbGMzVnNkSE1nVlZKTUlGeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmRISnBiU0JoYm5rZ2RISmhhV3hwYm1jZ2MyeGhjMmdnWm05eUlHVmhjMmxsY2lCamIyMXdZWEpwYzI5dU9seHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkWEpzWDJKaGMyVWdQU0IxY214ZlltRnpaUzV5WlhCc1lXTmxLQzljWEM4a0x5d2dKeWNwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2NtVnpkV3gwYzE5MWNtd2dQU0J5WlhOMWJIUnpYM1Z5YkM1eVpYQnNZV05sS0M5Y1hDOGtMeXdnSnljcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjbVZ6ZFd4MGMxOTFjbXhmWlc1amIyUmxaQ0E5SUdWdVkyOWtaVlZTU1NoeVpYTjFiSFJ6WDNWeWJDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnWTNWeWNtVnVkRjkxY214ZlkyOXVkR0ZwYm5OZmNtVnpkV3gwYzE5MWNtd2dQU0F0TVR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LQ2gxY214ZlltRnpaVDA5Y21WemRXeDBjMTkxY213cGZId29kWEpzWDJKaGMyVXVkRzlNYjNkbGNrTmhjMlVvS1QwOWNtVnpkV3gwYzE5MWNteGZaVzVqYjJSbFpDNTBiMHh2ZDJWeVEyRnpaU2dwS1NBZ0tYdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCamRYSnlaVzUwWDNWeWJGOWpiMjUwWVdsdWMxOXlaWE4xYkhSelgzVnliQ0E5SURFN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlJR1ZzYzJVZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUlDZ2djbVZ6ZFd4MGMxOTFjbXd1YVc1a1pYaFBaaWdnSno4bklDa2dJVDA5SUMweElDWW1JR04xY25KbGJuUmZkWEpzTG14aGMzUkpibVJsZUU5bUtISmxjM1ZzZEhOZmRYSnNMQ0F3S1NBOVBUMGdNQ0FwSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1kzVnljbVZ1ZEY5MWNteGZZMjl1ZEdGcGJuTmZjbVZ6ZFd4MGMxOTFjbXdnUFNBeE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTG05dWJIbGZjbVZ6ZFd4MGMxOWhhbUY0UFQweEtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdleTh2YVdZZ1lTQjFjMlZ5SUdoaGN5QmphRzl6Wlc0Z2RHOGdiMjVzZVNCaGJHeHZkeUJoYW1GNElHOXVJSEpsYzNWc2RITWdjR0ZuWlhNZ0tHUmxabUYxYkhRZ1ltVm9ZWFpwYjNWeUtWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWdnWTNWeWNtVnVkRjkxY214ZlkyOXVkR0ZwYm5OZmNtVnpkV3gwYzE5MWNtd2dQaUF0TVNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3THk5MGFHbHpJRzFsWVc1eklIUm9aU0JqZFhKeVpXNTBJRlZTVENCamIyNTBZV2x1Y3lCMGFHVWdjbVZ6ZFd4MGN5QjFjbXdzSUhkb2FXTm9JRzFsWVc1eklIZGxJR05oYmlCa2J5QmhhbUY0WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdabGRHTm9YMkZxWVhoZmNtVnpkV3gwY3lBOUlIUnlkV1U3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHVnNjMlZjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1psZEdOb1gyRnFZWGhmY21WemRXeDBjeUE5SUdaaGJITmxPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdWc2MyVmNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaWhtWlhSamFGOTBlWEJsUFQxY0luQmhaMmx1WVhScGIyNWNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0NCamRYSnlaVzUwWDNWeWJGOWpiMjUwWVdsdWMxOXlaWE4xYkhSelgzVnliQ0ErSUMweEtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdMeTkwYUdseklHMWxZVzV6SUhSb1pTQmpkWEp5Wlc1MElGVlNUQ0JqYjI1MFlXbHVjeUIwYUdVZ2NtVnpkV3gwY3lCMWNtd3NJSGRvYVdOb0lHMWxZVzV6SUhkbElHTmhiaUJrYnlCaGFtRjRYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHVnNjMlZjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdMeTlrYjI0bmRDQmhhbUY0SUhCaFoybHVZWFJwYjI0Z2QyaGxiaUJ1YjNRZ2IyNGdZU0JUSmtZZ2NHRm5aVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ1ptVjBZMmhmWVdwaGVGOXlaWE4xYkhSeklEMGdabUZzYzJVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJtWlhSamFGOWhhbUY0WDNKbGMzVnNkSE03WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCMGFHbHpMbk5sZEhWd1FXcGhlRkJoWjJsdVlYUnBiMjRnUFNCbWRXNWpkR2x2YmlncFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZMMmx1Wm1sdWFYUmxJSE5qY205c2JGeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaDBhR2x6TG5CaFoybHVZWFJwYjI1ZmRIbHdaVDA5UFZ3aWFXNW1hVzVwZEdWZmMyTnliMnhzWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJwYm1acGJtbDBaVjl6WTNKdmJHeGZaVzVrSUQwZ1ptRnNjMlU3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTGlSaGFtRjRYM0psYzNWc2RITmZZMjl1ZEdGcGJtVnlMbVpwYm1Rb1hDSmJaR0YwWVMxelpXRnlZMmd0Wm1sc2RHVnlMV0ZqZEdsdmJqMG5hVzVtYVc1cGRHVXRjMk55YjJ4c0xXVnVaQ2RkWENJcExteGxibWQwYUQ0d0tWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbHVabWx1YVhSbFgzTmpjbTlzYkY5bGJtUWdQU0IwY25WbE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVhWE5mYldGNFgzQmhaMlZrSUQwZ2RISjFaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JwWmlod1lYSnpaVWx1ZENoMGFHbHpMbWx1YzNSaGJtTmxYMjUxYldKbGNpazlQVDB4S1NCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkNoM2FXNWtiM2NwTG05bVppaGNJbk5qY205c2JGd2lMQ0J6Wld4bUxtOXVWMmx1Wkc5M1UyTnliMnhzS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLSE5sYkdZdVkyRnVSbVYwWTJoQmFtRjRVbVZ6ZFd4MGN5aGNJbkJoWjJsdVlYUnBiMjVjSWlrcElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKQ2gzYVc1a2IzY3BMbTl1S0Z3aWMyTnliMnhzWENJc0lITmxiR1l1YjI1WGFXNWtiM2RUWTNKdmJHd3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQmxiSE5sSUdsbUtIUjVjR1Z2WmloelpXeG1MbUZxWVhoZmJHbHVhM05mYzJWc1pXTjBiM0lwUFQxY0luVnVaR1ZtYVc1bFpGd2lLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCeVpYUjFjbTQ3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdaV3h6WlNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtLR1J2WTNWdFpXNTBLUzV2Wm1Zb0oyTnNhV05ySnl3Z2MyVnNaaTVoYW1GNFgyeHBibXR6WDNObGJHVmpkRzl5S1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUW9aRzlqZFcxbGJuUXBMbTltWmloelpXeG1MbUZxWVhoZmJHbHVhM05mYzJWc1pXTjBiM0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pDaHpaV3htTG1GcVlYaGZiR2x1YTNOZmMyVnNaV04wYjNJcExtOW1aaWdwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1FvWkc5amRXMWxiblFwTG05dUtDZGpiR2xqYXljc0lITmxiR1l1WVdwaGVGOXNhVzVyYzE5elpXeGxZM1J2Y2l3Z1puVnVZM1JwYjI0b1pTbDdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0hObGJHWXVZMkZ1Um1WMFkyaEJhbUY0VW1WemRXeDBjeWhjSW5CaFoybHVZWFJwYjI1Y0lpa3BYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQmxMbkJ5WlhabGJuUkVaV1poZFd4MEtDazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdiR2x1YXlBOUlHcFJkV1Z5ZVNoMGFHbHpLUzVoZEhSeUtDZG9jbVZtSnlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WVdwaGVGOWhZM1JwYjI0Z1BTQmNJbkJoWjJsdVlYUnBiMjVjSWp0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCd1lXZGxUblZ0WW1WeUlEMGdjMlZzWmk1blpYUlFZV2RsWkVaeWIyMVZVa3dvYkdsdWF5azdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCelpXeG1MaVJoYW1GNFgzSmxjM1ZzZEhOZlkyOXVkR0ZwYm1WeUxtRjBkSElvWENKa1lYUmhMWEJoWjJWa1hDSXNJSEJoWjJWT2RXMWlaWElwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1bVpYUmphRUZxWVhoU1pYTjFiSFJ6S0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J5WlhSMWNtNGdabUZzYzJVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0I5TzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxtZGxkRkJoWjJWa1JuSnZiVlZTVENBOUlHWjFibU4wYVc5dUtGVlNUQ2w3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjR0ZuWldSV1lXd2dQU0F4TzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0F2TDJacGNuTjBJSFJsYzNRZ2RHOGdjMlZsSUdsbUlIZGxJR2hoZG1VZ1hDSXZjR0ZuWlM4MEwxd2lJR2x1SUhSb1pTQlZVa3hjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhSd1ZtRnNJRDBnYzJWc1ppNW5aWFJSZFdWeWVWQmhjbUZ0Um5KdmJWVlNUQ2hjSW5ObVgzQmhaMlZrWENJc0lGVlNUQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJR2xtS0NoMGVYQmxiMllvZEhCV1lXd3BQVDFjSW5OMGNtbHVaMXdpS1h4OEtIUjVjR1Z2WmloMGNGWmhiQ2s5UFZ3aWJuVnRZbVZ5WENJcEtWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQndZV2RsWkZaaGJDQTlJSFJ3Vm1Gc08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnlaWFIxY200Z2NHRm5aV1JXWVd3N1hISmNiaUFnSUNBZ0lDQWdmVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1blpYUlJkV1Z5ZVZCaGNtRnRSbkp2YlZWU1RDQTlJR1oxYm1OMGFXOXVLRzVoYldVc0lGVlNUQ2w3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjWE4wY21sdVp5QTlJRndpUDF3aUsxVlNUQzV6Y0d4cGRDZ25QeWNwV3pGZE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaDBlWEJsYjJZb2NYTjBjbWx1WnlraFBWd2lkVzVrWldacGJtVmtYQ0lwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQjJZV3dnUFNCa1pXTnZaR1ZWVWtsRGIyMXdiMjVsYm5Rb0tHNWxkeUJTWldkRmVIQW9KMXMvZkNaZEp5QXJJRzVoYldVZ0t5QW5QU2NnS3lBbktGdGVKanRkS3o4cEtDWjhJM3c3ZkNRcEp5a3VaWGhsWXloeGMzUnlhVzVuS1h4OFd5eGNJbHdpWFNsYk1WMHVjbVZ3YkdGalpTZ3ZYRndyTDJjc0lDY2xNakFuS1NsOGZHNTFiR3c3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCeVpYUjFjbTRnZG1Gc08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJjSWx3aU8xeHlYRzRnSUNBZ0lDQWdJSDA3WEhKY2JseHlYRzVjY2x4dVhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1bWIzSnRWWEJrWVhSbFpDQTlJR1oxYm1OMGFXOXVLR1VwZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OWxMbkJ5WlhabGJuUkVaV1poZFd4MEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lHbG1LSE5sYkdZdVlYVjBiMTkxY0dSaGRHVTlQVEVwSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1YzNWaWJXbDBSbTl5YlNncE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUdWc2MyVWdhV1lvS0hObGJHWXVZWFYwYjE5MWNHUmhkR1U5UFRBcEppWW9jMlZzWmk1aGRYUnZYMk52ZFc1MFgzSmxabkpsYzJoZmJXOWtaVDA5TVNrcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1Wm05eWJWVndaR0YwWldSR1pYUmphRUZxWVhnb0tUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjbVYwZFhKdUlHWmhiSE5sTzF4eVhHNGdJQ0FnSUNBZ0lIMDdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11Wm05eWJWVndaR0YwWldSR1pYUmphRUZxWVhnZ1BTQm1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnTHk5c2IyOXdJSFJvY205MVoyZ2dZV3hzSUhSb1pTQm1hV1ZzWkhNZ1lXNWtJR0oxYVd4a0lIUm9aU0JWVWt4Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNW1aWFJqYUVGcVlYaEdiM0p0S0NrN1hISmNibHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnY21WMGRYSnVJR1poYkhObE8xeHlYRzRnSUNBZ0lDQWdJSDA3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQzh2YldGclpTQmhibmtnWTI5eWNtVmpkR2x2Ym5NdmRYQmtZWFJsY3lCMGJ5Qm1hV1ZzWkhNZ1ltVm1iM0psSUhSb1pTQnpkV0p0YVhRZ1kyOXRjR3hsZEdWelhISmNiaUFnSUNBZ0lDQWdkR2hwY3k1elpYUkdhV1ZzWkhNZ1BTQm1kVzVqZEdsdmJpaGxLWHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUM4dmFXWW9jMlZzWmk1cGMxOWhhbUY0UFQwd0tTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXpiMjFsZEdsdFpYTWdkR2hsSUdadmNtMGdhWE1nYzNWaWJXbDBkR1ZrSUhkcGRHaHZkWFFnZEdobElITnNhV1JsY2lCNVpYUWdhR0YyYVc1bklIVndaR0YwWldRc0lHRnVaQ0JoY3lCM1pTQm5aWFFnYjNWeUlIWmhiSFZsY3lCbWNtOXRYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNSb1pTQnpiR2xrWlhJZ1lXNWtJRzV2ZENCcGJuQjFkSE1zSUhkbElHNWxaV1FnZEc4Z1kyaGxZMnNnYVhRZ2FXWWdibVZsWkhNZ2RHOGdZbVVnYzJWMFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMMjl1YkhrZ2IyTmpkWEp6SUdsbUlHRnFZWGdnYVhNZ2IyWm1MQ0JoYm1RZ1lYVjBiM04xWW0xcGRDQnZibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNGtabWxsYkdSekxtVmhZMmdvWm5WdVkzUnBiMjRvS1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrWm1sbGJHUWdQU0FrS0hSb2FYTXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2NtRnVaMlZmWkdsemNHeGhlVjkyWVd4MVpYTWdQU0FrWm1sbGJHUXVabWx1WkNnbkxuTm1MVzFsZEdFdGNtRnVaMlV0YzJ4cFpHVnlKeWt1WVhSMGNpaGNJbVJoZEdFdFpHbHpjR3hoZVMxMllXeDFaWE10WVhOY0lpazdMeTlrWVhSaExXUnBjM0JzWVhrdGRtRnNkV1Z6TFdGelBWd2lkR1Y0ZEZ3aVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LSEpoYm1kbFgyUnBjM0JzWVhsZmRtRnNkV1Z6UFQwOVhDSjBaWGgwYVc1d2RYUmNJaWtnZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvSkdacFpXeGtMbVpwYm1Rb1hDSXViV1YwWVMxemJHbGtaWEpjSWlrdWJHVnVaM1JvUGpBcGUxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBa1ptbGxiR1F1Wm1sdVpDaGNJaTV0WlhSaExYTnNhV1JsY2x3aUtTNWxZV05vS0daMWJtTjBhVzl1SUNocGJtUmxlQ2tnZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnpiR2xrWlhKZmIySnFaV04wSUQwZ0pDaDBhR2x6S1Zzd1hUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQWtjMnhwWkdWeVgyVnNJRDBnSkNoMGFHbHpLUzVqYkc5elpYTjBLRndpTG5ObUxXMWxkR0V0Y21GdVoyVXRjMnhwWkdWeVhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OTJZWElnYldsdVZtRnNJRDBnSkhOc2FXUmxjbDlsYkM1aGRIUnlLRndpWkdGMFlTMXRhVzVjSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNaaGNpQnRZWGhXWVd3Z1BTQWtjMnhwWkdWeVgyVnNMbUYwZEhJb1hDSmtZWFJoTFcxaGVGd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ0YVc1V1lXd2dQU0FrYzJ4cFpHVnlYMlZzTG1acGJtUW9YQ0l1YzJZdGNtRnVaMlV0YldsdVhDSXBMblpoYkNncE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHMWhlRlpoYkNBOUlDUnpiR2xrWlhKZlpXd3VabWx1WkNoY0lpNXpaaTF5WVc1blpTMXRZWGhjSWlrdWRtRnNLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnpiR2xrWlhKZmIySnFaV04wTG01dlZXbFRiR2xrWlhJdWMyVjBLRnR0YVc1V1lXd3NJRzFoZUZaaGJGMHBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmU2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQzh2ZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseHlYRzRnSUNBZ0lDQWdJQzh2YzNWaWJXbDBYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NXpkV0p0YVhSR2IzSnRJRDBnWm5WdVkzUnBiMjRvWlNsN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQXZMMnh2YjNBZ2RHaHliM1ZuYUNCaGJHd2dkR2hsSUdacFpXeGtjeUJoYm1RZ1luVnBiR1FnZEdobElGVlNURnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWh6Wld4bUxtbHpVM1ZpYldsMGRHbHVaeUE5UFNCMGNuVmxLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCeVpYUjFjbTRnWm1Gc2MyVTdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWMyVjBSbWxsYkdSektDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lITmxiR1l1WTJ4bFlYSlVhVzFsY2lncE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1cGMxTjFZbTFwZEhScGJtY2dQU0IwY25WbE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjSEp2WTJWemMxOW1iM0p0TG5ObGRGUmhlRUZ5WTJocGRtVlNaWE4xYkhSelZYSnNLSE5sYkdZc0lITmxiR1l1Y21WemRXeDBjMTkxY213cE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk0a1lXcGhlRjl5WlhOMWJIUnpYMk52Ym5SaGFXNWxjaTVoZEhSeUtGd2laR0YwWVMxd1lXZGxaRndpTENBeEtUc2dMeTlwYm1sMElIQmhaMlZrWEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNCcFppaHpaV3htTG1OaGJrWmxkR05vUVdwaGVGSmxjM1ZzZEhNb0tTbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2V5OHZkR2hsYmlCM1pTQjNhV3hzSUdGcVlYZ2djM1ZpYldsMElIUm9aU0JtYjNKdFhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNWhhbUY0WDJGamRHbHZiaUE5SUZ3aWMzVmliV2wwWENJN0lDOHZjMjhnZDJVZ2EyNXZkeUJwZENCM1lYTnVKM1FnY0dGbmFXNWhkR2x2Ymx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTVtWlhSamFFRnFZWGhTWlhOMWJIUnpLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdaV3h6WlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I3THk5MGFHVnVJSGRsSUhkcGJHd2djMmx0Y0d4NUlISmxaR2x5WldOMElIUnZJSFJvWlNCU1pYTjFiSFJ6SUZWU1RGeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnlaWE4xYkhSelgzVnliQ0E5SUhCeWIyTmxjM05mWm05eWJTNW5aWFJTWlhOMWJIUnpWWEpzS0hObGJHWXNJSE5sYkdZdWNtVnpkV3gwYzE5MWNtd3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlIRjFaWEo1WDNCaGNtRnRjeUE5SUhObGJHWXVaMlYwVlhKc1VHRnlZVzF6S0hSeWRXVXNJQ2NuS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lISmxjM1ZzZEhOZmRYSnNJRDBnYzJWc1ppNWhaR1JWY214UVlYSmhiU2h5WlhOMWJIUnpYM1Z5YkN3Z2NYVmxjbmxmY0dGeVlXMXpLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjNhVzVrYjNjdWJHOWpZWFJwYjI0dWFISmxaaUE5SUhKbGMzVnNkSE5mZFhKc08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnlaWFIxY200Z1ptRnNjMlU3WEhKY2JpQWdJQ0FnSUNBZ2ZUdGNjbHh1SUNBZ0lDQWdJQ0IwYUdsekxuSmxjMlYwUm05eWJTQTlJR1oxYm1OMGFXOXVLSE4xWW0xcGRGOW1iM0p0S1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0x5OTFibk5sZENCaGJHd2dabWxsYkdSelhISmNiaUFnSUNBZ0lDQWdJQ0FnSUhObGJHWXVKR1pwWld4a2N5NWxZV05vS0daMWJtTjBhVzl1S0NsN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUm1hV1ZzWkNBOUlDUW9kR2hwY3lrN1hISmNibHgwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwSkdacFpXeGtMbkpsYlc5MlpVRjBkSElvWENKa1lYUmhMWE5tTFhSaGVHOXViMjE1TFdGeVkyaHBkbVZjSWlrN1hISmNibHgwWEhSY2RGeDBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0F2TDNOMFlXNWtZWEprSUdacFpXeGtJSFI1Y0dWelhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtabWxsYkdRdVptbHVaQ2hjSW5ObGJHVmpkRHB1YjNRb1cyMTFiSFJwY0d4bFBTZHRkV3gwYVhCc1pTZGRLU0ErSUc5d2RHbHZianBtYVhKemRDMWphR2xzWkZ3aUtTNXdjbTl3S0Z3aWMyVnNaV04wWldSY0lpd2dkSEoxWlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtabWxsYkdRdVptbHVaQ2hjSW5ObGJHVmpkRnR0ZFd4MGFYQnNaVDBuYlhWc2RHbHdiR1VuWFNBK0lHOXdkR2x2Ymx3aUtTNXdjbTl3S0Z3aWMyVnNaV04wWldSY0lpd2dabUZzYzJVcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKR1pwWld4a0xtWnBibVFvWENKcGJuQjFkRnQwZVhCbFBTZGphR1ZqYTJKdmVDZGRYQ0lwTG5CeWIzQW9YQ0pqYUdWamEyVmtYQ0lzSUdaaGJITmxLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSbWFXVnNaQzVtYVc1a0tGd2lQaUIxYkNBK0lHeHBPbVpwY25OMExXTm9hV3hrSUdsdWNIVjBXM1I1Y0dVOUozSmhaR2x2SjExY0lpa3VjSEp2Y0NoY0ltTm9aV05yWldSY0lpd2dkSEoxWlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtabWxsYkdRdVptbHVaQ2hjSW1sdWNIVjBXM1I1Y0dVOUozUmxlSFFuWFZ3aUtTNTJZV3dvWENKY0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FrWm1sbGJHUXVabWx1WkNoY0lpNXpaaTF2Y0hScGIyNHRZV04wYVhabFhDSXBMbkpsYlc5MlpVTnNZWE56S0Z3aWMyWXRiM0IwYVc5dUxXRmpkR2wyWlZ3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1JtYVdWc1pDNW1hVzVrS0Z3aVBpQjFiQ0ErSUd4cE9tWnBjbk4wTFdOb2FXeGtJR2x1Y0hWMFczUjVjR1U5SjNKaFpHbHZKMTFjSWlrdWNHRnlaVzUwS0NrdVlXUmtRMnhoYzNNb1hDSnpaaTF2Y0hScGIyNHRZV04wYVhabFhDSXBPeUF2TDNKbElHRmtaQ0JoWTNScGRtVWdZMnhoYzNNZ2RHOGdabWx5YzNRZ1hDSmtaV1poZFd4MFhDSWdiM0IwYVc5dVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5dWRXMWlaWElnY21GdVoyVWdMU0F5SUc1MWJXSmxjaUJwYm5CMWRDQm1hV1ZzWkhOY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUm1hV1ZzWkM1bWFXNWtLRndpYVc1d2RYUmJkSGx3WlQwbmJuVnRZbVZ5SjExY0lpa3VaV0ZqYUNobWRXNWpkR2x2YmlocGJtUmxlQ2w3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQWtkR2hwYzBsdWNIVjBJRDBnSkNoMGFHbHpLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb0pIUm9hWE5KYm5CMWRDNXdZWEpsYm5Rb0tTNXdZWEpsYm5Rb0tTNW9ZWE5EYkdGemN5aGNJbk5tTFcxbGRHRXRjbUZ1WjJWY0lpa3BJSHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LR2x1WkdWNFBUMHdLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtkR2hwYzBsdWNIVjBMblpoYkNna2RHaHBjMGx1Y0hWMExtRjBkSElvWENKdGFXNWNJaWtwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHVnNjMlVnYVdZb2FXNWtaWGc5UFRFcElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSMGFHbHpTVzV3ZFhRdWRtRnNLQ1IwYUdselNXNXdkWFF1WVhSMGNpaGNJbTFoZUZ3aUtTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXRaWFJoSUM4Z2JuVnRZbVZ5Y3lCM2FYUm9JRElnYVc1d2RYUnpJQ2htY205dElDOGdkRzhnWm1sbGJHUnpLU0F0SUhObFkyOXVaQ0JwYm5CMWRDQnRkWE4wSUdKbElISmxjMlYwSUhSdklHMWhlQ0IyWVd4MVpWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSdFpYUmhYM05sYkdWamRGOW1jbTl0WDNSdklEMGdKR1pwWld4a0xtWnBibVFvWENJdWMyWXRiV1YwWVMxeVlXNW5aUzF6Wld4bFkzUXRabkp2YlhSdlhDSXBPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LQ1J0WlhSaFgzTmxiR1ZqZEY5bWNtOXRYM1J2TG14bGJtZDBhRDR3S1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJ6ZEdGeWRGOXRhVzRnUFNBa2JXVjBZVjl6Wld4bFkzUmZabkp2YlY5MGJ5NWhkSFJ5S0Z3aVpHRjBZUzF0YVc1Y0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSE4wWVhKMFgyMWhlQ0E5SUNSdFpYUmhYM05sYkdWamRGOW1jbTl0WDNSdkxtRjBkSElvWENKa1lYUmhMVzFoZUZ3aUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKRzFsZEdGZmMyVnNaV04wWDJaeWIyMWZkRzh1Wm1sdVpDaGNJbk5sYkdWamRGd2lLUzVsWVdOb0tHWjFibU4wYVc5dUtHbHVaR1Y0S1h0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2RHaHBjMGx1Y0hWMElEMGdKQ2gwYUdsektUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdsbUtHbHVaR1Y0UFQwd0tTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKSFJvYVhOSmJuQjFkQzUyWVd3b2MzUmhjblJmYldsdUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JsYkhObElHbG1LR2x1WkdWNFBUMHhLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtkR2hwYzBsdWNIVjBMblpoYkNoemRHRnlkRjl0WVhncE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDBwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQWtiV1YwWVY5eVlXUnBiMTltY205dFgzUnZJRDBnSkdacFpXeGtMbVpwYm1Rb1hDSXVjMll0YldWMFlTMXlZVzVuWlMxeVlXUnBieTFtY205dGRHOWNJaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvSkcxbGRHRmZjbUZrYVc5ZlpuSnZiVjkwYnk1c1pXNW5kR2crTUNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdjM1JoY25SZmJXbHVJRDBnSkcxbGRHRmZjbUZrYVc5ZlpuSnZiVjkwYnk1aGRIUnlLRndpWkdGMFlTMXRhVzVjSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlITjBZWEowWDIxaGVDQTlJQ1J0WlhSaFgzSmhaR2x2WDJaeWIyMWZkRzh1WVhSMGNpaGNJbVJoZEdFdGJXRjRYQ0lwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKSEpoWkdsdlgyZHliM1Z3Y3lBOUlDUnRaWFJoWDNKaFpHbHZYMlp5YjIxZmRHOHVabWx1WkNnbkxuTm1MV2x1Y0hWMExYSmhibWRsTFhKaFpHbHZKeWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNSeVlXUnBiMTluY205MWNITXVaV0ZqYUNobWRXNWpkR2x2YmlocGJtUmxlQ2w3WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUnlZV1JwYjNNZ1BTQWtLSFJvYVhNcExtWnBibVFvWENJdWMyWXRhVzV3ZFhRdGNtRmthVzljSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUnlZV1JwYjNNdWNISnZjQ2hjSW1Ob1pXTnJaV1JjSWl3Z1ptRnNjMlVwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lvYVc1a1pYZzlQVEFwWEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1J5WVdScGIzTXVabWxzZEdWeUtDZGJkbUZzZFdVOVhDSW5LM04wWVhKMFgyMXBiaXNuWENKZEp5a3VjSEp2Y0NoY0ltTm9aV05yWldSY0lpd2dkSEoxWlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdaV3h6WlNCcFppaHBibVJsZUQwOU1TbGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pISmhaR2x2Y3k1bWFXeDBaWElvSjF0MllXeDFaVDFjSWljcmMzUmhjblJmYldGNEt5ZGNJbDBuS1M1d2NtOXdLRndpWTJobFkydGxaRndpTENCMGNuVmxLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOUtUdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5dWRXMWlaWElnYzJ4cFpHVnlJQzBnYm05VmFWTnNhV1JsY2x4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0pHWnBaV3hrTG1acGJtUW9YQ0l1YldWMFlTMXpiR2xrWlhKY0lpa3VaV0ZqYUNobWRXNWpkR2x2YmlocGJtUmxlQ2w3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQnpiR2xrWlhKZmIySnFaV04wSUQwZ0pDaDBhR2x6S1Zzd1hUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdktuWmhjaUJ6Ykdsa1pYSmZiMkpxWldOMElEMGdKR052Ym5SaGFXNWxjaTVtYVc1a0tGd2lMbTFsZEdFdGMyeHBaR1Z5WENJcFd6QmRPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnYzJ4cFpHVnlYM1poYkNBOUlITnNhV1JsY2w5dlltcGxZM1F1Ym05VmFWTnNhV1JsY2k1blpYUW9LVHNxTDF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdKSE5zYVdSbGNsOWxiQ0E5SUNRb2RHaHBjeWt1WTJ4dmMyVnpkQ2hjSWk1elppMXRaWFJoTFhKaGJtZGxMWE5zYVdSbGNsd2lLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnYldsdVZtRnNJRDBnSkhOc2FXUmxjbDlsYkM1aGRIUnlLRndpWkdGMFlTMXRhVzVjSWlrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHMWhlRlpoYkNBOUlDUnpiR2xrWlhKZlpXd3VZWFIwY2loY0ltUmhkR0V0YldGNFhDSXBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lITnNhV1JsY2w5dlltcGxZM1F1Ym05VmFWTnNhV1JsY2k1elpYUW9XMjFwYmxaaGJDd2diV0Y0Vm1Gc1hTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZTazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXVaV1ZrSUhSdklITmxaU0JwWmlCaGJua2dZWEpsSUdOdmJXSnZZbTk0SUdGdVpDQmhZM1FnWVdOamIzSmthVzVuYkhsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUFrWTI5dFltOWliM2dnUFNBa1ptbGxiR1F1Wm1sdVpDaGNJbk5sYkdWamRGdGtZWFJoTFdOdmJXSnZZbTk0UFNjeEoxMWNJaWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppZ2tZMjl0WW05aWIzZ3ViR1Z1WjNSb1BqQXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lnS0hSNWNHVnZaaUFrWTI5dFltOWliM2d1WTJodmMyVnVJQ0U5SUZ3aWRXNWtaV1pwYm1Wa1hDSXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtZMjl0WW05aWIzZ3VkSEpwWjJkbGNpaGNJbU5vYjNObGJqcDFjR1JoZEdWa1hDSXBPeUF2TDJadmNpQmphRzl6Wlc0Z2IyNXNlVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCbGJITmxYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWtZMjl0WW05aWIzZ3VkbUZzS0NjbktUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKR052YldKdlltOTRMblJ5YVdkblpYSW9KMk5vWVc1blpTNXpaV3hsWTNReUp5azdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMHBPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnpaV3htTG1Oc1pXRnlWR2x0WlhJb0tUdGNjbHh1WEhKY2JseHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdhV1lvYzNWaWJXbDBYMlp2Y20wOVBWd2lZV3gzWVhselhDSXBYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSE5sYkdZdWMzVmliV2wwUm05eWJTZ3BPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lHVnNjMlVnYVdZb2MzVmliV2wwWDJadmNtMDlQVndpYm1WMlpYSmNJaWxjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2RHaHBjeTVoZFhSdlgyTnZkVzUwWDNKbFpuSmxjMmhmYlc5a1pUMDlNU2xjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0J6Wld4bUxtWnZjbTFWY0dSaGRHVmtSbVYwWTJoQmFtRjRLQ2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnWld4elpTQnBaaWh6ZFdKdGFYUmZabTl5YlQwOVhDSmhkWFJ2WENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1LSFJvYVhNdVlYVjBiMTkxY0dSaGRHVTlQWFJ5ZFdVcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTV6ZFdKdGFYUkdiM0p0S0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0JsYkhObFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWW9kR2hwY3k1aGRYUnZYMk52ZFc1MFgzSmxabkpsYzJoZmJXOWtaVDA5TVNsY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhObGJHWXVabTl5YlZWd1pHRjBaV1JHWlhSamFFRnFZWGdvS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnZlR0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnZEdocGN5NXBibWwwS0NrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhaaGNpQmxkbVZ1ZEY5a1lYUmhJRDBnZTMwN1hISmNiaUFnSUNBZ0lDQWdaWFpsYm5SZlpHRjBZUzV6Wm1sa0lEMGdjMlZzWmk1elptbGtPMXh5WEc0Z0lDQWdJQ0FnSUdWMlpXNTBYMlJoZEdFdWRHRnlaMlYwVTJWc1pXTjBiM0lnUFNCelpXeG1MbUZxWVhoZmRHRnlaMlYwWDJGMGRISTdYSEpjYmlBZ0lDQWdJQ0FnWlhabGJuUmZaR0YwWVM1dlltcGxZM1FnUFNCMGFHbHpPMXh5WEc0Z0lDQWdJQ0FnSUdsbUtHOXdkSE11YVhOSmJtbDBLVnh5WEc0Z0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYzJWc1ppNTBjbWxuWjJWeVJYWmxiblFvWENKelpqcHBibWwwWENJc0lHVjJaVzUwWDJSaGRHRXBPMXh5WEc0Z0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQjlLVHRjY2x4dWZUdGNjbHh1SWwxOSIsIihmdW5jdGlvbiAoZ2xvYmFsKXtcblxyXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbCk7XHJcblxyXG5tb2R1bGUuZXhwb3J0cyA9IHtcclxuXHJcblx0dGF4b25vbXlfYXJjaGl2ZXM6IDAsXHJcbiAgICB1cmxfcGFyYW1zOiB7fSxcclxuICAgIHRheF9hcmNoaXZlX3Jlc3VsdHNfdXJsOiBcIlwiLFxyXG4gICAgYWN0aXZlX3RheDogXCJcIixcclxuICAgIGZpZWxkczoge30sXHJcblx0aW5pdDogZnVuY3Rpb24odGF4b25vbXlfYXJjaGl2ZXMsIGN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSl7XHJcblxyXG4gICAgICAgIHRoaXMudGF4b25vbXlfYXJjaGl2ZXMgPSAwO1xyXG4gICAgICAgIHRoaXMudXJsX3BhcmFtcyA9IHt9O1xyXG4gICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgIHRoaXMuYWN0aXZlX3RheCA9IFwiXCI7XHJcblxyXG5cdFx0Ly90aGlzLiRmaWVsZHMgPSAkZmllbGRzO1xyXG4gICAgICAgIHRoaXMudGF4b25vbXlfYXJjaGl2ZXMgPSB0YXhvbm9teV9hcmNoaXZlcztcclxuICAgICAgICB0aGlzLmN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZSA9IGN1cnJlbnRfdGF4b25vbXlfYXJjaGl2ZTtcclxuXHJcblx0XHR0aGlzLmNsZWFyVXJsQ29tcG9uZW50cygpO1xyXG5cclxuXHR9LFxyXG4gICAgc2V0VGF4QXJjaGl2ZVJlc3VsdHNVcmw6IGZ1bmN0aW9uKCRmb3JtLCBjdXJyZW50X3Jlc3VsdHNfdXJsLCBnZXRfYWN0aXZlKSB7XHJcblxyXG4gICAgICAgIHZhciBzZWxmID0gdGhpcztcclxuXHRcdHRoaXMuY2xlYXJUYXhBcmNoaXZlUmVzdWx0c1VybCgpO1xyXG4gICAgICAgIC8vdmFyIGN1cnJlbnRfcmVzdWx0c191cmwgPSBcIlwiO1xyXG4gICAgICAgIGlmKHRoaXMudGF4b25vbXlfYXJjaGl2ZXMhPTEpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZih0eXBlb2YoZ2V0X2FjdGl2ZSk9PVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdHZhciBnZXRfYWN0aXZlID0gZmFsc2U7XHJcblx0XHR9XHJcblxyXG4gICAgICAgIC8vY2hlY2sgdG8gc2VlIGlmIHdlIGhhdmUgYW55IHRheG9ub21pZXMgc2VsZWN0ZWRcclxuICAgICAgICAvL2lmIHNvLCBjaGVjayB0aGVpciByZXdyaXRlcyBhbmQgdXNlIHRob3NlIGFzIHRoZSByZXN1bHRzIHVybFxyXG4gICAgICAgIHZhciAkZmllbGQgPSBmYWxzZTtcclxuICAgICAgICB2YXIgZmllbGRfbmFtZSA9IFwiXCI7XHJcbiAgICAgICAgdmFyIGZpZWxkX3ZhbHVlID0gXCJcIjtcclxuXHJcbiAgICAgICAgdmFyICRhY3RpdmVfdGF4b25vbXkgPSAkZm9ybS4kZmllbGRzLnBhcmVudCgpLmZpbmQoXCJbZGF0YS1zZi10YXhvbm9teS1hcmNoaXZlPScxJ11cIik7XHJcbiAgICAgICAgaWYoJGFjdGl2ZV90YXhvbm9teS5sZW5ndGg9PTEpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAkZmllbGQgPSAkYWN0aXZlX3RheG9ub215O1xyXG5cclxuICAgICAgICAgICAgdmFyIGZpZWxkVHlwZSA9ICRmaWVsZC5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cclxuICAgICAgICAgICAgaWYgKChmaWVsZFR5cGUgPT0gXCJ0YWdcIikgfHwgKGZpZWxkVHlwZSA9PSBcImNhdGVnb3J5XCIpIHx8IChmaWVsZFR5cGUgPT0gXCJ0YXhvbm9teVwiKSkge1xyXG4gICAgICAgICAgICAgICAgdmFyIHRheG9ub215X3ZhbHVlID0gc2VsZi5wcm9jZXNzVGF4b25vbXkoJGZpZWxkLCB0cnVlKTtcclxuICAgICAgICAgICAgICAgIGZpZWxkX25hbWUgPSAkZmllbGQuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuICAgICAgICAgICAgICAgIHZhciB0YXhvbm9teV9uYW1lID0gZmllbGRfbmFtZS5yZXBsYWNlKFwiX3NmdF9cIiwgXCJcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgaWYgKHRheG9ub215X3ZhbHVlKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgZmllbGRfdmFsdWUgPSB0YXhvbm9teV92YWx1ZS52YWx1ZTtcclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgaWYoZmllbGRfdmFsdWU9PVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgICRmaWVsZCA9IGZhbHNlO1xyXG4gICAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZigoc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUhPVwiXCIpJiYoc2VsZi5jdXJyZW50X3RheG9ub215X2FyY2hpdmUhPXRheG9ub215X25hbWUpKVxyXG4gICAgICAgIHtcclxuXHJcbiAgICAgICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSBjdXJyZW50X3Jlc3VsdHNfdXJsO1xyXG4gICAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBpZigoKGZpZWxkX3ZhbHVlPT1cIlwiKXx8KCEkZmllbGQpICkpXHJcbiAgICAgICAge1xyXG4gICAgICAgICAgICAkZm9ybS4kZmllbGRzLmVhY2goZnVuY3Rpb24gKCkge1xyXG5cclxuICAgICAgICAgICAgICAgIGlmICghJGZpZWxkKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBmaWVsZFR5cGUgPSAkKHRoaXMpLmF0dHIoXCJkYXRhLXNmLWZpZWxkLXR5cGVcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmICgoZmllbGRUeXBlID09IFwidGFnXCIpIHx8IChmaWVsZFR5cGUgPT0gXCJjYXRlZ29yeVwiKSB8fCAoZmllbGRUeXBlID09IFwidGF4b25vbXlcIikpIHtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHRheG9ub215X3ZhbHVlID0gc2VsZi5wcm9jZXNzVGF4b25vbXkoJCh0aGlzKSwgdHJ1ZSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZpZWxkX25hbWUgPSAkKHRoaXMpLmF0dHIoXCJkYXRhLXNmLWZpZWxkLW5hbWVcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodGF4b25vbXlfdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBmaWVsZF92YWx1ZSA9IHRheG9ub215X3ZhbHVlLnZhbHVlO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChmaWVsZF92YWx1ZSAhPSBcIlwiKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICRmaWVsZCA9ICQodGhpcyk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICB9KTtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGlmKCAoJGZpZWxkKSAmJiAoZmllbGRfdmFsdWUgIT0gXCJcIiApKSB7XHJcbiAgICAgICAgICAgIC8vaWYgd2UgZm91bmQgYSBmaWVsZFxyXG5cdFx0XHR2YXIgcmV3cml0ZV9hdHRyID0gKCRmaWVsZC5hdHRyKFwiZGF0YS1zZi10ZXJtLXJld3JpdGVcIikpO1xyXG5cclxuICAgICAgICAgICAgaWYocmV3cml0ZV9hdHRyIT1cIlwiKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgdmFyIHJld3JpdGUgPSBKU09OLnBhcnNlKHJld3JpdGVfYXR0cik7XHJcbiAgICAgICAgICAgICAgICB2YXIgaW5wdXRfdHlwZSA9ICRmaWVsZC5hdHRyKFwiZGF0YS1zZi1maWVsZC1pbnB1dC10eXBlXCIpO1xyXG4gICAgICAgICAgICAgICAgc2VsZi5hY3RpdmVfdGF4ID0gZmllbGRfbmFtZTtcclxuXHJcbiAgICAgICAgICAgICAgICAvL2ZpbmQgdGhlIGFjdGl2ZSBlbGVtZW50XHJcbiAgICAgICAgICAgICAgICBpZiAoKGlucHV0X3R5cGUgPT0gXCJyYWRpb1wiKSB8fCAoaW5wdXRfdHlwZSA9PSBcImNoZWNrYm94XCIpKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIC8vdmFyICRhY3RpdmUgPSAkZmllbGQuZmluZChcIi5zZi1vcHRpb24tYWN0aXZlXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIC8vZXhwbG9kZSB0aGUgdmFsdWVzIGlmIHRoZXJlIGlzIGEgZGVsaW1cclxuICAgICAgICAgICAgICAgICAgICAvL2ZpZWxkX3ZhbHVlXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIHZhciBpc19zaW5nbGVfdmFsdWUgPSB0cnVlO1xyXG4gICAgICAgICAgICAgICAgICAgIHZhciBmaWVsZF92YWx1ZXMgPSBmaWVsZF92YWx1ZS5zcGxpdChcIixcIikuam9pbihcIitcIikuc3BsaXQoXCIrXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgIGlmIChmaWVsZF92YWx1ZXMubGVuZ3RoID4gMSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICBpc19zaW5nbGVfdmFsdWUgPSBmYWxzZTtcclxuICAgICAgICAgICAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgICAgICAgICAgIGlmIChpc19zaW5nbGVfdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkaW5wdXQgPSAkZmllbGQuZmluZChcImlucHV0W3ZhbHVlPSdcIiArIGZpZWxkX3ZhbHVlICsgXCInXVwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkaW5wdXQucGFyZW50KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aCA9ICRhY3RpdmUuYXR0cihcImRhdGEtc2YtZGVwdGhcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL25vdyBsb29wIHRocm91Z2ggcGFyZW50cyB0byBncmFiIHRoZWlyIG5hbWVzXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciB2YWx1ZXMgPSBuZXcgQXJyYXkoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnB1c2goZmllbGRfdmFsdWUpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgZm9yICh2YXIgaSA9IGRlcHRoOyBpID4gMDsgaS0tKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkYWN0aXZlID0gJGFjdGl2ZS5wYXJlbnQoKS5wYXJlbnQoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5wdXNoKCRhY3RpdmUuZmluZChcImlucHV0XCIpLnZhbCgpKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgfVxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnJldmVyc2UoKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vZ3JhYiB0aGUgcmV3cml0ZSBmb3IgdGhpcyBkZXB0aFxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgYWN0aXZlX3Jld3JpdGUgPSByZXdyaXRlW2RlcHRoXTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHVybCA9IGFjdGl2ZV9yZXdyaXRlO1xyXG5cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vdGhlbiBtYXAgZnJvbSB0aGUgcGFyZW50cyB0byB0aGUgZGVwdGhcclxuICAgICAgICAgICAgICAgICAgICAgICAgJCh2YWx1ZXMpLmVhY2goZnVuY3Rpb24gKGluZGV4LCB2YWx1ZSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHVybCA9IHVybC5yZXBsYWNlKFwiW1wiICsgaW5kZXggKyBcIl1cIiwgdmFsdWUpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmwgPSB1cmw7XHJcbiAgICAgICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgICAgIGVsc2Uge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9pZiB0aGVyZSBhcmUgbXVsdGlwbGUgdmFsdWVzLFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL3RoZW4gd2UgbmVlZCB0byBjaGVjayBmb3IgMyB0aGluZ3M6XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL2lmIHRoZSB2YWx1ZXMgc2VsZWN0ZWQgYXJlIGFsbCBpbiB0aGUgc2FtZSB0cmVlIHRoZW4gd2UgY2FuIGRvIHNvbWUgY2xldmVyIHJld3JpdGUgc3R1ZmZcclxuICAgICAgICAgICAgICAgICAgICAgICAgLy9tZXJnZSBhbGwgdmFsdWVzIGluIHNhbWUgbGV2ZWwsIHRoZW4gY29tYmluZSB0aGUgbGV2ZWxzXHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvL2lmIHRoZXkgYXJlIGZyb20gZGlmZmVyZW50IHRyZWVzIHRoZW4ganVzdCBjb21iaW5lIHRoZW0gb3IganVzdCB1c2UgYGZpZWxkX3ZhbHVlYFxyXG4gICAgICAgICAgICAgICAgICAgICAgICAvKlxyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aHMgPSBuZXcgQXJyYXkoKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgICQoZmllbGRfdmFsdWVzKS5lYWNoKGZ1bmN0aW9uIChpbmRleCwgdmFsKSB7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRpbnB1dCA9ICRmaWVsZC5maW5kKFwiaW5wdXRbdmFsdWU9J1wiICsgZmllbGRfdmFsdWUgKyBcIiddXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkaW5wdXQucGFyZW50KCk7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGRlcHRoID0gJGFjdGl2ZS5hdHRyKFwiZGF0YS1zZi1kZXB0aFwiKTtcclxuICAgICAgICAgICAgICAgICAgICAgICAgIC8vZGVwdGhzLnB1c2goZGVwdGgpO1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgIH0pOyovXHJcblxyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIH1cclxuICAgICAgICAgICAgICAgIGVsc2UgaWYgKChpbnB1dF90eXBlID09IFwic2VsZWN0XCIpIHx8IChpbnB1dF90eXBlID09IFwibXVsdGlzZWxlY3RcIikpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGlzX3NpbmdsZV92YWx1ZSA9IHRydWU7XHJcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZpZWxkX3ZhbHVlcyA9IGZpZWxkX3ZhbHVlLnNwbGl0KFwiLFwiKS5qb2luKFwiK1wiKS5zcGxpdChcIitcIik7XHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKGZpZWxkX3ZhbHVlcy5sZW5ndGggPiAxKSB7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGlzX3NpbmdsZV92YWx1ZSA9IGZhbHNlO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgaWYgKGlzX3NpbmdsZV92YWx1ZSkge1xyXG5cclxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRhY3RpdmUgPSAkZmllbGQuZmluZChcIm9wdGlvblt2YWx1ZT0nXCIgKyBmaWVsZF92YWx1ZSArIFwiJ11cIik7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBkZXB0aCA9ICRhY3RpdmUuYXR0cihcImRhdGEtc2YtZGVwdGhcIik7XHJcblxyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgdmFsdWVzID0gbmV3IEFycmF5KCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5wdXNoKGZpZWxkX3ZhbHVlKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGkgPSBkZXB0aDsgaSA+IDA7IGktLSkge1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGFjdGl2ZSA9ICRhY3RpdmUucHJldkFsbChcIm9wdGlvbltkYXRhLXNmLWRlcHRoPSdcIiArIChpIC0gMSkgKyBcIiddXCIpO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWVzLnB1c2goJGFjdGl2ZS52YWwoKSk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlcy5yZXZlcnNlKCk7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBhY3RpdmVfcmV3cml0ZSA9IHJld3JpdGVbZGVwdGhdO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgdXJsID0gYWN0aXZlX3Jld3JpdGU7XHJcbiAgICAgICAgICAgICAgICAgICAgICAgICQodmFsdWVzKS5lYWNoKGZ1bmN0aW9uIChpbmRleCwgdmFsdWUpIHtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cmwgPSB1cmwucmVwbGFjZShcIltcIiArIGluZGV4ICsgXCJdXCIsIHZhbHVlKTtcclxuXHJcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xyXG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnRheF9hcmNoaXZlX3Jlc3VsdHNfdXJsID0gdXJsO1xyXG4gICAgICAgICAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuXHJcbiAgICAgICAgfVxyXG4gICAgICAgIC8vdGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCA9IGN1cnJlbnRfcmVzdWx0c191cmw7XHJcbiAgICB9LFxyXG4gICAgZ2V0UmVzdWx0c1VybDogZnVuY3Rpb24oJGZvcm0sIGN1cnJlbnRfcmVzdWx0c191cmwpIHtcclxuXHJcbiAgICAgICAgLy90aGlzLnNldFRheEFyY2hpdmVSZXN1bHRzVXJsKCRmb3JtLCBjdXJyZW50X3Jlc3VsdHNfdXJsKTtcclxuXHJcbiAgICAgICAgaWYodGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybD09XCJcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybiBjdXJyZW50X3Jlc3VsdHNfdXJsO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgcmV0dXJuIHRoaXMudGF4X2FyY2hpdmVfcmVzdWx0c191cmw7XHJcbiAgICB9LFxyXG5cdGdldFVybFBhcmFtczogZnVuY3Rpb24oJGZvcm0pe1xyXG5cclxuXHRcdHRoaXMuYnVpbGRVcmxDb21wb25lbnRzKCRmb3JtLCB0cnVlKTtcclxuXHJcbiAgICAgICAgaWYodGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCE9XCJcIilcclxuICAgICAgICB7XHJcblxyXG4gICAgICAgICAgICBpZih0aGlzLmFjdGl2ZV90YXghPVwiXCIpXHJcbiAgICAgICAgICAgIHtcclxuICAgICAgICAgICAgICAgIHZhciBmaWVsZF9uYW1lID0gdGhpcy5hY3RpdmVfdGF4O1xyXG5cclxuICAgICAgICAgICAgICAgIGlmKHR5cGVvZih0aGlzLnVybF9wYXJhbXNbZmllbGRfbmFtZV0pIT1cInVuZGVmaW5lZFwiKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIGRlbGV0ZSB0aGlzLnVybF9wYXJhbXNbZmllbGRfbmFtZV07XHJcbiAgICAgICAgICAgICAgICB9XHJcbiAgICAgICAgICAgIH1cclxuICAgICAgICB9XHJcblxyXG5cdFx0cmV0dXJuIHRoaXMudXJsX3BhcmFtcztcclxuXHR9LFxyXG5cdGNsZWFyVXJsQ29tcG9uZW50czogZnVuY3Rpb24oKXtcclxuXHRcdC8vdGhpcy51cmxfY29tcG9uZW50cyA9IFwiXCI7XHJcblx0XHR0aGlzLnVybF9wYXJhbXMgPSB7fTtcclxuXHR9LFxyXG5cdGNsZWFyVGF4QXJjaGl2ZVJlc3VsdHNVcmw6IGZ1bmN0aW9uKCkge1xyXG5cdFx0dGhpcy50YXhfYXJjaGl2ZV9yZXN1bHRzX3VybCA9ICcnO1xyXG5cdH0sXHJcblx0ZGlzYWJsZUlucHV0czogZnVuY3Rpb24oJGZvcm0pe1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHQkZm9ybS4kZmllbGRzLmVhY2goZnVuY3Rpb24oKXtcclxuXHRcdFx0XHJcblx0XHRcdHZhciAkaW5wdXRzID0gJCh0aGlzKS5maW5kKFwiaW5wdXQsIHNlbGVjdCwgLm1ldGEtc2xpZGVyXCIpO1xyXG5cdFx0XHQkaW5wdXRzLmF0dHIoXCJkaXNhYmxlZFwiLCBcImRpc2FibGVkXCIpO1xyXG5cdFx0XHQkaW5wdXRzLmF0dHIoXCJkaXNhYmxlZFwiLCB0cnVlKTtcclxuXHRcdFx0JGlucHV0cy5wcm9wKFwiZGlzYWJsZWRcIiwgdHJ1ZSk7XHJcblx0XHRcdCRpbnB1dHMudHJpZ2dlcihcImNob3Nlbjp1cGRhdGVkXCIpO1xyXG5cdFx0XHRcclxuXHRcdH0pO1xyXG5cdFx0XHJcblx0XHRcclxuXHR9LFxyXG5cdGVuYWJsZUlucHV0czogZnVuY3Rpb24oJGZvcm0pe1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0JGZvcm0uJGZpZWxkcy5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdHZhciAkaW5wdXRzID0gJCh0aGlzKS5maW5kKFwiaW5wdXQsIHNlbGVjdCwgLm1ldGEtc2xpZGVyXCIpO1xyXG5cdFx0XHQkaW5wdXRzLnByb3AoXCJkaXNhYmxlZFwiLCBmYWxzZSk7XHJcblx0XHRcdCRpbnB1dHMuYXR0cihcImRpc2FibGVkXCIsIGZhbHNlKTtcclxuXHRcdFx0JGlucHV0cy50cmlnZ2VyKFwiY2hvc2VuOnVwZGF0ZWRcIik7XHRcdFx0XHJcblx0XHR9KTtcclxuXHRcdFxyXG5cdFx0XHJcblx0fSxcclxuXHRidWlsZFVybENvbXBvbmVudHM6IGZ1bmN0aW9uKCRmb3JtLCBjbGVhcl9jb21wb25lbnRzKXtcclxuXHRcdFxyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHRpZih0eXBlb2YoY2xlYXJfY29tcG9uZW50cykhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGNsZWFyX2NvbXBvbmVudHM9PXRydWUpXHJcblx0XHRcdHtcclxuXHRcdFx0XHR0aGlzLmNsZWFyVXJsQ29tcG9uZW50cygpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdCRmb3JtLiRmaWVsZHMuZWFjaChmdW5jdGlvbigpe1xyXG5cdFx0XHRcclxuXHRcdFx0dmFyIGZpZWxkTmFtZSA9ICQodGhpcykuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuXHRcdFx0dmFyIGZpZWxkVHlwZSA9ICQodGhpcykuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdFx0XHJcblx0XHRcdGlmKGZpZWxkVHlwZT09XCJzZWFyY2hcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc1NlYXJjaEZpZWxkKCQodGhpcykpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoKGZpZWxkVHlwZT09XCJ0YWdcIil8fChmaWVsZFR5cGU9PVwiY2F0ZWdvcnlcIil8fChmaWVsZFR5cGU9PVwidGF4b25vbXlcIikpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NUYXhvbm9teSgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJzb3J0X29yZGVyXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NTb3J0T3JkZXJGaWVsZCgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJwb3N0c19wZXJfcGFnZVwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0c2VsZi5wcm9jZXNzUmVzdWx0c1BlclBhZ2VGaWVsZCgkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJhdXRob3JcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc0F1dGhvcigkKHRoaXMpKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGZpZWxkVHlwZT09XCJwb3N0X3R5cGVcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHNlbGYucHJvY2Vzc1Bvc3RUeXBlKCQodGhpcykpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoZmllbGRUeXBlPT1cInBvc3RfZGF0ZVwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0c2VsZi5wcm9jZXNzUG9zdERhdGUoJCh0aGlzKSk7XHJcblx0XHRcdH1cclxuXHRcdFx0ZWxzZSBpZihmaWVsZFR5cGU9PVwicG9zdF9tZXRhXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRzZWxmLnByb2Nlc3NQb3N0TWV0YSgkKHRoaXMpKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlXHJcblx0XHRcdHtcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH0pO1xyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzU2VhcmNoRmllbGQ6IGZ1bmN0aW9uKCRjb250YWluZXIpXHJcblx0e1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHR2YXIgJGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiaW5wdXRbbmFtZV49J19zZl9zZWFyY2gnXVwiKTtcclxuXHRcdFxyXG5cdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0e1xyXG5cdFx0XHR2YXIgZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHR2YXIgZmllbGRWYWwgPSAkZmllbGQudmFsKCk7XHJcblx0XHRcdFxyXG5cdFx0XHRpZihmaWVsZFZhbCE9XCJcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZfc2Zfcz1cIitlbmNvZGVVUklDb21wb25lbnQoZmllbGRWYWwpO1xyXG5cdFx0XHRcdHNlbGYudXJsX3BhcmFtc1snX3NmX3MnXSA9IGVuY29kZVVSSUNvbXBvbmVudChmaWVsZFZhbCk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHR9LFxyXG5cdHByb2Nlc3NTb3J0T3JkZXJGaWVsZDogZnVuY3Rpb24oJGNvbnRhaW5lcilcclxuXHR7XHJcblx0XHR0aGlzLnByb2Nlc3NBdXRob3IoJGNvbnRhaW5lcik7XHJcblx0XHRcclxuXHR9LFxyXG5cdHByb2Nlc3NSZXN1bHRzUGVyUGFnZUZpZWxkOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHRoaXMucHJvY2Vzc0F1dGhvcigkY29udGFpbmVyKTtcclxuXHRcdFxyXG5cdH0sXHJcblx0Z2V0QWN0aXZlVGF4OiBmdW5jdGlvbigkZmllbGQpIHtcclxuXHRcdHJldHVybiB0aGlzLmFjdGl2ZV90YXg7XHJcblx0fSxcclxuXHRnZXRTZWxlY3RWYWw6IGZ1bmN0aW9uKCRmaWVsZCl7XHJcblxyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0aWYoJGZpZWxkLnZhbCgpIT0wKVxyXG5cdFx0e1xyXG5cdFx0XHRmaWVsZFZhbCA9ICRmaWVsZC52YWwoKTtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0aWYoZmllbGRWYWw9PW51bGwpXHJcblx0XHR7XHJcblx0XHRcdGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0cmV0dXJuIGZpZWxkVmFsO1xyXG5cdH0sXHJcblx0Z2V0TWV0YVNlbGVjdFZhbDogZnVuY3Rpb24oJGZpZWxkKXtcclxuXHRcdFxyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0ZmllbGRWYWwgPSAkZmllbGQudmFsKCk7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0aWYoZmllbGRWYWw9PW51bGwpXHJcblx0XHR7XHJcblx0XHRcdGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0cmV0dXJuIGZpZWxkVmFsO1xyXG5cdH0sXHJcblx0Z2V0TXVsdGlTZWxlY3RWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHR2YXIgZGVsaW0gPSBcIitcIjtcclxuXHRcdGlmKG9wZXJhdG9yPT1cIm9yXCIpXHJcblx0XHR7XHJcblx0XHRcdGRlbGltID0gXCIsXCI7XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZigkZmllbGQudmFsKCkpPT1cIm9iamVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZigkZmllbGQudmFsKCkhPW51bGwpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRyZXR1cm4gJGZpZWxkLnZhbCgpLmpvaW4oZGVsaW0pO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHR9LFxyXG5cdGdldE1ldGFNdWx0aVNlbGVjdFZhbDogZnVuY3Rpb24oJGZpZWxkLCBvcGVyYXRvcil7XHJcblx0XHRcclxuXHRcdHZhciBkZWxpbSA9IFwiLSstXCI7XHJcblx0XHRpZihvcGVyYXRvcj09XCJvclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRkZWxpbSA9IFwiLSwtXCI7XHJcblx0XHR9XHJcblx0XHRcdFx0XHJcblx0XHRpZih0eXBlb2YoJGZpZWxkLnZhbCgpKT09XCJvYmplY3RcIilcclxuXHRcdHtcclxuXHRcdFx0aWYoJGZpZWxkLnZhbCgpIT1udWxsKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIGZpZWxkdmFsID0gW107XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0JCgkZmllbGQudmFsKCkpLmVhY2goZnVuY3Rpb24oaW5kZXgsdmFsdWUpe1xyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZHZhbC5wdXNoKCh2YWx1ZSkpO1xyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdHJldHVybiBmaWVsZHZhbC5qb2luKGRlbGltKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gXCJcIjtcclxuXHRcdFxyXG5cdH0sXHJcblx0Z2V0Q2hlY2tib3hWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKXtcclxuXHRcdFx0aWYoJCh0aGlzKS5wcm9wKFwiY2hlY2tlZFwiKT09dHJ1ZSlcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHJldHVybiAkKHRoaXMpLnZhbCgpO1xyXG5cdFx0XHR9XHJcblx0XHR9KS5nZXQoKTtcclxuXHRcdFxyXG5cdFx0dmFyIGRlbGltID0gXCIrXCI7XHJcblx0XHRpZihvcGVyYXRvcj09XCJvclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRkZWxpbSA9IFwiLFwiO1xyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWwuam9pbihkZWxpbSk7XHJcblx0fSxcclxuXHRnZXRNZXRhQ2hlY2tib3hWYWw6IGZ1bmN0aW9uKCRmaWVsZCwgb3BlcmF0b3Ipe1xyXG5cdFx0XHJcblx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKXtcclxuXHRcdFx0aWYoJCh0aGlzKS5wcm9wKFwiY2hlY2tlZFwiKT09dHJ1ZSlcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHJldHVybiAoJCh0aGlzKS52YWwoKSk7XHJcblx0XHRcdH1cclxuXHRcdH0pLmdldCgpO1xyXG5cdFx0XHJcblx0XHR2YXIgZGVsaW0gPSBcIi0rLVwiO1xyXG5cdFx0aWYob3BlcmF0b3I9PVwib3JcIilcclxuXHRcdHtcclxuXHRcdFx0ZGVsaW0gPSBcIi0sLVwiO1xyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWwuam9pbihkZWxpbSk7XHJcblx0fSxcclxuXHRnZXRSYWRpb1ZhbDogZnVuY3Rpb24oJGZpZWxkKXtcclxuXHRcdFx0XHRcdFx0XHRcclxuXHRcdHZhciBmaWVsZFZhbCA9ICRmaWVsZC5tYXAoZnVuY3Rpb24oKVxyXG5cdFx0e1xyXG5cdFx0XHRpZigkKHRoaXMpLnByb3AoXCJjaGVja2VkXCIpPT10cnVlKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0cmV0dXJuICQodGhpcykudmFsKCk7XHJcblx0XHRcdH1cclxuXHRcdFx0XHJcblx0XHR9KS5nZXQoKTtcclxuXHRcdFxyXG5cdFx0XHJcblx0XHRpZihmaWVsZFZhbFswXSE9MClcclxuXHRcdHtcclxuXHRcdFx0cmV0dXJuIGZpZWxkVmFsWzBdO1xyXG5cdFx0fVxyXG5cdH0sXHJcblx0Z2V0TWV0YVJhZGlvVmFsOiBmdW5jdGlvbigkZmllbGQpe1xyXG5cdFx0XHRcdFx0XHRcdFxyXG5cdFx0dmFyIGZpZWxkVmFsID0gJGZpZWxkLm1hcChmdW5jdGlvbigpXHJcblx0XHR7XHJcblx0XHRcdGlmKCQodGhpcykucHJvcChcImNoZWNrZWRcIik9PXRydWUpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRyZXR1cm4gJCh0aGlzKS52YWwoKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH0pLmdldCgpO1xyXG5cdFx0XHJcblx0XHRyZXR1cm4gZmllbGRWYWxbMF07XHJcblx0fSxcclxuXHRwcm9jZXNzQXV0aG9yOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHZhciBzZWxmID0gdGhpcztcclxuXHRcdFxyXG5cdFx0XHJcblx0XHR2YXIgZmllbGRUeXBlID0gJGNvbnRhaW5lci5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cdFx0dmFyIGlucHV0VHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtaW5wdXQtdHlwZVwiKTtcclxuXHRcdFxyXG5cdFx0dmFyICRmaWVsZDtcclxuXHRcdHZhciBmaWVsZE5hbWUgPSBcIlwiO1xyXG5cdFx0dmFyIGZpZWxkVmFsID0gXCJcIjtcclxuXHRcdFxyXG5cdFx0aWYoaW5wdXRUeXBlPT1cInNlbGVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJzZWxlY3RcIik7XHJcblx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRTZWxlY3RWYWwoJGZpZWxkKTsgXHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJtdWx0aXNlbGVjdFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJzZWxlY3RcIik7XHJcblx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0dmFyIG9wZXJhdG9yID0gJGZpZWxkLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcclxuXHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldE11bHRpU2VsZWN0VmFsKCRmaWVsZCwgXCJvclwiKTtcclxuXHRcdFx0XHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJjaGVja2JveFwiKVxyXG5cdFx0e1xyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OmNoZWNrYm94XCIpO1xyXG5cdFx0XHRcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdHZhciBvcGVyYXRvciA9ICRjb250YWluZXIuZmluZChcIj4gdWxcIikuYXR0cihcImRhdGEtb3BlcmF0b3JcIik7XHJcblx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldENoZWNrYm94VmFsKCRmaWVsZCwgXCJvclwiKTtcclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cInJhZGlvXCIpXHJcblx0XHR7XHJcblx0XHRcdFxyXG5cdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OnJhZGlvXCIpO1xyXG5cdFx0XHRcdFx0XHRcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRSYWRpb1ZhbCgkZmllbGQpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZihmaWVsZFZhbCkhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGZpZWxkVmFsIT1cIlwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0dmFyIGZpZWxkU2x1ZyA9IFwiXCI7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoZmllbGROYW1lPT1cIl9zZl9hdXRob3JcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZFNsdWcgPSBcImF1dGhvcnNcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZSBpZihmaWVsZE5hbWU9PVwiX3NmX3NvcnRfb3JkZXJcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZFNsdWcgPSBcInNvcnRfb3JkZXJcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZSBpZihmaWVsZE5hbWU9PVwiX3NmX3BwcFwiKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkU2x1ZyA9IFwiX3NmX3BwcFwiO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0XHRlbHNlIGlmKGZpZWxkTmFtZT09XCJfc2ZfcG9zdF90eXBlXCIpXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRTbHVnID0gXCJwb3N0X3R5cGVzXCI7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdGVsc2VcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKGZpZWxkU2x1ZyE9XCJcIilcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHQvL3NlbGYudXJsX2NvbXBvbmVudHMgKz0gXCImXCIrZmllbGRTbHVnK1wiPVwiK2ZpZWxkVmFsO1xyXG5cdFx0XHRcdFx0c2VsZi51cmxfcGFyYW1zW2ZpZWxkU2x1Z10gPSBmaWVsZFZhbDtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHRcdFxyXG5cdH0sXHJcblx0cHJvY2Vzc1Bvc3RUeXBlIDogZnVuY3Rpb24oJHRoaXMpe1xyXG5cdFx0XHJcblx0XHR0aGlzLnByb2Nlc3NBdXRob3IoJHRoaXMpO1xyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzUG9zdE1ldGE6IGZ1bmN0aW9uKCRjb250YWluZXIpXHJcblx0e1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFx0XHJcblx0XHR2YXIgZmllbGRUeXBlID0gJGNvbnRhaW5lci5hdHRyKFwiZGF0YS1zZi1maWVsZC10eXBlXCIpO1xyXG5cdFx0dmFyIGlucHV0VHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtaW5wdXQtdHlwZVwiKTtcclxuXHRcdHZhciBtZXRhVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtbWV0YS10eXBlXCIpO1xyXG5cclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHR2YXIgJGZpZWxkO1xyXG5cdFx0dmFyIGZpZWxkTmFtZSA9IFwiXCI7XHJcblx0XHRcclxuXHRcdGlmKG1ldGFUeXBlPT1cIm51bWJlclwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZihpbnB1dFR5cGU9PVwicmFuZ2UtbnVtYmVyXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCIuc2YtbWV0YS1yYW5nZS1udW1iZXIgaW5wdXRcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIHZhbHVlcyA9IFtdO1xyXG5cdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdHZhbHVlcy5wdXNoKCQodGhpcykudmFsKCkpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdH0pO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gdmFsdWVzLmpvaW4oXCIrXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cInJhbmdlLXNsaWRlclwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLW1ldGEtcmFuZ2Utc2xpZGVyIGlucHV0XCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdC8vZ2V0IGFueSBudW1iZXIgZm9ybWF0dGluZyBzdHVmZlxyXG5cdFx0XHRcdHZhciAkbWV0YV9yYW5nZSA9ICRjb250YWluZXIuZmluZChcIi5zZi1tZXRhLXJhbmdlLXNsaWRlclwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHR2YXIgZGVjaW1hbF9wbGFjZXMgPSAkbWV0YV9yYW5nZS5hdHRyKFwiZGF0YS1kZWNpbWFsLXBsYWNlc1wiKTtcclxuXHRcdFx0XHR2YXIgdGhvdXNhbmRfc2VwZXJhdG9yID0gJG1ldGFfcmFuZ2UuYXR0cihcImRhdGEtdGhvdXNhbmQtc2VwZXJhdG9yXCIpO1xyXG5cdFx0XHRcdHZhciBkZWNpbWFsX3NlcGVyYXRvciA9ICRtZXRhX3JhbmdlLmF0dHIoXCJkYXRhLWRlY2ltYWwtc2VwZXJhdG9yXCIpO1xyXG5cclxuXHRcdFx0XHR2YXIgZmllbGRfZm9ybWF0ID0gd051bWIoe1xyXG5cdFx0XHRcdFx0bWFyazogZGVjaW1hbF9zZXBlcmF0b3IsXHJcblx0XHRcdFx0XHRkZWNpbWFsczogcGFyc2VGbG9hdChkZWNpbWFsX3BsYWNlcyksXHJcblx0XHRcdFx0XHR0aG91c2FuZDogdGhvdXNhbmRfc2VwZXJhdG9yXHJcblx0XHRcdFx0fSk7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0dmFyIHZhbHVlcyA9IFtdO1xyXG5cclxuXHJcblx0XHRcdFx0dmFyIHNsaWRlcl9vYmplY3QgPSAkY29udGFpbmVyLmZpbmQoXCIubWV0YS1zbGlkZXJcIilbMF07XHJcblx0XHRcdFx0Ly92YWwgZnJvbSBzbGlkZXIgb2JqZWN0XHJcblx0XHRcdFx0dmFyIHNsaWRlcl92YWwgPSBzbGlkZXJfb2JqZWN0Lm5vVWlTbGlkZXIuZ2V0KCk7XHJcblxyXG5cdFx0XHRcdHZhbHVlcy5wdXNoKGZpZWxkX2Zvcm1hdC5mcm9tKHNsaWRlcl92YWxbMF0pKTtcclxuXHRcdFx0XHR2YWx1ZXMucHVzaChmaWVsZF9mb3JtYXQuZnJvbShzbGlkZXJfdmFsWzFdKSk7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0ZmllbGRWYWwgPSB2YWx1ZXMuam9pbihcIitcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJG1ldGFfcmFuZ2UuYXR0cihcImRhdGEtc2YtZmllbGQtbmFtZVwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1yYWRpb1wiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLWlucHV0LXJhbmdlLXJhZGlvXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg9PTApXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0Ly90aGVuIHRyeSBhZ2Fpbiwgd2UgbXVzdCBiZSB1c2luZyBhIHNpbmdsZSBmaWVsZFxyXG5cdFx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwiPiB1bFwiKTtcclxuXHRcdFx0XHR9XHJcblxyXG5cdFx0XHRcdHZhciAkbWV0YV9yYW5nZSA9ICRjb250YWluZXIuZmluZChcIi5zZi1tZXRhLXJhbmdlXCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdC8vdGhlcmUgaXMgYW4gZWxlbWVudCB3aXRoIGEgZnJvbS90byBjbGFzcyAtIHNvIHdlIG5lZWQgdG8gZ2V0IHRoZSB2YWx1ZXMgb2YgdGhlIGZyb20gJiB0byBpbnB1dCBmaWVsZHMgc2VwZXJhdGVseVxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0XHR7XHRcclxuXHRcdFx0XHRcdHZhciBmaWVsZF92YWxzID0gW107XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHR2YXIgJHJhZGlvcyA9ICQodGhpcykuZmluZChcIi5zZi1pbnB1dC1yYWRpb1wiKTtcclxuXHRcdFx0XHRcdFx0ZmllbGRfdmFscy5wdXNoKHNlbGYuZ2V0TWV0YVJhZGlvVmFsKCRyYWRpb3MpKTtcclxuXHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0XHR9KTtcclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0Ly9wcmV2ZW50IHNlY29uZCBudW1iZXIgZnJvbSBiZWluZyBsb3dlciB0aGFuIHRoZSBmaXJzdFxyXG5cdFx0XHRcdFx0aWYoZmllbGRfdmFscy5sZW5ndGg9PTIpXHJcblx0XHRcdFx0XHR7XHJcblx0XHRcdFx0XHRcdGlmKE51bWJlcihmaWVsZF92YWxzWzFdKTxOdW1iZXIoZmllbGRfdmFsc1swXSkpXHJcblx0XHRcdFx0XHRcdHtcclxuXHRcdFx0XHRcdFx0XHRmaWVsZF92YWxzWzFdID0gZmllbGRfdmFsc1swXTtcclxuXHRcdFx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkX3ZhbHMuam9pbihcIitcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD09MSlcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuZmluZChcIi5zZi1pbnB1dC1yYWRpb1wiKS5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9ICRtZXRhX3JhbmdlLmF0dHIoXCJkYXRhLXNmLWZpZWxkLW5hbWVcIik7XHJcblx0XHRcdFx0fVxyXG5cclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1zZWxlY3RcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcIi5zZi1pbnB1dC1zZWxlY3RcIik7XHJcblx0XHRcdFx0dmFyICRtZXRhX3JhbmdlID0gJGNvbnRhaW5lci5maW5kKFwiLnNmLW1ldGEtcmFuZ2VcIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0Ly90aGVyZSBpcyBhbiBlbGVtZW50IHdpdGggYSBmcm9tL3RvIGNsYXNzIC0gc28gd2UgbmVlZCB0byBnZXQgdGhlIHZhbHVlcyBvZiB0aGUgZnJvbSAmIHRvIGlucHV0IGZpZWxkcyBzZXBlcmF0ZWx5XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdHZhciBmaWVsZF92YWxzID0gW107XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdCRmaWVsZC5lYWNoKGZ1bmN0aW9uKCl7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHR2YXIgJHRoaXMgPSAkKHRoaXMpO1xyXG5cdFx0XHRcdFx0XHRmaWVsZF92YWxzLnB1c2goc2VsZi5nZXRNZXRhU2VsZWN0VmFsKCR0aGlzKSk7XHJcblx0XHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0fSk7XHJcblx0XHRcdFx0XHRcclxuXHRcdFx0XHRcdC8vcHJldmVudCBzZWNvbmQgbnVtYmVyIGZyb20gYmVpbmcgbG93ZXIgdGhhbiB0aGUgZmlyc3RcclxuXHRcdFx0XHRcdGlmKGZpZWxkX3ZhbHMubGVuZ3RoPT0yKVxyXG5cdFx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0XHRpZihOdW1iZXIoZmllbGRfdmFsc1sxXSk8TnVtYmVyKGZpZWxkX3ZhbHNbMF0pKVxyXG5cdFx0XHRcdFx0XHR7XHJcblx0XHRcdFx0XHRcdFx0ZmllbGRfdmFsc1sxXSA9IGZpZWxkX3ZhbHNbMF07XHJcblx0XHRcdFx0XHRcdH1cclxuXHRcdFx0XHRcdH1cclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0XHJcblx0XHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkX3ZhbHMuam9pbihcIitcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdFx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD09MSlcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHRcdGVsc2VcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHRmaWVsZE5hbWUgPSAkbWV0YV9yYW5nZS5hdHRyKFwiZGF0YS1zZi1maWVsZC1uYW1lXCIpO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJyYW5nZS1jaGVja2JveFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwidWwgPiBsaSBpbnB1dDpjaGVja2JveFwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRpZigkZmllbGQubGVuZ3RoPjApXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldENoZWNrYm94VmFsKCRmaWVsZCwgXCJhbmRcIik7XHJcblx0XHRcdFx0fVxyXG5cdFx0XHR9XHJcblx0XHRcdFxyXG5cdFx0XHRpZihmaWVsZE5hbWU9PVwiXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHRcdGVsc2UgaWYobWV0YVR5cGU9PVwiY2hvaWNlXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGlucHV0VHlwZT09XCJzZWxlY3RcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInNlbGVjdFwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0TWV0YVNlbGVjdFZhbCgkZmllbGQpOyBcclxuXHRcdFx0XHRcclxuXHRcdFx0fVxyXG5cdFx0XHRlbHNlIGlmKGlucHV0VHlwZT09XCJtdWx0aXNlbGVjdFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRcdHZhciBvcGVyYXRvciA9ICRmaWVsZC5hdHRyKFwiZGF0YS1vcGVyYXRvclwiKTtcclxuXHRcdFx0XHRcclxuXHRcdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0TWV0YU11bHRpU2VsZWN0VmFsKCRmaWVsZCwgb3BlcmF0b3IpO1xyXG5cdFx0XHR9XHJcblx0XHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cImNoZWNrYm94XCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQkZmllbGQgPSAkY29udGFpbmVyLmZpbmQoXCJ1bCA+IGxpIGlucHV0OmNoZWNrYm94XCIpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0XHR7XHJcblx0XHRcdFx0XHR2YXIgb3BlcmF0b3IgPSAkY29udGFpbmVyLmZpbmQoXCI+IHVsXCIpLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcdFx0ZmllbGRWYWwgPSBzZWxmLmdldE1ldGFDaGVja2JveFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwicmFkaW9cIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6cmFkaW9cIik7XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRNZXRhUmFkaW9WYWwoJGZpZWxkKTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdH1cclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkVmFsKTtcclxuXHRcdFx0aWYodHlwZW9mKCRmaWVsZCkhPT1cInVuZGVmaW5lZFwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHRcdFxyXG5cdFx0XHRcdFx0Ly9mb3IgdGhvc2Ugd2hvIGluc2lzdCBvbiB1c2luZyAmIGFtcGVyc2FuZHMgaW4gdGhlIG5hbWUgb2YgdGhlIGN1c3RvbSBmaWVsZCAoISlcclxuXHRcdFx0XHRcdGZpZWxkTmFtZSA9IChmaWVsZE5hbWUpO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fVxyXG5cdFx0XHRcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYobWV0YVR5cGU9PVwiZGF0ZVwiKVxyXG5cdFx0e1xyXG5cdFx0XHRzZWxmLnByb2Nlc3NQb3N0RGF0ZSgkY29udGFpbmVyKTtcclxuXHRcdH1cclxuXHRcdFxyXG5cdFx0aWYodHlwZW9mKGZpZWxkVmFsKSE9XCJ1bmRlZmluZWRcIilcclxuXHRcdHtcclxuXHRcdFx0aWYoZmllbGRWYWwhPVwiXCIpXHJcblx0XHRcdHtcclxuXHRcdFx0XHQvL3NlbGYudXJsX2NvbXBvbmVudHMgKz0gXCImXCIrZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkTmFtZSkrXCI9XCIrKGZpZWxkVmFsKTtcclxuXHRcdFx0XHRzZWxmLnVybF9wYXJhbXNbZW5jb2RlVVJJQ29tcG9uZW50KGZpZWxkTmFtZSldID0gKGZpZWxkVmFsKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdH0sXHJcblx0cHJvY2Vzc1Bvc3REYXRlOiBmdW5jdGlvbigkY29udGFpbmVyKVxyXG5cdHtcclxuXHRcdHZhciBzZWxmID0gdGhpcztcclxuXHRcdFxyXG5cdFx0dmFyIGZpZWxkVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdHZhciBpbnB1dFR5cGUgPSAkY29udGFpbmVyLmF0dHIoXCJkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGVcIik7XHJcblx0XHRcclxuXHRcdHZhciAkZmllbGQ7XHJcblx0XHR2YXIgZmllbGROYW1lID0gXCJcIjtcclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHRcclxuXHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6dGV4dFwiKTtcclxuXHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFxyXG5cdFx0dmFyIGRhdGVzID0gW107XHJcblx0XHQkZmllbGQuZWFjaChmdW5jdGlvbigpe1xyXG5cdFx0XHRcclxuXHRcdFx0ZGF0ZXMucHVzaCgkKHRoaXMpLnZhbCgpKTtcclxuXHRcdFxyXG5cdFx0fSk7XHJcblx0XHRcclxuXHRcdGlmKCRmaWVsZC5sZW5ndGg9PTIpXHJcblx0XHR7XHJcblx0XHRcdGlmKChkYXRlc1swXSE9XCJcIil8fChkYXRlc1sxXSE9XCJcIikpXHJcblx0XHRcdHtcclxuXHRcdFx0XHRmaWVsZFZhbCA9IGRhdGVzLmpvaW4oXCIrXCIpO1xyXG5cdFx0XHRcdGZpZWxkVmFsID0gZmllbGRWYWwucmVwbGFjZSgvXFwvL2csJycpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRlbHNlIGlmKCRmaWVsZC5sZW5ndGg9PTEpXHJcblx0XHR7XHJcblx0XHRcdGlmKGRhdGVzWzBdIT1cIlwiKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGRWYWwgPSBkYXRlcy5qb2luKFwiK1wiKTtcclxuXHRcdFx0XHRmaWVsZFZhbCA9IGZpZWxkVmFsLnJlcGxhY2UoL1xcLy9nLCcnKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0XHRpZih0eXBlb2YoZmllbGRWYWwpIT1cInVuZGVmaW5lZFwiKVxyXG5cdFx0e1xyXG5cdFx0XHRpZihmaWVsZFZhbCE9XCJcIilcclxuXHRcdFx0e1xyXG5cdFx0XHRcdHZhciBmaWVsZFNsdWcgPSBcIlwiO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGlmKGZpZWxkTmFtZT09XCJfc2ZfcG9zdF9kYXRlXCIpXHJcblx0XHRcdFx0e1xyXG5cdFx0XHRcdFx0ZmllbGRTbHVnID0gXCJwb3N0X2RhdGVcIjtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0ZWxzZVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdGZpZWxkU2x1ZyA9IGZpZWxkTmFtZTtcclxuXHRcdFx0XHR9XHJcblx0XHRcdFx0XHJcblx0XHRcdFx0aWYoZmllbGRTbHVnIT1cIlwiKVxyXG5cdFx0XHRcdHtcclxuXHRcdFx0XHRcdC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZcIitmaWVsZFNsdWcrXCI9XCIrZmllbGRWYWw7XHJcblx0XHRcdFx0XHRzZWxmLnVybF9wYXJhbXNbZmllbGRTbHVnXSA9IGZpZWxkVmFsO1xyXG5cdFx0XHRcdH1cclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0XHJcblx0fSxcclxuXHRwcm9jZXNzVGF4b25vbXk6IGZ1bmN0aW9uKCRjb250YWluZXIsIHJldHVybl9vYmplY3QpXHJcblx0e1xyXG4gICAgICAgIGlmKHR5cGVvZihyZXR1cm5fb2JqZWN0KT09XCJ1bmRlZmluZWRcIilcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybl9vYmplY3QgPSBmYWxzZTtcclxuICAgICAgICB9XHJcblxyXG5cdFx0Ly9pZigpXHRcdFx0XHRcdFxyXG5cdFx0Ly92YXIgZmllbGROYW1lID0gJCh0aGlzKS5hdHRyKFwiZGF0YS1zZi1maWVsZC1uYW1lXCIpO1xyXG5cdFx0dmFyIHNlbGYgPSB0aGlzO1xyXG5cdFxyXG5cdFx0dmFyIGZpZWxkVHlwZSA9ICRjb250YWluZXIuYXR0cihcImRhdGEtc2YtZmllbGQtdHlwZVwiKTtcclxuXHRcdHZhciBpbnB1dFR5cGUgPSAkY29udGFpbmVyLmF0dHIoXCJkYXRhLXNmLWZpZWxkLWlucHV0LXR5cGVcIik7XHJcblx0XHRcclxuXHRcdHZhciAkZmllbGQ7XHJcblx0XHR2YXIgZmllbGROYW1lID0gXCJcIjtcclxuXHRcdHZhciBmaWVsZFZhbCA9IFwiXCI7XHJcblx0XHRcclxuXHRcdGlmKGlucHV0VHlwZT09XCJzZWxlY3RcIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdFxyXG5cdFx0XHRmaWVsZFZhbCA9IHNlbGYuZ2V0U2VsZWN0VmFsKCRmaWVsZCk7IFxyXG5cdFx0fVxyXG5cdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwibXVsdGlzZWxlY3RcIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwic2VsZWN0XCIpO1xyXG5cdFx0XHRmaWVsZE5hbWUgPSAkZmllbGQuYXR0cihcIm5hbWVcIikucmVwbGFjZSgnW10nLCAnJyk7XHJcblx0XHRcdHZhciBvcGVyYXRvciA9ICRmaWVsZC5hdHRyKFwiZGF0YS1vcGVyYXRvclwiKTtcclxuXHRcdFx0XHJcblx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRNdWx0aVNlbGVjdFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdH1cclxuXHRcdGVsc2UgaWYoaW5wdXRUeXBlPT1cImNoZWNrYm94XCIpXHJcblx0XHR7XHJcblx0XHRcdCRmaWVsZCA9ICRjb250YWluZXIuZmluZChcInVsID4gbGkgaW5wdXQ6Y2hlY2tib3hcIik7XHJcblx0XHRcdGlmKCRmaWVsZC5sZW5ndGg+MClcclxuXHRcdFx0e1xyXG5cdFx0XHRcdGZpZWxkTmFtZSA9ICRmaWVsZC5hdHRyKFwibmFtZVwiKS5yZXBsYWNlKCdbXScsICcnKTtcclxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcclxuXHRcdFx0XHR2YXIgb3BlcmF0b3IgPSAkY29udGFpbmVyLmZpbmQoXCI+IHVsXCIpLmF0dHIoXCJkYXRhLW9wZXJhdG9yXCIpO1xyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRDaGVja2JveFZhbCgkZmllbGQsIG9wZXJhdG9yKTtcclxuXHRcdFx0fVxyXG5cdFx0fVxyXG5cdFx0ZWxzZSBpZihpbnB1dFR5cGU9PVwicmFkaW9cIilcclxuXHRcdHtcclxuXHRcdFx0JGZpZWxkID0gJGNvbnRhaW5lci5maW5kKFwidWwgPiBsaSBpbnB1dDpyYWRpb1wiKTtcclxuXHRcdFx0aWYoJGZpZWxkLmxlbmd0aD4wKVxyXG5cdFx0XHR7XHJcblx0XHRcdFx0ZmllbGROYW1lID0gJGZpZWxkLmF0dHIoXCJuYW1lXCIpLnJlcGxhY2UoJ1tdJywgJycpO1xyXG5cdFx0XHRcdFxyXG5cdFx0XHRcdGZpZWxkVmFsID0gc2VsZi5nZXRSYWRpb1ZhbCgkZmllbGQpO1xyXG5cdFx0XHR9XHJcblx0XHR9XHJcblx0XHRcclxuXHRcdGlmKHR5cGVvZihmaWVsZFZhbCkhPVwidW5kZWZpbmVkXCIpXHJcblx0XHR7XHJcblx0XHRcdGlmKGZpZWxkVmFsIT1cIlwiKVxyXG5cdFx0XHR7XHJcbiAgICAgICAgICAgICAgICBpZihyZXR1cm5fb2JqZWN0PT10cnVlKVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB7bmFtZTogZmllbGROYW1lLCB2YWx1ZTogZmllbGRWYWx9O1xyXG4gICAgICAgICAgICAgICAgfVxyXG4gICAgICAgICAgICAgICAgZWxzZVxyXG4gICAgICAgICAgICAgICAge1xyXG4gICAgICAgICAgICAgICAgICAgIC8vc2VsZi51cmxfY29tcG9uZW50cyArPSBcIiZcIitmaWVsZE5hbWUrXCI9XCIrZmllbGRWYWw7XHJcbiAgICAgICAgICAgICAgICAgICAgc2VsZi51cmxfcGFyYW1zW2ZpZWxkTmFtZV0gPSBmaWVsZFZhbDtcclxuICAgICAgICAgICAgICAgIH1cclxuXHJcblx0XHRcdH1cclxuXHRcdH1cclxuXHJcbiAgICAgICAgaWYocmV0dXJuX29iamVjdD09dHJ1ZSlcclxuICAgICAgICB7XHJcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcclxuICAgICAgICB9XHJcblx0fVxyXG59O1xufSkuY2FsbCh0aGlzLHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWwgOiB0eXBlb2Ygc2VsZiAhPT0gXCJ1bmRlZmluZWRcIiA/IHNlbGYgOiB0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93IDoge30pXG4vLyMgc291cmNlTWFwcGluZ1VSTD1kYXRhOmFwcGxpY2F0aW9uL2pzb247Y2hhcnNldDp1dGYtODtiYXNlNjQsZXlKMlpYSnphVzl1SWpvekxDSnpiM1Z5WTJWeklqcGJJbk55WXk5d2RXSnNhV012WVhOelpYUnpMMnB6TDJsdVkyeDFaR1Z6TDNCeWIyTmxjM05mWm05eWJTNXFjeUpkTENKdVlXMWxjeUk2VzEwc0ltMWhjSEJwYm1keklqb2lPMEZCUVVFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRWlMQ0ptYVd4bElqb2laMlZ1WlhKaGRHVmtMbXB6SWl3aWMyOTFjbU5sVW05dmRDSTZJaUlzSW5OdmRYSmpaWE5EYjI1MFpXNTBJanBiSWx4eVhHNTJZWElnSkNBOUlDaDBlWEJsYjJZZ2QybHVaRzkzSUNFOVBTQmNJblZ1WkdWbWFXNWxaRndpSUQ4Z2QybHVaRzkzV3lkcVVYVmxjbmtuWFNBNklIUjVjR1Z2WmlCbmJHOWlZV3dnSVQwOUlGd2lkVzVrWldacGJtVmtYQ0lnUHlCbmJHOWlZV3hiSjJwUmRXVnllU2RkSURvZ2JuVnNiQ2s3WEhKY2JseHlYRzV0YjJSMWJHVXVaWGh3YjNKMGN5QTlJSHRjY2x4dVhISmNibHgwZEdGNGIyNXZiWGxmWVhKamFHbDJaWE02SURBc1hISmNiaUFnSUNCMWNteGZjR0Z5WVcxek9pQjdmU3hjY2x4dUlDQWdJSFJoZUY5aGNtTm9hWFpsWDNKbGMzVnNkSE5mZFhKc09pQmNJbHdpTEZ4eVhHNGdJQ0FnWVdOMGFYWmxYM1JoZURvZ1hDSmNJaXhjY2x4dUlDQWdJR1pwWld4a2N6b2dlMzBzWEhKY2JseDBhVzVwZERvZ1puVnVZM1JwYjI0b2RHRjRiMjV2YlhsZllYSmphR2wyWlhNc0lHTjFjbkpsYm5SZmRHRjRiMjV2YlhsZllYSmphR2wyWlNsN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVkR0Y0YjI1dmJYbGZZWEpqYUdsMlpYTWdQU0F3TzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11ZFhKc1gzQmhjbUZ0Y3lBOUlIdDlPMXh5WEc0Z0lDQWdJQ0FnSUhSb2FYTXVkR0Y0WDJGeVkyaHBkbVZmY21WemRXeDBjMTkxY213Z1BTQmNJbHdpTzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11WVdOMGFYWmxYM1JoZUNBOUlGd2lYQ0k3WEhKY2JseHlYRzVjZEZ4MEx5OTBhR2x6TGlSbWFXVnNaSE1nUFNBa1ptbGxiR1J6TzF4eVhHNGdJQ0FnSUNBZ0lIUm9hWE11ZEdGNGIyNXZiWGxmWVhKamFHbDJaWE1nUFNCMFlYaHZibTl0ZVY5aGNtTm9hWFpsY3p0Y2NseHVJQ0FnSUNBZ0lDQjBhR2x6TG1OMWNuSmxiblJmZEdGNGIyNXZiWGxmWVhKamFHbDJaU0E5SUdOMWNuSmxiblJmZEdGNGIyNXZiWGxmWVhKamFHbDJaVHRjY2x4dVhISmNibHgwWEhSMGFHbHpMbU5zWldGeVZYSnNRMjl0Y0c5dVpXNTBjeWdwTzF4eVhHNWNjbHh1WEhSOUxGeHlYRzRnSUNBZ2MyVjBWR0Y0UVhKamFHbDJaVkpsYzNWc2RITlZjbXc2SUdaMWJtTjBhVzl1S0NSbWIzSnRMQ0JqZFhKeVpXNTBYM0psYzNWc2RITmZkWEpzTENCblpYUmZZV04wYVhabEtTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lIWmhjaUJ6Wld4bUlEMGdkR2hwY3p0Y2NseHVYSFJjZEhSb2FYTXVZMnhsWVhKVVlYaEJjbU5vYVhabFVtVnpkV3gwYzFWeWJDZ3BPMXh5WEc0Z0lDQWdJQ0FnSUM4dmRtRnlJR04xY25KbGJuUmZjbVZ6ZFd4MGMxOTFjbXdnUFNCY0lsd2lPMXh5WEc0Z0lDQWdJQ0FnSUdsbUtIUm9hWE11ZEdGNGIyNXZiWGxmWVhKamFHbDJaWE1oUFRFcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnlaWFIxY200N1hISmNiaUFnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQnBaaWgwZVhCbGIyWW9aMlYwWDJGamRHbDJaU2s5UFZ3aWRXNWtaV1pwYm1Wa1hDSXBYSEpjYmx4MFhIUjdYSEpjYmx4MFhIUmNkSFpoY2lCblpYUmZZV04wYVhabElEMGdabUZzYzJVN1hISmNibHgwWEhSOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUM4dlkyaGxZMnNnZEc4Z2MyVmxJR2xtSUhkbElHaGhkbVVnWVc1NUlIUmhlRzl1YjIxcFpYTWdjMlZzWldOMFpXUmNjbHh1SUNBZ0lDQWdJQ0F2TDJsbUlITnZMQ0JqYUdWamF5QjBhR1ZwY2lCeVpYZHlhWFJsY3lCaGJtUWdkWE5sSUhSb2IzTmxJR0Z6SUhSb1pTQnlaWE4xYkhSeklIVnliRnh5WEc0Z0lDQWdJQ0FnSUhaaGNpQWtabWxsYkdRZ1BTQm1ZV3h6WlR0Y2NseHVJQ0FnSUNBZ0lDQjJZWElnWm1sbGJHUmZibUZ0WlNBOUlGd2lYQ0k3WEhKY2JpQWdJQ0FnSUNBZ2RtRnlJR1pwWld4a1gzWmhiSFZsSUQwZ1hDSmNJanRjY2x4dVhISmNiaUFnSUNBZ0lDQWdkbUZ5SUNSaFkzUnBkbVZmZEdGNGIyNXZiWGtnUFNBa1ptOXliUzRrWm1sbGJHUnpMbkJoY21WdWRDZ3BMbVpwYm1Rb1hDSmJaR0YwWVMxelppMTBZWGh2Ym05dGVTMWhjbU5vYVhabFBTY3hKMTFjSWlrN1hISmNiaUFnSUNBZ0lDQWdhV1lvSkdGamRHbDJaVjkwWVhodmJtOXRlUzVzWlc1bmRHZzlQVEVwWEhKY2JpQWdJQ0FnSUNBZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBa1ptbGxiR1FnUFNBa1lXTjBhWFpsWDNSaGVHOXViMjE1TzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR1pwWld4a1ZIbHdaU0E5SUNSbWFXVnNaQzVoZEhSeUtGd2laR0YwWVMxelppMW1hV1ZzWkMxMGVYQmxYQ0lwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLQ2htYVdWc1pGUjVjR1VnUFQwZ1hDSjBZV2RjSWlrZ2ZId2dLR1pwWld4a1ZIbHdaU0E5UFNCY0ltTmhkR1ZuYjNKNVhDSXBJSHg4SUNobWFXVnNaRlI1Y0dVZ1BUMGdYQ0owWVhodmJtOXRlVndpS1NrZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhSaGVHOXViMjE1WDNaaGJIVmxJRDBnYzJWc1ppNXdjbTlqWlhOelZHRjRiMjV2Ylhrb0pHWnBaV3hrTENCMGNuVmxLVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdacFpXeGtYMjVoYldVZ1BTQWtabWxsYkdRdVlYUjBjaWhjSW1SaGRHRXRjMll0Wm1sbGJHUXRibUZ0WlZ3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCMFlYaHZibTl0ZVY5dVlXMWxJRDBnWm1sbGJHUmZibUZ0WlM1eVpYQnNZV05sS0Z3aVgzTm1kRjljSWl3Z1hDSmNJaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdhV1lnS0hSaGVHOXViMjE1WDNaaGJIVmxLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdabWxsYkdSZmRtRnNkV1VnUFNCMFlYaHZibTl0ZVY5MllXeDFaUzUyWVd4MVpUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb1ptbGxiR1JmZG1Gc2RXVTlQVndpWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUm1hV1ZzWkNBOUlHWmhiSE5sTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCcFppZ29jMlZzWmk1amRYSnlaVzUwWDNSaGVHOXViMjE1WDJGeVkyaHBkbVVoUFZ3aVhDSXBKaVlvYzJWc1ppNWpkWEp5Wlc1MFgzUmhlRzl1YjIxNVgyRnlZMmhwZG1VaFBYUmhlRzl1YjIxNVgyNWhiV1VwS1Z4eVhHNGdJQ0FnSUNBZ0lIdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSFJvYVhNdWRHRjRYMkZ5WTJocGRtVmZjbVZ6ZFd4MGMxOTFjbXdnUFNCamRYSnlaVzUwWDNKbGMzVnNkSE5mZFhKc08xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNCeVpYUjFjbTQ3WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNCcFppZ29LR1pwWld4a1gzWmhiSFZsUFQxY0lsd2lLWHg4S0NFa1ptbGxiR1FwSUNrcFhISmNiaUFnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWtabTl5YlM0a1ptbGxiR1J6TG1WaFkyZ29ablZ1WTNScGIyNGdLQ2tnZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNnaEpHWnBaV3hrS1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJtYVdWc1pGUjVjR1VnUFNBa0tIUm9hWE1wTG1GMGRISW9YQ0prWVhSaExYTm1MV1pwWld4a0xYUjVjR1ZjSWlrN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1JQ2dvWm1sbGJHUlVlWEJsSUQwOUlGd2lkR0ZuWENJcElIeDhJQ2htYVdWc1pGUjVjR1VnUFQwZ1hDSmpZWFJsWjI5eWVWd2lLU0I4ZkNBb1ptbGxiR1JVZVhCbElEMDlJRndpZEdGNGIyNXZiWGxjSWlrcElIdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUhSaGVHOXViMjE1WDNaaGJIVmxJRDBnYzJWc1ppNXdjbTlqWlhOelZHRjRiMjV2Ylhrb0pDaDBhR2x6S1N3Z2RISjFaU2s3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdacFpXeGtYMjVoYldVZ1BTQWtLSFJvYVhNcExtRjBkSElvWENKa1lYUmhMWE5tTFdacFpXeGtMVzVoYldWY0lpazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppQW9kR0Y0YjI1dmJYbGZkbUZzZFdVcElIdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQm1hV1ZzWkY5MllXeDFaU0E5SUhSaGVHOXViMjE1WDNaaGJIVmxMblpoYkhWbE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lHbG1JQ2htYVdWc1pGOTJZV3gxWlNBaFBTQmNJbHdpS1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ1JtYVdWc1pDQTlJQ1FvZEdocGN5azdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCOVhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0I5S1R0Y2NseHVJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lHbG1LQ0FvSkdacFpXeGtLU0FtSmlBb1ptbGxiR1JmZG1Gc2RXVWdJVDBnWENKY0lpQXBLU0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQzh2YVdZZ2QyVWdabTkxYm1RZ1lTQm1hV1ZzWkZ4eVhHNWNkRngwWEhSMllYSWdjbVYzY21sMFpWOWhkSFJ5SUQwZ0tDUm1hV1ZzWkM1aGRIUnlLRndpWkdGMFlTMXpaaTEwWlhKdExYSmxkM0pwZEdWY0lpa3BPMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnYVdZb2NtVjNjbWwwWlY5aGRIUnlJVDFjSWx3aUtTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSEpsZDNKcGRHVWdQU0JLVTA5T0xuQmhjbk5sS0hKbGQzSnBkR1ZmWVhSMGNpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IyWVhJZ2FXNXdkWFJmZEhsd1pTQTlJQ1JtYVdWc1pDNWhkSFJ5S0Z3aVpHRjBZUzF6WmkxbWFXVnNaQzFwYm5CMWRDMTBlWEJsWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdjMlZzWmk1aFkzUnBkbVZmZEdGNElEMGdabWxsYkdSZmJtRnRaVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMMlpwYm1RZ2RHaGxJR0ZqZEdsMlpTQmxiR1Z0Wlc1MFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQnBaaUFvS0dsdWNIVjBYM1I1Y0dVZ1BUMGdYQ0p5WVdScGIxd2lLU0I4ZkNBb2FXNXdkWFJmZEhsd1pTQTlQU0JjSW1Ob1pXTnJZbTk0WENJcEtTQjdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2ZG1GeUlDUmhZM1JwZG1VZ1BTQWtabWxsYkdRdVptbHVaQ2hjSWk1elppMXZjSFJwYjI0dFlXTjBhWFpsWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dlpYaHdiRzlrWlNCMGFHVWdkbUZzZFdWeklHbG1JSFJvWlhKbElHbHpJR0VnWkdWc2FXMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwyWnBaV3hrWDNaaGJIVmxYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCcGMxOXphVzVuYkdWZmRtRnNkV1VnUFNCMGNuVmxPMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJtYVdWc1pGOTJZV3gxWlhNZ1BTQm1hV1ZzWkY5MllXeDFaUzV6Y0d4cGRDaGNJaXhjSWlrdWFtOXBiaWhjSWl0Y0lpa3VjM0JzYVhRb1hDSXJYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNobWFXVnNaRjkyWVd4MVpYTXViR1Z1WjNSb0lENGdNU2tnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcGMxOXphVzVuYkdWZmRtRnNkV1VnUFNCbVlXeHpaVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtSUNocGMxOXphVzVuYkdWZmRtRnNkV1VwSUh0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lBa2FXNXdkWFFnUFNBa1ptbGxiR1F1Wm1sdVpDaGNJbWx1Y0hWMFczWmhiSFZsUFNkY0lpQXJJR1pwWld4a1gzWmhiSFZsSUNzZ1hDSW5YVndpS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJQ1JoWTNScGRtVWdQU0FrYVc1d2RYUXVjR0Z5Wlc1MEtDazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSFpoY2lCa1pYQjBhQ0E5SUNSaFkzUnBkbVV1WVhSMGNpaGNJbVJoZEdFdGMyWXRaR1Z3ZEdoY0lpazdYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwyNXZkeUJzYjI5d0lIUm9jbTkxWjJnZ2NHRnlaVzUwY3lCMGJ5Qm5jbUZpSUhSb1pXbHlJRzVoYldWelhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUIyWVd4MVpYTWdQU0J1WlhjZ1FYSnlZWGtvS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnNkV1Z6TG5CMWMyZ29abWxsYkdSZmRtRnNkV1VwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdabTl5SUNoMllYSWdhU0E5SUdSbGNIUm9PeUJwSUQ0Z01Ec2dhUzB0S1NCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FrWVdOMGFYWmxJRDBnSkdGamRHbDJaUzV3WVhKbGJuUW9LUzV3WVhKbGJuUW9LVHRjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhiSFZsY3k1d2RYTm9LQ1JoWTNScGRtVXVabWx1WkNoY0ltbHVjSFYwWENJcExuWmhiQ2dwS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2ZWeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1Gc2RXVnpMbkpsZG1WeWMyVW9LVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZaM0poWWlCMGFHVWdjbVYzY21sMFpTQm1iM0lnZEdocGN5QmtaWEIwYUZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMllYSWdZV04wYVhabFgzSmxkM0pwZEdVZ1BTQnlaWGR5YVhSbFcyUmxjSFJvWFR0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJSFZ5YkNBOUlHRmpkR2wyWlY5eVpYZHlhWFJsTzF4eVhHNWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUM4dmRHaGxiaUJ0WVhBZ1puSnZiU0IwYUdVZ2NHRnlaVzUwY3lCMGJ5QjBhR1VnWkdWd2RHaGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdKQ2gyWVd4MVpYTXBMbVZoWTJnb1puVnVZM1JwYjI0Z0tHbHVaR1Y0TENCMllXeDFaU2tnZTF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhWeWJDQTlJSFZ5YkM1eVpYQnNZV05sS0Z3aVcxd2lJQ3NnYVc1a1pYZ2dLeUJjSWwxY0lpd2dkbUZzZFdVcE8xeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlNrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIUm9hWE11ZEdGNFgyRnlZMmhwZG1WZmNtVnpkV3gwYzE5MWNtd2dQU0IxY213N1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZlZ4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1ZzYzJVZ2UxeHlYRzVjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnTHk5cFppQjBhR1Z5WlNCaGNtVWdiWFZzZEdsd2JHVWdkbUZzZFdWekxGeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZMM1JvWlc0Z2QyVWdibVZsWkNCMGJ5QmphR1ZqYXlCbWIzSWdNeUIwYUdsdVozTTZYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwybG1JSFJvWlNCMllXeDFaWE1nYzJWc1pXTjBaV1FnWVhKbElHRnNiQ0JwYmlCMGFHVWdjMkZ0WlNCMGNtVmxJSFJvWlc0Z2QyVWdZMkZ1SUdSdklITnZiV1VnWTJ4bGRtVnlJSEpsZDNKcGRHVWdjM1IxWm1aY2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0x5OXRaWEpuWlNCaGJHd2dkbUZzZFdWeklHbHVJSE5oYldVZ2JHVjJaV3dzSUhSb1pXNGdZMjl0WW1sdVpTQjBhR1VnYkdWMlpXeHpYSEpjYmx4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBdkwybG1JSFJvWlhrZ1lYSmxJR1p5YjIwZ1pHbG1abVZ5Wlc1MElIUnlaV1Z6SUhSb1pXNGdhblZ6ZENCamIyMWlhVzVsSUhSb1pXMGdiM0lnYW5WemRDQjFjMlVnWUdacFpXeGtYM1poYkhWbFlGeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQXZLbHh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJrWlhCMGFITWdQU0J1WlhjZ1FYSnlZWGtvS1R0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUW9abWxsYkdSZmRtRnNkV1Z6S1M1bFlXTm9LR1oxYm1OMGFXOXVJQ2hwYm1SbGVDd2dkbUZzS1NCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlDUnBibkIxZENBOUlDUm1hV1ZzWkM1bWFXNWtLRndpYVc1d2RYUmJkbUZzZFdVOUoxd2lJQ3NnWm1sbGJHUmZkbUZzZFdVZ0t5QmNJaWRkWENJcE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUNSaFkzUnBkbVVnUFNBa2FXNXdkWFF1Y0dGeVpXNTBLQ2s3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZ5SUdSbGNIUm9JRDBnSkdGamRHbDJaUzVoZEhSeUtGd2laR0YwWVMxelppMWtaWEIwYUZ3aUtUdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQzh2WkdWd2RHaHpMbkIxYzJnb1pHVndkR2dwTzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDBwT3lvdlhISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUdWc2MyVWdhV1lnS0NocGJuQjFkRjkwZVhCbElEMDlJRndpYzJWc1pXTjBYQ0lwSUh4OElDaHBibkIxZEY5MGVYQmxJRDA5SUZ3aWJYVnNkR2x6Wld4bFkzUmNJaWtwSUh0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJR2x6WDNOcGJtZHNaVjkyWVd4MVpTQTlJSFJ5ZFdVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZG1GeUlHWnBaV3hrWDNaaGJIVmxjeUE5SUdacFpXeGtYM1poYkhWbExuTndiR2wwS0Z3aUxGd2lLUzVxYjJsdUtGd2lLMXdpS1M1emNHeHBkQ2hjSWl0Y0lpazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLR1pwWld4a1gzWmhiSFZsY3k1c1pXNW5kR2dnUGlBeEtTQjdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2x6WDNOcGJtZHNaVjkyWVd4MVpTQTlJR1poYkhObE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUgxY2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2FXWWdLR2x6WDNOcGJtZHNaVjkyWVd4MVpTa2dlMXh5WEc1Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2RtRnlJQ1JoWTNScGRtVWdQU0FrWm1sbGJHUXVabWx1WkNoY0ltOXdkR2x2Ymx0MllXeDFaVDBuWENJZ0t5Qm1hV1ZzWkY5MllXeDFaU0FySUZ3aUoxMWNJaWs3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUhaaGNpQmtaWEIwYUNBOUlDUmhZM1JwZG1VdVlYUjBjaWhjSW1SaGRHRXRjMll0WkdWd2RHaGNJaWs3WEhKY2JseHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnZG1Gc2RXVnpJRDBnYm1WM0lFRnljbUY1S0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhiSFZsY3k1d2RYTm9LR1pwWld4a1gzWmhiSFZsS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1p2Y2lBb2RtRnlJR2tnUFNCa1pYQjBhRHNnYVNBK0lEQTdJR2t0TFNrZ2UxeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSkdGamRHbDJaU0E5SUNSaFkzUnBkbVV1Y0hKbGRrRnNiQ2hjSW05d2RHbHZibHRrWVhSaExYTm1MV1JsY0hSb1BTZGNJaUFySUNocElDMGdNU2tnS3lCY0lpZGRYQ0lwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdkbUZzZFdWekxuQjFjMmdvSkdGamRHbDJaUzUyWVd3b0tTazdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhiSFZsY3k1eVpYWmxjbk5sS0NrN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJoWTNScGRtVmZjbVYzY21sMFpTQTlJSEpsZDNKcGRHVmJaR1Z3ZEdoZE8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjJZWElnZFhKc0lEMGdZV04wYVhabFgzSmxkM0pwZEdVN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDUW9kbUZzZFdWektTNWxZV05vS0daMWJtTjBhVzl1SUNocGJtUmxlQ3dnZG1Gc2RXVXBJSHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0IxY213Z1BTQjFjbXd1Y21Wd2JHRmpaU2hjSWx0Y0lpQXJJR2x1WkdWNElDc2dYQ0pkWENJc0lIWmhiSFZsS1R0Y2NseHVYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDBwTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCMGFHbHpMblJoZUY5aGNtTm9hWFpsWDNKbGMzVnNkSE5mZFhKc0lEMGdkWEpzTzF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNiaUFnSUNBZ0lDQWdJQ0FnSUNBZ0lDQjlYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2ZWeHlYRzRnSUNBZ0lDQWdJQzh2ZEdocGN5NTBZWGhmWVhKamFHbDJaVjl5WlhOMWJIUnpYM1Z5YkNBOUlHTjFjbkpsYm5SZmNtVnpkV3gwYzE5MWNtdzdYSEpjYmlBZ0lDQjlMRnh5WEc0Z0lDQWdaMlYwVW1WemRXeDBjMVZ5YkRvZ1puVnVZM1JwYjI0b0pHWnZjbTBzSUdOMWNuSmxiblJmY21WemRXeDBjMTkxY213cElIdGNjbHh1WEhKY2JpQWdJQ0FnSUNBZ0x5OTBhR2x6TG5ObGRGUmhlRUZ5WTJocGRtVlNaWE4xYkhSelZYSnNLQ1JtYjNKdExDQmpkWEp5Wlc1MFgzSmxjM1ZzZEhOZmRYSnNLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdhV1lvZEdocGN5NTBZWGhmWVhKamFHbDJaVjl5WlhOMWJIUnpYM1Z5YkQwOVhDSmNJaWxjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJqZFhKeVpXNTBYM0psYzNWc2RITmZkWEpzTzF4eVhHNGdJQ0FnSUNBZ0lIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2NtVjBkWEp1SUhSb2FYTXVkR0Y0WDJGeVkyaHBkbVZmY21WemRXeDBjMTkxY213N1hISmNiaUFnSUNCOUxGeHlYRzVjZEdkbGRGVnliRkJoY21GdGN6b2dablZ1WTNScGIyNG9KR1p2Y20wcGUxeHlYRzVjY2x4dVhIUmNkSFJvYVhNdVluVnBiR1JWY214RGIyMXdiMjVsYm5SektDUm1iM0p0TENCMGNuVmxLVHRjY2x4dVhISmNiaUFnSUNBZ0lDQWdhV1lvZEdocGN5NTBZWGhmWVhKamFHbDJaVjl5WlhOMWJIUnpYM1Z5YkNFOVhDSmNJaWxjY2x4dUlDQWdJQ0FnSUNCN1hISmNibHh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQnBaaWgwYUdsekxtRmpkR2wyWlY5MFlYZ2hQVndpWENJcFhISmNiaUFnSUNBZ0lDQWdJQ0FnSUh0Y2NseHVJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lIWmhjaUJtYVdWc1pGOXVZVzFsSUQwZ2RHaHBjeTVoWTNScGRtVmZkR0Y0TzF4eVhHNWNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR2xtS0hSNWNHVnZaaWgwYUdsekxuVnliRjl3WVhKaGJYTmJabWxsYkdSZmJtRnRaVjBwSVQxY0luVnVaR1ZtYVc1bFpGd2lLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJR1JsYkdWMFpTQjBhR2x6TG5WeWJGOXdZWEpoYlhOYlptbGxiR1JmYm1GdFpWMDdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0I5WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dUlDQWdJQ0FnSUNCOVhISmNibHh5WEc1Y2RGeDBjbVYwZFhKdUlIUm9hWE11ZFhKc1gzQmhjbUZ0Y3p0Y2NseHVYSFI5TEZ4eVhHNWNkR05zWldGeVZYSnNRMjl0Y0c5dVpXNTBjem9nWm5WdVkzUnBiMjRvS1h0Y2NseHVYSFJjZEM4dmRHaHBjeTUxY214ZlkyOXRjRzl1Wlc1MGN5QTlJRndpWENJN1hISmNibHgwWEhSMGFHbHpMblZ5YkY5d1lYSmhiWE1nUFNCN2ZUdGNjbHh1WEhSOUxGeHlYRzVjZEdOc1pXRnlWR0Y0UVhKamFHbDJaVkpsYzNWc2RITlZjbXc2SUdaMWJtTjBhVzl1S0NrZ2UxeHlYRzVjZEZ4MGRHaHBjeTUwWVhoZllYSmphR2wyWlY5eVpYTjFiSFJ6WDNWeWJDQTlJQ2NuTzF4eVhHNWNkSDBzWEhKY2JseDBaR2x6WVdKc1pVbHVjSFYwY3pvZ1puVnVZM1JwYjI0b0pHWnZjbTBwZTF4eVhHNWNkRngwZG1GeUlITmxiR1lnUFNCMGFHbHpPMXh5WEc1Y2RGeDBYSEpjYmx4MFhIUWtabTl5YlM0a1ptbGxiR1J6TG1WaFkyZ29ablZ1WTNScGIyNG9LWHRjY2x4dVhIUmNkRngwWEhKY2JseDBYSFJjZEhaaGNpQWthVzV3ZFhSeklEMGdKQ2gwYUdsektTNW1hVzVrS0Z3aWFXNXdkWFFzSUhObGJHVmpkQ3dnTG0xbGRHRXRjMnhwWkdWeVhDSXBPMXh5WEc1Y2RGeDBYSFFrYVc1d2RYUnpMbUYwZEhJb1hDSmthWE5oWW14bFpGd2lMQ0JjSW1ScGMyRmliR1ZrWENJcE8xeHlYRzVjZEZ4MFhIUWthVzV3ZFhSekxtRjBkSElvWENKa2FYTmhZbXhsWkZ3aUxDQjBjblZsS1R0Y2NseHVYSFJjZEZ4MEpHbHVjSFYwY3k1d2NtOXdLRndpWkdsellXSnNaV1JjSWl3Z2RISjFaU2s3WEhKY2JseDBYSFJjZENScGJuQjFkSE11ZEhKcFoyZGxjaWhjSW1Ob2IzTmxianAxY0dSaGRHVmtYQ0lwTzF4eVhHNWNkRngwWEhSY2NseHVYSFJjZEgwcE8xeHlYRzVjZEZ4MFhISmNibHgwWEhSY2NseHVYSFI5TEZ4eVhHNWNkR1Z1WVdKc1pVbHVjSFYwY3pvZ1puVnVZM1JwYjI0b0pHWnZjbTBwZTF4eVhHNWNkRngwZG1GeUlITmxiR1lnUFNCMGFHbHpPMXh5WEc1Y2RGeDBKR1p2Y20wdUpHWnBaV3hrY3k1bFlXTm9LR1oxYm1OMGFXOXVLQ2w3WEhKY2JseDBYSFJjZEhaaGNpQWthVzV3ZFhSeklEMGdKQ2gwYUdsektTNW1hVzVrS0Z3aWFXNXdkWFFzSUhObGJHVmpkQ3dnTG0xbGRHRXRjMnhwWkdWeVhDSXBPMXh5WEc1Y2RGeDBYSFFrYVc1d2RYUnpMbkJ5YjNBb1hDSmthWE5oWW14bFpGd2lMQ0JtWVd4elpTazdYSEpjYmx4MFhIUmNkQ1JwYm5CMWRITXVZWFIwY2loY0ltUnBjMkZpYkdWa1hDSXNJR1poYkhObEtUdGNjbHh1WEhSY2RGeDBKR2x1Y0hWMGN5NTBjbWxuWjJWeUtGd2lZMmh2YzJWdU9uVndaR0YwWldSY0lpazdYSFJjZEZ4MFhISmNibHgwWEhSOUtUdGNjbHh1WEhSY2RGeHlYRzVjZEZ4MFhISmNibHgwZlN4Y2NseHVYSFJpZFdsc1pGVnliRU52YlhCdmJtVnVkSE02SUdaMWJtTjBhVzl1S0NSbWIzSnRMQ0JqYkdWaGNsOWpiMjF3YjI1bGJuUnpLWHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBkbUZ5SUhObGJHWWdQU0IwYUdsek8xeHlYRzVjZEZ4MFhISmNibHgwWEhScFppaDBlWEJsYjJZb1kyeGxZWEpmWTI5dGNHOXVaVzUwY3lraFBWd2lkVzVrWldacGJtVmtYQ0lwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdsbUtHTnNaV0Z5WDJOdmJYQnZibVZ1ZEhNOVBYUnlkV1VwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUjBhR2x6TG1Oc1pXRnlWWEpzUTI5dGNHOXVaVzUwY3lncE8xeHlYRzVjZEZ4MFhIUjlYSEpjYmx4MFhIUjlYSEpjYmx4MFhIUmNjbHh1WEhSY2RDUm1iM0p0TGlSbWFXVnNaSE11WldGamFDaG1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2RGeDBYSFJjY2x4dVhIUmNkRngwZG1GeUlHWnBaV3hrVG1GdFpTQTlJQ1FvZEdocGN5a3VZWFIwY2loY0ltUmhkR0V0YzJZdFptbGxiR1F0Ym1GdFpWd2lLVHRjY2x4dVhIUmNkRngwZG1GeUlHWnBaV3hrVkhsd1pTQTlJQ1FvZEdocGN5a3VZWFIwY2loY0ltUmhkR0V0YzJZdFptbGxiR1F0ZEhsd1pWd2lLVHRjY2x4dVhIUmNkRngwWEhKY2JseDBYSFJjZEdsbUtHWnBaV3hrVkhsd1pUMDlYQ0p6WldGeVkyaGNJaWxjY2x4dVhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RITmxiR1l1Y0hKdlkyVnpjMU5sWVhKamFFWnBaV3hrS0NRb2RHaHBjeWtwTzF4eVhHNWNkRngwWEhSOVhISmNibHgwWEhSY2RHVnNjMlVnYVdZb0tHWnBaV3hrVkhsd1pUMDlYQ0owWVdkY0lpbDhmQ2htYVdWc1pGUjVjR1U5UFZ3aVkyRjBaV2R2Y25sY0lpbDhmQ2htYVdWc1pGUjVjR1U5UFZ3aWRHRjRiMjV2YlhsY0lpa3BYSEpjYmx4MFhIUmNkSHRjY2x4dVhIUmNkRngwWEhSelpXeG1MbkJ5YjJObGMzTlVZWGh2Ym05dGVTZ2tLSFJvYVhNcEtUdGNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBYSFJsYkhObElHbG1LR1pwWld4a1ZIbHdaVDA5WENKemIzSjBYMjl5WkdWeVhDSXBYSEpjYmx4MFhIUmNkSHRjY2x4dVhIUmNkRngwWEhSelpXeG1MbkJ5YjJObGMzTlRiM0owVDNKa1pYSkdhV1ZzWkNna0tIUm9hWE1wS1R0Y2NseHVYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmxiSE5sSUdsbUtHWnBaV3hrVkhsd1pUMDlYQ0p3YjNOMGMxOXdaWEpmY0dGblpWd2lLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MGMyVnNaaTV3Y205alpYTnpVbVZ6ZFd4MGMxQmxjbEJoWjJWR2FXVnNaQ2drS0hSb2FYTXBLVHRjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSbGJITmxJR2xtS0dacFpXeGtWSGx3WlQwOVhDSmhkWFJvYjNKY0lpbGNjbHh1WEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZEhObGJHWXVjSEp2WTJWemMwRjFkR2h2Y2lna0tIUm9hWE1wS1R0Y2NseHVYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmxiSE5sSUdsbUtHWnBaV3hrVkhsd1pUMDlYQ0p3YjNOMFgzUjVjR1ZjSWlsY2NseHVYSFJjZEZ4MGUxeHlYRzVjZEZ4MFhIUmNkSE5sYkdZdWNISnZZMlZ6YzFCdmMzUlVlWEJsS0NRb2RHaHBjeWtwTzF4eVhHNWNkRngwWEhSOVhISmNibHgwWEhSY2RHVnNjMlVnYVdZb1ptbGxiR1JVZVhCbFBUMWNJbkJ2YzNSZlpHRjBaVndpS1Z4eVhHNWNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBjMlZzWmk1d2NtOWpaWE56VUc5emRFUmhkR1VvSkNoMGFHbHpLU2s3WEhKY2JseDBYSFJjZEgxY2NseHVYSFJjZEZ4MFpXeHpaU0JwWmlobWFXVnNaRlI1Y0dVOVBWd2ljRzl6ZEY5dFpYUmhYQ0lwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUnpaV3htTG5CeWIyTmxjM05RYjNOMFRXVjBZU2drS0hSb2FYTXBLVHRjY2x4dVhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmxiSE5sWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBYSFJjY2x4dVhIUmNkSDBwTzF4eVhHNWNkRngwWEhKY2JseDBmU3hjY2x4dVhIUndjbTlqWlhOelUyVmhjbU5vUm1sbGJHUTZJR1oxYm1OMGFXOXVLQ1JqYjI1MFlXbHVaWElwWEhKY2JseDBlMXh5WEc1Y2RGeDBkbUZ5SUhObGJHWWdQU0IwYUdsek8xeHlYRzVjZEZ4MFhISmNibHgwWEhSMllYSWdKR1pwWld4a0lEMGdKR052Ym5SaGFXNWxjaTVtYVc1a0tGd2lhVzV3ZFhSYmJtRnRaVjQ5SjE5elpsOXpaV0Z5WTJnblhWd2lLVHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBhV1lvSkdacFpXeGtMbXhsYm1kMGFENHdLVnh5WEc1Y2RGeDBlMXh5WEc1Y2RGeDBYSFIyWVhJZ1ptbGxiR1JPWVcxbElEMGdKR1pwWld4a0xtRjBkSElvWENKdVlXMWxYQ0lwTG5KbGNHeGhZMlVvSjF0ZEp5d2dKeWNwTzF4eVhHNWNkRngwWEhSMllYSWdabWxsYkdSV1lXd2dQU0FrWm1sbGJHUXVkbUZzS0NrN1hISmNibHgwWEhSY2RGeHlYRzVjZEZ4MFhIUnBaaWhtYVdWc1pGWmhiQ0U5WENKY0lpbGNjbHh1WEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZEM4dmMyVnNaaTUxY214ZlkyOXRjRzl1Wlc1MGN5QXJQU0JjSWlaZmMyWmZjejFjSWl0bGJtTnZaR1ZWVWtsRGIyMXdiMjVsYm5Rb1ptbGxiR1JXWVd3cE8xeHlYRzVjZEZ4MFhIUmNkSE5sYkdZdWRYSnNYM0JoY21GdGMxc25YM05tWDNNblhTQTlJR1Z1WTI5a1pWVlNTVU52YlhCdmJtVnVkQ2htYVdWc1pGWmhiQ2s3WEhKY2JseDBYSFJjZEgxY2NseHVYSFJjZEgxY2NseHVYSFI5TEZ4eVhHNWNkSEJ5YjJObGMzTlRiM0owVDNKa1pYSkdhV1ZzWkRvZ1puVnVZM1JwYjI0b0pHTnZiblJoYVc1bGNpbGNjbHh1WEhSN1hISmNibHgwWEhSMGFHbHpMbkJ5YjJObGMzTkJkWFJvYjNJb0pHTnZiblJoYVc1bGNpazdYSEpjYmx4MFhIUmNjbHh1WEhSOUxGeHlYRzVjZEhCeWIyTmxjM05TWlhOMWJIUnpVR1Z5VUdGblpVWnBaV3hrT2lCbWRXNWpkR2x2Ymlna1kyOXVkR0ZwYm1WeUtWeHlYRzVjZEh0Y2NseHVYSFJjZEhSb2FYTXVjSEp2WTJWemMwRjFkR2h2Y2lna1kyOXVkR0ZwYm1WeUtUdGNjbHh1WEhSY2RGeHlYRzVjZEgwc1hISmNibHgwWjJWMFFXTjBhWFpsVkdGNE9pQm1kVzVqZEdsdmJpZ2tabWxsYkdRcElIdGNjbHh1WEhSY2RISmxkSFZ5YmlCMGFHbHpMbUZqZEdsMlpWOTBZWGc3WEhKY2JseDBmU3hjY2x4dVhIUm5aWFJUWld4bFkzUldZV3c2SUdaMWJtTjBhVzl1S0NSbWFXVnNaQ2w3WEhKY2JseHlYRzVjZEZ4MGRtRnlJR1pwWld4a1ZtRnNJRDBnWENKY0lqdGNjbHh1WEhSY2RGeHlYRzVjZEZ4MGFXWW9KR1pwWld4a0xuWmhiQ2dwSVQwd0tWeHlYRzVjZEZ4MGUxeHlYRzVjZEZ4MFhIUm1hV1ZzWkZaaGJDQTlJQ1JtYVdWc1pDNTJZV3dvS1R0Y2NseHVYSFJjZEgxY2NseHVYSFJjZEZ4eVhHNWNkRngwYVdZb1ptbGxiR1JXWVd3OVBXNTFiR3dwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdacFpXeGtWbUZzSUQwZ1hDSmNJanRjY2x4dVhIUmNkSDFjY2x4dVhIUmNkRnh5WEc1Y2RGeDBjbVYwZFhKdUlHWnBaV3hrVm1Gc08xeHlYRzVjZEgwc1hISmNibHgwWjJWMFRXVjBZVk5sYkdWamRGWmhiRG9nWm5WdVkzUnBiMjRvSkdacFpXeGtLWHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBkbUZ5SUdacFpXeGtWbUZzSUQwZ1hDSmNJanRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBabWxsYkdSV1lXd2dQU0FrWm1sbGJHUXVkbUZzS0NrN1hISmNibHgwWEhSY2RGeDBYSFJjZEZ4eVhHNWNkRngwYVdZb1ptbGxiR1JXWVd3OVBXNTFiR3dwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdacFpXeGtWbUZzSUQwZ1hDSmNJanRjY2x4dVhIUmNkSDFjY2x4dVhIUmNkRnh5WEc1Y2RGeDBjbVYwZFhKdUlHWnBaV3hrVm1Gc08xeHlYRzVjZEgwc1hISmNibHgwWjJWMFRYVnNkR2xUWld4bFkzUldZV3c2SUdaMWJtTjBhVzl1S0NSbWFXVnNaQ3dnYjNCbGNtRjBiM0lwZTF4eVhHNWNkRngwWEhKY2JseDBYSFIyWVhJZ1pHVnNhVzBnUFNCY0lpdGNJanRjY2x4dVhIUmNkR2xtS0c5d1pYSmhkRzl5UFQxY0ltOXlYQ0lwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdSbGJHbHRJRDBnWENJc1hDSTdYSEpjYmx4MFhIUjlYSEpjYmx4MFhIUmNjbHh1WEhSY2RHbG1LSFI1Y0dWdlppZ2tabWxsYkdRdWRtRnNLQ2twUFQxY0ltOWlhbVZqZEZ3aUtWeHlYRzVjZEZ4MGUxeHlYRzVjZEZ4MFhIUnBaaWdrWm1sbGJHUXVkbUZzS0NraFBXNTFiR3dwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUnlaWFIxY200Z0pHWnBaV3hrTG5aaGJDZ3BMbXB2YVc0b1pHVnNhVzBwTzF4eVhHNWNkRngwWEhSOVhISmNibHgwWEhSOVhISmNibHgwWEhSY2NseHVYSFI5TEZ4eVhHNWNkR2RsZEUxbGRHRk5kV3gwYVZObGJHVmpkRlpoYkRvZ1puVnVZM1JwYjI0b0pHWnBaV3hrTENCdmNHVnlZWFJ2Y2lsN1hISmNibHgwWEhSY2NseHVYSFJjZEhaaGNpQmtaV3hwYlNBOUlGd2lMU3N0WENJN1hISmNibHgwWEhScFppaHZjR1Z5WVhSdmNqMDlYQ0p2Y2x3aUtWeHlYRzVjZEZ4MGUxeHlYRzVjZEZ4MFhIUmtaV3hwYlNBOUlGd2lMU3d0WENJN1hISmNibHgwWEhSOVhISmNibHgwWEhSY2RGeDBYSEpjYmx4MFhIUnBaaWgwZVhCbGIyWW9KR1pwWld4a0xuWmhiQ2dwS1QwOVhDSnZZbXBsWTNSY0lpbGNjbHh1WEhSY2RIdGNjbHh1WEhSY2RGeDBhV1lvSkdacFpXeGtMblpoYkNncElUMXVkV3hzS1Z4eVhHNWNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwZG1GeUlHWnBaV3hrZG1Gc0lEMGdXMTA3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBKQ2drWm1sbGJHUXVkbUZzS0NrcExtVmhZMmdvWm5WdVkzUnBiMjRvYVc1a1pYZ3NkbUZzZFdVcGUxeHlYRzVjZEZ4MFhIUmNkRngwWEhKY2JseDBYSFJjZEZ4MFhIUm1hV1ZzWkhaaGJDNXdkWE5vS0NoMllXeDFaU2twTzF4eVhHNWNkRngwWEhSY2RIMHBPMXh5WEc1Y2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RISmxkSFZ5YmlCbWFXVnNaSFpoYkM1cWIybHVLR1JsYkdsdEtUdGNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBmVnh5WEc1Y2RGeDBYSEpjYmx4MFhIUnlaWFIxY200Z1hDSmNJanRjY2x4dVhIUmNkRnh5WEc1Y2RIMHNYSEpjYmx4MFoyVjBRMmhsWTJ0aWIzaFdZV3c2SUdaMWJtTjBhVzl1S0NSbWFXVnNaQ3dnYjNCbGNtRjBiM0lwZTF4eVhHNWNkRngwWEhKY2JseDBYSFJjY2x4dVhIUmNkSFpoY2lCbWFXVnNaRlpoYkNBOUlDUm1hV1ZzWkM1dFlYQW9ablZ1WTNScGIyNG9LWHRjY2x4dVhIUmNkRngwYVdZb0pDaDBhR2x6S1M1d2NtOXdLRndpWTJobFkydGxaRndpS1QwOWRISjFaU2xjY2x4dVhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RISmxkSFZ5YmlBa0tIUm9hWE1wTG5aaGJDZ3BPMXh5WEc1Y2RGeDBYSFI5WEhKY2JseDBYSFI5S1M1blpYUW9LVHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBkbUZ5SUdSbGJHbHRJRDBnWENJclhDSTdYSEpjYmx4MFhIUnBaaWh2Y0dWeVlYUnZjajA5WENKdmNsd2lLVnh5WEc1Y2RGeDBlMXh5WEc1Y2RGeDBYSFJrWld4cGJTQTlJRndpTEZ3aU8xeHlYRzVjZEZ4MGZWeHlYRzVjZEZ4MFhISmNibHgwWEhSeVpYUjFjbTRnWm1sbGJHUldZV3d1YW05cGJpaGtaV3hwYlNrN1hISmNibHgwZlN4Y2NseHVYSFJuWlhSTlpYUmhRMmhsWTJ0aWIzaFdZV3c2SUdaMWJtTjBhVzl1S0NSbWFXVnNaQ3dnYjNCbGNtRjBiM0lwZTF4eVhHNWNkRngwWEhKY2JseDBYSFJjY2x4dVhIUmNkSFpoY2lCbWFXVnNaRlpoYkNBOUlDUm1hV1ZzWkM1dFlYQW9ablZ1WTNScGIyNG9LWHRjY2x4dVhIUmNkRngwYVdZb0pDaDBhR2x6S1M1d2NtOXdLRndpWTJobFkydGxaRndpS1QwOWRISjFaU2xjY2x4dVhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RISmxkSFZ5YmlBb0pDaDBhR2x6S1M1MllXd29LU2s3WEhKY2JseDBYSFJjZEgxY2NseHVYSFJjZEgwcExtZGxkQ2dwTzF4eVhHNWNkRngwWEhKY2JseDBYSFIyWVhJZ1pHVnNhVzBnUFNCY0lpMHJMVndpTzF4eVhHNWNkRngwYVdZb2IzQmxjbUYwYjNJOVBWd2liM0pjSWlsY2NseHVYSFJjZEh0Y2NseHVYSFJjZEZ4MFpHVnNhVzBnUFNCY0lpMHNMVndpTzF4eVhHNWNkRngwZlZ4eVhHNWNkRngwWEhKY2JseDBYSFJ5WlhSMWNtNGdabWxsYkdSV1lXd3VhbTlwYmloa1pXeHBiU2s3WEhKY2JseDBmU3hjY2x4dVhIUm5aWFJTWVdScGIxWmhiRG9nWm5WdVkzUnBiMjRvSkdacFpXeGtLWHRjY2x4dVhIUmNkRngwWEhSY2RGeDBYSFJjY2x4dVhIUmNkSFpoY2lCbWFXVnNaRlpoYkNBOUlDUm1hV1ZzWkM1dFlYQW9ablZ1WTNScGIyNG9LVnh5WEc1Y2RGeDBlMXh5WEc1Y2RGeDBYSFJwWmlna0tIUm9hWE1wTG5CeWIzQW9YQ0pqYUdWamEyVmtYQ0lwUFQxMGNuVmxLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MGNtVjBkWEp1SUNRb2RHaHBjeWt1ZG1Gc0tDazdYSEpjYmx4MFhIUmNkSDFjY2x4dVhIUmNkRngwWEhKY2JseDBYSFI5S1M1blpYUW9LVHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBYSEpjYmx4MFhIUnBaaWhtYVdWc1pGWmhiRnN3WFNFOU1DbGNjbHh1WEhSY2RIdGNjbHh1WEhSY2RGeDBjbVYwZFhKdUlHWnBaV3hrVm1Gc1d6QmRPMXh5WEc1Y2RGeDBmVnh5WEc1Y2RIMHNYSEpjYmx4MFoyVjBUV1YwWVZKaFpHbHZWbUZzT2lCbWRXNWpkR2x2Ymlna1ptbGxiR1FwZTF4eVhHNWNkRngwWEhSY2RGeDBYSFJjZEZ4eVhHNWNkRngwZG1GeUlHWnBaV3hrVm1Gc0lEMGdKR1pwWld4a0xtMWhjQ2htZFc1amRHbHZiaWdwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdsbUtDUW9kR2hwY3lrdWNISnZjQ2hjSW1Ob1pXTnJaV1JjSWlrOVBYUnlkV1VwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUnlaWFIxY200Z0pDaDBhR2x6S1M1MllXd29LVHRjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSY2NseHVYSFJjZEgwcExtZGxkQ2dwTzF4eVhHNWNkRngwWEhKY2JseDBYSFJ5WlhSMWNtNGdabWxsYkdSV1lXeGJNRjA3WEhKY2JseDBmU3hjY2x4dVhIUndjbTlqWlhOelFYVjBhRzl5T2lCbWRXNWpkR2x2Ymlna1kyOXVkR0ZwYm1WeUtWeHlYRzVjZEh0Y2NseHVYSFJjZEhaaGNpQnpaV3htSUQwZ2RHaHBjenRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBYSEpjYmx4MFhIUjJZWElnWm1sbGJHUlVlWEJsSUQwZ0pHTnZiblJoYVc1bGNpNWhkSFJ5S0Z3aVpHRjBZUzF6WmkxbWFXVnNaQzEwZVhCbFhDSXBPMXh5WEc1Y2RGeDBkbUZ5SUdsdWNIVjBWSGx3WlNBOUlDUmpiMjUwWVdsdVpYSXVZWFIwY2loY0ltUmhkR0V0YzJZdFptbGxiR1F0YVc1d2RYUXRkSGx3WlZ3aUtUdGNjbHh1WEhSY2RGeHlYRzVjZEZ4MGRtRnlJQ1JtYVdWc1pEdGNjbHh1WEhSY2RIWmhjaUJtYVdWc1pFNWhiV1VnUFNCY0lsd2lPMXh5WEc1Y2RGeDBkbUZ5SUdacFpXeGtWbUZzSUQwZ1hDSmNJanRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBhV1lvYVc1d2RYUlVlWEJsUFQxY0luTmxiR1ZqZEZ3aUtWeHlYRzVjZEZ4MGUxeHlYRzVjZEZ4MFhIUWtabWxsYkdRZ1BTQWtZMjl1ZEdGcGJtVnlMbVpwYm1Rb1hDSnpaV3hsWTNSY0lpazdYSEpjYmx4MFhIUmNkR1pwWld4a1RtRnRaU0E5SUNSbWFXVnNaQzVoZEhSeUtGd2libUZ0WlZ3aUtTNXlaWEJzWVdObEtDZGJYU2NzSUNjbktUdGNjbHh1WEhSY2RGeDBYSEpjYmx4MFhIUmNkR1pwWld4a1ZtRnNJRDBnYzJWc1ppNW5aWFJUWld4bFkzUldZV3dvSkdacFpXeGtLVHNnWEhKY2JseDBYSFI5WEhKY2JseDBYSFJsYkhObElHbG1LR2x1Y0hWMFZIbHdaVDA5WENKdGRXeDBhWE5sYkdWamRGd2lLVnh5WEc1Y2RGeDBlMXh5WEc1Y2RGeDBYSFFrWm1sbGJHUWdQU0FrWTI5dWRHRnBibVZ5TG1acGJtUW9YQ0p6Wld4bFkzUmNJaWs3WEhKY2JseDBYSFJjZEdacFpXeGtUbUZ0WlNBOUlDUm1hV1ZzWkM1aGRIUnlLRndpYm1GdFpWd2lLUzV5WlhCc1lXTmxLQ2RiWFNjc0lDY25LVHRjY2x4dVhIUmNkRngwZG1GeUlHOXdaWEpoZEc5eUlEMGdKR1pwWld4a0xtRjBkSElvWENKa1lYUmhMVzl3WlhKaGRHOXlYQ0lwTzF4eVhHNWNkRngwWEhSY2NseHVYSFJjZEZ4MFptbGxiR1JXWVd3Z1BTQnpaV3htTG1kbGRFMTFiSFJwVTJWc1pXTjBWbUZzS0NSbWFXVnNaQ3dnWENKdmNsd2lLVHRjY2x4dVhIUmNkRngwWEhKY2JseDBYSFI5WEhKY2JseDBYSFJsYkhObElHbG1LR2x1Y0hWMFZIbHdaVDA5WENKamFHVmphMkp2ZUZ3aUtWeHlYRzVjZEZ4MGUxeHlYRzVjZEZ4MFhIUWtabWxsYkdRZ1BTQWtZMjl1ZEdGcGJtVnlMbVpwYm1Rb1hDSjFiQ0ErSUd4cElHbHVjSFYwT21Ob1pXTnJZbTk0WENJcE8xeHlYRzVjZEZ4MFhIUmNjbHh1WEhSY2RGeDBhV1lvSkdacFpXeGtMbXhsYm1kMGFENHdLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFptbGxiR1JPWVcxbElEMGdKR1pwWld4a0xtRjBkSElvWENKdVlXMWxYQ0lwTG5KbGNHeGhZMlVvSjF0ZEp5d2dKeWNwTzF4eVhHNWNkRngwWEhSY2RGeDBYSFJjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBYSFJjZEhaaGNpQnZjR1Z5WVhSdmNpQTlJQ1JqYjI1MFlXbHVaWEl1Wm1sdVpDaGNJajRnZFd4Y0lpa3VZWFIwY2loY0ltUmhkR0V0YjNCbGNtRjBiM0pjSWlrN1hISmNibHgwWEhSY2RGeDBabWxsYkdSV1lXd2dQU0J6Wld4bUxtZGxkRU5vWldOclltOTRWbUZzS0NSbWFXVnNaQ3dnWENKdmNsd2lLVHRjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSY2NseHVYSFJjZEgxY2NseHVYSFJjZEdWc2MyVWdhV1lvYVc1d2RYUlVlWEJsUFQxY0luSmhaR2x2WENJcFhISmNibHgwWEhSN1hISmNibHgwWEhSY2RGeHlYRzVjZEZ4MFhIUWtabWxsYkdRZ1BTQWtZMjl1ZEdGcGJtVnlMbVpwYm1Rb1hDSjFiQ0ErSUd4cElHbHVjSFYwT25KaFpHbHZYQ0lwTzF4eVhHNWNkRngwWEhSY2RGeDBYSFJjY2x4dVhIUmNkRngwYVdZb0pHWnBaV3hrTG14bGJtZDBhRDR3S1Z4eVhHNWNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBabWxsYkdST1lXMWxJRDBnSkdacFpXeGtMbUYwZEhJb1hDSnVZVzFsWENJcExuSmxjR3hoWTJVb0oxdGRKeXdnSnljcE8xeHlYRzVjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBYSFJjZEdacFpXeGtWbUZzSUQwZ2MyVnNaaTVuWlhSU1lXUnBiMVpoYkNna1ptbGxiR1FwTzF4eVhHNWNkRngwWEhSOVhISmNibHgwWEhSOVhISmNibHgwWEhSY2NseHVYSFJjZEdsbUtIUjVjR1Z2WmlobWFXVnNaRlpoYkNraFBWd2lkVzVrWldacGJtVmtYQ0lwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdsbUtHWnBaV3hrVm1Gc0lUMWNJbHdpS1Z4eVhHNWNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBkbUZ5SUdacFpXeGtVMngxWnlBOUlGd2lYQ0k3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBhV1lvWm1sbGJHUk9ZVzFsUFQxY0lsOXpabDloZFhSb2IzSmNJaWxjY2x4dVhIUmNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBYSFJtYVdWc1pGTnNkV2NnUFNCY0ltRjFkR2h2Y25OY0lqdGNjbHh1WEhSY2RGeDBYSFI5WEhKY2JseDBYSFJjZEZ4MFpXeHpaU0JwWmlobWFXVnNaRTVoYldVOVBWd2lYM05tWDNOdmNuUmZiM0prWlhKY0lpbGNjbHh1WEhSY2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFhIUm1hV1ZzWkZOc2RXY2dQU0JjSW5OdmNuUmZiM0prWlhKY0lqdGNjbHh1WEhSY2RGeDBYSFI5WEhKY2JseDBYSFJjZEZ4MFpXeHpaU0JwWmlobWFXVnNaRTVoYldVOVBWd2lYM05tWDNCd2NGd2lLVnh5WEc1Y2RGeDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUmNkR1pwWld4a1UyeDFaeUE5SUZ3aVgzTm1YM0J3Y0Z3aU8xeHlYRzVjZEZ4MFhIUmNkSDFjY2x4dVhIUmNkRngwWEhSbGJITmxJR2xtS0dacFpXeGtUbUZ0WlQwOVhDSmZjMlpmY0c5emRGOTBlWEJsWENJcFhISmNibHgwWEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZEZ4MFptbGxiR1JUYkhWbklEMGdYQ0p3YjNOMFgzUjVjR1Z6WENJN1hISmNibHgwWEhSY2RGeDBmVnh5WEc1Y2RGeDBYSFJjZEdWc2MyVmNjbHh1WEhSY2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBmVnh5WEc1Y2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RHbG1LR1pwWld4a1UyeDFaeUU5WENKY0lpbGNjbHh1WEhSY2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFhIUXZMM05sYkdZdWRYSnNYMk52YlhCdmJtVnVkSE1nS3owZ1hDSW1YQ0lyWm1sbGJHUlRiSFZuSzF3aVBWd2lLMlpwWld4a1ZtRnNPMXh5WEc1Y2RGeDBYSFJjZEZ4MGMyVnNaaTUxY214ZmNHRnlZVzF6VzJacFpXeGtVMngxWjEwZ1BTQm1hV1ZzWkZaaGJEdGNjbHh1WEhSY2RGeDBYSFI5WEhKY2JseDBYSFJjZEgxY2NseHVYSFJjZEgxY2NseHVYSFJjZEZ4eVhHNWNkSDBzWEhKY2JseDBjSEp2WTJWemMxQnZjM1JVZVhCbElEb2dablZ1WTNScGIyNG9KSFJvYVhNcGUxeHlYRzVjZEZ4MFhISmNibHgwWEhSMGFHbHpMbkJ5YjJObGMzTkJkWFJvYjNJb0pIUm9hWE1wTzF4eVhHNWNkRngwWEhKY2JseDBmU3hjY2x4dVhIUndjbTlqWlhOelVHOXpkRTFsZEdFNklHWjFibU4wYVc5dUtDUmpiMjUwWVdsdVpYSXBYSEpjYmx4MGUxeHlYRzVjZEZ4MGRtRnlJSE5sYkdZZ1BTQjBhR2x6TzF4eVhHNWNkRngwWEhKY2JseDBYSFIyWVhJZ1ptbGxiR1JVZVhCbElEMGdKR052Ym5SaGFXNWxjaTVoZEhSeUtGd2laR0YwWVMxelppMW1hV1ZzWkMxMGVYQmxYQ0lwTzF4eVhHNWNkRngwZG1GeUlHbHVjSFYwVkhsd1pTQTlJQ1JqYjI1MFlXbHVaWEl1WVhSMGNpaGNJbVJoZEdFdGMyWXRabWxsYkdRdGFXNXdkWFF0ZEhsd1pWd2lLVHRjY2x4dVhIUmNkSFpoY2lCdFpYUmhWSGx3WlNBOUlDUmpiMjUwWVdsdVpYSXVZWFIwY2loY0ltUmhkR0V0YzJZdGJXVjBZUzEwZVhCbFhDSXBPMXh5WEc1Y2NseHVYSFJjZEhaaGNpQm1hV1ZzWkZaaGJDQTlJRndpWENJN1hISmNibHgwWEhSMllYSWdKR1pwWld4a08xeHlYRzVjZEZ4MGRtRnlJR1pwWld4a1RtRnRaU0E5SUZ3aVhDSTdYSEpjYmx4MFhIUmNjbHh1WEhSY2RHbG1LRzFsZEdGVWVYQmxQVDFjSW01MWJXSmxjbHdpS1Z4eVhHNWNkRngwZTF4eVhHNWNkRngwWEhScFppaHBibkIxZEZSNWNHVTlQVndpY21GdVoyVXRiblZ0WW1WeVhDSXBYSEpjYmx4MFhIUmNkSHRjY2x4dVhIUmNkRngwWEhRa1ptbGxiR1FnUFNBa1kyOXVkR0ZwYm1WeUxtWnBibVFvWENJdWMyWXRiV1YwWVMxeVlXNW5aUzF1ZFcxaVpYSWdhVzV3ZFhSY0lpazdYSEpjYmx4MFhIUmNkRngwWEhKY2JseDBYSFJjZEZ4MGRtRnlJSFpoYkhWbGN5QTlJRnRkTzF4eVhHNWNkRngwWEhSY2RDUm1hV1ZzWkM1bFlXTm9LR1oxYm1OMGFXOXVLQ2w3WEhKY2JseDBYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBYSFJjZEhaaGJIVmxjeTV3ZFhOb0tDUW9kR2hwY3lrdWRtRnNLQ2twTzF4eVhHNWNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkSDBwTzF4eVhHNWNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkR1pwWld4a1ZtRnNJRDBnZG1Gc2RXVnpMbXB2YVc0b1hDSXJYQ0lwTzF4eVhHNWNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUjlYSEpjYmx4MFhIUmNkR1ZzYzJVZ2FXWW9hVzV3ZFhSVWVYQmxQVDFjSW5KaGJtZGxMWE5zYVdSbGNsd2lLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MEpHWnBaV3hrSUQwZ0pHTnZiblJoYVc1bGNpNW1hVzVrS0Z3aUxuTm1MVzFsZEdFdGNtRnVaMlV0YzJ4cFpHVnlJR2x1Y0hWMFhDSXBPMXh5WEc1Y2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RDOHZaMlYwSUdGdWVTQnVkVzFpWlhJZ1ptOXliV0YwZEdsdVp5QnpkSFZtWmx4eVhHNWNkRngwWEhSY2RIWmhjaUFrYldWMFlWOXlZVzVuWlNBOUlDUmpiMjUwWVdsdVpYSXVabWx1WkNoY0lpNXpaaTF0WlhSaExYSmhibWRsTFhOc2FXUmxjbHdpS1R0Y2NseHVYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBYSFIyWVhJZ1pHVmphVzFoYkY5d2JHRmpaWE1nUFNBa2JXVjBZVjl5WVc1blpTNWhkSFJ5S0Z3aVpHRjBZUzFrWldOcGJXRnNMWEJzWVdObGMxd2lLVHRjY2x4dVhIUmNkRngwWEhSMllYSWdkR2h2ZFhOaGJtUmZjMlZ3WlhKaGRHOXlJRDBnSkcxbGRHRmZjbUZ1WjJVdVlYUjBjaWhjSW1SaGRHRXRkR2h2ZFhOaGJtUXRjMlZ3WlhKaGRHOXlYQ0lwTzF4eVhHNWNkRngwWEhSY2RIWmhjaUJrWldOcGJXRnNYM05sY0dWeVlYUnZjaUE5SUNSdFpYUmhYM0poYm1kbExtRjBkSElvWENKa1lYUmhMV1JsWTJsdFlXd3RjMlZ3WlhKaGRHOXlYQ0lwTzF4eVhHNWNjbHh1WEhSY2RGeDBYSFIyWVhJZ1ptbGxiR1JmWm05eWJXRjBJRDBnZDA1MWJXSW9lMXh5WEc1Y2RGeDBYSFJjZEZ4MGJXRnlhem9nWkdWamFXMWhiRjl6WlhCbGNtRjBiM0lzWEhKY2JseDBYSFJjZEZ4MFhIUmtaV05wYldGc2N6b2djR0Z5YzJWR2JHOWhkQ2hrWldOcGJXRnNYM0JzWVdObGN5a3NYSEpjYmx4MFhIUmNkRngwWEhSMGFHOTFjMkZ1WkRvZ2RHaHZkWE5oYm1SZmMyVndaWEpoZEc5eVhISmNibHgwWEhSY2RGeDBmU2s3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBkbUZ5SUhaaGJIVmxjeUE5SUZ0ZE8xeHlYRzVjY2x4dVhISmNibHgwWEhSY2RGeDBkbUZ5SUhOc2FXUmxjbDl2WW1wbFkzUWdQU0FrWTI5dWRHRnBibVZ5TG1acGJtUW9YQ0l1YldWMFlTMXpiR2xrWlhKY0lpbGJNRjA3WEhKY2JseDBYSFJjZEZ4MEx5OTJZV3dnWm5KdmJTQnpiR2xrWlhJZ2IySnFaV04wWEhKY2JseDBYSFJjZEZ4MGRtRnlJSE5zYVdSbGNsOTJZV3dnUFNCemJHbGtaWEpmYjJKcVpXTjBMbTV2VldsVGJHbGtaWEl1WjJWMEtDazdYSEpjYmx4eVhHNWNkRngwWEhSY2RIWmhiSFZsY3k1d2RYTm9LR1pwWld4a1gyWnZjbTFoZEM1bWNtOXRLSE5zYVdSbGNsOTJZV3hiTUYwcEtUdGNjbHh1WEhSY2RGeDBYSFIyWVd4MVpYTXVjSFZ6YUNobWFXVnNaRjltYjNKdFlYUXVabkp2YlNoemJHbGtaWEpmZG1Gc1d6RmRLU2s3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBabWxsYkdSV1lXd2dQU0IyWVd4MVpYTXVhbTlwYmloY0lpdGNJaWs3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBabWxsYkdST1lXMWxJRDBnSkcxbGRHRmZjbUZ1WjJVdVlYUjBjaWhjSW1SaGRHRXRjMll0Wm1sbGJHUXRibUZ0WlZ3aUtUdGNjbHh1WEhSY2RGeDBYSFJjY2x4dVhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmxiSE5sSUdsbUtHbHVjSFYwVkhsd1pUMDlYQ0p5WVc1blpTMXlZV1JwYjF3aUtWeHlYRzVjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwSkdacFpXeGtJRDBnSkdOdmJuUmhhVzVsY2k1bWFXNWtLRndpTG5ObUxXbHVjSFYwTFhKaGJtZGxMWEpoWkdsdlhDSXBPMXh5WEc1Y2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RHbG1LQ1JtYVdWc1pDNXNaVzVuZEdnOVBUQXBYSEpjYmx4MFhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RGeDBMeTkwYUdWdUlIUnllU0JoWjJGcGJpd2dkMlVnYlhWemRDQmlaU0IxYzJsdVp5QmhJSE5wYm1kc1pTQm1hV1ZzWkZ4eVhHNWNkRngwWEhSY2RGeDBKR1pwWld4a0lEMGdKR052Ym5SaGFXNWxjaTVtYVc1a0tGd2lQaUIxYkZ3aUtUdGNjbHh1WEhSY2RGeDBYSFI5WEhKY2JseHlYRzVjZEZ4MFhIUmNkSFpoY2lBa2JXVjBZVjl5WVc1blpTQTlJQ1JqYjI1MFlXbHVaWEl1Wm1sdVpDaGNJaTV6WmkxdFpYUmhMWEpoYm1kbFhDSXBPMXh5WEc1Y2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RDOHZkR2hsY21VZ2FYTWdZVzRnWld4bGJXVnVkQ0IzYVhSb0lHRWdabkp2YlM5MGJ5QmpiR0Z6Y3lBdElITnZJSGRsSUc1bFpXUWdkRzhnWjJWMElIUm9aU0IyWVd4MVpYTWdiMllnZEdobElHWnliMjBnSmlCMGJ5QnBibkIxZENCbWFXVnNaSE1nYzJWd1pYSmhkR1ZzZVZ4eVhHNWNkRngwWEhSY2RHbG1LQ1JtYVdWc1pDNXNaVzVuZEdnK01DbGNjbHh1WEhSY2RGeDBYSFI3WEhSY2NseHVYSFJjZEZ4MFhIUmNkSFpoY2lCbWFXVnNaRjkyWVd4eklEMGdXMTA3WEhKY2JseDBYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBYSFJjZENSbWFXVnNaQzVsWVdOb0tHWjFibU4wYVc5dUtDbDdYSEpjYmx4MFhIUmNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkRngwWEhSMllYSWdKSEpoWkdsdmN5QTlJQ1FvZEdocGN5a3VabWx1WkNoY0lpNXpaaTFwYm5CMWRDMXlZV1JwYjF3aUtUdGNjbHh1WEhSY2RGeDBYSFJjZEZ4MFptbGxiR1JmZG1Gc2N5NXdkWE5vS0hObGJHWXVaMlYwVFdWMFlWSmhaR2x2Vm1Gc0tDUnlZV1JwYjNNcEtUdGNjbHh1WEhSY2RGeDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBYSFI5S1R0Y2NseHVYSFJjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBYSFJjZEZ4MEx5OXdjbVYyWlc1MElITmxZMjl1WkNCdWRXMWlaWElnWm5KdmJTQmlaV2x1WnlCc2IzZGxjaUIwYUdGdUlIUm9aU0JtYVhKemRGeHlYRzVjZEZ4MFhIUmNkRngwYVdZb1ptbGxiR1JmZG1Gc2N5NXNaVzVuZEdnOVBUSXBYSEpjYmx4MFhIUmNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBYSFJjZEdsbUtFNTFiV0psY2lobWFXVnNaRjkyWVd4eld6RmRLVHhPZFcxaVpYSW9abWxsYkdSZmRtRnNjMXN3WFNrcFhISmNibHgwWEhSY2RGeDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUmNkRngwWEhSbWFXVnNaRjkyWVd4eld6RmRJRDBnWm1sbGJHUmZkbUZzYzFzd1hUdGNjbHh1WEhSY2RGeDBYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmNkRngwZlZ4eVhHNWNkRngwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwWEhSbWFXVnNaRlpoYkNBOUlHWnBaV3hrWDNaaGJITXVhbTlwYmloY0lpdGNJaWs3WEhKY2JseDBYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmNkRngwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwYVdZb0pHWnBaV3hrTG14bGJtZDBhRDA5TVNsY2NseHVYSFJjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwWEhSbWFXVnNaRTVoYldVZ1BTQWtabWxsYkdRdVptbHVaQ2hjSWk1elppMXBibkIxZEMxeVlXUnBiMXdpS1M1aGRIUnlLRndpYm1GdFpWd2lLUzV5WlhCc1lXTmxLQ2RiWFNjc0lDY25LVHRjY2x4dVhIUmNkRngwWEhSOVhISmNibHgwWEhSY2RGeDBaV3h6WlZ4eVhHNWNkRngwWEhSY2RIdGNjbHh1WEhSY2RGeDBYSFJjZEdacFpXeGtUbUZ0WlNBOUlDUnRaWFJoWDNKaGJtZGxMbUYwZEhJb1hDSmtZWFJoTFhObUxXWnBaV3hrTFc1aGJXVmNJaWs3WEhKY2JseDBYSFJjZEZ4MGZWeHlYRzVjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSbGJITmxJR2xtS0dsdWNIVjBWSGx3WlQwOVhDSnlZVzVuWlMxelpXeGxZM1JjSWlsY2NseHVYSFJjZEZ4MGUxeHlYRzVjZEZ4MFhIUmNkQ1JtYVdWc1pDQTlJQ1JqYjI1MFlXbHVaWEl1Wm1sdVpDaGNJaTV6WmkxcGJuQjFkQzF6Wld4bFkzUmNJaWs3WEhKY2JseDBYSFJjZEZ4MGRtRnlJQ1J0WlhSaFgzSmhibWRsSUQwZ0pHTnZiblJoYVc1bGNpNW1hVzVrS0Z3aUxuTm1MVzFsZEdFdGNtRnVaMlZjSWlrN1hISmNibHgwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwTHk5MGFHVnlaU0JwY3lCaGJpQmxiR1Z0Wlc1MElIZHBkR2dnWVNCbWNtOXRMM1J2SUdOc1lYTnpJQzBnYzI4Z2QyVWdibVZsWkNCMGJ5Qm5aWFFnZEdobElIWmhiSFZsY3lCdlppQjBhR1VnWm5KdmJTQW1JSFJ2SUdsdWNIVjBJR1pwWld4a2N5QnpaWEJsY21GMFpXeDVYSEpjYmx4MFhIUmNkRngwWEhKY2JseDBYSFJjZEZ4MGFXWW9KR1pwWld4a0xteGxibWQwYUQ0d0tWeHlYRzVjZEZ4MFhIUmNkSHRjY2x4dVhIUmNkRngwWEhSY2RIWmhjaUJtYVdWc1pGOTJZV3h6SUQwZ1cxMDdYSEpjYmx4MFhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MFhIUmNkQ1JtYVdWc1pDNWxZV05vS0daMWJtTjBhVzl1S0NsN1hISmNibHgwWEhSY2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RGeDBYSFIyWVhJZ0pIUm9hWE1nUFNBa0tIUm9hWE1wTzF4eVhHNWNkRngwWEhSY2RGeDBYSFJtYVdWc1pGOTJZV3h6TG5CMWMyZ29jMlZzWmk1blpYUk5aWFJoVTJWc1pXTjBWbUZzS0NSMGFHbHpLU2s3WEhKY2JseDBYSFJjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBYSFJjZEZ4MGZTazdYSEpjYmx4MFhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MFhIUmNkQzh2Y0hKbGRtVnVkQ0J6WldOdmJtUWdiblZ0WW1WeUlHWnliMjBnWW1WcGJtY2diRzkzWlhJZ2RHaGhiaUIwYUdVZ1ptbHljM1JjY2x4dVhIUmNkRngwWEhSY2RHbG1LR1pwWld4a1gzWmhiSE11YkdWdVozUm9QVDB5S1Z4eVhHNWNkRngwWEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZEZ4MFhIUnBaaWhPZFcxaVpYSW9abWxsYkdSZmRtRnNjMXN4WFNrOFRuVnRZbVZ5S0dacFpXeGtYM1poYkhOYk1GMHBLVnh5WEc1Y2RGeDBYSFJjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwWEhSY2RGeDBabWxsYkdSZmRtRnNjMXN4WFNBOUlHWnBaV3hrWDNaaGJITmJNRjA3WEhKY2JseDBYSFJjZEZ4MFhIUmNkSDFjY2x4dVhIUmNkRngwWEhSY2RIMWNjbHh1WEhSY2RGeDBYSFJjZEZ4eVhHNWNkRngwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwWEhSbWFXVnNaRlpoYkNBOUlHWnBaV3hrWDNaaGJITXVhbTlwYmloY0lpdGNJaWs3WEhKY2JseDBYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmNkRngwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwYVdZb0pHWnBaV3hrTG14bGJtZDBhRDA5TVNsY2NseHVYSFJjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwWEhSbWFXVnNaRTVoYldVZ1BTQWtabWxsYkdRdVlYUjBjaWhjSW01aGJXVmNJaWt1Y21Wd2JHRmpaU2duVzEwbkxDQW5KeWs3WEhKY2JseDBYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUmNkR1ZzYzJWY2NseHVYSFJjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwWEhSbWFXVnNaRTVoYldVZ1BTQWtiV1YwWVY5eVlXNW5aUzVoZEhSeUtGd2laR0YwWVMxelppMW1hV1ZzWkMxdVlXMWxYQ0lwTzF4eVhHNWNkRngwWEhSY2RIMWNjbHh1WEhSY2RGeDBYSFJjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSbGJITmxJR2xtS0dsdWNIVjBWSGx3WlQwOVhDSnlZVzVuWlMxamFHVmphMkp2ZUZ3aUtWeHlYRzVjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwSkdacFpXeGtJRDBnSkdOdmJuUmhhVzVsY2k1bWFXNWtLRndpZFd3Z1BpQnNhU0JwYm5CMWREcGphR1ZqYTJKdmVGd2lLVHRjY2x4dVhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MFhIUnBaaWdrWm1sbGJHUXViR1Z1WjNSb1BqQXBYSEpjYmx4MFhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RGeDBabWxsYkdSV1lXd2dQU0J6Wld4bUxtZGxkRU5vWldOclltOTRWbUZzS0NSbWFXVnNaQ3dnWENKaGJtUmNJaWs3WEhKY2JseDBYSFJjZEZ4MGZWeHlYRzVjZEZ4MFhIUjlYSEpjYmx4MFhIUmNkRnh5WEc1Y2RGeDBYSFJwWmlobWFXVnNaRTVoYldVOVBWd2lYQ0lwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUm1hV1ZzWkU1aGJXVWdQU0FrWm1sbGJHUXVZWFIwY2loY0ltNWhiV1ZjSWlrdWNtVndiR0ZqWlNnblcxMG5MQ0FuSnlrN1hISmNibHgwWEhSY2RIMWNjbHh1WEhSY2RIMWNjbHh1WEhSY2RHVnNjMlVnYVdZb2JXVjBZVlI1Y0dVOVBWd2lZMmh2YVdObFhDSXBYSEpjYmx4MFhIUjdYSEpjYmx4MFhIUmNkR2xtS0dsdWNIVjBWSGx3WlQwOVhDSnpaV3hsWTNSY0lpbGNjbHh1WEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZENSbWFXVnNaQ0E5SUNSamIyNTBZV2x1WlhJdVptbHVaQ2hjSW5ObGJHVmpkRndpS1R0Y2NseHVYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBYSFJtYVdWc1pGWmhiQ0E5SUhObGJHWXVaMlYwVFdWMFlWTmxiR1ZqZEZaaGJDZ2tabWxsYkdRcE95QmNjbHh1WEhSY2RGeDBYSFJjY2x4dVhIUmNkRngwZlZ4eVhHNWNkRngwWEhSbGJITmxJR2xtS0dsdWNIVjBWSGx3WlQwOVhDSnRkV3gwYVhObGJHVmpkRndpS1Z4eVhHNWNkRngwWEhSN1hISmNibHgwWEhSY2RGeDBKR1pwWld4a0lEMGdKR052Ym5SaGFXNWxjaTVtYVc1a0tGd2ljMlZzWldOMFhDSXBPMXh5WEc1Y2RGeDBYSFJjZEhaaGNpQnZjR1Z5WVhSdmNpQTlJQ1JtYVdWc1pDNWhkSFJ5S0Z3aVpHRjBZUzF2Y0dWeVlYUnZjbHdpS1R0Y2NseHVYSFJjZEZ4MFhIUmNjbHh1WEhSY2RGeDBYSFJtYVdWc1pGWmhiQ0E5SUhObGJHWXVaMlYwVFdWMFlVMTFiSFJwVTJWc1pXTjBWbUZzS0NSbWFXVnNaQ3dnYjNCbGNtRjBiM0lwTzF4eVhHNWNkRngwWEhSOVhISmNibHgwWEhSY2RHVnNjMlVnYVdZb2FXNXdkWFJVZVhCbFBUMWNJbU5vWldOclltOTRYQ0lwWEhKY2JseDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUWtabWxsYkdRZ1BTQWtZMjl1ZEdGcGJtVnlMbVpwYm1Rb1hDSjFiQ0ErSUd4cElHbHVjSFYwT21Ob1pXTnJZbTk0WENJcE8xeHlYRzVjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBYSFJjZEdsbUtDUm1hV1ZzWkM1c1pXNW5kR2crTUNsY2NseHVYSFJjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwWEhSMllYSWdiM0JsY21GMGIzSWdQU0FrWTI5dWRHRnBibVZ5TG1acGJtUW9YQ0krSUhWc1hDSXBMbUYwZEhJb1hDSmtZWFJoTFc5d1pYSmhkRzl5WENJcE8xeHlYRzVjZEZ4MFhIUmNkRngwWm1sbGJHUldZV3dnUFNCelpXeG1MbWRsZEUxbGRHRkRhR1ZqYTJKdmVGWmhiQ2drWm1sbGJHUXNJRzl3WlhKaGRHOXlLVHRjY2x4dVhIUmNkRngwWEhSOVhISmNibHgwWEhSY2RIMWNjbHh1WEhSY2RGeDBaV3h6WlNCcFppaHBibkIxZEZSNWNHVTlQVndpY21Ga2FXOWNJaWxjY2x4dVhIUmNkRngwZTF4eVhHNWNkRngwWEhSY2RDUm1hV1ZzWkNBOUlDUmpiMjUwWVdsdVpYSXVabWx1WkNoY0luVnNJRDRnYkdrZ2FXNXdkWFE2Y21Ga2FXOWNJaWs3WEhKY2JseDBYSFJjZEZ4MFhISmNibHgwWEhSY2RGeDBhV1lvSkdacFpXeGtMbXhsYm1kMGFENHdLVnh5WEc1Y2RGeDBYSFJjZEh0Y2NseHVYSFJjZEZ4MFhIUmNkR1pwWld4a1ZtRnNJRDBnYzJWc1ppNW5aWFJOWlhSaFVtRmthVzlXWVd3b0pHWnBaV3hrS1R0Y2NseHVYSFJjZEZ4MFhIUjlYSEpjYmx4MFhIUmNkSDFjY2x4dVhIUmNkRngwWEhKY2JseDBYSFJjZEdacFpXeGtWbUZzSUQwZ1pXNWpiMlJsVlZKSlEyOXRjRzl1Wlc1MEtHWnBaV3hrVm1Gc0tUdGNjbHh1WEhSY2RGeDBhV1lvZEhsd1pXOW1LQ1JtYVdWc1pDa2hQVDFjSW5WdVpHVm1hVzVsWkZ3aUtWeHlYRzVjZEZ4MFhIUjdYSEpjYmx4MFhIUmNkRngwYVdZb0pHWnBaV3hrTG14bGJtZDBhRDR3S1Z4eVhHNWNkRngwWEhSY2RIdGNjbHh1WEhSY2RGeDBYSFJjZEdacFpXeGtUbUZ0WlNBOUlDUm1hV1ZzWkM1aGRIUnlLRndpYm1GdFpWd2lLUzV5WlhCc1lXTmxLQ2RiWFNjc0lDY25LVHRjY2x4dVhIUmNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkRngwTHk5bWIzSWdkR2h2YzJVZ2QyaHZJR2x1YzJsemRDQnZiaUIxYzJsdVp5QW1JR0Z0Y0dWeWMyRnVaSE1nYVc0Z2RHaGxJRzVoYldVZ2IyWWdkR2hsSUdOMWMzUnZiU0JtYVdWc1pDQW9JU2xjY2x4dVhIUmNkRngwWEhSY2RHWnBaV3hrVG1GdFpTQTlJQ2htYVdWc1pFNWhiV1VwTzF4eVhHNWNkRngwWEhSY2RIMWNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBYSFJjY2x4dVhIUmNkSDFjY2x4dVhIUmNkR1ZzYzJVZ2FXWW9iV1YwWVZSNWNHVTlQVndpWkdGMFpWd2lLVnh5WEc1Y2RGeDBlMXh5WEc1Y2RGeDBYSFJ6Wld4bUxuQnliMk5sYzNOUWIzTjBSR0YwWlNna1kyOXVkR0ZwYm1WeUtUdGNjbHh1WEhSY2RIMWNjbHh1WEhSY2RGeHlYRzVjZEZ4MGFXWW9kSGx3Wlc5bUtHWnBaV3hrVm1Gc0tTRTlYQ0oxYm1SbFptbHVaV1JjSWlsY2NseHVYSFJjZEh0Y2NseHVYSFJjZEZ4MGFXWW9abWxsYkdSV1lXd2hQVndpWENJcFhISmNibHgwWEhSY2RIdGNjbHh1WEhSY2RGeDBYSFF2TDNObGJHWXVkWEpzWDJOdmJYQnZibVZ1ZEhNZ0t6MGdYQ0ltWENJclpXNWpiMlJsVlZKSlEyOXRjRzl1Wlc1MEtHWnBaV3hrVG1GdFpTa3JYQ0k5WENJcktHWnBaV3hrVm1Gc0tUdGNjbHh1WEhSY2RGeDBYSFJ6Wld4bUxuVnliRjl3WVhKaGJYTmJaVzVqYjJSbFZWSkpRMjl0Y0c5dVpXNTBLR1pwWld4a1RtRnRaU2xkSUQwZ0tHWnBaV3hrVm1Gc0tUdGNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBmVnh5WEc1Y2RIMHNYSEpjYmx4MGNISnZZMlZ6YzFCdmMzUkVZWFJsT2lCbWRXNWpkR2x2Ymlna1kyOXVkR0ZwYm1WeUtWeHlYRzVjZEh0Y2NseHVYSFJjZEhaaGNpQnpaV3htSUQwZ2RHaHBjenRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBkbUZ5SUdacFpXeGtWSGx3WlNBOUlDUmpiMjUwWVdsdVpYSXVZWFIwY2loY0ltUmhkR0V0YzJZdFptbGxiR1F0ZEhsd1pWd2lLVHRjY2x4dVhIUmNkSFpoY2lCcGJuQjFkRlI1Y0dVZ1BTQWtZMjl1ZEdGcGJtVnlMbUYwZEhJb1hDSmtZWFJoTFhObUxXWnBaV3hrTFdsdWNIVjBMWFI1Y0dWY0lpazdYSEpjYmx4MFhIUmNjbHh1WEhSY2RIWmhjaUFrWm1sbGJHUTdYSEpjYmx4MFhIUjJZWElnWm1sbGJHUk9ZVzFsSUQwZ1hDSmNJanRjY2x4dVhIUmNkSFpoY2lCbWFXVnNaRlpoYkNBOUlGd2lYQ0k3WEhKY2JseDBYSFJjY2x4dVhIUmNkQ1JtYVdWc1pDQTlJQ1JqYjI1MFlXbHVaWEl1Wm1sdVpDaGNJblZzSUQ0Z2JHa2dhVzV3ZFhRNmRHVjRkRndpS1R0Y2NseHVYSFJjZEdacFpXeGtUbUZ0WlNBOUlDUm1hV1ZzWkM1aGRIUnlLRndpYm1GdFpWd2lLUzV5WlhCc1lXTmxLQ2RiWFNjc0lDY25LVHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBkbUZ5SUdSaGRHVnpJRDBnVzEwN1hISmNibHgwWEhRa1ptbGxiR1F1WldGamFDaG1kVzVqZEdsdmJpZ3BlMXh5WEc1Y2RGeDBYSFJjY2x4dVhIUmNkRngwWkdGMFpYTXVjSFZ6YUNna0tIUm9hWE1wTG5aaGJDZ3BLVHRjY2x4dVhIUmNkRnh5WEc1Y2RGeDBmU2s3WEhKY2JseDBYSFJjY2x4dVhIUmNkR2xtS0NSbWFXVnNaQzVzWlc1bmRHZzlQVElwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZEdsbUtDaGtZWFJsYzFzd1hTRTlYQ0pjSWlsOGZDaGtZWFJsYzFzeFhTRTlYQ0pjSWlrcFhISmNibHgwWEhSY2RIdGNjbHh1WEhSY2RGeDBYSFJtYVdWc1pGWmhiQ0E5SUdSaGRHVnpMbXB2YVc0b1hDSXJYQ0lwTzF4eVhHNWNkRngwWEhSY2RHWnBaV3hrVm1Gc0lEMGdabWxsYkdSV1lXd3VjbVZ3YkdGalpTZ3ZYRnd2TDJjc0p5Y3BPMXh5WEc1Y2RGeDBYSFI5WEhKY2JseDBYSFI5WEhKY2JseDBYSFJsYkhObElHbG1LQ1JtYVdWc1pDNXNaVzVuZEdnOVBURXBYSEpjYmx4MFhIUjdYSEpjYmx4MFhIUmNkR2xtS0dSaGRHVnpXekJkSVQxY0lsd2lLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFptbGxiR1JXWVd3Z1BTQmtZWFJsY3k1cWIybHVLRndpSzF3aUtUdGNjbHh1WEhSY2RGeDBYSFJtYVdWc1pGWmhiQ0E5SUdacFpXeGtWbUZzTG5KbGNHeGhZMlVvTDF4Y0x5OW5MQ2NuS1R0Y2NseHVYSFJjZEZ4MGZWeHlYRzVjZEZ4MGZWeHlYRzVjZEZ4MFhISmNibHgwWEhScFppaDBlWEJsYjJZb1ptbGxiR1JXWVd3cElUMWNJblZ1WkdWbWFXNWxaRndpS1Z4eVhHNWNkRngwZTF4eVhHNWNkRngwWEhScFppaG1hV1ZzWkZaaGJDRTlYQ0pjSWlsY2NseHVYSFJjZEZ4MGUxeHlYRzVjZEZ4MFhIUmNkSFpoY2lCbWFXVnNaRk5zZFdjZ1BTQmNJbHdpTzF4eVhHNWNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkR2xtS0dacFpXeGtUbUZ0WlQwOVhDSmZjMlpmY0c5emRGOWtZWFJsWENJcFhISmNibHgwWEhSY2RGeDBlMXh5WEc1Y2RGeDBYSFJjZEZ4MFptbGxiR1JUYkhWbklEMGdYQ0p3YjNOMFgyUmhkR1ZjSWp0Y2NseHVYSFJjZEZ4MFhIUjlYSEpjYmx4MFhIUmNkRngwWld4elpWeHlYRzVjZEZ4MFhIUmNkSHRjY2x4dVhIUmNkRngwWEhSY2RHWnBaV3hrVTJ4MVp5QTlJR1pwWld4a1RtRnRaVHRjY2x4dVhIUmNkRngwWEhSOVhISmNibHgwWEhSY2RGeDBYSEpjYmx4MFhIUmNkRngwYVdZb1ptbGxiR1JUYkhWbklUMWNJbHdpS1Z4eVhHNWNkRngwWEhSY2RIdGNjbHh1WEhSY2RGeDBYSFJjZEM4dmMyVnNaaTUxY214ZlkyOXRjRzl1Wlc1MGN5QXJQU0JjSWlaY0lpdG1hV1ZzWkZOc2RXY3JYQ0k5WENJclptbGxiR1JXWVd3N1hISmNibHgwWEhSY2RGeDBYSFJ6Wld4bUxuVnliRjl3WVhKaGJYTmJabWxsYkdSVGJIVm5YU0E5SUdacFpXeGtWbUZzTzF4eVhHNWNkRngwWEhSY2RIMWNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBmVnh5WEc1Y2RGeDBYSEpjYmx4MGZTeGNjbHh1WEhSd2NtOWpaWE56VkdGNGIyNXZiWGs2SUdaMWJtTjBhVzl1S0NSamIyNTBZV2x1WlhJc0lISmxkSFZ5Ymw5dlltcGxZM1FwWEhKY2JseDBlMXh5WEc0Z0lDQWdJQ0FnSUdsbUtIUjVjR1Z2WmloeVpYUjFjbTVmYjJKcVpXTjBLVDA5WENKMWJtUmxabWx1WldSY0lpbGNjbHh1SUNBZ0lDQWdJQ0I3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJSEpsZEhWeWJsOXZZbXBsWTNRZ1BTQm1ZV3h6WlR0Y2NseHVJQ0FnSUNBZ0lDQjlYSEpjYmx4eVhHNWNkRngwTHk5cFppZ3BYSFJjZEZ4MFhIUmNkRnh5WEc1Y2RGeDBMeTkyWVhJZ1ptbGxiR1JPWVcxbElEMGdKQ2gwYUdsektTNWhkSFJ5S0Z3aVpHRjBZUzF6WmkxbWFXVnNaQzF1WVcxbFhDSXBPMXh5WEc1Y2RGeDBkbUZ5SUhObGJHWWdQU0IwYUdsek8xeHlYRzVjZEZ4eVhHNWNkRngwZG1GeUlHWnBaV3hrVkhsd1pTQTlJQ1JqYjI1MFlXbHVaWEl1WVhSMGNpaGNJbVJoZEdFdGMyWXRabWxsYkdRdGRIbHdaVndpS1R0Y2NseHVYSFJjZEhaaGNpQnBibkIxZEZSNWNHVWdQU0FrWTI5dWRHRnBibVZ5TG1GMGRISW9YQ0prWVhSaExYTm1MV1pwWld4a0xXbHVjSFYwTFhSNWNHVmNJaWs3WEhKY2JseDBYSFJjY2x4dVhIUmNkSFpoY2lBa1ptbGxiR1E3WEhKY2JseDBYSFIyWVhJZ1ptbGxiR1JPWVcxbElEMGdYQ0pjSWp0Y2NseHVYSFJjZEhaaGNpQm1hV1ZzWkZaaGJDQTlJRndpWENJN1hISmNibHgwWEhSY2NseHVYSFJjZEdsbUtHbHVjSFYwVkhsd1pUMDlYQ0p6Wld4bFkzUmNJaWxjY2x4dVhIUmNkSHRjY2x4dVhIUmNkRngwSkdacFpXeGtJRDBnSkdOdmJuUmhhVzVsY2k1bWFXNWtLRndpYzJWc1pXTjBYQ0lwTzF4eVhHNWNkRngwWEhSbWFXVnNaRTVoYldVZ1BTQWtabWxsYkdRdVlYUjBjaWhjSW01aGJXVmNJaWt1Y21Wd2JHRmpaU2duVzEwbkxDQW5KeWs3WEhKY2JseDBYSFJjZEZ4eVhHNWNkRngwWEhSbWFXVnNaRlpoYkNBOUlITmxiR1l1WjJWMFUyVnNaV04wVm1Gc0tDUm1hV1ZzWkNrN0lGeHlYRzVjZEZ4MGZWeHlYRzVjZEZ4MFpXeHpaU0JwWmlocGJuQjFkRlI1Y0dVOVBWd2liWFZzZEdselpXeGxZM1JjSWlsY2NseHVYSFJjZEh0Y2NseHVYSFJjZEZ4MEpHWnBaV3hrSUQwZ0pHTnZiblJoYVc1bGNpNW1hVzVrS0Z3aWMyVnNaV04wWENJcE8xeHlYRzVjZEZ4MFhIUm1hV1ZzWkU1aGJXVWdQU0FrWm1sbGJHUXVZWFIwY2loY0ltNWhiV1ZjSWlrdWNtVndiR0ZqWlNnblcxMG5MQ0FuSnlrN1hISmNibHgwWEhSY2RIWmhjaUJ2Y0dWeVlYUnZjaUE5SUNSbWFXVnNaQzVoZEhSeUtGd2laR0YwWVMxdmNHVnlZWFJ2Y2x3aUtUdGNjbHh1WEhSY2RGeDBYSEpjYmx4MFhIUmNkR1pwWld4a1ZtRnNJRDBnYzJWc1ppNW5aWFJOZFd4MGFWTmxiR1ZqZEZaaGJDZ2tabWxsYkdRc0lHOXdaWEpoZEc5eUtUdGNjbHh1WEhSY2RIMWNjbHh1WEhSY2RHVnNjMlVnYVdZb2FXNXdkWFJVZVhCbFBUMWNJbU5vWldOclltOTRYQ0lwWEhKY2JseDBYSFI3WEhKY2JseDBYSFJjZENSbWFXVnNaQ0E5SUNSamIyNTBZV2x1WlhJdVptbHVaQ2hjSW5Wc0lENGdiR2tnYVc1d2RYUTZZMmhsWTJ0aWIzaGNJaWs3WEhKY2JseDBYSFJjZEdsbUtDUm1hV1ZzWkM1c1pXNW5kR2crTUNsY2NseHVYSFJjZEZ4MGUxeHlYRzVjZEZ4MFhIUmNkR1pwWld4a1RtRnRaU0E5SUNSbWFXVnNaQzVoZEhSeUtGd2libUZ0WlZ3aUtTNXlaWEJzWVdObEtDZGJYU2NzSUNjbktUdGNjbHh1WEhSY2RGeDBYSFJjZEZ4MFhIUmNkRngwWEhSY2NseHVYSFJjZEZ4MFhIUjJZWElnYjNCbGNtRjBiM0lnUFNBa1kyOXVkR0ZwYm1WeUxtWnBibVFvWENJK0lIVnNYQ0lwTG1GMGRISW9YQ0prWVhSaExXOXdaWEpoZEc5eVhDSXBPMXh5WEc1Y2RGeDBYSFJjZEdacFpXeGtWbUZzSUQwZ2MyVnNaaTVuWlhSRGFHVmphMkp2ZUZaaGJDZ2tabWxsYkdRc0lHOXdaWEpoZEc5eUtUdGNjbHh1WEhSY2RGeDBmVnh5WEc1Y2RGeDBmVnh5WEc1Y2RGeDBaV3h6WlNCcFppaHBibkIxZEZSNWNHVTlQVndpY21Ga2FXOWNJaWxjY2x4dVhIUmNkSHRjY2x4dVhIUmNkRngwSkdacFpXeGtJRDBnSkdOdmJuUmhhVzVsY2k1bWFXNWtLRndpZFd3Z1BpQnNhU0JwYm5CMWREcHlZV1JwYjF3aUtUdGNjbHh1WEhSY2RGeDBhV1lvSkdacFpXeGtMbXhsYm1kMGFENHdLVnh5WEc1Y2RGeDBYSFI3WEhKY2JseDBYSFJjZEZ4MFptbGxiR1JPWVcxbElEMGdKR1pwWld4a0xtRjBkSElvWENKdVlXMWxYQ0lwTG5KbGNHeGhZMlVvSjF0ZEp5d2dKeWNwTzF4eVhHNWNkRngwWEhSY2RGeHlYRzVjZEZ4MFhIUmNkR1pwWld4a1ZtRnNJRDBnYzJWc1ppNW5aWFJTWVdScGIxWmhiQ2drWm1sbGJHUXBPMXh5WEc1Y2RGeDBYSFI5WEhKY2JseDBYSFI5WEhKY2JseDBYSFJjY2x4dVhIUmNkR2xtS0hSNWNHVnZaaWhtYVdWc1pGWmhiQ2toUFZ3aWRXNWtaV1pwYm1Wa1hDSXBYSEpjYmx4MFhIUjdYSEpjYmx4MFhIUmNkR2xtS0dacFpXeGtWbUZzSVQxY0lsd2lLVnh5WEc1Y2RGeDBYSFI3WEhKY2JpQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNCcFppaHlaWFIxY201ZmIySnFaV04wUFQxMGNuVmxLVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnZTF4eVhHNGdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSEpsZEhWeWJpQjdibUZ0WlRvZ1ptbGxiR1JPWVcxbExDQjJZV3gxWlRvZ1ptbGxiR1JXWVd4OU8xeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdmVnh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnWld4elpWeHlYRzRnSUNBZ0lDQWdJQ0FnSUNBZ0lDQWdlMXh5WEc0Z0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ0lDOHZjMlZzWmk1MWNteGZZMjl0Y0c5dVpXNTBjeUFyUFNCY0lpWmNJaXRtYVdWc1pFNWhiV1VyWENJOVhDSXJabWxsYkdSV1lXdzdYSEpjYmlBZ0lDQWdJQ0FnSUNBZ0lDQWdJQ0FnSUNBZ2MyVnNaaTUxY214ZmNHRnlZVzF6VzJacFpXeGtUbUZ0WlYwZ1BTQm1hV1ZzWkZaaGJEdGNjbHh1SUNBZ0lDQWdJQ0FnSUNBZ0lDQWdJSDFjY2x4dVhISmNibHgwWEhSY2RIMWNjbHh1WEhSY2RIMWNjbHh1WEhKY2JpQWdJQ0FnSUNBZ2FXWW9jbVYwZFhKdVgyOWlhbVZqZEQwOWRISjFaU2xjY2x4dUlDQWdJQ0FnSUNCN1hISmNiaUFnSUNBZ0lDQWdJQ0FnSUhKbGRIVnliaUJtWVd4elpUdGNjbHh1SUNBZ0lDQWdJQ0I5WEhKY2JseDBmVnh5WEc1OU95SmRmUT09IiwiXHJcbm1vZHVsZS5leHBvcnRzID0ge1xyXG5cdFxyXG5cdHNlYXJjaEZvcm1zOiB7fSxcclxuXHRcclxuXHRpbml0OiBmdW5jdGlvbigpe1xyXG5cdFx0XHJcblx0XHRcclxuXHR9LFxyXG5cdGFkZFNlYXJjaEZvcm06IGZ1bmN0aW9uKGlkLCBvYmplY3Qpe1xyXG5cdFx0XHJcblx0XHR0aGlzLnNlYXJjaEZvcm1zW2lkXSA9IG9iamVjdDtcclxuXHR9LFxyXG5cdGdldFNlYXJjaEZvcm06IGZ1bmN0aW9uKGlkKVxyXG5cdHtcclxuXHRcdHJldHVybiB0aGlzLnNlYXJjaEZvcm1zW2lkXTtcdFxyXG5cdH1cclxuXHRcclxufTsiLCIoZnVuY3Rpb24gKGdsb2JhbCl7XG5cbnZhciAkIFx0XHRcdFx0PSAodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvd1snalF1ZXJ5J10gOiB0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsWydqUXVlcnknXSA6IG51bGwpO1xuXG5tb2R1bGUuZXhwb3J0cyA9IHtcblx0XG5cdGluaXQ6IGZ1bmN0aW9uKCl7XG5cdFx0JChkb2N1bWVudCkub24oXCJzZjphamF4ZmluaXNoXCIsIFwiLnNlYXJjaGFuZGZpbHRlclwiLCBmdW5jdGlvbiggZSwgZGF0YSApIHtcblx0XHRcdHZhciBkaXNwbGF5X21ldGhvZCA9IGRhdGEub2JqZWN0LmRpc3BsYXlfcmVzdWx0X21ldGhvZDtcblx0XHRcdGlmICggZGlzcGxheV9tZXRob2QgPT09ICdjdXN0b21fZWRkX3N0b3JlJyApIHtcblx0XHRcdFx0JCgnaW5wdXQuZWRkLWFkZC10by1jYXJ0JykuY3NzKCdkaXNwbGF5JywgXCJub25lXCIpO1xuXHRcdFx0XHQkKCdhLmVkZC1hZGQtdG8tY2FydCcpLmFkZENsYXNzKCdlZGQtaGFzLWpzJyk7XG5cdFx0XHR9IGVsc2UgaWYgKCBkaXNwbGF5X21ldGhvZCA9PT0gJ2N1c3RvbV9sYXlvdXRzJyApIHtcblx0XHRcdFx0aWYgKCAkKCcuY2wtbGF5b3V0JykuaGFzQ2xhc3MoICdjbC1sYXlvdXQtLW1hc29ucnknICkgKSB7XG5cdFx0XHRcdFx0Ly90aGVuIHJlLWluaXQgbWFzb25yeVxuXHRcdFx0XHRcdGNvbnN0IG1hc29ucnlDb250YWluZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCAnLmNsLWxheW91dC0tbWFzb25yeScgKTtcblx0XHRcdFx0XHRpZiAoIG1hc29ucnlDb250YWluZXIubGVuZ3RoID4gMCApIHtcblx0XHRcdFx0XHRcdGNvbnN0IGN1c3RvbUxheW91dEdyaWQgPSBuZXcgTWFzb25yeSggJy5jbC1sYXlvdXQtLW1hc29ucnknLCB7XG5cdFx0XHRcdFx0XHRcdC8vIG9wdGlvbnMuLi5cblx0XHRcdFx0XHRcdFx0aXRlbVNlbGVjdG9yOiAnLmNsLWxheW91dF9faXRlbScsXG5cdFx0XHRcdFx0XHRcdC8vY29sdW1uV2lkdGg6IDMxOVxuXHRcdFx0XHRcdFx0XHRwZXJjZW50UG9zaXRpb246IHRydWUsXG5cdFx0XHRcdFx0XHRcdC8vZ3V0dGVyOiAxMCxcblx0XHRcdFx0XHRcdFx0dHJhbnNpdGlvbkR1cmF0aW9uOiAwLFxuXHRcdFx0XHRcdFx0fSApO1xuXHRcdFx0XHRcdFx0aW1hZ2VzTG9hZGVkKCBtYXNvbnJ5Q29udGFpbmVyICkub24oICdwcm9ncmVzcycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRjdXN0b21MYXlvdXRHcmlkLmxheW91dCgpO1xuXHRcdFx0XHRcdFx0fSApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH0pO1xuXHR9LFxuXG59O1xufSkuY2FsbCh0aGlzLHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWwgOiB0eXBlb2Ygc2VsZiAhPT0gXCJ1bmRlZmluZWRcIiA/IHNlbGYgOiB0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93IDoge30pXG4vLyMgc291cmNlTWFwcGluZ1VSTD1kYXRhOmFwcGxpY2F0aW9uL2pzb247Y2hhcnNldDp1dGYtODtiYXNlNjQsZXlKMlpYSnphVzl1SWpvekxDSnpiM1Z5WTJWeklqcGJJbk55WXk5d2RXSnNhV012WVhOelpYUnpMMnB6TDJsdVkyeDFaR1Z6TDNSb2FYSmtjR0Z5ZEhrdWFuTWlYU3dpYm1GdFpYTWlPbHRkTENKdFlYQndhVzVuY3lJNklqdEJRVUZCTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQk8wRkJRMEU3UVVGRFFUdEJRVU5CTzBGQlEwRTdRVUZEUVR0QlFVTkJPMEZCUTBFN1FVRkRRVHRCUVVOQklpd2labWxzWlNJNkltZGxibVZ5WVhSbFpDNXFjeUlzSW5OdmRYSmpaVkp2YjNRaU9pSWlMQ0p6YjNWeVkyVnpRMjl1ZEdWdWRDSTZXeUpjYm5aaGNpQWtJRngwWEhSY2RGeDBQU0FvZEhsd1pXOW1JSGRwYm1SdmR5QWhQVDBnWENKMWJtUmxabWx1WldSY0lpQS9JSGRwYm1SdmQxc25hbEYxWlhKNUoxMGdPaUIwZVhCbGIyWWdaMnh2WW1Gc0lDRTlQU0JjSW5WdVpHVm1hVzVsWkZ3aUlEOGdaMnh2WW1Gc1d5ZHFVWFZsY25rblhTQTZJRzUxYkd3cE8xeHVYRzV0YjJSMWJHVXVaWGh3YjNKMGN5QTlJSHRjYmx4MFhHNWNkR2x1YVhRNklHWjFibU4wYVc5dUtDbDdYRzVjZEZ4MEpDaGtiMk4xYldWdWRDa3ViMjRvWENKelpqcGhhbUY0Wm1sdWFYTm9YQ0lzSUZ3aUxuTmxZWEpqYUdGdVpHWnBiSFJsY2x3aUxDQm1kVzVqZEdsdmJpZ2daU3dnWkdGMFlTQXBJSHRjYmx4MFhIUmNkSFpoY2lCa2FYTndiR0Y1WDIxbGRHaHZaQ0E5SUdSaGRHRXViMkpxWldOMExtUnBjM0JzWVhsZmNtVnpkV3gwWDIxbGRHaHZaRHRjYmx4MFhIUmNkR2xtSUNnZ1pHbHpjR3hoZVY5dFpYUm9iMlFnUFQwOUlDZGpkWE4wYjIxZlpXUmtYM04wYjNKbEp5QXBJSHRjYmx4MFhIUmNkRngwSkNnbmFXNXdkWFF1WldSa0xXRmtaQzEwYnkxallYSjBKeWt1WTNOektDZGthWE53YkdGNUp5d2dYQ0p1YjI1bFhDSXBPMXh1WEhSY2RGeDBYSFFrS0NkaExtVmtaQzFoWkdRdGRHOHRZMkZ5ZENjcExtRmtaRU5zWVhOektDZGxaR1F0YUdGekxXcHpKeWs3WEc1Y2RGeDBYSFI5SUdWc2MyVWdhV1lnS0NCa2FYTndiR0Y1WDIxbGRHaHZaQ0E5UFQwZ0oyTjFjM1J2YlY5c1lYbHZkWFJ6SnlBcElIdGNibHgwWEhSY2RGeDBhV1lnS0NBa0tDY3VZMnd0YkdGNWIzVjBKeWt1YUdGelEyeGhjM01vSUNkamJDMXNZWGx2ZFhRdExXMWhjMjl1Y25rbklDa2dLU0I3WEc1Y2RGeDBYSFJjZEZ4MEx5OTBhR1Z1SUhKbExXbHVhWFFnYldGemIyNXllVnh1WEhSY2RGeDBYSFJjZEdOdmJuTjBJRzFoYzI5dWNubERiMjUwWVdsdVpYSWdQU0JrYjJOMWJXVnVkQzV4ZFdWeWVWTmxiR1ZqZEc5eVFXeHNLQ0FuTG1Oc0xXeGhlVzkxZEMwdGJXRnpiMjV5ZVNjZ0tUdGNibHgwWEhSY2RGeDBYSFJwWmlBb0lHMWhjMjl1Y25sRGIyNTBZV2x1WlhJdWJHVnVaM1JvSUQ0Z01DQXBJSHRjYmx4MFhIUmNkRngwWEhSY2RHTnZibk4wSUdOMWMzUnZiVXhoZVc5MWRFZHlhV1FnUFNCdVpYY2dUV0Z6YjI1eWVTZ2dKeTVqYkMxc1lYbHZkWFF0TFcxaGMyOXVjbmtuTENCN1hHNWNkRngwWEhSY2RGeDBYSFJjZEM4dklHOXdkR2x2Ym5NdUxpNWNibHgwWEhSY2RGeDBYSFJjZEZ4MGFYUmxiVk5sYkdWamRHOXlPaUFuTG1Oc0xXeGhlVzkxZEY5ZmFYUmxiU2NzWEc1Y2RGeDBYSFJjZEZ4MFhIUmNkQzh2WTI5c2RXMXVWMmxrZEdnNklETXhPVnh1WEhSY2RGeDBYSFJjZEZ4MFhIUndaWEpqWlc1MFVHOXphWFJwYjI0NklIUnlkV1VzWEc1Y2RGeDBYSFJjZEZ4MFhIUmNkQzh2WjNWMGRHVnlPaUF4TUN4Y2JseDBYSFJjZEZ4MFhIUmNkRngwZEhKaGJuTnBkR2x2YmtSMWNtRjBhVzl1T2lBd0xGeHVYSFJjZEZ4MFhIUmNkRngwZlNBcE8xeHVYSFJjZEZ4MFhIUmNkRngwYVcxaFoyVnpURzloWkdWa0tDQnRZWE52Ym5KNVEyOXVkR0ZwYm1WeUlDa3ViMjRvSUNkd2NtOW5jbVZ6Y3ljc0lHWjFibU4wYVc5dUtDa2dlMXh1WEhSY2RGeDBYSFJjZEZ4MFhIUmpkWE4wYjIxTVlYbHZkWFJIY21sa0xteGhlVzkxZENncE8xeHVYSFJjZEZ4MFhIUmNkRngwZlNBcE8xeHVYSFJjZEZ4MFhIUmNkSDFjYmx4MFhIUmNkRngwZlZ4dVhIUmNkRngwZlZ4dVhIUmNkSDBwTzF4dVhIUjlMRnh1WEc1OU95SmRmUT09Il19
