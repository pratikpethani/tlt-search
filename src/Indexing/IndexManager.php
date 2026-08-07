<?php

namespace TLTSuite\TLTSearch\Indexing;

use TLTSuite\TLTSearch\Search\LoupeEngine;
use TLTSuite\TLTSearch\WooCommerce\ProductDocumentMapper;

class IndexManager {

	protected LoupeEngine $engine;

	protected ProductDocumentMapper $mapper;

	public function __construct() {

		$this->engine = new LoupeEngine();

		$this->mapper = new ProductDocumentMapper();
	}

	public function index_product( int $product_id ): void {

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$document = $this->mapper->map( $product );

		$this->engine->index_document( $document );
	}

	public function update_product( int $product_id ): void {

		$this->delete_product( $product_id );

		$this->index_product( $product_id );
	}

	public function delete_product( int $product_id ): void {

		$this->engine->delete_document(
			(string) $product_id
		);
	}
}