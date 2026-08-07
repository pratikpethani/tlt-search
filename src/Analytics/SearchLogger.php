<?php

namespace TLTSuite\TLTSearch\Analytics;

class SearchLogger {

	const TABLE_VERSION_OPTION = 'tlt_search_logs_db_version';
	const TABLE_VERSION        = '1.0';

	/**
	 * Create (or upgrade) the search log table using dbDelta.
	 * Safe to call multiple times — dbDelta is idempotent.
	 */
	public static function create_table(): void {

		global $wpdb;

		$table   = $wpdb->prefix . 'tlt_search_logs';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			query varchar(200) NOT NULL,
			results_count int(11) NOT NULL DEFAULT 0,
			searched_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_query (query(100)),
			KEY idx_searched_at (searched_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::TABLE_VERSION_OPTION, self::TABLE_VERSION );
	}

	/**
	 * Create the table only if the stored version does not match TABLE_VERSION.
	 * Called on admin_init so upgrades are applied without manual reactivation.
	 */
	public static function maybe_create_table(): void {

		if ( get_option( self::TABLE_VERSION_OPTION ) !== self::TABLE_VERSION ) {
			self::create_table();
		}
	}

	/**
	 * Record a single search event.
	 */
	public function log( string $query, int $results_count ): void {

		global $wpdb;

		$query = trim( $query );

		if ( '' === $query ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'tlt_search_logs',
			[
				'query'         => mb_substr( $query, 0, 200 ),
				'results_count' => max( 0, $results_count ),
				'searched_at'   => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s' ]
		);
	}

	/**
	 * Delete log entries older than $days days.
	 * Used as the WP-cron callback — non-static wrapper so the loader can hook it.
	 */
	public function run_cleanup(): void {
		self::delete_old_logs( 90 );
	}

	public static function delete_old_logs( int $days = 90 ): int {

		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}tlt_search_logs WHERE searched_at < %s",
				$cutoff
			)
		);
	}
}
