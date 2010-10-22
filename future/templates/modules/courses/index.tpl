{include file="findInclude:common/header.tpl"}

{capture name="searchBar" assign="searchBar"}
  {include file="findInclude:common/search.tpl" placeholder="Search Courses" searchPage='searchCourses' tip="You can search for courses by keyword, course #, or instructor name."}
{/capture}

{block name="topSearch"}{$searchBar}{/block}

{if count($myClasses) || count($myRemovedClasses)}
  <div class="nonfocal">        
    <h3>My Courses</h3>
  </div> 
{/if}
{if count($myClasses)}
  {include file="findInclude:common/navlist.tpl" navlistItems=$myClasses}
{/if}
{if count($myRemovedClasses)}
  <div class="nonfocal legend">
    The following old classes have been removed from your My Courses Settings:
    {foreach $myRemovedClasses as $class}
      {$class}{if !$class@last}, {/if}
    {/foreach}
  </div>
{/if}

<div class="nonfocal">
  <h3>Browse by school:</h3>
</div>
{include file="findInclude:common/navlist.tpl" navlistItems=$schools}

{block name="bottomSearch"}{/block}

{include file="findInclude:common/footer.tpl"}
