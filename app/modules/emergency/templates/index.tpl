{include file="findInclude:common/templates/header.tpl"}

{if $hasEmergencyFeed}
  {foreach $emergencyNotices as $i => $emergencyNotice}
      {capture name="title" assign="title"}
        {block name="title"}
          <div class="emergency-notice{if $emergencyNotice@first} emergency-featured{/if}">
            <div class="title">{$emergencyNotice['title']}</div>
            {if $emergencyNotice['date']}
              <div class="pubdate">{$emergencyNotice['date']|date_format:$dateFormat} {$emergencyNotice['date']|date_format:$timeFormat}</div>
            {/if}
            {if $emergencyNotice['text']}
              <div class="content">{$emergencyNotice['text']}</div>
            {/if}
          </div>
        {/block}
      {/capture}
      {$emergencyNotices[$i]['title'] = $title}
  {/foreach}
  
  {block name="featuredNotice"}
    <div class="focal">
      {if count($emergencyNotices)}
        {$featuredNotice = array_shift($emergencyNotices)}
        {$featuredNotice['title']}
      {else}
        {$moduleStrings.NO_EMERGENCY}
      {/if}
    </div>
  {/block}
{/if}

{if $hasContacts}
  {block name="contacts"}
    {include file="findInclude:common/templates/navlist.tpl"
       navlistItems=$contactNavListItems
       accessKey=false
       subtitleNewLine=false}
  {/block}
{/if}

{if $hasEmergencyFeed && count($emergencyNotices)}
  {block name="emergencyNotices"}
    {include file="findInclude:common/templates/results.tpl" results=$emergencyNotices}
  {/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
