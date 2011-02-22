{include file="findInclude:common/header.tpl" customHeader="" scalable=false}

{if $isStatic}
  {include file="findInclude:modules/map/mapscrollers.tpl"}
{/if}

<div id="mapimage">
<img id="staticmapimage" onload="hide('loadingimage')" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
</div>

{include file="findInclude:modules/map/mapcontrols.tpl"}

<div id="options">
  <form action="#" method="get" id="mapform" name="mapform">
    <h2>Labels for Fullscreen Map</h2>
    {foreach $labels as $label}
      <p>
        <label>
          <input class="check" name="{$label['id']}" id="{$label['id']}" type="checkbox" value="{$label['value']}" checked="checked" />
          {$label['title']}
        </label>
      </p>
    {/foreach}
    <div id="formbuttons">
      <button type="button" id="submit" value="Apply" onclick="saveOptions('mapform')">Apply</button>
      <button type="button" id="cancel" value="Cancel" onclick="cancelOptions('mapform')">Cancel</button>
    </div>
  </form>
  <div id="scrim">&nbsp;</div>
</div>

{* footer *}

{foreach $inlineJavascriptFooterBlocks as $script}
  <script type="text/javascript">
    {$script} 
  </script>
{/foreach}

</div>
</body>
</html>
