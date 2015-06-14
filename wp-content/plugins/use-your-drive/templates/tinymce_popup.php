<?php
if (!current_user_can('edit_pages')) {
  die();
}

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'default';

if (!function_exists('shortcode_exists')) {

  function shortcode_exists($shortcode = false) {
    global $shortcode_tags;

    if (!$shortcode)
      return false;

    if (array_key_exists($shortcode, $shortcode_tags))
      return true;

    return false;
  }

}

function wp_roles_checkbox($name, $selected = array()) {
  global $wp_roles;
  if (!isset($wp_roles)) {
    $wp_roles = new WP_Roles();
  }

  $roles = $wp_roles->get_names();


  foreach ($roles as $role_value => $role_name) {
    if (in_array($role_value, $selected) || $selected[0] == 'all') {
      $checked = 'checked="checked"';
    } else {
      $checked = '';
    }
    echo '<input class="simple" type="checkbox" name="' . $name . '[]" value="' . $role_value . '" ' . $checked . '>' . $role_name . '<br/>';
  }
  if (in_array('guest', $selected) || $selected[0] == 'all') {
    $checked = 'checked="checked"';
  } else {
    $checked = '';
  }
  echo '<input class="simple" type="checkbox" name="' . $name . '[]" value="guest" ' . $checked . '>' . __('Guest', 'useyourdrive');
}

wp_register_script('collagePlus', USEYOURDRIVE_ROOTPATH . '/includes/collagePlus/jquery.collagePlus.min.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/collagePlus/jquery.collagePlus.min.js'));
wp_register_script('removeWhitespace', USEYOURDRIVE_ROOTPATH . '/includes/collagePlus/extras/jquery.removeWhitespace.min.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/collagePlus/extras/jquery.removeWhitespace.min.js'));
wp_register_script('Radiobuttons', USEYOURDRIVE_ROOTPATH . '/includes/jquery-radiobutton/jquery-radiobutton-2.0.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/jquery-radiobutton/jquery-radiobutton-2.0.js'));
wp_register_script('imagesloaded', USEYOURDRIVE_ROOTPATH . '/includes/jquery-qTip/imagesloaded.pkgd.min.js', null, false, true);
wp_register_script('qtip', USEYOURDRIVE_ROOTPATH . '/includes/jquery-qTip/jquery.qtip.min.js', array('jquery', 'imagesloaded'), false, true);
wp_register_script('unveil', USEYOURDRIVE_ROOTPATH . '/includes/jquery-unveil/jquery.unveil.min.js', array('jquery'), false, true);

wp_register_script('UseyourDrive', USEYOURDRIVE_ROOTPATH . '/includes/UseyourDrive.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/UseyourDrive.js'), true);
wp_register_script('UseyourDrive.tinymce', USEYOURDRIVE_ROOTPATH . '/includes/UseyourDrive_tinymce_popup.js', array('jquery'), filemtime(USEYOURDRIVE_ROOTDIR . '/includes/UseyourDrive_tinymce_popup.js'), true);

function UseyourDrive_remove_all_scripts() {
  global $wp_scripts;
  $wp_scripts->queue = array();

  wp_enqueue_script('jquery-ui-core');
  wp_enqueue_script('jquery-ui-tabs');
  wp_enqueue_script('jquery-ui-tooltip');
  wp_enqueue_script('jquery-ui-widget');
  wp_enqueue_script('jquery-ui-position');
  wp_enqueue_script('jquery-effects-fade');

  wp_enqueue_script('jquery');

  wp_enqueue_script('collagePlus');
  wp_enqueue_script('removeWhitespace');

  wp_enqueue_script('Radiobuttons');
  wp_enqueue_script('imagesloaded');
  wp_enqueue_script('qtip');
  wp_enqueue_script('unveil');

  wp_enqueue_script('UseyourDrive');
  wp_enqueue_script('UseyourDrive.tinymce');
}

add_action('wp_print_scripts', 'UseyourDrive_remove_all_scripts', 100);

$post_max_size_bytes = min(UseyourDrive_return_bytes(ini_get('post_max_size')), UseyourDrive_return_bytes(ini_get('upload_max_filesize')));

$localize = array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'plugin_url' => USEYOURDRIVE_ROOTPATH,
    'js_url' => USEYOURDRIVE_ROOTPATH . '/includes/jQuery.jPlayer',
    'post_max_size' => $post_max_size_bytes,
    'refresh_nonce' => wp_create_nonce("useyourdrive-get-filelist"),
    'gallery_nonce' => wp_create_nonce("useyourdrive-get-gallery"),
    'upload_nonce' => wp_create_nonce("useyourdrive-upload-file"),
    'delete_nonce' => wp_create_nonce("useyourdrive-delete-entry"),
    'rename_nonce' => wp_create_nonce("useyourdrive-rename-entry"),
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
    'str_zip_title' => __('Create zip file', 'useyourdrive'),
    'str_delete' => __('Do you really want to delete:', 'useyourdrive'),
    'str_rename_title' => __('Rename', 'useyourdrive'),
    'str_rename' => __('Rename to:', 'useyourdrive'),
    'str_no_filelist' => __("Can't receive filelist", 'useyourdrive'),
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
    'maxNumberOfFiles' => __('Maximum number of files exceeded', 'useyourdrive'),
    'acceptFileTypes' => __('File type not allowed', 'useyourdrive'),
    'maxFileSize' => __('File is too large', 'useyourdrive'),
    'minFileSize' => __('File is too small', 'useyourdrive')
);

