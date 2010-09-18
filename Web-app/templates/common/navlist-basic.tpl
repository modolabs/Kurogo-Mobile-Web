<p class="nav"> 
  {foreach $navlistItems as $item}
    {strip}
      {if isset($item['url'])}
        <a href="{$item['url']}" class="{$item['class']|default:''}" {access_key platform=$platform}>
      {/if}  
        {$item['html']}
      {if isset($item['url'])}
        </a>
      {/if}
      <br/>
    {/strip}
  {/foreach}
</p>
