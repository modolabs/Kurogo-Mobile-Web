{include file="findInclude:common/header.tpl" scalable=false}

{include file="findInclude:common/search.tpl" extraArgs=$extraArgs}

{if count($stories)}
  {include file="findInclude:modules/{$moduleID}/common/stories.tpl"}
{else}
  <div class="nonfocal">
    No stories found
  </div>
{/if}

{include file="findInclude:common/footer.tpl"}
