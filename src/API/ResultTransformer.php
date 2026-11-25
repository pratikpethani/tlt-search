<?php

namespace TLTSuite\TLTSearch\API;

use TLTSuite\TLTSearch\Search\SearchOptions;

class ResultTransformer {

	public function transform( array $document ): array {

		$product_id = absint(
			$document['id'] ?? 0
		);

		$raw_price = $document['price'] ?? '';
		$product   = wc_get_product( $product_id );

		$data = [
			'id'           => $product_id,
			'title'        => $document['title'] ?? '',
			'sku'          => $document['sku'] ?? '',
			'price'        => is_numeric( $raw_price ) ? wc_price( $raw_price ) : '',
			'stock_status' => $document['stock_status'] ?? '',
			'featured'     => (bool) ( $document['featured'] ?? false ),
			'on_sale'      => $product ? $product->is_on_sale() : false,
			'permalink'    => get_permalink( $product_id ),
			'image'        => get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			'is_pinned'    => (bool) ( $document['_pinned'] ?? false ),
		];

		// Optional extras, only computed when switched on in settings.
		if ( $product && SearchOptions::show_rating() ) {
			$data['rating']       = (float) $product->get_average_rating();
			$data['rating_count'] = (int) $product->get_review_count();
		}

		if ( $product && SearchOptions::show_add_to_cart() ) {
			// WooCommerce builds a simple product's add-to-cart URL from the *current* URL,
			// which in a REST request is this search endpoint. Anchor it to the product page.
			$url = $product->is_type( 'simple' )
				? add_query_arg( 'add-to-cart', $product->get_id(), $product->get_permalink() )
				: $product->add_to_cart_url();

			$data['add_to_cart'] = ( $product->is_purchasable() && $product->is_in_stock() ) ? [
				'url'  => $url,
				'text' => $product->add_to_cart_text(),
			] : null;
		}

		return $data;
	}

	public function transform_collection( array $documents ): array {

		return array_map(
			[ $this, 'transform' ],
			$documents
		);
	}
}