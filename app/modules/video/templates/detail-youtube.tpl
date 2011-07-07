{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}
{$forceHTML5 = ($pagetype == 'compliant' && $platform == 'android')}
<iframe class="youtube-player" type="text/html" width="298" height="200" scrolling="no" src="http://www.youtube.com/embed/{$videoid}?{if $forceHTML5}html5=1&controls=0&{/if}rel=0&hd=0&modestbranding=1&title=" frameborder="0">
</iframe>
{/block}
