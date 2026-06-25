<?php
defined( 'ABSPATH' ) || exit;

get_header();

$when     = sanitize_key( $_GET['when'] ?? 'upcoming' );
$cat_slug = sanitize_text_field( $_GET['category'] ?? '' );
$base_url = get_post_type_archive_link( PRESSONTHEGO_CPT_EVENT );
?>

<div style="max-width:900px;margin:2rem auto;padding:0 1rem;">

	<h1 style="margin:0 0 1.5rem;"><?php post_type_archive_title(); ?></h1>

	<?php // ── Filter bar ────────────────────────────────────────────────── ?>
	<div style="margin-bottom:1.75rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">

		<?php // Upcoming / Past toggle ?>
		<div style="display:flex;gap:.375rem;">
			<?php foreach ( [ 'upcoming' => 'Upcoming', 'past' => 'Past' ] as $value => $label ) :
				$active = $when === $value;
				$url    = esc_url( add_query_arg( [ 'when' => $value, 'category' => $cat_slug ?: false ], $base_url ) );
			?>
			<a href="<?php echo $url; ?>"
			   style="padding:.375rem .875rem;border:1px solid <?php echo $active ? '#185FA5' : '#ccc'; ?>;border-radius:4px;text-decoration:none;font-size:.875rem;background:<?php echo $active ? '#185FA5' : '#fff'; ?>;color:<?php echo $active ? '#fff' : '#333'; ?>;">
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</div>

		<?php // Category filter ?>
		<?php $categories = get_terms( [ 'taxonomy' => PRESSONTHEGO_TAX_EVENT_CAT, 'hide_empty' => true ] );
		if ( $categories && ! is_wp_error( $categories ) ) : ?>
		<select onchange="window.location=this.value"
		        style="padding:.375rem .75rem;border:1px solid #ccc;border-radius:4px;font-size:.875rem;background:#fff;color:#333;">
			<option value="<?php echo esc_url( add_query_arg( [ 'when' => $when, 'category' => false ], $base_url ) ); ?>"
			        <?php selected( $cat_slug, '' ); ?>>
				All categories
			</option>
			<?php foreach ( $categories as $term ) : ?>
			<option value="<?php echo esc_url( add_query_arg( [ 'when' => $when, 'category' => $term->slug ], $base_url ) ); ?>"
			        <?php selected( $cat_slug, $term->slug ); ?>>
				<?php echo esc_html( $term->name ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php endif; ?>

	</div>

	<?php if ( have_posts() ) : ?>
		<div style="display:flex;flex-direction:column;gap:1.25rem;">
			<?php while ( have_posts() ) : the_post();
				$start     = get_post_meta( get_the_ID(), '_EventStartDate', true );
				$end       = get_post_meta( get_the_ID(), '_EventEndDate', true );
				$all_day   = get_post_meta( get_the_ID(), '_EventAllDay', true ) === 'yes';
				$cost      = get_post_meta( get_the_ID(), '_EventCost', true );
				$symbol    = get_post_meta( get_the_ID(), '_EventCurrencySymbol', true ) ?: '€';
				$sym_pos   = get_post_meta( get_the_ID(), '_EventCurrencyPosition', true ) ?: 'prefix';
				$venue_id  = (int) get_post_meta( get_the_ID(), '_EventVenueID', true );
				$venue_city = $venue_id ? get_post_meta( $venue_id, '_VenueCity', true ) : '';

				if ( $cost !== '' && $cost !== null && $cost !== false ) {
					$cost_display = $cost == 0 ? 'Free' : ( $sym_pos === 'prefix' ? $symbol . $cost : $cost . $symbol );
				} else {
					$cost_display = null;
				}
			?>
			<article style="display:flex;gap:1.25rem;padding:1.25rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff;align-items:flex-start;">

				<?php if ( $start ) :
					$dt = new DateTime( $start );
				?>
				<div style="min-width:56px;text-align:center;background:#e6f1fb;border-radius:6px;padding:.5rem .75rem;flex-shrink:0;">
					<div style="font-size:1.4rem;font-weight:700;color:#185FA5;line-height:1.1;"><?php echo esc_html( $dt->format( 'j' ) ); ?></div>
					<div style="font-size:.7rem;color:#185FA5;text-transform:uppercase;font-weight:600;"><?php echo esc_html( $dt->format( 'M' ) ); ?></div>
				</div>
				<?php endif; ?>

				<div style="flex:1;min-width:0;">
					<h2 style="margin:0 0 .375rem;font-size:1.05rem;font-weight:600;">
						<a href="<?php the_permalink(); ?>" style="text-decoration:none;color:#111;"><?php the_title(); ?></a>
					</h2>

					<div style="display:flex;flex-wrap:wrap;gap:.625rem .875rem;font-size:.8125rem;color:#6b7280;margin-bottom:.5rem;">
						<?php if ( $start && ! $all_day ) : ?>
						<span><?php
							echo esc_html( (new DateTime($start))->format('H:i') );
							if ( $end ) echo ' – ' . esc_html( (new DateTime($end))->format('H:i') );
						?></span>
						<?php elseif ( $all_day ) : ?>
						<span>All day</span>
						<?php endif; ?>

						<?php if ( $venue_city ) : ?>
						<span><?php echo esc_html( $venue_city ); ?></span>
						<?php endif; ?>

						<?php if ( $cost_display !== null ) : ?>
						<span><?php echo esc_html( $cost_display ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( has_excerpt() ) : ?>
					<p style="margin:0;font-size:.875rem;color:#4b5563;"><?php the_excerpt(); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
				<div style="flex-shrink:0;">
					<?php the_post_thumbnail( 'thumbnail', [ 'style' => 'width:80px;height:80px;object-fit:cover;border-radius:6px;display:block;' ] ); ?>
				</div>
				<?php endif; ?>

			</article>
			<?php endwhile; ?>
		</div>

		<div style="margin-top:2rem;">
			<?php the_posts_pagination( [ 'prev_text' => '&larr; Previous', 'next_text' => 'Next &rarr;' ] ); ?>
		</div>

	<?php else : ?>
		<p style="color:#6b7280;">
			<?php echo $when === 'upcoming' ? 'No upcoming events.' : 'No past events.'; ?>
		</p>
	<?php endif; ?>

</div>

<?php get_footer();
