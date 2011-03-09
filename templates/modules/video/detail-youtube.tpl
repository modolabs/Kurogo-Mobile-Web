{include file="findInclude:common/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}</h1>
<p class="nonfocal">

{include file="findInclude:common/share.tpl" shareURL={$videoURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}

    <iframe class="youtube-player" type="text/html" width="298" height="200" src="http://www.youtube.com/embed/{$videoid}" frameborder="0">
    </iframe>
</p>
<p class="focal">{$videoDescription}</p>

<div class="focal shaded">
   {include file="findInclude:common/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
 
{include file="findInclude:common/footer.tpl"}