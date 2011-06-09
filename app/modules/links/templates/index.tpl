{include file="findInclude:common/templates/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description|escape}
  </p>
{/if}

{if $displayType == 'springboard'}
  {include file="findInclude:common/templates/springboard.tpl" springboardItems=$links springboardID="links"}
{elseif $displayType == 'list'}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

<p class="clear"> </p>

{include file="findInclude:common/templates/footer.tpl"}
