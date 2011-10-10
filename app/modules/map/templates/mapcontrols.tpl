<div id="mapzoom">
  <a id="zoomin">
    <img src="/common/images/blank.png" width="40" height="34" alt="Zoom In" />
  </a>
  <a id="zoomout">
    <img src="/common/images/blank.png" width="40" height="34" alt="Zoom Out" />
  </a>
  <a id="recenter">
    <img src="/common/images/blank.png" width="40" height="34" alt="Recenter" />
  </a>
  <a id="locateMe">
    <img src="/common/images/blank.png" width="40" height="34" alt="Locate Me " />
  </a>
  {if $fullscreen}
  <a id="smallscreen" href="{$detailURL}">
    <img src="/common/images/blank.png" width="40" height="34" alt="Return to Detail" />
  </a>
  {else}
  <a id="fullscreen" href="{$fullscreenURL}">
    <img src="/common/images/blank.png" width="40" height="34" alt="Full Screen" />
  </a>
  {/if}
</div>
