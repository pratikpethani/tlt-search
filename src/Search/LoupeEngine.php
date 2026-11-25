<?php

namespace TLTSuite\TLTSearch\Search;

use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;
use Loupe\Loupe\SearchParameters;
use TLTSuite\TLTSearch\Contracts\SearchEngineInterface;
use TLTSuite\TLTSearch\Support\Paths;

class LoupeEngine implements SearchEngineInterface {

	/** Most results one search can return (Loupe's default is 1000). */
	public const MAX_HITS = 10000;

	protected Loupe $loupe;

	public function __construct() {

		$language  = $this->get_site_language();
		$settings  = (array) get_option( 'tlt_search_settings', [] );
		$stopWords = $this->parse_stop_words( (string) ( $settings['stop_words'] ?? '' ) );

		$config = Configuration::create()
			->withPrimaryKey( 'id' )
			// Field order determines attribute weight: earlier = higher relevance boost.
			// Chosen and ordered on the Settings page.
			->withSearchableAttributes( SearchFields::enabled() )
			// Filterable attributes needed for Phase 7 facets.
			->withFilterableAttributes( [ 'price', 'stock_status', 'featured', 'categories', 'brands', 'tags', 'lang' ] )
			// Sortable attributes needed for Phase 7 ordering.
			->withSortableAttributes( [ 'price', 'title' ] )
			// exactness first → exact matches rank above fuzzy/partial matches.
			->withRankingRules( [ 'exactness', 'attribute', 'words', 'typo', 'proximity' ] )
			->withMaxTotalHits( self::MAX_HITS )
			->withLanguages( $this->get_languages( $language ) );

		if ( ! SearchOptions::typo_tolerance() ) {
			$config = $config->withTypoTolerance( \Loupe\Loupe\Config\TypoTolerance::disabled() );
		}

		if ( ! empty( $stopWords ) ) {
			$config = $config->withStopWords( $stopWords );
		}

		$this->loupe = ( new LoupeFactory() )->create( Paths::index_directory(), $config );
	}

	public function create_index(): void {
		// Loupe creates automatically.
	}

	public function delete_index(): void {
		\TLTSuite\TLTSearch\Support\FileSystem::delete_directory(
			\TLTSuite\TLTSearch\Support\Paths::index_directory()
		);
	}

	public function index_document( array $document ): void {
		$this->loupe->addDocuments( [ $document ] );
	}

	public function delete_document( string $document_id ): void {
		$this->loupe->deleteDocuments( [ $document_id ] );
	}

	public function count_documents(): int {
		return $this->loupe->countDocuments();
	}

	public function needs_reindex(): bool {
		return $this->loupe->needsReindex();
	}

	public function search( string $query, array $options = [] ): array {

		$limit    = min( (int) ( $options['hitsPerPage'] ?? 20 ), self::MAX_HITS );
		$per_page = min( $limit, 1000 );
		$hits     = [];

		for ( $page = 1; count( $hits ) < $limit; $page++ ) {

			$params = SearchParameters::create()
				->withQuery( $query )
				->withHitsPerPage( $per_page )
				->withPage( $page );

			if ( ! empty( $options['filter'] ) ) {
				$params = $params->withFilter( (string) $options['filter'] );
			}

			$result = $this->query( $params );
			$hits   = array_merge( $hits, $result->getHits() );

			if ( $page >= $result->getTotalPages() ) {
				break;
			}
		}

		return array_slice( $hits, 0, $limit );
	}

	/**
	 * Run a fully-built Loupe query and return the raw result (hits, totals, facets).
	 */
	public function query( SearchParameters $params ): \Loupe\Loupe\SearchResult {
		// Every query honors the admin's AND/OR choice.
		return $this->loupe->search( $params->withMatchingStrategy( SearchOptions::match_strategy() ) );
	}

	/** Site language first, then any other supported language used by WPML/Polylang. */
	private function get_languages( string $site_language ): array {

		$supported = [ 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'sv', 'no', 'da', 'fi', 'ru', 'hu', 'ro' ];
		$extra     = array_intersect( \TLTSuite\TLTSearch\Support\Multilingual::languages(), $supported );

		return array_values( array_unique( array_merge( [ $site_language ], $extra ) ) );
	}

	private function get_site_language(): string {

		$wplang = (string) get_option( 'WPLANG', 'en_US' );
		$lang    = explode( '_', $wplang )[0];

		$supported = [ 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'sv', 'no', 'da', 'fi', 'ru', 'hu', 'ro' ];

		return in_array( $lang, $supported, true ) ? $lang : 'en';
	}

	private function parse_stop_words( string $raw ): array {

		if ( '' === trim( $raw ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
	}
}
