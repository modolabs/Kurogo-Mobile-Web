{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl"}
{/block}

{block name="resultCount"}{/block}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{include file="findInclude:common/footer.tpl"}
