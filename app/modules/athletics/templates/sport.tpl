{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
  <h2>{$sportTitle}</h2>
</div>

{if $scheduleItems}
<h3 class="nonfocal">{"SCHEDULE_TITLE"|getLocalizedString}</h3>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$scheduleItems subTitleNewline=true}
{/if}
{if $newsItems}
    <h3 class="nonfocal">{"NEWS_TITLE"|getLocalizedString}</h3>
    {include file="findInclude:modules/athletics/templates/stories.tpl" stories=$newsItems} 
{/if}


{include file="findInclude:common/templates/footer.tpl"}
