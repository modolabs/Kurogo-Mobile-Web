{include file="findInclude:common/templates/header.tpl"}

{block name="title"}
<h1 class="nonfocal">{$title}</h1>
<p class="smallprint nonfocal">{$date}</p>
{/block}
  
{block name="fields"}
{if count($fields)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$fields accessKey=false}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
