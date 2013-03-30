{include file="findInclude:common/templates/header.tpl"}

{block name="description"}
{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}
{/block}
<div id="locations">
{foreach $groupedLocations as $locationGroup}
	{if $locationGroup}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$locationGroup['title'] navlistItems=$locationGroup['items'] subTitleNewline=true}
	{/if}
{/foreach}
</div>
{include file="findInclude:common/templates/footer.tpl"}
