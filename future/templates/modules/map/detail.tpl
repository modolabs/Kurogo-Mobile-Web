{include file="findInclude:common/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {strip}
  {block name="mapPane"}
    {if $hasMap}
      <div id="mapscrollers">
        <div id="nw">
          <a href="#scrolldown" onclick="scroll('nw'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="NW" />
          </a>
        </div>
        <div id="n">
          <a href="#scrolldown" onclick="scroll('n'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="N" />
          </a>
        </div>
        <div id="ne">
          <a href="#scrolldown" onclick="scroll('ne'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="NE" />
          </a>
        </div>
        <div id="e">
          <a href="#scrolldown" onclick="scroll('e'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="E" />
          </a>
        </div>
        <div id="se">
          <a href="#scrolldown" onclick="scroll('se'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="SE" />
          </a>
        </div>
        <div id="s">
          <a href="#scrolldown" onclick="scroll('s'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="S" />
          </a>
        </div>
        <div id="sw">
          <a href="#scrolldown" onclick="scroll('sw'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="SW" />
          </a>
        </div>
        <div id="w">
          <a href="#scrolldown" onclick="scroll('w'); ">
            <img src="/common/images/blank.png" width="50" height="50" alt="W" />
          </a>
        </div>
        <img id="loadingimage" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
      </div> <!-- id="mapscrollers" -->
      <img id="mapimage" width="{$imageWidth}" height="{$imageHeight}" alt="" onload="hide('loadingimage')"/> 
      <div id="mapzoom">
        <a href="#" onclick="zoomin(); return false;" id="zoomin">
          <img src="/common/images/blank.png" width="40" height="34" alt="Zoom In" />
        </a>
        <a href="#" onclick="zoomout(); return false;" id="zoomout">
          <img src="/common/images/blank.png" width="40" height="34" alt="Zoom Out" />
        </a>
        <a href="#" onclick="recenter(); return false;" id="recenter" class="disabled">
          <img src="/common/images/blank.png" width="40" height="34" alt="Recenter" />
        </a>
        <a href="" id="fullscreen">
          <img src="/common/images/blank.png" width="40" height="34" alt="Full Screen" />
        </a>
      </div>
    {else}
      <img id="mapimage" width="{$imageWidth}" height="{$imageHeight}" alt="" onload="hide('loadingimage')" src="{$imageUrl}"/> 
    {/if}
  {/block}
  {strip}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="photoPane" assign="photoPane"}
  {block name="photoPane"}
    <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
    <img id="photo" src="" width="99.9%" alt="{$name} Photo" onload="hide('loadingimage2')" />
  {/block}
{/capture}
{$tabBodies['photo'] = $photoPane}

{capture name="detailPane" assign="detailPane"}
  {block name="detailPane"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false}
  {/block}
{/capture}
{$tabBodies['detail'] = $detailPane}

{block name="tabView"}
	<a name="scrolldown"></a>		
	<div class="focal shaded">

		<h2>{$name}</h2>
		<p class="address">{$address|replace:' ':'&shy; '}</p>

    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
