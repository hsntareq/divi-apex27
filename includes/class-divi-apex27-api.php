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

	const DIVI_WEBSITE_URL_OPTION = 'divi_apex27_website_url';
	const DIVI_API_KEY_OPTION     = 'divi_apex27_api_key';
	const DIVI_API_TOKEN_OPTION   = 'divi_apex27_api_token';
	const WEBSITE_URL_OPTION = 'apex27_website_url';
	const API_KEY_OPTION     = 'apex27_api_key';
	const API_TOKEN_OPTION   = 'apex27_api_token';

	/**
	 * Whether the original Apex27 settings are available.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return (bool) $this->get_website_url() && ( (bool) $this->get_api_key() || (bool) $this->get_api_token() );
	}

	/**
	 * Fetch listings from the Apex27 website API.
	 *
	 * @param array $query Module query values.
	 *
	 * @return object|\WP_Error
	 */
	public function get_listings( array $query ) {
		$endpoint = ( isset( $query['listing_type'] ) && 'valuations' === (string) $query['listing_type'] )
			? 'valuations'
			: 'listings';

		return $this->request_collection_endpoint( $query, $endpoint );
	}

	/**
	 * Fetch listings using /listings or /valuations endpoint URL shape.
	 *
	 * @param array $query Module query values.
	 *
	 * @return object|\WP_Error
	 */
	private function request_collection_endpoint( array $query, $endpoint ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'divi_apex27_not_configured', __( 'Apex27 Website URL and API Key are not configured.', 'divi-apex27' ) );
		}

		$transaction_type = $this->resolve_transaction_type( $query );
		$page             = isset( $query['page'] ) ? absint( $query['page'] ) : 1;
		$page_size        = isset( $query['posts_per_page'] ) ? absint( $query['posts_per_page'] ) : 27;
		$requested_page   = $page;
		$requested_size   = $page_size;
		$effective_page   = $page;
		$effective_size   = $page_size;
		$is_aggregated    = false;

		if ( $page < 1 ) {
			$page = 1;
		}

		if ( $page_size < 1 ) {
			$page_size = 27;
		}

		if ( $requested_size < 25 ) {
			$effective_page = 1;
			$effective_size = max( 25, $requested_page * $requested_size );
			$is_aggregated  = true;
		}

		$params = array(
			'transactionType' => $transaction_type,
			'page'            => $effective_page,
			'pageSize'        => $effective_size,
			'includeImages'   => 1,
		);

		if ( 'valuations' === ( isset( $query['listing_type'] ) ? (string) $query['listing_type'] : 'listings' ) && ! empty( $query['valuation_type'] ) ) {
			$params['valuationType'] = sanitize_text_field( (string) $query['valuation_type'] );
		}

		$api_key   = $this->get_api_key();
		$api_token = $this->get_api_token();
		$headers   = array(
			'Accept' => 'application/json',
		);

		if ( '' !== trim( $api_key ) ) {
			$params['api_key'] = $api_key;
			$headers['x-api-key'] = $api_key;
		}

		if ( '' !== trim( $api_token ) ) {
			$params['apiToken'] = $api_token;
			$params['token']    = $api_token;
			$headers['x-api-token']   = $api_token;
			$headers['Authorization'] = 'Bearer ' . $api_token;
		}

		$url       = trailingslashit( $this->get_website_url() ) . ltrim( (string) $endpoint, '/' );
		$request   = add_query_arg( $params, $url );
		$cache_key = 'divi_apex27_' . md5( $request );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			$request,
			array(
				'timeout' => 20,
				'headers' => $headers,
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

		$fetch_meta = array(
			'requestedPage'     => $requested_page,
			'requestedPageSize' => $requested_size,
			'effectivePage'     => $effective_page,
			'effectivePageSize' => $effective_size,
			'aggregated'        => $is_aggregated,
		);

		if ( is_object( $data ) ) {
			$data->_divi_apex27_fetch = $fetch_meta;
		} elseif ( is_array( $data ) ) {
			$data['_divi_apex27_fetch'] = $fetch_meta;
		}

		set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Resolve final transaction type for listings query.
	 *
	 * @param string $type Module type.
	 *
	 * @return string
	 */
	private function resolve_transaction_type( array $query ) {
		$type = isset( $query['type'] ) ? (string) $query['type'] : 'rent';

		if ( in_array( $type, array( 'sale', 'rent', 'land', 'commercial_sale', 'commercial_rent' ), true ) ) {
			return $type;
		}

		return 'rent';
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
		$selected_type = isset( $query['type'] ) ? (string) $query['type'] : 'rent';
		$sector        = isset( $query['sector'] ) ? (string) $query['sector'] : '';
		$effective_type = $selected_type;
		$posts_per_page = isset( $query['posts_per_page'] ) ? absint( $query['posts_per_page'] ) : 10;
		$page           = isset( $query['page'] ) ? absint( $query['page'] ) : 1;

		if ( $posts_per_page < 1 ) {
			$posts_per_page = 10;
		}

		if ( $page < 1 ) {
			$page = 1;
		}

		$offset = ( $page - 1 ) * $posts_per_page;

		if ( 'commercial' === $sector ) {
			if ( 'sale' === $selected_type ) {
				$effective_type = 'commercial_sale';
			} elseif ( 'rent' === $selected_type ) {
				$effective_type = 'commercial_rent';
			}
		}

		return array(
			'search'           => 1,
			'type'             => $effective_type,
			'property_type'    => $query['property_type'] ?? '',
			'transaction_type' => $effective_type,
			'sector'           => $sector,
			'residential_commercial' => $sector,
			'city'             => $query['city'] ?? '',
			'valuation_type'   => $query['valuation_type'] ?? '',
			'valuation'        => $query['valuation_type'] ?? '',
			'valuationType'    => $query['valuation_type'] ?? '',
			'posts_per_page'   => $posts_per_page,
			'per_page'         => $posts_per_page,
			'limit'            => $posts_per_page,
			'count'            => $posts_per_page,
			'page_size'        => $posts_per_page,
			'pageSize'         => $posts_per_page,
			'results_per_page' => $posts_per_page,
			'postsToDisplay'   => $posts_per_page,
			'posts_to_display' => $posts_per_page,
			'offset'           => $offset,
			'start'            => $offset,
			'from'             => $offset,
			'skip'             => $offset,
			'min_gross_yield'  => $query['min_gross_yield'] ?? '',
			'include_sstc'     => $query['include_sstc'] ?? '',
			'sort'             => $query['sort'] ?? 'highest_price',
			'page'             => $page,
			'paged'            => $page,
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

		$api_key   = $this->get_api_key();
		$api_token = $this->get_api_token();
		$headers   = array(
			'Accept' => 'application/json',
		);

		if ( '' !== trim( $api_key ) ) {
			$data['api_key'] = $api_key;
			$headers['x-api-key'] = $api_key;
		}

		if ( '' !== trim( $api_token ) ) {
			$data['apiToken'] = $api_token;
			$data['token']    = $api_token;
			$headers['x-api-token']   = $api_token;
			$headers['Authorization'] = 'Bearer ' . $api_token;
		}
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
				'headers' => $headers,
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
		$website_url = (string) get_option( self::DIVI_WEBSITE_URL_OPTION, '' );

		if ( '' === trim( $website_url ) ) {
			$website_url = (string) get_option( self::WEBSITE_URL_OPTION, '' );
		}

		return untrailingslashit( esc_url_raw( $website_url ) );
	}

	/**
	 * Get API key from Divi and legacy Apex27 settings.
	 *
	 * @return string
	 */
	private function get_api_key() {
		$api_key = (string) get_option( self::DIVI_API_KEY_OPTION, '' );

		if ( '' === trim( $api_key ) ) {
			$api_key = (string) get_option( self::API_KEY_OPTION, '' );
		}

		return $api_key;
	}

	/**
	 * Get API token from Divi and legacy Apex27 settings.
	 *
	 * @return string
	 */
	private function get_api_token() {
		$api_token = (string) get_option( self::DIVI_API_TOKEN_OPTION, '' );

		if ( '' === trim( $api_token ) ) {
			$api_token = (string) get_option( self::API_TOKEN_OPTION, '' );
		}

		if ( '' === trim( $api_token ) ) {
			$api_token = (string) get_option( self::DIVI_API_KEY_OPTION, '' );
		}

		if ( '' === trim( $api_token ) ) {
			$api_token = (string) get_option( self::API_KEY_OPTION, '' );
		}

		return $api_token;
	}
}
