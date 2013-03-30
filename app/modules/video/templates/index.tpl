{include file="findInclude:common/templates/header.tpl"}

{capture name="categorySelect" assign="categorySelect"}
  <select class="input" id="section" name="feed" onchange="loadSection(this);">
    {foreach $sections as $section}
      {if $section['value']==$currentSection}
        <option value="{$section['value']}" selected="true">{$section['title']}</option>
      {else}
        <option value="{$section['value']}">{$section['title']}</option>
      {/if}
    {/foreach}
  </select>
{/capture}

{block name="videoHeader"}
  {if count($sections) > 1}
    <div class="header">
      <div id="category-switcher" class="category-mode">
        <form method="get" action="/{$configModule}/index" id="category-form">
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">{"SECTION_TEXT"|getLocalizedString}</td>
              <td class="inputfield"><div id="video-category-select">{$categorySelect}</div></td>
              <td class="togglefield">
                {block name="categoryButton"}
                  <input src="/common/images/search_button.png" type="image" class="toggle-search-button"  onclick="return toggleSearch();" width="32" height="30" />
                {/block}
              </td>
            </tr>
          </table>
          {foreach $hiddenArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value|escape}" />
          {/foreach}
          {foreach $breadcrumbSamePageArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value|escape}" />
          {/foreach}
        </form>
  
        <form method="get" action="/{$configModule}/search" id="search-form">
          <input type="hidden" name="feed" value="{$currentSection}" />
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">{"SEARCH"|getLocalizedString}</td>
              <td class="inputfield">
                <input class="videoinput search-field" type="text" id="search_terms" 
                name="filter" value="{$searchTerms|escape}" 
                onKeyPress="return submitenter(this, event);"/>
              </td>
              <td class="togglefield">
                {block name="searchCancelButton"}
                <input type="button" class="toggle-search-button" onclick="return toggleSearch();" value="Cancel" />
                {/block}
              </td>
            </tr>
          </table>
          {foreach $hiddenArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value|escape}" />
          {/foreach}
          {foreach $breadcrumbArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value|escape}" />
          {/foreach}
        </form>
      </div>
    </div>
  {else}
    {include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}
  {/if}
{/block}

{block name="bookmarks"}
  {if $hasBookmarks}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
  {/if}
{/block}

{block name="videos"}
  {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
{/block}
{include file="findInclude:common/templates/footer.tpl"}
