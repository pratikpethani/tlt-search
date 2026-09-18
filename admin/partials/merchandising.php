<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Merchandising\PinManager;

$pin_manager = new PinManager();
$all_pins    = $pin_manager->get_all_pins_grouped();
?>

<div id="tlt-suite">
	<div class="tlt-suite tlt-settings-page">
		<div class="container py-2">
			<div class="columns">
				<div class="column col-lg-12">
					<h1 class="text-bold mb-2"><?php esc_html_e( 'TLT Search — Merchandising', 'tlt-search' ); ?></h1>
					<p><?php esc_html_e( 'Pin specific products to the top of search results for given queries. Pinned products always appear first, regardless of relevance score.', 'tlt-search' ); ?></p>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns">
				<div class="column col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Pin a Product', 'tlt-search' ); ?></div>
							<form class="form-horizontal" style="max-width:720px;">
								<div class="form-group">
									<div class="col-4 col-sm-12">
										<label for="tlt-pin-query" class="form-label"><?php esc_html_e( 'Search Query', 'tlt-search' ); ?></label>
									</div>
									<div class="col-8 col-sm-12">
										<div class="input-group">
											<input class="form-input" type="text" id="tlt-pin-query" placeholder="<?php esc_attr_e( 'e.g. tote bag', 'tlt-search' ); ?>">
											<button id="tlt-pin-search-btn" class="btn btn-primary input-group-btn"><?php esc_html_e( 'Search Products', 'tlt-search' ); ?></button>
										</div>
										<p class="description"><?php esc_html_e( 'Enter the exact query shoppers would type, then search to see current results.', 'tlt-search' ); ?></p>
									</div>
								</div>
							</form>
						</div>
						<div class="card-body">
							<div id="tlt-pin-results"></div>
						</div>
					</div>
				</div>
				<div class="column col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Pinned Products', 'tlt-search' ); ?></div>
							<p class="description"><?php esc_html_e( 'View and manage pinned products for all queries.', 'tlt-search' ); ?></p>
						</div>
						<div class="card-body">
							<?php if ( ! empty( $all_pins ) ) : ?>
									<?php foreach ( $all_pins as $query_term => $pins ) : ?>
									<div class="tile mb-2">
										<div class="tile-content">
											<p class="tile-title text-bold text-primary mb-0 bg-secondary"><?php echo esc_html( $query_term ); ?></p>
											<table class="table">
												<thead>
													<tr>
														<th style="width:60px;"><?php esc_html_e( 'Pos.', 'tlt-search' ); ?></th>
														<th><?php esc_html_e( 'Product', 'tlt-search' ); ?></th>
														<th style="width:80px;text-align:center;"><?php esc_html_e( 'Remove', 'tlt-search' ); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ( $pins as $pin ) :
														$product = wc_get_product( (int) $pin['product_id'] );
													?>
													<tr>
														<td><?php echo esc_html( (int) $pin['position'] + 1 ); ?></td>
														<td>
															<?php if ( $product ) : ?>
																<a href="<?php echo esc_url( get_edit_post_link( (int) $pin['product_id'] ) ); ?>">
																	<?php echo esc_html( $product->get_name() ); ?>
																</a>
																<span style="color:#888;font-size:0.85em;">
																	#<?php echo esc_html( $pin['product_id'] ); ?>
																	<?php if ( $product->get_sku() ) echo ' &mdash; SKU: ' . esc_html( $product->get_sku() ); ?>
																</span>
															<?php else : ?>
																<span style="color:#d63638;"><?php esc_html_e( 'Product not found', 'tlt-search' ); ?> (#<?php echo esc_html( $pin['product_id'] ); ?>)</span>
															<?php endif; ?>
														</td>
														<td style="text-align:center;">
															<button class="btn btn-error btn-sm tlt-remove-pin" data-pin-id="<?php echo esc_attr( $pin['id'] ); ?>"
															><i class="icon icon-delete"></i></button>
														</td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
									<?php endforeach; ?>
								<?php else : ?>
									<p><?php esc_html_e( 'No pinned products yet. Search for a query above and pin results to get started.', 'tlt-search' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	

	

</div>
