<?php

namespace TLTSuite\TLTSearch\Search;

/**
 * Which product fields are searched, and in what priority.
 *
 * Loupe ranks matches in earlier searchable attributes higher, so the saved
 * order of the enabled fields is the field "weight".
 */
class SearchFields {

	public const OPTION = 'tlt_search_settings';

	/** Field keys in default priority order. No translation calls: safe to use before `init`. */
	public static function keys(): array {
		return [ 'title', 'sku', 'gtin', 'variations', 'categories', 'brands', 'tags', 'attribute_values', 'custom_fields', 'taxonomy_terms', 'short_description', 'description' ];
	}

	/** Field key => label, for the admin UI only (translates, so call it on `init` or later). */
	public static function available(): array {
		return [
			'title'             => __( 'Product title', 'tlt-search' ),
			'sku'               => __( 'SKU', 'tlt-search' ),
			'gtin'              => __( 'GTIN / EAN / UPC / ISBN', 'tlt-search' ),
			'variations'        => __( 'Variations (SKU, GTIN, attributes)', 'tlt-search' ),
			'categories'        => __( 'Categories', 'tlt-search' ),
			'brands'            => __( 'Brands', 'tlt-search' ),
			'tags'              => __( 'Tags', 'tlt-search' ),
			'attribute_values'  => __( 'Attributes', 'tlt-search' ),
			'custom_fields'     => __( 'Custom fields', 'tlt-search' ),
			'taxonomy_terms'    => __( 'Custom taxonomies', 'tlt-search' ),
			'short_description' => __( 'Short description', 'tlt-search' ),
			'description'       => __( 'Description', 'tlt-search' ),
		];
	}

	/** Enabled field keys, highest priority first. Falls back to all fields in default order. */
	public static function enabled(): array {

		$all    = self::keys();
		$stored = (array) ( ( (array) get_option( self::OPTION, [] ) )['search_fields'] ?? [] );

		if ( empty( $stored ) ) {
			return $all;
		}

		$rows = [];
		foreach ( $all as $pos => $key ) {
			// Fields added after the settings were saved default to on, in their default position.
			$cfg = $stored[ $key ] ?? [ 'enabled' => 1, 'rank' => $pos + 1 ];
			if ( ! empty( $cfg['enabled'] ) ) {
				$rows[] = [ $key, (int) ( $cfg['rank'] ?? 99 ), $pos ];
			}
		}

		// Lower rank first; ties keep default order.
		usort( $rows, static fn( $a, $b ) => [ $a[1], $a[2] ] <=> [ $b[1], $b[2] ] );

		return $rows ? array_column( $rows, 0 ) : $all;
	}

	/** Meta keys whose values are indexed into the custom_fields attribute. */
	public static function custom_field_keys(): array {

		$raw = (string) ( ( (array) get_option( self::OPTION, [] ) )['custom_field_keys'] ?? '' );

		return self::parse_keys( $raw );
	}

	public static function parse_keys( string $raw ): array {

		$keys = array_map(
			static fn( $k ) => preg_replace( '/[^A-Za-z0-9_\-:.]/', '', trim( $k ) ),
			explode( ',', $raw )
		);

		return array_values( array_unique( array_filter( $keys ) ) );
	}

	/** Clean the submitted checkbox/rank table. */
	public static function sanitize( $input ): array {

		$clean = [];

		foreach ( self::keys() as $key ) {
			$clean[ $key ] = [
				'enabled' => empty( $input[ $key ]['enabled'] ) ? 0 : 1,
				'rank'    => max( 1, min( 99, (int) ( $input[ $key ]['rank'] ?? 99 ) ) ),
			];
		}

		return $clean;
	}
}
