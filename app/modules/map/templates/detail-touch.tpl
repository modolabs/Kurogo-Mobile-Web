{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="mapImage"}
<p class="image">
  <a name="map"> </a>
  <img id="staticmapimage" src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
</p>
{/block}

{block name="photoPane"}
  <p class="image">
    <img src="{$photoURL}" width="{$photoWidth}" alt="Photo" />
  </p>
{/block}
