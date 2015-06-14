<div class="UseyourDrive settingspage">
  <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
    <?php settings_fields('use_your_drive_settings'); ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="use_your_drive_settings[googledrive_app_refresh_token]" id="googledrive_app_refresh_token" value="<?php echo @esc_attr($this->settings['googledrive_app_refresh_token']); ?>" >
    <input type="hidden" name="use_your_drive_settings[googledrive_app_current_token]" id="googledrive_app_current_token" value="<?php echo @esc_attr($this->settings['googledrive_app_current_token']); ?>" >
    <div class="wrap">
      <h1><?php _e('Use-your-Drive', 'useyourdrive'); ?></h1>
      <div id="tabs"  style="display:none;">
        <ul>
          <li><a href="#settings_general"><span>Authorization</span></a></li>
          <li><a href="#settings_layout"><span>Layout</span></a></li>
          <li><a href="#settings_userfolders"><span>User Folders</span></a></li>
          <li><a href="#settings_advanced"><span>Advanced</span></a></li>
          <li><a href="#settings_notifications"><span>Notifications</span></a></li>
          <li><a href="#settings_stats"><span>Statistics</span></a></li>
          <?php if (!is_multisite() || !is_plugin_active_for_network(dirname(plugin_basename(__DIR__)) . '/use-your-drive.php')) { ?>
            <li><a href="#settings_update"><span>Auto-updater</span></a></li>
          <?php } ?>
          <li><a href="#settings_system"><span>System information</span></a></li>
          <li><a href="#settings_help"><span><i>Need help?</i></span></a></li>
        </ul>
        <!-- General Tab -->
        <div id="settings_general">
          <div class="option option-help">
            <div class="section">
              <div class="description">
                <?php
                $this->CheckDriveApp();
                ?>
              </div>
            </div>
          </div>    

          <div class="option option-help">
            <h4><?php _e('Own Google Drive App', 'useyourdrive'); ?>
            </h4>
            <div class="section">
              <div class="description">
                If you created your own Google Drive App, please enter your settings below. 
                <a href="https://florisdeleeuwnl.zendesk.com/hc/en-us/articles/201804806--How-do-I-create-my-own-Google-Drive-App-" target="_blank">How do I create a Google DriveApp?</a>.
              </div>
            </div>
          </div>

          <div class="option">
            <h4><?php _e('Google Client ID', 'useyourdrive'); ?>
              <span class="help" title="<p>If you want to use your own App, insert your Google Client ID here. You can find this key in the Cloud Console.</p>">?</span>
            </h4>
            <div class="section largeinput">
              <input type="text" name="use_your_drive_settings[googledrive_app_client_id]" id="googledrive_app_client_id" value="<?php echo esc_attr($this->settings['googledrive_app_client_id']); ?>" >
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Google Client secret', 'useyourdrive'); ?>
              <span class="help" title="<p>If you want to use your own App, insert your Google Client secret here. You can find this secret in the Cloud Console.</p>">?</span>
            </h4>
            <div class="section largeinput">
              <input type="text" name="use_your_drive_settings[googledrive_app_client_secret]" id="googledrive_app_client_secret" value="<?php echo esc_attr($this->settings['googledrive_app_client_secret']); ?>">
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Send "Lost Authorization" notification to', 'useyourdrive'); ?>
              <span class="help" title="<p>If the plugin somehow loses its authorization, a notification will be send to the following email address.</p>">?</span>
            </h4>
            <div class="section largeinput">
              <input type="text" name="use_your_drive_settings[lostauthorization_notification]" id="lostauthorization_notification" value="<?php echo esc_attr($this->settings['lostauthorization_notification']); ?>">
            </div>
          </div>
        </div>
        <!-- End General Tab -->

        <!-- Layout Tab -->
        <div id="settings_layout">
          <div class="option" style='overflow: visible;'>
            <h4><?php _e('Lightbox skin', 'useyourdrive'); ?>
              <span class="help" title="<p>Select which skin you want to use for the lightbox</p>">?</span>
            </h4>
            <div class="section">
              <select name="lightbox_skin_selectbox" id="lightbox_skin_selectbox" class="ddslickbox">
                <?php
                foreach (new DirectoryIterator(USEYOURDRIVE_ROOTDIR . '/includes/iLightBox/') as $fileInfo) {
                  if ($fileInfo->isDir() && !$fileInfo->isDot() && (strpos($fileInfo->getFilename(), 'skin') !== false)) {
                    if (file_exists(USEYOURDRIVE_ROOTDIR . '/includes/iLightBox/' . $fileInfo->getFilename() . '/skin.css')) {
                      $selected = '';
                      $skinname = str_replace('-skin', '', $fileInfo->getFilename());

                      if ($skinname === $this->settings['lightbox_skin']) {
                        $selected = 'selected="selected"';
                      }

                      $icon = file_exists(USEYOURDRIVE_ROOTDIR . '/includes/iLightBox/' . $fileInfo->getFilename() . '/thumb.jpg') ? USEYOURDRIVE_ROOTPATH . '/includes/iLightBox/' . $fileInfo->getFilename() . '/thumb.jpg' : '';
                      echo '<option value="' . $skinname . '" data-imagesrc="' . $icon . '" data-description="" ' . $selected . '>' . $fileInfo->getFilename() . "</option>\n";
                    }
                  }
                }
                ?>
              </select>
              <input type="hidden" name="use_your_drive_settings[lightbox_skin]" id="lightbox_skin" value="<?php echo esc_attr($this->settings['lightbox_skin']); ?>">
            </div>
          </div>

          <div class="option">
            <h4><?php _e('Scroll horizontal or vertical in Lightbox', 'useyourdrive'); ?>
              <span class="help" title="<p>Sets path for switching windows. Possible values are 'vertical' and 'horizontal' and the default is 'vertical'</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[lightbox_path]" id="lightbox_path">
                <option value="horizontal" <?php echo ($this->settings['lightbox_path'] === "horizontal" ? "selected='selected'" : ''); ?>>Horizontal</option>
                <option value="vertical" <?php echo ($this->settings['lightbox_path'] === "vertical" ? "selected='selected'" : ''); ?>>Vertical</option>
              </select>
            </div>
          </div>

          <div class="option">
            <h4><?php _e('Source of images in Lightbox', 'useyourdrive'); ?>
              <span class="help" title="<p>Select the source of the images. Large Google thumbnails load fast, orignal files will take some time to load.</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[loadimages]" id="loadimages">
                <option value="googlethumbnail" <?php echo ($this->settings['loadimages'] === "googlethumbnail" ? "selected='selected'" : ''); ?>>Fast - Large preview thumbnails</option>
                <option value="original" <?php echo ($this->settings['loadimages'] === "original" ? "selected='selected'" : ''); ?>>Slow - Show orginal files</option>
              </select>
            </div>
          </div> 

          <div class="option" style='overflow: visible;'>
            <h4><?php _e('Media player skin', 'useyourdrive'); ?>
              <span class="help" title="<p>Select which skin you want to use for the audio or media player</p>">?</span>
            </h4>
            <div class="section">
              <select name="mediaplayer_skin_selectbox" id="mediaplayer_skin_selectbox" class="ddslickbox">
                <?php
                foreach (new DirectoryIterator(USEYOURDRIVE_ROOTDIR . '/skins/') as $fileInfo) {
                  if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                    if (file_exists(USEYOURDRIVE_ROOTDIR . '/skins/' . $fileInfo->getFilename() . '/UseyourDrive_Media.js')) {
                      $selected = '';
                      if ($fileInfo->getFilename() === $this->settings['mediaplayer_skin']) {
                        $selected = 'selected="selected"';
                      }

                      $icon = file_exists(USEYOURDRIVE_ROOTDIR . '/skins/' . $fileInfo->getFilename() . '/thumb.jpg') ? USEYOURDRIVE_ROOTPATH . '/skins/' . $fileInfo->getFilename() . '/thumb.jpg' : '';
                      echo '<option value="' . $fileInfo->getFilename() . '" data-imagesrc="' . $icon . '" data-description="" ' . $selected . '>' . $fileInfo->getFilename() . "</option>\n";
                    }
                  }
                }
                ?>
              </select>
              <input type="hidden" name="use_your_drive_settings[mediaplayer_skin]" id="mediaplayer_skin" value="<?php echo esc_attr($this->settings['mediaplayer_skin']); ?>">
            </div>
          </div>

          <div class="option option-help">
            <h4><?php _e('Custom CSS', 'useyourdrive'); ?>
            </h4>
            <div class="section">
              <div class="description">
                If you want to modify the looks of the plugin slightly, you can insert here your custom CSS. Don't edit the CSS files itself, because those modifications will be lost during an update.
              </div>
            </div>
          </div>

          <div class="option">
            <h4><?php _e('CSS', 'useyourdrive'); ?>
              <span class="help" title="<p>You can insert here your custom CSS</p>">?</span>
            </h4>
            <div class="section largeinput">
              <textarea name="use_your_drive_settings[custom_css]" id="custom_css" cols="" rows="10"><?php echo esc_attr($this->settings['custom_css']); ?></textarea>
            </div>
          </div>

        </div>
        <!-- End Layout Tab -->

        <!-- UserFolders Tab -->
        <div id="settings_userfolders">
          <div class="option">
            <h4><?php _e('User folder name', 'useyourdrive'); ?>
              <span class="help" title="<p>Template name for automatically created user folders. You can use %user_login%, %user_email%, %display_name%, %ID%.</p>">?</span>
            </h4>
            <div class="section largeinput">
              <input type="text" name="use_your_drive_settings[userfolder_name]" id="userfolder_name" value="<?php echo esc_attr($this->settings['userfolder_name']); ?>">
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Create user folders on registration', 'useyourdrive'); ?>
              <span class="help" title="<p>Create the a new user folder automatically after a new user has been created</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[userfolder_oncreation]" id="userfolder_oncreation">
                <option value="Yes" <?php echo ($this->settings['userfolder_oncreation'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['userfolder_oncreation'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Create all user folders on first visit', 'useyourdrive'); ?>
              <span class="help" title="<p>Create all user folders on first visit. This takes around 1 sec per user, so it isn't recommended if you have tons of users.</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[userfolder_onfirstvisit]" id="userfolder_onfirstvisit">
                <option value="Yes" <?php echo ($this->settings['userfolder_onfirstvisit'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['userfolder_onfirstvisit'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Update user folders after profile update', 'useyourdrive'); ?>
              <span class="help" title="<p>Update the folder name of the user after they update their profile.</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[userfolder_update]" id="userfolder_update">
                <option value="Yes" <?php echo ($this->settings['userfolder_update'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['userfolder_update'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Remove user folders after deletion', 'useyourdrive'); ?>
              <span class="help" title="<p>Try to remove user folders after they are deleted.</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[userfolder_remove]" id="userfolder_remove">
                <option value="Yes" <?php echo ($this->settings['userfolder_remove'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['userfolder_remove'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div>
        </div>
        <!-- End UserFolders Tab -->

        <!--  Advanced Tab -->
        <div id="settings_advanced">
          <div class="option option-help">
            <h4><?php _e('Sharing Permissions', 'useyourdrive'); ?>
            </h4>
            <div class="section">
              <div class="description">
                To preview or download files from your Google Drive without loggin in, the files needs to have the sharing permission of at least <i>'Anyone with link can view'</i>. By default the plugin can change these settings for you if necessary. If you want your to manage the sharing permissions by yourself or if you want users to login to Google, disabled the 'Manage Permissions' function.
                <br/><br/>
                If you have Google Apps and you want to set the sharing permissions to your domain only, please insert your domain.
              </div>
            </div>
          </div>

          <div class="option">
            <h4><?php _e('Manage Permissions', 'useyourdrive'); ?>
              <span class="help" title="<p>If you want your to manage the sharing permissions by yourself or if you want users to login to Google, disabled the 'Manage Permissions' function.</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[manage_permissions]" id="manage_permissions">
                <option value="Yes" <?php echo ($this->settings['manage_permissions'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['manage_permissions'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div> 

          <div class="option">
            <h4><?php _e('Your Google Apps Domain', 'useyourdrive'); ?>
              <span class="help" title="<p>If you have Google Apps and you want to set the sharing permissions to your domain only, please insert your domain here.</p>">?</span>
            </h4>
            <div class="section largeinput">
              <input type="text" name="use_your_drive_settings[permission_domain]" id="permission_domain" value="<?php echo esc_attr($this->settings['permission_domain']); ?>" placeholder="mydomain.com">
            </div>
          </div> 
        </div>
        <!-- End Advanced Tab -->

        <!-- Notifications Tab -->
        <div id="settings_notifications">
          <div class="option">
            <h4><?php _e('Template download', 'useyourdrive'); ?>
              <span class="help" title="<p>Template for email notification</p>">?</span>
            </h4>
            <div class="section largeinput">
              <textarea name="use_your_drive_settings[download_template]" id="download_template" cols="" rows="6"><?php echo esc_attr($this->settings['download_template']); ?></textarea>
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Template upload', 'useyourdrive'); ?>
              <span class="help" title="<p>Template for email notification</p>">?</span>
            </h4>
            <div class="section largeinput">
              <textarea name="use_your_drive_settings[upload_template]" id="upload_template" cols="" rows="6"><?php echo esc_attr($this->settings['upload_template']); ?></textarea>
            </div>
          </div>
          <div class="option">
            <h4><?php _e('Template deletion', 'useyourdrive'); ?>
              <span class="help" title="<p>Template for email notification</p>">?</span>
            </h4>
            <div class="section largeinput">
              <textarea name="use_your_drive_settings[delete_template]" id="delete_template" cols="" rows="6"><?php echo esc_attr($this->settings['delete_template']); ?></textarea>
            </div>
          </div>
        </div>
        <!-- End Notifications Tab -->

        <!--  Statistics Tab -->
        <div id="settings_stats">
          <div class="option option-help">
            <h4><?php _e('Statistics', 'useyourdrive'); ?>
            </h4>
            <div class="section">
              <div class="description">
                Would you like to see some statistics about your files? Use-your-Drive can send all download/upload events to Google Analytics. 
                If you enable this feature, please make sure you already added your <a href="https://support.google.com/analytics/answer/1008080?hl=en">Google Analytics web tracking</a> code to your site.
              </div>
            </div>
          </div>

          <div class="option">
            <h4><?php _e('Enable Google Analytics', 'useyourdrive'); ?>
              <span class="help" title="<p>Enable Google Analytics to track all download/upload/stream events</p>">?</span>
            </h4>
            <div class="section">
              <select type="text" name="use_your_drive_settings[google_analytics]" id="google_analytics">
                <option value="Yes" <?php echo ($this->settings['google_analytics'] === "Yes" ? "selected='selected'" : ''); ?>>Yes</option>
                <option value="No" <?php echo ($this->settings['google_analytics'] === "No" ? "selected='selected'" : ''); ?>>No</option>
              </select>
            </div>
          </div> 
        </div>
        <!-- End Statistics Tab -->

        <!-- Auto Updater Tab -->
        <?php if (!is_multisite() || !is_plugin_active_for_network(dirname(plugin_basename(__DIR__)) . '/use-your-drive.php')) { ?>
          <div id="settings_update">
            <div class="option option-help">
              <h4><?php _e('Auto-updater', 'useyourdrive'); ?>
              </h4>
              <div class="section">
                <div class="description">
                  We recommend you to insert your Purchase code, so you can receive updates and bugfixes. <a href="http://support.envato.com/index.php?/Knowledgebase/Article/View/506/54/where-can-i-find-my-purchase-code" target="_blank">Where do I find the purchase code</a>? After you inserted your purchase code, check if there is an <a href="<?php echo get_admin_url(null, 'update-core.php?force-check=1'); ?>">update available</a>.
                </div>
              </div>
            </div>

            <div class="option">
              <h4><?php _e('Purchase code', 'useyourdrive'); ?>
                <span class="help" title="<p>If you want to receive updates (recommended), insert your purchase code.</p>">?</span>
              </h4>
              <div class="section largeinput">
                <input type="text" name="use_your_drive_settings[purcase_code]" id="purcase_code" value="<?php echo esc_attr($this->settings['purcase_code']); ?>" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX" maxlength="37">
              </div>
            </div> 

          </div>
        <?php } ?>
        <!-- End Auto Updater Tab -->

        <!-- System info Tab -->
        <div id="settings_system">
          <div class="option option-help">
            <h4><?php _e('System information', 'useyourdrive'); ?>
            </h4>
            <div class="section">
              <div class="description">
                <?php
                echo $this->checkDependencies();
                ?>
              </div>
            </div>
          </div>
        </div>
        <!-- End System info -->
        <!-- Help Tab -->
        <div id="settings_help">
          <div class="option option-help">
            <h4><?php _e('Support & Documentation', 'useyourdrive'); ?></h4>
            <div class="section">
              <div class="description">
                <p><a href='http://goo.gl/En2CUY' title='Use your Drive documentation' target="_blank"><?php _e('Visit the Use-your-Drive website', 'useyourdrive'); ?></a> <?php _e('for documentation and installation details', 'useyourdrive'); ?>.</p>
                <p><?php _e('Discovered a bug or just need some help with the plugin?', 'useyourdrive'); ?> <a href='http://goo.gl/gHsdBC' title='Use-your-Drive support' target="_blank"><?php _e('Visit the support page', 'useyourdrive'); ?></a>.</p>
              </div>
            </div>
          </div>
        </div>
        <!-- End Help info -->
      </div>
      <?php submit_button(); ?>
    </div>
  </form>
  <script type="text/javascript" >
    jQuery(document).ready(function ($) {
      $('#lightbox_skin_selectbox').ddslick({
        width: 330,
        imagePosition: "right",
        background: '#FFFFFF',
        onSelected: function (item) {
          $("#lightbox_skin").val($('#lightbox_skin_selectbox').data('ddslick').selectedData.value);
        }
      });
      $('#mediaplayer_skin_selectbox').ddslick({
        width: 330,
        imagePosition: "right",
        background: '#FFFFFF',
        onSelected: function (item) {
          $("#mediaplayer_skin").val($('#mediaplayer_skin_selectbox').data('ddslick').selectedData.value);
        }
      });
    });
  </script>
</div>