<?php
/**
 * @package WordPress
 * @subpackage faculty
 */
?>

<?php get_header(); ?>

    
	<div class="fac-page home">
		<div id="inside">
				
			<?php the_post(); ?>
            <?php 
                $styles = "";
                $header_class = "";
                if ( has_post_thumbnail() ){
                    $header_class = 'has-bg';
                    $styles = "background-image:url(".get_the_post_thumbnail_url().");";
                }
            ?>
            <div class="pageheader <?php echo $header_class; ?>" style="<?php echo $styles; ?>">
                <div class="headercontent">
                    <div class="section-container">
                        
                        <h2 class="title"><?php the_title(); ?></h2>
                        
                    </div>
                </div>
            </div>
            <div class="pagecontents">
                <div class="section color-1">
                    <div class="section-container">
                		<?php the_content(); ?>
			
						<?php wp_link_pages(); ?>
                    </div>
                </div>
            </div>
			
		</div>
        <?php get_footer(); ?>
    </div>
	<div id="overlay"></div>


