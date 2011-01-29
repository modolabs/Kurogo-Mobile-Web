{if $pager['inPagedMode'] && $pager['pageNumber'] > 0}
  <p>Page {$pager['pageNumber']+1} of {$pager['pageCount']}</p>
{/if}

{$pager['html']['page']}    

{if $pager['inPagedMode']}
  {if $pager['pageCount'] > 1}
    <div class="pager">
      Page:&nbsp;
      {if isset($pager['url']['prev'])}
        <a href="{$pager['url']['prev']}" class="page-button">&lt;</a>&nbsp;&nbsp;
      {/if}
      {foreach $pager['url']['pages'] as $pageNumber => $pageUrl}
        {if $pager['pageNumber'] == $pageNumber}
          {$pageNumber+1} 
        {else}
          <a href="{$pageUrl}">{$pageNumber+1}</a> 
        {/if}
      {/foreach}
      {if isset($pager['url']['next'])}
        &nbsp;<a href="{$pager['url']['next']}" class="page-button">&gt;</a>
      {/if}
      <br/><br/><a href="{$pager['url']['all']}">Read Full Article</a>
    </div>
  {/if}
{else}
  <div class="pager">
    <a href="{$pager['url']['pages'][0]}">View in Pages</a>
  </div>
{/if}
