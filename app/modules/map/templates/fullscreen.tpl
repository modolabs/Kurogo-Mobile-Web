{include file="findInclude:common/templates/header.tpl" customHeader="" scalable=false}

<div id="mapimage" class="fullmap">
{if $isStatic}
  {include file="findInclude:modules/map/templates/mapscrollers.tpl"}
  <img id="staticmapimage" onload="hide('loadingimage'); scrollTo(0, 1);" alt="Map" />
{/if}
</div>
{include file="findInclude:modules/map/templates/mapcontrols.tpl"}

<!-- this section isn't being used currently, don't know if
     we use cases to keep it around for -->
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
