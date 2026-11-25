<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://trileotech.com/
 * @since      1.0.0
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/public
 * @author     TLT Suite <info@trileotech.com>
 */
class Tlt_Search_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tlt-search-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		// Component registers its alpine:init listener first, then Alpine loads and fires the event.
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tlt-search-public.js', [], $this->version, true );
		wp_enqueue_script( 'tlt-search-alpine', plugin_dir_url( __FILE__ ) . '../assets/js/vendor/alpine.min.js', [ $this->plugin_name ], $this->version, true );

		wp_localize_script(
			$this->plugin_name,
			'tltSearchConfig',
			[
				'apiUrl' => rest_url( 'tlt-search/v1/search' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'popularUrl'     => rest_url( 'tlt-search/v1/popular' ),
				'clickUrl'       => rest_url( 'tlt-search/v1/click' ),
				'cartUrl'        => class_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( 'add_to_cart' ) : '',
				'addedText'      => __( 'Added', 'tlt-search' ),
				'perPage'        => \TLTSuite\TLTSearch\Search\SearchOptions::results_per_page(),
				'showRating'     => \TLTSuite\TLTSearch\Search\SearchOptions::show_rating(),
				'showAddToCart'  => \TLTSuite\TLTSearch\Search\SearchOptions::show_add_to_cart(),
				'minLength' => \TLTSuite\TLTSearch\Search\SearchOptions::min_query_length(),
				'lang'   => \TLTSuite\TLTSearch\Support\Multilingual::current(),
			]
		);
	}

	public function register_shortcodes(): void {
		add_shortcode( 'tlt_search', [ $this, 'render_search_shortcode' ] );
	}

	/** elementor/widgets/register (Elementor 3.5+). The widget class extends an Elementor class, so load it only now. */
	public function register_elementor_widget( $widgets_manager ): void {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'elementor/class-tlt-search-elementor-widget.php';

		$widgets_manager->register( new \Tlt_Search_Elementor_Widget() );
	}

	public function register_block(): void {

		register_block_type(
			plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/search',
			[ 'render_callback' => [ $this, 'render_search_block' ] ]
		);
	}

	public function render_search_block( array $attributes ): string {

		// Return shortcode text, not markup: WordPress runs wptexturize over block output
		// and it corrupts attributes containing ">" or quotes. The shortcode is expanded
		// afterwards, on clean markup.
		$show = ! isset( $attributes['showFilters'] ) || $attributes['showFilters'] ? 'true' : 'false';
		$code = '[tlt_search show_filters="' . $show . '"';

		foreach ( [ 'placeholder' => 'placeholder', 'category' => 'category', 'layout' => 'layout' ] as $key => $att ) {
			if ( ! empty( $attributes[ $key ] ) ) {
				$code .= ' ' . $att . '="' . str_replace( [ '"', '[', ']' ], '', wp_strip_all_tags( $attributes[ $key ] ) ) . '"';
			}
		}
		if ( ! empty( $attributes['perPage'] ) ) {
			$code .= ' per_page="' . absint( $attributes['perPage'] ) . '"';
		}

		return $code . ']';
	}

	public function render_search_shortcode( $atts ): string {

		$atts = shortcode_atts(
			[
				'placeholder'  => __( 'Search products…', 'tlt-search' ),
				'show_filters' => 'true',
				'category'     => '', // exact category names, comma separated
				'per_page'     => '', // overrides the global setting
				'layout'       => '', // list | grid, overrides the global setting
			],
			$atts,
			'tlt_search'
		);

		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/tlt-search-public-display.php';
		return ob_get_clean();
	}

}
