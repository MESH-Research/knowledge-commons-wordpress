<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WP_Bootstrap_Starter
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<?php $blog_title = get_bloginfo(); ?>


<body <?php body_class(); ?>>
<div id="page" class="site">
    <a class="skip-link screen-reader-text"
       href="#content"><?php esc_html_e( 'Skip to content', 'wp-bootstrap-starter' ); ?></a>
	<?php if ( ! is_page_template( 'blank-page.php' ) && ! is_page_template( 'blank-page-with-container.php' ) ): ?>
    <header id="masthead" class="site-header navbar-static-top <?php echo wp_bootstrap_starter_bg_class(); ?>"
            role="banner">
        <div class="container">
            <nav class="navbar navbar-expand-xl p-0">
                <div class="navbar-brand">
					<?php if ( get_theme_mod( 'wp_bootstrap_starter_logo' ) ): ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <img src="<?php echo esc_url( get_theme_mod( 'wp_bootstrap_starter_logo' ) ); ?>"
                                 alt="<?php echo esc_attr( $blog_title ); ?>">
                        </a>
					<?php else : ?>
                        <a class="site-title"
                           href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_url( bloginfo( 'name' ) ); ?></a>
					<?php endif; ?>

                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-nav"
                        aria-controls="" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

				<?php
				wp_nav_menu( array(
					'theme_location'  => 'primary',
					'container'       => 'div',
					'container_id'    => 'main-nav',
					'container_class' => 'collapse navbar-collapse justify-content-end',
					'menu_id'         => false,
					'menu_class'      => 'navbar-nav',
					'depth'           => 3,
					'fallback_cb'     => 'wp_bootstrap_navwalker::fallback',
					'walker'          => new wp_bootstrap_navwalker()
				) );
				?>

            </nav>
        </div>
    </header><!-- #masthead -->
	<?php if ( is_front_page() ): ?>
        <div id="page-sub-header">
            <div class="container">
                <div class="row">


                    <!-- Course Card -->
                    <div class="course-card col-md-8"
					     <?php if ( has_header_image() ) { ?>style="background-image: url('<?php header_image(); ?>');" <?php } ?> >
						<?php
						$blade = $_SESSION['blade'][0];
						echo $blade->make( 'header_card',
							[
								'title' => $blog_title,
								'name'  => get_theme_mod( 'header_banner_title_setting' ),
								'email' => get_theme_mod( 'header_banner_tagline_setting' )
							]
						)->render();
						?>
                        <h4 class="label">Course Description</h4>
                        <div class="course-card--course-description">
							<?php if ( have_posts() ) : while ( have_posts() ) : the_post();
								the_content();
							endwhile;
							else:
							endif; ?>
                        </div>
                    </div>
                    <!-- /Course Card -->


                    <div class="calendar-list col-md-4 pl-md-5 pt-sm-5 pt-md-0 mt-0">

						<?php dynamic_sidebar( 'front-page-sidebar' ); ?>
                    </div>

                </div>

            </div>
        </div>
	<?php endif; ?>
    <div id="content" class="site-content <?php echo get_post_type(); ?>">
		<?php endif; ?>
