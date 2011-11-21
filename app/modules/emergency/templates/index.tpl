{include file="findInclude:common/templates/header.tpl"}

{if $hasEmergencyFeed}
  {foreach $emergencyNotices as $i => $emergencyNotice}
      {capture name="title" assign="title"}
        <div class="emergency-notice{if $emergencyNotice@first} emergency-featured{/if}">
          <div class="title">{$emergencyNotice['title']}</div>
          <div class="pubdate">{$emergencyNotice['date']|date_format:$dateFormat} {$emergencyNotice['date']|date_format:$timeFormat}</div>
          <div class="content">{$emergencyNotice['text']}</div>
        </div>
      {/capture}
      {$emergencyNotices[$i]['title'] = $title}
  {/foreach}
  
  <div class="focal">
    {if count($emergencyNotices)}
      {$featuredNotice = array_shift($emergencyNotices)}
      {$featuredNotice['title']}
    {else}
      {$moduleStrings.NO_EMERGENCY}
    {/if}
  </div>
{/if}

{if $hasContacts}
  {include file="findInclude:common/templates/navlist.tpl"
     navlistItems=$contactNavListItems
     accessKey=false
     subtitleNewLine=false}
{/if}

{if $hasEmergencyFeed && count($emergencyNotices)}
  {include file="findInclude:common/templates/results.tpl" results=$emergencyNotices}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
