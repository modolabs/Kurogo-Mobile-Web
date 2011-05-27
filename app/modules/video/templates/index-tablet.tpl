{extends file="findExtends:modules/video/templates/index.tpl"}

{block name="videoHeader"}
  {if count($sections) > 1}
    <table id="videoHeader">
      <tr><td id="categoryformcontainer">
        <form method="get" action="index.php">
          <fieldset>
            <label for="section">Section:</label>
            {$categorySelect}
            
            {foreach $hiddenArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value}" />
            {/foreach}
            {foreach $breadcrumbSamePageArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value}" />
            {/foreach}
          </fieldset>
        </form>
      </td><td id="searchformcontainer">
        <form method="get" action="search.php">
          {include file="findInclude:common/templates/search.tpl" insideForm=true placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
        </form>
      </td></tr>
    </table>
  {else}
    {include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {/if}
{/block}

{block name="videos"}
<div id="tabletVideos">
<div id="videos">
{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
</div>
<div id="videoDetailWrapper">
<div id="videoDetail">
</div><!-- videoDetail -->
</div><!-- videoDetailWrapper -->
</div><!-- tabletVideos -->
{/block}
