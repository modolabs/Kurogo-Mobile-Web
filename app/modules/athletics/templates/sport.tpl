{include file="findInclude:common/templates/header.tpl"}

<h1 class="nonfocal">{$sportTitle}</h1>

Recent:{$recent.sport}
Next:{$next.sport}

{if $newsItems}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$newsItems}  
{/if}


{include file="findInclude:common/templates/footer.tpl"}
