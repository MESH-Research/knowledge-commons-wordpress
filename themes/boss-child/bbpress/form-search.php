<?php

/**
 * Search 
 *
 * @package bbPress
 * @subpackage Theme
 */

$forum_id = bbp_get_forum_id();
$group_slug = bp_get_current_group_slug();
?>

<form role="search" method="get" id="bbp-search-form" action="/groups/<?php echo $group_slug ?>/forum/search-forum/">
	<div>
		<label class="screen-reader-text hidden" for="bbp_search"><?php _e( 'Search for:', 'bbpress' ); ?></label>
		<!--<input type="hidden" name="action" value="bbp-search-request" />-->

		<?php if( $forum_id ): ?>
                    <input type="hidden" name="bbp_search_forum_id" value="<?php echo $forum_id; ?>" />
                <?php endif; ?>
                 
                <?php if( $group_slug ): ?>
                    <input type="hidden" name="bbp_search_group_slug" value="<?php echo $group_slug; ?>" />
                <?php endif; ?> 


		<input tabindex="<?php bbp_tab_index(); ?>" type="text" value="<?php echo esc_attr( bbp_get_search_terms() ); ?>" name="bbp_search" id="bbp_search" />
                <input tabindex="<?php bbp_tab_index(); ?>" class="button" type="submit" id="bbp_search_submit" value="<?php esc_attr_e( 'Search', 'bbpress' ); ?>" />

	
       </div>
</form>
