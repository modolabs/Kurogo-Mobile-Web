{extends file="findExtends:modules/home/templates/search.tpl"}

{block name="searchResults"}
  {include file="findInclude:modules/home/templates/searchResult.tpl" federatedSearchResults=$federatedSearchModule['results']}
{/block}
