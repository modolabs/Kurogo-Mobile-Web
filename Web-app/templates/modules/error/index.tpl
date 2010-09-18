{include file="common/header.tpl"|for_device:$device}

<div class="focal">
  <p>{$message}</p>
  
  {if isset($url)}
    <p>
      <a href="{$url}">Click here to retry page</a>
    </p>
  {/if}
</div>

{include file="common/footer.tpl"|for_device:$device}
