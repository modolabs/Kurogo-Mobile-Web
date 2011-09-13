{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" resultCount=$resultCount extraArgs=$hiddenArgs}
{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

{include file="findInclude:common/templates/footer.tpl"}

