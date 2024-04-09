<?php
/**
 * The template for displaying all single posts/lessons
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WP_Bootstrap_Starter
 */

get_header(); ?>

<div class="container-fluid <?php echo get_post_type(); ?>--header">
	<div class="row">
		<div class="mx-auto col-10">
			<?php echo $_SESSION['blade'][0]->make('components/cpt-header'); ?>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">

	<section id="primary" class="content-area col-sm-12 col-lg-8">
		<main id="main" class="site-main" role="main">

		<?php
		while ( have_posts() ) : the_post();

			get_template_part( 'template-parts/content', get_post_format() );

		endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</section><!-- #primary -->

	<aside class="col-sm-12 col-lg-4">

			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				 comments_template();
		 	endif;
			?>

	</aside>
<?php
get_footer();
