<div class="gallery"{if $springboardID} id="{$springboardID}"{/if}>
  {foreach $springboardItems as $item}
    {if $item['separator']}
      {block name="separator"}
        <p class="separator">&nbsp;</p>
      {/block}
    {else}
        {if $item['url']}
          <a href="{$item['url']}">
        {/if}
            <img src="{$item['img']}" alt="{$item['title']}" width="68" height="68" />
        {if $item['url']}
          </a>
        {/if}
    {/if}
  {/foreach}
</div>
