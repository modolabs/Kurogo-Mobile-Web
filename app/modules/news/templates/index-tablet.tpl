{extends file="findExtends:modules/news/templates/index.tpl"}

{block name="newsHeader"}
  {if count($sections) > 1}
    <table id="newsHeader" class="section-search">
      <tr><td id="categoryformcontainer">
        <form method="get" action="/{$configModule}/index">
          <fieldset>
            <label for="section" class="formlabel">Section:</label>
            {$categorySelect}
            
            {foreach $hiddenArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value|escape}" />
            {/foreach}
            {foreach $breadcrumbSamePageArgs as $arg => $value}
              <input type="hidden" name="{$arg}" value="{$value|escape}" />
            {/foreach}
          </fieldset>
        </form>
      </td><td id="searchformcontainer">
        <form method="get" action="/{$configModule}/search">
          <input type="hidden" name="section" value="{$currentSection.value}" />
          {include file="findInclude:common/templates/search.tpl" insideForm=true extraArgs=$hiddenArgs}
        </form>
      </td></tr>
    </table>
  {else}
  <div id="newsHeader" class="single-section-search">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$hiddenArgs}
  </div>
  {/if}
{/block}

{block name="stories"}
  {capture name="splitviewList" assign="splitviewList"}
    {include file="findInclude:modules/news/templates/stories.tpl"}
  {/capture}
  {$splitview = array()}
  {$splitview['id'] = "tabletNews"}
  {$splitview['class'] = "splitview-stories"}
  {$splitview['list'] = $splitviewList}
  {include file="findInclude:common/templates/splitview.tpl" splitview=$splitview}
{/block}
