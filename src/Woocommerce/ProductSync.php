<?php

namespace TLTSuite\TLTSearch\WooCommerce;

use TLTSuite\TLTSearch\Indexing\IndexManager;

class ProductSync {

	protected IndexManager $manager;

	public function __construct() {

		$this->manager = new IndexManager();
	}

	public function product_updated( int $product_id ): void {

		$this->manager->update_product(
			$product_id
		);
	}

	public function product_deleted( int $product_id ): void {

		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$this->manager->delete_product(
			$product_id
		);
	}

}