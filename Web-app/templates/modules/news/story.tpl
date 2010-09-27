{include file="findInclude:common/header.tpl"}

<div class="news">
  <h1 class="slugline">{$story["title"]}</h1>
  
  <p class="byline">
    <a href="{$shareUrl}"><img src="/common/images/share.png" class="share" /></a>
  
    {if isset($story["author"]) && strlen($story["author"])}
      <span class="credit">by <span class="author">{$story["author"]}</span><br /></span>
    {/if}
  
    <span class="postdate">{$date}</span>
  </p>
  
  {if isset($story['image'])}
    <div id="image">
      <img class="thumbnail" src="{$story['image']['url']}"
        {if isset($story['image']['width'])} width="{$story['image']['width']}"{/if}
        {if isset($story['image']['height'])} height="{$story['image']['height']}"{/if}>
    </div>
  {/if}
  <!--<p class="dek">{$story['description']}</p>-->
  
  {$allPages}
</div>


{include file="findInclude:common/footer.tpl"}
