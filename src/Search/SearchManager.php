<?php

namespace TLTSuite\TLTSearch\Search;

use Loupe\Loupe\SearchParameters;

class SearchManager {

	/** Allowed sort keys => Loupe sort rule. 'relevance' is the default ranking. */
	public const SORTS = [
		'price_asc'  => 'price:asc',
		'price_desc' => 'price:desc',
		'title_asc'  => 'title:asc',
	];

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
			foreach ( $this->engine->search( $q, [ 'hitsPerPage' => LoupeEngine::MAX_HITS, 'filter' => $this->language_filter( $filters ) ] ) as $hit ) {
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

		// Relevance order is already the default; other sorts are applied here.
		$sort = (string) ( $options['sort'] ?? 'relevance' );
		if ( isset( self::SORTS[ $sort ] ) ) {
			[ $field, $dir ] = explode( ':', self::SORTS[ $sort ] );
			usort( $filtered, static function ( array $a, array $b ) use ( $field, $dir ): int {
				$cmp = 'price' === $field
					? (float) ( $a['price'] ?? 0 ) <=> (float) ( $b['price'] ?? 0 )
					: strcasecmp( (string) ( $a['title'] ?? '' ), (string) ( $b['title'] ?? '' ) );
				return 'desc' === $dir ? -$cmp : $cmp;
			} );
		}

		return [ 'hits' => $filtered, 'facets' => $facets, 'total' => count( $filtered ) ];
	}

	/**
	 * Search using Loupe's own filtering, sorting, paging and facets.
	 *
	 * Returns null when synonyms expand the query into several searches; those
	 * results must be merged in PHP, so the caller falls back to search_with_facets().
	 *
	 * @return array{hits: array[], facets: array, total: int}|null
	 */
	public function search_native( string $query, array $filters, string $sort, int $page, int $per_page ): ?array {

		$normalized = $this->preprocessor->normalize( $query );

		if ( '' === $normalized ) {
			return [ 'hits' => [], 'facets' => [], 'total' => 0 ];
		}

		if ( count( $this->preprocessor->get_synonym_queries( $normalized ) ) > 1 ) {
			return null;
		}

		$params = SearchParameters::create()
			->withQuery( $normalized )
			->withHitsPerPage( $per_page )
			->withPage( $page );

		$filter = $this->build_filter( $filters );
		if ( '' !== $filter ) {
			$params = $params->withFilter( $filter );
		}
		if ( isset( self::SORTS[ $sort ] ) ) {
			$params = $params->withSort( [ self::SORTS[ $sort ] ] );
		}

		$result = $this->engine->query( $params );

		return [
			'hits'   => $result->getHits(),
			'facets' => $this->native_facets( $normalized, $filters ),
			'total'  => $result->getTotalHits(),
		];
	}

	/**
	 * Facet counts for the query WITHOUT the active filters, so every option stays
	 * visible. Price range comes from the cheapest and dearest match.
	 */
	private function native_facets( string $query, array $filters ): array {

		$base = SearchParameters::create()->withQuery( $query )->withHitsPerPage( 1 );

		$lang_filter = $this->language_filter( $filters );
		if ( '' !== $lang_filter ) {
			$base = $base->withFilter( $lang_filter );
		}

		$dist = $this->engine->query(
			$base->withFacets( [ 'categories', 'brands', 'tags', 'stock_status' ] )
		)->getFacetDistribution();

		$low  = $this->engine->query( $base->withSort( [ 'price:asc' ] ) )->getHits();
		$high = $this->engine->query( $base->withSort( [ 'price:desc' ] ) )->getHits();

		$range = ( isset( $low[0]['price'], $high[0]['price'] ) )
			? [ 'min' => (float) $low[0]['price'], 'max' => (float) $high[0]['price'] ]
			: null;

		return ( new FacetBuilder() )->from_distribution( $dist, $filters, $range );
	}

	/**
	 * Translate request filters into a Loupe filter string.
	 * Values within one facet are OR-ed; different facets are AND-ed.
	 */
	private function build_filter( array $filters ): string {

		$esc    = static fn( $v ): string => SearchParameters::escapeFilterValue( (string) $v );
		$clauses = [];

		$lang_filter = $this->language_filter( $filters );
		if ( '' !== $lang_filter ) {
			$clauses[] = $lang_filter;
		}

		foreach ( [ 'categories', 'brands', 'tags' ] as $attr ) {
			$values = array_filter( (array) ( $filters[ $attr ] ?? [] ), static fn( $v ) => '' !== (string) $v );
			if ( $values ) {
				$clauses[] = '(' . implode( ' OR ', array_map( static fn( $v ) => $attr . ' = ' . $esc( $v ), $values ) ) . ')';
			}
		}

		if ( ! empty( $filters['stock_status'] ) ) {
			$clauses[] = 'stock_status = ' . $esc( $filters['stock_status'] );
		}
		if ( isset( $filters['price_min'] ) && '' !== (string) $filters['price_min'] ) {
			$clauses[] = 'price >= ' . (float) $filters['price_min'];
		}
		if ( isset( $filters['price_max'] ) && '' !== (string) $filters['price_max'] ) {
			$clauses[] = 'price <= ' . (float) $filters['price_max'];
		}

		return implode( ' AND ', $clauses );
	}

	/** Restrict to one language when WPML/Polylang is active and a language is known. */
	private function language_filter( array $filters ): string {

		$lang = (string) ( $filters['lang'] ?? '' );

		return '' === $lang ? '' : 'lang = ' . SearchParameters::escapeFilterValue( $lang );
	}

	public function search( string $query, array $options = [] ): array {

		$normalized = $this->preprocessor->normalize( $query );

		if ( '' === $normalized ) {
			return [];
		}

		$queries = $this->preprocessor->get_synonym_queries( $normalized );

		$options['filter'] = $this->language_filter( [ 'lang' => \TLTSuite\TLTSearch\Support\Multilingual::current() ] );

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
