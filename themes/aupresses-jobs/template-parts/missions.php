<?php if (is_page('about-aupresses')) : ?>

	<div class="missions-about">
		<div class="container">
			<h3>AUPresses Mission Statement</h3>
			<ul>
			<?php
				$args = array('post_type' => 'mission', 'posts_per_page'=>'-1', 'orderby'=>'title', 'order'=>'ASC');
				$loop = new WP_Query( $args );
				while ( $loop->have_posts() ) : $loop->the_post();
			?>
				<li>
					<?php the_content(); ?>
					<span class="mission-meta"><?php echo get_the_title(); ?> | <?php echo get_field('translation_credit'); ?></span>
				</li>
			<?php endwhile;  wp_reset_postdata(); ?>
			</ul>
		</div>
	</div>

<?php else : ?>

	<div class="missions">
		<div class="container">
			<?php
				$args = array('post_type' => 'mission', 'posts_per_page'=>'1', 'orderby'=>'rand');
				$loop = new WP_Query( $args );
				while ( $loop->have_posts() ) : $loop->the_post();
					the_content();
					echo '<span class="mission-meta">&mdash; AUPresses Mission Statement in '. get_the_title() . '</span>';
				endwhile; 
				wp_reset_postdata(); 
			?>
		</div>
	</div>

<?php endif; ?>