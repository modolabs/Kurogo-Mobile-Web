{extends file="findExtends:modules/news/templates/index.tpl"}

{block name="newsHeader"}
  {if count($sections) > 1}
    <table id="newsHeader" class="section-search">
      <tr><td id="categoryformcontainer">
        <form method="get" action="index.php">
          <fieldset>
            <label for="section" class="formlabel">Section:</label>
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
        <form method="get" action="search">
          {include file="findInclude:common/templates/search.tpl" insideForm=true extraArgs=$hiddenArgs}
        </form>
      </td></tr>
    </table>
  {else}
  <div id="newsHeader">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}
  </div>
  {/if}
{/block}

{block name="stories"}
<div id="tabletNews" class="splitview">
<div id="stories" class="listcontainer">
{include file="findInclude:modules/news/templates/stories.tpl"}
</div>
<div id="storyDetailWrapper" class="splitview-detailwrapper">
<div id="storyDetail">
</div><!-- storyDetail -->
</div><!-- storyDetailWrapper -->
</div><!-- tabletNews -->
{/block}
