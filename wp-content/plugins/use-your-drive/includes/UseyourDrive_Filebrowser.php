<?php

require_once 'UseyourDrive.php';

class UseyourDrive_Filebrowser extends UseyourDrive {

  private $_search = false;
  private $_parentfolders = array();
  private $_layout;

  public function getFilesList() {

    $hardrefresh = (isset($_REQUEST['hardrefresh']) && $_REQUEST['hardrefresh'] == 'true') ? true : false;
    $this->_folder = $this->getFolder(false, false, $hardrefresh);

    if (($this->_folder !== false)) {
      $this->setLayout();
      $this->filesarray = $this->createFilesArray();
      $this->renderFilelist();
    }
  }

  public function searchFiles() {
    $this->_search = true;
    $input = mb_strtolower($_REQUEST['query'], 'UTF-8');
    $this->_folder = array();
    $this->_folder['contents'] = $this->searchByName($input);

    if (($this->_folder !== false)) {
      $this->setLayout();
      $this->filesarray = $this->createFilesArray();

      $this->renderFilelist();
    }
  }

  public function setLayout() {

    /* Set layout */
    $this->_layout = $this->options['filelayout'];
    if (isset($_REQUEST['filelayout'])) {
      switch ($_REQUEST['filelayout']) {
        case 'grid':
          $this->_layout = 'grid';
          break;
        case 'list':
          $this->_layout = 'list';
          break;
      }
    }
  }

  public function setParentFolder() {
    if ($this->_search === true) {
      return;
    }

    $currentfolder = $this->_folder['folder']->getItem()->getId();
    if ($currentfolder !== $this->_rootFolder) {

      /* Get parent folder from known folder path */
      $cacheparentfolder = $this->cache->isCached($this->_rootFolder);
      $parentid = end($this->_folderPath);
      if ($parentid !== false) {
        $cacheparentfolder = $this->cache->isCached($parentid);
      }

      /* Check if parent folder indeed is direct parent of entry
       * If not, return all known parents */
      $parentfolders = array();
      if ($cacheparentfolder !== false && $cacheparentfolder->hasChildren() && array_key_exists($currentfolder, $cacheparentfolder->getChildren())) {
        $parentfolders[] = $cacheparentfolder->getItem();
      } else {
        if ($this->_folder['folder']->hasParents()) {
          foreach ($this->_folder['folder']->getParents() as $parent) {
            $parentfolders[] = $parent->getItem();
          }
        }
      }
      $this->_parentfolders = $parentfolders;
    }
  }

