(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/* global wpforms_gutenberg_form_selector */
/* jshint es3: false, esversion: 6 */

'use strict';

var _wp = wp,
  _wp$serverSideRender = _wp.serverSideRender,
  ServerSideRender = _wp$serverSideRender === void 0 ? wp.components.ServerSideRender : _wp$serverSideRender;
var _wp$element = wp.element,
  createElement = _wp$element.createElement,
  Fragment = _wp$element.Fragment;
var registerBlockType = wp.blocks.registerBlockType;
var _ref = wp.blockEditor || wp.editor,
  InspectorControls = _ref.InspectorControls;
var _wp$components = wp.components,
  SelectControl = _wp$components.SelectControl,
  ToggleControl = _wp$components.ToggleControl,
  PanelBody = _wp$components.PanelBody,
  Placeholder = _wp$components.Placeholder;
var __ = wp.i18n.__;
var wpformsIcon = createElement('svg', {
  width: 20,
  height: 20,
  viewBox: '0 0 612 612',
  className: 'dashicon'
}, createElement('path', {
  fill: 'currentColor',
  d: 'M544,0H68C30.445,0,0,30.445,0,68v476c0,37.556,30.445,68,68,68h476c37.556,0,68-30.444,68-68V68 C612,30.445,581.556,0,544,0z M464.44,68L387.6,120.02L323.34,68H464.44z M288.66,68l-64.26,52.02L147.56,68H288.66z M544,544H68 V68h22.1l136,92.14l79.9-64.6l79.56,64.6l136-92.14H544V544z M114.24,263.16h95.88v-48.28h-95.88V263.16z M114.24,360.4h95.88 v-48.62h-95.88V360.4z M242.76,360.4h255v-48.62h-255V360.4L242.76,360.4z M242.76,263.16h255v-48.28h-255V263.16L242.76,263.16z M368.22,457.3h129.54V408H368.22V457.3z'
}));

/**
 * Popup container.
 *
 * @since 1.8.3
 *
 * @type {object}
 */
var $popup = {};

/**
 * Close button (inside the form builder) click event.
 *
 * @since 1.8.3
 *
 * @param {string} clientID Block Client ID.
 */
var builderCloseButtonEvent = function builderCloseButtonEvent(clientID) {
  $popup.off('wpformsBuilderInPopupClose').on('wpformsBuilderInPopupClose', function (e, action, formId, formTitle) {
    if (action !== 'saved' || !formId) {
      return;
    }

    // Insert a new block when a new form is created from the popup to update the form list and attributes.
    var newBlock = wp.blocks.createBlock('wpforms/form-selector', {
      formId: formId.toString() // Expects string value, make sure we insert string.
    });

    // eslint-disable-next-line camelcase
    wpforms_gutenberg_form_selector.forms = [{
      ID: formId,
      post_title: formTitle
    }];

    // Insert a new block.
    wp.data.dispatch('core/block-editor').removeBlock(clientID);
    wp.data.dispatch('core/block-editor').insertBlocks(newBlock);
  });
};

/**
 * Open builder popup.
 *
 * @since 1.6.2
 *
 * @param {string} clientID Block Client ID.
 */
var openBuilderPopup = function openBuilderPopup(clientID) {
  if (jQuery.isEmptyObject($popup)) {
    var tmpl = jQuery('#wpforms-gutenberg-popup');
    var parent = jQuery('#wpwrap');
    parent.after(tmpl);
    $popup = parent.siblings('#wpforms-gutenberg-popup');
  }
  var url = wpforms_gutenberg_form_selector.get_started_url,
    $iframe = $popup.find('iframe');
  builderCloseButtonEvent(clientID);
  $iframe.attr('src', url);
  $popup.fadeIn();
};
var hasForms = function hasForms() {
  return wpforms_gutenberg_form_selector.forms.length > 0;
};
registerBlockType('wpforms/form-selector', {
  title: wpforms_gutenberg_form_selector.strings.title,
  description: wpforms_gutenberg_form_selector.strings.description,
  icon: wpformsIcon,
  keywords: wpforms_gutenberg_form_selector.strings.form_keywords,
  category: 'widgets',
  attributes: {
    formId: {
      type: 'string'
    },
    displayTitle: {
      type: 'boolean'
    },
    displayDesc: {
      type: 'boolean'
    },
    preview: {
      type: 'boolean'
    }
  },
  example: {
    attributes: {
      preview: true
    }
  },
  supports: {
    customClassName: hasForms()
  },
  edit: function edit(props) {
    // eslint-disable-line max-lines-per-function
    var _props$attributes = props.attributes,
      _props$attributes$for = _props$attributes.formId,
      formId = _props$attributes$for === void 0 ? '' : _props$attributes$for,
      _props$attributes$dis = _props$attributes.displayTitle,
      displayTitle = _props$attributes$dis === void 0 ? false : _props$attributes$dis,
      _props$attributes$dis2 = _props$attributes.displayDesc,
      displayDesc = _props$attributes$dis2 === void 0 ? false : _props$attributes$dis2,
      _props$attributes$pre = _props$attributes.preview,
      preview = _props$attributes$pre === void 0 ? false : _props$attributes$pre,
      setAttributes = props.setAttributes;
    var formOptions = wpforms_gutenberg_form_selector.forms.map(function (value) {
      return {
        value: value.ID,
        label: value.post_title
      };
    });
    var strings = wpforms_gutenberg_form_selector.strings;
    var jsx;
    formOptions.unshift({
      value: '',
      label: wpforms_gutenberg_form_selector.strings.form_select
    });
    function selectForm(value) {
      // eslint-disable-line jsdoc/require-jsdoc
      setAttributes({
        formId: value
      });
    }
    function toggleDisplayTitle(value) {
      // eslint-disable-line jsdoc/require-jsdoc
      setAttributes({
        displayTitle: value
      });
    }
    function toggleDisplayDesc(value) {
      // eslint-disable-line jsdoc/require-jsdoc
      setAttributes({
        displayDesc: value
      });
    }

    /**
     * Get block empty JSX code.
     *
     * @since 1.8.3
     *
     * @param {object} props Block properties.
     * @returns {JSX.Element} Block empty JSX code.
     */
    function getEmptyFormsPreview(props) {
      var clientId = props.clientId;
      return /*#__PURE__*/React.createElement(Fragment, {
        key: "wpforms-gutenberg-form-selector-fragment-block-empty"
      }, /*#__PURE__*/React.createElement("div", {
        className: "wpforms-no-form-preview"
      }, /*#__PURE__*/React.createElement("img", {
        src: wpforms_gutenberg_form_selector.block_empty_url
      }), /*#__PURE__*/React.createElement("p", {
        dangerouslySetInnerHTML: {
          __html: strings.wpforms_empty_info
        }
      }), /*#__PURE__*/React.createElement("button", {
        type: "button",
        className: "get-started-button components-button is-button is-primary",
        onClick: function onClick() {
          openBuilderPopup(clientId);
        }
      }, __('Get Started', 'wpforms-lite')), /*#__PURE__*/React.createElement("p", {
        className: "empty-desc",
        dangerouslySetInnerHTML: {
          __html: strings.wpforms_empty_help
        }
      }), /*#__PURE__*/React.createElement("div", {
        id: "wpforms-gutenberg-popup",
        className: "wpforms-builder-popup"
      }, /*#__PURE__*/React.createElement("iframe", {
        src: "about:blank",
        width: "100%",
        height: "100%",
        id: "wpforms-builder-iframe"
      }))));
    }

    /**
     * Print empty forms notice.
     *
     * @since 1.8.3
     *
     * @param {string} clientId Block client ID.
     *
     * @returns {JSX.Element} Field styles JSX code.
     */
    function printEmptyFormsNotice(clientId) {
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
        className: "get-started-button components-button is-button is-secondary",
        onClick: function onClick() {
          openBuilderPopup(clientId);
        }
      }, __('Get Started', 'wpforms-lite'))));
    }
    if (!hasForms()) {
      jsx = [printEmptyFormsNotice(props.clientId)];
      jsx.push(getEmptyFormsPreview(props));
      return jsx;
    }
    jsx = [/*#__PURE__*/React.createElement(InspectorControls, {
      key: "wpforms-gutenberg-form-selector-inspector-controls"
    }, /*#__PURE__*/React.createElement(PanelBody, {
      title: wpforms_gutenberg_form_selector.strings.form_settings
    }, /*#__PURE__*/React.createElement(SelectControl, {
      label: wpforms_gutenberg_form_selector.strings.form_selected,
      value: formId,
      options: formOptions,
      onChange: selectForm
    }), /*#__PURE__*/React.createElement(ToggleControl, {
      label: wpforms_gutenberg_form_selector.strings.show_title,
      checked: displayTitle,
      onChange: toggleDisplayTitle
    }), /*#__PURE__*/React.createElement(ToggleControl, {
      label: wpforms_gutenberg_form_selector.strings.show_description,
      checked: displayDesc,
      onChange: toggleDisplayDesc
    }), /*#__PURE__*/React.createElement("p", {
      className: "wpforms-gutenberg-panel-notice"
    }, /*#__PURE__*/React.createElement("strong", null, strings.update_wp_notice_head), strings.update_wp_notice_text, " ", /*#__PURE__*/React.createElement("a", {
      href: strings.update_wp_notice_link,
      rel: "noreferrer",
      target: "_blank"
    }, strings.learn_more))))];
    if (formId) {
      jsx.push( /*#__PURE__*/React.createElement(ServerSideRender, {
        key: "wpforms-gutenberg-form-selector-server-side-renderer",
        block: "wpforms/form-selector",
        attributes: props.attributes
      }));
    } else if (preview) {
      jsx.push( /*#__PURE__*/React.createElement(Fragment, {
        key: "wpforms-gutenberg-form-selector-fragment-block-preview"
      }, /*#__PURE__*/React.createElement("img", {
        src: wpforms_gutenberg_form_selector.block_preview_url,
        style: {
          width: '100%'
        }
      })));
    } else {
      jsx.push( /*#__PURE__*/React.createElement(Placeholder, {
        key: "wpforms-gutenberg-form-selector-wrap",
        className: "wpforms-gutenberg-form-selector-wrap"
      }, /*#__PURE__*/React.createElement("img", {
        src: wpforms_gutenberg_form_selector.logo_url
      }), /*#__PURE__*/React.createElement("h3", null, wpforms_gutenberg_form_selector.strings.title), /*#__PURE__*/React.createElement(SelectControl, {
        key: "wpforms-gutenberg-form-selector-select-control",
        value: formId,
        options: formOptions,
        onChange: selectForm
      })));
    }
    return jsx;
  },
  save: function save() {
    return null;
  }
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfd3AiLCJ3cCIsIl93cCRzZXJ2ZXJTaWRlUmVuZGVyIiwic2VydmVyU2lkZVJlbmRlciIsIlNlcnZlclNpZGVSZW5kZXIiLCJjb21wb25lbnRzIiwiX3dwJGVsZW1lbnQiLCJlbGVtZW50IiwiY3JlYXRlRWxlbWVudCIsIkZyYWdtZW50IiwicmVnaXN0ZXJCbG9ja1R5cGUiLCJibG9ja3MiLCJfcmVmIiwiYmxvY2tFZGl0b3IiLCJlZGl0b3IiLCJJbnNwZWN0b3JDb250cm9scyIsIl93cCRjb21wb25lbnRzIiwiU2VsZWN0Q29udHJvbCIsIlRvZ2dsZUNvbnRyb2wiLCJQYW5lbEJvZHkiLCJQbGFjZWhvbGRlciIsIl9fIiwiaTE4biIsIndwZm9ybXNJY29uIiwid2lkdGgiLCJoZWlnaHQiLCJ2aWV3Qm94IiwiY2xhc3NOYW1lIiwiZmlsbCIsImQiLCIkcG9wdXAiLCJidWlsZGVyQ2xvc2VCdXR0b25FdmVudCIsImNsaWVudElEIiwib2ZmIiwib24iLCJlIiwiYWN0aW9uIiwiZm9ybUlkIiwiZm9ybVRpdGxlIiwibmV3QmxvY2siLCJjcmVhdGVCbG9jayIsInRvU3RyaW5nIiwid3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciIsImZvcm1zIiwiSUQiLCJwb3N0X3RpdGxlIiwiZGF0YSIsImRpc3BhdGNoIiwicmVtb3ZlQmxvY2siLCJpbnNlcnRCbG9ja3MiLCJvcGVuQnVpbGRlclBvcHVwIiwialF1ZXJ5IiwiaXNFbXB0eU9iamVjdCIsInRtcGwiLCJwYXJlbnQiLCJhZnRlciIsInNpYmxpbmdzIiwidXJsIiwiZ2V0X3N0YXJ0ZWRfdXJsIiwiJGlmcmFtZSIsImZpbmQiLCJhdHRyIiwiZmFkZUluIiwiaGFzRm9ybXMiLCJsZW5ndGgiLCJ0aXRsZSIsInN0cmluZ3MiLCJkZXNjcmlwdGlvbiIsImljb24iLCJrZXl3b3JkcyIsImZvcm1fa2V5d29yZHMiLCJjYXRlZ29yeSIsImF0dHJpYnV0ZXMiLCJ0eXBlIiwiZGlzcGxheVRpdGxlIiwiZGlzcGxheURlc2MiLCJwcmV2aWV3IiwiZXhhbXBsZSIsInN1cHBvcnRzIiwiY3VzdG9tQ2xhc3NOYW1lIiwiZWRpdCIsInByb3BzIiwiX3Byb3BzJGF0dHJpYnV0ZXMiLCJfcHJvcHMkYXR0cmlidXRlcyRmb3IiLCJfcHJvcHMkYXR0cmlidXRlcyRkaXMiLCJfcHJvcHMkYXR0cmlidXRlcyRkaXMyIiwiX3Byb3BzJGF0dHJpYnV0ZXMkcHJlIiwic2V0QXR0cmlidXRlcyIsImZvcm1PcHRpb25zIiwibWFwIiwidmFsdWUiLCJsYWJlbCIsImpzeCIsInVuc2hpZnQiLCJmb3JtX3NlbGVjdCIsInNlbGVjdEZvcm0iLCJ0b2dnbGVEaXNwbGF5VGl0bGUiLCJ0b2dnbGVEaXNwbGF5RGVzYyIsImdldEVtcHR5Rm9ybXNQcmV2aWV3IiwiY2xpZW50SWQiLCJSZWFjdCIsImtleSIsInNyYyIsImJsb2NrX2VtcHR5X3VybCIsImRhbmdlcm91c2x5U2V0SW5uZXJIVE1MIiwiX19odG1sIiwid3Bmb3Jtc19lbXB0eV9pbmZvIiwib25DbGljayIsIndwZm9ybXNfZW1wdHlfaGVscCIsImlkIiwicHJpbnRFbXB0eUZvcm1zTm90aWNlIiwiZm9ybV9zZXR0aW5ncyIsInN0eWxlIiwiZGlzcGxheSIsInB1c2giLCJmb3JtX3NlbGVjdGVkIiwib3B0aW9ucyIsIm9uQ2hhbmdlIiwic2hvd190aXRsZSIsImNoZWNrZWQiLCJzaG93X2Rlc2NyaXB0aW9uIiwidXBkYXRlX3dwX25vdGljZV9oZWFkIiwidXBkYXRlX3dwX25vdGljZV90ZXh0IiwiaHJlZiIsInVwZGF0ZV93cF9ub3RpY2VfbGluayIsInJlbCIsInRhcmdldCIsImxlYXJuX21vcmUiLCJibG9jayIsImJsb2NrX3ByZXZpZXdfdXJsIiwibG9nb191cmwiLCJzYXZlIl0sInNvdXJjZXMiOlsiZmFrZV9iMWQ4OWI2MS5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4ndXNlIHN0cmljdCc7XG5cbmNvbnN0IHsgc2VydmVyU2lkZVJlbmRlcjogU2VydmVyU2lkZVJlbmRlciA9IHdwLmNvbXBvbmVudHMuU2VydmVyU2lkZVJlbmRlciB9ID0gd3A7XG5jb25zdCB7IGNyZWF0ZUVsZW1lbnQsIEZyYWdtZW50IH0gPSB3cC5lbGVtZW50O1xuY29uc3QgeyByZWdpc3RlckJsb2NrVHlwZSB9ID0gd3AuYmxvY2tzO1xuY29uc3QgeyBJbnNwZWN0b3JDb250cm9scyB9ID0gd3AuYmxvY2tFZGl0b3IgfHwgd3AuZWRpdG9yO1xuY29uc3QgeyBTZWxlY3RDb250cm9sLCBUb2dnbGVDb250cm9sLCBQYW5lbEJvZHksIFBsYWNlaG9sZGVyIH0gPSB3cC5jb21wb25lbnRzO1xuY29uc3QgeyBfXyB9ID0gd3AuaTE4bjtcblxuY29uc3Qgd3Bmb3Jtc0ljb24gPSBjcmVhdGVFbGVtZW50KCAnc3ZnJywgeyB3aWR0aDogMjAsIGhlaWdodDogMjAsIHZpZXdCb3g6ICcwIDAgNjEyIDYxMicsIGNsYXNzTmFtZTogJ2Rhc2hpY29uJyB9LFxuXHRjcmVhdGVFbGVtZW50KCAncGF0aCcsIHtcblx0XHRmaWxsOiAnY3VycmVudENvbG9yJyxcblx0XHRkOiAnTTU0NCwwSDY4QzMwLjQ0NSwwLDAsMzAuNDQ1LDAsNjh2NDc2YzAsMzcuNTU2LDMwLjQ0NSw2OCw2OCw2OGg0NzZjMzcuNTU2LDAsNjgtMzAuNDQ0LDY4LTY4VjY4IEM2MTIsMzAuNDQ1LDU4MS41NTYsMCw1NDQsMHogTTQ2NC40NCw2OEwzODcuNiwxMjAuMDJMMzIzLjM0LDY4SDQ2NC40NHogTTI4OC42Niw2OGwtNjQuMjYsNTIuMDJMMTQ3LjU2LDY4SDI4OC42NnogTTU0NCw1NDRINjggVjY4aDIyLjFsMTM2LDkyLjE0bDc5LjktNjQuNmw3OS41Niw2NC42bDEzNi05Mi4xNEg1NDRWNTQ0eiBNMTE0LjI0LDI2My4xNmg5NS44OHYtNDguMjhoLTk1Ljg4VjI2My4xNnogTTExNC4yNCwzNjAuNGg5NS44OCB2LTQ4LjYyaC05NS44OFYzNjAuNHogTTI0Mi43NiwzNjAuNGgyNTV2LTQ4LjYyaC0yNTVWMzYwLjRMMjQyLjc2LDM2MC40eiBNMjQyLjc2LDI2My4xNmgyNTV2LTQ4LjI4aC0yNTVWMjYzLjE2TDI0Mi43NiwyNjMuMTZ6IE0zNjguMjIsNDU3LjNoMTI5LjU0VjQwOEgzNjguMjJWNDU3LjN6Jyxcblx0fSApXG4pO1xuXG4vKipcbiAqIFBvcHVwIGNvbnRhaW5lci5cbiAqXG4gKiBAc2luY2Uge1ZFUlNJT059XG4gKlxuICogQHR5cGUge29iamVjdH1cbiAqL1xubGV0ICRwb3B1cCA9IHt9O1xuXG4vKipcbiAqIENsb3NlIGJ1dHRvbiAoaW5zaWRlIHRoZSBmb3JtIGJ1aWxkZXIpIGNsaWNrIGV2ZW50LlxuICpcbiAqIEBzaW5jZSB7VkVSU0lPTn1cbiAqXG4gKiBAcGFyYW0ge3N0cmluZ30gY2xpZW50SUQgQmxvY2sgQ2xpZW50IElELlxuICovXG5jb25zdCBidWlsZGVyQ2xvc2VCdXR0b25FdmVudCA9IGZ1bmN0aW9uKCBjbGllbnRJRCApIHtcblxuXHQkcG9wdXBcblx0XHQub2ZmKCAnd3Bmb3Jtc0J1aWxkZXJJblBvcHVwQ2xvc2UnIClcblx0XHQub24oICd3cGZvcm1zQnVpbGRlckluUG9wdXBDbG9zZScsIGZ1bmN0aW9uKCBlLCBhY3Rpb24sIGZvcm1JZCwgZm9ybVRpdGxlICkge1xuXHRcdFx0aWYgKCBhY3Rpb24gIT09ICdzYXZlZCcgfHwgISBmb3JtSWQgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Ly8gSW5zZXJ0IGEgbmV3IGJsb2NrIHdoZW4gYSBuZXcgZm9ybSBpcyBjcmVhdGVkIGZyb20gdGhlIHBvcHVwIHRvIHVwZGF0ZSB0aGUgZm9ybSBsaXN0IGFuZCBhdHRyaWJ1dGVzLlxuXHRcdFx0Y29uc3QgbmV3QmxvY2sgPSB3cC5ibG9ja3MuY3JlYXRlQmxvY2soICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdGZvcm1JZDogZm9ybUlkLnRvU3RyaW5nKCksIC8vIEV4cGVjdHMgc3RyaW5nIHZhbHVlLCBtYWtlIHN1cmUgd2UgaW5zZXJ0IHN0cmluZy5cblx0XHRcdH0gKTtcblxuXHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGNhbWVsY2FzZVxuXHRcdFx0d3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5mb3JtcyA9IFsgeyBJRDogZm9ybUlkLCBwb3N0X3RpdGxlOiBmb3JtVGl0bGUgfSBdO1xuXG5cdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2suXG5cdFx0XHR3cC5kYXRhLmRpc3BhdGNoKCAnY29yZS9ibG9jay1lZGl0b3InICkucmVtb3ZlQmxvY2soIGNsaWVudElEICk7XG5cdFx0XHR3cC5kYXRhLmRpc3BhdGNoKCAnY29yZS9ibG9jay1lZGl0b3InICkuaW5zZXJ0QmxvY2tzKCBuZXdCbG9jayApO1xuXG5cdFx0fSApO1xufTtcblxuLyoqXG4gKiBPcGVuIGJ1aWxkZXIgcG9wdXAuXG4gKlxuICogQHNpbmNlIDEuNi4yXG4gKlxuICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElEIEJsb2NrIENsaWVudCBJRC5cbiAqL1xuY29uc3Qgb3BlbkJ1aWxkZXJQb3B1cCA9IGZ1bmN0aW9uKCBjbGllbnRJRCApIHtcblxuXHRpZiAoIGpRdWVyeS5pc0VtcHR5T2JqZWN0KCAkcG9wdXAgKSApIHtcblx0XHRsZXQgdG1wbCA9IGpRdWVyeSggJyN3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cCcgKTtcblx0XHRsZXQgcGFyZW50ID0galF1ZXJ5KCAnI3dwd3JhcCcgKTtcblxuXHRcdHBhcmVudC5hZnRlciggdG1wbCApO1xuXG5cdFx0JHBvcHVwID0gcGFyZW50LnNpYmxpbmdzKCAnI3dwZm9ybXMtZ3V0ZW5iZXJnLXBvcHVwJyApO1xuXHR9XG5cblx0Y29uc3QgdXJsID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5nZXRfc3RhcnRlZF91cmwsXG5cdFx0JGlmcmFtZSA9ICRwb3B1cC5maW5kKCAnaWZyYW1lJyApO1xuXG5cdGJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApO1xuXHQkaWZyYW1lLmF0dHIoICdzcmMnLCB1cmwgKTtcblx0JHBvcHVwLmZhZGVJbigpO1xufTtcblxuY29uc3QgaGFzRm9ybXMgPSBmdW5jdGlvbigpIHtcblx0cmV0dXJuIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuZm9ybXMubGVuZ3RoID4gMDtcbn07XG5cbnJlZ2lzdGVyQmxvY2tUeXBlKCAnd3Bmb3Jtcy9mb3JtLXNlbGVjdG9yJywge1xuXHR0aXRsZTogd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdHJpbmdzLnRpdGxlLFxuXHRkZXNjcmlwdGlvbjogd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdHJpbmdzLmRlc2NyaXB0aW9uLFxuXHRpY29uOiB3cGZvcm1zSWNvbixcblx0a2V5d29yZHM6IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3Iuc3RyaW5ncy5mb3JtX2tleXdvcmRzLFxuXHRjYXRlZ29yeTogJ3dpZGdldHMnLFxuXHRhdHRyaWJ1dGVzOiB7XG5cdFx0Zm9ybUlkOiB7XG5cdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHR9LFxuXHRcdGRpc3BsYXlUaXRsZToge1xuXHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdH0sXG5cdFx0ZGlzcGxheURlc2M6IHtcblx0XHRcdHR5cGU6ICdib29sZWFuJyxcblx0XHR9LFxuXHRcdHByZXZpZXc6IHtcblx0XHRcdHR5cGU6ICdib29sZWFuJyxcblx0XHR9LFxuXHR9LFxuXHRleGFtcGxlOiB7XG5cdFx0YXR0cmlidXRlczoge1xuXHRcdFx0cHJldmlldzogdHJ1ZSxcblx0XHR9LFxuXHR9LFxuXHRzdXBwb3J0czoge1xuXHRcdGN1c3RvbUNsYXNzTmFtZTogaGFzRm9ybXMoKSxcblx0fSxcblx0ZWRpdCggcHJvcHMgKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXHRcdGNvbnN0IHsgYXR0cmlidXRlczogeyBmb3JtSWQgPSAnJywgZGlzcGxheVRpdGxlID0gZmFsc2UsIGRpc3BsYXlEZXNjID0gZmFsc2UsIHByZXZpZXcgPSBmYWxzZSB9LCBzZXRBdHRyaWJ1dGVzIH0gPSBwcm9wcztcblx0XHRjb25zdCBmb3JtT3B0aW9ucyA9IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuZm9ybXMubWFwKCB2YWx1ZSA9PiAoXG5cdFx0XHR7IHZhbHVlOiB2YWx1ZS5JRCwgbGFiZWw6IHZhbHVlLnBvc3RfdGl0bGUgfVxuXHRcdCkgKTtcblxuXHRcdGNvbnN0IHN0cmluZ3MgPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLnN0cmluZ3M7XG5cdFx0bGV0IGpzeDtcblxuXHRcdGZvcm1PcHRpb25zLnVuc2hpZnQoIHsgdmFsdWU6ICcnLCBsYWJlbDogd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdHJpbmdzLmZvcm1fc2VsZWN0IH0gKTtcblxuXHRcdGZ1bmN0aW9uIHNlbGVjdEZvcm0oIHZhbHVlICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIGpzZG9jL3JlcXVpcmUtanNkb2Ncblx0XHRcdHNldEF0dHJpYnV0ZXMoIHsgZm9ybUlkOiB2YWx1ZSB9ICk7XG5cdFx0fVxuXG5cdFx0ZnVuY3Rpb24gdG9nZ2xlRGlzcGxheVRpdGxlKCB2YWx1ZSApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBqc2RvYy9yZXF1aXJlLWpzZG9jXG5cdFx0XHRzZXRBdHRyaWJ1dGVzKCB7IGRpc3BsYXlUaXRsZTogdmFsdWUgfSApO1xuXHRcdH1cblxuXHRcdGZ1bmN0aW9uIHRvZ2dsZURpc3BsYXlEZXNjKCB2YWx1ZSApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBqc2RvYy9yZXF1aXJlLWpzZG9jXG5cdFx0XHRzZXRBdHRyaWJ1dGVzKCB7IGRpc3BsYXlEZXNjOiB2YWx1ZSB9ICk7XG5cdFx0fVxuXG5cdFx0LyoqXG5cdFx0ICogR2V0IGJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtvYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0ICogQHJldHVybnMge0pTWC5FbGVtZW50fSBCbG9jayBlbXB0eSBKU1ggY29kZS5cblx0XHQgKi9cblx0XHRmdW5jdGlvbiBnZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSB7XG5cblx0XHRcdGNvbnN0IGNsaWVudElkID0gcHJvcHMuY2xpZW50SWQ7XG5cblx0XHRcdHJldHVybiAoXG5cdFx0XHRcdDxGcmFnbWVudFxuXHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stZW1wdHlcIj5cblx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtbm8tZm9ybS1wcmV2aWV3XCI+XG5cdFx0XHRcdFx0XHQ8aW1nIHNyYz17IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuYmxvY2tfZW1wdHlfdXJsIH0gLz5cblx0XHRcdFx0XHRcdDxwIGRhbmdlcm91c2x5U2V0SW5uZXJIVE1MPXt7IF9faHRtbDogc3RyaW5ncy53cGZvcm1zX2VtcHR5X2luZm8gfX0+PC9wPlxuXHRcdFx0XHRcdFx0PGJ1dHRvbiB0eXBlPVwiYnV0dG9uXCIgY2xhc3NOYW1lPVwiZ2V0LXN0YXJ0ZWQtYnV0dG9uIGNvbXBvbmVudHMtYnV0dG9uIGlzLWJ1dHRvbiBpcy1wcmltYXJ5XCJcblx0XHRcdFx0XHRcdFx0b25DbGljaz17XG5cdFx0XHRcdFx0XHRcdFx0KCkgPT4ge1xuXHRcdFx0XHRcdFx0XHRcdFx0b3BlbkJ1aWxkZXJQb3B1cCggY2xpZW50SWQgKTtcblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdD5cblx0XHRcdFx0XHRcdFx0eyBfXyggJ0dldCBTdGFydGVkJywgJ3dwZm9ybXMtbGl0ZScgKSB9XG5cdFx0XHRcdFx0XHQ8L2J1dHRvbj5cblx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cImVtcHR5LWRlc2NcIiBkYW5nZXJvdXNseVNldElubmVySFRNTD17eyBfX2h0bWw6IHN0cmluZ3Mud3Bmb3Jtc19lbXB0eV9oZWxwIH19PjwvcD5cblxuXHRcdFx0XHRcdFx0ey8qIFRlbXBsYXRlIGZvciBwb3B1cCB3aXRoIGJ1aWxkZXIgaWZyYW1lICovfVxuXHRcdFx0XHRcdFx0PGRpdiBpZD1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBvcHVwXCIgY2xhc3NOYW1lPVwid3Bmb3Jtcy1idWlsZGVyLXBvcHVwXCI+XG5cdFx0XHRcdFx0XHRcdDxpZnJhbWUgc3JjPVwiYWJvdXQ6YmxhbmtcIiB3aWR0aD1cIjEwMCVcIiBoZWlnaHQ9XCIxMDAlXCIgaWQ9XCJ3cGZvcm1zLWJ1aWxkZXItaWZyYW1lXCI+PC9pZnJhbWU+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdCk7XG5cdFx0fVxuXG5cdFx0LyoqXG5cdFx0ICogUHJpbnQgZW1wdHkgZm9ybXMgbm90aWNlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElkIEJsb2NrIGNsaWVudCBJRC5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtKU1guRWxlbWVudH0gRmllbGQgc3R5bGVzIEpTWCBjb2RlLlxuXHRcdCAqL1xuXHRcdGZ1bmN0aW9uIHByaW50RW1wdHlGb3Jtc05vdGljZSggY2xpZW50SWQgKSB7XG5cdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHQ8SW5zcGVjdG9yQ29udHJvbHMga2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1pbnNwZWN0b3ItbWFpbi1zZXR0aW5nc1wiPlxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWxcIiB0aXRsZT17IHN0cmluZ3MuZm9ybV9zZXR0aW5ncyB9PlxuXHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlIHdwZm9ybXMtd2FybmluZyB3cGZvcm1zLWVtcHR5LWZvcm0tbm90aWNlXCIgc3R5bGU9e3sgZGlzcGxheTogJ2Jsb2NrJyB9fT5cblx0XHRcdFx0XHRcdFx0PHN0cm9uZz57IF9fKCAnWW91IGhhdmVu4oCZdCBjcmVhdGVkIGEgZm9ybSwgeWV0IScsICd3cGZvcm1zLWxpdGUnICkgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0XHR7IF9fKCAnV2hhdCBhcmUgeW91IHdhaXRpbmcgZm9yPycsICd3cGZvcm1zLWxpdGUnICkgfVxuXHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0PGJ1dHRvbiB0eXBlPVwiYnV0dG9uXCIgY2xhc3NOYW1lPVwiZ2V0LXN0YXJ0ZWQtYnV0dG9uIGNvbXBvbmVudHMtYnV0dG9uIGlzLWJ1dHRvbiBpcy1zZWNvbmRhcnlcIlxuXHRcdFx0XHRcdFx0XHRvbkNsaWNrPXtcblx0XHRcdFx0XHRcdFx0XHQoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRvcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJZCApO1xuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0XHR7IF9fKCAnR2V0IFN0YXJ0ZWQnLCAnd3Bmb3Jtcy1saXRlJyApIH1cblx0XHRcdFx0XHRcdDwvYnV0dG9uPlxuXHRcdFx0XHRcdDwvUGFuZWxCb2R5PlxuXHRcdFx0XHQ8L0luc3BlY3RvckNvbnRyb2xzPlxuXHRcdFx0KTtcblx0XHR9XG5cblxuXHRcdGlmICggISBoYXNGb3JtcygpICkge1xuXG5cdFx0XHRqc3ggPSBbIHByaW50RW1wdHlGb3Jtc05vdGljZSggcHJvcHMuY2xpZW50SWQgKSBdO1xuXG5cdFx0XHRqc3gucHVzaCggZ2V0RW1wdHlGb3Jtc1ByZXZpZXcoIHByb3BzICkgKTtcblx0XHRcdHJldHVybiBqc3g7XG5cdFx0fVxuXG5cdFx0anN4ID0gW1xuXHRcdFx0PEluc3BlY3RvckNvbnRyb2xzIGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItaW5zcGVjdG9yLWNvbnRyb2xzXCI+XG5cdFx0XHRcdDxQYW5lbEJvZHkgdGl0bGU9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLnN0cmluZ3MuZm9ybV9zZXR0aW5ncyB9PlxuXHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRsYWJlbD17IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3Iuc3RyaW5ncy5mb3JtX3NlbGVjdGVkIH1cblx0XHRcdFx0XHRcdHZhbHVlPXsgZm9ybUlkIH1cblx0XHRcdFx0XHRcdG9wdGlvbnM9eyBmb3JtT3B0aW9ucyB9XG5cdFx0XHRcdFx0XHRvbkNoYW5nZT17IHNlbGVjdEZvcm0gfVxuXHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0PFRvZ2dsZUNvbnRyb2xcblx0XHRcdFx0XHRcdGxhYmVsPXsgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdHJpbmdzLnNob3dfdGl0bGUgfVxuXHRcdFx0XHRcdFx0Y2hlY2tlZD17IGRpc3BsYXlUaXRsZSB9XG5cdFx0XHRcdFx0XHRvbkNoYW5nZT17IHRvZ2dsZURpc3BsYXlUaXRsZSB9XG5cdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHQ8VG9nZ2xlQ29udHJvbFxuXHRcdFx0XHRcdFx0bGFiZWw9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLnN0cmluZ3Muc2hvd19kZXNjcmlwdGlvbiB9XG5cdFx0XHRcdFx0XHRjaGVja2VkPXsgZGlzcGxheURlc2MgfVxuXHRcdFx0XHRcdFx0b25DaGFuZ2U9eyB0b2dnbGVEaXNwbGF5RGVzYyB9XG5cdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2VcIj5cblx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLnVwZGF0ZV93cF9ub3RpY2VfaGVhZCB9PC9zdHJvbmc+XG5cdFx0XHRcdFx0XHR7IHN0cmluZ3MudXBkYXRlX3dwX25vdGljZV90ZXh0IH0gPGEgaHJlZj17c3RyaW5ncy51cGRhdGVfd3Bfbm90aWNlX2xpbmt9IHJlbD1cIm5vcmVmZXJyZXJcIiB0YXJnZXQ9XCJfYmxhbmtcIj57IHN0cmluZ3MubGVhcm5fbW9yZSB9PC9hPlxuXHRcdFx0XHRcdDwvcD5cblxuXHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdDwvSW5zcGVjdG9yQ29udHJvbHM+LFxuXHRcdF07XG5cblx0XHRpZiAoIGZvcm1JZCApIHtcblx0XHRcdGpzeC5wdXNoKFxuXHRcdFx0XHQ8U2VydmVyU2lkZVJlbmRlclxuXHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3Itc2VydmVyLXNpZGUtcmVuZGVyZXJcIlxuXHRcdFx0XHRcdGJsb2NrPVwid3Bmb3Jtcy9mb3JtLXNlbGVjdG9yXCJcblx0XHRcdFx0XHRhdHRyaWJ1dGVzPXsgcHJvcHMuYXR0cmlidXRlcyB9XG5cdFx0XHRcdC8+XG5cdFx0XHQpO1xuXHRcdH0gZWxzZSBpZiAoIHByZXZpZXcgKSB7XG5cdFx0XHRqc3gucHVzaChcblx0XHRcdFx0PEZyYWdtZW50XG5cdFx0XHRcdFx0a2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1mcmFnbWVudC1ibG9jay1wcmV2aWV3XCI+XG5cdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX3ByZXZpZXdfdXJsIH0gc3R5bGU9e3sgd2lkdGg6ICcxMDAlJyB9fS8+XG5cdFx0XHRcdDwvRnJhZ21lbnQ+XG5cdFx0XHQpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHRqc3gucHVzaChcblx0XHRcdFx0PFBsYWNlaG9sZGVyXG5cdFx0XHRcdFx0a2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci13cmFwXCJcblx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXdyYXBcIj5cblx0XHRcdFx0XHQ8aW1nIHNyYz17IHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IubG9nb191cmwgfS8+XG5cdFx0XHRcdFx0PGgzPnsgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5zdHJpbmdzLnRpdGxlIH08L2gzPlxuXHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXNlbGVjdC1jb250cm9sXCJcblx0XHRcdFx0XHRcdHZhbHVlPXsgZm9ybUlkIH1cblx0XHRcdFx0XHRcdG9wdGlvbnM9eyBmb3JtT3B0aW9ucyB9XG5cdFx0XHRcdFx0XHRvbkNoYW5nZT17IHNlbGVjdEZvcm0gfVxuXHRcdFx0XHRcdC8+XG5cdFx0XHRcdDwvUGxhY2Vob2xkZXI+XG5cdFx0XHQpO1xuXHRcdH1cblxuXHRcdHJldHVybiBqc3g7XG5cdH0sXG5cdHNhdmUoKSB7XG5cdFx0cmV0dXJuIG51bGw7XG5cdH0sXG59ICk7XG4iXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0E7O0FBRUEsWUFBWTs7QUFFWixJQUFBQSxHQUFBLEdBQWdGQyxFQUFFO0VBQUFDLG9CQUFBLEdBQUFGLEdBQUEsQ0FBMUVHLGdCQUFnQjtFQUFFQyxnQkFBZ0IsR0FBQUYsb0JBQUEsY0FBR0QsRUFBRSxDQUFDSSxVQUFVLENBQUNELGdCQUFnQixHQUFBRixvQkFBQTtBQUMzRSxJQUFBSSxXQUFBLEdBQW9DTCxFQUFFLENBQUNNLE9BQU87RUFBdENDLGFBQWEsR0FBQUYsV0FBQSxDQUFiRSxhQUFhO0VBQUVDLFFBQVEsR0FBQUgsV0FBQSxDQUFSRyxRQUFRO0FBQy9CLElBQVFDLGlCQUFpQixHQUFLVCxFQUFFLENBQUNVLE1BQU0sQ0FBL0JELGlCQUFpQjtBQUN6QixJQUFBRSxJQUFBLEdBQThCWCxFQUFFLENBQUNZLFdBQVcs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
},{}]},{},[1])