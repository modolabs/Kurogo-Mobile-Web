{include file="findInclude:common/templates/header.tpl"}

<div class="fullimage-wrapper">
<img class="fullimage" src="{$photoURL}" />
</div>

<div class="nonfocal">
	<h1 class="slugline">{$photoTitle}</h1>
	<p class="fineprint">
	{$photoAuthor}<br/>
    {$photoDate}
    </p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>

{include file="findInclude:common/templates/footer.tpl"}
