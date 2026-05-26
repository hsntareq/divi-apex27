<?php
/**
 * Plugin Name: Divi Apex27
 * Description: Divi module for rendering Apex27 property search results using the existing Apex27 API settings.
 * Version: 1.0.54
 * Author: Hasan Tareq
 * Text Domain: divi-apex27
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIVI_APEX27_VERSION', '1.0.54' );
define( 'DIVI_APEX27_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIVI_APEX27_URL', plugin_dir_url( __FILE__ ) );

require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-api.php';
require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-renderer.php';
require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-search-form-renderer.php';
require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-property-details.php';

register_activation_hook( __FILE__, array( 'Divi_Apex27_Property_Details', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'Divi_Apex27_Property_Details', 'on_deactivation' ) );

add_action( 'wp_enqueue_scripts', 'divi_apex27_enqueue_assets' );
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'divi_apex27_enqueue_builder_assets' );
add_action( 'et_builder_ready', 'divi_apex27_register_modules' );
add_action( 'divi_module_library_register_modules', 'divi_apex27_register_modules' );
add_action( 'init', 'divi_apex27_register_modules', 20 );
add_action( 'admin_init', 'divi_apex27_register_settings' );
add_action( 'admin_menu', 'divi_apex27_register_settings_page' );
add_shortcode( 'divi_apex27_property_filter', 'divi_apex27_shortcode' );

divi_apex27_boot_property_details();

/**
 * Boot standalone property-details handling.
 *
 * @return void
 */
function divi_apex27_boot_property_details() {
	static $booted = false;

	if ( $booted ) {
		return;
	}

	$handler = new Divi_Apex27_Property_Details();
	$handler->register();
	$booted = true;
}

/**
 * Register settings fields for Divi Apex27.
 *
 * @return void
 */
