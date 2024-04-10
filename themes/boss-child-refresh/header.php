<?php
/**
 * The Header for your theme.
 *
 * Displays all of the <head> section and everything up until <div id="main">
 *
 * @package WordPress
 * @subpackage Boss
 * @since Boss 1.0.0
 */
?><!DOCTYPE html>

<html <?php language_attributes(); ?>>

<?php
	if ( current_user_can( 'manage_options ') ) {
		//echo '<!--' . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true ) . '-->';
	}
?>

	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="msapplication-tap-highlight" content="no"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<link rel="profile" href="http://gmpg.org/xfn/11" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
		<!-- BuddyPress and bbPress Stylesheets are called in wp_head, if plugins are activated -->
		<?php wp_head(); ?>
	</head>

	<?php
	global $rtl;
	$logo	 = ( boss_get_option( 'logo_switch' ) && boss_get_option( 'boss_logo', 'id' ) ) ? '1' : '0';
	$inputs	 = ( boss_get_option( 'boss_inputs' ) ) ? '1' : '0';
	$boxed	 = boss_get_option( 'boss_layout_style' );

	$show		 = boss_get_option( 'logo_switch' );
	$show_mini	 = boss_get_option( 'mini_logo_switch' );

	$logo_id		 = boss_get_option( 'boss_logo', 'id' );
	$logo_small_id	 = boss_get_option( 'boss_small_logo', 'id' );
	$site_title = get_bloginfo( 'name' );

	if ( $show && $logo_id ) {
		$logo_large_src = wp_get_attachment_image_src( $logo_id, 'medium' );
		$logo_small_src = wp_get_attachment_image_src( $logo_small_id, 'medium' );
		$logo_large = "<img src='{$logo_large_src[0]}' class='boss-logo large' />";
		$logo_small = "<img src='{$logo_small_src[0]}' class='boss-logo small' />";
	} else {
		$logo_large = '<span class="bb-title-large">' . $site_title . '</span>';
		$logo_small = '<span class="bb-title-small">' . $site_title . '</span>';
	}

    $header_style = boss_get_option('boss_header');

	$home_url = defined( 'HC_SITE_URL' ) ? HC_SITE_URL : esc_url( home_url( '/' ) );
	$organization_url = esc_url( home_url( '/' ) );
	?>

	<body <?php body_class(); ?> data-logo="<?php echo $logo; ?>" data-inputs="<?php echo $inputs; ?>" data-rtl="<?php echo ($rtl) ? 'true' : 'false'; ?>" data-header="<?php echo $header_style; ?>">

		<?php do_action( 'buddyboss_before_header' ); ?>

		<div id="scroll-to"></div>

		<header id="masthead" class="site-header" data-infinite="<?php echo ( boss_get_option( 'boss_activity_infinite' ) ) ? 'on' : 'off'; ?>">

			<a class="skip-navigation" href="#content">Skip to content</a>

			<div id="header-logo">
				<a href="<?= $home_url ?>" rel="home">
					<?= $logo_small ?>
				</a>
				<a href="<?= $organization_url ?>" rel="home">
					<?= $logo_large ?>
				</a>
			</div>
			
			<div id="header-center">
				<?= get_template_part( 'template-parts/header-middle-column' ); ?>
			</div>

			<div id="header-profile">
				<?= get_template_part( 'template-parts/header-profile' ); ?>
			</div>

		</header><!-- #masthead -->

		<?php do_action( 'buddyboss_after_header' ); ?>

		<?php get_template_part( 'template-parts/header-mobile' ); ?>

		<!-- #panels closed in footer-->
		<div id="panels" class="<?php echo (boss_get_option( 'boss_adminbar' )) ? 'with-adminbar' : ''; ?>">

			<!-- Left Panel -->
			<?php // get_template_part( 'template-parts/left-panel' ); ?>
			<!-- Left Mobile Menu -->
			<?php get_template_part( 'template-parts/left-mobile-menu' ); ?>

			<div id="right-panel">
				<div id="right-panel-inner">
					<div id="main-wrap"> <!-- Wrap for Mobile content -->
						<div id="inner-wrap"> <!-- Inner Wrap for Mobile content -->

							<?php do_action( 'buddyboss_inside_wrapper' ); ?>

							<div id="page" class="hfeed site">
								<div id="main" class="wrapper">