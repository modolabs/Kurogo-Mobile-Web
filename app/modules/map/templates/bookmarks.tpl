{include file="findInclude:common/templates/header.tpl"}

{capture name="additionalInputs" assign="additionalInputs"}
  {if $campuses}
    {include file="findInclude:modules/map/templates/selectcampus.tpl" campuses=$campuses}
  {/if}
{/capture}

{include file="findInclude:common/templates/search.tpl"
         placeholder={"MAP_SEARCH_PLACEHOLDER"|getLocalizedString}}

{if $navItems}
<div class="nonfocal">
  <a name="places"> </a>
  <h3>{"MAP_BOOKMARKED_PLACES_TITLE"|getLocalizedString}</h3>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
