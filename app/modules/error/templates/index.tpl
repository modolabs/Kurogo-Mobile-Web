{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  <p>{$message}</p>
  
  {if isset($url)}
    <p>
      <a href="{$url|escape}">{$linkText|default:'Click here to retry page'}</a>
    </p>
  {/if}
</div>

{include file="findInclude:common/templates/footer.tpl"}
