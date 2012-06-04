{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
  {$paneStoriesInstance = $configModule|cat:"PaneStories"}
  <div id="{$paneStoriesInstance}" class="pane-stories-container">
    <div class="pane-stories">
      {foreach $stories as $story}
        <a href="{$story['url']}" class="{if $story@first}current {/if}{if !$showImages}noimage{/if}">
          {if $showImages|default:true}
            <div class="thumbnail">
              <img src="{if $story['img']}{$story['img']}{else}/modules/{$configModule}/images/{$configModule}-placeholder.png{/if}" />
            </div>
          {/if}
          <h2 class="title">{$story["title"]}</h2>
          {$story['subtitle']}
        </a>
      {/foreach}
    </div>
    <div class="pane-stories-pager">
      <div class="pane-stories-pager-dots">
        {foreach $stories as $story} 
          <div{if $story@first} class="current"{/if}></div>
        {/foreach}
      </div>
      <a onclick="javascript:return {$paneStoriesInstance}.switchStory(this, 'prev');" class="pane-stories-pager-prev disabled"></a>
      <a onclick="javascript:return {$paneStoriesInstance}.switchStory(this, 'next');" class="pane-stories-pager-next"></a>
    </div>
  </div>
  <script type="text/javascript">
      var {$paneStoriesInstance} = new paneStories("{$paneStoriesInstance}");
      registerPaneResizeHandler(function () {ldelim}
          {$paneStoriesInstance}.resizeHandler();
      {rdelim});
  </script>
{/block}
