{include file="findInclude:common/templates/header.tpl"}

<h1 class="nonfocal">{$sportTitle}</h1>
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  

{if $scheduleItems}
<h2 class="nonfocal">{"SCHEDULE_TITLE"|getLocalizedString}</h2>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$scheduleItems subTitleNewline=true}
{/if}
{if $newsItems}
    <h2 class="nonfocal">{"NEWS_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:modules/athletics/templates/stories.tpl" stories=$newsItems} 
{/if}


{include file="findInclude:common/templates/footer.tpl"}
