<?php

class UseyourDrive_Cache {

  /**
   *  @var UseyourDrive
   */
  public $processor;
  public $_updated = false;
  protected $_last_cache_id = '';
  protected $_last_update = '';
  protected $_locked = false;
  protected $_checked_for_updates = false;

  /* How often do we need to poll for changes? (half hour) */
  protected $_max_change_age = 1800;

  /**
   * The Cache as Tree Class
   *  @var UseyourDrive_Tree
   */
  protected $_cache;

  public function __construct(UseyourDrive $processor) {
    $this->processor = $processor;

    $this->loadCache();
    $this->lockCache();
    /* Remove any results that hasn't found a parent */
    $this->_cache->removeInvalidNodes();

    add_action('shutdown', array($this, 'unlockCache'));
  }

  public function loadCache() {
    $cache_from_db = get_option('use_your_drive_cache', array('last_update' => null, 'last_cache_id' => '', 'cache' => '', 'locked' => false));

    if ($cache_from_db['cache'] !== '') {
      $this->_cache = unserialize($cache_from_db['cache']);
      if ($this->_cache === false || $this->_cache === '') {
        $this->_cache = new UseyourDrive_Tree;
      }
    } else {
      $this->_cache = new UseyourDrive_Tree;
    }

    $this->_locked = $cache_from_db['locked'];
    $this->_last_cache_id = $cache_from_db['last_cache_id'];
    $this->_last_update = $cache_from_db['last_update'];
  }

  public function lockCache() {
    if ($this->isLocked()) {
      // some other process is writing to this file too, wait until it's done to prevent hiccups
      $this->waitForLock();
    }

    $this->_locked = time();
    $this->updateCache();
  }

  public function unlockCache() {
    $this->_locked = false;
    $this->updateCache();
  }

  public function isLocked() {
    if ($this->_locked !== false) {
      return true;
    }
    return false;
  }

  private function waitForLock() {
    if ($this->_locked < (time() + 5)) {
      // 5 seconds passed, assume the owning process died off and remove it
      $this->unlockCache();
    }

    // 5 x 1000 = 5 seconds
    $tries = 5;
    $cnt = 0;
    do {
      // 1000 ms is a long time to sleep, but it does stop the server from burning all resources on polling locks..
      usleep(1000000);
      $this->loadCache();
      $cnt++;
    } while ($cnt <= $tries && $this->isLocked());
    if ($this->isLocked()) {
      // 5 seconds passed, assume the owning process died off and remove it
      $this->unlockCache();
    }
  }

  public function setLastCacheId($id) {
    $this->_last_cache_id = $id;
    $this->_updated = true;
    return $this->_last_cache_id;
  }

  public function getLastCacheId() {
    return $this->_last_cache_id;
  }

  public function setLastUpdate() {
    $this->_last_update = time();
    $this->_updated = true;
    return $this->_last_update;
  }

  public function getLastUpdate() {
    return $this->_last_update;
  }

  public function setRoot($entry) {
    $this->_cache->setRoot($entry);
    $this->updateCache();
    return $this->_cache->getRoot();
  }

  public function getRoot() {
    $root = $this->_cache->getRoot();
    if ($root->getId() === '***Root***') {
      return false;
    }
    if ($root->hasItem() === false) {
      return false;
    }
    return $root;
  }

  public function removeFromCache($entry) {
    try {
      $node = $this->_cache->searchNodeId($entry->getId());
      $this->_cache->removeNode($node);
    } catch (Exception $ex) {
      return false;
    }
    return true;
  }

