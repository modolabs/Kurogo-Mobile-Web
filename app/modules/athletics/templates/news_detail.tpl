{include file="findInclude:common/templates/header.tpl"}

<div class="news">
  <h1 class="slugline">{if $showLink}<a href="{$link}">{/if}{$title}{if $showLink}</a>{/if}</h1>

  <div id="storysubhead">
    {include file="findInclude:common/templates/share.tpl" shareURL={$storyURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
            
    {if $pager['pageNumber'] == 0}
        <p class="byline">
          {block name="byline"}
              
            {if $author}
              <span class="credit author">{"AUTHOR_CREDIT"|getLocalizedString:$author}</span><br />
            {/if}
    
            <span class="postdate">{$date}</span>
          {/block}
        </p>    
    {/if}        
  </div><!--storysubhead-->
  
  <div id="story">
    {if $pager['pageNumber'] == 0}
        {if $thumbnail && $showBodyThumbnail}
          <div id="thumbnail">
            <img class="thumbnail" src="{$thumbnail['src']}" alt="" />
          </div>
        {/if}
    {/if}
    
    <span id="storybody">
      {include file="findInclude:common/templates/pager.tpl"}
    </span>
    {if $showLink}
    <div id="showmore">
    <a href="{$link}">{"READ_MORE"|getLocalizedString}</a>
    </div>
    {/if}
  </div><!--story-->
</div>

{include file="findInclude:common/templates/footer.tpl"}
