<?php

namespace TLTSuite\TLTSearch\API;

use TLTSuite\TLTSearch\Analytics\ClickLogger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /tlt-search/v1/click: records a click on a search result.
 */
class ClickController {

	public function register_routes(): void {

		register_rest_route(
			'tlt-search/v1',
			'/click',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'click' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function click( WP_REST_Request $request ): WP_REST_Response {

		$query      = trim( sanitize_text_field( (string) $request->get_param( 'q' ) ) );
		$product_id = absint( $request->get_param( 'product_id' ) );

		if ( '' === $query || ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false ], 400 );
		}

		if ( ! ( new RateLimiter() )->allow_request( $request ) ) {
			return new WP_REST_Response( [ 'success' => false ], 429 );
		}

		( new ClickLogger() )->log( $query, $product_id );

		return new WP_REST_Response( [ 'success' => true ] );
	}
}
