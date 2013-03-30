{if count($tabsData)==1 && !$forceTabs}
{$tabData = current($tabsData)}
{include file="findInclude:common/templates/dataObjectDetail.tpl" dataObjectDetails=$tabData}
{else}
{$tabBodies=array()}
{foreach $tabsData as $key=>$tabData}
    {capture name="tab" assign="tabBody"}
    {include file="findInclude:common/templates/dataObjectDetail.tpl" dataObjectDetails=$tabData}
    {/capture}
    {$tabBodies[$key] = $tabBody}
{/foreach}
{block name="tabs"}
<div id="tabscontainer" class="tabscount-{count($tabBodies)}">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
</div>
{/block}
{/if}
