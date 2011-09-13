{include file="findInclude:common/templates/header.tpl" scalable=false}

{block name="newsHeader"}
  {include file="findInclude:common/templates/search.tpl" extraArgs=$extraArgs}
{/block}

{if count($stories)}
  {block name="stories"}
    {include file="findInclude:modules/news/templates/stories.tpl"}
  {/block}
{else}
  <div class="nonfocal">
    No stories found
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
