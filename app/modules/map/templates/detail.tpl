{include file="findInclude:common/templates/header.tpl" scalable=false}

<a name="scrolldown"> </a>

{$tabBodies = array()}

{if in_array('map', $tabKeys)}
  {capture name="mapPane" assign="mapPane"}
    {block name="mapImage"}
      <a name="map"> </a>
      <!--<div id="mapwrapper" class="image">-->
      <div id="mapimage" class="image">
        {if $isStatic}
          {include file="findInclude:modules/$moduleID/templates/mapscrollers.tpl"}
          <img id="staticmapimage" onload="hide('loadingimage')" src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
        {/if}
      </div>
      <!--<div id="mapimage" style="display:none"></div>-->
    {/block}
    {include file="findInclude:modules/$moduleID/templates/mapcontrols.tpl"}
  {/capture}
  {$tabBodies['map'] = $mapPane}
{/if}

{if in_array('info', $tabKeys)}
  {capture name="detailPane" assign="detailPane"}
    {block name="photoPane"}
      {if $photoURL}
        <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
        <img id="photo" src="" width="99.9%" alt="{$name} Photo" onload="hide('loadingimage2')" />
      {/if}
    {/block}
    {block name="detailPane"}
      {if $displayDetailsAsList}
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false}
      {else}
        {$details}
      {/if}
    {/block}
  {/capture}
  {$tabBodies['info'] = $detailPane}
{/if}

{if in_array('nearby', $tabKeys)}
  {capture name="nearbyPane" assign="nearbyPane"}
    {if $poweredByGoogle}
      {block name="poweredByGoogle"}
      <div>
        <img src="/modules/map/images/powered-by-google-on-white.png"/>
      </div>
      {/block}
    {/if}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$nearbyResults boldLabels=true accessKey=false}
  {/capture}
  {$tabBodies['nearby'] = $nearbyPane}
{/if}

{if in_array('links', $tabKeys)}
  {capture name="linksPane" assign="linksPane"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$externalLinks boldLabels=true listItemTemplateFile="findInclude:modules/map/templates/listItemWithID.tpl"}
  {/capture}
  {$tabBodies['links'] = $linksPane}
{/if}

{block name="tabView"}
  <div id="tabscontainer">
    <h2>{$name|escape}</h2>
    <p class="address">{$address|escape}</p>
    <div id="buttonWrapper">
      <div id="viewOnMapContainer" class="buttonContainer">
        <a href="{$mapURL}">
          <div id="viewOnMapButton"
               ontouchstart="addClass(this, 'pressed')"
               ontouchend="removeClass(this, 'pressed')"{if $bookmarkStatus == "on"} class="on"{/if}>
              <img src="/modules/map/images/map-button-map.png"/>
              View on map
          </div>
        </a>
      </div>
      <div id="bookmarkButtonContainer" class="buttonContainer">
        <a onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', 'bookmark button pressed', '{$bookmarkItem|escape:'javascript'|escape:'html'}']);{/if}toggleBookmark('{$name}', '{$bookmarkItem|escape:'javascript'|escape:'html'}', {$expireDate}, '{$smarty.const.COOKIE_PATH}')">
          <div id="bookmarkButton"
               ontouchstart="addClass(this, 'pressed')"
               ontouchend="removeClass(this, 'pressed')"{if $bookmarkStatus == "on"} class="on"{/if}>
              <img src="/modules/map/images/map-button-favorites.png"/>
              Bookmark
          </div>
        </a>
      </div>
    </div>
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
