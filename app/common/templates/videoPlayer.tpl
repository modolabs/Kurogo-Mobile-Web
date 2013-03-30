{if $video}
  {if $video->canPlay(Kurogo::deviceClassifier())}
    {$videoPlayerType = $video->getType()}
    <div class="kgo-videoplayer kgo-videoplayer-{$videoPlayerType}">
      <div class="kgo-videoplayer-container">
        {include file="findInclude:common/templates/videoPlayer/videoPlayer_$videoPlayerType.tpl"}
      </div>
    </div>
  {else}
    <div class="nonfocal">
      {"VIDEO_UNSUPPORTED"|getLocalizedString}
    </div>
  {/if}
{else}
  <div class="nonfocal">
    {"VIDEO_INVALID"|getLocalizedString}
  </div>
{/if}