  public function addToCache($entry) {
    $newentry = $this->_cache->searchNodeId($entry->getId());
    if ($newentry === false || !$newentry->hasItem()) {
      $this->_updated = true;

      $newentry = $this->_cache->createNode($entry->getId());
      $newentry->setItem($entry);
    }


    /* Set Check if entry isn't a folder */
    if ($entry->getMimeType() !== 'application/vnd.google-apps.folder') {
      $newentry->setChecked(true);
    } else {
      $newentry->setChecked(false);
    }

    /* If entry hasn't any parents, add it to root */
    if (!$newentry->hasParents()) {
      $this->getRoot()->addChild($newentry);
      return $newentry;
    }

    /* Parent doesn't exists yet in our cache
     * We need to get this parents */
    $getparents = array();
    foreach ($entry->getParents() as $parent) {
      $parent_in_tree = $this->isCached($parent->getId(), false, false);
      if ($parent_in_tree === false) {
        $getparents[] = $parent->getId();
      }
    }

    if (count($getparents) > 0) {
      $parents = $this->processor->getMultipleEntries($getparents);
      foreach ($parents as $parent) {
        $this->addToCache($parent);
      }
    }

    /* Add entry to all parents */
    foreach ($entry->getParents() as $parent) {
      $parent_in_tree = $this->_cache->searchNodeId($parent->getId());
      /* Parent does already exists in our cache */
      if ($parent_in_tree !== false) {
        $newentry->setParent($parent_in_tree);
      }
    }

    return $newentry;
  }

  public function getEntryById($id, $parent = null) {
    $entry_in_tree = $this->_cache->searchNodeId($id, $parent);
    return $entry_in_tree;
  }

  public function getEntryByName($name, $parent = null) {
    $entry_in_tree = $this->_cache->searchNodeTitle($name, $parent);
    return $entry_in_tree;
  }

  public function isCached($id, $title = false, $hardrefresh = false) {

    if ($title !== false) {
      $entry_in_tree = $this->_cache->searchNodeTitle($title);
    } else {
      $entry_in_tree = $this->_cache->searchNodeId($id);
    }


    if ($entry_in_tree !== false) {

      if ($hardrefresh) {
        $this->_cache->removeNode($entry_in_tree);
        return false;
      }

      if ($entry_in_tree->isExpired() && !$entry_in_tree->getRoot()) {
        $entry_in_tree->setItem(null);
        return false;
      }

      /* Check if the children of the cached item are alread cached */
      if ($entry_in_tree->getChecked()) {
        return $entry_in_tree;
      }
    }

    return false;
  }

  public function refreshCache() {
    /* Check if we need to check for updates */
    $currenttime = time();
    if (($this->_last_update + $this->_max_change_age) > $currenttime) {
      return;
    }

    $params = array(
        "userIp" => $this->processor->userip,
        "maxResults" => 500,
        "includeDeleted" => true,
        "includeSubscribed" => true);

    if ($this->getLastCacheId() === '') {
      $params['maxResults'] = 1;
    } else {
      $params['startChangeId'] = (string) ((int) $this->getLastCacheId() + 1);
    }

    $changes = $this->processor->getChanges($params);

    if ($changes !== false) {
      if ($this->getLastCacheId() === '') {
        $this->setLastCacheId($changes->getLargestChangeId());
        $this->setLastUpdate();
      } else {
        $this->processChanges($changes);

        $pageToken = $changes->getNextPageToken();
        if ($pageToken) {
          $this->refreshCache();
        }

        if (count($changes->getItems()) === 0) {
          $this->setLastCacheId($changes->getLargestChangeId());
          $this->setLastUpdate();
        }
      }
    }
  }

  public function resetCache() {
    $this->_cache = new UseyourDrive_Tree;
    $this->setLastCacheId('');
    $this->setLastUpdate();
    $this->updateCache();
  }

