<?php

namespace TLTSuite\TLTSearch\Indexing;

use TLTSuite\TLTSearch\Search\LoupeEngine;
use TLTSuite\TLTSearch\Search\SearchOptions;
use TLTSuite\TLTSearch\WooCommerce\ProductDocumentMapper;

class IndexManager {

	protected LoupeEngine $engine;

	protected ProductDocumentMapper $mapper;

	public function __construct() {

		$this->engine = new LoupeEngine();

		$this->mapper = new ProductDocumentMapper();
	}

	/**
	 * Only published products that can appear in search results are indexed.
	 */
	public function is_indexable( \WC_Product $product ): bool {

		if ( 'publish' !== $product->get_status()
			|| ! in_array( $product->get_catalog_visibility(), [ 'visible', 'search' ], true ) ) {
			return false;
		}

		// Exclusions chosen on the Settings page.
		if ( in_array( $product->get_id(), SearchOptions::excluded_product_ids(), true ) ) {
			return false;
		}

		if ( SearchOptions::hide_out_of_stock() && 'outofstock' === $product->get_stock_status() ) {
			return false;
		}

		$categories = SearchOptions::excluded_category_slugs();

		return ! ( $categories && has_term( $categories, 'product_cat', $product->get_id() ) );
	}

	/**
	 * Bring one product's index entry in line with its current state:
	 * re-add it if indexable, otherwise remove it. Variations resolve to their parent.
	 */
	public function sync_product( int $product_id ): void {

		$product = wc_get_product( $product_id );

		if ( $product && $product->is_type( 'variation' ) ) {
			$product_id = $product->get_parent_id();
			$product    = $product_id ? wc_get_product( $product_id ) : false;
		}

		if ( ! $product_id ) {
			return;
		}

		$this->delete_product( $product_id );

		if ( $product && $this->is_indexable( $product ) ) {
			$this->index_product( $product_id );
		}
	}

	public function index_product( int $product_id ): void {

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $this->is_indexable( $product ) ) {
			return;
		}

		$document = $this->mapper->map( $product );

		$this->engine->index_document( $document );
	}

	public function update_product( int $product_id ): void {

		$this->sync_product( $product_id );
	}

	public function delete_product( int $product_id ): void {

		$this->engine->delete_document(
			(string) $product_id
		);
	}
}