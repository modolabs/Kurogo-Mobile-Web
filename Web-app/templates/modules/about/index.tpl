{include file="common/header.tpl"|for_device:$device}

{include file="common/navlist.tpl"|for_device:$device navlistItems=$navlistItems}

{if isset($lastNavItem)}
  <p class="nav secondary">
    <a href="{$lastNavItem['url']}" class="{$lastNavItem['class']|default:''}">
      {$lastNavItem['html']}
    </a>
  </p>
{/if}

{include file="common/footer.tpl"|for_device:$device}
