<?php

namespace TLTSuite\TLTSearch\Search;

/**
 * Read-only access to the behavior settings saved on the Settings page.
 * No translation calls here: this runs when the plugin loads.
 */
class SearchOptions {

	public static function all(): array {
		return (array) get_option( 'tlt_search_settings', [] );
	}

	/** Loupe matching strategy: 'any' = a result needs any word (OR), 'all' = every word (AND). */
	public static function match_strategy(): string {
		return 'all' === ( self::all()['match_logic'] ?? '' ) ? 'all' : 'any';
	}

	public static function typo_tolerance(): bool {
		return 'off' !== ( self::all()['typo_tolerance'] ?? 'on' );
	}

	public static function min_query_length(): int {
		return max( 1, min( 10, (int) ( self::all()['min_query_length'] ?? 3 ) ) );
	}

	public static function results_per_page(): int {
		return max( 1, min( 50, (int) ( self::all()['results_per_page'] ?? 10 ) ) );
	}

	/** Result thumbnail size in pixels. */
	public static function image_size(): int {
		return [ 'small' => 40, 'medium' => 60, 'large' => 90 ][ self::all()['image_size'] ?? 'medium' ] ?? 60;
	}

	public static function layout(): string {
		return 'grid' === ( self::all()['layout'] ?? '' ) ? 'grid' : 'list';
	}

	public static function show_rating(): bool {
		return 'yes' === ( self::all()['show_rating'] ?? 'no' );
	}

	public static function show_add_to_cart(): bool {
		return 'yes' === ( self::all()['show_add_to_cart'] ?? 'no' );
	}

	public static function result_cache_enabled(): bool {
		return 'off' !== ( self::all()['result_cache'] ?? 'on' );
	}

	public static function hide_out_of_stock(): bool {
		return 'yes' === ( self::all()['hide_out_of_stock'] ?? 'no' );
	}

	/** @return int[] */
	public static function excluded_product_ids(): array {
		return self::parse_list( (string) ( self::all()['exclude_product_ids'] ?? '' ), 'absint' );
	}

	/** @return string[] product_cat slugs */
	public static function excluded_category_slugs(): array {
		return self::parse_list( (string) ( self::all()['exclude_categories'] ?? '' ), 'sanitize_title' );
	}

	/** @return string[] taxonomy slugs whose terms are searchable */
	public static function custom_taxonomies(): array {
		return self::parse_list( (string) ( self::all()['custom_taxonomies'] ?? '' ), 'sanitize_key' );
	}

	/** Split a comma-separated setting, clean each value, drop empties and duplicates. */
	public static function parse_list( string $raw, callable $clean ): array {

		$values = array_map( static fn( $v ) => $clean( trim( $v ) ), explode( ',', $raw ) );

		return array_values( array_unique( array_filter( $values ) ) );
	}
}
