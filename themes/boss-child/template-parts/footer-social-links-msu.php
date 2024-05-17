<?php
$show_social_links	 = boss_get_option( 'footer_social_links' );
$social_links		 = boss_get_option( 'boss_footer_social_links' );
$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-mastodon" style="vertical-align:middle;" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#575757" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18.648 15.254c-1.816 1.763 -6.648 1.626 -6.648 1.626a18.262 18.262 0 0 1 -3.288 -.256c1.127 1.985 4.12 2.81 8.982 2.475c-1.945 2.013 -13.598 5.257 -13.668 -7.636l-.026 -1.154c0 -3.036 .023 -4.115 1.352 -5.633c1.671 -1.91 6.648 -1.666 6.648 -1.666s4.977 -.243 6.648 1.667c1.329 1.518 1.352 2.597 1.352 5.633s-.456 4.074 -1.352 4.944z" /><path d="M12 11.204v-2.926c0 -1.258 -.895 -2.278 -2 -2.278s-2 1.02 -2 2.278v4.722m4 -4.722c0 -1.258 .895 -2.278 2 -2.278s2 1.02 2 2.278v4.722" /></svg>';

if ( $show_social_links && is_array( $social_links ) ) {
	?>

	<div id="footer-icons">

		<ul class="social-icons">
			<li><a class="link-mastodon" title="mastodon" href="https://hcommons.social/" target="_blank"><span><?php echo $svg_icon; ?></span></a></li>
			<?php
			foreach ( $social_links as $key => $link ) {
				if ( !empty( $link ) ) {
					$href = ( $key == 'email' ) ? 'mailto:' . sanitize_email( $link ) : esc_url( $link );
					?>
					<li>
						<a class="link-<?php echo $key; ?>" title="<?php echo $key; ?>" href="<?php echo $href; ?>" target="_blank">
							<span></span>
						</a>
					</li>
					<?php
				}
			}
			?>
		</ul>

	</div>

	<?php
}