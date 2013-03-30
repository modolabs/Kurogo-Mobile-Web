{extends file="findExtends:common/templates/header-common-common-native.tpl"}

{block name="kgoHeaderAJAXContent"}
  {$ignored = array_unshift($onLoadBlocks, "tabletInit();")}
  {$smarty.block.parent}
{/block}

{block name="kgoHeaderContainerStart"}
  <div id="wrapper">
    <div id="container-wrapper">
      <div id="container">
{/block}
