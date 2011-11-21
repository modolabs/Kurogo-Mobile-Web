<div id="videos">
  {foreach $videos as $video}
    <a id="videos_{$video@index}" href="{$video['url']}"{if $video@first} class="current"{/if}>
      <div class="thumbnail">
        <img src="{if $video['img']}{$video['img']}{else}/modules/{$moduleID}/images/video-placeholder.png{/if}" />
      </div>
      <h2 class="title">{$video["title"]}</h2>
      {$video['subtitle']}
    </a>
  {/foreach}
</div>
<div id="videoPager" class="panepager">
  <div id="videoPagerDots" class="dots">
    {foreach $videos as $video}
      <div id="videoDot_{$video@index}"{if $video@first} class="current"{/if}></div>
    {/foreach}
  </div>

  <a id="videoPrev" onclick="javascript:return videoPaneSwitchVideo(this, 'prev');" class="prev disabled"></a>
  <a id="videoNext" onclick="javascript:return videoPaneSwitchVideo(this, 'next');" class="next"></a>
</div>
