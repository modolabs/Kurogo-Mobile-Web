{include file="findInclude:common/templates/header.tpl"}

<img class="fullimage" src="{$photoURL}" />

<div class="nonfocal smallprint">
	<h2>{$photoTitle}</h2>
	<p>
	{$photoAuthor}<br/>
    {$photoDate}
    </p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>

{include file="findInclude:common/templates/footer.tpl"}
