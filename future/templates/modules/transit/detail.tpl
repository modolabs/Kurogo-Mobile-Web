{include file="findInclude:common/header.tpl"}

<div id="map">
  <h3>{$stopName}</h3>
  <div id="map">
    <img src="{$mapImageSrc}" height="{$mapImageHeight}" width="{$mapImageWidth}" />
  </div>
</div>

<h3 class="nonfocal">Currently serviced by:</h3>
{include file="findInclude:common/navlist.tpl" navlistItems=$runningRoutes accessKey=false}

<h3 class="nonfocal">Services at other times by:</h3>
{include file="findInclude:common/navlist.tpl" navlistItems=$offlineRoutes accessKey=false}

{include file="findInclude:common/footer.tpl"}
