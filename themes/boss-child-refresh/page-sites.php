<?php
/**
 * Template Name: Sites
 *
 * Description: Use this page template for a sites page with a right sidebar.
 */
get_header(); ?>

<?php if(is_multisite() && bp_is_current_component( 'blogs' ) && !bp_is_user()): ?>
<div class="dir-page-entry">
    <div class="inner-padding">
        <header class="group-header page-header">
            <div id="item-statistics" class="follows">
                <h1 class="main-title"><?php buddyboss_page_title(); ?></h1>
            </div><!-- #item-statistics -->
        </header><!-- .group-header -->
        <?php do_action( 'bp_before_directory_blogs_content' ); ?>
    </div>
</div>
<?php endif; ?>

<div class="page-right-sidebar">

	<div id="primary" class="site-content">
	
		<div id="content" role="main">

			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'content', 'buddypress' ); ?>
				<?php comments_template( '', true ); ?>
			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->
	
    <?php 
		if ( is_active_sidebar( 'sidebar-sites' ) ) :
			?>
			<div id="secondary" class="widget-area" role="complementary">
				<aside id="sidebar-sites" role="complementary">
					<?php dynamic_sidebar( 'sidebar-sites' ); ?>
				</aside>
			</div>
			<?php
		endif;
	?>
</div>
<?php get_footer(); ?>
