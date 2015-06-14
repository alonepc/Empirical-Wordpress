<div class="fileupload-container" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>" >
  <div>
    <div id="fileupload-<?php echo $this->listtoken; ?>" class="fileuploadform" data-token='<?php echo $this->listtoken; ?>' data-directupload="<?php echo $directupload ?>">
      <input type="hidden" name="acceptfiletypes" value="<?php echo $acceptfiletypes; ?>">
      <div class="fileupload-buttonbar">
        <span class="fileinput-button">
          <span><?php
            if ($directupload === '0') {
              _e('Add files', 'useyourdrive');
            } else {
              _e('Upload files', 'useyourdrive');
            }
            ?></span>

          <?php
          ## Mobile browser don't always like the multiple attribute causing bad uploads
          if (wp_is_mobile()) {
            ?>
            <input type="file" name="files[]" class='fileupload-browse-button'>
            <?php
          } else {
            ?>
            <input type="file" name="files[]" multiple="multiple" class='fileupload-browse-button'>
            <?php
          }
          ?>

        </span>
        <?php if ($directupload === '0') { ?>
          <button type="submit" class="start">
            <span><?php _e('Start upload', 'useyourdrive'); ?></span>
          </button>
          <button type="reset" class="cancel">
            <span><?php _e('Cancel upload', 'useyourdrive'); ?></span>
          </button>
        <?php } ?>
        <span class="filesize"><?php _e('Max. ', 'useyourdrive'); ?> <span><?php echo UseyourDrive_bytesToSize1024($this->options['maxfilesize']) ?></span></span>
      </div>
      <div class='fileupload-list'>
        <div role="presentation">
          <div class="files">&nbsp;</div>

        </div>
        <input type="hidden" name="fileupload-filelist" id="fileupload-filelist" class="fileupload-filelist" value="">
      </div>
    </div>
  </div>
</div>