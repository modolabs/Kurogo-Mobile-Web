{extends file="findExtends:modules/video/templates/index.tpl"}

{block name="videoHeader"}
  {if count($sections) > 1}
    <table id="videoHeader">
      <tr><td id="categoryformcontainer">
        <form method="get" action="index.php">
          <fieldset>
            <label for="section" class="formlabel">{"SECTION_TEXT"|getLocalizedString}</label>
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
        <form method="get" action="search">
          {include file="findInclude:common/templates/search.tpl" insideForm=true extraArgs=$hiddenArgs}
        </form>
      </td></tr>
    </table>
  {else}
  <div id="videoHeader">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}
  </div>
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
