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

$current_url = admin_url( 'admin.php?page=tlt-search-analytics' );

function tlt_days_link( string $base_url, int $days, int $current_days ): string {
	$url    = esc_url( add_query_arg( 'days', $days, $base_url ) );
	$class  = $days === $current_days ? 'button button-primary' : 'button';
	$label  = esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $days, 'tlt-search' ), $days ) );
	return "<a href=\"{$url}\" class=\"{$class}\">{$label}</a>";
}
?>

<div class="wrap">

	<h1><?php esc_html_e( 'TLT Search — Analytics', 'tlt-search' ); ?></h1>

	<!-- Date range selector -->
	<div style="margin-bottom:1.5em;">
		<?php
		echo tlt_days_link( $current_url, 7, $days );
		echo ' ';
		echo tlt_days_link( $current_url, 30, $days );
		echo ' ';
		echo tlt_days_link( $current_url, 90, $days );
		?>
	</div>

	<!-- Summary cards -->
	<div style="display:flex;gap:1em;flex-wrap:wrap;margin-bottom:2em;">

		<?php
		$cards = [
			[ 'label' => __( 'Total Searches', 'tlt-search' ),    'value' => $summary['total_searches'] ],
			[ 'label' => __( 'Unique Queries', 'tlt-search' ),     'value' => $summary['unique_queries'] ],
			[ 'label' => __( 'With Results', 'tlt-search' ),       'value' => $summary['with_results_count'] ],
			[ 'label' => __( 'Zero Results', 'tlt-search' ),       'value' => $summary['zero_result_count'] ],
		];
		foreach ( $cards as $card ) :
		?>
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:1em 1.5em;min-width:140px;text-align:center;">
			<div style="font-size:2em;font-weight:700;color:#1d2327;"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></div>
			<div style="font-size:0.875em;color:#646970;margin-top:0.2em;"><?php echo esc_html( $card['label'] ); ?></div>
		</div>
		<?php endforeach; ?>

	</div>

	<div style="display:grid;grid-template-columns:1fr 1fr;gap:2em;align-items:start;">

		<!-- Top Searches -->
		<div>
			<h2 style="margin-top:0;"><?php esc_html_e( 'Top Searches', 'tlt-search' ); ?></h2>

			<?php if ( empty( $popular ) ) : ?>
				<p style="color:#646970;"><?php esc_html_e( 'No data yet for this period.', 'tlt-search' ); ?></p>
			<?php else : ?>
			<table class="widefat striped" style="max-width:600px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Query', 'tlt-search' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Searches', 'tlt-search' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Avg Results', 'tlt-search' ); ?></th>
						<th><?php esc_html_e( 'Last Seen', 'tlt-search' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $popular as $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $row['query'] ); ?></code></td>
						<td style="text-align:right;"><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
						<td style="text-align:right;"><?php echo esc_html( number_format_i18n( (float) $row['avg_results'], 1 ) ); ?></td>
						<td style="white-space:nowrap;color:#646970;font-size:0.875em;"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['last_searched'] ) ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

		<!-- Zero-Result Searches -->
		<div>
			<h2 style="margin-top:0;"><?php esc_html_e( 'Zero-Result Searches', 'tlt-search' ); ?></h2>
			<p style="color:#646970;font-size:0.875em;margin-top:-0.5em;">
				<?php esc_html_e( 'Queries your catalog could not answer — add synonyms or new products for these terms.', 'tlt-search' ); ?>
			</p>

			<?php if ( empty( $no_results ) ) : ?>
				<p style="color:#0a8a0a;"><?php esc_html_e( 'No zero-result searches in this period. Great!', 'tlt-search' ); ?></p>
			<?php else : ?>
			<table class="widefat striped" style="max-width:480px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Query', 'tlt-search' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Searches', 'tlt-search' ); ?></th>
						<th><?php esc_html_e( 'Last Seen', 'tlt-search' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $no_results as $row ) : ?>
					<tr>
						<td>
							<code><?php echo esc_html( $row['query'] ); ?></code>
							<a
								href="<?php echo esc_url( admin_url( 'admin.php?page=tlt-search-synonyms' ) ); ?>"
								style="margin-left:0.5em;font-size:0.8em;"
								title="<?php esc_attr_e( 'Add synonym', 'tlt-search' ); ?>"
							>+&nbsp;<?php esc_html_e( 'synonym', 'tlt-search' ); ?></a>
						</td>
						<td style="text-align:right;color:#a00;font-weight:600;"><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
						<td style="white-space:nowrap;color:#646970;font-size:0.875em;"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['last_searched'] ) ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

	</div>

</div>
