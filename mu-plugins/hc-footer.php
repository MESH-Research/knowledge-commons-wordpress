<?php
/**
 * Global footer for all non-root sites.
 *
 * @package Commons
 */

/**
 * Append some text to the bottom of any/all themes to tell users about HC and its networks
 */
function hcommons_wp_footer() {
	$is_society_blog = function () {
		$society_blog_ids = [
			constant( 'HC_ROOT_BLOG_ID' ),
			constant( 'AJS_ROOT_BLOG_ID' ),
			constant( 'ARLISNA_ROOT_BLOG_ID' ),
			constant( 'ASEEES_ROOT_BLOG_ID' ),
			constant( 'CAA_ROOT_BLOG_ID' ),
			constant( 'MLA_ROOT_BLOG_ID' ),
			constant( 'MSU_ROOT_BLOG_ID' ),
			constant( 'SAH_ROOT_BLOG_ID' ),
			constant( 'UP_ROOT_BLOG_ID' ),
            constant( 'STEMEDPLUS_ROOT_BLOG_ID' ),
		];

		return in_array( (string) get_current_blog_id(), $society_blog_ids, true );
	};

	if ( class_exists( 'Humanities_Commons' )
		&& ! empty( Humanities_Commons::$society_id )
		&& ! $is_society_blog()
	) {
		$main_site_domain = Humanities_Commons::$main_site->domain;
		$society_id       = Humanities_Commons::$society_id;
		$root_url         = 'https://' . $main_site_domain;

		$society_url = sprintf(
			'https://%s%s',
			( 'hc' === $society_id ) ? '' : $society_id . '.',
			$main_site_domain
		);

		$theme      = wp_get_theme();

		// 'background-color: white',
        // 'color: black',

        $styles = [
			'line-height: 3em',
			'position: relative',
			'text-align: center',
			'width: 100%',
			'z-index: 100',
		];

		printf(
			'<div id="hcommons-network-footer" style="%s">',
			esc_attr( implode( ';', $styles ) )
		);

            printf('<ul style="list-style: none;margin: 0;padding: 0;">');
                printf('<li style="display: inline;">This site is part of <em><a href="https://hcommons.org">Knowledge Commons</a></em>.</li>');
                printf('<li style="display: inline;"><a href="https://hcommons.org/sites"> Explore other sites on this network</a> or <a href="https://hcommons.org">register to build your own</a>.</li>');
            printf('</ul>');

            printf('<ul style="list-style: none;margin: 0;padding: 0;">');
                printf('<li style="display: inline;"><a href="https://about.hcommons.org/terms-of-service/">Terms of Service</a></li>');
                printf('<li style="display: inline;margin: 0 1em;"><a href="https://sustaining.hcommons.org/policies/privacy/">Privacy Policy</a></li>');
                printf('<li style="display: inline;"><a href="https://sustaining.hcommons.org/policies/guidelines/">Guidelines for Participation</a></li>');
            printf('</ul>');

		// Close #hcommons-network-footer.
		echo '</div>';

		// Fix commentpress.
		if ( false !== strpos( strtolower( $theme->get( 'Name' ) ), 'commentpress' ) ) {
			echo '<script>jQuery(".cp_sidebar_toc #hcommons-network-footer").appendTo("#footer").css({"line-height":"2em"});</script>';
		}

		// 2015 needs a bit more help.
		if ( 'twenty fifteen' === strtolower( $theme->get( 'Name' ) ) ) {
			echo '<script>jQuery("#hcommons-network-footer").appendTo("footer .site-info").css({"margin-top":"2em","line-height":"2em"});</script>';
		}
	}
}
add_action( 'wp_footer', 'hcommons_wp_footer' );
