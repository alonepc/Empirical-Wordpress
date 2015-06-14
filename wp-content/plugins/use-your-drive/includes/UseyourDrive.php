<?php

require_once 'UseyourDrive_Processor.php';

// Load Google Drive SDK
// hack around with the include paths a bit so the library 'just works'
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
if (!function_exists('google_api_php_client_autoload')) {
  try {
    require_once "Google-sdk/src/Google/autoload.php";
  } catch (Exception $ex) {
    return new WP_Error('broke', __('Something went wrong... See settings page', 'outofthebox'));
  }
}

class UseyourDrive extends UseyourDrive_Processor {

  /**
   *  @var Google_Client
   */
  private $client = null;
  private $userInfoService;
  private $googleDriveService;
  private $googleUrlshortenerService;
  protected $apifilefields = 'thumbnailLink,alternateLink,id,description,embedLink,etag,downloadUrl,iconLink,exportLinks,mimeType,modifiedDate,fileExtension,webContentLink,fileSize,userPermission,imageMediaMetadata(width,height),permissions(id,kind,name,role,type,value,withLink),kind,parents(id,isRoot,kind),title,openWithLinks';
  protected $apilistfilesfields = 'items(thumbnailLink,alternateLink,id,description,embedLink,etag,downloadUrl,iconLink,exportLinks,mimeType,modifiedDate,fileExtension,webContentLink,fileSize,userPermission,imageMediaMetadata(width,height),kind,permissions(kind,name,role,type,value,withLink), parents(id,isRoot,kind),title,openWithLinks),kind';

  /*
   * Try to load prestored token
   *
   * @return boolean|WP_Error
   */

  public function loadToken() {
    if (empty($this->settings['googledrive_app_current_token'])) {
      return new WP_Error('broke', __("The plugin isn't yet authorized to use your Google Drive! Please (re)-authorize the plugin", 'useyourdrive'));
    } else {
      $this->accessToken = $this->settings['googledrive_app_current_token'];
      $this->refreshToken = $this->settings['googledrive_app_refresh_token'];
    }

    return true;
  }

  /*
   * Revoke token
   *
   * @return boolean|WP_Error
   */

  public function revokeToken() {
    $this->client->revokeToken();
    $this->accessToken = '';
    $this->refreshToken = '';
    $this->settings['googledrive_app_current_token'] = '';
    $this->settings['googledrive_app_refresh_token'] = '';
    update_option('use_your_drive_lists', array());
    update_option('use_your_drive_cache', array(
        'last_update' => null,
        'last_cache_id' => '',
        'locked' => false,
        'cache' => ''
    ));
    update_option('use_your_drive_settings', $this->settings);
    return true;
  }

  /*
   * Read Google Drive app key and secret
   */

