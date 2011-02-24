{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" placeholder="Search Map" tip=$searchTip}

<div class="nonfocal">
  <h3>{$browseHint}</h3>
</div>

{include file="findInclude:common/navlist.tpl" navlistItems=$categories}

{if $hasBookmarks}
{include file="findInclude:common/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/footer.tpl"}
