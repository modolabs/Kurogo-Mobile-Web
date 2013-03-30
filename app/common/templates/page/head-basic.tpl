{extends file="findExtends:common/templates/page/head.tpl"}

{block name="kgoHeadJavascript"}{/block}

{block name="kgoHeadAdditionalTags"}
  {$smarty.block.parent}
  <style type="text/css" media="screen">
    {$fontsizeCSS}
  </style>
{/block}
