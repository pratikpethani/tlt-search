( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
	const { __ } = wp.i18n;

	registerBlockType( 'tlt-search/search', {
		edit( { attributes, setAttributes } ) {
			const placeholder = attributes.placeholder || __( 'Search products…', 'tlt-search' );

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Search settings', 'tlt-search' ) },
						el( TextControl, {
							label: __( 'Placeholder text', 'tlt-search' ),
							value: attributes.placeholder,
							onChange: ( value ) => setAttributes( { placeholder: value } ),
						} ),
						el( ToggleControl, {
							label: __( 'Show filters', 'tlt-search' ),
							checked: attributes.showFilters,
							onChange: ( value ) => setAttributes( { showFilters: value } ),
						} )
					)
				),
				// Static preview: the live search needs the front-end scripts.
				el( 'input', { type: 'search', placeholder, disabled: true, style: { width: '100%', padding: '0.6em 0.8em' } } )
			);
		},
		save() {
			return null; // Rendered on the server.
		},
	} );
} )( window.wp );
