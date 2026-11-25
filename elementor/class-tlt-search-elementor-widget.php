<?php
defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Elementor widget: the same live search as the [tlt_search] shortcode and the block.
 * Loaded only when Elementor is active (see Tlt_Search_Public::register_elementor_widget).
 */
class Tlt_Search_Elementor_Widget extends Widget_Base {

	public function get_name(): string {
		return 'tlt-search';
	}

	public function get_title(): string {
		return esc_html__( 'TLT Product Search', 'tlt-search' );
	}

	public function get_icon(): string {
		return 'eicon-search';
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_keywords(): array {
		return [ 'search', 'product', 'woocommerce' ];
	}

	protected function register_controls(): void {

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Search', 'tlt-search' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'placeholder',
			[
				'label'       => esc_html__( 'Placeholder text', 'tlt-search' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Search products…', 'tlt-search' ),
			]
		);

		$this->add_control(
			'show_filters',
			[
				'label'        => esc_html__( 'Show filters', 'tlt-search' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'tlt-search' ),
				'label_off'    => esc_html__( 'No', 'tlt-search' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {

		$settings = $this->get_settings_for_display();

		$atts = [ 'show_filters' => 'true' === ( $settings['show_filters'] ?? 'true' ) ? 'true' : 'false' ];

		if ( ! empty( $settings['placeholder'] ) ) {
			$atts['placeholder'] = $settings['placeholder'];
		}

		// Same output as the shortcode, called directly so nothing rewrites the markup.
		$public = new Tlt_Search_Public( 'tlt-search', TLT_SEARCH_VERSION );
		echo $public->render_search_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes its own output.
	}
}
