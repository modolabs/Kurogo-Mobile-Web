{include file="findInclude:common/templates/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}
<p>
  {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
  {include file="findInclude:common/templates/share.tpl" shareURL={$videoURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</p>
</h1>

<p class="nonfocal">
<iframe class="youtube-player" type="text/html" width="298" height="200" src="http://www.youtube.com/embed/{$videoid}" frameborder="0">
</iframe>
</p>

<p class="focal">{$videoDescription}</p>

{include file="findInclude:common/templates/footer.tpl"}