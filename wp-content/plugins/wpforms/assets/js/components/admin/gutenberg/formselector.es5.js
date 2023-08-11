(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/* global wpforms_gutenberg_form_selector, Choices */
/* jshint es3: false, esversion: 6 */

'use strict';

/**
 * Gutenberg editor block.
 *
 * @since 1.8.1
 */
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i.return && (_r = _i.return(), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
var WPForms = window.WPForms || {};
WPForms.FormSelector = WPForms.FormSelector || function (document, window, $) {
  var _wp = wp,
    _wp$serverSideRender = _wp.serverSideRender,
    ServerSideRender = _wp$serverSideRender === void 0 ? wp.components.ServerSideRender : _wp$serverSideRender;
  var _wp$element = wp.element,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment,
    useState = _wp$element.useState,
    createInterpolateElement = _wp$element.createInterpolateElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var _ref = wp.blockEditor || wp.editor,
    InspectorControls = _ref.InspectorControls,
    InspectorAdvancedControls = _ref.InspectorAdvancedControls,
    PanelColorSettings = _ref.PanelColorSettings;
  var _wp$components = wp.components,
    SelectControl = _wp$components.SelectControl,
    ToggleControl = _wp$components.ToggleControl,
    PanelBody = _wp$components.PanelBody,
    Placeholder = _wp$components.Placeholder,
    Flex = _wp$components.Flex,
    FlexBlock = _wp$components.FlexBlock,
    __experimentalUnitControl = _wp$components.__experimentalUnitControl,
    TextareaControl = _wp$components.TextareaControl,
    Button = _wp$components.Button,
    Modal = _wp$components.Modal;
  var _wpforms_gutenberg_fo = wpforms_gutenberg_form_selector,
    strings = _wpforms_gutenberg_fo.strings,
    defaults = _wpforms_gutenberg_fo.defaults,
    sizes = _wpforms_gutenberg_fo.sizes;
  var defaultStyleSettings = defaults;
  var __ = wp.i18n.__;

  /**
   * Blocks runtime data.
   *
   * @since 1.8.1
   *
   * @type {object}
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
   * @type {object}
   */
  var $popup = {};

  /**
   * Public functions and properties.
   *
   * @since 1.8.1
   *
   * @type {object}
   */
  var app = {
    /**
     * Start the engine.
     *
     * @since 1.8.1
     */
    init: function init() {
      app.initDefaults();
      app.registerBlock();
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
      $(window).on('wpformsFormSelectorEdit', _.debounce(app.blockEdit, 250)).on('wpformsFormSelectorFormLoaded', _.debounce(app.formLoaded, 250));
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
        var tmpl = $('#wpforms-gutenberg-popup');
        var parent = $('#wpwrap');
        parent.after(tmpl);
        $popup = parent.siblings('#wpforms-gutenberg-popup');
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
        wpforms_gutenberg_form_selector.forms = [{
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
     */
    // eslint-disable-next-line max-lines-per-function
    registerBlock: function registerBlock() {
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
        edit: function edit(props) {
          var attributes = props.attributes;
          var formOptions = app.getFormOptions();
          var sizeOptions = app.getSizeOptions();
          var handlers = app.getSettingsFieldsHandlers(props);

          // Store block clientId in attributes.
          if (!attributes.clientId) {
            // We just want client ID to update once.
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
            return jsx;
          }

          // Form style settings & block content.
          if (attributes.formId) {
            jsx.push(app.jsxParts.getStyleSettings(attributes, handlers, sizeOptions), app.jsxParts.getAdvancedSettings(attributes, handlers), app.jsxParts.getBlockFormContent(props));
            handlers.updateCopyPasteContent();
            $(window).trigger('wpformsFormSelectorEdit', [props]);
            return jsx;
          }

          // Block preview picture.
          if (attributes.preview) {
            jsx.push(app.jsxParts.getBlockPreview());
            return jsx;
          }

          // Block placeholder (form selector).
          jsx.push(app.jsxParts.getBlockPlaceholder(props.attributes, handlers, formOptions));
          return jsx;
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
     */
    initDefaults: function initDefaults() {
      ['formId', 'copyPasteJsonValue'].forEach(function (key) {
        return delete defaultStyleSettings[key];
      });
    },
    /**
     * Check if site has forms.
     *
     * @since 1.8.3
     *
     * @returns {boolean} Whether site has atleast one form.
     */
    hasForms: function hasForms() {
      return app.getFormOptions().length > 1;
    },
    /**
     * Block JSX parts.
     *
     * @since 1.8.1
     *
     * @type {object}
     */
    jsxParts: {
      /**
       * Get main settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} formOptions Form selector options.
       *
       * @returns {JSX.Element} Main setting JSX code.
       */
      getMainSettings: function getMainSettings(attributes, handlers, formOptions) {
        if (!app.hasForms()) {
          return app.jsxParts.printEmptyFormsNotice(attributes.clientId);
        }
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-inspector-main-settings"
        }, /*#__PURE__*/React.createElement(PanelBody, {
          className: "wpforms-gutenberg-panel",
          title: strings.form_settings
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.form_selected,
          value: attributes.formId,
          options: formOptions,
          onChange: function onChange(value) {
            return handlers.attrChange('formId', value);
          }
        }), /*#__PURE__*/React.createElement(ToggleControl, {
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
        }), /*#__PURE__*/React.createElement("p", {
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
       * @returns {JSX.Element} Field styles JSX code.
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
       * Get Field styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} sizeOptions Size selector options.
       *
       * @returns {JSX.Element} Field styles JSX code.
       */
      getFieldStyles: function getFieldStyles(attributes, handlers, sizeOptions) {
        // eslint-disable-line max-lines-per-function

        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(attributes),
          title: strings.field_styles
        }, /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-use-modern-notice"
        }, /*#__PURE__*/React.createElement("strong", null, strings.use_modern_notice_head), strings.use_modern_notice_text, " ", /*#__PURE__*/React.createElement("a", {
          href: strings.use_modern_notice_link,
          rel: "noreferrer",
          target: "_blank"
        }, strings.learn_more)), /*#__PURE__*/React.createElement("p", {
          className: "wpforms-gutenberg-panel-notice wpforms-warning wpforms-lead-form-notice",
          style: {
            display: 'none'
          }
        }, /*#__PURE__*/React.createElement("strong", null, strings.lead_forms_panel_notice_head), strings.lead_forms_panel_notice_text), /*#__PURE__*/React.createElement(Flex, {
          gap: 4,
          align: "flex-start",
          className: 'wpforms-gutenberg-form-selector-flex',
          justify: "space-between"
        }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: attributes.fieldSize,
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('fieldSize', value);
          }
        })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
          label: strings.border_radius,
          value: attributes.fieldBorderRadius,
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
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: attributes.fieldBackgroundColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldBackgroundColor', value);
            },
            label: strings.background
          }, {
            value: attributes.fieldBorderColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldBorderColor', value);
            },
            label: strings.border
          }, {
            value: attributes.fieldTextColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('fieldTextColor', value);
            },
            label: strings.text
          }]
        })));
      },
      /**
       * Get Label styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} sizeOptions Size selector options.
       *
       * @returns {JSX.Element} Label styles JSX code.
       */
      getLabelStyles: function getLabelStyles(attributes, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(attributes),
          title: strings.label_styles
        }, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: attributes.labelSize,
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
            value: attributes.labelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelColor', value);
            },
            label: strings.label
          }, {
            value: attributes.labelSublabelColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelSublabelColor', value);
            },
            label: strings.sublabel_hints.replace('&amp;', '&')
          }, {
            value: attributes.labelErrorColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('labelErrorColor', value);
            },
            label: strings.error_message
          }]
        })));
      },
      /**
       * Get Button styles JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} sizeOptions Size selector options.
       *
       * @returns {JSX.Element}  Button styles JSX code.
       */
      getButtonStyles: function getButtonStyles(attributes, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(PanelBody, {
          className: app.getPanelClass(attributes),
          title: strings.button_styles
        }, /*#__PURE__*/React.createElement(Flex, {
          gap: 4,
          align: "flex-start",
          className: 'wpforms-gutenberg-form-selector-flex',
          justify: "space-between"
        }, /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(SelectControl, {
          label: strings.size,
          value: attributes.buttonSize,
          options: sizeOptions,
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonSize', value);
          }
        })), /*#__PURE__*/React.createElement(FlexBlock, null, /*#__PURE__*/React.createElement(__experimentalUnitControl, {
          onChange: function onChange(value) {
            return handlers.styleAttrChange('buttonBorderRadius', value);
          },
          label: strings.border_radius,
          isUnitSelectTabbable: true,
          value: attributes.buttonBorderRadius
        }))), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-color-picker"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-control-label"
        }, strings.colors), /*#__PURE__*/React.createElement(PanelColorSettings, {
          __experimentalIsRenderedInSidebar: true,
          enableAlpha: true,
          showTitle: false,
          className: "wpforms-gutenberg-form-selector-color-panel",
          colorSettings: [{
            value: attributes.buttonBackgroundColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('buttonBackgroundColor', value);
            },
            label: strings.background
          }, {
            value: attributes.buttonTextColor,
            onChange: function onChange(value) {
              return handlers.styleAttrChange('buttonTextColor', value);
            },
            label: strings.text
          }]
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-legend wpforms-button-color-notice"
        }, strings.button_color_notice)));
      },
      /**
       * Get style settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} sizeOptions Size selector options.
       *
       * @returns {JSX.Element} Inspector controls JSX code.
       */
      getStyleSettings: function getStyleSettings(attributes, handlers, sizeOptions) {
        return /*#__PURE__*/React.createElement(InspectorControls, {
          key: "wpforms-gutenberg-form-selector-style-settings"
        }, app.jsxParts.getFieldStyles(attributes, handlers, sizeOptions), app.jsxParts.getLabelStyles(attributes, handlers, sizeOptions), app.jsxParts.getButtonStyles(attributes, handlers, sizeOptions));
      },
      /**
       * Get advanced settings JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes Block attributes.
       * @param {object} handlers   Block event handlers.
       *
       * @returns {JSX.Element} Inspector advanced controls JSX code.
       */
      getAdvancedSettings: function getAdvancedSettings(attributes, handlers) {
        var _useState = useState(false),
          _useState2 = _slicedToArray(_useState, 2),
          isOpen = _useState2[0],
          setOpen = _useState2[1];
        var openModal = function openModal() {
          return setOpen(true);
        };
        var closeModal = function closeModal() {
          return setOpen(false);
        };
        return /*#__PURE__*/React.createElement(InspectorAdvancedControls, null, /*#__PURE__*/React.createElement("div", {
          className: app.getPanelClass(attributes)
        }, /*#__PURE__*/React.createElement(TextareaControl, {
          label: strings.copy_paste_settings,
          rows: "4",
          spellCheck: "false",
          value: attributes.copyPasteJsonValue,
          onChange: function onChange(value) {
            return handlers.pasteSettings(value);
          }
        }), /*#__PURE__*/React.createElement("div", {
          className: "wpforms-gutenberg-form-selector-legend",
          dangerouslySetInnerHTML: {
            __html: strings.copy_paste_notice
          }
        }), /*#__PURE__*/React.createElement(Button, {
          className: "wpforms-gutenberg-form-selector-reset-button",
          onClick: openModal
        }, strings.reset_style_settings)), isOpen && /*#__PURE__*/React.createElement(Modal, {
          className: "wpforms-gutenberg-modal",
          title: strings.reset_style_settings,
          onRequestClose: closeModal
        }, /*#__PURE__*/React.createElement("p", null, strings.reset_settings_confirm_text), /*#__PURE__*/React.createElement(Flex, {
          gap: 3,
          align: "center",
          justify: "flex-end"
        }, /*#__PURE__*/React.createElement(Button, {
          isSecondary: true,
          onClick: closeModal
        }, strings.btn_no), /*#__PURE__*/React.createElement(Button, {
          isPrimary: true,
          onClick: function onClick() {
            closeModal();
            handlers.resetSettings();
          }
        }, strings.btn_yes_reset))));
      },
      /**
       * Get block content JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} props Block properties.
       *
       * @returns {JSX.Element} Block content JSX code.
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
        if (!block || !block.innerHTML) {
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
       * @returns {JSX.Element} Block preview JSX code.
       */
      getBlockPreview: function getBlockPreview() {
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_preview_url,
          style: {
            width: '100%'
          }
        }));
      },
      /**
       * Get block empty JSX code.
       *
       * @since 1.8.3
       *
       * @param {object} props Block properties.
       * @returns {JSX.Element} Block empty JSX code.
       */
      getEmptyFormsPreview: function getEmptyFormsPreview(props) {
        var clientId = props.clientId;
        return /*#__PURE__*/React.createElement(Fragment, {
          key: "wpforms-gutenberg-form-selector-fragment-block-empty"
        }, /*#__PURE__*/React.createElement("div", {
          className: "wpforms-no-form-preview"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.block_empty_url
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
          id: "wpforms-builder-iframe"
        }))));
      },
      /**
       * Get block placeholder (form selector) JSX code.
       *
       * @since 1.8.1
       *
       * @param {object} attributes  Block attributes.
       * @param {object} handlers    Block event handlers.
       * @param {object} formOptions Form selector options.
       *
       * @returns {JSX.Element} Block placeholder JSX code.
       */
      getBlockPlaceholder: function getBlockPlaceholder(attributes, handlers, formOptions) {
        return /*#__PURE__*/React.createElement(Placeholder, {
          key: "wpforms-gutenberg-form-selector-wrap",
          className: "wpforms-gutenberg-form-selector-wrap"
        }, /*#__PURE__*/React.createElement("img", {
          src: wpforms_gutenberg_form_selector.logo_url
        }), /*#__PURE__*/React.createElement("h3", null, strings.title), /*#__PURE__*/React.createElement(SelectControl, {
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
     * Get Style Settings panel class.
     *
     * @since 1.8.1
     *
     * @param {object} attributes Block attributes.
     *
     * @returns {string} Style Settings panel class.
     */
    getPanelClass: function getPanelClass(attributes) {
      var cssClass = 'wpforms-gutenberg-panel wpforms-block-settings-' + attributes.clientId;
      if (!app.isFullStylingEnabled()) {
        cssClass += ' disabled_panel';
      }
      return cssClass;
    },
    /**
     * Determine whether the full styling is enabled.
     *
     * @since 1.8.1
     *
     * @returns {boolean} Whether the full styling is enabled.
     */
    isFullStylingEnabled: function isFullStylingEnabled() {
      return wpforms_gutenberg_form_selector.is_modern_markup && wpforms_gutenberg_form_selector.is_full_styling;
    },
    /**
     * Get block container DOM element.
     *
     * @since 1.8.1
     *
     * @param {object} props Block properties.
     *
     * @returns {Element} Block container.
     */
    getBlockContainer: function getBlockContainer(props) {
      var blockSelector = "#block-".concat(props.clientId, " > div");
      var block = document.querySelector(blockSelector);

      // For FSE / Gutenberg plugin we need to take a look inside the iframe.
      if (!block) {
        var editorCanvas = document.querySelector('iframe[name="editor-canvas"]');
        block = editorCanvas && editorCanvas.contentWindow.document.querySelector(blockSelector);
      }
      return block;
    },
    /**
     * Get settings fields event handlers.
     *
     * @since 1.8.1
     *
     * @param {object} props Block properties.
     *
     * @returns {object} Object that contains event handlers for the settings fields.
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
            property = attribute.replace(/[A-Z]/g, function (letter) {
              return "-".concat(letter.toLowerCase());
            }),
            setAttr = {};
          if (container) {
            switch (property) {
              case 'field-size':
              case 'label-size':
              case 'button-size':
                for (var key in sizes[property][value]) {
                  container.style.setProperty("--wpforms-".concat(property, "-").concat(key), sizes[property][value][key]);
                }
                break;
              default:
                container.style.setProperty("--wpforms-".concat(property), value);
            }
          }
          setAttr[attribute] = value;
          props.setAttributes(setAttr);
          triggerServerRender = false;
          this.updateCopyPasteContent();
          $(window).trigger('wpformsFormSelectorStyleAttrChange', [block, props, attribute, value]);
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
          props.setAttributes(setAttr);
          triggerServerRender = true;
          this.updateCopyPasteContent();
        },
        /**
         * Reset Form Styles settings to defaults.
         *
         * @since 1.8.1
         */
        resetSettings: function resetSettings() {
          for (var key in defaultStyleSettings) {
            this.styleAttrChange(key, defaultStyleSettings[key]);
          }
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
            'copyPasteJsonValue': JSON.stringify(content)
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
          var pasteAttributes = app.parseValidateJson(value);
          if (!pasteAttributes) {
            wp.data.dispatch('core/notices').createErrorNotice(strings.copy_paste_error, {
              id: 'wpforms-json-parse-error'
            });
            this.updateCopyPasteContent();
            return;
          }
          pasteAttributes.copyPasteJsonValue = value;
          props.setAttributes(pasteAttributes);
          triggerServerRender = true;
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
     * @returns {boolean|object} Parsed JSON object OR false on error.
     */
    parseValidateJson: function parseValidateJson(value) {
      if (typeof value !== 'string') {
        return false;
      }
      var atts;
      try {
        atts = JSON.parse(value);
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
     * @returns {DOM.element} WPForms icon DOM element.
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
     * Get block attributes.
     *
     * @since 1.8.1
     *
     * @returns {object} Block attributes.
     */
    getBlockAttributes: function getBlockAttributes() {
      // eslint-disable-line max-lines-per-function

      return {
        clientId: {
          type: 'string',
          default: ''
        },
        formId: {
          type: 'string',
          default: defaults.formId
        },
        displayTitle: {
          type: 'boolean',
          default: defaults.displayTitle
        },
        displayDesc: {
          type: 'boolean',
          default: defaults.displayDesc
        },
        preview: {
          type: 'boolean'
        },
        fieldSize: {
          type: 'string',
          default: defaults.fieldSize
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
        labelSize: {
          type: 'string',
          default: defaults.labelSize
        },
        labelColor: {
          type: 'string',
          default: defaults.labelColor
        },
        labelSublabelColor: {
          type: 'string',
          default: defaults.labelSublabelColor
        },
        labelErrorColor: {
          type: 'string',
          default: defaults.labelErrorColor
        },
        buttonSize: {
          type: 'string',
          default: defaults.buttonSize
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
        copyPasteJsonValue: {
          type: 'string',
          default: defaults.copyPasteJsonValue
        }
      };
    },
    /**
     * Get form selector options.
     *
     * @since 1.8.1
     *
     * @returns {Array} Form options.
     */
    getFormOptions: function getFormOptions() {
      var formOptions = wpforms_gutenberg_form_selector.forms.map(function (value) {
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
     * @returns {Array} Size options.
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
     * @param {object} e     Event object.
     * @param {object} props Block properties.
     */
    blockEdit: function blockEdit(e, props) {
      var block = app.getBlockContainer(props);
      if (!block || !block.dataset) {
        return;
      }
      app.initLeadFormSettings(block.parentElement);
    },
    /**
     * Init Lead Form Settings panels.
     *
     * @since 1.8.1
     *
     * @param {Element} block Block element.
     */
    initLeadFormSettings: function initLeadFormSettings(block) {
      if (!block || !block.dataset) {
        return;
      }
      if (!app.isFullStylingEnabled()) {
        return;
      }
      var clientId = block.dataset.block;
      var $form = $(block.querySelector('.wpforms-container'));
      var $panel = $(".wpforms-block-settings-".concat(clientId));
      if ($form.hasClass('wpforms-lead-forms-container')) {
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
     * @param {object} e Event object.
     */
    formLoaded: function formLoaded(e) {
      app.initLeadFormSettings(e.detail.block);
      app.updateAccentColors(e.detail);
      app.loadChoicesJS(e.detail);
      app.initRichTextField(e.detail.formId);
      $(e.detail.block).off('click').on('click', app.blockClick);
    },
    /**
     * Click on the block event handler.
     *
     * @since 1.8.1
     *
     * @param {object} e Event object.
     */
    blockClick: function blockClick(e) {
      app.initLeadFormSettings(e.currentTarget);
    },
    /**
     * Update accent colors of some fields in GB block in Modern Markup mode.
     *
     * @since 1.8.1
     *
     * @param {object} detail Event details object.
     */
    updateAccentColors: function updateAccentColors(detail) {
      if (!wpforms_gutenberg_form_selector.is_modern_markup || !window.WPForms || !window.WPForms.FrontendModern || !detail.block) {
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
     * @param {object} detail Event details object.
     */
    loadChoicesJS: function loadChoicesJS(detail) {
      if (typeof window.Choices !== 'function') {
        return;
      }
      var $form = $(detail.block.querySelector("#wpforms-".concat(detail.formId)));
      $form.find('.choicesjs-select').each(function (idx, el) {
        var $el = $(el);
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
           * In case if select is empty - we return placeholder text back.
           */
          if ($element.prop('multiple')) {
            // On init event.
            $input.data('placeholder', $input.attr('placeholder'));
            if (self.getValue(true).length) {
              $input.removeAttr('placeholder');
            }
          }
          this.disable();
          $field.find('.is-disabled').removeClass('is-disabled');
        };
        try {
          var choicesInstance = new Choices(el, args);

          // Save Choices.js instance for future access.
          $el.data('choicesjs', choicesInstance);
        } catch (e) {} // eslint-disable-line no-empty
      });
    },

    /**
     * Initialize RichText field.
     *
     * @since 1.8.1
     *
     * @param {int} formId Form ID.
     */
    initRichTextField: function initRichTextField(formId) {
      // Set default tab to `Visual`.
      $("#wpforms-".concat(formId, " .wp-editor-wrap")).removeClass('html-active').addClass('tmce-active');
    }
  };

  // Provide access to public functions/properties.
  return app;
}(document, window, jQuery);

// Initialize.
WPForms.FormSelector.init();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfc2xpY2VkVG9BcnJheSIsImFyciIsImkiLCJfYXJyYXlXaXRoSG9sZXMiLCJfaXRlcmFibGVUb0FycmF5TGltaXQiLCJfdW5zdXBwb3J0ZWRJdGVyYWJsZVRvQXJyYXkiLCJfbm9uSXRlcmFibGVSZXN0IiwiVHlwZUVycm9yIiwibyIsIm1pbkxlbiIsIl9hcnJheUxpa2VUb0FycmF5IiwibiIsIk9iamVjdCIsInByb3RvdHlwZSIsInRvU3RyaW5nIiwiY2FsbCIsInNsaWNlIiwiY29uc3RydWN0b3IiLCJuYW1lIiwiQXJyYXkiLCJmcm9tIiwidGVzdCIsImxlbiIsImxlbmd0aCIsImFycjIiLCJfaSIsIlN5bWJvbCIsIml0ZXJhdG9yIiwiX3MiLCJfZSIsIl94IiwiX3IiLCJfYXJyIiwiX24iLCJfZCIsIm5leHQiLCJkb25lIiwicHVzaCIsInZhbHVlIiwiZXJyIiwicmV0dXJuIiwiaXNBcnJheSIsIldQRm9ybXMiLCJ3aW5kb3ciLCJGb3JtU2VsZWN0b3IiLCJkb2N1bWVudCIsIiQiLCJfd3AiLCJ3cCIsIl93cCRzZXJ2ZXJTaWRlUmVuZGVyIiwic2VydmVyU2lkZVJlbmRlciIsIlNlcnZlclNpZGVSZW5kZXIiLCJjb21wb25lbnRzIiwiX3dwJGVsZW1lbnQiLCJlbGVtZW50IiwiY3JlYXRlRWxlbWVudCIsIkZyYWdtZW50IiwidXNlU3RhdGUiLCJjcmVhdGVJbnRlcnBvbGF0ZUVsZW1lbnQiLCJyZWdpc3RlckJsb2NrVHlwZSIsImJsb2NrcyIsIl9yZWYiLCJibG9ja0VkaXRvciIsImVkaXRvciIsIkluc3BlY3RvckNvbnRyb2xzIiwiSW5zcGVjdG9yQWR2YW5jZWRDb250cm9scyIsIlBhbmVsQ29sb3JTZXR0aW5ncyIsIl93cCRjb21wb25lbnRzIiwiU2VsZWN0Q29udHJvbCIsIlRvZ2dsZUNvbnRyb2wiLCJQYW5lbEJvZHkiLCJQbGFjZWhvbGRlciIsIkZsZXgiLCJGbGV4QmxvY2siLCJfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sIiwiVGV4dGFyZWFDb250cm9sIiwiQnV0dG9uIiwiTW9kYWwiLCJfd3Bmb3Jtc19ndXRlbmJlcmdfZm8iLCJ3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yIiwic3RyaW5ncyIsImRlZmF1bHRzIiwic2l6ZXMiLCJkZWZhdWx0U3R5bGVTZXR0aW5ncyIsIl9fIiwiaTE4biIsInRyaWdnZXJTZXJ2ZXJSZW5kZXIiLCIkcG9wdXAiLCJhcHAiLCJpbml0IiwiaW5pdERlZmF1bHRzIiwicmVnaXN0ZXJCbG9jayIsInJlYWR5IiwiZXZlbnRzIiwib24iLCJfIiwiZGVib3VuY2UiLCJibG9ja0VkaXQiLCJmb3JtTG9hZGVkIiwib3BlbkJ1aWxkZXJQb3B1cCIsImNsaWVudElEIiwiaXNFbXB0eU9iamVjdCIsInRtcGwiLCJwYXJlbnQiLCJhZnRlciIsInNpYmxpbmdzIiwidXJsIiwiZ2V0X3N0YXJ0ZWRfdXJsIiwiJGlmcmFtZSIsImZpbmQiLCJidWlsZGVyQ2xvc2VCdXR0b25FdmVudCIsImF0dHIiLCJmYWRlSW4iLCJvZmYiLCJlIiwiYWN0aW9uIiwiZm9ybUlkIiwiZm9ybVRpdGxlIiwibmV3QmxvY2siLCJjcmVhdGVCbG9jayIsImZvcm1zIiwiSUQiLCJwb3N0X3RpdGxlIiwiZGF0YSIsImRpc3BhdGNoIiwicmVtb3ZlQmxvY2siLCJpbnNlcnRCbG9ja3MiLCJ0aXRsZSIsImRlc2NyaXB0aW9uIiwiaWNvbiIsImdldEljb24iLCJrZXl3b3JkcyIsImZvcm1fa2V5d29yZHMiLCJjYXRlZ29yeSIsImF0dHJpYnV0ZXMiLCJnZXRCbG9ja0F0dHJpYnV0ZXMiLCJzdXBwb3J0cyIsImN1c3RvbUNsYXNzTmFtZSIsImhhc0Zvcm1zIiwiZXhhbXBsZSIsInByZXZpZXciLCJlZGl0IiwicHJvcHMiLCJmb3JtT3B0aW9ucyIsImdldEZvcm1PcHRpb25zIiwic2l6ZU9wdGlvbnMiLCJnZXRTaXplT3B0aW9ucyIsImhhbmRsZXJzIiwiZ2V0U2V0dGluZ3NGaWVsZHNIYW5kbGVycyIsImNsaWVudElkIiwic2V0QXR0cmlidXRlcyIsImpzeCIsImpzeFBhcnRzIiwiZ2V0TWFpblNldHRpbmdzIiwiZ2V0RW1wdHlGb3Jtc1ByZXZpZXciLCJnZXRTdHlsZVNldHRpbmdzIiwiZ2V0QWR2YW5jZWRTZXR0aW5ncyIsImdldEJsb2NrRm9ybUNvbnRlbnQiLCJ1cGRhdGVDb3B5UGFzdGVDb250ZW50IiwidHJpZ2dlciIsImdldEJsb2NrUHJldmlldyIsImdldEJsb2NrUGxhY2Vob2xkZXIiLCJzYXZlIiwiZm9yRWFjaCIsImtleSIsInByaW50RW1wdHlGb3Jtc05vdGljZSIsIlJlYWN0IiwiY2xhc3NOYW1lIiwiZm9ybV9zZXR0aW5ncyIsImxhYmVsIiwiZm9ybV9zZWxlY3RlZCIsIm9wdGlvbnMiLCJvbkNoYW5nZSIsImF0dHJDaGFuZ2UiLCJzaG93X3RpdGxlIiwiY2hlY2tlZCIsImRpc3BsYXlUaXRsZSIsInNob3dfZGVzY3JpcHRpb24iLCJkaXNwbGF5RGVzYyIsInBhbmVsX25vdGljZV9oZWFkIiwicGFuZWxfbm90aWNlX3RleHQiLCJocmVmIiwicGFuZWxfbm90aWNlX2xpbmsiLCJyZWwiLCJ0YXJnZXQiLCJwYW5lbF9ub3RpY2VfbGlua190ZXh0Iiwic3R5bGUiLCJkaXNwbGF5IiwidHlwZSIsIm9uQ2xpY2siLCJnZXRGaWVsZFN0eWxlcyIsImdldFBhbmVsQ2xhc3MiLCJmaWVsZF9zdHlsZXMiLCJ1c2VfbW9kZXJuX25vdGljZV9oZWFkIiwidXNlX21vZGVybl9ub3RpY2VfdGV4dCIsInVzZV9tb2Rlcm5fbm90aWNlX2xpbmsiLCJsZWFybl9tb3JlIiwibGVhZF9mb3Jtc19wYW5lbF9ub3RpY2VfaGVhZCIsImxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX3RleHQiLCJnYXAiLCJhbGlnbiIsImp1c3RpZnkiLCJzaXplIiwiZmllbGRTaXplIiwic3R5bGVBdHRyQ2hhbmdlIiwiYm9yZGVyX3JhZGl1cyIsImZpZWxkQm9yZGVyUmFkaXVzIiwiaXNVbml0U2VsZWN0VGFiYmFibGUiLCJjb2xvcnMiLCJfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXIiLCJlbmFibGVBbHBoYSIsInNob3dUaXRsZSIsImNvbG9yU2V0dGluZ3MiLCJmaWVsZEJhY2tncm91bmRDb2xvciIsImJhY2tncm91bmQiLCJmaWVsZEJvcmRlckNvbG9yIiwiYm9yZGVyIiwiZmllbGRUZXh0Q29sb3IiLCJ0ZXh0IiwiZ2V0TGFiZWxTdHlsZXMiLCJsYWJlbF9zdHlsZXMiLCJsYWJlbFNpemUiLCJsYWJlbENvbG9yIiwibGFiZWxTdWJsYWJlbENvbG9yIiwic3VibGFiZWxfaGludHMiLCJyZXBsYWNlIiwibGFiZWxFcnJvckNvbG9yIiwiZXJyb3JfbWVzc2FnZSIsImdldEJ1dHRvblN0eWxlcyIsImJ1dHRvbl9zdHlsZXMiLCJidXR0b25TaXplIiwiYnV0dG9uQm9yZGVyUmFkaXVzIiwiYnV0dG9uQmFja2dyb3VuZENvbG9yIiwiYnV0dG9uVGV4dENvbG9yIiwiYnV0dG9uX2NvbG9yX25vdGljZSIsIl91c2VTdGF0ZSIsIl91c2VTdGF0ZTIiLCJpc09wZW4iLCJzZXRPcGVuIiwib3Blbk1vZGFsIiwiY2xvc2VNb2RhbCIsImNvcHlfcGFzdGVfc2V0dGluZ3MiLCJyb3dzIiwic3BlbGxDaGVjayIsImNvcHlQYXN0ZUpzb25WYWx1ZSIsInBhc3RlU2V0dGluZ3MiLCJkYW5nZXJvdXNseVNldElubmVySFRNTCIsIl9faHRtbCIsImNvcHlfcGFzdGVfbm90aWNlIiwicmVzZXRfc3R5bGVfc2V0dGluZ3MiLCJvblJlcXVlc3RDbG9zZSIsInJlc2V0X3NldHRpbmdzX2NvbmZpcm1fdGV4dCIsImlzU2Vjb25kYXJ5IiwiYnRuX25vIiwiaXNQcmltYXJ5IiwicmVzZXRTZXR0aW5ncyIsImJ0bl95ZXNfcmVzZXQiLCJibG9jayIsImdldEJsb2NrQ29udGFpbmVyIiwiaW5uZXJIVE1MIiwiYmxvY2tIVE1MIiwibG9hZGVkRm9ybUlkIiwic3JjIiwiYmxvY2tfcHJldmlld191cmwiLCJ3aWR0aCIsImJsb2NrX2VtcHR5X3VybCIsImIiLCJhIiwid3Bmb3Jtc19ndWlkZSIsImlkIiwiaGVpZ2h0IiwibG9nb191cmwiLCJjc3NDbGFzcyIsImlzRnVsbFN0eWxpbmdFbmFibGVkIiwiaXNfbW9kZXJuX21hcmt1cCIsImlzX2Z1bGxfc3R5bGluZyIsImJsb2NrU2VsZWN0b3IiLCJjb25jYXQiLCJxdWVyeVNlbGVjdG9yIiwiZWRpdG9yQ2FudmFzIiwiY29udGVudFdpbmRvdyIsImF0dHJpYnV0ZSIsImNvbnRhaW5lciIsInByb3BlcnR5IiwibGV0dGVyIiwidG9Mb3dlckNhc2UiLCJzZXRBdHRyIiwic2V0UHJvcGVydHkiLCJjb250ZW50IiwiYXR0cyIsInNlbGVjdCIsIkpTT04iLCJzdHJpbmdpZnkiLCJwYXN0ZUF0dHJpYnV0ZXMiLCJwYXJzZVZhbGlkYXRlSnNvbiIsImNyZWF0ZUVycm9yTm90aWNlIiwiY29weV9wYXN0ZV9lcnJvciIsInBhcnNlIiwiZXJyb3IiLCJ2aWV3Qm94IiwiZmlsbCIsImQiLCJkZWZhdWx0IiwibWFwIiwidW5zaGlmdCIsImZvcm1fc2VsZWN0Iiwic21hbGwiLCJtZWRpdW0iLCJsYXJnZSIsImRhdGFzZXQiLCJpbml0TGVhZEZvcm1TZXR0aW5ncyIsInBhcmVudEVsZW1lbnQiLCIkZm9ybSIsIiRwYW5lbCIsImhhc0NsYXNzIiwiYWRkQ2xhc3MiLCJjc3MiLCJyZW1vdmVDbGFzcyIsImRldGFpbCIsInVwZGF0ZUFjY2VudENvbG9ycyIsImxvYWRDaG9pY2VzSlMiLCJpbml0UmljaFRleHRGaWVsZCIsImJsb2NrQ2xpY2siLCJjdXJyZW50VGFyZ2V0IiwiRnJvbnRlbmRNb2Rlcm4iLCJ1cGRhdGVHQkJsb2NrUGFnZUluZGljYXRvckNvbG9yIiwidXBkYXRlR0JCbG9ja0ljb25DaG9pY2VzQ29sb3IiLCJ1cGRhdGVHQkJsb2NrUmF0aW5nQ29sb3IiLCJDaG9pY2VzIiwiZWFjaCIsImlkeCIsImVsIiwiJGVsIiwiYXJncyIsIndwZm9ybXNfY2hvaWNlc2pzX2NvbmZpZyIsInNlYXJjaEVuYWJsZWQiLCIkZmllbGQiLCJjbG9zZXN0IiwiY2FsbGJhY2tPbkluaXQiLCJzZWxmIiwiJGVsZW1lbnQiLCJwYXNzZWRFbGVtZW50IiwiJGlucHV0IiwiaW5wdXQiLCJzaXplQ2xhc3MiLCJjb250YWluZXJPdXRlciIsInByb3AiLCJnZXRWYWx1ZSIsInJlbW92ZUF0dHIiLCJkaXNhYmxlIiwiY2hvaWNlc0luc3RhbmNlIiwialF1ZXJ5Il0sInNvdXJjZXMiOlsiZmFrZV9mYjlmZWVmYS5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3RvciwgQ2hvaWNlcyAqL1xuLyoganNoaW50IGVzMzogZmFsc2UsIGVzdmVyc2lvbjogNiAqL1xuXG4ndXNlIHN0cmljdCc7XG5cbi8qKlxuICogR3V0ZW5iZXJnIGVkaXRvciBibG9jay5cbiAqXG4gKiBAc2luY2UgMS44LjFcbiAqL1xudmFyIFdQRm9ybXMgPSB3aW5kb3cuV1BGb3JtcyB8fCB7fTtcblxuV1BGb3Jtcy5Gb3JtU2VsZWN0b3IgPSBXUEZvcm1zLkZvcm1TZWxlY3RvciB8fCAoIGZ1bmN0aW9uKCBkb2N1bWVudCwgd2luZG93LCAkICkge1xuXG5cdGNvbnN0IHsgc2VydmVyU2lkZVJlbmRlcjogU2VydmVyU2lkZVJlbmRlciA9IHdwLmNvbXBvbmVudHMuU2VydmVyU2lkZVJlbmRlciB9ID0gd3A7XG5cdGNvbnN0IHsgY3JlYXRlRWxlbWVudCwgRnJhZ21lbnQsIHVzZVN0YXRlLCBjcmVhdGVJbnRlcnBvbGF0ZUVsZW1lbnQgfSA9IHdwLmVsZW1lbnQ7XG5cdGNvbnN0IHsgcmVnaXN0ZXJCbG9ja1R5cGUgfSA9IHdwLmJsb2Nrcztcblx0Y29uc3QgeyBJbnNwZWN0b3JDb250cm9scywgSW5zcGVjdG9yQWR2YW5jZWRDb250cm9scywgUGFuZWxDb2xvclNldHRpbmdzIH0gPSB3cC5ibG9ja0VkaXRvciB8fCB3cC5lZGl0b3I7XG5cdGNvbnN0IHsgU2VsZWN0Q29udHJvbCwgVG9nZ2xlQ29udHJvbCwgUGFuZWxCb2R5LCBQbGFjZWhvbGRlciwgRmxleCwgRmxleEJsb2NrLCBfX2V4cGVyaW1lbnRhbFVuaXRDb250cm9sLCBUZXh0YXJlYUNvbnRyb2wsIEJ1dHRvbiwgTW9kYWwgfSA9IHdwLmNvbXBvbmVudHM7XG5cdGNvbnN0IHsgc3RyaW5ncywgZGVmYXVsdHMsIHNpemVzIH0gPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yO1xuXHRjb25zdCBkZWZhdWx0U3R5bGVTZXR0aW5ncyA9IGRlZmF1bHRzO1xuXHRjb25zdCB7IF9fIH0gPSB3cC5pMThuO1xuXG5cdC8qKlxuXHQgKiBCbG9ja3MgcnVudGltZSBkYXRhLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICpcblx0ICogQHR5cGUge29iamVjdH1cblx0ICovXG5cdGxldCBibG9ja3MgPSB7fTtcblxuXHQvKipcblx0ICogV2hldGhlciBpdCBpcyBuZWVkZWQgdG8gdHJpZ2dlciBzZXJ2ZXIgcmVuZGVyaW5nLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICpcblx0ICogQHR5cGUge2Jvb2xlYW59XG5cdCAqL1xuXHRsZXQgdHJpZ2dlclNlcnZlclJlbmRlciA9IHRydWU7XG5cblx0LyoqXG5cdCAqIFBvcHVwIGNvbnRhaW5lci5cblx0ICpcblx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHQgKlxuXHQgKiBAdHlwZSB7b2JqZWN0fVxuXHQgKi9cblx0bGV0ICRwb3B1cCA9IHt9O1xuXG5cdC8qKlxuXHQgKiBQdWJsaWMgZnVuY3Rpb25zIGFuZCBwcm9wZXJ0aWVzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICpcblx0ICogQHR5cGUge29iamVjdH1cblx0ICovXG5cdGNvbnN0IGFwcCA9IHtcblxuXHRcdC8qKlxuXHRcdCAqIFN0YXJ0IHRoZSBlbmdpbmUuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKi9cblx0XHRpbml0OiBmdW5jdGlvbigpIHtcblxuXHRcdFx0YXBwLmluaXREZWZhdWx0cygpO1xuXHRcdFx0YXBwLnJlZ2lzdGVyQmxvY2soKTtcblxuXHRcdFx0JCggYXBwLnJlYWR5ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERvY3VtZW50IHJlYWR5LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0cmVhZHk6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRhcHAuZXZlbnRzKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEV2ZW50cy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdGV2ZW50czogZnVuY3Rpb24oKSB7XG5cblx0XHRcdCQoIHdpbmRvdyApXG5cdFx0XHRcdC5vbiggJ3dwZm9ybXNGb3JtU2VsZWN0b3JFZGl0JywgXy5kZWJvdW5jZSggYXBwLmJsb2NrRWRpdCwgMjUwICkgKVxuXHRcdFx0XHQub24oICd3cGZvcm1zRm9ybVNlbGVjdG9yRm9ybUxvYWRlZCcsIF8uZGVib3VuY2UoIGFwcC5mb3JtTG9hZGVkLCAyNTAgKSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBPcGVuIGJ1aWxkZXIgcG9wdXAuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS42LjJcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBjbGllbnRJRCBCbG9jayBDbGllbnQgSUQuXG5cdFx0ICovXG5cdFx0b3BlbkJ1aWxkZXJQb3B1cDogZnVuY3Rpb24oIGNsaWVudElEICkge1xuXG5cdFx0XHRpZiAoICQuaXNFbXB0eU9iamVjdCggJHBvcHVwICkgKSB7XG5cdFx0XHRcdGxldCB0bXBsID0gJCggJyN3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cCcgKTtcblx0XHRcdFx0bGV0IHBhcmVudCA9ICQoICcjd3B3cmFwJyApO1xuXG5cdFx0XHRcdHBhcmVudC5hZnRlciggdG1wbCApO1xuXG5cdFx0XHRcdCRwb3B1cCA9IHBhcmVudC5zaWJsaW5ncyggJyN3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cCcgKTtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgdXJsID0gd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5nZXRfc3RhcnRlZF91cmwsXG5cdFx0XHRcdCRpZnJhbWUgPSAkcG9wdXAuZmluZCggJ2lmcmFtZScgKTtcblxuXHRcdFx0YXBwLmJ1aWxkZXJDbG9zZUJ1dHRvbkV2ZW50KCBjbGllbnRJRCApO1xuXHRcdFx0JGlmcmFtZS5hdHRyKCAnc3JjJywgdXJsICk7XG5cdFx0XHQkcG9wdXAuZmFkZUluKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENsb3NlIGJ1dHRvbiAoaW5zaWRlIHRoZSBmb3JtIGJ1aWxkZXIpIGNsaWNrIGV2ZW50LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtzdHJpbmd9IGNsaWVudElEIEJsb2NrIENsaWVudCBJRC5cblx0XHQgKi9cblx0XHRidWlsZGVyQ2xvc2VCdXR0b25FdmVudDogZnVuY3Rpb24oIGNsaWVudElEICkge1xuXG5cdFx0XHQkcG9wdXBcblx0XHRcdFx0Lm9mZiggJ3dwZm9ybXNCdWlsZGVySW5Qb3B1cENsb3NlJyApXG5cdFx0XHRcdC5vbiggJ3dwZm9ybXNCdWlsZGVySW5Qb3B1cENsb3NlJywgZnVuY3Rpb24oIGUsIGFjdGlvbiwgZm9ybUlkLCBmb3JtVGl0bGUgKSB7XG5cblx0XHRcdFx0XHRpZiAoIGFjdGlvbiAhPT0gJ3NhdmVkJyB8fCAhIGZvcm1JZCApIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBJbnNlcnQgYSBuZXcgYmxvY2sgd2hlbiBhIG5ldyBmb3JtIGlzIGNyZWF0ZWQgZnJvbSB0aGUgcG9wdXAgdG8gdXBkYXRlIHRoZSBmb3JtIGxpc3QgYW5kIGF0dHJpYnV0ZXMuXG5cdFx0XHRcdFx0Y29uc3QgbmV3QmxvY2sgPSB3cC5ibG9ja3MuY3JlYXRlQmxvY2soICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdFx0XHRmb3JtSWQ6IGZvcm1JZC50b1N0cmluZygpLCAvLyBFeHBlY3RzIHN0cmluZyB2YWx1ZSwgbWFrZSBzdXJlIHdlIGluc2VydCBzdHJpbmcuXG5cdFx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGNhbWVsY2FzZVxuXHRcdFx0XHRcdHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuZm9ybXMgPSBbIHsgSUQ6IGZvcm1JZCwgcG9zdF90aXRsZTogZm9ybVRpdGxlIH0gXTtcblxuXHRcdFx0XHRcdC8vIEluc2VydCBhIG5ldyBibG9jay5cblx0XHRcdFx0XHR3cC5kYXRhLmRpc3BhdGNoKCAnY29yZS9ibG9jay1lZGl0b3InICkucmVtb3ZlQmxvY2soIGNsaWVudElEICk7XG5cdFx0XHRcdFx0d3AuZGF0YS5kaXNwYXRjaCggJ2NvcmUvYmxvY2stZWRpdG9yJyApLmluc2VydEJsb2NrcyggbmV3QmxvY2sgKTtcblxuXHRcdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFJlZ2lzdGVyIGJsb2NrLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG1heC1saW5lcy1wZXItZnVuY3Rpb25cblx0XHRyZWdpc3RlckJsb2NrOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0cmVnaXN0ZXJCbG9ja1R5cGUoICd3cGZvcm1zL2Zvcm0tc2VsZWN0b3InLCB7XG5cdFx0XHRcdHRpdGxlOiBzdHJpbmdzLnRpdGxlLFxuXHRcdFx0XHRkZXNjcmlwdGlvbjogc3RyaW5ncy5kZXNjcmlwdGlvbixcblx0XHRcdFx0aWNvbjogYXBwLmdldEljb24oKSxcblx0XHRcdFx0a2V5d29yZHM6IHN0cmluZ3MuZm9ybV9rZXl3b3Jkcyxcblx0XHRcdFx0Y2F0ZWdvcnk6ICd3aWRnZXRzJyxcblx0XHRcdFx0YXR0cmlidXRlczogYXBwLmdldEJsb2NrQXR0cmlidXRlcygpLFxuXHRcdFx0XHRzdXBwb3J0czoge1xuXHRcdFx0XHRcdGN1c3RvbUNsYXNzTmFtZTogYXBwLmhhc0Zvcm1zKCksXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGV4YW1wbGU6IHtcblx0XHRcdFx0XHRhdHRyaWJ1dGVzOiB7XG5cdFx0XHRcdFx0XHRwcmV2aWV3OiB0cnVlLFxuXHRcdFx0XHRcdH0sXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGVkaXQ6IGZ1bmN0aW9uKCBwcm9wcyApIHtcblxuXHRcdFx0XHRcdGNvbnN0IHsgYXR0cmlidXRlcyB9ID0gcHJvcHM7XG5cdFx0XHRcdFx0Y29uc3QgZm9ybU9wdGlvbnMgPSBhcHAuZ2V0Rm9ybU9wdGlvbnMoKTtcblx0XHRcdFx0XHRjb25zdCBzaXplT3B0aW9ucyA9IGFwcC5nZXRTaXplT3B0aW9ucygpO1xuXHRcdFx0XHRcdGNvbnN0IGhhbmRsZXJzID0gYXBwLmdldFNldHRpbmdzRmllbGRzSGFuZGxlcnMoIHByb3BzICk7XG5cblxuXHRcdFx0XHRcdC8vIFN0b3JlIGJsb2NrIGNsaWVudElkIGluIGF0dHJpYnV0ZXMuXG5cdFx0XHRcdFx0aWYgKCAhIGF0dHJpYnV0ZXMuY2xpZW50SWQgKSB7XG5cblx0XHRcdFx0XHRcdC8vIFdlIGp1c3Qgd2FudCBjbGllbnQgSUQgdG8gdXBkYXRlIG9uY2UuXG5cdFx0XHRcdFx0XHQvLyBUaGUgYmxvY2sgZWRpdG9yIGRvZXNuJ3QgaGF2ZSBhIGZpeGVkIGJsb2NrIElELCBzbyB3ZSBuZWVkIHRvIGdldCBpdCBvbiB0aGUgaW5pdGlhbCBsb2FkLCBidXQgb25seSBvbmNlLlxuXHRcdFx0XHRcdFx0cHJvcHMuc2V0QXR0cmlidXRlcyggeyBjbGllbnRJZDogcHJvcHMuY2xpZW50SWQgfSApO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdC8vIE1haW4gYmxvY2sgc2V0dGluZ3MuXG5cdFx0XHRcdFx0bGV0IGpzeCA9IFtcblx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRNYWluU2V0dGluZ3MoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApLFxuXHRcdFx0XHRcdF07XG5cblx0XHRcdFx0XHQvLyBCbG9jayBwcmV2aWV3IHBpY3R1cmUuXG5cdFx0XHRcdFx0aWYgKCAhIGFwcC5oYXNGb3JtcygpICkge1xuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRFbXB0eUZvcm1zUHJldmlldyggcHJvcHMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gRm9ybSBzdHlsZSBzZXR0aW5ncyAmIGJsb2NrIGNvbnRlbnQuXG5cdFx0XHRcdFx0aWYgKCBhdHRyaWJ1dGVzLmZvcm1JZCApIHtcblx0XHRcdFx0XHRcdGpzeC5wdXNoKFxuXHRcdFx0XHRcdFx0XHRhcHAuanN4UGFydHMuZ2V0U3R5bGVTZXR0aW5ncyggYXR0cmlidXRlcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICksXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRBZHZhbmNlZFNldHRpbmdzKCBhdHRyaWJ1dGVzLCBoYW5kbGVycyApLFxuXHRcdFx0XHRcdFx0XHRhcHAuanN4UGFydHMuZ2V0QmxvY2tGb3JtQ29udGVudCggcHJvcHMgKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdGhhbmRsZXJzLnVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQoKTtcblxuXHRcdFx0XHRcdFx0JCggd2luZG93ICkudHJpZ2dlciggJ3dwZm9ybXNGb3JtU2VsZWN0b3JFZGl0JywgWyBwcm9wcyBdICk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gQmxvY2sgcHJldmlldyBwaWN0dXJlLlxuXHRcdFx0XHRcdGlmICggYXR0cmlidXRlcy5wcmV2aWV3ICkge1xuXHRcdFx0XHRcdFx0anN4LnB1c2goXG5cdFx0XHRcdFx0XHRcdGFwcC5qc3hQYXJ0cy5nZXRCbG9ja1ByZXZpZXcoKSxcblx0XHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRcdHJldHVybiBqc3g7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0Ly8gQmxvY2sgcGxhY2Vob2xkZXIgKGZvcm0gc2VsZWN0b3IpLlxuXHRcdFx0XHRcdGpzeC5wdXNoKFxuXHRcdFx0XHRcdFx0YXBwLmpzeFBhcnRzLmdldEJsb2NrUGxhY2Vob2xkZXIoIHByb3BzLmF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApLFxuXHRcdFx0XHRcdCk7XG5cblx0XHRcdFx0XHRyZXR1cm4ganN4O1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRzYXZlOiAoKSA9PiBudWxsLFxuXHRcdFx0fSApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBJbml0IGRlZmF1bHQgc3R5bGUgc2V0dGluZ3MuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKi9cblx0XHRpbml0RGVmYXVsdHM6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRbICdmb3JtSWQnLCAnY29weVBhc3RlSnNvblZhbHVlJyBdLmZvckVhY2goIGtleSA9PiBkZWxldGUgZGVmYXVsdFN0eWxlU2V0dGluZ3NbIGtleSBdICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENoZWNrIGlmIHNpdGUgaGFzIGZvcm1zLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHRcdCAqXG5cdFx0ICogQHJldHVybnMge2Jvb2xlYW59IFdoZXRoZXIgc2l0ZSBoYXMgYXRsZWFzdCBvbmUgZm9ybS5cblx0XHQgKi9cblx0XHRoYXNGb3JtczogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gYXBwLmdldEZvcm1PcHRpb25zKCkubGVuZ3RoID4gMTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQmxvY2sgSlNYIHBhcnRzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAdHlwZSB7b2JqZWN0fVxuXHRcdCAqL1xuXHRcdGpzeFBhcnRzOiB7XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IG1haW4gc2V0dGluZ3MgSlNYIGNvZGUuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IGF0dHJpYnV0ZXMgIEJsb2NrIGF0dHJpYnV0ZXMuXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gaGFuZGxlcnMgICAgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gZm9ybU9wdGlvbnMgRm9ybSBzZWxlY3RvciBvcHRpb25zLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm5zIHtKU1guRWxlbWVudH0gTWFpbiBzZXR0aW5nIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRNYWluU2V0dGluZ3M6IGZ1bmN0aW9uKCBhdHRyaWJ1dGVzLCBoYW5kbGVycywgZm9ybU9wdGlvbnMgKSB7XG5cblx0XHRcdFx0aWYgKCAhIGFwcC5oYXNGb3JtcygpICkge1xuXHRcdFx0XHRcdHJldHVybiBhcHAuanN4UGFydHMucHJpbnRFbXB0eUZvcm1zTm90aWNlKCBhdHRyaWJ1dGVzLmNsaWVudElkICk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxJbnNwZWN0b3JDb250cm9scyBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWluc3BlY3Rvci1tYWluLXNldHRpbmdzXCI+XG5cdFx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsXCIgdGl0bGU9eyBzdHJpbmdzLmZvcm1fc2V0dGluZ3MgfT5cblx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuZm9ybV9zZWxlY3RlZCB9XG5cdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBhdHRyaWJ1dGVzLmZvcm1JZCB9XG5cdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IGZvcm1PcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17IHZhbHVlID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdmb3JtSWQnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PFRvZ2dsZUNvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2hvd190aXRsZSB9XG5cdFx0XHRcdFx0XHRcdFx0Y2hlY2tlZD17IGF0dHJpYnV0ZXMuZGlzcGxheVRpdGxlIH1cblx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17IHZhbHVlID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdkaXNwbGF5VGl0bGUnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PFRvZ2dsZUNvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2hvd19kZXNjcmlwdGlvbiB9XG5cdFx0XHRcdFx0XHRcdFx0Y2hlY2tlZD17IGF0dHJpYnV0ZXMuZGlzcGxheURlc2MgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgdmFsdWUgPT4gaGFuZGxlcnMuYXR0ckNoYW5nZSggJ2Rpc3BsYXlEZXNjJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZVwiPlxuXHRcdFx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLnBhbmVsX25vdGljZV9oZWFkIH08L3N0cm9uZz5cblx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MucGFuZWxfbm90aWNlX3RleHQgfVxuXHRcdFx0XHRcdFx0XHRcdDxhIGhyZWY9e3N0cmluZ3MucGFuZWxfbm90aWNlX2xpbmt9IHJlbD1cIm5vcmVmZXJyZXJcIiB0YXJnZXQ9XCJfYmxhbmtcIj57IHN0cmluZ3MucGFuZWxfbm90aWNlX2xpbmtfdGV4dCB9PC9hPlxuXHRcdFx0XHRcdFx0XHQ8L3A+XG5cdFx0XHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdFx0XHQ8L0luc3BlY3RvckNvbnRyb2xzPlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBQcmludCBlbXB0eSBmb3JtcyBub3RpY2UuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIHtWRVJTSU9OfVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBjbGllbnRJZCBCbG9jayBjbGllbnQgSUQuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybnMge0pTWC5FbGVtZW50fSBGaWVsZCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdHByaW50RW1wdHlGb3Jtc05vdGljZTogZnVuY3Rpb24oIGNsaWVudElkICkge1xuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxJbnNwZWN0b3JDb250cm9scyBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWluc3BlY3Rvci1tYWluLXNldHRpbmdzXCI+XG5cdFx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsXCIgdGl0bGU9eyBzdHJpbmdzLmZvcm1fc2V0dGluZ3MgfT5cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlIHdwZm9ybXMtd2FybmluZyB3cGZvcm1zLWVtcHR5LWZvcm0tbm90aWNlXCIgc3R5bGU9e3sgZGlzcGxheTogJ2Jsb2NrJyB9fT5cblx0XHRcdFx0XHRcdFx0XHQ8c3Ryb25nPnsgX18oICdZb3UgaGF2ZW7igJl0IGNyZWF0ZWQgYSBmb3JtLCB5ZXQhJywgJ3dwZm9ybXMtbGl0ZScgKSB9PC9zdHJvbmc+XG5cdFx0XHRcdFx0XHRcdFx0eyBfXyggJ1doYXQgYXJlIHlvdSB3YWl0aW5nIGZvcj8nLCAnd3Bmb3Jtcy1saXRlJyApIH1cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzc05hbWU9XCJnZXQtc3RhcnRlZC1idXR0b24gY29tcG9uZW50cy1idXR0b24gaXMtc2Vjb25kYXJ5XCJcblx0XHRcdFx0XHRcdFx0XHRvbkNsaWNrPXtcblx0XHRcdFx0XHRcdFx0XHRcdCgpID0+IHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0YXBwLm9wZW5CdWlsZGVyUG9wdXAoIGNsaWVudElkICk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHQ+XG5cdFx0XHRcdFx0XHRcdFx0eyBfXyggJ0dldCBTdGFydGVkJywgJ3dwZm9ybXMtbGl0ZScgKSB9XG5cdFx0XHRcdFx0XHRcdDwvYnV0dG9uPlxuXHRcdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdFx0PC9JbnNwZWN0b3JDb250cm9scz5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IEZpZWxkIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gYXR0cmlidXRlcyAgQmxvY2sgYXR0cmlidXRlcy5cblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBoYW5kbGVycyAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBzaXplT3B0aW9ucyBTaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybnMge0pTWC5FbGVtZW50fSBGaWVsZCBzdHlsZXMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEZpZWxkU3R5bGVzOiBmdW5jdGlvbiggYXR0cmlidXRlcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG1heC1saW5lcy1wZXItZnVuY3Rpb25cblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQYW5lbEJvZHkgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIGF0dHJpYnV0ZXMgKSB9IHRpdGxlPXsgc3RyaW5ncy5maWVsZF9zdHlsZXMgfT5cblx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZSB3cGZvcm1zLXVzZS1tb2Rlcm4tbm90aWNlXCI+XG5cdFx0XHRcdFx0XHRcdDxzdHJvbmc+eyBzdHJpbmdzLnVzZV9tb2Rlcm5fbm90aWNlX2hlYWQgfTwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MudXNlX21vZGVybl9ub3RpY2VfdGV4dCB9IDxhIGhyZWY9e3N0cmluZ3MudXNlX21vZGVybl9ub3RpY2VfbGlua30gcmVsPVwibm9yZWZlcnJlclwiIHRhcmdldD1cIl9ibGFua1wiPnsgc3RyaW5ncy5sZWFybl9tb3JlIH08L2E+XG5cdFx0XHRcdFx0XHQ8L3A+XG5cblx0XHRcdFx0XHRcdDxwIGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZSB3cGZvcm1zLXdhcm5pbmcgd3Bmb3Jtcy1sZWFkLWZvcm0tbm90aWNlXCIgc3R5bGU9e3sgZGlzcGxheTogJ25vbmUnIH19PlxuXHRcdFx0XHRcdFx0XHQ8c3Ryb25nPnsgc3RyaW5ncy5sZWFkX2Zvcm1zX3BhbmVsX25vdGljZV9oZWFkIH08L3N0cm9uZz5cblx0XHRcdFx0XHRcdFx0eyBzdHJpbmdzLmxlYWRfZm9ybXNfcGFuZWxfbm90aWNlX3RleHQgfVxuXHRcdFx0XHRcdFx0PC9wPlxuXG5cdFx0XHRcdFx0XHQ8RmxleCBnYXA9ezR9IGFsaWduPVwiZmxleC1zdGFydFwiIGNsYXNzTmFtZT17J3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZmxleCd9IGp1c3RpZnk9XCJzcGFjZS1iZXR3ZWVuXCI+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5zaXplIH1cblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5maWVsZFNpemUgfVxuXHRcdFx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zIH1cblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgdmFsdWUgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRTaXplJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PF9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsPXsgc3RyaW5ncy5ib3JkZXJfcmFkaXVzIH1cblx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5maWVsZEJvcmRlclJhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRpc1VuaXRTZWxlY3RUYWJiYWJsZVxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyB2YWx1ZSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdmaWVsZEJvcmRlclJhZGl1cycsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PC9GbGV4PlxuXG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXtbXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBhdHRyaWJ1dGVzLmZpZWxkQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogdmFsdWUgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnZmllbGRCYWNrZ3JvdW5kQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5iYWNrZ3JvdW5kLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU6IGF0dHJpYnV0ZXMuZmllbGRCb3JkZXJDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6IHZhbHVlID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkQm9yZGVyQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5ib3JkZXIsXG5cdFx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogYXR0cmlidXRlcy5maWVsZFRleHRDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6IHZhbHVlID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2ZpZWxkVGV4dENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MudGV4dCxcblx0XHRcdFx0XHRcdFx0XHRcdH0sXG5cdFx0XHRcdFx0XHRcdFx0XX1cblx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdDwvUGFuZWxCb2R5PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgTGFiZWwgc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBhdHRyaWJ1dGVzICBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IHNpemVPcHRpb25zIFNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJucyB7SlNYLkVsZW1lbnR9IExhYmVsIHN0eWxlcyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0TGFiZWxTdHlsZXM6IGZ1bmN0aW9uKCBhdHRyaWJ1dGVzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMgKSB7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGFwcC5nZXRQYW5lbENsYXNzKCBhdHRyaWJ1dGVzICkgfSB0aXRsZT17IHN0cmluZ3MubGFiZWxfc3R5bGVzIH0+XG5cdFx0XHRcdFx0XHQ8U2VsZWN0Q29udHJvbFxuXHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5sYWJlbFNpemUgfVxuXHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZpeC1ib3R0b20tbWFyZ2luXCJcblx0XHRcdFx0XHRcdFx0b3B0aW9ucz17IHNpemVPcHRpb25zfVxuXHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17IHZhbHVlID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2xhYmVsU2l6ZScsIHZhbHVlICkgfVxuXHRcdFx0XHRcdFx0Lz5cblxuXHRcdFx0XHRcdFx0PGRpdiBjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBpY2tlclwiPlxuXHRcdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29udHJvbC1sYWJlbFwiPnsgc3RyaW5ncy5jb2xvcnMgfTwvZGl2PlxuXHRcdFx0XHRcdFx0XHQ8UGFuZWxDb2xvclNldHRpbmdzXG5cdFx0XHRcdFx0XHRcdFx0X19leHBlcmltZW50YWxJc1JlbmRlcmVkSW5TaWRlYmFyXG5cdFx0XHRcdFx0XHRcdFx0ZW5hYmxlQWxwaGFcblx0XHRcdFx0XHRcdFx0XHRzaG93VGl0bGU9eyBmYWxzZSB9XG5cdFx0XHRcdFx0XHRcdFx0Y2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb2xvci1wYW5lbFwiXG5cdFx0XHRcdFx0XHRcdFx0Y29sb3JTZXR0aW5ncz17W1xuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogYXR0cmlidXRlcy5sYWJlbENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogdmFsdWUgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnbGFiZWxDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmxhYmVsLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU6IGF0dHJpYnV0ZXMubGFiZWxTdWJsYWJlbENvbG9yLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRvbkNoYW5nZTogdmFsdWUgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnbGFiZWxTdWJsYWJlbENvbG9yJywgdmFsdWUgKSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3Muc3VibGFiZWxfaGludHMucmVwbGFjZSggJyZhbXA7JywgJyYnICksXG5cdFx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogYXR0cmlidXRlcy5sYWJlbEVycm9yQ29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiB2YWx1ZSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdsYWJlbEVycm9yQ29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy5lcnJvcl9tZXNzYWdlLFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdfVxuXHRcdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PC9QYW5lbEJvZHk+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBCdXR0b24gc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBhdHRyaWJ1dGVzICBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IHNpemVPcHRpb25zIFNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJucyB7SlNYLkVsZW1lbnR9ICBCdXR0b24gc3R5bGVzIEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRCdXR0b25TdHlsZXM6IGZ1bmN0aW9uKCBhdHRyaWJ1dGVzLCBoYW5kbGVycywgc2l6ZU9wdGlvbnMgKSB7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8UGFuZWxCb2R5IGNsYXNzTmFtZT17IGFwcC5nZXRQYW5lbENsYXNzKCBhdHRyaWJ1dGVzICkgfSB0aXRsZT17IHN0cmluZ3MuYnV0dG9uX3N0eWxlcyB9PlxuXHRcdFx0XHRcdFx0PEZsZXggZ2FwPXs0fSBhbGlnbj1cImZsZXgtc3RhcnRcIiBjbGFzc05hbWU9eyd3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZsZXgnfSBqdXN0aWZ5PVwic3BhY2UtYmV0d2VlblwiPlxuXHRcdFx0XHRcdFx0XHQ8RmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0XHRcdDxTZWxlY3RDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3Muc2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZT17IGF0dHJpYnV0ZXMuYnV0dG9uU2l6ZSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgc2l6ZU9wdGlvbnMgfVxuXHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U9eyB2YWx1ZSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdidXR0b25TaXplJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0Lz5cblx0XHRcdFx0XHRcdFx0PC9GbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdDxGbGV4QmxvY2s+XG5cdFx0XHRcdFx0XHRcdFx0PF9fZXhwZXJpbWVudGFsVW5pdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgdmFsdWUgPT4gaGFuZGxlcnMuc3R5bGVBdHRyQ2hhbmdlKCAnYnV0dG9uQm9yZGVyUmFkaXVzJywgdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdFx0XHRsYWJlbD17IHN0cmluZ3MuYm9yZGVyX3JhZGl1cyB9XG5cdFx0XHRcdFx0XHRcdFx0XHRpc1VuaXRTZWxlY3RUYWJiYWJsZVxuXHRcdFx0XHRcdFx0XHRcdFx0dmFsdWU9eyBhdHRyaWJ1dGVzLmJ1dHRvbkJvcmRlclJhZGl1cyB9IC8+XG5cdFx0XHRcdFx0XHRcdDwvRmxleEJsb2NrPlxuXHRcdFx0XHRcdFx0PC9GbGV4PlxuXG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItY29sb3ItcGlja2VyXCI+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1jb250cm9sLWxhYmVsXCI+eyBzdHJpbmdzLmNvbG9ycyB9PC9kaXY+XG5cdFx0XHRcdFx0XHRcdDxQYW5lbENvbG9yU2V0dGluZ3Ncblx0XHRcdFx0XHRcdFx0XHRfX2V4cGVyaW1lbnRhbElzUmVuZGVyZWRJblNpZGViYXJcblx0XHRcdFx0XHRcdFx0XHRlbmFibGVBbHBoYVxuXHRcdFx0XHRcdFx0XHRcdHNob3dUaXRsZT17IGZhbHNlIH1cblx0XHRcdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWNvbG9yLXBhbmVsXCJcblx0XHRcdFx0XHRcdFx0XHRjb2xvclNldHRpbmdzPXtbXG5cdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHZhbHVlOiBhdHRyaWJ1dGVzLmJ1dHRvbkJhY2tncm91bmRDb2xvcixcblx0XHRcdFx0XHRcdFx0XHRcdFx0b25DaGFuZ2U6IHZhbHVlID0+IGhhbmRsZXJzLnN0eWxlQXR0ckNoYW5nZSggJ2J1dHRvbkJhY2tncm91bmRDb2xvcicsIHZhbHVlICksXG5cdFx0XHRcdFx0XHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmJhY2tncm91bmQsXG5cdFx0XHRcdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR2YWx1ZTogYXR0cmlidXRlcy5idXR0b25UZXh0Q29sb3IsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlOiB2YWx1ZSA9PiBoYW5kbGVycy5zdHlsZUF0dHJDaGFuZ2UoICdidXR0b25UZXh0Q29sb3InLCB2YWx1ZSApLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRsYWJlbDogc3RyaW5ncy50ZXh0LFxuXHRcdFx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0XHRdfSAvPlxuXHRcdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItbGVnZW5kIHdwZm9ybXMtYnV0dG9uLWNvbG9yLW5vdGljZVwiPlxuXHRcdFx0XHRcdFx0XHRcdHsgc3RyaW5ncy5idXR0b25fY29sb3Jfbm90aWNlIH1cblx0XHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHQ8L2Rpdj5cblx0XHRcdFx0XHQ8L1BhbmVsQm9keT5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IHN0eWxlIHNldHRpbmdzIEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBhdHRyaWJ1dGVzICBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IGhhbmRsZXJzICAgIEJsb2NrIGV2ZW50IGhhbmRsZXJzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IHNpemVPcHRpb25zIFNpemUgc2VsZWN0b3Igb3B0aW9ucy5cblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJucyB7SlNYLkVsZW1lbnR9IEluc3BlY3RvciBjb250cm9scyBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0U3R5bGVTZXR0aW5nczogZnVuY3Rpb24oIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBzaXplT3B0aW9ucyApIHtcblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxJbnNwZWN0b3JDb250cm9scyBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXN0eWxlLXNldHRpbmdzXCI+XG5cdFx0XHRcdFx0XHR7IGFwcC5qc3hQYXJ0cy5nZXRGaWVsZFN0eWxlcyggYXR0cmlidXRlcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkgfVxuXHRcdFx0XHRcdFx0eyBhcHAuanN4UGFydHMuZ2V0TGFiZWxTdHlsZXMoIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBzaXplT3B0aW9ucyApIH1cblx0XHRcdFx0XHRcdHsgYXBwLmpzeFBhcnRzLmdldEJ1dHRvblN0eWxlcyggYXR0cmlidXRlcywgaGFuZGxlcnMsIHNpemVPcHRpb25zICkgfVxuXHRcdFx0XHRcdDwvSW5zcGVjdG9yQ29udHJvbHM+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBhZHZhbmNlZCBzZXR0aW5ncyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gYXR0cmlidXRlcyBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IGhhbmRsZXJzICAgQmxvY2sgZXZlbnQgaGFuZGxlcnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybnMge0pTWC5FbGVtZW50fSBJbnNwZWN0b3IgYWR2YW5jZWQgY29udHJvbHMgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEFkdmFuY2VkU2V0dGluZ3M6IGZ1bmN0aW9uKCBhdHRyaWJ1dGVzLCBoYW5kbGVycyApIHtcblxuXHRcdFx0XHRjb25zdCBbIGlzT3Blbiwgc2V0T3BlbiBdID0gdXNlU3RhdGUoIGZhbHNlICk7XG5cdFx0XHRcdGNvbnN0IG9wZW5Nb2RhbCA9ICgpID0+IHNldE9wZW4oIHRydWUgKTtcblx0XHRcdFx0Y29uc3QgY2xvc2VNb2RhbCA9ICgpID0+IHNldE9wZW4oIGZhbHNlICk7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8SW5zcGVjdG9yQWR2YW5jZWRDb250cm9scz5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPXsgYXBwLmdldFBhbmVsQ2xhc3MoIGF0dHJpYnV0ZXMgKSB9PlxuXHRcdFx0XHRcdFx0XHQ8VGV4dGFyZWFDb250cm9sXG5cdFx0XHRcdFx0XHRcdFx0bGFiZWw9eyBzdHJpbmdzLmNvcHlfcGFzdGVfc2V0dGluZ3MgfVxuXHRcdFx0XHRcdFx0XHRcdHJvd3M9XCI0XCJcblx0XHRcdFx0XHRcdFx0XHRzcGVsbENoZWNrPVwiZmFsc2VcIlxuXHRcdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5jb3B5UGFzdGVKc29uVmFsdWUgfVxuXHRcdFx0XHRcdFx0XHRcdG9uQ2hhbmdlPXsgdmFsdWUgPT4gaGFuZGxlcnMucGFzdGVTZXR0aW5ncyggdmFsdWUgKSB9XG5cdFx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1sZWdlbmRcIiBkYW5nZXJvdXNseVNldElubmVySFRNTD17eyBfX2h0bWw6IHN0cmluZ3MuY29weV9wYXN0ZV9ub3RpY2UgfX0+PC9kaXY+XG5cblx0XHRcdFx0XHRcdFx0PEJ1dHRvbiBjbGFzc05hbWU9J3dwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItcmVzZXQtYnV0dG9uJyBvbkNsaWNrPXsgb3Blbk1vZGFsIH0+eyBzdHJpbmdzLnJlc2V0X3N0eWxlX3NldHRpbmdzIH08L0J1dHRvbj5cblx0XHRcdFx0XHRcdDwvZGl2PlxuXG5cdFx0XHRcdFx0XHR7IGlzT3BlbiAmJiAoXG5cdFx0XHRcdFx0XHRcdDxNb2RhbCAgY2xhc3NOYW1lPVwid3Bmb3Jtcy1ndXRlbmJlcmctbW9kYWxcIlxuXHRcdFx0XHRcdFx0XHRcdHRpdGxlPXsgc3RyaW5ncy5yZXNldF9zdHlsZV9zZXR0aW5nc31cblx0XHRcdFx0XHRcdFx0XHRvblJlcXVlc3RDbG9zZT17IGNsb3NlTW9kYWwgfT5cblxuXHRcdFx0XHRcdFx0XHRcdDxwPnsgc3RyaW5ncy5yZXNldF9zZXR0aW5nc19jb25maXJtX3RleHQgfTwvcD5cblxuXHRcdFx0XHRcdFx0XHRcdDxGbGV4IGdhcD17M30gYWxpZ249XCJjZW50ZXJcIiBqdXN0aWZ5PVwiZmxleC1lbmRcIj5cblx0XHRcdFx0XHRcdFx0XHRcdDxCdXR0b24gaXNTZWNvbmRhcnkgb25DbGljaz17IGNsb3NlTW9kYWwgfT5cblx0XHRcdFx0XHRcdFx0XHRcdFx0e3N0cmluZ3MuYnRuX25vfVxuXHRcdFx0XHRcdFx0XHRcdFx0PC9CdXR0b24+XG5cblx0XHRcdFx0XHRcdFx0XHRcdDxCdXR0b24gaXNQcmltYXJ5IG9uQ2xpY2s9eyAoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGNsb3NlTW9kYWwoKTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0aGFuZGxlcnMucmVzZXRTZXR0aW5ncygpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fSB9PlxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7IHN0cmluZ3MuYnRuX3llc19yZXNldCB9XG5cdFx0XHRcdFx0XHRcdFx0XHQ8L0J1dHRvbj5cblx0XHRcdFx0XHRcdFx0XHQ8L0ZsZXg+XG5cdFx0XHRcdFx0XHRcdDwvTW9kYWw+XG5cdFx0XHRcdFx0XHQpIH1cblx0XHRcdFx0XHQ8L0luc3BlY3RvckFkdmFuY2VkQ29udHJvbHM+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBibG9jayBjb250ZW50IEpTWCBjb2RlLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdFx0ICpcblx0XHRcdCAqIEByZXR1cm5zIHtKU1guRWxlbWVudH0gQmxvY2sgY29udGVudCBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0QmxvY2tGb3JtQ29udGVudDogZnVuY3Rpb24oIHByb3BzICkge1xuXG5cdFx0XHRcdGlmICggdHJpZ2dlclNlcnZlclJlbmRlciApIHtcblxuXHRcdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0XHQ8U2VydmVyU2lkZVJlbmRlclxuXHRcdFx0XHRcdFx0XHRrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLXNlcnZlci1zaWRlLXJlbmRlcmVyXCJcblx0XHRcdFx0XHRcdFx0YmxvY2s9XCJ3cGZvcm1zL2Zvcm0tc2VsZWN0b3JcIlxuXHRcdFx0XHRcdFx0XHRhdHRyaWJ1dGVzPXsgcHJvcHMuYXR0cmlidXRlcyB9XG5cdFx0XHRcdFx0XHQvPlxuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRjb25zdCBjbGllbnRJZCA9IHByb3BzLmNsaWVudElkO1xuXHRcdFx0XHRjb25zdCBibG9jayA9IGFwcC5nZXRCbG9ja0NvbnRhaW5lciggcHJvcHMgKTtcblxuXHRcdFx0XHQvLyBJbiB0aGUgY2FzZSBvZiBlbXB0eSBjb250ZW50LCB1c2Ugc2VydmVyIHNpZGUgcmVuZGVyZXIuXG5cdFx0XHRcdC8vIFRoaXMgaGFwcGVucyB3aGVuIHRoZSBibG9jayBpcyBkdXBsaWNhdGVkIG9yIGNvbnZlcnRlZCB0byBhIHJldXNhYmxlIGJsb2NrLlxuXHRcdFx0XHRpZiAoICEgYmxvY2sgfHwgISBibG9jay5pbm5lckhUTUwgKSB7XG5cdFx0XHRcdFx0dHJpZ2dlclNlcnZlclJlbmRlciA9IHRydWU7XG5cblx0XHRcdFx0XHRyZXR1cm4gYXBwLmpzeFBhcnRzLmdldEJsb2NrRm9ybUNvbnRlbnQoIHByb3BzICk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRibG9ja3NbIGNsaWVudElkIF0gPSBibG9ja3NbIGNsaWVudElkIF0gfHwge307XG5cdFx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXS5ibG9ja0hUTUwgPSBibG9jay5pbm5lckhUTUw7XG5cdFx0XHRcdGJsb2Nrc1sgY2xpZW50SWQgXS5sb2FkZWRGb3JtSWQgPSBwcm9wcy5hdHRyaWJ1dGVzLmZvcm1JZDtcblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxGcmFnbWVudCBrZXk9XCJ3cGZvcm1zLWd1dGVuYmVyZy1mb3JtLXNlbGVjdG9yLWZyYWdtZW50LWZvcm0taHRtbFwiPlxuXHRcdFx0XHRcdFx0PGRpdiBkYW5nZXJvdXNseVNldElubmVySFRNTD17eyBfX2h0bWw6IGJsb2Nrc1sgY2xpZW50SWQgXS5ibG9ja0hUTUwgfX0gLz5cblx0XHRcdFx0XHQ8L0ZyYWdtZW50PlxuXHRcdFx0XHQpO1xuXHRcdFx0fSxcblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBHZXQgYmxvY2sgcHJldmlldyBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcmV0dXJucyB7SlNYLkVsZW1lbnR9IEJsb2NrIHByZXZpZXcgSlNYIGNvZGUuXG5cdFx0XHQgKi9cblx0XHRcdGdldEJsb2NrUHJldmlldzogZnVuY3Rpb24oKSB7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stcHJldmlld1wiPlxuXHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX3ByZXZpZXdfdXJsIH0gc3R5bGU9e3sgd2lkdGg6ICcxMDAlJyB9fSAvPlxuXHRcdFx0XHRcdDwvRnJhZ21lbnQ+XG5cdFx0XHRcdCk7XG5cdFx0XHR9LFxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEdldCBibG9jayBlbXB0eSBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2Uge1ZFUlNJT059XG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IHByb3BzIEJsb2NrIHByb3BlcnRpZXMuXG5cdFx0XHQgKiBAcmV0dXJucyB7SlNYLkVsZW1lbnR9IEJsb2NrIGVtcHR5IEpTWCBjb2RlLlxuXHRcdFx0ICovXG5cdFx0XHRnZXRFbXB0eUZvcm1zUHJldmlldzogZnVuY3Rpb24oIHByb3BzICkge1xuXG5cdFx0XHRcdGNvbnN0IGNsaWVudElkID0gcHJvcHMuY2xpZW50SWQ7XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQ8RnJhZ21lbnRcblx0XHRcdFx0XHRcdGtleT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3ItZnJhZ21lbnQtYmxvY2stZW1wdHlcIj5cblx0XHRcdFx0XHRcdDxkaXYgY2xhc3NOYW1lPVwid3Bmb3Jtcy1uby1mb3JtLXByZXZpZXdcIj5cblx0XHRcdFx0XHRcdFx0PGltZyBzcmM9eyB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmJsb2NrX2VtcHR5X3VybCB9IC8+XG5cdFx0XHRcdFx0XHRcdDxwPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J1lvdSBjYW4gdXNlIDxiPldQRm9ybXM8L2I+IHRvIGJ1aWxkIGNvbnRhY3QgZm9ybXMsIHN1cnZleXMsIHBheW1lbnQgZm9ybXMsIGFuZCBtb3JlIHdpdGgganVzdCBhIGZldyBjbGlja3MuJyxcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQnd3Bmb3Jtcy1saXRlJ1xuXHRcdFx0XHRcdFx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdFx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0YjogPHN0cm9uZyAvPixcblx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0KVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PC9wPlxuXHRcdFx0XHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzc05hbWU9XCJnZXQtc3RhcnRlZC1idXR0b24gY29tcG9uZW50cy1idXR0b24gaXMtcHJpbWFyeVwiXG5cdFx0XHRcdFx0XHRcdFx0b25DbGljaz17XG5cdFx0XHRcdFx0XHRcdFx0XHQoKSA9PiB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGFwcC5vcGVuQnVpbGRlclBvcHVwKCBjbGllbnRJZCApO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0PlxuXHRcdFx0XHRcdFx0XHRcdHsgX18oICdHZXQgU3RhcnRlZCcsICd3cGZvcm1zLWxpdGUnICkgfVxuXHRcdFx0XHRcdFx0XHQ8L2J1dHRvbj5cblx0XHRcdFx0XHRcdFx0PHAgY2xhc3NOYW1lPVwiZW1wdHktZGVzY1wiPlxuXHRcdFx0XHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdFx0XHRcdGNyZWF0ZUludGVycG9sYXRlRWxlbWVudChcblx0XHRcdFx0XHRcdFx0XHRcdFx0X18oXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J05lZWQgc29tZSBoZWxwPyBDaGVjayBvdXQgb3VyIDxhPmNvbXByZWhlbnNpdmUgZ3VpZGUuPC9hPicsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0J3dwZm9ybXMtbGl0ZSdcblx0XHRcdFx0XHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdFx0XHRcdFx0e1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdGE6IDxhIGhyZWY9e3dwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3Iud3Bmb3Jtc19ndWlkZX0gdGFyZ2V0PVwiX2JsYW5rXCIgcmVsPVwibm9vcGVuZXIgbm9yZWZlcnJlclwiLz4sXG5cdFx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRcdClcblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdDwvcD5cblxuXHRcdFx0XHRcdFx0XHR7LyogVGVtcGxhdGUgZm9yIHBvcHVwIHdpdGggYnVpbGRlciBpZnJhbWUgKi99XG5cdFx0XHRcdFx0XHRcdDxkaXYgaWQ9XCJ3cGZvcm1zLWd1dGVuYmVyZy1wb3B1cFwiIGNsYXNzTmFtZT1cIndwZm9ybXMtYnVpbGRlci1wb3B1cFwiPlxuXHRcdFx0XHRcdFx0XHRcdDxpZnJhbWUgc3JjPVwiYWJvdXQ6YmxhbmtcIiB3aWR0aD1cIjEwMCVcIiBoZWlnaHQ9XCIxMDAlXCIgaWQ9XCJ3cGZvcm1zLWJ1aWxkZXItaWZyYW1lXCI+PC9pZnJhbWU+XG5cdFx0XHRcdFx0XHRcdDwvZGl2PlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PC9GcmFnbWVudD5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cblx0XHRcdC8qKlxuXHRcdFx0ICogR2V0IGJsb2NrIHBsYWNlaG9sZGVyIChmb3JtIHNlbGVjdG9yKSBKU1ggY29kZS5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gYXR0cmlidXRlcyAgQmxvY2sgYXR0cmlidXRlcy5cblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBoYW5kbGVycyAgICBCbG9jayBldmVudCBoYW5kbGVycy5cblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBmb3JtT3B0aW9ucyBGb3JtIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHJldHVybnMge0pTWC5FbGVtZW50fSBCbG9jayBwbGFjZWhvbGRlciBKU1ggY29kZS5cblx0XHRcdCAqL1xuXHRcdFx0Z2V0QmxvY2tQbGFjZWhvbGRlcjogZnVuY3Rpb24oIGF0dHJpYnV0ZXMsIGhhbmRsZXJzLCBmb3JtT3B0aW9ucyApIHtcblxuXHRcdFx0XHRyZXR1cm4gKFxuXHRcdFx0XHRcdDxQbGFjZWhvbGRlclxuXHRcdFx0XHRcdFx0a2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci13cmFwXCJcblx0XHRcdFx0XHRcdGNsYXNzTmFtZT1cIndwZm9ybXMtZ3V0ZW5iZXJnLWZvcm0tc2VsZWN0b3Itd3JhcFwiPlxuXHRcdFx0XHRcdFx0PGltZyBzcmM9e3dwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IubG9nb191cmx9IC8+XG5cdFx0XHRcdFx0XHQ8aDM+eyBzdHJpbmdzLnRpdGxlIH08L2gzPlxuXHRcdFx0XHRcdFx0PFNlbGVjdENvbnRyb2xcblx0XHRcdFx0XHRcdFx0a2V5PVwid3Bmb3Jtcy1ndXRlbmJlcmctZm9ybS1zZWxlY3Rvci1zZWxlY3QtY29udHJvbFwiXG5cdFx0XHRcdFx0XHRcdHZhbHVlPXsgYXR0cmlidXRlcy5mb3JtSWQgfVxuXHRcdFx0XHRcdFx0XHRvcHRpb25zPXsgZm9ybU9wdGlvbnMgfVxuXHRcdFx0XHRcdFx0XHRvbkNoYW5nZT17IHZhbHVlID0+IGhhbmRsZXJzLmF0dHJDaGFuZ2UoICdmb3JtSWQnLCB2YWx1ZSApIH1cblx0XHRcdFx0XHRcdC8+XG5cdFx0XHRcdFx0PC9QbGFjZWhvbGRlcj5cblx0XHRcdFx0KTtcblx0XHRcdH0sXG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBTdHlsZSBTZXR0aW5ncyBwYW5lbCBjbGFzcy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtvYmplY3R9IGF0dHJpYnV0ZXMgQmxvY2sgYXR0cmlidXRlcy5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtzdHJpbmd9IFN0eWxlIFNldHRpbmdzIHBhbmVsIGNsYXNzLlxuXHRcdCAqL1xuXHRcdGdldFBhbmVsQ2xhc3M6IGZ1bmN0aW9uKCBhdHRyaWJ1dGVzICkge1xuXG5cdFx0XHRsZXQgY3NzQ2xhc3MgPSAnd3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwgd3Bmb3Jtcy1ibG9jay1zZXR0aW5ncy0nICsgYXR0cmlidXRlcy5jbGllbnRJZDtcblxuXHRcdFx0aWYgKCAhIGFwcC5pc0Z1bGxTdHlsaW5nRW5hYmxlZCgpICkge1xuXHRcdFx0XHRjc3NDbGFzcyArPSAnIGRpc2FibGVkX3BhbmVsJztcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGNzc0NsYXNzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgd2hldGhlciB0aGUgZnVsbCBzdHlsaW5nIGlzIGVuYWJsZWQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtib29sZWFufSBXaGV0aGVyIHRoZSBmdWxsIHN0eWxpbmcgaXMgZW5hYmxlZC5cblx0XHQgKi9cblx0XHRpc0Z1bGxTdHlsaW5nRW5hYmxlZDogZnVuY3Rpb24oKSB7XG5cblx0XHRcdHJldHVybiB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmlzX21vZGVybl9tYXJrdXAgJiYgd3Bmb3Jtc19ndXRlbmJlcmdfZm9ybV9zZWxlY3Rvci5pc19mdWxsX3N0eWxpbmc7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBibG9jayBjb250YWluZXIgRE9NIGVsZW1lbnQuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybnMge0VsZW1lbnR9IEJsb2NrIGNvbnRhaW5lci5cblx0XHQgKi9cblx0XHRnZXRCbG9ja0NvbnRhaW5lcjogZnVuY3Rpb24oIHByb3BzICkge1xuXG5cdFx0XHRjb25zdCBibG9ja1NlbGVjdG9yID0gYCNibG9jay0ke3Byb3BzLmNsaWVudElkfSA+IGRpdmA7XG5cdFx0XHRsZXQgYmxvY2sgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCBibG9ja1NlbGVjdG9yICk7XG5cblx0XHRcdC8vIEZvciBGU0UgLyBHdXRlbmJlcmcgcGx1Z2luIHdlIG5lZWQgdG8gdGFrZSBhIGxvb2sgaW5zaWRlIHRoZSBpZnJhbWUuXG5cdFx0XHRpZiAoICEgYmxvY2sgKSB7XG5cdFx0XHRcdGNvbnN0IGVkaXRvckNhbnZhcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICdpZnJhbWVbbmFtZT1cImVkaXRvci1jYW52YXNcIl0nICk7XG5cblx0XHRcdFx0YmxvY2sgPSBlZGl0b3JDYW52YXMgJiYgZWRpdG9yQ2FudmFzLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQucXVlcnlTZWxlY3RvciggYmxvY2tTZWxlY3RvciApO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gYmxvY2s7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzZXR0aW5ncyBmaWVsZHMgZXZlbnQgaGFuZGxlcnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqXG5cdFx0ICogQHJldHVybnMge29iamVjdH0gT2JqZWN0IHRoYXQgY29udGFpbnMgZXZlbnQgaGFuZGxlcnMgZm9yIHRoZSBzZXR0aW5ncyBmaWVsZHMuXG5cdFx0ICovXG5cdFx0Z2V0U2V0dGluZ3NGaWVsZHNIYW5kbGVyczogZnVuY3Rpb24oIHByb3BzICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIG1heC1saW5lcy1wZXItZnVuY3Rpb25cblxuXHRcdFx0cmV0dXJuIHtcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogRmllbGQgc3R5bGUgYXR0cmlidXRlIGNoYW5nZSBldmVudCBoYW5kbGVyLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IGF0dHJpYnV0ZSBBdHRyaWJ1dGUgbmFtZS5cblx0XHRcdFx0ICogQHBhcmFtIHtzdHJpbmd9IHZhbHVlICAgICBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0c3R5bGVBdHRyQ2hhbmdlOiBmdW5jdGlvbiggYXR0cmlidXRlLCB2YWx1ZSApIHtcblxuXHRcdFx0XHRcdGNvbnN0IGJsb2NrID0gYXBwLmdldEJsb2NrQ29udGFpbmVyKCBwcm9wcyApLFxuXHRcdFx0XHRcdFx0Y29udGFpbmVyID0gYmxvY2sucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLSR7cHJvcHMuYXR0cmlidXRlcy5mb3JtSWR9YCApLFxuXHRcdFx0XHRcdFx0cHJvcGVydHkgPSBhdHRyaWJ1dGUucmVwbGFjZSggL1tBLVpdL2csIGxldHRlciA9PiBgLSR7bGV0dGVyLnRvTG93ZXJDYXNlKCl9YCApLFxuXHRcdFx0XHRcdFx0c2V0QXR0ciA9IHt9O1xuXG5cdFx0XHRcdFx0aWYgKCBjb250YWluZXIgKSB7XG5cdFx0XHRcdFx0XHRzd2l0Y2ggKCBwcm9wZXJ0eSApIHtcblx0XHRcdFx0XHRcdFx0Y2FzZSAnZmllbGQtc2l6ZSc6XG5cdFx0XHRcdFx0XHRcdGNhc2UgJ2xhYmVsLXNpemUnOlxuXHRcdFx0XHRcdFx0XHRjYXNlICdidXR0b24tc2l6ZSc6XG5cdFx0XHRcdFx0XHRcdFx0Zm9yICggY29uc3Qga2V5IGluIHNpemVzWyBwcm9wZXJ0eSBdWyB2YWx1ZSBdICkge1xuXHRcdFx0XHRcdFx0XHRcdFx0Y29udGFpbmVyLnN0eWxlLnNldFByb3BlcnR5KFxuXHRcdFx0XHRcdFx0XHRcdFx0XHRgLS13cGZvcm1zLSR7cHJvcGVydHl9LSR7a2V5fWAsXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHNpemVzWyBwcm9wZXJ0eSBdWyB2YWx1ZSBdWyBrZXkgXSxcblx0XHRcdFx0XHRcdFx0XHRcdCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cblx0XHRcdFx0XHRcdFx0ZGVmYXVsdDpcblx0XHRcdFx0XHRcdFx0XHRjb250YWluZXIuc3R5bGUuc2V0UHJvcGVydHkoIGAtLXdwZm9ybXMtJHtwcm9wZXJ0eX1gLCB2YWx1ZSApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHNldEF0dHJbIGF0dHJpYnV0ZSBdID0gdmFsdWU7XG5cblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCBzZXRBdHRyICk7XG5cblx0XHRcdFx0XHR0cmlnZ2VyU2VydmVyUmVuZGVyID0gZmFsc2U7XG5cblx0XHRcdFx0XHR0aGlzLnVwZGF0ZUNvcHlQYXN0ZUNvbnRlbnQoKTtcblxuXHRcdFx0XHRcdCQoIHdpbmRvdyApLnRyaWdnZXIoICd3cGZvcm1zRm9ybVNlbGVjdG9yU3R5bGVBdHRyQ2hhbmdlJywgWyBibG9jaywgcHJvcHMsIGF0dHJpYnV0ZSwgdmFsdWUgXSApO1xuXHRcdFx0XHR9LFxuXG5cdFx0XHRcdC8qKlxuXHRcdFx0XHQgKiBGaWVsZCByZWd1bGFyIGF0dHJpYnV0ZSBjaGFuZ2UgZXZlbnQgaGFuZGxlci5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSBhdHRyaWJ1dGUgQXR0cmlidXRlIG5hbWUuXG5cdFx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSAgICAgTmV3IGF0dHJpYnV0ZSB2YWx1ZS5cblx0XHRcdFx0ICovXG5cdFx0XHRcdGF0dHJDaGFuZ2U6IGZ1bmN0aW9uKCBhdHRyaWJ1dGUsIHZhbHVlICkge1xuXG5cdFx0XHRcdFx0Y29uc3Qgc2V0QXR0ciA9IHt9O1xuXG5cdFx0XHRcdFx0c2V0QXR0clsgYXR0cmlidXRlIF0gPSB2YWx1ZTtcblxuXHRcdFx0XHRcdHByb3BzLnNldEF0dHJpYnV0ZXMoIHNldEF0dHIgKTtcblxuXHRcdFx0XHRcdHRyaWdnZXJTZXJ2ZXJSZW5kZXIgPSB0cnVlO1xuXG5cdFx0XHRcdFx0dGhpcy51cGRhdGVDb3B5UGFzdGVDb250ZW50KCk7XG5cdFx0XHRcdH0sXG5cblx0XHRcdFx0LyoqXG5cdFx0XHRcdCAqIFJlc2V0IEZvcm0gU3R5bGVzIHNldHRpbmdzIHRvIGRlZmF1bHRzLlxuXHRcdFx0XHQgKlxuXHRcdFx0XHQgKiBAc2luY2UgMS44LjFcblx0XHRcdFx0ICovXG5cdFx0XHRcdHJlc2V0U2V0dGluZ3M6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRcdFx0Zm9yICggbGV0IGtleSBpbiBkZWZhdWx0U3R5bGVTZXR0aW5ncyApIHtcblx0XHRcdFx0XHRcdHRoaXMuc3R5bGVBdHRyQ2hhbmdlKCBrZXksIGRlZmF1bHRTdHlsZVNldHRpbmdzWyBrZXkgXSApO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogVXBkYXRlIGNvbnRlbnQgb2YgdGhlIFwiQ29weS9QYXN0ZVwiIGZpZWxkcy5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqL1xuXHRcdFx0XHR1cGRhdGVDb3B5UGFzdGVDb250ZW50OiBmdW5jdGlvbigpIHtcblxuXHRcdFx0XHRcdGxldCBjb250ZW50ID0ge307XG5cdFx0XHRcdFx0bGV0IGF0dHMgPSB3cC5kYXRhLnNlbGVjdCggJ2NvcmUvYmxvY2stZWRpdG9yJyApLmdldEJsb2NrQXR0cmlidXRlcyggcHJvcHMuY2xpZW50SWQgKTtcblxuXHRcdFx0XHRcdGZvciAoIGxldCBrZXkgaW4gZGVmYXVsdFN0eWxlU2V0dGluZ3MgKSB7XG5cdFx0XHRcdFx0XHRjb250ZW50W2tleV0gPSBhdHRzWyBrZXkgXTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCB7ICdjb3B5UGFzdGVKc29uVmFsdWUnOiBKU09OLnN0cmluZ2lmeSggY29udGVudCApIH0gKTtcblx0XHRcdFx0fSxcblxuXHRcdFx0XHQvKipcblx0XHRcdFx0ICogUGFzdGUgc2V0dGluZ3MgaGFuZGxlci5cblx0XHRcdFx0ICpcblx0XHRcdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0XHRcdCAqXG5cdFx0XHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB2YWx1ZSBOZXcgYXR0cmlidXRlIHZhbHVlLlxuXHRcdFx0XHQgKi9cblx0XHRcdFx0cGFzdGVTZXR0aW5nczogZnVuY3Rpb24oIHZhbHVlICkge1xuXG5cdFx0XHRcdFx0bGV0IHBhc3RlQXR0cmlidXRlcyA9IGFwcC5wYXJzZVZhbGlkYXRlSnNvbiggdmFsdWUgKTtcblxuXHRcdFx0XHRcdGlmICggISBwYXN0ZUF0dHJpYnV0ZXMgKSB7XG5cblx0XHRcdFx0XHRcdHdwLmRhdGEuZGlzcGF0Y2goICdjb3JlL25vdGljZXMnICkuY3JlYXRlRXJyb3JOb3RpY2UoXG5cdFx0XHRcdFx0XHRcdHN0cmluZ3MuY29weV9wYXN0ZV9lcnJvcixcblx0XHRcdFx0XHRcdFx0eyBpZDogJ3dwZm9ybXMtanNvbi1wYXJzZS1lcnJvcicgfVxuXHRcdFx0XHRcdFx0KTtcblxuXHRcdFx0XHRcdFx0dGhpcy51cGRhdGVDb3B5UGFzdGVDb250ZW50KCk7XG5cblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRwYXN0ZUF0dHJpYnV0ZXMuY29weVBhc3RlSnNvblZhbHVlID0gdmFsdWU7XG5cblx0XHRcdFx0XHRwcm9wcy5zZXRBdHRyaWJ1dGVzKCBwYXN0ZUF0dHJpYnV0ZXMgKTtcblxuXHRcdFx0XHRcdHRyaWdnZXJTZXJ2ZXJSZW5kZXIgPSB0cnVlO1xuXHRcdFx0XHR9LFxuXHRcdFx0fTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUGFyc2UgYW5kIHZhbGlkYXRlIEpTT04gc3RyaW5nLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsdWUgSlNPTiBzdHJpbmcuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7Ym9vbGVhbnxvYmplY3R9IFBhcnNlZCBKU09OIG9iamVjdCBPUiBmYWxzZSBvbiBlcnJvci5cblx0XHQgKi9cblx0XHRwYXJzZVZhbGlkYXRlSnNvbjogZnVuY3Rpb24oIHZhbHVlICkge1xuXG5cdFx0XHRpZiAoIHR5cGVvZiB2YWx1ZSAhPT0gJ3N0cmluZycgKSB7XG5cdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdH1cblxuXHRcdFx0bGV0IGF0dHM7XG5cblx0XHRcdHRyeSB7XG5cdFx0XHRcdGF0dHMgPSBKU09OLnBhcnNlKCB2YWx1ZSApO1xuXHRcdFx0fSBjYXRjaCAoIGVycm9yICkge1xuXHRcdFx0XHRhdHRzID0gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBhdHRzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBHZXQgV1BGb3JtcyBpY29uIERPTSBlbGVtZW50LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7RE9NLmVsZW1lbnR9IFdQRm9ybXMgaWNvbiBET00gZWxlbWVudC5cblx0XHQgKi9cblx0XHRnZXRJY29uOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0cmV0dXJuIGNyZWF0ZUVsZW1lbnQoXG5cdFx0XHRcdCdzdmcnLFxuXHRcdFx0XHR7IHdpZHRoOiAyMCwgaGVpZ2h0OiAyMCwgdmlld0JveDogJzAgMCA2MTIgNjEyJywgY2xhc3NOYW1lOiAnZGFzaGljb24nIH0sXG5cdFx0XHRcdGNyZWF0ZUVsZW1lbnQoXG5cdFx0XHRcdFx0J3BhdGgnLFxuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdGZpbGw6ICdjdXJyZW50Q29sb3InLFxuXHRcdFx0XHRcdFx0ZDogJ001NDQsMEg2OEMzMC40NDUsMCwwLDMwLjQ0NSwwLDY4djQ3NmMwLDM3LjU1NiwzMC40NDUsNjgsNjgsNjhoNDc2YzM3LjU1NiwwLDY4LTMwLjQ0NCw2OC02OFY2OCBDNjEyLDMwLjQ0NSw1ODEuNTU2LDAsNTQ0LDB6IE00NjQuNDQsNjhMMzg3LjYsMTIwLjAyTDMyMy4zNCw2OEg0NjQuNDR6IE0yODguNjYsNjhsLTY0LjI2LDUyLjAyTDE0Ny41Niw2OEgyODguNjZ6IE01NDQsNTQ0SDY4IFY2OGgyMi4xbDEzNiw5Mi4xNGw3OS45LTY0LjZsNzkuNTYsNjQuNmwxMzYtOTIuMTRINTQ0VjU0NHogTTExNC4yNCwyNjMuMTZoOTUuODh2LTQ4LjI4aC05NS44OFYyNjMuMTZ6IE0xMTQuMjQsMzYwLjRoOTUuODggdi00OC42MmgtOTUuODhWMzYwLjR6IE0yNDIuNzYsMzYwLjRoMjU1di00OC42MmgtMjU1VjM2MC40TDI0Mi43NiwzNjAuNHogTTI0Mi43NiwyNjMuMTZoMjU1di00OC4yOGgtMjU1VjI2My4xNkwyNDIuNzYsMjYzLjE2eiBNMzY4LjIyLDQ1Ny4zaDEyOS41NFY0MDhIMzY4LjIyVjQ1Ny4zeicsXG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0KSxcblx0XHRcdCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBibG9jayBhdHRyaWJ1dGVzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7b2JqZWN0fSBCbG9jayBhdHRyaWJ1dGVzLlxuXHRcdCAqL1xuXHRcdGdldEJsb2NrQXR0cmlidXRlczogZnVuY3Rpb24oKSB7IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbWF4LWxpbmVzLXBlci1mdW5jdGlvblxuXG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRjbGllbnRJZDoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6ICcnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmb3JtSWQ6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5mb3JtSWQsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGRpc3BsYXlUaXRsZToge1xuXHRcdFx0XHRcdHR5cGU6ICdib29sZWFuJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5kaXNwbGF5VGl0bGUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGRpc3BsYXlEZXNjOiB7XG5cdFx0XHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmRpc3BsYXlEZXNjLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRwcmV2aWV3OiB7XG5cdFx0XHRcdFx0dHlwZTogJ2Jvb2xlYW4nLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZFNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZFNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQm9yZGVyUmFkaXVzOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCb3JkZXJSYWRpdXMsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQmFja2dyb3VuZENvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuZmllbGRCYWNrZ3JvdW5kQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGZpZWxkQm9yZGVyQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5maWVsZEJvcmRlckNvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRmaWVsZFRleHRDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmZpZWxkVGV4dENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRsYWJlbFNpemU6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbFNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGxhYmVsQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRsYWJlbFN1YmxhYmVsQ29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5sYWJlbFN1YmxhYmVsQ29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGxhYmVsRXJyb3JDb2xvcjoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmxhYmVsRXJyb3JDb2xvcixcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uU2l6ZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvblNpemUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGJ1dHRvbkJvcmRlclJhZGl1czoge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmJ1dHRvbkJvcmRlclJhZGl1cyxcblx0XHRcdFx0fSxcblx0XHRcdFx0YnV0dG9uQmFja2dyb3VuZENvbG9yOiB7XG5cdFx0XHRcdFx0dHlwZTogJ3N0cmluZycsXG5cdFx0XHRcdFx0ZGVmYXVsdDogZGVmYXVsdHMuYnV0dG9uQmFja2dyb3VuZENvbG9yLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHRidXR0b25UZXh0Q29sb3I6IHtcblx0XHRcdFx0XHR0eXBlOiAnc3RyaW5nJyxcblx0XHRcdFx0XHRkZWZhdWx0OiBkZWZhdWx0cy5idXR0b25UZXh0Q29sb3IsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGNvcHlQYXN0ZUpzb25WYWx1ZToge1xuXHRcdFx0XHRcdHR5cGU6ICdzdHJpbmcnLFxuXHRcdFx0XHRcdGRlZmF1bHQ6IGRlZmF1bHRzLmNvcHlQYXN0ZUpzb25WYWx1ZSxcblx0XHRcdFx0fSxcblx0XHRcdH07XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBmb3JtIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtBcnJheX0gRm9ybSBvcHRpb25zLlxuXHRcdCAqL1xuXHRcdGdldEZvcm1PcHRpb25zOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0Y29uc3QgZm9ybU9wdGlvbnMgPSB3cGZvcm1zX2d1dGVuYmVyZ19mb3JtX3NlbGVjdG9yLmZvcm1zLm1hcCggdmFsdWUgPT4gKFxuXHRcdFx0XHR7IHZhbHVlOiB2YWx1ZS5JRCwgbGFiZWw6IHZhbHVlLnBvc3RfdGl0bGUgfVxuXHRcdFx0KSApO1xuXG5cdFx0XHRmb3JtT3B0aW9ucy51bnNoaWZ0KCB7IHZhbHVlOiAnJywgbGFiZWw6IHN0cmluZ3MuZm9ybV9zZWxlY3QgfSApO1xuXG5cdFx0XHRyZXR1cm4gZm9ybU9wdGlvbnM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzaXplIHNlbGVjdG9yIG9wdGlvbnMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtBcnJheX0gU2l6ZSBvcHRpb25zLlxuXHRcdCAqL1xuXHRcdGdldFNpemVPcHRpb25zOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0cmV0dXJuIFtcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLnNtYWxsLFxuXHRcdFx0XHRcdHZhbHVlOiAnc21hbGwnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XHR7XG5cdFx0XHRcdFx0bGFiZWw6IHN0cmluZ3MubWVkaXVtLFxuXHRcdFx0XHRcdHZhbHVlOiAnbWVkaXVtJyxcblx0XHRcdFx0fSxcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGxhYmVsOiBzdHJpbmdzLmxhcmdlLFxuXHRcdFx0XHRcdHZhbHVlOiAnbGFyZ2UnLFxuXHRcdFx0XHR9LFxuXHRcdFx0XTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JFZGl0YCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge29iamVjdH0gZSAgICAgRXZlbnQgb2JqZWN0LlxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBwcm9wcyBCbG9jayBwcm9wZXJ0aWVzLlxuXHRcdCAqL1xuXHRcdGJsb2NrRWRpdDogZnVuY3Rpb24oIGUsIHByb3BzICkge1xuXG5cdFx0XHRjb25zdCBibG9jayA9IGFwcC5nZXRCbG9ja0NvbnRhaW5lciggcHJvcHMgKTtcblxuXHRcdFx0aWYgKCAhIGJsb2NrIHx8ICEgYmxvY2suZGF0YXNldCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRhcHAuaW5pdExlYWRGb3JtU2V0dGluZ3MoIGJsb2NrLnBhcmVudEVsZW1lbnQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBMZWFkIEZvcm0gU2V0dGluZ3MgcGFuZWxzLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge0VsZW1lbnR9IGJsb2NrIEJsb2NrIGVsZW1lbnQuXG5cdFx0ICovXG5cdFx0aW5pdExlYWRGb3JtU2V0dGluZ3M6IGZ1bmN0aW9uKCBibG9jayApIHtcblxuXHRcdFx0aWYgKCAhIGJsb2NrIHx8ICEgYmxvY2suZGF0YXNldCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoICEgYXBwLmlzRnVsbFN0eWxpbmdFbmFibGVkKCkgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgY2xpZW50SWQgPSBibG9jay5kYXRhc2V0LmJsb2NrO1xuXHRcdFx0Y29uc3QgJGZvcm0gPSAkKCBibG9jay5xdWVyeVNlbGVjdG9yKCAnLndwZm9ybXMtY29udGFpbmVyJyApICk7XG5cdFx0XHRjb25zdCAkcGFuZWwgPSAkKCBgLndwZm9ybXMtYmxvY2stc2V0dGluZ3MtJHtjbGllbnRJZH1gICk7XG5cblx0XHRcdGlmICggJGZvcm0uaGFzQ2xhc3MoICd3cGZvcm1zLWxlYWQtZm9ybXMtY29udGFpbmVyJyApICkge1xuXG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5hZGRDbGFzcyggJ2Rpc2FibGVkX3BhbmVsJyApXG5cdFx0XHRcdFx0LmZpbmQoICcud3Bmb3Jtcy1ndXRlbmJlcmctcGFuZWwtbm90aWNlLndwZm9ybXMtbGVhZC1mb3JtLW5vdGljZScgKVxuXHRcdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ2Jsb2NrJyApO1xuXG5cdFx0XHRcdCRwYW5lbFxuXHRcdFx0XHRcdC5maW5kKCAnLndwZm9ybXMtZ3V0ZW5iZXJnLXBhbmVsLW5vdGljZS53cGZvcm1zLXVzZS1tb2Rlcm4tbm90aWNlJyApXG5cdFx0XHRcdFx0LmNzcyggJ2Rpc3BsYXknLCAnbm9uZScgKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQucmVtb3ZlQ2xhc3MoICdkaXNhYmxlZF9wYW5lbCcgKVxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy1sZWFkLWZvcm0tbm90aWNlJyApXG5cdFx0XHRcdC5jc3MoICdkaXNwbGF5JywgJ25vbmUnICk7XG5cblx0XHRcdCRwYW5lbFxuXHRcdFx0XHQuZmluZCggJy53cGZvcm1zLWd1dGVuYmVyZy1wYW5lbC1ub3RpY2Uud3Bmb3Jtcy11c2UtbW9kZXJuLW5vdGljZScgKVxuXHRcdFx0XHQuY3NzKCAnZGlzcGxheScsIG51bGwgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRXZlbnQgYHdwZm9ybXNGb3JtU2VsZWN0b3JGb3JtTG9hZGVkYCBoYW5kbGVyLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge29iamVjdH0gZSBFdmVudCBvYmplY3QuXG5cdFx0ICovXG5cdFx0Zm9ybUxvYWRlZDogZnVuY3Rpb24oIGUgKSB7XG5cblx0XHRcdGFwcC5pbml0TGVhZEZvcm1TZXR0aW5ncyggZS5kZXRhaWwuYmxvY2sgKTtcblx0XHRcdGFwcC51cGRhdGVBY2NlbnRDb2xvcnMoIGUuZGV0YWlsICk7XG5cdFx0XHRhcHAubG9hZENob2ljZXNKUyggZS5kZXRhaWwgKTtcblx0XHRcdGFwcC5pbml0UmljaFRleHRGaWVsZCggZS5kZXRhaWwuZm9ybUlkICk7XG5cblx0XHRcdCQoIGUuZGV0YWlsLmJsb2NrIClcblx0XHRcdFx0Lm9mZiggJ2NsaWNrJyApXG5cdFx0XHRcdC5vbiggJ2NsaWNrJywgYXBwLmJsb2NrQ2xpY2sgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQ2xpY2sgb24gdGhlIGJsb2NrIGV2ZW50IGhhbmRsZXIuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBlIEV2ZW50IG9iamVjdC5cblx0XHQgKi9cblx0XHRibG9ja0NsaWNrOiBmdW5jdGlvbiggZSApIHtcblxuXHRcdFx0YXBwLmluaXRMZWFkRm9ybVNldHRpbmdzKCBlLmN1cnJlbnRUYXJnZXQgKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogVXBkYXRlIGFjY2VudCBjb2xvcnMgb2Ygc29tZSBmaWVsZHMgaW4gR0IgYmxvY2sgaW4gTW9kZXJuIE1hcmt1cCBtb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcGFyYW0ge29iamVjdH0gZGV0YWlsIEV2ZW50IGRldGFpbHMgb2JqZWN0LlxuXHRcdCAqL1xuXHRcdHVwZGF0ZUFjY2VudENvbG9yczogZnVuY3Rpb24oIGRldGFpbCApIHtcblxuXHRcdFx0aWYgKFxuXHRcdFx0XHQhIHdwZm9ybXNfZ3V0ZW5iZXJnX2Zvcm1fc2VsZWN0b3IuaXNfbW9kZXJuX21hcmt1cCB8fFxuXHRcdFx0XHQhIHdpbmRvdy5XUEZvcm1zIHx8XG5cdFx0XHRcdCEgd2luZG93LldQRm9ybXMuRnJvbnRlbmRNb2Rlcm4gfHxcblx0XHRcdFx0ISBkZXRhaWwuYmxvY2tcblx0XHRcdCkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0ICRmb3JtID0gJCggZGV0YWlsLmJsb2NrLnF1ZXJ5U2VsZWN0b3IoIGAjd3Bmb3Jtcy0ke2RldGFpbC5mb3JtSWR9YCApICksXG5cdFx0XHRcdEZyb250ZW5kTW9kZXJuID0gd2luZG93LldQRm9ybXMuRnJvbnRlbmRNb2Rlcm47XG5cblx0XHRcdEZyb250ZW5kTW9kZXJuLnVwZGF0ZUdCQmxvY2tQYWdlSW5kaWNhdG9yQ29sb3IoICRmb3JtICk7XG5cdFx0XHRGcm9udGVuZE1vZGVybi51cGRhdGVHQkJsb2NrSWNvbkNob2ljZXNDb2xvciggJGZvcm0gKTtcblx0XHRcdEZyb250ZW5kTW9kZXJuLnVwZGF0ZUdCQmxvY2tSYXRpbmdDb2xvciggJGZvcm0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdCBNb2Rlcm4gc3R5bGUgRHJvcGRvd24gZmllbGRzICg8c2VsZWN0PikuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSBkZXRhaWwgRXZlbnQgZGV0YWlscyBvYmplY3QuXG5cdFx0ICovXG5cdFx0bG9hZENob2ljZXNKUzogZnVuY3Rpb24oIGRldGFpbCApIHtcblxuXHRcdFx0aWYgKCB0eXBlb2Ygd2luZG93LkNob2ljZXMgIT09ICdmdW5jdGlvbicgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgJGZvcm0gPSAkKCBkZXRhaWwuYmxvY2sucXVlcnlTZWxlY3RvciggYCN3cGZvcm1zLSR7ZGV0YWlsLmZvcm1JZH1gICkgKTtcblxuXHRcdFx0JGZvcm0uZmluZCggJy5jaG9pY2VzanMtc2VsZWN0JyApLmVhY2goIGZ1bmN0aW9uKCBpZHgsIGVsICkge1xuXG5cdFx0XHRcdGNvbnN0ICRlbCA9ICQoIGVsICk7XG5cblx0XHRcdFx0aWYgKCAkZWwuZGF0YSggJ2Nob2ljZScgKSA9PT0gJ2FjdGl2ZScgKSB7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0dmFyIGFyZ3MgPSB3aW5kb3cud3Bmb3Jtc19jaG9pY2VzanNfY29uZmlnIHx8IHt9LFxuXHRcdFx0XHRcdHNlYXJjaEVuYWJsZWQgPSAkZWwuZGF0YSggJ3NlYXJjaC1lbmFibGVkJyApLFxuXHRcdFx0XHRcdCRmaWVsZCA9ICRlbC5jbG9zZXN0KCAnLndwZm9ybXMtZmllbGQnICk7XG5cblx0XHRcdFx0YXJncy5zZWFyY2hFbmFibGVkID0gJ3VuZGVmaW5lZCcgIT09IHR5cGVvZiBzZWFyY2hFbmFibGVkID8gc2VhcmNoRW5hYmxlZCA6IHRydWU7XG5cdFx0XHRcdGFyZ3MuY2FsbGJhY2tPbkluaXQgPSBmdW5jdGlvbigpIHtcblxuXHRcdFx0XHRcdHZhciBzZWxmID0gdGhpcyxcblx0XHRcdFx0XHRcdCRlbGVtZW50ID0gJCggc2VsZi5wYXNzZWRFbGVtZW50LmVsZW1lbnQgKSxcblx0XHRcdFx0XHRcdCRpbnB1dCA9ICQoIHNlbGYuaW5wdXQuZWxlbWVudCApLFxuXHRcdFx0XHRcdFx0c2l6ZUNsYXNzID0gJGVsZW1lbnQuZGF0YSggJ3NpemUtY2xhc3MnICk7XG5cblx0XHRcdFx0XHQvLyBBZGQgQ1NTLWNsYXNzIGZvciBzaXplLlxuXHRcdFx0XHRcdGlmICggc2l6ZUNsYXNzICkge1xuXHRcdFx0XHRcdFx0JCggc2VsZi5jb250YWluZXJPdXRlci5lbGVtZW50ICkuYWRkQ2xhc3MoIHNpemVDbGFzcyApO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdC8qKlxuXHRcdFx0XHRcdCAqIElmIGEgbXVsdGlwbGUgc2VsZWN0IGhhcyBzZWxlY3RlZCBjaG9pY2VzIC0gaGlkZSBhIHBsYWNlaG9sZGVyIHRleHQuXG5cdFx0XHRcdFx0ICogSW4gY2FzZSBpZiBzZWxlY3QgaXMgZW1wdHkgLSB3ZSByZXR1cm4gcGxhY2Vob2xkZXIgdGV4dCBiYWNrLlxuXHRcdFx0XHRcdCAqL1xuXHRcdFx0XHRcdGlmICggJGVsZW1lbnQucHJvcCggJ211bHRpcGxlJyApICkge1xuXG5cdFx0XHRcdFx0XHQvLyBPbiBpbml0IGV2ZW50LlxuXHRcdFx0XHRcdFx0JGlucHV0LmRhdGEoICdwbGFjZWhvbGRlcicsICRpbnB1dC5hdHRyKCAncGxhY2Vob2xkZXInICkgKTtcblxuXHRcdFx0XHRcdFx0aWYgKCBzZWxmLmdldFZhbHVlKCB0cnVlICkubGVuZ3RoICkge1xuXHRcdFx0XHRcdFx0XHQkaW5wdXQucmVtb3ZlQXR0ciggJ3BsYWNlaG9sZGVyJyApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHRoaXMuZGlzYWJsZSgpO1xuXHRcdFx0XHRcdCRmaWVsZC5maW5kKCAnLmlzLWRpc2FibGVkJyApLnJlbW92ZUNsYXNzKCAnaXMtZGlzYWJsZWQnICk7XG5cdFx0XHRcdH07XG5cblx0XHRcdFx0dHJ5IHtcblx0XHRcdFx0XHRjb25zdCBjaG9pY2VzSW5zdGFuY2UgPSAgbmV3IENob2ljZXMoIGVsLCBhcmdzICk7XG5cblx0XHRcdFx0XHQvLyBTYXZlIENob2ljZXMuanMgaW5zdGFuY2UgZm9yIGZ1dHVyZSBhY2Nlc3MuXG5cdFx0XHRcdFx0JGVsLmRhdGEoICdjaG9pY2VzanMnLCBjaG9pY2VzSW5zdGFuY2UgKTtcblxuXHRcdFx0XHR9IGNhdGNoICggZSApIHt9IC8vIGVzbGludC1kaXNhYmxlLWxpbmUgbm8tZW1wdHlcblx0XHRcdH0gKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogSW5pdGlhbGl6ZSBSaWNoVGV4dCBmaWVsZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHtpbnR9IGZvcm1JZCBGb3JtIElELlxuXHRcdCAqL1xuXHRcdGluaXRSaWNoVGV4dEZpZWxkOiBmdW5jdGlvbiggZm9ybUlkICkge1xuXG5cdFx0XHQvLyBTZXQgZGVmYXVsdCB0YWIgdG8gYFZpc3VhbGAuXG5cdFx0XHQkKCBgI3dwZm9ybXMtJHtmb3JtSWR9IC53cC1lZGl0b3Itd3JhcGAgKS5yZW1vdmVDbGFzcyggJ2h0bWwtYWN0aXZlJyApLmFkZENsYXNzKCAndG1jZS1hY3RpdmUnICk7XG5cdFx0fSxcblx0fTtcblxuXHQvLyBQcm92aWRlIGFjY2VzcyB0byBwdWJsaWMgZnVuY3Rpb25zL3Byb3BlcnRpZXMuXG5cdHJldHVybiBhcHA7XG5cbn0oIGRvY3VtZW50LCB3aW5kb3csIGpRdWVyeSApICk7XG5cbi8vIEluaXRpYWxpemUuXG5XUEZvcm1zLkZvcm1TZWxlY3Rvci5pbml0KCk7XG4iXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0E7O0FBRUEsWUFBWTs7QUFFWjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBSkEsU0FBQUEsZUFBQUMsR0FBQSxFQUFBQyxDQUFBLFdBQUFDLGVBQUEsQ0FBQUYsR0FBQSxL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
},{}]},{},[1])