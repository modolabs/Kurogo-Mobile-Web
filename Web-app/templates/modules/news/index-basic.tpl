{include file="findInclude:common/header.tpl"}

{if $isHome}
  <h2>{$category}</h2>
{elseif $isSearchResults}
  {include file="findInclude:common/search.tpl" extraArgs=$hiddenArgs inputName="search_terms"}
{/if}

<div class="focal">
  {foreach $stories as $story}
    <p>
      {if $story@first && isset($story['image'])}
        <img class="thumbnail" src="{$story['image']['url']}" />
      {/if}
      <a class="story-link" href="{$story['url']}">{$story["title"]}</a>
      <br />
      <span class="smallprint">{$story['description']}</span>
    </p>
  {/foreach}
</div>

<div class="nonfocal">
  {if $previousUrl}
    <a href="{$previousUrl}">Previous stories</a>
  {/if}
  {if $previousURL && $nextURL}&nbsp;|&nbsp;{/if}
  {if $nextUrl}
    <a href="{$nextUrl}">More stories</a>
  {/if}
</div>

{include file="findInclude:common/search.tpl" extraArgs=$hiddenArgs}

{include file="findInclude:common/footer.tpl" additionalLinks=$categoryLinks}
