<div class="gallery"{if $springboardID} id="{$springboardID}"{/if}>
  {foreach $springboardItems as $item}
    {if $item['separator']}
      {block name="separator"}
        <p class="separator">&nbsp;</p>
      {/block}
    {else}
      <div {if $item['class']} class="{$item['class']}"{/if}>
        {if $item['url']}
          <a href="{$item['url']}">
        {/if}
            <img src="{$item['img']}" alt="{$item['title']}" />
        {if $item['url']}
          </a>
        {/if}
      </div>
    {/if}
  {/foreach}
</div>
