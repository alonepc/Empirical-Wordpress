<?php
/*
Template Name: Blank Template
*/
?>

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
