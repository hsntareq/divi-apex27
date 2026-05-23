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
				'default'         => '',
			),
			'type' => array(
				'label'           => esc_html__( 'Type', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::type_options(),
				'default'         => 'rent',
			),
			'property_type' => array(
				'label'           => esc_html__( 'Property Type', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::property_type_options(),
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
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::price_options(),
				'default'         => '',
			),
			'max_price' => array(
				'label'           => esc_html__( 'Max. Price', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::price_options(),
				'default'         => '',
			),
			'city' => array(
				'label'           => esc_html__( 'Location', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::city_options(),
				'default'         => '',
			),
			'min_beds' => array(
				'label'           => esc_html__( 'Min. Bedrooms', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::bedroom_options( 'min' ),
				'default'         => '',
			),
			'max_beds' => array(
				'label'           => esc_html__( 'Max. Bedrooms', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::bedroom_options( 'max' ),
				'default'         => '',
			),
			'sort' => array(
				'label'           => esc_html__( 'Sort', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::sort_options(),
				'default'         => 'highest_price',
			),
		);
	}

	/**
	 * Type options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function type_options() {
		return array(
			'sale'            => esc_html__( 'Sales', 'divi-apex27' ),
			'rent'            => esc_html__( 'Lettings', 'divi-apex27' ),
			'land'            => esc_html__( 'Land', 'divi-apex27' ),
			'commercial_sale' => esc_html__( 'Commercial Sales', 'divi-apex27' ),
			'commercial_rent' => esc_html__( 'Commercial Lettings', 'divi-apex27' ),
			'new_homes'       => esc_html__( 'New Homes', 'divi-apex27' ),
			'auctions'        => esc_html__( 'Auctions', 'divi-apex27' ),
		);
	}

	/**
	 * Property type options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function property_type_options() {
		return array(
			''                    => esc_html__( 'Property Type', 'divi-apex27' ),
			'bungalow'            => esc_html__( 'Bungalow', 'divi-apex27' ),
			'detached_house'      => esc_html__( 'Detached House', 'divi-apex27' ),
			'end_terrace'         => esc_html__( 'End Terraced House', 'divi-apex27' ),
			'flat'                => esc_html__( 'Flat', 'divi-apex27' ),
			'house'               => esc_html__( 'House', 'divi-apex27' ),
			'industrial'          => esc_html__( 'Industrial', 'divi-apex27' ),
			'link_detached'       => esc_html__( 'Link Detached House', 'divi-apex27' ),
			'maisonette'          => esc_html__( 'Maisonette', 'divi-apex27' ),
			'office'              => esc_html__( 'Office', 'divi-apex27' ),
			'restaurant'          => esc_html__( 'Restaurant', 'divi-apex27' ),
			'semi_detached_house' => esc_html__( 'Semi-detached House', 'divi-apex27' ),
			'studio'              => esc_html__( 'Studio', 'divi-apex27' ),
			'terraced'            => esc_html__( 'Terraced House', 'divi-apex27' ),
			'warehouse'           => esc_html__( 'Warehouse', 'divi-apex27' ),
		);
	}

	/**
	 * Combined rent and sale price values from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function price_options() {
		$values = array_unique(
			array_merge(
				array( '' ),
				range( 100, 1000, 50 ),
				range( 1250, 2000, 250 ),
				range( 3000, 5000, 500 ),
				range( 50000, 500000, 25000 ),
				range( 550000, 1000000, 50000 ),
				range( 1100000, 1500000, 100000 ),
				range( 1750000, 3000000, 250000 )
			)
		);

		$options = array( '' => esc_html__( 'Any price', 'divi-apex27' ) );
		foreach ( $values as $value ) {
			if ( '' === $value ) {
				continue;
			}
			$options[ (string) $value ] = number_format_i18n( $value );
		}
		return $options;
	}

	/**
	 * City options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function city_options() {
		return array(
			''                => esc_html__( 'Location', 'divi-apex27' ),
			'Barking'         => 'Barking',
			'Chigwell'        => 'Chigwell',
			'Dagenham'        => 'Dagenham',
			'Dartford'        => 'Dartford',
			'Grays'           => 'Grays',
			'Hornchurch'      => 'Hornchurch',
			'Hounslow'        => 'Hounslow',
			'Ilford'          => 'Ilford',
			'leyton'          => 'leyton',
			'London'          => 'London',
			'Rainham'         => 'Rainham',
			'Romford'         => 'Romford',
			'Shoreditch'      => 'Shoreditch',
			'Southend-On-Sea' => 'Southend-On-Sea',
			'Tilbury'         => 'Tilbury',
			'Woodford Green'  => 'Woodford Green',
		);
	}

	/**
	 * Bedroom options.
	 *
	 * @param string $mode min|max.
	 *
	 * @return array
	 */
	private static function bedroom_options( $mode ) {
		$options = array( '' => 'min' === $mode ? esc_html__( 'Min. Bedrooms', 'divi-apex27' ) : esc_html__( 'Max. Bedrooms', 'divi-apex27' ) );
		foreach ( range( 1, 10 ) as $value ) {
			$options[ (string) $value ] = 'min' === $mode
				? sprintf( esc_html__( 'At least %d beds', 'divi-apex27' ), $value )
				: sprintf( esc_html__( 'At most %d beds', 'divi-apex27' ), $value );
		}
		return $options;
	}

	/**
	 * Sort options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function sort_options() {
		return array(
			'featured'          => esc_html__( 'Featured first', 'divi-apex27' ),
			'highest_price'     => esc_html__( 'Highest price first', 'divi-apex27' ),
			'lowest_price'      => esc_html__( 'Lowest price first', 'divi-apex27' ),
			'newly_instructed'  => esc_html__( 'Newly instructed first', 'divi-apex27' ),
			'newest'            => esc_html__( 'Newest first', 'divi-apex27' ),
			'oldest'            => esc_html__( 'Oldest first', 'divi-apex27' ),
			'nearest'           => esc_html__( 'Nearest first', 'divi-apex27' ),
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
