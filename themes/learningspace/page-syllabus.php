<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WP_Bootstrap_Starter
 */

get_header(); ?>
<div class="container">
	<div class="row">

	<section id="primary" class="content-area col-sm-12 col-lg-8">
		<main id="main" class="site-main" role="main">

			<?php
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/content', 'page' );

                // If comments are open or we have at least one comment, load up the comment template.
                if ( comments_open() || get_comments_number() ) :
                    comments_template();
                endif;

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</section><!-- #primary -->
    <aside id="secondary" class="widget-area col-sm-12 col-lg-4" role="complementary">
        <?php
            $blade = $_SESSION['blade'][0];
            echo $blade->make('header_card',
                [
                        'name'=>get_theme_mod( 'header_banner_title_setting' ),
                        'email' => get_theme_mod( 'header_banner_tagline_setting' )
                ]
            )->render();
        ?>
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
    </aside><!-- #secondary -->
<?php
get_footer();
