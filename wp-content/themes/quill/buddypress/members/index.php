<?php do_action( 'bp_before_directory_members_page' ); ?>

<div id="buddypress">
	
	<div id="buddypress-sidebar">
		<ul class="buddypress-group-list">
			<li class="active">
				<a href="#">
					<i class="fa fa-university"></i>
					Quill Community
				</a>
			</li>
			
		<?php
			if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) :
				while ( bp_groups() ) : bp_the_group();
		?>
		
		<li>
			<a href="<?php bp_group_permalink(); ?>">
				<?php bp_group_avatar( 'type=full&width=30&height=30' ); ?>
				<?php bp_group_name(); ?>
			</a>
		</li>
				
		<?php	
			endwhile; endif;
		?>
			
		</ul>
	</div>
	
	<?php do_action( 'bp_before_directory_members' ); ?>

	<?php do_action( 'bp_before_directory_members_content' ); ?>


	<?php do_action( 'bp_before_directory_members_tabs' ); ?>

	<form action="" method="post" id="members-directory-form" class="dir-form item-body">

		<div id="members-dir-list" class="members dir-list">
			<?php bp_get_template_part( 'members/members-loop' ); ?>
		</div><!-- #members-dir-list -->

		<?php do_action( 'bp_directory_members_content' ); ?>

		<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ); ?>

		<?php do_action( 'bp_after_directory_members_content' ); ?>

	</form><!-- #members-directory-form -->

	<?php do_action( 'bp_after_directory_members' ); ?>

</div><!-- #buddypress -->

<?php do_action( 'bp_after_directory_members_page' ); ?>