  public function renderFilelist() {

    /* Create HTML Filelist */
    $filelist_html = "";


    $filelist_html = "<div class='files layout-" . $this->_layout . "'>";
    if (count($this->filesarray) > 0) {
      $hasfilesorfolders = false;

      foreach ($this->filesarray as $item) {
        /* Render folder div */
        if ($item['is_dir']) {
          if ($this->_layout === 'list') {
            $filelist_html .= $this->renderDirForList($item);
          } elseif ($this->_layout === 'grid') {
            $filelist_html .= $this->renderDirForGrid($item);
          }


          if ($item['parentfolder'] === false) {
            $hasfilesorfolders = true;
          }
        }
      }
    }

    if ($this->_layout === 'list') {
      $filelist_html .= $this->renderNewFolderForList();
    } elseif ($this->_layout === 'grid') {
      $filelist_html .= $this->renderNewFolderForGrid();
    }

    if (count($this->filesarray) > 0) {
      foreach ($this->filesarray as $item) {
        /* Render files div */
        if (!$item['is_dir']) {
          if ($this->_layout === 'list') {
            $filelist_html .= $this->renderFileForList($item);
          } elseif ($this->_layout === 'grid') {
            $filelist_html .= $this->renderFileForGrid($item);
          }
          $hasfilesorfolders = true;
        }
      }

      if ($hasfilesorfolders === false) {
        if ($this->options['show_files'] === '1') {
          $filelist_html .= $this->renderNoResults();
        }
      }
    } else {
      if ($this->options['show_files'] === '1' || $this->_search === true) {
        $filelist_html .= $this->renderNoResults();
      }
    }

    $filelist_html .= "</div>";

    /* Create HTML Filelist title */
    $filepath = '';

    if ($this->_search === true) {
      $filepath = __('Results', 'useyourdrive');
    } elseif ($this->_userFolder !== false) {
      $filepath = "<a href='javascript:void(0)' class='folder' data-id='" . $this->_rootFolder . "'>" . $this->_userFolder->getTitle() . "</a>";
    } else {
      if ($this->_rootFolder === $this->_folder['folder']->getItem()->getId()) {
        $filepath = "<a href='javascript:void(0)' class='folder' data-id='" . $this->_folder['folder']->getItem()->getId() . "'><strong>" . $this->options['root_text'] . "</strong></a>";
      } else {

        $parentId = $this->_rootFolder;
        $lastparent = end($this->_parentfolders);
        if ($lastparent !== false) {
          $parentId = $lastparent->getId();

          if ($parentId === $this->_rootFolder) {
            $title = $this->options['root_text'];
          } else {
            $title = $lastparent->getTitle();
          }

          $filepath = " <a href='javascript:void(0)' class='folder' data-id='" . $parentId . "'>" . $title . "</a> &laquo; ";
        } else {
          $filepath = " <a href='javascript:void(0)' class='folder' data-id='" . $parentId . "'>" . __('Back', 'useyourdrive') . "</a> &laquo; ";
        }

        $filepath .= "<a href='javascript:void(0)' class='folder' data-id='" . $this->_folder['folder']->getItem()->getId() . "'><strong>" . $this->_folder['folder']->getItem()->getTitle() . "</strong>";

        $filepath .="</a>";
      }
    }

    $raw_path = '';
    if (($this->_search !== true) && (current_user_can('edit_posts') || current_user_can('edit_pages')) && (get_user_option('rich_editing') == 'true')) {
      $raw_path = $this->_folder['folder']->getItem()->getTitle();
    }

    /* lastFolder contains current folder path of the user */
    if ($this->_search !== true && (end($this->_folderPath) !== $this->_folder['folder']->getItem()->getId())) {
      $this->_folderPath[] = $this->_folder['folder']->getItem()->getId();
    }

    if ($this->_search === true) {
      $lastFolder = $this->_lastFolder;
      $expires = 0;
    } else {
      $lastFolder = $this->_folder['folder']->getItem()->getId();
      $expires = $this->_folder['folder']->getExpired();
    }

    echo json_encode(array(
        'rawpath' => $raw_path,
        'folderPath' => base64_encode(serialize($this->_folderPath)),
        'lastFolder' => $lastFolder,
        'breadcrumb' => $filepath,
        'html' => $filelist_html,
        'expires' => $expires));

    die();
  }

  public function renderNoResults() {
    $html = '';

    if ($this->_layout === 'list') {
      $html .= '
  <div class="entry folder">
<div class="entry_icon">
<img src="' . USEYOURDRIVE_ROOTPATH . '/css/clouds/cloud_status_16.png" ></div>
<div class="entry_name"><a class="entry_link">' . __('No files or folders found', 'useyourdrive') . '</a></div></div>
';
    } else {
      $html .='<div class="entry file">
<div class="entry_block">
<div class="entry_thumbnail"><div class="entry_thumbnail-view-bottom"><div class="entry_thumbnail-view-center">
<a class="entry_link"><img class="preloading" src="' . USEYOURDRIVE_ROOTPATH . '/css/images/transparant.png" data-src="' . USEYOURDRIVE_ROOTPATH . '/css/clouds/cloud_status_128.png" data-src-retina="' . USEYOURDRIVE_ROOTPATH . '/css/clouds/cloud_status_256.png"></a></div></div></div>
<div class="entry_name"><a class="entry_link"><div class="entry-name-view"><span><strong>' . __('No files or folders found', 'useyourdrive') . '</strong></span></div></a></div>
</div>
</div>';
    }

    return $html;
  }

