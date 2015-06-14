<?php

require_once 'UseyourDrive.php';

class UseyourDrive_Gallery extends UseyourDrive {

  private $_search = false;

  public function getImagesList() {

    $hardrefresh = (isset($_REQUEST['hardrefresh'])) ? true : false;
    $this->_folder = $this->getFolder(false, false, $hardrefresh);

    if (($this->_folder !== false)) {

      /* Create Image Array */
      $this->imagesarray = $this->createImageArray();

      $this->renderImagesList();
    }
  }

  public function searchImageFiles() {
    $this->_search = true;
    $input = mb_strtolower($_REQUEST['query'], 'UTF-8');
    $this->_folder = array();
    $this->_folder['contents'] = $this->searchByName($input);

    if (($this->_folder !== false)) {
      //Create Gallery array
      $this->imagesarray = $this->createImageArray();

      $this->renderImagesList();
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

  public function renderImagesList() {

    // Create HTML Filelist
    $imageslist_html = "";

    if (count($this->imagesarray) > 0) {
      $imageslist_html = "<div class='images image-collage'>";
      foreach ($this->imagesarray as $item) {
        // Render folder div
        if ($item['is_dir']) {
          $imageslist_html .= $this->renderDir($item);
        }
      }

      $imageslist_html .= $this->renderNewFolder();

      $i = 0;
      foreach ($this->imagesarray as $item) {

        // Render file div
        if (!$item['is_dir']) {
          $hidden = (($this->options['maximages'] !== '0') && ($i >= $this->options['maximages']));
          $imageslist_html .= $this->renderFile($item, $hidden);
          $i++;
        }
      }

      $imageslist_html .= "</div>";
    } else {
      if ($this->_search === true) {
        $imageslist_html .= '<div class="no_results">' . __('No files or folders found', 'useyourdrive') . '</div>';
      }
    }



    //Create HTML Filelist title
    $filepath = '';

    if ($this->_search === true) {
      $filepath = __('Results', 'useyourdrive');
    } elseif ($this->_userFolder !== false) {
      $filepath = "<a href='javascript:void(0)' class='folder' data-id='" . $this->_rootFolder . "'>" . $this->_userFolder->getTitle() . "</a>";
    } else {
      if ($this->_rootFolder === $this->_folder['folder']->getItem()->getId()) {
        $filepath = "<a href='javascript:void(0)' class='folder' data-id='" . $this->_folder['folder']->getItem()->getId() . "'>" . $this->options['root_text'] . "</a>";
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
      }
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

    $nextimages = '';
    if (($this->options['maximages'] !== '0') && (count($this->imagesarray) > $this->options['maximages'])) {
      $nextimages = '<div class="nextimages" data-loadimages="' . $this->options['maximages'] . '"><a><i class="fa fa-play-circle"></i>&nbsp;' . __('Load more...', 'outofthebox') . '</a></div>';
    }

    echo json_encode(array(
        'folderPath' => base64_encode(serialize($this->_folderPath)),
        'lastFolder' => $lastFolder,
        'breadcrumb' => $filepath,
        'html' => $imageslist_html . $nextimages,
        'expires' => $expires));

    die();
  }

  public function renderDir($item) {
    $return = "";
    if ($item['parentfolder'] === true) {
      $return .= "<div class='image-container image-folder " . (($item['parentfolder']) ? 'parentfolder' : '') . "' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>";
    } else {
      $return .= "<div class='image-container image-folder entry ' data-id='" . $item['id'] . "' data-name='" . $item['basename'] . "'>";

      $return .= "<div class='entry_edit'>";
      $return .= $this->renderEditItem($item);
      $return .= $this->renderDescription($item);


      if (($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role'])) ||
              ($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role']))) {
        $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
      }
      $return .= "</div>";
    }
    $return .= "<a title='" . $item['name'] . "'>";
    $return .= "<img class='preloading image-folder-img' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . plugins_url('css/images/folder.png', dirname(__FILE__)) . "' width='" . $item['width'] . "' height='" . $item['height'] . "' style='width:" . $item['width'] . "px;height:" . $item['height'] . "px;'/>";

    if (count($item['folderimages']) > 0) {

      $number = 1;

      foreach (array_reverse($item['folderimages']) as $folderimage) {

        $thumbnaillink = $folderimage->getThumbnailLink();
        $defaultthumbnailsize = 220;
        $thumbnailsize = $this->options['targetheight'];

        $thumbnaillink = str_replace('=s' . $defaultthumbnailsize, '=s' . ($thumbnailsize * 2), $thumbnaillink);
        $thumbnailheight = $thumbnailsize;
        $thumbnailwidth = $thumbnailsize;

        $return .= "<div class='folder-thumb thumb$number' style='width:" . $thumbnailwidth . "px;height:" . $thumbnailheight . "px;background-image: url(" . $thumbnaillink . ")'></div>";
        $number++;
      }
    }

    $return .= "<div class='folder-text'>" . $item['name'] . "</div></a>";

    $return .= "</div>\n";

    return $return;
  }

  public function renderFile($item, $hidden = false) {

    $class = ($hidden) ? 'hidden' : '';
    $classmoveable = (($this->options['move'] === '1' && $this->checkUserRole($this->options['move_role']))) ? 'moveable' : '';

    $return = "<div class='image-container $class entry ' data-id='" . $item['id'] . "' data-name='" . $item['name'] . "'>";

    $return .= "<div class='entry_edit'>";
    $return .= $this->renderEditItem($item);
    $return .= $this->renderDescription($item);

    if (($this->options['can_download_zip'] === '1') && ($this->checkUserRole($this->options['download_role'])) ||
            ($this->options['delete'] === '1') && ($this->checkUserRole($this->options['delete_role']))) {
      $return .= "<div class='entry_checkbox'><input type='checkbox' name='selected-files[]' class='selected-files' value='" . $item['id'] . "'/></div>";
    }
    $return .= "</div>";

    $thumbnail = 'data-options="thumbnail: \'' . $item['thumb'] . '\'"';

    $link = admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . urlencode($item['id']) . "&link=true&listtoken=" . $this->listtoken;
    if ($this->settings['loadimages'] === 'googlethumbnail') {
      $link = str_replace('=s220', '', $item['thumb']);
    }

    $return .= "<a href='" . $link . "' title='" . $item['basename'] . "' class='ilightbox-group' data-type='image' $thumbnail rel='ilightbox[" . $this->listtoken . "]' data-caption='" . $item['description'] . "'><span class='image-rollover'></span>";

    if ($item['width'] === NULL || $item['height'] === NULL) {
      $return .= "<img class='preloading $class' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . $item['thumb'] . "' data-src-retina='" . $item['thumb'] . "'/>";
    } else {
      $thumbnailsize = $this->options['targetheight']; //Max thumbnail size
      $return .= "<img class='preloading $class' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . str_replace('=s220', '=s' . round($thumbnailsize * 1.5), $item['thumb']) . "' data-src-retina='" . str_replace('=s220', '=s' . round($thumbnailsize * 2.5), $item['thumb']) . "' width='" . $item['width'] . "' height='" . $item['height'] . "' style='width:" . $item['width'] . "px;height:" . $item['height'] . "px;'/>";
    }
    $return .= "</a>";


    $return .= "</div>\n";
    return $return;
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

    $usercanread = ($this->checkUserRole($this->options['download_role']) && $item['permissions']['canread']);
    $usercanrename = ($this->checkUserRole($this->options['rename_role']) && $item['permissions']['canrename']);
    $usercandelete = ($this->checkUserRole($this->options['delete_role']) && $item['permissions']['candelete']);

    /* Download */
    if (($usercanread) && (!$item['is_dir'])) {
      $html .= "<li><a href='" . admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . $item['id'] . "&link=true&listtoken=" . $this->listtoken . "' class='entry_action_download' title='" . __('Download file', 'useyourdrive') . "'><i class='fa fa-cloud-download fa-lg'></i>&nbsp;" . __('Download file', 'useyourdrive') . "</a></li>";
    }

    /* Shortlink */
    if (($usercanread) && (!$item['is_dir'])) {
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

  public function renderNewFolder() {
    $html = '';
    if (($this->_search === false) && ($this->options['addfolder'] === '1')) {
      $user_can_add_folder = $this->checkUserRole($this->options['addfolder_role']);

      if ($user_can_add_folder) {
        $height = $this->options['targetheight'];
        $html .= "<div class='image-container image-folder image-add-folder grey newfolder'>";
        $html .= "<a title='" . __('Add folder', 'useyourdrive') . "'>";
        $html .= "<img class='preloading' src='" . USEYOURDRIVE_ROOTPATH . "/css/images/transparant.png' data-src='" . plugins_url('css/images/addfolder.png', dirname(__FILE__)) . "' width='$height' height='$height' style='width:" . $height . "px;height:" . $height . "px;'/>";
        $html .= "<div class='folder-text'>" . __('Add folder', 'useyourdrive') . "</div>";
        $html .= "</a>";
        $html .= "</div>\n";
      }
    }
    return $html;
  }

  public function createImageArray() {
    $imagearray = array();

    $this->setParentFolder();
    //Add folders and files to filelist
    if (count($this->_folder['contents']) > 0) {

      foreach ($this->_folder['contents'] as $node) {
        $child = $node->getItem();

        /* Check if entry is allowed */
        if (!$this->_isEntryAuthorized($node)) {
          continue;
        }

        /* set permissions / */
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

        if ($child->getMimeType() === 'application/vnd.google-apps.folder') {
          //Read folder for possible images
          $folder = $this->getFolder(false, $child->getId());

          $foldercontents = array();
          if (isset($folder['contents'])) {
            $foldercontents = $folder['contents'];
          }
          $folderimages = array();

          foreach ($foldercontents as $foldernode) {

            $entry = $foldernode->getItem();
            /* Check if entry is allowed */
            if (!$this->_isEntryAuthorized($foldernode)) {
              continue;
            }

            if ($entry->getMimeType() === 'application/vnd.google-apps.folder') {
              continue;
            }

            $thumbnaillink = $entry->getThumbnailLink();
            if (!empty($thumbnaillink)) {
              $folderimages[] = $entry;
            }
          }

          if (count($folderimages) > 0) {
            //Process only the first three
            $folderimages = array_slice($folderimages, 0, 3);
          }

          $extension = (isset($child->fileExtension)) ? $child->fileExtension : '';
          $basename = (isset($child->fileExtension)) ? str_replace('.' . $extension, '', $child->title) : $child->title;

          array_push($imagearray, array(
              'name' => $child->title,
              'basename' => $basename,
              'id' => $child->id,
              'is_dir' => ($child->mimeType === 'application/vnd.google-apps.folder'),
              'url' => $child->getWebContentLink(),
              'thumb' => plugins_url('css/images/folder.png', dirname(__FILE__)),
              'width' => $this->options['targetheight'],
              'height' => $this->options['targetheight'],
              'folderimages' => $folderimages,
              'parentfolder' => false,
              'edited' => $child->getModifiedDate(),
              'size' => 0,
              'permissions' => array(
                  'canread' => $canread,
                  'candelete' => $candelete,
                  'canadd' => $canadd,
                  'canrename' => $canrename,
              )
          ));
          continue;
        }

        //add files with thumbnails
        $thumbnaillink = $child->getThumbnailLink();
        if (!empty($thumbnaillink)) {

          $thumbnailsize = $this->options['targetheight']; //Max thumbnail size
          $thumbnailheight = $thumbnailsize;
          $thumbnailwidth = $thumbnailsize;

          $thumbnail = $child->getImageMediaMetadata();

          if ($thumbnail !== NULL) {
            $imageheight = $thumbnail->getHeight();
            $imagewidth = $thumbnail->getWidth();

            if ($imageheight > $imagewidth) {
              $thumbnailwidth = ($thumbnailsize * $imagewidth) / $imageheight;
            } elseif ($imageheight < $imagewidth) {
              $thumbnailheight = ($thumbnailsize * $imageheight) / $imagewidth;
            }
          } else {
            $thumbnaillink = $child->getThumbnailLink();
            $thumbnailheight = NULL;
            $thumbnailwidth = NULL;
          }

          $extension = (isset($child->fileExtension)) ? $child->fileExtension : '';
          $basename = (isset($child->fileExtension)) ? str_replace('.' . $extension, '', $child->title) : $child->title;

          array_push($imagearray, array(
              'name' => $child->title,
              'basename' => $basename,
              'id' => $child->id,
              'is_dir' => ($child->mimeType === 'application/vnd.google-apps.folder'),
              'url' => $child->getDownloadUrl(),
              'thumb' => $thumbnaillink,
              'description' => $child->getDescription(),
              'width' => $thumbnailwidth,
              'height' => $thumbnailheight,
              'size' => $child->getFileSize(),
              'edited' => $child->getModifiedDate(),
              'permissions' => array(
                  'canread' => $canread,
                  'candelete' => $candelete,
                  'canadd' => $canadd,
                  'canrename' => $canrename,
              )
          ));
        }
      }

      $imagearray = $this->sortFilelist($imagearray);
    }

    return $imagearray;
  }

}
