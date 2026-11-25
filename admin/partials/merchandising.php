<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Merchandising\PinManager;

$pin_manager = new PinManager();
$all_pins    = $pin_manager->get_all_pins_grouped();
$rules       = \TLTSuite\TLTSearch\Merchandising\RankingRules::all();

$tlt_page = 'merchandising';
require __DIR__ . '/header.php';
?>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Choose which products show first', 'tlt-search' ); ?></h2>
	<div class="tlt-inline">
		<input type="text" id="tlt-pin-query" aria-label="<?php esc_attr_e( 'Search words', 'tlt-search' ); ?>" placeholder="<?php esc_attr_e( 'e.g. tote bag', 'tlt-search' ); ?>">
		<button type="button" id="tlt-pin-search-btn" class="button button-primary"><?php esc_html_e( 'Find products', 'tlt-search' ); ?></button>
	</div>
	<p class="tlt-help"><?php esc_html_e( 'Type what a shopper would search for, then pick an action for a product.', 'tlt-search' ); ?></p>
	<ul class="tlt-legend">
		<li><strong><?php esc_html_e( 'Pin', 'tlt-search' ); ?></strong> — <?php esc_html_e( 'always first for these search words.', 'tlt-search' ); ?></li>
		<li><strong><?php esc_html_e( 'Boost', 'tlt-search' ); ?></strong> — <?php esc_html_e( 'higher in every search sorted by best match.', 'tlt-search' ); ?></li>
		<li><strong><?php esc_html_e( 'Bury', 'tlt-search' ); ?></strong> — <?php esc_html_e( 'lower in every search sorted by best match.', 'tlt-search' ); ?></li>
	</ul>
	<div id="tlt-pin-results"></div>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Pinned products', 'tlt-search' ); ?></h2>
	<?php if ( ! empty( $all_pins ) ) : ?>
		<?php foreach ( $all_pins as $query_term => $pins ) : ?>
			<h3><?php echo esc_html( $query_term ); ?></h3>
			<table class="table table-hover">
				<tbody>
					<?php foreach ( $pins as $pin ) :
						$product = wc_get_product( (int) $pin['product_id'] );
						?>
						<tr>
							<td class="tlt-pos"><?php echo esc_html( (int) $pin['position'] + 1 ); ?></td>
							<td>
								<?php if ( $product ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( (int) $pin['product_id'] ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
									<?php if ( $product->get_sku() ) : ?>
										<span class="tlt-muted"><?php echo esc_html( 'SKU: ' . $product->get_sku() ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<?php esc_html_e( 'Product not found', 'tlt-search' ); ?> (#<?php echo esc_html( $pin['product_id'] ); ?>)
								<?php endif; ?>
							</td>
							<td class="tlt-actions">
								<button type="button" class="button tlt-remove-pin" data-pin-id="<?php echo esc_attr( $pin['id'] ); ?>"><?php esc_html_e( 'Remove', 'tlt-search' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No pinned products yet.', 'tlt-search' ); ?></p>
	<?php endif; ?>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Boosted and buried products', 'tlt-search' ); ?></h2>
	<?php if ( $rules ) : ?>
		<table class="table">
			<tbody>
				<?php foreach ( $rules as $rule_pid => $rule_mode ) :
					$rule_product = wc_get_product( (int) $rule_pid );
					?>
					<tr>
						<td class="tlt-pos"><?php echo esc_html( 'boost' === $rule_mode ? __( 'Boost', 'tlt-search' ) : __( 'Bury', 'tlt-search' ) ); ?></td>
						<td><?php echo esc_html( $rule_product ? $rule_product->get_name() : __( 'Product not found', 'tlt-search' ) ); ?> <span class="tlt-muted">#<?php echo esc_html( $rule_pid ); ?></span></td>
						<td class="tlt-actions"><button type="button" class="button tlt-remove-rule" data-product-id="<?php echo esc_attr( $rule_pid ); ?>"><?php esc_html_e( 'Remove', 'tlt-search' ); ?></button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'None yet.', 'tlt-search' ); ?></p>
	<?php endif; ?>
</div>

		</div>
	</div>
</div>
