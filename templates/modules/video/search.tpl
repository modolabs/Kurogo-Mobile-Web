{include file="findInclude:common/header.tpl"}

{if isset($showUnsupported)}
 Sorry, unsupported device.
{else}

{if isset($doSearch)}
 {include file="findInclude:common/search.tpl" placeholder="Search" resultCount=$resultCount}
{/if}
{include file="findInclude:common/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

{/if}

{include file="findInclude:common/footer.tpl"}

