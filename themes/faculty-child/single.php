

	<?php get_header(); ?>

	    <div class="" data-pos="home" data-url="<?php the_permalink(); ?>">
	    	<a id="hideshow" href="#"><i class="fa fa-chevron-circle-right"></i><span><?php _e( 'List', 'faculty' ); ?></span></a>
			<div id="blog-content">



			    <div class="inner-wrapper" id="ajax-single-post">
					<!-- here will be populated with the single post content -->
			    
					<?php the_post(); ?>
					
					<?php get_template_part( 'template-single-post' ); ?>

				</div>
				<?php get_footer(); ?>

			</div>

			<div id="blog-side">
	        	
	        	<div class="archive-header" id="archive-header">
					
					<h3 class="archive-title"><i class="fa-quote-right fa"></i>&nbsp;&nbsp;<?php _e( 'Posts', 'faculty' ); ?></h3>
					
				</div>

				<div id="postlist">
					<?php get_template_part( 'loop' ); ?>
	        	</div>
	        </div>

		</div>
	    <div id="overlay"></div>
	</div>





