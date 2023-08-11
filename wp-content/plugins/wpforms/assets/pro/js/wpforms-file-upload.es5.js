(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/* global WPFormsUtils */
'use strict';

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
(function ($) {
  /**
   * All connections are slow by default.
   *
   * @since 1.6.2
   *
   * @type {boolean|null}
   */
  var isSlow = null;

  /**
   * Previously submitted data.
   *
   * @since 1.7.1
   *
   * @type {Array}
   */
  var submittedValues = [];

  /**
   * Default settings for our speed test.
   *
   * @since 1.6.2
   *
   * @type {{maxTime: number, payloadSize: number}}
   */
  var speedTestSettings = {
    maxTime: 3000,
    // Max time (ms) it should take to be considered a 'fast connection'.
    payloadSize: 100 * 1024 // Payload size.
  };

  /**
   * Create a random payload for the speed test.
   *
   * @since 1.6.2
   *
   * @returns {string} Random payload.
   */
  function getPayload() {
    var data = '';
    for (var i = 0; i < speedTestSettings.payloadSize; ++i) {
      data += String.fromCharCode(Math.round(Math.random() * 36 + 64));
    }
    return data;
  }

  /**
   * Run speed tests and flag the clients as slow or not. If a connection
   * is slow it would let the backend know and the backend most likely
   * would disable parallel uploads and would set smaller chunk sizes.
   *
   * @since 1.6.2
   *
   * @param {Function} next Function to call when the speed detection is done.
   */
  function speedTest(next) {
    if (null !== isSlow) {
      setTimeout(next);
      return;
    }
    var data = getPayload();
    var start = new Date();
    wp.ajax.post({
      action: 'wpforms_file_upload_speed_test',
      data: data
    }).then(function () {
      var delta = new Date() - start;
      isSlow = delta >= speedTestSettings.maxTime;
      next();
    }).fail(function () {
      isSlow = true;
      next();
    });
  }

  /**
   * Toggle loading message above submit button.
   *
   * @since 1.5.6
   *
   * @param {object} $form jQuery form element.
   *
   * @returns {Function} event handler function.
   */
  function toggleLoadingMessage($form) {
    return function () {
      if ($form.find('.wpforms-uploading-in-progress-alert').length) {
        return;
      }
      $form.find('.wpforms-submit-container').before("<div class=\"wpforms-error-alert wpforms-uploading-in-progress-alert\">\n\t\t\t\t\t\t".concat(window.wpforms_file_upload.loading_message, "\n\t\t\t\t\t</div>"));
    };
  }

  /**
   * Is a field loading?
   *
   * @since 1.7.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {boolean} true if the field is loading.
   */
  function uploadInProgress(dz) {
    return dz.loading > 0 || dz.getFilesWithStatus('error').length > 0;
  }

  /**
   * Is at least one field loading?
   *
   * @since 1.7.6
   *
   * @returns {boolean} true if at least one field is loading.
   */
  function anyUploadsInProgress() {
    var anyUploadsInProgress = false;
    window.wpforms.dropzones.some(function (dz) {
      if (uploadInProgress(dz)) {
        anyUploadsInProgress = true;
        return true;
      }
    });
    return anyUploadsInProgress;
  }

  /**
   * Disable submit button and add overlay.
   *
   * @param {object} $form jQuery form element.
   */
  function disableSubmitButton($form) {
    // Find the primary submit button and the "Next" button for multi-page forms.
    var $btn = $form.find('.wpforms-submit');
    var $btnNext = $form.find('.wpforms-page-next:visible');
    var handler = toggleLoadingMessage($form); // Get the handler function for loading message toggle.

    // For multi-pages layout, use the "Next" button instead of the primary submit button.
    if ($form.find('.wpforms-page-indicator').length !== 0 && $btnNext.length !== 0) {
      $btn = $btnNext;
    }

    // Disable the submit button.
    $btn.prop('disabled', true);
    WPFormsUtils.triggerEvent($form, 'wpformsFormSubmitButtonDisable', [$form, $btn]);

    // If the overlay is not already added and the button is of type "submit", add an overlay.
    if (!$form.find('.wpforms-submit-overlay').length && $btn.attr('type') === 'submit') {
      // Add a container for the overlay and append the overlay element to it.
      $btn.parent().addClass('wpforms-submit-overlay-container');
      $btn.parent().append('<div class="wpforms-submit-overlay"></div>');

      // Set the overlay dimensions to match the submit button's size.
      $form.find('.wpforms-submit-overlay').css({
        width: "".concat($btn.outerWidth(), "px"),
        height: "".concat($btn.parent().outerHeight(), "px")
      });

      // Attach the click event to the overlay so that it triggers the handler function.
      $form.find('.wpforms-submit-overlay').on('click', handler);
    }
  }

  /**
   * Disable submit button when we are sending files to the server.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function toggleSubmit(dz) {
    // eslint-disable-line complexity

    var $form = jQuery(dz.element).closest('form'),
      $btn = $form.find('.wpforms-submit'),
      $btnNext = $form.find('.wpforms-page-next:visible'),
      handler = toggleLoadingMessage($form),
      disabled = uploadInProgress(dz);

    // For multi-pages layout.
    if ($form.find('.wpforms-page-indicator').length !== 0 && $btnNext.length !== 0) {
      $btn = $btnNext;
    }
    var isButtonDisabled = Boolean($btn.prop('disabled')) || $btn.hasClass('wpforms-disabled');
    if (disabled === isButtonDisabled) {
      return;
    }
    if (disabled) {
      disableSubmitButton($form);
      return;
    }
    if (anyUploadsInProgress()) {
      return;
    }
    $btn.prop('disabled', false);
    WPFormsUtils.triggerEvent($form, 'wpformsFormSubmitButtonRestore', [$form, $btn]);
    $form.find('.wpforms-submit-overlay').off('click', handler);
    $form.find('.wpforms-submit-overlay').remove();
    $btn.parent().removeClass('wpforms-submit-overlay-container');
    if ($form.find('.wpforms-uploading-in-progress-alert').length) {
      $form.find('.wpforms-uploading-in-progress-alert').remove();
    }
  }

  /**
   * Try to parse JSON or return false.
   *
   * @since 1.5.6
   *
   * @param {string} str JSON string candidate.
   *
   * @returns {*} Parse object or false.
   */
  function parseJSON(str) {
    try {
      return JSON.parse(str);
    } catch (e) {
      return false;
    }
  }

  /**
   * Leave only objects with length.
   *
   * @since 1.5.6
   *
   * @param {object} el Any array.
   *
   * @returns {bool} Has length more than 0 or no.
   */
  function onlyWithLength(el) {
    return el.length > 0;
  }

  /**
   * Leave only positive elements.
   *
   * @since 1.5.6
   *
   * @param {*} el Any element.
   *
   * @returns {*} Filter only positive.
   */
  function onlyPositive(el) {
    return el;
  }

  /**
   * Get xhr.
   *
   * @since 1.5.6
   *
   * @param {object} el Object with xhr property.
   *
   * @returns {*} Get XHR.
   */
  function getXHR(el) {
    return el.chunkResponse || el.xhr;
  }

  /**
   * Get response text.
   *
   * @since 1.5.6
   *
   * @param {object} el Xhr object.
   *
   * @returns {object} Response text.
   */
  function getResponseText(el) {
    return typeof el === 'string' ? el : el.responseText;
  }

  /**
   * Get data.
   *
   * @since 1.5.6
   *
   * @param {object} el Object with data property.
   *
   * @returns {object} Data.
   */
  function getData(el) {
    return el.data;
  }

  /**
   * Get value from files.
   *
   * @since 1.5.6
   *
   * @param {object} files Dropzone files.
   *
   * @returns {object} Prepared value.
   */
  function getValue(files) {
    return files.map(getXHR).filter(onlyPositive).map(getResponseText).filter(onlyWithLength).map(parseJSON).filter(onlyPositive).map(getData);
  }

  /**
   * Sending event higher order function.
   *
   * @since 1.5.6
   * @since 1.5.6.1 Added special processing of a file that is larger than server's post_max_size.
   *
   * @param {object} dz Dropzone object.
   * @param {object} data Adding data to request.
   *
   * @returns {Function} Handler function.
   */
  function sending(dz, data) {
    return function (file, xhr, formData) {
      /*
       * We should not allow sending a file, that exceeds server post_max_size.
       * With this "hack" we redefine the default send functionality
       * to prevent only this object from sending a request at all.
       * The file that generated that error should be marked as rejected,
       * so Dropzone will silently ignore it.
       *
       * If Chunks are enabled the file size will never exceed (by a PHP constraint) the
       * postMaxSize. This block shouldn't be removed nonetheless until the "modern" upload is completely
       * deprecated and removed.
       */
      if (file.size > this.dataTransfer.postMaxSize) {
        xhr.send = function () {};
        file.accepted = false;
        file.processing = false;
        file.status = 'rejected';
        file.previewElement.classList.add('dz-error');
        file.previewElement.classList.add('dz-complete');
        return;
      }
      Object.keys(data).forEach(function (key) {
        formData.append(key, data[key]);
      });
    };
  }

  /**
   * Convert files to input value.
   *
   * @since 1.5.6
   * @since 1.7.1 Added the dz argument.
   *
   * @param {object} files Files list.
   * @param {object} dz Dropzone object.
   *
   * @returns {string} Converted value.
   */
  function convertFilesToValue(files, dz) {
    if (!submittedValues[dz.dataTransfer.formId] || !submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId]) {
      return files.length ? JSON.stringify(files) : '';
    }
    files.push.apply(files, submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId]);
    return JSON.stringify(files);
  }

  /**
   * Get input element.
   *
   * @since 1.7.1
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {jQuery} Hidden input element.
   */
  function getInput(dz) {
    return jQuery(dz.element).parents('.wpforms-field-file-upload').find('input[name=' + dz.dataTransfer.name + ']');
  }

  /**
   * Update value in input.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function updateInputValue(dz) {
    var $input = getInput(dz);
    $input.val(convertFilesToValue(getValue(dz.files), dz)).trigger('input');
    if (typeof jQuery.fn.valid !== 'undefined') {
      $input.valid();
    }
  }

  /**
   * Complete event higher order function.
   *
   * @deprecated 1.6.2
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function complete(dz) {
    return function () {
      dz.loading = dz.loading || 0;
      dz.loading--;
      dz.loading = Math.max(dz.loading - 1, 0);
      toggleSubmit(dz);
      updateInputValue(dz);
    };
  }

  /**
   * Add an error message to the current file.
   *
   * @since 1.6.2
   *
   * @param {object} file         File object.
   * @param {string} errorMessage Error message
   */
  function addErrorMessage(file, errorMessage) {
    if (file.isErrorNotUploadedDisplayed) {
      return;
    }
    var span = document.createElement('span');
    span.innerText = errorMessage.toString();
    span.setAttribute('data-dz-errormessage', '');
    file.previewElement.querySelector('.dz-error-message').appendChild(span);
  }

  /**
   * Confirm the upload to the server.
   *
   * The confirmation is needed in order to let PHP know
   * that all the chunks have been uploaded.
   *
   * @since 1.6.2
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function confirmChunksFinishUpload(dz) {
    return function confirm(file) {
      if (!file.retries) {
        file.retries = 0;
      }
      if ('error' === file.status) {
        return;
      }

      /**
       * Retry finalize function.
       *
       * @since 1.6.2
       */
      function retry() {
        file.retries++;
        if (file.retries === 3) {
          addErrorMessage(file, window.wpforms_file_upload.errors.file_not_uploaded);
          return;
        }
        setTimeout(function () {
          confirm(file);
        }, 5000 * file.retries);
      }

      /**
       * Fail handler for ajax request.
       *
       * @since 1.6.2
       *
       * @param {object} response Response from the server
       */
      function fail(response) {
        var hasSpecificError = response.responseJSON && response.responseJSON.success === false && response.responseJSON.data;
        if (hasSpecificError) {
          addErrorMessage(file, response.responseJSON.data);
        } else {
          retry();
        }
      }

      /**
       * Handler for ajax request.
       *
       * @since 1.6.2
       *
       * @param {object} response Response from the server
       */
      function complete(response) {
        file.chunkResponse = JSON.stringify({
          data: response
        });
        dz.loading = dz.loading || 0;
        dz.loading--;
        dz.loading = Math.max(dz.loading, 0);
        toggleSubmit(dz);
        updateInputValue(dz);
      }
      wp.ajax.post(jQuery.extend({
        action: 'wpforms_file_chunks_uploaded',
        form_id: dz.dataTransfer.formId,
        field_id: dz.dataTransfer.fieldId,
        name: file.name
      }, dz.options.params.call(dz, null, null, {
        file: file,
        index: 0
      }))).then(complete).fail(fail);

      // Move to upload the next file, if any.
      dz.processQueue();
    };
  }

  /**
   * Toggle showing empty message.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function toggleMessage(dz) {
    setTimeout(function () {
      var validFiles = dz.files.filter(function (file) {
        return file.accepted;
      });
      if (validFiles.length >= dz.options.maxFiles) {
        dz.element.querySelector('.dz-message').classList.add('hide');
      } else {
        dz.element.querySelector('.dz-message').classList.remove('hide');
      }
    }, 0);
  }

  /**
   * Toggle error message if total size more than limit.
   * Runs for each file.
   *
   * @since 1.5.6
   *
   * @param {object} file Current file.
   * @param {object} dz   Dropzone object.
   */
  function validatePostMaxSizeError(file, dz) {
    setTimeout(function () {
      if (file.size >= dz.dataTransfer.postMaxSize) {
        var errorMessage = window.wpforms_file_upload.errors.post_max_size;
        if (!file.isErrorNotUploadedDisplayed) {
          file.isErrorNotUploadedDisplayed = true;
          errorMessage = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + errorMessage;
          addErrorMessage(file, errorMessage);
        }
      }
    }, 1);
  }

  /**
   * Start File Upload.
   *
   * This would do the initial request to start a file upload. No chunk
   * is uploaded at this stage, instead all the information related to the
   * file are send to the server waiting for an authorization.
   *
   * If the server authorizes the client would start uploading the chunks.
   *
   * @since 1.6.2
   *
   * @param {object} dz   Dropzone object.
   * @param {object} file Current file.
   */
  function initFileUpload(dz, file) {
    wp.ajax.post(jQuery.extend({
      action: 'wpforms_upload_chunk_init',
      form_id: dz.dataTransfer.formId,
      field_id: dz.dataTransfer.fieldId,
      name: file.name,
      slow: isSlow
    }, dz.options.params.call(dz, null, null, {
      file: file,
      index: 0
    }))).then(function (response) {
      // File upload has been authorized.

      for (var key in response) {
        dz.options[key] = response[key];
      }
      if (response.dzchunksize) {
        dz.options.chunkSize = parseInt(response.dzchunksize, 10);
        file.upload.totalChunkCount = Math.ceil(file.size / dz.options.chunkSize);
      }
      dz.processQueue();
    }).fail(function (response) {
      file.status = 'error';
      if (!file.xhr) {
        var field = dz.element.closest('.wpforms-field');
        var hiddenInput = field.querySelector('.dropzone-input');
        var errorMessage = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + window.wpforms_file_upload.errors.default_error;
        file.previewElement.classList.add('dz-processing', 'dz-error', 'dz-complete');
        hiddenInput.classList.add('wpforms-error');
        field.classList.add('wpforms-has-error');
        addErrorMessage(file, errorMessage);
      }
      dz.processQueue();
    });
  }

  /**
   * Validate the file when it was added in the dropzone.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function addedFile(dz) {
    return function (file) {
      if (file.size >= dz.dataTransfer.postMaxSize) {
        validatePostMaxSizeError(file, dz);
      } else {
        speedTest(function () {
          initFileUpload(dz, file);
        });
      }
      dz.loading = dz.loading || 0;
      dz.loading++;
      toggleSubmit(dz);
      toggleMessage(dz);
    };
  }

  /**
   * Send an AJAX request to remove file from the server.
   *
   * @since 1.5.6
   *
   * @param {string} file File name.
   * @param {object} dz Dropzone object.
   */
  function removeFromServer(file, dz) {
    wp.ajax.post({
      action: 'wpforms_remove_file',
      file: file,
      form_id: dz.dataTransfer.formId,
      field_id: dz.dataTransfer.fieldId
    });
  }

  /**
   * Init the file removal on server when user removed it on front-end.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function removedFile(dz) {
    return function (file) {
      toggleMessage(dz);
      var json = file.chunkResponse || (file.xhr || {}).responseText;
      if (json) {
        var object = parseJSON(json);
        if (object && object.data && object.data.file) {
          removeFromServer(object.data.file, dz);
        }
      }

      // Remove submitted value.
      if (Object.prototype.hasOwnProperty.call(file, 'isDefault') && file.isDefault) {
        submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId].splice(file.index, 1);
        dz.options.maxFiles++;
        removeFromServer(file.file, dz);
      }
      updateInputValue(dz);
      dz.loading = dz.loading || 0;
      dz.loading--;
      dz.loading = Math.max(dz.loading, 0);
      toggleSubmit(dz);
      var numErrors = dz.element.querySelectorAll('.dz-preview.dz-error').length;
      if (numErrors === 0) {
        dz.element.classList.remove('wpforms-error');
        dz.element.closest('.wpforms-field').classList.remove('wpforms-has-error');
      }
    };
  }

  /**
   * Process any error that was fired per each file.
   * There might be several errors per file, in that case - display "not uploaded" text only once.
   *
   * @since 1.5.6.1
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function error(dz) {
    return function (file, errorMessage) {
      if (file.isErrorNotUploadedDisplayed) {
        return;
      }
      if (_typeof(errorMessage) === 'object') {
        errorMessage = Object.prototype.hasOwnProperty.call(errorMessage, 'data') && typeof errorMessage.data === 'string' ? errorMessage.data : '';
      }
      errorMessage = errorMessage !== '0' ? errorMessage : '';
      file.isErrorNotUploadedDisplayed = true;
      file.previewElement.querySelectorAll('[data-dz-errormessage]')[0].textContent = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + errorMessage;
      dz.element.classList.add('wpforms-error');
      dz.element.closest('.wpforms-field').classList.add('wpforms-has-error');
    };
  }

  /**
   * Preset previously submitted files to the dropzone.
   *
   * @since 1.7.1
   *
   * @param {object} dz Dropzone object.
   */
  function presetSubmittedData(dz) {
    var files = parseJSON(getInput(dz).val());
    if (!files || !files.length) {
      return;
    }
    submittedValues[dz.dataTransfer.formId] = [];

    // We do deep cloning an object to be sure that data is passed without links.
    submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId] = JSON.parse(JSON.stringify(files));
    files.forEach(function (file, index) {
      file.isDefault = true;
      file.index = index;
      if (file.type.match(/image.*/)) {
        dz.displayExistingFile(file, file.url);
        return;
      }
      dz.emit('addedfile', file);
      dz.emit('complete', file);
    });
    dz.options.maxFiles = dz.options.maxFiles - files.length;
  }

  /**
   * Dropzone.js init for each field.
   *
   * @since 1.5.6
   *
   * @param {object} $el WPForms uploader DOM element.
   *
   * @returns {object} Dropzone object.
   */
  function dropZoneInit($el) {
    if ($el.dropzone) {
      return $el.dropzone;
    }
    var formId = parseInt($el.dataset.formId, 10);
    var fieldId = parseInt($el.dataset.fieldId, 10) || 0;
    var maxFiles = parseInt($el.dataset.maxFileNumber, 10);
    var acceptedFiles = $el.dataset.extensions.split(',').map(function (el) {
      return '.' + el;
    }).join(',');

    // Configure and modify Dropzone library.
    var dz = new window.Dropzone($el, {
      url: window.wpforms_file_upload.url,
      addRemoveLinks: true,
      chunking: true,
      forceChunking: true,
      retryChunks: true,
      chunkSize: parseInt($el.dataset.fileChunkSize, 10),
      paramName: $el.dataset.inputName,
      parallelChunkUploads: !!($el.dataset.parallelUploads || '').match(/^true$/i),
      parallelUploads: parseInt($el.dataset.maxParallelUploads, 10),
      autoProcessQueue: false,
      maxFilesize: (parseInt($el.dataset.maxSize, 10) / (1024 * 1024)).toFixed(2),
      maxFiles: maxFiles,
      acceptedFiles: acceptedFiles,
      dictMaxFilesExceeded: window.wpforms_file_upload.errors.file_limit.replace('{fileLimit}', maxFiles),
      dictInvalidFileType: window.wpforms_file_upload.errors.file_extension,
      dictFileTooBig: window.wpforms_file_upload.errors.file_size
    });

    // Custom variables.
    dz.dataTransfer = {
      postMaxSize: $el.dataset.maxSize,
      name: $el.dataset.inputName,
      formId: formId,
      fieldId: fieldId
    };
    presetSubmittedData(dz);

    // Process events.
    dz.on('sending', sending(dz, {
      action: 'wpforms_upload_chunk',
      form_id: formId,
      field_id: fieldId
    }));
    dz.on('addedfile', addedFile(dz));
    dz.on('removedfile', removedFile(dz));
    dz.on('complete', confirmChunksFinishUpload(dz));
    dz.on('error', error(dz));
    return dz;
  }

  /**
   * Hidden Dropzone input focus event handler.
   *
   * @since 1.8.1
   */
  function dropzoneInputFocus() {
    $(this).prev('.wpforms-uploader').addClass('wpforms-focus');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   */
  function dropzoneInputBlur() {
    $(this).prev('.wpforms-uploader').removeClass('wpforms-focus');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   *
   * @param {object} e Event object.
   */
  function dropzoneInputKeypress(e) {
    e.preventDefault();
    if (e.keyCode !== 13) {
      return;
    }
    $(this).prev('.wpforms-uploader').trigger('click');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   */
  function dropzoneClick() {
    $(this).next('.dropzone-input').trigger('focus');
  }

  /**
   * Classic File upload success callback to determine if all files are uploaded.
   *
   * @since 1.8.3
   *
   * @param {Event} e Event.
   * @param {jQuery} $form Form.
   */
  function combinedUploadsSizeOk(e, $form) {
    if (anyUploadsInProgress()) {
      disableSubmitButton($form);
    }
  }

  /**
   * Events.
   *
   * @since 1.8.1
   */
  function events() {
    $('.dropzone-input').on('focus', dropzoneInputFocus).on('blur', dropzoneInputBlur).on('keypress', dropzoneInputKeypress);
    $('.wpforms-uploader').on('click', dropzoneClick);
    $('form.wpforms-form').on('wpformsCombinedUploadsSizeOk', combinedUploadsSizeOk);
  }

  /**
   * DOMContentLoaded handler.
   *
   * @since 1.5.6
   */
  function ready() {
    window.wpforms = window.wpforms || {};
    window.wpforms.dropzones = [].slice.call(document.querySelectorAll('.wpforms-uploader')).map(dropZoneInit);
    events();
  }

  /**
   * Modern File Upload engine.
   *
   * @since 1.6.0
   */
  var wpformsModernFileUpload = {
    /**
     * Start the initialization.
     *
     * @since 1.6.0
     */
    init: function init() {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
      } else {
        ready();
      }
    }
  };

  // Call init and save in global variable.
  wpformsModernFileUpload.init();
  window.wpformsModernFileUpload = wpformsModernFileUpload;
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfdHlwZW9mIiwib2JqIiwiU3ltYm9sIiwiaXRlcmF0b3IiLCJjb25zdHJ1Y3RvciIsInByb3RvdHlwZSIsIiQiLCJpc1Nsb3ciLCJzdWJtaXR0ZWRWYWx1ZXMiLCJzcGVlZFRlc3RTZXR0aW5ncyIsIm1heFRpbWUiLCJwYXlsb2FkU2l6ZSIsImdldFBheWxvYWQiLCJkYXRhIiwiaSIsIlN0cmluZyIsImZyb21DaGFyQ29kZSIsIk1hdGgiLCJyb3VuZCIsInJhbmRvbSIsInNwZWVkVGVzdCIsIm5leHQiLCJzZXRUaW1lb3V0Iiwic3RhcnQiLCJEYXRlIiwid3AiLCJhamF4IiwicG9zdCIsImFjdGlvbiIsInRoZW4iLCJkZWx0YSIsImZhaWwiLCJ0b2dnbGVMb2FkaW5nTWVzc2FnZSIsIiRmb3JtIiwiZmluZCIsImxlbmd0aCIsImJlZm9yZSIsImNvbmNhdCIsIndpbmRvdyIsIndwZm9ybXNfZmlsZV91cGxvYWQiLCJsb2FkaW5nX21lc3NhZ2UiLCJ1cGxvYWRJblByb2dyZXNzIiwiZHoiLCJsb2FkaW5nIiwiZ2V0RmlsZXNXaXRoU3RhdHVzIiwiYW55VXBsb2Fkc0luUHJvZ3Jlc3MiLCJ3cGZvcm1zIiwiZHJvcHpvbmVzIiwic29tZSIsImRpc2FibGVTdWJtaXRCdXR0b24iLCIkYnRuIiwiJGJ0bk5leHQiLCJoYW5kbGVyIiwicHJvcCIsIldQRm9ybXNVdGlscyIsInRyaWdnZXJFdmVudCIsImF0dHIiLCJwYXJlbnQiLCJhZGRDbGFzcyIsImFwcGVuZCIsImNzcyIsIndpZHRoIiwib3V0ZXJXaWR0aCIsImhlaWdodCIsIm91dGVySGVpZ2h0Iiwib24iLCJ0b2dnbGVTdWJtaXQiLCJqUXVlcnkiLCJlbGVtZW50IiwiY2xvc2VzdCIsImRpc2FibGVkIiwiaXNCdXR0b25EaXNhYmxlZCIsIkJvb2xlYW4iLCJoYXNDbGFzcyIsIm9mZiIsInJlbW92ZSIsInJlbW92ZUNsYXNzIiwicGFyc2VKU09OIiwic3RyIiwiSlNPTiIsInBhcnNlIiwiZSIsIm9ubHlXaXRoTGVuZ3RoIiwiZWwiLCJvbmx5UG9zaXRpdmUiLCJnZXRYSFIiLCJjaHVua1Jlc3BvbnNlIiwieGhyIiwiZ2V0UmVzcG9uc2VUZXh0IiwicmVzcG9uc2VUZXh0IiwiZ2V0RGF0YSIsImdldFZhbHVlIiwiZmlsZXMiLCJtYXAiLCJmaWx0ZXIiLCJzZW5kaW5nIiwiZmlsZSIsImZvcm1EYXRhIiwic2l6ZSIsImRhdGFUcmFuc2ZlciIsInBvc3RNYXhTaXplIiwic2VuZCIsImFjY2VwdGVkIiwicHJvY2Vzc2luZyIsInN0YXR1cyIsInByZXZpZXdFbGVtZW50IiwiY2xhc3NMaXN0IiwiYWRkIiwiT2JqZWN0Iiwia2V5cyIsImZvckVhY2giLCJrZXkiLCJjb252ZXJ0RmlsZXNUb1ZhbHVlIiwiZm9ybUlkIiwiZmllbGRJZCIsInN0cmluZ2lmeSIsInB1c2giLCJhcHBseSIsImdldElucHV0IiwicGFyZW50cyIsIm5hbWUiLCJ1cGRhdGVJbnB1dFZhbHVlIiwiJGlucHV0IiwidmFsIiwidHJpZ2dlciIsImZuIiwidmFsaWQiLCJjb21wbGV0ZSIsIm1heCIsImFkZEVycm9yTWVzc2FnZSIsImVycm9yTWVzc2FnZSIsImlzRXJyb3JOb3RVcGxvYWRlZERpc3BsYXllZCIsInNwYW4iLCJkb2N1bWVudCIsImNyZWF0ZUVsZW1lbnQiLCJpbm5lclRleHQiLCJ0b1N0cmluZyIsInNldEF0dHJpYnV0ZSIsInF1ZXJ5U2VsZWN0b3IiLCJhcHBlbmRDaGlsZCIsImNvbmZpcm1DaHVua3NGaW5pc2hVcGxvYWQiLCJjb25maXJtIiwicmV0cmllcyIsInJldHJ5IiwiZXJyb3JzIiwiZmlsZV9ub3RfdXBsb2FkZWQiLCJyZXNwb25zZSIsImhhc1NwZWNpZmljRXJyb3IiLCJyZXNwb25zZUpTT04iLCJzdWNjZXNzIiwiZXh0ZW5kIiwiZm9ybV9pZCIsImZpZWxkX2lkIiwib3B0aW9ucyIsInBhcmFtcyIsImNhbGwiLCJpbmRleCIsInByb2Nlc3NRdWV1ZSIsInRvZ2dsZU1lc3NhZ2UiLCJ2YWxpZEZpbGVzIiwibWF4RmlsZXMiLCJ2YWxpZGF0ZVBvc3RNYXhTaXplRXJyb3IiLCJwb3N0X21heF9zaXplIiwiaW5pdEZpbGVVcGxvYWQiLCJzbG93IiwiZHpjaHVua3NpemUiLCJjaHVua1NpemUiLCJwYXJzZUludCIsInVwbG9hZCIsInRvdGFsQ2h1bmtDb3VudCIsImNlaWwiLCJmaWVsZCIsImhpZGRlbklucHV0IiwiZGVmYXVsdF9lcnJvciIsImFkZGVkRmlsZSIsInJlbW92ZUZyb21TZXJ2ZXIiLCJyZW1vdmVkRmlsZSIsImpzb24iLCJvYmplY3QiLCJoYXNPd25Qcm9wZXJ0eSIsImlzRGVmYXVsdCIsInNwbGljZSIsIm51bUVycm9ycyIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJlcnJvciIsInRleHRDb250ZW50IiwicHJlc2V0U3VibWl0dGVkRGF0YSIsInR5cGUiLCJtYXRjaCIsImRpc3BsYXlFeGlzdGluZ0ZpbGUiLCJ1cmwiLCJlbWl0IiwiZHJvcFpvbmVJbml0IiwiJGVsIiwiZHJvcHpvbmUiLCJkYXRhc2V0IiwibWF4RmlsZU51bWJlciIsImFjY2VwdGVkRmlsZXMiLCJleHRlbnNpb25zIiwic3BsaXQiLCJqb2luIiwiRHJvcHpvbmUiLCJhZGRSZW1vdmVMaW5rcyIsImNodW5raW5nIiwiZm9yY2VDaHVua2luZyIsInJldHJ5Q2h1bmtzIiwiZmlsZUNodW5rU2l6ZSIsInBhcmFtTmFtZSIsImlucHV0TmFtZSIsInBhcmFsbGVsQ2h1bmtVcGxvYWRzIiwicGFyYWxsZWxVcGxvYWRzIiwibWF4UGFyYWxsZWxVcGxvYWRzIiwiYXV0b1Byb2Nlc3NRdWV1ZSIsIm1heEZpbGVzaXplIiwibWF4U2l6ZSIsInRvRml4ZWQiLCJkaWN0TWF4RmlsZXNFeGNlZWRlZCIsImZpbGVfbGltaXQiLCJyZXBsYWNlIiwiZGljdEludmFsaWRGaWxlVHlwZSIsImZpbGVfZXh0ZW5zaW9uIiwiZGljdEZpbGVUb29CaWciLCJmaWxlX3NpemUiLCJkcm9wem9uZUlucHV0Rm9jdXMiLCJwcmV2IiwiZHJvcHpvbmVJbnB1dEJsdXIiLCJkcm9wem9uZUlucHV0S2V5cHJlc3MiLCJwcmV2ZW50RGVmYXVsdCIsImtleUNvZGUiLCJkcm9wem9uZUNsaWNrIiwiY29tYmluZWRVcGxvYWRzU2l6ZU9rIiwiZXZlbnRzIiwicmVhZHkiLCJzbGljZSIsIndwZm9ybXNNb2Rlcm5GaWxlVXBsb2FkIiwiaW5pdCIsInJlYWR5U3RhdGUiLCJhZGRFdmVudExpc3RlbmVyIl0sInNvdXJjZXMiOlsiZmFrZV84MmQxMWJmYS5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKiBnbG9iYWwgV1BGb3Jtc1V0aWxzICovXG4ndXNlIHN0cmljdCc7XG5cbiggZnVuY3Rpb24oICQgKSB7XG5cblx0LyoqXG5cdCAqIEFsbCBjb25uZWN0aW9ucyBhcmUgc2xvdyBieSBkZWZhdWx0LlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHR5cGUge2Jvb2xlYW58bnVsbH1cblx0ICovXG5cdHZhciBpc1Nsb3cgPSBudWxsO1xuXG5cdC8qKlxuXHQgKiBQcmV2aW91c2x5IHN1Ym1pdHRlZCBkYXRhLlxuXHQgKlxuXHQgKiBAc2luY2UgMS43LjFcblx0ICpcblx0ICogQHR5cGUge0FycmF5fVxuXHQgKi9cblx0dmFyIHN1Ym1pdHRlZFZhbHVlcyA9IFtdO1xuXG5cdC8qKlxuXHQgKiBEZWZhdWx0IHNldHRpbmdzIGZvciBvdXIgc3BlZWQgdGVzdC5cblx0ICpcblx0ICogQHNpbmNlIDEuNi4yXG5cdCAqXG5cdCAqIEB0eXBlIHt7bWF4VGltZTogbnVtYmVyLCBwYXlsb2FkU2l6ZTogbnVtYmVyfX1cblx0ICovXG5cdHZhciBzcGVlZFRlc3RTZXR0aW5ncyA9IHtcblx0XHRtYXhUaW1lOiAzMDAwLCAvLyBNYXggdGltZSAobXMpIGl0IHNob3VsZCB0YWtlIHRvIGJlIGNvbnNpZGVyZWQgYSAnZmFzdCBjb25uZWN0aW9uJy5cblx0XHRwYXlsb2FkU2l6ZTogMTAwICogMTAyNCwgLy8gUGF5bG9hZCBzaXplLlxuXHR9O1xuXG5cdC8qKlxuXHQgKiBDcmVhdGUgYSByYW5kb20gcGF5bG9hZCBmb3IgdGhlIHNwZWVkIHRlc3QuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjYuMlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7c3RyaW5nfSBSYW5kb20gcGF5bG9hZC5cblx0ICovXG5cdGZ1bmN0aW9uIGdldFBheWxvYWQoKSB7XG5cblx0XHR2YXIgZGF0YSA9ICcnO1xuXG5cdFx0Zm9yICggdmFyIGkgPSAwOyBpIDwgc3BlZWRUZXN0U2V0dGluZ3MucGF5bG9hZFNpemU7ICsraSApIHtcblx0XHRcdGRhdGEgKz0gU3RyaW5nLmZyb21DaGFyQ29kZSggTWF0aC5yb3VuZCggTWF0aC5yYW5kb20oKSAqIDM2ICsgNjQgKSApO1xuXHRcdH1cblxuXHRcdHJldHVybiBkYXRhO1xuXHR9XG5cblx0LyoqXG5cdCAqIFJ1biBzcGVlZCB0ZXN0cyBhbmQgZmxhZyB0aGUgY2xpZW50cyBhcyBzbG93IG9yIG5vdC4gSWYgYSBjb25uZWN0aW9uXG5cdCAqIGlzIHNsb3cgaXQgd291bGQgbGV0IHRoZSBiYWNrZW5kIGtub3cgYW5kIHRoZSBiYWNrZW5kIG1vc3QgbGlrZWx5XG5cdCAqIHdvdWxkIGRpc2FibGUgcGFyYWxsZWwgdXBsb2FkcyBhbmQgd291bGQgc2V0IHNtYWxsZXIgY2h1bmsgc2l6ZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjYuMlxuXHQgKlxuXHQgKiBAcGFyYW0ge0Z1bmN0aW9ufSBuZXh0IEZ1bmN0aW9uIHRvIGNhbGwgd2hlbiB0aGUgc3BlZWQgZGV0ZWN0aW9uIGlzIGRvbmUuXG5cdCAqL1xuXHRmdW5jdGlvbiBzcGVlZFRlc3QoIG5leHQgKSB7XG5cblx0XHRpZiAoIG51bGwgIT09IGlzU2xvdyApIHtcblx0XHRcdHNldFRpbWVvdXQoIG5leHQgKTtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR2YXIgZGF0YSAgPSBnZXRQYXlsb2FkKCk7XG5cdFx0dmFyIHN0YXJ0ID0gbmV3IERhdGU7XG5cblx0XHR3cC5hamF4LnBvc3QoIHtcblx0XHRcdGFjdGlvbjogJ3dwZm9ybXNfZmlsZV91cGxvYWRfc3BlZWRfdGVzdCcsXG5cdFx0XHRkYXRhOiBkYXRhLFxuXHRcdH0gKS50aGVuKCBmdW5jdGlvbigpIHtcblxuXHRcdFx0dmFyIGRlbHRhID0gbmV3IERhdGUgLSBzdGFydDtcblxuXHRcdFx0aXNTbG93ID0gZGVsdGEgPj0gc3BlZWRUZXN0U2V0dGluZ3MubWF4VGltZTtcblxuXHRcdFx0bmV4dCgpO1xuXHRcdH0gKS5mYWlsKCBmdW5jdGlvbigpIHtcblxuXHRcdFx0aXNTbG93ID0gdHJ1ZTtcblxuXHRcdFx0bmV4dCgpO1xuXHRcdH0gKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgbG9hZGluZyBtZXNzYWdlIGFib3ZlIHN1Ym1pdCBidXR0b24uXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gJGZvcm0galF1ZXJ5IGZvcm0gZWxlbWVudC5cblx0ICpcblx0ICogQHJldHVybnMge0Z1bmN0aW9ufSBldmVudCBoYW5kbGVyIGZ1bmN0aW9uLlxuXHQgKi9cblx0ZnVuY3Rpb24gdG9nZ2xlTG9hZGluZ01lc3NhZ2UoICRmb3JtICkge1xuXG5cdFx0cmV0dXJuIGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRpZiAoICRmb3JtLmZpbmQoICcud3Bmb3Jtcy11cGxvYWRpbmctaW4tcHJvZ3Jlc3MtYWxlcnQnICkubGVuZ3RoICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdCRmb3JtLmZpbmQoICcud3Bmb3Jtcy1zdWJtaXQtY29udGFpbmVyJyApXG5cdFx0XHRcdC5iZWZvcmUoXG5cdFx0XHRcdFx0YDxkaXYgY2xhc3M9XCJ3cGZvcm1zLWVycm9yLWFsZXJ0IHdwZm9ybXMtdXBsb2FkaW5nLWluLXByb2dyZXNzLWFsZXJ0XCI+XG5cdFx0XHRcdFx0XHQke3dpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmxvYWRpbmdfbWVzc2FnZX1cblx0XHRcdFx0XHQ8L2Rpdj5gXG5cdFx0XHRcdCk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBJcyBhIGZpZWxkIGxvYWRpbmc/XG5cdCAqXG5cdCAqIEBzaW5jZSAxLjcuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbGVhbn0gdHJ1ZSBpZiB0aGUgZmllbGQgaXMgbG9hZGluZy5cblx0ICovXG5cdGZ1bmN0aW9uIHVwbG9hZEluUHJvZ3Jlc3MoIGR6ICkge1xuXG5cdFx0cmV0dXJuIGR6LmxvYWRpbmcgPiAwIHx8IGR6LmdldEZpbGVzV2l0aFN0YXR1cyggJ2Vycm9yJyApLmxlbmd0aCA+IDA7XG5cdH1cblxuXHQvKipcblx0ICogSXMgYXQgbGVhc3Qgb25lIGZpZWxkIGxvYWRpbmc/XG5cdCAqXG5cdCAqIEBzaW5jZSAxLjcuNlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbGVhbn0gdHJ1ZSBpZiBhdCBsZWFzdCBvbmUgZmllbGQgaXMgbG9hZGluZy5cblx0ICovXG5cdGZ1bmN0aW9uIGFueVVwbG9hZHNJblByb2dyZXNzKCkge1xuXG5cdFx0dmFyIGFueVVwbG9hZHNJblByb2dyZXNzID0gZmFsc2U7XG5cblx0XHR3aW5kb3cud3Bmb3Jtcy5kcm9wem9uZXMuc29tZSggZnVuY3Rpb24oIGR6ICkge1xuXG5cdFx0XHRpZiAoIHVwbG9hZEluUHJvZ3Jlc3MoIGR6ICkgKSB7XG5cdFx0XHRcdGFueVVwbG9hZHNJblByb2dyZXNzID0gdHJ1ZTtcblxuXHRcdFx0XHRyZXR1cm4gdHJ1ZTtcblx0XHRcdH1cblx0XHR9ICk7XG5cblx0XHRyZXR1cm4gYW55VXBsb2Fkc0luUHJvZ3Jlc3M7XG5cdH1cblxuXHQvKipcblx0ICogRGlzYWJsZSBzdWJtaXQgYnV0dG9uIGFuZCBhZGQgb3ZlcmxheS5cblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9ICRmb3JtIGpRdWVyeSBmb3JtIGVsZW1lbnQuXG5cdCAqL1xuXHRmdW5jdGlvbiBkaXNhYmxlU3VibWl0QnV0dG9uKCAkZm9ybSApIHtcblxuXHRcdC8vIEZpbmQgdGhlIHByaW1hcnkgc3VibWl0IGJ1dHRvbiBhbmQgdGhlIFwiTmV4dFwiIGJ1dHRvbiBmb3IgbXVsdGktcGFnZSBmb3Jtcy5cblx0XHRsZXQgJGJ0biA9ICRmb3JtLmZpbmQoICcud3Bmb3Jtcy1zdWJtaXQnICk7XG5cdFx0Y29uc3QgJGJ0bk5leHQgPSAkZm9ybS5maW5kKCAnLndwZm9ybXMtcGFnZS1uZXh0OnZpc2libGUnICk7XG5cdFx0Y29uc3QgaGFuZGxlciA9IHRvZ2dsZUxvYWRpbmdNZXNzYWdlKCAkZm9ybSApOyAvLyBHZXQgdGhlIGhhbmRsZXIgZnVuY3Rpb24gZm9yIGxvYWRpbmcgbWVzc2FnZSB0b2dnbGUuXG5cblx0XHQvLyBGb3IgbXVsdGktcGFnZXMgbGF5b3V0LCB1c2UgdGhlIFwiTmV4dFwiIGJ1dHRvbiBpbnN0ZWFkIG9mIHRoZSBwcmltYXJ5IHN1Ym1pdCBidXR0b24uXG5cdFx0aWYgKCAkZm9ybS5maW5kKCAnLndwZm9ybXMtcGFnZS1pbmRpY2F0b3InICkubGVuZ3RoICE9PSAwICYmICRidG5OZXh0Lmxlbmd0aCAhPT0gMCApIHtcblx0XHRcdCRidG4gPSAkYnRuTmV4dDtcblx0XHR9XG5cblx0XHQvLyBEaXNhYmxlIHRoZSBzdWJtaXQgYnV0dG9uLlxuXHRcdCRidG4ucHJvcCggJ2Rpc2FibGVkJywgdHJ1ZSApO1xuXHRcdFdQRm9ybXNVdGlscy50cmlnZ2VyRXZlbnQoICRmb3JtLCAnd3Bmb3Jtc0Zvcm1TdWJtaXRCdXR0b25EaXNhYmxlJywgWyAkZm9ybSwgJGJ0biBdICk7XG5cblx0XHQvLyBJZiB0aGUgb3ZlcmxheSBpcyBub3QgYWxyZWFkeSBhZGRlZCBhbmQgdGhlIGJ1dHRvbiBpcyBvZiB0eXBlIFwic3VibWl0XCIsIGFkZCBhbiBvdmVybGF5LlxuXHRcdGlmICggISAkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LW92ZXJsYXknICkubGVuZ3RoICYmICRidG4uYXR0ciggJ3R5cGUnICkgPT09ICdzdWJtaXQnICkge1xuXG5cdFx0XHQvLyBBZGQgYSBjb250YWluZXIgZm9yIHRoZSBvdmVybGF5IGFuZCBhcHBlbmQgdGhlIG92ZXJsYXkgZWxlbWVudCB0byBpdC5cblx0XHRcdCRidG4ucGFyZW50KCkuYWRkQ2xhc3MoICd3cGZvcm1zLXN1Ym1pdC1vdmVybGF5LWNvbnRhaW5lcicgKTtcblx0XHRcdCRidG4ucGFyZW50KCkuYXBwZW5kKCAnPGRpdiBjbGFzcz1cIndwZm9ybXMtc3VibWl0LW92ZXJsYXlcIj48L2Rpdj4nICk7XG5cblx0XHRcdC8vIFNldCB0aGUgb3ZlcmxheSBkaW1lbnNpb25zIHRvIG1hdGNoIHRoZSBzdWJtaXQgYnV0dG9uJ3Mgc2l6ZS5cblx0XHRcdCRmb3JtLmZpbmQoICcud3Bmb3Jtcy1zdWJtaXQtb3ZlcmxheScgKS5jc3MoIHtcblx0XHRcdFx0d2lkdGg6IGAkeyRidG4ub3V0ZXJXaWR0aCgpfXB4YCxcblx0XHRcdFx0aGVpZ2h0OiBgJHskYnRuLnBhcmVudCgpLm91dGVySGVpZ2h0KCl9cHhgLFxuXHRcdFx0fSApO1xuXG5cdFx0XHQvLyBBdHRhY2ggdGhlIGNsaWNrIGV2ZW50IHRvIHRoZSBvdmVybGF5IHNvIHRoYXQgaXQgdHJpZ2dlcnMgdGhlIGhhbmRsZXIgZnVuY3Rpb24uXG5cdFx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LW92ZXJsYXknICkub24oICdjbGljaycsIGhhbmRsZXIgKTtcblx0XHR9XG5cdH1cblxuXHQvKipcblx0ICogRGlzYWJsZSBzdWJtaXQgYnV0dG9uIHdoZW4gd2UgYXJlIHNlbmRpbmcgZmlsZXMgdG8gdGhlIHNlcnZlci5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqL1xuXHRmdW5jdGlvbiB0b2dnbGVTdWJtaXQoIGR6ICkgeyAvLyBlc2xpbnQtZGlzYWJsZS1saW5lIGNvbXBsZXhpdHlcblxuXHRcdHZhciAkZm9ybSA9IGpRdWVyeSggZHouZWxlbWVudCApLmNsb3Nlc3QoICdmb3JtJyApLFxuXHRcdFx0JGJ0biA9ICRmb3JtLmZpbmQoICcud3Bmb3Jtcy1zdWJtaXQnICksXG5cdFx0XHQkYnRuTmV4dCA9ICRmb3JtLmZpbmQoICcud3Bmb3Jtcy1wYWdlLW5leHQ6dmlzaWJsZScgKSxcblx0XHRcdGhhbmRsZXIgPSB0b2dnbGVMb2FkaW5nTWVzc2FnZSggJGZvcm0gKSxcblx0XHRcdGRpc2FibGVkID0gdXBsb2FkSW5Qcm9ncmVzcyggZHogKTtcblxuXHRcdC8vIEZvciBtdWx0aS1wYWdlcyBsYXlvdXQuXG5cdFx0aWYgKCAkZm9ybS5maW5kKCAnLndwZm9ybXMtcGFnZS1pbmRpY2F0b3InICkubGVuZ3RoICE9PSAwICYmICRidG5OZXh0Lmxlbmd0aCAhPT0gMCApIHtcblx0XHRcdCRidG4gPSAkYnRuTmV4dDtcblx0XHR9XG5cblx0XHRjb25zdCBpc0J1dHRvbkRpc2FibGVkID0gQm9vbGVhbiggJGJ0bi5wcm9wKCAnZGlzYWJsZWQnICkgKSB8fCAkYnRuLmhhc0NsYXNzKCAnd3Bmb3Jtcy1kaXNhYmxlZCcgKTtcblxuXHRcdGlmICggZGlzYWJsZWQgPT09IGlzQnV0dG9uRGlzYWJsZWQgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0aWYgKCBkaXNhYmxlZCApIHtcblx0XHRcdGRpc2FibGVTdWJtaXRCdXR0b24oICRmb3JtICk7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0aWYgKCBhbnlVcGxvYWRzSW5Qcm9ncmVzcygpICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdCRidG4ucHJvcCggJ2Rpc2FibGVkJywgZmFsc2UgKTtcblx0XHRXUEZvcm1zVXRpbHMudHJpZ2dlckV2ZW50KCAkZm9ybSwgJ3dwZm9ybXNGb3JtU3VibWl0QnV0dG9uUmVzdG9yZScsIFsgJGZvcm0sICRidG4gXSApO1xuXHRcdCRmb3JtLmZpbmQoICcud3Bmb3Jtcy1zdWJtaXQtb3ZlcmxheScgKS5vZmYoICdjbGljaycsIGhhbmRsZXIgKTtcblx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LW92ZXJsYXknICkucmVtb3ZlKCk7XG5cdFx0JGJ0bi5wYXJlbnQoKS5yZW1vdmVDbGFzcyggJ3dwZm9ybXMtc3VibWl0LW92ZXJsYXktY29udGFpbmVyJyApO1xuXHRcdGlmICggJGZvcm0uZmluZCggJy53cGZvcm1zLXVwbG9hZGluZy1pbi1wcm9ncmVzcy1hbGVydCcgKS5sZW5ndGggKSB7XG5cdFx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtdXBsb2FkaW5nLWluLXByb2dyZXNzLWFsZXJ0JyApLnJlbW92ZSgpO1xuXHRcdH1cblx0fVxuXG5cdC8qKlxuXHQgKiBUcnkgdG8gcGFyc2UgSlNPTiBvciByZXR1cm4gZmFsc2UuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge3N0cmluZ30gc3RyIEpTT04gc3RyaW5nIGNhbmRpZGF0ZS5cblx0ICpcblx0ICogQHJldHVybnMgeyp9IFBhcnNlIG9iamVjdCBvciBmYWxzZS5cblx0ICovXG5cdGZ1bmN0aW9uIHBhcnNlSlNPTiggc3RyICkge1xuXHRcdHRyeSB7XG5cdFx0XHRyZXR1cm4gSlNPTi5wYXJzZSggc3RyICk7XG5cdFx0fSBjYXRjaCAoIGUgKSB7XG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fVxuXHR9XG5cblx0LyoqXG5cdCAqIExlYXZlIG9ubHkgb2JqZWN0cyB3aXRoIGxlbmd0aC5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCBBbnkgYXJyYXkuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtib29sfSBIYXMgbGVuZ3RoIG1vcmUgdGhhbiAwIG9yIG5vLlxuXHQgKi9cblx0ZnVuY3Rpb24gb25seVdpdGhMZW5ndGgoIGVsICkge1xuXHRcdHJldHVybiBlbC5sZW5ndGggPiAwO1xuXHR9XG5cblx0LyoqXG5cdCAqIExlYXZlIG9ubHkgcG9zaXRpdmUgZWxlbWVudHMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0geyp9IGVsIEFueSBlbGVtZW50LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Kn0gRmlsdGVyIG9ubHkgcG9zaXRpdmUuXG5cdCAqL1xuXHRmdW5jdGlvbiBvbmx5UG9zaXRpdmUoIGVsICkge1xuXHRcdHJldHVybiBlbDtcblx0fVxuXG5cdC8qKlxuXHQgKiBHZXQgeGhyLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGVsIE9iamVjdCB3aXRoIHhociBwcm9wZXJ0eS5cblx0ICpcblx0ICogQHJldHVybnMgeyp9IEdldCBYSFIuXG5cdCAqL1xuXHRmdW5jdGlvbiBnZXRYSFIoIGVsICkge1xuXHRcdHJldHVybiBlbC5jaHVua1Jlc3BvbnNlIHx8IGVsLnhocjtcblx0fVxuXG5cdC8qKlxuXHQgKiBHZXQgcmVzcG9uc2UgdGV4dC5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCBYaHIgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7b2JqZWN0fSBSZXNwb25zZSB0ZXh0LlxuXHQgKi9cblx0ZnVuY3Rpb24gZ2V0UmVzcG9uc2VUZXh0KCBlbCApIHtcblx0XHRyZXR1cm4gdHlwZW9mIGVsID09PSAnc3RyaW5nJyA/IGVsIDogZWwucmVzcG9uc2VUZXh0O1xuXHR9XG5cblx0LyoqXG5cdCAqIEdldCBkYXRhLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGVsIE9iamVjdCB3aXRoIGRhdGEgcHJvcGVydHkuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtvYmplY3R9IERhdGEuXG5cdCAqL1xuXHRmdW5jdGlvbiBnZXREYXRhKCBlbCApIHtcblx0XHRyZXR1cm4gZWwuZGF0YTtcblx0fVxuXG5cdC8qKlxuXHQgKiBHZXQgdmFsdWUgZnJvbSBmaWxlcy5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBmaWxlcyBEcm9wem9uZSBmaWxlcy5cblx0ICpcblx0ICogQHJldHVybnMge29iamVjdH0gUHJlcGFyZWQgdmFsdWUuXG5cdCAqL1xuXHRmdW5jdGlvbiBnZXRWYWx1ZSggZmlsZXMgKSB7XG5cdFx0cmV0dXJuIGZpbGVzXG5cdFx0XHQubWFwKCBnZXRYSFIgKVxuXHRcdFx0LmZpbHRlciggb25seVBvc2l0aXZlIClcblx0XHRcdC5tYXAoIGdldFJlc3BvbnNlVGV4dCApXG5cdFx0XHQuZmlsdGVyKCBvbmx5V2l0aExlbmd0aCApXG5cdFx0XHQubWFwKCBwYXJzZUpTT04gKVxuXHRcdFx0LmZpbHRlciggb25seVBvc2l0aXZlIClcblx0XHRcdC5tYXAoIGdldERhdGEgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBTZW5kaW5nIGV2ZW50IGhpZ2hlciBvcmRlciBmdW5jdGlvbi5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqIEBzaW5jZSAxLjUuNi4xIEFkZGVkIHNwZWNpYWwgcHJvY2Vzc2luZyBvZiBhIGZpbGUgdGhhdCBpcyBsYXJnZXIgdGhhbiBzZXJ2ZXIncyBwb3N0X21heF9zaXplLlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZGF0YSBBZGRpbmcgZGF0YSB0byByZXF1ZXN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7RnVuY3Rpb259IEhhbmRsZXIgZnVuY3Rpb24uXG5cdCAqL1xuXHRmdW5jdGlvbiBzZW5kaW5nKCBkeiwgZGF0YSApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbiggZmlsZSwgeGhyLCBmb3JtRGF0YSApIHtcblxuXHRcdFx0Lypcblx0XHRcdCAqIFdlIHNob3VsZCBub3QgYWxsb3cgc2VuZGluZyBhIGZpbGUsIHRoYXQgZXhjZWVkcyBzZXJ2ZXIgcG9zdF9tYXhfc2l6ZS5cblx0XHRcdCAqIFdpdGggdGhpcyBcImhhY2tcIiB3ZSByZWRlZmluZSB0aGUgZGVmYXVsdCBzZW5kIGZ1bmN0aW9uYWxpdHlcblx0XHRcdCAqIHRvIHByZXZlbnQgb25seSB0aGlzIG9iamVjdCBmcm9tIHNlbmRpbmcgYSByZXF1ZXN0IGF0IGFsbC5cblx0XHRcdCAqIFRoZSBmaWxlIHRoYXQgZ2VuZXJhdGVkIHRoYXQgZXJyb3Igc2hvdWxkIGJlIG1hcmtlZCBhcyByZWplY3RlZCxcblx0XHRcdCAqIHNvIERyb3B6b25lIHdpbGwgc2lsZW50bHkgaWdub3JlIGl0LlxuXHRcdFx0ICpcblx0XHRcdCAqIElmIENodW5rcyBhcmUgZW5hYmxlZCB0aGUgZmlsZSBzaXplIHdpbGwgbmV2ZXIgZXhjZWVkIChieSBhIFBIUCBjb25zdHJhaW50KSB0aGVcblx0XHRcdCAqIHBvc3RNYXhTaXplLiBUaGlzIGJsb2NrIHNob3VsZG4ndCBiZSByZW1vdmVkIG5vbmV0aGVsZXNzIHVudGlsIHRoZSBcIm1vZGVyblwiIHVwbG9hZCBpcyBjb21wbGV0ZWx5XG5cdFx0XHQgKiBkZXByZWNhdGVkIGFuZCByZW1vdmVkLlxuXHRcdFx0ICovXG5cdFx0XHRpZiAoIGZpbGUuc2l6ZSA+IHRoaXMuZGF0YVRyYW5zZmVyLnBvc3RNYXhTaXplICkge1xuXHRcdFx0XHR4aHIuc2VuZCA9IGZ1bmN0aW9uKCkge307XG5cblx0XHRcdFx0ZmlsZS5hY2NlcHRlZCA9IGZhbHNlO1xuXHRcdFx0XHRmaWxlLnByb2Nlc3NpbmcgPSBmYWxzZTtcblx0XHRcdFx0ZmlsZS5zdGF0dXMgPSAncmVqZWN0ZWQnO1xuXHRcdFx0XHRmaWxlLnByZXZpZXdFbGVtZW50LmNsYXNzTGlzdC5hZGQoICdkei1lcnJvcicgKTtcblx0XHRcdFx0ZmlsZS5wcmV2aWV3RWxlbWVudC5jbGFzc0xpc3QuYWRkKCAnZHotY29tcGxldGUnICk7XG5cblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRPYmplY3Qua2V5cyggZGF0YSApLmZvckVhY2goIGZ1bmN0aW9uKCBrZXkgKSB7XG5cdFx0XHRcdGZvcm1EYXRhLmFwcGVuZCgga2V5LCBkYXRhW2tleV0gKTtcblx0XHRcdH0gKTtcblx0XHR9O1xuXHR9XG5cblx0LyoqXG5cdCAqIENvbnZlcnQgZmlsZXMgdG8gaW5wdXQgdmFsdWUuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKiBAc2luY2UgMS43LjEgQWRkZWQgdGhlIGR6IGFyZ3VtZW50LlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZmlsZXMgRmlsZXMgbGlzdC5cblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge3N0cmluZ30gQ29udmVydGVkIHZhbHVlLlxuXHQgKi9cblx0ZnVuY3Rpb24gY29udmVydEZpbGVzVG9WYWx1ZSggZmlsZXMsIGR6ICkge1xuXG5cdFx0aWYgKCAhIHN1Ym1pdHRlZFZhbHVlc1sgZHouZGF0YVRyYW5zZmVyLmZvcm1JZCBdIHx8ICEgc3VibWl0dGVkVmFsdWVzWyBkei5kYXRhVHJhbnNmZXIuZm9ybUlkIF1bIGR6LmRhdGFUcmFuc2Zlci5maWVsZElkIF0gKSB7XG5cdFx0XHRyZXR1cm4gZmlsZXMubGVuZ3RoID8gSlNPTi5zdHJpbmdpZnkoIGZpbGVzICkgOiAnJztcblx0XHR9XG5cblx0XHRmaWxlcy5wdXNoLmFwcGx5KCBmaWxlcywgc3VibWl0dGVkVmFsdWVzWyBkei5kYXRhVHJhbnNmZXIuZm9ybUlkIF1bIGR6LmRhdGFUcmFuc2Zlci5maWVsZElkIF0gKTtcblxuXHRcdHJldHVybiBKU09OLnN0cmluZ2lmeSggZmlsZXMgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBHZXQgaW5wdXQgZWxlbWVudC5cblx0ICpcblx0ICogQHNpbmNlIDEuNy4xXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtqUXVlcnl9IEhpZGRlbiBpbnB1dCBlbGVtZW50LlxuXHQgKi9cblx0ZnVuY3Rpb24gZ2V0SW5wdXQoIGR6ICkge1xuXG5cdFx0cmV0dXJuIGpRdWVyeSggZHouZWxlbWVudCApLnBhcmVudHMoICcud3Bmb3Jtcy1maWVsZC1maWxlLXVwbG9hZCcgKS5maW5kKCAnaW5wdXRbbmFtZT0nICsgZHouZGF0YVRyYW5zZmVyLm5hbWUgKyAnXScgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBVcGRhdGUgdmFsdWUgaW4gaW5wdXQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKi9cblx0ZnVuY3Rpb24gdXBkYXRlSW5wdXRWYWx1ZSggZHogKSB7XG5cblx0XHR2YXIgJGlucHV0ID0gZ2V0SW5wdXQoIGR6ICk7XG5cblx0XHQkaW5wdXQudmFsKCBjb252ZXJ0RmlsZXNUb1ZhbHVlKCBnZXRWYWx1ZSggZHouZmlsZXMgKSwgZHogKSApLnRyaWdnZXIoICdpbnB1dCcgKTtcblxuXHRcdGlmICggdHlwZW9mIGpRdWVyeS5mbi52YWxpZCAhPT0gJ3VuZGVmaW5lZCcgKSB7XG5cdFx0XHQkaW5wdXQudmFsaWQoKTtcblx0XHR9XG5cdH1cblxuXHQvKipcblx0ICogQ29tcGxldGUgZXZlbnQgaGlnaGVyIG9yZGVyIGZ1bmN0aW9uLlxuXHQgKlxuXHQgKiBAZGVwcmVjYXRlZCAxLjYuMlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge0Z1bmN0aW9ufSBIYW5kbGVyIGZ1bmN0aW9uLlxuXHQgKi9cblx0ZnVuY3Rpb24gY29tcGxldGUoIGR6ICkge1xuXG5cdFx0cmV0dXJuIGZ1bmN0aW9uKCkge1xuXHRcdFx0ZHoubG9hZGluZyA9IGR6LmxvYWRpbmcgfHwgMDtcblx0XHRcdGR6LmxvYWRpbmctLTtcblx0XHRcdGR6LmxvYWRpbmcgPSBNYXRoLm1heCggZHoubG9hZGluZyAtIDEsIDAgKTtcblx0XHRcdHRvZ2dsZVN1Ym1pdCggZHogKTtcblx0XHRcdHVwZGF0ZUlucHV0VmFsdWUoIGR6ICk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBBZGQgYW4gZXJyb3IgbWVzc2FnZSB0byB0aGUgY3VycmVudCBmaWxlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGZpbGUgICAgICAgICBGaWxlIG9iamVjdC5cblx0ICogQHBhcmFtIHtzdHJpbmd9IGVycm9yTWVzc2FnZSBFcnJvciBtZXNzYWdlXG5cdCAqL1xuXHRmdW5jdGlvbiBhZGRFcnJvck1lc3NhZ2UoIGZpbGUsIGVycm9yTWVzc2FnZSApIHtcblxuXHRcdGlmICggZmlsZS5pc0Vycm9yTm90VXBsb2FkZWREaXNwbGF5ZWQgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dmFyIHNwYW4gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCAnc3BhbicgKTtcblx0XHRzcGFuLmlubmVyVGV4dCA9IGVycm9yTWVzc2FnZS50b1N0cmluZygpO1xuXHRcdHNwYW4uc2V0QXR0cmlidXRlKCAnZGF0YS1kei1lcnJvcm1lc3NhZ2UnLCAnJyApO1xuXG5cdFx0ZmlsZS5wcmV2aWV3RWxlbWVudC5xdWVyeVNlbGVjdG9yKCAnLmR6LWVycm9yLW1lc3NhZ2UnICkuYXBwZW5kQ2hpbGQoIHNwYW4gKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBDb25maXJtIHRoZSB1cGxvYWQgdG8gdGhlIHNlcnZlci5cblx0ICpcblx0ICogVGhlIGNvbmZpcm1hdGlvbiBpcyBuZWVkZWQgaW4gb3JkZXIgdG8gbGV0IFBIUCBrbm93XG5cdCAqIHRoYXQgYWxsIHRoZSBjaHVua3MgaGF2ZSBiZWVuIHVwbG9hZGVkLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge0Z1bmN0aW9ufSBIYW5kbGVyIGZ1bmN0aW9uLlxuXHQgKi9cblx0ZnVuY3Rpb24gY29uZmlybUNodW5rc0ZpbmlzaFVwbG9hZCggZHogKSB7XG5cblx0XHRyZXR1cm4gZnVuY3Rpb24gY29uZmlybSggZmlsZSApIHtcblxuXHRcdFx0aWYgKCAhIGZpbGUucmV0cmllcyApIHtcblx0XHRcdFx0ZmlsZS5yZXRyaWVzID0gMDtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAnZXJyb3InID09PSBmaWxlLnN0YXR1cyApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQvKipcblx0XHRcdCAqIFJldHJ5IGZpbmFsaXplIGZ1bmN0aW9uLlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjYuMlxuXHRcdFx0ICovXG5cdFx0XHRmdW5jdGlvbiByZXRyeSgpIHtcblx0XHRcdFx0ZmlsZS5yZXRyaWVzKys7XG5cblx0XHRcdFx0aWYgKCBmaWxlLnJldHJpZXMgPT09IDMgKSB7XG5cdFx0XHRcdFx0YWRkRXJyb3JNZXNzYWdlKCBmaWxlLCB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZmlsZV9ub3RfdXBsb2FkZWQgKTtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRzZXRUaW1lb3V0KCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRjb25maXJtKCBmaWxlICk7XG5cdFx0XHRcdH0sIDUwMDAgKiBmaWxlLnJldHJpZXMgKTtcblx0XHRcdH1cblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBGYWlsIGhhbmRsZXIgZm9yIGFqYXggcmVxdWVzdC5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS42LjJcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gcmVzcG9uc2UgUmVzcG9uc2UgZnJvbSB0aGUgc2VydmVyXG5cdFx0XHQgKi9cblx0XHRcdGZ1bmN0aW9uIGZhaWwoIHJlc3BvbnNlICkge1xuXG5cdFx0XHRcdHZhciBoYXNTcGVjaWZpY0Vycm9yID1cdHJlc3BvbnNlLnJlc3BvbnNlSlNPTiAmJlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRyZXNwb25zZS5yZXNwb25zZUpTT04uc3VjY2VzcyA9PT0gZmFsc2UgJiZcblx0XHRcdFx0XHRcdFx0XHRcdFx0cmVzcG9uc2UucmVzcG9uc2VKU09OLmRhdGE7XG5cblx0XHRcdFx0aWYgKCBoYXNTcGVjaWZpY0Vycm9yICkge1xuXHRcdFx0XHRcdGFkZEVycm9yTWVzc2FnZSggZmlsZSwgcmVzcG9uc2UucmVzcG9uc2VKU09OLmRhdGEgKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRyZXRyeSgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogSGFuZGxlciBmb3IgYWpheCByZXF1ZXN0LlxuXHRcdFx0ICpcblx0XHRcdCAqIEBzaW5jZSAxLjYuMlxuXHRcdFx0ICpcblx0XHRcdCAqIEBwYXJhbSB7b2JqZWN0fSByZXNwb25zZSBSZXNwb25zZSBmcm9tIHRoZSBzZXJ2ZXJcblx0XHRcdCAqL1xuXHRcdFx0ZnVuY3Rpb24gY29tcGxldGUoIHJlc3BvbnNlICkge1xuXG5cdFx0XHRcdGZpbGUuY2h1bmtSZXNwb25zZSA9IEpTT04uc3RyaW5naWZ5KCB7IGRhdGE6IHJlc3BvbnNlIH0gKTtcblx0XHRcdFx0ZHoubG9hZGluZyA9IGR6LmxvYWRpbmcgfHwgMDtcblx0XHRcdFx0ZHoubG9hZGluZy0tO1xuXHRcdFx0XHRkei5sb2FkaW5nID0gTWF0aC5tYXgoIGR6LmxvYWRpbmcsIDAgKTtcblxuXHRcdFx0XHR0b2dnbGVTdWJtaXQoIGR6ICk7XG5cdFx0XHRcdHVwZGF0ZUlucHV0VmFsdWUoIGR6ICk7XG5cdFx0XHR9XG5cblx0XHRcdHdwLmFqYXgucG9zdCggalF1ZXJ5LmV4dGVuZChcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGFjdGlvbjogJ3dwZm9ybXNfZmlsZV9jaHVua3NfdXBsb2FkZWQnLFxuXHRcdFx0XHRcdGZvcm1faWQ6IGR6LmRhdGFUcmFuc2Zlci5mb3JtSWQsXG5cdFx0XHRcdFx0ZmllbGRfaWQ6IGR6LmRhdGFUcmFuc2Zlci5maWVsZElkLFxuXHRcdFx0XHRcdG5hbWU6IGZpbGUubmFtZSxcblx0XHRcdFx0fSxcblx0XHRcdFx0ZHoub3B0aW9ucy5wYXJhbXMuY2FsbCggZHosIG51bGwsIG51bGwsIHtmaWxlOiBmaWxlLCBpbmRleDogMH0gKVxuXHRcdFx0KSApLnRoZW4oIGNvbXBsZXRlICkuZmFpbCggZmFpbCApO1xuXG5cdFx0XHQvLyBNb3ZlIHRvIHVwbG9hZCB0aGUgbmV4dCBmaWxlLCBpZiBhbnkuXG5cdFx0XHRkei5wcm9jZXNzUXVldWUoKTtcblx0XHR9O1xuXHR9XG5cblx0LyoqXG5cdCAqIFRvZ2dsZSBzaG93aW5nIGVtcHR5IG1lc3NhZ2UuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKi9cblx0ZnVuY3Rpb24gdG9nZ2xlTWVzc2FnZSggZHogKSB7XG5cblx0XHRzZXRUaW1lb3V0KCBmdW5jdGlvbigpIHtcblx0XHRcdHZhciB2YWxpZEZpbGVzID0gZHouZmlsZXMuZmlsdGVyKCBmdW5jdGlvbiggZmlsZSApIHtcblx0XHRcdFx0cmV0dXJuIGZpbGUuYWNjZXB0ZWQ7XG5cdFx0XHR9ICk7XG5cblx0XHRcdGlmICggdmFsaWRGaWxlcy5sZW5ndGggPj0gZHoub3B0aW9ucy5tYXhGaWxlcyApIHtcblx0XHRcdFx0ZHouZWxlbWVudC5xdWVyeVNlbGVjdG9yKCAnLmR6LW1lc3NhZ2UnICkuY2xhc3NMaXN0LmFkZCggJ2hpZGUnICk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRkei5lbGVtZW50LnF1ZXJ5U2VsZWN0b3IoICcuZHotbWVzc2FnZScgKS5jbGFzc0xpc3QucmVtb3ZlKCAnaGlkZScgKTtcblx0XHRcdH1cblx0XHR9LCAwICk7XG5cdH1cblxuXHQvKipcblx0ICogVG9nZ2xlIGVycm9yIG1lc3NhZ2UgaWYgdG90YWwgc2l6ZSBtb3JlIHRoYW4gbGltaXQuXG5cdCAqIFJ1bnMgZm9yIGVhY2ggZmlsZS5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBmaWxlIEN1cnJlbnQgZmlsZS5cblx0ICogQHBhcmFtIHtvYmplY3R9IGR6ICAgRHJvcHpvbmUgb2JqZWN0LlxuXHQgKi9cblx0ZnVuY3Rpb24gdmFsaWRhdGVQb3N0TWF4U2l6ZUVycm9yKCBmaWxlLCBkeiApIHtcblxuXHRcdHNldFRpbWVvdXQoIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCBmaWxlLnNpemUgPj0gZHouZGF0YVRyYW5zZmVyLnBvc3RNYXhTaXplICkge1xuXHRcdFx0XHR2YXIgZXJyb3JNZXNzYWdlID0gd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQuZXJyb3JzLnBvc3RfbWF4X3NpemU7XG5cdFx0XHRcdGlmICggISBmaWxlLmlzRXJyb3JOb3RVcGxvYWRlZERpc3BsYXllZCApIHtcblx0XHRcdFx0XHRmaWxlLmlzRXJyb3JOb3RVcGxvYWRlZERpc3BsYXllZCA9IHRydWU7XG5cdFx0XHRcdFx0ZXJyb3JNZXNzYWdlID0gd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQuZXJyb3JzLmZpbGVfbm90X3VwbG9hZGVkICsgJyAnICsgZXJyb3JNZXNzYWdlO1xuXHRcdFx0XHRcdGFkZEVycm9yTWVzc2FnZSggZmlsZSwgZXJyb3JNZXNzYWdlICk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9LCAxICk7XG5cdH1cblxuXHQvKipcblx0ICogU3RhcnQgRmlsZSBVcGxvYWQuXG5cdCAqXG5cdCAqIFRoaXMgd291bGQgZG8gdGhlIGluaXRpYWwgcmVxdWVzdCB0byBzdGFydCBhIGZpbGUgdXBsb2FkLiBObyBjaHVua1xuXHQgKiBpcyB1cGxvYWRlZCBhdCB0aGlzIHN0YWdlLCBpbnN0ZWFkIGFsbCB0aGUgaW5mb3JtYXRpb24gcmVsYXRlZCB0byB0aGVcblx0ICogZmlsZSBhcmUgc2VuZCB0byB0aGUgc2VydmVyIHdhaXRpbmcgZm9yIGFuIGF1dGhvcml6YXRpb24uXG5cdCAqXG5cdCAqIElmIHRoZSBzZXJ2ZXIgYXV0aG9yaXplcyB0aGUgY2xpZW50IHdvdWxkIHN0YXJ0IHVwbG9hZGluZyB0aGUgY2h1bmtzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6ICAgRHJvcHpvbmUgb2JqZWN0LlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZmlsZSBDdXJyZW50IGZpbGUuXG5cdCAqL1xuXHRmdW5jdGlvbiBpbml0RmlsZVVwbG9hZCggZHosIGZpbGUgKSB7XG5cblx0XHR3cC5hamF4LnBvc3QoIGpRdWVyeS5leHRlbmQoXG5cdFx0XHR7XG5cdFx0XHRcdGFjdGlvbiA6ICd3cGZvcm1zX3VwbG9hZF9jaHVua19pbml0Jyxcblx0XHRcdFx0Zm9ybV9pZDogZHouZGF0YVRyYW5zZmVyLmZvcm1JZCxcblx0XHRcdFx0ZmllbGRfaWQ6IGR6LmRhdGFUcmFuc2Zlci5maWVsZElkLFxuXHRcdFx0XHRuYW1lOiBmaWxlLm5hbWUsXG5cdFx0XHRcdHNsb3c6IGlzU2xvdyxcblx0XHRcdH0sXG5cdFx0XHRkei5vcHRpb25zLnBhcmFtcy5jYWxsKCBkeiwgbnVsbCwgbnVsbCwge2ZpbGU6IGZpbGUsIGluZGV4OiAwfSApXG5cdFx0KSApLnRoZW4oIGZ1bmN0aW9uKCByZXNwb25zZSApIHtcblxuXHRcdFx0Ly8gRmlsZSB1cGxvYWQgaGFzIGJlZW4gYXV0aG9yaXplZC5cblxuXHRcdFx0Zm9yICggdmFyIGtleSBpbiByZXNwb25zZSApIHtcblx0XHRcdFx0ZHoub3B0aW9uc1sga2V5IF0gPSByZXNwb25zZVsga2V5IF07XG5cdFx0XHR9XG5cblx0XHRcdGlmICggcmVzcG9uc2UuZHpjaHVua3NpemUgKSB7XG5cdFx0XHRcdGR6Lm9wdGlvbnMuY2h1bmtTaXplID0gcGFyc2VJbnQoIHJlc3BvbnNlLmR6Y2h1bmtzaXplLCAxMCApO1xuXHRcdFx0XHRmaWxlLnVwbG9hZC50b3RhbENodW5rQ291bnQgPSBNYXRoLmNlaWwoIGZpbGUuc2l6ZSAvIGR6Lm9wdGlvbnMuY2h1bmtTaXplICk7XG5cdFx0XHR9XG5cblx0XHRcdGR6LnByb2Nlc3NRdWV1ZSgpO1xuXHRcdH0gKS5mYWlsKCBmdW5jdGlvbiggcmVzcG9uc2UgKSB7XG5cblx0XHRcdGZpbGUuc3RhdHVzID0gJ2Vycm9yJztcblxuXHRcdFx0aWYgKCAhIGZpbGUueGhyICkge1xuXHRcdFx0XHRjb25zdCBmaWVsZCA9IGR6LmVsZW1lbnQuY2xvc2VzdCggJy53cGZvcm1zLWZpZWxkJyApO1xuXHRcdFx0XHRjb25zdCBoaWRkZW5JbnB1dCA9IGZpZWxkLnF1ZXJ5U2VsZWN0b3IoICcuZHJvcHpvbmUtaW5wdXQnICk7XG5cdFx0XHRcdGNvbnN0IGVycm9yTWVzc2FnZSA9IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5maWxlX25vdF91cGxvYWRlZCArICcgJyArIHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5kZWZhdWx0X2Vycm9yO1xuXG5cdFx0XHRcdGZpbGUucHJldmlld0VsZW1lbnQuY2xhc3NMaXN0LmFkZCggJ2R6LXByb2Nlc3NpbmcnLCAnZHotZXJyb3InLCAnZHotY29tcGxldGUnICk7XG5cdFx0XHRcdGhpZGRlbklucHV0LmNsYXNzTGlzdC5hZGQoICd3cGZvcm1zLWVycm9yJyApO1xuXHRcdFx0XHRmaWVsZC5jbGFzc0xpc3QuYWRkKCAnd3Bmb3Jtcy1oYXMtZXJyb3InICk7XG5cdFx0XHRcdGFkZEVycm9yTWVzc2FnZSggZmlsZSwgZXJyb3JNZXNzYWdlICk7XG5cdFx0XHR9XG5cblx0XHRcdGR6LnByb2Nlc3NRdWV1ZSgpO1xuXHRcdH0gKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBWYWxpZGF0ZSB0aGUgZmlsZSB3aGVuIGl0IHdhcyBhZGRlZCBpbiB0aGUgZHJvcHpvbmUuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7RnVuY3Rpb259IEhhbmRsZXIgZnVuY3Rpb24uXG5cdCAqL1xuXHRmdW5jdGlvbiBhZGRlZEZpbGUoIGR6ICkge1xuXG5cdFx0cmV0dXJuIGZ1bmN0aW9uKCBmaWxlICkge1xuXG5cdFx0XHRpZiAoIGZpbGUuc2l6ZSA+PSBkei5kYXRhVHJhbnNmZXIucG9zdE1heFNpemUgKSB7XG5cdFx0XHRcdHZhbGlkYXRlUG9zdE1heFNpemVFcnJvciggZmlsZSwgZHogKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHNwZWVkVGVzdCggZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0aW5pdEZpbGVVcGxvYWQoIGR6LCBmaWxlICk7XG5cdFx0XHRcdH0gKTtcblx0XHRcdH1cblxuXHRcdFx0ZHoubG9hZGluZyA9IGR6LmxvYWRpbmcgfHwgMDtcblx0XHRcdGR6LmxvYWRpbmcrKztcblx0XHRcdHRvZ2dsZVN1Ym1pdCggZHogKTtcblxuXHRcdFx0dG9nZ2xlTWVzc2FnZSggZHogKTtcblx0XHR9O1xuXHR9XG5cblx0LyoqXG5cdCAqIFNlbmQgYW4gQUpBWCByZXF1ZXN0IHRvIHJlbW92ZSBmaWxlIGZyb20gdGhlIHNlcnZlci5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBmaWxlIEZpbGUgbmFtZS5cblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIHJlbW92ZUZyb21TZXJ2ZXIoIGZpbGUsIGR6ICkge1xuXG5cdFx0d3AuYWpheC5wb3N0KCB7XG5cdFx0XHRhY3Rpb246ICd3cGZvcm1zX3JlbW92ZV9maWxlJyxcblx0XHRcdGZpbGU6IGZpbGUsXG5cdFx0XHRmb3JtX2lkOiBkei5kYXRhVHJhbnNmZXIuZm9ybUlkLFxuXHRcdFx0ZmllbGRfaWQ6IGR6LmRhdGFUcmFuc2Zlci5maWVsZElkLFxuXHRcdH0gKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBJbml0IHRoZSBmaWxlIHJlbW92YWwgb24gc2VydmVyIHdoZW4gdXNlciByZW1vdmVkIGl0IG9uIGZyb250LWVuZC5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtGdW5jdGlvbn0gSGFuZGxlciBmdW5jdGlvbi5cblx0ICovXG5cdGZ1bmN0aW9uIHJlbW92ZWRGaWxlKCBkeiApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbiggZmlsZSApIHtcblx0XHRcdHRvZ2dsZU1lc3NhZ2UoIGR6ICk7XG5cblx0XHRcdHZhciBqc29uID0gZmlsZS5jaHVua1Jlc3BvbnNlIHx8ICggZmlsZS54aHIgfHwge30gKS5yZXNwb25zZVRleHQ7XG5cblx0XHRcdGlmICgganNvbiApIHtcblx0XHRcdFx0dmFyIG9iamVjdCA9IHBhcnNlSlNPTigganNvbiApO1xuXG5cdFx0XHRcdGlmICggb2JqZWN0ICYmIG9iamVjdC5kYXRhICYmIG9iamVjdC5kYXRhLmZpbGUgKSB7XG5cdFx0XHRcdFx0cmVtb3ZlRnJvbVNlcnZlciggb2JqZWN0LmRhdGEuZmlsZSwgZHogKTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXG5cdFx0XHQvLyBSZW1vdmUgc3VibWl0dGVkIHZhbHVlLlxuXHRcdFx0aWYgKCBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwoIGZpbGUsICdpc0RlZmF1bHQnICkgJiYgZmlsZS5pc0RlZmF1bHQgKSB7XG5cdFx0XHRcdHN1Ym1pdHRlZFZhbHVlc1sgZHouZGF0YVRyYW5zZmVyLmZvcm1JZCBdWyBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCBdLnNwbGljZSggZmlsZS5pbmRleCwgMSApO1xuXHRcdFx0XHRkei5vcHRpb25zLm1heEZpbGVzKys7XG5cdFx0XHRcdHJlbW92ZUZyb21TZXJ2ZXIoIGZpbGUuZmlsZSwgZHogKTtcblx0XHRcdH1cblxuXHRcdFx0dXBkYXRlSW5wdXRWYWx1ZSggZHogKTtcblxuXHRcdFx0ZHoubG9hZGluZyA9IGR6LmxvYWRpbmcgfHwgMDtcblx0XHRcdGR6LmxvYWRpbmctLTtcblx0XHRcdGR6LmxvYWRpbmcgPSBNYXRoLm1heCggZHoubG9hZGluZywgMCApO1xuXG5cdFx0XHR0b2dnbGVTdWJtaXQoIGR6ICk7XG5cblx0XHRcdGNvbnN0IG51bUVycm9ycyA9IGR6LmVsZW1lbnQucXVlcnlTZWxlY3RvckFsbCggJy5kei1wcmV2aWV3LmR6LWVycm9yJyApLmxlbmd0aDtcblxuXHRcdFx0aWYgKCBudW1FcnJvcnMgPT09IDAgKSB7XG5cdFx0XHRcdGR6LmVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSggJ3dwZm9ybXMtZXJyb3InICk7XG5cdFx0XHRcdGR6LmVsZW1lbnQuY2xvc2VzdCggJy53cGZvcm1zLWZpZWxkJyApLmNsYXNzTGlzdC5yZW1vdmUoICd3cGZvcm1zLWhhcy1lcnJvcicgKTtcblx0XHRcdH1cblx0XHR9O1xuXHR9XG5cblx0LyoqXG5cdCAqIFByb2Nlc3MgYW55IGVycm9yIHRoYXQgd2FzIGZpcmVkIHBlciBlYWNoIGZpbGUuXG5cdCAqIFRoZXJlIG1pZ2h0IGJlIHNldmVyYWwgZXJyb3JzIHBlciBmaWxlLCBpbiB0aGF0IGNhc2UgLSBkaXNwbGF5IFwibm90IHVwbG9hZGVkXCIgdGV4dCBvbmx5IG9uY2UuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNi4xXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtGdW5jdGlvbn0gSGFuZGxlciBmdW5jdGlvbi5cblx0ICovXG5cdGZ1bmN0aW9uIGVycm9yKCBkeiApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbiggZmlsZSwgZXJyb3JNZXNzYWdlICkge1xuXG5cdFx0XHRpZiAoIGZpbGUuaXNFcnJvck5vdFVwbG9hZGVkRGlzcGxheWVkICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGlmICggdHlwZW9mIGVycm9yTWVzc2FnZSA9PT0gJ29iamVjdCcgKSB7XG5cdFx0XHRcdGVycm9yTWVzc2FnZSA9IE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbCggZXJyb3JNZXNzYWdlLCAnZGF0YScgKSAmJiB0eXBlb2YgZXJyb3JNZXNzYWdlLmRhdGEgPT09ICdzdHJpbmcnID8gZXJyb3JNZXNzYWdlLmRhdGEgOiAnJztcblx0XHRcdH1cblxuXHRcdFx0ZXJyb3JNZXNzYWdlID0gZXJyb3JNZXNzYWdlICE9PSAnMCcgPyBlcnJvck1lc3NhZ2UgOiAnJztcblxuXHRcdFx0ZmlsZS5pc0Vycm9yTm90VXBsb2FkZWREaXNwbGF5ZWQgPSB0cnVlO1xuXHRcdFx0ZmlsZS5wcmV2aWV3RWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsKCAnW2RhdGEtZHotZXJyb3JtZXNzYWdlXScgKVswXS50ZXh0Q29udGVudCA9IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5maWxlX25vdF91cGxvYWRlZCArICcgJyArIGVycm9yTWVzc2FnZTtcblx0XHRcdGR6LmVsZW1lbnQuY2xhc3NMaXN0LmFkZCggJ3dwZm9ybXMtZXJyb3InICk7XG5cdFx0XHRkei5lbGVtZW50LmNsb3Nlc3QoICcud3Bmb3Jtcy1maWVsZCcgKS5jbGFzc0xpc3QuYWRkKCAnd3Bmb3Jtcy1oYXMtZXJyb3InICk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBQcmVzZXQgcHJldmlvdXNseSBzdWJtaXR0ZWQgZmlsZXMgdG8gdGhlIGRyb3B6b25lLlxuXHQgKlxuXHQgKiBAc2luY2UgMS43LjFcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIHByZXNldFN1Ym1pdHRlZERhdGEoIGR6ICkge1xuXG5cdFx0dmFyIGZpbGVzID0gcGFyc2VKU09OKCBnZXRJbnB1dCggZHogKS52YWwoKSApO1xuXG5cdFx0aWYgKCAhIGZpbGVzIHx8ICEgZmlsZXMubGVuZ3RoICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHN1Ym1pdHRlZFZhbHVlc1tkei5kYXRhVHJhbnNmZXIuZm9ybUlkXSA9IFtdO1xuXG5cdFx0Ly8gV2UgZG8gZGVlcCBjbG9uaW5nIGFuIG9iamVjdCB0byBiZSBzdXJlIHRoYXQgZGF0YSBpcyBwYXNzZWQgd2l0aG91dCBsaW5rcy5cblx0XHRzdWJtaXR0ZWRWYWx1ZXNbZHouZGF0YVRyYW5zZmVyLmZvcm1JZF1bZHouZGF0YVRyYW5zZmVyLmZpZWxkSWRdID0gSlNPTi5wYXJzZSggSlNPTi5zdHJpbmdpZnkoIGZpbGVzICkgKTtcblxuXHRcdGZpbGVzLmZvckVhY2goIGZ1bmN0aW9uKCBmaWxlLCBpbmRleCApIHtcblxuXHRcdFx0ZmlsZS5pc0RlZmF1bHQgPSB0cnVlO1xuXHRcdFx0ZmlsZS5pbmRleCA9IGluZGV4O1xuXG5cdFx0XHRpZiAoIGZpbGUudHlwZS5tYXRjaCggL2ltYWdlLiovICkgKSB7XG5cdFx0XHRcdGR6LmRpc3BsYXlFeGlzdGluZ0ZpbGUoIGZpbGUsIGZpbGUudXJsICk7XG5cblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRkei5lbWl0KCAnYWRkZWRmaWxlJywgZmlsZSApO1xuXHRcdFx0ZHouZW1pdCggJ2NvbXBsZXRlJywgZmlsZSApO1xuXHRcdH0gKTtcblxuXHRcdGR6Lm9wdGlvbnMubWF4RmlsZXMgPSBkei5vcHRpb25zLm1heEZpbGVzIC0gZmlsZXMubGVuZ3RoO1xuXHR9XG5cblx0LyoqXG5cdCAqIERyb3B6b25lLmpzIGluaXQgZm9yIGVhY2ggZmllbGQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gJGVsIFdQRm9ybXMgdXBsb2FkZXIgRE9NIGVsZW1lbnQuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtvYmplY3R9IERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIGRyb3Bab25lSW5pdCggJGVsICkge1xuXG5cdFx0aWYgKCAkZWwuZHJvcHpvbmUgKSB7XG5cdFx0XHRyZXR1cm4gJGVsLmRyb3B6b25lO1xuXHRcdH1cblxuXHRcdHZhciBmb3JtSWQgPSBwYXJzZUludCggJGVsLmRhdGFzZXQuZm9ybUlkLCAxMCApO1xuXHRcdHZhciBmaWVsZElkID0gcGFyc2VJbnQoICRlbC5kYXRhc2V0LmZpZWxkSWQsIDEwICkgfHwgMDtcblx0XHR2YXIgbWF4RmlsZXMgPSBwYXJzZUludCggJGVsLmRhdGFzZXQubWF4RmlsZU51bWJlciwgMTAgKTtcblxuXHRcdHZhciBhY2NlcHRlZEZpbGVzID0gJGVsLmRhdGFzZXQuZXh0ZW5zaW9ucy5zcGxpdCggJywnICkubWFwKCBmdW5jdGlvbiggZWwgKSB7XG5cdFx0XHRyZXR1cm4gJy4nICsgZWw7XG5cdFx0fSApLmpvaW4oICcsJyApO1xuXG5cdFx0Ly8gQ29uZmlndXJlIGFuZCBtb2RpZnkgRHJvcHpvbmUgbGlicmFyeS5cblx0XHR2YXIgZHogPSBuZXcgd2luZG93LkRyb3B6b25lKCAkZWwsIHtcblx0XHRcdHVybDogd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQudXJsLFxuXHRcdFx0YWRkUmVtb3ZlTGlua3M6IHRydWUsXG5cdFx0XHRjaHVua2luZzogdHJ1ZSxcblx0XHRcdGZvcmNlQ2h1bmtpbmc6IHRydWUsXG5cdFx0XHRyZXRyeUNodW5rczogdHJ1ZSxcblx0XHRcdGNodW5rU2l6ZTogcGFyc2VJbnQoICRlbC5kYXRhc2V0LmZpbGVDaHVua1NpemUsIDEwICksXG5cdFx0XHRwYXJhbU5hbWU6ICRlbC5kYXRhc2V0LmlucHV0TmFtZSxcblx0XHRcdHBhcmFsbGVsQ2h1bmtVcGxvYWRzOiAhISAoICRlbC5kYXRhc2V0LnBhcmFsbGVsVXBsb2FkcyB8fCAnJyApLm1hdGNoKCAvXnRydWUkL2kgKSxcblx0XHRcdHBhcmFsbGVsVXBsb2FkczogcGFyc2VJbnQoICRlbC5kYXRhc2V0Lm1heFBhcmFsbGVsVXBsb2FkcywgMTAgKSxcblx0XHRcdGF1dG9Qcm9jZXNzUXVldWU6IGZhbHNlLFxuXHRcdFx0bWF4RmlsZXNpemU6ICggcGFyc2VJbnQoICRlbC5kYXRhc2V0Lm1heFNpemUsIDEwICkgLyAoIDEwMjQgKiAxMDI0ICkgKS50b0ZpeGVkKCAyICksXG5cdFx0XHRtYXhGaWxlczogbWF4RmlsZXMsXG5cdFx0XHRhY2NlcHRlZEZpbGVzOiBhY2NlcHRlZEZpbGVzLFxuXHRcdFx0ZGljdE1heEZpbGVzRXhjZWVkZWQ6IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5maWxlX2xpbWl0LnJlcGxhY2UoICd7ZmlsZUxpbWl0fScsIG1heEZpbGVzICksXG5cdFx0XHRkaWN0SW52YWxpZEZpbGVUeXBlOiB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZmlsZV9leHRlbnNpb24sXG5cdFx0XHRkaWN0RmlsZVRvb0JpZzogd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQuZXJyb3JzLmZpbGVfc2l6ZSxcblx0XHR9ICk7XG5cblx0XHQvLyBDdXN0b20gdmFyaWFibGVzLlxuXHRcdGR6LmRhdGFUcmFuc2ZlciA9IHtcblx0XHRcdHBvc3RNYXhTaXplOiAkZWwuZGF0YXNldC5tYXhTaXplLFxuXHRcdFx0bmFtZTogJGVsLmRhdGFzZXQuaW5wdXROYW1lLFxuXHRcdFx0Zm9ybUlkOiBmb3JtSWQsXG5cdFx0XHRmaWVsZElkOiBmaWVsZElkLFxuXHRcdH07XG5cblx0XHRwcmVzZXRTdWJtaXR0ZWREYXRhKCBkeiApO1xuXG5cdFx0Ly8gUHJvY2VzcyBldmVudHMuXG5cdFx0ZHoub24oICdzZW5kaW5nJywgc2VuZGluZyggZHosIHtcblx0XHRcdGFjdGlvbjogJ3dwZm9ybXNfdXBsb2FkX2NodW5rJyxcblx0XHRcdGZvcm1faWQ6IGZvcm1JZCxcblx0XHRcdGZpZWxkX2lkOiBmaWVsZElkLFxuXHRcdH0gKSApO1xuXHRcdGR6Lm9uKCAnYWRkZWRmaWxlJywgYWRkZWRGaWxlKCBkeiApICk7XG5cdFx0ZHoub24oICdyZW1vdmVkZmlsZScsIHJlbW92ZWRGaWxlKCBkeiApICk7XG5cdFx0ZHoub24oICdjb21wbGV0ZScsIGNvbmZpcm1DaHVua3NGaW5pc2hVcGxvYWQoIGR6ICkgKTtcblx0XHRkei5vbiggJ2Vycm9yJywgZXJyb3IoIGR6ICkgKTtcblxuXHRcdHJldHVybiBkejtcblx0fVxuXG5cdC8qKlxuXHQgKiBIaWRkZW4gRHJvcHpvbmUgaW5wdXQgZm9jdXMgZXZlbnQgaGFuZGxlci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqL1xuXHRmdW5jdGlvbiBkcm9wem9uZUlucHV0Rm9jdXMoKSB7XG5cblx0XHQkKCB0aGlzICkucHJldiggJy53cGZvcm1zLXVwbG9hZGVyJyApLmFkZENsYXNzKCAnd3Bmb3Jtcy1mb2N1cycgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBIaWRkZW4gRHJvcHpvbmUgaW5wdXQgYmx1ciBldmVudCBoYW5kbGVyLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICovXG5cdGZ1bmN0aW9uIGRyb3B6b25lSW5wdXRCbHVyKCkge1xuXG5cdFx0JCggdGhpcyApLnByZXYoICcud3Bmb3Jtcy11cGxvYWRlcicgKS5yZW1vdmVDbGFzcyggJ3dwZm9ybXMtZm9jdXMnICk7XG5cdH1cblxuXHQvKipcblx0ICogSGlkZGVuIERyb3B6b25lIGlucHV0IGJsdXIgZXZlbnQgaGFuZGxlci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlIEV2ZW50IG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIGRyb3B6b25lSW5wdXRLZXlwcmVzcyggZSApIHtcblxuXHRcdGUucHJldmVudERlZmF1bHQoKTtcblxuXHRcdGlmICggZS5rZXlDb2RlICE9PSAxMyApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHQkKCB0aGlzICkucHJldiggJy53cGZvcm1zLXVwbG9hZGVyJyApLnRyaWdnZXIoICdjbGljaycgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBIaWRkZW4gRHJvcHpvbmUgaW5wdXQgYmx1ciBldmVudCBoYW5kbGVyLlxuXHQgKlxuXHQgKiBAc2luY2UgMS44LjFcblx0ICovXG5cdGZ1bmN0aW9uIGRyb3B6b25lQ2xpY2soKSB7XG5cblx0XHQkKCB0aGlzICkubmV4dCggJy5kcm9wem9uZS1pbnB1dCcgKS50cmlnZ2VyKCAnZm9jdXMnICk7XG5cdH1cblxuXHQvKipcblx0ICogQ2xhc3NpYyBGaWxlIHVwbG9hZCBzdWNjZXNzIGNhbGxiYWNrIHRvIGRldGVybWluZSBpZiBhbGwgZmlsZXMgYXJlIHVwbG9hZGVkLlxuXHQgKlxuXHQgKiBAc2luY2Uge1ZFUlNJT059XG5cdCAqXG5cdCAqIEBwYXJhbSB7RXZlbnR9IGUgRXZlbnQuXG5cdCAqIEBwYXJhbSB7alF1ZXJ5fSAkZm9ybSBGb3JtLlxuXHQgKi9cblx0ZnVuY3Rpb24gY29tYmluZWRVcGxvYWRzU2l6ZU9rKCBlLCAkZm9ybSApIHtcblxuXHRcdGlmICggYW55VXBsb2Fkc0luUHJvZ3Jlc3MoKSApIHtcblx0XHRcdGRpc2FibGVTdWJtaXRCdXR0b24oICRmb3JtICk7XG5cdFx0fVxuXHR9XG5cblx0LyoqXG5cdCAqIEV2ZW50cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqL1xuXHRmdW5jdGlvbiBldmVudHMoKSB7XG5cblx0XHQkKCAnLmRyb3B6b25lLWlucHV0JyApXG5cdFx0XHQub24oICdmb2N1cycsIGRyb3B6b25lSW5wdXRGb2N1cyApXG5cdFx0XHQub24oICdibHVyJywgZHJvcHpvbmVJbnB1dEJsdXIgKVxuXHRcdFx0Lm9uKCAna2V5cHJlc3MnLCBkcm9wem9uZUlucHV0S2V5cHJlc3MgKTtcblxuXHRcdCQoICcud3Bmb3Jtcy11cGxvYWRlcicgKVxuXHRcdFx0Lm9uKCAnY2xpY2snLCBkcm9wem9uZUNsaWNrICk7XG5cblx0XHQkKCAnZm9ybS53cGZvcm1zLWZvcm0nIClcblx0XHRcdC5vbiggJ3dwZm9ybXNDb21iaW5lZFVwbG9hZHNTaXplT2snLCBjb21iaW5lZFVwbG9hZHNTaXplT2sgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBET01Db250ZW50TG9hZGVkIGhhbmRsZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKi9cblx0ZnVuY3Rpb24gcmVhZHkoKSB7XG5cblx0XHR3aW5kb3cud3Bmb3JtcyA9IHdpbmRvdy53cGZvcm1zIHx8IHt9O1xuXHRcdHdpbmRvdy53cGZvcm1zLmRyb3B6b25lcyA9IFtdLnNsaWNlLmNhbGwoIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICcud3Bmb3Jtcy11cGxvYWRlcicgKSApLm1hcCggZHJvcFpvbmVJbml0ICk7XG5cblx0XHRldmVudHMoKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBNb2Rlcm4gRmlsZSBVcGxvYWQgZW5naW5lLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjBcblx0ICovXG5cdHZhciB3cGZvcm1zTW9kZXJuRmlsZVVwbG9hZCA9IHtcblxuXHRcdC8qKlxuXHRcdCAqIFN0YXJ0IHRoZSBpbml0aWFsaXphdGlvbi5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjYuMFxuXHRcdCAqL1xuXHRcdGluaXQ6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRpZiAoIGRvY3VtZW50LnJlYWR5U3RhdGUgPT09ICdsb2FkaW5nJyApIHtcblx0XHRcdFx0ZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lciggJ0RPTUNvbnRlbnRMb2FkZWQnLCByZWFkeSApO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0cmVhZHkoKTtcblx0XHRcdH1cblx0XHR9LFxuXHR9O1xuXG5cdC8vIENhbGwgaW5pdCBhbmQgc2F2ZSBpbiBnbG9iYWwgdmFyaWFibGUuXG5cdHdwZm9ybXNNb2Rlcm5GaWxlVXBsb2FkLmluaXQoKTtcblx0d2luZG93LndwZm9ybXNNb2Rlcm5GaWxlVXBsb2FkID0gd3Bmb3Jtc01vZGVybkZpbGVVcGxvYWQ7XG5cbn0oIGpRdWVyeSApICk7XG4iXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0EsWUFBWTs7QUFBQyxTQUFBQSxRQUFBQyxHQUFBLHNDQUFBRCxPQUFBLHdCQUFBRSxNQUFBLHVCQUFBQSxNQUFBLENBQUFDLFFBQUEsYUFBQUYsR0FBQSxrQkFBQUEsR0FBQSxnQkFBQUEsR0FBQSxXQUFBQSxHQUFBLHlCQUFB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
},{}]},{},[1])