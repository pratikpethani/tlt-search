<?php

namespace TLTSuite\TLTSearch\Support;

/**
 * Thin bridge to WPML and Polylang. Everything returns '' / [] when neither is active,
 * so single-language sites behave exactly as before.
 */
class Multilingual {

	public static function is_active(): bool {
		return function_exists( 'pll_current_language' ) || has_filter( 'wpml_current_language' );
	}

	/** Active language codes, e.g. ['en','fr']. */
	public static function languages(): array {

		if ( function_exists( 'pll_languages_list' ) ) {
			return array_values( (array) pll_languages_list( [ 'fields' => 'slug' ] ) );
		}

		if ( has_filter( 'wpml_active_languages' ) ) {
			return array_keys( (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] ) );
		}

		return [];
	}

	/** Language of the page being viewed. '' when unknown, not multilingual, or "all languages". */
	public static function current(): string {

		$code = '';

		if ( function_exists( 'pll_current_language' ) ) {
			$code = (string) pll_current_language( 'slug' );
		} elseif ( has_filter( 'wpml_current_language' ) ) {
			$code = (string) apply_filters( 'wpml_current_language', null );
		}

		return 'all' === $code ? '' : $code;
	}

	/** Language a product was written in. Used when indexing. */
	public static function post_language( int $post_id ): string {

		if ( function_exists( 'pll_get_post_language' ) ) {
			return (string) pll_get_post_language( $post_id, 'slug' );
		}

		if ( has_filter( 'wpml_element_language_code' ) ) {
			return (string) apply_filters(
				'wpml_element_language_code',
				null,
				[ 'element_id' => $post_id, 'element_type' => 'post_product' ]
			);
		}

		return '';
	}

	/** Accept a requested language only if it is one of the site's active languages. */
	public static function validate( string $code ): string {

		$code = sanitize_key( $code );

		return ( '' !== $code && in_array( $code, self::languages(), true ) ) ? $code : '';
	}
}
