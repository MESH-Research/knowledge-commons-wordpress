<?php
/** Front page template
 */

get_header(); ?>
<div class="container">
	<div class="row">

		<div class="sub-banner col-12">
			<p>Course Materials</p>
		</div>

	<section id="primary" class="content-area col-sm-12 col-lg-8">
		<main id="main" class="site-main" role="main">

			<?php dynamic_sidebar( 'front-page-bottom-left' ); ?>

		</main><!-- #main -->
	</section><!-- #primary -->
    <aside id="secondary" class="widget-area col-sm-12 col-lg-4 pl-md-5 pt-sm-5 pt-md-0 mt-0" role="complementary">
		<?php dynamic_sidebar( 'front-page-bottom-right' ); ?>
    </aside><!-- #secondary -->
<?php
//get_sidebar();
get_footer();
