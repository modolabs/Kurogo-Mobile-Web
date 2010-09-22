{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/navlist.tpl" navlistItems=$navlistItems}

{if isset($lastNavItem)}
  <p class="nav secondary">
    <a href="{$lastNavItem['url']}" class="{$lastNavItem['class']|default:''}">
      {$lastNavItem['title']}
    </a>
  </p>
{/if}

{include file="findInclude:common/footer.tpl"}
