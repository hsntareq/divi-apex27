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
			'type' => array(
				'label'           => esc_html__( 'Type', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::type_options(),
				'default'         => 'rent',
			),
			'listing_type' => array(
				'label'           => esc_html__( 'Listing Type', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => self::listing_type_options(),
				'default'         => 'listings',
			),
			'column_count' => array(
				'label'           => esc_html__( 'Columns', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'1' => esc_html__( '1', 'divi-apex27' ),
					'2' => esc_html__( '2', 'divi-apex27' ),
					'3' => esc_html__( '3', 'divi-apex27' ),
					'4' => esc_html__( '4', 'divi-apex27' ),
					'5' => esc_html__( '5', 'divi-apex27' ),
					'6' => esc_html__( '6', 'divi-apex27' ),
				),
				'default'         => '4',
			),
			'row_count' => array(
				'label'           => esc_html__( 'Rows', 'divi-apex27' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'1' => esc_html__( '1', 'divi-apex27' ),
					'2' => esc_html__( '2', 'divi-apex27' ),
					'3' => esc_html__( '3', 'divi-apex27' ),
					'4' => esc_html__( '4', 'divi-apex27' ),
					'5' => esc_html__( '5', 'divi-apex27' ),
					'6' => esc_html__( '6', 'divi-apex27' ),
				),
				'default'         => '2',
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
			'sale'            => esc_html__( 'Sales (Residential)', 'divi-apex27' ),
			'rent'            => esc_html__( 'Lettings (Residential)', 'divi-apex27' ),
			'land'            => esc_html__( 'Land', 'divi-apex27' ),
			'commercial_sale' => esc_html__( 'Sales (Commercial)', 'divi-apex27' ),
			'commercial_rent' => esc_html__( 'Lettings (Commercial)', 'divi-apex27' ),
		);
	}

	/**
	 * Listing endpoint options.
	 *
	 * @return array
	 */
	private static function listing_type_options() {
		return array(
			'listings'   => esc_html__( 'Listing', 'divi-apex27' ),
			'valuations' => esc_html__( 'Valuations', 'divi-apex27' ),
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
