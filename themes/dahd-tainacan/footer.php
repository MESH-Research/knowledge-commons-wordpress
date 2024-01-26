<?php if (!is_404()): ?>
  <footer class="container-fluid p-4 p-sm-5 mt-5 tainacan-footer <?php echo ('tainacan-footer-' . get_theme_mod('tainacan_footer_color', 'dark')) ?>" style="padding-bottom: 0 !important;">
    <?php if (is_active_sidebar('tainacan-sidebar-footer')) {?>
      <div class="row tainacan-footer-widgets-area">
        <div class="col-12 col-lg">
          <ul class="pt-3 pb-3 pl-0 pr-0 d-lg-flex justify-content-xs-center mb-md-0">
            <?php dynamic_sidebar('tainacan-sidebar-footer');?>
          </ul>
        </div>
      </div>
    <?php }?>
    <hr class="bg-scooter"/>
    <div class="row pt-3 pb-4 pl-0 pr-0 tainacan-footer-info">
      <div class="col text-white font-weight-normal">
        <p class="tainacan-footer-info--blog">
          <?php echo bloginfo('title');
            if (!wp_is_mobile()) {
                echo '<br>';
            } else {
                echo '</p><p>';
            }
            if (get_theme_mod('tainacan_blogaddress')) {
                echo wp_filter_nohtml_kses(get_theme_mod('tainacan_blogaddress', ''));
            }
          ?>
          <?php if (get_theme_mod('tainacan_blogemail')) {
              printf(__('E-mail: %s', 'tainacan-interface'), sanitize_email(get_theme_mod('tainacan_blogemail', '')));
            }
            if (get_theme_mod('tainacan_blogemail') && get_theme_mod('tainacan_blogphone')) {
                if (wp_is_mobile()):
                    echo '<br>';
                else:
                    echo ' - ';
                endif;
            }
            if (get_theme_mod('tainacan_blogphone')) {
                printf(__('Telephone: %s', 'tainacan-interface'), wp_filter_nohtml_kses(get_theme_mod('tainacan_blogphone', '')));
            }
          ?>
        </p>
      </div>
    </div>
  </footer>
  <?php endif;?>
  <?php wp_footer();?>
</body>

</html>
