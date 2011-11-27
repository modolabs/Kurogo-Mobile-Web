{include file="findInclude:common/templates/header.tpl"}

<img class="fullimage" src="{$photoURL}" />

<div class="nonfocal smallprint">
	<p>{$photoTitle}<br/>
	{$photoAuthor}<br/>
    {$photoDate}</p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>

{include file="findInclude:common/templates/footer.tpl"}
