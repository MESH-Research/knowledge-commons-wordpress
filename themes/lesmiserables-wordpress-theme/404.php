<?php
/**
 * The template for displaying 404 pages (not found)
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found">
				<header class="page-header" style="height: 20em;">
                    <p class="dot">&bull;</p>
                    <p class="p-center page-title"><?php _e( 'Sorry, this page does not exist.', 'twentyfifteen' ); ?></p>
                    <p class="p-center">
                        <a href="<?php echo site_url(); ?>" class="lm_button reg">start over</a>
                    </p>
				</header>

				<div class="page-content">

				</div>
			</section>

		</main>
	</div>

<?php get_footer(); ?>
