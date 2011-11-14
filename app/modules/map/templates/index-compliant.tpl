{include file="findInclude:common/templates/header.tpl"}

<div id="header">
    <div id="searchbar" class="searchbar">
        <form id="search-form" action="search" method="get">
            <input id="search_terms" class="search-field" type="text" value="" name="filter" placeholder="placeholder" />
        </form>
    </div>
    <div class="searchBarButton">
        <a href="{$bookmarksURL}"><img src="/modules/map/images/map-button-favorites.png"/></a>
    </div>
    <div class="searchBarButton">
        <a href="{$browseURL}"><img src="/modules/map/images/map-button-browse.png"/></a>
    </div>
</div>

<div id="mapimage"></div>


{include file="findInclude:common/templates/footer.tpl"}
