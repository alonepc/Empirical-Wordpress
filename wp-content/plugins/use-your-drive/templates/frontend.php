<div class="list-container" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>;">
  <?php
  if ($this->options['show_breadcrumb'] === '1' || $this->options['search'] === '1' || $this->options['show_refreshbutton'] === '1' ||
          (($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
          ($this->options['delete'] === '1' && $this->checkUserRole($this->options['delete_role']))) {
    ?>
    <div class="nav-header">
      <?php if ($this->options['show_breadcrumb'] === '1') { ?>
        <a class="nav-home" title="<?php _e('Back to our first folder', 'useyourdrive'); ?>">
          <i class="fa fa-home pull-left"></i>
        </a>
        <?php if ($this->options['show_refreshbutton'] === '1') { ?>
          <a class="nav-refresh" title="<?php _e('Refresh', 'useyourdrive'); ?>">
            <i class="fa fa-refresh"></i>
          </a>
        <?php } ?>
        <a class="nav-gear" title="<?php _e('Options', 'useyourdrive'); ?>">
          <i class="fa fa-gear"></i>
        </a>
        <div class="gear-menu" data-token="<?php echo $this->listtoken; ?>">
          <ul>
            <li style="display: none"><a class="nav-layout nav-layout-grid" title="<?php _e('View as grid', 'useyourdrive'); ?>">
                <i class="fa fa-th-large fa-lg"></i><?php _e('View as grid', 'useyourdrive'); ?>
              </a>
            </li>
            <li><a class="nav-layout nav-layout-list" title="<?php _e('View as list', 'useyourdrive'); ?>">
                <i class="fa fa-th-list fa-lg"></i><?php _e('View as list', 'useyourdrive'); ?>
              </a>
            </li>           
            <?php
            if ($this->options['upload'] === '1' && $this->checkUserRole($this->options['upload_role'])) {
              ?>
              <li><a class="nav-upload" title="<?php _e('Upload files', 'useyourdrive'); ?>"><i class="fa fa-upload fa-lg"></i><?php _e('Upload files', 'useyourdrive'); ?></a></li>
              <?php
            }

            if (($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) {
              ?>
              <li><a class="all-files-to-zip"><i class='fa fa-cloud-download fa-lg'></i><?php _e('Download all files', 'useyourdrive'); ?> (.zip)</a></li>
              <li><a class="selected-files-to-zip"><i class='fa fa-cloud-download fa-lg'></i><?php _e('Download selected files', 'useyourdrive'); ?> (.zip)</a></li>
              <?php
            }
            if ($this->options['delete'] === '1' && $this->checkUserRole($this->options['delete_role'])) {
              ?>
              <li><a class="selected-files-delete" title="<?php _e('Delete selected files', 'useyourdrive'); ?>"><i class="fa fa-times-circle fa-lg"></i><?php _e('Delete selected files', 'useyourdrive'); ?></a></li>
              <?php
            }
            ?>
            <li class='gear-menu-no-options' style="display: none"><a><i class='fa fa-info-circle fa-lg'></i><?php _e('No options...', 'useyourdrive') ?></a></li>
          </ul>
        </div>
        <?php
      }

      if ($this->options['search'] === '1') {
        ?>
        <a class="nav-search">
          <i class="fa fa-search"></i>
        </a>

        <div class="search-div">
          <div class="search-remove"><i class="fa fa-times-circle fa-lg"></i></div>
          <input name="q" type="text" size="40" placeholder="<?php echo __('Search filenames', 'useyourdrive') . (($this->options['searchcontents'] === '1') ? ' ' . __('and within contents', 'useyourdrive') : ''); ?>" class="search-input" />
        </div>
      <?php }; ?>
      <?php if ($this->options['show_breadcrumb'] === '1') { ?>
        <div class="nav-title"><?php _e('Loading...', 'useyourdrive'); ?></div>
      <?php }; ?>
    </div>
  <?php } ?>
  <?php if ($this->options['show_columnnames'] === '1') { ?>
    <div class='column_names'>
      <div class='entry_icon'></div>
      <div class='entry_name sortable <?php echo ($this->options['sort_field'] === 'name') ? $this->options['sort_order'] : ''; ?>' data-sortname="name"><a class='entry_sort'><?php _e('Name', 'useyourdrive'); ?></a><span class="sort_icon">&nbsp;</span></div>
      <?php
      if ((($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
              ($this->options['delete'] === '1' && $this->checkUserRole($this->options['delete_role']))) {
        ?>
        <div class='entry_checkallbox'><input type='checkbox' name='select-all-files' class='select-all-files'/></div>
        <?php
      };
      ?>
      <div class='entry_edit'>&nbsp;</div>
      <?php
      if ($this->options['show_filesize'] === '1') {
        ?>
        <div class='entry_size sortable <?php echo ($this->options['sort_field'] === 'size') ? $this->options['sort_order'] : ''; ?>' data-sortname="size"><span class="sort_icon">&nbsp;</span><a class='entry_sort'><?php _e('Size', 'useyourdrive'); ?></a></div>
        <?php
      };

      if ($this->options['show_filedate'] === '1') {
        ?>
        <div class='entry_lastedit sortable <?php echo ($this->options['sort_field'] === 'modified') ? $this->options['sort_order'] : ''; ?>' data-sortname="modified"><a class='entry_sort'><?php _e('Date modified', 'useyourdrive'); ?></a><span class="sort_icon">&nbsp;</span></div>
        <?php
      };
      ?>

    </div>
  <?php }; ?>
  <div class="loading initialize">&nbsp;</div>
  <div class="ajax-filelist" style="<?php echo (!empty($this->options['maxheight'])) ? 'max-height:' . $this->options['maxheight'] . ';overflow-y: scroll;' : '' ?>">&nbsp;</div>
</div>