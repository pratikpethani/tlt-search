<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Search\SearchFields;
use TLTSuite\TLTSearch\Search\SearchOptions;

$options = (array) get_option( 'tlt_search_settings', [] );

$lang_codes = [
	'en' => 'English', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian',
	'pt' => 'Portuguese', 'nl' => 'Dutch', 'sv' => 'Swedish', 'no' => 'Norwegian', 'da' => 'Danish',
	'fi' => 'Finnish', 'ru' => 'Russian', 'hu' => 'Hungarian', 'ro' => 'Romanian',
];
$wplang    = (string) get_option( 'WPLANG', 'en_US' );
$lang_name = $lang_codes[ explode( '_', $wplang )[0] ] ?? $wplang;

// Section slug => title.
$tabs = [
	'search'     => __( 'Search', 'tlt-search' ),
	'fields'     => __( 'Fields', 'tlt-search' ),
	'display'    => __( 'Results display', 'tlt-search' ),
	'exclusions' => __( 'Exclusions', 'tlt-search' ),
];

// Setting key => [ label, type, choices (select), [ min, max ] (number) or [ off, on ] (toggle), default, help ].
$rows = [
	'stop_words'          => [ __( 'Stop words', 'tlt-search' ), 'text', null, '', __( 'Words to ignore, separated by commas (e.g. a, an, the). Then update search data on the Dashboard.', 'tlt-search' ) ],
	'match_logic'         => [ __( 'Word matching', 'tlt-search' ), 'select', [ 'any' => __( 'Any word (broader results)', 'tlt-search' ), 'all' => __( 'All words (stricter, AND)', 'tlt-search' ) ], 'any', __( 'Choose whether a product needs any or all of the searched words.', 'tlt-search' ) ],
	'typo_tolerance'      => [ __( 'Typo tolerance', 'tlt-search' ), 'toggle', [ 'off', 'on' ], 'on', __( 'Finds products despite small spelling mistakes. Then update search data on the Dashboard.', 'tlt-search' ) ],
	'min_query_length'    => [ __( 'Minimum characters', 'tlt-search' ), 'number', [ 1, 10 ], '3', __( 'How many characters a shopper types before search starts (1 to 10).', 'tlt-search' ) ],
	'result_cache'        => [ __( 'Result cache', 'tlt-search' ), 'toggle', [ 'off', 'on' ], 'on', __( 'Keeps results for 5 minutes. Clears when products, synonyms or pins change.', 'tlt-search' ) ],
	'results_per_page'    => [ __( 'Results per page', 'tlt-search' ), 'number', [ 1, 50 ], '10', __( 'How many products each page of live results shows (1 to 50).', 'tlt-search' ) ],
	'layout'              => [ __( 'Results layout', 'tlt-search' ), 'select', [ 'list' => __( 'List', 'tlt-search' ), 'grid' => __( 'Grid', 'tlt-search' ) ], 'list', __( 'Show products as rows or as a grid of cards.', 'tlt-search' ) ],
	'image_size'          => [ __( 'Image size', 'tlt-search' ), 'select', [ 'small' => __( 'Small (40px)', 'tlt-search' ), 'medium' => __( 'Medium (60px)', 'tlt-search' ), 'large' => __( 'Large (90px)', 'tlt-search' ) ], 'medium', __( 'Size of the product thumbnail in results.', 'tlt-search' ) ],
	'show_rating'         => [ __( 'Star rating', 'tlt-search' ), 'toggle', [ 'no', 'yes' ], 'no', __( 'Show the average review stars on rated products.', 'tlt-search' ) ],
	'show_add_to_cart'    => [ __( 'Add to cart button', 'tlt-search' ), 'toggle', [ 'no', 'yes' ], 'no', __( 'Simple products go straight to the cart; others open the product page.', 'tlt-search' ) ],
	'show_term_results'   => [ __( 'Category and tag results', 'tlt-search' ), 'toggle', [ 'no', 'yes' ], 'yes', __( 'List matching categories and tags above the products.', 'tlt-search' ) ],
	'hide_out_of_stock'   => [ __( 'Hide out-of-stock products', 'tlt-search' ), 'toggle', [ 'no', 'yes' ], 'no', __( 'Keeps sold-out products out of results. Then update search data on the Dashboard.', 'tlt-search' ) ],
	'exclude_product_ids' => [ __( 'Exclude products', 'tlt-search' ), 'text', null, '', __( 'Product IDs to leave out of search, separated by commas. Then update search data on the Dashboard.', 'tlt-search' ) ],
	'exclude_categories'  => [ __( 'Exclude categories', 'tlt-search' ), 'text', null, '', __( 'Category slugs to leave out of search, separated by commas. Then update search data on the Dashboard.', 'tlt-search' ) ],
];

