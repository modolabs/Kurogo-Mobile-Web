{strip}
{foreach $feeds as $key=>$item}     
  <ul class="nav{if $secondary} secondary{/if}"{if $feedlistID} id="{$feedlistID}"{/if}>
  {if $showFeedLabels}
        <li>
         <label>Label</label>
         {$key}
      </li>   
      {/if}
      <li>
         <label>Title</label>
         <input type="text" name="moduleData[feeds][{$key}][TITLE]" value="{$item.TITLE|escape}" />
      </li>
      <li>
         <label>URL</label>
         <input type="text" name="moduleData[feeds][{$key}][BASE_URL]" value="{$item.BASE_URL|escape}" />
      </li>
  </ul>
{/foreach}
{/strip}
