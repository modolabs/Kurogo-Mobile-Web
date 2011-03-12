{include file="findInclude:common/templates/header.tpl"}

{if isset($showUnsupported)}
 Sorry, unsupported device.
{else}

{include file="findInclude:common/templates/search.tpl" placeholder="Search" resultCount=$resultCount extraArgs=$hiddenArgs}
{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

{/if}

{include file="findInclude:common/templates/footer.tpl"}

