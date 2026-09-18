<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Admin\SystemInfo;

$dashboard_url = admin_url( 'admin.php?page=tlt-search' );
$settings_url = admin_url( 'admin.php?page=tlt-search-settings' );
$analytics_url = admin_url( 'admin.php?page=tlt-search-analytics' );
$synonyms_url = admin_url( 'admin.php?page=tlt-search-synonyms' );
$merchandising_url = admin_url( 'admin.php?page=tlt-search-merchandising' );
$diagnostics_url = admin_url( 'admin.php?page=tlt-search-diagnostics' );

$info        = new SystemInfo();
$checks      = $info->system_checks();
$index       = $info->index_info();
$cron        = $info->cron_info();
$analytics   = $info->analytics_info();

$all_pass = array_reduce( $checks, fn( $carry, $c ) => $carry && $c['pass'], true );
?>

<div id="tlt-suite">
	<div class="tlt-suite tlt-settings-page">
		<div class="container py-2">
			<div class="colums">
				<div class="column">
					<h1><?php esc_html_e( 'TLT Search — Diagnostics', 'tlt-search' ); ?></h1>
					<?php if ( ! $all_pass ) : ?>
						<div class="notice notice-error">
							<p><strong><?php esc_html_e( 'One or more system requirements are not met. Search may not function correctly.', 'tlt-search' ); ?></strong></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns">
				<div class="column col-sm-12 col-lg-4">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold"><?php esc_html_e( 'System Requirements', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<table class="table">
								<thead>
									<tr>
										<th style="width:220px;"><?php esc_html_e( 'Requirement', 'tlt-search' ); ?></th>
										<th style="width:80px;"><?php esc_html_e( 'Status', 'tlt-search' ); ?></th>
										<th><?php esc_html_e( 'Value', 'tlt-search' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $checks as $check ) : ?>
									<tr>
										<td><?php echo esc_html( $check['label'] ); ?></td>
										<td>
											<?php if ( $check['pass'] ) : ?>
												<span style="color:#0a8a0a;font-weight:600;">&#10003; <?php esc_html_e( 'Pass', 'tlt-search' ); ?></span>
											<?php else : ?>
												<span style="color:#d63638;font-weight:600;">&#10007; <?php esc_html_e( 'Fail', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="font-family:monospace;font-size:0.875em;word-break:break-all;"><?php echo esc_html( $check['value'] ); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="column col-sm-12 col-lg-4">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold"><?php esc_html_e( 'Index Information', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<table class="table">
								<tbody>
									<tr>
										<td style="width:220px;"><?php esc_html_e( 'Index Directory', 'tlt-search' ); ?></td>
										<td style="font-family:monospace;font-size:0.875em;word-break:break-all;"><?php echo esc_html( $index['index_dir'] ); ?></td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Index Size', 'tlt-search' ); ?></td>
										<td><?php echo esc_html( SystemInfo::format_bytes( $index['index_size'] ) ); ?></td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Indexed Products', 'tlt-search' ); ?></td>
										<td>
											<?php
											echo esc_html( number_format_i18n( $index['indexed_count'] ) );
											echo ' / ';
											echo esc_html( number_format_i18n( $index['catalog_count'] ) );
											echo ' ';
											esc_html_e( 'in catalog', 'tlt-search' );
											?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Last Full Rebuild', 'tlt-search' ); ?></td>
										<td>
											<?php if ( $index['last_rebuilt'] ) : ?>
												<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $index['last_rebuilt'] ) ); ?>
												<span style="color:#646970;font-size:0.875em;">
													(<?php echo esc_html( human_time_diff( $index['last_rebuilt'] ) ); ?> <?php esc_html_e( 'ago', 'tlt-search' ); ?>)
												</span>
											<?php else : ?>
												<span style="color:#646970;"><?php esc_html_e( 'Never recorded', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Needs Reindex', 'tlt-search' ); ?></td>
										<td>
											<?php if ( $index['needs_reindex'] ) : ?>
												<span style="color:#d63638;font-weight:600;"><?php esc_html_e( 'Yes — configuration changed', 'tlt-search' ); ?></span>
											<?php else : ?>
												<span style="color:#0a8a0a;"><?php esc_html_e( 'No', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="column col-sm-12 col-lg-4">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold"><?php esc_html_e( 'WP-Cron Status', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<table class="table">
								<tbody>
									<tr>
										<td style="width:220px;"><?php esc_html_e( 'WP-Cron', 'tlt-search' ); ?></td>
										<td>
											<?php if ( $cron['cron_disabled'] ) : ?>
												<span style="color:#d9822b;font-weight:600;"><?php esc_html_e( 'Disabled (DISABLE_WP_CRON = true)', 'tlt-search' ); ?></span>
											<?php else : ?>
												<span style="color:#0a8a0a;">&#10003; <?php esc_html_e( 'Enabled', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Next Log Cleanup', 'tlt-search' ); ?></td>
										<td>
											<?php if ( $cron['next_cleanup'] ) : ?>
												<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cron['next_cleanup'] ) ); ?>
												<span style="color:#646970;font-size:0.875em;">
													(<?php echo esc_html( human_time_diff( $cron['next_cleanup'] ) ); ?>)
												</span>
											<?php else : ?>
												<span style="color:#d63638;"><?php esc_html_e( 'Not scheduled — reactivate the plugin to register.', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Analytics Log Table', 'tlt-search' ); ?></td>
										<td>
											<?php if ( $analytics['table_exists'] ) : ?>
												<span style="color:#0a8a0a;">&#10003; <?php esc_html_e( 'Exists', 'tlt-search' ); ?></span>
											<?php else : ?>
												<span style="color:#d63638;"><?php esc_html_e( 'Missing — visit any admin page to auto-create it.', 'tlt-search' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
