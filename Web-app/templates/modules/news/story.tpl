{include file="findInclude:common/header.tpl"}

<div class="news">
  <h1 class="slugline">{$story["title"]}</h1>
  
  {if $isFirstPage}
    <p class="byline">
      {block name="byline"}
        {block name="shareImage"}
          <a href="{$shareUrl}"><img src="/common/images/share.png" class="share" /></a>
        {/block}
      
        {if isset($story["author"]) && strlen($story["author"])}
          <span class="credit">by <span class="author">{$story["author"]}</span><br /></span>
        {/if}
    
        <span class="postdate">{$date}</span>
      {/block}
    </p>
  {else}
    <p>Page {$pageNumber+1} of {$totalPageCount}</p>
  {/if}
  
  {if $isFirstPage && isset($story['image'])}
    <div id="image">
      <img class="thumbnail" src="{$story['image']['url']}"
        {if isset($story['image']['width'])} width="{$story['image']['width']}"{/if}
        {if isset($story['image']['height'])} height="{$story['image']['height']}"{/if}>
    </div>
  {/if}
  
  {block name="content"}
    {$allPages}
  {/block}
</div>


{include file="findInclude:common/footer.tpl"}
