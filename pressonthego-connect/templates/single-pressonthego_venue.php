<?php
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$id      = get_the_ID();
	$address = get_post_meta( $id, '_VenueAddress',       true );
	$city    = get_post_meta( $id, '_VenueCity',          true );
	$zip     = get_post_meta( $id, '_VenueZip',           true );
	$state   = get_post_meta( $id, '_VenueStateProvince', true );
	$country = get_post_meta( $id, '_VenueCountry',       true );
	$phone   = get_post_meta( $id, '_VenuePhone',         true );
	$url     = get_post_meta( $id, '_VenueURL',           true );
	$lat     = (float) get_post_meta( $id, '_VenueLat',   true );
	$lng     = (float) get_post_meta( $id, '_VenueLng',   true );

	// Upcoming events at this venue.
	$upcoming = get_posts( [
		'post_type'      => PRESSONTHEGO_CPT_EVENT,
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'meta_query'     => [
			[
				'key'     => '_EventVenueID',
				'value'   => $id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			],
			[
				'key'     => '_EventStartDate',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
		],
		'meta_key' => '_EventStartDate',
		'orderby'  => 'meta_value',
		'order'    => 'ASC',
	] );
?>

<article style="max-width:800px;margin:2rem auto;padding:0 1rem;">

	<?php if ( has_post_thumbnail() ) : ?>
	<div style="margin-bottom:1.75rem;border-radius:10px;overflow:hidden;line-height:0;">
		<?php the_post_thumbnail( 'large', [ 'style' => 'width:100%;height:auto;display:block;' ] ); ?>
	</div>
	<?php endif; ?>

	<h1 style="margin:0 0 1.25rem;font-size:1.75rem;line-height:1.25;"><?php the_title(); ?></h1>

	<?php // ── Venue details ─────────────────────────────────────────────── ?>
	<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:1.25rem;margin-bottom:1.75rem;">

		<?php if ( $address || $city ) : ?>
		<div style="margin-bottom:.875rem;">
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Address</div>
			<div style="color:#111;">
				<?php
				$parts = array_filter( [ $address, $city, $zip, $state, $country ] );
				echo esc_html( implode( ', ', $parts ) );
				?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $phone ) : ?>
		<div style="margin-bottom:.875rem;">
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Phone</div>
			<a href="tel:<?php echo esc_attr( $phone ); ?>" style="color:#185FA5;text-decoration:none;"><?php echo esc_html( $phone ); ?></a>
		</div>
		<?php endif; ?>

		<?php if ( $url ) : ?>
		<div style="margin-bottom:.875rem;">
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Website</div>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" style="color:#185FA5;text-decoration:none;">
				<?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $url, '/' ) ) ); ?>
			</a>
		</div>
		<?php endif; ?>

		<?php if ( $lat && $lng ) : ?>
		<div>
			<a href="<?php echo esc_url( 'https://www.google.com/maps?q=' . $lat . ',' . $lng ); ?>"
			   target="_blank" rel="noopener noreferrer"
			   style="color:#185FA5;font-size:.875rem;text-decoration:none;">
				View on Google Maps &rarr;
			</a>
		</div>
		<?php endif; ?>

	</div>

	<?php if ( get_the_content() ) : ?>
	<div class="entry-content" style="margin-bottom:2rem;">
		<?php the_content(); ?>
	</div>
	<?php endif; ?>

	<?php // ── Upcoming events ────────────────────────────────────────────── ?>
	<?php if ( $upcoming ) : ?>
	<h2 style="font-size:1.2rem;margin:0 0 1rem;padding-top:1.25rem;border-top:1px solid #e5e7eb;">Upcoming events here</h2>
	<div style="display:flex;flex-direction:column;gap:.75rem;">
		<?php foreach ( $upcoming as $event ) :
			$e_start  = get_post_meta( $event->ID, '_EventStartDate', true );
			$e_all_day = get_post_meta( $event->ID, '_EventAllDay', true ) === 'yes';
		?>
		<a href="<?php echo esc_url( get_permalink( $event ) ); ?>"
		   style="display:flex;gap:1rem;padding:.875rem 1rem;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:inherit;background:#fff;align-items:center;">

			<?php if ( $e_start ) :
				$dt = new DateTime( $e_start );
			?>
			<div style="min-width:48px;text-align:center;background:#e6f1fb;border-radius:5px;padding:.375rem .5rem;flex-shrink:0;">
				<div style="font-size:1.2rem;font-weight:700;color:#185FA5;line-height:1.1;"><?php echo esc_html( $dt->format( 'j' ) ); ?></div>
				<div style="font-size:.65rem;color:#185FA5;text-transform:uppercase;font-weight:600;"><?php echo esc_html( $dt->format( 'M' ) ); ?></div>
			</div>
			<?php endif; ?>

			<div>
				<div style="font-weight:600;color:#111;"><?php echo esc_html( get_the_title( $event ) ); ?></div>
				<?php if ( $e_start && ! $e_all_day ) : ?>
				<div style="font-size:.8125rem;color:#6b7280;"><?php echo esc_html( (new DateTime($e_start))->format('H:i') ); ?></div>
				<?php endif; ?>
			</div>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div style="margin-top:2rem;padding-top:1.25rem;border-top:1px solid #e5e7eb;">
		<a href="<?php echo esc_url( get_post_type_archive_link( PRESSONTHEGO_CPT_EVENT ) ); ?>"
		   style="color:#185FA5;font-size:.875rem;text-decoration:none;">
			&larr; All events
		</a>
	</div>

</article>

<?php endwhile;

get_footer();
