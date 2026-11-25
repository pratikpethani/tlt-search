document.addEventListener( 'alpine:init', () => {

	Alpine.data( 'TLTSearch', ( config = {} ) => ( {

		query: '',
		results: [],
		total: 0,
		page: 1,
		perPage: config.perPage || tltSearchConfig.perPage || 10,
		scope: config.scope || '',
		cfg: tltSearchConfig,
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

		activeIndex: -1,
		terms: [],
		didYouMean: null,
		popular: [],
		popularLoaded: false,
		showPopular: false,
		sort: 'relevance',

		init() {},

		// Empty search box gets focus: offer popular searches (fetched once).
		async onFocus() {
			if ( this.query !== '' || ! this.cfg.popularUrl ) return;
			if ( ! this.popularLoaded ) {
				this.popularLoaded = true;
				try {
					const json = await ( await fetch( this.cfg.popularUrl ) ).json();
					this.popular = json.success ? json.data : [];
				} catch ( e ) {
					this.popular = [];
				}
			}
			this.showPopular = this.popular.length !== 0;
		},

		async usePopular( term ) {
			this.query = term;
			this.showPopular = false;
			await this.search();
		},

		async useSuggestion() {
			this.query = this.didYouMean;
			await this.search();
		},

		// Simple products are added without leaving the page; others follow the link.
		async addToCart( result, event ) {
			if ( ! result.add_to_cart.ajax || ! this.cfg.cartUrl ) return;
			event.preventDefault();
			if ( result.atc_busy ) return;
			result.atc_busy = true;
			try {
				const res  = await fetch( this.cfg.cartUrl, { method: 'POST', body: new URLSearchParams( { product_id: result.id, quantity: 1 } ) } );
				const data = await res.json();
				if ( data && data.error && data.product_url ) { window.location.href = data.product_url; return; }
				result.atc_label = this.cfg.addedText;
				// Let WooCommerce refresh mini-cart fragments if jQuery is present.
				if ( window.jQuery ) window.jQuery( document.body ).trigger( 'added_to_cart', [ data.fragments, data.cart_hash ] );
			} catch ( e ) {
				window.location.href = result.add_to_cart.url;
			} finally {
				result.atc_busy = false;
			}
		},

		// Tell the server which result was clicked (feeds the Analytics page).
		trackClick( result ) {
			if ( ! this.cfg.clickUrl ) return;
			const body = new URLSearchParams( { q: this.query, product_id: result.id } );
			try {
				fetch( this.cfg.clickUrl, { method: 'POST', body, keepalive: true } );
			} catch ( e ) { /* tracking must never block navigation */ }
		},

		// Escape HTML, then wrap query words in <mark>. Safe for x-html.
		highlight( text ) {
			const esc = s => String( s ).replace( /[&<>"']/g, c => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ] ) );
			const safe = esc( text ?? '' );
			const words = this.query.trim().split( /\s+/ ).filter( w => w.length > 1 ).map( w => esc( w ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) );
			if ( ! words.length ) return safe;
			return safe.replace( new RegExp( '(' + words.join( '|' ) + ')', 'gi' ), '<mark>$1</mark>' );
		},

		moveActive( step ) {
			if ( ! this.results.length ) return;
			this.activeIndex = ( this.activeIndex + step + this.results.length ) % this.results.length;
			this.$nextTick( () => this.$root.querySelector( '.tlt-search__result--active' )?.scrollIntoView( { block: 'nearest' } ) );
		},

		openActive() {
			const r = this.results[ this.activeIndex ];
			if ( r ) window.location.href = r.permalink;
		},

		closeResults() {
			this.showPopular = false;
			this.activeIndex = -1;
			this.hasSearched = false;
		},

		async search() {
			this.page = 1;
			this.activeIndex = -1;
			this.showPopular = false;
			this.filters.categories  = [];
			this.filters.brands      = [];
			this.filters.tags        = [];
			this.filters.stock_status = '';
			this.filters.price_min   = '';
			this.filters.price_max   = '';

			if ( this.query.length < ( tltSearchConfig.minLength || 3 ) ) {
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
				if ( this.scope ) {
					url.searchParams.set( 'scope', this.scope );
				}
				if ( tltSearchConfig.lang ) {
					url.searchParams.set( 'lang', tltSearchConfig.lang );
				}
				if ( this.sort !== 'relevance' ) {
					url.searchParams.set( 'sort', this.sort );
				}

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
					this.activeIndex = -1;
					this.terms      = json.data.terms ?? [];
					this.didYouMean = json.data.did_you_mean ?? null;
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

		async changeSort() {
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

		hasPages() {
			return this.totalPages > 1;
		},

		isFirstPage() {
			return this.page <= 1;
		},

		isLastPage() {
			return this.page >= this.totalPages;
		},

		hasResults() {
			return this.results.length > 0;
		},

		hasError() {
			return this.error !== null;
		},

		clearResults() {
			this.results     = [];
			this.terms       = [];
			this.didYouMean  = null;
			this.total       = 0;
			this.totalPages  = 0;
			this.hasSearched = false;
			this.facets      = { categories: [], brands: [], tags: [], stock_status: [], price: null };
		},

	} ) );

} );
