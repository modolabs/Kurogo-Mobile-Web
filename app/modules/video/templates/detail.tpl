{include file="findInclude:common/templates/header.tpl"}

<div class="video">
	<h2>{$videoTitle}</h2>

	<div class="videoplayer">
	{block name="videoPlayer"}{/block}
	</div>

	<div class="videodescription">
		<div class="videobuttons">
		  {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
		  {include file="findInclude:common/templates/share.tpl" shareURL=$videoURL shareRemark=$shareRemark shareEmailURL=$shareEmailURL}
		</div>

		{$videoDescription}
	</div>
</div>

{include file="findInclude:common/templates/footer.tpl"}