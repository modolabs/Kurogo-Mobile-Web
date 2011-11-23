{include file="findInclude:common/templates/header.tpl"}

<h1 class="nonfocal">{$sportTitle}</h1>
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  

{if $scheduleItems}
<h2 class="nonfocal">Schedule</h2>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$scheduleItems subTitleNewline=true}
{/if}
{if $newsItems}
    <h2 class="nonfocal">News</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$newsItems}  
{/if}


{include file="findInclude:common/templates/footer.tpl"}
