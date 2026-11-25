<?php

namespace TLTSuite\TLTSearch\Search;

class FacetBuilder {

	/**
	 * Compute facet counts from a full (unfiltered) hit set.
	 * Marks each option as selected if it appears in $active_filters.
	 *
	 * @param array[] $hits
	 * @param array   $active_filters  Keys: categories[], brands[], tags[], stock_status, price_min, price_max
	 * @return array{categories: list, brands: list, tags: list, stock_status: list, price: array|null}
	 */
	public function compute_facets( array $hits, array $active_filters = [] ): array {

		$cats   = [];
		$brands = [];
		$tags   = [];
		$stock  = [];
		$prices = [];

		foreach ( $hits as $hit ) {
			foreach ( (array) ( $hit['categories'] ?? [] ) as $v ) {
				$v = (string) $v;
				if ( '' !== $v ) {
					$cats[ $v ] = ( $cats[ $v ] ?? 0 ) + 1;
				}
			}
			foreach ( (array) ( $hit['brands'] ?? [] ) as $v ) {
				$v = (string) $v;
				if ( '' !== $v ) {
					$brands[ $v ] = ( $brands[ $v ] ?? 0 ) + 1;
				}
			}
			foreach ( (array) ( $hit['tags'] ?? [] ) as $v ) {
				$v = (string) $v;
				if ( '' !== $v ) {
					$tags[ $v ] = ( $tags[ $v ] ?? 0 ) + 1;
				}
			}
			$s = (string) ( $hit['stock_status'] ?? '' );
			if ( '' !== $s ) {
				$stock[ $s ] = ( $stock[ $s ] ?? 0 ) + 1;
			}
			if ( isset( $hit['price'] ) && is_numeric( $hit['price'] ) ) {
				$prices[] = (float) $hit['price'];
			}
		}

		arsort( $cats );
		arsort( $brands );
		arsort( $tags );
		arsort( $stock );

		$sel_cats   = array_map( 'strval', (array) ( $active_filters['categories'] ?? [] ) );
		$sel_brands = array_map( 'strval', (array) ( $active_filters['brands'] ?? [] ) );
		$sel_tags   = array_map( 'strval', (array) ( $active_filters['tags'] ?? [] ) );
		$sel_stock  = (string) ( $active_filters['stock_status'] ?? '' );

		$price_data = null;
		if ( ! empty( $prices ) ) {
			$price_data = [
				'min'          => round( (float) min( $prices ), 2 ),
				'max'          => round( (float) max( $prices ), 2 ),
				'selected_min' => ( isset( $active_filters['price_min'] ) && '' !== (string) $active_filters['price_min'] )
					? (float) $active_filters['price_min']
					: null,
				'selected_max' => ( isset( $active_filters['price_max'] ) && '' !== (string) $active_filters['price_max'] )
					? (float) $active_filters['price_max']
					: null,
			];
		}

		return [
			'categories'  => $this->build_option_list( $cats, $sel_cats ),
			'brands'      => $this->build_option_list( $brands, $sel_brands ),
			'tags'        => $this->build_option_list( $tags, $sel_tags ),
			'stock_status' => $this->build_option_list( $stock, '' !== $sel_stock ? [ $sel_stock ] : [] ),
			'price'       => $price_data,
		];
	}

