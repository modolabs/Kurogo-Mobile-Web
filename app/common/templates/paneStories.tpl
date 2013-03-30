{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
  {$placeholderImage = $placeholderImage|default:"/modules/$configModule/images/$configModule-placeholder.png"}
  {$showImages = $showImages|default:true}
  {$hasLargeImages = false}
  {foreach $stories as $story}
    {if $story['large']}
      {$hasLargeImages = true}
    {/if}
  {/foreach}

  {$paneStoriesInstanceId = "{$configModule}PaneStoriesId"}
  <div id="{$paneStoriesInstanceId}" class="pane-stories-container">
    <div class="pane-stories">
      {foreach $stories as $story}
        {$showImageLarge = $showImages && $story['large']}
        {$showImageSmall = $showImages && !$showImageLarge && ($story['img'] || !$hasLargeImages)}
      
        {$imageClass = "pane-story-image-none"}
        {if $showImageLarge}
          {$imageClass = "pane-story-image-large"}
        {elseif $showImageSmall}
          {$imageClass = "pane-story-image-small"}
        {/if}
        
        {block name="paneStory"}
          <a href="{$story['url']}" class="pane-story{if $story@first} current{/if} {$imageClass}{if $story['class']} {$story['class']}{/if}">
            {if $showImageLarge}
              <div class="pane-story-image portlet-content-top-element" style="background-image:url('{$story['img']}')"></div>
            {/if}
            <div class="pane-story-caption">
              <div class="ellipsis">
                <h2 class="title">
                  {if $showImageSmall}
                    <div class="thumbnail">
                      <img src="{if $story['img']}{$story['img']}{else}{$placeholderImage}{/if}" alt="" />
                    </div>
                  {/if}
                  {$story["title"]}
                </h2>
              <div class="smallprint">{$story['subtitle']}</div>
              </div>
            </div>
          </a>
        {/block}
      {/foreach}
    </div>
    <div class="pane-stories-pager">
      <a onclick="return homePortlets.{$configModule}.switchStory(this, 'prev');" class="pane-stories-pager-prev disabled"><img src="/common/images/page-prev.png" alt="Previous Story" /></a>
      <div class="pane-stories-pager-dots">
        {foreach $stories as $story} 
          <div class="pane-stories-pager-dot {if $story@first} current{/if}"></div>
        {/foreach}
      </div>
      <a onclick="return homePortlets.{$configModule}.switchStory(this, 'next');" class="pane-stories-pager-next"><img src="/common/images/page-next.png" alt="Next Story" /></a>
    </div>
  </div>
  <script type="text/javascript">
      homePortlets.{$configModule} = new paneStories("{$paneStoriesInstanceId}");
      registerPaneResizeHandler(function () {ldelim}
          homePortlets.{$configModule}.resizeHandler();
      {rdelim});
  </script>
{/block}
