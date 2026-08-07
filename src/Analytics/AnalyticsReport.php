<?php

namespace TLTSuite\TLTSearch\Analytics;

class AnalyticsReport {

	/**
	 * High-level summary counts for the last $days days.
	 *
	 * @return array{total_searches: int, unique_queries: int, with_results_count: int, zero_result_count: int}
	 */
	public function summary( int $days = 30 ): array {

		global $wpdb;

		$since = $this->since( $days );
		$table = $wpdb->prefix . 'tlt_search_logs';

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(*)                                              AS total_searches,
				COUNT(DISTINCT query)                                 AS unique_queries,
				SUM(CASE WHEN results_count  > 0 THEN 1 ELSE 0 END)  AS with_results_count,
				SUM(CASE WHEN results_count  = 0 THEN 1 ELSE 0 END)  AS zero_result_count
			FROM {$table}
			WHERE searched_at >= %s",
			$since
		) );

		return [
			'total_searches'     => (int) ( $row->total_searches ?? 0 ),
			'unique_queries'     => (int) ( $row->unique_queries ?? 0 ),
			'with_results_count' => (int) ( $row->with_results_count ?? 0 ),
			'zero_result_count'  => (int) ( $row->zero_result_count ?? 0 ),
		];
	}

	/**
	 * Most-searched queries that returned at least one result.
	 *
	 * @return array<array{query: string, search_count: int, avg_results: float, last_searched: string}>
	 */
	public function popular_searches( int $days = 30, int $limit = 20 ): array {

		global $wpdb;

		$since = $this->since( $days );
		$table = $wpdb->prefix . 'tlt_search_logs';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				query,
				COUNT(*)         AS search_count,
				AVG(results_count) AS avg_results,
				MAX(searched_at) AS last_searched
			FROM {$table}
			WHERE searched_at >= %s AND results_count > 0
			GROUP BY query
			ORDER BY search_count DESC
			LIMIT %d",
			$since,
			$limit
		), ARRAY_A );

		return $rows ?: [];
	}

	/**
	 * Queries that consistently returned zero results.
	 *
	 * @return array<array{query: string, search_count: int, last_searched: string}>
	 */
	public function no_result_searches( int $days = 30, int $limit = 20 ): array {

		global $wpdb;

		$since = $this->since( $days );
		$table = $wpdb->prefix . 'tlt_search_logs';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				query,
				COUNT(*)         AS search_count,
				MAX(searched_at) AS last_searched
			FROM {$table}
			WHERE searched_at >= %s AND results_count = 0
			GROUP BY query
			ORDER BY search_count DESC
			LIMIT %d",
			$since,
			$limit
		), ARRAY_A );

		return $rows ?: [];
	}

	/**
	 * Daily search volume for charting/display purposes.
	 *
	 * @return array<array{search_date: string, search_count: int}>
	 */
	public function search_volume_by_day( int $days = 30 ): array {

		global $wpdb;

		$since = $this->since( $days );
		$table = $wpdb->prefix . 'tlt_search_logs';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				DATE(searched_at) AS search_date,
				COUNT(*)          AS search_count
			FROM {$table}
			WHERE searched_at >= %s
			GROUP BY DATE(searched_at)
			ORDER BY search_date ASC",
			$since
		), ARRAY_A );

		return $rows ?: [];
	}

	private function since( int $days ): string {
		return gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
	}
}
