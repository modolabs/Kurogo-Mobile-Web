{extends file="findExtends:common/templates/header-common-common-native.tpl"}

{block name="ajaxContentHeader"}
  {$ignored = array_unshift($onLoadBlocks, "tabletInit();")}
  {$smarty.block.parent}
{/block}

{block name="navbar"}<div id="navbar" style="visibility:hidden;position:absolute;height:0px;"></div>{/block}

{block name="containerStart"}
  <div id="wrapper">
    <div id="container">
      <div id="containerinset">
{/block}
