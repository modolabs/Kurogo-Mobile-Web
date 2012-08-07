{include file="findInclude:common/templates/header.tpl"}

{if $scheduleItems}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$scheduleItems subTitleNewline=true}
{else}
<div class="focal">{"NO_SCHEDULE"|getLocalizedString}</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
