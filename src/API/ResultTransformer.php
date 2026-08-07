<?php

namespace TLTSuite\TLTSearch\API;

class ResultTransformer {

	public function transform( array $document ): array {

		$product_id = absint(
			$document['id'] ?? 0
		);

		$raw_price = $document['price'] ?? '';

		return [
			'id'           => $product_id,
			'title'        => $document['title'] ?? '',
			'sku'          => $document['sku'] ?? '',
			'price'        => is_numeric( $raw_price ) ? wc_price( $raw_price ) : '',
			'stock_status' => $document['stock_status'] ?? '',
			'featured'     => (bool) ( $document['featured'] ?? false ),
			'permalink'    => get_permalink( $product_id ),
			'image'        => get_the_post_thumbnail_url( $product_id, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			'is_pinned'    => (bool) ( $document['_pinned'] ?? false ),
		];
	}

	public function transform_collection( array $documents ): array {

		return array_map(
			[ $this, 'transform' ],
			$documents
		);
	}
}