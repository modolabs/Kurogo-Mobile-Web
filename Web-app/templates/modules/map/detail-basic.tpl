{extends file="findExtends:modules/$moduleID/detail.tpl"}

{block name="mapPane"}
  <p class="image">
    <a name="map"> </a>
    <img src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
  </p>
  {if $hasMap}
    <p>
      Scroll: 
      <a href="{$scrollNorth}">N</a>&nbsp;|&nbsp;
      <a href="{$scrollSouth}">S</a>&nbsp;|&nbsp;
      <a href="{$scrollEast}">E</a>&nbsp;|&nbsp;
      <a href="{$scrollWest}">W</a><br/>
      Zoom: 
      <a href="{$zoomInUrl}">In</a>&nbsp;|&nbsp;
      <a href="{$zoomOutUrl}">Out</a>
    </p>
  {/if}
{/block}

{block name="photoPane"}
  <p class="image">
    <img src="{$photoUrl}" width="{$photoWidth}" alt="Photo" />
  </p>
{/block}
