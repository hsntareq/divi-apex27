<?php
/**
 * Legacy Divi Builder module.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Divi 4 module wrapper.
 */
class Divi_Apex27_Module extends ET_Builder_Module {

	/**
	 * Advanced fields.
	 *
	 * @var array
	 */
	public $advanced_fields = array(
		'fonts'      => false,
		'background' => array(),
		'borders'    => array(),
		'box_shadow' => array(),
		'margin_padding' => array(),
	);

	/**
	 * Init module.
	 *
	 * @return void
	 */
	public function init() {
		$this->name             = esc_html__( 'Apex27 Property Filter', 'divi-apex27' );
		$this->slug             = 'divi_apex27_property_filter';
		$this->vb_support       = 'on';
		$this->main_css_element = '%%order_class%%.divi-apex27-property-filter';
	}

	/**
	 * Fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'title' => array(
				'label'           => esc_html__( 'Title', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Property Search', 'divi-apex27' ),
			),
			'type' => array(
				'label'           => esc_html__( 'Type', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'sale' => esc_html__( 'Sales', 'divi-apex27' ),
					'rent' => esc_html__( 'Lettings', 'divi-apex27' ),
				),
				'default'         => 'rent',
			),
			'property_type' => array(
				'label'           => esc_html__( 'Property Type', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'flat',
			),
			'overseas' => array(
				'label'           => esc_html__( 'Location Scope', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'0' => esc_html__( 'UK', 'divi-apex27' ),
					'1' => esc_html__( 'Overseas', 'divi-apex27' ),
				),
				'default'         => '0',
			),
			'min_price' => array(
				'label'           => esc_html__( 'Min. Price', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
			),
			'max_price' => array(
				'label'           => esc_html__( 'Max. Price', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
			),
			'city' => array(
				'label'           => esc_html__( 'Location', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
			),
			'min_beds' => array(
				'label'           => esc_html__( 'Min. Bedrooms', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
			),
			'max_beds' => array(
				'label'           => esc_html__( 'Max. Bedrooms', 'divi-apex27' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
			),
			'sort' => array(
				'label'           => esc_html__( 'Sort', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'highest_price' => esc_html__( 'Highest price first', 'divi-apex27' ),
					'lowest_price'  => esc_html__( 'Lowest price first', 'divi-apex27' ),
					'newest'        => esc_html__( 'Newest first', 'divi-apex27' ),
				),
				'default'         => 'highest_price',
			),
		);
	}

	/**
	 * Render module.
	 *
	 * @param array  $attrs       Attrs.
	 * @param string $content     Content.
	 * @param string $render_slug Render slug.
	 *
	 * @return string
	 */
	public function render( $attrs, $content = null, $render_slug = null ) {
		return Divi_Apex27_Renderer::render( $this->props );
	}
}
