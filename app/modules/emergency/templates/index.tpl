{include file="findInclude:common/templates/header.tpl"}

{if $hasEmergencyFeed}
  <div class="focal">
    {if !$emergencyFeedEmpty}
      <h2>{$title}</h2>
      <p class="smallprint">{$date|date_format:"%l:%M %p, %a %b %e, %Y"}</p>
      {$content}
    {else}
      {$moduleStrings.NO_EMERGENCY}
    {/if}
  </div>
{/if}

{if $hasContacts}
  <div>
    {include file="findInclude:common/templates/navlist.tpl"
       navlistItems=$contactNavListItems
       accessKey=false
       nested=true
       subtitleNewLine=false}
  </div>
{/if}
{include file="findInclude:common/templates/footer.tpl"}
