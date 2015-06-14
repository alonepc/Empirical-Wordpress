<div id="jp_container_<?php echo $this->listtoken; ?>" class="jp-video" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>;">
  <!--container in which our video will be played-->
  <div id="jquery_jplayer_<?php echo $this->listtoken; ?>" class="jp-jplayer"></div>

  <!--main containers for our controls-->
  <div class="jp-gui">
    <div class="jp-interface">
      <div class="jp-song-title">
        <div class="jp-playlist-item-song-title"><?php _e('Wait...', 'useyourdrive'); ?></div>
        <div class="jp-playlist-item-song-artist"><?php _e('Busy loading playlist', 'useyourdrive'); ?></div>
      </div>

      <div class="jp-controls-holder">
        <div class="jp-progress">
          <div class="jp-current-time"></div>
          <div class="jp-duration"></div>
          <div class="jp-progress-bar">
            <div class="jp-seek-bar">
              <div class="jp-play-bar"></div>
            </div>
          </div>
        </div>
        <ul class="jp-controls clearfix">
          <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute"><i class="mticon-volume-high"></i></a></li>
          <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute"><i class="mticon-volume-mute2"></i></a></li>
          <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen"></a></li>
          <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen"></a></li>
          <li><a href="javascript:;" class="jp-previous disabled" tabindex="1"><i class="mticon-backward"></i></a></li>
          <li><a href="javascript:;" class="jp-play" tabindex="1"><i class="mticon-play"></i></a></li>
          <li><a href="javascript:;" class="jp-pause" tabindex="1"><i class="mticon-pause"></i></a></li>
          <li><a href="javascript:;" class="jp-next" tabindex="1"><i class="mticon-forward"></i></a></li>
          <li><a href="javascript:;" class="jp-stop" tabindex="1"></a></li>
          <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume"></a></li>
          <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle"></a></li>
          <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off"></a></li>
          <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat"><i class="mticon-loop"></i></a></li>
          <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off"><i class="mticon-loop"></i></a></li>
          <li><a href="javascript:;" class="jp-playlist-toggle" tabindex="1" title="Toggle PlayList"><i class="mticon-list-ul"></i></a></li>
        </ul>
        <div class="jp-volume-bar">
          <div class="jp-volume-bar-value"></div>
        </div>
      </div><!--end jp-controls-holder-->
    </div><!--end jp-interface-->
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