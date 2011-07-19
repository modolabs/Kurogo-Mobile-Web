{extends file="findExtends:modules/video/templates/detail-youtube.tpl"}

{block name="videoPlayer"}
<a class="videoLink" href="{$videoStreamingURL}">
<div class="playButton"><div></div></div>
<img src="{$videoStillImage}" />
</a>
{/block}
