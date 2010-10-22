{include file="findInclude:common/header.tpl"}

<div class="news">
  <h1 class="slugline">{$title}</h1>
  
  {if $pager['pageNumber'] == 0}
    <p class="byline">
      {block name="byline"}
        {block name="shareImage"}
          <a href="{$shareUrl}"><img src="/common/images/share.png" class="share" /></a>
        {/block}
      
        {if $author}
          <span class="credit">by <span class="author">{$author}</span><br /></span>
        {/if}
    
        <span class="postdate">{$date}</span>
      {/block}
    </p>
    {if isset($image)}
      <div id="image">
        <img class="thumbnail" src="{$image['src']}">
      </div>
    {/if}
  {/if}
  
  {include file="findInclude:common/pager.tpl"}
</div>


{include file="findInclude:common/footer.tpl"}
