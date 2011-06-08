{extends file="findExtends:modules/people/templates/detail.tpl"}

{block name="detail"}
  <li class="detail_{$key}{if !$item['label']} nolabel{/if}">
    {if $item['url']}
      <a href="{$item['url']}" class="{$item['class']|default:''}">
    {/if}
        <div class="label">{$item['label']}</div>
        <div class="value">{$item['title']}</div>
    {if $item['url']}
      </a>
    {/if}
  </li>
{/block}
