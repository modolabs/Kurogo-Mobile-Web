{include file="findInclude:common/templates/header.tpl"}

{if $description}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false subTitleNewline=$contactsSubTitleNewline}

{include file="findInclude:common/templates/footer.tpl"}
