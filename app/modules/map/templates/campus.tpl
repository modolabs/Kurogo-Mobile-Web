{include file="findInclude:common/templates/header.tpl"}

{* {include file="findInclude:modules/map/templates/searchbar.tpl"} *}

<div class="nonfocal">
  <h2>{$browseHint}</h2>
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems navlistID="categories"}

{if $clearLink}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$clearLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
