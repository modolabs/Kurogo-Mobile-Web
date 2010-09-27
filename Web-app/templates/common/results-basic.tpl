{extends file="findExtends:common/results.tpl"}

{block name="resultsStart"} 
  {if $accessKey|default:true}
    {html_access_key_reset index=count($breadcrumbs)+1}
  {/if}
  <p class="focal results">
{/block}

    {block name="resultsItem"}
      {if isset($item['url'])}
        {if $accessKey|default:true}
          {html_access_key_link href=$item['url'] class=$item['class']|default:null accessKey=false}
            {$itemHTML}
          {/html_access_key_link}
        {else}
          <a href="{$item['url']}" class="{$item['class']|default:''}">{$itemHTML}</a>
        {/if}
      {else}
        <span class="{$item['class']|default:''}">
          {$itemHTML}
        </span>
      {/if}
      <br/>
    {/block}

{block name="resultsEnd"}
  </p>
{/block}
