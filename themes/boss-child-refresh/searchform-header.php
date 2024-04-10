<?php
/**
 * The template for displaying search forms in Boss
 *
 * @package Boss
 */
?>
<div id="search-modal-wrapper">
    <button aria-label="Open Search" data-custom-open="modal-1" role="button"><span class="fa fa-search"></p></button>
    <div class="modal" id="modal-1" aria-hidden="true">
        <div tabindex="-1" data-micromodal-close>
            <div aria-label="Menu" aria-modal="true" role="dialog">
                <div id="modal-1-content">
                    <button id="modal-1-close" aria-label="Close Search" data-micromodal-close>
                        <span class="fa fa-close"></span> 
                    </button>
                    <form role="search" method="get" id="searchform" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="search-wrapper">
                            <label class="screen-reader-text" for="s"><?php _e( 'Search for:', 'boss' ); ?></label>
                            <input type="text" value="" name="s" id="s" placeholder="Search">
                            <button type="submit" id="searchsubmit" title="<?php _e( 'Search', 'boss' ); ?>"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

