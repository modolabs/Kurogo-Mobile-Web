{$resultsList = $federatedSearchResults['items']}
{if !count($federatedSearchResults['items'])}
  {$noResults = array()}
  {$noResults['title'] = "NO_RESULTS"|getLocalizedString}
  {$resultsList[] = $noResults}

{elseif $federatedSearchResults['total'] > count($federatedSearchResults['items'])}
  {$moreLink = array()}
  {$moreLink['title'] = "More results"}
  {$moreLink['url'] = $federatedSearchResults['url']}
  {$resultsList[] = $moreLink}
{/if}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$resultsList subTitleNewline=true}
