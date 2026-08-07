<?php

namespace TLTSuite\TLTSearch\Search;

use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;
use Loupe\Loupe\SearchParameters;
use TLTSuite\TLTSearch\Contracts\SearchEngineInterface;
use TLTSuite\TLTSearch\Support\Paths;

class LoupeEngine implements SearchEngineInterface {

	protected Loupe $loupe;

	public function __construct() {

		$language  = $this->get_site_language();
		$settings  = (array) get_option( 'tlt_search_settings', [] );
		$stopWords = $this->parse_stop_words( (string) ( $settings['stop_words'] ?? '' ) );

		$config = Configuration::create()
			->withPrimaryKey( 'id' )
			// Field order determines attribute weight: earlier = higher relevance boost.
			->withSearchableAttributes( [
				'title',
				'sku',
				'categories',
				'brands',
				'tags',
				'attribute_values',
				'short_description',
				'description',
			] )
			// Filterable attributes needed for Phase 7 facets.
			->withFilterableAttributes( [ 'price', 'stock_status', 'featured', 'categories', 'brands', 'tags' ] )
			// Sortable attributes needed for Phase 7 ordering.
			->withSortableAttributes( [ 'price', 'title' ] )
			// exactness first → exact matches rank above fuzzy/partial matches.
			->withRankingRules( [ 'exactness', 'attribute', 'words', 'typo', 'proximity' ] )
			->withLanguages( [ $language ] );

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

		$hits_per_page = min( (int) ( $options['hitsPerPage'] ?? 20 ), 1000 );

		$params = SearchParameters::create()
			->withQuery( $query )
			->withHitsPerPage( $hits_per_page );

		return $this->loupe->search( $params )->getHits();
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
