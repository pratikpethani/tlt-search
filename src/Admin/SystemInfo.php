<?php

namespace TLTSuite\TLTSearch\Admin;

use TLTSuite\TLTSearch\Search\LoupeEngine;
use TLTSuite\TLTSearch\Support\Paths;

class SystemInfo {

	public function system_checks(): array {

		$index_dir = Paths::index_directory();

		$pdo_sqlite = extension_loaded( 'pdo' ) && in_array( 'sqlite', \PDO::getAvailableDrivers(), true );

		return [
			[
				'label' => 'PHP Version (≥ 8.1)',
				'pass'  => version_compare( PHP_VERSION, '8.1.0', '>=' ),
				'value' => PHP_VERSION,
			],
			[
				'label' => 'PDO Extension',
				'pass'  => extension_loaded( 'pdo' ),
				'value' => extension_loaded( 'pdo' ) ? 'Loaded' : 'Missing',
			],
			[
				'label' => 'PDO SQLite Driver',
				'pass'  => $pdo_sqlite,
				'value' => $pdo_sqlite ? 'Available' : 'Missing',
			],
			[
				'label' => 'Index Directory Writable',
				'pass'  => is_writable( $index_dir ),
				'value' => $index_dir,
			],
		];
	}

	public function index_info(): array {

		$index_dir     = Paths::index_directory();
		$catalog_count = (int) ( wp_count_posts( 'product' )->publish ?? 0 );
		$indexed_count = 0;
		$needs_reindex = false;

		try {
			$engine        = new LoupeEngine();
			$indexed_count = $engine->count_documents();
			$needs_reindex = $engine->needs_reindex();
		} catch ( \Throwable $e ) {
			// Engine unavailable — leave defaults.
		}

		return [
			'index_dir'     => $index_dir,
			'index_size'    => $this->directory_size( $index_dir ),
			'indexed_count' => $indexed_count,
			'catalog_count' => $catalog_count,
			'needs_reindex' => $needs_reindex,
			'last_rebuilt'  => (int) get_option( 'tlt_search_last_rebuilt', 0 ),
		];
	}

	public function cron_info(): array {

		$next_cleanup = wp_next_scheduled( 'tlt_search_cleanup_logs' );

		return [
			'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'next_cleanup'  => $next_cleanup ?: null,
		];
	}

	public function analytics_info(): array {

		global $wpdb;
		$table  = $wpdb->prefix . 'tlt_search_logs';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

		return [ 'table_exists' => $exists ];
	}

	public static function format_bytes( int $bytes ): string {

		if ( $bytes >= 1048576 ) {
			return round( $bytes / 1048576, 2 ) . ' MB';
		}

		if ( $bytes >= 1024 ) {
			return round( $bytes / 1024, 1 ) . ' KB';
		}

		return $bytes . ' B';
	}

	private function directory_size( string $dir ): int {

		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$size = 0;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $file ) {
				$size += $file->getSize();
			}
		} catch ( \Throwable $e ) {
			// Directory unreadable.
		}

		return $size;
	}
}
