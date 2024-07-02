<?php
/**
 * The template for displaying search forms in Boss
 *
 * @package Boss
 */

namespace MeshResearch\BossChild;

$action = apply_filters( 'kc_searchform_action', home_url() );
$query_var = apply_filters( 'kc_searchform_query_var', 's' );

?>
<form role="search" method="get" id="searchform" class="searchform" action="<?= $action ?>">
    <div class="search-wrapper">
        <label class="screen-reader-text" for="<?= $query_var ?>"><?php _e( 'Search for:', 'boss' ); ?></label>
        <input type="text" value="" name="<?= $query_var ?>" id="<?= $query_var ?>" placeholder="Search">
        <button type="submit" id="searchsubmit" title="<?php _e( 'Search', 'boss' ); ?>"><i class="fa fa-search"></i></button>
        <button id="search-close"><i class="fa fa-close"></i></button>
    </div>
</form>

