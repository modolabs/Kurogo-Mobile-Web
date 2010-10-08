{include file="findInclude:common/header.tpl"}

<div class="news">
  <h1 class="slugline">{$story->getTitle()}</h1>
  
  {if $pager['pageNumber'] == 0}
    <p class="byline">
      {block name="byline"}
        {block name="shareImage"}
          <a href="{$shareUrl}"><img src="/common/images/share.png" class="share" /></a>
        {/block}
      
        {if $story->getProperty('harvard:author')}
          <span class="credit">by <span class="author">{$story->getProperty('harvard:author')}</span><br /></span>
        {/if}
    
        <span class="postdate">{$date}</span>
      {/block}
    </p>
    {if $story->getImage()}
    {$image = $story->getImage()}
      <div id="image">
        <img class="thumbnail" src="{$image->getURL()}"
          {if $image->getWidth()} width="{$image->getWidth()}"{/if}
          {if $image->getHeight()} height="{$image->getHeight()}"{/if}>
      </div>
    {/if}
  {/if}
  
  {include file="findInclude:common/pager.tpl"}
</div>


{include file="findInclude:common/footer.tpl"}
