{extends file="findExtends:modules/{$moduleID}/index-compliant-blackberry.tpl"}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".png"}
  {$bannerImg['width'] = 400}
  {$bannerImg['height'] = 68}
{/block}
