{include file="findInclude:common/templates/header.tpl"}

{if $navItems}
<div class="nonfocal">
  <a name="places"> </a>
  <h3>{"MAP_BOOKMARKED_PLACES_TITLE"|getLocalizedString}</h3>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
