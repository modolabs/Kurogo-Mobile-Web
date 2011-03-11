{include file="findInclude:common/templates/header.tpl"}

{if isset($showUnsupported)}
 Sorry, unsupported device.
{else}

{if isset($doSearch)}
 {include file="findInclude:common/templates/search.tpl" placeholder="Search" resultCount=$resultCount}
{/if}
{*
{include file="findInclude:common/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
*}
{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

{/if}

{include file="findInclude:common/templates/footer.tpl"}

