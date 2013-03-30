{include file="findInclude:common/templates/header.tpl"}

{$firstField = array_shift($fields)}
{$lastField = array_pop($fields)}

{block name="scheduleHeader"}{/block}
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
  
  <p>
    {include file="findInclude:common/templates/listItem.tpl" item=$lastField}
  </p>

</div>

{block name="scheduleFooter"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
