jQuery(document).ready(function ($) {
  'use strict';

  $("#tabs").tabs().addClass("ui-tabs-vertical ui-helper-clearfix").show();

  $("#tabs li").removeClass("ui-corner-top").addClass("ui-corner-left");
  $(".loadingshortcode").fadeOut(300, function () {
    $(this).remove();
  });

  /* Fix for not scrolling popup*/
  if (/Android|webOS|iPhone|iPod|iPad|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    var parent = $(tinyMCEPopup.getWin().document);

    if (parent.find('#safari_fix').length === 0) {
      parent.find('.mceWrapper iframe').wrap(function () {
        return $('<div id="safari_fix"/>').css({
          'width': "100%",
          'height': "100%",
          'overflow': 'auto',
          '-webkit-overflow-scrolling': 'touch'
        });
      });
    }
  }

  $('input:checkbox:not(.simple)').radiobutton({
    className: 'jquery-switch',
    checkedClass: 'jquery-switch-on'
  });

  $('input:radio').radiobutton();

  /* qTip help ballons */
  $('.UseyourDrive .help').qtip({
    content: {
      attr: 'title'
    },
    position: {
      my: 'bottom center',
      at: 'top center',
      viewport: $(window),
      adjust: {
        scroll: false
      }
    },
    style: {
      classes: 'UseyourDrive help qtip-light'
    },
    show: {
      solo: true
    }

  });

  $("input[name=mode]:radio").change(function () {

    $('.option').hide();
    $("#UseyourDrive_linkedfolders").trigger('change');

    switch ($(this).val()) {
      case 'files':
        $('.option.forfilebrowser').not('.hidden').show();
        $('#settings_userfolders_tab, #settings_upload_tab, #settings_advanced_tab, #settings_manipulation_tab, #settings_notifications_tab').show();
        $('#settings_mediafiles_tab').hide();
        $('#UseyourDrive_upload_ext, #UseyourDrive_ext').val('');
        break;

      case 'gallery':
        $('.option.forgallery').show();
        $('#settings_userfolders_tab, #settings_upload_tab, #settings_advanced_tab, #settings_manipulation_tab, #settings_notifications_tab').show();
        $('#settings_mediafiles_tab').hide();
        $('#UseyourDrive_upload_ext, #UseyourDrive_ext').val('gif|jpg|jpeg|png|bmp');
        break;

      case 'audio':
        $('.option.foraudio').show();
        $('.root-folder').show();
        $('.no-root-folder').hide();
        $('#settings_mediafiles_tab').show();
        $('#settings_userfolders_tab, #settings_upload_tab, #settings_advanced_tab, #settings_manipulation_tab, #settings_notifications_tab').hide();
        break;

      case 'video':
        $('.option.forvideo').show();
        $('.root-folder').show();
        $('.no-root-folder').hide();
        $('#settings_mediafiles_tab').show();
        $('#settings_userfolders_tab, #settings_upload_tab, #settings_advanced_tab, #settings_manipulation_tab, #settings_notifications_tab').hide();
        break;
    }

    $("#UseyourDrive_breadcrumb, #UseyourDrive_mediapurchase, #UseyourDrive_search, #UseyourDrive_slideshow, #UseyourDrive_upload, #UseyourDrive_rename, #UseyourDrive_move, #UseyourDrive_editdescription, #UseyourDrive_delete, #UseyourDrive_addfolder").trigger('change');
    $('input[name=UseyourDrive_file_layout]:radio:checked').trigger('change').prop('checked', true);
    $('#UseyourDrive_linkedfolders').trigger('change');
  });

  $("input[name=UseyourDrive_file_layout]:radio").change(function () {
    switch ($(this).val()) {
      case 'grid':
        $('.columnnames-options, .option-filesize, .option-filedate').hide();
        break;
      case 'list':
        $('.columnnames-options, .option-filesize, .option-filedate').show();
        break;
    }
  });

  $("#UseyourDrive_breadcrumb, #UseyourDrive_mediapurchase, #UseyourDrive_search, #UseyourDrive_slideshow, #UseyourDrive_upload, #UseyourDrive_rename, #UseyourDrive_move, #UseyourDrive_editdescription, #UseyourDrive_delete, #UseyourDrive_addfolder, #UseyourDrive_user_folders, #UseyourDrive_userfolders_template").change(function () {
    var toggleelement = '.' + $(this).attr('data-div-toggle');
    if ($(this).is(":checked")) {
      $(toggleelement).show().removeClass('hidden');
    } else {
      $(toggleelement).hide().addClass('hidden');
    }
  });

  $("#UseyourDrive_linkedfolders").change(function () {
    if ($(this).is(":checked")) {
      $(".option-userfolders ").show();
    } else {
      $(".option-userfolders").hide();
    }
    $('input[name=UseyourDrive_userfolders_method]:radio:checked').trigger('change').prop('checked', true);
  });

  $("input[name=UseyourDrive_userfolders_method]:radio").change(function () {
    switch ($(this).val()) {
      case 'manual':
        $('.root-folder').hide();
        $('.no-root-folder').show();
        $('.option-userfolders_auto').hide().addClass('hidden');
        break;
      case 'auto':
        $('.root-folder').show();
        $('.no-root-folder').hide();
        $('.option-userfolders_auto').show().removeClass('hidden');
        break;
    }
  });

  $("input[name=sort_field]:radio").change(function () {
    switch ($(this).val()) {
      case 'shuffle':
        $('.option-sort-field').hide();
        break;
      default:
        $('.option-sort-field').show();
        break;
    }
  });


  $(".UseyourDrive .insert_links").click(createDirectLinks);
  $(".UseyourDrive .insert_embedded").click(insertEmbedded);
  $('.UseyourDrive .insert_shortcode').click(insertUseyourDriveShortCode);
  $('.UseyourDrive .insert_shortcode_gf').click(insertUseyourDriveShortCodeGF);

  $(".UseyourDrive img.preloading").unveil(200, $(".UseyourDrive .ajax-filelist"), function () {
    $(this).load(function () {
      $(this).removeClass('preloading');
    });
  });

  /* Initialise from shortcode */
  $('input[name=mode]:radio:checked').trigger('change').prop('checked', true);

  function createShortcode() {
    var dir = $(".root-folder .UseyourDrive.files").attr('data-id'),
            linkedfolders = $('#UseyourDrive_linkedfolders').prop("checked"),
            show_files = $('#UseyourDrive_showfiles').prop("checked"),
            show_folders = $('#UseyourDrive_showfolders').prop("checked"),
            ext = $('#UseyourDrive_ext').val(),
            show_filesize = $('#UseyourDrive_filesize').prop("checked"),
            show_filedate = $('#UseyourDrive_filedate').prop("checked"),
            filelayout = $("input[name=UseyourDrive_file_layout]:radio:checked").val(),
            show_ext = $('#UseyourDrive_showext').prop("checked"),
            show_columnnames = $('#UseyourDrive_showcolumnnames').prop("checked"),
            candownloadzip = $('#UseyourDrive_candownloadzip').prop("checked"),
            showsharelink = $('#UseyourDrive_showsharelink').prop("checked"),
            showrefreshbutton = $('#UseyourDrive_showrefreshbutton').prop("checked"),
            show_breadcrumb = $('#UseyourDrive_breadcrumb').prop("checked"),
            breadcrumb_roottext = $('#UseyourDrive_roottext').val(),
            search = $('#UseyourDrive_search').prop("checked"),
            search_field = $("input[name=UseyourDrive_search_field]:radio:checked").val(),
            search_from = $('#UseyourDrive_searchfrom').prop("checked"),
            previewinline = $('#UseyourDrive_previewinline').prop("checked"),
            force_download = $('#UseyourDrive_forcedownload').prop("checked"),
            include = $('#UseyourDrive_include').val(),
            exclude = $('#UseyourDrive_exclude').val(),
            sort_field = $("input[name=sort_field]:radio:checked").val(),
            sort_order = $("input[name=sort_order]:radio:checked").val(),
            slideshow = $('#UseyourDrive_slideshow').prop("checked"),
            pausetime = $('#UseyourDrive_pausetime').val(),
            maximages = $('#UseyourDrive_maximage').val(),
            target_height = $('#UseyourDrive_targetHeight').val(),
            max_width = $('#UseyourDrive_max_width').val(),
            max_height = $('#UseyourDrive_max_height').val(),
            upload = $('#UseyourDrive_upload').prop("checked"),
            upload_simple = $('#UseyourDrive_upload_simple').prop("checked"),
            upload_ext = $('#UseyourDrive_upload_ext').val(),
            maxfilesize = $('#UseyourDrive_maxfilesize').val(),
            convert = $('#UseyourDrive_upload_convert').prop("checked"),
            rename = $('#UseyourDrive_rename').prop("checked"),
            move = $('#UseyourDrive_move').prop("checked"),
            editdescription = $('#UseyourDrive_editdescription').prop("checked"),
            can_delete = $('#UseyourDrive_delete').prop("checked"),
            can_addfolder = $('#UseyourDrive_addfolder').prop("checked"),
            deletetotrash = $('#UseyourDrive_deletetotrash').prop("checked"),
            notification_download = $('#UseyourDrive_notificationdownload').prop("checked"),
            notification_upload = $('#UseyourDrive_notificationupload').prop("checked"),
            notification_deletion = $('#UseyourDrive_notificationdeletion').prop("checked"),
            notification_emailaddress = $('#UseyourDrive_notification_email').val(),
            user_folders = $('#UseyourDrive_user_folders').prop("checked"),
            use_template_dir = $('#UseyourDrive_userfolders_template').prop("checked"),
            template_dir = $(".template-folder .UseyourDrive.files").attr('data-id'),
            view_role = readCheckBoxes("input[name='UseyourDrive_view_role[]']"),
            download_role = readCheckBoxes("input[name='UseyourDrive_download_role[]']"),
            upload_role = readCheckBoxes("input[name='UseyourDrive_upload_role[]']"),
            rename_role = readCheckBoxes("input[name='UseyourDrive_rename_role[]']"),
            move_role = readCheckBoxes("input[name='UseyourDrive_move_role[]']"),
            editdescription_role = readCheckBoxes("input[name='UseyourDrive_editdescription_role[]']"),
            delete_role = readCheckBoxes("input[name='UseyourDrive_delete_role[]']"),
            addfolder_role = readCheckBoxes("input[name='UseyourDrive_addfolder_role[]']"),
            view_user_folders_role = readCheckBoxes("input[name='UseyourDrive_view_user_folders_role[]']"),
            mediaextensions = readCheckBoxes("input[name='UseyourDrive_mediaextensions[]']"),
            autoplay = $('#UseyourDrive_autoplay').prop("checked"),
            hideplaylist = $('#UseyourDrive_hideplaylist').prop("checked"),
            covers = $('#UseyourDrive_covers').prop("checked"),
            linktomedia = $('#UseyourDrive_linktomedia').prop("checked"),
            mediapurchase = $('#UseyourDrive_mediapurchase').prop("checked"),
            linktoshop = $('#UseyourDrive_linktoshop').val();

    var data = '';

    if (UseyourDrive_vars.shortcodeRaw === '1') {
      data += '[raw]';
    }

    data += '[useyourdrive ';

    if (dir !== '') {
      if (linkedfolders) {
        if ($("input[name=UseyourDrive_userfolders_method]:radio:checked").val() !== 'manual') {
          data += 'dir="' + dir + '" ';
        }
      } else {
        data += 'dir="' + dir + '" ';
      }
    }

    if (max_width !== '') {
      if (max_width.indexOf("px") !== -1 || max_width.indexOf("%") !== -1) {
        data += 'maxwidth="' + max_width + '" ';
      } else {
        data += 'maxwidth="' + parseInt(max_width) + '" ';
      }
    }

    if (max_height !== '') {
      if (max_height.indexOf("px") !== -1 || max_height.indexOf("%") !== -1) {
        data += 'maxheight="' + max_height + '" ';
      } else {
        data += 'maxheight="' + parseInt(max_height) + '" ';
      }
    }

    data += 'mode="' + $("input[name=mode]:radio:checked").val() + '" ';

    if (ext !== '') {
      data += 'ext="' + ext + '" ';
    }

    if (include !== '') {
      data += 'include="' + include + '" ';
    }
    if (exclude !== '') {
      data += 'exclude="' + exclude + '" ';
    }

    if (view_role !== 'administrator|editor|author|contributor|subscriber|pending|guest') {
      data += 'viewrole="' + view_role + '" ';
    }

    if (sort_field !== 'name') {
      data += 'sortfield="' + sort_field + '" ';
    }

    if (sort_field !== 'shuffle' && sort_order !== 'asc') {
      data += 'sortorder="' + sort_order + '" ';
    }

    var mode = $("input[name=mode]:radio:checked").val();
    switch (mode) {
      case 'audio':
      case 'video':

        if (mediaextensions === 'none') {
          $('#settings_mediafiles_tab a').trigger('click');
          $(".mediaextensions").css("color", "red");
          return false;
        }
        data += 'mediaextensions="' + mediaextensions + '" ';

        if (autoplay === true) {
          data += 'autoplay="1" ';
        }

        if (hideplaylist === true) {
          data += 'hideplaylist="1" ';
        }

        if (covers === true) {
          data += 'covers="1" ';
        }

        if (linktomedia === true) {
          data += 'linktomedia="1" ';
        }

        if (mediapurchase === true && linktoshop !== '') {
          data += 'linktoshop="' + linktoshop + '" ';
        }

        break;

      case 'files':
      case 'gallery':
        if (mode === 'gallery') {

          if (maximages !== '') {
            data += 'maximages="' + maximages + '" ';
          }

          if (target_height !== '') {
            data += 'targetheight="' + target_height + '" ';
          }

          if (slideshow === true) {
            data += 'slideshow="1" ';
            if (pausetime !== '') {
              data += 'pausetime="' + pausetime + '" ';
            }
          }
        }

        if (mode === 'files') {
          if (show_files === false) {
            data += 'showfiles="0" ';
          }
          if (show_folders === false) {
            data += 'showfolders="0" ';
          }
          if (show_filesize === false) {
            data += 'filesize="0" ';
          }

          if (show_filedate === false) {
            data += 'filedate="0" ';
          }

          if (filelayout === 'list') {
            data += 'filelayout="list" ';
          }

          if (show_ext === false) {
            data += 'showext="0" ';
          }

          if (force_download === true) {
            data += 'forcedownload="1" ';
          }

          if (show_columnnames === false) {
            data += 'showcolumnnames="0" ';
          }

          if (download_role !== 'administrator|editor|author|contributor|subscriber|pending|guest') {
            data += 'downloadrole="' + download_role + '" ';
          }
        }

        if (previewinline === false) {
          data += 'previewinline="0" ';
        }
        if (candownloadzip === true) {
          data += 'candownloadzip="1" ';
        }

        if (showsharelink === true) {
          data += 'showsharelink="1" ';
        }

        if (showrefreshbutton === false) {
          data += 'showrefreshbutton="0" ';
        }

        if (search === false) {
          data += 'search="0" ';
        } else {
          if (search_field === '1') {
            data += 'searchcontents="1" ';
          }

          if (search_from === true) {
            data += 'searchfrom="selectedroot" ';
          }
        }

        if (show_breadcrumb === true) {
          if (breadcrumb_roottext !== '') {
            data += 'roottext="' + breadcrumb_roottext + '" ';
          }
        } else {
          data += 'showbreadcrumb="0" ';
        }

        if (notification_download === true || notification_upload === true || notification_deletion === true) {
          if (notification_emailaddress !== '') {
            data += 'notificationemail="' + notification_emailaddress + '" ';
          }
        }

        if (notification_download === true) {
          data += 'notificationdownload="1" ';
        }

        if (upload === true) {
          data += 'upload="1" ';

          if (upload_simple === true) {
            data += 'simpleupload="1" ';
          }

          if (upload_role !== 'administrator|editor|author|contributor|subscriber') {
            data += 'uploadrole="' + upload_role + '" ';
          }
          if (maxfilesize !== '') {
            data += 'maxfilesize="' + maxfilesize + '" ';
          }
          if (convert === true) {
            data += 'convert="1" ';
          }


          if (upload_ext !== '') {
            data += 'uploadext="' + upload_ext + '" ';
          }

          if (notification_upload === true) {
            data += 'notificationupload="1" ';
          }

        }

        if (rename === true) {
          data += 'rename="1" ';

          if (rename_role !== 'administrator|editor') {
            data += 'renamerole="' + rename_role + '" ';
          }
        }

        if (move === true) {
          data += 'move="1" ';

          if (move_role !== 'administrator|editor') {
            data += 'moverole="' + move_role + '" ';
          }
        }

        if (editdescription === true) {
          data += 'editdescription="1" ';

          if (editdescription_role !== 'administrator|editor') {
            data += 'editdescription="' + editdescription_role + '" ';
          }
        }

        if (can_delete === true) {
          data += 'delete="1" ';

          if (delete_role !== 'administrator|editor') {
            data += 'deleterole="' + delete_role + '" ';
          }

          if (notification_deletion === true) {
            data += 'notificationdeletion="1" ';
          }

          if (deletetotrash === true) {
            data += 'deletetotrash="1" ';
          }
        }

        if (can_addfolder === true) {
          data += 'addfolder="1" ';

          if (addfolder_role !== 'administrator|editor') {
            data += 'addfolderrole="' + addfolder_role + '" ';
          }
        }

        if (linkedfolders === true) {
          var method = $("input[name=UseyourDrive_userfolders_method]:radio:checked").val();
          data += 'userfolders="' + method + '" ';

          if (method === 'auto' && use_template_dir === true && template_dir !== '') {
            data += 'usertemplatedir="' + template_dir + '" ';
          }

          if (view_user_folders_role !== 'administrator') {
            data += 'viewuserfoldersrole="' + view_user_folders_role + '" ';
          }
        }

        break;
    }

    data += ']';

    if (UseyourDrive_vars.shortcodeRaw === '1') {
      data += '[/raw]';
    }

    return data;
  }

  function insertUseyourDriveShortCode() {
    var data = createShortcode();

    tinyMCEPopup.execCommand('mceInsertContent', false, data);
    // Refocus in window
    if (tinyMCEPopup.isWindow)
      window.focus();
    tinyMCEPopup.editor.focus();
    tinyMCEPopup.close();
  }

  function insertUseyourDriveShortCodeGF() {
    var data = createShortcode();
    $('#field_useyourdrive', window.parent.document).val(data);
    window.parent.SetFieldProperty('UseyourdriveShortcode', data);
    window.parent.tb_remove();
  }

  function createDirectLinks() {
    var listtoken = $(".UseyourDrive.files").attr('data-token'),
            lastpath = $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-path'),
            entries = readGDriveArrCheckBoxes(".UseyourDrive[data-token='" + listtoken + "'] input[name='selected-files[]']");

    if (entries.length === 0) {
      if (tinyMCEPopup.isWindow)
        window.focus();
      tinyMCEPopup.editor.focus();
      tinyMCEPopup.close();
    }

    $.ajax({
      type: "POST",
      url: UseyourDrive_vars.ajax_url,
      data: {
        action: 'useyourdrive-create-link',
        listtoken: listtoken,
        lastpath: lastpath,
        entries: entries,
        _ajax_nonce: UseyourDrive_vars.createlink_nonce
      },
      beforeSend: function () {
        $(".UseyourDrive .loading").height($(".UseyourDrive .ajax-filelist").height());
        $(".UseyourDrive .loading").fadeTo(400, 0.8);
        $(".UseyourDrive .insert_links").attr('disabled', 'disabled');
      },
      complete: function () {
        $(".UseyourDrive .loading").fadeOut(400);
        $(".UseyourDrive .insert_links").removeAttr('disabled');
      },
      success: function (response) {
        if (response !== null) {
          if (response.links !== null && response.links.length > 0) {

            var data = '<table>';

            $.each(response.links, function (key, linkresult) {
              data += '<tr><td><a class="UseyourDrive-directlink" href="' + linkresult.link.replace('?dl=1', '') + '">' + linkresult.name + '</a></td><td>&nbsp;</td><td></td></tr>';
            });

            data += '</table>';

            tinyMCEPopup.execCommand('mceInsertContent', false, data);
            // Refocus in window
            if (tinyMCEPopup.isWindow)
              window.focus();
            tinyMCEPopup.editor.focus();
            tinyMCEPopup.close();
          } else {
          }
        }
      },
      dataType: 'json'
    });
    return false;
  }

  function insertEmbedded() {
    var listtoken = $(".UseyourDrive.files").attr('data-token'),
            lastpath = $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-path'),
            entries = readGDriveArrCheckBoxes(".UseyourDrive[data-token='" + listtoken + "'] input[name='selected-files[]']");

    if (entries.length === 0) {
      if (tinyMCEPopup.isWindow)
        window.focus();
      tinyMCEPopup.editor.focus();
      tinyMCEPopup.close();
    }

    $.ajax({
      type: "POST",
      url: UseyourDrive_vars.ajax_url,
      data: {
        action: 'useyourdrive-embedded',
        listtoken: listtoken,
        lastpath: lastpath,
        entries: entries,
        _ajax_nonce: UseyourDrive_vars.createlink_nonce
      },
      beforeSend: function () {
        $(".UseyourDrive .loading").height($(".UseyourDrive .ajax-filelist").height());
        $(".UseyourDrive .loading").fadeTo(400, 0.8);
        $(".UseyourDrive .insert_links").attr('disabled', 'disabled');
      },
      complete: function () {
        $(".UseyourDrive .loading").fadeOut(400);
        $(".UseyourDrive .insert_links").removeAttr('disabled');
      },
      success: function (response) {
        if (response !== null) {
          if (response.links !== null && response.links.length > 0) {

            var data = '';

            $.each(response.links, function (key, linkresult) {
              data += '<iframe src="' + linkresult.embeddedlink + '" height="480" style="width:100%;" frameborder="0" scrolling="no" class="uyd-embedded"></iframe>';
            });



            tinyMCEPopup.execCommand('mceInsertContent', false, data);
            // Refocus in window
            if (tinyMCEPopup.isWindow)
              window.focus();
            tinyMCEPopup.editor.focus();
            tinyMCEPopup.close();
          } else {
          }
        }
      },
      dataType: 'json'
    });
    return false;
  }

  function readCheckBoxes(element) {
    var values = $(element + ":checked").map(function () {
      return this.value;
    }).get();


    if (values.length === 0) {
      return "none";
    }

    return values.join('|');
  }
});

(function ($) {
  $.fn.disableTab = function (tabIndex, hide) {

    // Get the array of disabled tabs, if any
    var disabledTabs = this.tabs("option", "disabled");

    if ($.isArray(disabledTabs)) {
      var pos = $.inArray(tabIndex, disabledTabs);

      if (pos < 0) {
        disabledTabs.push(tabIndex);
      }
    }
    else {
      disabledTabs = [tabIndex];
    }

    this.tabs("option", "disabled", disabledTabs);

    if (hide === true) {
      $(this).find('li:eq(' + tabIndex + ')').addClass('ui-state-hidden');
    }

    // Enable chaining
    return this;
  };

  $.fn.enableTab = function (tabIndex) {

    // Remove the ui-state-hidden class if it exists
    $(this).find('li:eq(' + tabIndex + ')').removeClass('ui-state-hidden');

    // Use the built-in enable function
    this.tabs("enable", tabIndex);

    // Enable chaining
    return this;

  };
})(jQuery);