{if $hasEmergencyFeed}
<div id="emergencyNotice">
    {if !$emergencyFeedEmpty}
      <h2>{$title}</h2>
      <p class="smallprint">{$date|date_format:"%l:%M %p, %a %b %e, %Y"}</p>
      {$content}
    {else}
      {$moduleStrings.NO_EMERGENCY}
    {/if}
</div>
{/if}
