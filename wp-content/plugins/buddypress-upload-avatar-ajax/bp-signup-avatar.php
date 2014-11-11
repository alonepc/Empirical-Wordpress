<?php

/**
 * Plugin Name: Buddypress Upload Avatar Ajax
 * Plugin URI: http://tutviet.net
 * Version: 1.0.1
 * Author: Huu Ha
 * Author URI: http://tutviet.net
 * License: GPL
 * 
 * Description: Allow you add upload avatar function with ajax in register page
 */
function plugin_scripts() {
    $plugin_url = plugins_url( '', __FILE__ );
    wp_enqueue_style( 'jcrop' );
    wp_enqueue_script( 'plupload-handlers' );
    wp_enqueue_script( 'tutviet-handle-upload', $plugin_url . '/js/handle-upload.js', array('jquery', 'plupload-handlers', 'jcrop') );
  
    wp_localize_script( 'tutviet-handle-upload', 'tutviet_custom_js', array(
        'confirmMsg' => __( 'Are you sure?', 'tutviet' ),
        'nonce' => wp_create_nonce( 'tv_nonce' ),
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'plupload' => array(
            'url' => admin_url( 'admin-ajax.php' ) . '?nonce=' . wp_create_nonce( 'tutviet_image' ),
            'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
            'filters' => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
            'multipart' => true,
            'urlstream_upload' => true,
        )
    ) );
}
add_action( 'wp_enqueue_scripts', 'plugin_scripts' );

add_action( 'wp_ajax_tuviet_action_file_upload', 'tv_upload_file' );
add_action( 'wp_ajax_nopriv_tuviet_action_file_upload','tv_upload_file' );

add_action( 'wp_ajax_tutviet_action_handle_crop', 'tv_handle_avatar_upload_crop' );
add_action( 'wp_ajax_nopriv_tutviet_action_handle_crop','tv_handle_avatar_upload_crop' );

add_action( 'wp_ajax_tutviet_action_file_del', 'tv_delete_file' );
add_action( 'wp_ajax_nopriv_tutviet_action_file_del', 'tv_delete_file' );


