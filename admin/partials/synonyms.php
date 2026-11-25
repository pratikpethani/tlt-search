<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Synonyms\SynonymManager;

$manager = new SynonymManager();
$groups  = $manager->get_groups();

$tlt_page = 'synonyms';
require __DIR__ . '/header.php';
?>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Add words that mean the same', 'tlt-search' ); ?></h2>
	<div class="tlt-inline">
		<input type="text" id="tlt-synonym-input" aria-label="<?php esc_attr_e( 'Words, separated by commas', 'tlt-search' ); ?>" placeholder="<?php esc_attr_e( 'e.g. sofa, couch, settee', 'tlt-search' ); ?>" />
		<button type="button" id="tlt-add-synonym-btn" class="button button-primary"><span class="dashicons dashicons-plus" aria-hidden="true"></span><?php esc_html_e( 'Add', 'tlt-search' ); ?></button>
	</div>
	<p class="tlt-help"><?php esc_html_e( 'Type two or more words, separated by commas. Works right away.', 'tlt-search' ); ?></p>
	<p id="tlt-synonym-msg" role="status"></p>
</div>

<div class="tlt-box">
	<h2><?php esc_html_e( 'Your synonyms', 'tlt-search' ); ?></h2>
	<?php if ( empty( $groups ) ) : ?>
		<p><?php esc_html_e( 'No synonyms yet.', 'tlt-search' ); ?></p>
	<?php else : ?>
		<table class="table table-hover" id="tlt-synonyms-table">
			<tbody>
				<?php foreach ( $groups as $i => $terms ) : ?>
				<tr id="tlt-synonym-row-<?php echo esc_attr( $i ); ?>">
					<td><?php echo esc_html( implode( ', ', $terms ) ); ?></td>
					<td class="tlt-actions">
						<?php $delete = __( 'Delete', 'tlt-search' ) . ' ' . implode( ', ', $terms ); ?>
						<button type="button" class="button button-small tlt-icon-btn tlt-delete-synonym" data-index="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( $delete ); ?>" title="<?php esc_attr_e( 'Delete', 'tlt-search' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

		</div>
	</div>
</div>
