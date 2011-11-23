{include file="findInclude:common/templates/header.tpl"}

{if $campuses}
  {capture name="additionalInputs" assign="additionalInputs"}
    <div id="campus-select">
      <label for="group" class="formlabel">{"SEARCH_IN_GROUP"|getLocalizedString}</label>&nbsp;
      <select name="group">
      {foreach $campuses as $campus}
        <option value="{$campus['id']}">{$campus['title']}</option>
      {/foreach}
      </select>
    </div>
  {/capture}
{/if}

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
