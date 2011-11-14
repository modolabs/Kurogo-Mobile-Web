{include file="findInclude:common/templates/header.tpl" scalable=false}

{include file="findInclude:common/templates/search.tpl" emphasized=false}

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