  function setAppConfig($approval = 'auto') {
    $this->client = new Google_Client();

    /* Set Retries */
    $this->client->setClassConfig('Google_Task_Runner', 'retries', 5);

    $this->userInfoService = new Google_Service_Oauth2($this->client);
    $this->googleDriveService = new Google_Service_Drive($this->client);
    $this->googleUrlshortenerService = new Google_Service_Urlshortener($this->client);

    if ((!empty($this->settings['googledrive_app_client_id'])) && (!empty($this->settings['googledrive_app_client_secret']))) {
      $this->client->setClientId($this->settings['googledrive_app_client_id']);
      $this->client->setClientSecret($this->settings['googledrive_app_client_secret']);
    } else {
      $this->client->setClientId('538839470620-fvjmtsvik53h255bnu0qjmbr8kvd923i.apps.googleusercontent.com');
      $this->client->setClientSecret('UZ1I3I-D4rPhXpnE8T1ggGhE');
    }

    $this->client->setRedirectUri('http://www.florisdeleeuw.nl/use-your-drive/index.php');

    $this->client->setApprovalPrompt($approval);
    $this->client->setAccessType('offline');

    $this->client->setScopes(array(
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/urlshortener'));

    $page = isset($_GET["page"]) ? '?page=' . $_GET["page"] : '';
    $location = get_admin_url(null, 'admin.php' . $page);

    $this->client->setState(strtr(base64_encode($location), '+/=', '-_~'));

    /* Logger */
    $this->client->setClassConfig('Google_Logger_File', array(
        'file' => USEYOURDRIVE_CACHEDIR . '/log',
        'mode' => 0640,
        'lock' => true));

    $this->client->setClassConfig('Google_Logger_Abstract', array(
        'level' => 'debug', //'warning' or 'debug'
        'log_format' => "[%datetime%] %level%: %message% %context%\n",
        'date_format' => 'd/M/Y:H:i:s O',
        'allow_newlines' => true));

    /* Uncomment the following line to log communcations.
     * The log is located in /cache/log
     */
    //$this->client->setLogger(new Google_Logger_File($this->client));
    return true;
  }

  /*
   * Start Google Drive API Client with token
   *
   */

  public function startClient() {
    if ($this->accessToken === false)
      die();

    try {
      $token = $this->accessToken;
      $this->client->setAccessToken($token);

      if ($this->client->isAccessTokenExpired()) {
        $tokenobj = json_decode($token);
        if (isset($tokenobj->refresh_token)) {
          try {
            $this->client->refreshToken($tokenobj->refresh_token);
          } catch (Exception $e) {
            $this->settings['googledrive_app_current_token'] = '';
            $this->settings['googledrive_app_refresh_token'] = '';
            update_option('use_your_drive_settings', $this->settings);

            if ($this->settings['lostauthorization_notification'] !== 'No') {
              $subject = get_bloginfo() . ' | ' . __('Use-your-Drive authorization', 'useyourdrive');
              $message = 'Hi!

The Use-your-Drive plugin has lost its authorization to Box. Without authorization the plugin cannot longer function. Please authorize the plugin again to make sure that your visitors can access your files.';

              $message .= "<br>********<br>" . $e->getMessage() . '<br>********';
              $result = wp_mail($this->settings['lostauthorization_notification'], $subject, $message);
            }

            return new WP_Error('broke', __("Use-your-Drive isn't ready to run", 'useyourdrive') . $e->getMessage());
          }
          $this->accessToken = $this->client->getAccessToken();

          $this->settings['googledrive_app_current_token'] = $this->accessToken;
          update_option('use_your_drive_settings', $this->settings);
        } else {
          $this->settings['googledrive_app_current_token'] = '';
          $this->settings['googledrive_app_refresh_token'] = '';
          update_option('use_your_drive_settings', $this->settings);
          return new WP_Error('broke', __("Use-your-Drive isn't ready to run", 'useyourdrive'));
        }
      }
    } catch (Exception $e) {
      return new WP_Error('broke', __("Couldn't connect to Google API: ", 'useyourdrive') . $e->getMessage());
    }

    return $this->client;
  }

  /*
   * Get AccountInfo
   *
   * @return mixed|WP_Error
   */

  function getAccountInfo() {
    if ($this->client === null)
      return false;

    $accountInfo = null;

    try {
      $accountInfo = $this->userInfoService->userinfo->get(array("userIp" => $this->userip));
    } catch (Exception $ex) {
      return new WP_Error('broke', $ex->getMessage());
    }
    if ($accountInfo != null && $accountInfo->getId() != null) {
      return $accountInfo;
    } else {
      return new WP_Error('broke', $ex->getMessage());
    }
  }

  /*
   * Get DriveInfo
   *
   * @return mixed|WP_Error
   */

  function getDriveInfo() {
    if ($this->client === null)
      return false;

    $driveInfo = null;

    try {
      $driveInfo = $this->googleDriveService->about->get(array("fields" => 'name,quotaBytesUsed,rootFolderId,kind,quotaBytesTotal,user', "userIp" => $this->userip));
    } catch (Exception $ex) {
      return new WP_Error('broke', $ex->getMessage());
    }
    if ($driveInfo !== null) {
      return $driveInfo;
    } else {
      return new WP_Error('broke', $ex->getMessage());
    }
  }

  /*
   * Gets a $authorizeUrl
   *
   * @return string|WP_Error
   * The URL to redirect the user to.
   */

  public function startWebAuth() {
    try {
      $authorizeUrl = $this->client->createAuthUrl();
    } catch (Exception $ex) {
      return new WP_Error('broke', __("Could not start authorization: ", 'useyourdrive') . $ex->getMessage());
    }
    update_option('use_your_drive_settings', $this->settings);
    return $authorizeUrl;
  }

  /*
   * Creates token after the user has visited the authorize URL, approved the app,
   * and was redirected to your redirect URI.
   *
   * @return WP_Error|true
   */

  public function createToken() {
    try {
      $this->client->authenticate($_GET['code']);

      $token = $this->client->getAccessToken();
      $this->accessToken = $token;

      $this->settings['googledrive_app_current_token'] = $token;
      $this->settings['googledrive_app_refresh_token'] = $token;
    } catch (Exception $ex) {
      return new WP_Error('broke', __("Error communicating with Google Drive API: ", 'useyourdrive') . $ex->getMessage());
    }

    $this->cache->resetCache();
    update_option('use_your_drive_settings', $this->settings);

    return true;
  }

  public function getMultipleEntries($entries) {
    if ($this->client === null)
      return false;

    $this->client->setUseBatch(true);
    $batch = new Google_Http_Batch($this->client);

    foreach ($entries as $entryid) {
      $batch->add($this->googleDriveService->files->get($entryid, array("userIp" => $this->userip)), $entryid);
    }

    try {
      usleep(150000); // Don't fire multiple queries fast
      $batch_result = $batch->execute();
    } catch (Exception $ex) {
      return false;
    }
    $this->client->setUseBatch(false);

    return $batch_result;
  }

  /* Get entry */

  public function getEntry($entryid = false, $hardrefresh = false) {
    if ($this->client === null)
      return false;

    if ($entryid === false) {
      $entryid = $this->_requestedEntry;
    }

    /* Check if root is set */
    if (!$this->cache->getRoot()) {
      $this->getFolder(true);
    }

    /* Get entry from cache */
    $cachedentry = ($hardrefresh) ? false : $this->cache->isCached($entryid);

    /* If entry isn't cached */
    if (!$cachedentry) {

      try {
        $entry = $this->googleDriveService->files->get($entryid, array("userIp" => $this->userip, 'fields' => $this->apifilefields));
        $cachedentry = $this->cache->addToCache($entry);
      } catch (Exception $e) {
        return false;
      }
    }

    if (!$this->_isEntryAuthorized($cachedentry)) {
      return false;
    }

    return $cachedentry;
  }

  /*
   * Get folders and files
   */

  public function getFolder($root = false, $folderid = false, $hardrefresh = false, $checkauthorized = true) {
    if ($this->client === null)
      return false;

    if ($folderid === false) {
      $folderid = $this->_requestedEntry;
    }

    if ($root === true) {
      if ($this->cache->getRoot()) {
        return $this->cache->getRoot();
      } else {
        try {
          $folderid = $this->googleDriveService->about->get(array("userIp" => $this->userip))->getRootFolderId();
        } catch (Exception $ex) {
          return new WP_Error('broke', __("Error communicating with Google Drive API: ", 'useyourdrive') . $ex->getMessage());
        }
      }
    } elseif (!$this->cache->getRoot()) {
      $this->getFolder(true);
    }

    $cachedfolder = $this->cache->isCached($folderid, false, $hardrefresh);

    if (!$cachedfolder) {
      if (($root === true) || ($folderid === $this->options['base'])) {
        $params = array('q' => "'root' in parents and trashed = false", "fields" => $this->apilistfilesfields, "maxResults" => 999, "userIp" => $this->userip);
      } else {
        $params = array('q' => "'" . $folderid . "' in parents and trashed = false", "fields" => $this->apilistfilesfields, "maxResults" => 999, "userIp" => $this->userip);
      }

      $this->client->setUseBatch(true);
      $batch = new Google_Http_Batch($this->client);

      $batch->add($this->googleDriveService->files->get($folderid, array("fields" => $this->apifilefields, "userIp" => $this->userip)), 'folder');
      $batch->add($this->googleDriveService->files->listFiles($params), 'foldercontents');

      try {
        usleep(50000);
        $results = $batch->execute();
      } catch (Exception $ex) {
        return false;
      }

      $this->client->setUseBatch(false);
      $folder = $results['response-folder'];

      if (($root === true) || ($folderid === $this->options['base'])) {
        $cachedfolder = $this->cache->setRoot($folder);
      } else {
        $cachedfolder = $this->cache->addToCache($folder);
      }

      $files_in_folder = $results['response-foldercontents'];

      $cachedfolder->setChecked(true);

      /* Add all entries in folder to cache */
      foreach ($files_in_folder->getItems() as $item) {
        $newitem = $this->cache->addToCache($item);
      }
    }

    $folder = $cachedfolder;
    $files_in_folder = $cachedfolder->getChildren();

    /* Check if folder is in the shortcode-set rootfolder */
    if ($root === true) {
      return $folder;
    } elseif ($checkauthorized === true) {
      if (!$this->_isEntryAuthorized($cachedfolder)) {
        return false;
      }
    }

    return array('folder' => $folder, 'contents' => $files_in_folder);
  }

  public function searchByName($query) {
    if ($this->client === null)
      return false;

    if ($this->options['searchfrom'] === 'parent') {
      $searchedfolder = $this->_requestedEntry;
    } else {
      $searchedfolder = $this->_rootFolder;
    }

    /* Set search field */
    if ($this->options['searchcontents'] === '1') {
      $field = 'fullText';
    } else {
      $field = 'title';
    }

    /* Find all items containing query */
    $params = array('q' => $field . " contains '" . addslashes($query) . "' and trashed = false", "fields" => $this->apilistfilesfields, "maxResults" => 999, "userIp" => $this->userip);

    try {
      $found_entries = $this->googleDriveService->files->listFiles($params);
    } catch (Exception $ex) {
      return array();
    }


    $entries_in_searchedfolder = array();

    foreach ($found_entries->getItems() as $entry) {
      /* Check if entries are in cache */
      $cachedentry = $this->cache->isCached($entry->getId());

      /* If not found, add to cache */
      if ($cachedentry === false) {
        $cachedentry = $this->cache->addToCache($entry);
      }

      /* Keep all entries that are in searched folder */

      if ($this->_isEntryAuthorized($cachedentry) && $cachedentry->isInFolder($searchedfolder)) {
        $entries_in_searchedfolder[] = $cachedentry;
      }
    }

    return $entries_in_searchedfolder;
  }

  /*
   * Uploads file to server
   * After upload send file to Google Drive
   * and delete files from tempdir
   */

  function uploadFile() {

    $cachedfolder = $this->cache->isCached($this->_requestedEntry);

    if ($cachedfolder === false) {
      $cachedfolder = $this->getEntry($this->_requestedEntry);
      if ($cachedfolder === false) {
        return new WP_Error('broke', __("Root folder not found ", 'useyourdrive'));
        die();
      }
    }

    /* Check if user is allowed to upload to this dir */
    if (!$cachedfolder->isInFolder($this->_rootFolder)) {
      return new WP_Error('broke', __("You are not authorized to upload files to this directory", 'useyourdrive'));
      die();
    }

    /* Upload File to server */
    require('jquery-file-upload/server/UploadHandler.php');
    $accept_file_types = '/.(' . $this->options['upload_ext'] . ')$/i';
    $max_file_size = $this->options['maxfilesize'];

    $uploadir = wp_upload_dir();

    $options = array(
        'upload_dir' => $uploadir['path'] . '/',
        'upload_url' => $uploadir['url'] . '/',
        'access_control_allow_methods' => array('POST', 'PUT'),
        'accept_file_types' => $accept_file_types,
        'inline_file_types' => '/\.____$/i',
        'orient_image' => false,
        'image_versions' => array(),
        'max_file_size' => $max_file_size,
        'print_response' => false
    );

    if ($this->options['demo'] === '1') {
      $options['accept_file_types'] = '/\.____$/i';
    }

    $error_messages = array(
        1 => __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'useyourdrive'),
        2 => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'useyourdrive'),
        3 => __('The uploaded file was only partially uploaded', 'useyourdrive'),
        4 => __('No file was uploaded', 'useyourdrive'),
        6 => __('Missing a temporary folder', 'useyourdrive'),
        7 => __('Failed to write file to disk', 'useyourdrive'),
        8 => __('A PHP extension stopped the file upload', 'useyourdrive'),
        'post_max_size' => __('The uploaded file exceeds the post_max_size directive in php.ini', 'useyourdrive'),
        'max_file_size' => __('File is too big', 'useyourdrive'),
        'min_file_size' => __('File is too small', 'useyourdrive'),
        'accept_file_types' => __('Filetype not allowed', 'useyourdrive'),
        'max_number_of_files' => __('Maximum number of files exceeded', 'useyourdrive'),
        'max_width' => __('Image exceeds maximum width', 'useyourdrive'),
        'min_width' => __('Image requires a minimum width', 'useyourdrive'),
        'max_height' => __('Image exceeds maximum height', 'useyourdrive'),
        'min_height' => __('Image requires a minimum height', 'useyourdrive')
    );

    $this->upload_handler = new UploadHandler($options, false, $error_messages);
    $response = @$this->upload_handler->post(false);

    /* Upload files to Google Drive */
    foreach ($response['files'] as &$file) {

      /* Check user permission */
      $userrole = $cachedfolder->getItem()->getUserPermission()->getRole();
      if (in_array($userrole, array('reader', 'commenter'))) {
        $file->error = __("You are not authorized to upload files to this directory", 'useyourdrive');
      }

      if (!isset($file->error)) {
        /* Write file */
        $filePath = $file->tmp_path;
        $chunkSizeBytes = 1 * 1024 * 1024;

        /* Update Mime-type if needed (for IE8 and lower?) */
        include_once 'mime-types/mime-types.php';
        $fileExtension = pathinfo($file->name, PATHINFO_EXTENSION);
        $file->type = getMimeType($fileExtension);

        try {

          /* Create new Google File */
          $googledrive_file = new Google_Service_Drive_DriveFile();
          $googledrive_file->setTitle($file->name);
          $googledrive_file->setMimeType($file->type);

          /* Add Parent to Google File */
          if ($this->_lastFolder != null) {
            $parent = new Google_Service_Drive_ParentReference();
            $parent->setId($this->_lastFolder);
            $googledrive_file->setParents(array($parent));
          }

          /* Call the API with the media upload, defer so it doesn't immediately return. */
          $this->client->setDefer(true);
          $convert = ($this->options['convert'] === '1') ? true : false;
          $request = $this->googleDriveService->files->insert($googledrive_file, array('convert' => $convert));
          $request->disableGzip();

          /* Create a media file upload to represent our upload process. */
          $media = new Google_Http_MediaFileUpload(
                  $this->client, $request, $file->type, null, true, $chunkSizeBytes
          );

          $media->setFileSize(filesize($filePath));

          /* Start partialy upload 
            Upload the various chunks. $status will be false until the process is
            complete. */
          $status = false;
          $handle = fopen($filePath, "rb");
          while (!$status && !feof($handle)) {
            set_time_limit(60);
            $chunk = fread($handle, $chunkSizeBytes);
            $uploadStatus = $media->nextChunk($chunk);
          }

          fclose($handle);
        } catch (Exception $ex) {
          $file->error = __('Not uploaded to Google Drive', 'useyourdrive') . ': ' . $ex->getMessage();
        }

        $this->client->setDefer(false);

        if (!empty($uploadStatus)) {
          /* check if uploaded file has size */
          $newentry = $this->googleDriveService->files->get($uploadStatus['id'], array("userIp" => $this->userip));

          if (($newentry->getFileSize() === 0) && (strpos($newentry->getMimeType(), 'google-apps') === false)) {
            $deletedentry = $this->googleDriveService->files->delete($newentry->getId(), array("userIp" => $this->userip));
            $file->error = __('Not succesfully uploaded to Google Drive', 'useyourdrive');
          } else {

            /* Add new file to our Cache */
            $cachedentry = $this->cache->addToCache($newentry);
            $file->completepath = $cachedentry->getPath($this->_rootFolder);
            $file->fileid = $newentry->getId();
            $file->filesize = UseyourDrive_bytesToSize1024($file->size);
            $file->link = urlencode($newentry->getAlternateLink());
          }
        }
      } else {

        if ($this->options['debug'] === '1') {
          $file->error = __('Uploading failed', 'useyourdrive') . ': ' . $file->error;
        } else {
          $file->error = __('Uploading failed', 'useyourdrive');
        }
      }
    }

    /* Send email if needed */
    if ($this->options['notificationupload'] === '1') {
      $this->sendNotificationEmail('upload', $response['files']);
    }

    /* Create response */
    $this->upload_handler->generate_response($response);
    die();
  }

