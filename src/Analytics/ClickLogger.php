<?php

namespace TLTSuite\TLTSearch\Analytics;

/**
 * Records which product a shopper clicked after a search.
 */
class ClickLogger {

	const TABLE_VERSION_OPTION = 'tlt_search_clicks_db_version';
	const TABLE_VERSION        = '1.0';

	public static function create_table(): void {

		global $wpdb;

		$table   = $wpdb->prefix . 'tlt_search_clicks';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			query varchar(200) NOT NULL,
			product_id bigint(20) UNSIGNED NOT NULL,
			clicked_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_product (product_id),
			KEY idx_clicked_at (clicked_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::TABLE_VERSION_OPTION, self::TABLE_VERSION );
	}

	public static function maybe_create_table(): void {

		if ( get_option( self::TABLE_VERSION_OPTION ) !== self::TABLE_VERSION ) {
			self::create_table();
		}
	}

	public function log( string $query, int $product_id ): void {

		global $wpdb;

		$query = trim( $query );

		if ( '' === $query || $product_id <= 0 ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'tlt_search_clicks',
			[
				'query'       => mb_substr( $query, 0, 200 ),
				'product_id'  => $product_id,
				'clicked_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s' ]
		);
	}

	public static function delete_old_logs( int $days = 90 ): int {

		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->prefix}tlt_search_clicks WHERE clicked_at < %s", $cutoff )
		);
	}
}
