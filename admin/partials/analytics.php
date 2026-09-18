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
	$class  = $days === $current_days ? 'tab-item mb-0 active' : 'tab-item mb-0';
	$label  = esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $days, 'tlt-search' ), $days ) );
	return "<li class=\"{$class}\"><a href=\"{$url}\">{$label}</a></li>";
}
?>

<div id="tlt-suite">
	<div class="tlt-suite tlt-settings-page">
		<div class="container py-2">
			<div class="columns">
				<div class="column col-lg-12">
					<h1><?php esc_html_e( 'TLT Search — Analytics', 'tlt-search' ); ?></h1>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns">
				<div class="column col-12">
					<ul class="tab">
						<!-- Date range selector -->
						<?php
						echo tlt_days_link( $current_url, 7, $days );
						echo ' ';
						echo tlt_days_link( $current_url, 30, $days );
						echo ' ';
						echo tlt_days_link( $current_url, 90, $days );
						?>
					</ul>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns" >
				<?php
					$cards = [
						[ 'label' => __( 'Total Searches', 'tlt-search' ),    'value' => $summary['total_searches'] ],
						[ 'label' => __( 'Unique Queries', 'tlt-search' ),     'value' => $summary['unique_queries'] ],
						[ 'label' => __( 'With Results', 'tlt-search' ),       'value' => $summary['with_results_count'] ],
						[ 'label' => __( 'Zero Results', 'tlt-search' ),       'value' => $summary['zero_result_count'] ],
					];
					foreach ( $cards as $card ) :
					?>
					<div class="column col-sm-12 col-lg-3">
						<div class="card p-2">
							<div class="card-header">
								<div class="card-title h4 mb-2 text-bold"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></div>
								<div class="card-subtitle text-gray"><?php echo esc_html( $card['label'] ); ?></div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="container">
			<div class="columns" >
				<div class="column col-sm-12 col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold"><?php esc_html_e( 'Top Searches', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<?php if ( empty( $popular ) ) : ?>
								<div class="empty">
									<p class="empty-title h5">No data available</p>
									<p class="empty-subtitle"><?php esc_html_e( 'No data yet for this period.', 'tlt-search' ); ?></p>
								</div>
							<?php else : ?>
							<table class="table table-hover">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Query', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Searches', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Avg Results', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Last Seen', 'tlt-search' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $popular as $row ) : ?>
									<tr>
										<td><code><?php echo esc_html( $row['query'] ); ?></code></td>
										<td><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
										<td><?php echo esc_html( number_format_i18n( (float) $row['avg_results'], 1 ) ); ?></td>
										<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['last_searched'] ) ) ); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="column col-sm-12 col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Zero-Result Searches', 'tlt-search' ); ?></div>
							<div class="card-subtitle text-gray"><?php esc_html_e( 'Queries your catalog could not answer — add synonyms or new products for these terms.', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<?php if ( empty( $no_results ) ) : ?>
								<div class="empty">
									<p class="empty-title h5">No data available</p>
									<p class="empty-subtitle"><?php esc_html_e( 'No zero-result searches in this period. Great!', 'tlt-search' ); ?></p>
								</div>
							<?php else : ?>
							<table class="table table-hover" style="max-width:100%;">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Query', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Searches', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Last Seen', 'tlt-search' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $no_results as $row ) : ?>
									<tr>
										<td>
											<code><?php echo esc_html( $row['query'] ); ?></code>
											<a class="btn btn-link" href="<?php echo esc_url( admin_url( 'admin.php?page=tlt-search-synonyms' ) ); ?>" title="<?php esc_attr_e( 'Add synonym', 'tlt-search' ); ?>"
											>+&nbsp;<?php esc_html_e( 'synonym', 'tlt-search' ); ?></a>
										</td>
										<td><?php echo esc_html( number_format_i18n( (int) $row['search_count'] ) ); ?></td>
										<td ><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['last_searched'] ) ) ); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
