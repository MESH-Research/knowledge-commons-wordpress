<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

    <!--[if lt IE 9]>
	<script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/html5.js"></script>
	<![endif]-->
    <link href='https://fonts.googleapis.com/css?family=Alegreya:400,400italic,700|Alegreya+SC:400,400italic,700,700italic' rel='stylesheet' type='text/css'>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="<?php echo esc_url( get_template_directory_uri() ); ?>/public/vendor/jquery/dist/jquery.min.js"><\/script>')</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?> id="lm">
    <span class="lm_top-decor"></span>

    <header class="su lm_header">
        <a href="" class="go-up-link hidden" id="go-up-link" data-target="initial-target">Go up</a>

        <div class="content">
            <a href="<?php echo site_url(); ?>">
                <h1>
                    Visualizing <span class="lm_book-name">Les Misérables</span>
                </h1>
            </a>
        </div>
    </header>