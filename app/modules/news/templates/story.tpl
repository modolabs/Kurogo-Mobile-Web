{include file="findInclude:common/templates/header.tpl"}

<div class="news">
  <h1 class="slugline">{$title}</h1>

  <div id="storysubhead">
    {include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
            
    {if $pager['pageNumber'] == 0}
        <p class="byline">
          {block name="byline"}
              
            {if $author}
              <span class="credit">by <span class="author">{$author}</span><br /></span>
            {/if}
    
            <span class="postdate">{$date}</span>
          {/block}
        </p>    
    {/if}        
  </div><!--storysubhead-->
  
  <div id="story">
    {if $pager['pageNumber'] == 0}
        {if isset($image)}
          <div id="image">
            <img class="thumbnail" src="{$image['src']}" />
          </div>
        {/if}
    {/if}
    
    <span id="storybody">
      {include file="findInclude:common/templates/pager.tpl"}
    </span>
  </div><!--story-->
</div>

{include file="findInclude:common/templates/footer.tpl"}
