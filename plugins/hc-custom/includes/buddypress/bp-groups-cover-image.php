<?php
/**
 * Disables BuddyPress group cover image uploads.
 *
 * The active theme no longer supports group cover images, so the upload UI
 * must be removed from the group creation wizard and the Manage > Cover
 * Image settings screen. BuddyPress gates both behind
 * bp_group_use_cover_image_header(), which returns false when the
 * bp_disable_group_cover_image_uploads filter returns true.
 *
 * @package Hc_Custom
 */

/**
 * Forces group cover image uploads to be treated as disabled.
 *
 * @param bool $disabled Whether cover image uploads are disabled by option.
 *
 * @return bool Always true: cover image uploads are unsupported.
 */
function hcommons_disable_group_cover_image_uploads( $disabled = false ) {
	return true;
}
add_filter( 'bp_disable_group_cover_image_uploads', 'hcommons_disable_group_cover_image_uploads' );
