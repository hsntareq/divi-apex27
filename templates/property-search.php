<?php
/**
 * Standalone property search template for Divi Apex27.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search_context = isset( $GLOBALS['divi_apex27_property_search_context'] ) && is_array( $GLOBALS['divi_apex27_property_search_context'] )
	? $GLOBALS['divi_apex27_property_search_context']
	: array();

get_header();
?>
<div class="divi-apex27-details">
	<div class="divi-apex27-results-wrap">
		<?php if ( ! empty( $search_context['error_message'] ) ) : ?>
			<div class="divi-apex27-notice">
				<?php echo esc_html( (string) $search_context['error_message'] ); ?>
			</div>
		<?php else : ?>
			<?php
			echo Divi_Apex27_Search_Form_Renderer::render(
				array(
					'title'              => __( 'Property Search', 'divi-apex27' ),
					'show_listing_type'  => 'on',
					'default_listing_type' => isset( $search_context['listing_type'] ) ? (string) $search_context['listing_type'] : 'listings',
					'default_type'       => isset( $search_context['type'] ) ? (string) $search_context['type'] : 'rent',
				)
			);
			echo Divi_Apex27_Renderer::render(
				array(
					'title'          => __( 'Properties', 'divi-apex27' ),
					'listing_type'   => isset( $search_context['listing_type'] ) ? (string) $search_context['listing_type'] : 'listings',
					'type'           => isset( $search_context['type'] ) ? (string) $search_context['type'] : 'rent',
					'row_count'      => '2',
					'column_count'   => '4',
					'empty_text'     => __( 'No properties found for the selected criteria.', 'divi-apex27' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
