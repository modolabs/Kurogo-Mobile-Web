{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/map/templates/searchbar.tpl"}

<div class="nonfocal">
  <!--
  <div class="buttonContainer">
    <a href="{$mapURL}">
      <div id="viewOnMapButton"
           ontouchstart="addClass(this, 'pressed')"
           ontouchend="removeClass(this, 'pressed')"{if $bookmarkStatus == "on"} class="on"{/if}>
          <img src="/modules/map/images/map-button-map.png" width="24" height="24" />
          {"VIEW_ALL_ON_MAP"|getLocalizedString}
      </div>
    </a>
  </div>
  -->
  <h2>{$title}</h2>
</div>

{include file="findInclude:common/templates/results.tpl" results=$navItems}

{if $categories|@count>1}
<div class="nonfocal">
  <form action="category.php" method="get">
    <select name="category" onchange="this.parentNode.submit();">
        <option value="" selected="selected">Browse map by:</option>
      {foreach $categories as $category}
        <option value="{$category['id']}">{$category['title']}</option>
      {/foreach}
    </select>
    {block name="categorysubmit"}{/block}
  </form>
</div>
{/if}

{if $clearLink}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$clearLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
