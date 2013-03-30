<ul class="results">
  {if $previousURL}
    <li class="pagerlink">
      <a href="{$previousURL}">{"PREVIOUS_STORY_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}

  {$ellipsisCount=0}
  {foreach $stories as $story}
    <li class="story{if !$showImages} noimage{/if}">
      <a href="{$story['url']}">
      {if $showImages}
        {if $story['img']}
          <img class="thumbnail" src="{$story['img']}" alt="" />
        {else}
          <img class="thumbnail" src="/modules/{$moduleID}/images/athletics-placeholder{$imageExt}" alt="" />
        {/if}
        {/if}
        <div class="ellipsis" id="ellipsis_{$ellipsisCount++}">
          <div class="title">{$story["title"]}</div>
          <div class="smallprint">{if $showAuthor}<div class="author">{$story['author']}</div>{/if}
          {if $showPubDate}<div class="pubdate">{$story['pubDate']}</div>{/if}
          {$story['subtitle']}</div>
        </div>
      </a>
    </li>
  {/foreach}

  {if $nextURL}
    <li class="pagerlink">
      <a href="{$nextURL}">{"NEXT_STORY_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}
</ul>
