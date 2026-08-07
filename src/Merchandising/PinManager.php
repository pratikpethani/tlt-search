<?php

namespace TLTSuite\TLTSearch\Merchandising;

class PinManager {

	const TABLE_VERSION_OPTION = 'tlt_search_pins_db_version';
	const TABLE_VERSION        = '1.0';

	public static function create_table(): void {

		global $wpdb;

		$table           = $wpdb->prefix . 'tlt_search_pins';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			query_term VARCHAR(200)    NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			position   TINYINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY query_product   (query_term(100), product_id),
			KEY        query_position  (query_term(100), position)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::TABLE_VERSION_OPTION, self::TABLE_VERSION );
	}

	public static function maybe_create_table(): void {

		if ( get_option( self::TABLE_VERSION_OPTION ) !== self::TABLE_VERSION ) {
			self::create_table();
		}
	}

	/**
	 * Return all pins for a query, ordered by position then ID.
	 *
	 * @return array<int, array{id:string, product_id:string, position:string}>
	 */
	public function get_pins( string $query ): array {

		global $wpdb;

		$table = $wpdb->prefix . 'tlt_search_pins';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, product_id, position FROM {$table} WHERE query_term = %s ORDER BY position ASC, id ASC",
				$this->normalise_query( $query )
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Return just the product IDs pinned to a query, in position order.
	 *
	 * @return int[]
	 */
	public function get_pinned_product_ids( string $query ): array {

		return array_map( 'intval', array_column( $this->get_pins( $query ), 'product_id' ) );
	}

	/**
	 * Add a product to the pin list for a query.
	 * Uses REPLACE so re-pinning the same product just updates it.
	 */
	public function add_pin( string $query, int $product_id ): void {

		global $wpdb;

		$table = $wpdb->prefix . 'tlt_search_pins';
		$q     = $this->normalise_query( $query );

		$max_pos = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(position), -1) FROM {$table} WHERE query_term = %s",
				$q
			)
		);

		$wpdb->replace( $table, [
			'query_term' => $q,
			'product_id' => $product_id,
			'position'   => $max_pos + 1,
		] );
	}

	/**
	 * Remove a single pin by its row ID.
	 */
	public function remove_pin( int $pin_id ): void {

		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'tlt_search_pins', [ 'id' => $pin_id ] );
	}

	/**
	 * Return all pins across all queries, grouped by query_term.
	 *
	 * @return array<string, list<array{id:string, product_id:string, position:string}>>
	 */
	public function get_all_pins_grouped(): array {

		global $wpdb;

		$table = $wpdb->prefix . 'tlt_search_pins';
		$rows  = $wpdb->get_results(
			"SELECT id, query_term, product_id, position FROM {$table} ORDER BY query_term ASC, position ASC, id ASC",
			ARRAY_A
		) ?: [];

		$grouped = [];

		foreach ( $rows as $row ) {
			$grouped[ $row['query_term'] ][] = $row;
		}

		return $grouped;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private function normalise_query( string $query ): string {
		return mb_strtolower( trim( $query ) );
	}
}
