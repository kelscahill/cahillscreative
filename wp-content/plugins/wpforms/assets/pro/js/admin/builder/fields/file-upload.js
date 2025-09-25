/* global wpforms_builder, wpforms_education, wpf, WPFormsBuilder */

/**
 * @param wpforms_builder.empty_label_alternative_text
 * @param wpforms_builder.file_upload.all_user_roles_selected
 * @param wpforms_builder.file_upload.password_empty_error_text
 * @param wpforms_builder.file_upload.password_empty_error_title
 * @param wpforms_builder.file_upload.password_match_error_text
 * @param wpforms_builder.file_upload.password_match_error_title
 * @param wpforms_builder.incompatible_addon_text
 * @param wpforms_builder.notification_error_text
 * @param wpforms_builder.notification_error_title
 * @param wpforms_builder.notification_warning_text
 * @param wpforms_builder.notification_warning_title
 */

// noinspection ES6ConvertVarToLetConst
/**
 * Form Builder Field File Upload module.
 *
 * @since 1.9.4
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldFileUpload = WPForms.Admin.Builder.FieldFileUpload || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.9.4
	 *
	 * @type {Object}
	 */
	let el = {};

	/**
	 * Spinner markup.
	 *
	 * @since 1.7.0
	 *
	 * @type {string}
	 */
	const spinner = '<i class="wpforms-loading-spinner wpforms-loading-white wpforms-loading-inline"></i>';

	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.4
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.9.4
		 */
		ready() {
			app.setup();
			app.initUserRestrictionsSelects();
			app.events();
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.9.4
		 */
		setup() {
			// Cache DOM elements.
			el = {
				$builder: $( '#wpforms-builder' ),
			};
		},

		/**
		 * Add handlers on events.
		 *
		 * @since 1.9.4
		 */
		events() {
			el.$builder
				.on( 'change', '.wpforms-file-upload-media-library', app.mediaLibraryOptionHandler )
				.on( 'change', '.wpforms-camera-media-library', app.mediaLibraryOptionHandler )
				.on( 'change', '.wpforms-file-upload-access-restrictions', app.accessRestrictionsOptionHandler )
				.on( 'change', '.wpforms-file-upload-password-restrictions', app.passwordRestrictionsOptionHandler )
				.on( 'change', '.wpforms-file-upload-user-restrictions', app.userRestrictionsOptionHandler )
				.on( 'keyup focus', '.wpforms-file-upload-password', app.cleanPasswordButtonHandler )
				.on( 'keyup', '.wpforms-file-upload-password-confirm', app.checkPasswordMatch )
				.on( 'change', '.wpforms-file-upload-password', app.sanitizePasswordValue )
				.on( 'click', '.wpforms-file-upload-password-clean', app.cleanPasswordInput )
				.on( 'click', '.wpforms-notifications-disabled-option', app.displayNotificationAlert )
				.on( 'click', '[post-submissions-disabled="1"]', app.displayPostSubmissionsDisabledAlert )
				.on( 'wpformsFieldAdd', app.initFieldUserRestrictionsSelects )
				.on( 'wpformsBeforeSave', app.checkPasswordMatchBeforeSave )
				.on( 'wpformsBeforeSave', app.checkNotificationsBeforeSave )
				.on( 'wpformsNotificationFieldAdded', app.notificationFileUploadFieldAdded )
				.on( 'wpformsNotificationFieldRemoved', app.notificationFileUploadFieldRemoved )
				.on( 'wpformsNotificationsToggleConditionalChange', app.notificationToggleConditionalChange )
				.on( 'wpformsNotificationsToggle', app.notificationsToggle )
				.on( 'wpformsBuilderReady', app.disableRestrictions );
		},

		/**
		 * Initialize user restrictions selects with ChoicesJS.
		 *
		 * @since 1.9.4
		 */
		initUserRestrictionsSelects() {
			const $userRolesSelects = $( '.wpforms-file-upload-user-roles-select' );

			$userRolesSelects.each( function() {
				app.initChoicesJS( $( this )[ 0 ], {}, wpforms_builder.file_upload.all_user_roles_selected );
			} );

			const $userNamesSelects = $( '.wpforms-file-upload-user-names-select' );

			$userNamesSelects.each( function() {
				app.initUserNamesSelect( $( this )[ 0 ] );
			} );
		},

		/**
		 * Initialize user restrictions select for a specific field after it has been added.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event}  event   The event object.
		 * @param {number} fieldId The ID of the field.
		 * @param {string} type    The type of the field.
		 */
		initFieldUserRestrictionsSelects( event, fieldId, type ) {
			if ( type !== 'file-upload' && type !== 'camera' ) {
				return;
			}

			app.initChoicesJS( $( `#wpforms-field-option-${ fieldId }-user_roles_restrictions` )[ 0 ], {}, wpforms_builder.file_upload.all_user_roles_selected );
			app.initUserNamesSelect( $( `#wpforms-field-option-${ fieldId }-user_names_restrictions` )[ 0 ] );
		},

		/**
		 * Initialize usernames select with AJAX search.
		 *
		 * @since 1.9.4
		 *
		 * @param {HTMLElement} select The select element to initialize.
		 */
		initUserNamesSelect( select ) {
			const ajaxArgs = {
				action: 'wpforms_ajax_search_user_names',
				nonce: wpforms_builder.nonce,
			};

			app.initChoicesJS( select, ajaxArgs );
		},

		/**
		 * Initialize ChoicesJS for the given select element.
		 *
		 * @since 1.9.4
		 *
		 * @param {HTMLElement} select        The select element to initialize ChoicesJS on.
		 * @param {Object}      ajaxArgs      Optional. Arguments for AJAX requests.
		 * @param {string}      noChoicesText Optional. Text to display when there are no choices.
		 */
		initChoicesJS( select, ajaxArgs = {}, noChoicesText = '' ) {
			const choicesJS = WPForms.Admin.Builder.WPFormsChoicesJS.setup(
				select,
				{
					removeItemButton: true,
					noChoicesText,
					callbackOnInit() {
						wpf.showMoreButtonForChoices( this.containerOuter.element );
					},
				},
				ajaxArgs
			);

			if ( ! choicesJS.getValue().length ) {
				const fieldId = $( select ).data( 'field-id' ),
					fieldName = $( select ).data( 'field-name' );

				const values = app.getHiddenValues( fieldId, fieldName );

				choicesJS.setChoiceByValue( values );
			}

			choicesJS.passedElement.element.addEventListener( 'removeItem', function( event ) {
				// Set the selected value to 'administrator' if it is removed.
				if ( event.detail.value === 'administrator' ) {
					choicesJS.setChoiceByValue( 'administrator' );
				}

				if ( event.target.classList.contains( 'wpforms-file-upload-user-names-select' ) ) {
					choicesJS.clearChoices();
				}
			} );

			choicesJS.passedElement.element.addEventListener( 'addItem', function( event ) {
				if ( event.target.classList.contains( 'wpforms-file-upload-user-names-select' ) ) {
					choicesJS.hideDropdown();
					choicesJS.clearChoices();
				}
			} );
		},

		/**
		 * Get hidden values from a hidden input field.
		 *
		 * @since 1.9.4
		 *
		 * @param {number} fieldId   The ID of the field.
		 * @param {string} fieldName The name of the field.
		 *
		 * @return {Array} Array of selected values as strings.
		 */
		getHiddenValues( fieldId, fieldName ) {
			const $hidden = $( `#wpforms-field-${ fieldId }-${ fieldName }-select-multiple-options` ),
				value = $hidden.val();

			let selected;

			try {
				selected = JSON.parse( value );
			} catch ( e ) {
				selected = {};
			}

			selected = Object.values( selected );

			return selected.map( function( item ) {
				return item.toString();
			} );
		},

		/**
		 * Handle the media library option change event.
		 *
		 * @since 1.9.4
		 */
		mediaLibraryOptionHandler() {
			const option = $( this );

			const conditions = {
				mediaLibraryEnabled: option.prop( 'checked' ),
				accessRestrictionsEnabled: false,
				passwordRestrictionsEnabled: false,
			};

			app.optionsHandler( option, conditions );
		},

		/**
		 * Handle the access restrictions option change event.
		 *
		 * @since 1.9.4
		 */
		accessRestrictionsOptionHandler() {
			const option = $( this );

			const accessRestrictionsEnabled = option.prop( 'checked' );

			const conditions = {
				accessRestrictionsEnabled,
			};

			if ( accessRestrictionsEnabled ) {
				conditions.mediaLibraryEnabled = false;
			} else {
				conditions.passwordRestrictionsEnabled = false;
			}

			app.optionsHandler( option, conditions );
		},

		/**
		 * Handle the password restrictions option change event.
		 *
		 * @since 1.9.4
		 */
		passwordRestrictionsOptionHandler() {
			const option = $( this );

			const conditions = {
				passwordRestrictionsEnabled: option.prop( 'checked' ),
				accessRestrictionsEnabled: true,
			};

			if ( ! conditions.passwordRestrictionsEnabled ) {
				const fieldId = option.closest( '.wpforms-field-option' ).data( 'field-id' ),
					cleanButton = $( `#wpforms-field-option-${ fieldId }-password_restrictions_clean_button` );

				cleanButton.addClass( 'wpforms-hidden' );

				$( `#wpforms-field-option-${ fieldId }-protection_password` ).val( '' );
				$( `#wpforms-field-option-${ fieldId }-protection_password_confirm` ).val( '' );
			}

			app.optionsHandler( option, conditions );
		},

		/**
		 * Handle the option change event.
		 *
		 * @since 1.9.4
		 *
		 * @param {jQuery} option     The jQuery object representing the option element.
		 * @param {Object} conditions The conditions object.
		 */
		optionsHandler( option, conditions = {} ) {
			const fieldId = option.closest( '.wpforms-field-option' ).data( 'field-id' );

			$( `#wpforms-field-option-${ fieldId }-is_restricted` ).prop( 'checked', conditions.accessRestrictionsEnabled );
			$( `#wpforms-field-option-${ fieldId }-media_library` ).prop( 'checked', conditions.mediaLibraryEnabled );
			$( `#wpforms-field-option-${ fieldId }-is_protected` ).prop( 'checked', conditions.passwordRestrictionsEnabled );

			$( `#wpforms-field-option-row-${ fieldId }-user_restrictions` ).toggleClass( 'wpforms-hidden', ! conditions.accessRestrictionsEnabled );
			$( `#wpforms-field-option-row-${ fieldId }-password_restrictions` ).toggleClass( 'wpforms-hidden', ! conditions.accessRestrictionsEnabled );

			$( `#wpforms-field-option-row-${ fieldId }-user_roles_restrictions` ).toggleClass( 'wpforms-hidden', ! app.isLoggedInRestrictionSelected( fieldId ) || ! conditions.accessRestrictionsEnabled );
			$( `#wpforms-field-option-row-${ fieldId }-user_names_restrictions` ).toggleClass( 'wpforms-hidden', ! app.isLoggedInRestrictionSelected( fieldId ) || ! conditions.accessRestrictionsEnabled );

			$( `#wpforms-field-option-row-${ fieldId }-protection_password_label` ).toggleClass( 'wpforms-hidden', ! conditions.passwordRestrictionsEnabled );
			$( `#wpforms-field-option-row-${ fieldId }-protection_password_columns` ).toggleClass( 'wpforms-hidden', ! conditions.passwordRestrictionsEnabled );
		},

		/**
		 * Handle the user restrictions option change event.
		 *
		 * @since 1.9.4
		 */
		userRestrictionsOptionHandler() {
			const option = $( this ),
				selectedValue = option.val(),
				fieldId = option.closest( '.wpforms-field-option' ).data( 'field-id' );

			$( `#wpforms-field-option-row-${ fieldId }-user_roles_restrictions` ).toggleClass( 'wpforms-hidden', selectedValue !== 'logged' );
			$( `#wpforms-field-option-row-${ fieldId }-user_names_restrictions` ).toggleClass( 'wpforms-hidden', selectedValue !== 'logged' );
		},

		/**
		 * Check if the logged-in restriction is selected for the given field.
		 *
		 * @since 1.9.4
		 *
		 * @param {number} fieldId The ID of the field.
		 *
		 * @return {boolean}      True if the logged-in restriction is selected, false otherwise.
		 */
		isLoggedInRestrictionSelected( fieldId ) {
			return $( `#wpforms-field-option-${ fieldId }-user_restrictions` ).val() === 'logged';
		},

		/**
		 * Handle the password field keyup event to show or hide the clean button.
		 *
		 * @since 1.9.4
		 */
		cleanPasswordButtonHandler() {
			const passwordField = $( this ),
				password = passwordField.val();

			const wrapper = passwordField.closest( '.wpforms-field-option-row' ),
				cleanButton = wrapper.find( '.wpforms-file-upload-password-clean' );

			cleanButton.toggleClass( 'wpforms-hidden', password.length < 1 );
		},

		/**
		 * Sanitize the password value.
		 *
		 * @since 1.9.4
		 */
		sanitizePasswordValue() {
			const passwordField = $( this ),
				fieldId = passwordField.closest( '.wpforms-field-option' ).data( 'field-id' ),
				password = passwordField.val(),
				sanitizedPassword = wpf.sanitizeHTML( password );

			$( `#wpforms-field-option-${ fieldId }-protection_password_confirm` ).attr( 'type', password !== sanitizedPassword ? 'text' : 'password' );

			passwordField
				.attr( 'type', password !== sanitizedPassword ? 'text' : 'password' )
				.val( sanitizedPassword );
		},

		/**
		 * Check if the password and confirm password fields match.
		 *
		 * @since 1.9.4
		 */
		checkPasswordMatch() {
			const confirmPasswordField = $( this ),
				confirmPassword = confirmPasswordField.val(),
				fieldId = confirmPasswordField.closest( '.wpforms-field-option' ).data( 'field-id' ),
				password = $( `#wpforms-field-option-${ fieldId }-protection_password` ).val(),
				errorMessage = $( `#wpforms-field-option-row-${ fieldId }-protection_password_confirm_error` );

			confirmPasswordField.removeClass( 'wpforms-error' );
			errorMessage.addClass( 'wpforms-hidden' );

			if ( confirmPassword !== password ) {
				confirmPasswordField.addClass( 'wpforms-error' );
				errorMessage.removeClass( 'wpforms-hidden' );
			}
		},

		/**
		 * Check if there are any file upload fields with restrictions enabled in notifications before saving.
		 * Display an alert if there are any.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event} event The event object.
		 */
		checkNotificationsBeforeSave( event ) {
			const fileUploadOptions = $( '.wpforms-field-option-file-upload, .wpforms-field-option-camera' );

			if ( fileUploadOptions.length < 1 ) {
				return;
			}

			const fieldsLabels = [];

			const fields = app.getNotificationsFileUploadFields();

			fileUploadOptions.each( function() {
				const fieldId = $( this ).data( 'field-id' ),
					restrictionEnabled = $( `#wpforms-field-option-${ fieldId }-is_restricted` ).prop( 'checked' );

				if ( ! restrictionEnabled ) {
					return;
				}

				if ( fields.includes( parseInt( fieldId, 10 ) ) ) {
					fieldsLabels.push( $( `#wpforms-field-option-${ fieldId }-label` ).val() || `${ wpforms_builder.empty_label_alternative_text }${ fieldId }` );
				}
			} );

			if ( fieldsLabels.length ) {
				event.preventDefault();

				app.displayNotificationRestrictionAlert( fieldsLabels );
			}
		},

		/**
		 * Check if the password and confirm password fields match before saving.
		 * Display an alert if they don't match.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event} event The event object.
		 */
		checkPasswordMatchBeforeSave( event ) {
			const fileUploadOptions = $( '.wpforms-field-option-file-upload, .wpforms-field-option-camera' );

			if ( fileUploadOptions.length < 1 ) {
				return;
			}

			const fieldsLabels = [];
			const emptyFieldsLabels = [];

			fileUploadOptions.each( function() {
				const fieldId = $( this ).data( 'field-id' ),
					protectedEnabled = $( `#wpforms-field-option-${ fieldId }-is_protected` ).prop( 'checked' );

				if ( ! protectedEnabled ) {
					return;
				}

				const password = $( `#wpforms-field-option-${ fieldId }-protection_password` ).val(),
					confirmPasswordField = $( `#wpforms-field-option-${ fieldId }-protection_password_confirm` ),
					confirmPassword = confirmPasswordField.val(),
					label = $( `#wpforms-field-option-${ fieldId }-label` ).val() || `${ wpforms_builder.empty_label_alternative_text }${ fieldId }`;

				if ( password !== confirmPassword ) {
					fieldsLabels.push( label );

					confirmPasswordField.toggleClass( 'wpforms-error', true );
					$( `#wpforms-field-option-row-${ fieldId }-protection_password_confirm_error` ).removeClass( 'wpforms-hidden' );
				}

				if ( password === '' && confirmPassword === '' ) {
					emptyFieldsLabels.push( label );
				}
			} );

			if ( emptyFieldsLabels.length ) {
				event.preventDefault();

				app.displayEmptyPasswordAlert( emptyFieldsLabels );

				return;
			}

			if ( fieldsLabels.length ) {
				event.preventDefault();

				app.displayNotMatchPasswordAlert( fieldsLabels );
			}
		},

		/**
		 * Display an alert for fields with non-matching passwords.
		 *
		 * @since 1.9.4
		 *
		 * @param {Array} fieldsLabels Array of field labels with non-matching passwords.
		 */
		displayNotMatchPasswordAlert( fieldsLabels ) {
			const fieldsLabelsText = fieldsLabels.join( ', ' );
			const errorMessage = wpforms_builder.file_upload.password_match_error_text.replace( '{fields}', fieldsLabelsText );

			app.displayAlert( wpforms_builder.file_upload.password_match_error_title, errorMessage );
		},

		/**
		 * Display an alert for fields with empty passwords.
		 *
		 * @since 1.9.4
		 *
		 * @param {Array} fieldsLabels Array of field labels with empty passwords.
		 */
		displayEmptyPasswordAlert( fieldsLabels ) {
			const fieldsLabelsText = fieldsLabels.join( ', ' );
			const errorMessage = wpforms_builder.file_upload.password_empty_error_text.replace( '{fields}', fieldsLabelsText );

			app.displayAlert( wpforms_builder.file_upload.password_empty_error_title, errorMessage );
		},

		/**
		 * Display an alert for fields with notification restrictions.
		 *
		 * @since 1.9.4
		 *
		 * @param {Array} fieldsLabels Array of field labels with notification restrictions.
		 */
		displayNotificationRestrictionAlert( fieldsLabels ) {
			const fieldsLabelsText = fieldsLabels.join( ', ' );
			const errorMessage = wpforms_builder.file_upload.notification_error_text.replace( '{fields}', fieldsLabelsText );

			app.displayAlert( wpforms_builder.file_upload.notification_error_title, errorMessage );
		},

		/**
		 * Display an alert with the given title and content.
		 *
		 * @since 1.9.4
		 *
		 * @param {string} title   The title of the alert.
		 * @param {string} content The content of the alert.
		 */
		displayAlert( title, content ) {
			$.alert( {
				title,
				content,
				type: 'red',
				icon: 'fa fa-exclamation-triangle',
				buttons: {
					confirm: {
						text: wpforms_builder.close,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * Handle the clean password input event.
		 *
		 * @since 1.9.4
		 */
		cleanPasswordInput() {
			const cleanButton = $( this ),
				fieldId = cleanButton.data( 'field-id' );

			$( `#wpforms-field-option-${ fieldId }-protection_password` ).val( '' );
			$( `#wpforms-field-option-${ fieldId }-protection_password_confirm` ).val( '' ).removeClass( 'wpforms-error' );

			$( `#wpforms-field-option-row-${ fieldId }-protection_password_confirm_error` ).addClass( 'wpforms-hidden' );

			cleanButton.addClass( 'wpforms-hidden' );
		},

		/**
		 * Disable restrictions for file upload fields in notifications.
		 *
		 * @since 1.9.4
		 */
		disableRestrictions() {
			const fields = app.getNotificationsFileUploadFields();

			if ( ! fields.length ) {
				return;
			}

			fields.forEach( function( fieldId ) {
				app.disallowRestriction( fieldId );
			} );
		},

		/**
		 * Disallow restrictions for a specific field.
		 *
		 * @since 1.9.4
		 *
		 * @param {number} fieldId The ID of the field.
		 */
		disallowRestriction( fieldId ) {
			if ( ! $( `#wpforms-field-option-${ fieldId }-is_restricted` ).prop( 'checked' ) ) {
				$( `#wpforms-field-option-row-${ fieldId }-access_restrictions` ).addClass( 'wpforms-notifications-disabled-option' );
			}
		},

		/**
		 * Allow restrictions for a specific field.
		 *
		 * @since 1.9.4
		 *
		 * @param {number} fieldId The ID of the field.
		 */
		allowRestriction( fieldId ) {
			$( `#wpforms-field-option-row-${ fieldId }-access_restrictions` ).removeClass( 'wpforms-notifications-disabled-option' );
		},

		/**
		 * Handle the event when a file upload field is added to notifications.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event}  event   The event object.
		 * @param {number} fieldId The ID of the field.
		 * @param {Object} element The element being added.
		 */
		notificationFileUploadFieldAdded( event, fieldId, element ) {
			if ( ! app.isFileUploadAttachmentField( element ) ) {
				return;
			}

			app.disallowRestriction( fieldId );
		},

		/**
		 * Handle the event when a file upload field is removed from notifications.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event}  event   The event object.
		 * @param {number} fieldId The ID of the field.
		 * @param {Object} element The element being removed.
		 */
		notificationFileUploadFieldRemoved( event, fieldId, element ) {
			if ( ! app.isFileUploadAttachmentField( element ) ) {
				return;
			}

			setTimeout( function() {
				const fields = app.getNotificationsFileUploadFields();

				if ( fields.includes( parseInt( fieldId, 10 ) ) ) {
					return;
				}

				app.allowRestriction( fieldId );
			}, 0 );
		},

		/**
		 * Handle the conditional change event for notifications.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event} event   The event object.
		 * @param {Array} choices Array of choice objects.
		 */
		notificationToggleConditionalChange( event, choices ) {
			const fields = app.getNotificationsFileUploadFields(),
				choicesIds = choices.map( ( choice ) => choice.value );

			app.handleRestrictions( fields, choicesIds );
		},

		/**
		 * Toggle notifications.
		 *
		 * @since 1.9.4
		 */
		notificationsToggle() {
			const choicesIds = WPForms.Admin.Builder.Notifications.choicesJSHelperMethods.getFormFields( [ 'file-upload', 'camera' ] ).map( ( choice ) => choice.value ),
				fields = app.getNotificationsFileUploadFields();

			app.handleRestrictions( fields, choicesIds );
		},

		/**
		 * Handle restrictions for file upload fields in notifications.
		 *
		 * @since 1.9.4
		 *
		 * @param {Array} fields     Array of file upload field IDs.
		 * @param {Array} choicesIds Array of choice IDs.
		 */
		handleRestrictions( fields, choicesIds ) {
			setTimeout( function() {
				choicesIds.forEach( function( fieldId ) {
					if ( fields.includes( parseInt( fieldId, 10 ) ) ) {
						app.disallowRestriction( fieldId );

						return;
					}

					app.allowRestriction( fieldId );
				} );
			}, 0 );
		},

		/**
		 * Check if the given field is a file upload attachment field.
		 *
		 * @since 1.9.4
		 *
		 * @param {Object} field The field to check.
		 *
		 * @return {boolean} True if the field is a file upload attachment field, false otherwise.
		 */
		isFileUploadAttachmentField( field ) {
			return $( field ).hasClass( 'file_upload_attachment_fields' );
		},

		/**
		 * Get all notifications.
		 *
		 * @since 1.9.4
		 *
		 * @return {Array|jQuery} Array of notification elements.
		 */
		getAllNotifications() {
			const isNotificationsEnabled = $( '#wpforms-panel-field-settings-notification_enable' ).prop( 'checked' );

			if ( ! isNotificationsEnabled ) {
				return [];
			}

			return $( '.wpforms-notification' ) || [];
		},

		/**
		 * Get all file upload fields in notifications.
		 *
		 * @since 1.9.4
		 *
		 * @return {Array} Array of file upload field IDs.
		 */
		getNotificationsFileUploadFields() {
			const fields = [];

			const $notifications = app.getAllNotifications();

			if ( ! $notifications.length ) {
				return fields;
			}

			$notifications.each( function() {
				const notificationId = $( this ).data( 'block-id' );
				const isFileUploadAttachmentEnabled = $( `#wpforms-panel-field-notifications-${ notificationId }-file_upload_attachment_enable` ).prop( 'checked' );

				if ( ! isFileUploadAttachmentEnabled ) {
					return;
				}

				const fileUploadFields = $( `input[name="settings[notifications][${ notificationId }][file_upload_attachment_fields][hidden]"]` ).val();

				let notificationFields;

				try {
					notificationFields = JSON.parse( fileUploadFields );
				} catch ( e ) {
					notificationFields = [];
				}

				if ( ! notificationFields.length ) {
					return;
				}

				notificationFields.forEach( function( fieldId ) {
					fields.push( fieldId );
				} );
			} );

			// Filter out duplicate field IDs.
			// Because the same field can appear in different notifications.
			return fields.filter( ( value, index, self ) => self.indexOf( value ) === index );
		},

		/**
		 * Display a notification alert.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event} event The event object.
		 */
		displayNotificationAlert( event ) {
			event.preventDefault();

			$.alert( {
				title: wpforms_builder.file_upload.notification_warning_title,
				content: wpforms_builder.file_upload.notification_warning_text,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * Display an alert Post Submissions need to be updated.
		 *
		 * @since 1.9.4
		 *
		 * @param {Event} event The event object.
		 */
		displayPostSubmissionsDisabledAlert( event ) {
			event.preventDefault();

			$.alert( {
				title: wpforms_education.addon_incompatible.title,
				content: wpforms_builder.file_upload.incompatible_addon_text,
				icon: 'fa fa-exclamation-circle',
				type: 'red',
				buttons: {
					confirm: {
						text: wpforms_education.addon_incompatible.button_text,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action() {
							if ( typeof WPFormsBuilder === 'undefined' ) {
								location.href = wpforms_education.addon_incompatible.button_url;

								return false;
							}

							this.$$confirm
								.prop( 'disabled', true )
								.html( spinner + this.$$confirm.text() );

							this.$$cancel
								.prop( 'disabled', true );

							if ( WPFormsBuilder.formIsSaved() ) {
								location.href = wpforms_education.addon_incompatible.button_url;

								return false;
							}

							const saveForm = WPFormsBuilder.formSave( false );

							if ( ! saveForm ) {
								return false;
							}

							saveForm.done( function() {
								location.href = wpforms_education.addon_incompatible.button_url;
							} );

							return false;
						},
					},
					cancel: {
						text: wpforms_education.cancel,
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

WPForms.Admin.Builder.FieldFileUpload.init();
