{include file="findInclude:common/header.tpl" scalable=false}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" emphasized=false placeholder="Search $SITE_NAME"}
{/block}

{foreach $federatedResults as $federatedResult}
  <h3 class="nonfocal">{$federatedResult['title']}</h3>
  {$results = $federatedResult['results']}
  {if !count($results)}
    {$noResults = array()}
    {$noResults['title'] = "No results"}
    {$results[] = $noResults}    
  {elseif $federatedResult['total'] > count($results)}
    {$moreLink = array()}
    {$moreLink['title'] = "More results"}
    {$moreLink['url'] = $federatedResult['url']}
    {$results[] = $moreLink}
  {/if}
  {include file="findInclude:common/navlist.tpl" navlistItems=$results subTitleNewline=true}
{/foreach}


{include file="findInclude:common/footer.tpl"}
