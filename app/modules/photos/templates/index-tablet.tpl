{extends file="findExtends:modules/photos/templates/index.tpl"}

{block name="navList"}
  
  <div class="photo-albums">
  {foreach $albums as $album}
    <div class="album">
        <a href="{$album.url}">
        <span class="album-cover"><img src="{$album.img}" width="150" height="150" alt="" /></span>
        <span class="album-title">{$album.title}</span>
        <span class="smallprint serviceicon {$album.type}">{$album.type} | {$album.albumcount}</span>
        </a>
    </div>
  {/foreach}
  </div> <!-- class="photo-albums" -->
  
{/block}
