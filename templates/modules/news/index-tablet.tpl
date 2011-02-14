{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="newsHeader"}
  <table id="newsHeader">
    <tr><td id="categoryformcontainer">
      <form method="get" action="index.php">
        <fieldset>
          <label for="section">Section:</label>
          {$categorySelect}
          
          {foreach $hiddenArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          {foreach $breadcrumbSamePageArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
        </fieldset>
      </form>
    </td><td id="searchformcontainer">
      <form method="get" action="search.php">
        {include file="findInclude:common/search.tpl" insideForm=true placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
      </form>
    </td></tr>
  </table>
{/block}
