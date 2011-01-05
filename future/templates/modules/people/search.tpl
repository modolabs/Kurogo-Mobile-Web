{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" placeholder="Search" emphasized=false inlineSearchError=$searchError}
{/block}

{include file="findInclude:common/results.tpl" results=$results accessKey=false}

{include file="findInclude:common/footer.tpl"}

