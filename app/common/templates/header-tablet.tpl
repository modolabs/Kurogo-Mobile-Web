{extends file="findExtends:common/templates/header.tpl"}

{block name="kgoAccessibilityLinks"}
  <a href="#content_top">Skip to Content</a>
{/block}

{block name="kgoHeaderBodyAttributes"}
  onload="tabletInit();{if count($onLoadBlocks)}onLoad();{/if}{if count($onOrientationChangeBlocks)}onOrientationChange();{/if}"
{/block}

{block name="kgoHeaderContainerStart"}
  <div id="wrapper">
    {if isset($customMenu)}
      {$customMenu}
    {else}
      {include file="findInclude:common/templates/page/navigation/navmenu.tpl" navmenuID="navmenu"}
    {/if}
    <div id="container-wrapper">
      <div id="container">
{/block}
