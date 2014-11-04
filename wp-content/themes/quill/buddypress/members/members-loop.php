<?php

/**
 * BuddyPress - Members Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

global $bp, $members_template, $wpdb;


?>

<?php do_action( 'bp_before_members_loop' ); ?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>

	<?php do_action( 'bp_before_directory_members_list' ); ?>

	<ul id="members-list" class="item-list" role="main">

	<?php while ( bp_members() ) : bp_the_member();
		$groups = '';
		$user_id = bp_get_member_user_id(); 
		$group_ids = $wpdb->get_results( "SELECT group_id FROM wp_bp_groups_members WHERE user_id=$user_id" );
		foreach($group_ids as $group)
		{
			$group_id = intval($group->group_id);
			$group_name = $wpdb->get_var( "SELECT name FROM wp_bp_groups WHERE id=$group_id" );
			$group_slug = $wpdb->get_var( "SELECT slug FROM wp_bp_groups WHERE id=$group_id" );
			$avatar = bp_core_fetch_avatar( array( 'item_id' => $group_id, 'object' => 'group', 'width' => 50, 'height' => 50 ) );
			$groups = $groups . ' ' . $group_slug;
		}
	?>
		<li class="member <?php echo($groups); ?>">
			<div class="item-avatar">
			<?php
			$args = array(
				'item_id' => $members_template->member->id, 
				'type' => 'full', 
				'alt' => 'Avatar', 
				'width' => 125, 
				'height' => 125, 
				'email' => $members_template->member->user_email
			);
			$avatar = bp_core_fetch_avatar($args);
			?>
				<a href="<?php bp_member_permalink(); ?>"><?php echo $avatar; ?></a>
			</div>

			<div class="item">
				<div class="item-title">
					<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
				</div>

				<?php do_action( 'bp_directory_members_item' ); ?>
				
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_members_list' ); ?>

	<?php bp_member_hidden_fields(); ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( "Sorry, no members were found.", 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_members_loop' ); ?>
