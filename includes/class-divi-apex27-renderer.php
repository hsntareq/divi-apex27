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
			'title'         => __( 'Property Search', 'divi-apex27' ),
			'type'          => 'rent',
			'property_type' => 'flat',
			'overseas'      => '0',
			'min_price'     => '',
			'max_price'     => '',
			'city'          => '',
			'min_beds'      => '',
			'max_beds'      => '',
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

		$output  = '<div class="divi-apex27-property-filter">';
		$output .= self::render_heading( $props['title'] );

		if ( ! $api->is_configured() ) {
			$output .= self::render_notice( __( 'Configure the Website URL and API Key in Settings > Apex27 before using this module.', 'divi-apex27' ) );
		} else {
			$result = $api->get_listings( $query );
			$output .= is_wp_error( $result ) ? self::render_notice( $result->get_error_message() ) : self::render_results( $result, $props['empty_text'] );
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
		$fields = array( 'type', 'property_type', 'overseas', 'min_price', 'max_price', 'city', 'min_beds', 'max_beds', 'sort' );
		$query  = array();

		foreach ( $fields as $field ) {
			$query[ $field ] = isset( $props[ $field ] ) ? sanitize_text_field( (string) $props[ $field ] ) : '';
		}

		$query['page'] = isset( $props['page'] ) ? sanitize_text_field( (string) $props['page'] ) : '';

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
	 * Render listing results.
	 *
	 * @param object $result     Apex27 result object.
	 * @param string $empty_text Empty message.
	 *
	 * @return string
	 */
	private static function render_results( $result, $empty_text ) {
		$items = array();

		if ( is_array( $result ) && isset( $result[0] ) ) {
			$items = $result;
		} elseif ( is_object( $result ) ) {
			foreach ( array( 'listings', 'properties', 'results', 'items', 'data' ) as $key ) {
				if ( isset( $result->{$key} ) && is_array( $result->{$key} ) ) {
					$items = $result->{$key};
					break;
				}
			}
		}

		if ( empty( $items ) ) {
			return self::render_notice( $empty_text );
		}

		$output = '<div class="divi-apex27-results">';

		foreach ( $items as $item ) {
			$output .= self::render_card( is_object( $item ) ? $item : (object) $item );
		}

		return $output . '</div>';
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
