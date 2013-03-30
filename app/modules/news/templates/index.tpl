{include file="findInclude:common/templates/header.tpl" scalable=false}

{capture name="categorySelect" assign="categorySelect"}
  <select class="newsinput" id="section" name="section" onchange="loadSection(this);">
    {foreach $sections as $section}
      {if $section['selected']}
        <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
      {else}
        <option value="{$section['value']}">{$section['title']|escape}</option>
      {/if}
    {/foreach}
  </select>
{/capture}


{block name="newsHeader"}
  {if count($sections) > 1}
    <div class="header">
      <div id="category-switcher" class="category-mode">
        <form method="get" action="/{$configModule}/index" id="category-form">
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">{"SECTION_TEXT"|getLocalizedString}</td>
              <td class="inputfield"><div id="news-category-select">{$categorySelect}</div></td>
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
          <table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="formlabel">{"SEARCH"|getLocalizedString}</td>
              <td class="inputfield">
                <input type="hidden" name="section" value="{$currentSection.value}" />
                <input class="newsinput search-field" type="text" id="search_terms" 
                name="filter" value="{$searchTerms|escape}" 
                onKeyPress="return submitenter(this, event);"/>
              </td>
              <td class="togglefield">
                <input type="button" class="toggle-search-button" onclick="return toggleSearch();" value="Cancel" />
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
{block name="stories"}
{include file="findInclude:modules/news/templates/stories.tpl"}
{/block}

{block name="newsFooter"}
  {include file="findInclude:common/templates/footer.tpl"}
{/block}
