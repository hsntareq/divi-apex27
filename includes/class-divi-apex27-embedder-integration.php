<?php
/**
 * Integration with Embedder for Google Reviews plugin.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integration with Embedder for Google Reviews plugin.
 */
class Divi_Apex27_Embedder_Integration {

	/**
	 * Initialize integration.
	 */
	public static function init() {
		// Enqueue builder JavaScript for search functionality
		add_action( 'divi_visual_builder_assets_before_enqueue_scripts', array( __CLASS__, 'enqueue_builder_assets' ) );

		// Search for businesses via Embedder API
		add_action( 'wp_ajax_divi_apex27_search_business', array( __CLASS__, 'search_business' ) );
		add_action( 'wp_ajax_nopriv_divi_apex27_search_business', array( __CLASS__, 'search_business' ) );

		// Get reviews from Embedder
		add_action( 'wp_ajax_divi_apex27_get_embedder_reviews', array( __CLASS__, 'get_embedder_reviews' ) );
		add_action( 'wp_ajax_nopriv_divi_apex27_get_embedder_reviews', array( __CLASS__, 'get_embedder_reviews' ) );
	}

	/**
	 * Enqueue builder assets for business search.
	 */
	public static function enqueue_builder_assets() {
		wp_localize_script( 'divi-apex27-builder', 'diviApex27EmbedderSettings', array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'searchNonce' => wp_create_nonce( 'divi_apex27_search_nonce' ),
		) );
	}

	/**
	 * Check if Embedder plugin is active.
	 *
	 * @return bool
	 */
	public static function is_embedder_active() {
		return function_exists( 'grwp_fs' ) && class_exists( 'GRWP_Free_API_Service' );
	}

	/**
	 * Search for businesses using Embedder API.
	 */
	public static function search_business() {
		// Log request for debugging
		error_log( 'Divi Apex27: Search business called' );
		error_log( 'POST data: ' . json_encode( $_POST ) );

		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'en';

		if ( empty( $search ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a search term' ) );
		}

		// Check if Embedder is active
		if ( ! self::is_embedder_active() ) {
			wp_send_json_error( array( 'message' => 'Embedder for Google Reviews plugin is not active' ) );
		}

		// Call Embedder API
		try {
			$results = self::call_embedder_search_api( $search, $language );

			if ( is_wp_error( $results ) ) {
				error_log( 'Embedder API error: ' . $results->get_error_message() );
				wp_send_json_error( array( 'message' => $results->get_error_message() ) );
			}

			error_log( 'Search results: ' . json_encode( $results ) );
			wp_send_json_success( $results );
		} catch ( Exception $e ) {
			error_log( 'Search business exception: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Call Embedder search API.
	 *
	 * @param string $search_value Search term.
	 * @param string $language Language code.
	 *
	 * @return array|WP_Error
	 */
	public static function call_embedder_search_api( $search_value, $language ) {
		// Get Embedder install ID
		if ( function_exists( 'grwp_fs' ) && grwp_fs()->get_site() ) {
			$install_id = grwp_fs()->get_site()->id;
		} else {
			$install_id = '';
		}

		$site        = urlencode( get_site_url() );
		$admin_email = urlencode( get_option( 'admin_email' ) );
		$is_premium  = function_exists( 'grwp_fs' ) && grwp_fs()->is__premium_only() ? 'true' : 'false';

		$license_request_url = sprintf(
			'https://easyreviewsapi.com/get-results.php?install_id=%s&search_value=%s&language=%s&site=%s&mail=%s&is_premium=%s',
			$install_id,
			urlencode( $search_value ),
			$language,
			$site,
			$admin_email,
			$is_premium
		);

		$response = wp_remote_get(
			$license_request_url,
			array( 'timeout' => 30 )
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf( __( 'API Error: %s', 'divi-apex27' ), $response->get_error_message() )
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'api_error',
				__( 'Invalid API response', 'divi-apex27' )
			);
		}

		if ( isset( $body['error'] ) ) {
			return new WP_Error(
				'api_error',
				isset( $body['reason'] ) ? $body['reason'] : __( 'Search failed', 'divi-apex27' )
			);
		}

		return $body;
	}

	/**
	 * Get reviews from Embedder plugin.
	 */
	public static function get_embedder_reviews() {
		$business_name = isset( $_POST['business_name'] ) ? sanitize_text_field( $_POST['business_name'] ) : '';

		if ( empty( $business_name ) ) {
			wp_send_json_error( array( 'message' => 'Business name is required' ) );
		}

		// Search for the business
		$results = self::search_embedder_business( $business_name );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}

		if ( empty( $results ) ) {
			wp_send_json_error( array( 'message' => 'Business not found. Make sure it\'s configured in the Embedder plugin.' ) );
		}

		wp_send_json_success( array(
			'business_name' => $business_name,
			'reviews'       => $results,
		) );
	}

	/**
	 * Search for a business in Embedder and fetch its reviews.
	 *
	 * @param string $business_name Business name to search for.
	 *
	 * @return array|WP_Error Array of reviews or WP_Error.
	 */
	public static function search_embedder_business( $business_name ) {
		error_log( '=== Divi Apex27 Embedder: search_embedder_business called ===' );
		error_log( 'Business name: ' . $business_name );

		// Check if Embedder is active
		$is_active = self::is_embedder_active();
		error_log( 'is_embedder_active(): ' . ($is_active ? 'true' : 'false') );

		if ( ! $is_active ) {
			error_log( 'Embedder plugin is not active' );
			return new WP_Error( 'not_active', 'Embedder plugin is not active' );
		}

		// Step 1: Search for the business
		error_log( 'Calling call_embedder_search_api...' );
		$search_results = self::call_embedder_search_api( $business_name, 'en' );

		if ( is_wp_error( $search_results ) ) {
			error_log( 'Search API error: ' . $search_results->get_error_message() );
			return $search_results;
		}

		error_log( 'Search results: ' . json_encode( $search_results ) );

		if ( empty( $search_results ) || ! is_array( $search_results ) ) {
			error_log( 'No search results or not an array' );
			return new WP_Error( 'no_results', 'No businesses found matching: ' . $business_name );
		}

		// Step 2: Extract data_id from first result
		$first_result = is_array( $search_results ) ? reset( $search_results ) : null;
		error_log( 'First result: ' . json_encode( $first_result ) );

		if ( ! $first_result ) {
			error_log( 'No first result found' );
			return new WP_Error( 'no_results', 'No businesses found matching: ' . $business_name );
		}

		// Get data_id - different APIs may use different field names
		$data_id = $first_result['data_id'] ?? $first_result['id'] ?? $first_result['serp_data_id'] ?? null;
		error_log( 'Extracted data_id: ' . ($data_id ?: 'null') );

		if ( ! $data_id ) {
			error_log( 'Embedder search result (full): ' . json_encode( $first_result ) );
			return new WP_Error( 'no_data_id', 'Could not find data_id in search results' );
		}

		// Step 3: Fetch reviews for this business
		error_log( 'Fetching reviews for data_id: ' . $data_id );
		$reviews = self::fetch_reviews_from_embedder( $data_id );

		error_log( 'Reviews fetched: ' . json_encode( $reviews ) );
		error_log( '=== End search_embedder_business ===' );

		return $reviews;
	}

	/**
	 * Fetch reviews from Embedder using their data_id.
	 *
	 * @param string $data_id Business data ID from Embedder.
	 *
	 * @return array|WP_Error
	 */
	public static function fetch_reviews_from_embedder( $data_id ) {
		error_log( 'fetch_reviews_from_embedder called with data_id: ' . $data_id );

		// Get Embedder install ID
		if ( function_exists( 'grwp_fs' ) && grwp_fs()->get_site() ) {
			$install_id = grwp_fs()->get_site()->id;
		} else {
			$install_id = '';
		}

		error_log( 'Embedder install_id: ' . ($install_id ?: 'empty') );

		// Try to get reviews from Embedder's cached option first
		$embedder_options = get_option( 'google_reviews_option_name' );
		$stored_data_id = isset( $embedder_options['serp_data_id'] ) ? $embedder_options['serp_data_id'] : '';
		$language = isset( $embedder_options['reviews_language_3'] ) ? $embedder_options['reviews_language_3'] : 'en';

		error_log( 'Language: ' . $language );

		// Fetch fresh reviews from Embedder's API
		$site = urlencode( get_site_url() );
		$admin_email = urlencode( get_option( 'admin_email' ) );

		$license_request_url = sprintf(
			'https://easyreviewsapi.com/get-reviews-data.php?install_id=%s&data_id=%s&language=%s&site=%s&mail=%s',
			$install_id,
			$data_id,
			$language,
			$site,
			$admin_email
		);

		error_log( 'API URL: ' . $license_request_url );

		$response = wp_remote_get(
			$license_request_url,
			array( 'timeout' => 30 )
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'API call failed: ' . $response->get_error_message() );
			return new WP_Error(
				'fetch_failed',
				sprintf( __( 'Could not fetch reviews: %s', 'divi-apex27' ), $response->get_error_message() )
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		error_log( 'API response body: ' . substr( $response_body, 0, 500 ) );

		$body = json_decode( $response_body, true );

		if ( ! is_array( $body ) ) {
			error_log( 'Response is not an array. Type: ' . gettype( $body ) );
			return new WP_Error(
				'invalid_response',
				__( 'Invalid API response', 'divi-apex27' )
			);
		}

		if ( isset( $body['error'] ) ) {
			error_log( 'API error: ' . ($body['reason'] ?? 'Unknown error') );
			return new WP_Error(
				'api_error',
				isset( $body['reason'] ) ? $body['reason'] : __( 'Failed to fetch reviews', 'divi-apex27' )
			);
		}

		// Parse the reviews from the response
		error_log( 'Parsing reviews...' );
		$reviews = self::parse_embedder_reviews( $body );

		error_log( 'Parsed reviews count: ' . count( $reviews ) );

		return $reviews;
	}

	/**
	 * Parse reviews from Embedder API response.
	 *
	 * @param array $response API response data.
	 *
	 * @return array
	 */
	public static function parse_embedder_reviews( $response ) {
		$reviews = array();

		// If response itself is the reviews array, handle it
		if ( is_array( $response ) && isset( $response[0] ) && is_array( $response[0] ) ) {
			// Check if first item looks like a review
			if ( isset( $response[0]['reviewer'] ) || isset( $response[0]['author'] ) || isset( $response[0]['rating'] ) ) {
				foreach ( $response as $item ) {
					$review = self::parse_single_embedder_review( $item );
					if ( $review ) {
						$reviews[] = $review;
					}
				}
				return $reviews;
			}
		}

		// Embedder returns reviews in various formats, check for different keys
		$review_keys = array( 'reviews', 'data', 'results', 'items' );

		foreach ( $review_keys as $key ) {
			if ( isset( $response[ $key ] ) && is_array( $response[ $key ] ) ) {
				foreach ( $response[ $key ] as $item ) {
					$review = self::parse_single_embedder_review( $item );
					if ( $review ) {
						$reviews[] = $review;
					}
				}
				return $reviews;
			}
		}

		// If we have no reviews, log the response for debugging
		if ( empty( $reviews ) ) {
			error_log( 'No reviews found in Embedder response: ' . json_encode( $response ) );
		}

		return $reviews;
	}

	/**
	 * Parse a single review from Embedder response.
	 *
	 * @param array|object $item Review item.
	 *
	 * @return array|false
	 */
	public static function parse_single_embedder_review( $item ) {
		if ( is_object( $item ) ) {
			$item = (array) $item;
		}

		if ( ! is_array( $item ) ) {
			return false;
		}

		$author = $item['reviewer'] ?? $item['author'] ?? $item['name'] ?? 'Anonymous';
		$rating = (int) ( $item['rating'] ?? $item['rate'] ?? 5 );
		$text = $item['review'] ?? $item['text'] ?? $item['content'] ?? '';
		$date = $item['review_datetime_utc'] ?? $item['published_at'] ?? $item['date'] ?? date( 'Y-m-d' );

		if ( empty( $text ) ) {
			return false;
		}

		// Handle date formatting
		if ( is_numeric( $date ) ) {
			$date = date( 'Y-m-d', $date );
		} elseif ( strpos( $date, ' ' ) !== false ) {
			// Extract just the date part if it includes time
			$date_parts = explode( ' ', $date );
			$date = $date_parts[0];
		}

		return array(
			'author' => sanitize_text_field( $author ),
			'rating' => min( 5, max( 0, $rating ) ),
			'text'   => sanitize_textarea_field( $text ),
			'date'   => sanitize_text_field( $date ),
		);
	}
}
