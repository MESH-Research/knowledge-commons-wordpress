<?php
/**
 * Member-count markup for the groups directory loop.
 *
 * @package hc-styles
 */

/**
 * Build the "N member(s)" markup shown for each group in the directory.
 *
 * The members-page URL is passed to sprintf() as an argument rather than
 * spliced into the format string: a slug containing percent-encoded
 * characters would otherwise be parsed as conversion specifiers.
 *
 * @param int    $count       Total member count for the group.
 * @param string $members_url Permalink to the group's members page.
 * @return string HTML markup.
 */
function hc_styles_group_member_count_html( $count, $members_url ) {
	$count = (int) $count;

	return sprintf(
		_n(
			'<span class="meta-wrap"><span class="count">%1$s</span> <span>member</span></span>',
			'<a href="%2$s"><span class="meta-wrap"><span class="count">%1$s</span> <span>members</span></span></a>',
			$count,
			'boss'
		),
		$count,
		esc_url( $members_url )
	);
}
