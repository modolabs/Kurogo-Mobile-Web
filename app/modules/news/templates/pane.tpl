<div id="newsStories">
  {foreach $stories as $story}
    <a id="newsStory_{$story@index}" href="{$story['url']}"{if $story@first} class="current"{/if}>
      <div class="thumbnail">
        <img src="{if $story['img']}{$story['img']}{else}/modules/{$moduleID}/images/news-placeholder.png{/if}" />
      </div>
      <h2 class="title">{$story["title"]}</h2>
      {$story['subtitle']}
    </a>
  {/foreach}
</div>
<div id="newsPager" class="panepager">
  <div id="newsPagerDots" class="dots">
    {foreach $stories as $story} 
      <div id="newsDot_{$story@index}"{if $story@first} class="current"{/if}></div>
    {/foreach}
  </div>
  <a id="newsStoryPrev" onclick="javascript:return newsPaneSwitchStory(this, 'prev');" class="prev disabled"></a>
  <a id="newsStoryNext" onclick="javascript:return newsPaneSwitchStory(this, 'next');" class="next"></a>
</div>
