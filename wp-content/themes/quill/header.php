<?php
/**
 * The Header for our theme
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */
?><!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) & !(IE 8)]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<!--[if lt IE 9]>
	<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js"></script>
	<![endif]-->
	<link rel="icon" href="<?php echo get_template_directory_uri(); ?>/images/favicon.ico" type="image/x-icon">
	<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/images/favicon.ico" type="image/x-icon">
	<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
	<?php wp_head(); ?>
	
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('select.group-selector').on('change', function(){
				window.location.href = $(this).find(':selected').data('href');
			});
		});
	</script>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<header id="masthead" class="site-header" role="banner">
		<div class="header-main">
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>

			<nav id="primary-navigation" class="site-navigation primary-navigation" role="navigation">			
				
				<ul class="nav navbar-nav">
	                <li><a href="http://quill.org/about">About</a></li>
	                <li><a href="http://community.quill.org/">Community</a></li>
	                <li><a href="http://quill.org/faq">Support</a></li>
	                <li><a href="http://news.quill.org">News</a></li>
	                
					<?php if(is_user_logged_in()) { ?>
						<li><a href="<?php echo(wp_logout_url(esc_url( home_url( '/' ) ))); ?>">Logout</a></li>
					<?php } else { ?>
						<li><a href="<?php echo(esc_url( home_url( '/signup' ) )); ?>">Register</a></li>
						<li><a href="<?php echo(wp_login_url(esc_url( home_url( '/' ) ))); ?>">Login</a></li>
					<?php } ?>
	            </ul>
              
			</nav>
		</div>
		<div class="header-bottom">
			<nav id="sub-navigation" class="site-navigation sub-navigation">
			
			<?php
				$args = array(
					'type' => 'alphabetical',
					'per_page' => 99999,
					'max' => false
				);
				if ( bp_has_groups($args) ) : ?>
				<select class="group-selector">
					<option>Quill Community</option>
					<?php while ( bp_groups() ) : bp_the_group(); ?>
						<?php
							
							if(bp_get_group_slug() == bp_current_item())
							{
								$selected = true;
							} else {
								$selected = false;
							}
							
						?>
						<option <?php if($selected){echo('selected="selected"');} ?> data-href="<?php bp_group_permalink() ?>">
							<?php bp_group_name() ?>
						</option>
					<?php endwhile; ?>
				</select>
			<?php endif; ?>
			
			<?php if(bp_current_item()) : ?>

			<?php else: ?>
				<ul class="menu">		
					<li><a href="/teams">Teams</a></li>
					<li><a href="/">Contributors</a></li>
					<li><a href="/activity/">Activity</a></li>
					<li><a href="http://empirical-core.readme.io/v1.0">Docs</a></li>
					<li><a href="/chat/">Chat</a></li>
				</ul>
			<?php endif; ?>
			</nav>
		</div>
		
	</header><!-- #masthead -->
	<div id="main" class="site-main">
