<script type="text/javascript">

var updateMapDimensionsTimeoutIds = [];
function clearUpdateMapDimensionsTimeouts() {
    for(var i = 0; i < updateMapDimensionsTimeoutIds.length; i++) {
        window.clearTimeout(updateMapDimensionsTimeoutIds[i]);
    }
    updateMapDimensionsTimeoutIds = [];
}

function updateContainerDimensions() {
    if (typeof doUpdateContainerDimensions == 'function') {
        clearUpdateMapDimensionsTimeouts();
        var timeoutId = window.setTimeout(doUpdateContainerDimensions, 200);
        updateMapDimensionsTimeoutIds.push(timeoutId);
        timeoutId = window.setTimeout(doUpdateContainerDimensions, 500);
        updateMapDimensionsTimeoutIds.push(timeoutId);
        timeoutId = window.setTimeout(doUpdateContainerDimensions, 1000);
        updateMapDimensionsTimeoutIds.push(timeoutId);
    }
}

function doUpdateContainerDimensions() {
    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}
</script>
<div id="mapimage" class="pane" style="position:absolute;top:40px;bottom:0;left:0;right:0;"></div>
