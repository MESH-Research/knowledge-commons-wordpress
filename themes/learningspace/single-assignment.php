<?php
/**
 * The template for displaying single assignments
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WP_Bootstrap_Starter
 */

get_header(); ?>

<div class="container-fluid <?php echo get_post_type(); ?>--header">
	<div class="row">
		<div class="mx-auto col-10">
			<?php echo blade()->make('components/cpt-header'); ?>
		</div>
	</div>
</div>

<div class="container">
	<div class="row">

	<section id="primary" class="content-area col-sm-12 col-lg-8 offset-lg-2">
		<main id="main" class="site-main" role="main">

		<?php
		while ( have_posts() ) : the_post();

			get_template_part( 'template-parts/content', get_post_format() );

			   // the_post_navigation();

		endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
