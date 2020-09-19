<?php
global $isEmbed;
$isEmbed = 1;
global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
User::loginFromRequest();
if (!empty($_GET['evideo'])) {
    $v = Video::decodeEvideo();
    $evideo = $v['evideo'];
}
if (!empty($evideo)) {
    $video = $v['video'];
    $img = $evideo->thumbnails;
    $poster = $evideo->thumbnails;
    $imgw = 1280;
    $imgh = 720;
    $autoPlaySources = array();
    $autoPlayURL = '';
    $autoPlayPoster = '';
    $autoPlayThumbsSprit = '';
} else if (!empty($_GET['v'])) {
    $video = Video::getVideo($_GET['v'], "", true, false, false, true);
    //$video['id'] = $_GET['v'];
} else if (!empty($_GET['videoName'])) {
    $video = Video::getVideoFromCleanTitle($_GET['videoName']);
}

Video::unsetAddView($video['id']);

AVideoPlugin::getEmbed($video['id']);

if (empty($video)) {
    die("Video not found");
}


$customizedAdvanced = AVideoPlugin::getObjectDataIfEnabled('CustomizeAdvanced');

// allow embrd from in same site
$host = strtolower(parse_url(@$_SERVER['HTTP_REFERER'], PHP_URL_HOST));
$allowedHost = strtolower(parse_url($global['webSiteRootURL'], PHP_URL_HOST));
if ($allowedHost !== $host) {
    if (!empty($advancedCustomUser->blockEmbedFromSharedVideos) && !CustomizeUser::canShareVideosFromVideo($video['id'])) {
        die("Embed is forbidden");
    }

    $objSecure = AVideoPlugin::loadPluginIfEnabled('SecureVideosDirectory');
    if (!empty($objSecure)) {
        $objSecure->verifyEmbedSecurity();
    }
}

$imgw = 1280;
$imgh = 720;

if ($video['type'] !== "pdf") {
    $source = Video::getSourceFile($video['filename']);
    $img = $source['url'];
    $data = getimgsize($source['path']);
    $imgw = $data[0];
    $imgh = $data[1];
} else if (($video['type'] !== "audio") && ($video['type'] !== "linkAudio")) {
    $source = Video::getSourceFile($video['filename']);
    $img = $source['url'];
    $data = getimgsize($source['path']);
    $imgw = $data[0];
    $imgh = $data[1];
} else {
    $img = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
}
$images = Video::getImageFromFilename($video['filename']);
$poster = $images->poster;
if (!empty($images->posterPortrait)) {
    $img = $images->posterPortrait;
    $data = getimgsize($source['path']);
    $imgw = $data[0];
    $imgh = $data[1];
}

require_once $global['systemRootPath'] . 'plugin/AVideoPlugin.php';
/*
 * Swap aspect ratio for rotated (vvs) videos

  if ($video['rotation'] === "90" || $video['rotation'] === "270") {
  $embedResponsiveClass = "embed-responsive-9by16";
  $vjsClass = "vjs-9-16";
  } else {
  $embedResponsiveClass = "embed-responsive-16by9";
  $vjsClass = "vjs-16-9";
  } */
$vjsClass = "";
$obj = new Video("", "", $video['id']);
$resp = $obj->addView();
if (($video['type'] !== "audio") && ($video['type'] !== "linkAudio")) {
    $poster = "{$global['webSiteRootURL']}videos/{$video['filename']}.jpg";
} else {
    $poster = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
}

//https://.../vEmbed/527?modestbranding=1&showinfo=0&autoplay=1&controls=0&loop=1&mute=1&t=0
$modestbranding = false;
$autoplay = false;
$controls = "controls";
$loop = "";
$mute = "";
$objectFit = "";
$t = 0;

if (isset($_GET['modestbranding']) && $_GET['modestbranding'] == "1") {
    $modestbranding = true;
}
if (!empty($_GET['autoplay']) || $config->getAutoplay()) {
    $autoplay = true;
}
if (isset($_GET['controls']) && $_GET['controls'] == "0") {
    $controls = "";
}
if (!empty($_GET['loop'])) {
    $loop = "loop";
}
if (!empty($_GET['mute'])) {
    $mute = 'muted="muted"';
}
if (!empty($_GET['objectFit'])) {
    $objectFit = 'object-fit: ' . $_GET['objectFit'];
}
if (!empty($_GET['t'])) {
    $t = intval($_GET['t']);
} else if (!empty($video['progress']['lastVideoTime'])) {
    $t = intval($video['progress']['lastVideoTime']);
} else if (!empty($video['externalOptions']->videoStartSeconds)) {
    $t = parseDurationToSeconds($video['externalOptions']->videoStartSeconds);
}

