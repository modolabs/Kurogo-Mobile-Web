{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="photoPane"}
  {if $photoURL}
    <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
    <img id="photo" src="" style="max-width: 99.9%;" alt="{$name} Photo" onload="hide('loadingimage2')" />
  {/if}
{/block}

{block name='scrollLink'}{$scrollLink = "#"}{/block}
