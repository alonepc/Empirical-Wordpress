jQuery(document).ready(function ($) {
  $(window).load(function () {
    'use strict';

    /* Audio Players*/
    $('.UseyourDrive.media.audio').each(function () {
      var listtoken = $(this).attr('data-token'),
              extensions = $(this).attr('data-extensions'),
              autoplay = $(this).attr('data-autoplay'),
              jPlayerSelector = '#' + $(this).find('.jp-jplayer').attr('id'),
              cssSelector = '#' + $(this).find('.jp-video').attr('id');
      uyd_playlists[listtoken] = new jPlayerPlaylist({
        jPlayer: jPlayerSelector,
        cssSelectorAncestor: cssSelector
      }, [], {
        playlistOptions: {
          autoPlay: (autoplay === '1' ? true : false)
        },
        swfPath: UseyourDrive_vars.js_url,
        supplied: extensions,
        solution: "html,flash",
        wmode: "window",
        size: {
          width: "100%",
          height: "0px"
        },
        volume: 1,
        ready: function () {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-title").show();
          var data = {
            action: 'useyourdrive-get-playlist',
            lastFolder: $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-id'),
            sort: $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-sort'),
            listtoken: listtoken,
            _ajax_nonce: UseyourDrive_vars.getplaylist_nonce
          };
          $.ajax({
            type: "POST",
            url: UseyourDrive_vars.ajax_url,
            data: data,
            success: function (result) {
              if (result !== '-1') {
                uyd_playlists[listtoken].setPlaylist(result);
                if (!$(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").hasClass('hideonstart')) {
                  $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").slideDown("slow");
                }

                $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item-dl").unbind('click');
                $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item-dl").click(function (e) {
                  e.stopPropagation();
                  var href = $(this).attr('href') + '&dl=1',
                          dataname = $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-title").html() +
                          " - " + $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-artist").html();

                  sendDriveGooglePageView('Download', dataname);

                  // Delay a few milliseconds for Tracking event
                  setTimeout(function () {
                    window.location = href;
                  }, 300);

                  return false;

                });

              }
            },
            dataType: 'json'
          });
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer img").imagesLoaded(function () {
            $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").stop().delay(1500).animate({height: "200px"});
          });

          animatePlaylist(listtoken);
        },
        loadstart: function (e) {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-title").html($(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current").html());
        },
        play: function (e) {
          var dataname = $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-title").html() +
                  " - " + $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-artist").html();
          sendDriveGooglePageView('Play Music', dataname);
        }
      });
      createAudioSlider(listtoken);
    });


    /* Video Players*/
    $('.UseyourDrive.media.video').each(function () {
      var listtoken = $(this).attr('data-token'),
              extensions = $(this).attr('data-extensions'),
              autoplay = $(this).attr('data-autoplay'),
              jPlayerSelector = '#' + $(this).find('.jp-jplayer').attr('id'),
              cssSelector = '#' + $(this).find('.jp-video').attr('id');
      uyd_playlists[listtoken] = new jPlayerPlaylist({
        jPlayer: jPlayerSelector,
        cssSelectorAncestor: cssSelector
      }, [], {
        playlistOptions: {
          autoPlay: (autoplay === '1' ? true : false)
        },
        autohide: {
          full: false
        },
        swfPath: UseyourDrive_vars.js_url,
        supplied: extensions,
        solution: "html,flash",
        volume: 1,
        audioFullScreen: true,
        errorAlerts: false,
        warningAlerts: false,
        size: {
          width: "100%",
          height: "100%"
        },
        error: function (event) {
          console.log(event.jPlayer.error);
          console.log(event.jPlayer.error.type);
        },
        ready: function (e) {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-title").show();
          var data = {
            action: 'useyourdrive-get-playlist',
            lastFolder: $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-id'),
            sort: $(".UseyourDrive[data-token='" + listtoken + "']").attr('data-sort'),
            listtoken: listtoken,
            _ajax_nonce: UseyourDrive_vars.getplaylist_nonce
          };
          $.ajax({
            type: "POST",
            url: UseyourDrive_vars.ajax_url,
            data: data,
            success: function (result) {
              if (result !== '-1') {
                uyd_playlists[listtoken].setPlaylist(result);

                if (!$(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").hasClass('hideonstart')) {
                  $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").slideDown("slow");
                }
                $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item-dl").unbind('click');
                $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item-dl").click(function (e) {
                  e.stopPropagation();
                  var href = $(this).attr('href') + '&dl=1',
                          dataname = $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-title").html() +
                          " - " + $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-artist").html();

                  sendDriveGooglePageView('Download', dataname);

                  // Delay a few milliseconds for Tracking event
                  setTimeout(function () {
                    window.location = href;
                  }, 300);

                  return false;

                });
              }
            },
            dataType: 'json'
          });
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").width() / 1.6);
          $(".UseyourDrive[data-token='" + listtoken + "'] object").width('100%');
          $(".UseyourDrive[data-token='" + listtoken + "'] object").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());

          animatePlaylist(listtoken);
        },
        ended: function (e) {

        },
        pause: function (e) {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").fadeIn();
        },
        loadstart: function (e) {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-title").html($(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current").html());
        },
        loadedmetadata: function (e) {

          if (e.jPlayer.status.videoHeight !== 0 && e.jPlayer.status.videoWidth !== 0) {
            var ratio = e.jPlayer.status.videoWidth / e.jPlayer.status.videoHeight;
            var videoselector = $(".UseyourDrive[data-token='" + listtoken + "'] object");
            if (e.jPlayer.html.active === true) {
              videoselector = $(".UseyourDrive[data-token='" + listtoken + "'] video");
              videoselector.bind('contextmenu', function () {
                return false;
              });
            }
            if (videoselector.height() === 0 || videoselector.height() !== videoselector.parent().width() / ratio) {
              videoselector.width(videoselector.parent().width());
              videoselector.height(videoselector.width() / ratio);
              $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height(videoselector.width() / ratio);
              $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());
            }
            $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer img").hide();
          }
        },
        waiting: function (e) {
          var videoselector = $(".UseyourDrive[data-token='" + listtoken + "'] object");
          if (e.jPlayer.html.active === true) {
            videoselector = $(".UseyourDrive[data-token='" + listtoken + "'] video");
            videoselector.bind('contextmenu', function () {
              return false;
            });
          }
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());
        },
        resize: function (e) {
          if (e.jPlayer.options.fullScreen === false) {
            $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").height($(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").height());
          }
        },
        play: function (e) {
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-video-play").fadeOut();
          $(".UseyourDrive[data-token='" + listtoken + "'] .jp-jplayer").one('click', function () {
            uyd_playlists[listtoken].pause();
          });

          var dataname = $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-title").html() +
                  " - " + $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist-item.jp-playlist-current  .jp-playlist-item-song-artist").html();
          sendDriveGooglePageView('Play Video', dataname);

          $('html, body').animate({
            scrollTop: $(".UseyourDrive[data-token='" + listtoken + "'].media").offset().top
          }, 1500);

        }
      });

      createAudioSlider(listtoken);

      var guivisable = false;
      $(this).mousemove(function () {
        if (!guivisable) {
          $(this).trigger('mouseenter');
        }
      });
      $(this).hover(
              function () {
                guivisable = true;
                $(this).find('.gui-container').stop().animate({bottom: 0}, 400).delay(5000).animate({bottom: -40}, 400, function () {
                  guivisable = false;
                });
              },
              function () {
                $(this).find('.gui-container').stop().animate({bottom: -40}, 400, function () {
                  guivisable = false;
                });
              });
    });

    function createAudioSlider(listtoken) {
      // Create the volume slider control
      $(".UseyourDrive[data-token='" + listtoken + "'] .currentVolume").slider({
        range: [0, 1],
        step: 0.01,
        start: 1,
        handles: 1,
        slide: function () {
          var value = $(this).val();
          $(".UseyourDrive[data-token='" + listtoken + "']  .jp-jplayer").jPlayer("option", "muted", false);
          $(".UseyourDrive[data-token='" + listtoken + "']  .jp-jplayer").jPlayer("option", "volume", value);
        }
      });
    }

    function animatePlaylist(listtoken) {
      if ($(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").hasClass('hideonstart')) {
        $(".UseyourDrive[data-token='" + listtoken + "'] .jp-gui, .UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").hover(
                function () {
                  $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").stop().slideDown("slow");
                },
                function () {
                  $(".UseyourDrive[data-token='" + listtoken + "'] .jp-playlist").stop().slideUp("slow");
                });
      }
    }
  });
});

