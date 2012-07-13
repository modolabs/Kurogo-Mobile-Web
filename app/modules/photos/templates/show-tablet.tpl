{include file="findInclude:common/templates/header.tpl"}

{if $prevURL || $nextURL}
	<div class="sidenav2">
		{if $prevURL && $prev}
		<a href="{$prevURL}" class="sidenav-prev">&nbsp;</a>
		{/if}

		{if $nextURL && $next}
		<a href="{$nextURL}" class="sidenav-next">&nbsp;</a>
		{/if}
	</div>
{/if}

<div class="fullimage-wrapper">
<img class="fullimage" src="{$photoURL}" />
</div>
{block name="caption"}
<div class="nonfocal caption">
	<h1 class="slugline">{$photoTitle}</h1>
	<p class="fineprint">
	{$photoAuthor} &nbsp;&nbsp; | &nbsp;&nbsp; 
	{$photoDate}
    </p>
	{include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>
{/block}
{include file="findInclude:common/templates/footer.tpl"}
