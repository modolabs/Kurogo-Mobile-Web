{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}
<iframe class="youtube-player" type="text/html" width="298" height="200" src="http://www.youtube.com/embed/{$videoid}" frameborder="0">
</iframe>
{/block}
