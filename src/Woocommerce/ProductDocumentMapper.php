<?php

namespace TLTSuite\TLTSearch\WooCommerce;

use WC_Product;
use WC_Product_Attribute;

class ProductDocumentMapper {

	public function map( WC_Product $product ): array {

		$attributes = $this->get_attributes( $product );

		return [
			'id'                => (string) $product->get_id(),
			'title'             => $product->get_name(),
			'sku'               => $product->get_sku(),
			'description'       => wp_strip_all_tags(
				$product->get_description()
			),
			'short_description' => wp_strip_all_tags(
				$product->get_short_description()
			),
			'categories'        => $this->get_term_names(
				$product->get_id(),
				'product_cat'
			),
			'tags'              => $this->get_term_names(
				$product->get_id(),
				'product_tag'
			),
			'brands'            => $this->get_term_names(
				$product->get_id(),
				'product_brand'
			),
			'attribute_values'  => $attributes['values'],
			'attribute_map'     => $attributes['map'],
			'price'             => (float) $product->get_price(),
			'stock_status'      => $product->get_stock_status(),
			'featured'          => $product->is_featured(),
		];
	}

	protected function get_term_names( int $product_id, string $taxonomy ): array {

		$terms = get_the_terms(
			$product_id,
			$taxonomy
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		return wp_list_pluck(
			$terms,
			'name'
		);
	}

	protected function get_attributes( WC_Product $product ): array {

		$attribute_values = [];

		$attribute_map = [];

		foreach ( $product->get_attributes() as $attribute ) {

			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}

			$name = sanitize_title(
				$attribute->get_name()
			);

			$values = [];

			/**
			 * Global attribute.
			 *
			 * Example:
			 * pa_color
			 * pa_size
			 */
			if ( $attribute->is_taxonomy() ) {

				$terms = wp_get_post_terms(
					$product->get_id(),
					$attribute->get_name()
				);

				if ( ! is_wp_error( $terms ) ) {

					$values = wp_list_pluck(
						$terms,
						'name'
					);
				}
			} else {

				/**
				 * Custom product attribute.
				 */
				$values = $attribute->get_options();
			}

			if ( empty( $values ) ) {
				continue;
			}

			$values = array_values(
				array_unique( $values )
			);

			$attribute_map[ $name ] = $values;

			$attribute_values = array_merge(
				$attribute_values,
				$values
			);
		}

		return [
			'values' => array_values(
				array_unique( $attribute_values )
			),
			'map' => $attribute_map,
		];
	}
}