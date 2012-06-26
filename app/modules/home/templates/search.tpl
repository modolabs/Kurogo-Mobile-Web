{include file="findInclude:common/templates/header.tpl" scalable=false}

{if $showFederatedSearch}
{include file="findInclude:common/templates/search.tpl" emphasized=false}
{else}
<div class="focal">{"FEDERATED_SEARCH_DISABLED"|getLocalizedString}</div>
{/if}

{foreach $federatedSearchModules as $federatedSearchModule}
  <h3 class="nonfocal">{$federatedSearchModule['title']}</h3>
  <div id="{$federatedSearchModule['elementId']}">
    {block name="searchResults"}
      <div class="focal">
        <div class="loading">Loading...</div>
      </div>
    {/block}
  </div>
{/foreach}


{include file="findInclude:common/templates/footer.tpl"}
