{extends file="findExtends:modules/news/story.tpl"}

{block name="shareImage"}{/block}
{block name="byline"}
  {$smarty.block.parent}
  <a href="{$shareUrl}">Email this article</a>
{/block}

{block name="content"}
  {if $pager['mode'] == 'all'}
    {$allPages}
    
    <div class="pager">
      <a href="{$pager['paged']}">View in Pages</a>
    </div>
  {else}
    {$storyPage}
    {if $totalPageCount > 1}
      <div class="pager">
        Page:
        {if isset($pager['prev'])}
          <a href="{$pager['prev']}" class="page-button">&lt;</a>
        {/if}
        {foreach $pager['pages'] as $number => $url}
          {if $pageNumber+1 == $number}
            {$number}
          {else}
            <a href="{$url}">{$number}</a>
          {/if}
        {/foreach}
        {if isset($pager['next'])}
          &nbsp;&nbsp;<a href="{$pager['next']}" class="page-button">&gt;</a>
        {/if}
        <br/><br/><a href="{$pager['all']}">Read Full Article</a>
      </div>
    {/if}
  {/if}
{/block}
