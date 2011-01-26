{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


{$firstField = array_shift($fields)}
{$lastField = array_pop($fields)}

<div class="focal">
  {block name="firstField"}
    <h2>
      {include file="findInclude:common/listItem.tpl" item=$firstField}
    </h2>
  {/block}
  
  {block name="fields"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$fields}
  {/block}
  
  <p class="legend">
    {include file="findInclude:common/listItem.tpl" item=$lastField}
  </p>

</div>

{include file="findInclude:common/footer.tpl"}
