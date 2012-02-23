{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="tabs"}
<div id="tabscontainer">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=false}
</div>
{/block}
