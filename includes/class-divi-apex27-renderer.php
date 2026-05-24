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
			'listing_type'  => 'listings',
			'type'          => 'rent',
			'column_count'  => '4',
			'sector'        => '',
			'property_type' => '',
			'city'          => '',
			'valuation_type'  => '',
			'posts_per_page'  => '27',
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
			$output .= is_wp_error( $result ) ? self::render_notice( $result->get_error_message() ) : self::render_results( $result, $props['empty_text'], $is_builder_preview, $query, $props );
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
			'listing_type'  => array( 'apex27_listing_type', 'listing_type', 'listingType' ),
			'type'          => array( 'apex27_type', 'transaction_type' ),
			'sector'        => array( 'apex27_sector', 'sector', 'residential_commercial' ),
			'property_type' => array( 'apex27_property_type', 'propertyType' ),
			'city'          => array( 'apex27_city', 'apex27_query', 'query' ),
			'valuation_type'  => array( 'apex27_valuation_type', 'valuation_type', 'valuationType', 'valuation' ),
			'posts_per_page'  => array( 'propertyPerPage', 'property_per_page', 'pageSize', 'per_page', 'posts_per_page', 'apex27_posts_per_page' ),
			'min_gross_yield' => array( 'apex27_min_gross_yield' ),
			'include_sstc'    => array( 'apex27_include_sstc' ),
			'sort'          => array( 'apex27_sort' ),
			'page'          => array( 'apex27_page', 'paged', 'page' ),
		);
		$query = array();

		foreach ( $aliases as $canonical => $keys ) {
			$value = isset( $props[ $canonical ] ) ? sanitize_text_field( (string) $props[ $canonical ] ) : '';

			if ( 'page' !== $canonical && isset( $_GET[ $canonical ] ) && ! is_array( $_GET[ $canonical ] ) ) {
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
	 * @param array  $query              Effective query values.
	 *
	 * @return string
	 */
	private static function render_results( $result, $empty_text, $show_builder_debug = false, array $query = array(), array $props = array() ) {
		$items = self::extract_items( $result );
		$page_size = isset( $query['posts_per_page'] ) ? absint( $query['posts_per_page'] ) : 27;
		$current_page = isset( $query['page'] ) ? absint( $query['page'] ) : self::requested_page_from_url();
		$fetch_meta   = self::extract_fetch_meta( $result );
		$slice_offset = 0;

		if ( $page_size < 1 ) {
			$page_size = 27;
		}

		if ( $current_page < 1 ) {
			$current_page = 1;
		}

		if ( ! empty( $fetch_meta['aggregated'] ) && $current_page > 1 ) {
			$slice_offset = ( $current_page - 1 ) * $page_size;
		}

		if ( count( $items ) > $page_size || $slice_offset > 0 ) {
			$items = array_slice( $items, $slice_offset, $page_size );
		}

		if ( empty( $items ) ) {
			$notice = self::render_notice( $empty_text );
			if ( $show_builder_debug ) {
				$notice .= self::render_builder_debug( $result );
			}
			return $notice;
		}

		$grid_style = self::results_grid_style_attr( $props );
		$output     = sprintf( '<div class="divi-apex27-results" style="%s">', esc_attr( $grid_style ) );

		foreach ( $items as $item ) {
			$output .= self::render_card( is_object( $item ) ? $item : (object) $item );
		}

		$output .= self::render_pagination( $result, count( $items ), $page_size );

		return $output . '</div>';
	}

	/**
	 * Build responsive CSS variable string for result grid columns.
	 *
	 * @param array $props Module props.
	 *
	 * @return string
	 */
	private static function results_grid_style_attr( array $props ) {
		$columns = isset( $props['column_count'] ) ? absint( $props['column_count'] ) : 4;

		if ( $columns < 1 ) {
			$columns = 1;
		}

		if ( $columns > 6 ) {
			$columns = 6;
		}

		$columns_lg = min( $columns, 3 );
		$columns_md = min( $columns, 2 );

		return sprintf(
			'--apex27-columns:%1$d;--apex27-columns-lg:%2$d;--apex27-columns-md:%3$d;--apex27-columns-sm:1;',
			$columns,
			$columns_lg,
			$columns_md
		);
	}

	/**
	 * Render pagination controls when API metadata exposes multiple pages.
	 *
	 * @param mixed $result API result.
	 * @param int   $count      Number of items in current page.
	 * @param int   $page_size  Requested per-page count.
	 *
	 * @return string
	 */
	private static function render_pagination( $result, $count, $page_size = 10 ) {
		$pagination = self::extract_pagination( $result, $count, $page_size );

		if ( empty( $pagination['total_pages'] ) || $pagination['total_pages'] < 2 ) {
			return '';
		}

		$current_page = max( 1, (int) $pagination['current_page'] );
		$total_pages  = max( 1, (int) $pagination['total_pages'] );
		$start_page   = max( 1, $current_page - 2 );
		$end_page     = min( $total_pages, $current_page + 2 );

		if ( $end_page - $start_page < 4 ) {
			$end_page   = min( $total_pages, $start_page + 4 );
			$start_page = max( 1, $end_page - 4 );
		}

		$output = '<nav class="divi-apex27-pagination" aria-label="' . esc_attr__( 'Property results pagination', 'divi-apex27' ) . '">';

		if ( $current_page > 1 ) {
			$output .= sprintf(
				'<a class="divi-apex27-page-link prev" href="%s">%s</a>',
				esc_url( self::build_page_url( $current_page - 1, $page_size ) ),
				esc_html__( 'Previous', 'divi-apex27' )
			);
		}

		for ( $page = $start_page; $page <= $end_page; $page++ ) {
			if ( $page === $current_page ) {
				$output .= sprintf( '<span class="divi-apex27-page-link is-active" aria-current="page">%d</span>', absint( $page ) );
				continue;
			}

			$output .= sprintf(
				'<a class="divi-apex27-page-link" href="%s">%d</a>',
				esc_url( self::build_page_url( $page, $page_size ) ),
				absint( $page )
			);
		}

		if ( $current_page < $total_pages ) {
			$output .= sprintf(
				'<a class="divi-apex27-page-link next" href="%s">%s</a>',
				esc_url( self::build_page_url( $current_page + 1, $page_size ) ),
				esc_html__( 'Next', 'divi-apex27' )
			);
		}

		return $output . '</nav>';
	}

	/**
	 * Extract pagination details from common API response shapes.
	 *
	 * @param mixed $result API result.
	 * @param int   $count      Number of items in current page.
	 * @param int   $page_size  Requested per-page count.
	 *
	 * @return array
	 */
	private static function extract_pagination( $result, $count, $page_size = 10 ) {
		$requested_page = self::requested_page_from_url();
		$current_page = $requested_page;
		$total_pages = 0;
		$page_size   = max( 1, absint( $page_size ) );

		$candidates = self::find_pagination_candidate_arrays( $result );

		foreach ( $candidates as $candidate ) {
			if ( isset( $candidate['current_page'] ) && is_numeric( $candidate['current_page'] ) ) {
				$current_page = max( 1, absint( $candidate['current_page'] ) );
			} elseif ( isset( $candidate['currentPage'] ) && is_numeric( $candidate['currentPage'] ) ) {
				$current_page = max( 1, absint( $candidate['currentPage'] ) );
			} elseif ( isset( $candidate['page'] ) && is_numeric( $candidate['page'] ) ) {
				$current_page = max( 1, absint( $candidate['page'] ) );
			} elseif ( isset( $candidate['pageNumber'] ) && is_numeric( $candidate['pageNumber'] ) ) {
				$current_page = max( 1, absint( $candidate['pageNumber'] ) );
			} elseif ( isset( $candidate['paged'] ) && is_numeric( $candidate['paged'] ) ) {
				$current_page = max( 1, absint( $candidate['paged'] ) );
			}

			foreach ( array( 'last_page', 'lastPage', 'total_pages', 'totalPages', 'pages', 'page_count', 'pageCount' ) as $key ) {
				if ( isset( $candidate[ $key ] ) && is_numeric( $candidate[ $key ] ) ) {
					$total_pages = max( $total_pages, absint( $candidate[ $key ] ) );
				}
			}

			$total_items = 0;
			foreach ( array( 'total', 'total_count', 'totalCount', 'total_results', 'totalResults' ) as $key ) {
				if ( isset( $candidate[ $key ] ) && is_numeric( $candidate[ $key ] ) && (int) $candidate[ $key ] >= 0 ) {
					$total_items = (int) $candidate[ $key ];
					break;
				}
			}

			if ( $total_pages < 2 && $total_items > 0 ) {
				$total_pages = max( $total_pages, (int) ceil( $total_items / max( 1, $page_size ) ) );
			}

			$has_next = false;
			foreach ( array( 'has_next_page', 'hasNextPage', 'has_more', 'hasMore' ) as $key ) {
				if ( isset( $candidate[ $key ] ) ) {
					$has_next = filter_var( $candidate[ $key ], FILTER_VALIDATE_BOOLEAN );
					break;
				}
			}

			if ( ! $has_next ) {
				foreach ( array( 'next_page', 'nextPage', 'next_page_url', 'nextPageUrl' ) as $key ) {
					if ( isset( $candidate[ $key ] ) && '' !== (string) $candidate[ $key ] && null !== $candidate[ $key ] ) {
						$has_next = true;
						break;
					}
				}
			}

			if ( $has_next && $total_pages < ( $current_page + 1 ) ) {
				$total_pages = $current_page + 1;
			}
		}

		// Prefer explicit requested page when API metadata is stale or always reports page 1.
		if ( $requested_page > 1 && $current_page < $requested_page ) {
			$current_page = $requested_page;
		}

		if ( $current_page > 1 && $total_pages < $current_page ) {
			// When API omits totals, keep pagination visible on deeper pages.
			$total_pages = $current_page;
		}

		if ( $total_pages < 2 && $count >= $page_size ) {
			// Fallback: if current page is full, assume a possible next page.
			$total_pages = $current_page + 1;
		}

		return array(
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
		);
	}

	/**
	 * Extract internal fetch metadata injected by API adapter.
	 *
	 * @param mixed $result API result.
	 *
	 * @return array
	 */
	private static function extract_fetch_meta( $result ) {
		if ( is_object( $result ) && isset( $result->_divi_apex27_fetch ) ) {
			$meta = $result->_divi_apex27_fetch;
			if ( is_object( $meta ) ) {
				$meta = get_object_vars( $meta );
			}

			if ( is_array( $meta ) ) {
				return $meta;
			}
		}

		if ( is_array( $result ) && isset( $result['_divi_apex27_fetch'] ) && is_array( $result['_divi_apex27_fetch'] ) ) {
			return $result['_divi_apex27_fetch'];
		}

		return array();
	}

	/**
	 * Find possible pagination arrays from nested API response structures.
	 *
	 * @param mixed $result API result.
	 *
	 * @return array
	 */
	private static function find_pagination_candidate_arrays( $result ) {
		$candidates = array();

		if ( is_object( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
		}

		if ( ! is_array( $result ) ) {
			return $candidates;
		}

		$queue = array( $result );

		while ( ! empty( $queue ) ) {
			$current = array_shift( $queue );

			if ( ! is_array( $current ) ) {
				continue;
			}

			$keys = array_keys( $current );
			if ( array_intersect( $keys, array( 'current_page', 'currentPage', 'last_page', 'lastPage', 'total_pages', 'totalPages', 'pages', 'page', 'pageNumber', 'per_page', 'pageSize', 'total', 'totalCount', 'total_results', 'totalResults' ) ) ) {
				$candidates[] = $current;
			}

			foreach ( $current as $value ) {
				if ( is_array( $value ) ) {
					$queue[] = $value;
				}
			}
		}

		return $candidates;
	}

	/**
	 * Build page URL while preserving existing query parameters.
	 *
	 * @param int $page      Target page.
	 * @param int $page_size Requested per-page count.
	 *
	 * @return string
	 */
	private static function build_page_url( $page, $page_size = 10 ) {
		$page      = max( 1, absint( $page ) );
		$page_size = max( 25, absint( $page_size ) );
		return add_query_arg(
			array(
				'apex27_page' => $page,
				'pageSize'    => $page_size,
				'page'        => false,
				'paged'       => false,
			)
		);
	}

	/**
	 * Resolve requested page from URL supporting both plugin and generic page vars.
	 *
	 * @return int
	 */
	private static function requested_page_from_url() {
		foreach ( array( 'apex27_page', 'paged', 'page' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && ! is_array( $_GET[ $key ] ) ) {
				return max( 1, absint( wp_unslash( $_GET[ $key ] ) ) );
			}
		}

		return 1;
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

			foreach ( array( 'listings', 'properties', 'valuations', 'results', 'items', 'data' ) as $key ) {
				if ( isset( $result[ $key ] ) && is_array( $result[ $key ] ) ) {
					return $result[ $key ];
				}
			}
		}

		if ( is_object( $result ) ) {
			foreach ( array( 'listings', 'properties', 'valuations', 'results', 'items', 'data' ) as $key ) {
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
		$property = self::normalize_property_for_card( $property );
		$title    = self::first_property_value( $property, array( 'displayAddress', 'display_address', 'address', 'fullAddress', 'full_address', 'title', 'header', 'name' ), __( 'Apex27 Property', 'divi-apex27' ) );
		$price    = self::first_property_value( $property, array( 'displayPrice', 'display_price', 'price', 'valuationPrice', 'valuation_price', 'valuationAmount', 'valuation_amount', 'amount' ), '' );
		$price_prefix = self::first_property_value( $property, array( 'pricePrefix', 'price_prefix' ), '' );
		$subtitle = self::first_property_value( $property, array( 'subtitle', 'subTitle' ), '' );
		$summary  = self::first_property_value( $property, array( 'summary', 'subtitle', 'description', 'shortDescription', 'short_description', 'details' ), '' );
		$status   = self::first_property_value( $property, array( 'status', 'availability', 'listingStatus', 'listing_status', 'propertyStatus', 'property_status' ), '' );
		$reference = self::first_property_value( $property, array( 'reference', 'ref', 'listingReference', 'listing_reference', 'propertyReference', 'property_reference' ), '' );
		$property_type = self::first_property_value( $property, array( 'propertyType', 'property_type', 'typeName', 'type_name', 'category' ), '' );
		$landlord = self::first_property_value( $property, array( 'landlord', 'landlords', 'landlordName', 'landlord_name', 'ownerName', 'owner_name' ), '' );
		$image_overlay_text = self::first_property_value( $property, array( 'imageOverlayText', 'image_overlay_text' ), '' );
		$banner = self::first_property_value( $property, array( 'banner', 'bannerText', 'banner_text' ), '' );
		$income_description = self::first_property_value( $property, array( 'incomeDescription', 'income_description' ), '' );
		$gross_yield = self::first_property_value( $property, array( 'grossYield', 'gross_yield' ), '' );
		$image    = self::extract_property_image_url( $property );
		$url      = self::property_url( $property );

		if ( '' === trim( $image ) ) {
			$image = self::fallback_property_image_url();
		}

		$output = '<article class="divi-apex27-card">';

		if ( $image ) {
			$output .= sprintf(
				'<a class="divi-apex27-card-media" href="%s"><img src="%s" alt="%s" loading="lazy" />',
				esc_url( $url ),
				esc_url( $image ),
				esc_attr( wp_strip_all_tags( $title ) )
			);

			if ( $image_overlay_text ) {
				$output .= sprintf( '<span class="divi-apex27-card-overlay">%s</span>', esc_html( $image_overlay_text ) );
			}

			if ( $banner ) {
				$output .= sprintf( '<span class="divi-apex27-card-banner">%s</span>', esc_html( $banner ) );
			}

			$output .= '</a>';
		}

		$output .= '<div class="divi-apex27-card-body">';
		$output .= sprintf( '<h3>%s</h3>', esc_html( $title ) );

		if ( $reference || $status || $property_type ) {
			$output .= '<p class="divi-apex27-card-aux">';
			$output .= $reference ? sprintf( '<span><strong>%s:</strong> %s</span>', esc_html__( 'Ref', 'divi-apex27' ), esc_html( $reference ) ) : '';
			$output .= $status ? sprintf( '<span><strong>%s:</strong> %s</span>', esc_html__( 'Status', 'divi-apex27' ), esc_html( $status ) ) : '';
			$output .= $property_type ? sprintf( '<span><strong>%s:</strong> %s</span>', esc_html__( 'Type', 'divi-apex27' ), esc_html( $property_type ) ) : '';
			$output .= '</p>';
		}

		if ( $price ) {
			$output .= '<p class="divi-apex27-card-price">' . esc_html( $price );
			if ( $price_prefix ) {
				$output .= ' <small>' . esc_html( $price_prefix ) . '</small>';
			}
			$output .= '</p>';
		}

		if ( $subtitle ) {
			$output .= sprintf( '<p class="divi-apex27-card-subtitle">%s</p>', esc_html( $subtitle ) );
		}

		if ( $landlord ) {
			$output .= sprintf( '<p class="divi-apex27-card-landlord"><strong>%s:</strong> %s</p>', esc_html__( 'Landlord', 'divi-apex27' ), esc_html( $landlord ) );
		}

		$bedrooms   = self::first_property_value( $property, array( 'bedrooms', 'bedroomCount', 'bedroom_count', 'beds' ), '' );
		$bathrooms  = self::first_property_value( $property, array( 'bathrooms', 'bathroomCount', 'bathroom_count', 'baths' ), '' );
		$receptions = self::first_property_value( $property, array( 'livingRooms', 'living_rooms', 'receptions', 'receptionRooms', 'reception_rooms' ), '' );

		if ( '' !== $bedrooms || '' !== $bathrooms || '' !== $receptions ) {
			$output .= '<p class="divi-apex27-card-meta">';
			$output .= '' !== $bedrooms ? sprintf( '<span>%s beds</span>', esc_html( $bedrooms ) ) : '';
			$output .= '' !== $bathrooms ? sprintf( '<span>%s baths</span>', esc_html( $bathrooms ) ) : '';
			$output .= '' !== $receptions ? sprintf( '<span>%s receptions</span>', esc_html( $receptions ) ) : '';
			$output .= '</p>';
		}

		if ( $summary ) {
			$output .= sprintf( '<p class="divi-apex27-card-summary">%s</p>', esc_html( wp_trim_words( wp_strip_all_tags( $summary ), 24 ) ) );
		}

		if ( $income_description ) {
			$output .= sprintf( '<p class="divi-apex27-card-income"><strong>%s:</strong> %s</p>', esc_html__( 'Gross Income', 'divi-apex27' ), esc_html( $income_description ) );
		}

		if ( $gross_yield ) {
			$output .= sprintf( '<p class="divi-apex27-card-yield"><strong>%s:</strong> %s</p>', esc_html__( 'Gross Yield', 'divi-apex27' ), esc_html( $gross_yield ) );
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
	 * Normalize listing/valuation objects so both card views use equivalent fields.
	 *
	 * @param mixed $property Item object.
	 *
	 * @return object
	 */
	private static function normalize_property_for_card( $property ) {
		if ( is_array( $property ) ) {
			$property = (object) $property;
		}

		if ( ! is_object( $property ) ) {
			return (object) array();
		}

		$base = get_object_vars( $property );

		foreach ( array( 'property', 'listing', 'item', 'result', 'data', 'details' ) as $nested_key ) {
			if ( empty( $property->{$nested_key} ) ) {
				continue;
			}

			$nested = $property->{$nested_key};

			if ( is_array( $nested ) ) {
				$nested = (object) $nested;
			}

			if ( ! is_object( $nested ) ) {
				continue;
			}

			$base = self::merge_preserving_non_empty_values( $base, get_object_vars( $nested ) );
		}

		return (object) $base;
	}

	/**
	 * Merge arrays while preventing non-empty source values from being replaced by empty values.
	 *
	 * @param array $source Existing flattened values.
	 * @param array $incoming Incoming nested values.
	 *
	 * @return array
	 */
	private static function merge_preserving_non_empty_values( array $source, array $incoming ) {
		foreach ( $incoming as $key => $value ) {
			$has_source = array_key_exists( $key, $source );
			$source_is_empty = ! $has_source || self::is_empty_scalar_value( $source[ $key ] );
			$incoming_is_empty = self::is_empty_scalar_value( $value );

			if ( $source_is_empty || ! $incoming_is_empty ) {
				$source[ $key ] = $value;
			}
		}

		return $source;
	}

	/**
	 * Determine whether a value is effectively empty for merge purposes.
	 *
	 * @param mixed $value Candidate value.
	 *
	 * @return bool
	 */
	private static function is_empty_scalar_value( $value ) {
		if ( null === $value ) {
			return true;
		}

		if ( is_string( $value ) ) {
			return '' === trim( $value );
		}

		return false;
	}

	/**
	 * Extract image URL from common listing payload shapes.
	 *
	 * @param object $property Property object.
	 *
	 * @return string
	 */
	private static function extract_property_image_url( $property ) {
		$direct = self::first_property_value(
			$property,
			array( 'thumbnailUrl', 'thumbnailURL', 'thumbnail', 'previewURL', 'imageUrl', 'imageURL', 'image', 'imageSrc', 'mainImage', 'mainImageUrl', 'featuredImage', 'featuredImageUrl', 'heroImage', 'heroImageUrl', 'picture', 'photo' ),
			''
		);
		$direct = self::normalize_media_url( $direct );

		if ( '' !== $direct ) {
			return $direct;
		}

		foreach ( array( 'images', 'media', 'photos', 'gallery', 'propertyImages' ) as $collection_key ) {
			if ( empty( $property->{$collection_key} ) ) {
				continue;
			}

			$found = self::extract_first_url_from_mixed( $property->{$collection_key} );
			if ( '' !== $found ) {
				return $found;
			}
		}

		return '';
	}

	/**
	 * Get fallback placeholder image used by original Apex27 plugin.
	 *
	 * @return string
	 */
	private static function fallback_property_image_url() {
		$candidate = content_url( 'plugins/apex27-wp-plugin/assets/img/property.png' );

		return esc_url_raw( $candidate );
	}

	/**
	 * Normalize image/media URL from absolute or relative source.
	 *
	 * @param string $url Raw URL.
	 *
	 * @return string
	 */
	private static function normalize_media_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			return 'https:' . $url;
		}

		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}

		$base = self::remote_website_base_url();

		if ( 0 === strpos( $url, '/' ) ) {
			if ( '' !== $base ) {
				return untrailingslashit( $base ) . $url;
			}

			return home_url( $url );
		}

		if ( '' !== $base ) {
			return trailingslashit( $base ) . ltrim( $url, '/' );
		}

		return home_url( '/' . ltrim( $url, '/' ) );
	}

	/**
	 * Resolve configured remote website base URL used for API calls.
	 *
	 * @return string
	 */
	private static function remote_website_base_url() {
		$url = (string) get_option( 'divi_apex27_website_url', '' );

		if ( '' === trim( $url ) ) {
			$url = (string) get_option( 'apex27_website_url', '' );
		}

		return untrailingslashit( esc_url_raw( $url ) );
	}

	/**
	 * Recursively extract first URL string from mixed arrays/objects.
	 *
	 * @param mixed $value Value tree.
	 *
	 * @return string
	 */
	private static function extract_first_url_from_mixed( $value ) {
		if ( is_string( $value ) ) {
			return self::normalize_media_url( $value );
		}

		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'thumbnailUrl', 'thumbnailURL', 'url', 'src', 'href', 'large', 'medium', 'small', 'thumbnail', 'original' ) as $key ) {
			if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) ) {
				$normalized = self::normalize_media_url( $value[ $key ] );
				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}

		foreach ( $value as $child ) {
			$found = self::extract_first_url_from_mixed( $child );
			if ( '' !== $found ) {
				return $found;
			}
		}

		return '';
	}

	/**
	 * Build details URL matching the original Apex27 template.
	 *
	 * @param object $property Property object.
	 *
	 * @return string
	 */
	private static function property_url( $property ) {
		$direct_url = self::first_property_value( $property, array( 'url', 'link', 'detailUrl', 'detailURL', 'detailsUrl', 'detailsURL', 'propertyUrl', 'propertyURL' ), '' );

		if ( '' !== trim( $direct_url ) ) {
			return (string) $direct_url;
		}

		$id = self::first_property_value( $property, array( 'id', 'listingId', 'listing_id', 'propertyId', 'property_id' ), '' );

		$route = self::first_property_value( $property, array( 'transactionTypeRoute', 'transaction_type_route', 'route' ), '' );

		if ( '' === trim( $route ) ) {
			$transaction_type = self::first_property_value( $property, array( 'transactionType', 'transaction_type', 'type', 'listingType', 'listing_type' ), '' );
			$route            = self::transaction_type_route_slug( $transaction_type );
		}

		if ( '' === trim( $id ) || '' === trim( $route ) ) {
			return '#';
		}

		$address = self::first_property_value( $property, array( 'displayAddress', 'display_address', 'address', 'fullAddress', 'full_address', 'title', 'name' ), 'no address' );
		$address = function_exists( 'mb_strtolower' ) ? mb_strtolower( $address ) : strtolower( $address );
		$slug    = preg_replace( '/[-]+/', '-', preg_replace( '/\W/u', '-', $address ) );

		return home_url( sprintf( '/property-details/%s/%s/%d', $route, $slug, absint( $id ) ) );
	}

	/**
	 * Convert transaction type into the route slug used by property details pages.
	 *
	 * @param string $transaction_type Transaction type.
	 *
	 * @return string
	 */
	private static function transaction_type_route_slug( $transaction_type ) {
		$transaction_type = strtolower( trim( (string) $transaction_type ) );

		$map = array(
			'sale'               => 'sales',
			'sales'              => 'sales',
			'rent'               => 'lettings',
			'lettings'           => 'lettings',
			'new_homes'          => 'new-homes',
			'new-homes'          => 'new-homes',
			'land'               => 'land',
			'commercial_sale'    => 'commercial-sales',
			'commercial-sales'   => 'commercial-sales',
			'commercial_rent'    => 'commercial-lettings',
			'commercial-lettings'=> 'commercial-lettings',
		);

		return $map[ $transaction_type ] ?? '';
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
