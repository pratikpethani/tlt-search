<?php
defined( 'ABSPATH' ) || exit;
$options = (array) get_option( 'tlt_search_settings', [] );
?>

<div id="tlt-suite">
	<div class="tlt-suite tlt-settings-page">
		<div class="container py-2">
			<div class="columns">
				<div class="column col-lg-12">
					<h1 class="text-bold mb-2"><?php esc_html_e( 'TLT Search — Settings', 'tlt-search' ); ?></h1>
					<p><?php esc_html_e( 'Configure the settings for the TLT Search plugin.', 'tlt-search' ); ?></p>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns">
				<div class="column col-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Relevance Engine', 'tlt-search' ); ?></div>
							<div class="card-subtitle"><?php esc_html_e( 'Language is set to your WordPress site language. Stop Words affect how documents are indexed. Rebuild the index after changing Stop Words.', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<form method="post" action="options.php" class="form-horizontal">
								<?php settings_fields( 'tlt_search_settings_group' ); ?>
								<div class="form-group">
									<div class="col-3 col-sm-12">
										<label class="form-label"><?php esc_html_e( 'Language', 'tlt-search' ); ?></label>
									</div>
									<div class="col-9 col-sm-12">
										<p style="margin:0;">
												<?php
												$wplang = (string) get_option( 'WPLANG', 'en_US' );
												$lang_codes = [
													'en' => 'English',
													'de' => 'German',
													'fr' => 'French',
													'es' => 'Spanish',
													'it' => 'Italian',
													'pt' => 'Portuguese',
													'nl' => 'Dutch',
													'sv' => 'Swedish',
													'no' => 'Norwegian',
													'da' => 'Danish',
													'fi' => 'Finnish',
													'ru' => 'Russian',
													'hu' => 'Hungarian',
													'ro' => 'Romanian',
												];
												$lang = explode( '_', $wplang )[0];
												$lang_name = $lang_codes[ $lang ] ?? $wplang;
												echo esc_html( $lang_name );
												?>
											</p>
											<p class="description" style="margin-top:0.5em;">
												<?php esc_html_e( 'Set in WordPress General Settings. Used for stemming (e.g. "running" matches "run").', 'tlt-search' ); ?>
											</p>
									</div>
								</div>
								<div class="form-group">
									<div class="col-3 col-sm-12">
									<label class="form-label" for="tlt-stop-words"><?php esc_html_e( 'Stop Words', 'tlt-search' ); ?></label>
									</div>
									<div class="col-9 col-sm-12">
										<input type="text" id="tlt-stop-words" name="tlt_search_settings[stop_words]" class="form-input mb-1" value="<?php echo esc_attr( (string) ( $options['stop_words'] ?? '' ) ); ?>"/>
										<span class="text-gray"><?php esc_html_e( 'Comma-separated words ignored during indexing and search (e.g. a, an, the, and, or). Requires index rebuild after change.', 'tlt-search' ); ?></span>
									</div>
								</div>
								<div class="form-group">
									<div class="col-3 col-sm-12">
										<label class="form-label" for="input-example-1"><?php esc_html_e( 'Synonyms', 'tlt-search' ); ?></label>
									</div>
									<div class="col-9 col-sm-12">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=tlt-search-synonyms' ) ); ?>" class="btn btn-primary btn-block mb-1"><?php esc_html_e( 'Manage Synonyms →', 'tlt-search' ); ?></a>
										<span class="text-gray"><?php esc_html_e( 'Add, edit, and remove synonym groups from the dedicated Synonyms page. Changes take effect immediately.', 'tlt-search' ); ?></span>
									</div>
								</div>
								<?php
								$fields_saved = (array) ( $options['search_fields'] ?? [] );
								$enabled_now  = \TLTSuite\TLTSearch\Search\SearchFields::enabled();
								$pos          = 0;
								?>
								<div class="form-group">
									<div class="col-3 col-sm-12">
										<label class="form-label"><?php esc_html_e( 'Searchable fields', 'tlt-search' ); ?></label>
									</div>
									<div class="col-9 col-sm-12">
										<table class="table table-striped table-hover">
											<thead><tr>
												<th><?php esc_html_e( 'Search', 'tlt-search' ); ?></th>
												<th><?php esc_html_e( 'Field', 'tlt-search' ); ?></th>
												<th><?php esc_html_e( 'Priority', 'tlt-search' ); ?></th>
											</tr></thead>
											<tbody>
											<?php foreach ( \TLTSuite\TLTSearch\Search\SearchFields::available() as $key => $label ) :
												$pos++;
												$is_on = in_array( $key, $enabled_now, true );
												$rank  = (int) ( $fields_saved[ $key ]['rank'] ?? $pos );
												?>
												<tr>
													<td><input type="checkbox" name="tlt_search_settings[search_fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $is_on ); ?>/></td>
													<td><?php echo esc_html( $label ); ?></td>
													<td><input type="number" min="1" max="99" style="width:4em" name="tlt_search_settings[search_fields][<?php echo esc_attr( $key ); ?>][rank]" value="<?php echo esc_attr( $rank ); ?>"/></td>
												</tr>
											<?php endforeach; ?>
											</tbody>
										</table>
										<span class="text-gray"><?php esc_html_e( 'Matches in a field with a lower priority number rank higher (1 = most important). Rebuild the index after changing fields or custom field keys.', 'tlt-search' ); ?></span>
									</div>
								</div>
								<div class="form-group">
									<div class="col-3 col-sm-12">
										<label class="form-label" for="tlt-custom-field-keys"><?php esc_html_e( 'Custom field keys', 'tlt-search' ); ?></label>
									</div>
									<div class="col-9 col-sm-12">
										<input type="text" id="tlt-custom-field-keys" name="tlt_search_settings[custom_field_keys]" class="form-input mb-1" value="<?php echo esc_attr( (string) ( $options['custom_field_keys'] ?? '' ) ); ?>"/>
										<span class="text-gray"><?php esc_html_e( 'Comma-separated product meta keys (e.g. an ACF field name) to make searchable. Needs the "Custom fields" row enabled above.', 'tlt-search' ); ?></span>
									</div>
								</div>
								<?php
								$opt = \TLTSuite\TLTSearch\Search\SearchOptions::all();
								$rows = [
									[ 'match_logic', __( 'Word matching', 'tlt-search' ), 'select', [ 'any' => __( 'Any word (broader results)', 'tlt-search' ), 'all' => __( 'All words (stricter, AND)', 'tlt-search' ) ], 'any', __( 'Whether a product must contain every word searched for.', 'tlt-search' ) ],
									[ 'typo_tolerance', __( 'Typo tolerance', 'tlt-search' ), 'select', [ 'on' => __( 'On', 'tlt-search' ), 'off' => __( 'Off (exact spelling only)', 'tlt-search' ) ], 'on', __( 'Turn off for exact-match behavior. Rebuild the index after changing.', 'tlt-search' ) ],
									[ 'min_query_length', __( 'Minimum characters', 'tlt-search' ), 'number', [ 1, 10 ], '3', __( 'Search starts after this many characters (1 to 10).', 'tlt-search' ) ],
									[ 'results_per_page', __( 'Results per page', 'tlt-search' ), 'number', [ 1, 50 ], '10', __( 'Products shown per page in the live search.', 'tlt-search' ) ],
									[ 'layout', __( 'Results layout', 'tlt-search' ), 'select', [ 'list' => __( 'List', 'tlt-search' ), 'grid' => __( 'Grid', 'tlt-search' ) ], 'list', '' ],
									[ 'image_size', __( 'Image size', 'tlt-search' ), 'select', [ 'small' => __( 'Small (40px)', 'tlt-search' ), 'medium' => __( 'Medium (60px)', 'tlt-search' ), 'large' => __( 'Large (90px)', 'tlt-search' ) ], 'medium', '' ],
									[ 'show_rating', __( 'Star rating', 'tlt-search' ), 'select', [ 'no' => __( 'Hide', 'tlt-search' ), 'yes' => __( 'Show', 'tlt-search' ) ], 'no', '' ],
									[ 'show_add_to_cart', __( 'Add to cart button', 'tlt-search' ), 'select', [ 'no' => __( 'Hide', 'tlt-search' ), 'yes' => __( 'Show', 'tlt-search' ) ], 'no', __( 'Simple products add to cart; others link to the product page.', 'tlt-search' ) ],
									[ 'show_term_results', __( 'Category and tag results', 'tlt-search' ), 'select', [ 'yes' => __( 'Show in dropdown', 'tlt-search' ), 'no' => __( 'Hide', 'tlt-search' ) ], 'yes', __( 'Matching categories and tags appear above the products.', 'tlt-search' ) ],
									[ 'result_cache', __( 'Result cache', 'tlt-search' ), 'select', [ 'on' => __( 'On (5 minutes)', 'tlt-search' ), 'off' => __( 'Off', 'tlt-search' ) ], 'on', __( 'Repeat searches are served from a short cache that clears when products, synonyms or pins change.', 'tlt-search' ) ],
									[ 'hide_out_of_stock', __( 'Out-of-stock products', 'tlt-search' ), 'select', [ 'no' => __( 'Show in results', 'tlt-search' ), 'yes' => __( 'Hide from results', 'tlt-search' ) ], 'no', __( 'Rebuild the index after changing.', 'tlt-search' ) ],
									[ 'exclude_product_ids', __( 'Exclude products', 'tlt-search' ), 'text', null, '', __( 'Comma-separated product IDs to keep out of search. Rebuild the index after changing.', 'tlt-search' ) ],
									[ 'exclude_categories', __( 'Exclude categories', 'tlt-search' ), 'text', null, '', __( 'Comma-separated category slugs to keep out of search. Rebuild the index after changing.', 'tlt-search' ) ],
									[ 'custom_taxonomies', __( 'Extra taxonomies', 'tlt-search' ), 'text', null, '', __( 'Comma-separated taxonomy slugs whose terms are searchable (needs the "Custom taxonomies" row enabled above). Rebuild the index after changing.', 'tlt-search' ) ],
								];
								foreach ( $rows as [ $key, $label, $type, $choices, $default, $help ] ) :
									$value = (string) ( $opt[ $key ] ?? $default );
									?>
									<div class="form-group">
										<div class="col-3 col-sm-12">
											<label class="form-label" for="tlt-opt-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
										</div>
										<div class="col-9 col-sm-12">
											<?php if ( 'select' === $type ) : ?>
												<select id="tlt-opt-<?php echo esc_attr( $key ); ?>" name="tlt_search_settings[<?php echo esc_attr( $key ); ?>]" class="form-select mb-1">
													<?php foreach ( $choices as $v => $text ) : ?>
														<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $value, $v ); ?>><?php echo esc_html( $text ); ?></option>
													<?php endforeach; ?>
												</select>
											<?php else : ?>
												<input type="<?php echo esc_attr( $type ); ?>" id="tlt-opt-<?php echo esc_attr( $key ); ?>" name="tlt_search_settings[<?php echo esc_attr( $key ); ?>]" class="form-input mb-1" value="<?php echo esc_attr( $value ); ?>" <?php echo 'number' === $type ? 'min="' . (int) $choices[0] . '" max="' . (int) $choices[1] . '"' : ''; ?>/>
											<?php endif; ?>
											<span class="text-gray"><?php echo esc_html( $help ); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
								<div class="form-group">
									<input type="submit" name="submit" id="submit" class="btn btn-primary" value="Save Changes">
								</div>
								<?php //submit_button(); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
