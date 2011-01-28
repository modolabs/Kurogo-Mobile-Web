<ul class="results">
  {if $previousURL}
    <li class="pagerlink">
      <a href="{$previousURL}">Previous 10 stories...</a>
    </li>
  {/if}

  {$ellipsisCount=0}
  {foreach $stories as $story}
    <li class="story">
      {if $story['image']}
        <img class="thumbnail" src="{$story['image']['src']}" />
      {else}
        <img class="thumbnail" src="/modules/{$moduleID}/images/news-placeholder.png" />
      {/if}
      <a href="{$story['url']}">
        <div class="ellipsis" id="ellipsis_{$ellipsisCount++}">
          <div class="title">{$story["title"]}</div>
          {$story['description']}
        </div>
      </a>
    </li>
  {/foreach}

  {if $nextURL}
    <li class="pagerlink">
      <a href="{$nextURL}">Next 10 stories...</a>
    </li>
  {/if}
</ul>
