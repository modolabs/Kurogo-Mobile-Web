{include file="findInclude:common/header.tpl"}


<div class="header">
  <div id="category-switcher">
    {if $isHome}
      <form method="get" id="category-form">
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="formlabel">Section:</td>
            <td class="inputfield">
              <div>
                <select class="newsinput" id="category_id" name="category_id" onchange="loadCategory(this);">
                  {foreach $categories as $id => $title}
                    {if $newsURLCategoryId == $id}
                        <option value="{$id}" selected="true">{$title}</option>
                    {else}
                        <option value="{$id}">{$title}</option>
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
    {/if}
    <form id="search-form"{if $isHome} style="display:none;"{/if}>
      {if $isHome}
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="formlabel">Search:</td>
            <td class="inputfield">
              <input class="newsinput search-field" type="text" id="search_terms" name="search_terms"/>
            </td>
            <td class="togglefield">
              <input type="button" class="toggle-search-button"  onclick="return toggleSearch();" value="Cancel" />
            </td>
          </tr>
        </table>
        {$hiddenHomeStateForm}
      {else}
        <fieldset class="inputcombo">
          <input class="forminput" type="text" id="filter" name="filter" placeholder="Search" value="{$searchTerms|escape}"/>
          <input class="combobutton" id="sch_btn" src="/common/images/search-button.png" type="image" />
          {$breadcrumbInputs|default:''}
        </fieldset>	
      {/if}
    </form>
  </div>
</div>

<ul class="results">
  {if $previousUrl}
    <li class="non-story">
      <a href="{$previousUrl}">Previous stories</a>
    </li>
  {/if}

  {$ellipsisCount=0}
  {foreach $stories as $story}
    <li class="story">
      {if isset($story['image'])}
        <img class="thumbnail" src="{$story['image']['url']}" />
      {else}
        <img class="thumbnail" src="/common/images/news-placeholder.png" />
      {/if}
      <a href="{$story['url']}">
        <div class="ellipsis" id="ellipsis_{$ellipsisCount++}">
          <div class="title">{$story["title"]}</div>
          {$story['description']}
        </div>
      </a>
    </li>
  {/foreach}

  {if $nextUrl}
    <li class="non-story">
      <a href="{$nextUrl}">More stories</a>
    </li>
  {/if}
</ul>

{include file="findInclude:common/footer.tpl"}
