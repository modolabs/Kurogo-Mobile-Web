{include file="findInclude:common/templates/header.tpl"}

{* {include file="findInclude:modules/map/templates/searchbar.tpl"} *}

<div class="nonfocal">
  {block name="viewAllOnMap"}
  {if $mapURL}
  <div class="actionbuttons viewall">
    <div class="actionbutton"><a href="{$mapURL}" ontouchstart="this.className='pressedaction'" ontouchend="this.className=''"><img src="/modules/map/images/map-button-placemark.png" width="20" height="20" />{"VIEW_ALL_ON_MAP"|getLocalizedString}</a></div>
  </div>
  {/if}
  {/block}
  <h2>{$title}</h2>
</div>

{include file="findInclude:common/templates/results.tpl" results=$navItems}

{if $categories|@count>1}
<div class="nonfocal">
  <form action="/{$configModule}/category" method="get">
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
