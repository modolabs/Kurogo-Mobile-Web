{extends file="findExtends:modules/news/templates/index.tpl"}

{block name="newsHeader"}
  {if count($sections) > 1}
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
  {else}
    {include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {/if}
{/block}

{block name="newsFooter"}
  {if count($sections) > 1}
    {include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {/if}
  {$smarty.block.parent}
{/block}
