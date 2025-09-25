/* global wpforms_camera_builder, wpf */

/**
 * @param wpforms_camera_builder.button_link_text_label
 * @param wpforms_camera_builder.button_link_text_tooltip
 * @param wpforms_camera_builder.error_ok
 * @param wpforms_camera_builder.error_title
 * @param wpforms_camera_builder.link_text_label
 * @param wpforms_camera_builder.link_text_tooltip
 */

// noinspection ES6ConvertVarToLetConst
/**
 * Form Builder Field File Upload Camera module.
 *
 * @since 1.9.8
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldFileUploadCamera = WPForms.Admin.Builder.FieldFileUploadCamera || ( function( document, window, $ ) {
	/**
	 * Main application object.
	 *
	 * @since 1.9.8
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.8
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.9.8
		 */
		ready() {
			app.bindEvents();
			app.initializeAspectRatioOptions();
		},

		/**
		 * Add handlers on events.
		 *
		 * @since 1.9.8
		 */
		bindEvents() {
			$( document ).on( 'change', '.wpforms-file-upload-camera-enabled-toggle', app.toggleCameraOptions );
			$( document ).on( 'change', '.wpforms-file-upload-camera-format-select', app.toggleTimeLimit );
			$( document ).on( 'change', '.wpforms-file-upload-camera-aspect-ratio-select', app.toggleCustomRatio );
			$( document ).on( 'change', '.wpforms-camera-style select', app.updateCameraPreview );
			$( document ).on( 'input', '.wpforms-field-option-row-button_link_text input', app.updateCameraButtonLinkText );
			$( document ).on( 'blur', '.wpforms-file-upload-camera-time-limit-minutes-input', app.validateTimeLimitMinutes );
			$( document ).on( 'blur', '.wpforms-file-upload-camera-time-limit-seconds-input', app.validateTimeLimitSeconds );
			$( document ).on( 'blur', '.wpforms-file-upload-camera-ratio-width-input', app.validateRatioWidth );
			$( document ).on( 'blur', '.wpforms-file-upload-camera-ratio-height-input', app.validateRatioHeight );
			$( document ).on( 'wpformsBeforeSave', app.validateCameraFields );
		},

		/**
		 * Initialize aspect ratio options by hiding the freeform option where not allowed.
		 *
		 * @since 1.9.8
		 */
		initializeAspectRatioOptions() {
			$( '.wpforms-file-upload-camera-aspect-ratio-no-freeform' ).each( function() {
				$( this ).find( 'option[value="freeform"]' ).hide();
			} );
		},

		/**
		 * Toggle camera options visibility based on the camera-enabled state.
		 *
		 * @since 1.9.8
		 */
		toggleCameraOptions() {
			const $toggle = $( this );
			const $field = $toggle.closest( '.wpforms-field-option' );
			const isEnabled = $toggle.is( ':checked' );

			// Get all camera option elements.
			const $cameraFormat = $field.find( '.wpforms-file-upload-camera-format' );
			const $cameraAspectRatio = $field.find( '.wpforms-file-upload-camera-aspect-ratio' );
			const $cameraTimeLimit = $field.find( '.wpforms-file-upload-camera-time-limit' );

			if ( isEnabled ) {
				// Show camera options.
				$cameraFormat.removeClass( 'wpforms-hidden' );
				$cameraAspectRatio.removeClass( 'wpforms-hidden' );

				// Check if a format is video to show a time limit.
				app.toggleTimeLimit.call( $field.find( '.wpforms-file-upload-camera-format-select' )[ 0 ] );
			} else {
				// Hide camera options.
				$cameraFormat.addClass( 'wpforms-hidden' );
				$cameraAspectRatio.addClass( 'wpforms-hidden' );
				$cameraTimeLimit.addClass( 'wpforms-hidden' );
			}
		},

		/**
		 * Toggle time limit option visibility based on the selected format.
		 *
		 * @since 1.9.8
		 */
		toggleTimeLimit() {
			const $formatSelect = $( this );
			const $field = $formatSelect.closest( '.wpforms-field-option' );
			const $timeLimit = $field.find( '.wpforms-file-upload-camera-time-limit' );
			const $aspectRatioSelect = $field.find( '.wpforms-file-upload-camera-aspect-ratio-select' );
			const selectedFormat = $formatSelect.val();

			if ( selectedFormat === 'video' ) {
				$timeLimit.removeClass( 'wpforms-hidden' );
				// Hide the freeform option for video.
				$aspectRatioSelect.addClass( 'wpforms-file-upload-camera-aspect-ratio-no-freeform' );
				$aspectRatioSelect.find( 'option[value="freeform"]' ).hide();

				// If a freeform was selected, change to the first available option.
				if ( $aspectRatioSelect.val() === 'freeform' ) {
					$aspectRatioSelect.val( 'original' );
				}
			} else {
				$timeLimit.addClass( 'wpforms-hidden' );
				// Show freeform option for a photo.
				$aspectRatioSelect.removeClass( 'wpforms-file-upload-camera-aspect-ratio-no-freeform' );
				$aspectRatioSelect.find( 'option[value="freeform"]' ).show();
			}
		},

		/**
		 * Toggle custom ratio input visibility based on a selected aspect ratio.
		 *
		 * @since 1.9.8
		 */
		toggleCustomRatio() {
			const $aspectRatioSelect = $( this );
			const $field = $aspectRatioSelect.closest( '.wpforms-field-option' );
			const $customRatio = $field.find( '.wpforms-file-upload-camera-custom-ratio' );
			const selectedAspectRatio = $aspectRatioSelect.val();
			const previousValue = $aspectRatioSelect.data( 'previous-value' );

			if ( selectedAspectRatio === 'custom' ) {
				$customRatio.removeClass( 'wpforms-hidden' );

				// Autofill ratio fields if the previous value was a predefined ratio.
				if ( previousValue && previousValue.includes( ':' ) ) {
					const [ width, height ] = previousValue.split( ':' );
					const $ratioWidth = $field.find( '.wpforms-file-upload-camera-ratio-width-input' );
					const $ratioHeight = $field.find( '.wpforms-file-upload-camera-ratio-height-input' );

					$ratioWidth.val( width );
					$ratioHeight.val( height );
				}
			} else {
				$customRatio.addClass( 'wpforms-hidden' );
			}

			// Store current value for next change.
			$aspectRatioSelect.data( 'previous-value', selectedAspectRatio );
		},

		/**
		 * Validate time limit minutes input.
		 *
		 * @since 1.9.8
		 */
		validateTimeLimitMinutes() {
			const $input = $( this );
			const $field = $input.closest( '.wpforms-field-option' );
			const $secondsInput = $field.find( '.wpforms-file-upload-camera-time-limit-seconds-input' );
			let value = parseInt( $input.val(), 10 );
			const secondsValue = parseInt( $secondsInput.val(), 10 );

			// Ensure the value is a number.
			if ( isNaN( value ) ) {
				value = 0;
			}

			// Clamp value to minimum 0.
			if ( value < 0 ) {
				value = 0;
			}

			$input.val( value );

			// Ensure minimum 1 second total.
			if ( value === 0 && ( isNaN( secondsValue ) || secondsValue === 0 ) ) {
				$secondsInput.val( 1 );
			}
		},

		/**
		 * Validate time limit seconds input.
		 *
		 * @since 1.9.8
		 */
		validateTimeLimitSeconds() {
			const $input = $( this );
			const $field = $input.closest( '.wpforms-field-option' );
			const $minutesInput = $field.find( '.wpforms-file-upload-camera-time-limit-minutes-input' );
			let value = parseInt( $input.val(), 10 );
			const minutesValue = parseInt( $minutesInput.val(), 10 );

			// Ensure the value is a number.
			if ( isNaN( value ) ) {
				value = 0;
			}

			// Clamp value between 0 and 59.
			if ( value < 0 ) {
				value = 0;
			} else if ( value > 59 ) {
				value = 59;
			}

			$input.val( value );

			// Ensure minimum 1 second total.
			if ( value === 0 && ( isNaN( minutesValue ) || minutesValue === 0 ) ) {
				$input.val( 1 );
			}
		},

		/**
		 * Validate ratio width input.
		 *
		 * @since 1.9.8
		 */
		validateRatioWidth() {
			const $input = $( this );
			let value = parseInt( $input.val(), 10 );

			// Ensure the value is a number.
			if ( isNaN( value ) ) {
				value = 1;
			}

			// Clamp value to minimum 1.
			if ( value < 1 ) {
				value = 1;
			}

			$input.val( value );
		},

		/**
		 * Validate ratio height input.
		 *
		 * @since 1.9.8
		 */
		validateRatioHeight() {
			const $input = $( this );
			let value = parseInt( $input.val(), 10 );

			// Ensure the value is a number.
			if ( isNaN( value ) ) {
				value = 1;
			}

			// Clamp value to minimum 1.
			if ( value < 1 ) {
				value = 1;
			}

			$input.val( value );
		},

		/**
		 * Update camera field preview when style changes.
		 *
		 * @since 1.9.8
		 */
		updateCameraPreview() {
			const $select = $( this );
			const $field = $select.closest( '.wpforms-field-option' );
			const fieldId = $field.data( 'field-id' );
			const selectedStyle = $select.val();
			const $preview = $( '#wpforms-field-' + fieldId );
			const $buttonPreview = $preview.find( '.wpforms-camera-button' );
			const $linkPreview = $preview.find( '.wpforms-camera-link' );

			// Update preview visibility.
			if ( selectedStyle === 'button' ) {
				// Show button, hide link.
				$buttonPreview.removeClass( 'wpforms-hidden' );
				$linkPreview.addClass( 'wpforms-hidden' );
			} else {
				// Show link, hide button.
				$buttonPreview.addClass( 'wpforms-hidden' );
				$linkPreview.removeClass( 'wpforms-hidden' );
			}

			// Update Button Link Text field label.
			const $buttonLinkTextRow = $field.find( '.wpforms-field-option-row-button_link_text' );
			const $buttonLinkTextLabel = $buttonLinkTextRow.find( 'label' );
			const $tooltip = $buttonLinkTextLabel.find( 'i.fa-question-circle-o' );

			if ( selectedStyle === 'button' ) {
				$tooltip.attr( 'title', wpforms_camera_builder.button_link_text_tooltip );
				$buttonLinkTextLabel.html( wpforms_camera_builder.button_link_text_label + $tooltip.prop( 'outerHTML' ) );
			} else {
				$tooltip.attr( 'title', wpforms_camera_builder.link_text_tooltip );
				$buttonLinkTextLabel.html( wpforms_camera_builder.link_text_label + $tooltip.prop( 'outerHTML' ) );
			}

			// Reinitialize tooltips for the updated element.
			if ( typeof wpf !== 'undefined' && typeof wpf.initTooltips === 'function' ) {
				wpf.restoreTooltips( $buttonLinkTextRow );
			}
		},

		/**
		 * Update the camera field button/link text in preview when Button Link Text changes.
		 *
		 * @since 1.9.8
		 */
		updateCameraButtonLinkText() {
			const $input = $( this );
			const $field = $input.closest( '.wpforms-field-option' );
			const fieldId = $field.data( 'field-id' );
			const newText = $input.val();
			const $preview = $( '#wpforms-field-' + fieldId );
			const $buttonPreview = $preview.find( '.wpforms-camera-button' );
			const $linkPreview = $preview.find( '.wpforms-camera-link' );

			// Update button text (preserve icon).
			const $buttonIcon = $buttonPreview.find( 'svg' );

			$buttonPreview.html( $buttonIcon ).append( ' ' + newText );

			// Update link text.
			$linkPreview.text( newText );
		},

		/**
		 * Validate camera fields before form submission.
		 *
		 * @since 1.9.8
		 *
		 * @param {Event} event Event object.
		 */
		validateCameraFields( event ) {
			const $cameraFields = $( '.wpforms-field-option' ).filter( function() {
				return $( this ).find( '.wpforms-field-option-row-button_link_text' ).length > 0;
			} );

			$cameraFields.each( function() {
				const $field = $( this );
				const $styleSelect = $field.find( '.wpforms-camera-style select' );
				const $buttonLinkTextInput = $field.find( '.wpforms-field-option-row-button_link_text input' );
				const $minutesInput = $field.find( '.wpforms-file-upload-camera-time-limit-minutes-input' );
				const $secondsInput = $field.find( '.wpforms-file-upload-camera-time-limit-seconds-input' );
				const selectedStyle = $styleSelect.val();
				const buttonLinkText = $buttonLinkTextInput.val().trim();
				const minutesValue = parseInt( $minutesInput.val(), 10 ) || 0;
				const secondsValue = parseInt( $secondsInput.val(), 10 ) || 0;

				// If the style is 'link' and button link text is empty, show an error and prevent submission.
				if ( selectedStyle === 'link' && buttonLinkText === '' ) {
					$.confirm( {
						title: wpforms_camera_builder.error_title,
						content: wpforms_camera_builder.error_message,
						type: 'red',
						typeAnimated: true,
						icon: 'fa fa-exclamation-triangle',
						buttons: {
							ok: {
								text: wpforms_camera_builder.error_ok,
								btnClass: 'btn-confirm',
								action: () => {
									// Close the dialog and prevent form submission.
								},
							},
						},
					} );

					event.preventDefault();
					return false;
				}

				const $formatSelect = $field.find( '.wpforms-file-upload-camera-format select' );
				const selectedFormat = $formatSelect.val();

				// If format is video and total time is 0, ensure minimum 1 second.
				if ( selectedFormat === 'video' && minutesValue === 0 && secondsValue === 0 ) {
					$secondsInput.val( 1 );
				}
			} );
		},
	};

	app.init();

	return app;
}( document, window, jQuery ) );
