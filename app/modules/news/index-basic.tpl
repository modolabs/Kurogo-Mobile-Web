{extends file="findExtends:modules/$moduleID/index.tpl"}

{block name="newsHeader"}
  <h2>{$currentSection['title']}</h2>
{/block}

{block name="newsFooter"}
  {include file="findInclude:common/search.tpl" extraArgs=$hiddenArgs}

  {include file="findInclude:common/footer.tpl" additionalLinks=$sections}
{/block}
