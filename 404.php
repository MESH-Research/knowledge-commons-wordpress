<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap cf">

					<div id="main" class="m-all t-2of3 d-5of7 cf" role="main">

						<article id="post-not-found" class="hentry cf">

							<header class="article-header">

								<h1><?php _e( '404 - Article Not Found', 'cbox-mla-blog' ); ?></h1>

							</header>

							<section class="entry-content">

								<p><?php _e( 'The article you were looking for was not found. Maybe try searching for it.', 'cbox-mla-blog' ); ?></p>

							</section>

							<section class="search">

									<p><?php get_search_form(); ?></p>

							</section>

						</article>

					</div>

				</div>

			</div>

<?php get_footer(); ?>
