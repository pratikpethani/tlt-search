(function ($) {
	'use strict';

	$(function () {

		var cfg      = window.tltSearchAdmin || {};
		var ajaxUrl  = cfg.ajaxUrl  || ajaxurl;
		var nonce    = cfg.nonce    || '';
		var perBatch = cfg.perBatch || 100;

		// ── Full Rebuild ─────────────────────────────────────────────────────

		var $btn      = $('#tlt-rebuild-btn');
		var $progress = $('#tlt-index-progress');
		var $bar      = $('#tlt-progress-bar');
		var $text     = $('#tlt-progress-text');

		if ( $btn.length ) {

			$btn.on('click', function () {
				$btn.prop('disabled', true).text('Preparing…');
				$progress.show();
				$bar.css('width', '0%');
				$text.text('Clearing existing index…');

				$.post(ajaxUrl, {
					action : 'tlt_search_init_rebuild',
					nonce  : nonce,
				})
				.done(function (res) {
					if ( ! res.success ) {
						return showError(res.data);
					}
					var total = res.data.total;
					if ( total === 0 ) {
						return showDone(0, 0);
					}
					indexBatch(0, total);
				})
				.fail(function () {
					showError('Server error — check your error log.');
				});
			});
		}

		function indexBatch(offset, total) {
			$text.text('Indexing ' + offset + ' / ' + total + '…');

			$.post(ajaxUrl, {
				action    : 'tlt_search_index_batch',
				nonce     : nonce,
				offset    : offset,
				per_batch : perBatch,
			})
			.done(function (res) {
				if ( ! res.success ) {
					return showError(res.data);
				}

				var newOffset = offset + res.data.count;
				var pct       = total > 0 ? Math.min(100, Math.round(newOffset / total * 100)) : 100;
				$bar.css('width', pct + '%');
				$text.text('Indexing ' + newOffset + ' / ' + total + '…');

				if ( res.data.done ) {
					showDone(newOffset, total);
				} else {
					indexBatch(newOffset, total);
				}
			})
			.fail(function () {
				showError('Server error on batch at offset ' + offset + '.');
			});
		}

		function showDone(indexed) {
			$bar.css('width', '100%');
			$text.html('<strong style="color:#0a8a0a;"><i class="icon icon-check"></i> Done — ' + indexed + ' products indexed.</strong>');
			$btn.prop('disabled', false).text('Rebuild Index');
			setTimeout(function () { location.reload(); }, 1500);
		}

		function showError(msg) {
			$text.html('<strong style="color:#d63638;">Error: ' + msg + '</strong>');
			$btn.prop('disabled', false).text('Rebuild Index');
		}

		// ── Synonyms Manager ─────────────────────────────────────────────────

		var $synonymInput  = $('#tlt-synonym-input');
		var $addSynonymBtn = $('#tlt-add-synonym-btn');
		var $synonymMsg    = $('#tlt-synonym-msg');

		if ( $addSynonymBtn.length ) {

			$addSynonymBtn.on('click', function () {
				var terms = $synonymInput.val().trim();
				if ( ! terms ) return;

				$addSynonymBtn.prop('disabled', true);
				$synonymMsg.text('');

				$.post(ajaxUrl, {
					action : 'tlt_search_add_synonym_group',
					nonce  : nonce,
					terms  : terms,
				})
				.done(function (res) {
					if ( ! res.success ) {
						$synonymMsg.css('color', '#d63638').text(res.data);
					} else {
						$synonymMsg.css('color', '#0a8a0a').text('Group added.');
						$synonymInput.val('');
						setTimeout(function () { location.reload(); }, 800);
					}
				})
				.fail(function () {
					$synonymMsg.css('color', '#d63638').text('Server error.');
				})
				.always(function () {
					$addSynonymBtn.prop('disabled', false);
				});
			});

			$synonymInput.on('keypress', function (e) {
				if ( e.which === 13 ) { $addSynonymBtn.trigger('click'); }
			});
		}

		$(document).on('click', '.tlt-delete-synonym', function () {
			var $btn  = $(this);
			var index = $btn.data('index');

			if ( ! confirm('Remove this synonym group?') ) return;

			$btn.prop('disabled', true);

			$.post(ajaxUrl, {
				action      : 'tlt_search_delete_synonym_group',
				nonce       : nonce,
				group_index : index,
			})
			.done(function (res) {
				if ( res.success ) {
					$('#tlt-synonym-row-' + index).fadeOut(300, function () {
						$(this).remove();
					});
				}
			})
			.fail(function () {
				$btn.prop('disabled', false);
			});
		});

		// ── Merchandising ─────────────────────────────────────────────────────

		var $pinQuery      = $('#tlt-pin-query');
		var $pinSearchBtn  = $('#tlt-pin-search-btn');
		var $pinResults    = $('#tlt-pin-results');
		var restUrl        = (cfg.restUrl || '').replace(/\/$/, '');

		if ( $pinSearchBtn.length ) {

			$pinSearchBtn.on('click', function () {
				var q = $pinQuery.val().trim();
				if ( ! q ) return;

				$pinSearchBtn.prop('disabled', true).text('Searching…');
				$pinResults.html('<p style="color:#888;">Loading…</p>');

				$.getJSON(restUrl + '/search', { q: q, per_page: 20 })
				.done(function (res) {
					if ( ! res.success || ! res.data || ! res.data.results || ! res.data.results.length ) {
						$pinResults.html('<p style="color:#646970;">No results found for <strong>' + $('<span>').text(q).html() + '</strong>.</p>');
						return;
					}

					var rows = res.data.results.map(function (item) {
						var img   = item.image ? '<img src="' + item.image + '" style="width:40px;height:40px;object-fit:cover;border-radius:3px;vertical-align:middle;margin-right:8px;">' : '';
						var label = '<strong>' + $('<span>').text(item.title).html() + '</strong>';
						if ( item.sku ) label += ' <span style="color:#888;font-size:0.85em;">SKU: ' + $('<span>').text(item.sku).html() + '</span>';
						return '<tr>' +
							'<td>' + img + label + '</td>' +
							'<td style="text-align:right;">' +
								'<button class="btn btn-sm btn-primary tlt-pin-product" ' +
									'data-product-id="' + item.id + '" ' +
									'data-query="' + $('<span>').text(q).html() + '"' +
									( item.is_pinned ? ' disabled title="Already pinned"' : '' ) +
								'>' + ( item.is_pinned ? '<i class="icon icon-check"></i> Pinned' : '<i class="icon icon-plus"></i> Pin' ) + '</button>' +
							'</td>' +
						'</tr>';
					});

					$pinResults.html(
						'<table class="table table-hover">' +
						'<thead><tr><th>Product</th><th style="width:90px;text-align:right;">Action</th></tr></thead>' +
						'<tbody>' + rows.join('') + '</tbody></table>'
					);
				})
				.fail(function () {
					$pinResults.html('<p style="color:#d63638;">Search failed — check your error log.</p>');
				})
				.always(function () {
					$pinSearchBtn.prop('disabled', false).text('Search Products');
				});
			});

			$pinQuery.on('keypress', function (e) {
				if ( e.which === 13 ) { $pinSearchBtn.trigger('click'); }
			});
		}

		$(document).on('click', '.tlt-pin-product', function () {
			var $btn       = $(this);
			var productId  = $btn.data('product-id');
			var query      = $btn.data('query');

			$btn.prop('disabled', true).text('Pinning…');

			$.post(ajaxUrl, {
				action     : 'tlt_search_add_pin',
				nonce      : nonce,
				query      : query,
				product_id : productId,
			})
			.done(function (res) {
				if ( res.success ) {
					$btn.text('Pinned');
				} else {
					$btn.prop('disabled', false).text('Pin');
					alert(res.data || 'Error adding pin.');
				}
			})
			.fail(function () {
				$btn.prop('disabled', false).text('Pin');
			});
		});

		$(document).on('click', '.tlt-remove-pin', function () {
			var $btn  = $(this);
			var pinId = $btn.data('pin-id');

			if ( ! confirm('Remove this pin?') ) return;

			$btn.prop('disabled', true);

			$.post(ajaxUrl, {
				action : 'tlt_search_remove_pin',
				nonce  : nonce,
				pin_id : pinId,
			})
			.done(function (res) {
				if ( res.success ) {
					$btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
				} else {
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				$btn.prop('disabled', false);
			});
		});

		// ── Partial Rebuild (by category) ────────────────────────────────────

		var $catSelect     = $('#tlt-category-select');
		var $partialBtn    = $('#tlt-partial-btn');
		var $partialStatus = $('#tlt-partial-status');

		if ( $catSelect.length ) {

			$catSelect.on('change', function () {
				$partialBtn.prop('disabled', ! $(this).val());
				$partialStatus.text('');
			});

			$partialBtn.on('click', function () {
				var termId = $catSelect.val();
				if ( ! termId ) return;

				$partialBtn.prop('disabled', true).text('Reindexing…');
				$partialStatus.text('');

				$.post(ajaxUrl, {
					action  : 'tlt_search_reindex_category',
					nonce   : nonce,
					term_id : termId,
				})
				.done(function (res) {
					if ( ! res.success ) {
						$partialStatus.html('<span style="color:#d63638;">Error: ' + res.data + '</span>');
					} else {
						$partialStatus.html(
							'<span style="color:#0a8a0a;font-weight:600;"><i class="icon icon-check"></i> ' +
							res.data.count + ' product(s) re-indexed.</span>'
						);
					}
				})
				.fail(function () {
					$partialStatus.html('<span style="color:#d63638;">Server error — check your error log.</span>');
				})
				.always(function () {
					$partialBtn.prop('disabled', false).text('Reindex Category');
				});
			});
		}

	});

})(jQuery);
