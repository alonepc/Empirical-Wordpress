;(function($) {

    /**
     * Upload handler helper
     *
     * @param string {browse_button} browse_button ID of the pickfile
     * @param string {container} container ID of the wrapper
     * @param int {max} maximum number of file uplaods
     * @param string {type}
     */
    window.Tuviet_Uploader = function (browse_button, container, type, allowed_type, max_file_size) {
        this.container = container;
        this.browse_button = browse_button;
        this.max = 1;
        this.count = $('#' + container).find('.tv-attachment-list > li').length; //count how many items are there

        //if no element found on the page, bail out
        if( !$('#'+browse_button).length ) {
            return;
        }

        //instantiate the uploader
        this.uploader = new plupload.Uploader({
            runtimes: 'html5,html4',
            //drop_element : browse_button,//use drop to upload
            browse_button: browse_button,
            container: container,
            multipart: true,
            multipart_params: {
                action: 'tuviet_action_file_upload'
            },
            multiple_queues: false,
            multi_selection: false,
            urlstream_upload: true,
            file_data_name: 'file',
            max_file_size: max_file_size + 'kb',
            url: tutviet_custom_js.plupload.url + '&type=' + type,
            flash_swf_url: tutviet_custom_js.flash_swf_url,
            filters: [{
                title: 'Allowed Files',
                extensions: allowed_type
            }]
        });

        //attach event handlers
        this.uploader.bind('Init', $.proxy(this, 'init'));
        this.uploader.bind('FilesAdded', $.proxy(this, 'added'));
        this.uploader.bind('QueueChanged', $.proxy(this, 'upload'));
        this.uploader.bind('UploadProgress', $.proxy(this, 'progress'));
        this.uploader.bind('Error', $.proxy(this, 'error'));
        this.uploader.bind('FileUploaded', $.proxy(this, 'uploaded'));

        this.uploader.init();

        $('#' + container).on('click', '#avatar_crop_submit', $.proxy(this.handle_crop_avata, this));

        $('#tutviet-avatar-wrapper').on('click', 'a.avatar-delete', $.proxy(this.removeAttachment, this));
    };

    Tuviet_Uploader.prototype = {

        init: function (up, params) {
            this.showHide();
        },

        showHide: function () {

            if ( this.count >= this.max) {
                $('#' + this.container).find('.file_upload').hide();

                return;
            };

            $('#' + this.container).find('.file_upload').show();
        },

        added: function (up, files) {
            var $container = $('#' + this.container).find('.tv-attachment-upload-filelist');

            this.count += 1;
            this.showHide();

            $.each(files, function(i, file) {
                $container.append(
                    '<div class="upload-item" id="' + file.id + '"><div class="progress progress-striped active"><div class="bar"></div></div><div class="filename original">' +
                    file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                    '</div></div>');
            });

            up.refresh(); // Reposition Flash/Silverlight
            up.start();
        },

        upload: function (uploader) {
            this.uploader.start();
        },

        progress: function (up, file) {
            var item = $('#' + file.id);

            $('.bar', item).css({ width: file.percent + '%' });
            $('.percent', item).html( file.percent + '%' );
        },

        error: function (up, error) {
            $('#' + this.container).find('#' + error.file.id).remove();
            alert('Error #' + error.code + ': ' + error.message);

            this.count -= 1;
            this.showHide();
            this.uploader.refresh();
        },

        uploaded: function (up, file, response) {
            // var res = $.parseJSON(response.response);

            // console.log( typeof response, typeof response.response);
            // console.log(response, response.response, res);

            $('#' + file.id + " b").html("100%");
            $('#' + file.id).remove();

            if(response.response !== 'error') {
                var $container = $('#' + this.container).find('.tv-attachment-list');
                $('#tutviet-signup-avatar').hide();
                $container.append(response.response);

            } else {
                alert(res.error);

                this.count -= 1;
                this.showHide();
            }
        },
        
        handle_crop_avata: function(e) {
            e.preventDefault();
            var self = this,
            el = $(e.currentTarget);
            jQuery('.waiting').show();
            var avatar_crop_submit = jQuery('#avatar_crop_submit').val();
            var crop_x = jQuery('#x').val();
            var crop_y = jQuery('#y').val();
            var crop_w = jQuery('#w').val();
            var crop_h = jQuery('#h').val();
            var user_id = jQuery('#id_user').val();
            var image_src = jQuery('#image_src').val();
            jQuery.ajax({
                type : "post",
                dataType : "html",
                url :tutviet_custom_js.ajaxurl,
                data :{
                    action: 'tutviet_action_handle_crop',
                    image_src:image_src,
                    x:crop_x,
                    y:crop_y,
                    w:crop_w,
                    h:crop_h,
                    user_id:user_id,
                    avatar_crop_submit:avatar_crop_submit,
                },
                success:function(data) {
                    jQuery('.waiting').hide();
                    jQuery('#tutviet-signup-avatar').html(data);
                    jQuery('#tutviet-signup-avatar').show();
                    jQuery('.tv-attachment-list').html('');
                 }
              });
        },

        removeAttachment: function(e) {
            
            e.preventDefault();

            var self = this,
            el = $(e.currentTarget);

            if ( confirm(tutviet_custom_js.confirmMsg) ) {
                el.parent().hide();
                self.count -= 1;
                self.showHide();
                self.uploader.refresh();

            }
        }
    };
})(jQuery);