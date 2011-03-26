{include file="findInclude:common/templates/header.tpl"}

<div class="focal">{$message|escape}</div>
{if $url}
<ul class="nav"><li><a href="{$url}">{$linkText}</a></li></ul>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
