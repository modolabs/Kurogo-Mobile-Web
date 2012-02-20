{include file="findInclude:common/templates/header.tpl"}

{$tabBodies = array()}

{capture name="searchPane" assign="searchPane"}
    {if $poweredByGoogle}
    <div>
        <img src="/modules/map/images/powered-by-google-on-white.png"/>
    </div>
    {/if}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$places}
{/capture}

{capture name="browsePane" assign="browsePane"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$navItems navlistID="categories"}
{/capture}

{$tabBodies['search'] = $searchPane}
{$tabBodies['browse'] = $browsePane}

<a name="scrolldown"> </a>

{include file="findInclude:modules/map/templates/searchbar.tpl"}

<div id="spacer"></div>

<div id="tabscontainer">
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
</div>

{include file="findInclude:common/templates/footer.tpl"}
