;(function(wp){

	/**
	 *  Shortcut variables
	 */
	var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType;

	/**
	 * Custom SVG icon
	 * could move to a separated file if we need it in other places, too
	 *
	 * @source https://gist.github.com/zgordon/e837e29f77c343d29ebb7290a1a75eea
	 */
	const advadsIconEl = el(
		'svg',
		{
			width: "24px",
			height: "24px",
			viewBox: "1.396 3276 24 24",
			xmlns: "http://www.w3.org/2000/svg",
			x: "0px",
			y: "0px"
		},
		el(
			'g',
			{},
			el(
				'path',
				{
					fill: "#1C1B3A",
					d: "M18.602,3286.2v8.53H6.677v-11.925h8.53c-0.355-0.804-0.545-1.684-0.545-2.625s0.205-1.82,0.545-2.625 h-2.57H1.406v18.266l0.6,0.6l-0.6-0.6c0,2.304,1.875,4.179,4.18,4.179l0,0h7.05h11.216v-13.821 c-0.805,0.355-1.705,0.566-2.645,0.566C20.286,3286.745,19.406,3286.541,18.602,3286.2z"
				}
			),
			el( 'circle', { fill: "#0E75A4", cx: "21.206", cy: "3280.179", r: "4.18" } )
		),
	);

	/**
	 * Register the single ad block type
	 */
	registerBlockType( 'advads/gblock', {

		title: advadsGutenberg.i18n.advads,

		icon: advadsIconEl,

		category: 'common',

		attributes: {
			itemID: {
				type: 'string',
			},
		},

		// todo: make the keywords translatable
		keywords: [ 'advert', 'adsense', 'banner' ],

		edit: function( props ) {

			var itemID = props.attributes.itemID;

			/**
			 * Update property on submit
			 */
			function setItemID( event ) {
				var selected = event.target.querySelector( 'option:checked' );
				props.setAttributes( { itemID: selected.value } );
				event.preventDefault();
			}

			// the form children elements
			var children = [];

			// argument list (in array form) for the children creation
			var args = [];
			var ads = [];
			var groups = [];
			var placements = [];

			args.push( 'select' );
			args.push( { value: itemID, onChange: setItemID } );
			args.push( el( 'option', null, advadsGutenberg.i18n['--empty--'] ) );

			for ( var adID in advadsGutenberg.ads ) {
				if ( 'undefined' == typeof advadsGutenberg.ads[adID].id ) continue;
				ads.push( el( 'option', {value: 'ad_' + advadsGutenberg.ads[adID].id}, advadsGutenberg.ads[adID].title ) );
			}

			for ( var GID in advadsGutenberg.groups ) {
				if ( 'undefined' == typeof advadsGutenberg.groups[GID].id ) continue;
				groups.push( el( 'option', {value: 'group_' + advadsGutenberg.groups[GID]['id'] }, advadsGutenberg.groups[GID]['name'] ) );

			}

			if ( advadsGutenberg.placements ) {
				for ( var pid in advadsGutenberg.placements ) {
				if ( 'undefined' == typeof advadsGutenberg.placements[pid].id ) continue;
					placements.push( el( 'option', {value: 'place_' + advadsGutenberg.placements[pid]['id']}, advadsGutenberg.placements[pid]['name'] ) );
				}
			}

			if ( advadsGutenberg.placements ) {
				args.push( el( 'optgroup', {label: advadsGutenberg.i18n['placements']}, placements ) );
			}

			args.push( el( 'optgroup', {label: advadsGutenberg.i18n['adGroups']}, groups ) );

			args.push( el( 'optgroup', {label: advadsGutenberg.i18n['ads']}, ads ) );

			// add a <label /> first and style it.
			children.push( el( 'label', {style:{fontWeight:'bold',display:'block'}}, advadsGutenberg.i18n.advads ) );

			// then add the <select /> input with its own children
			children.push( el.apply( null, args ) );

			if ( itemID && advadsGutenberg.i18n['--empty--'] != itemID ) {

				var url = '#';
				if ( 0 === itemID.indexOf( 'place_' ) ) {
					url = advadsGutenberg.editLinks.placement;
				} else if ( 0 === itemID.indexOf( 'group_' ) ) {
					url = advadsGutenberg.editLinks.group;
				} else if ( 0 === itemID.indexOf( 'ad_' ) ) {
					var _adID = itemID.substr(3);
					url = advadsGutenberg.editLinks.ad.replace( '%ID%', _adID );
				}

				children.push(
					el(
						'a',
						{
							class: 'dashicons dashicons-external',
							style: {
								'vetical-align': 'middle',
								margin: 5,
								'border-bottom': 'none'
							},
							href: url,
							target: '_blank',
						}
					)
				);

			}
			// return the complete form
			return el( 'form', { onSubmit: setItemID }, children );

		},

		save: function() {
			// server side rendering
			return null;
		},

	} );

})(window.wp);
