<?php
global $wpdb;

/* get the users from the database ordered by user nicename */
$query = "SELECT $wpdb->users.ID, $wpdb->users.user_nicename FROM $wpdb->users ORDER BY $wpdb->users.user_nicename";
$user_ids = $wpdb->get_results($query);

/* add object for guest user */
$guest = new stdClass();
$guest->ID = 'GUEST';
$guest->user_email = 'guest@example.com';
$guest->display_name = 'Default folder for Guests and non-linked Users';
//$user_ids[] = $guest;

$html = '';
?>
<div class="wrap adminfilebrowser">
  <h2><?php _e('Link users to folder', 'useyourdrive'); ?></h2>
  <div id='UseyourDrive-UserToFolder'>

    <?php
    $html .= getUserListing($guest);

    //loop through each user
    foreach ($user_ids as $user) {
      // Get user data
      $curuser = get_userdata($user->ID);
      $html .= getUserListing($curuser);
    }
    echo $html;
    ?>
  </div>
  <div id='uyd-embedded' style='clear:both;display:none;'>
    <?php
    echo $this->UseyourDrive->createFromShortcode(array('mode' => 'files', 'upload' => '0', 'rename' => '0', 'delete' => '0', 'addfolder' => '1', 'showfiles' => '0', 'mcepopup' => 'linkto', 'search' => '0'));
    ?>
  </div>
</div>
<?php

function getUserListing($curuser) {
  $html = '<div class="uyd-user ' . (($curuser->ID === 'GUEST') ? 'guest' : '' ) . '">';

  /* Gravatar */
  if (function_exists('get_wp_user_avatar')) {
    $display_gravatar = get_wp_user_avatar($curuser->user_email, 32);
  } else {
    $display_gravatar = get_avatar($curuser->user_email, 32);
    if ($display_gravatar === false) {
      //Gravatar is disabled, show default image.
      $display_gravatar = '<img src="' . USEYOURDRIVE_ROOTPATH . '/css/images/usericon.png"/>';
    }
  }

  $html .= "<div class=\"uyd-avatar\"><a title=\"$curuser->display_name\">$display_gravatar</a></div>\n";

  $html .= "<div class=\"uyd-userinfo\" data-userid=\"" . $curuser->ID . "\">";

  /* name */
  $html .= "<div class=\"uyd-name\"><a href=\"" . (($curuser->ID === 'GUEST') ? '#' : get_edit_user_link($curuser->ID)) . "\"title=\"$curuser->display_name\">$curuser->display_name</a></div>\n";

  /* Current link */
  if ($curuser->ID === 'GUEST') {
    $curfolder = get_site_option('use_your_drive_guestlinkedto');
  } else {
    $curfolder = get_user_option('use_your_drive_linkedto', $curuser->ID);
  }
  $nolink = true;
  if (empty($curfolder) || !is_array($curfolder) || !isset($curfolder['foldertext'])) {
    $curfolder = __('Not yet linked to a folder', 'useyourdrive');
  } else {
    $curfolder = $curfolder['foldertext'];
    $nolink = false;
  }

  $html .= "<div class=\"uyd-linkedto\">$curfolder</div>\n";
  $html .= "<input class='uyd-linkbutton button-primary' type='submit' title='" . __('Link to folder', 'useyourdrive') . "' value='" . __('Link to folder', 'useyourdrive') . "'>";
  $html .= "<input class='uyd-unlinkbutton button-secondary " . ($nolink ? 'disabled' : '') . "' type='submit' title='" . __('Remove link', 'useyourdrive') . "' value='" . __('Remove link', 'useyourdrive') . "'>";

  $html .= "</div>";

  $html .= '</div>';
  return $html;
}
