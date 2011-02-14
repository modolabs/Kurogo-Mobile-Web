{include file="findInclude:common/header.tpl" customHeader="" scalable=false}
<div id="mapzoom">
  <a href="#" onclick="zoomin(); scrollTo(0,1); return false;" id="zoomin">
    <img src="/common/images/blank.png" width="40" height="34" alt="Zoom In" />
  </a>
  <a href="#" onclick="zoomout(); scrollTo(0,1); return false;" id="zoomout">
    <img src="/common/images/blank.png" width="40" height="34" alt="Zoom Out" />
  </a>
  <a href="#" onclick="recenter(); scrollTo(0,1); return false;" id="recenter" class=" disabled">
    <img src="/common/images/blank.png" width="40" height="34" alt="Recenter" />
  </a>
  <a href="#" onclick="show('options'); scrollTo(0,1); return false;" id="viewoptions">
    <img src="/common/images/blank.png" width="40" height="34" alt="Options" />
  </a>
  <a href="detail.php" id="smallscreen">
    <img src="/common/images/blank.png" width="40" height="34" alt="Return to Detail" />
  </a>
</div>
<div id="mapscrollers">
  <div id="nw">
    <a href="#" onclick="scroll('nw'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="NW" />
    </a>
  </div>
  <div id="n">
    <a href="#" onclick="scroll('n'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="N" />
    </a>
  </div>
  <div id="ne">
    <a href="#" onclick="scroll('ne'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="NE" />
    </a>
  </div>
  <div id="e">
    <a href="#" onclick="scroll('e'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="E" />
    </a>
  </div>
  <div id="se">
    <a href="#" onclick="scroll('se'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="SE" />
    </a>
  </div>
  <div id="s">
    <a href="#" onclick="scroll('s'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="S" />
    </a>
  </div>
  <div id="sw">
    <a href="#" onclick="scroll('sw'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="SW" />
    </a>
  </div>
  <div id="w">
    <a href="#" onclick="scroll('w'); scrollTo(0,1); return false">
      <img src="/common/images/blank.png" width="50" height="50" alt="W" />
    </a>
  </div>
  <img id="loadingimage" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
</div>
<div id="fullmap">
  <img width="" height="" alt="" id="mapimage" onload="hide('loadingimage')" />
</div>
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
</div>
<script type="text/javascript">
  checkIfMoved();
</script>
</body>
</html>
