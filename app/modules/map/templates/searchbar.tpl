<div id="toolbar">
    <div id="searchbar" class="searchbar">
        <form id="search-form"
              method="get"
            {if !$mapURL}
              onsubmit="submitMapSearch(this);return false"
            {/if}
            >
            <fieldset class="inputcombo">
          		<div class="searchwrapper">
                    <input id="search_terms"
                        class="search-form"
                        type="text"
                        value="{$searchTerms|default:''|escape}"
                        name="filter"
                        placeholder="{"MAP_SEARCH_PLACEHOLDER"|getLocalizedString}"
                        onfocus="androidPlaceholderFix(this);showSearchFormButtons();" />
                    {if $group && !$campuses}
                        <input type="hidden" name="group" value="{$group}" />
                    {/if}
                    {if $feedId}
                        <input type="hidden" name="feed" value="{$feedId}" />
                    {/if}
                    {if $mapURL}
                    <input type="hidden" name="listview" value=1 />
                    {/if}
                </div>
                <div id="toolbar-buttons">
                    <div class="toolbar-button">
                        <a href="{$bookmarkLink[0]['url']}" title="{"SEARCHBAR_BUTTON_FAVORITES"|getLocalizedString}">
                            <img src="/modules/map/images/map-button-favorites.png" width="24" height="24" alt="{"SEARCHBAR_BUTTON_FAVORITES"|getLocalizedString}"/>
                        </a>
                    </div>
                    {if $mapURL}
                        <div class="toolbar-button">
                            <a id="mapLink" href="{$mapURL}"><img src="/modules/map/images/map-button-placemark.png" width="24" height="24" /></a>
                        </div>
                    {else}
                        <div class="toolbar-button">
                            <a id="browseLink" href="{$browseURL}" title="{"SEARCHBAR_BUTTON_BROWSE"|getLocalizedString}">
                                <img src="/modules/map/images/map-button-browse.png" width="24" height="24" alt="{"SEARCHBAR_BUTTON_BROWSE"|getLocalizedString}"/>
                            </a>
                        </div>
                    {/if}
                </div>
                <div id="search-options">
                    {if $campuses}
                        {include file="findInclude:modules/map/templates/selectcampus.tpl" campuses=$campuses}
                    {/if}
                    <div id="searchbar-buttons">
                        <div id="searchButton">
                            <input type="button"
                                   onclick="{if !$mapURL}submitMapSearch(this.form){else}this.form.submit(){/if}"
                                   value={"SEARCHBAR_BUTTON_SEARCH"|getLocalizedString} 
                                   ontouchstart="this.className='pressedaction'" ontouchend="this.className=''" />
                        </div>
                        <div id="clearButton">
                            <input type="button" onmousedown="clearSearch(event, this.form)" ontouchstart="this.className='pressedaction'" ontouchend="this.className=''" value={"SEARCHBAR_BUTTON_CLEAR"|getLocalizedString} />
                        </div>
                        <div id="cancelButton">
                            <input type="button" onclick="hideSearchFormButtons()"  ontouchstart="this.className='pressedaction'" ontouchend="this.className=''" value={"SEARCHBAR_BUTTON_CANCEL"|getLocalizedString} />
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div> <!-- id="searchbar" -->
</div> <!-- id="toolbar" -->