function divi_apex27_register_settings() {
	register_setting(
		'divi_apex27_settings_group',
		'divi_apex27_website_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		)
	);

	register_setting(
		'divi_apex27_settings_group',
		'divi_apex27_api_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'divi_apex27_settings_group',
		'divi_apex27_api_token',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}

/**
 * Register settings page under WordPress Settings menu.
 *
 * @return void
 */
function divi_apex27_register_settings_page() {
	add_options_page(
		esc_html__( 'Divi Apex27 Settings', 'divi-apex27' ),
		esc_html__( 'Divi Apex27', 'divi-apex27' ),
		'manage_options',
		'divi-apex27',
		'divi_apex27_render_settings_page'
	);
}

/**
 * Render settings page.
 *
 * @return void
 */
function divi_apex27_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$website_url = get_option( 'divi_apex27_website_url', '' );
	$api_key     = get_option( 'divi_apex27_api_key', '' );
	$api_token   = get_option( 'divi_apex27_api_token', '' );
	$legacy_url  = get_option( 'apex27_website_url', '' );
	$legacy_key  = get_option( 'apex27_api_key', '' );
	$legacy_token = get_option( 'apex27_api_token', '' );

	if ( empty( $api_key ) && ! empty( $legacy_key ) ) {
		$api_key = $legacy_key;
	}

	if ( empty( $api_token ) ) {
		if ( ! empty( $legacy_token ) ) {
			$api_token = $legacy_token;
		} elseif ( ! empty( $api_key ) ) {
			$api_token = $api_key;
		}
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Divi Apex27 Settings', 'divi-apex27' ); ?></h1>
		<p><?php echo esc_html__( 'These settings mirror the Apex27 connection values used for API requests.', 'divi-apex27' ); ?></p>

		<?php if ( empty( $website_url ) && ! empty( $legacy_url ) ) : ?>
			<div class="notice notice-info inline"><p><?php echo esc_html__( 'Legacy Apex27 settings were detected. You can keep using them, or save values here to override for Divi Apex27 only.', 'divi-apex27' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'divi_apex27_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="divi_apex27_website_url"><?php echo esc_html__( 'Website URL', 'divi-apex27' ); ?></label></th>
					<td>
						<input name="divi_apex27_website_url" type="url" id="divi_apex27_website_url" value="<?php echo esc_attr( $website_url ); ?>" class="regular-text" placeholder="https://example.com" />
						<p class="description"><?php echo esc_html__( 'Base URL of your Apex27 website.', 'divi-apex27' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="divi_apex27_api_key"><?php echo esc_html__( 'API Key', 'divi-apex27' ); ?></label></th>
					<td>
						<input name="divi_apex27_api_key" type="text" id="divi_apex27_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="off" />
						<p class="description"><?php echo esc_html__( 'API key used for Apex27 endpoints.', 'divi-apex27' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="divi_apex27_api_token"><?php echo esc_html__( 'API Token', 'divi-apex27' ); ?></label></th>
					<td>
						<input name="divi_apex27_api_token" type="text" id="divi_apex27_api_token" value="<?php echo esc_attr( $api_token ); ?>" class="regular-text" autocomplete="off" />
						<p class="description"><?php echo esc_html__( 'Token used for Apex27 authenticated endpoints.', 'divi-apex27' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue frontend assets.
 *
 * @return void
 */
function divi_apex27_enqueue_assets() {
	wp_enqueue_style(
		'divi-apex27',
		DIVI_APEX27_URL . 'assets/css/divi-apex27.css',
		array(),
		DIVI_APEX27_VERSION
	);
}

/**
 * Enqueue Divi 5 builder registration asset.
 *
 * @return void
 */
function divi_apex27_enqueue_builder_assets() {
	$dependency_groups = array(
		array( 'lodash' ),
		array( 'divi-vendor-wp-hooks', 'wp-hooks' ),
		array( 'divi-vendor-wp-i18n', 'wp-i18n' ),
		array( 'divi-module-library' ),
		array( 'divi-module' ),
		array( 'react' ),
	);

	$dependencies = array();
	foreach ( $dependency_groups as $group ) {
		foreach ( $group as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				$dependencies[] = $handle;
				break;
			}
		}
	}

	wp_register_script(
		'divi-apex27-builder',
		DIVI_APEX27_URL . 'assets/js/builder.js',
		$dependencies,
		DIVI_APEX27_VERSION,
		true
	);

	$metadata = json_decode( file_get_contents( DIVI_APEX27_PATH . 'modules/property-filter/module.json' ), true );
	if ( is_array( $metadata ) ) {
		wp_add_inline_script(
				'divi-apex27-builder',
				'window.diviApex27PropertyFilterMetadata = ' . wp_json_encode( $metadata ) . ';',
				'before'
			);
	}

	$search_form_metadata = json_decode( file_get_contents( DIVI_APEX27_PATH . 'modules/property-search-form/module.json' ), true );
	if ( is_array( $search_form_metadata ) ) {
		wp_add_inline_script(
			'divi-apex27-builder',
			'window.diviApex27PropertySearchFormMetadata = ' . wp_json_encode( $search_form_metadata ) . ';',
			'before'
		);
	}

	wp_enqueue_script( 'divi-apex27-builder' );
	wp_enqueue_style(
		'divi-apex27-builder',
		DIVI_APEX27_URL . 'assets/css/divi-apex27.css',
		array(),
		DIVI_APEX27_VERSION
	);
}

/**
 * Register Divi 4 and Divi 5 modules.
 *
 * @return void
 */
function divi_apex27_register_modules() {
	static $legacy_registered = false;
	static $divi5_registered  = false;

	if ( ! $legacy_registered && class_exists( 'ET_Builder_Module' ) && ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) ) {
		require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-module.php';
		new Divi_Apex27_Module();
		$legacy_registered = true;
	}

	if ( $divi5_registered ) {
		return;
	}

	$divi5_modules = array(
		array(
			'path'   => DIVI_APEX27_PATH . 'modules/property-filter',
			'config' => array(
				'render_callback' => 'divi_apex27_render_callback',
			),
		),
		array(
			'path'   => DIVI_APEX27_PATH . 'modules/property-search-form',
			'config' => array(
				'render_callback' => 'divi_apex27_search_form_render_callback',
			),
		),
	);

	if ( class_exists( 'ET\\Builder\\Packages\\ModuleLibrary\\ModuleRegistration' ) ) {
		foreach ( $divi5_modules as $module ) {
			ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module( $module['path'], $module['config'] );
		}
		$divi5_registered = true;
	} elseif ( function_exists( 'divi_module_library_register_module' ) ) {
		foreach ( $divi5_modules as $module ) {
			divi_module_library_register_module( $module['path'], $module['config'] );
		}
		$divi5_registered = true;
	}
}

/**
 * Divi 5 render callback.
 *
 * @param array     $attrs    Module attributes.
 * @param string    $content  Module content.
 * @param \WP_Block $block    Block object.
 * @param object    $elements Divi elements object.
 *
 * @return string
 */
function divi_apex27_render_callback( $attrs, $content, $block, $elements ) {
	$output = Divi_Apex27_Renderer::render( Divi_Apex27_Renderer::attrs_to_props( $attrs ) );
	$parsed_block = ( is_object( $block ) && isset( $block->parsed_block ) && is_array( $block->parsed_block ) ) ? $block->parsed_block : array();
	$block_type   = ( is_object( $block ) && isset( $block->block_type ) && is_object( $block->block_type ) ) ? $block->block_type : null;

	if ( class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
		$style_components = '';
		if ( is_object( $elements ) && method_exists( $elements, 'style_components' ) ) {
			$style_components = $elements->style_components(
				array(
					'attrName' => 'module',
				)
			);
		}

		return ET\Builder\Packages\Module\Module::render(
			array(
				'attrs'              => $attrs,
				'elements'           => $elements,
				'id'                 => $parsed_block['id'] ?? '',
				'name'               => is_object( $block_type ) && isset( $block_type->name ) ? $block_type->name : 'divi-apex27/property-filter',
				'moduleCategory'     => is_object( $block_type ) && isset( $block_type->category ) ? $block_type->category : 'module',
				'orderIndex'         => $parsed_block['orderIndex'] ?? null,
				'storeInstance'      => $parsed_block['storeInstance'] ?? null,
				'children'           => $style_components . $output . $content,
			)
		);
	}

	return $output . $content;
}

/**
 * Divi 5 search form render callback.
 *
 * @param array     $attrs    Module attributes.
 * @param string    $content  Module content.
 * @param \WP_Block $block   Block object.
 * @param object    $elements Divi elements object.
 *
 * @return string
 */
function divi_apex27_search_form_render_callback( $attrs, $content, $block, $elements ) {
	try {
		$output = Divi_Apex27_Search_Form_Renderer::render( Divi_Apex27_Search_Form_Renderer::attrs_to_props( $attrs ) );

		return $output . $content;
	} catch ( Throwable $error ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Divi Apex27 search form render failed: ' . $error->getMessage() );
		}

		$message = __( 'The Apex27 property search form could not be rendered in the builder.', 'divi-apex27' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$message .= ' ' . $error->getMessage();
		}

		return sprintf(
			'<div class="divi-apex27-notice divi-apex27-builder-placeholder">%s</div>',
			esc_html( $message )
		);
	}
}

/**
 * Shortcode fallback for non-Divi rendering.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function divi_apex27_shortcode( $atts ) {
	return Divi_Apex27_Renderer::render( shortcode_atts( Divi_Apex27_Renderer::defaults(), $atts ) );
}
