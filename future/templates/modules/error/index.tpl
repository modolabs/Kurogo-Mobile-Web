{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


<div class="focal">
  <p>{$message}</p>
  
  {if isset($url)}
    <p>
      <a href="{$url}">{$linkText|default:'Click here to retry page'}</a>
    </p>
  {/if}
</div>

{include file="findInclude:common/footer.tpl"}
