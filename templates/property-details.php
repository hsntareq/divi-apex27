<?php
/**
 * Standalone property details template for Divi Apex27.
 *
 * @package DiviApex27
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = isset( $GLOBALS['divi_apex27_property_details_context'] ) && is_array( $GLOBALS['divi_apex27_property_details_context'] )
	? $GLOBALS['divi_apex27_property_details_context']
	: array();

$details       = isset( $context['details'] ) && is_object( $context['details'] ) ? $context['details'] : null;
$error_message = isset( $context['error_message'] ) ? (string) $context['error_message'] : '';

$as_array = static function ( $value ) {
	return is_array( $value ) ? $value : array();
};

$get_text = static function ( $object, $field, $default = '' ) {
	if ( is_object( $object ) && isset( $object->{$field} ) && null !== $object->{$field} ) {
		return trim( (string) $object->{$field} );
	}

	return $default;
};

$get_int = static function ( $object, $field ) {
	if ( is_object( $object ) && isset( $object->{$field} ) && '' !== trim( (string) $object->{$field} ) ) {
		return (int) $object->{$field};
	}

	return 0;
};

$description = $details ? $get_text( $details, 'description' ) : '';
$lines       = array_values( array_filter( array_map( 'trim', explode( "\n", $description ) ) ) );

get_header();
?>
<div class="divi-apex27-details">
	<div class="divi-apex27-results-wrap">
		<?php if ( ! $details ) : ?>
			<div class="divi-apex27-notice">
				<strong><?php echo esc_html__( 'Error', 'divi-apex27' ); ?></strong>
				<p><?php echo esc_html( $error_message ? $error_message : __( 'Cannot retrieve property details at this time. Please try again later.', 'divi-apex27' ) ); ?></p>
			</div>
		<?php else : ?>
			<?php
			$address      = $get_text( $details, 'displayAddress', __( 'Property Details', 'divi-apex27' ) );
			$display_price = $get_text( $details, 'displayPrice' );
			$price_prefix  = $get_text( $details, 'pricePrefix' );
			$subtitle      = $get_text( $details, 'subtitle' );
			$status        = $get_text( $details, 'imageOverlayText' );
			$banner        = $get_text( $details, 'banner' );
			$reference     = $get_text( $details, 'reference' );
			$is_commercial = ! empty( $details->isCommercial );
			$images        = $as_array( isset( $details->images ) ? $details->images : array() );
			$bullets       = $as_array( isset( $details->bullets ) ? $details->bullets : array() );
			$additional    = $as_array( isset( $details->additionalDetails ) ? $details->additionalDetails : array() );
			$features      = $as_array( isset( $details->additionalFeatures ) ? $details->additionalFeatures : array() );
			$rooms         = $as_array( isset( $details->rooms ) ? $details->rooms : array() );
			$videos        = $as_array( isset( $details->videos ) ? $details->videos : array() );
			$floorplans    = $as_array( isset( $details->floorplans ) ? $details->floorplans : array() );
			$brochures     = $as_array( isset( $details->brochures ) ? $details->brochures : array() );
			$epcs          = $as_array( isset( $details->epcs ) ? $details->epcs : array() );
			$virtual_tours = $as_array( isset( $details->virtualTours ) ? $details->virtualTours : array() );
			$gallery_images = array();

			foreach ( $images as $image ) {
				if ( ! is_object( $image ) ) {
					continue;
				}

				$image_url = isset( $image->url ) ? (string) $image->url : '';
				if ( '' === trim( $image_url ) && isset( $image->thumbnailUrl ) ) {
					$image_url = (string) $image->thumbnailUrl;
				}

				if ( '' === trim( $image_url ) ) {
					continue;
				}

				$gallery_images[] = array(
					'url' => $image_url,
					'alt' => isset( $image->name ) ? (string) $image->name : $address,
				);
			}

			$gallery_count = count( $gallery_images );
			$slider_id     = 'divi-apex27-slider-' . absint( $context['listing_id'] ?? 0 );
			?>
			<header class="divi-apex27-details-header">
				<h1><?php echo esc_html( $address ); ?></h1>
				<div class="divi-apex27-card-meta">
					<?php if ( '' !== $status ) : ?>
						<span class="divi-apex27-card-status"><?php echo esc_html( $status ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $banner ) : ?>
						<span class="divi-apex27-card-banner"><?php echo esc_html( $banner ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $display_price ) : ?>
					<div class="divi-apex27-card-price">
						<?php echo esc_html( $display_price ); ?>
						<?php if ( '' !== $price_prefix ) : ?>
							<small><?php echo esc_html( $price_prefix ); ?></small>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $subtitle ) : ?>
					<p><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
				<?php if ( ! $is_commercial ) : ?>
					<p class="divi-apex27-details-stats">
						<?php
						$bedrooms   = $get_int( $details, 'bedrooms' );
						$bathrooms  = $get_int( $details, 'bathrooms' );
						$living     = $get_int( $details, 'livingRooms' );
						$garages    = $get_int( $details, 'garages' );
						$stats      = array();
						if ( $bedrooms > 0 ) {
							$stats[] = sprintf( __( '%d beds', 'divi-apex27' ), $bedrooms );
						}
						if ( $bathrooms > 0 ) {
							$stats[] = sprintf( __( '%d baths', 'divi-apex27' ), $bathrooms );
						}
						if ( $living > 0 ) {
							$stats[] = sprintf( __( '%d receptions', 'divi-apex27' ), $living );
						}
						if ( $garages > 0 ) {
							$stats[] = sprintf( __( '%d garages', 'divi-apex27' ), $garages );
						}
						echo esc_html( implode( ' | ', $stats ) );
						?>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( $gallery_count > 0 ) : ?>
				<section class="divi-apex27-details-gallery" data-apex27-slider id="<?php echo esc_attr( $slider_id ); ?>">
					<div class="divi-apex27-details-gallery-track">
						<?php foreach ( $gallery_images as $index => $slide ) : ?>
							<figure class="divi-apex27-details-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-slide-index="<?php echo esc_attr( (string) $index ); ?>">
								<img src="<?php echo esc_url( $slide['url'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" loading="lazy" />
							</figure>
						<?php endforeach; ?>
					</div>

					<?php if ( $gallery_count > 1 ) : ?>
						<button type="button" class="divi-apex27-slider-btn is-prev" data-slider-nav="prev" aria-label="<?php echo esc_attr__( 'Previous image', 'divi-apex27' ); ?>">&#10094;</button>
						<button type="button" class="divi-apex27-slider-btn is-next" data-slider-nav="next" aria-label="<?php echo esc_attr__( 'Next image', 'divi-apex27' ); ?>">&#10095;</button>
						<div class="divi-apex27-slider-thumbs" role="tablist" aria-label="<?php echo esc_attr__( 'Property images', 'divi-apex27' ); ?>">
							<?php foreach ( $gallery_images as $index => $slide ) : ?>
								<button
									type="button"
									class="divi-apex27-slider-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
									data-slider-thumb="<?php echo esc_attr( (string) $index ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Show image %d', 'divi-apex27' ), $index + 1 ) ); ?>"
									aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								>
									<img src="<?php echo esc_url( $slide['url'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" loading="lazy" />
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<section class="divi-apex27-details-body">
				<div class="divi-apex27-details-main">
					<h2><?php echo esc_html__( 'Description', 'divi-apex27' ); ?></h2>
					<?php if ( ! empty( $lines ) ) : ?>
						<?php foreach ( $lines as $line ) : ?>
							<p><?php echo esc_html( $line ); ?></p>
						<?php endforeach; ?>
					<?php else : ?>
						<p><em><?php echo esc_html__( 'No description is available for this property.', 'divi-apex27' ); ?></em></p>
					<?php endif; ?>

					<?php if ( ! empty( $rooms ) ) : ?>
						<h3><?php echo esc_html__( 'Rooms', 'divi-apex27' ); ?></h3>
						<?php foreach ( $rooms as $room ) : ?>
							<?php if ( ! is_object( $room ) ) { continue; } ?>
							<article class="divi-apex27-room">
								<strong><?php echo esc_html( isset( $room->name ) ? (string) $room->name : '' ); ?></strong>
								<?php if ( ! empty( $room->dimensions ) || ! empty( $room->feetInches ) ) : ?>
									<p><em><?php echo esc_html( trim( (string) ( $room->dimensions ?? '' ) . ' ' . (string) ( $room->feetInches ?? '' ) ) ); ?></em></p>
								<?php endif; ?>
								<?php if ( ! empty( $room->description ) ) : ?>
									<p><?php echo esc_html( (string) $room->description ); ?></p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( ! empty( $additional ) ) : ?>
						<h3><?php echo esc_html__( 'Additional Details', 'divi-apex27' ); ?></h3>
						<ul>
							<?php foreach ( $additional as $item ) : ?>
								<?php if ( ! is_object( $item ) ) { continue; } ?>
								<li>
									<strong><?php echo esc_html( isset( $item->label ) ? (string) $item->label : '' ); ?>:</strong>
									<?php echo esc_html( isset( $item->text ) ? (string) $item->text : '' ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( ! empty( $features ) ) : ?>
						<h3><?php echo esc_html__( 'Additional Features', 'divi-apex27' ); ?></h3>
						<ul>
							<?php foreach ( $features as $feature ) : ?>
								<li><?php echo esc_html( (string) $feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( '' !== $reference ) : ?>
						<p><small><?php echo esc_html__( 'Reference:', 'divi-apex27' ); ?> <?php echo esc_html( $reference ); ?></small></p>
					<?php endif; ?>
				</div>

				<aside class="divi-apex27-details-aside">
					<?php if ( ! empty( $bullets ) ) : ?>
						<h3><?php echo esc_html__( 'Features', 'divi-apex27' ); ?></h3>
						<ul>
							<?php foreach ( $bullets as $bullet ) : ?>
								<li><?php echo esc_html( (string) $bullet ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php
					$media_sections = array(
						__( 'Floorplans', 'divi-apex27' ) => $floorplans,
						__( 'EPCs', 'divi-apex27' )      => $epcs,
						__( 'Brochures', 'divi-apex27' ) => $brochures,
						__( 'Videos', 'divi-apex27' )    => $videos,
						__( 'Virtual Tours', 'divi-apex27' ) => $virtual_tours,
					);
					?>
					<?php foreach ( $media_sections as $label => $items ) : ?>
						<?php if ( empty( $items ) ) { continue; } ?>
						<h4><?php echo esc_html( $label ); ?></h4>
						<ul>
							<?php foreach ( $items as $item ) : ?>
								<?php if ( ! is_object( $item ) ) { continue; } ?>
								<?php
								$url  = isset( $item->url ) ? (string) $item->url : '';
								$name = isset( $item->name ) ? (string) $item->name : __( 'Open', 'divi-apex27' );
								?>
								<?php if ( '' !== trim( $url ) ) : ?>
									<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $name ); ?></a></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</aside>
			</section>

			<?php if ( ! empty( $details->hasGeolocation ) && ! empty( $details->mapEmbedUrl ) ) : ?>
				<section>
					<h3><?php echo esc_html__( 'Map', 'divi-apex27' ); ?></h3>
					<iframe style="border:0; width:100%; min-height:360px;" src="<?php echo esc_url( (string) $details->mapEmbedUrl ); ?>" loading="lazy" allowfullscreen></iframe>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $details->hasPov ) && ! empty( $details->streetViewEmbedUrl ) ) : ?>
				<section>
					<h3><?php echo esc_html__( 'Street View', 'divi-apex27' ); ?></h3>
					<iframe style="border:0; width:100%; min-height:360px;" src="<?php echo esc_url( (string) $details->streetViewEmbedUrl ); ?>" loading="lazy" allowfullscreen></iframe>
				</section>
			<?php endif; ?>

			<?php if ( $gallery_count > 1 ) : ?>
				<script>
				(function () {
					var slider = document.getElementById(<?php echo wp_json_encode( $slider_id ); ?>);
					if (!slider) {
						return;
					}

					var slides = Array.prototype.slice.call(slider.querySelectorAll('.divi-apex27-details-slide'));
					var thumbs = Array.prototype.slice.call(slider.querySelectorAll('.divi-apex27-slider-thumb'));
					var prev = slider.querySelector('[data-slider-nav="prev"]');
					var next = slider.querySelector('[data-slider-nav="next"]');
					var current = 0;

					var setActive = function (index) {
						current = (index + slides.length) % slides.length;
						slides.forEach(function (slide, i) {
							slide.classList.toggle('is-active', i === current);
						});

						thumbs.forEach(function (thumb, i) {
							var active = i === current;
							thumb.classList.toggle('is-active', active);
							thumb.setAttribute('aria-selected', active ? 'true' : 'false');
						});
					};

					if (prev) {
						prev.addEventListener('click', function () {
							setActive(current - 1);
						});
					}

					if (next) {
						next.addEventListener('click', function () {
							setActive(current + 1);
						});
					}

					thumbs.forEach(function (thumb) {
						thumb.addEventListener('click', function () {
							var target = parseInt(thumb.getAttribute('data-slider-thumb'), 10);
							if (!isNaN(target)) {
								setActive(target);
							}
						});
					});

					slider.addEventListener('keydown', function (event) {
						if (event.key === 'ArrowLeft') {
							event.preventDefault();
							setActive(current - 1);
						}
						if (event.key === 'ArrowRight') {
							event.preventDefault();
							setActive(current + 1);
						}
					});

					slider.setAttribute('tabindex', '0');
					setActive(0);
				})();
				</script>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
