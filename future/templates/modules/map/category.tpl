{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>Browse Buildings: {$title}</h2>
</div>

{include file="findInclude:common/results.tpl" results=$places}

<div class="nonfocal">
  <form action="category.php" method="get">
    <select name="category" onchange="this.parentNode.submit();">
        <option value="" selected="selected">Browse map by:</option>
      {foreach $categories as $category}
        <option value="{$category['id']}">{$category['title']}</option>
      {/foreach}
    </select>
  </form>
</div>

{include file="findInclude:common/footer.tpl"}
