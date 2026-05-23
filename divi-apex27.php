<?php
/**
 * Plugin Name: Divi Apex27
 * Description: Divi module for rendering Apex27 property search results using the existing Apex27 API settings.
 * Version: 1.0.0
 * Author: Hasan Tareq
 * Text Domain: divi-apex27
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIVI_APEX27_VERSION', '1.0.0' );
define( 'DIVI_APEX27_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIVI_APEX27_URL', plugin_dir_url( __FILE__ ) );

require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-api.php';
require_once DIVI_APEX27_PATH . 'includes/class-divi-apex27-renderer.php';

add_action( 'wp_enqueue_scripts', 'divi_apex27_enqueue_assets' );
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'divi_apex27_enqueue_builder_assets' );
add_action( 'et_builder_ready', 'divi_apex27_register_modules' );
add_action( 'divi_module_library_register_modules', 'divi_apex27_register_modules' );
add_action( 'init', 'divi_apex27_register_modules', 20 );
add_shortcode( 'divi_apex27_property_filter', 'divi_apex27_shortcode' );

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
	wp_register_script(
		'divi-apex27-builder',
		DIVI_APEX27_URL . 'assets/js/builder.js',
		array( 'lodash', 'divi-vendor-wp-hooks', 'divi-vendor-wp-i18n' ),
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

	$module_path = DIVI_APEX27_PATH . 'modules/property-filter';
	$config      = array(
		'render_callback' => 'divi_apex27_render_callback',
	);

	if ( class_exists( 'ET\Builder\Packages\ModuleLibrary\ModuleRegistration' ) ) {
		ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module( $module_path, $config );
		$divi5_registered = true;
	} elseif ( function_exists( 'divi_module_library_register_module' ) ) {
		divi_module_library_register_module( $module_path, $config );
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

	if ( class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
		return ET\Builder\Packages\Module\Module::render(
			array(
				'attrs'          => $attrs,
				'elements'       => $elements,
				'id'             => $block->parsed_block['id'] ?? '',
				'name'           => $block->block_type->name,
				'moduleCategory' => $block->block_type->category,
				'children'       => $output . $content,
			)
		);
	}

	return $output . $content;
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
