{include file="findInclude:common/templates/header.tpl"}

{$firstField = array_shift($fields)}
{$lastField = array_pop($fields)}

<div class="focal">
  {block name="firstField"}
    <h2>
      {include file="findInclude:common/templates/listItem.tpl" item=$firstField}
    </h2>
  {/block}
  
  {block name="fields"}
    {if count($fields)}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$fields accessKey=false}
    {/if}
  {/block}
  
  <p class="legend">
    {include file="findInclude:common/templates/listItem.tpl" item=$lastField}
  </p>

</div>

{include file="findInclude:common/templates/footer.tpl"}
