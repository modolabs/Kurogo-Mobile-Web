{extends file="findExtends:modules/{$moduleID}/templates/index.tpl"}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".gif"}
  {$bannerImg['width'] = 208}
  {$bannerImg['height'] = 35}
{/block}

{block name="topItem"}
  <p class="bb"> </p>  
{/block}
