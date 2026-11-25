=== TLT Search ===
Contributors: trileotech
Tags: woocommerce, search, live search, product search, typo tolerance
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fast, typo-tolerant live product search for WooCommerce. Runs on your own server with no external service.

== Description ==

TLT Search replaces the default WooCommerce search with a Loupe (SQLite) index kept on your own site.

* Live results dropdown with images, prices, ratings, badges and optional add to cart
* Typo tolerance, stemming, synonyms, stop words, "did you mean" and popular searches
* Searches title, SKU, GTIN, variations, categories, brands, tags, attributes, custom fields and taxonomies (choose and weight them in settings)
* Filters, facets with live counts and sorting
* Pinned results plus boost and bury rules (Merchandising page)
* Analytics: searches, zero-result queries, clicks
* WPML and Polylang aware, Gutenberg block, Elementor widget, WP-CLI command
* Result caching, hide out-of-stock, exclusions

Usage: add the `[tlt_search]` shortcode, the "TLT Search" block, or the Elementor widget. Search results pages use the same engine.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` and activate it.
2. Open TLT Search in the admin menu and run the index build.
3. Add the search box with the shortcode, block or widget.

After changing searchable fields, stop words or exclusions, rebuild the index (admin dashboard banner or `wp tlt-search reindex`).

== WP-CLI ==

* `wp tlt-search reindex [--batch=100]` rebuild the index
* `wp tlt-search status` show index health

== Development ==

Run the standalone tests (no WordPress needed, needs `vendor/` from composer):

`php tests/run.php`

The index lives in the uploads folder as a SQLite file. Loupe needs pdo_sqlite with SQLite 3.35+, and works best below roughly 50,000 products.

== Changelog ==

= 1.0.0 =
* First release.