  public function renderDirForList($item) {
    $return = '';
    $return .= "<div class='entry folder " . (($item['parentfolder']) ? 'parentfolder' : '') . "' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>\n";
    $return .= "<div class='entry_icon'><img src='" . $item['icon'] . "'/></div>";

    $return .= "<div class='entry_name'><a class='entry_link'>" . $item['name'] . "</a></div>";

    if ($item['parentfolder'] === false) {
      if ((($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
              (($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role'])))) {
        $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
      }


      if ($this->options['mcepopup'] === 'links') {
        $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
      }

      $return .= "<div class='entry_edit'>";
      $return .= $this->renderDescription($item);
      $return .= $this->renderEditItem($item);
      $return .= "</div>";
    }

    $return .= "</div>\n";
    return $return;
  }

  public function renderDirForGrid($item) {

    $return = '';
    $return .= "<div class='entry folder " . (($item['parentfolder']) ? 'parentfolder' : '') . "' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>\n";
    if ($item['parentfolder'] === false) {
      if ($this->options['mcepopup'] === 'linkto') {
        $return .= "<div class='entry_linkto'>\n";
        $return .= "<span>" . "<input class='button-secondary' type='submit' title='" . __('Select folder', 'useyourdrive') . "' value='" . __('Select folder', 'useyourdrive') . "'>" . '</span>';
        $return .= "</div>";
      }
    }

    $return .= "<div class='entry_block'>\n";

    if ($item['parentfolder'] === false) {
      $return .= "<div class='entry_edit'>";
      $return .= $this->renderEditItem($item);
      $return .= $this->renderDescription($item);
      $return .= "</div>";
    }

    $return .= "<div class='entry_thumbnail'><div class='entry_thumbnail-view-bottom'><div class='entry_thumbnail-view-center'>\n";
    $return .= "<a class='entry_link'><img class='preloading' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . str_replace('=s220', '=w200-h200', $item['thumb']) . "' data-src-retina='" . str_replace('=s220', '=w400-h400', $item['thumb']) . "'/></a>";
    $return .= "</div></div></div>\n";
    $return .= "<div class='entry_name'><a class='entry_link'><div class='entry-name-view'><span>";

    if ($item['parentfolder'] === false) {

      if ((($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
              (($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role'])))) {
        $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
      }

      if (($this->options['mcepopup'] === 'links')) {
        $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
      }
    }

    $return .= $item['name'] . " </span></div></a>";
    $return .= "</div>\n";
    $return .= "</div>\n";
    $return .= "</div>\n";

    return $return;
  }

  public function renderFileForList($item) {
    $return = '';
    $classmoveable = (($this->options['move'] === '1' && $this->checkUserRole($this->options['move_role']))) ? 'moveable' : '';

    $return .= "<div class='entry file $classmoveable' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "' " . ((!empty($item['thumb'])) ? "data-tooltip=''" : '') . ">\n";
    $return .= "<div class='entry_icon'><img src='" . $item['icon'] . "'/></div>";

    $link = $this->renderFileNameLink($item);
    $title = $link['filename'] . ((($this->options['show_filesize'] === '1') && ($item['size'] > 0)) ? ' (' . UseyourDrive_bytesToSize1024($item['size']) . ')' : '&nbsp;');

    $return .= "<div class='entry_name'><a " . $link['url'] . " " . $link['target'] . " class='entry_link " . $link['class'] . "' title='$title' " . $link['lightbox'] . " data-filename='" . $link['filename'] . "'>" . $link['filename'] . "</a>";

    if ($this->_search === true) {
      $return .= "<div class='entry_foundpath'>" . $item['path'] . "</div>";
    }

    $return .= "</div>";

    if ((($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
            (($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role'])))) {
      $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
    }

    if ((in_array($this->options['mcepopup'], array('links', 'embedded'))) && ($item['parentfolder'] === false)) {
      $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
    }

    $return .= "<div class='entry_edit_placheholder'><div class='entry_edit'>";
    $return .= $this->renderDescription($item);
    $return .= $this->renderEditItem($item);
    $return .= "</div></div>";

    if ($this->options['show_filesize'] === '1') {
      $size = ($item['size'] > 0) ? UseyourDrive_bytesToSize1024($item['size']) : '&nbsp;';
      $return .= "<div class='entry_size'>" . $size . "</div>";
    }

    if ($this->options['show_filedate'] === '1') {
      $edited = date_i18n(get_option('date_format') . ' H:s', strtotime($item['edited']));
      $return .= "<div class='entry_lastedit'>" . $edited . "</div>";
    }

    if (!empty($item['thumb'])) {
      $return .= "<div class='description_textbox'>";
      $return .= ((!empty($item['thumb'])) ? "<img src='" . str_replace('=s220', '=s300', $item['thumb']) . "' width='150'>" : '');
      $return .= "</div>";
    }

    $return .= "</div>\n";

    return $return;
  }

  public function renderFileForGrid($item) {
    $link = $this->renderFileNameLink($item);
    $title = $link['filename'] . ((($this->options['show_filesize'] === '1') && ($item['size'] > 0)) ? ' (' . UseyourDrive_bytesToSize1024($item['size']) . ')' : '&nbsp;');

    $classmoveable = (($this->options['move'] === '1' && $this->checkUserRole($this->options['move_role']))) ? 'moveable' : '';

    $return = '';
    $return .= "<div class='entry file $classmoveable' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>\n";
    $return .= "<div class='entry_block'>\n";

    $return .= "<div class='entry_edit'>";

    $return .= $this->renderEditItem($item);
    $return .= $this->renderDescription($item);

    $return .= "</div>";

    $return .= "<div class='entry_thumbnail'><div class='entry_thumbnail-view-bottom'><div class='entry_thumbnail-view-center'>\n";
    $return .= "<a class='entry_thumbnail_link'  title='" . $title . "'><img class='preloading' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . str_replace('=s220', '=w200-h200', $item['thumb']) . "' data-src-retina='" . str_replace('=s220', '=w400-h400', $item['thumb']) . "' data-src-backup='" . $item['thumbicon'] . "'/></a>";
    $return .= "</div></div></div>\n";

    $return .= "<div class='entry_name'>";

    if ((($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role']))) ||
            (($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role'])))) {
      $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
    }

    if ((in_array($this->options['mcepopup'], array('links', 'embedded'))) && ($item['parentfolder'] === false)) {
      $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
    }

    $return .= "<a " . $link['url'] . " " . $link['target'] . " class='entry_link " . $link['class'] . "' " . $link['onclick'] . " title='" . $title . "' " . $link['lightbox'] . " data-filename='" . $link['filename'] . "'><div class='entry-name-view'><span>" . $link['filename'] . "</span></div></a>";
    $return .= "</div>\n";
    $return .= "</div>\n";
    $return .= "</div>\n";

    return $return;
  }

  public function renderFileNameLink($item) {
    $class = '';
    $url = '';
    $target = '';
    $onclick = '';
    $datatype = 'iframe';

    $usercanpreview = ($item['permissions']['canread']);
    $usercanread = ($this->checkUserRole($this->options['download_role']) && $item['permissions']['canread']);

    /* Check if user is allowed to download file */
    if (($this->options['mcepopup'] === '0') && ($usercanpreview)) {
      if ($usercanread && $this->options['forcedownload'] === '1') {
        $url = admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . $item['id'] . "&link=true&listtoken=" . $this->listtoken;
        $class = 'entry_action_download';
      } else {
        $url = admin_url('admin-ajax.php') . "?action=useyourdrive-preview&id=" . urlencode($item['id']) . "&listtoken=" . $this->listtoken;
        $onclick = "sendDriveGooglePageView('Preview', '" . $item['basename'] . ((!empty($item['extension'])) ? '.' . $item['extension'] : '') . "');
    ";

        if ($usercanpreview) {
          $class = ($this->mobile) ? 'ilightbox-group' : 'ilightbox-group';
        }

        if ($item['openwithgoogle']) {
          $url = admin_url('admin-ajax.php') . "?action=useyourdrive-preview&id=" . urlencode($item['id']) . "&listtoken=" . $this->listtoken . '&openwithgoogle=1';

          if ($this->options['previewinline'] === '0') {
            $onclick = "sendDriveGooglePageView('Preview (new window)', '" . $item['basename'] . ((!empty($item['extension'])) ? '.' . $item['extension'] : '') . "');";
            $class = 'entry_action_external_view';
            $target = "_blank";
          }
        } elseif (in_array($item['extension'], array('jpg', 'jpeg', 'gif', 'png')) && $usercanread) {
          $class = ($this->mobile) ? 'ilightbox-group' : 'ilightbox-group';
          $datatype = 'image';

          $url = admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . urlencode($item['id']) . "&listtoken=" . $this->listtoken . '';
          if ($this->settings['loadimages'] === 'googlethumbnail') {
            $url = str_replace('=s220', '', $item['thumb']);
          }
        } else {
          $class = 'entry_action_download';
        }
      }
    }

    $filename = $item['basename'];
    $filename .= (($this->options['show_ext'] === '1' && !empty($item['extension'])) ? '.' . $item['extension'] : '');

    if (!empty($url)) {
      $url = "href='" . $url . "'";
    }
    if (!empty($target)) {
      $target = "target='" . $target . "'";
    }
    if (!empty($onclick)) {
      $onclick = 'onclick="' . $onclick . '"';
    }

    /* Lightbox Settings */
    $lightbox = "rel='ilightbox[" . $this->listtoken . "]' ";
    if ($datatype === 'iframe') {
      $lightbox .= 'data-options="thumbnail: \'' . $item['thumb'] . '\', width: \'85%\', height: \'80%\'" data-type="iframe"';
    } else {
      $lightbox .= 'data-options="thumbnail: \'' . $item['thumb'] . '\'" data-type="image"';
    }

    /* Return Values */
    return array('filename' => $filename, 'class' => $class, 'url' => $url, 'lightbox' => $lightbox, 'target' => $target, 'onclick' => $onclick);
  }

  public function renderDescription($item) {
    $html = '';
    if (!empty($item['description'])) {

      $title = $item['basename'] . ((($this->options['show_filesize'] === '1') && ($item['size'] > 0)) ? ' (' . UseyourDrive_bytesToSize1024($item['size']) . ')' : '&nbsp;');

      $html .= "<a class='entry_description'><i class='fa fa-info-circle fa-lg'></i></a>\n";
      $html .= "<div class='description_textbox'>";

      if (($this->options['editdescription'] === '1') && ($this->checkUserRole($this->options['editdescription_role']))) {
        $html .= "<span class='entry_edit_description'><a class='entry_action_description' data-id='" . $item['id'] . "'><i class='fa fa-pencil-square fa-lg'></i></a></span>";
      }

      $html .= "<div class='description_title'>$title</div><div class='description_text'>" . nl2br($item['description']) . "</div>";
      $html .= "</div>";
    }

    return $html;
  }

  public function renderEditItem($item) {
    $html = '';

    $usercanpreview = ($item['permissions']['canread']);
    $usercanread = ($this->checkUserRole($this->options['download_role']) && $item['permissions']['canread']);
    $usercanrename = ($this->checkUserRole($this->options['rename_role']) && $item['permissions']['canrename']);
    $usercandelete = ($this->checkUserRole($this->options['delete_role']) && $item['permissions']['candelete']);

    $filename = $item['basename'];
    $filename .= (($this->options['show_ext'] === '1' && !empty($item['extension'])) ? '.' . $item['extension'] : '');

    //Exportformats
    if ($usercanread && ($this->options['forcedownload'] === '1')) {
      if ($item['exportlinks'] !== false) {
        foreach ($item['exportlinks'] as $key => $exportlinks) {
          $extensionpos = (strripos($exportlinks, 'exportFormat=') + strlen('exportFormat='));
          $extension = substr($exportlinks, $extensionpos);
          $html .= "<li><a class='entry_action_export' data-key='" . urlencode($key) . "'><i class='fa fa-cloud-download fa-lg'></i>&nbsp;" . __('Download as', 'useyourdrive') . ' .' . strtoupper($extension) . "</a>";
        }
      }
    }

    /* View */
    if (($usercanpreview) && $this->options['forcedownload'] !== '1' && (!$item['is_dir'])) {
      if (($this->options['previewinline'] === '1')) {
        $html .= "<li><a class='entry_action_view' title='" . __('Preview', 'useyourdrive') . "'><i class='fa fa-desktop fa-lg'></i>&nbsp;" . __('Preview', 'useyourdrive') . "</a></li>";
      }

      if ($item['openwithgoogle']) {
        $url = admin_url('admin-ajax.php') . "?action=useyourdrive-preview&id=" . urlencode($item['id']) . "&listtoken=" . $this->listtoken . '&openwithgoogle=1';
        $onclick = "sendDriveGooglePageView('Preview (new window)', '" . $item['basename'] . ((!empty($item['extension'])) ? '.' . $item['extension'] : '') . "');";
        $html .= "<li><a href='$url' target='_blank' class='entry_action_external_view' onclick=\"$onclick\" title='" . __('Preview in new window', 'useyourdrive') . "'><i class='fa fa-external-link-square fa-lg'></i>&nbsp;" . __('Preview in new window', 'useyourdrive') . "</a></li>";
      }
    }

    /* Download */
    if (($usercanread) && (!$item['is_dir'])) {
      $html .= "<li><a href='" . admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . $item['id'] . "&link=true&listtoken=" . $this->listtoken . "' class='entry_action_download' data-filename='" . $filename . "' title='" . __('Download file', 'useyourdrive') . "'><i class='fa fa-cloud-download fa-lg'></i>&nbsp;" . __('Download file', 'useyourdrive') . "</a></li>";
    }

    /* Shortlink */
    if (($usercanread)) {
      if (($this->options['show_sharelink'] === '1') && ($this->checkUserRole($this->options['download_role']))) {
        $html .= "<li><a class='entry_action_shortlink' title='" . __('Sharing link', 'useyourdrive') . "'><i class='fa fa-group fa-lg'></i>&nbsp;" . __('Sharing link', 'useyourdrive') . "</a></li>";
      }
    }

    /* Rename */
    if (($this->options['rename'] === '1') && ($usercanrename)) {
      $html .= "<li><a class='entry_action_rename' title='" . __('Rename', 'useyourdrive') . "'><i class='fa fa-tag fa-lg'></i>&nbsp;" . __('Rename', 'useyourdrive') . "</a></li>";
    }

    /* Delete */
    if (($this->options['delete'] === '1') && ($usercandelete)) {
      $html .= "<li><a class='entry_action_delete' title='" . __('Delete', 'useyourdrive') . "'><i class='fa fa-times-circle fa-lg'></i>&nbsp;" . __('Delete', 'useyourdrive') . "</a></li>";
    }

    if ($html !== '') {
      return "<a class='entry_edit_menu'><i class='fa fa-chevron-circle-down fa-lg'></i></a><div id='menu-" . $item['id'] . "' class='uyd-dropdown-menu'><ul data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>" . $html . "</ul></div>\n";
    }

    return $html;
  }

  public function renderNewFolderForList() {
    $html = '';
    if (($this->_search === false) && ($this->options['addfolder'] === '1')) {
      $user_can_add_folder = $this->checkUserRole($this->options['addfolder_role']);

      if ($user_can_add_folder) {
        $html .= "<div class='entry folder newfolder'>";
        $html .= "<div class='entry_icon'><span class='ui-icon ui-icon-plusthick'></span></div>";
        $html .= "<div class='entry_name'>" . __('Add folder', 'useyourdrive') . "</div>";
        $html .= "<div class='entry_description'>" . __('Add a new folder in this directory', 'useyourdrive') . "</div>";
        $html .= "</div>";
      }
    }
    return $html;
  }

  public function renderNewFolderForGrid() {
    $return = '';
    if (($this->_search === false) && ($this->options['addfolder'] === '1')) {
      $user_can_add_folder = $this->checkUserRole($this->options['addfolder_role']);

      if ($user_can_add_folder) {

        $return .= "<div class='entry folder newfolder'>\n";
        $return .= "<div class='entry_block'>\n";
        $return .= "<div class='entry_thumbnail'><div class='entry_thumbnail-view-bottom'><div class='entry_thumbnail-view-center'>\n";
        $return .= "<a class='entry_link'><img class='preloading' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . plugins_url('css/icons/icon_10_addfolder_xl128.png', dirname(__FILE__)) . "' /></a>";
        $return .= "</div></div></div>\n";
        $return .= "<div class='entry_name'><a class='entry_link'><div class='entry-name-view'><span>" . __('Add folder', 'useyourdrive') . "</span></div></a>";
        $return .= "</div>\n";
        $return .= "</div>\n";
        $return .= "</div>\n";
      }
    }
    return $return;
  }

  public function createFilesArray() {
    $filesarray = array();

    $this->setParentFolder();

//Add folders and files to filelist
    if (count($this->_folder['contents']) > 0) {

      foreach ($this->_folder['contents'] as $node) {

        $child = $node->getItem();
        if ($child === null) {
          /* remove node? */
          continue;
        }

        /* Check if entry is allowed */
        if (!$this->_isEntryAuthorized($node)) {
          continue;
        }

        $userrole = $child->getUserPermission()->getRole();
        $canread = false;
        $candelete = false;
        $canadd = false;
        $canrename = false;

        switch ($userrole) {
          case 'commenter':
            break;
          case 'reader':
            $canread = true;
            break;
          case 'writer':
            $canread = true;
            $canadd = true;
            $canrename = true;
            break;
          case 'owner':
            $canread = true;
            $candelete = true;
            $canadd = true;
            $canrename = true;
            break;
        }

        $extension = (isset($child->fileExtension)) ? $child->getFileExtension() : '';
        $basename = str_replace('.' . $extension, '', $child->getTitle());

        $exportlinks = false;
        if (($child->getMimeType() !== 'application/vnd.google-apps.folder')) {


          /* Only show files that can be embedded in the mcepopup */
          //if ($this->options['mcepopup'] === 'embedded') {
          //  $embedlink=$child->getEmbedLink();
          //  if (empty($embedlink)) {
          //    continue;
          //  }
          //}

          $downloadlinks = $child->getDownloadUrl();
          if ($downloadlinks === null) {
            $exportlinks = $child->getExportLinks();
          }
        }

        $openwithgoogle = false;
        $openwithlink = $child->getAlternateLink();
        if (!empty($openwithlink) && (!in_array($extension, array('jpg', 'jpeg', 'gif', 'png')))) {
          $openwithgoogle = true;
        }

        $thumbnail = $child->getThumbnailLink();
        /* Thumbnails with feeds in URL give 404 without token? */
        if (strpos($thumbnail, 'google.com') !== false) {
          $token = json_decode($this->settings['googledrive_app_current_token']);
          //$thumbnail .= '&access_token=' . $token->access_token;
          $thumbnail = 'https://googledrive.com/thumb/' . $child->getId() . '?width=400&height=400&crop=false';
        }

        /* Set default thumbnail if needed */

        switch ($child->getMimeType()) {

          case 'application/vnd.google-apps.folder':
            $thumbnailicon = 'icon_10_folder_xl128.png';
            break;
          case 'application/vnd.google-apps.audio':
          case 'audio/mpeg':
            $thumbnailicon = 'icon_11_audio_xl128.png';
            break;
          case 'application/vnd.google-apps.document':
          case 'application/vnd.oasis.opendocument.text':
          case 'text/plain':
            $thumbnailicon = 'icon_11_document_xl128.png';
            break;
          case 'application/vnd.google-apps.drawing':
            $thumbnailicon = 'icon_11_drawing_xl128.png';
            break;
          case 'application/vnd.google-apps.form':
            $thumbnailicon = 'icon_11_form_xl128.png';
            break;
          case 'application/vnd.google-apps.fusiontable':
            $thumbnailicon = 'icon_11_table_xl128.png';
            break;
          case 'application/vnd.google-apps.photo':
          case 'image/jpeg':
          case 'image/png':
          case 'image/gif':
          case 'image/bmp':
            $thumbnailicon = 'icon_11_image_xl128.png';
            break;
          case 'application/vnd.google-apps.presentation':
          case 'application/vnd.oasis.opendocument.presentation':
            $thumbnailicon = 'icon_11_presentation_xl128.png';
            break;
          case 'application/vnd.google-apps.script':
          case 'application/x-httpd-php':
          case 'text/js':
            $thumbnailicon = 'icon_11_script_xl128.png';
            break;
          case 'application/vnd.google-apps.sites':
            $thumbnailicon = 'icon_11_sites_xl128.png';
            break;
          case 'application/vnd.google-apps.spreadsheet':
          case 'application/vnd.oasis.opendocument.spreadsheet':
            $thumbnailicon = 'icon_11_spreadsheet_xl128.png';
            break;
          case 'application/vnd.google-apps.video':
            $thumbnailicon = 'icon_11_video_xl128.png';
            break;

          case 'application/vnd.ms-excel':
          case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
          case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            $thumbnailicon = 'icon_11_excel_xl128.png';
            break;
          case 'application/msword':
            $thumbnailicon = 'icon_11_word_xl128.png';
            break;


          case 'application/pdf':
            $thumbnailicon = 'icon_11_pdf_xl128.png';
            break;
          default:
            $thumbnailicon = 'icon_10_generic_xl128.png';
            break;
        }

        if ($thumbnail === null) {
          $thumbnail = USEYOURDRIVE_ROOTPATH . '/css/icons/' . $thumbnailicon;
        }

        array_push($filesarray, array(
            'name' => $child->getTitle(),
            'basename' => $basename,
            'extension' => strtolower($extension),
            'id' => $child->getId(),
            'icon' => $child->getIconLink(),
            'mimetype' => $child->getMimeType(),
            'is_dir' => ($child->getMimeType() === 'application/vnd.google-apps.folder'),
            'size' => $child->getFileSize(),
            'edited' => $child->getModifiedDate(),
            'description' => $child->getDescription(),
            'exportlinks' => $exportlinks,
            'openwithgoogle' => $openwithgoogle,
            'thumb' => $thumbnail,
            'thumbicon' => USEYOURDRIVE_ROOTPATH . '/css/icons/' . $thumbnailicon,
            'path' => '',
            'parentfolder' => false,
            'permissions' => array(
                'canread' => $canread,
                'candelete' => $candelete,
                'canadd' => $canadd,
                'canrename' => $canrename,
            )
        ));
      }

      $filesarray = $this->sortFilelist($filesarray);
    }

    // Add 'back to Previous folder' if needed
    if (isset($this->_folder['folder'])) {
      $folder = $this->_folder['folder']->getItem();
      if (($this->_search === false) && ($folder->getId() !== $this->_rootFolder)) {

        foreach ($this->_parentfolders as $parentfolder) {
          array_unshift($filesarray, array(
              'name' => '<strong>' . __('Previous folder', 'useyourdrive') . ' (' . $parentfolder->getTitle() . ')</strong>',
              'basename' => $parentfolder->getTitle(),
              'id' => $parentfolder->getId(),
              'icon' => $parentfolder->getIconLink(),
              'edited' => $parentfolder->getModifiedDate(),
              'size' => 0,
              'is_dir' => true,
              'thumb' => USEYOURDRIVE_ROOTPATH . '/css/icons/icon_10_folder_xl128.png',
              'parentfolder' => true
          ));
        }
      }
    }

    return $filesarray;
  }

}
