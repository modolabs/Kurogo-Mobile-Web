{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search Map" tip=$searchTip}

<div class="nonfocal">
  <h2>{$browseHint}</h2>
</div>

{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$categories}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
