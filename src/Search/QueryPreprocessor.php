<?php

namespace TLTSuite\TLTSearch\Search;

use TLTSuite\TLTSearch\Synonyms\SynonymManager;

class QueryPreprocessor {

	private array $synonyms = [];

	public function __construct() {
		$this->synonyms = ( new SynonymManager() )->get_synonym_map();
	}

	/**
	 * Normalise a raw query string: lowercase, strip special characters, collapse whitespace.
	 */
	public function normalize( string $query ): string {
		$query = mb_strtolower( trim( $query ) );
		$query = preg_replace( '/[^\p{L}\p{N}\s\-]/u', ' ', $query );
		$query = preg_replace( '/\s+/', ' ', $query );
		return trim( $query );
	}

	/**
	 * Return an array of queries to run against Loupe.
	 * First element is always the (normalised) original; subsequent elements are
	 * synonym-substituted alternatives so results can be merged.
	 *
	 * Multi-word synonyms (e.g. "fifty five") are matched against the full query
	 * string. Phrases are sorted longest-first so "fifty five" is matched before
	 * the single-word "fifty" or "five".
	 *
	 * @return string[]
	 */
	public function get_synonym_queries( string $query ): array {
		$queries = [ $query ];

		if ( empty( $this->synonyms ) ) {
			return $queries;
		}

		// Longest phrases first — prevents a single-word match clobbering a multi-word phrase.
		$phrases = array_keys( $this->synonyms );
		usort( $phrases, static fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );

		foreach ( $phrases as $phrase ) {

			// Check if the phrase appears in the query (word-boundary aware).
			if ( ! preg_match( '/\b' . preg_quote( $phrase, '/' ) . '\b/ui', $query ) ) {
				continue;
			}

			foreach ( $this->synonyms[ $phrase ] as $synonym ) {

				if ( $synonym === $phrase ) {
					continue;
				}

				$alt = trim( (string) preg_replace(
					'/\b' . preg_quote( $phrase, '/' ) . '\b/ui',
					$synonym,
					$query
				) );

				if ( $alt !== '' && $alt !== $query && ! in_array( $alt, $queries, true ) ) {
					$queries[] = $alt;
				}
			}
		}

		return $queries;
	}

}
