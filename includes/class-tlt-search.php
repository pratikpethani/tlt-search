<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://trileotech.com/
 * @since      1.0.0
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Tlt_Search
 * @subpackage Tlt_Search/includes
 * @author     TLT Suite <info@trileotech.com>
 */
class Tlt_Search {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Tlt_Search_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'TLT_SEARCH_VERSION' ) ) {
			$this->version = TLT_SEARCH_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'tlt-search';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_woocommerce_hooks();
		$this->define_api_hooks();
		$this->define_analytics_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Tlt_Search_Loader. Orchestrates the hooks of the plugin.
	 * - Tlt_Search_i18n. Defines internationalization functionality.
	 * - Tlt_Search_Admin. Defines all hooks for the admin area.
	 * - Tlt_Search_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tlt-search-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tlt-search-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-tlt-search-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-tlt-search-public.php';

		$this->loader = new Tlt_Search_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Tlt_Search_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Tlt_Search_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Tlt_Search_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'handle_rebuild_index' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'handle_search_request' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'wp_ajax_tlt_search_init_rebuild',      $plugin_admin, 'ajax_init_rebuild' );
		$this->loader->add_action( 'wp_ajax_tlt_search_index_batch',       $plugin_admin, 'ajax_index_batch' );
		$this->loader->add_action( 'wp_ajax_tlt_search_reindex_category',  $plugin_admin, 'ajax_reindex_category' );
		$this->loader->add_action( 'wp_ajax_tlt_search_add_synonym_group', $plugin_admin, 'ajax_add_synonym_group' );
		$this->loader->add_action( 'wp_ajax_tlt_search_delete_synonym_group', $plugin_admin, 'ajax_delete_synonym_group' );
		$this->loader->add_action( 'wp_ajax_tlt_search_add_pin',           $plugin_admin, 'ajax_add_pin' );
		$this->loader->add_action( 'wp_ajax_tlt_search_remove_pin',        $plugin_admin, 'ajax_remove_pin' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Tlt_Search_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

	}

	private function define_woocommerce_hooks(): void {

		$product_sync = new \TLTSuite\TLTSearch\WooCommerce\ProductSync();

		$this->loader->add_action( 'woocommerce_update_product', $product_sync, 'product_updated' );
		$this->loader->add_action( 'woocommerce_new_product', $product_sync, 'product_updated' );
		$this->loader->add_action( 'trashed_post', $product_sync, 'product_deleted' );
	}

	private function define_api_hooks(): void {

		$controller = new \TLTSuite\TLTSearch\API\SearchController();
		$this->loader->add_action( 'rest_api_init', $controller, 'register_routes' );

		$trending = new \TLTSuite\TLTSearch\API\TrendingController();
		$this->loader->add_action( 'rest_api_init', $trending, 'register_routes' );

		add_filter( 'rest_endpoints', [ $this, 'hide_rest_namespace' ] );
	}

	public function hide_rest_namespace( $endpoints ): array {
		unset( $endpoints['/tlt-search/v1'] );
		return $endpoints;
	}

	private function define_analytics_hooks(): void {

		// Static callbacks — register directly since the loader pattern requires object instances.
		add_action( 'admin_init', [ '\TLTSuite\TLTSearch\Analytics\SearchLogger', 'maybe_create_table' ] );
		add_action( 'admin_init', [ '\TLTSuite\TLTSearch\Merchandising\PinManager', 'maybe_create_table' ] );

		// Daily cron: delete log entries older than 90 days.
		$logger = new \TLTSuite\TLTSearch\Analytics\SearchLogger();
		$this->loader->add_action( 'tlt_search_cleanup_logs', $logger, 'run_cleanup' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Tlt_Search_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
