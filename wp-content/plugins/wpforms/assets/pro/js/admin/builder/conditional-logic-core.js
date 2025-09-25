/* global wpf, wpforms_builder */

// noinspection ES6ConvertVarToLetConst
var WPFormsConditionals = window.WPFormsConditionals || ( function( document, window, $ ) { // eslint-disable-line no-var
	/**
	 * Helper methods.
	 *
	 * @since 1.6.0.2
	 */
	const helpers = {

		/**
		 * Splits an array to chunks of n elements.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Array}  array Array to split.
		 * @param {number} n     Number of elements in each chunk.
		 *
		 * @return {Array} Array.
		 */
		arraySplitIntoChunks( array, n ) {
			if ( ! array.length ) {
				return [];
			}

			return [ array.slice( 0, n ) ]
				.concat( helpers.arraySplitIntoChunks( array.slice( n ), n ) );
		},
	};

	/**
	 * Conditional rules updating methods.
	 *
	 * @since 1.6.0.2
	 */
	const updater = {

		/**
		 * All form fields.
		 *
		 * @since 1.6.0.2
		 */
		allFields: {},

		/**
		 * Conditional rule rows.
		 *
		 * @since 1.6.0.2
		 */
		$ruleRows: {},

		/**
		 * Form fields supporting conditional logic.
		 *
		 * @since 1.6.0.2
		 */
		conditionalFields: {},

		/**
		 * HTML template containing a list of <option> elements representing available conditional fields.
		 *
		 * @since 1.6.0.2
		 */
		fieldsListTemplate: '',

		/**
		 * HTML templates containing a list of <option> elements representing values of every conditional field.
		 *
		 * @since 1.6.0.2
		 */
		fieldValuesListTemplates: {},

		/**
		 * Cache all form fields.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Array} allFields List of all fields.
		 */
		cacheAllFields( allFields ) {
			updater.allFields = allFields;
		},

		/**
		 * Cache all rule rows.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {jQuery} $rows Collection of all conditional rule rows.
		 */
		cacheRuleRows( $rows ) {
			updater.$ruleRows = $rows || $( '.wpforms-conditional-row' );
		},

		/**
		 * Cache allowed form fields supporting conditional logic.
		 *
		 * @since 1.6.0.2
		 */
		setConditionalFields() {
			updater.conditionalFields = updater.removeUnsupportedFields();
		},

		/**
		 * Remove field types that are not allowed and whitelisted.
		 *
		 * @since 1.6.0.2
		 *
		 * @return {Object} Filtered list of fields.
		 */
		removeUnsupportedFields() {
			const allowed = wpforms_builder.cl_fields_supported;

			let fields = { ...updater.allFields };

			/**
			 * Filter the fields list before removing unsupported field types.
			 *
			 * @since 1.8.9
			 *
			 * @param {Object} fields Fields data.
			 *
			 * @return {Object} Filtered fields data.
			 */
			fields = wp.hooks.applyFilters( 'wpforms.ConditionalLogicCore.BeforeRemoveUnsupportedFields', fields );

			Object.keys( fields ).forEach( ( key ) => {
				if ( ! allowed.includes( fields[ key ].type ) || fields[ key ].dynamic_choices ) {
					delete fields[ key ];
				}
			} );

			return fields;
		},

		/**
		 * Set up all HTML templates.
		 *
		 * @since 1.6.0.2
		 */
		setTemplates() {
			updater.setFieldsListTemplate();

			// Reset cached field values templates before processing.
			updater.fieldValuesListTemplates = {};
		},

		/**
		 * Return an HTML template for a select with all the field names.
		 *
		 * Doing a jQuery-DOM and copying the underlying HTML makes rendering
		 * twice as fast.
		 *
		 * @since 1.6.0.2
		 */
		setFieldsListTemplate() {
			// language=HTML
			const $fieldsListTemplate = $( '<select>' )
				.append( $( '<option>', { value: '', text: wpforms_builder.select_field } ) );

			for ( const key in updater.conditionalFields ) {
				const field = updater.conditionalFields[ key ];
				let label;

				if ( typeof field.label !== 'undefined' && field.label.toString().trim() !== '' ) {
					label = wpf.sanitizeHTML( field.label.toString().trim() );
				} else {
					label = wpforms_builder.field + ' #' + field.id;
				}

				$fieldsListTemplate.append( $( '<option>', {
					value: field.id,
					text : label,
					id   : 'option-' + field.id,
				} ) );
			}

			updater.fieldsListTemplate = $fieldsListTemplate.html();
		},

		/**
		 * Return an HTML with a list of options from a given field.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Array}  fields        Array of fields.
		 * @param {number} fieldSelected ID of selected field.
		 *
		 * @return {string} HTML template.
		 */
		getFieldValuesListTemplate( fields, fieldSelected ) {
			// Return the cached template if possible.
			if ( updater.fieldValuesListTemplates[ fieldSelected ] ) {
				return updater.fieldValuesListTemplates[ fieldSelected ];
			}

			const items = wpf.orders.choices[ 'field_' + fieldSelected ],
				// language=HTML
				$select = $( '<select>' ),
				field = Object.values( wpf.getFields() ).find( ( el ) => el.id.toString() === fieldSelected.toString() );

			for ( const key in items ) {
				const choiceKey = items[ key ],
					label = typeof field.choices[ choiceKey ] !== 'undefined' && field.choices[ choiceKey ].label.toString().trim() !== ''
						? wpf.sanitizeHTML( field.choices[ choiceKey ].label.toString().trim() )
						: wpforms_builder.choice_empty_label_tpl.replace( '{number}', choiceKey );
				$select.append( $( '<option>', { value: choiceKey, text: label, id: 'choice-' + choiceKey } ) );
			}

			// Cache the template for future use and return the HTML.
			return updater.fieldValuesListTemplates[ fieldSelected ] = $select.html();
		},

		/**
		 * Form fields supporting conditional logic.
		 *
		 * @since 1.6.0.2
		 */
		updateConditionalRuleRows() {
			/**
			 * Split all the rows in sets of at most 20 elements.
			 *
			 * The set of 20 rows would then be processed as soon as possible, but without blocking
			 * the main thread (thanks to setTimeout).
			 *
			 * When all the groups are processed, the function finalize is called.
			 *
			 * @since 1.6.0.2
			 */
			helpers.arraySplitIntoChunks( updater.$ruleRows, 20 ).map( function( elements ) {
				setTimeout( function() {
					for ( let i = 0; i < elements.length; ++i ) {
						updater.updateConditionalRuleRow( elements[ i ] );
					}
				}, 0 );

				return elements;
			} );
		},

		/**
		 * Redraw the conditional rule in the builder.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Object} row Element container.
		 */
		updateConditionalRuleRow( row ) {
			const $row = $( row ),
				fieldID = $row.attr( 'data-field-id' ),
				$fields = $row.find( '.wpforms-conditional-field' ),
				fieldSelected = $fields.val();

			// Clone template
			$fields[ 0 ].innerHTML = updater.fieldsListTemplate;

			// Remove the current item
			$fields.find( '#option-' + fieldID ).remove();

			if ( ! fieldSelected ) {
				// Remove all id properties.
				// Querying the DOM by ID is much faster. It is safe to remove the IDs now.
				$fields.find( 'option' ).removeAttr( 'id' );
				return;
			}

			const $value = $row.find( '.wpforms-conditional-value' );

			// Check if the previous selected field exists in the new options added.
			if ( $fields.find( '#option-' + fieldSelected ).length ) {
				updater.restorePreviousRuleRowSelection( $row, $fields, fieldSelected, $value );
			} else {
				updater.removeRuleRow( $row );
			}

			// Remove all id properties.
			// Querying the DOM by ID is much faster. It is safe to remove the IDs now.
			$fields.find( 'option' ).removeAttr( 'id' );
			$value.find( 'option' ).removeAttr( 'id' );
		},

		/**
		 * Update delete confirmation alert message.
		 *
		 * @since 1.6.7
		 *
		 * @param {Object} fieldData Field Data object.
		 */
		fieldDeleteConfirmAlert( fieldData ) {
			let alert = wpforms_builder.conditionals_change + '<br>',
				updateAlert;

			$( '.wpforms-conditional-field' ).each( function() {
				if ( fieldData.id === Number( $( this ).val() ) ) {
					if ( fieldData.choiceId && fieldData.choiceId !== Number( $( this ).closest( '.wpforms-conditional-row' ).find( '.wpforms-conditional-value' ).val() ) ) {
						return;
					}

					alert += updater.getChangedFieldNameForAlert( updater.getReferenceName( this ) );

					updateAlert = true;
					fieldData.trigger = true;
				}
			} );

			if ( updateAlert ) {
				fieldData.message = '<strong>' + fieldData.message + '</strong>' + '<br><br>' + alert;
			}
		},

		/**
		 * Retrieves the reference name based on the provided conditional field context.
		 * The reference name is determined by the closest provider or conditional group associated with the field.
		 *
		 * @since 1.9.6
		 *
		 * @param {HTMLElement} conditionalField The conditional field element for which the reference name needs to be determined.
		 *
		 * @return {string} The determined reference name.
		 */
		getReferenceName( conditionalField ) {
			const $conditionalField = $( conditionalField );
			// Fetch only Marketing provider name.
			const providerName = $conditionalField.closest( '.wpforms-builder-provider' ).data( 'provider-name' );

			if ( ! providerName ) {
				return $conditionalField.closest( '.wpforms-conditional-group' ).data( 'reference' );
			}

			return wpforms_builder.cl_reference.replace( '{integration}', providerName );
		},

		/**
		 * Restore the rule row selection before conditional rules update.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Object} $row          Row container.
		 * @param {Object} $fields       Field object.
		 * @param {string} fieldSelected Field selected value.
		 * @param {Object} $value        Field Value.
		 */
		restorePreviousRuleRowSelection( $row, $fields, fieldSelected, $value ) {
			let valueSelected = '';

			// Exists, so restore the previous selected value
			$fields.find( '#option-' + fieldSelected ).prop( 'selected', true );

			if ( ! $value.length || ! $value.is( 'select' ) ) {
				return;
			}

			// Since the field exists and was selected, now we must proceed to update the field values.
			// Luckily, we only have to do this for fields that leverage a select element.
			// Grab the currently selected value to restore later
			valueSelected = $value.val();

			$value[ 0 ].innerHTML = updater.getFieldValuesListTemplate( updater.conditionalFields, fieldSelected );

			// Check if the previous selected value exists in the new options added
			if ( $value.find( '#choice-' + valueSelected ).length ) {
				$value.find( '#choice-' + valueSelected ).prop( 'selected', true );
			}
		},

		/**
		 * Check if the previous selected field exists in the new options added.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {Object} $row Row container.
		 */
		removeRuleRow( $row ) {
			// Since the previously selected field no longer exists, this
			// means this rule is now invalid. So the rule gets
			// deleted as long as it isn't the only rule remaining.
			const $group = $row.closest( '.wpforms-conditional-group' );

			if ( $group.find( 'table >tbody >tr' ).length === 1 ) {
				const $groups = $row.closest( '.wpforms-conditional-groups' );
				if ( $groups.find( '.wpforms-conditional-group' ).length > 1 ) {
					$group.remove();
				} else {
					$row.find( '.wpforms-conditional-value' ).remove();
					$row.find( '.value' ).append( '<select>' );
				}
			} else {
				$row.remove();
			}
		},

		/**
		 * Return HTML with an error message.
		 *
		 * @since 1.6.0.2
		 *
		 * @param {string|number} field Field ID or field name.
		 *
		 * @return {string} HTML message.
		 */
		getChangedFieldNameForAlert( field ) {
			if ( ! wpf.isNumber( field ) ) {
				// Panel
				return '<br>' + field;
			}

			const formData = wpf.formObject( '#wpforms-field-options' );

			if ( ( ( formData.fields[ field ] || {} ).label || '' ).length ) {
				return '<br/>' + wpf.sanitizeHTML( formData.fields[ field ].label ) + ' (' + wpforms_builder.field + ' #' + field + ')';
			}

			return '<br>' + wpforms_builder.field + ' #' + field;
		},
	};

	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init() {
			// Document ready
			$( WPFormsConditionals.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready() {
			WPFormsConditionals.bindUIActions();
		},

		/**
		 * Get layout fields to exclude.
		 *
		 * @since 1.9.0
		 *
		 * @param {Object} $element Element to get layout fields to exclude.
		 *
		 * @return {Object} Layout fields to exclude.
		 */
		getLayoutFieldsToExclude( $element ) {
			const layoutID = $element.parents( '.wpforms-field-option' ).find( '.wpforms-field-option-hidden-id' ).val();
			const formData = wpf.formObject( '#wpforms-field-options' );
			const layoutFieldData = layoutID && formData?.fields?.[ layoutID ] ? formData.fields[ layoutID ] : [];
			const fieldsToExclude = {};

			Object.values( layoutFieldData[ 'columns-json' ] ?? {} ).forEach( ( column ) => {
				Object.values( column?.fields ?? [] ).forEach( ( field ) => {
					if ( ! formData.fields[ field ] ) {
						return;
					}

					fieldsToExclude[ field ] = formData.fields[ field ];
				} );
			} );

			return fieldsToExclude;
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions() {
			const $builder = $( '#wpforms-builder' );

			// Conditional support toggle.
			$builder.on( 'change', '.wpforms-conditionals-enable-toggle input[type=checkbox]', function( e ) {
				WPFormsConditionals.conditionalToggle( this, e );
			} );

			$builder.on( 'click', '.wpforms-field-option-group-conditionals', function() {
				const $this = $( this );
				const isAllowedLayoutFields = $this.parents( '.wpforms-field-option' ).find( '.wpforms-field-option-hidden-type' ).val() !== 'layout';

				if ( isAllowedLayoutFields ) {
					return;
				}

				const $block = $this.find( '.wpforms-conditional-block' );
				const fields = wpf.getFields( false, true, false, app.getLayoutFieldsToExclude( $this ) );

				WPFormsConditionals.conditionalUpdateOptions( false, fields, $block.find( '.wpforms-conditional-row' ) );
			} );

			// Conditional process field selects.
			$builder.on( 'change', '.wpforms-conditional-field', function( e ) {
				WPFormsConditionals.conditionalField( this, e );
			} );

			// Conditional process operator select.
			$builder.on( 'change', '.wpforms-conditional-operator', function( e ) {
				WPFormsConditionals.conditionalOperator( this, e );
			} );

			// Conditional add new rule.
			$builder.on( 'click', '.wpforms-conditional-rule-add', function( e ) {
				WPFormsConditionals.conditionalRuleAdd( this, e );
			} );

			// Conditional delete rule.
			$builder.on( 'click', '.wpforms-conditional-rule-delete', function( e ) {
				WPFormsConditionals.conditionalRuleDelete( this, e );
			} );

			// Conditional add new group.
			$builder.on( 'click', '.wpforms-conditional-groups-add', function( e ) {
				WPFormsConditionals.conditionalGroupAdd( this, e );
			} );

			// Conditional logic update/refresh.
			$( document ).on( 'wpformsFieldUpdate', WPFormsConditionals.conditionalUpdateOptions );

			$builder.on( 'wpformsBeforeFieldDeleteAlert', function( e, fieldData ) {
				updater.fieldDeleteConfirmAlert( fieldData );
			} );
		},

		/**
		 * Update/refresh the conditional logic fields and associated options.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} e         Event object.
		 * @param {Array}  allFields All fields in the form.
		 * @param {jQuery} $rows     Conditional rule rows.
		 */
		conditionalUpdateOptions( e, allFields, $rows ) {
			if ( wpf.empty( allFields ) ) {
				return;
			}

			updater.cacheAllFields( allFields );
			updater.cacheRuleRows( $rows );

			updater.setConditionalFields();
			updater.setTemplates();

			updater.updateConditionalRuleRows();
		},

		/**
		 * Toggle conditional support.
		 *
		 * @since 1.0.0
		 * @since 1.7.5 Added `wpformsRemoveConditionalLogicRules` trigger.
		 *
		 * @param {Object} el Conditional Logic input (toggle).
		 * @param {Object} e  Event object.
		 */
		conditionalToggle( el, e ) {
			e.preventDefault();

			const $this = $( el ),
				$block = $this.closest( '.wpforms-conditional-block' ),
				logicBlock = wp.template( 'wpforms-conditional-block' ),
				data = {
					fieldID    : $this.closest( '.wpforms-field-option-row' ).data( 'field-id' ),
					fieldName  : $this.data( 'name' ),
					actions    : $this.data( 'actions' ),
					actionDesc : $this.data( 'action-desc' ),
					reference  : $this.data( 'reference' ),
				};

			if ( $this.is( ':checked' ) ) {
				// Add conditional logic rules.
				$block.append( logicBlock( data ) );

				// Update fields in the added rule.
				const fields = wpf.getFields( false, true, false, app.getLayoutFieldsToExclude( $this ) );

				WPFormsConditionals.conditionalUpdateOptions( false, fields, $block.find( '.wpforms-conditional-row' ) );
			} else {
				// Remove conditional logic rules.
				$.confirm( {
					title: false,
					content: wpforms_builder.conditionals_disable,
					backgroundDismiss: false,
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					buttons: {
						confirm: {
							text: wpforms_builder.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action() {
								// Prompt
								$block.find( '.wpforms-conditional-groups' ).remove();

								$( document ).trigger( 'wpformsRemoveConditionalLogicRules', [ $this ] );
							},
						},
						cancel: {
							text: wpforms_builder.cancel,
							action() {
								$this.prop( 'checked', true );
							},
						},
					},
				} );
			}
		},

		/**
		 * Process conditional field.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} el Conditional Logic input (toggle).
		 * @param {Object} e  Event object.
		 */
		conditionalField( el, e ) { // eslint-disable-line complexity
			e.preventDefault();

			const $this = $( el ),
				$rule = $this.parent().parent(),
				$operator = $rule.find( '.wpforms-conditional-operator' ),
				operator = $operator.find( 'option:selected' ).val(),
				data = WPFormsConditionals.conditionalData( $this ),
				name = data.inputName + '[' + data.groupID + '][' + data.ruleID + '][value]';
			let $element;

			if ( ! data.field ) {
				// Placeholder has been selected.
				// language=HTML
				$element = $( '<select>' );
			} else if (
				data.field.type === 'select' ||
				data.field.type === 'radio' ||
				data.field.type === 'checkbox' ||
				data.field.type === 'payment-multiple' ||
				data.field.type === 'payment-checkbox' ||
				data.field.type === 'payment-select'
			) {
				// Selector type fields use select elements.

				// language=HTML
				$element = $( '<select>' ).attr( { name, class: 'wpforms-conditional-value' } ); // jshint ignore:line
				$element.append( $( '<option>', { value: '', text : wpforms_builder.select_choice } ) );
				if ( data.field.choices ) {
					for ( const key in wpf.orders.choices[ 'field_' + data.field.id ] ) {
						const choiceKey = wpf.orders.choices[ 'field_' + data.field.id ][ key ],
							label = typeof data.field.choices[ choiceKey ].label !== 'undefined' && data.field.choices[ choiceKey ].label.toString().trim() !== ''
								? wpf.sanitizeHTML( data.field.choices[ choiceKey ].label.toString().trim() )
								: wpforms_builder.choice_empty_label_tpl.replace( '{number}', choiceKey );
						$element.append( $( '<option>', { value: choiceKey, text: wpf.sanitizeHTML( label ) } ) );
					}
				}
				$operator.find( "option:not([value='=='],[value='!='],[value='e'],[value='!e'])" ).prop( 'disabled', true ).prop( 'selected', false ); // jshint ignore:line
			} else {
				// Text type fields (everything else) use text inputs.

				// Determine an input type.
				let inputType = 'text';

				if ( 'rating' === data.field.type || 'net_promoter_score' === data.field.type || 'number-slider' === data.field.type ) {
					inputType = 'number';
				}

				// language=HTML
				$element = $( '<input>' ).attr( { type: inputType, name, class: 'wpforms-conditional-value' } ); // jshint ignore:line
				$operator.find( 'option' ).prop( 'disabled', false );
			}

			if ( operator === 'e' || operator === '!e' ) {
				// Empty/not empty doesn't use input, so we disable it.
				$element.prop( 'disabled', true );
			}

			$rule.find( '.value' ).empty().append( $element );
		},

		/**
		 * Process conditional field.
		 *
		 * @since 1.2.0
		 *
		 * @param {HTMLElement} el Conditional Logic input (toggle).
		 * @param {Object}      e  Event object.
		 */
		conditionalOperator( el, e ) {
			e.preventDefault();

			const $this = $( el ),
				$rule = $this.parent().parent(),
				$value = $rule.find( '.wpforms-conditional-value' ),
				operator = $this.find( 'option:selected' ).val();

			if ( operator === 'e' || operator === '!e' ) {
				$value.prop( 'disabled', true );
				if ( $value.is( 'select' ) ) {
					$value.find( 'option:selected' ).prop( 'selected', false );
				} else {
					$value.val( '' );
				}
			} else {
				$value.prop( 'disabled', false );
			}
		},

		/**
		 * Add new conditional rule.
		 *
		 * @since 1.0.0
		 *
		 * @param {HTMLElement} el Conditional Logic input (toggle).
		 * @param {Object}      e  Event object.
		 */
		conditionalRuleAdd( el, e ) {
			e.preventDefault();

			const $this = $( el ),
				$group = $this.closest( '.wpforms-conditional-group' ),
				$rule = $group.find( 'tr' ).last(),
				$newRule = $rule.clone(),
				$field = $newRule.find( '.wpforms-conditional-field' ),
				$operator = $newRule.find( '.wpforms-conditional-operator' ),
				data = WPFormsConditionals.conditionalData( $field ),
				ruleID = Number( data.ruleID ) + 1,
				name = data.inputName + '[' + data.groupID + '][' + ruleID + ']';

			$newRule.find( 'option:selected' ).prop( 'selected', false );

			// language=HTML
			$newRule.find( '.value' ).empty().append( $( '<select>' ) );
			$field.attr( 'name', name + '[field]' ).attr( 'data-ruleid', ruleID );
			$operator.attr( 'name', name + '[operator]' );
			$rule.after( $newRule );
		},

		/**
		 * Delete conditional rule.
		 * If the only rule in a group, then a group will also be removed.
		 *
		 * @since 1.0.0
		 *
		 * @param {HTMLElement} el Conditional Logic input (toggle).
		 * @param {Object}      e  Event object.
		 */
		conditionalRuleDelete( el, e ) {
			e.preventDefault();

			const $this = $( el ),
				$group = $this.closest( '.wpforms-conditional-group' ),
				$rows = $group.find( 'table >tbody >tr' );

			if ( $rows && $rows.length === 1 ) {
				const $groups = $this.closest( '.wpforms-conditional-groups' );
				if ( $groups.find( '.wpforms-conditional-group' ).length > 1 ) {
					$group.remove();
				} else {
					$rows.find( '.wpforms-conditional-operator' ).val( '==' ).trigger( 'change' );
					$rows.find( '.wpforms-conditional-value' ).val( '' ).trigger( 'change' );
					$rows.find( '.wpforms-conditional-field' ).val( '' ).trigger( 'change' );
				}
			} else {
				$this.parent().parent().remove();
			}
		},

		/**
		 * Add a new conditional group.
		 *
		 * @since 1.0.0
		 *
		 * @param {HTMLElement} el Conditional Logic input (toggle).
		 * @param {Object}      e  Event object.
		 */
		conditionalGroupAdd( el, e ) {
			e.preventDefault();

			const $this = $( el ),
				$groupLast = $this.parent().find( '.wpforms-conditional-group' ).last(),
				$newGroup = $groupLast.clone();

			$newGroup.find( 'tr' ).slice( 1 ).remove();

			const $field = $newGroup.find( '.wpforms-conditional-field' ),
				$operator = $newGroup.find( '.wpforms-conditional-operator' ),
				data = WPFormsConditionals.conditionalData( $field ),
				groupID = Number( data.groupID ) + 1,
				ruleID = 0,
				name = data.inputName + '[' + groupID + '][' + ruleID + ']';

			$newGroup.find( 'option:selected' ).prop( 'selected', false );

			// language=HTML
			$newGroup.find( '.value' ).empty().append( $( '<select>' ) );
			$field.attr( 'name', name + '[field]' ).attr( 'data-ruleid', ruleID ).attr( 'data-groupid', groupID );
			$operator.attr( 'name', name + '[operator]' );
			$this.before( $newGroup );
		},

		//--------------------------------------------------------------------//
		// Helper functions
		//--------------------------------------------------------------------//

		/**
		 * Return various data for the conditional field.
		 *
		 * @since 1.0.0
		 *
		 * @param {HTMLElement} el Conditional Logic input (toggle).
		 *
		 * @return {Object} Data object containing fields, inputBase, fieldID, ruleID, groupID, selectedID and field.
		 */
		conditionalData( el ) {
			const $this = $( el );
			const data = {
				fields     : wpf.getFields( false, true ),
				inputBase  : $this.closest( '.wpforms-conditional-row' ).attr( 'data-input-name' ),
				fieldID    : $this.closest( '.wpforms-conditional-row' ).attr( 'data-field-id' ),
				ruleID     : $this.attr( 'data-ruleid' ),
				groupID    : $this.attr( 'data-groupid' ),
				selectedID : $this.find( ':selected' ).val(),
			};

			data.inputName = data.inputBase + '[conditionals]';

			if ( data.selectedID.length ) {
				data.field = wpf.getField( data.selectedID );
			} else {
				data.field = false;
			}

			return data;
		},
	};

	return app;
}( document, window, jQuery ) );

WPFormsConditionals.init();
