<?php

namespace TLTSuite\TLTSearch\Search;

/**
 * Short-lived cache of finished search responses.
 *
 * Every entry is keyed with a version number. Anything that can change results
 * (product sync, reindex, synonyms, pins, settings) calls bump(), which makes all
 * older entries unreachable at once; they then expire on their own.
 */
class ResultCache {

	private const VERSION_OPTION = 'tlt_search_cache_version';

	public static function bump(): void {
		update_option( self::VERSION_OPTION, uniqid( '', true ), 'yes' );
	}

	private static function ttl(): int {

		if ( ! SearchOptions::result_cache_enabled() ) {
			return 0;
		}

		return max( 0, (int) apply_filters( 'tlt_search_cache_ttl', 300 ) );
	}

	private function key( array $parts ): string {
		return 'tlt_sc_' . md5( (string) get_option( self::VERSION_OPTION, '' ) . wp_json_encode( $parts ) );
	}

	/** @return array|null Cached value, or null on a miss or when caching is off. */
	public function get( array $parts ): ?array {

		if ( 0 === self::ttl() ) {
			return null;
		}

		$value = get_transient( $this->key( $parts ) );

		return is_array( $value ) ? $value : null;
	}

	public function set( array $parts, array $value ): void {

		$ttl = self::ttl();

		if ( $ttl > 0 ) {
			set_transient( $this->key( $parts ), $value, $ttl );
		}
	}
}
