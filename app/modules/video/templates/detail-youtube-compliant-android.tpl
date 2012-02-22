{extends file="findExtends:modules/video/templates/detail-youtube.tpl"}

{block name="videoPlayer"}
<script type="text/javascript" src="http://www.youtube.com/player_api"></script>
<iframe id="ytplayer" class="youtube-player" type="text/html" width="298" height="200" scrolling="no" src="http://www.youtube.com/embed/{$videoid}?html5=1&controls=0&rel=0&hd=0&enablejsapi=1&modestbranding=1&title=" frameborder="0">
</iframe>
{/block}
