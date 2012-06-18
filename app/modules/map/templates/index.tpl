{include file="findInclude:common/templates/header.tpl"}

{capture name="additionalInputs" assign="additionalInputs"}
  {if $campuses}
    {include file="findInclude:modules/map/templates/selectcampus.tpl" campuses=$campuses}
  {/if}
{/capture}

{block name="search"}
{include file="findInclude:common/templates/search.tpl"
         placeholder={"MAP_SEARCH_PLACEHOLDER"|getLocalizedString}}
{/block}        

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{if $campuses && $showAllCampuses}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$worldmapLink secondary=true}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems navlistID="categories" navListHeading=$browseHint subTitleNewline=true}

{include file="findInclude:common/templates/footer.tpl"}
