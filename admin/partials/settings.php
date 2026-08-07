<?php
defined( 'ABSPATH' ) || exit;
$options = (array) get_option( 'tlt_search_settings', [] );
?>

<div class="wrap">

	<h1><?php esc_html_e( 'TLT Search — Settings', 'tlt-search' ); ?></h1>

	<form method="post" action="options.php">

		<?php settings_fields( 'tlt_search_settings_group' ); ?>

		<h2><?php esc_html_e( 'Relevance Engine', 'tlt-search' ); ?></h2>

		<p class="description" style="margin-bottom:1em;">
			<?php esc_html_e( 'Language is set to your WordPress site language. Stop Words affect how documents are indexed. Rebuild the index after changing Stop Words.', 'tlt-search' ); ?>
		</p>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row"><?php esc_html_e( 'Language', 'tlt-search' ); ?></th>
				<td>
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
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="tlt-stop-words"><?php esc_html_e( 'Stop Words', 'tlt-search' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="tlt-stop-words"
						name="tlt_search_settings[stop_words]"
						class="large-text"
						value="<?php echo esc_attr( (string) ( $options['stop_words'] ?? '' ) ); ?>"
					/>
					<p class="description">
						<?php esc_html_e( 'Comma-separated words ignored during indexing and search (e.g. a, an, the, and, or). Requires index rebuild after change.', 'tlt-search' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Synonyms', 'tlt-search' ); ?></th>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tlt-search-synonyms' ) ); ?>" class="button">
						<?php esc_html_e( 'Manage Synonyms →', 'tlt-search' ); ?>
					</a>
					<p class="description">
						<?php esc_html_e( 'Add, edit, and remove synonym groups from the dedicated Synonyms page. Changes take effect immediately.', 'tlt-search' ); ?>
					</p>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>

	</form>

</div>
