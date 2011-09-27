{include file="findInclude:common/templates/header.tpl"}

{block name="videoHeader"}
  {include file="findInclude:common/templates/search.tpl" resultCount=$resultCount extraArgs=$hiddenArgs}
{/block}

{block name="videos"}
  {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
{/block}

{include file="findInclude:common/templates/footer.tpl"}

