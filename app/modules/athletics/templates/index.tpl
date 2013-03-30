{include file="findInclude:common/templates/header.tpl"}

{$tabBodies = array()}
{if $latestSubTabLinks}
  {capture name="latestTab" assign="latestTab"}
    {include file="findInclude:modules/athletics/templates/index-latest.tpl"}
  {/capture}
  {$tabBodies['topnews'] = $latestTab}
{/if}

{if $menSports}
  {capture name="menTab" assign="menTab"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$menSports}
  {/capture}
  {$tabBodies['men'] = $menTab}
{/if}

{if $womenSports}
  {capture name="womenTab" assign="womenTab"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$womenSports}
  {/capture}
  {$tabBodies['women'] = $womenTab}
{/if}

{if $coedSports}
  {capture name="coedTab" assign="coedTab"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$coedSports}
  {/capture}
  {$tabBodies['coed'] = $coedTab}
{/if}

{if $bookmarksTitle} 
  {capture name="bookmarksTab" assign="bookmarksTab"}
    {include file="findInclude:modules/athletics/templates/bookmarks.tpl"}
  {/capture}
  {$tabBodies['bookmarks'] = $bookmarksTab}
{/if}

{block name="tabs"}
<div id="tabscontainer">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
</div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
