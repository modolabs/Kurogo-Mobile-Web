{if $hasEmergencyFeed}
<div id="emergencyNotice">
    {if !$emergencyFeedEmpty}
      <div class="emergency-notice{if $emergencyNotice@first} emergency-featured{/if}">
        <div class="title">{$title}</div>
        <div class="pubdate">{$date|date_format:$dateFormat} {$date|date_format:$timeFormat}</div>
        <div class="content">{$text}</div>
      </div>
    {else}
      {$moduleStrings.NO_EMERGENCY}
    {/if}
</div>
{/if}
