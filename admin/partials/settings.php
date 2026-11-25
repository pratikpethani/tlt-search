<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Search\SearchFields;

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
	'custom_field_keys'   => [ __( 'Custom field keys', 'tlt-search' ), 'text', null, '', __( 'Product meta keys, separated by commas. Turn on "Custom fields" above, then update search data on the Dashboard.', 'tlt-search' ) ],
	'custom_taxonomies'   => [ __( 'Extra taxonomies', 'tlt-search' ), 'text', null, '', __( 'Taxonomy slugs, separated by commas. Turn on "Custom taxonomies" above, then update search data on the Dashboard.', 'tlt-search' ) ],
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
					$pos          = 0;
					?>
					<div class="tlt-row">
						<span class="tlt-row__label"><?php esc_html_e( 'Searchable fields', 'tlt-search' ); ?></span>
						<div class="tlt-row__field">
							<ul class="tlt-sources">
								<?php foreach ( SearchFields::available() as $key => $label ) :
									$pos++;
									$is_on = in_array( $key, $enabled_now, true );
									$rank  = (int) ( $fields_saved[ $key ]['rank'] ?? $pos );
									$name  = 'tlt_search_settings[search_fields][' . $key . ']';
									?>
									<li>
										<label>
											<input type="checkbox" class="tlt-switch" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $is_on ); ?>/>
											<?php echo esc_html( $label ); ?>
										</label>
										<label class="tlt-sources__rank">
											<?php esc_html_e( 'Priority', 'tlt-search' ); ?>
											<input type="number" min="1" max="99" name="<?php echo esc_attr( $name ); ?>[rank]" value="<?php echo esc_attr( $rank ); ?>" aria-label="<?php echo esc_attr( $label . ' — ' . __( 'Priority', 'tlt-search' ) ); ?>"/>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="tlt-help"><?php esc_html_e( '1 is the most important priority. Then update search data on the Dashboard.', 'tlt-search' ); ?></p>
						</div>
					</div>
					<?php $render( [ 'custom_field_keys', 'custom_taxonomies' ] ); ?>
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