  /*
   * Delete entry from Google Drive
   */

  function deleteEntry(UseyourDrive_Node $cachedentry = null, $multiple = false) {

    if ($this->options['demo'] === '1') {
      return new WP_Error('broke', __('Failed to delete entry', 'useyourdrive'));
    }

    if (($cachedentry === null)) {
      /* Check if file is cached and still valid */
      $cached = $this->cache->isCached($this->_requestedEntry);

      /* Get the file if not cached or doesn't have permissions yet */
      if ($cached === false) {
        $cachedentry = $this->getEntry($this->_requestedEntry);

        if ($cachedentry === false) {
          if ($this->options['debug'] === '1') {
            return new WP_Error('broke', __('Invalid entry', 'useyourdrive'));
          } else {
            return new WP_Error('broke', __('Failed to delete entry', 'useyourdrive'));
          }
          die();
        }
      } else {
        $cachedentry = $cached;
      }
    }


    /* Check if user is allowed to delete from this dir */
    if (!$cachedentry->isInFolder($this->_rootFolder)) {
      return new WP_Error('broke', __("You are not authorized to delete files from this directory", 'useyourdrive'));
      die();
    }

    $entry = $cachedentry->getItem();

    /* Check user permission */
    $userrole = $entry->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter', 'writer'))) {
      return new WP_Error('broke', __('You are not authorized to delete this file or folder', 'useyourdrive'));
    }

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedentry)) {
      return new WP_Error('broke', __('You are not authorized to delete this file or folder', 'useyourdrive'));
    }

    try {
      $entrypath = $cachedentry->getPath($this->_rootFolder);

      if ($this->options['deletetotrash'] === '1') {
        $deleted_entry = $this->googleDriveService->files->trash($entry->getId(), array("userIp" => $this->userip));
      } else {
        $deleted_entry = $this->googleDriveService->files->delete($entry->getId(), array("userIp" => $this->userip));
      }
    } catch (Exception $ex) {
      if ($this->options['debug'] === '1') {
        return new WP_Error('broke', $ex->getMessage());
      } else {
        return new WP_Error('broke', __('Failed to delete entry', 'useyourdrive'));
      }
    }

    /* Send email if needed */
    if (($this->options['notificationdeletion'] === '1') && ($multiple === false)) {
      $this->sendNotificationEmail('deletion', $entry, $entrypath);
    }

    $this->cache->removeFromCache($entry);

    return $deleted_entry;
  }

  public function deleteEntries() {
    $deleted_entries = array();
    $filelist_deleted = array();

    foreach ($_REQUEST['entries'] as $entry) {
      $cached = $this->cache->isCached($entry);


      if ($cached === false) {
        $cachedentry = $this->getEntry($entry);

        if ($cachedentry === false) {
          $deleted_entries[$entry] = new WP_Error('broke', __('Failed to delete entry', 'useyourdrive'));
          continue;
        }
      } else {
        $cachedentry = $cached;
      }

      $entrypath = $cachedentry->getPath($this->_rootFolder);
      $filelist_text = $entrypath . " (" . UseyourDrive_bytesToSize1024($cachedentry->getItem()->getFileSize()) . ")";
      $deleted_entries[$entry] = $this->deleteEntry($cachedentry, 'multiple');

      if ($deleted_entries[$entry] === null) {
        $filelist_deleted[] = $filelist_text;
      }
    }

    /* Send email if needed */
    if ($this->options['notificationdeletion'] === '1') {
      $this->sendNotificationEmail('deletion_multiple', $filelist_deleted);
    }

    return $deleted_entries;
  }

  /*
   * Rename entry from Google Drive
   */

  function renameEntry($new_filename = null) {

    if ($new_filename === null && $this->options['debug'] === '1') {
      return new WP_Error('broke', __('No new name set', 'useyourdrive'));
    }

    /* Get entry meta data */
    $cachedentry = $this->cache->isCached($this->_requestedEntry);

    if ($cachedentry === false) {
      $cachedentry = $this->getEntry($this->_requestedEntry);
      if ($cachedentry === false) {
        if ($this->options['debug'] === '1') {
          return new WP_Error('broke', __('Invalid entry', 'useyourdrive'));
        } else {
          return new WP_Error('broke', __('Failed to rename entry', 'useyourdrive'));
        }
        return new WP_Error('broke', __('Failed to rename entry', 'useyourdrive'));
      }
    }

    /* Check if user is allowed to delete from this dir */
    if (!$cachedentry->isInFolder($this->_rootFolder)) {
      return new WP_Error('broke', __("You are not authorized to rename files in this directory", 'useyourdrive'));
    }

    $entry = $cachedentry->getItem();

    /* Check user permission */
    $userrole = $entry->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter'))) {
      return new WP_Error('broke', __('You are not authorized to rename this file or folder', 'useyourdrive'));
    }

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedentry)) {
      return new WP_Error('broke', __('You are not authorized to rename this file or folder', 'useyourdrive'));
    }

    $title = (isset($extension) && $extension !== '') ? $new_filename . '.' . $extension : $new_filename;
    $entry->setTitle($title);

    try {
      $renamed_entry = $this->updateEntry($entry);

      if ($renamed_entry !== false && $renamed_entry !== null) {
        $this->cache->updateCache();
      }
    } catch (Exception $ex) {
      if ($this->options['debug'] === '1') {
        return new WP_Error('broke', $ex->getMessage());
      } else {
        return new WP_Error('broke', __('Failed to rename entry', 'useyourdrive'));
      }
    }

    return $renamed_entry;
  }

  /*
   * Edit descriptions entry from Google Drive
   */

  function moveEntry($target = null, $copy = false) {

    if ($this->_requestedEntry === null || $target === null) {
      return new WP_Error('broke', __('Failed to move entry', 'useyourdrive'));
    }

    /* Get entry meta data */
    $cachedentry = $this->getEntry($this->_requestedEntry);
    $cachedtarget = $this->getEntry($target);
    $cachedcurrentfolder = $this->getEntry($this->_lastFolder);

    if ($cachedentry === false || $cachedtarget === false) {
      return new WP_Error('broke', __('Failed to move entry', 'useyourdrive'));
    }

    /* Check if user is allowed to delete from this dir */
    if (!$cachedentry->isInFolder($cachedcurrentfolder->getId())) {
      return new WP_Error('broke', __("You are not authorized to move items in this directory", 'useyourdrive'));
    }

    $entry = $cachedentry->getItem();

    /* Check user permission */
    $userrole = $entry->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter'))) {
      return new WP_Error('broke', __('You are not authorized to move this file or folder', 'useyourdrive'));
    }

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedentry)) {
      return new WP_Error('broke', __('You are not authorized to move this file or folder', 'useyourdrive'));
    }

    $parents = $entry->getParents();

    /* Add new Parent */
    $alreadyparent = false;
    foreach ($parents as $parent) {
      if ($parent->getId() === $cachedtarget->getId()) {
        $alreadyparent = true; // file is alread in parent
      }
    }
    if (!$alreadyparent) {
      $newParent = new Google_Service_Drive_ParentReference();
      $newParent->setId($cachedtarget->getId());
      $parents[] = $newParent;
    }

    /* Remove old Parent */
    if ($copy === false) {
      foreach ($parents as $key => $parent) {
        if ($parent->getId() === $this->_lastFolder) {
          unset($parents[$key]);
        }
      }
    }

    $parents = array_values($parents);
    $entry->setParents($parents);

    try {
      $moved_entry = $this->updateEntry($entry);

      if ($moved_entry !== false && $moved_entry !== null) {
        /* Remove file from Cache */
        $this->cache->removeFromCache($cachedentry);
        /* Add new file to our Cache */
        $this->cache->addToCache($moved_entry);
      }
    } catch (Exception $ex) {
      if ($this->options['debug'] === '1') {
        return new WP_Error('broke', $ex->getMessage());
      } else {
        return new WP_Error('broke', __('Failed to move entry', 'useyourdrive'));
      }
    }

    return $moved_entry;
  }

  /*
   * Edit descriptions entry from Google Drive
   */

  function descriptionEntry($new_description = null) {

    if ($new_description === null && $this->options['debug'] === '1') {
      return new WP_Error('broke', __('No new description set', 'useyourdrive'));
    }

    /* Get entry meta data */
    $cachedentry = $this->cache->isCached($this->_requestedEntry);

    if ($cachedentry === false) {
      $cachedentry = $this->getEntry($this->_requestedEntry);
      if ($cachedentry === false) {
        if ($this->options['debug'] === '1') {
          return new WP_Error('broke', __('Invalid entry', 'useyourdrive'));
        } else {
          return new WP_Error('broke', __('Failed to edit entry', 'useyourdrive'));
        }
        return new WP_Error('broke', __('Failed to edit entry', 'useyourdrive'));
      }
    }

    /* Check if user is allowed to delete from this dir */
    if (!$cachedentry->isInFolder($this->_rootFolder)) {
      return new WP_Error('broke', __("You are not authorized to edit files in this directory", 'useyourdrive'));
    }

    $entry = $cachedentry->getItem();

    /* Check user permission */
    $userrole = $entry->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter'))) {
      return new WP_Error('broke', __('You are not authorized to edit this file or folder', 'useyourdrive'));
    }

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedentry)) {
      return new WP_Error('broke', __('You are not authorized to edit this file or folder', 'useyourdrive'));
    }

    $entry->setDescription($new_description);

    try {
      $edited_entry = $this->updateEntry($entry);

      if ($edited_entry !== false && $edited_entry !== null) {
        $this->cache->updateCache();
      }
    } catch (Exception $ex) {
      if ($this->options['debug'] === '1') {
        return new WP_Error('broke', $ex->getMessage());
      } else {
        return new WP_Error('broke', __('Failed to edit entry', 'useyourdrive'));
      }
    }

    return $edited_entry->getDescription();
  }

  /*
   * Update entry from Google Drive
   */

  protected function updateEntry($entry) {

    /* Check user permission */
    $userrole = $entry->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter'))) {
      return false;
    }

    $result = $this->googleDriveService->files->patch($entry->getId(), $entry, array("userIp" => $this->userip));
    return $result;
  }

  /*
   * Add directory to Google Drive
   */

  function addFolder($new_folder = null) {
    if ($this->options['demo'] === '1') {
      return new WP_Error('broke', __('Failed to add folder', 'useyourdrive'));
    }

    if ($new_folder === null && $this->options['debug'] === '1') {
      return new WP_Error('broke', __('No new foldername set', 'useyourdrive'));
    }

    /* Get entry meta data of current folder */
    $cachedentry = $this->cache->isCached($this->_lastFolder);

    if ($cachedentry === false) {
      $cachedentry = $this->getEntry($this->_lastFolder);
      if ($cachedentry === false) {
        if ($this->options['debug'] === '1') {
          return new WP_Error('broke', __('Invalid entry', 'useyourdrive'));
        } else {
          return new WP_Error('broke', __('Failed to add entry', 'useyourdrive'));
        }
        return new WP_Error('broke', __('Failed to add entry', 'useyourdrive'));
      }
    }

    /* Check if user is allowed to delete from this dir */
    if (!$cachedentry->isInFolder($this->_rootFolder)) {
      return new WP_Error('broke', __("You are not authorized to add folders in this directory", 'useyourdrive'));
    }

    $currentfolder = $cachedentry->getItem();

    /* Check user permission */
    $userrole = $currentfolder->getUserPermission()->getRole();
    if (in_array($userrole, array('reader', 'commenter'))) {
      return new WP_Error('broke', __('You are not authorized to add a folder', 'useyourdrive'));
    }

    /* skip excluded folders and files */
    if ($this->options['exclude'][0] != '*') {
      if (in_array($currentfolder->title, $this->options['exclude'])) {
        return new WP_Error('broke', __('Failed to add folder', 'useyourdrive'));
      }
    }

    /* only allow included folders and files */
    if ($this->options['include'][0] != '*') {
      if (!in_array($currentfolder->title, $this->options['include'])) {
        return new WP_Error('broke', __('Failed to add folder', 'useyourdrive'));
      }
    }

    $newfolder = new Google_Service_Drive_DriveFile();
    $newfolder->setTitle($new_folder);
    $newfolder->setMimeType('application/vnd.google-apps.folder');

    $newParent = new Google_Service_Drive_ParentReference();
    $newParent->setId($currentfolder->getId());
    $newfolder->setParents(array($newParent));

    try {
      $newentry = $this->googleDriveService->files->insert($newfolder, array("userIp" => $this->userip));

      if ($newentry !== null) {
        /* Add new file to our Cache */
        $this->cache->addToCache($newentry);
      }
    } catch (Exception $ex) {
      if ($this->options['debug'] === '1') {
        return new WP_Error('broke', $ex->getMessage());
      } else {
        return new WP_Error('broke', __('Failed to add folder', 'useyourdrive'));
      }
    }

    return $newentry;
  }

  function addUserFolder($userfoldername, $rootfolder = false, $options = false) {
    if ($options === false) {
      $options = $this->options;
    }

    if ($rootfolder === false && isset($options['root'])) {
      $rootfolder = $options['root'];
    } elseif ($rootfolder === false) {
      return false;
    }

    $parentfolder = $this->getFolder(false, $rootfolder, false, false);
    if (!empty($parentfolder)) {
      $userfolder = $this->cache->getEntryByName($userfoldername, $parentfolder['folder']);
      if ($userfolder === false) {
        /* Not found -> create folder */
        $newfolder = new Google_Service_Drive_DriveFile();
        $newfolder->setTitle($userfoldername);
        $newfolder->setMimeType('application/vnd.google-apps.folder');

        $newParent = new Google_Service_Drive_ParentReference();
        $newParent->setId($rootfolder);
        $newfolder->setParents(array($newParent));

        try {
          $newentry = $this->googleDriveService->files->insert($newfolder, array("userIp" => $this->userip));
        } catch (Exception $ex) {
          if ($this->options['debug'] === '1') {
            return new WP_Error('broke', $ex->getMessage());
          } else {
            return new WP_Error('broke', __('Failed to add user folder', 'useyourdrive'));
          }
        }

        $userfolder = $this->cache->addToCache($newentry);
      }

      /* Check if template folder is present */
      if ($options['user_template_dir'] !== '' && !$userfolder->hasChildren() && $userfolder !== false) {
        $cachedtemplatefolder = $this->getFolder(false, $options['user_template_dir'], false, false);
        if ($cachedtemplatefolder !== false && $cachedtemplatefolder['folder'] !== false && $cachedtemplatefolder['folder']->hasChildren()) {
          $this->_copyFolderRecursive($cachedtemplatefolder['folder'], $userfolder);
        }
      }
      if ($userfolder !== false) {
        return $userfolder->getItem();
      } else {
        return false;
      }
    }
  }

  function addUserFolders($userfoldernames) {
    $rootfolder = $this->options['root'];


    $this->client->setUseBatch(true);
    $batch = new Google_Http_Batch($this->client);

    /* Find folders */
    foreach ($userfoldernames as $key => $userfoldername) {

      if ($rootfolder === $this->options['base']) {
        $params = array('q' => "'root' in parents and title='" . $userfoldername . "' and mimeType='application/vnd.google-apps.folder' and trashed = false", "userIp" => $this->userip);
      } else {
        $params = array('q' => "'" . $rootfolder . "' in parents and title='" . $userfoldername . "' and mimeType='application/vnd.google-apps.folder' and trashed = false", "userIp" => $this->userip);
      }

      $batch->add($this->googleDriveService->files->listFiles($params), 'find-' . $key);
    }

    try {
      usleep(250000); // Don't fire multiple queries fast
      $batch_result = $batch->execute();
    } catch (Exception $ex) {
      return false;
    }

    /* Create folders */
    $batch = new Google_Http_Batch($this->client);
    $newfolders = false;
    foreach ($userfoldernames as $key => $userfoldername) {

      if (isset($batch_result['response-find-' . $key])) {
        $fileslist = $batch_result['response-find-' . $key];
        $files = $fileslist->getItems();

        if (count($files) === 0) {
          $newfolder = new Google_Service_Drive_DriveFile();
          $newfolder->setTitle($userfoldername);
          $newfolder->setMimeType('application/vnd.google-apps.folder');

          $newParent = new Google_Service_Drive_ParentReference();
          $newParent->setId($rootfolder);
          $newfolder->setParents(array($newParent));

          $newfolders = true;
          $batch->add($this->googleDriveService->files->insert($newfolder, array("userIp" => $this->userip)), 'create-' . $key);
        }
      } else {
        $newfolder = new Google_Service_Drive_DriveFile();
        $newfolder->setTitle($userfoldername);
        $newfolder->setMimeType('application/vnd.google-apps.folder');

        $newParent = new Google_Service_Drive_ParentReference();
        $newParent->setId($rootfolder);
        $newfolder->setParents(array($newParent));
        $newfolders = true;
        $batch->add($this->googleDriveService->files->insert($newfolder, array("userIp" => $this->userip)), 'create-' . $key);
      }
    }

    if ($newfolders) {
      $parententry = $this->cache->removeFromCache($rootfolder);
      try {
        usleep(250000); // Don't fire multiple queries fast
        $batch_result = $batch->execute();
      } catch (Exception $ex) {
        return false;
      }
    }
  }

  private function _copyFolderRecursive(UseyourDrive_Node $templatefolder, UseyourDrive_Node $newfolder) {

    if ($templatefolder !== null && $templatefolder !== false && $templatefolder->hasItem() &&
            $newfolder !== null && $newfolder !== false && $newfolder->hasItem()) {

      $template_entry = $templatefolder->getItem();
      $newfolder_entry = $newfolder->getItem();

      if ($templatefolder->hasChildren()) {
        foreach ($templatefolder->getChildren() as $cached_child) {

          $child = $cached_child->getItem();
          if ($child->getMimeType() === 'application/vnd.google-apps.folder') {
            /* Create child folder in user folder */
            $newchildfolder = new Google_Service_Drive_DriveFile();
            $newchildfolder->setTitle($child->getTitle());
            $newchildfolder->setMimeType('application/vnd.google-apps.folder');

            $newParent = new Google_Service_Drive_ParentReference();
            $newParent->setId($newfolder_entry->getId());
            $newchildfolder->setParents(array($newParent));

            try {
              $a = 1;
              $newchildentry = $this->googleDriveService->files->insert($newchildfolder, array("userIp" => $this->userip));
            } catch (Exception $ex) {
              continue;
            }

            $cachednewchildentry = $this->cache->addToCache($newchildentry);

            /* Copy contents of child folder to new create child user folder */
            $cached_child_folder = $this->getFolder(false, $child->getId(), false, false);

            if ($cached_child_folder !== false && $cached_child_folder['folder'] !== false) {
              $this->_copyFolderRecursive($cached_child_folder['folder'], $cachednewchildentry);
            }
          } else {
            /* Copy file to new folder */
            $newfile = new Google_Service_Drive_DriveFile();
            $newfile->setTitle($child->getTitle());

            $newParent = new Google_Service_Drive_ParentReference();
            $newParent->setId($newfolder_entry->getId());
            $newfile->setParents(array($newParent));

            try {
              $a = 1;
              $newchildentry = $this->googleDriveService->files->copy($child->getId(), $newfile, array("userIp" => $this->userip));
            } catch (Exception $ex) {
              continue;
            }

            $cachednewchildentry = $this->cache->addToCache($newchildentry);
          }
        }
      }
    }
  }

  function updateUserFolder($listoptions, $userfoldername, $olduserfoldername = false, $delete = false) {
    $this->options = $listoptions;

    if ($olduserfoldername === false && $delete === false) {
      $this->addUserFolder($userfoldername, $listoptions['root'], $listoptions);
      return true;
    } elseif ($delete === true) {
      $params = array('q' => "'" . $listoptions['root'] . "' in parents and title='" . $userfoldername . "' and mimeType='application/vnd.google-apps.folder' and trashed = false", "userIp" => $this->userip);
    } else {
      $params = array('q' => "'" . $listoptions['root'] . "' in parents and title='" . $olduserfoldername . "' and mimeType='application/vnd.google-apps.folder' and trashed = false", "userIp" => $this->userip);
    }

    $fileslist = $this->googleDriveService->files->listFiles($params);

    $files = $fileslist->getItems();

    if (count($files) > 0) {
      $firstentry = reset($files);

      $cachedentry = $this->cache->removeFromCache($firstentry);

      if ($delete) {
        try {
          $userfolder = $this->googleDriveService->files->delete($firstentry->getId(), array("userIp" => $this->userip));

          return true;
        } catch (Exception $ex) {
          return false;
        }
      } else {

        $firstentry->setTitle($userfoldername);

        try {
          $entry = $this->googleDriveService->files->update($firstentry->getId(), $firstentry, array("userIp" => $this->userip));
          $this->cache->addToCache($entry);
          return true;
        } catch (Exception $ex) {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  function setRedirectUri($url) {
    $this->client->setRedirectUri($url);
    return $this->client->getRedirectUri();
  }

  /*
   * Set Filelist token
   */

  public function setListToken($listtoken) {
    $this->listtoken = $listtoken;
    return $this->listtoken;
  }

  /*
   * Download file
   */

  function downloadFile() {
    /* Check if file is cached and still valid */
    $cached = $this->cache->isCached($this->_requestedEntry);

    if ($cached === false) {
      $cachedentry = $this->getEntry($this->_requestedEntry, true);
    } else {
      $cachedentry = $cached;
    }

    if ($cachedentry === false) {
      die();
    }

    $entry = $cachedentry->getItem();

    /* get the last-modified-date of this very file */
    $lastModified = strtotime($entry->getModifiedDate());
    /* get a unique hash of this file (etag) */
    $etagFile = $entry->getEtag();
    /* get the HTTP_IF_MODIFIED_SINCE header if set */
    $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
    /* get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash) */
    $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
    header("Etag: $etagFile");
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 5) . ' GMT');
    header('Cache-Control: must-revalidate');

    /* check if page has changed. If not, send 304 and exit */
    if ($cached !== false) {
      if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified || $etagHeader == $etagFile) {
        header("HTTP/1.1 304 Not Modified");
        exit;
      }
    }

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedentry)) {
      die();
    }

    $forcedownload = ($this->options['forcedownload'] === '1' || (isset($_REQUEST['dl']) && $_REQUEST['dl'] === '1')) ? true : false;
    $use_directlink = true;

    /* Send email if needed */
    if ($this->options['notificationdownload'] === '1') {
      $entrypath = $cachedentry->getPath($this->_rootFolder);
      $this->sendNotificationEmail('download', $entry, $entrypath);
    }

    /* Check file permission */
    $has_permission = false;
    $shareable = false;

    $permission_type = ($this->settings['permission_domain'] === '') ? 'anyone' : 'domain';
    $permission_value = ($this->settings['permission_domain'] === '') ? '' : $this->settings['permission_domain'];

    if (count($entry->getPermissions()) > 0) {

      if ($this->settings['manage_permissions'] === 'Yes') {
        foreach ($entry->getPermissions() as $permission) {
          if (($permission->getType() === $permission_type) && (in_array($permission->getRole(), array('reader', 'writer'))) && ($permission->getValue() === $permission_value) && ($permission->getWithLink())) {
            $has_permission = true;
            $shareable = true;
            break;
          }
        }
      } else {
        $shareable = true;
      }
    }

    /* Set new permission if needed */
    if ($has_permission === false && $this->settings['manage_permissions'] === 'Yes') {
      $newPermission = new Google_Service_Drive_Permission();
      $newPermission->setType($permission_type);
      $newPermission->setRole("reader");
      $newPermission->setValue($permission_value);
      $newPermission->setWithLink(true);

      try {
        $permission = $this->googleDriveService->permissions->insert($entry->getId(), $newPermission);
        $cachedentry = $this->getEntry($this->_requestedEntry, true);
        $entry = $cachedentry->getItem();
        $shareable = true;
      } catch (Exception $e) {
        $shareable = false;
      }
    }

    /* Filesize > 25MB, dont use Googles preview */
    if ($entry->getFileSize() >= 25000000 && ((strpos($entry->getMimeType(), 'video') === false) && (strpos($entry->getMimeType(), 'audio') === false))) {
      $directlink = null;
      $authorizedlink = true;
      $shareable = false;
    }

    if ($shareable) {

      /* Create preview link if needed */
      if (!$this->checkUserRole($this->options['download_role']) && $_REQUEST['action'] === 'useyourdrive-preview') {
        $directlink = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview?rm=minimal';
      } else {

        /* Create download link */
        /* Get Direct Link and redirect if needed */
        $directlink = $entry->getWebContentLink();

        if (isset($_REQUEST['openwithgoogle']) && ($_REQUEST['openwithgoogle'] == 1)) {

          /* As of 12/12/2014 Google doesn't allow embedded files anymore ? */
          if ((strpos($entry->getMimeType(), 'google-apps')) === false) {
            $directlink = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview';
          } else {
            $directlink = $entry->getAlternateLink();
          }

          if ((strpos($entry->getMimeType(), 'video') !== false) || (strpos($entry->getMimeType(), 'audio') !== false)) {
            $embedlink = $entry->getEmbedLink();
            if (!empty($embedlink)) {
              $directlink = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview';
            }
          }
        }

        if ($directlink === null) {
          $links = $entry->getExportLinks();
          if (isset($_REQUEST['extension']) && isset($links[urldecode($_REQUEST['extension'])])) {
            $directlink = $links[urldecode($_REQUEST['extension'])];
          } else {
            $directlink = reset($links);
          }
        }
      }
    }
    if (!isset($authorizedlink)) {
      $authorizedlink = (isset($_REQUEST['auth']) && $_REQUEST['auth'] == 1) ? true : false;
    }

    if (($shareable) && ($use_directlink) && (!empty($directlink)) && (!$authorizedlink)) {
      header('Location: ' . $directlink);
      die();
    } else {
      /* Create download link, redirect or stream */

      /* Get downloadlink */
      $downloadlink = $entry->getDownloadUrl();

      if ($authorizedlink) {
        if (!$forcedownload) {
          $downloadlink = str_replace('e=download', 'e=export', $downloadlink);
        }

        $token = json_decode($this->client->getAccessToken());
        header('Authorization: Bearer ' . $token->access_token);
        header('Location: ' . $downloadlink . "&access_token=" . $token->access_token);

        die();
      }

      if ($downloadlink !== null) {
        $request = new Google_Http_Request($downloadlink, 'GET');

        $httpRequest = $this->client->getAuth()->authenticatedRequest($request);

        if ($httpRequest->getResponseHttpCode() == 200) {

          $headers = $httpRequest->getResponseHeaders();
          if (!$forcedownload) {
            $headers['content-disposition'] = str_replace('attachment;', '', $headers['content-disposition']);
          }
          if (isset($headers['transfer-encoding'])) {
            unset($headers['transfer-encoding']);
          }

          foreach ($headers as $key => $header) {
            header("$key: " . $header);
          }

          echo $httpRequest->getResponseBody();
        } else {
          /* An error occurred */
          return null;
        }
      }
    }

    die();
  }

  /*
   * Create zipfile
   */

  public function createZip() {
    /* Check if file is cached and still valid */
    $cachedfolder = $this->getFolder();


    if ($cachedfolder === false || $cachedfolder['folder'] === false) {
      return new WP_Error('broke', __("Requested directory isn't allowed", 'useyourdrive'));
    }

    $folder = $cachedfolder['folder']->getItem();

    /* Check if entry is allowed */
    if (!$this->_isEntryAuthorized($cachedfolder['folder'])) {
      return new WP_Error('broke', __("Requested directory isn't allowed", 'useyourdrive'));
    }

    /* Create upload dir if needed */
    $zip_filename = '_zip_' . basename($folder->getTitle()) . '_' . uniqid() . '.zip';

    $json_options = 0;
    if (defined('JSON_PRETTY_PRINT')) {
      $json_options |= JSON_PRETTY_PRINT;  // Supported in PHP 5.4+
    }

    if (isset($_REQUEST['files'])) {
      $dirlisting = array('folders' => array(), 'files' => array(), 'bytes' => 0, 'bytes_total' => 0);

      foreach ($_REQUEST['files'] as $fileid) {
        $cached_file = $this->getEntry($fileid);
        $data = $this->_getRecursiveFiles($cached_file, '', true);
        $dirlisting['files'] = array_merge($dirlisting['files'], $data['files']);
        $dirlisting['folders'] = array_merge($dirlisting['folders'], $data['folders']);
        $dirlisting['bytes_total'] += $data['bytes_total'];
      }
    } else {
      $dirlisting = $this->_getRecursiveFiles($cachedfolder['folder']);
    }

    if (count($dirlisting['folders']) > 0 || count($dirlisting['files']) > 0) {

      /* Create zip file */
      if (!function_exists('PHPZip\autoload')) {
        try {
          require_once "PHPZip/autoload.php";
        } catch (Exception $ex) {
          return new WP_Error('broke', __('Something went wrong... See settings page', 'useyourdrive'));
        }
      }
      $zip = new PHPZip\Zip\File\Zip($zip_filename);

      /* Add folders */
      if (count($dirlisting['folders']) > 0) {

        foreach ($dirlisting['folders'] as $key => $folder) {
          $zip->addDirectory($folder);
          unset($dirlisting['folders'][$key]);
        }
      }

      /* Add files */
      if (count($dirlisting['files']) > 0) {

        foreach ($dirlisting['files'] as $key => $file) {
          usleep(125000);
          set_time_limit(60);

          /* get file */
          $request = new Google_Http_Request($file['url'], 'GET');

          try {
            $httpRequest = $this->client->getAuth()->authenticatedRequest($request);
          } catch (Exception $ex) {
            continue;
          }

          if ($httpRequest->getResponseHttpCode() == 200) {
            ob_flush();
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $httpRequest->getResponseBody());
            rewind($stream);

            try {
              $zip->addLargeFile($stream, $file['path']);
            } catch (Exception $ex) {
              
            }

            fclose($stream);
            $dirlisting['bytes'] += $file['bytes'];
            unset($dirlisting['files'][$key]);
          }
        }
      }

      /* Close zip */
      $result = $zip->sendZip($zip_filename);

      die();
    } else {
      die('No files or folders selected');
    }
  }

  private function _getRecursiveFiles(UseyourDrive_Node $cached_entry, $currentpath = '', $selection = false, &$dirlisting = array('folders' => array(), 'files' => array(), 'bytes' => 0, 'bytes_total' => 0)) {
//Get entry meta data
    if ($cached_entry !== null && $cached_entry !== false && $cached_entry->hasItem()) {

      $entry = $cached_entry->getItem();
//First add Current Folder/File
      if ($selection) {
        $continue = true;
//Only add allowed files to array
        if (($entry->getMimeType() !== 'application/vnd.google-apps.folder') && (isset($entry->fileExtension) && !in_array(strtolower($entry->fileExtension), $this->options['ext'])) && $this->options['ext'][0] != '*') {
          $continue = false;
        }

        /* Check if entry is allowed */
        if (!$this->_isEntryAuthorized($cached_entry)) {
          $continue = false;
        }

        if ($continue) {
          $location = $entry->getTitle();

          if ($entry->getMimeType() === 'application/vnd.google-apps.folder') {
            $dirlisting['folders'][] = $location;
            $currentpath = $location;
          } else {

//Get download Link
            $downloadlink = $entry->getDownloadUrl();

            if ($downloadlink === null) {
              $links = $entry->getExportLinks();
              $downloadlink = reset($links);
              $extensionpos = (strripos($downloadlink, 'exportFormat=') + strlen('exportFormat='));
              $extension = substr($downloadlink, $extensionpos);
              $location .= '.' . $extension;
            }

            $dirlisting['files'][] = array('ID' => $entry->getId(), 'path' => $location, 'url' => $downloadlink, 'bytes' => $entry->getFileSize());
            $dirlisting['bytes_total'] += $entry->getFileSize();
          }
        }
      }

      $cached_folder = false;
//If Folder add all children
      if ($entry->getMimeType() === 'application/vnd.google-apps.folder') {

        /* @var UseyourDrive_Node */
        $cached_folder = $this->getFolder(false, $entry->getId());
      }

      if ($cached_folder !== false && $cached_folder['folder'] !== false && $cached_folder['folder']->hasChildren()) {

        foreach ($cached_folder['folder']->getChildren() as $cached_child) {

          $child = $cached_child->getItem();

//Only add allowed files to array
          if (($child->getMimeType() !== 'application/vnd.google-apps.folder') && (isset($child->fileExtension) && !in_array(strtolower($child->fileExtension), $this->options['ext'])) && $this->options['ext'][0] != '*') {
            continue;
          }

          /* Check if entry is allowed */
          if (!$this->_isEntryAuthorized($cached_child)) {
            $continue = false;
          }

          $location = ($currentpath === '') ? $child->getTitle() : $currentpath . '/' . $child->getTitle();

          if ($child->getMimeType() === 'application/vnd.google-apps.folder') {
            $dirlisting['folders'][] = $location;
            $this->_getRecursiveFiles($cached_child, $location, false, $dirlisting);
          } else {
//Get download Link
            $downloadlink = $child->getDownloadUrl();

            if ($downloadlink === null) {
              $links = $child->getExportLinks();
              $downloadlink = reset($links);
              $extensionpos = (strripos($downloadlink, 'exportFormat=') + strlen('exportFormat='));
              $extension = substr($downloadlink, $extensionpos);
              $location .= '.' . $extension;
            }

            $dirlisting['files'][] = array('ID' => $child->getId(), 'path' => $location, 'url' => $downloadlink, 'bytes' => $child->getFileSize());
            $dirlisting['bytes_total'] += $child->getFileSize();
          }
        }
      }
    }

    return $dirlisting;
  }

  public function createLink(UseyourDrive_Node $cachedentry = null, $shorten = true) {
    $link = false;
    $error = false;

    if (($cachedentry === null)) {
      /* Check if file is cached and still valid */
      $cached = $this->cache->isCached($this->_requestedEntry);

      /* Get the file if not cached */
      if ($cached === false) {
        $cachedentry = $this->getEntry($this->_requestedEntry);
      } else {
        $cachedentry = $cached;
      }
    }

    if ($cachedentry !== null && $cachedentry !== false) {

      $entry = $cachedentry->getItem();


      /* Check file permission */
      $has_permission = false;
      $permissions = $entry->getPermissions();
      $permission_type = ($this->settings['permission_domain'] === '') ? 'anyone' : 'domain';
      $permission_value = ($this->settings['permission_domain'] === '') ? '' : $this->settings['permission_domain'];

      if (count($entry->getPermissions()) > 0) {
        if ($this->settings['manage_permissions'] === 'Yes') {
          foreach ($entry->getPermissions() as $permission) {
            if (($permission->getType() === $permission_type) && (in_array($permission->getRole(), array('reader', 'writer'))) && ($permission->getValue() === $permission_value) && ($permission->getWithLink())) {
              $has_permission = true;
              $shareable = true;
              break;
            }
          }
        } else {
          $shareable = true;
        }
      }

      /* Set new permission if needed */
      if ($has_permission === false && $this->settings['manage_permissions'] === 'Yes') {
        $newPermission = new Google_Service_Drive_Permission();
        $newPermission->setType($permission_type);
        $newPermission->setRole("reader");
        $newPermission->setValue($permission_value);
        $newPermission->setWithLink(true);

        try {
          $permission = $this->googleDriveService->permissions->insert($entry->getId(), $newPermission);
          $cachedentry = $this->getEntry($this->_requestedEntry, true);
          $entry = $cachedentry->getItem();
          $shareable = true;
        } catch (Exception $e) {
          $shareable = false;
        }
      }


      $linkurl = $entry->getWebContentLink();
      if ($shareable) {
        $linkurl = $entry->getAlternateLink();
        if ((strpos($entry->getMimeType(), 'video') !== false) || (strpos($entry->getMimeType(), 'audio') !== false)) {
          $embedlink = $entry->getEmbedLink();
          if (!empty($embedlink)) {
            $linkurl = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview';
          }
        }
      }

      if (!empty($linkurl)) {
        if ($shorten) {
          $url = new Google_Service_Urlshortener_Url();
          $url->longUrl = $linkurl;
          $url = $this->googleUrlshortenerService->url->insert($url, array("userIp" => $this->userip));
          $link = $url->getId();
        } else {
          $link = $linkurl;
        }
      } else {
        $error = __("Can't create link", 'useyourdrive');
      }
    }

    $embedlink = $entry->getEmbedLink();
    if (empty($embedlink)) {
      $embedlink = 'https://docs.google.com/viewer?srcid=' . $entry->getId() . '&pid=explorer&embedded=true';
      /* As of 12 November 2014, the Google Doc viewer doesn't display PDF files anymore */
      if (strpos($entry->getMimeType(), 'application/pdf') !== false) {
        $embedlink = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview';
        /* Powerpoints can't be showed embedded */
      } elseif (strpos($entry->getMimeType(), 'google-apps.presentation') !== false) {
        $embedlink = 'https://docs.google.com/presentation/d/' . $entry->getId() . '/preview';
      }
    } else {
      if (strpos($entry->getMimeType(), 'application/vnd.google-apps') === false) {
        $embedlink = 'https://docs.google.com/file/d/' . $entry->getId() . '/preview';
        /* Powerpoints can't be showed embedded */
      } elseif (strpos($entry->getMimeType(), 'google-apps.presentation') !== false) {
        
      } else {
        $embedlink = $entry->getAlternateLink();
        $embedlink = str_replace('http://', 'https://', $embedlink);
      }
    }
    $resultdata = array(
        'id' => $entry->getId(),
        'name' => $entry->getTitle(),
        'link' => $link,
        'embeddedlink' => $embedlink,
        'size' => UseyourDrive_bytesToSize1024($entry->getFileSize()),
        'error' => $error
    );

    return $resultdata;
  }

  public function createLinks($shorten = true) {
    $links = array('links' => array());

    foreach ($_REQUEST['entries'] as $entry) {

      $cached = $this->cache->isCached($entry);

      /* Get the file if not cached or doesn't have permissions yet */
      if ($cached === false) {
        $cachedentry = $this->getEntry($entry);
      } else {
        $cachedentry = $cached;
      }

      $links['links'][] = $this->createLink($cachedentry, $shorten);
    }

    return $links;
  }

  public function getChanges($params) {
    try {
      $changes = $this->googleDriveService->changes->listChanges($params);
    } catch (Exception $ex) {
      return false;
    }

    return $changes;
  }

  /*
   * Check if $entry is allowed
   */

  public function _isEntryAuthorized(UseyourDrive_Node $cachedentry) {
    $entry = $cachedentry->getItem();

    /* Skip entry if its a file, and we dont want to show files */
    if (($entry->getMimeType() !== 'application/vnd.google-apps.folder') && ($this->options['show_files'] === '0')) {
      return false;
    }
    /* Skip entry if its a folder, and we dont want to show folders */
    if (($entry->getMimeType() === 'application/vnd.google-apps.folder') && ($this->options['show_folders'] === '0')) {
      return false;
    }

    /* Only add allowed files to array */
    $extension = (isset($entry->fileExtension)) ? $entry->getFileExtension() : '';
    if (($entry->getMimeType() !== 'application/vnd.google-apps.folder') && (!in_array(strtolower($extension), $this->options['ext'])) && $this->options['ext'][0] != '*') {
      return false;
    }

    /* skip excluded folders and files */
    if ($this->options['exclude'][0] != '*') {
      if (in_array($entry->getTitle(), $this->options['exclude'])) {
        return false;
      }
    }

    /* only allow included folders and files */
    if ($this->options['include'][0] != '*') {
      if (!in_array($entry->getTitle(), $this->options['include'])) {
        return false;
      }
    }

    /* Is file in the selected root Folder? */
    if (!$cachedentry->isInFolder($this->_rootFolder)) {
      return false;
    }
    return true;
  }

}
