{extends file="findExtends:modules/video/templates/index.tpl"}

{block name="videoHeader"}
  <table id="videoHeader" class="section-search">
    <tr>
    {if count($sections) > 1}
      <td id="categoryformcontainer">
        <form method="get" action="/{$configModule}/index">
          <fieldset>
            <label for="section" class="formlabel">{"SECTION_TEXT"|getLocalizedString}</label>
            {$categorySelect}
            
            {foreach $hiddenArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value|escape}" />
            {/foreach}
            {foreach $breadcrumbSamePageArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value|escape}" />
            {/foreach}
          </fieldset>
        </form>
      </td>
    {/if}
      <td id="searchformcontainer">
        <form method="get" action="/{$configModule}/search">
          <input type="hidden" name="feed" value="{$currentSection}" />
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
  {capture name="splitviewList" assign="splitviewList"}
    {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {/capture}
  {$splitview = array()}
  {$splitview['id'] = "tabletVideos"}
  {$splitview['class'] = "splitview-stories"}
  {$splitview['list'] = $splitviewList}
  {include file="findInclude:common/templates/splitview.tpl" splitview=$splitview}
{/block}
