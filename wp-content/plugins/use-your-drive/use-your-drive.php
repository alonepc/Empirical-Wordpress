<?php

/*
  Plugin Name: Use-your-Drive
  Plugin URI: http://www.florisdeleeuw.nl/wordpress-demo/
  Description: Integrates your Google Drive in WordPress
  Version: 1.5.1
  Author: F. de Leeuw
  Author URI:
  Text Domain: useyourdrive
 */

/* * ***********SYSTEM SETTINGS****************** */
define('USEYOURDRIVE_VERSION', '1.5.1');
define('USEYOURDRIVE_ROOTPATH', plugins_url('', __FILE__));
define('USEYOURDRIVE_ROOTDIR', __DIR__);
define('USEYOURDRIVE_CACHEDIR', __DIR__ . '/cache');
define('USEYOURDRIVE_CACHEURL', USEYOURDRIVE_ROOTPATH . '/cache');

if (!class_exists('UseyourDrive_Plugin')) {

  class UseyourDrive_Plugin {

    public $settings = false;

    /**
     * Construct the plugin object
     */
    public function __construct() {

      $this->LoadDefaultValues();

      add_action('init', array(&$this, 'Init'));

      if (is_admin() && !defined('DOING_AJAX')) {
        require_once(sprintf("%s/admin_page.php", dirname(__FILE__)));
        $UseyourDrive_settings = new UseyourDrive_settings($this);
      }

      add_action('wp_head', array(&$this, 'LoadIEstyles'));

      add_action('wp_enqueue_scripts', array(&$this, 'LoadScripts'));
      add_action('wp_enqueue_scripts', array(&$this, 'LoadLastScripts'), 99999);
      add_action('wp_enqueue_scripts', array(&$this, 'LoadStyles'));

      /* TO-DO: GravityForms addon */
      add_action('plugins_loaded', array(&$this, 'GravityFormsAddon'), 100);

      /* Shortcodes */
      add_shortcode('useyourdrive', array(&$this, 'CreateTemplate'));

      /* Add user folder if needed */
      if (isset($this->settings['userfolder_oncreation']) && $this->settings['userfolder_oncreation'] === 'Yes') {
        add_action('user_register', array(&$this, 'UpdateUserfolder'));
      }
      if (isset($this->settings['userfolder_update']) && $this->settings['userfolder_update'] === 'Yes') {
        add_action('profile_update', array(&$this, 'UpdateUserfolder'), 100, 2);
      }
      if (isset($this->settings['userfolder_remove']) && $this->settings['userfolder_remove'] === 'Yes') {
        add_action('delete_user', array(&$this, 'DeleteUserfolder'));
      }
      add_action('wp_head', array(&$this, 'CustomCss'), 100);

      /* Ajax calls */
      add_action('wp_ajax_nopriv_useyourdrive-get-filelist', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-get-filelist', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-search', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-search', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-get-gallery', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-get-gallery', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-upload-file', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-upload-file', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-delete-entry', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-delete-entry', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-delete-entries', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-delete-entries', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-rename-entry', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-rename-entry', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-move-entry', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-move-entry', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-edit-description-entry', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-edit-description-entry', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-add-folder', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-add-folder', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-get-playlist', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-get-playlist', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-create-zip', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-create-zip', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-download', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-download', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-preview', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-preview', array(&$this, 'StartProcess'));

      add_action('wp_ajax_nopriv_useyourdrive-create-link', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-create-link', array(&$this, 'StartProcess'));
      add_action('wp_ajax_useyourdrive-embedded', array(&$this, 'StartProcess'));

      add_action('wp_ajax_useyourdrive-revoke', array(&$this, 'StartProcess'));

      add_action('wp_ajax_useyourdrive-getpopup', array(&$this, 'GetPopup'));

      add_action('wp_ajax_useyourdrive-linkusertofolder', array(&$this, 'LinkUserToFolder'));
      add_action('wp_ajax_useyourdrive-unlinkusertofolder', array(&$this, 'UnlinkUserToFolder'));

      /* add settings link on plugin page */
      add_filter('plugin_row_meta', array(&$this, 'AddSettingsLink'), 10, 2);
    }

    public function Init() {
      /* Localize */
      $i18n_dir = dirname(plugin_basename(__FILE__)) . '/languages/';
      load_plugin_textdomain('useyourdrive', false, $i18n_dir);
    }

    public function LoadDefaultValues() {

      $this->settings = get_option('use_your_drive_settings', array(
          'googledrive_app_client_id' => '',
          'googledrive_app_client_secret' => '',
          'googledrive_app_refresh_token' => '',
          'googledrive_app_current_token' => '',
          'purcase_code' => '',
          'custom_css' => '',
          'google_analytics' => 'No',
          'loadimages' => 'googlethumbnail',
          'lightbox_skin' => 'metro-black',
          'lightbox_path' => 'vertical',
          'mediaplayer_skin' => 'default',
          'userfolder_name' => '%user_login% (%user_email%)',
          'userfolder_oncreation' => 'Yes',
          'userfolder_onfirstvisit' => 'No',
          'userfolder_update' => 'Yes',
          'userfolder_remove' => 'Yes',
          'download_template' => '',
          'upload_template' => '',
          'delete_template' => '',
          'manage_permissions' => 'Yes',
          'permission_domain' => '',
          'lostauthorization_notification' => get_site_option('admin_email')));

      if ($this->settings === false) {
        return;
      }

      /* Remove 'advancedsettings' option of versions before 1.3.4 */
      $advancedsettings = get_option('use_your_drive_advancedsettings');
      if ($advancedsettings !== false && $this->settings !== false) {
        $this->settings = array_merge($this->settings, $advancedsettings);
        delete_option('use_your_drive_advancedsettings');
        update_option('use_your_drive_settings', $this->settings);
        $this->settings = get_option('use_your_drive_settings');
      }

      /* Set default values */
      if (empty($this->settings['google_analytics'])) {
        $this->settings['google_analytics'] = 'No';
      }

      if (empty($this->settings['download_template'])) {
        $this->settings['download_template'] = 'Hi!

%visitor% has downloaded a file from your site: %filename% (%filesize%)';
      }
      if (empty($this->settings['upload_template'])) {
        $this->settings['upload_template'] = 'Hi!

%visitor% has uploaded the following file(s) to your Google Drive:

%filelist%';
      }
      if (empty($this->settings['delete_template'])) {
        $this->settings['delete_template'] = 'Hi!

%visitor% has deleted the following file(s) on your Google Drive:

%filelist%';
      }

      if (empty($this->settings['mediaplayer_skin'])) {
        $this->settings['mediaplayer_skin'] = 'default';
      }

      if (empty($this->settings['loadimages'])) {
        $this->settings['loadimages'] = 'googlethumbnail';
      }
      if (empty($this->settings['lightbox_skin'])) {
        $this->settings['lightbox_skin'] = 'metro-black';
      }
      if (empty($this->settings['lightbox_path'])) {
        $this->settings['lightbox_path'] = 'vertical';
      }

      if (empty($this->settings['manage_permissions'])) {
        $this->settings['manage_permissions'] = 'Yes';
      }

      if (empty($this->settings['permission_domain'])) {
        $this->settings['permission_domain'] = '';
      }

      if (empty($this->settings['lostauthorization_notification'])) {
        $this->settings['lostauthorization_notification'] = get_site_option('admin_email');
      }

      update_option('use_your_drive_settings', $this->settings);
    }

    public function AddSettingsLink($links, $file) {
      $plugin = plugin_basename(__FILE__);

      /* create link */
      if ($file == $plugin && !is_network_admin()) {
        return array_merge(
                $links, array(sprintf('<a href="options-general.php?page=%s">%s</a>', 'UseyourDrive_settings', __('Settings', 'useyourdrive')))
        );
      }

      return $links;
    }

    public function LoadScripts() {
      wp_register_script('jquery.requestAnimationFrame', plugins_url('includes/iLightBox/js/jquery.requestAnimationFrame.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'includes/iLightBox/js/jquery.requestAnimationFrame.js'));
      wp_register_script('jquery.mousewheel', plugins_url('includes/iLightBox/js/jquery.mousewheel.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'includes/iLightBox/js/jquery.mousewheel.js'));
      wp_register_script('ilightbox', plugins_url('includes/iLightBox/js/ilightbox.packed.js', __FILE__), array('jquery', 'jquery.requestAnimationFrame', 'jquery.mousewheel'), filemtime(plugin_dir_path(__FILE__) . 'includes/iLightBox/js/ilightbox.packed.js'));

      wp_register_script('collagePlus', plugins_url('includes/collagePlus/jquery.collagePlus.min.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'includes/collagePlus/jquery.collagePlus.min.js'));
      wp_register_script('removeWhitespace', plugins_url('includes/collagePlus/extras/jquery.removeWhitespace.min.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'includes/collagePlus/extras/jquery.removeWhitespace.min.js'));
      wp_register_script('unveil', plugins_url('includes/jquery-unveil/jquery.unveil.min.js', __FILE__), array('jquery'), false, true);

      $skin = $this->settings['mediaplayer_skin'];
      if ((!file_exists(USEYOURDRIVE_ROOTDIR . "/skins/$skin/UseyourDrive_Media.js")) ||
              (!file_exists(USEYOURDRIVE_ROOTDIR . "/skins/$skin/css/style.css")) ||
              (!file_exists(USEYOURDRIVE_ROOTDIR . "/skins/$skin/player.php"))) {
        $skin = 'default';
      }

      wp_register_style('UseyourDrive.Media', plugins_url("/skins/$skin/css/style.css", __FILE__), false, (filemtime(USEYOURDRIVE_ROOTDIR . "/skins/$skin/css/style.css")));
      wp_register_script('jQuery.jplayer', plugins_url("/skins/$skin/jquery.jplayer/jplayer.playlist.min.js", __FILE__), array('jquery'));
      wp_register_script('jQuery.jplayer.playlist', plugins_url("/skins/$skin/jquery.jplayer/jquery.jplayer.min.js", __FILE__), array('jquery'));

      wp_register_script('UseyourDrive.Media', plugins_url("/skins/$skin/UseyourDrive_Media.js", __FILE__), array('jquery'), false, true);

      /* load in footer */
      wp_register_script('jQuery.iframe-transport', plugins_url('includes/jquery-file-upload/js/jquery.iframe-transport.js', __FILE__), array('jquery'), false, true);
      wp_register_script('jQuery.fileupload', plugins_url('includes/jquery-file-upload/js/jquery.fileupload.js', __FILE__), array('jquery'), false, true);
      wp_register_script('jQuery.fileupload-process', plugins_url('includes/jquery-file-upload/js/jquery.fileupload-process.js', __FILE__), array('jquery'), false, true);
      wp_register_script('jQuery.fileupload-validate', plugins_url('includes/jquery-file-upload/js/jquery.fileupload-validate.js', __FILE__), array('jquery'), false, true);
      wp_register_script('jQuery.fileupload-ui', plugins_url('includes/jquery-file-upload/js/jquery.fileupload-ui.js', __FILE__), array('jquery'), false, true);
      wp_register_script('jQuery.fileupload-jquery-ui', plugins_url('includes/jquery-file-upload/js/jquery.fileupload-jquery-ui.js', __FILE__), array('jquery'), false, true);

      wp_register_script('UseyourDrive', plugins_url('includes/UseyourDrive.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'includes/UseyourDrive.js'), true);

      wp_enqueue_script('unveil');

      $post_max_size_bytes = min(UseyourDrive_return_bytes(ini_get('post_max_size')), UseyourDrive_return_bytes(ini_get('upload_max_filesize')));

      $localize = array(
          'plugin_ver' => USEYOURDRIVE_VERSION,
          'plugin_url' => plugins_url('', __FILE__),
          'ajax_url' => admin_url('admin-ajax.php'),
          'js_url' => plugins_url('/skins/' . $this->settings['mediaplayer_skin'] . '/jquery.jplayer', __FILE__),
          'is_mobile' => wp_is_mobile(),
          'lightbox_skin' => $this->settings['lightbox_skin'],
          'lightbox_path' => $this->settings['lightbox_path'],
          'post_max_size' => $post_max_size_bytes,
          'google_analytics' => (($this->settings['google_analytics'] === 'Yes') ? 1 : 0),
          'refresh_nonce' => wp_create_nonce("useyourdrive-get-filelist"),
          'gallery_nonce' => wp_create_nonce("useyourdrive-get-gallery"),
          'upload_nonce' => wp_create_nonce("useyourdrive-upload-file"),
          'delete_nonce' => wp_create_nonce("useyourdrive-delete-entry"),
          'rename_nonce' => wp_create_nonce("useyourdrive-rename-entry"),
          'move_nonce' => wp_create_nonce("useyourdrive-move-entry"),
          'description_nonce' => wp_create_nonce("useyourdrive-edit-description-entry"),
          'addfolder_nonce' => wp_create_nonce("useyourdrive-add-folder"),
          'getplaylist_nonce' => wp_create_nonce("useyourdrive-get-playlist"),
          'createzip_nonce' => wp_create_nonce("useyourdrive-create-zip"),
          'createlink_nonce' => wp_create_nonce("useyourdrive-create-link"),
          'str_success' => __('Success', 'useyourdrive'),
          'str_error' => __('Error', 'useyourdrive'),
          'str_inqueue' => __('In queue', 'useyourdrive'),
          'str_uploading' => __('Uploading', 'useyourdrive'),
          'str_error_title' => __('Error', 'useyourdrive'),
          'str_close_title' => __('Close', 'useyourdrive'),
          'str_start_title' => __('Start', 'useyourdrive'),
          'str_cancel_title' => __('Cancel', 'useyourdrive'),
          'str_delete_title' => __('Delete', 'useyourdrive'),
          'str_save_title' => __('Save', 'useyourdrive'),
          'str_zip_title' => __('Create zip file', 'useyourdrive'),
          'str_delete' => __('Do you really want to delete:', 'useyourdrive'),
          'str_delete_multiple' => __('Do you really want to delete these files?', 'useyourdrive'),
          'str_rename_title' => __('Rename', 'useyourdrive'),
          'str_rename' => __('Rename to:', 'useyourdrive'),
          'str_no_filelist' => __("Oops! This shouldn't happen... Try again!", 'useyourdrive'),
          'str_addfolder_title' => __('Add folder', 'useyourdrive'),
          'str_addfolder' => __('New folder', 'useyourdrive'),
          'str_zip_nofiles' => __('No files found or selected', 'useyourdrive'),
          'str_zip_createzip' => __('Creating zip file', 'useyourdrive'),
          'str_share_link' => __('Share file', 'useyourdrive'),
          'str_create_shared_link' => __('Creating shared link...', 'useyourdrive'),
          'str_previous_title' => __('Previous', 'useyourdrive'),
          'str_next_title' => __('Next', 'useyourdrive'),
          'str_xhrError_title' => __('This content failed to load', 'useyourdrive'),
          'str_imgError_title' => __('This image failed to load', 'useyourdrive'),
          'str_startslideshow' => __('Start slideshow', 'useyourdrive'),
          'str_stopslideshow' => __('Stop slideshow', 'useyourdrive'),
          'str_nolink' => __('Not yet linked to a folder', 'useyourdrive'),
          'maxNumberOfFiles' => __('Maximum number of files exceeded', 'useyourdrive'),
          'acceptFileTypes' => __('File type not allowed', 'useyourdrive'),
          'maxFileSize' => __('File is too large', 'useyourdrive'),
          'minFileSize' => __('File is too small', 'useyourdrive'),
          'str_iframe_loggedin' => "<div class='empty_iframe'><h1>" . __('Still Waiting?', 'useyourdrive') . "</h1><span>" . __("If the document doesn't open, you are probably trying to access a protected file which requires you to be logged in on Google.", 'useyourdrive') . " <strong>" . __('Try to open the file in a new window.', 'useyourdrive') . "</strong></span></div>"
      );

      wp_localize_script('UseyourDrive', 'UseyourDrive_vars', $localize);
    }

    public function LoadLastScripts() {
      /* Load scripts as last to support themes with Isotope */
      wp_register_script('imagesloaded', plugins_url('includes/jquery-qTip/imagesloaded.pkgd.min.js', __FILE__), null, false, true);
      wp_register_script('qtip', plugins_url('includes/jquery-qTip/jquery.qtip.min.js', __FILE__), array('jquery', 'imagesloaded'), false, true);
    }

    public function LoadStyles() {
      /* First looks in theme/template directories for the stylesheet, falling back to plugin directory */
      $cssfile = 'useyourdrive.css';
      if (file_exists(get_stylesheet_directory() . '/' . $cssfile)) {
        $stylesheet = get_stylesheet_directory_uri() . '/' . $cssfile;
      } elseif (file_exists(get_template_directory() . '/' . $cssfile)) {
        $stylesheet = get_template_directory_uri() . '/' . $cssfile;
      } else {
        $stylesheet = plugins_url('css/' . $cssfile, __FILE__);
      }

      wp_register_style('UseyourDrive-fileupload-jquery-ui', plugins_url('includes/jquery-file-upload/css', __FILE__) . '/jquery.fileupload-ui.css');

      $skin = $this->settings['lightbox_skin'];
      wp_register_style('ilightbox', plugins_url('includes/iLightBox/css/ilightbox.css', __FILE__), false, (filemtime(__DIR__ . "/includes/iLightBox/css/ilightbox.css")));
      wp_register_style('ilightbox-skin', plugins_url('includes/iLightBox/' . $skin . '-skin/skin.css', __FILE__), false, (filemtime(__DIR__ . "/includes/iLightBox/" . $skin . "-skin/skin.css")));

      wp_register_style('qtip', plugins_url('includes/jquery-qTip/jquery.qtip.min.css', __FILE__), null, false);
      wp_register_style('UseyourDrive-dialogs', plugins_url('css', __FILE__) . '/jquery-ui-1.10.3.custom.css');

      wp_register_style('Awesome-Font-css', plugins_url('includes/font-awesome/css/font-awesome.min.css', __FILE__), false, (filemtime(__DIR__ . "/includes/font-awesome/css/font-awesome.min.css")));
      wp_enqueue_style('Awesome-Font-css');

      wp_register_style('UseyourDrive', $stylesheet, false, filemtime(__FILE__));
      wp_enqueue_style('UseyourDrive');
    }

    public function LoadIEstyles() {

      echo "<!--[if IE]>\n";
      echo "<link rel='stylesheet' type='text/css' href='" . plugins_url('css/useyourdrive-skin-ie.css', __FILE__) . "' />\n";
      echo "<![endif]-->\n";
    }

    public function GravityFormsAddon() {
      require_once 'includes/UseyourDrive_GravityForms.php';
    }

    public function StartProcess() {
      if (isset($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
          case 'useyourdrive-get-filelist':
            require_once 'includes/UseyourDrive_Filebrowser.php';
            $processor = new UseyourDrive_Filebrowser;
            $processor->startProcess();
            break;
          case 'useyourdrive-download':
          case 'useyourdrive-preview':
          case 'useyourdrive-create-zip':
          case 'useyourdrive-create-link':
          case 'useyourdrive-embedded':
          case 'useyourdrive-revoke':
            require_once(ABSPATH . 'wp-includes/pluggable.php');
            require_once 'includes/UseyourDrive.php';
            $processor = new UseyourDrive;
            $processor->startProcess();
            break;

          case 'useyourdrive-get-gallery':
            require_once 'includes/UseyourDrive_Gallery.php';
            $processor = new UseyourDrive_Gallery;
            $processor->startProcess();
            break;

          case 'useyourdrive-upload-file':
          case 'useyourdrive-delete-entry':
          case 'useyourdrive-delete-entries':
          case 'useyourdrive-rename-entry':
          case 'useyourdrive-move-entry':
          case 'useyourdrive-edit-description-entry':
          case 'useyourdrive-add-folder':
            require_once 'includes/UseyourDrive.php';
            $processor = new UseyourDrive;
            $processor->startProcess();
            break;



          case 'useyourdrive-get-playlist':
            require_once 'includes/UseyourDrive_Mediaplayer.php';
            $processor = new UseyourDrive_Mediaplayer;
            $processor->startProcess();
            break;
        }
      }
    }

    public function CustomCss() {
      if (!empty($this->settings['custom_css'])) {
        echo '<!-- Custom UseyourDrive CSS Styles -->' . "\n";
        echo '<style type="text/css" media="screen">' . "\n";
        echo $this->settings['custom_css'] . "\n";
        echo '</style>' . "\n";
      }
    }

    public function CreateTemplate($atts = array()) {
      if (class_exists('Google_Client') && (!method_exists('Google_Client', 'getLibraryVersion'))) {
        return 'Use-your-Drive - Error: ' . __("We are not able to connect to the Google API as the plugin is interfering with an other plugin", 'useyourdrive') . '. ';
      }
      require_once 'includes/UseyourDrive.php';
      $processor = new UseyourDrive;
      return $processor->createFromShortcode($atts);
    }

    public function GetPopup() {
      include USEYOURDRIVE_ROOTDIR . '/templates/tinymce_popup.php';
      die();
    }

    public function UnlinkUserToFolder() {
      check_ajax_referer('useyourdrive-create-link');

      if (current_user_can('manage_options')) {
        if ($_REQUEST['userid'] === 'GUEST') {
          $result = delete_site_option('use_your_drive_guestlinkedto');
        } else {
          $result = delete_user_option($_REQUEST['userid'], 'use_your_drive_linkedto', false);
        }

        if ($result !== false) {
          die('1');
        }
      }
      die('-1');
    }

    public function LinkUserToFolder() {
      check_ajax_referer('useyourdrive-create-link');

      if (current_user_can('manage_options')) {
        $linkedto = array('folderid' => $_REQUEST['id'], 'foldertext' => $_REQUEST['text']);

        if ($_REQUEST['userid'] === 'GUEST') {
          $result = update_site_option('use_your_drive_guestlinkedto', $linkedto);
        } else {
          $result = update_user_option($_REQUEST['userid'], 'use_your_drive_linkedto', $linkedto, false);
        }

        if ($result !== false) {
          die('1');
        }
      }
      die('-1');
    }

    public function UpdateUserfolder($user_id, $old_user_data = false) {
      $useyourdrivelists = get_option('use_your_drive_lists', array());
      $updatelists = array();

      foreach ($useyourdrivelists as $list) {

        if (isset($list['user_upload_folders']) && $list['user_upload_folders'] === 'auto') {
          $updatelists[] = $list;
        }
      }

      if (count($updatelists) > 0) {
        require_once 'includes/UseyourDrive.php';
        $processor = new UseyourDrive;

        foreach ($updatelists as $listoptions) {

          $oldfoldername = false;
          if ($old_user_data !== false) {
            $oldfoldername = strtr($processor->settings['userfolder_name'], array(
                "%user_login%" => $old_user_data->user_login,
                "%user_email%" => $old_user_data->user_email,
                "%user_firstname%" => isset($old_user_data->user_firstname) ? $old_user_data->user_firstname : '?',
                "%user_lastname%" => isset($old_user_data->user_lastname) ? $old_user_data->user_lastname : '?',
                "%display_name%" => $old_user_data->display_name,
                "%ID%" => $old_user_data->ID
            ));
          }

          $new_user = get_user_by('id', $user_id);

          $userfoldername = strtr($processor->settings['userfolder_name'], array(
              "%user_login%" => $new_user->user_login,
              "%user_email%" => $new_user->user_email,
              "%user_firstname%" => isset($new_user->user_firstname) ? $new_user->user_firstname : '?',
              "%user_lastname%" => isset($new_user->user_lastname) ? $new_user->user_lastname : '?',
              "%display_name%" => $new_user->display_name,
              "%ID%" => $new_user->ID
          ));


          if ($oldfoldername === false || ($oldfoldername !== $userfoldername)) {
            $processor->userChangeFolder($listoptions, $userfoldername, $oldfoldername, false);
          }
        }
      }
    }

    public function DeleteUserfolder($user_id) {
      $useyourdrivelists = get_option('use_your_drive_lists', array());
      $updatelists = array();

      foreach ($useyourdrivelists as $list) {

        if (isset($list['user_upload_folders']) && $list['user_upload_folders'] === 'auto') {
          $updatelists[] = $list;
        }
      }

      if (count($updatelists) > 0) {
        require_once 'includes/UseyourDrive.php';
        $processor = new UseyourDrive;

        foreach ($updatelists as $listoptions) {

          $deleted_user = get_user_by('id', $user_id);

          $userfoldername = strtr($processor->settings['userfolder_name'], array(
              "%user_login%" => $deleted_user->user_login,
              "%user_email%" => $deleted_user->user_email,
              "%display_name%" => $deleted_user->display_name,
              "%ID%" => $deleted_user->ID
          ));

          $processor->userChangeFolder($listoptions, $userfoldername, false, true);
        }
      }
    }

  }

}

if (class_exists('UseyourDrive_Plugin')) {
  /* Installation and uninstallation hooks */
  register_activation_hook(__FILE__, 'UseyourDrive_Network_Activate');
  register_deactivation_hook(__FILE__, 'UseyourDrive_Network_Deactivate');
  register_uninstall_hook(__FILE__, 'UseyourDrive_Network_Uninstall');

  $UseyourDrive_Plugin = new UseyourDrive_Plugin();
}

/* Activation & Deactivation */

/**
 * Activate the plugin on network
 */
function UseyourDrive_Network_Activate($network_wide) {
  if (is_multisite() && $network_wide) { // See if being activated on the entire network or one blog
    global $wpdb;

    // Get this so we can switch back to it later
    $current_blog = $wpdb->blogid;
    // For storing the list of activated blogs
    $activated = array();

    // Get all blogs in the network and activate plugin on each one
    $sql = "SELECT blog_id FROM %d";
    $blog_ids = $wpdb->get_col($wpdb->prepare($sql, $wpdb->blogs));
    foreach ($blog_ids as $blog_id) {
      switch_to_blog($blog_id);
      UseyourDrive_Activate(); // The normal activation function
      $activated[] = $blog_id;
    }

    // Switch back to the current blog
    switch_to_blog($current_blog);

    // Store the array for a later function
    update_site_option('use_your_drive_activated', $activated);
  } else { // Running on a single blog
    UseyourDrive_Activate(); // The normal activation function
  }
}

/**
 * Activate the plugin
 */
function UseyourDrive_Activate() {
  add_option('use_your_drive_settings', array(
      'googledrive_app_client_id' => '',
      'googledrive_app_client_secret' => '',
      'googledrive_app_refresh_token' => '',
      'googledrive_app_current_token' => '',
      'purcase_code' => '',
      'custom_css' => '',
      'google_analytics' => 'No',
      'loadimages' => 'googlethumbnail',
      'lightbox_skin' => 'metro-black',
      'lightbox_path' => 'vertical',
      'mediaplayer_skin' => 'default',
      'userfolder_name' => '%user_login% (%user_email%)',
      'userfolder_oncreation' => 'Yes',
      'userfolder_onfirstvisit' => 'No',
      'userfolder_update' => 'Yes',
      'userfolder_remove' => 'Yes',
      'download_template' => '',
      'upload_template' => '',
      'delete_template' => '',
      'manage_permissions' => 'Yes',
      'permission_domain' => '',
      'lostauthorization_notification' => get_site_option('admin_email'))
  );

  update_option('use_your_drive_lists', array());

  add_option('use_your_drive_cache', array(
      'last_update' => null,
      'last_cache_id' => '',
      'locked' => false,
      'cache' => ''
  ));
}

/**
 * Deactivate the plugin on network
 */
function UseyourDrive_Network_Deactivate($network_wide) {
  if (is_multisite() && $network_wide) { // See if being activated on the entire network or one blog
    global $wpdb;

    // Get this so we can switch back to it later
    $current_blog = $wpdb->blogid;

    // If the option does not exist, plugin was not set to be network active
    if (get_site_option('use_your_drive_activated') === false) {
      return false;
    }

    // Get all blogs in the network
    $activated = get_site_option('use_your_drive_activated'); // An array of blogs with the plugin activated

    $sql = "SELECT blog_id FROM %d";
    $blog_ids = $wpdb->get_col($wpdb->prepare($sql, $wpdb->blogs));
    foreach ($blog_ids as $blog_id) {
      if (!in_array($blog_id, $activated)) { // Plugin is not activated on that blog
        switch_to_blog($blog_id);
        UseyourDrive_Deactivate();
      }
    }

    // Switch back to the current blog
    switch_to_blog($current_blog);

    // Store the array for a later function
    update_site_option('use_your_drive_activated', $activated);
  } else { // Running on a single blog
    UseyourDrive_Deactivate();
  }
}

/**
 * Deactivate the plugin
 */
function UseyourDrive_Deactivate() {
  update_option('use_your_drive_lists', array());

  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(USEYOURDRIVE_CACHEDIR, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {

    if ($path->getFilename() === '.htaccess') {
      continue;
    }

    $path->isFile() ? @unlink($path->getPathname()) : @rmdir($path->getPathname());
  }

  delete_option('use_your_drive_lists');
  delete_option('use_your_drive_cache');
}

function UseyourDrive_Network_Uninstall($network_wide) {
  if (is_multisite() && $network_wide) { // See if being activated on the entire network or one blog
    global $wpdb;

    // Get this so we can switch back to it later
    $current_blog = $wpdb->blogid;

    // If the option does not exist, plugin was not set to be network active
    if (get_site_option('use_your_drive_activated') === false) {
      return false;
    }

    // Get all blogs in the network
    $activated = get_site_option('use_your_drive_activated'); // An array of blogs with the plugin activated

    $sql = "SELECT blog_id FROM %d";
    $blog_ids = $wpdb->get_col($wpdb->prepare($sql, $wpdb->blogs));
    foreach ($blog_ids as $blog_id) {
      if (!in_array($blog_id, $activated)) { // Plugin is not activated on that blog
        switch_to_blog($blog_id);
        UseyourDrive_Uninstall();
      }
    }

    // Switch back to the current blog
    switch_to_blog($current_blog);


    delete_option('use_your_drive_activated');
  } else { // Running on a single blog
    UseyourDrive_Uninstall();
  }
}

function UseyourDrive_Uninstall() {
  delete_option('use_your_drive_lists');
  delete_option('use_your_drive_activated');
  delete_option('use_your_drive_cache');
  delete_site_option('use_your_drive_guestlinkedto');
}

/* Helpers */

function UseyourDrive_return_bytes($size_str) {
  switch (substr($size_str, -1)) {
    case 'M': case 'm': return (int) $size_str * 1048576;
    case 'K': case 'k': return (int) $size_str * 1024;
    case 'G': case 'g': return (int) $size_str * 1073741824;
    default: return $size_str;
  }
}

function UseyourDrive_bytesToSize1024($bytes, $precision = 2) {
  /* human readable format -- powers of 1024 */
  $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
  return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision) . ' ' . $unit[$i];
}
