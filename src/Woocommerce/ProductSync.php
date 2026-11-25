<?php

namespace TLTSuite\TLTSearch\WooCommerce;

use TLTSuite\TLTSearch\Indexing\IndexManager;
use TLTSuite\TLTSearch\Support\Logger;
use WP_Post;
use WC_Product;

/**
 * Keeps the search index in step with the catalog.
 *
 * Changes (save, stock, status, variations) are queued and applied once per
 * product when the request ends, so one request that fires several hooks
 * re-indexes each product only once. Deletes are applied immediately.
 */
class ProductSync {

	protected ?IndexManager $manager = null;

	/** @var array<int,true> Product IDs waiting to be re-synced at shutdown. */
	private array $queue = [];

	private bool $shutdown_registered = false;

	// Hook callbacks -----------------------------------------------------

	public function product_updated( int $product_id ): void {
		$this->queue( $product_id );
	}

	/** woocommerce_product_set_stock / woocommerce_variation_set_stock */
	public function stock_changed( $product ): void {
		if ( $product instanceof WC_Product ) {
			$this->queue( $product->get_id() );
		}
	}

	/** woocommerce_product_set_stock_status / woocommerce_variation_set_stock_status */
	public function stock_status_changed( int $product_id ): void {
		$this->queue( $product_id );
	}

	/** transition_post_status: publish, unpublish, trash, restore. */
	public function status_changed( string $new_status, string $old_status, WP_Post $post ): void {
		if ( $new_status !== $old_status && in_array( $post->post_type, [ 'product', 'product_variation' ], true ) ) {
			$this->queue( (int) $post->ID );
		}
	}

	/** woocommerce_new_product_variation / woocommerce_update_product_variation */
	public function variation_saved( int $variation_id ): void {
		$this->queue( $variation_id );
	}

	/** before_delete_post: permanent deletion. */
	public function post_deleted( int $post_id, $post = null ): void {

		$type = $post instanceof WP_Post ? $post->post_type : get_post_type( $post_id );

		if ( 'product' === $type ) {
			unset( $this->queue[ $post_id ] );
			$this->run( fn() => $this->manager()->delete_product( $post_id ) );
		} elseif ( 'product_variation' === $type && $post instanceof WP_Post && $post->post_parent ) {
			$this->queue( (int) $post->post_parent );
		}
	}

	/** trashed_post: removes the product at once (also covered by status_changed). */
	public function product_deleted( int $product_id ): void {

		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		unset( $this->queue[ $product_id ] );
		$this->run( fn() => $this->manager()->delete_product( $product_id ) );
	}

	// Queue ----------------------------------------------------------------

	private function queue( int $id ): void {

		if ( $id <= 0 ) {
			return;
		}

		$this->queue[ $id ] = true;

		if ( ! $this->shutdown_registered ) {
			$this->shutdown_registered = true;
			add_action( 'shutdown', [ $this, 'flush' ] );
		}
	}

	public function flush(): void {

		$ids         = array_keys( $this->queue );
		$this->queue = [];

		foreach ( $ids as $id ) {
			$this->run( fn() => $this->manager()->sync_product( (int) $id ) );
		}
	}

	// Helpers --------------------------------------------------------------

	/** The index must never break a save, checkout or stock update. */
	private function run( callable $job ): void {

		try {
			$job();
		} catch ( \Throwable $e ) {
			Logger::error( 'Index sync failed: ' . $e->getMessage() );
		}
	}

	private function manager(): IndexManager {
		return $this->manager ??= new IndexManager();
	}
}
