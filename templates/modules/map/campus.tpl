{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$browseHint}</h2>
</div>

{include file="findInclude:common/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}

{include file="findInclude:common/navlist.tpl" navlistItems=$categories}

{if $hasBookmarks}
{include file="findInclude:common/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

<div class="nonfocal">
  <form action="category.php" method="get">
    <select name="category" onchange="this.parentNode.submit();">
        <option value="" selected="selected">Browse {$title} by:</option>
      {foreach $categories as $category}
        <option value="{$category['id']}">{$category['title']}</option>
      {/foreach}
    </select>
  </form>
</div>

{include file="findInclude:common/footer.tpl"}
