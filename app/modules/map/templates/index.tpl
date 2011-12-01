{include file="findInclude:common/templates/header.tpl"}

{capture name="additionalInputs" assign="additionalInputs"}
  {if $campuses}
    {include file="findInclude:modules/map/templates/selectcampus.tpl" campuses=$campuses}
  {/if}
  {if $group}
    <input type="hidden" name="group" value="{$group}">
  {/if}
{/capture}

{include file="findInclude:common/templates/search.tpl"
         placeholder={"MAP_SEARCH_PLACEHOLDER"|getLocalizedString}}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{if $campuses}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$worldmapLink secondary=true}
{/if}

<div class="nonfocal">
  <h3>{$browseHint}</h3>
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$categories navlistID="categories"}

{if $clearLink}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$clearLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
