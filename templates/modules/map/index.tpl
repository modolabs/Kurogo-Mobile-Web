{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" placeholder="Search Map" tip=$searchTip}


{if $bookmarks}
<div class="nonfocal">
  <h3>Bookmarked Locations</h3>
</div>
{include file="findInclude:common/navlist.tpl" navlistItems=$bookmarks}
{/if}

<div class="nonfocal">
  <h3>{$browseHint}</h3>
</div>

{include file="findInclude:common/navlist.tpl" navlistItems=$categories}

{include file="findInclude:common/footer.tpl"}
