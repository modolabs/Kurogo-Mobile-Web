{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" inputName="search_terms"}

{block name="resultCount"}
{/block}

{include file="findInclude:modules/{$moduleID}/include/stories.tpl"}

{include file="findInclude:common/footer.tpl"}
