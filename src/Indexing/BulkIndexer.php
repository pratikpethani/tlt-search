<?php

namespace TLTSuite\TLTSearch\Indexing;

use TLTSuite\TLTSearch\Search\LoupeEngine;

class BulkIndexer {

	protected LoupeEngine $engine;

	public function __construct() {

		$this->engine = new LoupeEngine();
	}

	/**
	 * Clear the existing index and return the total number of published products.
	 * Call this once before starting a batch run.
	 */
	public function init_rebuild(): int {

		$this->engine->delete_index();
		$this->engine = new LoupeEngine();

		update_option( 'tlt_search_last_rebuilt', time() );

		return $this->count_published_products();
	}

	/**
	 * Index one batch of products and return how many were processed.
	 *
	 * @param int $offset    Number of products already indexed.
	 * @param int $per_batch Products to process in this request.
	 *
	 * @return array{ count: int, done: bool }
	 */
	public function index_batch( int $offset, int $per_batch = 100 ): array {

		$product_ids = wc_get_products( [
			'return' => 'ids',
			'status' => 'publish',
			'limit'  => $per_batch,
			'offset' => $offset,
		] );

		$manager = new IndexManager();
		$count   = 0;

		foreach ( $product_ids as $product_id ) {
			$manager->index_product( (int) $product_id );
			$count++;
		}

		return [
			'count' => $count,
			'done'  => $count < $per_batch,
		];
	}

	/**
	 * Rebuild the complete index in a single request.
	 * Fine for small catalogs; use init_rebuild + index_batch for large ones.
	 *
	 * @param int $limit Use -1 for all products.
	 */
	public function rebuild( int $limit = -1 ): int {

		$this->engine->delete_index();
		$this->engine = new LoupeEngine();

		$manager     = new IndexManager();
		$product_ids = wc_get_products( [
			'return' => 'ids',
			'status' => 'publish',
			'limit'  => $limit,
		] );

		$count = 0;

		foreach ( $product_ids as $product_id ) {
			$manager->index_product( (int) $product_id );
			$count++;
		}

		return $count;
	}

	/**
	 * Re-index (update) all published products in a given product category.
	 * Does not clear the full index — only refreshes the matched products.
	 *
	 * @param int $term_id product_cat term ID.
	 * @return int Number of products re-indexed.
	 */
	public function reindex_category( int $term_id ): int {

		$term = get_term( $term_id, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			return 0;
		}

		$product_ids = wc_get_products( [
			'return'   => 'ids',
			'status'   => 'publish',
			'limit'    => -1,
			'category' => [ $term->slug ],
		] );

		$manager = new IndexManager();
		$count   = 0;

		foreach ( $product_ids as $product_id ) {
			$manager->index_product( (int) $product_id );
			$count++;
		}

		return $count;
	}

	private function count_published_products(): int {
		$counts = wp_count_posts( 'product' );
		return (int) ( $counts->publish ?? 0 );
	}
}