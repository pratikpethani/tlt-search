<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Synonyms\SynonymManager;

$manager = new SynonymManager();
$groups  = $manager->get_groups();
?>

<div class="wrap">

	<h1><?php esc_html_e( 'TLT Search — Synonyms', 'tlt-search' ); ?></h1>

	<p style="max-width:640px;color:#646970;">
		<?php esc_html_e( 'Each row is a synonym group — all terms in a group match each other at search time. Changes take effect immediately; no index rebuild required.', 'tlt-search' ); ?>
	</p>

	<!-- Existing groups -->
	<?php if ( empty( $groups ) ) : ?>
		<p style="color:#646970;"><?php esc_html_e( 'No synonym groups defined yet.', 'tlt-search' ); ?></p>
	<?php else : ?>
	<table class="widefat striped" style="max-width:680px;margin-bottom:1.5em;" id="tlt-synonyms-table">
		<thead>
			<tr>
				<th style="width:60px;">#</th>
				<th><?php esc_html_e( 'Terms', 'tlt-search' ); ?></th>
				<th style="width:80px;text-align:center;"><?php esc_html_e( 'Remove', 'tlt-search' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $groups as $i => $terms ) : ?>
			<tr id="tlt-synonym-row-<?php echo esc_attr( $i ); ?>">
				<td style="color:#888;"><?php echo esc_html( $i + 1 ); ?></td>
				<td><?php echo esc_html( implode( ', ', $terms ) ); ?></td>
				<td style="text-align:center;">
					<button
						class="button button-small tlt-delete-synonym"
						data-index="<?php echo esc_attr( $i ); ?>"
						style="color:#d63638;border-color:#d63638;"
					>&#10007;</button>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<!-- Add new group -->
	<h2><?php esc_html_e( 'Add Synonym Group', 'tlt-search' ); ?></h2>

	<div style="display:flex;gap:0.75em;align-items:flex-start;flex-wrap:wrap;max-width:680px;">
		<div style="flex:1;">
			<input
				type="text"
				id="tlt-synonym-input"
				class="large-text"
				placeholder="<?php esc_attr_e( 'e.g. tote bag, totebag, tote-bag', 'tlt-search' ); ?>"
				style="width:100%;"
			/>
			<p class="description" style="margin-top:4px;">
				<?php esc_html_e( 'Enter two or more comma-separated terms.', 'tlt-search' ); ?>
			</p>
		</div>
		<button id="tlt-add-synonym-btn" class="button button-primary" style="margin-top:1px;">
			<?php esc_html_e( 'Add Group', 'tlt-search' ); ?>
		</button>
	</div>

	<p id="tlt-synonym-msg" style="margin-top:0.75em;font-weight:600;"></p>

</div>
