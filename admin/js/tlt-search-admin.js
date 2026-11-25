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
				$btn.prop('disabled', true).text('Starting…');
				$progress.show();
				$bar.css('width', '0%');
				$text.text('Getting ready…');

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
			$text.text('Updating ' + offset + ' / ' + total + '…');

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
				$text.text('Updating ' + newOffset + ' / ' + total + '…');

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
			$text.html('<strong style="color:#0a8a0a;"><i class="icon icon-check"></i> Done — ' + indexed + ' products updated.</strong>');
			$btn.prop('disabled', false).text('Update search data');
			setTimeout(function () { location.reload(); }, 1500);
		}

		function showError(msg) {
			$text.html('<strong style="color:#d63638;">Error: ' + msg + '</strong>');
			$btn.prop('disabled', false).text('Update search data');
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
					// Groups are renumbered after a delete, so reload to get fresh indexes.
					location.reload();
				} else {
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				$btn.prop('disabled', false);
			});
		});

		// ── Settings tabs ────────────────────────────────────────────────────

		// Only the Settings page links carry data-tab (Analytics reuses the style for plain links).
		var $tabs = $('.tlt-subtabs .nav-tab[data-tab]');

		if ( $tabs.length ) {

			var showTab = function (tab) {
				if ( ! $tabs.filter('[data-tab="' + tab + '"]').length ) tab = 'search';
				$tabs.removeClass('nav-tab-active').removeAttr('aria-current');
				$tabs.filter('[data-tab="' + tab + '"]').addClass('nav-tab-active').attr('aria-current', 'true');
				$('.tlt-tab-panel').prop('hidden', true).filter('[data-panel="' + tab + '"]').prop('hidden', false);
				// Remembered so the same tab is open again after saving.
				sessionStorage.setItem('tltSettingsTab', tab);
			};

			$tabs.on('click', function (e) {
				e.preventDefault();
				showTab($(this).data('tab'));
			});

			// An invalid field on a hidden tab would block saving silently: open its tab.
			$('.tlt-tab-panel :input').on('invalid', function () {
				// First invalid field wins. Native selector: jQuery has no :invalid.
				showTab($(this.form.querySelector('input:invalid')).closest('.tlt-tab-panel').data('panel'));
			});

			showTab(sessionStorage.getItem('tltSettingsTab'));
		}

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
								'>' + ( item.is_pinned ? '<i class="icon icon-check"></i> Pinned' : '<i class="icon icon-plus"></i> Pin' ) + '</button> ' +
								'<button class="btn btn-sm tlt-rule-product" data-mode="boost" data-product-id="' + item.id + '">Boost</button> ' +
								'<button class="btn btn-sm tlt-rule-product" data-mode="bury" data-product-id="' + item.id + '">Bury</button>' +
							'</td>' +
						'</tr>';
					});

					$pinResults.html(
						'<table class="table table-hover">' +
						'<thead><tr><th>Product</th><th style="width:220px;text-align:right;">Action</th></tr></thead>' +
						'<tbody>' + rows.join('') + '</tbody></table>'
					);
				})
				.fail(function () {
					$pinResults.html('<p style="color:#d63638;">Search failed — check your error log.</p>');
				})
				.always(function () {
					$pinSearchBtn.prop('disabled', false).text('Find products');
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

		// Analytics: bar chart (Chart.js) and sortable/searchable tables (simple-datatables).
		var chartEl = document.getElementById('tlt-volume-chart');
		if ( chartEl && window.Chart ) {
			new window.Chart(chartEl, {
				type: 'bar',
				data: {
					labels   : JSON.parse(chartEl.dataset.labels || '[]'),
					datasets : [{ label: chartEl.dataset.label, data: JSON.parse(chartEl.dataset.values || '[]'), backgroundColor: '#5755d9' }],
				},
				options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
			});
		}
		if ( window.simpleDatatables ) {
			document.querySelectorAll('table.tlt-datatable').forEach(function (t) {
				new window.simpleDatatables.DataTable(t, { perPage: 10, perPageSelect: false });
			});
		}

		// Zero-result list: one-click synonym.
		$(document).on('click', '.tlt-zero-add-synonym', function () {
			var $btn  = $(this);
			var other = $btn.siblings('.tlt-zero-synonym').val().trim();
			if ( ! other ) return;
			$btn.prop('disabled', true);
			$.post(ajaxUrl, { action: 'tlt_search_add_synonym_group', nonce: nonce, terms: $btn.data('query') + ', ' + other })
			.done(function (res) {
				if ( res.success ) { $btn.text('Added').siblings('input').prop('disabled', true); }
				else { $btn.prop('disabled', false); alert(res.data || 'Error.'); }
			})
			.fail(function () { $btn.prop('disabled', false); });
		});

		// Prefill the pin search when arriving from the zero-result list.
		if ( $pinQuery.length ) {
			var prefill = new URLSearchParams(window.location.search).get('q');
			if ( prefill ) { $pinQuery.val(prefill); $pinSearchBtn.trigger('click'); }
		}

		$(document).on('click', '.tlt-rule-product', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(ajaxUrl, {
				action     : 'tlt_search_set_rule',
				nonce      : nonce,
				product_id : $btn.data('product-id'),
				mode       : $btn.data('mode'),
			})
			.done(function (res) {
				if ( res.success ) { window.location.reload(); } else { $btn.prop('disabled', false); alert(res.data || 'Error saving rule.'); }
			})
			.fail(function () { $btn.prop('disabled', false); });
		});

		$(document).on('click', '.tlt-remove-rule', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(ajaxUrl, { action: 'tlt_search_remove_rule', nonce: nonce, product_id: $btn.data('product-id') })
			.done(function (res) {
				if ( res.success ) { $btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); } else { $btn.prop('disabled', false); }
			})
			.fail(function () { $btn.prop('disabled', false); });
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

				$partialBtn.prop('disabled', true).text('Updating…');
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
							res.data.count + ' product(s) updated.</span>'
						);
					}
				})
				.fail(function () {
					$partialStatus.html('<span style="color:#d63638;">Server error — check your error log.</span>');
				})
				.always(function () {
					$partialBtn.prop('disabled', false).text('Update category');
				});
			});
		}

	});

})(jQuery);
