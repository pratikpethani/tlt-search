<?php

namespace TLTSuite\TLTSearch\API;

use TLTSuite\TLTSearch\Analytics\AnalyticsReport;
use WP_REST_Response;

/**
 * GET /tlt-search/v1/popular: public list of popular searches that found results.
 * Only queries searched at least twice are shown, so one-off private searches never appear.
 */
class PopularController {

	const TRANSIENT_KEY = 'tlt_popular_public';
	const CACHE_SECONDS = 900;
	const MIN_SEARCHES  = 2;

	public function register_routes(): void {

		register_rest_route(
			'tlt-search/v1',
			'/popular',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'popular' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function popular(): WP_REST_Response {

		$list = get_transient( self::TRANSIENT_KEY );

		if ( false === $list ) {

			$rows = ( new AnalyticsReport() )->popular_searches( 30, 20 );
			$rows = array_filter( $rows, static fn( $r ) => (int) $r['search_count'] >= self::MIN_SEARCHES );
			$list = array_slice( array_column( $rows, 'query' ), 0, 6 );

			set_transient( self::TRANSIENT_KEY, $list, self::CACHE_SECONDS );
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => array_values( $list ) ] );
	}
}