function tv_upload_file(){
    global $bp, $wpdb;

    if( ! isset( $bp->signup->step ) )
        $bp->signup = new stdClass ();
   
    if( !isset( $bp->avatar_admin ) )
        $bp->avatar_admin = new stdClass ();
    
    $bp->signup->step = 'completed-confirmation';

    $next_user_id = get_last_id_auto_increment($wpdb->prefix.'users');
    $bp->signup->avatar_dir = wp_hash( $next_user_id );
    $bp->displayed_user->id = $next_user_id;

    //$bp->avatar_admin->image->url  = $_FILES['file']['name'];

    $avatar = tv_bp_core_avatar_handle_upload( $_FILES , 'xprofile_avatar_upload_dir');
    
    if ( $avatar ) {

        $bp->avatar_admin->step = 'crop-image';

        ob_start();
        //tv_add_jquery_cropper();
        // Bail if no image was uploaded
    $image = apply_filters( 'bp_inline_cropper_image', getimagesize( bp_core_avatar_upload_path() . buddypress()->avatar_admin->image->dir ) );
    if ( empty( $image ) )
            //
    $full_height = bp_core_avatar_full_height();
    $full_width  = bp_core_avatar_full_width();

    // Calculate Aspect Ratio
    if ( !empty( $full_height ) && ( $full_width != $full_height ) ) {
        $aspect_ratio = $full_width / $full_height;
    } else {
        $aspect_ratio = 1;
    }

    // Default cropper coordinates
    $crop_left   = round( $image[0] / 4 );
    $crop_top    = round( $image[1] / 4 );
    $crop_right  = $image[0] - $crop_left;
    $crop_bottom = $image[1] - $crop_top; 

        $html = '<li class="image-wrap thumbnail" style="width: 100%">';
        //$html .= '<h5>'. _e( 'Crop Your New Avatar', 'buddypress' ).'</h5>';
        $html = '<img src="'.bp_get_avatar_to_crop().'" id="avatar-to-crop" class="avatar" alt="Image to crop" />';
        $html .= '  <div id="avatar-crop-pane">';
        $html .= '      <img src="'.bp_get_avatar_to_crop().'" id="avatar-crop-preview" class="avatar" alt="Crop Preview" />';
        $html .= '  </div>';
        $html .= '  <input type="button" name="avatar_crop_submit" id="avatar_crop_submit" value="Crop image" />';
        $html .= '  <img class="waiting" src="'.esc_url( admin_url( 'images/wpspin_light.gif' ) ).'" alt="" style="display:none" />';
        $html .= '<input type="hidden" name="image_src" id="image_src" value="'.bp_get_avatar_to_crop_src().'" />';
        $html .= '<input type="hidden" name="id_user" id="id_user" value="'.$next_user_id.'" />';
        $html .= '<input type="hidden" id="x" name="x" />';
        $html .= '<input type="hidden" id="y" name="y" />';
        $html .= '<input type="hidden" id="w" name="w" />';
        $html .= '<input type="hidden" id="h" name="h" />';
        $html .= '<script type="text/javascript">';
        $html .= '
            jQuery("#avatar-to-crop").Jcrop({
                onChange: showPreview,
                onSelect: showPreview,
                onSelect: updateCoords,
                keySupport:false,
                aspectRatio: '.$aspect_ratio.',
                setSelect: ['.$crop_left.', '.$crop_top.', '.$crop_right.', '.$crop_bottom.' ]
            });
            updateCoords({x:'.$crop_left.', y: '.$crop_top.', w: '.$crop_right.', h: '.$crop_bottom.'});

        function updateCoords(c) {
            jQuery("#x").val(c.x);
            jQuery("#y").val(c.y);
            jQuery("#w").val(c.w);
            jQuery("#h").val(c.h);
        }

        function showPreview(coords) {

            if ( parseInt(coords.w) > 0 ) {
                var fw = 150;
                var fh = 150;
                var rx = fw / coords.w;
                var ry = fh / coords.h;

                jQuery( "#avatar-crop-preview" ).css({
                    width: Math.round(rx * '.$image[0].') + "px",
                    height: Math.round(ry * '.$image[1].') + "px",
                    marginLeft: "-" + Math.round(rx * coords.x) + "px",
                    marginTop: "-" + Math.round(ry * coords.y) + "px"
                });
            }
        }

        ';
    $html .= '</script>';
    $html .= '
        <style type="text/css">
        .jcrop-holder { float: left; margin: 0 20px 20px 0; text-align: left; }
        #avatar-crop-pane { width: 150px; height: 150px; overflow: hidden; }
        #avatar-crop-submit { margin: 20px 0; }
        .jcrop-holder img,
        #avatar-crop-pane img,
        #avatar-upload-form img,
        #create-group-form img,
        #group-settings-form img { border: none !important; max-width: none !important; }
    </style>    ';
        echo $html;
    }else{
        echo 'Error upload';
    } 
    exit;
}

function get_last_id_auto_increment($table){
    global $wpdb;
    $rows = $wpdb->get_row("SHOW TABLE STATUS LIKE '".$table."'",ARRAY_A);
    $last_id = $rows['Auto_increment'];
    return $last_id;
}

function tv_delete_file() {
    $attach_id = isset( $_POST['attach_id'] ) ? intval( $_POST['attach_id'] ) : 0;
    $attachment = get_post( $attach_id );

    //post author or editor role
    if ( get_current_user_id() == $attachment->post_author || current_user_can( 'delete_private_pages' ) ) {
        wp_delete_attachment( $attach_id, true );
        echo 'success';
    }

    exit;
}


