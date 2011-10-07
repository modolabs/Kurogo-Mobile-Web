{include file="findInclude:common/templates/header.tpl"}

{block name="videoHeader"}
  {include file="findInclude:common/templates/search.tpl" resultCount=$resultCount extraArgs=$hiddenArgs}
{/block}

{if count($videos)}
  {block name="videos"}
    {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {/block}
{else}
  <div class="nonfocal">
    {"NO_RESULTS"|getLocalizedString}
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}

