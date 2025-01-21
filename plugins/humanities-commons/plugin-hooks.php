<?php
/**
 * Functions to alter the behavior of Commons plugins site-wide.
 */

/**
 * Removes the 'SimpleMag theme is deactivated, please also deactivate the SimpleMag Addons plugin.'
 * alert from the dashboard.
 * 
 * We keep the plugin activated network-wide so that users of the SimpleMag theme don't have to activate
 * it themselves.
 *
 * @see plugins/simplemag-addons/init.php
 * @see https://github.com/MESH-Research/hc-admin-docs-support/issues/114
 */
function hc_suppress_simplemag_alert() {
	remove_action( 'admin_notices', 'simplemag_deactivated_admin_notice', 999 );
}
add_action( 'admin_notices', 'hc_suppress_simplemag_alert', 10, 0 );

/**
 * Changes the base directory for Poseidon fonts to the EFS mount point.
 * 
 * This attempts to get around a bug where the Poseidon theme tries to create
 * a fonts directory, but the FS_CHMOD_DIR constant has not been set, causing
 * a hard crash. For this to work, there must be an existing 'fonts' directory
 * as a subdirectory to what is returned by this filter.
 */
function hc_poseidon_font_base_directory() {
	return '/media/uploads/poseidon/';
}
add_filter( 'wptt_get_local_fonts_base_path', 'hc_poseidon_font_base_directory' );
