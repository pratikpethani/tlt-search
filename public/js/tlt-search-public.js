document.addEventListener( 'alpine:init', () => {

	Alpine.data( 'TLTSearch', ( config = {} ) => ( {

		query: '',
		results: [],
		total: 0,
		page: 1,
		perPage: 10,
		totalPages: 0,
		loading: false,
		hasSearched: false,
		error: null,

		showFilters: config.showFilters ?? true,
		filterPanelOpen: true,

		filters: {
			categories: [],
			brands: [],
			tags: [],
			stock_status: '',
			price_min: '',
			price_max: '',
		},

		facets: {
			categories: [],
			brands: [],
			tags: [],
			stock_status: [],
			price: null,
		},

		init() {},

		async search() {
			this.page = 1;
			this.filters.categories  = [];
			this.filters.brands      = [];
			this.filters.tags        = [];
			this.filters.stock_status = '';
			this.filters.price_min   = '';
			this.filters.price_max   = '';

			if ( this.query.length < 3 ) {
				this.clearResults();
				return;
			}

			await this.fetchResults();
		},

		async fetchResults() {
			this.loading     = true;
			this.hasSearched = true;
			this.error       = null;

			try {
				const url = new URL( tltSearchConfig.apiUrl );
				url.searchParams.set( 'q',        this.query );
				url.searchParams.set( 'page',     this.page );
				url.searchParams.set( 'per_page', this.perPage );

				this.filters.categories.forEach( v => url.searchParams.append( 'categories[]', v ) );
				this.filters.brands.forEach( v => url.searchParams.append( 'brands[]', v ) );
				this.filters.tags.forEach( v => url.searchParams.append( 'tags[]', v ) );

				if ( this.filters.stock_status ) {
					url.searchParams.set( 'stock_status', this.filters.stock_status );
				}
				if ( this.filters.price_min !== '' ) {
					url.searchParams.set( 'price_min', this.filters.price_min );
				}
				if ( this.filters.price_max !== '' ) {
					url.searchParams.set( 'price_max', this.filters.price_max );
				}

				const response = await fetch( url.toString(), {
					headers: { 'X-WP-Nonce': tltSearchConfig.nonce },
				} );

				const json = await response.json();

				if ( json.success ) {
					this.results    = json.data.results;
					this.total      = json.data.total;
					this.totalPages = json.data.total_pages;
					if ( json.data.facets ) {
						this.facets = json.data.facets;
					}
				} else {
					this.error = json.error?.message ?? 'Search failed.';
					this.clearResults();
				}

			} catch ( e ) {
				this.error = 'Search failed. Please try again.';
				this.clearResults();
			} finally {
				this.loading = false;
			}
		},

		async toggleFilter( type, value ) {
			const idx = this.filters[ type ].indexOf( value );
			if ( idx === -1 ) {
				this.filters[ type ].push( value );
			} else {
				this.filters[ type ].splice( idx, 1 );
			}
			this.page = 1;
			await this.fetchResults();
		},

		async setStockFilter( value ) {
			this.filters.stock_status = ( this.filters.stock_status === value ) ? '' : value;
			this.page = 1;
			await this.fetchResults();
		},

		async applyPriceFilter() {
			this.page = 1;
			await this.fetchResults();
		},

		async clearFilters() {
			this.filters.categories   = [];
			this.filters.brands       = [];
			this.filters.tags         = [];
			this.filters.stock_status = '';
			this.filters.price_min    = '';
			this.filters.price_max    = '';
			this.page = 1;
			await this.fetchResults();
		},

		hasActiveFilters() {
			return this.filters.categories.length > 0
				|| this.filters.brands.length > 0
				|| this.filters.tags.length > 0
				|| this.filters.stock_status !== ''
				|| this.filters.price_min !== ''
				|| this.filters.price_max !== '';
		},

		activeFilterCount() {
			let count = this.filters.categories.length
				+ this.filters.brands.length
				+ this.filters.tags.length;
			if ( this.filters.stock_status !== '' ) count++;
			if ( this.filters.price_min !== '' || this.filters.price_max !== '' ) count++;
			return count;
		},

		hasFacets() {
			return this.facets.categories.length > 0
				|| this.facets.brands.length > 0
				|| this.facets.tags.length > 0
				|| this.facets.stock_status.length > 0
				|| this.facets.price !== null;
		},

		isFilterSelected( type, value ) {
			return this.filters[ type ].includes( value );
		},

		stockLabel( value ) {
			const map = { instock: 'In Stock', outofstock: 'Out of Stock', onbackorder: 'On Backorder' };
			return map[ value ] ?? value;
		},

		async prevPage() {
			if ( this.page <= 1 ) return;
			this.page--;
			await this.fetchResults();
		},

		async nextPage() {
			if ( this.page >= this.totalPages ) return;
			this.page++;
			await this.fetchResults();
		},

		hasResults() {
			return this.results.length > 0;
		},

		hasError() {
			return this.error !== null;
		},

		clearResults() {
			this.results     = [];
			this.total       = 0;
			this.totalPages  = 0;
			this.hasSearched = false;
			this.facets      = { categories: [], brands: [], tags: [], stock_status: [], price: null };
		},

	} ) );

} );
