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
