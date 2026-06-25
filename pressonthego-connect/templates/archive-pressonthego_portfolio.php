<?php
defined( 'ABSPATH' ) || exit;

get_header();

$service_slug = sanitize_text_field( $_GET['service'] ?? '' );
$base_url     = get_post_type_archive_link( PRESSONTHEGO_CPT_PORTFOLIO );
$services     = get_terms( [ 'taxonomy' => PRESSONTHEGO_TAX_SERVICE, 'hide_empty' => true ] );
?>

<div style="max-width:1100px;margin:2rem auto;padding:0 1rem;">

	<h1 style="margin:0 0 1.5rem;"><?php post_type_archive_title(); ?></h1>

	<?php // ── Services filter ───────────────────────────────────────────── ?>
	<?php if ( $services && ! is_wp_error( $services ) ) : ?>
	<div style="margin-bottom:1.75rem;display:flex;flex-wrap:wrap;gap:.375rem;">
		<?php
		$all_active = $service_slug === '';
		$all_url    = esc_url( remove_query_arg( 'service', $base_url ) );
		?>
		<a href="<?php echo $all_url; ?>"
		   style="padding:.375rem .875rem;border:1px solid <?php echo $all_active ? '#185FA5' : '#ccc'; ?>;border-radius:4px;text-decoration:none;font-size:.875rem;background:<?php echo $all_active ? '#185FA5' : '#fff'; ?>;color:<?php echo $all_active ? '#fff' : '#333'; ?>;">
			All
		</a>
		<?php foreach ( $services as $term ) :
			$active = $service_slug === $term->slug;
			$url    = esc_url( add_query_arg( 'service', $term->slug, $base_url ) );
		?>
		<a href="<?php echo $url; ?>"
		   style="padding:.375rem .875rem;border:1px solid <?php echo $active ? '#185FA5' : '#ccc'; ?>;border-radius:4px;text-decoration:none;font-size:.875rem;background:<?php echo $active ? '#185FA5' : '#fff'; ?>;color:<?php echo $active ? '#fff' : '#333'; ?>;">
			<?php echo esc_html( $term->name ); ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;">
			<?php while ( have_posts() ) : the_post();
				$client   = get_post_meta( get_the_ID(), '_PortfolioClient', true );
				$proj_url = get_post_meta( get_the_ID(), '_PortfolioURL', true );
				$terms    = get_the_terms( get_the_ID(), PRESSONTHEGO_TAX_SERVICE ) ?: [];
			?>
			<article style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;display:flex;flex-direction:column;">

				<?php if ( has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" style="display:block;line-height:0;overflow:hidden;aspect-ratio:16/9;background:#f3f4f6;">
					<?php the_post_thumbnail( 'medium_large', [ 'style' => 'width:100%;height:100%;object-fit:cover;' ] ); ?>
				</a>
				<?php endif; ?>

				<div style="padding:1.125rem;flex:1;display:flex;flex-direction:column;">

					<?php if ( $terms ) : ?>
					<div style="margin-bottom:.5rem;display:flex;flex-wrap:wrap;gap:.25rem;">
						<?php foreach ( $terms as $term ) : ?>
						<span style="background:#e6f1fb;color:#185FA5;font-size:.6875rem;font-weight:600;padding:.15rem .5rem;border-radius:3px;">
							<?php echo esc_html( $term->name ); ?>
						</span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<h2 style="margin:0 0 .375rem;font-size:1rem;font-weight:600;line-height:1.3;">
						<a href="<?php the_permalink(); ?>" style="text-decoration:none;color:#111;"><?php the_title(); ?></a>
					</h2>

					<?php if ( $client ) : ?>
					<p style="margin:0 0 .5rem;font-size:.8125rem;color:#6b7280;"><?php echo esc_html( $client ); ?></p>
					<?php endif; ?>

					<?php if ( has_excerpt() ) : ?>
					<p style="margin:0;font-size:.875rem;color:#4b5563;flex:1;"><?php the_excerpt(); ?></p>
					<?php endif; ?>

					<?php if ( $proj_url ) : ?>
					<a href="<?php echo esc_url( $proj_url ); ?>" target="_blank" rel="noopener noreferrer"
					   style="margin-top:.875rem;display:inline-block;font-size:.8125rem;color:#185FA5;text-decoration:none;">
						View project &rarr;
					</a>
					<?php endif; ?>

				</div>
			</article>
			<?php endwhile; ?>
		</div>

		<div style="margin-top:2rem;">
			<?php the_posts_pagination( [ 'prev_text' => '&larr; Previous', 'next_text' => 'Next &rarr;' ] ); ?>
		</div>

	<?php else : ?>
		<p style="color:#6b7280;">No portfolio items found.</p>
	<?php endif; ?>

</div>

<?php get_footer();
