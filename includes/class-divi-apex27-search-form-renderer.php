<?php
/**
 * Search form renderer for Divi Apex27.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the standalone Apex27 property search form.
 */
class Divi_Apex27_Search_Form_Renderer {

	/**
	 * Default module values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'title'                => __( 'Property Search', 'divi-apex27' ),
			'default_type'         => 'rent',
			'default_listing_type' => 'listings',
			'show_listing_type'    => 'off',
			'show_type'            => 'on',
			'show_property_type'   => 'on',
			'show_city'            => 'on',
			'show_min_price'       => 'on',
			'show_max_price'       => 'on',
			'show_min_beds'        => 'on',
			'show_max_beds'        => 'on',
			'show_min_gross_yield' => 'on',
			'show_sort'            => 'on',
			'action_url'           => '',
			'submit_label'         => __( 'Update', 'divi-apex27' ),
		);
	}

	/**
	 * Convert Divi 5 attributes to flat props.
	 *
	 * @param array $attrs Module attributes.
	 *
	 * @return array
	 */
	public static function attrs_to_props( array $attrs ) {
		$settings = $attrs['apex27']['content'] ?? array();
		$props    = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			if ( isset( $settings[ $key ]['desktop']['value'] ) ) {
				$props[ $key ] = $settings[ $key ]['desktop']['value'];
			}
		}