$playerSkinsObj = AVideoPlugin::getObjectData("PlayerSkins");

$url = Video::getLink($video['id'], $video['clean_title'], false);
$title = str_replace('"', '', $video['title']) . ' - ' . $config->getWebSiteTitle();
$photo = User::getPhoto($video['users_id']);

if (empty($currentTime)) {
    $currentTime = 0;
}

if (User::hasBlockedUser($video['users_id'])) {
    $playerSkinsObj->disableEmbedTopInfo = true;
    $video['type'] = "blockedUser";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-3.5.1.min.js" type="text/javascript"></script>

        <?php
        echo AVideoPlugin::getHeadCode();
        ?>
        <script>
            var webSiteRootURL = '<?php echo $global['webSiteRootURL']; ?>';
        </script>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="<?php echo $config->getFavicon(); ?>">
        <title><?php echo $config->getWebSiteTitle(); ?> :: <?php echo $video['title']; ?></title>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/video.js/video-js.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/player.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/fontawesome-free-5.5.0-web/css/all.min.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css"/>

        <link rel="image_src" href="<?php echo $img; ?>" />
        <meta property="fb:app_id"             content="774958212660408" />
        <meta property="og:url"                content="<?php echo $url; ?>" />
        <meta property="og:type"               content="video.other" />
        <meta property="og:title"              content="<?php echo $title; ?>" />
        <meta property="og:description"        content="<?php echo!empty($custom) ? $custom : str_replace('"', '', $video['title']); ?>" />
        <meta property="og:image"              content="<?php echo $img; ?>" />
        <meta property="og:image:width"        content="<?php echo $imgw; ?>" />
        <meta property="og:image:height"       content="<?php echo $imgh; ?>" />
        <meta property="video:duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
        <meta property="duration" content="<?php echo Video::getItemDurationSeconds($video['duration']); ?>"  />
        <style>
            body {
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden;
                <?php
                if (!empty($customizedAdvanced->embedBackgroundColor)) {
                    echo "background-color: $customizedAdvanced->embedBackgroundColor !important;";
                }
                ?>

            }
            .video-js {
                position: static;
            }

            #topInfo{
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                min-height: 52px;
                font: 12px Roboto, Arial, sans-serif;
                color: #FFF;
                padding: 15px;
                background-image: linear-gradient(rgba(0,0,0,1), rgba(0,0,0,0));

            }
            #topInfo a{
                color: #EEE;
                text-shadow: 0 0 5px rgba(0,0,0,1);
            }
            #topInfo a:hover{
                color: #FFF;
            }
            #topInfo img{
                float: left; 
                max-height: 40px; 
                max-width: 40px; 
                margin-right: 10px;
            }
            #topInfo div{
                position: absolute;
                top: 15px;
                left: 0;
                display: flex; 
                height: 40px; 
                justify-content: center;
                align-items: center; 
                font-size: 1.5em;
                margin-left: 65px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #blockUserTop{
                position: absolute;
                right: 20px;
                top: 20px;
            }
            #blockUserTop button{
                background-color: rgba(255,255,255,0.3);
                border-color:  rgba(255,255,255,0.2);
                color: rgba(0,0,0,0.6);
            }
            #blockUserTop button:hover{
                background-color: rgba(255,255,255,0.8);
                border-color:  rgba(255,255,255,1);
                color: rgba(0,0,0,1);
            }
        </style>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
    </head>

    <body>
        <?php
        if ($video['type'] == "blockedUser") {
            ?>
            <video id="mainVideo" style="display: none; height: 0;width: 0;" ></video>
        <center style="height: 100%;">
            <br>
            <i class="fas fa-user-slash fa-3x"></i><hr>
            You've blocked user (<?php echo User::getNameIdentificationById($video['users_id']) ?>)<br>
            You won't see any comments or videos from this user<hr>
            <?php
            echo User::getblockUserButton($video['users_id']);
            ?>
            <br>
            <br>
        </center>
        <?php
    } else if ($video['type'] == "serie") {
        ?>
        <video id="mainVideo" style="display: none; height: 0;width: 0;" ></video>
        <iframe style="width: 100%; height: 100%;"  class="embed-responsive-item" src="<?php echo $global['webSiteRootURL']; ?>plugin/PlayLists/embed.php?playlists_id=<?php
        echo $video['serie_playlists_id'];
        if ($config->getAutoplay()) {
            echo "&autoplay=1";
        }
        ?>"></iframe>
        <script>
            $(document).ready(function () {
                addView(<?php echo $video['id']; ?>, 0);
            });
        </script>
        <?php
    } else if ($video['type'] == "article") {
        ?>
        <div id="main-video" class="bgWhite list-group-item" style="max-height: 100vh; overflow: hidden; overflow-y: auto; font-size: 1.5em;">
            <h1 style="font-size: 1.5em; font-weight: bold; text-transform: uppercase; border-bottom: #CCC solid 1px;">
                <?php
                echo $video['title'];
                ?>   
            </h1>
            <?php
            echo $video['description'];
            ?>     
            <script>
                $(document).ready(function () {
                    addView(<?php echo $video['id']; ?>, 0);
                });
            </script>

        </div>
        <?php
    } else if ($video['type'] == "pdf") {
        $sources = getVideosURLPDF($video['filename']);
        ?>
        <video id="mainVideo" style="display: none; height: 0;width: 0;" ></video>
        <iframe style="width: 100%; height: 100%;"  class="embed-responsive-item" src="<?php
        echo $sources["pdf"]['url'];
        ?>"></iframe>
        <script>
            $(document).ready(function () {
                addView(<?php echo $video['id']; ?>, 0);
            });
        </script>
        <?php
    } else if ($video['type'] == "image") {
        $sources = getVideosURLIMAGE($video['filename']);
        ?>
        <center style="height: 100%;">
            <img src="<?php
            echo $sources["image"]['url']
            ?>" class="img img-responsive"  style="height: 100%;" >
        </center>
        <script>
            $(document).ready(function () {
                addView(<?php echo $video['id']; ?>, 0);
            });
        </script>
        <?php
    } else if ($video['type'] == "zip") {
        $sources = getVideosURLZIP($video['filename']);
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><i class="far fa-file-archive"></i> <?php echo $video['title']; ?></div>
            <div class="panel-body">
                <ul class="list-group">
                    <?php
                    $za = new ZipArchive();
                    $za->open($sources['zip']["path"]);
                    for ($i = 0; $i < $za->numFiles; $i++) {
                        $stat = $za->statIndex($i);
                        $fname = basename($stat['name']);
                        ?>
                        <li class="list-group-item"  style="text-align: left;"><i class="<?php echo fontAwesomeClassName($fname) ?>"></i> <?php echo $fname; ?></li>
                        <?php
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
    } else if ($video['type'] == "embed") {
        ?>
        <video id="mainVideo" style="display: none; height: 0;width: 0;" ></video>
        <iframe style="width: 100%; height: 100%;"  class="embed-responsive-item" src="<?php
        echo parseVideos($video['videoLink']);
        if ($autoplay) {
            echo "?autoplay=1";
        }
        ?>"></iframe>
        <script>
            $(document).ready(function () {
                addView(<?php echo $video['id']; ?>, 0);
            });
        </script>
        <?php
    } else if ($video['type'] == "audio" && !file_exists("{$global['systemRootPath']}videos/{$video['filename']}.mp4")) {
        ?>
        <audio style="width: 100%; height: 100%;"  id="mainAudio" <?php echo $controls; ?> <?php echo $loop; ?> class="center-block video-js vjs-default-skin vjs-big-play-centered"  id="mainAudio"  data-setup='{ "fluid": true }'
               poster="<?php echo $global['webSiteRootURL']; ?>view/img/recorder.gif">
                   <?php
                   $ext = "";
                   if (file_exists($global['systemRootPath'] . "videos/" . $video['filename'] . ".ogg")) {
                       ?>
                <source src="<?php echo $global['webSiteRootURL']; ?>videos/<?php echo $video['filename']; ?>.ogg" type="audio/ogg" />
                <a href="<?php echo $global['webSiteRootURL']; ?>videos/<?php echo $video['filename']; ?>.ogg">horse</a>
                <?php
                $ext = ".ogg";
            } else {
                ?>
                <source src="<?php echo $global['webSiteRootURL']; ?>videos/<?php echo $video['filename']; ?>.mp3" type="audio/mpeg" />
                <a href="<?php echo $global['webSiteRootURL']; ?>videos/<?php echo $video['filename']; ?>.mp3">horse</a>
                <?php
                $ext = ".mp3";
            }
            ?>
        </audio>
        <script>
            $(document).ready(function () {
                addView(<?php echo $video['id']; ?>, this.currentTime());
            });
        </script>
        <?php
    } else if ($video['type'] == "linkVideo") {
        ?>
        <video style="width: 100%; height: 100%; position: fixed; top: 0; <?php echo $objectFit; ?>" playsinline webkit-playsinline poster="<?php echo $poster; ?>" <?php echo $controls; ?> <?php echo $loop; ?>   <?php echo $mute; ?> 
               class="video-js vjs-default-skin vjs-big-play-centered <?php echo $vjsClass; ?> " id="mainVideo">
            <source src="<?php echo $video['videoLink']; ?>" type="<?php echo (strpos($video['videoLink'], 'm3u8') !== false) ? "application/x-mpegURL" : "video/mp4" ?>" >
            <?php
                if (function_exists('getVTTTracks')) {
                    echo "<!-- getVTTTracks 2 -->";
                    echo getVTTTracks($video['filename']);
                }
            ?>
            <p><?php echo __("If you can't view this video, your browser does not support HTML5 videos"); ?></p>
        </video>

        <?php
        // the live users plugin
        if (empty($modestbranding) && AVideoPlugin::isEnabled("0e225f8e-15e2-43d4-8ff7-0cb07c2a2b3b")) {

            require_once $global['systemRootPath'] . 'plugin/VideoLogoOverlay/VideoLogoOverlay.php';
            $style = VideoLogoOverlay::getStyle();
            $url = VideoLogoOverlay::getLink();
            ?>
            <div style="<?php echo $style; ?>" class="VideoLogoOverlay">
                <a href="<?php echo $url; ?>"  target="_blank">
                    <img src="<?php echo $global['webSiteRootURL']; ?>videos/logoOverlay.png" alt="Logo"  class="img-responsive col-lg-12 col-md-8 col-sm-7 col-xs-6">
                </a>
            </div>
            <?php
        }
        ?>
        <script>
    <?php
    $onPlayerReady = "player.on('play', function () {addView({$video['id']}, this.currentTime());});";
    $onPlayerReady .= "player.on('timeupdate', function () {
var time = Math.round(this.currentTime());
var url = '" . Video::getURLFriendly($video['id']) . "';
if (url.indexOf('?') > -1) {
url += '&t=' + time;
} else {
url += '?t=' + time;
}
$('#linkCurrentTime').val(url);
if (time >= 5 && time % 5 === 0) {
addView({$video['id']}, time);
}
});";

    if ($autoplay) {
        $onPlayerReady .= "playerPlay({$currentTime});";
    } else {
        $onPlayerReady .= "setCurrentTime({$currentTime});";
    }
    $onPlayerReady .= "player.on('ended', function () {console.log(\"Finish Video\");
