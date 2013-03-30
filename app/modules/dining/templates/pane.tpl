<div id="locations">
{foreach $groupedLocations as $locationGroup}
	{if $locationGroup}
		{include file="findInclude:common/templates/navlist.tpl" navListHeading=$locationGroup['title'] navlistItems=$locationGroup['items'] subTitleNewline=true}
	{/if}
{/foreach}
</div>