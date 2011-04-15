{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}

    <!-- If your page already includes jQuery you can skip this step -->
    <script type="text/javascript" src="kaltura-html5player-widget/jquery-1.4.2.min.js" ></script>
    <!-- Include the css and javascript  -->
    <link rel="stylesheet" href="kaltura-html5player-widget/skins/jquery.ui.themes/jquery-ui-1.7.2.custom.css"></link> 
    <link rel="stylesheet" href="kaltura-html5player-widget/mwEmbed-player-static.css"></link> 
    <script type="text/javascript" src="kaltura-html5player-widget/mwEmbed-player-static.js"></script>

    <video style="width:400px;height:300px" durationHint="32.2" >
      <source src="myH.264.mp4" />
      <source src="myOgg.ogg" />
    </video>
    
    {*
    <object id="kaltura_player" name="kaltura_player"
       type="application/x-shockwave-flash"
       allowFullScreen="true" allowNetworking="all"
       allowScriptAccess="always" height="330" width="400"
       data="http://www.kaltura.com/index.php/kwidget/cache_st/1274763304/wid/_243342/uiconf_id/48501/entry_id/0_swup5zao">
        <param name="allowFullScreen" value="true" />
        <param name="allowNetworking" value="all" />
        <param name="allowScriptAccess" value="always" />
        <param name="bgcolor" value="#000000" />
        <param name="flashVars" value="&" />
        <param name="movie" value="http://www.kaltura.com/index.php/kwidget/cache_st/1274763304/wid/_243342/uiconf_id/48501/entry_id/0_swup5zao" />
      </object>
      *}

{/block}