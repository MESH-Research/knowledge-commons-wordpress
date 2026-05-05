<?php
/**
 * Plugin Name: MSU Docs Login Required
 * Description: Require login to view site-level /docs/ URLs on the MSU root
 *              blog (commons.msu.edu). Group docs are unaffected and continue
 *              to honour their per-doc access settings.
 */

function msu_docs_login_required_check() {
	if ( ! defined( 'MSU_ROOT_BLOG_ID' ) ) {
		return;
	}
	if ( get_current_blog_id() !== (int) MSU_ROOT_BLOG_ID ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}

	if ( function_exists( 'bp_docs_is_docs_component' )
		&& function_exists( 'bp_is_group' )
	) {
		if ( ! bp_docs_is_docs_component() ) {
			return;
		}
		if ( bp_is_group() ) {
			return;
		}
	} else {
		$docs_slug = function_exists( 'bp_docs_get_docs_slug' )
			? bp_docs_get_docs_slug()
			: 'docs';
		$groups_slug = function_exists( 'bp_get_groups_root_slug' )
			? bp_get_groups_root_slug()
			: 'groups';

		$path  = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?? '';
		$path  = trim( (string) $path, '/' );
		$first = strtok( $path, '/' );

		if ( $first === $groups_slug ) {
			return;
		}
		if ( $first !== $docs_slug ) {
			return;
		}
	}

	auth_redirect();
	exit;
}

add_action( 'template_redirect', 'msu_docs_login_required_check', 1 );
