{include file="findInclude:common/templates/header.tpl"}

<div class="photo">
<div class="fullimage-wrapper">
<img class="fullimage" src="{$photoURL}" />
</div>
{if $prevURL || $nextURL}
	<div class="sidenav2">
		{if $prevURL && $prev}
		<a href="{$prevURL}" class="sidenav-prev">{$prev}</a>
		{/if}

		{if $nextURL && $next}
		<a href="{$nextURL}" class="sidenav-next">{$next}</a>
		{/if}
	</div>
{/if}
{block name="caption"}
<div class="nonfocal caption">
	<h1 class="slugline">{$photoTitle}</h1>
	<p class="fineprint">
	{if $showAuthor}
	  {$photoAuthor}
	  <br/>
	{/if}
	{if $showDate}
	  {$photoDate}
	  <br/>
	{/if}
  </p>
	{if $showDescription}
	  <div class="description">{$photoDescription}</div>
	{/if}
</div>
{/block}
</div>
{include file="findInclude:common/templates/footer.tpl"}
