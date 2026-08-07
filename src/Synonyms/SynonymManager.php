<?php

namespace TLTSuite\TLTSearch\Synonyms;

class SynonymManager {

	const OPTION_KEY = 'tlt_search_synonym_groups';

	/**
	 * Returns all synonym groups as an array of term arrays.
	 * Auto-migrates from the legacy textarea on first call.
	 *
	 * @return array<int, string[]>
	 */
	public function get_groups(): array {

		$groups = get_option( self::OPTION_KEY, null );

		if ( null === $groups ) {
			$settings = (array) get_option( 'tlt_search_settings', [] );
			$groups   = $this->parse_textarea( (string) ( $settings['synonyms'] ?? '' ) );
			update_option( self::OPTION_KEY, $groups );
		}

		return is_array( $groups ) ? $groups : [];
	}

	/**
	 * Persist a complete list of synonym groups.
	 *
	 * @param array<int, string[]> $groups
	 */
	public function save_groups( array $groups ): void {
		update_option( self::OPTION_KEY, array_values( $groups ) );
	}

	/**
	 * Add a new synonym group.
	 *
	 * @param string[] $terms Comma-separated term strings or an already-split array.
	 */
	public function add_group( array $terms ): bool {

		$terms = array_values( array_unique( array_filter(
			array_map( 'mb_strtolower', array_map( 'trim', $terms ) )
		) ) );

		if ( count( $terms ) < 2 ) {
			return false;
		}

		$groups   = $this->get_groups();
		$groups[] = $terms;
		$this->save_groups( $groups );

		return true;
	}

	/**
	 * Remove the synonym group at the given zero-based index.
	 */
	public function delete_group( int $index ): void {

		$groups = $this->get_groups();

		if ( isset( $groups[ $index ] ) ) {
			array_splice( $groups, $index, 1 );
			$this->save_groups( $groups );
		}
	}

	/**
	 * Returns the word → group lookup consumed by QueryPreprocessor.
	 *
	 * @return array<string, string[]>
	 */
	public function get_synonym_map(): array {

		$map = [];

		foreach ( $this->get_groups() as $group ) {
			foreach ( $group as $term ) {
				$map[ $term ] = $group;
			}
		}

		return $map;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Parse the legacy textarea format into an array of term arrays.
	 *
	 * @return array<int, string[]>
	 */
	private function parse_textarea( string $raw ): array {

		$groups = [];

		foreach ( explode( "\n", $raw ) as $line ) {

			$line = trim( $line );

			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}

			$terms = array_values( array_unique( array_filter(
				array_map( 'mb_strtolower', array_map( 'trim', explode( ',', $line ) ) )
			) ) );

			if ( count( $terms ) >= 2 ) {
				$groups[] = $terms;
			}
		}

		return $groups;
	}
}
