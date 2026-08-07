<?php
defined( 'ABSPATH' ) || exit;
$placeholder   = $atts['placeholder'] ?? __( 'Search products…', 'tlt-search' );
$show_filters  = filter_var( $atts['show_filters'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
$show_filters_js = $show_filters ? 'true' : 'false';
?>

<div class="tlt-search" x-data="TLTSearch({showFilters: <?php echo $show_filters_js; ?>})">

	<!-- Search Input -->
	<div class="tlt-search__field">
		<input
			type="search"
			class="tlt-search__input"
			x-model="query"
			@input.debounce.300ms="search()"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			autocomplete="off"
			aria-label="<?php esc_attr_e( 'Search products', 'tlt-search' ); ?>"
		/>
		<span class="tlt-search__spinner" x-show="loading" aria-hidden="true"></span>
	</div>

	<!-- Error State -->
	<p class="tlt-search__error" x-show="hasError()" x-text="error" role="alert"></p>

	<!-- Results / filter bar (visible after first search) -->
	<div class="tlt-search__meta-bar" x-show="hasSearched && !loading && !hasError()">

		<span class="tlt-search__result-count">
			<span x-text="total"></span>
			<?php esc_html_e( 'result(s)', 'tlt-search' ); ?>
		</span>

		<template x-if="showFilters && hasFacets()">
			<div class="tlt-search__filter-controls">
				<button
					class="tlt-search__filter-toggle"
					@click="filterPanelOpen = !filterPanelOpen"
					:aria-expanded="filterPanelOpen"
					type="button"
				>
					<?php esc_html_e( 'Filters', 'tlt-search' ); ?>
					<span class="tlt-search__filter-badge" x-show="hasActiveFilters()" x-text="activeFilterCount()"></span>
					<span class="tlt-search__filter-chevron" :class="filterPanelOpen ? 'tlt-search__filter-chevron--up' : ''">&#8964;</span>
				</button>

				<button
					class="tlt-search__filter-clear"
					x-show="hasActiveFilters()"
					@click="clearFilters()"
					type="button"
				>
					<?php esc_html_e( 'Clear all', 'tlt-search' ); ?>
				</button>
			</div>
		</template>

	</div>

	<!-- Filter Panel -->
	<template x-if="showFilters && hasSearched && hasFacets() && filterPanelOpen">
		<div class="tlt-search__filter-panel">

			<!-- Categories -->
			<template x-if="facets.categories.length > 0">
				<div class="tlt-search__facet">
					<p class="tlt-search__facet-title"><?php esc_html_e( 'Category', 'tlt-search' ); ?></p>
					<template x-for="opt in facets.categories" :key="opt.value">
						<label class="tlt-search__facet-label" :class="{'tlt-search__facet-label--active': isFilterSelected('categories', opt.value)}">
							<input
								type="checkbox"
								class="tlt-search__facet-check"
								:value="opt.value"
								:checked="isFilterSelected('categories', opt.value)"
								@change="toggleFilter('categories', opt.value)"
							/>
							<span class="tlt-search__facet-name" x-text="opt.value"></span>
							<span class="tlt-search__facet-count" x-text="opt.count"></span>
						</label>
					</template>
				</div>
			</template>

			<!-- Brands -->
			<template x-if="facets.brands.length > 0">
				<div class="tlt-search__facet">
					<p class="tlt-search__facet-title"><?php esc_html_e( 'Brand', 'tlt-search' ); ?></p>
					<template x-for="opt in facets.brands" :key="opt.value">
						<label class="tlt-search__facet-label" :class="{'tlt-search__facet-label--active': isFilterSelected('brands', opt.value)}">
							<input
								type="checkbox"
								class="tlt-search__facet-check"
								:value="opt.value"
								:checked="isFilterSelected('brands', opt.value)"
								@change="toggleFilter('brands', opt.value)"
							/>
							<span class="tlt-search__facet-name" x-text="opt.value"></span>
							<span class="tlt-search__facet-count" x-text="opt.count"></span>
						</label>
					</template>
				</div>
			</template>

			<!-- Tags -->
			<template x-if="facets.tags.length > 0">
				<div class="tlt-search__facet">
					<p class="tlt-search__facet-title"><?php esc_html_e( 'Tag', 'tlt-search' ); ?></p>
					<template x-for="opt in facets.tags" :key="opt.value">
						<label class="tlt-search__facet-label" :class="{'tlt-search__facet-label--active': isFilterSelected('tags', opt.value)}">
							<input
								type="checkbox"
								class="tlt-search__facet-check"
								:value="opt.value"
								:checked="isFilterSelected('tags', opt.value)"
								@change="toggleFilter('tags', opt.value)"
							/>
							<span class="tlt-search__facet-name" x-text="opt.value"></span>
							<span class="tlt-search__facet-count" x-text="opt.count"></span>
						</label>
					</template>
				</div>
			</template>

			<!-- Stock Status -->
			<template x-if="facets.stock_status.length > 0">
				<div class="tlt-search__facet">
					<p class="tlt-search__facet-title"><?php esc_html_e( 'Availability', 'tlt-search' ); ?></p>
					<template x-for="opt in facets.stock_status" :key="opt.value">
						<label class="tlt-search__facet-label" :class="{'tlt-search__facet-label--active': filters.stock_status === opt.value}">
							<input
								type="checkbox"
								class="tlt-search__facet-check"
								:value="opt.value"
								:checked="filters.stock_status === opt.value"
								@change="setStockFilter(opt.value)"
							/>
							<span class="tlt-search__facet-name" x-text="stockLabel(opt.value)"></span>
							<span class="tlt-search__facet-count" x-text="opt.count"></span>
						</label>
					</template>
				</div>
			</template>

			<!-- Price Range -->
			<template x-if="facets.price !== null">
				<div class="tlt-search__facet">
					<p class="tlt-search__facet-title"><?php esc_html_e( 'Price', 'tlt-search' ); ?></p>
					<div class="tlt-search__facet-price">
						<input
							type="number"
							class="tlt-search__price-input"
							x-model="filters.price_min"
							:placeholder="facets.price.min"
							min="0"
							step="0.01"
							aria-label="<?php esc_attr_e( 'Minimum price', 'tlt-search' ); ?>"
						/>
						<span class="tlt-search__price-sep">&mdash;</span>
						<input
							type="number"
							class="tlt-search__price-input"
							x-model="filters.price_max"
							:placeholder="facets.price.max"
							min="0"
							step="0.01"
							aria-label="<?php esc_attr_e( 'Maximum price', 'tlt-search' ); ?>"
						/>
						<button
							type="button"
							class="tlt-search__price-apply"
							@click="applyPriceFilter()"
						>
							<?php esc_html_e( 'Go', 'tlt-search' ); ?>
						</button>
					</div>
					<p class="tlt-search__price-range-hint">
						<?php esc_html_e( 'Range:', 'tlt-search' ); ?>
						<span x-text="facets.price.min"></span> &mdash; <span x-text="facets.price.max"></span>
					</p>
				</div>
			</template>

		</div>
	</template>

	<!-- Empty State -->
	<p
		class="tlt-search__empty"
		x-show="hasSearched && !loading && !hasResults() && !hasError()"
	>
		<?php esc_html_e( 'No products found for', 'tlt-search' ); ?>
		&ldquo;<span x-text="query"></span>&rdquo;.
	</p>

	<!-- Results List -->
	<ul class="tlt-search__results" x-show="hasResults()" role="list">
		<template x-for="result in results" :key="result.id">
			<li class="tlt-search__result">
				<a :href="result.permalink" class="tlt-search__result-link">

					<img
						:src="result.image"
						:alt="result.title"
						class="tlt-search__result-image"
						loading="lazy"
						width="60"
						height="60"
					/>

					<div class="tlt-search__result-body">
						<span class="tlt-search__result-title" x-text="result.title"></span>
						<span class="tlt-search__result-price" x-html="result.price"></span>
					</div>

				</a>
			</li>
		</template>
	</ul>

	<!-- Pagination -->
	<nav class="tlt-search__pagination" x-show="totalPages > 1" aria-label="<?php esc_attr_e( 'Search results pages', 'tlt-search' ); ?>">
		<button
			class="tlt-search__page-btn"
			@click="prevPage()"
			:disabled="page <= 1"
			aria-label="<?php esc_attr_e( 'Previous page', 'tlt-search' ); ?>"
		>&lsaquo;</button>

		<span class="tlt-search__page-info">
			<span x-text="page"></span> / <span x-text="totalPages"></span>
		</span>

		<button
			class="tlt-search__page-btn"
			@click="nextPage()"
			:disabled="page >= totalPages"
			aria-label="<?php esc_attr_e( 'Next page', 'tlt-search' ); ?>"
		>&rsaquo;</button>
	</nav>

</div>
