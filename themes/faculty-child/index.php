<?php
/**
 * @package WordPress
 * @subpackage faculty
 */
?>


<?php get_header(); ?>


    <div class="" data-pos="home" data-url="<?php the_permalink(); ?>">

        <a id="hideshow" href="#"><i class="fa fa-chevron-circle-right"></i><span><?php _e( 'List', 'faculty' ); ?></span></a>
        <div id="blog-content">
            <div class="inner-wrapper" id="ajax-single-post">
                
                <!-- get the latest post content based on page type -->
                <?php 
                $query_args = array(
                    "posts_per_page" => 1,
                    "ignore_sticky_posts" => true
                );
                if ( is_category() ){
                    $query_args['cat'] = $cat; 
                } elseif ( is_tag() ) {
                    $query_args['tag'] = get_query_var('tag'); 
                } elseif(is_year() ) {
                    $query_args['date_query'] = array(
                        array(
                            'year'  => get_query_var('year'),
                        ),
                    );
                } elseif(is_month() ) {
                    $query_args['date_query'] = array(
                        array(
                            'year'  => get_query_var('year'),
                            'month' => get_query_var('monthnum'),
                        ),
                    );
                } elseif(is_day() ) {
                    $query_args['date_query'] = array(
                        array(
                            'year'  => get_query_var('year'),
                            'month' => get_query_var('monthnum'),
                            'day'   => get_query_var('day'),
                        ),
                    );
                } elseif(is_author() ){
                    $query_args['author'] = get_query_var('author');
                }else{
                    $query_args['ignore_sticky_posts'] = false; 
                }

                $my_query = new WP_Query($query_args);
                if ( $my_query->have_posts() ) {
                    while ($my_query->have_posts()) : 
                        $my_query->the_post();
                        if ( (int) $my_query->current_post === 0 ) {
                            global $withcomments; $withcomments = true;
                            get_template_part( 'template-single-post' );
                        } 
                    endwhile; 
                }
                wp_reset_query();

                ?>
	            <?php get_footer(); ?>
            </div>

        </div>

        <div id="blog-side">
            <div class="archive-header" id="archive-header">
                
                <h3 class="archive-title">

                    <?php if( is_archive() ): ?>

                        <?php if (is_category()) :?>
                            <i class="fa fa-folder-o"></i>
                        <?php elseif (is_author()): ?>
                            <i class="fa fa-pencil-square-o"></i>
                        <?php elseif( is_tag() ): ?>
                            <i class="fa fa-tag"></i>
                        <?php elseif ( is_date() ): ?>
                            <i class="fa fa-clock-o"></i>
                        <?php endif; ?>

                        &nbsp;&nbsp;<?php echo get_the_archive_title(); ?>

                    <?php  else: ?>
                        <i class="fa-quote-right fa"></i>&nbsp;&nbsp;<?php _e( 'Posts', 'faculty' ); ?>
                    <?php endif; ?>
                
                </h3>

            </div>

            <div id="postlist">

                
                <?php get_template_part( 'loop' ); ?>

            </div>
            

        </div>
    </div>

    <div id="overlay"></div>

<?php get_footer(); ?>
<script>
    jQuery(window).trigger('blogdecide');
</script>
