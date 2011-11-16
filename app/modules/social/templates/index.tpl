{include file="findInclude:common/templates/header.tpl"}
{if $needToAuth}
<p class="nonfocal">Some services require authorization before viewing posts</p>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$needToAuth subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/results.tpl" listitemTemplate="modules/$moduleID/templates/postlistItem.tpl" results=$posts subTitleNewline=true}

{include file="findInclude:common/templates/footer.tpl"}
