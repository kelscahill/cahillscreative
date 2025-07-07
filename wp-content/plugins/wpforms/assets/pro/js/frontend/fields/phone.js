/* global wpforms_settings */

/**
 * Phone field.
 *
 * @since 1.9.4
 */
window.WPFormsPhoneField = window.WPFormsPhoneField || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.4
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Initialize application.
		 *
		 * @since 1.9.4
		 */
		init() {
			$( document ).on( 'wpformsReady', app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.9.4
		 */
		ready() {
			app.loadValidation();
			app.loadSmartField();
			app.bindSmartField();

			$( '.wpforms-smart-phone-field' ).each( function() {
				app.repairSmartHiddenField( $( this ) );
			} );
		},

		/**
		 * Load jQuery Validation for Smartphone field.
		 *
		 * @since 1.9.4
		 */
		loadValidation() {
			// Load if jQuery validation library exists.
			if ( typeof $.fn.validate === 'undefined' ) {
				return;
			}

			// Validate US Phone Field.
			$.validator.addMethod( 'us-phone-field', function( value, element ) {
				if ( value.match( /[^\d()\-+\s]/ ) ) {
					return false;
				}
				return this.optional( element ) || value.replace( /\D/g, '' ).length === 10;
			}, wpforms_settings.val_phone );

			// Validate International Phone Field.
			$.validator.addMethod( 'int-phone-field', function( value, element ) {
				if ( value.match( /[^\d()\-+\s]/ ) ) {
					return false;
				}
				return this.optional( element ) || value.replace( /\D/g, '' ).length > 0;
			}, wpforms_settings.val_phone );

			// And the intlTelInput library is loaded.
			if ( typeof window.intlTelInput === 'undefined' ) {
				return;
			}

			// Validate Smartphone Field.
			$.validator.addMethod( 'smart-phone-field', function( value, element ) {
				if ( value.match( /[^\d()\-+\s]/ ) ) {
					return false;
				}

				const iti = window.intlTelInput?.getInstance( element );
				const result = $( element ).triggerHandler( 'validate' );

				return this.optional( element ) || iti?.isValidNumberPrecise() || result;
			}, wpforms_settings.val_phone );
		},

		/**
		 * Load Smartphone fields.
		 *
		 * @since 1.9.4
		 *
		 * @param {jQuery} $context Context to search for smartphone elements.
		 */
		loadSmartField( $context = null ) {
			if ( typeof window.intlTelInput === 'undefined' ) {
				// Only load if a library exists.
				return;
			}

			app.loadJqueryIntlTelInput();

			$context = $context?.length ? $context : $( document );

			$context.find( '.wpforms-smart-phone-field' ).each( function( i, el ) {
				const $el = $( el );

				// Prevent initialization if the popup is hidden.
				if ( $el.parents( '.elementor-location-popup' ).is( ':hidden' ) ) {
					return false;
				}

				app.initSmartField( $el, {} );
			} );
		},

		/**
		 * Backward compatibility jQuery plugin for IntlTelInput library, to support custom snippets.
		 * e.g., https://wpforms.com/developers/how-to-set-a-default-flag-on-smart-phone-field-with-gdpr/.
		 *
		 * @since 1.9.4
		 */
		loadJqueryIntlTelInput() {
			if ( typeof $.fn.intlTelInput !== 'undefined' ) {
				return;
			}

			$.fn.extend( {
				intlTelInput( options ) {
					const $el = $( this );
					if ( options === undefined || typeof options === 'object' ) {

						// Phone library stopped supporting preferredCountries with version 25.3.1.
						// They suggest to use countryOrder instead.
						if ( options.preferredCountries ) {
							options.countryOrder = options.preferredCountries;
						}

						return $el.each( function() {
							const $item = $( this );

							app.initSmartField( $item, options );
						} );
					}

					if ( typeof options !== 'string' && options[ 0 ] === '_' ) {
						return;
					}

					const methodName = options;
					let returns = this;

					$el.each( function() {
						const $phone = $( this );
						const iti = $phone.data( 'plugin_intlTelInput' );

						if ( typeof iti[ methodName ] !== 'function' ) {
							return;
						}

						// IntlTelInput library returned only the last applied method instance in v21.0-
						returns = iti[ methodName ]();

						if ( options === 'destroy' ) {
							$phone.removeData( 'plugin_intlTelInput' );
						}
					} );

					return returns;
				},
			} );
		},

		/**
		 * Initialize Smartphone field.
		 *
		 * @since 1.9.4
		 *
		 * @param {jQuery} $el          Input field.
		 * @param {Object} inputOptions Options for intlTelInput.
		 */
		initSmartField( $el, inputOptions ) {
			if ( typeof $el.data( 'plugin_intlTelInput' ) === 'object' ) {
				// Skip if it was already initialized.
				return;
			}

			inputOptions = Object.keys( inputOptions ).length > 0 ? inputOptions : app.getDefaultSmartFieldOptions();

			const fieldId = $el.closest( '.wpforms-field-phone' ).data( 'field-id' );

			// For proper validation, we should preserve the name attribute of the input field.
			// But we need to modify the original input name not to interfere with a hidden input.
			$el.attr( 'name', 'wpf-temp-wpforms[fields][' + fieldId + ']' );

			// Add special class to remove name attribute before submitting.
			// So, only the hidden input value will be submitted.
			$el.addClass( 'wpforms-input-temp-name' );

			// Hidden input allows to include country code into submitted data.
			inputOptions.hiddenInput = function() {
				return {
					phone: 'wpforms[fields][' + fieldId + ']',
				};
			};

			const iti = window.intlTelInput( $el.get( 0 ), inputOptions );

			$el.on( 'validate', function() {
				// Validate the field.
				return iti.isValidNumber( iti.getNumber() );
			} );

			$el.data( 'plugin_intlTelInput', iti );

			// Instantly update a hidden form input.
			// Validation is done separately, so we shouldn't worry about it.
			// Previously "blur" only was used, which is broken in case Enter was used to submit the form.
			const updateHiddenInput = function() {
				const itiPlugin = $el.data( 'plugin_intlTelInput' );
				$el.siblings( 'input[type="hidden"]' ).val( itiPlugin.getNumber() );
			};

			$el.on( 'blur input', updateHiddenInput );

			$( document ).ready( updateHiddenInput );
		},

		/**
		 * Bind Smartphone field event.
		 *
		 * @since 1.9.4
		 */
		bindSmartField() {
			$( '.wpforms-form' ).on( 'wpformsBeforeFormSubmit', function() {
				const $smartPhoneFields = $( this ).find( '.wpforms-smart-phone-field' );

				$smartPhoneFields.each( function() {
					app.repairSmartHiddenField( $( this ) );
				} );

				// Update hidden input of the `Smart` phone field to be sure the latest value will be submitted.
				$smartPhoneFields.trigger( 'input' );
			} );
		},

		/**
		 * Compatibility fix with an old intl-tel-input library that may include in other addons.
		 * Also, for custom snippets that use `options.hiddenInput` to receive fieldId.
		 *
		 * @since 1.9.4
		 *
		 * @param {jQuery} $field Phone field element.
		 */
		repairSmartHiddenField( $field ) {
			const fieldId = $field.closest( '.wpforms-field-phone' ).data( 'field-id' );

			if ( $( '[name="wpforms[fields][' + fieldId + ']"]' ).length ) {
				return;
			}

			const iti = $field.data( 'plugin_intlTelInput' );
			let fieldValue = $field.val();
			let inputOptions = {};

			if ( iti ) {
				inputOptions = iti.d || iti.options || {};
				fieldValue = iti.getNumber();

				iti.destroy();
			}

			$field.removeData( 'plugin_intlTelInput' );

			// The field has beautified view. We should use hidden input value before destroying.
			$field.val( fieldValue );

			app.initSmartField( $field, inputOptions );
		},

		/**
		 * Get a list of default smartphone field options.
		 *
		 * @since 1.9.4
		 *
		 * @return {Object} List of default options.
		 */
		getDefaultSmartFieldOptions() { // eslint-disable-line complexity
			const inputOptions = {
				countrySearch: false,
				fixDropdownWidth: false,
				countryOrder: [ 'us', 'gb' ],
				countryListAriaLabel: wpforms_settings.country_list_label,
				validationNumberTypes: [ 'FIXED_LINE_OR_MOBILE' ],
			};

			// Determine the country by IP if no GDPR restrictions enabled.
			if ( ! wpforms_settings.gdpr ) {
				inputOptions.geoIpLookup = app.currentIpToCountry;
			}

			let countryCode;
			// Try to kick in an alternative solution if GDPR restrictions are enabled.
			if ( wpforms_settings.gdpr ) {
				const lang = app.mapLanguageToIso( app.getFirstBrowserLanguage() );

				countryCode = lang.indexOf( '-' ) > -1 ? lang.split( '-' ).pop() : lang;
			}

			// Make sure the library recognizes browser country code to avoid console error.
			if ( countryCode ) {
				let countryData = window.intlTelInput?.getCountryData();

				countryData = countryData.filter( function( country ) {
					return country.iso2 === countryCode.toLowerCase();
				} );
				countryCode = countryData.length ? countryCode : '';
			}

			// Set default country.
			inputOptions.initialCountry = wpforms_settings.gdpr && countryCode ? countryCode.toLowerCase() : 'auto';

			return inputOptions;
		},

		/**
		 * Get user browser preferred language.
		 *
		 * @since 1.9.4
		 *
		 * @return {string} Language code.
		 */
		getFirstBrowserLanguage() { // eslint-disable-line complexity
			const nav = window.navigator;
			const browserLanguagePropertyKeys = [ 'language', 'browserLanguage', 'systemLanguage', 'userLanguage' ];
			let i, language;

			// Support for HTML 5.1 "navigator.languages".
			if ( Array.isArray( nav.languages ) ) {
				for ( i = 0; i < nav.languages.length; i++ ) {
					language = nav.languages[ i ];
					if ( language && language.length ) {
						return language;
					}
				}
			}

			// Support for other well-known properties in browsers.
			for ( i = 0; i < browserLanguagePropertyKeys.length; i++ ) {
				language = nav[ browserLanguagePropertyKeys[ i ] ];
				if ( language && language.length ) {
					return language;
				}
			}

			return '';
		},

		/**
		 * Function maps lang code like `el` to `el-GR`.
		 *
		 * @since 1.9.4
		 *
		 * @param {string} lang Language code.
		 *
		 * @return {string} Language code with ISO.
		 */
		mapLanguageToIso( lang ) {
			const langMap = {
				ar: 'ar-SA',
				bg: 'bg-BG',
				ca: 'ca-ES',
				cs: 'cs-CZ',
				da: 'da-DK',
				de: 'de-DE',
				el: 'el-GR',
				en: 'en-US',
				es: 'es-ES',
				fi: 'fi-FI',
				fr: 'fr-FR',
				he: 'he-IL',
				hi: 'hi-IN',
				hr: 'hr-HR',
				hu: 'hu-HU',
				id: 'id-ID',
				it: 'it-IT',
				ja: 'ja-JP',
				ko: 'ko-KR',
				lt: 'lt-LT',
				lv: 'lv-LV',
				ms: 'ms-MY',
				nl: 'nl-NL',
				no: 'nb-NO',
				pl: 'pl-PL',
				pt: 'pt-PT',
				ro: 'ro-RO',
				ru: 'ru-RU',
				sk: 'sk-SK',
				sl: 'sl-SI',
				sr: 'sr-RS',
				sv: 'sv-SE',
				th: 'th-TH',
				tr: 'tr-TR',
				uk: 'uk-UA',
				vi: 'vi-VN',
				zh: 'zh-CN',
			};

			return langMap[ lang ] || lang;
		},

		/**
		 * Asynchronously fetches country code using current IP
		 * and executes a callback provided with a country code parameter.
		 *
		 * @since 1.9.4
		 *
		 * @param {Function} callback Executes once the fetch is completed.
		 */
		currentIpToCountry( callback ) {
			if ( wpforms_settings.country ) {
				callback( wpforms_settings.country );
				return;
			}

			const fallback = function() {
				$.get( 'https://ipapi.co/jsonp', function() {}, 'jsonp' )
					.always( function( resp ) {
						let countryCode = resp?.country ? resp.country : '';

						if ( ! countryCode ) {
							const lang = app.getFirstBrowserLanguage();
							countryCode = lang.indexOf( '-' ) > -1 ? lang.split( '-' ).pop() : '';
						}

						callback( countryCode );
					} );
			};

			$.get( 'https://geo.wpforms.com/v3/geolocate/json' )
				.done( function( resp ) {
					if ( resp && resp.country_iso ) {
						callback( resp.country_iso );
					} else {
						fallback();
					}
				} )
				.fail( function() {
					fallback();
				} );
		},

	};

	return app;
}( document, window, jQuery ) );

// Initialize.
window.WPFormsPhoneField.init();
