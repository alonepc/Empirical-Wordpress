<?php do_action( 'bp_before_directory_activity' ); ?>

<div id="buddypress">

	<?php do_action( 'bp_before_directory_activity_content' ); ?>

	<?php do_action( 'template_notices' ); ?>

	<div id="buddypress-sidebar">
		<h2>Activity</h2>
		<img src="<?php echo(get_template_directory_uri()); ?>/assets/img/activity-sidebar.jpg" />
		<p>All activity on Empirical</p>
	</div>

	<?php do_action( 'bp_before_directory_activity_list' ); ?>

	<div class="activity item-body" role="main">

		<?php if ( is_user_logged_in() ) : ?>
	
			<?php bp_get_template_part( 'activity/post-form' ); ?>
	
		<?php endif; ?>

		<?php bp_get_template_part( 'activity/activity-loop' ); ?>

	</div><!-- .activity -->

	<?php do_action( 'bp_after_directory_activity_list' ); ?>

	<?php do_action( 'bp_directory_activity_content' ); ?>

	<?php do_action( 'bp_after_directory_activity_content' ); ?>

	<?php do_action( 'bp_after_directory_activity' ); ?>

</div>