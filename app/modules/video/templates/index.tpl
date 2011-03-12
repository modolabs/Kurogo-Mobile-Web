{include file="findInclude:common/templates/header.tpl"}

{if isset($showUnsupported)}
 Sorry, unsupported device.
{else}

{capture name="categorySelect" assign="categorySelect"}
  <select class="input" id="section" name="section" onchange="loadSection(this);">
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
        <form method="get" action="index.php" id="category-form">
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">Section:</td>
              <td class="inputfield"><div id="video-category-select">{$categorySelect}</div></td>
              <td class="togglefield">
                {block name="categoryButton"}
                  <input src="/common/images/search_button.png" type="image" class="toggle-search-button"  onclick="return toggleSearch();" />
                {/block}
              </td>
            </tr>
          </table>
          {foreach $hiddenArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          {foreach $breadcrumbSamePageArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
        </form>
  
        <form method="get" action="search.php" id="search-form">
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">Search:</td>
              <td class="inputfield">
                <input class="videoinput search-field" type="text" id="search_terms" 
                name="filter" value="{$searchTerms|escape}" 
                onKeyPress="return submitenter(this, event);"/>
              </td>
              <td class="togglefield">
                <input type="button" class="toggle-search-button" onclick="return toggleSearch();" value="Cancel" />
              </td>
            </tr>
          </table>
          {foreach $hiddenArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          {foreach $breadcrumbArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
        </form>
      </div>
    </div>
  {else}
    {include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {/if}
{/block}

  {if $previousURL}
      <a href="{$previousURL}">Previous <<<</a>
  {/if}

{include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}

  {if $nextURL}
      <a href="{$nextURL}">Next >>></a>
  {/if}

{if isset($totalItems)}
<p class="total_results"> Total: {$totalItems}</p>
{/if}

{/if}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}