/* Audio Slider */
(function ($) {
  $.fn.slider = function (options, flag) {
    var EVENT = window.navigator.msPointerEnabled ? 2 : "ontouchend" in document ? 3 : 1;
    if (window.debug && console) {
      console.log(EVENT)
    }
    function call(f, scope, args) {
      if (typeof f === "function") {
        f.call(scope, args)
      }
    }
    var percentage = {to: function (range, value) {
        value = range[0] < 0 ? value + Math.abs(range[0]) : value - range[0];
        return(value * 100) / this._length(range)
      }, from: function (range, value) {
        return(value * 100) / this._length(range)
      }, is: function (range, value) {
        return((value * this._length(range)) / 100) + range[0]
      }, _length: function (range) {
        return(range[0] > range[1] ? range[0] - range[1] : range[1] - range[0])
      }};
    function correct(proposal, slider, handle) {
      var setup = slider.data("setup"), handles = setup.handles, settings = setup.settings, pos = setup.pos;
      proposal = proposal < 0 ? 0 : proposal > 100 ? 100 : proposal;
      if (settings.handles == 2) {
        if (handle.is(":first-child")) {
          var other = parseFloat(handles[1][0].style[pos]) - settings.margin;
          proposal = proposal > other ? other : proposal
        } else {
          var other = parseFloat(handles[0][0].style[pos]) + settings.margin;
          proposal = proposal < other ? other : proposal
        }
      }
      if (settings.step) {
        var per = percentage.from(settings.range, settings.step);
        proposal = Math.round(proposal / per) * per
      }
      return proposal
    }
    function client(f) {
      try {
        return[(f.clientX || f.originalEvent.clientX || f.originalEvent.touches[0].clientX), (f.clientY || f.originalEvent.clientY || f.originalEvent.touches[0].clientY)]
      } catch (e) {
        return["x", "y"]
      }
    }
    function place(handle, pos) {
      return parseFloat(handle[0].style[pos])
    }
    var defaults = {handles: 1, serialization: {to: ["", ""], resolution: 0.01}};
    methods = {create: function () {
        return this.each(function () {
          function setHandle(handle, to, slider) {
            handle.css(pos, to + "%").data("input").val(percentage.is(settings.range, to).toFixed(res))
          }
          var settings = $.extend(defaults, options), handlehtml = "<a><div></div></a>", slider = $(this).data("_isnS_", true), handles = [], pos, orientation, classes = "", num = function (e) {
            return !isNaN(parseFloat(e)) && isFinite(e)
          }, split = (settings.serialization.resolution = settings.serialization.resolution || 0.01).toString().split("."), res = split[0] == 1 ? 0 : split[1].length;
          settings.start = num(settings.start) ? [settings.start, 0] : settings.start;
          $.each(settings, function (a, b) {
            if (num(b)) {
              settings[a] = parseFloat(b)
            } else {
              if (typeof b == "object" && num(b[0])) {
                b[0] = parseFloat(b[0]);
                if (num(b[1])) {
                  b[1] = parseFloat(b[1])
                }
              }
            }
            var e = false;
            b = typeof b == "undefined" ? "x" : b;
            switch (a) {
              case"range":
              case"start":
                e = b.length != 2 || !num(b[0]) || !num(b[1]);
                break;
              case"handles":
                e = (b < 1 || b > 2 || !num(b));
                break;
              case"connect":
                e = b != "lower" && b != "upper" && typeof b != "boolean";
                break;
              case"orientation":
                e = (b != "vertical" && b != "horizontal");
                break;
              case"margin":
              case"step":
                e = typeof b != "undefined" && !num(b);
                break;
              case"serialization":
                e = typeof b != "object" || !num(b.resolution) || (typeof b.to == "object" && b.to.length < settings.handles);
                break;
              case"slide":
                e = typeof b != "function";
                break
            }
            if (e && console) {
              console.error("Bad input for " + a + " on slider:", slider)
            }
          });
          settings.margin = settings.margin ? percentage.from(settings.range, settings.margin) : 0;
          if (settings.serialization.to instanceof jQuery || typeof settings.serialization.to == "string" || settings.serialization.to === false) {
            settings.serialization.to = [settings.serialization.to]
          }
          if (settings.orientation == "vertical") {
            classes += "vertical";
            pos = "top";
            orientation = 1
          } else {
            classes += "horizontal";
            pos = "left";
            orientation = 0
          }
          classes += settings.connect ? settings.connect == "lower" ? " connect lower" : " connect" : "";
          slider.addClass(classes);
          for (var i = 0; i < settings.handles; i++) {
            handles[i] = slider.append(handlehtml).children(":last");
            var setTo = percentage.to(settings.range, settings.start[i]);
            handles[i].css(pos, setTo + "%");
            if (setTo == 100 && handles[i].is(":first-child")) {
              handles[i].css("z-index", 2)
            }
            var bind = ".slider", onEvent = (EVENT === 1 ? "mousedown" : EVENT === 2 ? "MSPointerDown" : "touchstart") + bind + "X", moveEvent = (EVENT === 1 ? "mousemove" : EVENT === 2 ? "MSPointerMove" : "touchmove") + bind, offEvent = (EVENT === 1 ? "mouseup" : EVENT === 2 ? "MSPointerUp" : "touchend") + bind;
            handles[i].find("div").on(onEvent, function (e) {
              $("body").bind("selectstart" + bind, function () {
                return false
              });
              if (!slider.hasClass("disabled")) {
                $("body").addClass("TOUCH");
                var handle = $(this).addClass("active").parent(), unbind = handle.add($(document)).add("body"), originalPosition = parseFloat(handle[0].style[pos]), originalClick = client(e), previousClick = originalClick, previousProposal = false;
                $(document).on(moveEvent, function (f) {
                  f.preventDefault();
                  var currentClick = client(f);
                  if (currentClick[0] == "x") {
                    return
                  }
                  currentClick[0] -= originalClick[0];
                  currentClick[1] -= originalClick[1];
                  var movement = [previousClick[0] != currentClick[0], previousClick[1] != currentClick[1]], proposal = originalPosition + ((currentClick[orientation] * 100) / (orientation ? slider.height() : slider.width()));
                  proposal = correct(proposal, slider, handle);
                  if (movement[orientation] && proposal != previousProposal) {
                    handle.css(pos, proposal + "%").data("input").val(percentage.is(settings.range, proposal).toFixed(res));
                    call(settings.slide, slider.data("_n", true));
                    previousProposal = proposal;
                    handle.css("z-index", handles.length == 2 && proposal == 100 && handle.is(":first-child") ? 2 : 1)
                  }
                  previousClick = currentClick
                }).on(offEvent, function () {
                  unbind.off(bind);
                  $("body").removeClass("TOUCH");
                  if (slider.find(".active").removeClass("active").end().data("_n")) {
                    slider.data("_n", false).change()
                  }
                })
              }
            }).on("click", function (e) {
              e.stopPropagation()
            })
          }
          if (EVENT == 1) {
            slider.on("click", function (f) {
              if (!slider.hasClass("disabled")) {
                var currentClick = client(f), proposal = ((currentClick[orientation] - slider.offset()[pos]) * 100) / (orientation ? slider.height() : slider.width()), handle = handles.length > 1 ? (currentClick[orientation] < (handles[0].offset()[pos] + handles[1].offset()[pos]) / 2 ? handles[0] : handles[1]) : handles[0];
                setHandle(handle, correct(proposal, slider, handle), slider);
                call(settings.slide, slider);
                slider.change()
              }
            })
          }
          for (var i = 0; i < handles.length; i++) {
            var val = percentage.is(settings.range, place(handles[i], pos)).toFixed(res);
            if (typeof settings.serialization.to[i] == "string") {
              handles[i].data("input", slider.append('<input type="hidden" name="' + settings.serialization.to[i] + '">').find("input:last").val(val).change(function (a) {
                a.stopPropagation()
              }))
            } else {
              if (settings.serialization.to[i] == false) {
                handles[i].data("input", {val: function (a) {
                    if (typeof a != "undefined") {
                      this.handle.data("noUiVal", a)
                    } else {
                      return this.handle.data("noUiVal")
                    }
                  }, handle: handles[i]})
              } else {
                handles[i].data("input", settings.serialization.to[i].data("handleNR", i).val(val).change(function () {
                  var arr = [null, null];
                  arr[$(this).data("handleNR")] = $(this).val();
                  slider.val(arr)
                }))
              }
            }
          }
          $(this).data("setup", {settings: settings, handles: handles, pos: pos, res: res})
        })
      }, val: function () {
        if (typeof arguments[0] !== "undefined") {
          var val = typeof arguments[0] == "number" ? [arguments[0]] : arguments[0];
          return this.each(function () {
            var setup = $(this).data("setup");
            for (var i = 0; i < setup.handles.length; i++) {
              if (val[i] != null) {
                var proposal = correct(percentage.to(setup.settings.range, val[i]), $(this), setup.handles[i]);
                setup.handles[i].css(setup.pos, proposal + "%").data("input").val(percentage.is(setup.settings.range, proposal).toFixed(setup.res))
              }
            }
          })
        } else {
          var handles = $(this).data("setup").handles, re = [];
          for (var i = 0; i < handles.length; i++) {
            re.push(parseFloat(handles[i].data("input").val()))
          }
          return re.length == 1 ? re[0] : re
        }
      }, disabled: function () {
        return flag ? $(this).addClass("disabled") : $(this).removeClass("disabled")
      }};
    var $_val = jQuery.fn.val;
    jQuery.fn.val = function () {
      return this.data("_isnS_") ? methods.val.apply(this, arguments) : $_val.apply(this, arguments)
    };
    return options == "disabled" ? methods.disabled.apply(this) : methods.create.apply(this)
  }
})(jQuery);