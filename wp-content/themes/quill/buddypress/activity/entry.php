<?php

/**
 * BuddyPress - Activity Stream (Single Item)
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

global $bp;
if ( bp_activity_has_content() ) {
?>

<?php do_action( 'bp_before_activity_entry' ); ?>

<li class="activity activity_update activity-item" id="activity-<?php bp_activity_id(); ?>">
	<div class="activity-avatar">
		<a href="<?php bp_activity_user_link(); ?>">
			<?php
			global $wpdb;
			$activity_id = bp_get_activity_id();
			$group_id = $wpdb->get_var( "SELECT item_id FROM wp_bp_activity WHERE id=$activity_id" );
			$group_id = intval($group_id);
			if($group_id === 0) {
				bp_activity_avatar();
			} else {
				$avatar = bp_core_fetch_avatar( array( 'item_id' => $group_id, 'object' => 'group', 'width' => 75, 'height' => 75 ) );
				echo($avatar);
			}
			?>
		</a>
	</div>

	<div class="activity-content">

		<div class="activity-header">

			<?php
				if($group_id == 0) {
				
					echo($bp->displayed_user->fullname);
					
				} else {
				
					$group_name = $wpdb->get_var( "SELECT name FROM wp_bp_groups WHERE id=$group_id" );
					echo($group_name);
					
				}
			?>
		</div>

		

			<div class="activity-inner">
			
					<?php bp_activity_content_body(); ?>
				
			</div>


		<?php do_action( 'bp_activity_entry_content' ); ?>

	</div>

	<?php do_action( 'bp_before_activity_entry_comments' ); ?>

	<?php if ( ( is_user_logged_in() && bp_activity_can_comment() ) || bp_is_single_activity() ) : ?>

		<div class="activity-comments">

			<?php bp_activity_comments(); ?>

			<?php if ( is_user_logged_in() ) : ?>

				<form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-form-<?php bp_activity_id(); ?>" class="ac-form"<?php bp_activity_comment_form_nojs_display(); ?>>
					<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( 'width=' . BP_AVATAR_THUMB_WIDTH . '&height=' . BP_AVATAR_THUMB_HEIGHT ); ?></div>
					<div class="ac-reply-content">
						<div class="ac-textarea">
							<textarea id="ac-input-<?php bp_activity_id(); ?>" class="ac-input" name="ac_input_<?php bp_activity_id(); ?>"></textarea>
						</div>
						<input type="submit" name="ac_form_submit" value="<?php esc_attr_e( 'Post', 'buddypress' ); ?>" /> &nbsp; <a href="#" class="ac-reply-cancel"><?php _e( 'Cancel', 'buddypress' ); ?></a>
						<input type="hidden" name="comment_form_id" value="<?php bp_activity_id(); ?>" />
					</div>

					<?php do_action( 'bp_activity_entry_comments' ); ?>

					<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ); ?>

				</form>

			<?php endif; ?>

		</div>

	<?php endif; ?>

	<?php do_action( 'bp_after_activity_entry_comments' ); ?>

</li>

<?php do_action( 'bp_after_activity_entry' ); ?>
<?php } ?>