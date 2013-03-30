<div class="springboard"{if $springboardID} id="{$springboardID}"{/if}>
  {foreach $springboardItems as $item}
    {if $item['separator']}
      {block name="separator"}
        <p class="separator">&nbsp;</p>
      {/block}
    {else}
      <div {if $item['class']} class="{$item['class']}"{/if}>
        {if $item['url']}
          <a href="{$item['url']}"{if $item['linkTarget']} target="{$item['linkTarget']}"{/if}>
        {/if}
            {if $item['img']}
              <img src="{$item['img']}" alt="" />
            {/if}
            {if $item['img']}<br/>{/if}{$item['title']}
            {if isset($item['subTitle'])}
              <br/><span class="fineprint">{$item['subTitle']}</span>
            {/if}
            {block name="badge"}
              {if isset($item['badge'])}
                <span class="badge">{$item['badge']}</span>
              {/if}
            {/block}
            {block name="secured"}
              {if isset($item['secured'])}
          		<span class="secured"></span>
              {/if}
            {/block}
        {if $item['url']}
          </a>
        {/if}
      </div>
    {/if}
  {/foreach}
</div>