var time = Math.round(this.currentTime());
addView({$video['id']}, time);";
    $onPlayerReady .= "});";

    echo PlayerSkins::getStartPlayerJS($onPlayerReady);
    ?>
        </script>
        <?php
    } else {
        ?>
        <video style="width: 100%; height: 100%; position: fixed; top: 0; <?php echo $objectFit; ?>" playsinline webkit-playsinline poster="<?php echo $poster; ?>" <?php echo $controls; ?> <?php echo $loop; ?>   <?php echo $mute; ?> 
               class="video-js vjs-default-skin vjs-big-play-centered <?php echo $vjsClass; ?> " id="mainVideo">
                   <?php
                   echo getSources($video['filename']);
                   ?>
            <p><?php echo __("If you can't view this video, your browser does not support HTML5 videos"); ?></p>
        </video>

        <?php
        // the live users plugin
        if (empty($modestbranding) && AVideoPlugin::isEnabled("0e225f8e-15e2-43d4-8ff7-0cb07c2a2b3b")) {

            require_once $global['systemRootPath'] . 'plugin/VideoLogoOverlay/VideoLogoOverlay.php';
            $style = VideoLogoOverlay::getStyle();
            $url = VideoLogoOverlay::getLink();
            ?>
            <div style="<?php echo $style; ?>" class="VideoLogoOverlay">
                <a href="<?php echo $url; ?>"  target="_blank">
                    <img src="<?php echo $global['webSiteRootURL']; ?>videos/logoOverlay.png" alt="Logo"  class="img-responsive col-lg-12 col-md-8 col-sm-7 col-xs-6">
                </a>
            </div>
            <?php
        }
        ?>

        <script><?php
    $onPlayerReady = "";
    $onPlayerReady = "player.on('play', function () {addView({$video['id']}, this.currentTime());});";
    $onPlayerReady .= "player.on('timeupdate', function () {
var time = Math.round(this.currentTime());
var url = '" . Video::getURLFriendly($video['id']) . "';
if (url.indexOf('?') > -1) {
url += '&t=' + time;
} else {
url += '?t=' + time;
}
$('#linkCurrentTime').val(url);
if (time >= 5 && time % 5 === 0) {
addView({$video['id']}, time);
}
});";

    if ($autoplay) {
        $onPlayerReady .= "playerPlay({$currentTime});";
    } else {
        $onPlayerReady .= "setCurrentTime({$currentTime});";
    }
    $onPlayerReady .= "player.on('ended', function () {console.log(\"Finish Video\");
