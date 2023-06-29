<div id="footer-links">

	<?php if ( has_nav_menu( 'secondary-menu' ) ) : ?>
		<ul class="footer-menu">
			<?php wp_nav_menu( array( 'container' => false, 'menu_id' => 'nav', 'theme_location' => 'secondary-menu', 'items_wrap' => '%3$s', 'depth' => -1 ) ); ?>
		</ul>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/footer-social-links' ); ?>

	<?php if ( boss_get_option( 'boss_layout_switcher' ) ) { ?>

		<form id="switch-mode" name="switch-mode" method="post">
			<input type="submit" value="View as Desktop" tabindex="1" id="switch_submit" name="submit" />
			<input type="hidden" id="switch_mode" name="switch_mode" value="desktop" />
			<?php wp_nonce_field( 'switcher_action', 'switcher_nonce_field' ); ?>
		</form>

	<?php } else { ?>

		<a href="#scroll-to" class="to-top fa fa-angle-up scroll"></a>

	<?php } ?>

</div>
<div class="panel-panel grid-12 alpha omega msu_footer_wrapper" role="contentinfo">
<div class="panel-panel grid-3 alpha msu_footer_wordmark"><a href="http://www.msu.edu" rel="nofollow"><img alt="Michigan State University Wordmark" src="/app/uploads/sites/1001437/2020/05/msu-wordmark-green.png" /></a></div>

<div class="panel-panel grid-9 omega" id="footer_lists">
<div class="panel-panel grid-9 alpha omega">
<ul class="msu_footer_unit_info">
    <li>Call Us: <a href="tel:+15173538700">(517)&nbsp;353-8700</a> 
    <li><a href="http://www.lib.msu.edu/contact" rel="nofollow">Contact&nbsp;Information</a></li>
    <li><a href="https://sustaining.hcommons.org/policies/privacy/" rel="nofollow">Privacy&nbsp;Statement</a></li>
</ul>
</div>
<div class="panel-panel grid-9 alpha omega">
<ul class="msu_footer">
    <li>Call MSU: <strong><span class="msu_footer_green">(517) 355-1855</span></strong></li>
    <li>Visit: <strong><a href="http://msu.edu" rel="nofollow"><span class="msu_footer_green">msu.edu</span></a></strong></li>
    <li>MSU is an affirmative-action, <span>equal-opportunity employer.</span></li>
</ul>
</div>
<div class="panel-panel grid-9 alpha omega">
<ul class="copyright msu_footer">
    <li><strong>SPARTANS WILL.</strong></li>
    <li>© Michigan State University Board of Trustees</li>
</ul>
</div>
</div>
</div>
