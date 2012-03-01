{include file="findInclude:common/templates/header.tpl"}

{$tabBodies = array()}
{if $topNews}
  {capture name="topnewsTab" assign="topnewsTab"}
    {include file="findInclude:common/templates/search.tpl"}
     
    {include file="findInclude:modules/athletics/templates/stories.tpl" stories=$topNews}  
  {/capture}
  {$tabBodies['topnews'] = $topnewsTab}
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
