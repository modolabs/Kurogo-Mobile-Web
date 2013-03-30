<div class="focal">
  {foreach $stories as $story}
    <p>
      {if $story@first && $story['image']}
        <img class="thumbnail" src="{$story['image']['src']}" width="{$story['image']['width']}" height="{$story['image']['height']}"  alt="" />
      {/if}
      <a class="story-link" href="{$story['url']}">{$story["title"]}</a>
      <br />
      <span class="smallprint">{$story['description']|truncate:75}</span>
    </p>
  {/foreach}
</div>

<div class="nonfocal">
  {if $previousURL}
    <a href="{$previousURL}">&lt; {"PREVIOUS_STORY_TEXT"|getLocalizedString:$maxPerPage}</a>
  {/if}
  {if $previousURL && $nextURL}&nbsp;|&nbsp;{/if}
  {if $nextURL}
    <a href="{$nextURL}">{"NEXT_STORY_TEXT"|getLocalizedString:$maxPerPage} &gt;</a>
  {/if}
</div>
