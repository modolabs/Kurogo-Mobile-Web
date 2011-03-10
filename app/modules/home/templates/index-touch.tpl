{extends file="findExtends:modules/home/templates/index.tpl"}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".gif"}
  {$bannerImg['width'] = 210}
  {$bannerImg['height'] = 35}
{/block}
