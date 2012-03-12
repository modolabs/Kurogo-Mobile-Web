{include file="findInclude:common/templates/header.tpl"}

<div class="fullimage-wrapper">
<img class="fullimage" src="{$photoURL}" />
</div>

<div class="nonfocal">
	<h1 class="slugline">{$photoTitle}</h1>
	<p class="fineprint">
	{if $photoDescription}
		<br/>
		{$photoDescription}
	{/if}
	{$photoAuthor}<br/>
	{if $photoDate}
		<br/>
    	{$photoDate}
    {/if}
    </p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>

{include file="findInclude:common/templates/footer.tpl"}
