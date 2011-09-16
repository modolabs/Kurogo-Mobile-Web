{include file="findInclude:common/templates/header.tpl"}

{block name="bookmarkHeader"}
  <div class="nonfocal">
    <a name="videos"> </a>
    <h3>{"BOOKMARK_TITLE"|getLocalizedString}</h3>
  </div>
{/block}

{if count($videos)}
  {block name="bookmarks"}
    {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {/block}
{else}
<div class="nonfocal">
  No bookmarked videos
</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
