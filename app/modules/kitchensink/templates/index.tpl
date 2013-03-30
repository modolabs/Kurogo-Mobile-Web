{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">This module was created to show developers the available parts and features
of Kurogo templates.</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links}

{if $appQLinks}
    <div class="nonfocal">AppQ Tests</div>
    
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$appQLinks}
{/if}

{include file="findInclude:common/templates/footer.tpl"}