$render = static function ( array $keys ) use ( $rows, $options ) {
	foreach ( $keys as $key ) :
		[ $label, $type, $choices, $default, $help ] = $rows[ $key ];
		$value = (string) ( $options[ $key ] ?? $default );
		$id    = 'tlt-opt-' . $key;
		$name  = 'tlt_search_settings[' . $key . ']';
		?>
		<div class="tlt-row">
			<label class="tlt-row__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="tlt-row__field">
				<?php if ( 'toggle' === $type ) : ?>
					<?php // The hidden input submits the "off" value when the switch is unchecked. ?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $choices[0] ); ?>"/>
					<input type="checkbox" class="tlt-switch" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $choices[1] ); ?>" <?php checked( $value === $choices[1] ); ?>/>
				<?php elseif ( 'select' === $type ) : ?>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
						<?php foreach ( $choices as $v => $text ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $value, $v ); ?>><?php echo esc_html( $text ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo 'number' === $type ? 'min="' . (int) $choices[0] . '" max="' . (int) $choices[1] . '"' : ''; ?>/>
				<?php endif; ?>
				<p class="tlt-help"><?php echo esc_html( $help ); ?></p>
			</div>
		</div>
		<?php
	endforeach;
};

// Picker lists. Values must survive the sanitizer unchanged, or saving would alter them.
$taxonomy_choices = [];
foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy ) {
	// Categories, tags, brands and attributes have their own rows; the other two are WooCommerce internals.
	if ( in_array( $taxonomy->name, [ 'product_cat', 'product_tag', 'product_brand', 'product_type', 'product_visibility' ], true )
		|| 0 === strpos( $taxonomy->name, 'pa_' ) ) {
		continue;
	}
	$taxonomy_choices[ $taxonomy->name ] = $taxonomy->label . ' (' . $taxonomy->name . ')';
}

$meta_keys = get_transient( 'tlt_search_meta_keys' );
if ( ! is_array( $meta_keys ) ) {
	global $wpdb;
	// Keys starting with "_" are hidden system keys. ponytail: first 500 keys, cached 5 minutes; add a search box if stores need more.
	$meta_keys = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type = 'product' AND pm.meta_key NOT LIKE '\\_%'
		ORDER BY pm.meta_key LIMIT 500"
	);
	set_transient( 'tlt_search_meta_keys', $meta_keys, 5 * MINUTE_IN_SECONDS );
}
$meta_choices = [];
foreach ( $meta_keys as $meta_key ) {
	if ( [ $meta_key ] === SearchFields::parse_keys( $meta_key ) ) {
		$meta_choices[ $meta_key ] = $meta_key;
	}
}

// Field key => its picker.
$pickers = [
	'taxonomy_terms' => [
		'setting' => 'custom_taxonomies',
		'label'   => __( 'Taxonomies to search', 'tlt-search' ),
		'choices' => $taxonomy_choices,
		'picked'  => SearchOptions::parse_list( (string) ( $options['custom_taxonomies'] ?? '' ), 'sanitize_key' ),
	],
	'custom_fields'  => [
		'setting' => 'custom_field_keys',
		'label'   => __( 'Custom fields to search', 'tlt-search' ),
		'choices' => $meta_choices,
		'picked'  => SearchFields::parse_keys( (string) ( $options['custom_field_keys'] ?? '' ) ),
	],
];

$picked_item = static function ( string $key ) {
	?>
	<li data-key="<?php echo esc_attr( $key ); ?>">
		<span class="tlt-picker__name"><?php echo esc_html( $key ); ?></span>
		<button type="button" class="tlt-picker__remove" aria-label="<?php echo esc_attr( __( 'Remove', 'tlt-search' ) . ' ' . $key ); ?>">&times;</button>
	</li>
	<?php
};

