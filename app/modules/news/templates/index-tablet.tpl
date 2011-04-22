{extends file="findExtends:modules/news/templates/index.tpl"}

{block name="newsHeader"}
  {if count($sections) > 1}
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
          {include file="findInclude:common/templates/search.tpl" insideForm=true placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
        </form>
      </td></tr>
    </table>
  {else}
    {include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$moduleName extraArgs=$hiddenArgs}
  {/if}
{/block}

{block name="stories"}
<div id="tabletNews">
<div id="stories">
{include file="findInclude:modules/news/templates/stories.tpl"}
</div>
<div id="storyDetailWrapper">
<div id="storyDetail">
</div><!-- storyDetail -->
</div><!-- storyDetailWrapper -->
</div><!-- tabletNews -->
{/block}
