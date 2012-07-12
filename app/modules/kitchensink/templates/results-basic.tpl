{extends file="findExtends:modules/kitchensink/templates/results.tpl"}

{block name="date"}
  <strong>{$smarty.block.parent}</strong>
{/block}

{block name='sideNavClass'}nonfocal{/block}

{block name="prevPrefix"}&lt; {/block}
{block name="sidenavSpacer"}&nbsp;&nbsp;&nbsp;{/block}
{block name="nextSuffix"} &gt;{/block}
