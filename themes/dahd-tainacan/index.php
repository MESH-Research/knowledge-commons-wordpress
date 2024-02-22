<?php get_header(); ?>

<!-- Get the banner to display -->
<?php get_template_part( 'template-parts/bannerheader' ); ?>

<main class="mt-5 max-large margin-one-column">
  <div class="row">
    <div class="col-12 <?php if ( is_active_sidebar( 'tainacan-sidebar-right' ) ) { ?>col-lg-8 pr-lg-0<?php } ?>">
      <?php the_content(); ?>
    </div>
  </div>
</main>

<?php get_footer();
