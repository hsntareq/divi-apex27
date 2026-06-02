<?php
/**
 * Google Reviews renderer for Divi Apex27 modules.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Google Reviews based on builder settings.
 */
class Divi_Apex27_Google_Reviews_Renderer {

	const GOOGLE_REVIEWS_TRANSIENT_PREFIX = 'divi_apex27_google_reviews_';
	const GOOGLE_REVIEWS_CACHE_EXPIRY = HOUR_IN_SECONDS * 24; // 24 hours

	/**
	 * Default module values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'title'                => 'Google Reviews',
			'business_input_mode'  => 'url',
			'business_url'         => '',
			'business_name'        => '',
			'api_key'              => '',
			'row_count'            => '2',
			'column_count'         => '3',
			'max_reviews'          => '6',
			'review_sort'          => 'newest',
			'show_rating'          => 'on',
			'show_date'            => 'on',
			'show_author'          => 'on',
			'review_text_length'   => '150',
			'text_alignment'       => 'left',
			'empty_text'           => __( 'No reviews found. Please check your business URL or API key.', 'divi-apex27' ),
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
	 * Check if in builder preview mode.
	 *
	 * @return bool
	 */
	public static function is_builder_preview() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['action'] ) && 'divi_apex27_builder_google_reviews_preview' === $_REQUEST['action'];
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

		$wrapper_class = 'divi-apex27-google-reviews';
		$output        = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$output       .= self::render_heading( $props['title'] );

		if ( empty( $props['business_url'] ) && empty( $props['business_name'] ) ) {
			$output .= self::render_notice( __( 'Please configure a Google Business URL or Business Name.', 'divi-apex27' ) );
		} else {
			$reviews = self::fetch_reviews( $props );

			if ( is_wp_error( $reviews ) ) {
				$output .= self::render_notice( $reviews->get_error_message() );
			} else {
				$output .= self::render_reviews_grid( $reviews, $props );
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render heading.
	 *
	 * @param string $title Module title.
	 *
	 * @return string
	 */
	private static function render_heading( $title ) {
		if ( empty( $title ) ) {
			return '';
		}

		return sprintf(
			'<h3 class="divi-apex27-google-reviews-title">%s</h3>',
			esc_html( $title )
		);
	}

	/**
	 * Render error notice.
	 *
	 * @param string $message Notice message.
	 *
	 * @return string
	 */
	private static function render_notice( $message ) {
		return sprintf(
			'<div class="divi-apex27-notice divi-apex27-notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Fetch reviews from Google.
	 *
	 * @param array $props Module props.
	 *
	 * @return array|WP_Error
	 */
	public static function fetch_reviews( array $props ) {
		$cache_key = self::GOOGLE_REVIEWS_TRANSIENT_PREFIX . md5( $props['business_url'] . $props['business_name'] . $props['api_key'] );
		$cached    = get_transient( $cache_key );

		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$reviews = array();

		// Attempt to fetch from Google Places API if using API key method
		if ( 'api_key' === $props['business_input_mode'] && ! empty( $props['api_key'] ) ) {
			$reviews = self::fetch_via_places_api( $props );
		}
		// Attempt to scrape from Google Business Profile URL
		elseif ( ! empty( $props['business_url'] ) ) {
			$reviews = self::fetch_via_business_url( $props['business_url'] );
		}
		// Search by business name
		elseif ( ! empty( $props['business_name'] ) ) {
			$reviews = self::fetch_via_business_name( $props );
		}

		if ( is_wp_error( $reviews ) || empty( $reviews ) ) {
			return new WP_Error(
				'no_reviews',
				$props['empty_text']
			);
		}

		// Sort reviews
		$reviews = self::sort_reviews( $reviews, $props['review_sort'] );

		// Limit reviews to max_reviews
		$reviews = array_slice( $reviews, 0, (int) $props['max_reviews'] );

		// Cache the reviews
		set_transient( $cache_key, $reviews, self::GOOGLE_REVIEWS_CACHE_EXPIRY );

		return $reviews;
	}

	/**
	 * Fetch reviews via Google Places API.
	 *
	 * @param array $props Module props.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_via_places_api( array $props ) {
		// This is a placeholder for Google Places API integration
		// You would need to implement the actual API call here
		// For now, returning a structured error
		return new WP_Error(
			'api_not_configured',
			__( 'Google Places API integration needs to be configured with your API key.', 'divi-apex27' )
		);
	}

	/**
	 * Fetch reviews by scraping Google Business URL.
	 *
	 * @param string $business_url Google Business URL.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_via_business_url( $business_url ) {
		$business_id = self::extract_business_id_from_url( $business_url );

		if ( ! $business_id ) {
			return new WP_Error(
				'invalid_url',
				__( 'Could not extract business ID from the provided URL.', 'divi-apex27' )
			);
		}

		// This would require server-side scraping or integration with a Google API
		// For now, returning a placeholder
		return array();
	}

	/**
	 * Fetch reviews by searching business name.
	 *
	 * @param array $props Module props.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_via_business_name( array $props ) {
		// This would require integration with Google Places API or similar
		// For now, returning a placeholder
		return array();
	}

	/**
	 * Extract business ID from Google Business URL.
	 *
	 * @param string $url Business URL.
	 *
	 * @return string|false
	 */
	private static function extract_business_id_from_url( $url ) {
		// Google Business URLs typically follow patterns like:
		// https://www.google.com/maps/place/Business+Name/@latitude,longitude,zoom,data=xxx
		// https://goo.gl/maps/xxxxx
		// https://maps.google.com/?cid=xxxxx

		$parsed = wp_parse_url( $url );

		if ( ! $parsed ) {
			return false;
		}

		// Try to extract from cid parameter
		if ( ! empty( $parsed['query'] ) ) {
			$query_params = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
			if ( ! empty( $query_params['cid'] ) ) {
				return sanitize_text_field( $query_params['cid'] );
			}
		}

		// Try to extract from path
		if ( ! empty( $parsed['path'] ) ) {
			if ( preg_match( '/place\/[^@]+@[^,]+,[^,]+,.*?data=([^&]+)/', $url, $matches ) ) {
				return sanitize_text_field( $matches[1] );
			}
		}

		return false;
	}

	/**
	 * Sort reviews array based on sort option.
	 *
	 * @param array  $reviews Reviews array.
	 * @param string $sort_by Sort option.
	 *
	 * @return array
	 */
	private static function sort_reviews( array $reviews, $sort_by ) {
		usort( $reviews, function ( $a, $b ) use ( $sort_by ) {
			switch ( $sort_by ) {
				case 'oldest':
					return strtotime( $a['date'] ?? 0 ) - strtotime( $b['date'] ?? 0 );

				case 'highest_rating':
					return ( $b['rating'] ?? 0 ) - ( $a['rating'] ?? 0 );

				case 'lowest_rating':
					return ( $a['rating'] ?? 0 ) - ( $b['rating'] ?? 0 );

				case 'most_relevant':
					// Sort by rating first, then by review length (more detailed reviews first)
					if ( ( $b['rating'] ?? 0 ) !== ( $a['rating'] ?? 0 ) ) {
						return ( $b['rating'] ?? 0 ) - ( $a['rating'] ?? 0 );
					}
					return strlen( $b['text'] ?? '' ) - strlen( $a['text'] ?? '' );

				case 'newest':
				default:
					return strtotime( $b['date'] ?? 0 ) - strtotime( $a['date'] ?? 0 );
			}
		} );

		return $reviews;
	}

	/**
	 * Render reviews grid.
	 *
	 * @param array $reviews Reviews array.
	 * @param array $props   Module props.
	 *
	 * @return string
	 */
	private static function render_reviews_grid( array $reviews, array $props ) {
		if ( empty( $reviews ) ) {
			return sprintf(
				'<div class="divi-apex27-notice">%s</div>',
				esc_html( $props['empty_text'] )
			);
		}

		$columns      = (int) $props['column_count'];
		$text_align   = sanitize_text_field( $props['text_alignment'] );
		$grid_class   = sprintf( 'divi-apex27-reviews-grid divi-apex27-reviews-columns-%d divi-apex27-reviews-align-%s', $columns, $text_align );

		$output = sprintf( '<div class="%s">', esc_attr( $grid_class ) );

		foreach ( $reviews as $review ) {
			$output .= self::render_review_card( $review, $props );
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render single review card.
	 *
	 * @param array $review Review data.
	 * @param array $props  Module props.
	 *
	 * @return string
	 */
	private static function render_review_card( array $review, array $props ) {
		$output = '<div class="divi-apex27-review-card">';

		// Author and rating
		if ( ! empty( $props['show_author'] ) && 'on' === $props['show_author'] ) {
			$output .= sprintf(
				'<div class="divi-apex27-review-author">%s</div>',
				esc_html( $review['author'] ?? 'Anonymous' )
			);
		}

		// Star rating
		if ( ! empty( $props['show_rating'] ) && 'on' === $props['show_rating'] ) {
			$rating = (int) ( $review['rating'] ?? 0 );
			$output .= sprintf(
				'<div class="divi-apex27-review-rating">%s</div>',
				esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) )
			);
		}

		// Review date
		if ( ! empty( $props['show_date'] ) && 'on' === $props['show_date'] ) {
			$date = isset( $review['date'] ) ? wp_date( get_option( 'date_format' ), strtotime( $review['date'] ) ) : '';
			if ( $date ) {
				$output .= sprintf(
					'<div class="divi-apex27-review-date">%s</div>',
					esc_html( $date )
				);
			}
		}

		// Review text
		$review_text = $review['text'] ?? '';
		if ( ! empty( $review_text ) ) {
			$text_length = (int) $props['review_text_length'];
			if ( strlen( $review_text ) > $text_length ) {
				$review_text = substr( $review_text, 0, $text_length ) . '...';
			}

			$output .= sprintf(
				'<div class="divi-apex27-review-text"><p>%s</p></div>',
				wp_kses_post( $review_text )
			);
		}

		$output .= '</div>';

		return $output;
	}
}
