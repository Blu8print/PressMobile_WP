<?php
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$id       = get_the_ID();
	$client   = get_post_meta( $id, '_PortfolioClient', true );
	$proj_url = get_post_meta( $id, '_PortfolioURL',    true );
	$services = get_the_terms( $id, PRESSONTHEGO_TAX_SERVICE ) ?: [];
?>

<article style="max-width:800px;margin:2rem auto;padding:0 1rem;">

	<?php if ( has_post_thumbnail() ) : ?>
	<div style="margin-bottom:1.75rem;border-radius:10px;overflow:hidden;line-height:0;">
		<?php the_post_thumbnail( 'large', [ 'style' => 'width:100%;height:auto;display:block;' ] ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $services ) : ?>
	<div style="margin-bottom:.75rem;display:flex;gap:.375rem;flex-wrap:wrap;">
		<?php foreach ( $services as $term ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'service', $term->slug, get_post_type_archive_link( PRESSONTHEGO_CPT_PORTFOLIO ) ) ); ?>"
		   style="background:#e6f1fb;color:#185FA5;padding:.2rem .6rem;border-radius:4px;text-decoration:none;font-size:.75rem;font-weight:600;">
			<?php echo esc_html( $term->name ); ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<h1 style="margin:0 0 <?php echo $client ? '.375rem' : '1.25rem'; ?>;font-size:1.75rem;line-height:1.25;"><?php the_title(); ?></h1>

	<?php if ( $client ) : ?>
	<p style="margin:0 0 1.25rem;color:#6b7280;font-size:1rem;"><?php echo esc_html( $client ); ?></p>
	<?php endif; ?>

	<?php if ( $proj_url ) : ?>
	<p style="margin-bottom:1.75rem;">
		<a href="<?php echo esc_url( $proj_url ); ?>"
		   target="_blank" rel="noopener noreferrer"
		   style="display:inline-block;padding:.6rem 1.375rem;background:#185FA5;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:.9375rem;">
			View project &rarr;
		</a>
	</p>
	<?php endif; ?>

	<div class="entry-content">
		<?php the_content(); ?>
	</div>

	<div style="margin-top:2rem;padding-top:1.25rem;border-top:1px solid #e5e7eb;">
		<a href="<?php echo esc_url( get_post_type_archive_link( PRESSONTHEGO_CPT_PORTFOLIO ) ); ?>"
		   style="color:#185FA5;font-size:.875rem;text-decoration:none;">
			&larr; All portfolio items
		</a>
	</div>

</article>

<?php endwhile;

get_footer();