	/**
	 * Build the facet payload from Loupe's native facet distribution.
	 *
	 * @param array<string,array<string,int>> $dist   attribute => value => count
	 * @param array                           $active Active filters (same keys as apply_filters)
	 * @param array{min:float,max:float}|null $range  Price range of all matching products
	 */
	public function from_distribution( array $dist, array $active, ?array $range ): array {

		$sel = static fn( string $key ): array => array_map( 'strval', (array) ( $active[ $key ] ?? [] ) );
		$stock = (string) ( $active['stock_status'] ?? '' );

		$counts = static function ( array $values ): array {
			$values = array_filter( $values, static fn( $c, $v ) => '' !== (string) $v && $c > 0, ARRAY_FILTER_USE_BOTH );
			arsort( $values );
			return $values;
		};

		$price = null;
		if ( null !== $range ) {
			$price = [
				'min'          => round( $range['min'], 2 ),
				'max'          => round( $range['max'], 2 ),
				'selected_min' => ( isset( $active['price_min'] ) && '' !== (string) $active['price_min'] ) ? (float) $active['price_min'] : null,
				'selected_max' => ( isset( $active['price_max'] ) && '' !== (string) $active['price_max'] ) ? (float) $active['price_max'] : null,
			];
		}

		return [
			'categories'   => $this->build_option_list( $counts( (array) ( $dist['categories'] ?? [] ) ), $sel( 'categories' ) ),
			'brands'       => $this->build_option_list( $counts( (array) ( $dist['brands'] ?? [] ) ), $sel( 'brands' ) ),
			'tags'         => $this->build_option_list( $counts( (array) ( $dist['tags'] ?? [] ) ), $sel( 'tags' ) ),
			'stock_status' => $this->build_option_list( $counts( (array) ( $dist['stock_status'] ?? [] ) ), '' !== $stock ? [ $stock ] : [] ),
			'price'        => $price,
		];
	}

	/**
	 * Filter a hit set in PHP according to the given filters.
	 * Returns hits unchanged if no filters are active.
	 *
	 * @param array[] $hits
	 * @param array   $filters
	 * @return array[]
	 */
	public function apply_filters( array $hits, array $filters ): array {

		$categories = array_filter( array_map( 'strval', (array) ( $filters['categories'] ?? [] ) ) );
		$brands     = array_filter( array_map( 'strval', (array) ( $filters['brands'] ?? [] ) ) );
		$tags       = array_filter( array_map( 'strval', (array) ( $filters['tags'] ?? [] ) ) );
		$stock      = (string) ( $filters['stock_status'] ?? '' );
		$price_min  = ( isset( $filters['price_min'] ) && '' !== (string) $filters['price_min'] )
			? (float) $filters['price_min'] : null;
		$price_max  = ( isset( $filters['price_max'] ) && '' !== (string) $filters['price_max'] )
			? (float) $filters['price_max'] : null;

		if ( empty( $categories ) && empty( $brands ) && empty( $tags )
			&& '' === $stock && null === $price_min && null === $price_max ) {
			return $hits;
		}

		return array_values( array_filter( $hits, function ( array $hit ) use ( $categories, $brands, $tags, $stock, $price_min, $price_max ): bool {

			if ( ! empty( $categories )
				&& ! array_intersect( $categories, array_map( 'strval', (array) ( $hit['categories'] ?? [] ) ) ) ) {
				return false;
			}
			if ( ! empty( $brands )
				&& ! array_intersect( $brands, array_map( 'strval', (array) ( $hit['brands'] ?? [] ) ) ) ) {
				return false;
			}
			if ( ! empty( $tags )
				&& ! array_intersect( $tags, array_map( 'strval', (array) ( $hit['tags'] ?? [] ) ) ) ) {
				return false;
			}
			if ( '' !== $stock && (string) ( $hit['stock_status'] ?? '' ) !== $stock ) {
				return false;
			}
			$price = (float) ( $hit['price'] ?? 0 );
			if ( null !== $price_min && $price < $price_min ) {
				return false;
			}
			if ( null !== $price_max && $price > $price_max ) {
				return false;
			}

			return true;
		} ) );
	}

	/**
	 * @param array<string,int> $counts  value → count
	 * @param string[]          $selected
	 */
	private function build_option_list( array $counts, array $selected ): array {
		$list = [];
		foreach ( $counts as $value => $count ) {
			$list[] = [
				'value'    => (string) $value,
				'count'    => $count,
				'selected' => in_array( (string) $value, $selected, true ),
			];
		}
		return $list;
	}
}
