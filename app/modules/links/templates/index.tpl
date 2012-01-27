{include file="findInclude:common/templates/header.tpl"}


{if isset($description) && strlen($description)}
{block name="linksHeader"}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description}
  </p>
{/block}
{/if}


{if $displayType == 'springboard'}
  {include file="findInclude:common/templates/springboard.tpl" springboardItems=$links springboardID="links"}
{elseif $displayType == 'list'}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$links subTitleNewline=true}
{/if}

<p class="clear"> </p>

{if isset($description_footer) && strlen($description_footer)}
{block name="linksFooter"}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description_footer}
  </p>
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
