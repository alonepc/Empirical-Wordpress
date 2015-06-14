<div id="jp_container_<?php echo $this->listtoken; ?>" class="jp-video" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>;">
  <!--container in which our video will be played-->
  <div id="jquery_jplayer_<?php echo $this->listtoken; ?>" class="jp-jplayer"></div>

  <div class="playerScreen"><a tabindex="1" href="#" class="jp-video-play noload" style="display: block;"></a></div>

  <!--main containers for our controls-->
  <div class="jp-gui">
    <div class="gui-container">

      <div class="jp-progress">
        <div class="jp-seek-bar">
          <div class="jp-play-bar"></div>
        </div>
      </div>

      <a tabindex="1" href="#" class="jp-play left"></a>
      <a tabindex="1" href="#" class="jp-pause left"></a>

      <div class="seperator left"></div>
      <div class="volumecontrol left">
        <div class="jp-volume-bar">
          <div class="currentVolume"><div class="jp-volume-bar-value"></div></div>
        </div>
      </div>
      <div class="seperator left"></div>

      <div class="jp-timer">
        <div class="jp-current-time">00:00</div>
        <div class="seperate">/</div>
        <div class="jp-duration">00:00</div>
      </div>

      <div class="seperator left"></div>

      <a href="#" tabindex="1" class="jp-full-screen right"></a>
      <a href="#" tabindex="1" class="jp-restore-screen right"></a>

    </div><!--end jp-gui-->
  </div>

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