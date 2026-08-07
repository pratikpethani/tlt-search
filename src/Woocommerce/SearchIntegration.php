<?php

namespace TLTSuite\TLTSearch\WooCommerce;

use TLTSuite\TLTSearch\Search\SearchService;
use TLTSuite\TLTSearch\Analytics\SearchLogger;
use TLTSuite\TLTSearch\Support\Logger;
use WP_Query;

class SearchIntegration {

	private array $options;

	private bool $intercepted = false;

	public function __construct() {
		$this->options = (array) get_option( 'tlt_search_settings', [] );
	}

	public function intercept( WP_Query $query ): void {

		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		if ( empty( $this->options['enable_search_replacement'] ) ) {
			return;
		}

		$search_term = trim( $query->get( 's' ) );

		if ( '' === $search_term ) {
			return;
		}

		if ( $this->is_product_query( $query ) ) {

			if ( empty( $this->options['replace_product_search'] ) ) {
				return;
			}
		} else {

			if ( empty( $this->options['replace_header_search'] ) ) {
				return;
			}

			$query->set( 'post_type', 'product' );
		}

		$this->apply_loupe_results( $query, $search_term );
	}

	private function apply_loupe_results( WP_Query $query, string $search_term ): void {

		try {

			// Fetch all matching IDs so WooCommerce can handle pagination itself.
			$service = new SearchService();
			$results = $service->search( $search_term, [ 'hitsPerPage' => 10000 ] );

			if ( empty( $results ) ) {

				if ( ! empty( $this->options['fallback_on_empty'] ) ) {
					// Delegating to WC native search — skip logging (result count unknown).
					return;
				}

				( new SearchLogger() )->log( $search_term, 0 );

				$query->set( 'post__in', [ 0 ] );
				// Keep 's' so the "no results" page still shows the search term.
				$this->intercepted = true;
				add_filter( 'posts_search', [ $this, 'remove_search_sql' ], 10, 2 );
				return;
			}

			( new SearchLogger() )->log( $search_term, count( $results ) );

			$ids = array_values(
				array_filter(
					array_map( 'absint', array_column( $results, 'id' ) )
				)
			);

			$query->set( 'post__in', $ids );
			$query->set( 'orderby', 'post__in' );

			// Preserve 's' for page title display ("Search results for: tote").
			// Remove the SQL LIKE clause that WordPress would otherwise add.
			$this->intercepted = true;
			add_filter( 'posts_search', [ $this, 'remove_search_sql' ], 10, 2 );

		} catch ( \Exception $e ) {

			Logger::error(
				'Search interception failed: ' . $e->getMessage(),
				[ 'query' => $search_term ]
			);
		}
	}

	public function remove_search_sql( string $search, WP_Query $query ): string {

		if ( $this->intercepted && $query->is_main_query() ) {
			return '';
		}

		return $search;
	}

	private function is_product_query( WP_Query $query ): bool {

		$post_type = $query->get( 'post_type' );

		if ( 'product' === $post_type ) {
			return true;
		}

		if ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) {
			return true;
		}

		return false;
	}
}
