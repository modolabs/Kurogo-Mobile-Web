{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="tabView"}
  <a name="scrolldown"> </a>
  <div class="focal">
    <h2 class="itemtitle">{$name}</h2>
    <p class="address">{$address|replace:' ':'&shy; '}</p>
    {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
    {include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{block name="mapImage"}
<p class="image">
  <a name="map"> </a>
  <img id="staticmapimage" src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
</p>
{/block}

{block name="photoPane"}
  {if $photoURL}
    <p class="image">
      <img src="{$photoURL}" width="{$photoWidth}" alt="Photo" />
    </p>
  {/if}
{/block}
