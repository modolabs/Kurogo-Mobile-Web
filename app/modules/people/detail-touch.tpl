{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="detail"}
  <li>
    {if isset($item['url'])}
      <a href="{$item['url']}" class="{$item['class']|default:''}">
    {/if}
        <div class="label">{$item['label']}</div>
        <div class="value">{$item['title']}</div>
    {if isset($item['url'])}
      </a>
    {/if}
  </li>
{/block}
