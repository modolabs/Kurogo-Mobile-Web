{include file="findInclude:common/templates/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal{/block}">
    {$description|escape}
  </p>
{/if}

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$albums listItemTemplateFile="findInclude:modules/photos/templates/photoListItem.tpl"}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
