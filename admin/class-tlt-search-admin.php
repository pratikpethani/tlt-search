<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://trileotech.com/
 * @since      1.0.0
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/admin
 * @author     TLT Suite <info@trileotech.com>
 */
class Tlt_Search_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tlt_Search_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tlt_Search_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tlt-search-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tlt_Search_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tlt_Search_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tlt-search-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'tltSearchAdmin', [
			'nonce'    => wp_create_nonce( 'tlt_search_index' ),
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'restUrl'  => rest_url( 'tlt-search/v1' ),
			'perBatch' => 100,
		] );

	}

	public function add_plugin_admin_menu() {

		add_menu_page(
			__( 'TLT Search', 'tlt-search' ),
			__( 'TLT Search', 'tlt-search' ),
			'manage_options',
			'tlt-search',
			[ $this, 'display_dashboard_page' ],
			'dashicons-search',
			56
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Dashboard', 'tlt-search' ),
			__( 'Dashboard', 'tlt-search' ),
			'manage_options',
			'tlt-search',
			[ $this, 'display_dashboard_page' ]
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Settings', 'tlt-search' ),
			__( 'Settings', 'tlt-search' ),
			'manage_options',
			'tlt-search-settings',
			[ $this, 'display_settings_page' ]
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Analytics', 'tlt-search' ),
			__( 'Analytics', 'tlt-search' ),
			'manage_options',
			'tlt-search-analytics',
			[ $this, 'display_analytics_page' ]
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Synonyms', 'tlt-search' ),
			__( 'Synonyms', 'tlt-search' ),
			'manage_options',
			'tlt-search-synonyms',
			[ $this, 'display_synonyms_page' ]
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Merchandising', 'tlt-search' ),
			__( 'Merchandising', 'tlt-search' ),
			'manage_options',
			'tlt-search-merchandising',
			[ $this, 'display_merchandising_page' ]
		);

		add_submenu_page(
			'tlt-search',
			__( 'TLT Search — Diagnostics', 'tlt-search' ),
			__( 'Diagnostics', 'tlt-search' ),
			'manage_options',
			'tlt-search-diagnostics',
			[ $this, 'display_diagnostics_page' ]
		);
	}

	public function display_dashboard_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/dashboard.php';
	}

	public function display_settings_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/settings.php';
	}

	public function display_analytics_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/analytics.php';
	}

	public function display_synonyms_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/synonyms.php';
	}

	public function display_merchandising_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/merchandising.php';
	}

	public function display_diagnostics_page() {
		require plugin_dir_path( __FILE__ ) . 'partials/diagnostics.php';
	}

	public function register_settings(): void {

		register_setting(
			'tlt_search_settings_group',
			'tlt_search_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [],
			]
		);
	}

	public function sanitize_settings( $input ): array {

		$clean = [];

		$clean['stop_words'] = sanitize_text_field( $input['stop_words'] ?? '' );

		// Synonyms are now managed via the dedicated Synonyms Manager page.
		// Preserve the stored value so legacy data is not lost on settings save.
		$existing          = (array) get_option( 'tlt_search_settings', [] );
		$clean['synonyms'] = sanitize_textarea_field( $existing['synonyms'] ?? '' );

		return $clean;
	}

	public function ajax_init_rebuild(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		try {
			$indexer = new \TLTSuite\TLTSearch\Indexing\BulkIndexer();
			$total   = $indexer->init_rebuild();
			wp_send_json_success( [ 'total' => $total ] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	public function ajax_add_synonym_group(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$raw   = sanitize_text_field( wp_unslash( $_POST['terms'] ?? '' ) );
		$terms = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );

		$manager = new \TLTSuite\TLTSearch\Synonyms\SynonymManager();

		if ( ! $manager->add_group( $terms ) ) {
			wp_send_json_error( __( 'Provide at least two comma-separated terms.', 'tlt-search' ), 400 );
		}

		wp_send_json_success( [ 'groups' => $manager->get_groups() ] );
	}

	public function ajax_delete_synonym_group(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$index   = absint( $_POST['group_index'] ?? -1 );
		$manager = new \TLTSuite\TLTSearch\Synonyms\SynonymManager();
		$manager->delete_group( $index );

		wp_send_json_success( [ 'groups' => $manager->get_groups() ] );
	}

	public function ajax_add_pin(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$query      = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
		$product_id = absint( $_POST['product_id'] ?? 0 );

		if ( '' === $query || ! $product_id ) {
			wp_send_json_error( __( 'Invalid query or product ID.', 'tlt-search' ), 400 );
		}

		( new \TLTSuite\TLTSearch\Merchandising\PinManager() )->add_pin( $query, $product_id );

		wp_send_json_success();
	}

	public function ajax_remove_pin(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$pin_id = absint( $_POST['pin_id'] ?? 0 );

		if ( ! $pin_id ) {
			wp_send_json_error( __( 'Invalid pin ID.', 'tlt-search' ), 400 );
		}

		( new \TLTSuite\TLTSearch\Merchandising\PinManager() )->remove_pin( $pin_id );

		wp_send_json_success();
	}

	public function ajax_reindex_category(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$term_id = absint( $_POST['term_id'] ?? 0 );

		if ( ! $term_id ) {
			wp_send_json_error( __( 'Invalid category.', 'tlt-search' ), 400 );
		}

		try {
			$indexer = new \TLTSuite\TLTSearch\Indexing\BulkIndexer();
			$count   = $indexer->reindex_category( $term_id );
			wp_send_json_success( [ 'count' => $count ] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	public function ajax_index_batch(): void {

		check_ajax_referer( 'tlt_search_index', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'tlt-search' ), 403 );
		}

		$offset    = absint( $_POST['offset'] ?? 0 );
		$per_batch = absint( $_POST['per_batch'] ?? 100 );
		$per_batch = min( max( $per_batch, 1 ), 200 );

		try {
			$indexer = new \TLTSuite\TLTSearch\Indexing\BulkIndexer();
			$result  = $indexer->index_batch( $offset, $per_batch );
			wp_send_json_success( $result );
		} catch ( \Throwable $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	public function handle_rebuild_index() {

		if ( ! isset( $_POST['tlt_search_rebuild_index'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'tlt_search_rebuild_index' );

		try {
			$indexer = new \TLTSuite\TLTSearch\Indexing\BulkIndexer();
			$count   = $indexer->rebuild();
			add_settings_error(
				'tlt-search',
				'tlt-search-rebuild',
				sprintf( _n( '%d product indexed successfully.', '%d products indexed successfully.', $count, 'tlt-search' ), $count ),
				'updated'
			);
		} catch ( \Throwable $e ) {
			add_settings_error(
				'tlt-search',
				'tlt-search-rebuild',
				__( 'Index rebuild failed: ', 'tlt-search' ) . esc_html( $e->getMessage() ),
				'error'
			);
		}
	}

	public function display_admin_notices() {
		settings_errors( 'tlt-search' );
	}

	public function handle_search_request(): void {

		if ( ! isset( $_POST['tlt_search_submit'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'tlt_search_test_search' );
		$query = sanitize_text_field(
			wp_unslash( $_POST['tlt_search_query'] ?? '' )
		);

		if ( empty( $query ) ) {
			return;
		}

		$search_manager = new \TLTSuite\TLTSearch\Search\SearchManager();
		$GLOBALS['tlt_search_results'] = $search_manager->search( $query );
	}

}
