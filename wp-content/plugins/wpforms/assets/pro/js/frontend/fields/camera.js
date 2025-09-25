/* global wpforms_camera_frontend */

/**
 * @param wpforms_camera_frontend.wait_time
 */

// noinspection ES6ConvertVarToLetConst
/**
 * WPForms camera functionality.
 *
 * @since 1.9.8
 */
// eslint-disable-next-line no-var
var WPForms = window.WPForms || {};

WPForms.Camera = WPForms.Camera || ( function( document, window, $ ) {
	/**
	 * Localized strings.
	 *
	 * @since 1.9.8
	 *
	 * @type {Object}
	 */
	const strings = wpforms_camera_frontend.strings;

	const app = {
		/**
		 * Stream object for camera access.
		 *
		 * @since 1.9.8
		 *
		 * @type {MediaStream|null}
		 */
		stream: null,

		/**
		 * Current camera facing mode.
		 *
		 * @since 1.9.8
		 *
		 * @type {string}
		 */
		currentFacingMode: 'user',

		/**
		 * Start the engine.
		 *
		 * @since 1.9.8
		 */
		init() {
			// Document ready.
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.9.8
		 */
		ready() {
			app.events();
		},

		/**
		 * Events.
		 *
		 * @since 1.9.8
		 */
		events() {
			// Find all form containers that might contain camera elements.
			const formContainers = document.querySelectorAll( '.wpforms-container' );

			// Add the camera click handler to each form container.
			formContainers.forEach( function( container ) {
				app.addCameraClickHandler( container );
			} );

			$( document ).on( 'click', '.wpforms-camera-link, .wpforms-camera-button', app.openModal );
			$( document ).on( 'click', '.wpforms-camera-capture', app.capture );
			$( document ).on( 'click', '.wpforms-camera-remove-file', app.clearSelectedFile );
			$( document ).on( 'click', '.wpforms-camera-flip', app.flipCamera );
		},

		/**
		 * Add the camera click handler to a container.
		 *
		 * @since 1.9.8
		 *
		 * @param {Element} container The container element to add the handler to.
		 */
		addCameraClickHandler( container ) {
			container.addEventListener( 'click', function( event ) {
				// Check if the clicked element is a camera element or contains one
				if ( event.target.classList.contains( 'wpforms-camera' ) ||
					event.target.closest( '.wpforms-camera' ) ) {
					event.preventDefault();
					event.stopPropagation();
					event.stopImmediatePropagation();

					// Open the camera modal.
					app.openModal( event );

					// Prevent default browser behavior
					return false;
				}
			}, true );
		},

		/**
		 * Open camera modal.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} event Click event.
		 */
		openModal( event ) {
			event.preventDefault();

			// Remove focus from the clicked element to avoid duplicate modal openings after the `enter` keypress.
			event.target.blur();

			// Prepare variables.
			const $field = $( event.target.closest( '.wpforms-field' ) );
			const fieldId = $field.data( 'field-id' );
			const formId = $field.closest( '.wpforms-form' ).data( 'formid' );
			const $modalOverlay = $( `#wpforms-camera-modal-${ formId }-${ fieldId }` );

			// Format initial timer display
			const $videoCountdown = $modalOverlay.find( '.wpforms-camera-video-countdown' );

			if ( $videoCountdown.length ) {
				const timeLimit = parseInt( $videoCountdown.data( 'time-limit' ), 10 ) || 30;
				const $countdownSpan = $videoCountdown.find( 'span' );
				$countdownSpan.text( app.formatTime( timeLimit ) );
			}

			// Prevent body scroll.
			$( 'body' ).addClass( 'wpforms-camera-modal-open' );

			// Maybe show the flip button.
			if ( app.isPhone() ) {
				$modalOverlay.find( '.wpforms-camera-flip' ).addClass( 'wpforms-camera-flip-active' );
			}

			// Show the modal.
			$modalOverlay.css( 'display', 'flex' );

			// Initialize camera.
			app.initCamera( formId, fieldId );

			// Add the modal event listeners.
			const $closeBtn = $modalOverlay.find( '.wpforms-camera-modal-close' );

			$closeBtn.off( 'click' ).on( 'click', function() {
				app.closeModal( $modalOverlay );
			} );

			// Add the keyboard event listener for spacebar to trigger capture.
			app.addKeyboardListener( $modalOverlay );
		},

		/**
		 * Close camera modal.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} $modalOverlay Modal.
		 */
		closeModal( $modalOverlay ) {
			app.stopCamera();

			// Remove keyboard event listener.
			app.removeKeyboardListener( $modalOverlay );

			// Restore body scroll.
			$( 'body' ).removeClass( 'wpforms-camera-modal-open' );

			$modalOverlay.css( 'display', 'none' );
			$modalOverlay.find( '.wpforms-camera-captured-photo' ).remove();
			$modalOverlay.find( '.wpforms-camera-captured-video' ).remove();
			$modalOverlay.find( '.wpforms-camera-modal-buttons' ).hide();
			$modalOverlay.find( '.wpforms-camera-stop' ).hide();

			// Clean up cropper data and elements.
			$modalOverlay.find( 'cropper-canvas' ).remove();
			$modalOverlay.removeData( 'cropper' );
			$modalOverlay.removeData( 'cropped-blob' );

			// Show video preview and initial buttons.
			$modalOverlay.find( 'video' ).show();
			$modalOverlay.find( '.wpforms-camera-modal-actions' ).css( 'display', 'flex' );
			$modalOverlay.find( '.wpforms-camera-capture' ).show();
		},

		/**
		 * Add keyboard event listener for camera modal.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $modalOverlay Modal overlay element.
		 */
		addKeyboardListener( $modalOverlay ) {
			const keyboardHandler = function( event ) {
				// Only trigger on spacebar (keyCode 32) and when modal is visible.
				if ( event.keyCode === 32 && $modalOverlay.css( 'display' ) === 'flex' ) {
					event.preventDefault();

					// Check if stop button is visible (during video recording).
					const $stopButton = $modalOverlay.find( '.wpforms-camera-stop' );
					if ( $stopButton.is( ':visible' ) ) {
						// Trigger stop recording.
						$stopButton.trigger( 'click' );
						return;
					}

					// Check if capture button is visible and enabled.
					const $captureButton = $modalOverlay.find( '.wpforms-camera-capture' );
					if ( $captureButton.is( ':visible' ) && ! $captureButton.prop( 'disabled' ) ) {
						// Trigger capture.
						$captureButton.trigger( 'click' );
					}
				}
			};

			// Store the handler reference for later removal.
			$modalOverlay.data( 'keyboard-handler', keyboardHandler );

			// Add event listener to document.
			$( document ).on( 'keydown.wpforms-camera', keyboardHandler );
		},

		/**
		 * Remove keyboard event listener for camera modal.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $modalOverlay Modal overlay element.
		 */
		removeKeyboardListener( $modalOverlay ) {
			const keyboardHandler = $modalOverlay.data( 'keyboard-handler' );

			if ( keyboardHandler ) {
				// Remove event listener from document.
				$( document ).off( 'keydown.wpforms-camera', keyboardHandler );

				// Remove handler reference.
				$modalOverlay.removeData( 'keyboard-handler' );
			}
		},

		/**
		 * Initialize camera.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} formId  Form ID.
		 * @param {number} fieldId Field ID.
		 */
		initCamera( formId, fieldId ) {
			const $videoEl = $( `#wpforms-camera-video-${ formId }-${ fieldId }` );
			const $previewContainer = $( `#wpforms-camera-modal-${ formId }-${ fieldId } .wpforms-camera-preview` );
			const $errorContainer = $( `#wpforms-camera-modal-${ formId }-${ fieldId } .wpforms-camera-error` );
			const $modalOverlay = $( `#wpforms-camera-modal-${ formId }-${ fieldId }` );
			const videoEl = $videoEl.get( 0 );
			const $captureButton = $modalOverlay.find( '.wpforms-camera-capture' );

			// Determine if we're in video mode.
			const isVideoMode = $modalOverlay.hasClass( 'wpforms-camera-format-video' );

			// Reset display state.
			$previewContainer.css( 'display', 'flex' );
			$errorContainer.hide();

			// Stop any existing stream.
			app.stopCamera();

			// Request camera access with audio if in video mode.
			const constraints = {
				audio: isVideoMode,
				video: {
					facingMode: app.currentFacingMode,
					height: { ideal: 960, max: 1280 },
					frameRate: { ideal: 24, max: 30 },
				},
			};

			navigator.mediaDevices.getUserMedia( constraints )
				.then( function( stream ) {
					app.stream = stream;
					videoEl.srcObject = stream;
					$captureButton.removeAttr( 'disabled' );
				} )
				.catch( function() {
					const errorText = isVideoMode ? strings.camera_video_access_error : strings.camera_access_error;

					$previewContainer.hide();
					$errorContainer.text( errorText ).show();
				} );
		},

		/**
		 * Phone detection.
		 *
		 * @since 1.9.8
		 *
		 * @return {boolean} True if it's phone, false otherwise.
		 */
		isPhone() {
			if ( navigator.userAgentData?.mobile !== undefined ) {
				return navigator.userAgentData.mobile;
			}

			const isIPadOS = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
			const ua = navigator.userAgent.toLowerCase();
			const uaPhone = /(iphone|ipod|android.*mobile|windows phone|bb10|opera mini|mobile safari)/.test( ua );
			const coarse = matchMedia( '(pointer: coarse)' ).matches;
			const noHover = matchMedia( '(hover: none)' ).matches;
			const touch = ( 'ontouchstart' in window ) || navigator.maxTouchPoints > 0;
			return ( ! isIPadOS && uaPhone ) || ( ( coarse || noHover ) && touch );
		},

		/**
		 * Stop the camera stream.
		 *
		 * @since 1.9.8
		 */
		stopCamera() {
			// Stop any active recording.
			$( '.wpforms-camera-modal-overlay' ).each( function() {
				const $modal = $( this );
				const mediaRecorder = $modal.data( 'media-recorder' );

				if ( mediaRecorder && mediaRecorder.state === 'recording' ) {
					mediaRecorder.stop();
				}

				// Clear any active countdown timer.
				const countdownTimer = $modal.data( 'countdown-timer' );

				if ( countdownTimer ) {
					clearInterval( countdownTimer );
				}
			} );

			// Stop all tracks in the stream.
			if ( app.stream ) {
				$.each( app.stream.getTracks(), function( index, track ) {
					track.stop();
				} );
				app.stream = null;
			}
		},

		/**
		 * Flip camera between front and back.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} event Click event.
		 */
		flipCamera( event ) {
			// Only allow flipping on mobile devices.
			if ( ! app.isPhone() ) {
				return;
			}

			// Prevent default behavior.
			if ( event ) {
				event.preventDefault();
			}

			// Find the modal overlay from the event target.
			const $target = $( event.target );
			const $modalOverlay = $target.closest( '.wpforms-camera-modal-overlay' );

			if ( ! $modalOverlay.length ) {
				return;
			}

			// Get form and field IDs.
			const formId = $modalOverlay.data( 'form-id' );
			const fieldId = $modalOverlay.data( 'field-id' );

			// Toggle the facing mode.
			app.currentFacingMode = app.currentFacingMode === 'user' ? 'environment' : 'user';

			// Reinitialize the camera with the new facing mode.
			app.initCamera( formId, fieldId );
		},

		/**
		 * Capture image from camera with countdown timer.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} event Click event.
		 */
		capture( event ) {
			event.preventDefault();

			const $captureButton = $( event.currentTarget );
			const $modalOverlay = $captureButton.closest( '.wpforms-camera-modal-overlay' );
			const formId = $modalOverlay.data( 'form-id' );
			const fieldId = $modalOverlay.data( 'field-id' );

			app.captureAnimation( $captureButton, formId, fieldId );
		},

		/**
		 * Run photo capture animation.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $captureButton Clicked button.
		 * @param {number} formId         Form ID.
		 * @param {number} fieldId        Field ID.
		 */
		captureAnimation( $captureButton, formId, fieldId ) {
			const $countdownEl = $captureButton.parent().find( '.wpforms-camera-countdown' );
			const $numberEl = $countdownEl.find( 'span' );
			const format = $countdownEl.closest( '.wpforms-camera-modal-overlay' ).hasClass( 'wpforms-camera-format-photo' ) ? 'photo' : 'video';
			$captureButton.closest( '.wpforms-camera-modal-overlay' ).find( '.wpforms-camera-flip' ).removeClass( 'wpforms-camera-flip-active' );

			// Hide original button and show countdown.
			$captureButton.hide();
			$countdownEl.css( 'display', 'flex' );

			// Start the CSS animation.
			setTimeout( function() {
				$countdownEl.addClass( 'animate' );
			}, 50 );

			// Update the counter text.
			let count = wpforms_camera_frontend.wait_time;

			const timeInterval = count > 0 ? 1000 : 0;

			const updateCounter = function() {
				count--;

				if ( count > 0 ) {
					$numberEl.text( count );
					setTimeout( updateCounter, 1000 );
				} else {
					// The countdown is complete, capture the photo immediately.
					$countdownEl.removeClass( 'animate' ).hide();

					// Reset the counter for next use
					$numberEl.text( wpforms_camera_frontend.wait_time.toString() );

					// Capture the photo or video
					if ( format === 'photo' ) {
						$captureButton.css( 'display', 'flex' );
						app.takePhoto( formId, fieldId );
					} else {
						app.recordVideo( formId, fieldId );
					}
				}
			};

			setTimeout( updateCounter, timeInterval );
		},

		/**
		 * Take a photo from the camera stream.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} formId  Form ID.
		 * @param {number} fieldId Field ID.
		 */
		takePhoto( formId, fieldId ) {
			if ( ! app.stream ) {
				return;
			}

			const $modalOverlay = $( `#wpforms-camera-modal-${ formId }-${ fieldId }` );
			const $videoEl = $( `#wpforms-camera-video-${ formId }-${ fieldId }` );
			const $canvas = $( `#wpforms-camera-canvas-${ formId }-${ fieldId }` );
			const $previewContainer = $modalOverlay.find( '.wpforms-camera-preview' );
			const videoEl = $videoEl.get( 0 );
			const canvas = $canvas.get( 0 );

			// Set canvas dimensions to match the video stream's actual dimensions.
			const width = videoEl.videoWidth;
			const height = videoEl.videoHeight;
			canvas.width = width;
			canvas.height = height;

			// Draw the current video frame to the canvas.
			const ctx = canvas.getContext( '2d' );
			ctx.drawImage( videoEl, 0, 0, width, height );

			// Hide video.
			$videoEl.hide();

			// Create an image element to display the captured photo.
			const $imgElement = $( '<img>', {
				class: 'wpforms-camera-captured-photo',
				src: canvas.toDataURL( 'image/jpeg', 0.92 ),
			} );

			// Add the image to the preview container.
			$previewContainer.append( $imgElement );

			// Hide the capture button and show Accept/Cancel buttons.
			$modalOverlay.find( '.wpforms-camera-modal-actions' ).hide();
			const $modalButtons = $modalOverlay.find( '.wpforms-camera-modal-buttons' );
			$modalButtons.show();
			this.addPhotoHandlers( $modalButtons, canvas, $modalOverlay, formId, fieldId, $previewContainer, $videoEl );
		},

		/**
		 * Add event handlers for photo capture buttons.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery}      $modalButtons     Modal buttons container.
		 * @param {HTMLElement} canvas            Canvas element.
		 * @param {jQuery}      $modalOverlay     Modal overlay element.
		 * @param {number}      formId            Form ID.
		 * @param {number}      fieldId           Field ID.
		 * @param {jQuery}      $previewContainer Preview container element.
		 * @param {jQuery}      $videoEl          Video element.
		 */
		// eslint-disable-next-line max-lines-per-function
		addPhotoHandlers( $modalButtons, canvas, $modalOverlay, formId, fieldId, $previewContainer, $videoEl ) {
			// Accept button handler.
			$modalButtons.find( '.wpforms-camera-accept' ).off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();

				// Check if we have a cropped blob saved (after cropping was applied).
				const croppedBlob = $modalOverlay.data( 'cropped-blob' );

				if ( croppedBlob ) {
					// Close the modal.
					app.closeModal( $modalOverlay );

					// Attach the cropped file to the upload field.
					app.attachFile( formId, fieldId, croppedBlob );

					$modalOverlay.css( 'display', 'none' );
				} else {
					// Use the original canvas if no cropping was applied.
					canvas.toBlob( function( blob ) {
						if ( ! blob ) {
							return;
						}

						// Close the modal.
						app.closeModal( $modalOverlay );

						// Attach the file to the upload field.
						app.attachFile( formId, fieldId, blob );

						$modalOverlay.css( 'display', 'none' );
					}, 'image/jpeg', 0.92 );
				}
			} );

			// Cancel button handler.
			$modalButtons.find( '.wpforms-camera-cancel' ).off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();
				// Remove captured photo.
				$previewContainer.find( '.wpforms-camera-captured-photo' ).remove();

				// Show video and capture the button again.
				$videoEl.show();
				$modalOverlay.find( '.wpforms-camera-modal-actions' ).css( 'display', 'flex' );
				$modalButtons.hide();

				// Remove crop data.
				$modalOverlay.removeData( 'cropped-blob' );
				$modalOverlay.find( 'cropper-canvas' ).remove();

				// Restore button states.
				$modalButtons.find( '.wpforms-camera-accept-crop' ).hide();
				$modalButtons.find( '.wpforms-camera-crop-cancel' ).hide();
				$modalButtons.find( '.wpforms-camera-crop' ).show();
				$modalButtons.find( '.wpforms-camera-accept' ).show();
				$modalOverlay.find( '.wpforms-camera-flip' ).addClass( 'wpforms-camera-flip-active' );
			} );

			// Crop button handler (using event delegation for dynamic class changes).
			$modalButtons.off( 'click', '.wpforms-camera-crop' ).on( 'click', '.wpforms-camera-crop', function( event ) {
				event.preventDefault();

				const cropper = app.initCropper( $previewContainer );

				$modalOverlay.data( 'cropper', cropper );

				$( this ).hide();
				$modalButtons.find( '.wpforms-camera-accept' ).hide();
				$modalButtons.find( '.wpforms-camera-accept-crop' ).show();
				$modalButtons.find( '.wpforms-camera-crop-cancel' ).show();
			} );

			// The `crop cancel` button handler (using event delegation for dynamic class changes).
			$modalButtons.off( 'click', '.wpforms-camera-crop-cancel' ).on( 'click', '.wpforms-camera-crop-cancel', function( event ) {
				event.preventDefault();

				app.cancelCrop( $modalOverlay, $modalButtons );
			} );

			// The `crop accept` button handler.
			$modalButtons.off( 'click', '.wpforms-camera-accept-crop' ).on( 'click', '.wpforms-camera-accept-crop', function( event ) {
				event.preventDefault();

				app.acceptCrop( $modalOverlay, $modalButtons );
			} );
		},

		/**
		 * Accept crop.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $modalOverlay Modal overlay element.
		 * @param {jQuery} $modalButtons Modal buttons container.
		 */
		acceptCrop( $modalOverlay, $modalButtons ) {
			// Get the cropper instance and remove cropper elements from DOM.
			const cropper = $modalOverlay.data( 'cropper' );
			const cropperSelection = cropper.getCropperSelection();
			const capturedPhoto = $modalOverlay.find( '.wpforms-camera-captured-photo' );

			// Create canvas using manual method.
			const canvas = app.createManualCroppedCanvas( cropper, cropperSelection, capturedPhoto );

			// If canvas creation failed, cancel crop.
			if ( ! canvas ) {
				app.cancelCrop( $modalOverlay, $modalButtons );
				return;
			}

			// Convert canvas to blob and update the image.
			canvas.toBlob( function( blob ) {
				if ( blob ) {
					const imageUrl = URL.createObjectURL( blob );
					capturedPhoto.attr( 'src', imageUrl );

					// Store the cropped blob for later use.
					$modalOverlay.data( 'cropped-blob', blob );
				}
			}, 'image/jpeg', 0.92 );

			// Remove cropper elements from DOM.
			$modalOverlay.find( 'cropper-canvas' ).remove();

			// Clear the cropper data.
			$modalOverlay.removeData( 'cropper' );

			// Show the captured photo again.
			$modalOverlay.find( '.wpforms-camera-captured-photo' ).show();

			// Restore button states.
			$modalButtons.find( '.wpforms-camera-crop-cancel' ).hide();
			$modalButtons.find( '.wpforms-camera-accept-crop' ).hide();
			$modalButtons.find( '.wpforms-camera-crop' ).show();
			$modalButtons.find( '.wpforms-camera-accept' ).show();
		},

		/**
		 * Cancel crop mode and restore original state.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $modalOverlay Modal overlay element.
		 * @param {jQuery} $modalButtons Modal buttons container.
		 */
		cancelCrop( $modalOverlay, $modalButtons ) {
			// Get the cropper instance and remove cropper elements from DOM.
			const cropper = $modalOverlay.data( 'cropper' );

			if ( cropper ) {
				// Remove cropper elements from DOM.
				$modalOverlay.find( 'cropper-canvas' ).remove();

				// Clear the cropper data.
				$modalOverlay.removeData( 'cropper' );

				// Show the captured photo again.
				$modalOverlay.find( '.wpforms-camera-captured-photo' ).show();
			}

			// Clear any previously saved cropped blob since we're canceling.
			$modalOverlay.removeData( 'cropped-blob' );

			// Restore button states.
			$modalButtons.find( '.wpforms-camera-crop-cancel' ).hide();
			$modalButtons.find( '.wpforms-camera-accept-crop' ).hide();
			$modalButtons.find( '.wpforms-camera-accept' ).show();
			$modalButtons.find( '.wpforms-camera-crop' ).show();
		},

		/**
		 * Create cropped canvas.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} cropper          Cropper instance.
		 * @param {Object} cropperSelection Cropper selection element.
		 * @param {jQuery} capturedPhoto    Captured photo element.
		 *
		 * @return {HTMLCanvasElement|null} Canvas element or null if failed.
		 */
		createManualCroppedCanvas( cropper, cropperSelection, capturedPhoto ) {
			// Get the original image.
			const img = capturedPhoto[ 0 ];

			if ( ! img || ! img.complete ) {
				return null;
			}

			// Get cropper canvas and selection bounds.
			const cropperCanvas = cropper.getCropperCanvas();
			const canvasBounds = cropperCanvas.getBoundingClientRect();
			const selectionBounds = cropperSelection.getBoundingClientRect();

			// Calculate relative position and size.
			const relativeX = ( selectionBounds.left - canvasBounds.left ) / canvasBounds.width;
			const relativeY = ( selectionBounds.top - canvasBounds.top ) / canvasBounds.height;
			const relativeWidth = selectionBounds.width / canvasBounds.width;
			const relativeHeight = selectionBounds.height / canvasBounds.height;

			// Get natural image dimensions.
			const naturalWidth = img.naturalWidth || img.width;
			const naturalHeight = img.naturalHeight || img.height;

			// Calculate crop area in image coordinates.
			const cropX = Math.max( 0, Math.floor( relativeX * naturalWidth ) );
			const cropY = Math.max( 0, Math.floor( relativeY * naturalHeight ) );
			const cropWidth = Math.min( naturalWidth - cropX, Math.floor( relativeWidth * naturalWidth ) );
			const cropHeight = Math.min( naturalHeight - cropY, Math.floor( relativeHeight * naturalHeight ) );

			// Validate crop parameters.
			if ( cropWidth <= 0 || cropHeight <= 0 ) {
				return null;
			}

			// Create canvas and draw cropped image.
			const canvas = document.createElement( 'canvas' );

			canvas.width = cropWidth;
			canvas.height = cropHeight;

			const ctx = canvas.getContext( '2d' );

			ctx.imageSmoothingEnabled = true;
			ctx.imageSmoothingQuality = 'high';

			// Draw the cropped portion of the image.
			ctx.drawImage(
				img,
				cropX, cropY, cropWidth, cropHeight,
				0, 0, cropWidth, cropHeight
			);

			return canvas;
		},

		/**
		 * Init Cropper.js and return instance.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $previewContainer Preview container element.
		 * @return {Object} Cropper instance.
		 */
		initCropper( $previewContainer ) {
			// eslint-disable-next-line new-cap
			const CropperClass = ( window.Cropper && window.Cropper.default ) || window.Cropper;
			const containerEl = $previewContainer[ 0 ];

			const $img = $previewContainer.find( '.wpforms-camera-captured-photo' );

			// Init Cropper.
			const cropper = new CropperClass( $img[ 0 ], {
				container: containerEl,
				template: app.getCropperTemplate(),
			} );

			//Tweak cropper.
			const cropperCanvas = cropper.getCropperCanvas();
			const cropperSelection = cropper.getCropperSelection();
			const grid = document.querySelector( 'cropper-grid' );

			// Add border to the grid.
			grid.$addStyles( `
					:host>span+span {
						border-top: 1px solid #fff;
					}
					:host>span>span+span {
						border-left: 1px solid #fff;
					}
					` );

			// Dont allow going outside the canvas.
			if ( cropperCanvas && cropperSelection ) {
				// Keep the selection inside the canvas
				app.limitCropperBoundaries( cropperCanvas, cropperSelection );
			}

			return cropper;
		},

		/**
		 * Get the Cropper.js template.
		 *
		 * @since 1.9.8
		 *
		 * @return {string} Cropper template.
		 */
		getCropperTemplate() {
			return `
				<cropper-canvas background>
					<cropper-image></cropper-image>
					<cropper-shade theme-color="rgba(0, 0, 0, 0.75)"></cropper-shade>
					<cropper-handle action="select" plain></cropper-handle>
					<cropper-selection initial-coverage="0.7" movable resizable>
						<cropper-grid role="grid" theme-color="#fff" bordered covered></cropper-grid>
						<cropper-handle action="move" theme-color="transparent"></cropper-handle>
						<cropper-handle theme-color="#fff" action="n-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="e-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="s-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="w-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="ne-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="nw-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="se-resize"></cropper-handle>
						<cropper-handle theme-color="#fff" action="sw-resize"></cropper-handle>
					</cropper-selection>
				</cropper-canvas>
				`;
		},

		/**
		 * Limit the Cropper.js boundaries to the canvas.
		 *
		 * @param {Object} cropperCanvas    Cropper Canvas element.
		 * @param {Object} cropperSelection Cropper Selection element.
		 *
		 * @since 1.9.8
		 */
		limitCropperBoundaries( cropperCanvas, cropperSelection ) {
			cropperSelection.addEventListener( 'change', ( e ) => {
				// e.detail = { x, y, width, height } in CSS pixels.
				const { x, y, width, height } = e.detail;
				const { width: cw, height: ch } = cropperCanvas.getBoundingClientRect();

				// Check boundaries.
				const inside =
					x >= 0 &&
					y >= 0 &&
					x + width <= cw &&
					y + height <= ch;

				// Cancel this change if it would go outside.
				if ( ! inside ) {
					e.preventDefault();
				}
			} );
		},

		/**
		 * Check if the browser supports MediaRecorder.
		 *
		 * @since 1.9.8
		 *
		 * @return {boolean} True if MediaRecorder is supported.
		 */
		isMediaRecorderSupported() {
			return typeof MediaRecorder !== 'undefined';
		},

		/**
		 * Handle case when MediaRecorder is not supported.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} formId  Form ID.
		 * @param {number} fieldId Field ID.
		 */
		handleUnsupportedMediaRecorder( formId, fieldId ) {
			const $modalOverlay = $( `#wpforms-camera-modal-${ formId }-${ fieldId }` );
			const $previewContainer = $modalOverlay.find( '.wpforms-camera-preview' );
			const $errorContainer = $modalOverlay.find( '.wpforms-camera-error' );

			// Hide preview and show error.
			$previewContainer.hide();
			$errorContainer.text( strings.video_recording_error ).show();
		},

		/**
		 * Get the supported MIME type for video recording.
		 *
		 * @since 1.9.8
		 *
		 * @return {Object} Object with mimeType and extension properties.
		 */
		getSupportedVideoType() {
			// Default to the WebM, which works in Chrome, Firefox, Edge.
			let result = {
				mimeType: 'video/webm;codecs=vp9,opus',
				extension: 'webm',
				videoBitsPerSecond: 2500000, // 2.5 Mbps
			};

			// Check for Safari support (MP4 with H.264)
			const isSafari = /^((?!chrome|android).)*safari/i.test( navigator.userAgent );

			// Safari requires special handling.
			if ( isSafari ) {
				// Try MP4 for Safari.
				if ( MediaRecorder.isTypeSupported( 'video/mp4' ) ) {
					result = {
						mimeType: 'video/mp4',
						extension: 'mp4',
						videoBitsPerSecond: 2500000,
					};
				}
			} else {
				// For non-Safari browsers, try different codecs in order of preference.
				const types = [
					'video/webm;codecs=vp9,opus',
					'video/webm;codecs=vp8,opus',
					'video/webm',
				];

				for ( const type of types ) {
					if ( MediaRecorder.isTypeSupported( type ) ) {
						result.mimeType = type;
						break;
					}
				}
			}

			return result;
		},

		/**
		 * Create a MediaRecorder instance with appropriate settings.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} videoConfig       Video configuration object.
		 * @param {jQuery} $previewContainer Preview container element.
		 * @param {jQuery} $errorContainer   Error container element.
		 * @param {jQuery} $cameraStop       Stop button element.
		 *
		 * @return {MediaRecorder|null} MediaRecorder instance or null if creation failed.
		 */
		createMediaRecorder( videoConfig, $previewContainer, $errorContainer, $cameraStop ) {
			try {
				return new MediaRecorder( app.stream, {
					mimeType: videoConfig.mimeType,
					videoBitsPerSecond: videoConfig.videoBitsPerSecond,
				} );
			} catch ( e ) {
				try {
					// Try without options as a fallback.
					return new MediaRecorder( app.stream );
				} catch ( err ) {
					$previewContainer.hide();
					$errorContainer.text( strings.video_recording_error ).show();
					$cameraStop.hide();

					return null;
				}
			}
		},

		/**
		 * Setup video recording countdown timer.
		 *
		 * @since 1.9.8
		 *
		 * @param {number}        timeLimit      Time limit in seconds.
		 * @param {jQuery}        $countdownSpan Countdown span element.
		 * @param {MediaRecorder} mediaRecorder  MediaRecorder instance.
		 * @param {jQuery}        $cameraStop    Stop button element.
		 * @param {jQuery}        $modalOverlay  Modal overlay element.
		 *
		 * @return {number} Interval ID for the countdown timer.
		 */
		setupRecordingTimer( timeLimit, $countdownSpan, mediaRecorder, $cameraStop, $modalOverlay ) {
			let remainingTime = timeLimit;

			// Update the initial countdown display.
			$countdownSpan.text( app.formatTime( remainingTime ) );

			// Start the countdown timer.
			const countdownTimer = setInterval( function() {
				remainingTime--;
				$countdownSpan.text( app.formatTime( remainingTime ) );

				// When time is up.
				if ( remainingTime <= 0 ) {
					clearInterval( countdownTimer );

					// Stop recording.
					if ( mediaRecorder.state === 'recording' ) {
						mediaRecorder.stop();
					}

					// Reset and hide the stop button.
					$cameraStop.hide();
				}
			}, 1000 );

			// Store the timer reference to clear it if stopped manually.
			$modalOverlay.data( 'countdown-timer', countdownTimer );

			return countdownTimer;
		},

		/**
		 * Set up the stop recording button.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery}        $cameraStop    Stop button element.
		 * @param {jQuery}        $modalOverlay  Modal overlay element.
		 * @param {MediaRecorder} mediaRecorder  MediaRecorder instance.
		 * @param {number}        timeLimit      Time limit in seconds.
		 * @param {jQuery}        $countdownSpan Countdown span element.
		 */
		setupStopButton( $cameraStop, $modalOverlay, mediaRecorder, timeLimit, $countdownSpan ) {
			$cameraStop.off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();

				// Clear the countdown timer.
				clearInterval( $modalOverlay.data( 'countdown-timer' ) );

				// Reset and hide the stop button.
				$cameraStop.hide();

				// Stop the recording if it's still active.
				if ( mediaRecorder.state === 'recording' ) {
					mediaRecorder.stop();
				}

				// Reset the countdown display.
				$countdownSpan.text( app.formatTime( timeLimit ) );
			} );
		},

		/**
		 * Set up the cancel video button.
		 *
		 * @param {jQuery} $modalOverlay Modal overlay element.
		 * @since 1.9.8
		 */
		setupCancelVideoButton( $modalOverlay ) {
			const $videoCancel = $modalOverlay.find( '.wpforms-camera-cancel-video' );

			$videoCancel.show().off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();
				app.closeModal( $modalOverlay );
			} );
		},

		/**
		 * Handle video recording completion.
		 *
		 * @since 1.9.8
		 *
		 * @param {Blob}   blob              Recorded video blob.
		 * @param {string} blobType          MIME type of the blob.
		 * @param {number} formId            Form ID.
		 * @param {number} fieldId           Field ID.
		 * @param {Object} videoConfig       Video configuration object.
		 * @param {jQuery} $modalOverlay     Modal overlay element.
		 * @param {jQuery} $previewContainer Preview container element.
		 */
		handleRecordingComplete( blob, blobType, formId, fieldId, videoConfig, $modalOverlay, $previewContainer ) {
			// Create a URL for the recorded video
			const videoURL = URL.createObjectURL( blob );
			const $videoEl = $( `#wpforms-camera-video-${ formId }-${ fieldId }` );

			// Hide the original video element
			$videoEl.hide();

			// Create and display the recorded video
			const $recordedVideo = this.createRecordedVideoElement( videoURL );
			$previewContainer.append( $recordedVideo );

			// Set up the UI for accepting or canceling the recording.
			this.setupRecordingAcceptUI(
				$modalOverlay,
				blob,
				blobType,
				videoURL,
				formId,
				fieldId,
				videoConfig,
				$videoEl,
				$previewContainer
			);
		},

		/**
		 * Create a video element to display the recorded video.
		 *
		 * @since 1.9.8
		 *
		 * @param {string} videoURL URL of the recorded video.
		 *
		 * @return {jQuery} jQuery video element.
		 */
		createRecordedVideoElement( videoURL ) {
			return $( '<video>', {
				class: 'wpforms-camera-captured-video',
				src: videoURL,
				controls: true,
				autoplay: true,
				muted: false,
				playsinline: true, // Important for iOS.
			} );
		},

		/**
		 * Set up the UI for accepting or canceling the recording.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $modalOverlay     Modal overlay element.
		 * @param {Blob}   blob              Recorded video blob.
		 * @param {string} blobType          MIME type of the blob.
		 * @param {string} videoURL          URL of the recorded video.
		 * @param {number} formId            Form ID.
		 * @param {number} fieldId           Field ID.
		 * @param {Object} videoConfig       Video configuration object.
		 * @param {jQuery} $videoEl          Video element.
		 * @param {jQuery} $previewContainer Preview container element.
		 */
		setupRecordingAcceptUI( $modalOverlay, blob, blobType, videoURL, formId, fieldId, videoConfig, $videoEl, $previewContainer ) {
			// Hide the capture button and show Accept/Cancel buttons.
			$modalOverlay.find( '.wpforms-camera-modal-actions' ).hide();
			const $modalButtons = $modalOverlay.find( '.wpforms-camera-modal-buttons' );
			$modalButtons.show();

			// Accept button handler.
			$modalButtons.find( '.wpforms-camera-accept' ).off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();

				app.closeModal( $modalOverlay );

				// Determine file extension based on the MIME type.
				const extension = videoConfig.extension || 'webm';

				// Generate a filename with the timestamp
				const filename = `camera-video-${ Date.now() }.${ extension }`;
				const file = new File( [ blob ], filename, { type: blobType } );

				// Attach the file to the upload field.
				app.attachFile( formId, fieldId, file );

				// Close the modal.
				$modalOverlay.css( 'display', 'none' );
			} );

			// Cancel button handler.
			$modalButtons.find( '.wpforms-camera-cancel' ).off( 'click' ).on( 'click', function( event ) {
				event.preventDefault();

				// Remove recorded video.
				$previewContainer.find( '.wpforms-camera-captured-video' ).remove();

				// Release the object URL to free memory.
				URL.revokeObjectURL( videoURL );

				// Show video and capture the button again.
				$videoEl.show();
				$modalOverlay.find( '.wpforms-camera-capture' ).css( 'display', 'flex' );
				$modalOverlay.find( '.wpforms-camera-modal-actions' ).css( 'display', 'flex' );
				$modalOverlay.find( '.wpforms-camera-flip' ).addClass( 'wpforms-camera-flip-active' );
				$modalButtons.hide();
			} );
		},

		/**
		 * Record a video from the camera stream.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} formId  Form ID.
		 * @param {number} fieldId Field ID.
		 */
		recordVideo( formId, fieldId ) {
			if ( ! app.stream ) {
				return;
			}

			// Check if MediaRecorder is supported.
			if ( ! app.isMediaRecorderSupported() ) {
				app.handleUnsupportedMediaRecorder( formId, fieldId );

				return;
			}

			// Get DOM elements.
			const $modalOverlay = $( `#wpforms-camera-modal-${ formId }-${ fieldId }` );
			const $cameraStop = $modalOverlay.find( '.wpforms-camera-stop' );
			const $videoCountdown = $modalOverlay.find( '.wpforms-camera-video-countdown' );
			const $previewContainer = $modalOverlay.find( '.wpforms-camera-preview' );
			const $errorContainer = $modalOverlay.find( '.wpforms-camera-error' );

			// Get time limit from data attribute.
			const timeLimit = parseInt( $videoCountdown.data( 'time-limit' ), 10 ) || 30; // Default to 30 seconds

			// Show the stop button.
			$cameraStop.show();

			// Get the supported video type for this browser.
			const videoConfig = app.getSupportedVideoType();

			// Create the MediaRecorder instance.
			const mediaRecorder = app.createMediaRecorder( videoConfig, $previewContainer, $errorContainer, $cameraStop );

			if ( ! mediaRecorder ) {
				return;
			}

			// Setup data collection.
			const chunks = [];

			mediaRecorder.ondataavailable = function( event ) {
				if ( event.data && event.data.size > 0 ) {
					chunks.push( event.data );
				}
			};

			// Handle recording completion.
			mediaRecorder.onstop = function() {
				const blobType = mediaRecorder.mimeType || videoConfig.mimeType || 'video/webm';
				const blob = new Blob( chunks, { type: blobType } );

				app.handleRecordingComplete( blob, blobType, formId, fieldId, videoConfig, $modalOverlay, $previewContainer );
			};

			// Start recording.
			mediaRecorder.start( 1000 ); // Collect data in 1-second chunks
			$modalOverlay.data( 'media-recorder', mediaRecorder );

			const $countdownSpan = $videoCountdown.find( 'span' );

			// Setup recording timer.
			app.setupRecordingTimer( timeLimit, $countdownSpan, mediaRecorder, $cameraStop, $modalOverlay );

			// Setup buttons.
			app.setupStopButton( $cameraStop, $modalOverlay, mediaRecorder, timeLimit, $countdownSpan );
			app.setupCancelVideoButton( $modalOverlay );
		},

		/**
		 * Attach a file to the file upload field.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} formId  Form ID.
		 * @param {number} fieldId Field ID.
		 * @param {Blob}   blob    File blob.
		 */
		attachFile( formId, fieldId, blob ) {
			// Determine the file type and create the appropriate filename
			let filename, fileType;

			if ( blob.type.startsWith( 'video/' ) ) {
				// It's a video file
				const extension = blob.type.includes( 'mp4' ) ? 'mp4' : 'webm';
				filename = `camera-video-${ Date.now() }.${ extension }`;
				fileType = blob.type || ( extension === 'mp4' ? 'video/mp4' : 'video/webm' );
			} else {
				// Default to image/photo
				filename = `camera-photo-${ Date.now() }.jpg`;
				fileType = 'image/jpeg';
			}

			const file = new File( [ blob ], filename, { type: fileType } );

			// Check for modern uploader.
			const $uploaderContainer = $( `.wpforms-uploader[data-form-id="${ formId }"][data-field-id="${ fieldId }"]` );
			const isModernStyle = $uploaderContainer.length > 0;

			if ( isModernStyle ) {
				// Modern file upload field.
				const dropzoneElement = $uploaderContainer[ 0 ];

				if ( dropzoneElement && dropzoneElement.dropzone ) {
					// Add the file to Dropzone.
					dropzoneElement.dropzone.addFile( file );

					// Set camera field state for modern uploader.
					const $input = $uploaderContainer.parents( '.wpforms-field-camera' ).find( 'input[name="' + dropzoneElement.dataset.inputName + '"]' );
					if ( $input.length ) {
						app.setCameraFieldState( $input, file );
					}
				}
			} else {
				// Classic file upload field.
				const $fileInput = $( `#wpforms-${ formId }-field_${ fieldId }` );

				if ( $fileInput.length ) {
					// Create a FileList-like object to store our file.
					const dataTransfer = new DataTransfer();
					dataTransfer.items.add( file );

					// Set the file property of the input element.
					$fileInput[ 0 ].files = dataTransfer.files;

					// Trigger `change` event to notify any listeners.
					$fileInput.trigger( 'change' );

					app.setCameraFieldState( $fileInput, file );
				}
			}
		},

		/**
		 * Attach a file to the file upload field.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $fileInput File input element.
		 * @param {Object} file       Selected file.
		 */
		setCameraFieldState( $fileInput, file ) {
			// Debug: Check if fileInput still exists in DOM.
			if ( ! $fileInput.length || ! document.contains( $fileInput[ 0 ] ) ) {
				return;
			}

			// Handle Camera field.
			const $cameraField = $fileInput.closest( '.wpforms-field-camera' );
			if ( $cameraField.length ) {
				const fileName = file?.name ?? '';
				if ( ! fileName ) {
					return;
				}

				const $trigger = $cameraField.find( '.wpforms-camera-link, .wpforms-camera-button' );
				if ( ! $trigger.length ) {
					return;
				}

				$trigger.hide();
				const $selected = $cameraField.find( '.wpforms-camera-selected-file' );

				$selected
					.addClass( 'wpforms-camera-selected-file-active' )
					.find( 'span' )
					.text( fileName );

				// Clear validation errors when the file is selected.
				$cameraField.removeClass( 'wpforms-has-error' );
				$fileInput.removeClass( 'wpforms-error' );
				$cameraField.find( '.wpforms-error' ).remove();

				// Check if all field errors are cleared and hide general form error if needed.
				app.checkAndHideGeneralFormError( $cameraField );
			}

			// Handle File Upload field with camera enabled.
			const $fileUploadField = $fileInput.closest( '.wpforms-field-file-upload' );
			if ( $fileUploadField.length ) {
				// Clear validation errors when file is selected.
				$fileUploadField.removeClass( 'wpforms-has-error' );

				// Remove only error message elements, not the input itself.
				$fileUploadField.find( '.wpforms-error:not(input)' ).remove();

				// Remove wpforms-error class from the input element.
				$fileInput.removeClass( 'wpforms-error' );

				// Ensure the file input remains visible and functional.
				$fileInput.show().css( 'display', 'block' );

				// Trigger validation to clear any jQuery Validation errors (only if validator exists).
				if ( $fileInput.data( 'validator' ) ) {
					$fileInput.valid();
				}

				// Check if all field errors are cleared and hide general form error if needed.
				app.checkAndHideGeneralFormError( $fileUploadField );
			}
		},

		/**
		 * Reset the camera field state.
		 *
		 * @since 1.9.8
		 *
		 * @param {Object} event Event object
		 */
		clearSelectedFile( event ) {
			event.preventDefault();
			const $trigger = $( event.currentTarget );

			const $cameraField = $trigger.closest( '.wpforms-field-camera' );

			if ( ! $cameraField.length ) {
				return;
			}

			const fileInput = $cameraField.find( 'input[type="file"]' )[ 0 ];

			if ( ! fileInput ) {
				return;
			}

			const $selected = $cameraField.find( '.wpforms-camera-selected-file' );
			const $cameraLink = $cameraField.find( '.wpforms-camera-link, .wpforms-camera-button' );

			// Reset UI.
			fileInput.value = '';
			$selected.removeClass( 'wpforms-camera-selected-file-active' );
			$cameraLink.show();
		},

		/**
		 * Format time in M:SS format.
		 *
		 * @since 1.9.8
		 *
		 * @param {number} seconds Seconds to format.
		 *
		 * @return {string} Formatted time string.
		 */
		formatTime( seconds ) {
			const minutes = Math.floor( seconds / 60 );
			const secs = seconds % 60;

			return `${ minutes }:${ secs.toString().padStart( 2, '0' ) }`;
		},

		/**
		 * Check if all field errors are cleared and hide general form error if needed.
		 *
		 * @since 1.9.8
		 *
		 * @param {jQuery} $field Field element.
		 */
		checkAndHideGeneralFormError( $field ) {
			const $form = $field.closest( 'form' );
			if ( ! $form.length ) {
				return;
			}

			// Check if there are any remaining field errors in the form.
			const hasFieldErrors = $form.find( '.wpforms-has-error, .wpforms-error' ).length > 0;

			// If no field errors remain, hide the general form error.
			if ( ! hasFieldErrors ) {
				$form.find( '.wpforms-error-container' ).remove();
			}
		},

	};

	return app;
}( document, window, jQuery ) );

// Initialize.
WPForms.Camera.init();
