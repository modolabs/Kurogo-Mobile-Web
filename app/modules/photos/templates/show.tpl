{include file="findInclude:common/templates/header.tpl"}

<img class="fullimage" src="{$photo->getLUrl()}" />

<div class="nonfocal smallprint">
	<p>{$photo->getTitle()}<br/>
	{$photo->getAuthorName()}<br/>
	{$photo->getDateTaken()->format("F j, Y")}</p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>

{include file="findInclude:common/templates/footer.tpl"}
