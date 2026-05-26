<?php
/**
 * Standalone property details route and template handler.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles /property-details/... routes for Divi Apex27.
 */
class Divi_Apex27_Property_Details {

	const PAGE_QUERY_VAR = 'divi_apex27_page_name';
	const PAGE_VALUE     = 'property-details';

	/**
	 * Cached request context.
	 *
	 * @var array
	 */
	private $context = array();

	/**
	 * Whether request context was already built.
	 *
	 * @var bool
	 */
	private $context_built = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'template_include', array( $this, 'template_include' ), 999 );
		add_filter( 'document_title_parts', array( $this, 'document_title_parts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public static function on_activation() {
		$handler = new self();
		$handler->register_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	public static function on_deactivation() {
		flush_rewrite_rules();
	}

	/**
	 * Register property details rewrite rule.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule(
			'^property-details/(sales|lettings|new-homes|land|commercial-sales|commercial-lettings)/[^/]+/([0-9]+)/?$',
			'index.php?' . self::PAGE_QUERY_VAR . '=' . self::PAGE_VALUE . '&listing_id=$matches[2]',
			'top'
		);
	}

	/**
	 * Register custom query vars.
	 *
	 * @param array $vars Existing vars.
	 *
	 * @return array
	 */
	public function register_query_vars( $vars ) {
		$vars[] = self::PAGE_QUERY_VAR;
		$vars[] = 'listing_id';

		return $vars;
	}

	/**
	 * Set custom page title for property details routes.
	 *
	 * @param array $parts Title parts.
	 *
	 * @return array
	 */
	public function document_title_parts( $parts ) {
		if ( ! $this->is_property_details_request() ) {
			return $parts;
		}

		$parts['title'] = __( 'Property Details', 'divi-apex27' );
		$context        = $this->get_context();

		if ( ! empty( $context['details'] ) && ! empty( $context['details']->displayAddress ) ) {
			$parts['title'] = (string) $context['details']->displayAddress;
		}

		return $parts;
	}

	/**
	 * Ensure assets are loaded on details pages.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_property_details_request() ) {
			return;
		}

		wp_enqueue_style(
			'divi-apex27',
			DIVI_APEX27_URL . 'assets/css/divi-apex27.css',
			array(),
			DIVI_APEX27_VERSION
		);
	}

	/**
	 * Swap page template for property details route.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public function template_include( $template ) {
		if ( ! $this->is_property_details_request() ) {
			return $template;
		}

		$GLOBALS['divi_apex27_property_details_context'] = $this->get_context();

		$custom_template = DIVI_APEX27_PATH . 'templates/property-details.php';
		if ( file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}

	/**
	 * Determine if this request is for the property details route.
	 *
	 * @return bool
	 */
	private function is_property_details_request() {
		$page = (string) get_query_var( self::PAGE_QUERY_VAR, '' );

		return self::PAGE_VALUE === $page;
	}

	/**
	 * Build template context once per request.
	 *
	 * @return array
	 */
	private function get_context() {
		if ( $this->context_built ) {
			return $this->context;
		}

		$this->context_built = true;
		$listing_id          = absint( get_query_var( 'listing_id', 0 ) );
		$api                 = new Divi_Apex27_API();
		$details             = null;
		$error_message       = '';

		if ( $listing_id < 1 ) {
			$error_message = __( 'Invalid property details URL.', 'divi-apex27' );
		} elseif ( ! $api->is_configured() ) {
			$error_message = __( 'Apex27 settings are not configured.', 'divi-apex27' );
		} else {
			$details_response = $api->get_property_details( $listing_id );
			if ( is_wp_error( $details_response ) || ! is_object( $details_response ) ) {
				$error_message = __( 'Cannot retrieve property details at this time. Please try again later.', 'divi-apex27' );
			} else {
				$details = $details_response;
			}
		}

		$this->context = array(
			'listing_id'    => $listing_id,
			'details'       => $details,
			'error_message' => $error_message,
		);

		return $this->context;
	}
}
