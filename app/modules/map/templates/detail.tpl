{include file="findInclude:common/templates/header.tpl" scalable=false}

<a name="scrolldown"> </a>

{$tabBodies = array()}

{if in_array('map', $tabKeys)}
  {capture name="mapPane" assign="mapPane"}
    {block name="mapImage"}
      <a name="map"> </a>
      <!--<div id="mapwrapper" class="image">-->
      <div id="{$mapImageElementId}" class="mapimage image">
        {include file="findInclude:modules/$moduleID/templates/mapscrollers.tpl"}
        <img id="staticmapimage" onload="hide('loadingimage')" src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
      </div>
      <!--<div id="{$mapImageElementId}" class="mapimage" style="display:none"></div>-->
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
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false nested=true}
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
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$nearbyResults boldLabels=true accessKey=false nested=true}
  {/capture}
  {$tabBodies['nearby'] = $nearbyPane}
{/if}

{if in_array('links', $tabKeys)}
  {capture name="linksPane" assign="linksPane"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$externalLinks boldLabels=true listItemTemplateFile="findInclude:modules/map/templates/listItemWithID.tpl" nested=true}
  {/capture}
  {$tabBodies['links'] = $linksPane}
{/if}

{block name="tabView"}
  <h2 class="nonfocal">{$name|escape}</h2>
  <div class="nonfocal">
    <p class="address">{$address|escape}</p>
    <div class="actionbuttons">
      {if !$isStatic}
        <div class="actionbutton">
          <a href="{$mapURL}" ontouchstart="this.className='pressedaction'" ontouchend="this.className=''"><img src="/modules/map/images/map-button-placemark.png" width="20" height="20" alt="" />{"VIEW_ON_MAP"|getLocalizedString}</a>
        </div>
      {/if}
      {include file="findInclude:modules/map/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
 	<div class="clear"></div>
   </div>
  </div>
  
  <div id="tabscontainer">
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
