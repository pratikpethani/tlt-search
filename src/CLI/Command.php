<?php

namespace TLTSuite\TLTSearch\CLI;

use TLTSuite\TLTSearch\Indexing\BulkIndexer;
use TLTSuite\TLTSearch\Search\LoupeEngine;
use WP_CLI;

/**
 * Manage the TLT Search index from the command line.
 */
class Command {

	/**
	 * Rebuild the whole search index from published products.
	 *
	 * ## OPTIONS
	 *
	 * [--batch=<size>]
	 * : Products indexed per step. Default 100.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tlt-search reindex
	 *     wp tlt-search reindex --batch=250
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Named arguments.
	 */
	public function reindex( $args, $assoc_args ): void {

		$batch   = max( 1, min( 1000, (int) ( $assoc_args['batch'] ?? 100 ) ) );
		$indexer = new BulkIndexer();
		$total   = $indexer->init_rebuild();
		$done    = 0;
		$bar     = \WP_CLI\Utils\make_progress_bar( 'Indexing products', max( 1, $total ) );

		do {
			$result = $indexer->index_batch( $done, $batch );
			$done  += $result['count'];
			$bar->tick( $result['count'] );
		} while ( ! $result['done'] );

		$bar->finish();

		WP_CLI::success( sprintf( 'Processed %d products.', $done ) );
	}

	/**
	 * Show how many products are indexed and whether a rebuild is needed.
	 */
	public function status(): void {

		$engine = new LoupeEngine();

		WP_CLI::log( 'Indexed products: ' . $engine->count_documents() );
		WP_CLI::log( 'Rebuild needed: ' . ( $engine->needs_reindex() ? 'yes' : 'no' ) );
	}
}
