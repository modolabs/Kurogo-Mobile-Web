{include file="findInclude:common/header.tpl" scalable=false}

{block name="newsHeader"}
  <div class="header">
    <div id="category-switcher">
      <form method="get" id="category-form">
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="formlabel">Section:</td>
            <td class="inputfield">
              <div>
                <select class="newsinput" id="section" name="section" onchange="loadSection(this);">
                  {foreach $sections as $section}
                    {if $section['selected']}
                      <option value="{$section['value']}" selected="true">{$section['title']}</option>
                    {else}
                      <option value="{$section['value']}">{$section['title']}</option>
                    {/if}
                  {/foreach}
                </select>
              </div>
            </td>
            <td class="togglefield">
              <input src="/common/images/search_button.png" type="image" class="toggle-search-button"  onclick="return toggleSearch();" />
            </td>
          </tr>
        </table>
      </form>
      <form action="search.php" id="search-form" style="display:none;">
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="formlabel">Search:</td>
            <td class="inputfield">
              <input class="newsinput search-field" type="text" id="search_terms" name="filter" value="{$searchTerms|escape}"/>
            </td>
            <td class="togglefield">
              <input type="button" class="toggle-search-button"  onclick="return toggleSearch();" value="Cancel" />
            </td>
          </tr>
        </table>
        {foreach $hiddenArgs as $arg => $value}
          <input type="hidden" name="{$arg}" value="{$value}" />
        {/foreach}
      </form>
    </div>
  </div>
{/block}

{include file="findInclude:modules/{$moduleID}/common/stories.tpl"}

{block name="newsFooter"}
  {include file="findInclude:common/footer.tpl"}
{/block}
