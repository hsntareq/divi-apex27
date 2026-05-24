<?php
/**
 * Shared renderer for Divi Apex27 modules.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders remote listing results filtered by builder settings.
 */
class Divi_Apex27_Renderer {

	/**
	 * Default module values matching the requested query.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'title'         => '',
			'type'          => 'rent',
			'property_type' => '',
			'overseas'      => '0',
			'min_price'     => '',
			'max_price'     => '',
			'city'          => '',
			'min_beds'      => '',
			'max_beds'      => '',
			'min_gross_yield' => '',
			'include_sstc'    => '',
			'sort'          => 'highest_price',
			'empty_text'    => __( 'No properties found.', 'divi-apex27' ),
		);
	}

	/**
	 * Convert Divi 5 attributes into flat props.
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
	 * Render module.
	 *
	 * @param array $props Module props.
	 *
	 * @return string
	 */
	public static function render( array $props ) {
		$props = wp_parse_args( $props, self::defaults() );
		$query = self::current_query( $props );
		$api   = new Divi_Apex27_API();
		$is_builder_preview = self::is_builder_preview();
		$wrapper_class      = 'divi-apex27-property-filter' . ( $is_builder_preview ? ' divi-apex27-builder-mode' : '' );

		$output  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$output .= self::render_heading( $props['title'] );

		if ( ! $api->is_configured() ) {
			$output .= self::render_notice( __( 'Configure the Website URL and API Key in Settings > Apex27 before using this module.', 'divi-apex27' ) );
		} else {
			$result = $api->get_listings( $query );
			$output .= is_wp_error( $result ) ? self::render_notice( $result->get_error_message() ) : self::render_results( $result, $props['empty_text'], $is_builder_preview );
		}

		return $output . '</div>';
	}

	/**
	 * Build query values from builder settings only.
	 *
	 * @param array $props Module props.
	 *
	 * @return array
	 */
	private static function current_query( array $props ) {
		$aliases = array(
			'type'          => array( 'apex27_type', 'transaction_type' ),
			'property_type' => array( 'apex27_property_type', 'propertyType' ),
			'overseas'      => array( 'apex27_overseas' ),
			'min_price'     => array( 'apex27_min_price', 'minPrice' ),
			'max_price'     => array( 'apex27_max_price', 'maxPrice' ),
			'city'          => array( 'apex27_city', 'apex27_query', 'query' ),
			'min_beds'      => array( 'apex27_min_beds', 'apex27_bedrooms', 'minBeds' ),
			'max_beds'      => array( 'apex27_max_beds', 'maxBeds' ),
			'min_gross_yield' => array( 'apex27_min_gross_yield' ),
			'include_sstc'    => array( 'apex27_include_sstc' ),
			'sort'          => array( 'apex27_sort' ),
			'page'          => array( 'apex27_page' ),
		);
		$query = array();

		foreach ( $aliases as $canonical => $keys ) {
			$value = isset( $props[ $canonical ] ) ? sanitize_text_field( (string) $props[ $canonical ] ) : '';

			if ( isset( $_GET[ $canonical ] ) && ! is_array( $_GET[ $canonical ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_GET[ $canonical ] ) );
			}

			foreach ( $keys as $key ) {
				if ( isset( $_GET[ $key ] ) && ! is_array( $_GET[ $key ] ) ) {
					$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
					break;
				}
			}

			$query[ $canonical ] = $value;
		}

		return $query;
	}

	/**
	 * Render heading.
	 *
	 * @param string $title Heading text.
	 *
	 * @return string
	 */
	private static function render_heading( $title ) {
		if ( '' === trim( (string) $title ) ) {
			return '';
		}

		return sprintf( '<h2 class="divi-apex27-title">%s</h2>', esc_html( $title ) );
	}

	/**
	 * Determine whether the current render is happening in the builder.
	 *
	 * @return bool
	 */
	private static function is_builder_preview() {
		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}

		if ( isset( $_GET['et_fb'] ) || isset( $_POST['et_fb'] ) || isset( $_REQUEST['et_fb'] ) ) {
			return true;
		}

		if ( defined( 'ET_FB_ENABLED' ) && ET_FB_ENABLED ) {
			return true;
		}

