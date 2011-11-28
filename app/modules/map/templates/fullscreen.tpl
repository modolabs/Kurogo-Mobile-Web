{include file="findInclude:common/templates/header.tpl"}

<div id="header">
    <div id="searchbar" class="searchbar">
        <form id="search-form" onsubmit="submitMapSearch(this);return false">
            <fieldset class="inputcombo">
                <div class="searchwrapper">
                    <input id="search_terms"
                        class="search-form"
                        type="text"
                        value=""
                        name="filter"
                        placeholder={"MAP_SEARCH_PLACEHOLDER"|getLocalizedString}
                        onfocus="androidPlaceholderFix(this);showSearchFormButtons();" />
                </div>
                <div id="toolbar-buttons">
                    <div class="toolbar-button">
                        <a href="{$bookmarksURL}"><img src="/modules/map/images/map-button-favorites.png"/></a>
                    </div>
                    <div class="toolbar-button">
                        <a href="{$browseURL}"><img src="/modules/map/images/map-button-browse.png"/></a>
                    </div>
                </div>
                <div id="search-options">
                    {if $campuses}
                        {include file="findInclude:modules/map/templates/selectcampus.tpl" campuses=$campuses}
                    {/if}
                    <input type="button" id="searchButton" onclick="submitMapSearch(this.form)" value="Search" />
                    <input type="button" id="clearButton" onclick="clearSearch(this.form)" value="Clear" />
                    <input type="button" id="cancelButton" onclick="hideSearchFormButtons()" value="Cancel" />
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div id="spacer"></div>
<div id="mapimage"></div>


{include file="findInclude:common/templates/footer.tpl"}
