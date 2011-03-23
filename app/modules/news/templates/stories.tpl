<ul class="results">
  {if $previousURL}
    <li class="pagerlink">
      <a href="{$previousURL}">Previous 10 stories...</a>
    </li>
  {/if}

  {$ellipsisCount=0}
  {foreach $stories as $story}
    <li class="story{if !$showImages} noimage{/if}">
      <a href="{$story['url']}">
      {if $showImages}
        {if $story['image']}
          <img class="thumbnail" src="{$story['image']['src']}" />
        {else}
          <img class="thumbnail" src="/modules/{$moduleID}/images/news-placeholder.png" />
        {/if}
        {/if}
        <div class="ellipsis" id="ellipsis_{$ellipsisCount++}">
          <div class="title">{$story["title"]}</div>
          {if $showAuthor}<div class="author">{$story['author']}</div>{/if}
          {if $showPubDate}<div class="pubdate">{$story['pubDate']}</div>{/if}
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
