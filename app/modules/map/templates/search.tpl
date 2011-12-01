{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder={"MAP_SEARCH_PLACEHOLDER"|getLocalizedString} emphasized=false}

{if $poweredByGoogle}
  {block name="poweredByGoogle"}
  <div>
    <img src="/modules/map/images/powered-by-google-on-white.png"/>
  </div>
  {/block}
{/if}

{include file="findInclude:common/templates/results.tpl" results=$places}

{include file="findInclude:common/templates/footer.tpl"}
