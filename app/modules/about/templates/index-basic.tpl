{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="navList"}
  {$lastAboutPage = array_pop($aboutPages)}
  
  {$smarty.block.parent}

  <p class="nav secondary">
    <a href="{$lastAboutPage['url']}" class="{$lastAboutPage['class']|default:''}">
      {$lastAboutPage['title']}
    </a>
  </p>
{/block}
