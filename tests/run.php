<?php
/**
 * Standalone tests: no WordPress needed. Run from the plugin folder:
 *   php tests/run.php
 * Uses real Loupe; WordPress/WooCommerce are replaced by tiny stubs below.
 */

$autoload = is_file( __DIR__ . '/../vendor/autoload.php' ) ? __DIR__ . '/../vendor/autoload.php' : getenv( 'TLT_AUTOLOAD' );
require $autoload;

// Plugin classes by PSR-4 (works even if composer's dump is stale).
spl_autoload_register( function ( $c ) {
	$p = 'TLTSuite\\TLTSearch\\';
	if ( 0 === strpos( $c, $p ) ) {
		$f = __DIR__ . '/../src/' . str_replace( '\\', '/', substr( $c, strlen( $p ) ) ) . '.php';
		if ( is_file( $f ) ) {
			require $f;
		}
	}
} );

$tmp = sys_get_temp_dir() . '/tlt-search-tests-' . getmypid();
register_shutdown_function( function () use ( $tmp ) {
	exec( 'rm -rf ' . escapeshellarg( $tmp ) );
} );

$opts = [];
function get_option( $k, $d = false ) { global $opts; return $opts[ $k ] ?? $d; }
function update_option( $k, $v ) { global $opts; $opts[ $k ] = $v; return true; }
function wp_upload_dir() { global $tmp; return [ 'basedir' => $tmp ]; }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]+/i', '-', $s ) ); }
function absint( $n ) { return abs( (int) $n ); }
function has_filter( $h ) { return false; }
function wp_strip_all_tags( $s ) { return $s; }
function taxonomy_exists( $t ) { return false; }
function get_the_terms( $id, $t ) { return []; }
function has_term( $a, $b, $c ) { return false; }
function is_wp_error( $x ) { return false; }
function wp_json_encode( $x ) { return json_encode( $x ); }
function __( $s ) { return $s; }
class WC_Product {
	public function __construct( public int $id, public string $title, public string $sku = '', public string $stock = 'instock' ) {}
	function get_id() { return $this->id; } function get_status() { return 'publish'; }
	function get_catalog_visibility() { return 'visible'; } function is_type( $t ) { return 'simple' === $t; }
	function get_name() { return $this->title; } function get_sku() { return $this->sku; }
	function get_description() { return ''; } function get_short_description() { return ''; }
	function get_attributes() { return []; } function get_price() { return 5; }
	function get_stock_status() { return $this->stock; } function is_featured() { return false; }
	function get_meta( $k ) { return ''; } function get_children() { return []; } function get_parent_id() { return 0; }
}
$products = [];
function wc_get_product( $id ) { global $products; return $products[ $id ] ?? false; }

use TLTSuite\TLTSearch\Indexing\IndexManager;
use TLTSuite\TLTSearch\Merchandising\RankingRules;
use TLTSuite\TLTSearch\Search\LoupeEngine;

$fail = 0;
function check( string $name, $got, $want ): void {
	global $fail;
	$ok = $got === $want;
	$fail += $ok ? 0 : 1;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . ( $ok ? '' : ' got ' . json_encode( $got ) . ' want ' . json_encode( $want ) ) . "\n";
}

// ── RankingRules ────────────────────────────────────────────────────────────
$hits = [ [ 'id' => 1 ], [ 'id' => 2 ], [ 'id' => 3 ], [ 'id' => 4, '_pinned' => true ] ];
$out  = RankingRules::apply( $hits, [ 1 => 'bury', 3 => 'boost', 4 => 'bury' ] );
check( 'apply: pin, boost, normal, bury', array_column( $out, 'id' ), [ 4, 3, 2, 1 ] );
check( 'apply: no rules keeps order', array_column( RankingRules::apply( $hits, [] ), 'id' ), [ 1, 2, 3, 4 ] );

RankingRules::set( 7, 'boost' );
RankingRules::set( 8, 'nope' );
RankingRules::set( 9, 'bury' );
RankingRules::remove( 9 );
check( 'set/remove/ignore invalid', RankingRules::all(), [ 7 => 'boost' ] );

// ── Searches-per-day gap filling ────────────────────────────────────────────
$days = \TLTSuite\TLTSearch\Analytics\AnalyticsReport::fill_days( [ [ 'search_date' => '2026-11-02', 'search_count' => '4' ] ], '2026-11-01', 3 );
check( 'fill_days: zeros for empty days', array_column( $days, 'search_count' ), [ 0, 4, 0 ] );
check( 'fill_days: consecutive dates', array_column( $days, 'search_date' ), [ '2026-11-01', '2026-11-02', '2026-11-03' ] );

// ── Real Loupe index ───────────────────────────────────────────────────────
$products[1] = new WC_Product( 1, 'Blue Lamp', 'LAMP1' );
$products[2] = new WC_Product( 2, 'Blue Hat', 'HAT1' );
$products[3] = new WC_Product( 3, 'Red Sock', 'SOCK1' );
$im = new IndexManager();
foreach ( [ 1, 2, 3 ] as $id ) {
	$im->sync_product( $id );
}
$ids = fn( string $q ) => array_map( 'intval', array_column( ( new LoupeEngine() )->search( $q, [ 'hitsPerPage' => 50 ] ), 'id' ) );

$r = $ids( 'blue' );
sort( $r );
check( 'search by title', $r, [ 1, 2 ] );
check( 'search by sku', $ids( 'SOCK1' ), [ 3 ] );
check( 'typo tolerance finds lamp', in_array( 1, $ids( 'lampp' ), true ), true );

$opts['tlt_search_settings'] = [ 'typo_tolerance' => 'off' ];
check( 'typo off: no match', $ids( 'lampp' ), [] );

echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
