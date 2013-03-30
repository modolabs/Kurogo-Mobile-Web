{extends file="findExtends:common/templates/header.tpl"}

{block name="kgoHeaderNavigationBodyClasses"}
  {$kgoHasNavmenu = false}
  {$kgoHasNavbar = false}
{/block}

{block name="kgoHeaderBodyAttributes"}
  onload="kgoBridge.ajaxLoad();"
{/block}
