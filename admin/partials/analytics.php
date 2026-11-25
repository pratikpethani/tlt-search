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
$vol_max     = max( 1, ...array_map( 'intval', array_column( $volume, 'search_count' ) ?: [ 1 ] ) );

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
			<div class="columns">
				<div class="column col-12">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Searches per Day', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<?php if ( empty( $volume ) ) : ?>
								<p><?php esc_html_e( 'No searches in this period.', 'tlt-search' ); ?></p>
							<?php else : ?>
								<div style="display:flex;align-items:flex-end;gap:2px;height:120px;">
									<?php foreach ( $volume as $day ) : $h = max( 2, (int) round( 120 * (int) $day['search_count'] / $vol_max ) ); ?>
										<div title="<?php echo esc_attr( $day['search_date'] . ': ' . $day['search_count'] ); ?>" style="flex:1;height:<?php echo (int) $h; ?>px;background:#5755d9;border-radius:2px 2px 0 0;min-width:2px;"></div>
									<?php endforeach; ?>
								</div>
								<div style="display:flex;justify-content:space-between;color:#888;font-size:0.85em;margin-top:4px;">
									<span><?php echo esc_html( $volume[0]['search_date'] ); ?></span>
									<span><?php echo esc_html( $volume[ count( $volume ) - 1 ]['search_date'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
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
						[ 'label' => __( 'Result Clicks', 'tlt-search' ),      'value' => $clicks['clicks'] ],
						[ 'label' => __( 'Click-through rate (%)', 'tlt-search' ), 'value' => $clicks['ctr'] ],
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
											<span class="tlt-zero-actions" style="display:block;margin-top:4px;">
												<input type="text" class="form-input input-sm tlt-zero-synonym" style="width:150px;display:inline-block;" placeholder="<?php esc_attr_e( 'same as… (e.g. sofa)', 'tlt-search' ); ?>">
												<button type="button" class="btn btn-sm tlt-zero-add-synonym" data-query="<?php echo esc_attr( $row['query'] ); ?>"><?php esc_html_e( 'Add synonym', 'tlt-search' ); ?></button>
												<a class="btn btn-link btn-sm" href="<?php echo esc_url( add_query_arg( [ 'page' => 'tlt-search-merchandising', 'q' => $row['query'] ], admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Pin a product', 'tlt-search' ); ?></a>
											</span>
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
		<div class="container">
			<div class="columns">
				<div class="column col-12">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Most Clicked Products', 'tlt-search' ); ?></div>
							<div class="card-subtitle text-gray"><?php esc_html_e( 'Products shoppers opened from search results.', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<?php if ( empty( $top_clicked ) ) : ?>
								<div class="empty"><p class="empty-subtitle"><?php esc_html_e( 'No clicks recorded for this period yet.', 'tlt-search' ); ?></p></div>
							<?php else : ?>
							<table class="table table-hover">
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
		</div>
	</div>
</div>
