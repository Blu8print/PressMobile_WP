<?php
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$id        = get_the_ID();
	$start     = get_post_meta( $id, '_EventStartDate',        true );
	$end       = get_post_meta( $id, '_EventEndDate',          true );
	$all_day   = get_post_meta( $id, '_EventAllDay',           true ) === 'yes';
	$event_url = get_post_meta( $id, '_EventURL',              true );
	$cost      = get_post_meta( $id, '_EventCost',             true );
	$symbol    = get_post_meta( $id, '_EventCurrencySymbol',   true ) ?: '€';
	$sym_pos   = get_post_meta( $id, '_EventCurrencyPosition', true ) ?: 'prefix';
	$show_map  = get_post_meta( $id, '_EventShowMap',          true ) === 'TRUE';
	$map_link  = get_post_meta( $id, '_EventShowMapLink',      true ) === 'TRUE';
	$venue_id  = (int) get_post_meta( $id, '_EventVenueID',     true );
	$org_id    = (int) get_post_meta( $id, '_EventOrganizerID', true );

	if ( $cost !== '' && $cost !== null && $cost !== false ) {
		$cost_display = $cost == 0 ? 'Free' : ( $sym_pos === 'prefix' ? $symbol . $cost : $cost . $symbol );
	} else {
		$cost_display = null;
	}

	$venue_name    = $venue_id ? get_the_title( $venue_id ) : '';
	$venue_address = $venue_id ? get_post_meta( $venue_id, '_VenueAddress',  true ) : '';
	$venue_city    = $venue_id ? get_post_meta( $venue_id, '_VenueCity',     true ) : '';
	$venue_zip     = $venue_id ? get_post_meta( $venue_id, '_VenueZip',      true ) : '';
	$venue_country = $venue_id ? get_post_meta( $venue_id, '_VenueCountry',  true ) : '';
	$venue_lat     = $venue_id ? (float) get_post_meta( $venue_id, '_VenueLat', true ) : 0;
	$venue_lng     = $venue_id ? (float) get_post_meta( $venue_id, '_VenueLng', true ) : 0;

	$org_name    = $org_id ? get_the_title( $org_id ) : '';
	$org_email   = $org_id ? get_post_meta( $org_id, '_OrganizerEmail',   true ) : '';
	$org_website = $org_id ? get_post_meta( $org_id, '_OrganizerWebsite', true ) : '';
	$org_phone   = $org_id ? get_post_meta( $org_id, '_OrganizerPhone',   true ) : '';

	$categories = get_the_terms( $id, PRESSONTHEGO_TAX_EVENT_CAT ) ?: [];
?>

<article style="max-width:800px;margin:2rem auto;padding:0 1rem;">

	<?php if ( has_post_thumbnail() ) : ?>
	<div style="margin-bottom:1.75rem;border-radius:10px;overflow:hidden;line-height:0;">
		<?php the_post_thumbnail( 'large', [ 'style' => 'width:100%;height:auto;display:block;' ] ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $categories ) : ?>
	<div style="margin-bottom:.75rem;display:flex;gap:.375rem;flex-wrap:wrap;">
		<?php foreach ( $categories as $term ) : ?>
		<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
		   style="background:#e6f1fb;color:#185FA5;padding:.2rem .6rem;border-radius:4px;text-decoration:none;font-size:.75rem;font-weight:600;">
			<?php echo esc_html( $term->name ); ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<h1 style="margin:0 0 1.25rem;font-size:1.75rem;line-height:1.25;"><?php the_title(); ?></h1>

	<?php // ── Event details card ─────────────────────────────────────────── ?>
	<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:1.25rem;margin-bottom:1.75rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">

		<?php if ( $start ) :
			$dt_start = new DateTime( $start );
			$dt_end   = $end ? new DateTime( $end ) : null;
		?>
		<div>
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Date</div>
			<div style="font-weight:600;color:#111;"><?php echo esc_html( $dt_start->format( 'l, j F Y' ) ); ?></div>
			<?php if ( ! $all_day ) : ?>
			<div style="color:#4b5563;font-size:.875rem;"><?php
				echo esc_html( $dt_start->format( 'H:i' ) );
				if ( $dt_end ) echo ' – ' . esc_html( $dt_end->format( 'H:i' ) );
			?></div>
			<?php else : ?>
			<div style="color:#4b5563;font-size:.875rem;">All day</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $cost_display !== null ) : ?>
		<div>
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Cost</div>
			<div style="font-weight:600;color:#111;"><?php echo esc_html( $cost_display ); ?></div>
		</div>
		<?php endif; ?>

		<?php if ( $venue_name ) : ?>
		<div>
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Location</div>
			<div>
				<a href="<?php echo esc_url( get_permalink( $venue_id ) ); ?>" style="color:#185FA5;font-weight:600;text-decoration:none;">
					<?php echo esc_html( $venue_name ); ?>
				</a>
			</div>
			<?php if ( $venue_address || $venue_city ) : ?>
			<div style="color:#6b7280;font-size:.8125rem;">
				<?php
				$parts = array_filter( [ $venue_address, $venue_city, $venue_zip, $venue_country ] );
				echo esc_html( implode( ', ', $parts ) );
				?>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $org_name ) : ?>
		<div>
			<div style="font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.25rem;letter-spacing:.05em;">Organiser</div>
			<div>
				<a href="<?php echo esc_url( get_permalink( $org_id ) ); ?>" style="color:#185FA5;font-weight:600;text-decoration:none;">
					<?php echo esc_html( $org_name ); ?>
				</a>
			</div>
			<?php if ( $org_email ) : ?>
			<div style="font-size:.8125rem;">
				<a href="mailto:<?php echo esc_attr( $org_email ); ?>" style="color:#6b7280;"><?php echo esc_html( $org_email ); ?></a>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div>

	<?php if ( $event_url ) : ?>
	<p style="margin-bottom:1.75rem;">
		<a href="<?php echo esc_url( $event_url ); ?>"
		   target="_blank" rel="noopener noreferrer"
		   style="display:inline-block;padding:.6rem 1.375rem;background:#185FA5;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:.9375rem;">
			More info / Tickets &rarr;
		</a>
	</p>
	<?php endif; ?>

	<div class="entry-content">
		<?php the_content(); ?>
	</div>

	<?php if ( $map_link && $venue_lat && $venue_lng ) : ?>
	<p style="margin-top:1.5rem;">
		<a href="<?php echo esc_url( 'https://www.google.com/maps?q=' . $venue_lat . ',' . $venue_lng ); ?>"
		   target="_blank" rel="noopener noreferrer"
		   style="color:#185FA5;font-size:.875rem;">
			View on Google Maps &rarr;
		</a>
	</p>
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
