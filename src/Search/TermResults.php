<?php

namespace TLTSuite\TLTSearch\Search;

/**
 * Product categories and tags whose name matches the query, for the dropdown.
 */
class TermResults {

	/** @return array<int, array{name:string, url:string, type:string}> */
	public static function find( string $query ): array {

		if ( ! SearchOptions::show_term_results() || mb_strlen( $query ) < 2 ) {
			return [];
		}

		$terms = get_terms( [
			'taxonomy'   => [ 'product_cat', 'product_tag' ],
			'name__like' => $query,
			'number'     => 5,
			'hide_empty' => true,
		] );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$out = [];

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$out[] = [
				'name' => $term->name,
				'url'  => $link,
				'type' => 'product_cat' === $term->taxonomy ? __( 'Category', 'tlt-search' ) : __( 'Tag', 'tlt-search' ),
			];
		}

		return $out;
	}
}
