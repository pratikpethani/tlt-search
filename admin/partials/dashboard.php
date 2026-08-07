<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Admin\SystemInfo;

$diagnostics_url = admin_url( 'admin.php?page=tlt-search-diagnostics' );

$info          = new SystemInfo();
$index         = $info->index_info();
$indexed_count = $index['indexed_count'];
$catalog_count = $index['catalog_count'];
$needs_reindex = $index['needs_reindex'];
$last_rebuilt  = $index['last_rebuilt'];
$index_size    = $index['index_size'];

// Determine sync status.
if ( $catalog_count === 0 ) {
	$sync_color  = '#888';
	$sync_label  = __( 'No products in catalog', 'tlt-search' );
} elseif ( $indexed_count === 0 ) {
	$sync_color  = '#d63638';
	$sync_label  = __( 'Index not built', 'tlt-search' );
} elseif ( $indexed_count >= $catalog_count ) {
	$sync_color  = '#0a8a0a';
	$sync_label  = __( 'In sync', 'tlt-search' );
} else {
	$missing     = $catalog_count - $indexed_count;
	$sync_color  = $missing > ( $catalog_count * 0.1 ) ? '#d63638' : '#d9822b';
	/* translators: %d: number of missing products */
	$sync_label  = sprintf( _n( '%d product not indexed', '%d products not indexed', $missing, 'tlt-search' ), $missing );
}

// Product categories for partial rebuild.
$product_cats = get_terms( [
	'taxonomy'   => 'product_cat',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
?>

<div class="wrap">

	<h1><?php esc_html_e( 'TLT Search', 'tlt-search' ); ?></h1>

	<?php if ( $needs_reindex ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Index rebuild required.', 'tlt-search' ); ?></strong>
				<?php esc_html_e( 'The relevance configuration has changed (field weights, stop words, or language). Search will return no results until you rebuild the index.', 'tlt-search' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Status table -->
	<table class="widefat striped" style="max-width:520px;margin-bottom:1.5em;">
		<tbody>
			<tr>
				<td style="width:180px;"><?php esc_html_e( 'Loupe Status', 'tlt-search' ); ?></td>
				<td><span style="color:#0a8a0a;font-weight:600;">&#10003; <?php esc_html_e( 'Connected', 'tlt-search' ); ?></span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Indexed Products', 'tlt-search' ); ?></td>
				<td>
					<strong><?php echo esc_html( number_format_i18n( $indexed_count ) ); ?></strong>
					<span style="color:#646970;">
						/ <?php echo esc_html( number_format_i18n( $catalog_count ) ); ?> <?php esc_html_e( 'in catalog', 'tlt-search' ); ?>
					</span>
					&nbsp;
					<span style="color:<?php echo esc_attr( $sync_color ); ?>;font-weight:600;"><?php echo esc_html( $sync_label ); ?></span>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Index Size', 'tlt-search' ); ?></td>
				<td><?php echo esc_html( SystemInfo::format_bytes( $index_size ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Last Full Rebuild', 'tlt-search' ); ?></td>
				<td>
					<?php if ( $last_rebuilt ) : ?>
						<?php echo esc_html( wp_date( get_option( 'date_format' ), $last_rebuilt ) ); ?>
						<span style="color:#646970;font-size:0.875em;">
							(<?php echo esc_html( human_time_diff( $last_rebuilt ) ); ?> <?php esc_html_e( 'ago', 'tlt-search' ); ?>)
						</span>
					<?php else : ?>
						<span style="color:#646970;"><?php esc_html_e( 'Never', 'tlt-search' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Full Rebuild -->
	<h2 style="margin-bottom:0.5em;"><?php esc_html_e( 'Full Index Rebuild', 'tlt-search' ); ?></h2>
	<p style="color:#646970;margin-top:0;margin-bottom:0.75em;font-size:0.9em;">
		<?php esc_html_e( 'Clears the entire index and re-indexes every published product. Required after configuration changes.', 'tlt-search' ); ?>
	</p>

	<div id="tlt-index-wrap">

		<button id="tlt-rebuild-btn" class="button button-primary">
			<?php esc_html_e( 'Rebuild Index', 'tlt-search' ); ?>
		</button>

		<div id="tlt-index-progress" style="display:none;margin-top:12px;">
			<div style="background:#f0f0f1;border-radius:4px;height:16px;width:320px;overflow:hidden;">
				<div id="tlt-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.2s ease;"></div>
			</div>
			<p id="tlt-progress-text" style="margin:6px 0 0;color:#555;"></p>
		</div>

	</div>

	<?php if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ) : ?>
	<hr style="margin:1.5em 0;">

	<!-- Partial Rebuild -->
	<h2 style="margin-bottom:0.5em;"><?php esc_html_e( 'Partial Rebuild — By Category', 'tlt-search' ); ?></h2>
	<p style="color:#646970;margin-top:0;margin-bottom:0.75em;font-size:0.9em;">
		<?php esc_html_e( 'Re-index all products in a specific category without clearing the full index. Use this after editing products in bulk within one category.', 'tlt-search' ); ?>
	</p>

	<div id="tlt-partial-wrap" style="display:flex;align-items:center;gap:0.75em;flex-wrap:wrap;">

		<select id="tlt-category-select" style="max-width:280px;">
			<option value=""><?php esc_html_e( '— Select a category —', 'tlt-search' ); ?></option>
			<?php foreach ( $product_cats as $cat ) : ?>
				<option value="<?php echo esc_attr( $cat->term_id ); ?>">
					<?php echo esc_html( $cat->name ); ?>
					(<?php echo esc_html( number_format_i18n( $cat->count ) ); ?>)
				</option>
			<?php endforeach; ?>
		</select>

		<button id="tlt-partial-btn" class="button" disabled>
			<?php esc_html_e( 'Reindex Category', 'tlt-search' ); ?>
		</button>

		<span id="tlt-partial-status" style="font-size:0.9em;"></span>

	</div>
	<?php endif; ?>

	<hr style="margin:1.5em 0;">

	<h2>Test Search</h2>

	<form method="post">

		<?php wp_nonce_field( 'tlt_search_test_search' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="tlt_search_query">Search Query</label>
				</th>
				<td>
					<input
						type="text"
						name="tlt_search_query"
						id="tlt_search_query"
						class="regular-text"
						value="<?php echo esc_attr( $_POST['tlt_search_query'] ?? '' ); ?>"
					/>
				</td>
			</tr>
		</table>

		<p>
			<input
				type="submit"
				name="tlt_search_submit"
				class="button"
				value="Search"
			/>
		</p>

	</form>

	<?php if ( ! empty( $GLOBALS['tlt_search_results'] ) ) : ?>

		<h3>Results</h3>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>SKU</th>
					<th>Link</th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $GLOBALS['tlt_search_results'] as $result ) : ?>

					<tr>
						<td><?php echo esc_html( $result['id'] ?? '' ); ?></td>
						<td><?php echo esc_html( $result['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( $result['sku'] ?? '' ); ?></td>
						<td><?php $admin_edit_url = get_edit_post_link( $result['id'] ); echo '<a href="' . esc_url( $admin_edit_url ) . '">Edit Product</a>'; ?></td>
					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	<?php endif; ?>

	<p style="margin-top:2em;">
		<a href="<?php echo esc_url( $diagnostics_url ); ?>"><?php esc_html_e( '→ View System Diagnostics', 'tlt-search' ); ?></a>
	</p>

</div>
