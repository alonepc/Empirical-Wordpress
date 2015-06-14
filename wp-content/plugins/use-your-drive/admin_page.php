<?php
if (!class_exists('UseyourDrive_Settings')) {

  class UseyourDrive_settings {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $settings_key = 'use_your_drive_settings';
    private $plugin_options_key = 'UseyourDrive_settings';
    private $plugin_network_options_key = 'UseyourDrive_network_settings';
    private $plugin_settings_tabs = array();
    private $canconnect = true;

    /**
     * Construct the plugin object
     */
    public function __construct() {
      /* Check if plugin can be used */
      if ((version_compare(PHP_VERSION, '5.3.0') < 0) || ((!function_exists('curl_init')) && ((!ini_get('allow_url_fopen'))))) {
        add_action('admin_notices', array(&$this, 'AdminNotice'));
        return;
      } elseif (class_exists('Google_Client') && (!method_exists('Google_Client', 'getLibraryVersion'))) {
        add_action('admin_notices', array(&$this, 'AdminNotice'));
        return;
      } else {
        /* Init */
        add_action('init', array(&$this, 'LoadSettings'));
        add_action('admin_init', array(&$this, 'RegisterSettings'));
        add_action('admin_init', array(&$this, 'CheckForUpdates'));
        add_action('admin_enqueue_scripts', array(&$this, 'LoadAdmin'));

        /* add TinyMCE button */
        add_action('init', array(&$this, 'ShortcodeButtonInit'));

        /* Add menu's */
        add_action('admin_menu', array(&$this, 'AddMenu'));
        add_action('network_admin_menu', array(&$this, 'AddNetworkMenu'));

        /* Network save settings call */
        add_action('network_admin_edit_' . $this->plugin_network_options_key, array($this, 'SaveNetworkSettings'));

        /* Notices */
        add_action('admin_notices', array(&$this, 'AdminNotice_NotAuthorized'));
      }
    }

    public function LoadAdmin($hook) {

      if (class_exists('Google_Client') && (!method_exists('Google_Client', 'getLibraryVersion'))) {
        add_action('admin_notices', array(&$this, 'AdminNotice'));
        $this->UseyourDrive = false;
        $this->canconnect = false;
        return;
      }

      if (!isset($this->settingspage) && !isset($this->filebrowserpage)) {
        return;
      } elseif ($hook == $this->settingspage || $hook == $this->filebrowserpage || $hook == $this->userpage) {
        require_once 'includes/UseyourDrive_Processor.php';
        require_once 'includes/UseyourDrive.php';
        $this->UseyourDrive = new UseyourDrive;
      }

      if ($hook == $this->filebrowserpage || $hook == $this->userpage) {
        global $UseyourDrive_Plugin;
        $UseyourDrive_Plugin->LoadScripts();
        $UseyourDrive_Plugin->LoadLastScripts();
        $UseyourDrive_Plugin->LoadStyles();
      }

      if ($hook == $this->userpage) {
        add_thickbox();
      }

      if ($hook == $this->settingspage) {
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-position');
        wp_enqueue_script('jquery-effects-fade');
        wp_enqueue_script('jquery');

        wp_register_script('ddslick', USEYOURDRIVE_ROOTPATH . '/includes/jquery-ddslick/jquery.ddslick.min.js', array('jquery'), false, true);
        wp_enqueue_script('ddslick');

        wp_register_script('Radiobuttons', USEYOURDRIVE_ROOTPATH . '/includes/jquery-radiobutton/jquery-radiobutton-2.0.js', array('jquery'), false, true);
        wp_enqueue_script('Radiobuttons');

        wp_register_script('qtip', plugins_url('includes/jquery-qTip/jquery.qtip.min.js', __FILE__), array('jquery'), false, true);
        wp_enqueue_script('qtip');

        wp_register_script('unveil', plugins_url('includes/jquery-unveil/jquery.unveil.min.js', __FILE__), array('jquery'), false, true);
        wp_enqueue_script('unveil');

        wp_register_script('UseyourDrive.tinymce', USEYOURDRIVE_ROOTPATH . '/includes/UseyourDrive_tinymce_popup.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/UseyourDrive_tinymce_popup.js'), true);
        wp_enqueue_script('UseyourDrive.tinymce');

        wp_register_style('qtip', plugins_url('includes/jquery-qTip/jquery.qtip.min.css', __FILE__), null, false);
        wp_enqueue_style('qtip');

        wp_register_style('UseyourDrive.tinymce', plugins_url('css/useyourdrive_tinymce.css', __FILE__), null, false);
        wp_enqueue_style('UseyourDrive.tinymce');

        wp_register_style('UseyourDrive-dialogs', plugins_url('css', __FILE__) . '/jquery-ui-1.10.3.custom.css');
        wp_enqueue_style('UseyourDrive-dialogs');
      }
    }

    /**
     * add a menu
     */
    public function AddMenu() {
      /* Add a page to manage this plugin's settings */
      add_menu_page('Use-your-Drive', 'Use-your-Drive', 'manage_options', $this->plugin_options_key, array(&$this, 'SettingsPage'), plugin_dir_url(__FILE__) . 'css/images/google_drive_logo_small.png');
      $this->settingspage = add_submenu_page($this->plugin_options_key, 'Use-your-Drive - ' . __('Settings'), __('Settings'), 'manage_options', $this->plugin_options_key, array(&$this, 'SettingsPage'));
      $this->userpage = add_submenu_page($this->plugin_options_key, __('Link users to folder', 'useyourdrive'), __('Link users to folder', 'useyourdrive'), 'manage_options', $this->plugin_options_key . '_linkusers', array(&$this, 'LinkUsers'));
      $this->filebrowserpage = add_submenu_page($this->plugin_options_key, __('File browser', 'useyourdrive'), __('File browser', 'useyourdrive'), 'manage_options', $this->plugin_options_key . '_filebrowser', array(&$this, 'Filebrowser'));
    }

    public function AddNetworkMenu() {
      add_menu_page('Use-your-Drive', 'Use-your-Drive', 'manage_options', $this->plugin_network_options_key, array(&$this, 'NetworkSettingsPage'), plugin_dir_url(__FILE__) . 'css/images/google_drive_logo_small.png');
    }

    public function RegisterSettings() {
      register_setting($this->settings_key, $this->settings_key);
    }

    function LoadSettings() {
      $this->settings = (array) get_option($this->settings_key);

      if (!isset($this->settings['googledrive_app_client_id'])) {
        $this->settings['googledrive_app_client_id'] = '';
        $this->settings['googledrive_app_client_secret'] = '';
      }
      update_option($this->settings_key, $this->settings);
    }

    public function SettingsPage() {
      if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'useyourdrive'));
      }

      if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        update_option('use_your_drive_lists', array());
        $this->UseyourDrive->cache->resetCache();
      }

      include(sprintf("%s/templates/admin.php", USEYOURDRIVE_ROOTDIR));
    }

    public function NetworkSettingsPage() {
      $useyourdrive_purchaseid = get_site_option('useyourdrive_purchaseid');
      ?>
      <div class="wrap">
        <div class='left' style="min-width:400px; max-width:650px; padding: 0 20px 0 0; float:left">
          <?php if ($_GET['updated']) { ?>
            <div id="message" class="updated"><p><?php _e('Saved!', 'useyourdrive'); ?></p></div>
          <?php } ?>
          <form action="<?php echo network_admin_url('edit.php?action=' . $this->plugin_network_options_key); ?>" method="post">
            <?php
            echo __('If you would like to receive updates, please insert your Purchase code', 'useyourdrive') . '. ' .
            '<a href="http://support.envato.com/index.php?/Knowledgebase/Article/View/506/54/where-can-i-find-my-purchase-code">' .
            __('Where do I find the purchase code?', 'useyourdrive') . '</a>.';
            ?>
            <table class="form-table">
              <tbody>
                <tr valign="top">
                  <th scope="row"><?php _e('Purchase Code', 'useyourdrive'); ?></th>
                  <td><input type="text" name="useyourdrive_purchaseid" id="useyourdrive_purchaseid" value="<?php echo $useyourdrive_purchaseid; ?>" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX" maxlength="37" style="width:90%"/></td>
                </tr>
              </tbody>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
          </form>
        </div>
        <div class='right' style='float:left; width: 266px;'>
          <a href="http://goo.gl/JbV7pK" target="_blank">
            <img src="<?php echo plugins_url('css/images/Use-your-Drive-Logo.png', __FILE__); ?>" title="Use-your-Drive: a Google Drive plugin for Wordpress" width="266"/>
            <img src="<?php echo plugins_url('css/images/Out-of-the-Box-Logo.png', __FILE__); ?>" title="Out-of-the-Box: a Dropbox plugin for Wordpress" width="266"  style="margin-top: -4px;"/>

          </a>
        </div>
      </div>
      <?php
    }

    public function SaveNetworkSettings() {
      if (current_user_can('manage_network_options')) {
        update_site_option('useyourdrive_purchaseid', $_POST['useyourdrive_purchaseid']);
      }

      wp_redirect(
              add_query_arg(
                      array('page' => $this->plugin_network_options_key, 'updated' => 'true'), network_admin_url('admin.php')
              )
      );
      exit;
    }

    function Filebrowser() {
      ?>
      <div class="wrap adminfilebrowser">
        <?php
        echo '<h2>' . __('File browser', 'useyourdrive') . '</h2>';
        echo $this->UseyourDrive->createFromShortcode(
                array('mode' => 'files',
                    'viewrole' => 'all',
                    'downloadrole' => 'all',
                    'uploadrole' => 'all',
                    'upload' => '1',
                    'rename' => '1',
                    'delete' => '1',
                    'addfolder' => '1')
        );
        ?>
      </div>
      <?php
    }

    function LinkUsers() {
      require_once 'includes/UseyourDrive_LinkUsers.php';
    }

    public function CheckDriveApp() {
      if (!$this->canconnect) {
        $this->AdminNotice(true);
        return false;
      }

      $authorize = true;
      $authorizebutton = "<input id='authorizeDrive_button' type='button' value='" . __('(Re) Authorize the Plugin!', 'useyourdrive') . "' class='button-primary'/>";
      $revokebutton = "<input id='revokeDrive_button' type='button' value='" . __('Revoke authorization', 'useyourdrive') . "' class='button-secondary'/>&nbsp;";

      $appInfo = $this->UseyourDrive->setAppConfig('force');
      if (is_wp_error($appInfo)) {
        echo "<div id='message' class='error'><p>" . $appInfo->get_error_message() . "</p></div>";
        return false;
      }

      /* are we coming from Google API auth page? */
      if (!empty($_GET['code'])) {
        $createToken = $this->UseyourDrive->createToken();

        if (is_wp_error($createToken)) {
          echo "<div id='message' class='error'><p>" . $createToken->get_error_message() . '</p><p>' . $authorizebutton . "</p></div>";
        } else {
          $page = isset($_GET["page"]) ? '?page=' . $_GET["page"] : '';
          $location = get_admin_url(null, 'admin.php' . $page);

          echo "<script type='text/javascript'>window.location.href = '" . $location . "';</script>";
        }
      }

      $authUrl = $this->UseyourDrive->startWebAuth();
      $hasToken = $this->UseyourDrive->loadToken();

      if (!empty($_GET['error']) && $_GET['error'] === 'access_denied') {
        $this->UseyourDrive->revokeToken();
        $hasToken = new WP_Error('broke', __("The plugin isn't yet authorized to use your Google Drive! Please (re)-authorize the plugin", 'useyourdrive'));
      }

      if (is_wp_error($hasToken)) {
        echo "<div id='message' class='error'><p>" . $hasToken->get_error_message() . '</p><p>' . $authorizebutton . "</p></div>";
      } else {

        $client = $this->UseyourDrive->startClient();
        $accountInfo = $this->UseyourDrive->getAccountInfo();

        if ($accountInfo === false) {
          $error = new WP_Error('broke', __("Plugin isn't linked to your Google Drive anymore... Please Reauthorize!", 'useyourdrive'));
          echo "<div id='message' class='error'><p>" . $error->get_error_message() . '</p><p>' . $authorizebutton . "</p></div>";
        } else if (is_wp_error($accountInfo)) {
          $error = $accountInfo;
          echo "<div id='message' class='error'><p>" . $error->get_error_message() . '</p><p>' . $authorizebutton . "</p></div>";
        } else {
          $user = $accountInfo->getName();
          $email = $accountInfo->getEmail();

          $driveInfo = $this->UseyourDrive->getDriveInfo();

          if (is_wp_error($driveInfo)) {
            $error = $driveInfo;
            echo "<div id='message' class='error'><p><strong>$user ($email)</strong></p><p><i>" . $error->get_error_message() . "</i></p><p>" . __('Use-your-Drive can\'t access your Google Drive as the Google Drive App isn\'t properly configurated.', 'useyourdrive') . " Please use <a href='https://cloud.google.com/console' target='_blank'>Google Developers Console</a> to activate the <strong>Drive API</strong> (not SDK) for your project. You can find this setting under 'APIs & auth'.</p><p>" . $revokebutton . "</p></div>";
          } else {
            $storage = UseyourDrive_bytesToSize1024($driveInfo->getQuotaBytesUsed()) . '/' . UseyourDrive_bytesToSize1024($driveInfo->getQuotaBytesTotal());
            $authorize = false;
            echo "<div id='message' class='updated'><p>" . __('Use-your-Drive is succesfully authorized and linked with your Google account:', 'useyourdrive') . "<br/><strong>$user ($email - $storage)</strong></p><p>" . $revokebutton . $authorizebutton . "</p></div>";
          }
        }
      }
      ?>
      <script type="text/javascript" >
        jQuery(document).ready(function ($) {
          $('#authorizeDrive_button').click(function () {
            window.location = '<?php echo $authUrl; ?>';
          });
          $('#revokeDrive_button').click(function () {
            $.ajax({type: "POST",
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              data: {
                action: 'useyourdrive-revoke'
              },
              success: function (response) {
                location.reload(true)
              },
              dataType: 'json'
            });
          });
        });
      </script>
      <?php
    }

    public function AdminNotice($force = false) {
      global $pagenow;
      if ($pagenow == 'index.php' || $pagenow == 'plugins.php' || $force === true) {
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
          echo '<div id="message" class="error"><p><strong>Use-your-Drive - Error: </strong>' . __('You need at least PHP 5.3 if you want to use Use-your-Drive', 'useyourdrive') . '. ' .
          __('You are using:', 'useyourdrive') . ' <u>' . phpversion() . '</u></p></div>';
        } elseif (!function_exists('curl_init') && ((!ini_get('allow_url_fopen')))) {
          $this->canconnect = false;
          echo '<div id="message" class="error"><p><strong>Use-your-Drive - Error: </strong>' .
          __("We are not able to connect to the Google API as 'allow_url_fopen' is disabled and you don't have the cURL PHP extension installed", 'useyourdrive') . '. ' .
          __("Please enable allow_url_fopen or install the cURL extension", 'useyourdrive') . '. ' .
          '</p></div>';
        } elseif (class_exists('Google_Client') && (!method_exists('Google_Client', 'getLibraryVersion'))) {
          $this->canconnect = false;
          echo '<div id="message" class="error"><p><strong>Use-your-Drive - Error: </strong>' .
          __("We are not able to connect to the Google API as the plugin is interfering with an other plugin", 'useyourdrive') . '. <br/><br/>' .
          __("The other plugin is using an old version of the Google-Api-PHP-client that isn't capable of running multiple configurations", 'useyourdrive') . '. ' .
          __("Please disable this other plugin if you would like to use Use-your-Drive", 'useyourdrive') . '. ' .
          __("If you would like to use both plugins, ask the developer to update it's code", 'useyourdrive') . '. ' .
          '</p></div>';
        }
      }
    }

    public function AdminNotice_NotAuthorized() {
      global $pagenow;
      if ($pagenow == 'index.php' || $pagenow == 'plugins.php') {
        if (current_user_can('manage_options') || current_user_can('edit_theme_options')) {
          if ($this->settings['googledrive_app_current_token'] === '') {
            $location = get_admin_url(null, 'admin.php?page=UseyourDrive_settings');
            echo '<div id="message" class="error"><p><strong>Use-your-Drive: </strong>' . __('The plugin isn\'t autorized to use your Google Drive', 'useyourdrive') . '. ' .
            "<a href='$location' class='button-primary'>" . __('Authorize the plugin!', 'useyourdrive') . '</a></p></div>';
          }
        }
      }
    }

    public function CheckForUpdates() {
      /* Updater */
      $purchasecode = false;

      $plugin = dirname(plugin_basename(__FILE__)) . '/use-your-drive.php';
      if (is_multisite() && is_plugin_active_for_network($plugin)) {
        $purchasecode = get_site_option('useyourdrive_purchaseid');
      } else {
        $purchasecode = $this->settings['purcase_code'];
      }

      if (!empty($purchasecode)) {
        require_once('wp-updates-plugin.php');
        new WPUpdatesPluginUpdater_339('http://wp-updates.com/api/2/plugin', $plugin, $purchasecode);
      }
    }

    public function checkDependencies() {
      $check = array();

      array_push($check, array('success' => true, 'warning' => false, 'value' => __('Plugin version', 'useyourdrive'), 'description' => USEYOURDRIVE_VERSION));

      if (version_compare(PHP_VERSION, '5.3.0') < 0) {
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('PHP version', 'useyourdrive'), 'description' => phpversion() . ' ' . __('You need at least PHP 5.3 if you want to use Use-your-Drive', 'useyourdrive')));
      } else {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('PHP version', 'useyourdrive'), 'description' => phpversion()));
      }

      /* Check if we can use CURL */
      if (function_exists('curl_init')) {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('cURL PHP extension', 'useyourdrive'), 'description' => __('You have the cURL PHP extension installed and we can access Google with cURL', 'useyourdrive')));
      } else {
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('cURL PHP extension', 'useyourdrive'), 'description' => __("You don't have the cURL PHP extension installed (couldn't find function \"curl_init\"), please enable or install this extension", 'useyourdrive')));
      }

      /* Check if we can use fOpen */
      if (ini_get('allow_url_fopen')) {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is allow_url_fopen enabled?', 'useyourdrive'), 'description' => __('Yes, we can access Google with fopen', 'useyourdrive')));
      } else {
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('Is allow_url_fopen enabled?', 'useyourdrive'), 'description' => __("No, we can't access Google with fopen", 'useyourdrive')));
      }

      /* Check which version of the Google API Client we are using */
      if (class_exists('Google_Client') && (method_exists('Google_Client', 'getLibraryVersion'))) {
        $googleClient = new Google_Client;
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Version Google Api Client', 'useyourdrive'), 'description' => $googleClient->getLibraryVersion()));
      } else {
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('Version Google Api Client', 'useyourdrive'), 'description' => __("Before version 1.0.0", 'useyourdrive') . '. ' . __("Another plugin is loading an old Google Client library. Use-your-Drive isn't compatible with this version.", 'useyourdrive')));
      }

      /* Check if temp dir is writeable */
      $uploadir = wp_upload_dir();

      if (!is_writable($uploadir['path'])) {
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('Is TMP directory writable?', 'useyourdrive'), 'description' => __('TMP directory', 'useyourdrive') . ' \'' . $uploadir['path'] . '\' ' . __('isn\'t writable. You are not able to upload files to Drive.', 'useyourdrive') . ' ' . __('Make sure TMP directory is writable', 'useyourdrive')));
      } else {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is TMP directory writable?', 'useyourdrive'), 'description' => __('TMP directory is writable', 'useyourdrive')));
      }

      /* Check if cache dir is writeable */
      if (!file_exists(USEYOURDRIVE_CACHEDIR)) {
        @mkdir(USEYOURDRIVE_CACHEDIR, 0755);
      }

      if (!is_writable(USEYOURDRIVE_CACHEDIR)) {
        @chmod(USEYOURDRIVE_CACHEDIR, 0755);

        if (!is_writable(USEYOURDRIVE_CACHEDIR)) {
          array_push($check, array('success' => false, 'warning' => false, 'value' => __('Is CACHE directory writable?', 'useyourdrive'), 'description' => __('CACHE directory', 'useyourdrive') . ' \'' . USEYOURDRIVE_CACHEDIR . '\' ' . __('isn\'t writable. The gallery will load very slowly.', 'useyourdrive') . ' ' . __('Make sure CACHE directory is writable', 'useyourdrive')));
        } else {
          array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is CACHE directory writable?', 'useyourdrive'), 'description' => __('CACHE directory is now writable', 'useyourdrive')));
        }
      } else {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is CACHE directory writable?', 'useyourdrive'), 'description' => __('CACHE directory is writable', 'useyourdrive')));
      }

      /* Check if cache index-file is writeable */
      if (!is_readable(USEYOURDRIVE_CACHEDIR . '/index')) {
        @file_put_contents(USEYOURDRIVE_CACHEDIR . '/index', json_encode(array()));

        if (!is_readable(USEYOURDRIVE_CACHEDIR . '/index')) {
          array_push($check, array('success' => false, 'warning' => false, 'value' => __('Is CACHE-index file writable?', 'useyourdrive'), 'description' => __('-index file', 'useyourdrive') . ' \'' . USEYOURDRIVE_CACHEDIR . 'index' . '\' ' . __('isn\'t writable. The gallery will load very slowly.', 'useyourdrive') . ' ' . __('Make sure CACHE-index file is writable', 'useyourdrive')));
        } else {
          array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is CACHE-index file writable?', 'useyourdrive'), 'description' => __('CACHE-index file is now writable', 'useyourdrive')));
        }
      } else {
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Is CACHE-index file writable?', 'useyourdrive'), 'description' => __('CACHE-index file is writable', 'useyourdrive')));
      }

      /* Check if we can use ZIP class */
      if (class_exists('ZipArchive')) {
        $message = __("You can use the ZIP function", 'useyourdrive');
        array_push($check, array('success' => true, 'warning' => false, 'value' => __('Download files as ZIP', 'useyourdrive'), 'description' => $message));
      } else {
        $message = __("You cannot download files as ZIP", 'useyourdrive');
        array_push($check, array('success' => true, 'warning' => true, 'value' => __('Download files as ZIP', 'useyourdrive'), 'description' => $message));
      }

      /* Check if Gravity Forms is installed and can be used */
      if (class_exists("GFForms")) {
        $is_correct_version = false;
        if (class_exists('GFCommon')) {
          $is_correct_version = version_compare(GFCommon::$version, '1.9', '>=');
        }
        if ($is_correct_version) {
          $message = __("You can use Use-your-Drive in Gravity Forms (" . GFCommon::$version . ")", 'useyourdrive');
          array_push($check, array('success' => true, 'warning' => false, 'value' => __('Gravity Forms integration', 'useyourdrive'), 'description' => $message));
        } else {
          $message = __("You have Gravity Forms (" . GFCommon::$version . ") installed, but versions before 1.9 are not supported. Please update Gravity Forms if you want to use this plugin in combination with Gravity Forms", 'useyourdrive');
          array_push($check, array('success' => false, 'warning' => true, 'value' => __('Gravity Forms integration', 'useyourdrive'), 'description' => $message));
        }
      } else {
        $message = __("You don't have Gravity Forms installed", 'useyourdrive');
        array_push($check, array('success' => false, 'warning' => false, 'value' => __('Gravity Forms integration', 'useyourdrive'), 'description' => $message));
      }


      /* Create Table */
      $html = '<table border="0" cellspacing="0" cellpadding="0">';

      foreach ($check as $row) {

        $color = ($row['success']) ? 'green' : 'red';
        $color = ($row['warning']) ? 'orange' : $color;

        $html .= '<tr style="vertical-align:top;"><td width="200" style="padding: 5px; color:' . $color . '"><strong>' . $row['value'] . '</strong></td><td style="padding: 5px;">' . $row['description'] . '</td></tr>';
      }

      $html .= '</table>';

      return $html;
    }

    /*
     * Add MCE buttons and script
     */

    public function ShortcodeButtonInit() {

      /* Abort early if the user will never see TinyMCE */
      if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
        return;

      /* Add a callback to regiser our tinymce plugin */
      add_filter("mce_external_plugins", array(&$this, "RegisterTinymcePlugin"));

      /* Add a callback to add our button to the TinyMCE toolbar */
      add_filter('mce_buttons', array(&$this, 'AddTinymceButton'));

      /* Add custom CSS for placeholders */
      add_editor_style(USEYOURDRIVE_ROOTPATH . '/css/useyourdrive_tinymce_editor.css');
    }

    /* This callback registers our plug-in */

    function RegisterTinymcePlugin($plugin_array) {
      $plugin_array['useyourdrive'] = USEYOURDRIVE_ROOTPATH . "/includes/UseyourDrive_tinymce.js";
      return $plugin_array;
    }

    /* This callback adds our button to the toolbar */

    function AddTinymceButton($buttons) {
      /* Add the button ID to the $button array */
      $buttons[] = "useyourdrive";
      $buttons[] = "useyourdrive_embed";
      $buttons[] = "useyourdrive_links";
      return $buttons;
    }

  }

}