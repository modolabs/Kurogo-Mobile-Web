{extends file="findExtends:modules/news/templates/index.tpl"}

{block name="newsHeader"}
  <h2>{$currentSection['title']}</h2>
{/block}

{block name="newsFooter"}
  {include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}

  {include file="findInclude:common/templates/footer.tpl" additionalLinks=$sections}
{/block}
