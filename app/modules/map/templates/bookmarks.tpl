{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <a name="places"> </a>
  <h3>{"MAP_BOOKMARKED_PLACES_TITLE"|getLocalizedString}</h3>
</div>
{if $navItems}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems}
{else}
  {block name="noResults"}
    <ul class="nav">
      <li>{"NO_RESULTS"|getLocalizedString}</li>
    </ul>
  {/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
