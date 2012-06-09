var map;
var mapLoader;
var browseGroups = {};

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
