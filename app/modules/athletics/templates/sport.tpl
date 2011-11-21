{include file="findInclude:common/templates/header.tpl"}

<h1 class="nonfocal">{$sportTitle}</h1>

{if $next}
<div class="focal">
  <p>{"NEXT_MATCH"|getLocalizedString}</p>
  {include file="findInclude:modules/athletics/templates/schedule_summary.tpl" schedule=$next}
</div>
{/if}

{if $previous}
<div class="focal"> 
  <p>{"MOST_RECENT_MATCH"|getLocalizedString}</p>
  {include file="findInclude:modules/athletics/templates/schedule_summary.tpl" schedule=$previous}
</div>
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$fullSchedule}

{if $newsItems}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$newsItems}  
{/if}


{include file="findInclude:common/templates/footer.tpl"}
