{include file="findInclude:common/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}</h1>
<p class="nonfocal">
    <iframe class="youtube-player" type="text/html" width="298" height="200" src="http://www.youtube.com/embed/{$videoid}" frameborder="0">
    </iframe>
</p>
<p class="focal">{$videoDescription}</p>

{include file="findInclude:common/footer.tpl"}