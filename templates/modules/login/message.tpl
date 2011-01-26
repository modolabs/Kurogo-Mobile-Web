{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


<div class="nonfocal">
<h2>Login</h2>
</div>

<div class="focal">{$message|escape}</div>
{if $url}
<div class="focal"><a href="{$url}">{$linkText}</a></div>
{/if}

{include file="findInclude:common/footer.tpl"}
