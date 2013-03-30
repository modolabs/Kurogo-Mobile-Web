{include file="findInclude:common/templates/header.tpl"}

<div class="video">
  <h1 class="slugline">{$videoTitle}</h1>

  <div id="videosubhead">
    <div class="videobuttons">
      {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
      {include file="findInclude:common/templates/share.tpl" shareURL=$shareURL shareRemark=$shareRemark shareEmailURL=$shareEmailURL}
    </div>
    <p class="byline">
      {block name="byline"}
        {if $videoAuthor}
          <span class="credit">by <span class="author">{$videoAuthor}</span><br /></span>
        {/if}
        <span class="postdate">{$videoDate}</span>
      {/block}
    </p>    
  </div><!--videosubhead-->

  {include file="findInclude:common/templates/videoPlayer.tpl" video=$videoObject}

  <div class="videodescription">
    {$videoDescription}
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
