{include file="findInclude:common/templates/header.tpl"}

<div class="{if $showDescription}hasDescription{else}noDescription{/if}">
{if $prevURL || $nextURL}
	<div class="sidenav2">
		{if $prevURL && $prev}
		<a href="{$prevURL}" class="sidenav-prev"><span class="sidenav-label">Previous</span>&nbsp;</a>
		{/if}

		{if $nextURL && $next}
		<a href="{$nextURL}" class="sidenav-next">&nbsp;<span class="sidenav-label">Next</span></a>
		{/if}
	</div>
{/if}
<div class="photo-wrapper">
<div class="fullimage-wrapper">
  <img class="fullimage" src="{$photoURL}" />
</div>
</div>
{block name="caption"}
<div class="caption">
	<h1 class="slugline">{$photoTitle}</h1>
	<p class="fineprint">
	{if $showAuthor}
	  {$photoAuthor}
	{/if}
	{if $showAuthor && $showDate} 
	  &nbsp;&nbsp; | &nbsp;&nbsp; 
	{/if}
	{if $showDate}
	  {$photoDate}
	{/if}
  </p>
  {if $showDescription}
    <div class="description">{$photoDescription}</div>
  {/if}
</div>
{/block}
</div>
{include file="findInclude:common/templates/footer.tpl"}
