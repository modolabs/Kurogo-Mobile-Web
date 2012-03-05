{include file="findInclude:common/templates/header.tpl"}

{block name="description"}
{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}
{/block}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentPages}
    
{include file="findInclude:common/templates/footer.tpl"}
