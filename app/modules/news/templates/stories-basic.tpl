<div class="focal">
  {foreach $stories as $story}
    <p>
      {if $story@first && $story['image']}
        <img class="thumbnail" src="{$story['image']['src']}" width="{$story['image']['width']}" height="{$story['image']['height']}" />
      {/if}
      <a class="story-link" href="{$story['url']}">{$story["title"]|escape}</a>
      <br />
      <span class="smallprint">{$story['description']|truncate:75|escape}</span>
    </p>
  {/foreach}
</div>

<div class="nonfocal">
  {if $previousURL}
    <a href="{$previousURL}">< Previous 10 stories</a>
  {/if}
  {if $previousURL && $nextURL}&nbsp;|&nbsp;{/if}
  {if $nextURL}
    <a href="{$nextURL}">Next 10 stories ></a>
  {/if}
</div>