wp_localize_script('UseyourDrive', 'UseyourDrive_vars', $localize);
/* Initialize shortcode vars */
$mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : 'files';
?>
<html>
  <head>
    <title>
      <?php
      if ($type === 'default') {
        _e('Create Shortcode', 'useyourdrive');
        $mcepopup = 'shortcode';
      } else if ($type === 'links') {
        _e('Insert direct links to files or folders', 'useyourdrive');
        $mcepopup = 'links';
      } else if ($type === 'embedded') {
        _e('Embed files', 'useyourdrive');
        $mcepopup = 'embedded';
      } else if ($type === 'gravityforms') {
        _e('Create Shortcode', 'useyourdrive');
        $mcepopup = 'shortcode';
      }
      ?></title>
    <?php if ($type !== 'gravityforms') { ?>
      <script type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
      <script type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
      <script type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
    <?php } ?>
    <base target="_self" />
    <?php wp_print_scripts(); ?>
    <link rel='stylesheet' id='UseyourDrive-jquery-css'  href='<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/jquery-ui-1.10.3.custom.css?ver=<?php echo (filemtime(USEYOURDRIVE_ROOTDIR . "/css/jquery-ui-1.10.3.custom.css")); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='UseyourDrive-css'  href='<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/useyourdrive.css?ver=<?php echo (filemtime(USEYOURDRIVE_ROOTDIR . "/css/useyourdrive.css")); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='UseyourDrive-tinymce-css'  href='<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/useyourdrive_tinymce.css?ver=<?php echo (filemtime(USEYOURDRIVE_ROOTDIR . "/css/useyourdrive_tinymce.css")); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='Awesome-Font-css'  href='<?php echo USEYOURDRIVE_ROOTPATH; ?>/includes/font-awesome/css/font-awesome.min.css?ver=<?php echo (filemtime(USEYOURDRIVE_ROOTDIR . "/includes/font-awesome/css/font-awesome.min.css")); ?>' type='text/css' media='all' />
    <link rel='stylesheet' id='qTip'  href='<?php echo USEYOURDRIVE_ROOTPATH; ?>/includes/jquery-qTip/jquery.qtip.min.css?ver=<?php echo (filemtime(USEYOURDRIVE_ROOTDIR . "/includes/jquery-qTip/jquery.qtip.min.css")); ?>' type='text/css' media='all' />
  </head>
  <body class="UseyourDrive <?php echo $type; ?>">
    <div class='UseyourDrive list-container loadingshortcode'>
      <img class="preloading" src="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/clouds/cloud_loading_128.gif" data-src="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/clouds/cloud_loading_128.gif" data-src-retina="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/clouds/cloud_loading_256.gif">
      <h2><?php echo __("Loading Shortcode Generator", 'useyourdrive') ?>.</h2>
    </div>
    <form id="UseyourDrive_addshortce_form" action="#" class="UseyourDrive jsdisabled">

      <div class="wrap">

        <?php
        if ($type === 'links' || $type === 'embedded') {

          if ($type === 'embedded') {
            echo "<p>" . __('Please note that the embedded files need to be public (with link)', 'useyourdrive') . "</p>";
          }
          ?>
          <?php
          $atts = array(
              'mode' => 'files',
              'showfiles' => '1',
              'upload' => '0',
              'delete' => '0',
              'rename' => '0',
              'addfolder' => '0',
              'showcolumnnames' => '0',
              'viewrole' => 'all',
              'candownloadzip' => '0',
              'showsharelink' => '0',
              'previewinline' => '0',
              'mcepopup' => $mcepopup,
              '_random' => time()
          );

          echo $this->CreateTemplate($atts);
          ?>
          <?php
        } else {
          ?>

          <div id="tabs">
            <ul>
              <li><a href="#settings_general"><span>General</span></a></li>
              <li id="settings_userfolders_tab"><a href="#settings_userfolders"><span>User Folders</span></a></li>
              <li id="settings_mediafiles_tab" class="hidden"><a href="#settings_mediafiles"><span>Media files</span></a></li>
              <li><a href="#settings_layout"><span>Layout</span></a></li>
              <li><a href="#settings_sorting"><span>Sorting</span></a></li>
              <li id="settings_advanced_tab"><a href="#settings_advanced"><span>Advanced</span></a></li>
              <li><a href="#settings_exclusions"><span>Exclusions</span></a></li>

              <li id="settings_upload_tab"><a href="#settings_upload"><span>Upload Form</span></a></li>

              <li id="settings_notifications_tab"><a href="#settings_notifications"><span>Notifications</span></a></li>

              <li id="settings_manipulation_tab"><a href="#settings_manipulation"><span>File Manipulation</span></a></li>
              <li><a href="#settings_permissions"><span>User Permissions</span></a></li>
            </ul>
            <!-- General Tab -->
            <div id="settings_general">
              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Use plugin as', 'useyourdrive'); ?>
                  <span class="help" title="<p>Select how you want to use Use-your-Drive in your post or page</p>">?</span>
                </h4>
                <div class="section">
                  <div class="radiobuttons-container">
                    <div class="radiobutton">
                      <input type="radio" id="files" name="mode" <?php echo (($mode === 'files') ? 'checked="checked"' : ''); ?> value="files" class="mode"/><label for="files"><?php _e('File browser', 'useyourdrive'); ?></label>
                    </div>
                    <?php if ($type !== 'gravityforms') { ?>
                      <div class="radiobutton">
                        <input type="radio" id="gallery" name="mode" <?php echo (($mode === 'gallery') ? 'checked="checked"' : ''); ?> value="gallery" class="mode"/><label for="gallery"><?php _e('Photo gallery', 'useyourdrive'); ?></label>
                      </div>
                      <div class="radiobutton">
                        <input type="radio" id="audio" name="mode" <?php echo (($mode === 'audio') ? 'checked="checked"' : ''); ?> value="audio" class="mode"/><label for="audio"><?php _e('Audio player', 'useyourdrive'); ?></label>
                      </div>
                      <div class="radiobutton">
                        <input type="radio" id="video" name="mode" <?php echo (($mode === 'video') ? 'checked="checked"' : ''); ?> value="video" class="mode"/><label for="video"><?php _e('Video player', 'useyourdrive'); ?></label>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Select root folder', 'useyourdrive'); ?>
                  <span class="help" title="<p>What should be the start folder of the plugin? The user can not browse below this folder</p>">?</span>
                </h4>
                <div class="section">
                  <div class="root-folder">
                    <?php
                    $atts = array(
                        'mode' => 'files',
                        'filelayout' => 'list',
                        'showfiles' => '1',
                        'filesize' => '0',
                        'filedate' => '0',
                        'upload' => '0',
                        'delete' => '0',
                        'rename' => '0',
                        'addfolder' => '0',
                        'showbreadcrumb' => '0',
                        'showcolumnnames' => '0',
                        'viewrole' => 'administrator|editor|author|contributor',
                        'downloadrole' => 'none',
                        'candownloadzip' => '0',
                        'showsharelink' => '0',
                        'previewinline' => '0',
                        'mcepopup' => $mcepopup,
                        '_random' => time()
                    );

                    if (isset($_REQUEST['dir'])) {
                      $atts['startid'] = $_REQUEST['dir'];
                    }

                    echo $this->CreateTemplate($atts);
                    ?>
                  </div>
                  <div class="no-root-folder hidden">
                    <?php _e("You are using User-Linked user folders. You can't select a root folder.", 'useyourdrive'); ?>
                  </div>
                </div>
              </div>
            </div>
            <!-- End General Tab -->
            <!-- User Folders Tab -->
            <div id="settings_userfolders">
              <div class="option option-help forfilebrowser forgallery">
                <h4><?php _e('User folders', 'useyourdrive'); ?>
                </h4>
                <div class="section">
                  <div class="description">
                    <?php _e('User folders can be useful in some situations, for example', 'useyourdrive'); ?>:
                    <ul>
                      <li><?php _e('Each user should only be able to access their own files in their own folder', 'useyourdrive'); ?></li>
                      <li><?php _e('Users and guests on your site want to upload files to their own folder', 'useyourdrive'); ?></li>
                      <li><?php _e('Your clients should get their own personal folder if they register, already filled with some files from template folder', 'useyourdrive'); ?></li>
                    </ul>
                    <?php _e('You can use the plugin in two ways to create folders that are linked to the users', 'useyourdrive'); ?>. 
                    <?php _e('You can let the plugin automatically create user folders in the, by you selected, root folder', 'useyourdrive'); ?>. 
                    <?php _e('Or you can link each user to their own folder via the plugin settings menu', 'useyourdrive'); ?>. 
                    (<a href="<?php echo admin_url('admin.php?page=UseyourDrive_settings_linkusers'); ?>" target="_blank"><?php _e('here', 'useyourdrive'); ?></a>)
                  </div>
                </div>
              </div>  

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Use user specific folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Let users only browser through the their own folder (automatically or were you have linked them to)', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_linkedfolders" id="UseyourDrive_linkedfolders" <?php echo (isset($_REQUEST['userfolders'])) ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option option-userfolders forfilebrowser forgallery <?php echo (isset($_REQUEST['userfolders'])) ? '' : 'hidden'; ?>">
                <h4><?php _e('Method', 'useyourdrive'); ?>
                  <span class="help" title="<p><strong><?php _e('Select the method that should be used', 'useyourdrive'); ?></strong></br>
                        <?php _e('Use the user-folder link that you have created via the plugin settings menu', 'useyourdrive'); ?>.
                        <?php _e('Or let the plugin automatically create the user folders', 'useyourdrive'); ?>
                        </p>">?</span>
                </h4>
                <?php
                $userfolders = (!isset($_REQUEST['userfolders']) || (isset($_REQUEST['userfolders']) && ($_REQUEST['userfolders'] === 'auto'))) ? 'auto' : 'manual';
                ?>
                <div class="section">
                  <div class="radiobuttons-container">
                    <div class="radiobutton">
                      <input type="radio" id="userfolders_method_manual" name="UseyourDrive_userfolders_method" <?php echo ($userfolders === 'manual') ? 'checked="checked"' : ''; ?> value="manual"/><label for="file_layout_grid"><?php _e('Use my own created User-Folder link', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="userfolders_method_auto" name="UseyourDrive_userfolders_method" <?php echo ($userfolders === 'auto') ? 'checked="checked"' : ''; ?> value="auto"/><label for="file_layout_list"><?php _e('Automatically create the user folders', 'useyourdrive'); ?></label>
                    </div>
                    <div class="option option-userfolders_auto <?php echo ($userfolders === 'auto') ? '' : 'hidden'; ?>">
                      <i><?php echo __('By default guests (not logged in users) will also get their own folder', 'useyourdrive'); ?>. 
                        <?php echo __("Remove 'Guest' from View Roles on the 'User Permissions' tab to prevent guests to use the plugin", 'useyourdrive'); ?>.
                      </i>
                    </div>
                  </div>
                </div>

                <div class="option option-userfolders_auto forgallery <?php echo ($userfolders === 'auto') ? '' : 'hidden'; ?>">
                  <h4><?php _e('Use a template folder', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('If you would like to create the user folders based on another folder. The content of the template folder will be copied to the user folder', 'useyourdrive'); ?></p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <input type="checkbox" name="UseyourDrive_userfolders_template" id="UseyourDrive_userfolders_template" <?php echo (isset($_REQUEST['usertemplatedir'])) ? 'checked="checked"' : ''; ?> data-div-toggle="option-userfolders-template"/>
                    </div>
                  </div>

                  <div class="option option-userfolders-template forfilebrowser forgallery <?php echo (isset($_REQUEST['usertemplatedir'])) ? '' : 'hidden'; ?>">
                    <h4><?php _e('Template folder', 'useyourdrive'); ?>
                      <span class="help" title="<p><?php _e('Select the template folder', 'useyourdrive'); ?>.</p>">?</span>
                    </h4>
                    <div class="section">
                      <div class="template-folder">
                        <?php
                        $atts = array(
                            'mode' => 'files',
                            'filelayout' => 'list',
                            'showfiles' => '1',
                            'filesize' => '0',
                            'filedate' => '0',
                            'upload' => '0',
                            'delete' => '0',
                            'rename' => '0',
                            'addfolder' => '0',
                            'showbreadcrumb' => '0',
                            'showcolumnnames' => '0',
                            'viewrole' => 'administrator|editor|author|contributor',
                            'downloadrole' => 'none',
                            'candownloadzip' => '0',
                            'showsharelink' => '0',
                            'mcepopup' => $mcepopup,
                            '_random' => time() + 10
                        );

                        if (isset($_REQUEST['usertemplatedir'])) {
                          $atts['startid'] = $_REQUEST['usertemplatedir'];
                        }

                        echo $this->CreateTemplate($atts);
                        ?>
                      </div>
                    </div>
                  </div>

                  <h4><?php _e('Who can access all user folders', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can browse through all folders and access all files', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['viewuserfoldersrole'])) ? explode('|', $_REQUEST['viewuserfoldersrole']) : array('administrator');
                      wp_roles_checkbox('UseyourDrive_view_user_folders_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End User Folders Tab -->
            <!-- Media Files Tab -->
            <div id="settings_mediafiles">
              <div class="option option-help foraudio forvideo">
                <h4><?php _e('Media Files', 'useyourdrive'); ?>
                </h4>
                <div class="section">
                  <div class="description">
                    <?php _e('The mediaplayer will decided, based on the provided formats, if the user will have a HTML5 player or a Flash Player', 'useyourdrive'); ?>. <?php _e('You may provide the same file with different extensions to increase cross-browser support', 'useyourdrive'); ?>.<br/> <?php _e('Do always supply a mp3 (audio) or m4v/mp4 (video)file to support all browsers', 'useyourdrive'); ?>.
                  </div>
                </div>
              </div>        

              <div class="option foraudio">
                <h4 class="mediaextensions"><?php _e('Provided formats', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Select which sort of media files you will provide', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <?php
                $mediaextensions = (!isset($_REQUEST['mediaextensions']) || ($mode !== 'audio')) ? array() : explode('|', $_REQUEST['mediaextensions']);
                ?>
                <div class="section">
                  <div class="checkbox">
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('mp3', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='mp3'/>mp3&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('mp4', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='mp4'/>mp4&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('m4a', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='m4a'/>m4a&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('ogg', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='ogg'/>ogg&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('oga', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='oga'/>oga&nbsp&nbsp
                  </div>
                </div>
              </div>

              <div class="option forvideo">
                <h4 class="mediaextensions"><?php _e('Provided formats', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Select which sort of media files you will provide', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <?php
                $mediaextensions = (!isset($_REQUEST['mediaextensions']) || ($mode !== 'video')) ? array() : explode('|', $_REQUEST['mediaextensions']);
                ?>
                <div class="section">
                  <div class="checkbox">
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('mp4', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='mp4'/>mp4&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('m4v', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='m4v'/>m4v&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('ogg', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='ogg'/>ogg&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('ogv', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='ogv'/>ogv&nbsp&nbsp
                    <input class="simple" type="checkbox" name="UseyourDrive_mediaextensions[]" <?php echo (in_array('webmv', $mediaextensions)) ? 'checked="checked"' : ''; ?> value='webmv'/>webmv&nbsp&nbsp
                  </div>
                </div>
              </div>

              <div class="option foraudio forvideo">
                <h4><?php _e('Automatically start playing', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Autoplay - Automatically start playing', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_autoplay" id="UseyourDrive_autoplay" <?php echo (isset($_REQUEST['autoplay']) && $_REQUEST['autoplay'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option foraudio forvideo">
                <h4><?php _e('Allow download', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Show direct download link to media file in the playlist', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_linktomedia" id="UseyourDrive_linktomedia" <?php echo (isset($_REQUEST['linktomedia']) && $_REQUEST['linktomedia'] === '1') ? 'checked="checked"' : ''; ?> />
                  </div>
                </div>
              </div>

              <div class="option foraudio forvideo">
                <h4><?php _e('Allow purchase', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Show link to webshop in the playlist', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_mediapurchase" id="UseyourDrive_mediapurchase" <?php echo (isset($_REQUEST['linktoshop'])) ? 'checked="checked"' : ''; ?> data-div-toggle='webshop-options'/>
                  </div>
                </div>
              </div>

              <div class="option webshop-options <?php echo (isset($_REQUEST['linktoshop'])) ? '' : 'hidden'; ?>">
                <h4><?php _e('Link to webshop', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Insert link to your webshop here', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_linktoshop" id="UseyourDrive_linktoshop" placeholder="https://www.yourwebshop.com/" value="<?php echo (isset($_REQUEST['linktoshop'])) ? $_REQUEST['linktoshop'] : ''; ?>"/>
                </div>
              </div>
            </div>
            <!-- End Media Files Tab -->
            <!-- Layout Tab -->
            <div id="settings_layout">

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Container width', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Set max width for the Use-your-Drive container", "useyourdrive"); ?>. <?php _e("You can use pixels or percentages, eg '360px', '480px', '70%'", "useyourdrive"); ?>. <?php echo __('Leave empty for default value', 'useyourdrive'); ?> (100%).</p>">?</span>
                </h4>
                <div class="section smallinput">
                  <input type="text" name="UseyourDrive_max_width" id="UseyourDrive_max_width" placeholder="100%" value="<?php echo (isset($_REQUEST['maxwidth'])) ? $_REQUEST['maxwidth'] : ''; ?>"/>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Container height', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Set max height for the Use-your-Drive container", "useyourdrive"); ?>. <?php _e("You can use pixels or percentages, eg '360px', '480px', '70%'", "useyourdrive"); ?>. <?php echo __('Leave empty for default value', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section smallinput">
                  <input type="text" name="UseyourDrive_max_height" id="UseyourDrive_max_height" placeholder="" value="<?php echo (isset($_REQUEST['maxheight'])) ? $_REQUEST['maxheight'] : ''; ?>"/>
                </div>
              </div>

              <div class="option foraudio forvideo <?php echo (in_array($mode, array('audio', 'video'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Hide playlist on start', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Would you like to hide the playlist', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_hideplaylist" id="UseyourDrive_hideplaylist" <?php echo (isset($_REQUEST['hideplaylist']) && $_REQUEST['hideplaylist'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>


              <div class="option option-help foraudio  <?php echo ($mode === 'audio') ? '' : 'hidden'; ?>">
                <h4><?php _e('Album or audio file covers', 'useyourdrive'); ?></h4>
                <div class="section">
                  <div class="description">
                    <?php _e('You can show covers of your audio files in the Audio Player', 'useyourdrive'); ?>. <?php _e('Add a *.png or *.jpg file with the same name as your audio file in the same folder as your audio files. You can also add a cover with the name of the folder to show the cover for all audio files in the album', 'useyourdrive'); ?>.<br/> <?php _e('If no cover is available, a placeholder will be used', 'useyourdrive'); ?>.
                  </div>
                </div>
              </div>  

              <div class="option foraudio <?php echo ($mode === 'audio') ? '' : 'hidden'; ?>">
                <h4><?php _e('Display covers', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you have covers (*.png, *.jpg) available and should they be displayed?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_covers" id="UseyourDrive_covers" <?php echo (isset($_REQUEST['covers']) && $_REQUEST['covers'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser <?php echo (in_array($mode, array('files'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display files and folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('How do you want to display the files and folders? With or without thumbnails', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <?php
                $filelayout = (!isset($_REQUEST['filelayout'])) ? 'grid' : $_REQUEST['filelayout'];
                ?>
                <div class="section">
                  <div class="radiobuttons-container filelayout">
                    <div class="radiobutton">
                      <input type="radio" id="file_layout_grid" name="UseyourDrive_file_layout" <?php echo ($filelayout === 'grid') ? 'checked="checked"' : ''; ?> value="grid" class="mode"/><label for="file_layout_grid"><?php _e('with thumbnails in a grid', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="file_layout_list" name="UseyourDrive_file_layout" <?php echo ($filelayout === 'list') ? 'checked="checked"' : ''; ?> value="list" class="mode"/><label for="file_layout_list"><?php _e('In a file list', 'useyourdrive'); ?></label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery <?php echo (in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display breadcrumb', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display a breadcrumb in the file browser?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_breadcrumb" id="UseyourDrive_breadcrumb" <?php echo (isset($_REQUEST['showbreadcrumb']) && $_REQUEST['showbreadcrumb'] === '0') ? '' : 'checked="checked"'; ?> data-div-toggle="breadcrumb-options"/>
                  </div>
                </div>


                <div class="option breadcrumb-options <?php echo (isset($_REQUEST['showbreadcrumb']) && $_REQUEST['showbreadcrumb'] === '0') ? 'hidden' : ''; ?>">
                  <h4><?php _e('Root breadcrumb title', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('What should be the breadcrumb title of the root folder? Leave empty for default value.', 'useyourdrive'); ?></p>">?</span>
                  </h4>
                  <div class="section largeinput">
                    <input type="text" name="UseyourDrive_roottext" id="UseyourDrive_roottext" placeholder="Start" value="<?php echo (isset($_REQUEST['roottext'])) ? $_REQUEST['roottext'] : ''; ?>"/>
                  </div>
                </div>
              </div>

              <div class="option columnnames-options forfilebrowser <?php echo (($filelayout === 'grid') || !in_array($mode, array('files'))) ? 'hidden' : ''; ?>">
                <h4><?php _e('Display columnnames', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display the columnnames of the date and filesize?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showcolumnnames" id="UseyourDrive_showcolumnnames" <?php echo (isset($_REQUEST['showcolumnnames']) && $_REQUEST['showcolumnnames'] === '0') ? '' : 'checked="checked"'; ?> />
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery <?php echo (in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display refresh button', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display a refresh button so users can update the file list and refresh the cache?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showrefreshbutton" id="UseyourDrive_showrefreshbutton" <?php echo (isset($_REQUEST['showrefreshbutton']) && $_REQUEST['showrefreshbutton'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser <?php echo (in_array($mode, array('files'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display files in folder', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display files in the folder so the user can preview and download them?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showfiles" id="UseyourDrive_showfiles" <?php echo (isset($_REQUEST['showfiles']) && $_REQUEST['showfiles'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser <?php echo (in_array($mode, array('files'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display child folders in folder', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display the child folders in the selected root folder?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showfolders" id="UseyourDrive_showfolders" <?php echo (isset($_REQUEST['showfolders']) && $_REQUEST['showfolders'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>

              <div class="option option-filesize forfilebrowser <?php echo (!in_array($mode, array('files')) || ($filelayout === 'grid')) ? 'hidden' : ''; ?>">
                <h4><?php _e('Display file size', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display a column with the file size?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_filesize" id="UseyourDrive_filesize" <?php echo (isset($_REQUEST['filesize']) && $_REQUEST['filesize'] === '0') ? '' : 'checked="checked"'; ?> />
                  </div>
                </div>
              </div>

              <div class="option option-filedate forfilebrowser <?php echo (!in_array($mode, array('files')) || ($filelayout === 'grid') ) ? 'hidden' : ''; ?>">
                <h4><?php _e('Display date last modified', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display a column with the last modified date?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_filedate" id="UseyourDrive_filedate" <?php echo (isset($_REQUEST['filedate']) && $_REQUEST['filedate'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser <?php echo (in_array($mode, array('files'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Display file extension', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Do you want to display the file extensions (.pdf, .txt)?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showext" id="UseyourDrive_showext" <?php echo (isset($_REQUEST['showext']) && $_REQUEST['showext'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>


              <div class="option forgallery <?php echo (in_array($mode, array('gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Slideshow in Lightbox', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Do you want to enable the slideshow in the lightbox", 'useyourdrive'); ?>. <?php _e("Set to 0 to load all images at once", 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_slideshow" id="UseyourDrive_slideshow" <?php echo (isset($_REQUEST['slideshow']) && $_REQUEST['slideshow'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="slideshow-options"/>
                  </div>
                </div>
              </div>

              <div class="option slideshow-options forgallery <?php echo (in_array($mode, array('gallery'))) ? '' : 'hidden'; ?> ">
                <h4><?php _e('Delay between cycles (ms)', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Delay between cycles in milliseconds, the default is 5000", 'useyourdrive'); ?></p>.">?</span>
                </h4>
                <div class="section smallinput">
                  <input type="text" name="UseyourDrive_pausetime" id="UseyourDrive_pausetime" placeholder="5000" value="<?php echo (isset($_REQUEST['UseyourDrive_pausetime'])) ? $_REQUEST['UseyourDrive_pausetime'] : ''; ?>"/>
                </div>
              </div>

              <div class="option forgallery <?php echo (in_array($mode, array('gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Number of images', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Number of images to be loaded each time", 'useyourdrive'); ?>. <?php _e("Set to 0 to load all images at once", 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section smallinput">
                  <input type="text" name="UseyourDrive_maximage" id="UseyourDrive_maximage" placeholder="25" value="<?php echo (isset($_REQUEST['maximages'])) ? $_REQUEST['maximages'] : ''; ?>"/>
                </div>
              </div>

              <div class="option forgallery <?php echo (in_array($mode, array('gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Gallery row height', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("The ideal height you want your grid rows to be", 'useyourdrive'); ?>. <?php _e("It won't set it exactly to this as plugin adjusts the row height to get the correct width", 'useyourdrive'); ?>. <?php echo __('Leave empty for default value', 'useyourdrive'); ?> (150).</p>">?</span>
                </h4>
                <div class="section smallinput">
                  <input type="text" name="UseyourDrive_targetHeight" id="UseyourDrive_targetHeight" placeholder="150" value="<?php echo (isset($_REQUEST['targetheight'])) ? $_REQUEST['targetheight'] : ''; ?>"/>
                </div>
              </div>

            </div>
            <!-- End Layout Tab -->

            <!-- Sorting Tab -->
            <div id="settings_sorting">
              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Sort by', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Sort files and folders by their properties', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <?php
                $sortfield = (!isset($_REQUEST['sortfield'])) ? 'name' : $_REQUEST['sortfield'];
                ?>
                <div class="section">
                  <div class="radiobuttons-container sort_fields">
                    <div class="radiobutton">
                      <input type="radio" id="name" name="sort_field" <?php echo ($sortfield === 'name') ? 'checked="checked"' : ''; ?> value="name" class="mode"/><label for="name"><?php _e('Name', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="size" name="sort_field" <?php echo ($sortfield === 'size') ? 'checked="checked"' : ''; ?> value="size" class="mode"/><label for="size"><?php _e('Size', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="modified" name="sort_field" <?php echo ($sortfield === 'modified') ? 'checked="checked"' : ''; ?> value="modified" class="mode"/><label for="modified"><?php _e('Date modified', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="shuffle" name="sort_field" <?php echo ($sortfield === 'shuffle') ? 'checked="checked"' : ''; ?> value="shuffle" class="mode"/><label for="shuffle"><?php _e('Shuffle/Random', 'useyourdrive'); ?></label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="option option-sort-field forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Sort order', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Sort order: ascending or descending', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <?php
                $sortorder = (isset($_REQUEST['sortorder']) && $_REQUEST['sortorder'] === 'desc') ? 'desc' : 'asc';
                ?>
                <div class="section">
                  <div class="radiobuttons-container sort_fields">
                    <div class="radiobutton">
                      <input type="radio" id="asc" name="sort_order" <?php echo ($sortorder === 'asc') ? 'checked="checked"' : ''; ?> value="asc" class="mode"/><label for="files"><?php _e('Ascending', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="desc" name="sort_order" <?php echo ($sortorder === 'desc') ? 'checked="checked"' : ''; ?> value="desc" class="mode"/><label for="gallery"><?php _e('Descending', 'useyourdrive'); ?></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Sorting Tab -->
            <!-- Advanced Tab -->
            <div id="settings_advanced">
              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Enable search', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should users be able to use the search function', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_search" id="UseyourDrive_search" <?php echo (isset($_REQUEST['search']) && $_REQUEST['search'] === '0') ? '' : 'checked="checked"'; ?> data-div-toggle="search-options"/>
                  </div>
                </div>
              </div>

              <div class="option search-options forfilebrowser forgallery <?php echo (isset($_REQUEST['search']) && $_REQUEST['search'] === '0') ? 'hidden' : ''; ?>">
                <h4><?php _e('Search for', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should users be able to search in the content?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <?php
                $searchcontents = (isset($_REQUEST['searchcontents']) && $_REQUEST['searchcontents'] === '1') ? '1' : '0';
                ?>
                <div class="section">
                  <div class="radiobuttons-container">
                    <div class="radiobutton">
                      <input type="radio" id="filename" name="UseyourDrive_search_field" <?php echo ($searchcontents === '0') ? 'checked="checked"' : ''; ?> value="0" class="mode"/><label for="filename"><?php _e('Search only on filename', 'useyourdrive'); ?></label>
                    </div>
                    <div class="radiobutton">
                      <input type="radio" id="filecontents" name="UseyourDrive_search_field" <?php echo ($searchcontents === '1') ? 'checked="checked"' : ''; ?> value="1" class="mode"/><label for="filecontents"><?php _e('Search on filename and contents', 'useyourdrive'); ?></label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="option search-options forfilebrowser forgallery <?php echo (isset($_REQUEST['search']) && $_REQUEST['search'] === '0') ? 'hidden' : ''; ?>"">
                <h4><?php _e('Search from selected root', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Search only in the current folder or search from the selected root folder  ', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_searchfrom" id="UseyourDrive_searchfrom" <?php echo (isset($_REQUEST['searchfrom']) && $_REQUEST['searchfrom'] === 'selectedroot') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Enable link sharing', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should users be able to generate permanent direct links to the files?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_showsharelink" id="UseyourDrive_showsharelink" <?php echo (isset($_REQUEST['showsharelink']) && $_REQUEST['showsharelink'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e("Open preview inline", 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Do you want to open the preview in an inline popup or should it open in a new window", 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_previewinline" id="UseyourDrive_previewinline" <?php echo (isset($_REQUEST['previewinline']) && $_REQUEST['previewinline'] === '0') ? '' : 'checked="checked"'; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e("Force a 'Save as'", 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e("Force a 'Save as' Dialog on downloading file", 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_forcedownload" id="UseyourDrive_forcedownload" <?php echo (isset($_REQUEST['forcedownload']) && $_REQUEST['forcedownload'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Enable ZIP-download', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should users be able to use download multiple files as zip?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_candownloadzip" id="UseyourDrive_candownloadzip" <?php echo (isset($_REQUEST['candownloadzip']) && $_REQUEST['candownloadzip'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

            </div>
            <!-- End Advanced Tab -->
            <!-- Exclusions Tab -->
            <div id="settings_exclusions">
              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Limit by extension', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php echo __('Add extensions separated with | e.g. (jpg|png|gif)', 'useyourdrive') . '. ' . __('Leave empty to show all files', 'useyourdrive', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_ext" id="UseyourDrive_ext" value="<?php echo (isset($_REQUEST['ext'])) ? $_REQUEST['ext'] : ''; ?>"/>
                </div>
              </div> 

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Limit by name', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php echo __('Add files or folders separated with | e.g. (file1.jpg|long folder name)', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_include" id="UseyourDrive_include" value="<?php echo (isset($_REQUEST['include'])) ? $_REQUEST['include'] : ''; ?>"/>
                </div>
              </div> 

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Exclude by name', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php echo __('Add files or folders separated with | e.g. (file1.jpg|long folder name)', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_exclude" id="UseyourDrive_exclude" value="<?php echo (isset($_REQUEST['exclude'])) ? $_REQUEST['exclude'] : ''; ?>"/>
                </div>
              </div> 

            </div>
            <!-- End Exclusions Tab -->

            <!-- Upload Tab -->
            <div id="settings_upload">
              <div class="option forfilebrowser forgallery <?php echo (in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Include upload form', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should users be able to upload files? You can manage the permissions under \'User Permissions\'', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_upload" id="UseyourDrive_upload" data-div-toggle="upload-options" <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option upload-options forfilebrowser forgallery <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Simple upload', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Start uploading automatically (no start/cancel buttons)', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_upload_simple" id="UseyourDrive_upload_simple" <?php echo (isset($_REQUEST['simpleupload']) && $_REQUEST['simpleupload'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option upload-options forfilebrowser forgallery <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Limit upload by extension', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php echo __('Add extensions separated with | e.g. (jpg|png|gif)', 'useyourdrive') . ' ' . __('Leave empty for no restricion', 'useyourdrive', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_upload_ext" id="UseyourDrive_upload_ext" value="<?php echo (isset($_REQUEST['uploadext'])) ? $_REQUEST['uploadext'] : ''; ?>"/>
                </div>
              </div>

              <div class="option upload-options forfilebrowser forgallery <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <?php $max_size_bytes = min(UseyourDrive_return_bytes(ini_get('post_max_size')), UseyourDrive_return_bytes(ini_get('upload_max_filesize'))); ?>
                <h4><?php _e('Max. upload size', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Max filesize for uploading in bytes', 'useyourdrive'); ?>. <?php echo __('Leave empty for server maximum ', 'useyourdrive'); ?> (<?php echo $max_size_bytes; ?> bytes). <a href='http://www.google.nl/#q=1mb+in+bytes' target='_blank'><?php echo __('How to calculate?', 'useyourdrive'); ?></a></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_maxfilesize" id="UseyourDrive_maxfilesize" value="<?php echo (isset($_REQUEST['maxfilesize'])) ? $_REQUEST['maxfilesize'] : ''; ?>"/>
                </div>

              </div>
              <div class="option upload-options forfilebrowser forgallery <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <h4><?php _e('Convert to Google Docs', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Try to convert files to Google Documents', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_upload_convert" id="UseyourDrive_upload_convert" <?php echo (isset($_REQUEST['convert']) && $_REQUEST['convert'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

            </div>
            <!-- End Upload Tab -->

            <!-- Notifications Tab -->
            <div id="settings_notifications">
              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Download notification', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Would you like to receive a notification email when someone downloads a file?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_notificationdownload" id="UseyourDrive_notificationdownload" <?php echo (isset($_REQUEST['notificationdownload']) && $_REQUEST['notificationdownload'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Upload notification', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Would you like to receive a notification email when someone uploads a file?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_notificationupload" id="UseyourDrive_notificationupload" <?php echo (isset($_REQUEST['notificationupload']) && $_REQUEST['notificationupload'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Delete notification', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Would you like to receive a notification email when someone deletes a file?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_notificationdeletion" id="UseyourDrive_notificationdeletion" <?php echo (isset($_REQUEST['notificationdeletion']) && $_REQUEST['notificationdeletion'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery">
                <h4><?php _e('Send notification to', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('On which email address would you like to receive the notification? You can use %admin_email% and %user_email%.', 'useyourdrive'); ?>. <?php echo __('Default value is:', 'useyourdrive') . ' ' . get_site_option('admin_email'); ?></p>">?</span>
                </h4>
                <div class="section largeinput">
                  <input type="text" name="UseyourDrive_notification_email" id="UseyourDrive_notification_email" placeholder="<?php echo get_site_option('admin_email'); ?>" value="<?php echo (isset($_REQUEST['notificationemail'])) ? $_REQUEST['notificationemail'] : ''; ?>" />
                </div>
              </div>

            </div>
            <!-- End Notifications Tab -->

            <!-- Manipulation Tab -->
            <div id="settings_manipulation">
              <div class="option option-help forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('File Manipulation', 'useyourdrive'); ?>
                </h4>
                <div class="section">
                  <div class="description">
                    <?php _e('Use-your-Drive uses Wordpress Roles to determine how an user can use the plugin', 'useyourdrive'); ?>. 
                    <?php _e("You set these under 'User Permissions'", 'useyourdrive'); ?>.
                  </div>
                </div>
              </div>    

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Edit descriptions', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should it be possible to edit descriptions?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_editdescription" id="UseyourDrive_editdescription" <?php echo (isset($_REQUEST['editdescription']) && $_REQUEST['editdescription'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="editdescription-options"/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Rename files and folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should it be possible to rename files and folders?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_rename" id="UseyourDrive_rename" <?php echo (isset($_REQUEST['rename']) && $_REQUEST['rename'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="rename-options"/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Move files and folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should it be possible to move files and folders?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_move" id="UseyourDrive_move" <?php echo (isset($_REQUEST['move']) && $_REQUEST['move'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="move-options"/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Delete files and folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should it be possible to delete files and folders?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_delete" id="UseyourDrive_delete" <?php echo (isset($_REQUEST['delete']) && $_REQUEST['delete'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="delete-options"/>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery foraudio forvideo delete-options <?php echo (isset($_REQUEST['delete']) && $_REQUEST['delete'] === '1') ? '' : 'hidden'; ?>">
                <h4><?php _e('Delete to trash', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should the files be deleted permanently or moved to the trash bin?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_deletetotrash" id="UseyourDrive_deletetotrash" <?php echo (isset($_REQUEST['deletetotrash']) && $_REQUEST['deletetotrash'] === '1') ? 'checked="checked"' : ''; ?>/>
                  </div>
                </div>
              </div>


              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Create new folders', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Should it be possible to create new folders?', 'useyourdrive'); ?></p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <input type="checkbox" name="UseyourDrive_addfolder" id="UseyourDrive_addfolder" <?php echo (isset($_REQUEST['addfolder']) && $_REQUEST['addfolder'] === '1') ? 'checked="checked"' : ''; ?> data-div-toggle="addfolder-options"/>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Manipulation Tab -->
            <!-- Permissions Tab -->
            <div id="settings_permissions">
              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Who can view', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Select which WordPress Roles can view and use the plugin', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <?php
                    $selected = (isset($_REQUEST['viewrole'])) ? explode('|', $_REQUEST['viewrole']) : array('administrator', 'author', 'contributor', 'editor', 'subscriber', 'pending', 'guest');
                    wp_roles_checkbox('UseyourDrive_view_role', $selected);
                    ?>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery foraudio forvideo">
                <h4><?php _e('Who can download', 'useyourdrive'); ?>
                  <span class="help" title="<p><?php _e('Select which WordPress Roles can download files', 'useyourdrive'); ?>.</p>">?</span>
                </h4>
                <div class="section">
                  <div class="checkbox">
                    <?php
                    $selected = (isset($_REQUEST['downloadrole'])) ? explode('|', $_REQUEST['downloadrole']) : array('administrator', 'author', 'contributor', 'editor', 'subscriber', 'pending', 'guest');
                    wp_roles_checkbox('UseyourDrive_download_role', $selected);
                    ?>
                  </div>
                </div>
              </div>

              <div class="option forfilebrowser forgallery <?php echo (in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                <div class="option upload-options <?php echo (isset($_REQUEST['upload']) && $_REQUEST['upload'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can upload', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can upload files', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['uploadrole'])) ? explode('|', $_REQUEST['uploadrole']) : array('administrator', 'author', 'contributor', 'editor', 'subscriber');
                      wp_roles_checkbox('UseyourDrive_upload_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>

                <div class="option editdescription-options  <?php echo (isset($_REQUEST['editdescription']) && $_REQUEST['editdescription'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can edit descriptions', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can edit descriptions', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['editdescriptionrole'])) ? explode('|', $_REQUEST['editdescriptionrole']) : array('administrator', 'editor');
                      wp_roles_checkbox('UseyourDrive_editdescription_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>

                <div class="option rename-options  <?php echo (isset($_REQUEST['rename']) && $_REQUEST['rename'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can rename', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can rename files and folders', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['renamerole'])) ? explode('|', $_REQUEST['renamerole']) : array('administrator', 'author', 'contributor', 'editor');
                      wp_roles_checkbox('UseyourDrive_rename_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>

                <div class="option move-options  <?php echo (isset($_REQUEST['move']) && $_REQUEST['move'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can move files and folders', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can move files and folders', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['moverole'])) ? explode('|', $_REQUEST['moverole']) : array('administrator', 'editor');
                      wp_roles_checkbox('UseyourDrive_move_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>

                <div class="option delete-options  <?php echo (isset($_REQUEST['delete']) && $_REQUEST['delete'] === '1' && in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can delete', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can delete files and folders', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['deleterole'])) ? explode('|', $_REQUEST['deleterole']) : array('administrator', 'author', 'contributor', 'editor');
                      wp_roles_checkbox('UseyourDrive_delete_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>

                <div class="option addfolder-options  <?php echo (in_array($mode, array('files', 'gallery'))) ? '' : 'hidden'; ?>">
                  <h4><?php _e('Who can create new folders', 'useyourdrive'); ?>
                    <span class="help" title="<p><?php _e('Select which WordPress Roles can create new folders', 'useyourdrive'); ?>.</p>">?</span>
                  </h4>
                  <div class="section">
                    <div class="checkbox">
                      <?php
                      $selected = (isset($_REQUEST['addfolderrole'])) ? explode('|', $_REQUEST['addfolderrole']) : array('administrator', 'author', 'contributor', 'editor');
                      wp_roles_checkbox('UseyourDrive_addfolder_role', $selected);
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Permissions Tab -->

          </div>
          <?php
        }
        ?>

        <div class="footer">
          <div style="float: right; margin-left:10px">
            <?php if ($type === 'default') { ?>
              <input type="submit" id="insert"  class="insert_shortcode button-primary" name="insert" value="<?php _e("Insert", 'useyourdrive'); ?>" />
            <?php } elseif ($type === 'links') { ?>
              <input type="submit" id="insert" class="insert_links button-primary" name="insert" value="<?php _e("Insert links", 'useyourdrive'); ?>" />
            <?php } elseif ($type === 'embedded') { ?>
              <input type="submit" id="insert" class="insert_embedded button-primary" name="insert" value="<?php _e("Embed", 'useyourdrive'); ?>" />
            <?php } elseif ($type === 'gravityforms') { ?>
              <input type="submit" id="insert" class="insert_shortcode_gf button-primary" name="insert" value="<?php _e("Insert", 'useyourdrive'); ?>" />
            <?php } ?>
          </div>
          <div style="float: right">
            <?php if ($type === 'gravityforms') { ?>
              <input type="button" id="cancel" class="button-secondary" name="cancel" value="<?php _e("Cancel", 'useyourdrive'); ?>" onclick="parent.tb_remove();" />
            <?php } else { ?>
              <input type="button" id="cancel" class="button-secondary" name="cancel" value="<?php _e("Cancel", 'useyourdrive'); ?>" onclick="tinyMCEPopup.close();" />
            <?php } ?>
          </div>
        </div>
      </div>
    </form>

    <?php if ($type === 'gravityforms') { ?>
      <script>
        jQuery(document).ready(function ($) {
          $("#tabs").disableTab(2, true);
          $("#tabs").disableTab(4, true);
          $("#tabs").disableTab(5, true);
          $("#tabs").disableTab(6, true);
          $("#tabs").disableTab(8, true);
        });
      </script>
    <?php } ?>
  </body>
</html>