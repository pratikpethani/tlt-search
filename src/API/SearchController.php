<?php

namespace TLTSuite\TLTSearch\API;

use TLTSuite\TLTSearch\Search\SearchService;
use TLTSuite\TLTSearch\Analytics\SearchLogger;
use TLTSuite\TLTSearch\Merchandising\PinManager;
use TLTSuite\TLTSearch\API\SearchRequestValidator;
use TLTSuite\TLTSearch\API\ResultTransformer;
use TLTSuite\TLTSearch\API\ResponseFormatter;
use TLTSuite\TLTSearch\Support\Logger;
use TLTSuite\TLTSearch\API\PermissionManager;
use TLTSuite\TLTSearch\API\RateLimiter;
use WP_REST_Request;
use WP_REST_Response;
use Exception;

class SearchController {

	public function register_routes(): void {

		register_rest_route(
			'tlt-search/v1',
			'/search',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'search' ],
				'permission_callback' => [
					new PermissionManager(),
					'public_search',
				],
			]
		);
	}

	public function search( WP_REST_Request $request ): WP_REST_Response {

		$validator = new SearchRequestValidator();

		$valid = $validator->validate( $request );

		if ( is_wp_error( $valid ) ) {

			$status = $valid->get_error_data()['status'] ?? 400;

			$formatter = new ResponseFormatter();
			$response = new WP_REST_Response(
				$formatter->error(
					$valid->get_error_code(),
					$valid->get_error_message(),
					$status
				),
				$status
			);
			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
			return $response;
		}

		$rate_limiter = new RateLimiter();

		if ( ! $rate_limiter->allow_request( $request ) ) {

			$formatter = new ResponseFormatter();
			$response = new \WP_REST_Response( $formatter->error( 'tlt_search_rate_limit_exceeded', __( 'Too many requests.', 'tlt-search' ), 429 ), 429 );
			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
			$response->header( 'Retry-After', '60' );
			return $response;
		}
		
		try {

			$query   = $validator->get_query( $request );
			$filters = $validator->get_filters( $request );

			$service = new SearchService();

			$result   = $service->search_with_facets( $query, $filters );
			$all_hits = $result['hits'];
			$facets   = $result['facets'];

			// Inject manually pinned products at the front of results.
			$pin_manager = new PinManager();
			$pinned_ids  = $pin_manager->get_pinned_product_ids( $query );

			if ( ! empty( $pinned_ids ) ) {
				$all_hits = array_values( array_filter(
					$all_hits,
					static fn( array $h ) => ! in_array( (int) ( $h['id'] ?? 0 ), $pinned_ids, true )
				) );

				$pinned_docs = [];
				foreach ( $pinned_ids as $pid ) {
					$product = wc_get_product( $pid );
					if ( ! $product || $product->get_status() !== 'publish' ) {
						continue;
					}
					$pinned_docs[] = [
						'id'           => $pid,
						'title'        => $product->get_name(),
						'sku'          => $product->get_sku(),
						'price'        => (float) $product->get_price(),
						'stock_status' => $product->get_stock_status(),
						'featured'     => (bool) $product->get_featured(),
						'_pinned'      => true,
					];
				}

				$all_hits = array_merge( $pinned_docs, $all_hits );
			}

			$page           = $validator->get_page( $request );
			$per_page       = $validator->get_per_page( $request );
			$total          = count( $all_hits );
			$organic_total  = $result['total']; // excludes pins — used for analytics
			$offset         = ( $page - 1 ) * $per_page;

			$hits = array_slice( $all_hits, $offset, $per_page );

			( new SearchLogger() )->log( $query, $organic_total );

			$transformer = new ResultTransformer();

			$results = $transformer->transform_collection( $hits );

			$formatter = new ResponseFormatter();

			$response = new WP_REST_Response(
				$formatter->success(
					[
						'query'        => $query,
						'total'        => $total,
						'page'         => $page,
						'per_page'     => $per_page,
						'total_pages'  => $total > 0 ? (int) ceil( $total / $per_page ) : 0,
						'max_per_page' => SearchRequestValidator::MAX_PER_PAGE,
						'results'      => $results,
						'facets'       => $facets,
					]
				)
			);

			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
			return $response;

		} catch ( Exception $exception ) {

			Logger::error(
				$exception->getMessage(),
				[
					'trace' => $exception->getTraceAsString(),
				]
			);

			$formatter = new ResponseFormatter();
			$response = new WP_REST_Response( $formatter->error( 'tlt_search_internal_error', __( 'An internal error occurred.', 'tlt-search' ), 500 ), 500 );
			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
			return $response;
		}
	}
}