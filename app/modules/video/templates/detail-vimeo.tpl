{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}
<iframe src="http://player.vimeo.com/video/{$videoid}" width="298" height="200" frameborder="0"></iframe>
{/block}
