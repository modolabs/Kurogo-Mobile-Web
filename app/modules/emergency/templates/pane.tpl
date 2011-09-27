{if $hasEmergencyFeed}
<div id="emergencyNotice">
    {if !$emergencyFeedEmpty}
      <h2 class="title">{$title}</h2>
      <p class="smallprint">{$date|date_format:"$dateFormat"}</p>
      {$content}
    {else}
      {$moduleStrings.NO_EMERGENCY}
    {/if}
</div>
{/if}
