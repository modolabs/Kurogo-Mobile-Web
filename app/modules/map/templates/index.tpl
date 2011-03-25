{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search Map" tip=$searchTip}

<div class="nonfocal">
  <h3>{$browseHint}</h3>
</div>

{if $bookmarkStatus}
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
{/if}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$categories}

{if $clearLink}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$clearLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
