<?php

namespace TLTSuite\TLTSearch\API;

use WP_Error;
use WP_REST_Request;

class SearchRequestValidator {

	public const MAX_PER_PAGE = 100;

	public const DEFAULT_PER_PAGE = 10;

	public function validate( WP_REST_Request $request ) {

		$query = $this->get_query( $request );

		if ( '' === $query ) {
			return new WP_Error(
				'tlt_search_missing_query',
				__( 'Search query is required.', 'tlt-search' ),
				[ 'status' => 400 ]
			);
		}

		if ( mb_strlen( $query ) < 3 ) {
			return new WP_Error(
				'tlt_search_query_too_short',
				__( 'Search query must be at least 3 characters.', 'tlt-search' ),
				[ 'status' => 400 ]
			);
		}

		if ( mb_strlen( $query ) > 100 ) {
			return new WP_Error(
				'tlt_search_query_too_long',
				__( 'Search query must not exceed 100 characters.', 'tlt-search' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	public function get_query( WP_REST_Request $request ): string {

		return trim(
			sanitize_text_field(
				(string) $request->get_param( 'q' )
			)
		);
	}

	public function get_page( WP_REST_Request $request ): int {

		$page = absint(
			$request->get_param( 'page' )
		);

		return max( 1, $page );
	}

	public function get_per_page( WP_REST_Request $request ): int {

		$per_page = absint(
			$request->get_param( 'per_page' )
		);

		if ( $per_page < 1 ) {
			return self::DEFAULT_PER_PAGE;
		}

		return min( self::MAX_PER_PAGE, $per_page );
	}

	/**
	 * Extract and sanitize filter params from the request.
	 *
	 * @return array{categories?: string[], brands?: string[], tags?: string[], stock_status?: string, price_min?: float, price_max?: float}
	 */
	public function get_filters( WP_REST_Request $request ): array {

		$filters = [];

		$categories = $request->get_param( 'categories' );
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			$filters['categories'] = array_values( array_filter( array_map( 'sanitize_text_field', $categories ) ) );
		}

		$brands = $request->get_param( 'brands' );
		if ( ! empty( $brands ) && is_array( $brands ) ) {
			$filters['brands'] = array_values( array_filter( array_map( 'sanitize_text_field', $brands ) ) );
		}

		$tags = $request->get_param( 'tags' );
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			$filters['tags'] = array_values( array_filter( array_map( 'sanitize_text_field', $tags ) ) );
		}

		$stock = sanitize_text_field( (string) ( $request->get_param( 'stock_status' ) ?? '' ) );
		if ( in_array( $stock, [ 'instock', 'outofstock', 'onbackorder' ], true ) ) {
			$filters['stock_status'] = $stock;
		}

		$price_min = $request->get_param( 'price_min' );
		if ( '' !== (string) $price_min && is_numeric( $price_min ) && (float) $price_min >= 0 ) {
			$filters['price_min'] = (float) $price_min;
		}

		$price_max = $request->get_param( 'price_max' );
		if ( '' !== (string) $price_max && is_numeric( $price_max ) && (float) $price_max >= 0 ) {
			$filters['price_max'] = (float) $price_max;
		}

		return $filters;
	}
}