		return $props;
	}

	/**
	 * Render search form.
	 *
	 * @param array $props Module props.
	 *
	 * @return string
	 */
	public static function render( array $props ) {
		$props = wp_parse_args( $props, self::defaults() );
		$api   = new Divi_Apex27_API();
		$wrapper_class = 'divi-apex27-search-form' . ( self::is_builder_preview() ? ' divi-apex27-builder-mode' : '' );

		if ( ! $api->is_configured() ) {
			return sprintf( '<div class="divi-apex27-notice">%s</div>', esc_html__( 'Configure the Website URL and API Key in Settings > Divi Apex27 before using this module.', 'divi-apex27' ) );
		}

		$defaults = array(
			'listing_type'  => sanitize_text_field( (string) $props['default_listing_type'] ),
			'type'          => sanitize_text_field( (string) $props['default_type'] ),
			'property_type' => '',
			'city'          => '',
			'min_price'     => '',
			'max_price'     => '',
			'min_beds'      => '',
			'max_beds'      => '',
			'min_gross_yield'=> '',
			'sort'          => 'highest_price',
		);

		$current = array();
		foreach ( $defaults as $key => $default_value ) {
			if ( isset( $_GET[ $key ] ) && ! is_array( $_GET[ $key ] ) ) {
				$current[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
			} else {
				$current[ $key ] = $default_value;
			}
		}

		$action_url = trim( (string) $props['action_url'] );
		if ( '' === $action_url ) {
			$action_url = home_url( '/property-search/' );
		}

		$property_type_options = self::property_type_options();
		$city_options          = self::city_options();
		$sort_options          = self::sort_options();
		$price_options         = self::price_options();
		$min_bed_options       = self::bedroom_options( 'min' );
		$max_bed_options       = self::bedroom_options( 'max' );
		$yield_options         = self::gross_yield_options();

		$output  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$output .= sprintf( '<h2 class="divi-apex27-search-title">%s</h2>', esc_html( (string) $props['title'] ) );
		$output .= sprintf( '<form method="get" action="%s" class="divi-apex27-search-grid">', esc_url( $action_url ) );

		if ( 'on' === (string) $props['show_listing_type'] ) {
			$output .= self::render_select_field(
				'listing_type',
				esc_html__( 'Listing Type', 'divi-apex27' ),
				array(
					'listings'   => esc_html__( 'Listings', 'divi-apex27' ),
					'valuations' => esc_html__( 'Valuations', 'divi-apex27' ),
				),
				$current['listing_type']
			);
		}

		if ( 'on' === (string) $props['show_type'] ) {
			$output .= self::render_select_field( 'type', esc_html__( 'Type', 'divi-apex27' ), self::type_options(), $current['type'] );
		}

		if ( 'on' === (string) $props['show_property_type'] ) {
			$output .= self::render_select_field( 'property_type', esc_html__( 'Property Type', 'divi-apex27' ), $property_type_options, $current['property_type'], esc_html__( 'Any property type', 'divi-apex27' ) );
		}

		if ( 'on' === (string) $props['show_city'] ) {
			$output .= self::render_select_field( 'city', esc_html__( 'Location', 'divi-apex27' ), $city_options, $current['city'], esc_html__( 'Any location', 'divi-apex27' ) );
		}

		if ( 'on' === (string) $props['show_min_price'] || 'on' === (string) $props['show_max_price'] ) {
			$price_set = ( 'rent' === $current['type'] || 'commercial_rent' === $current['type'] ) ? $price_options['rent'] : $price_options['sale'];

			if ( 'on' === (string) $props['show_min_price'] ) {
				$output .= self::render_select_field( 'min_price', esc_html__( 'Min Price', 'divi-apex27' ), $price_set, $current['min_price'], esc_html__( 'No minimum', 'divi-apex27' ) );
			}

			if ( 'on' === (string) $props['show_max_price'] ) {
				$output .= self::render_select_field( 'max_price', esc_html__( 'Max Price', 'divi-apex27' ), $price_set, $current['max_price'], esc_html__( 'No maximum', 'divi-apex27' ) );
			}
		}

		if ( 'on' === (string) $props['show_min_beds'] ) {
			$output .= self::render_select_field( 'min_beds', esc_html__( 'Min Bedrooms', 'divi-apex27' ), $min_bed_options, $current['min_beds'], esc_html__( 'Any', 'divi-apex27' ) );
		}

		if ( 'on' === (string) $props['show_max_beds'] ) {
			$output .= self::render_select_field( 'max_beds', esc_html__( 'Max Bedrooms', 'divi-apex27' ), $max_bed_options, $current['max_beds'], esc_html__( 'Any', 'divi-apex27' ) );
		}

		if ( 'on' === (string) $props['show_min_gross_yield'] && ! empty( $yield_options ) ) {
			$output .= self::render_select_field( 'min_gross_yield', esc_html__( 'Min Gross Yield', 'divi-apex27' ), $yield_options, $current['min_gross_yield'], esc_html__( 'Any yield', 'divi-apex27' ) );
		}

		if ( 'on' === (string) $props['show_sort'] ) {
			$output .= self::render_select_field( 'sort', esc_html__( 'Sort', 'divi-apex27' ), $sort_options, $current['sort'] );
		}

		$output .= sprintf(
			'<div class="divi-apex27-search-actions"><button type="submit" class="divi-apex27-search-submit">%s</button></div>',
			esc_html( (string) $props['submit_label'] )
		);

		$output .= '</form></div>';

		return $output;
	}

	/**
	 * Determine whether request is from Divi Visual Builder.
	 *
	 * @return bool
	 */
	private static function is_builder_preview() {
		if ( is_admin() ) {
			return true;
		}

		if ( isset( $_GET['et_fb'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['et_fb'] ) ) ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		return false;
	}

	/**
	 * Render one select field.
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param array  $options     Value => label options.
	 * @param string $selected    Selected value.
	 * @param string $placeholder Optional placeholder.
	 *
	 * @return string
	 */
	private static function render_select_field( $name, $label, array $options, $selected, $placeholder = '' ) {
		$output  = '<label class="divi-apex27-search-field">';
		$output .= sprintf( '<span>%s</span>', esc_html( $label ) );
		$output .= sprintf( '<select name="%s">', esc_attr( $name ) );

		if ( '' !== $placeholder ) {
			$output .= sprintf( '<option value="">%s</option>', esc_html( $placeholder ) );
		}

		foreach ( $options as $value => $display ) {
			$output .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( (string) $value ),
				selected( (string) $selected, (string) $value, false ),
				esc_html( (string) $display )
			);
		}

		$output .= '</select></label>';

		return $output;
	}

	/**
	 * Type options.
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
	 * Property type options.
	 *
	 * @return array
	 */
	private static function property_type_options() {
		return array(
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
	 * Build city options locally.
	 *
	 * @return array
	 */
	private static function city_options() {
		return array(
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
	 * Build gross yield options locally.
	 *
	 * @return array
	 */
	private static function gross_yield_options() {
		return array(
			'1'  => '1%',
			'2'  => '2%',
			'3'  => '3%',
			'4'  => '4%',
			'5'  => '5%',
			'6'  => '6%',
			'7'  => '7%',
			'8'  => '8%',
			'9'  => '9%',
			'10' => '10%',
		);
	}

	/**
	 * Sort options.
	 *
	 * @return array
	 */
	private static function sort_options() {
		return array(
			'featured'         => esc_html__( 'Featured first', 'divi-apex27' ),
			'highest_price'    => esc_html__( 'Highest price first', 'divi-apex27' ),
			'lowest_price'     => esc_html__( 'Lowest price first', 'divi-apex27' ),
			'newly_instructed' => esc_html__( 'Newly instructed first', 'divi-apex27' ),
			'newest'           => esc_html__( 'Newest first', 'divi-apex27' ),
			'oldest'           => esc_html__( 'Oldest first', 'divi-apex27' ),
			'nearest'          => esc_html__( 'Nearest first', 'divi-apex27' ),
		);
	}

	/**
	 * Price options by transaction type.
	 *
	 * @return array
	 */
	private static function price_options() {
		$sale_values = array_merge(
			range( 50000, 500000, 25000 ),
			range( 550000, 1000000, 50000 ),
			range( 1100000, 1500000, 100000 ),
			range( 1750000, 3000000, 250000 )
		);
		$rent_values = array_merge(
			range( 100, 1000, 50 ),
			range( 1250, 2000, 250 ),
			range( 3000, 5000, 500 )
		);

		$build = static function( array $values ) {
			$options = array();
			foreach ( $values as $value ) {
				$options[ (string) $value ] = html_entity_decode( '&pound;' ) . number_format_i18n( $value );
			}
			return $options;
		};

		return array(
			'sale' => $build( $sale_values ),
			'rent' => $build( $rent_values ),
		);
	}

	/**
	 * Bedroom options.
	 *
	 * @param string $mode Option mode.
	 *
	 * @return array
	 */
	private static function bedroom_options( $mode ) {
		$options = array();
		foreach ( range( 1, 10 ) as $value ) {
			$options[ (string) $value ] = 'min' === $mode
				? sprintf( esc_html__( 'At least %d beds', 'divi-apex27' ), $value )
				: sprintf( esc_html__( 'At most %d beds', 'divi-apex27' ), $value );
		}

		return $options;
	}
}
