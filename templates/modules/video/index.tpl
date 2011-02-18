{include file="findInclude:common/header.tpl"}

{if isset($doSearch)}
 {include file="findInclude:common/search.tpl" placeholder="Search" resultCount=$resultCount}
{/if}

{include file="findInclude:common/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

{include file="findInclude:common/footer.tpl"}