$tlt_page = 'settings';
require __DIR__ . '/header.php';
?>

			<?php // One form around every tab, so saving always submits all settings. ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'tlt_search_settings_group' ); ?>

				<nav class="nav-tab-wrapper tlt-subtabs">
					<?php foreach ( $tabs as $slug => $title ) : ?>
						<a href="#tlt-tab-<?php echo esc_attr( $slug ); ?>" class="nav-tab" data-tab="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $title ); ?></a>
					<?php endforeach; ?>
				</nav>

				<div class="tlt-tab-panel" id="tlt-tab-search" data-panel="search">
					<h2><?php echo esc_html( $tabs['search'] ); ?></h2>
					<div class="tlt-row">
						<span class="tlt-row__label"><?php esc_html_e( 'Language', 'tlt-search' ); ?></span>
						<div class="tlt-row__field">
							<p class="tlt-row__value"><?php echo esc_html( $lang_name ); ?></p>
							<p class="tlt-help"><?php esc_html_e( 'Taken from WordPress General Settings, so "running" can match "run".', 'tlt-search' ); ?></p>
						</div>
					</div>
					<?php $render( [ 'match_logic', 'typo_tolerance', 'min_query_length' ] ); ?>
					<?php $render( [ 'stop_words' ] ); ?>
					<div class="tlt-row">
						<span class="tlt-row__label"><?php esc_html_e( 'Synonyms', 'tlt-search' ); ?></span>
						<div class="tlt-row__field">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=tlt-search-synonyms' ) ); ?>" class="button"><?php esc_html_e( 'Manage Synonyms →', 'tlt-search' ); ?></a>
							<p class="tlt-help"><?php esc_html_e( 'Group words that mean the same thing. Changes apply right away.', 'tlt-search' ); ?></p>
						</div>
					</div>
					<?php $render( [ 'result_cache' ] ); ?>
				</div>

				<div class="tlt-tab-panel" id="tlt-tab-fields" data-panel="fields">
					<h2><?php echo esc_html( $tabs['fields'] ); ?></h2>
					<?php
					$fields_saved = (array) ( $options['search_fields'] ?? [] );
					$enabled_now  = SearchFields::enabled();
					// Display order only: a field's default priority still comes from SearchFields::keys().
					$order  = [ 'title', 'description', 'sku', 'gtin', 'short_description', 'brands', 'categories', 'tags', 'attribute_values', 'taxonomy_terms', 'custom_fields', 'variations' ];
					$labels = array_replace( array_flip( $order ), SearchFields::available() );
					?>
					<div class="tlt-row">
						<span class="tlt-row__label"><?php esc_html_e( 'Search in', 'tlt-search' ); ?></span>
						<div class="tlt-row__field tlt-row__field--wide">
							<p class="tlt-help tlt-help--top"><?php esc_html_e( 'Switch on the places search should look. Priority 1 is the most important. If every switch is off, search looks in all of them.', 'tlt-search' ); ?></p>
							<ul class="tlt-sources">
								<?php foreach ( $labels as $key => $label ) :
									$rank     = (int) ( $fields_saved[ $key ]['rank'] ?? array_search( $key, SearchFields::keys(), true ) + 1 );
									$name     = 'tlt_search_settings[search_fields][' . $key . ']';
									$picker   = $pickers[ $key ] ?? null;
									$has_gear = 'attribute_values' !== $key;
									$panel    = 'tlt-source-' . $key;
									$rank_box = '<input type="number" min="1" max="99" name="' . esc_attr( $name ) . '[rank]" value="' . esc_attr( $rank ) . '" aria-label="' . esc_attr( $label . ' — ' . __( 'Priority', 'tlt-search' ) ) . '"/>';
									?>
									<li class="tlt-source">
										<div class="tlt-source__row">
											<label class="tlt-source__name">
												<input type="checkbox" class="tlt-switch" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( in_array( $key, $enabled_now, true ) ); ?>/>
												<?php echo esc_html( $label ); ?>
											</label>
											<?php if ( $picker ) : ?>
												<span class="tlt-badge"><?php esc_html_e( 'selected:', 'tlt-search' ); ?> <span data-selected><?php echo (int) count( $picker['picked'] ); ?></span></span>
											<?php endif; ?>
											<?php if ( $has_gear ) : ?>
												<span class="tlt-badge"><?php esc_html_e( 'priority:', 'tlt-search' ); ?> <span data-priority><?php echo (int) $rank; ?></span></span>
												<button type="button" class="tlt-source__gear" aria-expanded="true" aria-controls="<?php echo esc_attr( $panel ); ?>" aria-label="<?php echo esc_attr( __( 'Options:', 'tlt-search' ) . ' ' . $label ); ?>">
													<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
												</button>
											<?php else : ?>
												<?php // No options box on this row, so its priority is edited in place. ?>
												<label class="tlt-badge"><?php esc_html_e( 'priority:', 'tlt-search' ); ?> <?php echo $rank_box; // Escaped above. ?></label>
												<span class="tlt-source__gear tlt-source__gear--none"></span>
											<?php endif; ?>
										</div>
										<?php if ( 'attribute_values' === $key ) : ?>
											<p class="tlt-help tlt-source__note"><?php esc_html_e( 'This one priority applies to all product attributes. 1 is the most important, 99 the least.', 'tlt-search' ); ?></p>
										<?php endif; ?>
										<?php if ( $has_gear ) : ?>
											<div class="tlt-source__panel" id="<?php echo esc_attr( $panel ); ?>">
												<label class="tlt-source__line">
													<span><?php echo esc_html( $picker ? __( 'Priority for all items in the list', 'tlt-search' ) : __( 'Priority', 'tlt-search' ) ); ?></span>
													<?php echo $rank_box; // Escaped above. ?>
												</label>
												<p class="tlt-help"><?php echo esc_html( $picker ? __( 'One number applies to every item you pick below. 1 is the most important, 99 the least.', 'tlt-search' ) : __( '1 is the most important, 99 the least.', 'tlt-search' ) ); ?></p>
												<?php if ( $picker ) : ?>
													<div class="tlt-picker">
														<span class="tlt-picker__title"><?php echo esc_html( $picker['label'] ); ?></span>
														<div class="tlt-inline">
															<select aria-label="<?php echo esc_attr( $picker['label'] ); ?>">
																<option value=""><?php esc_html_e( 'Select item', 'tlt-search' ); ?></option>
																<?php foreach ( $picker['choices'] as $value => $text ) : ?>
																	<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
																<?php endforeach; ?>
															</select>
															<button type="button" class="button tlt-picker__add"><?php esc_html_e( 'Add', 'tlt-search' ); ?></button>
														</div>
														<?php // The stored comma list. Left exactly as saved until an item is added or removed. ?>
														<input type="text" class="tlt-picker__value" name="tlt_search_settings[<?php echo esc_attr( $picker['setting'] ); ?>]" value="<?php echo esc_attr( (string) ( $options[ $picker['setting'] ] ?? '' ) ); ?>"/>
														<ul class="tlt-picker__list">
															<?php array_map( $picked_item, $picker['picked'] ); ?>
														</ul>
														<p class="tlt-picker__empty"<?php echo $picker['picked'] ? ' hidden' : ''; ?>><?php esc_html_e( 'Nothing selected yet.', 'tlt-search' ); ?></p>
														<template><?php $picked_item( '' ); ?></template>
														<p class="tlt-help"><?php esc_html_e( 'Searched only while this row is switched on. After changing this list, update search data on the Dashboard. The Dashboard will not remind you.', 'tlt-search' ); ?></p>
													</div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="tlt-help"><?php esc_html_e( 'After changing switches or priorities, update search data on the Dashboard when it asks. Changing picked taxonomies or custom fields also needs an update, but the Dashboard will not ask.', 'tlt-search' ); ?></p>
						</div>
					</div>
				</div>

				<div class="tlt-tab-panel" id="tlt-tab-display" data-panel="display">
					<h2><?php echo esc_html( $tabs['display'] ); ?></h2>
					<?php $render( [ 'results_per_page', 'layout', 'image_size' ] ); ?>
					<?php $render( [ 'show_rating', 'show_add_to_cart', 'show_term_results' ] ); ?>
				</div>

				<div class="tlt-tab-panel" id="tlt-tab-exclusions" data-panel="exclusions">
					<h2><?php echo esc_html( $tabs['exclusions'] ); ?></h2>
					<?php $render( [ 'hide_out_of_stock', 'exclude_product_ids' ] ); ?>
					<?php $render( [ 'exclude_categories' ] ); ?>
				</div>

				<p class="tlt-submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'tlt-search' ); ?>">
				</p>
			</form>
		</div>
	</div>
</div>
