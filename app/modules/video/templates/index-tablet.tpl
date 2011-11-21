{extends file="findExtends:modules/video/templates/index.tpl"}

{block name="videoHeader"}
  <table id="videoHeader" class="section-search">
    <tr>
    {if count($sections) > 1}
      <td id="categoryformcontainer">
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
      </td>
    {/if}
      <td id="searchformcontainer">
        <form method="get" action="search">
          {include file="findInclude:common/templates/search.tpl" insideForm=true extraArgs=$hiddenArgs}
        </form>
      </td>
      <td id="bookmarkscontainer"{if !$hasBookmarks} style="display:none"{/if}>
        <a id="bookmarkslink" href="{$bookmarkLink[0]['url']}"><div></div></a>
      </td>
    </tr>
  </table>
{/block}

{block name="bookmarks"}
{/block}

{block name="videos"}
<div id="tabletVideos" class="splitview">
<div id="videos" class="listcontainer">
{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
</div>
<div id="videoDetailWrapper" class="splitview-detailwrapper">
<div id="videoDetail">
</div><!-- videoDetail -->
</div><!-- videoDetailWrapper -->
</div><!-- tabletVideos -->
{/block}