var time = Math.round(this.currentTime());
addView({$video['id']}, time);";
    $onPlayerReady .= "});";

    echo PlayerSkins::getStartPlayerJS($onPlayerReady);
    ?>
        </script>
        <?php
    }
    if (empty($playerSkinsObj->disableEmbedTopInfo)) {
        ?>
        <div id="topInfo" style="display: none;">
            <a href="<?php echo $url; ?>" target="_blank">
                <img src="<?php echo $photo; ?>" class="img img-responsive img-circle" style="" alt="User Photo"> 
                <div style="" class="topInfoTitle">    
                    <?php echo $title; ?>
                </div>
            </a>
            <span id="blockUserTop">
                <?php
                echo User::getblockUserButton($video['users_id']);
                ?>
            </span>
        </div>
        <?php
    }
    ?>
    <script src="<?php echo $global['webSiteRootURL']; ?>view/js/video.js/video.min.js" type="text/javascript"></script>
    <?php
    echo AVideoPlugin::afterVideoJS();
    $jsFiles = array();
    $jsFiles[] = "view/bootstrap/js/bootstrap.min.js";
    $jsFiles[] = "view/js/BootstrapMenu.min.js";
    $jsFiles[] = "view/js/seetalert/sweetalert.min.js";
    $jsFiles[] = "view/js/bootpag/jquery.bootpag.min.js";
    $jsFiles[] = "view/js/bootgrid/jquery.bootgrid.js";
    $jsFiles[] = "view/bootstrap/bootstrapSelectPicker/js/bootstrap-select.min.js";
    $jsFiles[] = "view/js/script.js";
    $jsFiles[] = "view/js/js-cookie/js.cookie.js";
    $jsFiles[] = "view/css/flagstrap/js/jquery.flagstrap.min.js";
    $jsFiles[] = "view/js/jquery.lazy/jquery.lazy.min.js";
    $jsFiles[] = "view/js/jquery.lazy/jquery.lazy.plugins.min.js";
    $jsFiles[] = "view/js/jquery-ui/jquery-ui.min.js";
    $jsFiles[] = "view/js/jquery-toast/jquery.toast.min.js";
    $jsURL = combineFiles($jsFiles, "js");
    ?>
    <script src="<?php echo $global['webSiteRootURL']; ?>view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="<?php echo $jsURL; ?>" type="text/javascript"></script>
    <?php
    echo AVideoPlugin::getFooterCode();
    include $global['systemRootPath'] . 'plugin/PlayerSkins/contextMenu.php';
    ?>
    <textarea id="elementToCopy" style="
              filter: alpha(opacity=0);
              -moz-opacity: 0;
              -khtml-opacity: 0;
              opacity: 0;
              position: absolute;
              z-index: -9999;
              top: 0;
              left: 0;
              pointer-events: none;"></textarea>
    <script>
            var topInfoTimeout;
            $(document).ready(function () {
                setInterval(function () {
                    if (!player.paused() && (!$('.vjs-control-bar').is(":visible") || $('.vjs-control-bar').css('opacity') == "0")) {
                        $('#topInfo').fadeOut();
                    } else {
                        $('#topInfo').fadeIn();
                    }

                }, 200);

                $("iframe, #topInfo").mouseover(function (e) {
                    clearTimeout(topInfoTimeout);
                    $('#mainVideo').addClass("vjs-user-active");
                });

                $("iframe").mouseout(function (e) {
                    topInfoTimeout = setTimeout(function () {
                        $('#mainVideo').removeClass("vjs-user-active");
                    }, 500);
                });

            });
    </script>
</body>
</html>

<?php
include $global['systemRootPath'] . 'objects/include_end.php';
?>
