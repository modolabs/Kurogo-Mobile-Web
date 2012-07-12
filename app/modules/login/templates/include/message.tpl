<div class="focal">{$message|escape}</div>
{if $url}
<ul class="nav"><li><a href="{$url}">{$linkText}</a></li></ul>
{/if}
{if $buttonTitle}
{include file="findInclude:common/templates/formButtonLink.tpl"}
{/if}    
