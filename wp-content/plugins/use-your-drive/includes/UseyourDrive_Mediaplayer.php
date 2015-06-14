<?php

require_once 'UseyourDrive.php';

class UseyourDrive_Mediaplayer extends UseyourDrive {

  public function getMediaList() {

    $this->_folder = $this->getFolder();

    if (($this->_folder !== false)) {
      $this->mediaarray = $this->createMediaArray();

      if (count($this->mediaarray) > 0) {
        echo json_encode($this->mediaarray);
      }
    }

    die();
  }

  public function createMediaArray() {

    $covers = array();
    /* Create covers */
    if (count($this->_folder['contents']) > 0) {

      foreach ($this->_folder['contents'] as $key => $node) {
        $child = $node->getItem();
        /* Add images to cover array */
        if (isset($child->fileExtension) && (in_array(strtolower($child->fileExtension), array('png', 'jpg', 'jpeg')))) {
          $covertitle = str_replace('.' . $child->getFileExtension(), '', $child->getTitle());
          $coverthumb = $child->getThumbnailLink();
          $covers[$covertitle] = $coverthumb;
          unset($this->_folder['contents'][$key]);
        }
      }
    }

    $playlist = array();
    $files = array();

    //Create Filelist array
    if (count($this->_folder['contents']) > 0) {

      $foldername = $this->_folder['folder']->getItem()->getTitle();

      $files = array();
      foreach ($this->_folder['contents'] as $node) {

        $child = $node->getItem();

        if ($child->getMimeType() === 'application/vnd.google-apps.folder') {
          continue;
        }


        $extension = (isset($child->fileExtension)) ? $child->fileExtension : '';
        $basename = (isset($child->fileExtension)) ? str_replace('.' . $extension, '', $child->title) : $child->title;

        /* Check if entry is allowed */
        if (!$this->_isEntryAuthorized($node)) {
          continue;
        }

        if (isset($covers[$basename])) {
          $thumbnail = $covers[$basename];
        } elseif (isset($covers[$foldername])) {
          $thumbnail = $covers[$foldername];
        } else {
          $thumbnail = (!empty($child->thumbnailLink) ? $child->getThumbnailLink() : USEYOURDRIVE_ROOTPATH . '/css/images/audiothumb.png');
        }
        $thumbnailsmall = str_replace('=s220', '=s200-c', $thumbnail);
        $poster = str_replace('=s220', '=s1024', $thumbnail);

        // combine same files with different extensions
        if (!isset($files[$basename])) {

          $files[$basename] = array(
              'title' => $basename,
              'name' => $basename,
              'artist' => $child->getDescription(),
              'is_dir' => false,
              'poster' => $poster,
              'thumb' => $thumbnailsmall,
              'extensions' => array(),
              'size' => $child->getFileSize(),
              'edited' => $child->getModifiedDate(),
              'download' => false,
              'linktoshop' => ($this->options['linktoshop'] !== '') ? $this->options['linktoshop'] : false
          );
        }

        //Can play mp4 but need to give m4v or m4a
        if ($extension === 'mp4') {
          $extension = ($this->options['mode'] === 'audio') ? 'm4a' : 'm4v';
        }
        if ($extension === 'ogg') {
          $extension = ($this->options['mode'] === 'audio') ? 'oga' : 'ogv';
        }

        array_push($files[$basename]['extensions'], strtolower($extension));
        $files[$basename][$extension] = admin_url('admin-ajax.php') . "?action=useyourdrive-download&id=" . $child->getId() . "&auth=1&listtoken=" . $this->listtoken;
        if ($this->options['linktomedia'] === '1') {
          $files[$basename]['download'] = $files[$basename][$extension];
        }
      }

      $files = $this->sortFilelist($files);
    }

    return array_values($files);
  }

}
