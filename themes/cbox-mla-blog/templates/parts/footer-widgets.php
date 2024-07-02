<?php
/**
 * Infinity Theme: Footer Menu Widgets
 *
 * @author Bowe Frankema <bowe@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Bowe Frankema
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package Infinity
 * @subpackage templates
 * @since 1.0
 */
?>
<?php if ( is_active_sidebar( 'Footer Left' ) || is_active_sidebar( 'Footer Middle' ) || is_active_sidebar( 'Footer Right' )  ) : ?>
	<div class="footer-widgets row">

			<!-- footer widgets -->
				<div class="five columns footer-widget " id="footer-widget-left">
					<?php
						the_widget('WP_Widget_Text', 'title=Contact Us&text=<ul class="contact">
<li class="address"><span class="smalltext">Our mailing address is:</span><br>
<a href="http://www.mla.org">Modern Language Association</a><br>
85 Broad Street<br>
New York, NY 10004-2434<br>
</li>
<li class="email"><a class="email" href="mailto:commons@mla.org">commons@mla.org</a></li>
<li class="address"><span class="smalltext">On the Web:</span> <a href="http://www.mla.org">mla.org</a></li>
<li class="phone">646 576-5000</li>
</ul>', 'before_title=<h4>&after_title=</h4>');
					?>
				</div>


				<div class="five columns footer-widget" id="footer-widget-middle">
					<?php
						the_widget('WP_Widget_RSS', 'url=https://faq.commons.mla.org/feed/&title=FAQ&items=6', 'before_title=<h4>&after_title=</h4>');
					?>
				</div>


				<div class="six columns footer-widget " id="footer-widget-right">
					<?php
						the_widget('WP_Widget_Text', 'title=Get Help&text=<ul><li>See <a href="http://commons.mla.org/docs/getting-started/">Getting Started</a>.</li>
<li>Join the member <a href="http://commons.mla.org/groups/mla-commons-help">Help Group</a>.</li>
<li>E-mail us at <a href="mailto:commons@mla.org">commons@mla.org</a>.</li></ul>', 'before_title=<h4>&after_title=</h4>');
					?>
				</div>

	</div>
<?php endif; ?>
<div style="clear:both;"></div>
