{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
  {include file="findInclude:common/templates/results.tpl" results=$events subTitleNewline=false}
{/block}
