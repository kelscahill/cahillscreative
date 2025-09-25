/**
 * WPForms Page Break Field Builder Script
 *
 * @since 1.9.7
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldPageBreak = WPForms.Admin.Builder.FieldPageBreak || ( function( document, window, $ ) {
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.7
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.9.7
		 */
		ready() {
			app.events();
		},

		/**
		 * Add handlers on events.
		 *
		 * @since 1.9.7
		 */
		events() {
			$( '#wpforms-builder' ).on( 'change', '.wpforms-pagebreak-progress-indicator', app.handlePageBreakProgressIndicatorChange );
		},

		/**
		 * Handle change event for the page break progress indicator.
		 *
		 * @since 1.9.7
		 *
		 * @param {Event} event The change event.
		 */
		handlePageBreakProgressIndicatorChange( event ) {
			const field = $( event.target ).closest( '.wpforms-field-option-row' ),
				fieldId = field.data( 'field-id' );

			$( `#wpforms-field-option-row-${ fieldId }-progress_text` ).toggleClass( 'wpforms-hidden', event.target.value !== 'progress' );
		},
	};

	return app;
}( document, window, jQuery ) );

WPForms.Admin.Builder.FieldPageBreak.init();
