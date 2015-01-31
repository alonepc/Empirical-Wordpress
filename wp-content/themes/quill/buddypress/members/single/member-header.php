<?php

/**
 * BuddyPress - Users Header
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */
global $wpdb, $bp;
?>

<?php do_action( 'bp_before_member_header' ); ?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=full&width=225&height=225' ); ?>
	</a>
</div><!-- #item-header-avatar -->
<div style="clear: both;"></div>
<div id="item-header-content">

	<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		<h2 class="user-nicename"><?php echo($bp->displayed_user->fullname); ?></h2>
	<?php endif; ?>

	<span class="activity"><?php bp_last_activity( bp_displayed_user_id() ); ?></span>

	<?php do_action( 'bp_before_member_header_meta' ); ?>

	<div id="item-meta">

		<?php if ( bp_is_active( 'activity' ) ) : ?>

			<div id="latest-update">

				<?php bp_activity_latest_update( bp_displayed_user_id() ); ?>

			</div>

		<?php endif; ?>

		<div id="item-buttons">

			<?php do_action( 'bp_member_header_actions' ); ?>
			<?php
			if($bp->displayed_user->id == get_current_user_id()) {
			?>
				<div class="generic-button" id="settings"><a href="<?php echo '/contributors/'. $bp->displayed_user->userdata->user_login . '/settings/'; ?>" title="Settings" class="activity-button mention">Settings</a></div>
			<?php
			}
			?>
			
		</div><!-- #item-buttons -->

		<?php
		/***
		 * If you'd like to show specific profile fields here use:
		 * bp_member_profile_data( 'field=About Me' ); -- Pass the name of the field
		 */
		 do_action( 'bp_profile_header_meta' );

		 ?>

	</div><!-- #item-meta -->
	
	
	<div id="item-member-groups">
		<ul>
		<?php
			$user_id = $bp->displayed_user->id; 
			$group_ids = $wpdb->get_results( "SELECT group_id FROM wp_bp_groups_members WHERE user_id=$user_id" );
			foreach($group_ids as $group)
			{
				$group_id = intval($group->group_id);
				$group_name = $wpdb->get_var( "SELECT name FROM wp_bp_groups WHERE id=$group_id" );
				$group_slug = $wpdb->get_var( "SELECT slug FROM wp_bp_groups WHERE id=$group_id" );
				$slug = '/groups/' . $group_slug;
				$avatar = bp_core_fetch_avatar( array( 'item_id' => $group_id, 'object' => 'group', 'width' => 50, 'height' => 50 ) );
			?>
			<li>
				<a href="<?php echo($slug); ?>">
					<?php echo($avatar); ?>
					<span class="text">
						<?php echo($group_name); ?>
					</span>
				</a>
			</li>
			<?php
			}
		?>
		</ul>
	</div>
	
</div><!-- #item-header-content -->

<?php do_action( 'bp_after_member_header' ); ?>

<?php do_action( 'template_notices' ); ?>