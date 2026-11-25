<?php

namespace TLTSuite\TLTSearch\Search;

class SearchService {

	protected SearchManager $manager;

	public function __construct() {

		$this->manager = new SearchManager();
	}

	public function search( string $query, array $options = [] ): array {

		return $this->manager->search( $query, $options );
	}

	public function search_native( string $query, array $filters, string $sort, int $page, int $per_page ): ?array {

		return $this->manager->search_native( $query, $filters, $sort, $page, $per_page );
	}

	public function search_with_facets( string $query, array $filters = [], array $options = [] ): array {

		return $this->manager->search_with_facets( $query, $filters, $options );
	}
}