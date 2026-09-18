<?php
defined( 'ABSPATH' ) || exit;

use TLTSuite\TLTSearch\Synonyms\SynonymManager;

$manager = new SynonymManager();
$groups  = $manager->get_groups();
?>

<div id="tlt-suite">
	<div class="tlt-suite tlt-settings-page">
		<div class="container py-2">
			<div class="columns">
				<div class="column col-lg-12">
					<h1 class="text-bold mb-2"><?php esc_html_e( 'TLT Search — Synonyms', 'tlt-search' ); ?></h1>
					<p><?php esc_html_e( 'Each row is a synonym group — all terms in a group match each other at search time. Changes take effect immediately; no index rebuild required.', 'tlt-search' ); ?></p>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="columns">
				<div class="column col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Existing Synonym Groups', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<?php if ( empty( $groups ) ) : ?>
								<p><?php esc_html_e( 'No synonym groups defined yet.', 'tlt-search' ); ?></p>
							<?php else : ?>
								<table class="table table-hover" id="tlt-synonyms-table">
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
											<td><?php echo esc_html( $i + 1 ); ?></td>
											<td><?php echo esc_html( implode( ', ', $terms ) ); ?></td>
											<td style="text-align:center;">
												<button class="btn btn-error btn-sm tlt-remove-pin" data-index="<?php echo esc_attr( $i ); ?>"><i class="icon icon-delete"></i></button>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="column col-lg-6">
					<div class="card p-1">
						<div class="card-header">
							<div class="card-title h3 text-bold mb-2"><?php esc_html_e( 'Add Synonym Group', 'tlt-search' ); ?></div>
							<div class="card-subtitle"><?php esc_html_e( 'Enter two or more comma-separated terms. Changes take effect immediately; no index rebuild required.', 'tlt-search' ); ?></div>
						</div>
						<div class="card-body">
							<div class="input-group">
								<input type="text" id="tlt-synonym-input" class="form-input" placeholder="<?php esc_attr_e( 'e.g. tote bag, totebag, tote-bag', 'tlt-search' ); ?>" />
								<button id="tlt-add-synonym-btn" class="btn btn-primary input-group-btn"><?php esc_html_e( 'Add Group', 'tlt-search' ); ?></button>
							</div>
							<p id="tlt-synonym-msg"></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>	
</div>
