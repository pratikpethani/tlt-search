<?php

namespace TLTSuite\TLTSearch\WooCommerce;

use TLTSuite\TLTSearch\Search\SearchFields;
use TLTSuite\TLTSearch\Search\SearchOptions;
use WC_Product;
use WC_Product_Attribute;

class ProductDocumentMapper {

	public function map( WC_Product $product ): array {

		$attributes = $this->get_attributes( $product );

		return [
			'id'                => (string) $product->get_id(),
			'title'             => $product->get_name(),
			'sku'               => $product->get_sku(),
			'lang'              => \TLTSuite\TLTSearch\Support\Multilingual::post_language( $product->get_id() ),
			'gtin'              => $this->get_gtin( $product ),
			'custom_fields'     => $this->get_custom_fields( $product ),
			'variations'        => $this->get_variation_terms( $product ),
			'taxonomy_terms'    => $this->get_custom_taxonomy_terms( $product->get_id() ),
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

	protected function get_gtin( WC_Product $product ): string {

		return method_exists( $product, 'get_global_unique_id' )
			? (string) $product->get_global_unique_id()
			: (string) $product->get_meta( '_global_unique_id' );
	}

	/**
	 * Values of the meta keys chosen in settings, flattened to a list of strings.
	 */
	protected function get_custom_fields( WC_Product $product ): array {

		$values = [];

		foreach ( SearchFields::custom_field_keys() as $key ) {
			$value = $product->get_meta( $key );
			if ( is_array( $value ) ) {
				array_walk_recursive( $value, static function ( $v ) use ( &$values ) {
					if ( is_scalar( $v ) && '' !== (string) $v ) {
						$values[] = (string) $v;
					}
				} );
			} elseif ( is_scalar( $value ) && '' !== (string) $value ) {
				$values[] = (string) $value;
			}
		}

		return array_values( array_unique( $values ) );
	}

	/**
	 * SKUs, GTINs and attribute values of a variable product's published variations,
	 * so a search for a variation SKU or "XL" finds the parent product.
	 */
	protected function get_variation_terms( WC_Product $product ): array {

		if ( ! $product->is_type( 'variable' ) ) {
			return [];
		}

		$out = [];

		foreach ( array_slice( $product->get_children(), 0, 200 ) as $variation_id ) {

			$variation = wc_get_product( $variation_id );

			if ( ! $variation || 'publish' !== $variation->get_status() ) {
				continue;
			}

			$out[] = $variation->get_sku();
			$out[] = $this->get_gtin( $variation );

			foreach ( $variation->get_attributes() as $taxonomy => $value ) {

				if ( '' === (string) $value ) {
					continue;
				}

				$term  = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $value, $taxonomy ) : false;
				$out[] = $term ? $term->name : (string) $value;
			}
		}

		return array_values( array_unique( array_filter( $out, static fn( $v ) => '' !== (string) $v ) ) );
	}

	/** Terms of the extra taxonomies chosen in settings. */
	protected function get_custom_taxonomy_terms( int $product_id ): array {

		$names = [];

		foreach ( SearchOptions::custom_taxonomies() as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$names = array_merge( $names, $this->get_term_names( $product_id, $taxonomy ) );
			}
		}

		return array_values( array_unique( $names ) );
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