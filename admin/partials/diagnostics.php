<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Admin\SystemInfo;

$info      = new SystemInfo();
$checks    = $info->system_checks();
$index     = $info->index_info();
$cron      = $info->cron_info();
$analytics = $info->analytics_info();

// Things that stop a feature from working. WP-Cron being disabled is often deliberate, so it is not one.
$problems = [];
foreach ( $checks as $check ) {
	if ( ! $check['pass'] ) {
		$problems[] = $check['label'] . ': ' . $check['value'];
	}
}
if ( $index['needs_reindex'] ) {
	$problems[] = __( 'Settings changed. Update search data on the Dashboard.', 'tlt-search' );
}
if ( ! $analytics['table_exists'] ) {
	$problems[] = __( 'Search history storage is missing. Reload this page to create it.', 'tlt-search' );
}
if ( ! $cron['next_cleanup'] ) {
	$problems[] = __( 'Old search history is not being cleaned up. Deactivate and reactivate the plugin.', 'tlt-search' );
}

$when = static fn( $time ) => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time );

$details = [
	__( 'Search data folder', 'tlt-search' )  => $index['index_dir'],
	__( 'Search data size', 'tlt-search' )    => SystemInfo::format_bytes( $index['index_size'] ),
	__( 'Searchable products', 'tlt-search' ) => number_format_i18n( $index['indexed_count'] ) . ' / ' . number_format_i18n( $index['catalog_count'] ),
	__( 'Last full update', 'tlt-search' )    => $index['last_rebuilt'] ? $when( $index['last_rebuilt'] ) : __( 'Never', 'tlt-search' ),
	__( 'WP-Cron', 'tlt-search' )             => $cron['cron_disabled'] ? __( 'Disabled (DISABLE_WP_CRON)', 'tlt-search' ) : __( 'Enabled', 'tlt-search' ),
	__( 'Next history cleanup', 'tlt-search' ) => $cron['next_cleanup'] ? $when( $cron['next_cleanup'] ) : __( 'Not scheduled', 'tlt-search' ),
	__( 'Search history table', 'tlt-search' ) => $analytics['table_exists'] ? __( 'Exists', 'tlt-search' ) : __( 'Missing', 'tlt-search' ),
];

$tlt_page = 'diagnostics';
require __DIR__ . '/header.php';
?>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Health check', 'tlt-search' ); ?></h2>
	<?php if ( $problems ) : ?>
		<p class="tlt-status tlt-status--bad"><?php esc_html_e( 'Something needs attention:', 'tlt-search' ); ?></p>
		<ul class="tlt-legend">
			<?php foreach ( $problems as $problem ) : ?>
				<li><?php echo esc_html( $problem ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="tlt-status tlt-status--good"><?php esc_html_e( 'Everything looks good.', 'tlt-search' ); ?></p>
	<?php endif; ?>
</div>

<details class="tlt-box">
	<summary><?php esc_html_e( 'Technical details (for support)', 'tlt-search' ); ?></summary>
	<table class="table">
		<tbody>
			<?php foreach ( $checks as $check ) : ?>
				<tr>
					<th><?php echo esc_html( $check['label'] ); ?></th>
					<td><?php echo esc_html( ( $check['pass'] ? '✓ ' : '✗ ' ) . $check['value'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php foreach ( $details as $label => $value ) : ?>
				<tr>
					<th><?php echo esc_html( $label ); ?></th>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</details>

		</div>
	</div>
</div>
