{block name="headersection"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" placeholder="Search" resultCount=$resultCount tip="You can search by part or all of a person's name, email address or phone number."}
{/block}

{include file="findInclude:common/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}

{include file="findInclude:common/footer.tpl"}
