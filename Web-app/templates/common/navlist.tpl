{strip}

{block name="navlistStart"}<ul class="nav">{/block}
  {assign var=last value=end(array_keys($navlistItems))}
  {foreach $navlistItems as $index => $item}
    {block name="navlistItem"}
      <li>
        <a href="{$item['url']}" class="{$item['class']|default:''}">
          {$item['html']}
        </a>
      </li>
    {/block}
  {/foreach}
{block name="navlistEnd"}</ul>{/block}

{/strip}
