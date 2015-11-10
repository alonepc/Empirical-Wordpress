<?php
/*
Template Name: Blank Template
*/
get_header();

?>
<style>

</style>

<div id="main-content" class="main-content signup-page">
	<div id="primary" class="content-area">
		<div id="content" role="main">
			<?php
			while ( have_posts() ) : the_post();
				the_content();
			endwhile;
			?>
		</div>
	</div>
</div>
<?php get_footer();?>
