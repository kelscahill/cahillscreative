/* global pwsL10n */

/**
 * Password field.
 *
 * @since 1.6.7
 */

const WPFormsPasswordField = window.WPFormsPasswordField || ( function( document, window, $ ) {
	const app = {

		init() {
			$( app.ready );
		},

		ready() {
			app.bindEvents();
		},

		/**
		 * Bind events.
		 *
		 * @since 1.9.8
		 */
		bindEvents() {
			$( '.wpforms-field-password-input-icon' )
				.on( 'click', app.togglePasswordVisibility );

			$( document ).on( 'wpformsRepeaterFieldCloneCreated', ( e, $clone ) => {
				$( $clone ).find( '.wpforms-field-password-input-icon' )
					.on( 'click', app.togglePasswordVisibility );
			} );

			window.addEventListener( 'elementor/popup/show', () => {
				$( '.wpforms-field-password-input-icon' )
					.on( 'click', app.togglePasswordVisibility );
			} );
		},

		/**
		 * Toggle password field visibility between masked and plaintext.
		 *
		 * @since 1.9.8
		 *
		 * @param {Event} e Event.
		 */
		togglePasswordVisibility( e ) {
			e.preventDefault();

			const $icon = $( this );
			const $inputWrapper = $icon.closest( '.wpforms-field-password-input' );
			const $input = $inputWrapper.find( 'input' );
			const isVisible = $input.attr( 'type' ) === 'text';
			const inputType = isVisible ? 'password' : 'text';
			const $visibleIcon = $inputWrapper.find( '.wpforms-field-password-input-icon-visible' );
			const $invisibleIcon = $inputWrapper.find( '.wpforms-field-password-input-icon-invisible' );

			$visibleIcon.toggle( ! isVisible );
			$invisibleIcon.toggle( isVisible );
			$input.attr( 'type', inputType );

			const title = $icon.attr( 'title' );
			const switchTitle = $icon.attr( 'data-switch-title' );

			$icon.attr( {
				title: switchTitle,
				'aria-label': switchTitle,
				'data-switch-title': title,
			} );
		},

		/**
		 * Toggle the hide message depending on if user hiding a from.
		 *
		 * @since 1.6.7
		 *
		 * @param {string} value   Password value.
		 * @param {Object} element Password field.
		 *
		 * @return {number} Strength result.
		 */
		// eslint-disable-next-line complexity
		passwordStrength( value, element ) {
			const $input = $( element );
			const $field = $input.closest( '.wpforms-field' );
			let $strengthResult = $field.find( '.wpforms-pass-strength-result' );

			// Don't check the password strength for empty fields which is set as not required.
			if ( $input.val().trim() === '' && ! $input.hasClass( 'wpforms-field-required' ) ) {
				$strengthResult.remove();
				$input.removeClass( 'wpforms-error-pass-strength' );

				return 0;
			}

			if ( ! $strengthResult.length ) {
				// language=HTML
				$strengthResult = $( '<div class="wpforms-pass-strength-result"></div>' );
				$strengthResult.css( 'max-width', $input.css( 'max-width' ) );
			}

			$strengthResult.removeClass( 'short bad good strong empty' );

			if ( ! value || value.trim() === '' ) {
				$strengthResult.remove();
				$input.removeClass( 'wpforms-error-pass-strength' );

				return 0;
			}

			// noinspection JSDeprecatedSymbols
			const disallowedList = Object.prototype.hasOwnProperty.call( wp.passwordStrength, 'userInputDisallowedList' )
				? wp.passwordStrength.userInputDisallowedList()
				: wp.passwordStrength.userInputBlacklist();

			const strength = wp.passwordStrength.meter( value, disallowedList, value );

			$strengthResult = app.updateStrengthResultEl( $strengthResult, strength );

			$strengthResult.insertAfter( $input );
			$input.addClass( 'wpforms-error-pass-strength' );

			return strength;
		},

		/**
		 * Update a strength result element to show the current result strength.
		 *
		 * @since 1.6.7
		 *
		 * @param {jQuery} $strengthResult Strength result element.
		 * @param {number} strength        Strength result number.
		 *
		 * @return {jQuery} Modified strength result element.
		 */
		updateStrengthResultEl( $strengthResult, strength ) {
			switch ( strength ) {
				case -1:
					$strengthResult.addClass( 'bad' ).html( pwsL10n.unknown );
					break;
				case 2:
					$strengthResult.addClass( 'bad' ).html( pwsL10n.bad );
					break;
				case 3:
					$strengthResult.addClass( 'good' ).html( pwsL10n.good );
					break;
				case 4:
					$strengthResult.addClass( 'strong' ).html( pwsL10n.strong );
					break;
				default:
					$strengthResult.addClass( 'short' ).html( pwsL10n.short );
			}

			return $strengthResult;
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

WPFormsPasswordField.init();
