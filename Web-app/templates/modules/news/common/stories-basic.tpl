<div class="focal">
  {foreach $stories as $story}
    <p>
      {if $story@first && isset($story['image'])}
        <img class="thumbnail" src="{$story['image']['src']}" />
      {/if}
      <a class="story-link" href="{$story['url']}">{$story["title"]|escape}</a>
      <br />
      <span class="smallprint">{$story['description']|truncate:75|escape}</span>
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
