<?php

namespace TLTSuite\TLTSearch\Merchandising;

use TLTSuite\TLTSearch\Search\ResultCache;

/**
 * Site-wide boost/bury rules per product (relevance sort only).
 * Stored as one option: [ product_id => 'boost'|'bury' ].
 */
class RankingRules {

	const OPTION = 'tlt_search_ranking_rules';

	/** @return array<int,string> */
	public static function all(): array {

		$rules = get_option( self::OPTION, [] );

		return is_array( $rules ) ? array_filter( $rules, static fn( $m ) => 'boost' === $m || 'bury' === $m ) : [];
	}

	public static function set( int $product_id, string $mode ): void {

		if ( $product_id <= 0 || ! in_array( $mode, [ 'boost', 'bury' ], true ) ) {
			return;
		}

		$rules                = self::all();
		$rules[ $product_id ] = $mode;

		update_option( self::OPTION, $rules, false );
		ResultCache::bump();
	}

	public static function remove( int $product_id ): void {

		$rules = self::all();
		unset( $rules[ $product_id ] );

		update_option( self::OPTION, $rules, false );
		ResultCache::bump();
	}

	/**
	 * Reorder hits: pinned first (untouched), then boosted, normal, buried.
	 * Order inside each group stays as Loupe ranked it.
	 *
	 * @param array<int,array> $hits
	 * @param array<int,string>|null $rules Defaults to the stored rules.
	 */
	public static function apply( array $hits, ?array $rules = null ): array {

		$rules = $rules ?? self::all();

		if ( ! $rules ) {
			return $hits;
		}

		$groups = [ 'pin' => [], 'boost' => [], 'normal' => [], 'bury' => [] ];

		foreach ( $hits as $hit ) {
			$key              = ! empty( $hit['_pinned'] ) ? 'pin' : ( $rules[ (int) ( $hit['id'] ?? 0 ) ] ?? 'normal' );
			$groups[ $key ][] = $hit;
		}

		return array_merge( $groups['pin'], $groups['boost'], $groups['normal'], $groups['bury'] );
	}
}