		return is_admin() && ( wp_doing_ajax() || wp_is_json_request() );
	}

	/**
	 * Type options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function type_options() {
		return array(
			'sale'            => __( 'Sales', 'divi-apex27' ),
			'rent'            => __( 'Lettings', 'divi-apex27' ),
			'land'            => __( 'Land', 'divi-apex27' ),
			'commercial_sale' => __( 'Commercial Sales', 'divi-apex27' ),
			'commercial_rent' => __( 'Commercial Lettings', 'divi-apex27' ),
			'new_homes'       => __( 'New Homes', 'divi-apex27' ),
			'auctions'        => __( 'Auctions', 'divi-apex27' ),
		);
	}

	/**
	 * Property type options from the Apex27 search page.
	 *
	 * @return array
	 */
	private static function property_type_options() {
		return array(
			''                    => __( 'Property Type', 'divi-apex27' ),
			'bungalow'            => __( 'Bungalow', 'divi-apex27' ),
			'detached_house'      => __( 'Detached House', 'divi-apex27' ),
			'end_terrace'         => __( 'End Terraced House', 'divi-apex27' ),
			'flat'                => __( 'Flat', 'divi-apex27' ),
			'house'               => __( 'House', 'divi-apex27' ),
			'industrial'          => __( 'Industrial', 'divi-apex27' ),
			'link_detached'       => __( 'Link Detached House', 'divi-apex27' ),
			'maisonette'          => __( 'Maisonette', 'divi-apex27' ),
			'office'              => __( 'Office', 'divi-apex27' ),
			'restaurant'          => __( 'Restaurant', 'divi-apex27' ),
			'semi_detached_house' => __( 'Semi-detached House', 'divi-apex27' ),
			'studio'              => __( 'Studio', 'divi-apex27' ),
			'terraced'            => __( 'Terraced House', 'divi-apex27' ),
			'warehouse'           => __( 'Warehouse', 'divi-apex27' ),
		);
	}

	/**
	 * Overseas options.
	 *
	 * @return array
	 */
	private static function overseas_options() {
		return array(
			'0' => __( 'UK', 'divi-apex27' ),
			'1' => __( 'Overseas', 'divi-apex27' ),
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

		$options = array( '' => __( 'Any price', 'divi-apex27' ) );
		foreach ( $values as $price ) {
			if ( '' === $price ) {
				continue;
			}
			$options[ (string) $price ] = sprintf( '£%s', number_format_i18n( $price ) );
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
			''                => __( 'Location', 'divi-apex27' ),
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
		$options = array( '' => 'min' === $mode ? __( 'Min. Bedrooms', 'divi-apex27' ) : __( 'Max. Bedrooms', 'divi-apex27' ) );

		foreach ( range( 1, 10 ) as $bedrooms ) {
			$options[ (string) $bedrooms ] = 'min' === $mode
				? sprintf( __( 'At least %d beds', 'divi-apex27' ), $bedrooms )
				: sprintf( __( 'At most %d beds', 'divi-apex27' ), $bedrooms );
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
			'featured'         => __( 'Featured first', 'divi-apex27' ),
			'highest_price'    => __( 'Highest price first', 'divi-apex27' ),
			'lowest_price'     => __( 'Lowest price first', 'divi-apex27' ),
			'newly_instructed' => __( 'Newly instructed first', 'divi-apex27' ),
			'newest'           => __( 'Newest first', 'divi-apex27' ),
			'oldest'           => __( 'Oldest first', 'divi-apex27' ),
			'nearest'          => __( 'Nearest first', 'divi-apex27' ),
		);
	}

	/**
	 * Render listing results.
	 *
	 * @param object $result             Apex27 result object.
	 * @param string $empty_text         Empty message.
	 * @param bool   $show_builder_debug Whether to show response diagnostics.
	 *
	 * @return string
	 */
	private static function render_results( $result, $empty_text, $show_builder_debug = false ) {
		$items = self::extract_items( $result );

		if ( empty( $items ) ) {
			$notice = self::render_notice( $empty_text );
			if ( $show_builder_debug ) {
				$notice .= self::render_builder_debug( $result );
			}
			return $notice;
		}

		$output = '<div class="divi-apex27-results">';

		foreach ( $items as $item ) {
			$output .= self::render_card( is_object( $item ) ? $item : (object) $item );
		}

		return $output . '</div>';
	}

	/**
	 * Extract listings from known Apex27 response shapes.
	 *
	 * @param mixed $result API result.
	 *
	 * @return array
	 */
	private static function extract_items( $result ) {
		if ( is_array( $result ) ) {
			if ( isset( $result[0] ) ) {
				return $result;
			}

			foreach ( array( 'listings', 'properties', 'results', 'items', 'data' ) as $key ) {
				if ( isset( $result[ $key ] ) && is_array( $result[ $key ] ) ) {
					return $result[ $key ];
				}
			}
		}

		if ( is_object( $result ) ) {
			foreach ( array( 'listings', 'properties', 'results', 'items', 'data' ) as $key ) {
				if ( isset( $result->{$key} ) && is_array( $result->{$key} ) ) {
					return $result->{$key};
				}

				if ( isset( $result->{$key} ) && is_object( $result->{$key} ) ) {
					$nested_items = self::extract_items( $result->{$key} );
					if ( ! empty( $nested_items ) ) {
						return $nested_items;
					}
				}
			}
		}

		return array();
	}

	/**
	 * Render builder-only diagnostic output when the API returns no cards.
	 *
	 * @param mixed $result API result.
	 *
	 * @return string
	 */
	private static function render_builder_debug( $result ) {
		$keys = array();

		if ( is_object( $result ) ) {
			$keys = array_keys( get_object_vars( $result ) );
		} elseif ( is_array( $result ) ) {
			$keys = array_keys( $result );
		}

		$message = $keys
			? sprintf(
				/* translators: %s: response keys. */
				__( 'Builder debug: Apex27 responded, but no listings were found in these response keys: %s.', 'divi-apex27' ),
				implode( ', ', array_map( 'sanitize_text_field', $keys ) )
			)
			: __( 'Builder debug: Apex27 returned an empty response.', 'divi-apex27' );

		return sprintf( '<div class="divi-apex27-debug">%s</div>', esc_html( $message ) );
	}

	/**
	 * Render one property card.
	 *
	 * @param object $property Property object.
	 *
	 * @return string
	 */
	private static function render_card( $property ) {
		$title   = self::first_property_value( $property, array( 'displayAddress', 'address', 'title', 'header' ), __( 'Apex27 Property', 'divi-apex27' ) );
		$price   = self::first_property_value( $property, array( 'displayPrice', 'price', 'pricePrefix' ), '' );
		$summary = self::first_property_value( $property, array( 'summary', 'subtitle', 'description' ), '' );
		$image   = self::first_property_value( $property, array( 'thumbnailUrl', 'previewURL', 'imageUrl' ), '' );
		$url     = self::property_url( $property );

		if ( empty( $image ) && ! empty( $property->images ) && is_array( $property->images ) && ! empty( $property->images[0]->url ) ) {
			$image = $property->images[0]->url;
		}

		$output = '<article class="divi-apex27-card">';

		if ( $image ) {
			$output .= sprintf(
				'<a class="divi-apex27-card-media" href="%s"><img src="%s" alt="%s" loading="lazy" /></a>',
				esc_url( $url ),
				esc_url( $image ),
				esc_attr( wp_strip_all_tags( $title ) )
			);
		}

		$output .= '<div class="divi-apex27-card-body">';
		$output .= sprintf( '<h3>%s</h3>', esc_html( $title ) );

		if ( $price ) {
			$output .= sprintf( '<p class="divi-apex27-card-price">%s</p>', esc_html( $price ) );
		}

		if ( ! empty( $property->bedrooms ) || ! empty( $property->bathrooms ) || ! empty( $property->livingRooms ) ) {
			$output .= '<p class="divi-apex27-card-meta">';
			$output .= ! empty( $property->bedrooms ) ? sprintf( '<span>%s beds</span>', esc_html( $property->bedrooms ) ) : '';
			$output .= ! empty( $property->bathrooms ) ? sprintf( '<span>%s baths</span>', esc_html( $property->bathrooms ) ) : '';
			$output .= ! empty( $property->livingRooms ) ? sprintf( '<span>%s receptions</span>', esc_html( $property->livingRooms ) ) : '';
			$output .= '</p>';
		}

		if ( $summary ) {
			$output .= sprintf( '<p class="divi-apex27-card-summary">%s</p>', esc_html( wp_trim_words( wp_strip_all_tags( $summary ), 24 ) ) );
		}

		$output .= sprintf( '<a class="divi-apex27-card-link" href="%s">%s</a>', esc_url( $url ), esc_html__( 'View details', 'divi-apex27' ) );
		$output .= '</div></article>';

		return $output;
	}

	/**
	 * Get first usable property value.
	 *
	 * @param object $property Property object.
	 * @param array  $keys     Candidate keys.
	 * @param string $fallback Fallback.
	 *
	 * @return string
	 */
	private static function first_property_value( $property, array $keys, $fallback ) {
		foreach ( $keys as $key ) {
			if ( isset( $property->{$key} ) && is_scalar( $property->{$key} ) && '' !== (string) $property->{$key} ) {
				return (string) $property->{$key};
			}
		}

		return $fallback;
	}

	/**
	 * Build details URL matching the original Apex27 template.
	 *
	 * @param object $property Property object.
	 *
	 * @return string
	 */
	private static function property_url( $property ) {
		if ( ! empty( $property->url ) ) {
			return (string) $property->url;
		}

		if ( empty( $property->id ) || empty( $property->transactionTypeRoute ) ) {
			return '#';
		}

		$address = ! empty( $property->displayAddress ) ? (string) $property->displayAddress : 'no address';
		$address = function_exists( 'mb_strtolower' ) ? mb_strtolower( $address ) : strtolower( $address );
		$slug    = preg_replace( '/[-]+/', '-', preg_replace( '/\W/u', '-', $address ) );

		return home_url( sprintf( '/property-details/%s/%s/%d', $property->transactionTypeRoute, $slug, absint( $property->id ) ) );
	}

	/**
	 * Render notice.
	 *
	 * @param string $message Message.
	 *
	 * @return string
	 */
	private static function render_notice( $message ) {
		return sprintf( '<div class="divi-apex27-notice">%s</div>', esc_html( $message ) );
	}
}