  public function processChanges(Google_Service_Drive_ChangeList $changes) {

    /* @var $change Google_Service_Drive_Change */
    foreach ($changes->getItems() as $change) {
      $entryId = $change->getFileId();

      $entry_in_tree = $this->_cache->searchNodeId($entryId);

      if ($change->getDeleted() === true) {
        /* Delete file from cache if is deleted */
        if (($entry_in_tree !== false)) {
          $this->_cache->removeNode($entry_in_tree);
        }
      } else if ($entry_in_tree !== false) {
        /* Update File info */
        $entry_in_tree->setItem(null);
        $this->addToCache($change->getFile());
      } else {
        /* Check if parent is known */
        foreach ($change->getFile()->getParents() as $parent) {
          $parent_in_tree = $this->_cache->searchNodeId($entryId);

          if ($parent_in_tree !== false) {
            /* Add new file to Cache */
            $this->addToCache($change->getFile());
            break;
          }
        }
      }
      $this->setLastUpdate();
      $this->setLastCacheId($change->getId());
    }
  }

  public function updateCache() {
    @update_option('use_your_drive_cache', array(
                'last_update' => $this->_last_update,
                'last_cache_id' => $this->_last_cache_id,
                'locked' => $this->_locked,
                'cache' => serialize($this->_cache)));
  }

  public function __destruct() {
    //if ($this->_updated === true) {

    /* Save cache */
    $this->unlockCache();
    //}
  }

}

class UseyourDrive_Node {

  public $id = null;
  public $parents = array();
  public $children = array();
  public $item = null;
  public $root = false;
  public $parentfound = false;
  public $checked = false;
  public $expires;

  /* Max age of a entry: needed for download/thumbnails urls (1 hour?) */
  protected $_max_entry_age = 3600; //

  function __construct($params = null) {
    foreach ($params as $key => $val)
      $this->$key = $val;
    if ($this->hasParents()) {
      foreach ($this->getParents() as $parent) {
        $parent->addChild($this);
      }
    }

    $this->expires = time() + $this->_max_entry_age;
  }

  public function addChild(UseyourDrive_Node $node) {
    $this->children[$node->getId()] = $node;
    return $this;
  }

  public function removeChild(UseyourDrive_Node $node) {
    unset($this->children[$node->getId()]);
    return $this;
  }

  public function removeChilds() {
    foreach ($this->getChildren() as $child) {
      $this->removeChild($child);
    }
    return $this;
  }

  public function hasItem() {
    return ($this->getItem() !== null);
  }

  /* @return Google_Service_Drive_DriveFile */

  public function getItem() {
    return $this->item;
  }

  public function setItem($entry) {
    $this->item = $entry;
    if ($entry !== null) {
      $this->setExpired();
    }
    return $this;
  }

  public function getPath($toParentId) {
    if ($toParentId === $this->getId()) {
      return '/' . $this->getItem()->getTitle();
    }

    if ($this->hasParents()) {
      foreach ($this->getParents() as $parent) {
        $path = $parent->getPath($toParentId);
        if ($path !== false) {
          return $path . '/' . $this->getItem()->getTitle();
        }
      }
    }

    return false;
  }

  public function getExpired() {
    return $this->expires;
  }

  public function setExpired() {
    $this->expires = time() + $this->_max_entry_age;
    return $this;
  }

  public function isExpired() {
    /* Check if the entry needs to be refreshed */
    if ($this->expires < time()) {
      return true;
    }

    return false;
  }

  public function hasParents() {
    return (count($this->parents) > 0);
  }

  public function getParents() {
    return $this->parents;
  }

  public function setParent(UseyourDrive_Node $pnode) {

    if ($this->getParentFound() === false) {
      $this->removeParents();
      $this->parentfound = true;
    }

    $this->parents[$pnode->getId()] = $pnode;
    $this->parents[$pnode->getId()]->addChild($this);

    return $this;
  }

  public function removeParents() {
    foreach ($this->getParents() as $parent) {
      $this->removeParent($parent);
    }
    return $this;
  }

  public function removeParent(UseyourDrive_Node $pnode) {
    if ($this->hasParents() && isset($this->parents[$pnode->getId()])) {
      $this->parents[$pnode->getId()]->removeChild($this);
      unset($this->parents[$pnode->getId()]);
    }
    return $this;
  }

  public function hasChildren() {
    return (count($this->children) > 0);
  }

  public function getChildren() {
    return $this->children;
  }

