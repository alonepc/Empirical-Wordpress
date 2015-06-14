<div id="jp_container_<?php echo $this->listtoken; ?>" class="jp-video" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>;">
  <!--container in which our video will be played-->
  <div id="jquery_jplayer_<?php echo $this->listtoken; ?>" class="jp-jplayer"></div>

  <div class="playerScreen"><a tabindex="1" href="#" class="jp-video-play noload" style="display: block;"></a></div>

  <!--main containers for our controls-->
  <div class="jp-gui">
    <div class="leftblock">
      <a tabindex="1" href="#" class="jp-play"></a>
      <a tabindex="1" href="#" class="jp-pause"></a>
    </div>

    <div class="jp-progress">
      <span class="jp-title"><?php _e('Wait...', 'useyourdrive'); ?></span>
      <div class="jp-progress-bar">
        <div class="jp-seek-bar">
          <div class="jp-play-bar"></div>
        </div>
      </div>
      <div class="jp-current-time"></div>
      <div class="jp-duration"></div>
    </div>

    <div class="rightblock">
      <div class="volumeBar">
        <div class="currentVolume"><div class="curvol"></div></div>
      </div>
      <div class="volumeText">Volume: 100</div>
      <a href="#" tabindex="1" class="jp-full-screen"></a>
      <a href="#" tabindex="1" class="jp-restore-screen"></a>
    </div>
  </div><!--end jp-gui-->

  <div class="jp-playlist <?php echo ($this->options['hideplaylist'] === '1') ? 'hideonstart' : ''; ?>" style="display:none;">
    <ul>
      <!-- The method Playlist.displayPlaylist() uses this unordered list -->
      <li></li>
    </ul>
  </div>

  <!--unsupported message-->
  <div class="jp-no-solution">
    <strong><?php _e('Update Required', 'useyourdrive'); ?></strong><br/>
    <?php _e('To play the media you will need to either update your browser to a recent version or update your', 'useyourdrive'); ?> <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
  </div>
</div>