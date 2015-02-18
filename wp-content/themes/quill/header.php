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
	<script src="//use.typekit.net/plr0bzn.js"></script>
	<script>try{Typekit.load();}catch(e){}</script>

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
			<h1 class="site-title"><a href="http://quill.org" rel="home"><?php bloginfo( 'name' ); ?></a></h1>

			<nav id="primary-navigation" class="site-navigation primary-navigation" role="navigation">			
				
				<ul class="nav navbar-nav">
	                <li><a href="http://quill.org/mission">Discover</a></li>
	                <li><a href="http://community.quill.org/">Community</a></li>
	                
					<?php if(is_user_logged_in()) { ?>
						<li><a href="<?php echo(wp_logout_url(esc_url( home_url( '/' ) ))); ?>">Logout</a></li>
					<?php } else { ?>
						<li><a href="<?php echo(wp_login_url(esc_url( home_url( '/' ) ))); ?>">Login</a></li>
						<li class="signup"><a href="<?php echo(esc_url( home_url( '/signup' ) )); ?>">Sign Up</a></li>
					<?php } ?>
	            </ul>
              
			</nav>

			<?php 
			if(bp_current_item())
			{
				$active = bp_current_item();
			} else {
				$active = false;
			}
			
			?>

			<div class="tab-outer-wrap">
			    <ul class="nav nav-tabs tabs-navigation-list" role="tablist">

			      <li class="<?php if(is_front_page()){echo('active');} ?>">
			        <a href="/">Home</a>
			      </li>

			      <li class="<?php if($active == 'quill-lms'){echo('active');} ?>">
			        <a href="/teams/quill-lms">LMS</a>
			      </li>


			      <li class="<?php if($active == 'apps'){echo('active');} ?>">
			        <a href="/teams/apps/">Apps</a>
			      </li>

			      <li class="<?php if($active == 'community-dashboard'){echo('active');} ?>">
			        <a href="/teams/community-dashboard/">Community</a>
			      </li>

			      <li class="<?php if($active == 'outreach'){echo('active');} ?>">
			        <a href="/teams/outreach/">Outreach</a>
			      </li>

			      <li class="<?php if($active == 'fundraising'){echo('active');} ?>">
			        <a href="/teams/fundraising/">Fundraising</a>
			      </li>
			    
			    </ul>

			</div>

		</div>
		<div class="header-bottom">
			<nav id="sub-navigation" class="site-navigation sub-navigation">
			
			<?php if(bp_current_item()) : ?>
				
					<?php
						$args = array(
							'name' => bp_current_item(),
							'post_type' => 'page',
							'post_status' => 'publish',
							'numberposts' => 1
						);
						$page = get_posts($args);
						if($page[0]->ID){
							$args = array(
								'post_parent' => $page[0]->ID,
								'post_type' => 'page',
								'posts_per_page' => -1,
								'post_status' => 'publish'
							);
							$subpages = get_children($args);
							
							echo('<ul class="menu">');

							foreach($subpages as $page)
							{
								echo('<li><a href="' . get_permalink($page->ID) . '">' . $page->post_title . '</a></li>');
							}
							
							echo('</ul>');
						}
					?>
				
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
