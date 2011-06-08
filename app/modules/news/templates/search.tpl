{include file="findInclude:common/templates/header.tpl" scalable=false}

{include file="findInclude:common/templates/search.tpl" extraArgs=$extraArgs}

{if count($stories)}
  {include file="findInclude:modules/news/templates/stories.tpl"}
{else}
  <div class="nonfocal">
    No stories found
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
