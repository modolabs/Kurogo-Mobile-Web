{if !$ajax}
{include file="findInclude:common/templates/header.tpl"}
{/if}

<div class="video">
    <h1 class="slugline">{$videoTitle}</h1>

  <div id="videosubhead">
		<div class="videobuttons">
      {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
      {include file="findInclude:common/templates/share.tpl" shareURL=$videoURL shareRemark=$shareRemark shareEmailURL=$shareEmailURL}
      </div>
            
        <p class="byline">
          {block name="byline"}
              
            {if $videoAuthor}
              <span class="credit">by <span class="author">{$videoAuthor}</span><br /></span>
            {/if}
    
            <span class="postdate">{$videoDate}</span>
          {/block}
        </p>    
  </div><!--storysubhead-->

	<div class="videoplayer">
	{block name="videoPlayer"}{/block}
	</div>

	<div class="videodescription">

		{$videoDescription}
	</div>
</div>

{if !$ajax}
{include file="findInclude:common/templates/footer.tpl"}
{/if}