{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="tabView"}
  <div class="focal">
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}
