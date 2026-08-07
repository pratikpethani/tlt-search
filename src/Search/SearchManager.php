<?php

namespace TLTSuite\TLTSearch\Search;

class SearchManager {

	protected LoupeEngine $engine;

	protected QueryPreprocessor $preprocessor;

	public function __construct() {
		$this->engine       = new LoupeEngine();
		$this->preprocessor = new QueryPreprocessor();
	}

	/**
	 * Search with facet computation and PHP-side filter application.
	 * Returns all matching hits (up to 1000), filtered by $filters, plus facet counts
	 * derived from the unfiltered hit set so counts reflect available options.
	 *
	 * @param  array  $filters  Keys: categories[], brands[], tags[], stock_status, price_min, price_max
	 * @return array{hits: array[], facets: array, total: int}
	 */
	public function search_with_facets( string $query, array $filters = [], array $options = [] ): array {

		$normalized = $this->preprocessor->normalize( $query );

		if ( '' === $normalized ) {
			return [ 'hits' => [], 'facets' => [], 'total' => 0 ];
		}

		$queries = $this->preprocessor->get_synonym_queries( $normalized );

		$seen     = [];
		$all_hits = [];

		foreach ( $queries as $q ) {
			foreach ( $this->engine->search( $q, [ 'hitsPerPage' => 1000 ] ) as $hit ) {
				$id = $hit['id'] ?? null;
				if ( null === $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$all_hits[]  = $hit;
			}
		}

		$builder  = new FacetBuilder();
		$facets   = $builder->compute_facets( $all_hits, $filters );
		$filtered = $builder->apply_filters( $all_hits, $filters );

		return [ 'hits' => $filtered, 'facets' => $facets, 'total' => count( $filtered ) ];
	}

	public function search( string $query, array $options = [] ): array {

		$normalized = $this->preprocessor->normalize( $query );

		if ( '' === $normalized ) {
			return [];
		}

		$queries = $this->preprocessor->get_synonym_queries( $normalized );

		if ( count( $queries ) === 1 ) {
			return $this->engine->search( $normalized, $options );
		}

		// Run each synonym variant and merge results, preserving original-query rank order.
		$seen    = [];
		$results = [];

		foreach ( $queries as $q ) {
			foreach ( $this->engine->search( $q, $options ) as $hit ) {
				$id = $hit['id'] ?? null;
				if ( null === $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$results[]   = $hit;
			}
		}

		return $results;
	}
}
