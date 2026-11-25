<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Analytics\AnalyticsReport;

$allowed_days = [ 7, 30, 90 ];
$days         = (int) ( $_GET['days'] ?? 30 );
if ( ! in_array( $days, $allowed_days, true ) ) {
	$days = 30;
}

$report  = new AnalyticsReport();
$summary = $report->summary( $days );
$popular = $report->popular_searches( $days, 20 );
$no_results = $report->no_result_searches( $days, 20 );
$clicks     = $report->click_summary( $days );
$top_clicked = $report->top_clicked( $days, 10 );
$volume      = $report->search_volume_by_day( $days );

$vendor = plugin_dir_url( dirname( __DIR__, 2 ) . '/tlt-search.php' ) . 'assets/vendor/';
wp_enqueue_style( 'tlt-simple-datatables', $vendor . 'simple-datatables.css', [], '9.2.1' );
wp_enqueue_script( 'tlt-chartjs', $vendor . 'chart.umd.js', [], '4.4.7', true );
wp_enqueue_script( 'tlt-simple-datatables', $vendor . 'simple-datatables.js', [], '9.2.1', true );

$current_url = admin_url( 'admin.php?page=tlt-search-analytics' );
$date        = static fn( $value ) => wp_date( get_option( 'date_format' ), strtotime( $value ) );

$cards = [
	__( 'Searches', 'tlt-search' )           => $summary['total_searches'],
	__( 'Different searches', 'tlt-search' ) => $summary['unique_queries'],
	__( 'Found products', 'tlt-search' )     => $summary['with_results_count'],
	__( 'Found nothing', 'tlt-search' )      => $summary['zero_result_count'],
	__( 'Product clicks', 'tlt-search' )     => $clicks['clicks'],
	__( 'Click rate (%)', 'tlt-search' )     => $clicks['ctr'],
];

$tlt_page = 'analytics';
require __DIR__ . '/header.php';
?>

<nav class="nav-tab-wrapper tlt-subtabs">
	<?php foreach ( $allowed_days as $range ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'days', $range, $current_url ) ); ?>" class="nav-tab<?php echo $range === $days ? ' nav-tab-active' : ''; ?>">
			<?php /* translators: %d: number of days */ echo esc_html( sprintf( __( 'Last %d days', 'tlt-search' ), $range ) ); ?>
		</a>
	<?php endforeach; ?>
</nav>

<div class="tlt-stats">
	<?php foreach ( $cards as $label => $value ) : ?>
		<div class="tlt-box">
			<strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong>
			<span><?php echo esc_html( $label ); ?></span>
		</div>
	<?php endforeach; ?>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Searches per day', 'tlt-search' ); ?></h2>
	<?php if ( empty( $volume ) ) : ?>
		<p><?php esc_html_e( 'No searches in this period.', 'tlt-search' ); ?></p>
	<?php else : ?>
		<div style="position:relative;height:220px;">
			<canvas id="tlt-volume-chart"
				data-labels="<?php echo esc_attr( wp_json_encode( array_column( $volume, 'search_date' ) ) ); ?>"
				data-values="<?php echo esc_attr( wp_json_encode( array_map( 'intval', array_column( $volume, 'search_count' ) ) ) ); ?>"
				data-label="<?php esc_attr_e( 'Searches', 'tlt-search' ); ?>"></canvas>
		</div>
	<?php endif; ?>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Searches that found nothing', 'tlt-search' ); ?></h2>
	<p class="tlt-help"><?php esc_html_e( 'Add a synonym or pin a product so shoppers find something.', 'tlt-search' ); ?></p>
	<?php if ( empty( $no_results ) ) : ?>
		<p><?php esc_html_e( 'None in this period. Great!', 'tlt-search' ); ?></p>
	<?php else : ?>
		<table class="table table-hover tlt-datatable">
			<thead><tr>
				<th><?php esc_html_e( 'Search words', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'Times', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'Last searched', 'tlt-search' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $no_results as $row ) : ?>
				<tr>
					<td>
						<code><?php echo esc_html( $row['query'] ); ?></code>
						<span class="tlt-zero-actions tlt-inline">
							<input type="text" class="tlt-zero-synonym" aria-label="<?php esc_attr_e( 'Word that means the same', 'tlt-search' ); ?>" placeholder="<?php esc_attr_e( 'same as… (e.g. sofa)', 'tlt-search' ); ?>">
							<button type="button" class="button button-small tlt-zero-add-synonym" data-query="<?php echo esc_attr( $row['query'] ); ?>"><?php esc_html_e( 'Add synonym', 'tlt-search' ); ?></button>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => 'tlt-search-merchandising', 'q' => $row['query'] ], admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Pin a product', 'tlt-search' ); ?></a>
						</span>
					</td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
					<td><?php echo esc_html( $date( $row['last_searched'] ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Popular searches', 'tlt-search' ); ?></h2>
	<?php if ( empty( $popular ) ) : ?>
		<p><?php esc_html_e( 'No searches in this period.', 'tlt-search' ); ?></p>
	<?php else : ?>
		<table class="table table-hover tlt-datatable">
			<thead><tr>
				<th><?php esc_html_e( 'Search words', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'Times', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'Products found (average)', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'Last searched', 'tlt-search' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $popular as $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( $row['query'] ); ?></code></td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (float) $row['avg_results'], 1 ) ); ?></td>
					<td><?php echo esc_html( $date( $row['last_searched'] ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Most clicked products', 'tlt-search' ); ?></h2>
	<?php if ( empty( $top_clicked ) ) : ?>
		<p><?php esc_html_e( 'No clicks in this period.', 'tlt-search' ); ?></p>
	<?php else : ?>
		<table class="table table-hover tlt-datatable">
			<thead><tr><th><?php esc_html_e( 'Product', 'tlt-search' ); ?></th><th><?php esc_html_e( 'Clicks', 'tlt-search' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $top_clicked as $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( get_edit_post_link( (int) $row['product_id'] ) ); ?>"><?php echo esc_html( get_the_title( (int) $row['product_id'] ) ?: '#' . (int) $row['product_id'] ); ?></a></td>
					<td><?php echo esc_html( number_format_i18n( (int) $row['clicks'] ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

		</div>
	</div>
</div>
