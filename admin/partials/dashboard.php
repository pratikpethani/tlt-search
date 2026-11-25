<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Admin\SystemInfo;

$index   = ( new SystemInfo() )->index_info();
$indexed = $index['indexed_count'];
$total   = $index['catalog_count'];
$missing = max( 0, $total - $indexed );

if ( 0 === $total ) {
	$status = [ '', __( 'Your store has no published products yet.', 'tlt-search' ) ];
} elseif ( 0 === $indexed ) {
	$status = [ 'bad', __( 'Search is not set up yet. Click "Update search data".', 'tlt-search' ) ];
} elseif ( ! $missing ) {
	$status = [ 'good', __( 'All products are searchable.', 'tlt-search' ) ];
} else {
	/* translators: %d: number of products missing from search */
	$status = [ $missing > $total * 0.1 ? 'bad' : 'warn', sprintf( _n( '%d product is missing from search.', '%d products are missing from search.', $missing, 'tlt-search' ), $missing ) ];
}

$product_cats = get_terms( [
	'taxonomy'   => 'product_cat',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );

$tlt_page = 'dashboard';
require __DIR__ . '/header.php';
?>

<?php if ( $index['needs_reindex'] ) : ?>
	<div class="tlt-box tlt-box--warn">
		<strong><?php esc_html_e( 'Your settings changed. Search may show no results until you update search data.', 'tlt-search' ); ?></strong>
	</div>
<?php endif; ?>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Search status', 'tlt-search' ); ?></h2>
	<p class="tlt-status tlt-status--<?php echo esc_attr( $status[0] ); ?>"><?php echo esc_html( $status[1] ); ?></p>
	<p>
		<?php
		/* translators: 1: searchable products, 2: published products */
		echo esc_html( sprintf( __( 'Searchable products: %1$s of %2$s', 'tlt-search' ), number_format_i18n( $indexed ), number_format_i18n( $total ) ) );
		?>
		<br>
		<?php esc_html_e( 'Last full update:', 'tlt-search' ); ?>
		<?php
		echo $index['last_rebuilt']
			/* translators: %s: time since, e.g. "2 hours" */
			? esc_html( sprintf( __( '%s ago', 'tlt-search' ), human_time_diff( $index['last_rebuilt'] ) ) )
			: esc_html__( 'Never', 'tlt-search' );
		?>
	</p>
	<div id="tlt-index-wrap">
		<div id="tlt-index-progress" style="display:none;">
			<div class="tlt-progress"><div id="tlt-progress-bar"></div></div>
			<p id="tlt-progress-text"></p>
		</div>
		<button type="button" id="tlt-rebuild-btn" class="button button-primary"><?php esc_html_e( 'Update search data', 'tlt-search' ); ?></button>
		<p class="tlt-help"><?php esc_html_e( 'Reads all products again. Keep this page open until it finishes.', 'tlt-search' ); ?></p>
	</div>
</div>

<?php if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ) : ?>
	<div class="tlt-box" id="tlt-partial-wrap">
		<h2><?php esc_html_e( 'Update one category', 'tlt-search' ); ?></h2>
		<div class="tlt-inline">
			<select id="tlt-category-select" aria-label="<?php esc_attr_e( 'Category', 'tlt-search' ); ?>">
				<option value=""><?php esc_html_e( '— Select a category —', 'tlt-search' ); ?></option>
				<?php foreach ( $product_cats as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( number_format_i18n( $cat->count ) ); ?>)</option>
				<?php endforeach; ?>
			</select>
			<button type="button" id="tlt-partial-btn" class="button" disabled><?php esc_html_e( 'Update category', 'tlt-search' ); ?></button>
			<span id="tlt-partial-status"></span>
		</div>
		<p class="tlt-help"><?php esc_html_e( 'Quicker option after editing many products in one category.', 'tlt-search' ); ?></p>
	</div>
<?php endif; ?>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Try a search', 'tlt-search' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( 'tlt_search_test_search' ); ?>
		<div class="tlt-inline">
			<input type="text" name="tlt_search_query" aria-label="<?php esc_attr_e( 'Search words', 'tlt-search' ); ?>" value="<?php echo esc_attr( wp_unslash( $_POST['tlt_search_query'] ?? '' ) ); ?>"/>
			<button type="submit" name="tlt_search_submit" class="button"><?php esc_html_e( 'Search', 'tlt-search' ); ?></button>
		</div>
		<p class="tlt-help"><?php esc_html_e( 'See which products a search finds.', 'tlt-search' ); ?></p>
	</form>
	<?php if ( ! empty( $GLOBALS['tlt_search_results'] ) ) : ?>
		<table class="table table-hover">
			<thead><tr>
				<th><?php esc_html_e( 'Product', 'tlt-search' ); ?></th>
				<th><?php esc_html_e( 'SKU', 'tlt-search' ); ?></th>
				<th></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $GLOBALS['tlt_search_results'] as $result ) : ?>
					<tr>
						<td><?php echo esc_html( $result['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( $result['sku'] ?? '' ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $result['id'] ?? 0 ) ); ?>"><?php esc_html_e( 'Edit', 'tlt-search' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php elseif ( isset( $GLOBALS['tlt_search_results'] ) ) : ?>
		<p><?php esc_html_e( 'No products found.', 'tlt-search' ); ?></p>
	<?php endif; ?>
</div>

		</div>
	</div>
</div>
