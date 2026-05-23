<?php
/**
 * Apex27 API client for the Divi Apex27 module.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the existing Apex27 plugin settings and calls the same website API.
 */
class Divi_Apex27_API {

	const WEBSITE_URL_OPTION = 'apex27_website_url';
	const API_KEY_OPTION     = 'apex27_api_key';

	/**
	 * Whether the original Apex27 settings are available.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return (bool) $this->get_website_url() && (bool) $this->get_api_key();
	}

	/**
	 * Fetch listings from the Apex27 website API.
	 *
	 * @param array $query Module query values.
	 *
	 * @return object|\WP_Error
	 */
	public function get_listings( array $query ) {
		return $this->request( 'get-listings', $this->build_listing_payload( $query ) );
	}

	/**
	 * Fetch search options for dynamic field choices.
	 *
	 * @return object|\WP_Error
	 */
	public function get_search_options() {
		return $this->request( 'get-search-options', array() );
	}

	/**
	 * Fetch portal options.
	 *
	 * @return object|\WP_Error
	 */
	public function get_portal_options() {
		return $this->request( 'get-portal-options', array() );
	}

	/**
	 * Build the exact property-search query payload used by the original plugin.
	 *
	 * @param array $query Module query values.
	 *
	 * @return array
	 */
	private function build_listing_payload( array $query ) {
		return array(
			'search'           => 1,
			'type'             => $query['type'] ?? 'rent',
			'property_type'    => $query['property_type'] ?? '',
			'transaction_type' => $query['type'] ?? 'rent',
			'overseas'         => $query['overseas'] ?? '0',
			'min_price'        => $query['min_price'] ?? '',
			'max_price'        => $query['max_price'] ?? '',
			'city'             => $query['city'] ?? '',
			'min_beds'         => $query['min_beds'] ?? '',
			'max_beds'         => $query['max_beds'] ?? '',
			'sort'             => $query['sort'] ?? 'highest_price',
			'page'             => $query['page'] ?? '',
			'locale'           => get_locale(),
		);
	}

	/**
	 * Make a POST request matching Apex27::api_call().
	 *
	 * @param string $endpoint Endpoint name.
	 * @param array  $data     POST body fields.
	 *
	 * @return object|\WP_Error
	 */
	private function request( $endpoint, array $data ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'divi_apex27_not_configured', __( 'Apex27 Website URL and API Key are not configured.', 'divi-apex27' ) );
		}

		$data['api_key'] = $this->get_api_key();
		$url             = sprintf( '%s/api/%s', $this->get_website_url(), ltrim( $endpoint, '/' ) );
		$cache_key       = 'divi_apex27_' . md5( $url . '|' . wp_json_encode( $data ) );
		$cached          = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'body'    => $data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'divi_apex27_request_failed',
				sprintf(
					/* translators: %d: HTTP response code. */
					__( 'Apex27 request failed with HTTP status %d.', 'divi-apex27' ),
					$status_code
				)
			);
		}

		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'divi_apex27_invalid_json', __( 'Apex27 returned an invalid JSON response.', 'divi-apex27' ) );
		}

		set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Get website URL from the original Apex27 plugin option.
	 *
	 * @return string
	 */
	private function get_website_url() {
		return untrailingslashit( esc_url_raw( (string) get_option( self::WEBSITE_URL_OPTION, '' ) ) );
	}

	/**
	 * Get API key from the original Apex27 plugin option.
	 *
	 * @return string
	 */
	private function get_api_key() {
		return (string) get_option( self::API_KEY_OPTION, '' );
	}
}
