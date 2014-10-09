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

global $bp, $wpdb, $activities_template;

if ( bp_activity_has_content() ) {

$bp->avatar->thumb->width = 75;
$bp->avatar->thumb->width = 75;

$activity_id = bp_get_activity_id();
$group_id = $wpdb->get_var( "SELECT item_id FROM wp_bp_activity WHERE id=$activity_id" );
$group_id = intval($group_id);
if($group_id === 0) {
  $link = bp_get_activity_user_link();
  $avatar = bp_get_activity_avatar();
  $user_id = $wpdb->get_var( "SELECT user_id FROM wp_bp_activity WHERE id=$activity_id" );
  $user_data = get_userdata($user_id);
  $name = $user_data->first_name . ' ' . $user_data->last_name;
} else {
  $avatar = bp_core_fetch_avatar( array( 'item_id' => $group_id, 'object' => 'group', 'width' => 75, 'height' => 75 ) );
  $link = '/groups/' . $wpdb->get_var( "SELECT slug FROM wp_bp_groups WHERE id=$group_id" );
  $name = $wpdb->get_var( "SELECT name FROM wp_bp_groups WHERE id=$group_id" );
}

$date = date("F jS, Y",strtotime($activities_template->activity->date_recorded));

?>

<?php do_action( 'bp_before_activity_entry' ); ?>

<li class="activity activity_update activity-item" id="activity-<?php bp_activity_id(); ?>">

  <div class="activity-avatar">
    <a href="<?php echo($link); ?>">
      <?php echo($avatar);  ?>
    </a>
  </div>

  <div class="activity-content">

    <div class="activity-header">
      <a href="<?php echo($link); ?>">
        <?php echo($name); ?>
      </a>
	    <div class="activity-date">
	    	<?php echo($date); ?>
	    </div>
    </div>

    <div class="activity-inner">
      <?php bp_activity_content_body(); ?>
    </div>


    <?php do_action( 'bp_activity_entry_content' ); ?>

  </div>

</li>

<?php do_action( 'bp_after_activity_entry' ); ?>
<?php } ?>