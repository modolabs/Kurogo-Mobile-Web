{extends file="findExtends:modules/video/templates/detail-youtube.tpl"}

{block name="videoPlayer"}
<iframe class="youtube-player" type="text/html" width="298" height="200" scrolling="no" src="http://www.youtube.com/embed/{$videoid}?html5=1&controls=0&rel=0&hd=0&modestbranding=1&title=" frameborder="0">
</iframe>
{/block}
