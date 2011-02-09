<div id="newsStories">
  {foreach $stories as $story}
    <a id="newsStory_{$story@index}" href="{$story['url']}"{if $story@first} class="current"{/if}>
      <div class="thumbnail">
        <img src="{if $story['image']}{$story['image']['src']}{else}/common/images/news-placeholder.png{/if}" />
      </div>
      <div class="title">{$story["title"]}</div>
      {$story['description']}
    </a>
  {/foreach}
</div>
<div id="newsPager">
  <div id="newsPagerDots" class="dots">
    {foreach $stories as $story} 
      <div id="newsDot_{$story@index}"{if $story@first} class="current"{/if}></div>
    {/foreach}
  </div>
  <a id="newsStoryPrev" onclick="javascript:return newsPaneSwitchStory(this, 'prev');" class="disabled"></a>
  <a id="newsStoryNext" onclick="javascript:return newsPaneSwitchStory(this, 'next');"></a>
</div>
