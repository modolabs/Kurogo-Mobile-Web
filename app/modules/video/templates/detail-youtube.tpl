{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}
<script type="text/javascript" src="http://www.youtube.com/player_api"></script>
<iframe id="ytplayer" class="youtube-player" type="text/html" width="298" height="200" scrolling="no" src="http://www.youtube.com/embed/{$videoid}?html5=1&enablejsapi=1" frameborder="0">
</iframe>
{/block}
