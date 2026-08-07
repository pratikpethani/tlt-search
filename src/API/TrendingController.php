<?php

namespace TLTSuite\TLTSearch\API;

use TLTSuite\TLTSearch\Analytics\AnalyticsReport;
use TLTSuite\TLTSearch\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;

class TrendingController {

	const TRANSIENT_KEY     = 'tlt_trending';
	const CACHE_SECONDS     = 900; // 15 minutes

	public function register_routes(): void {

		register_rest_route(
			'tlt-search/v1',
			'/trending',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'trending' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'limit' => [
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	private function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function trending( WP_REST_Request $request ): WP_REST_Response {

		$limit  = min( max( (int) $request->get_param( 'limit' ) ?: 10, 1 ), 25 );
		$cached = get_transient( self::TRANSIENT_KEY . ':' . $limit );

		if ( false !== $cached ) {
			return new WP_REST_Response( [ 'success' => true, 'data' => $cached ] );
		}

		try {

			$report  = new AnalyticsReport();
			$popular = $report->popular_searches( 30, $limit );

			$results = array_map( static fn( array $row ) => [
				'query'        => $row['query'],
				'search_count' => (int) $row['search_count'],
			], $popular );

			set_transient( self::TRANSIENT_KEY . ':' . $limit, $results, self::CACHE_SECONDS );

			return new WP_REST_Response( [ 'success' => true, 'data' => $results ] );

		} catch ( \Exception $e ) {

			Logger::error( 'Trending failed: ' . $e->getMessage() );

			return new WP_REST_Response( [ 'success' => false, 'data' => [] ], 500 );
		}
	}
}
