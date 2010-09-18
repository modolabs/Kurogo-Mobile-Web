<ul class="nav"> 
  {foreach $navlistItems as $item}
    {strip}
    <li>
      {if isset($item['url'])}<a href="{$item['url']}" class="{$item['class']|default:''}">{/if}  
          {$item['html']}
      {if isset($item['url'])}</a>{/if}  
    </li>
    {/strip}
  {/foreach}
</ul>
