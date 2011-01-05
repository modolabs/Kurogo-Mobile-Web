{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="newsHeader"}
  <form method="get" action="index.php" id="category-form">
    <label for="section">Section:</label>
    {$categorySelect}
    <input type="submit" id="cat_btn" value="Go" />
    
    {foreach $hiddenArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/block}

{block name="newsFooter"}
  {include file="findInclude:common/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {$smarty.block.parent}
{/block}
