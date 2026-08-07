<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Merchandising\PinManager;

$pin_manager = new PinManager();
$all_pins    = $pin_manager->get_all_pins_grouped();
?>

<div class="wrap">

	<h1><?php esc_html_e( 'TLT Search — Merchandising', 'tlt-search' ); ?></h1>

	<p style="max-width:700px;color:#646970;">
		<?php esc_html_e( 'Pin specific products to the top of search results for given queries. Pinned products always appear first, regardless of relevance score.', 'tlt-search' ); ?>
	</p>

	<!-- Add Pin section -->
	<h2><?php esc_html_e( 'Pin a Product', 'tlt-search' ); ?></h2>

	<table class="form-table" style="max-width:680px;">
		<tr>
			<th scope="row"><label for="tlt-pin-query"><?php esc_html_e( 'Search Query', 'tlt-search' ); ?></label></th>
			<td>
				<input type="text" id="tlt-pin-query" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. tote bag', 'tlt-search' ); ?>" />
				<button id="tlt-pin-search-btn" class="button" style="margin-left:0.5em;">
					<?php esc_html_e( 'Search Products', 'tlt-search' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Enter the exact query shoppers would type, then search to see current results.', 'tlt-search' ); ?></p>
			</td>
		</tr>
	</table>

	<div id="tlt-pin-results" style="max-width:680px;margin-top:1em;"></div>

	<?php if ( ! empty( $all_pins ) ) : ?>
	<hr style="margin:2em 0;">

	<!-- Existing pins grouped by query -->
	<h2><?php esc_html_e( 'Pinned Products', 'tlt-search' ); ?></h2>

		<?php foreach ( $all_pins as $query_term => $pins ) : ?>
		<div style="margin-bottom:1.5em;">

			<h3 style="margin-bottom:0.4em;">
				<code><?php echo esc_html( $query_term ); ?></code>
			</h3>

			<table class="widefat striped" style="max-width:640px;">
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
						<td style="color:#888;"><?php echo esc_html( (int) $pin['position'] + 1 ); ?></td>
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
							<button
								class="button button-small tlt-remove-pin"
								data-pin-id="<?php echo esc_attr( $pin['id'] ); ?>"
								style="color:#d63638;border-color:#d63638;"
							>&#10007;</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		</div>
		<?php endforeach; ?>

	<?php else : ?>
		<hr style="margin:2em 0;">
		<p style="color:#646970;"><?php esc_html_e( 'No pinned products yet. Search for a query above and pin results to get started.', 'tlt-search' ); ?></p>
	<?php endif; ?>

</div>
