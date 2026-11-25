( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, SelectControl } = wp.components;
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
						el( TextControl, {
							label: __( 'Only these categories (names, comma separated)', 'tlt-search' ),
							value: attributes.category,
							onChange: ( value ) => setAttributes( { category: value } ),
						} ),
						el( TextControl, {
							label: __( 'Results per page (0 = site default)', 'tlt-search' ),
							type: 'number',
							value: attributes.perPage,
							onChange: ( value ) => setAttributes( { perPage: parseInt( value, 10 ) || 0 } ),
						} ),
						el( SelectControl, {
							label: __( 'Layout', 'tlt-search' ),
							value: attributes.layout,
							options: [
								{ label: __( 'Site default', 'tlt-search' ), value: '' },
								{ label: __( 'List', 'tlt-search' ), value: 'list' },
								{ label: __( 'Grid', 'tlt-search' ), value: 'grid' },
							],
							onChange: ( value ) => setAttributes( { layout: value } ),
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
