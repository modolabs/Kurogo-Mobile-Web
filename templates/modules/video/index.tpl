{include file="findInclude:common/header.tpl"}

{if isset($showUnsupported)}
 Sorry, unsupported device.
{else}

{if isset($doSearch)}
 {include file="findInclude:common/search.tpl" placeholder="Search" resultCount=$resultCount}
{/if}


{capture name="categorySelect" assign="categorySelect"}
  <select class="input" id="section" name="section" onchange="loadSection(this);">
    {foreach $sections as $section}
      {if $section['selected']}
        <option value="{$section['value']}" selected="true">{$section['tag']}</option>
      {else}
        <option value="{$section['value']}">{$section['tag']}</option>
      {/if}
    {/foreach}
  </select>
{/capture}

<div id="video-category-select">{$categorySelect}</div>


  {if $prevURL}
      <a href="{$prevURL}">Previous <<<</a>
  {/if}
  {include file="findInclude:common/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {if $nextURL}
      <a href="{$nextURL}">Next >>></a>
  {/if}


{/if}

{include file="findInclude:common/footer.tpl"}