function tv_bp_avatar_upload_form(){
    global $bp;
       
?>
   

        <style type="text/css">
        a#tv-featured_image-pickfiles{
            background-color: #21759b;
            background-image: -webkit-gradient(linear, left top, left bottom, from(#2a95c5), to(#21759b));
            background-image: -webkit-linear-gradient(top, #2a95c5, #21759b);
            background-image: -moz-linear-gradient(top, #2a95c5, #21759b);
            background-image: -ms-linear-gradient(top, #2a95c5, #21759b);
            background-image: -o-linear-gradient(top, #2a95c5, #21759b);
            background-image: linear-gradient(to bottom, #2a95c5, #21759b);
            border-color: #21759b;
            border-bottom-color: #1e6a8d;
            -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,0.5);
            box-shadow: inset 0 1px 0 rgba(120,200,230,0.5);
            color: #fff;
            text-decoration: none;
            text-shadow: 0 1px 0 rgba(0,0,0,0.1);
            padding: 5px 10px
        }

        </style>
        <h4>Upload Avatar</h4>
        <div id="tutviet-avatar-wrapper">
            <div id="tutviet-signup-avatar">
            </div>
            <div class="tv-fields">
                <div id="tv-featured_image-upload-container" style="position: relative;">
                    <div class="tv-attachment-upload-filelist">
                        <a id="tv-featured_image-pickfiles" class="file_upload" href="#" style="position: relative; z-index: 1;">Upload avatar</a>
                        <span class="tv-file-validation" data-required="yes" data-type="file"></span>
                        <ul class="tv-attachment-list thumbnails">
                        </ul>
                    </div>
                </div> <!-- .tv-fields -->

                <script type="text/javascript">
                    jQuery(function($) {
                        new Tuviet_Uploader('tv-featured_image-pickfiles', 'tv-featured_image-upload-container','featured_image', 'jpg,jpeg,gif,png,bmp', 1024);
                    });
                </script>
                
                <?php wp_nonce_field( 'bp_avatar_upload' ) ?>
                
            </div>
        </div>
    

<?php
}

function tv_bp_core_avatar_handle_upload( $file, $upload_dir_filter) {

    /***
     * You may want to hook into this filter if you want to override this function.
     * Make sure you return false.
     */
    if ( !apply_filters( 'bp_core_pre_avatar_handle_upload', true, $file, $upload_dir_filter ) )
        return true;
    require_once( ABSPATH . '/wp-admin/includes/file.php' );

    $uploadErrors = array(
        0 => __( 'The image was uploaded successfully', 'buddypress' ),
        1 => __( 'The image exceeds the maximum allowed file size of: ', 'buddypress' ) . size_format( bp_core_avatar_original_max_filesize() ),
        2 => __( 'The image exceeds the maximum allowed file size of: ', 'buddypress' ) . size_format( bp_core_avatar_original_max_filesize() ),
        3 => __( 'The uploaded file was only partially uploaded.', 'buddypress' ),
        4 => __( 'The image was not uploaded.', 'buddypress' ),
        6 => __( 'Missing a temporary folder.', 'buddypress' )
    );

    if ( ! bp_core_check_avatar_upload( $file ) ) {
        bp_core_add_message( sprintf( __( 'Your upload failed, please try again. Error was: %s', 'buddypress' ), $uploadErrors[$file['file']['error']] ), 'error' );
        return false;
    }

    if ( ! bp_core_check_avatar_size( $file ) ) {
        bp_core_add_message( sprintf( __( 'The file you uploaded is too big. Please upload a file under %s', 'buddypress' ), size_format( bp_core_avatar_original_max_filesize() ) ), 'error' );
        return false;
    }

    if ( ! bp_core_check_avatar_type( $file ) ) {
        bp_core_add_message( __( 'Please upload only JPG, GIF or PNG photos.', 'buddypress' ), 'error' );
        return false;
    }

    // Filter the upload location
    add_filter( 'upload_dir', $upload_dir_filter, 10, 0 );

    $upload_dir = wp_upload_dir();
    emptyDirectory($upload_dir['path']);

    $bp = buddypress();
    
    $bp->avatar_admin->original = wp_handle_upload( $file['file'], array('test_form' => false) );

    // Remove the upload_dir filter, so that other upload URLs on the page
    // don't break
    remove_filter( 'upload_dir', $upload_dir_filter, 10, 0 );

    // Move the file to the correct upload location.
    if ( !empty( $bp->avatar_admin->original['error'] ) ) {
        bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->original['error'] ), 'error' );
        return false;
    }

    // Get image size
    $size  = @getimagesize( $bp->avatar_admin->original['file'] );
    $error = false;

    // Check image size and shrink if too large
    if ( $size[0] > bp_core_avatar_original_max_width() ) {
        $editor = wp_get_image_editor( $bp->avatar_admin->original['file'] );

        if ( ! is_wp_error( $editor ) ) {
            $editor->set_quality( 100 );

            $resized = $editor->resize( bp_core_avatar_original_max_width(), bp_core_avatar_original_max_width(), false );
            if ( ! is_wp_error( $resized ) ) {
                $thumb = $editor->save( $editor->generate_filename() );
            } else {
                $error = $resized;
            }

            // Check for thumbnail creation errors
            if ( false === $error && is_wp_error( $thumb ) ) {
                $error = $thumb;
            }

            // Thumbnail is good so proceed
            if ( false === $error ) {
                $bp->avatar_admin->resized = $thumb;
            }

        } else {
            $error = $editor;
        }

        if ( false !== $error ) {
            bp_core_add_message( sprintf( __( 'Upload Failed! Error was: %s', 'buddypress' ), $error->get_error_message() ), 'error' );
            return false;
        }
    }

    if ( ! isset( $bp->avatar_admin->image ) )
        $bp->avatar_admin->image = new stdClass();

    // We only want to handle one image after resize.
    if ( empty( $bp->avatar_admin->resized ) ) {
        $bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->original['file'] );
    } else {
        $bp->avatar_admin->image->dir = str_replace( bp_core_avatar_upload_path(), '', $bp->avatar_admin->resized['path'] );
        @unlink( $bp->avatar_admin->original['file'] );
    }

    // Check for WP_Error on what should be an image
    if ( is_wp_error( $bp->avatar_admin->image->dir ) ) {
        bp_core_add_message( sprintf( __( 'Upload failed! Error was: %s', 'buddypress' ), $bp->avatar_admin->image->dir->get_error_message() ), 'error' );
        return false;
    }

    // Set the url value for the image
    $bp->avatar_admin->image->url = bp_core_avatar_url() . $bp->avatar_admin->image->dir;

    return true;
}


function tv_handle_avatar_upload_crop(){
        global $bp, $wpdb;
        
   
    /* If the image cropping is done, crop the image and save a full/thumb version */
    if ( isset( $_POST['avatar_crop_submit'] ) ) {
            if ( !bp_core_avatar_handle_crop( array( 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) ){
               echo 'There was a problem cropping your avatar, please try uploading it again';
            }else{
                echo  bp_core_fetch_avatar( array( 'item_id' => $_POST['user_id'], 'type'=>'full','width'=>150,'height'=>150,'html'=>true) );       
                printf( '<br><a href="#" data-confirm="%s" class="tutviet-button button avatar-delete">%s</a>', __( 'Are you sure?', 'tutviet' ), __( 'Delete', 'tutviet' ) );
            }
        exit();
    }
    
}

function emptyDirectory($dirname,$self_delete=false) {
   if (is_dir($dirname))
      $dir_handle = opendir($dirname);
   if (!$dir_handle)
      return false;
   while($file = readdir($dir_handle)) {
      if ($file != "." && $file != "..") {
         if (!is_dir($dirname."/".$file))
            @unlink($dirname."/".$file);
         else
            emptyDirectory($dirname.'/'.$file,true);    
      }
   }
   closedir($dir_handle);
   if ($self_delete){
        @rmdir($dirname);
   }   
   return true;
}
add_action( 'bp_before_account_details_fields', 'tv_bp_avatar_upload_form' );