  public function isInFolder($in_folder) {

    /* Is node just the folder? */
    if ($this->getId() === $in_folder) {
      return true;
    }

    /* Has the node Parents? */
    if ($this->hasParents() === false) {
      return false;
    }

    foreach ($this->getParents() as $parent) {
      /* First check if one of the parents is the root folder */
      if ($parent->isInFolder($in_folder) === true) {
        return true;
      }
    }

    return false;
  }

  public function getId() {
    return $this->id;
  }

  public function setRoot($v) {
    $this->root = $v;
    return $this;
  }

  public function getRoot() {
    return $this->root;
  }

  public function setParentFound($v) {
    $this->parentfound = $v;
    return $this;
  }

  public function getParentFound() {
    return $this->parentfound;
  }

  public function setChecked($v) {
    $this->checked = $v;
    return $this;
  }

  public function getChecked() {
    return $this->checked;
  }

}

class UseyourDrive_Tree {

  public $root = null;

  function __construct() {
    $this->root = new UseyourDrive_Node(array('id' => '***Root***'));
  }

  public function setRoot($entry) {
    $this->root = new UseyourDrive_Node(array('id' => $entry->getId(), 'parentfound' => true));
    $this->root->setItem($entry);
    $this->root->setRoot(true);
    return $this;
  }

  public function getRoot() {
    return $this->root;
  }

  public function createNode($id, $pnode = false) {
    if ($pnode === false) {
      $pnode = $this->root;
    }
    $child = new UseyourDrive_Node(array(
        'parents' => array($pnode->getId() => $pnode),
        'parentfound' => false,
        'id' => $id));

    return $child;
  }

  /*
   * @return UseyourDrive_Node
   */

  public function searchNodeId($search_id, UseyourDrive_Node $in_node = null) {
    if ($in_node === null) {
      $in_node = $this->root;
    }

    /* Is it the node itself? */
    if ($in_node->getId() === $search_id) {
      return $in_node;
    }

    /* Is Id in Children */
    if ($in_node->hasChildren()) {
      /* First search all Children for id */
      foreach ($in_node->getChildren() as $child) {
        if ($child->getId() === $search_id) {
          return $child;
        }
      }

      /* Search in Childrens Children */
      foreach ($in_node->getChildren() as $child) {
        $result = $this->searchNodeId($search_id, $child);

        if ($result !== false) {
          return $result;
        }
      }
    }

    /* Nothing found */
    return false;
  }

  /*
   * @return UseyourDrive_Node
   */

  public function searchNodeTitle($search_title, UseyourDrive_Node $in_node = null) {
    if ($in_node === null) {
      $in_node = $this->root;
    }


    /* Is it the node itself? */
    if (($in_node->getItem() !== null) && ($in_node->getItem()->getTitle() === $search_title)) {
      return $in_node;
    }

    /* Is Id in Children */
    if ($in_node->hasChildren()) {
      /* First search all Children for id */
      foreach ($in_node->getChildren() as $child) {
        if (($child->getItem() !== null) && ($child->getItem()->getTitle() === $search_title)) {
          return $child;
        }
      }

      /* Search in Childrens Children */
      foreach ($in_node->getChildren() as $child) {
        $result = $this->searchNodeTitle($search_title, $child);

        if ($result !== false) {
          return $result;
        }
      }
    }

    /* Nothing found */
    return false;
  }

  public function removeNode($node) {

    if (!is_a($node, 'UseyourDrive_Node')) {
      return;
    }

    if ($node->getRoot() === true) {
      $node->removeChilds();
      $node->setItem(null);
      $node->setChecked(false);
    } else {
      $node->removeParents();
      unset($node);
    }
  }

  public function removeInvalidNodes() {
    /* Remove nodes without a parent */
    foreach ($this->getRoot()->getChildren() as $child) {
      if ($child->getParentFound() === false) { {
          $this->removeNode($child);
        }
      }
    }
  }

}
