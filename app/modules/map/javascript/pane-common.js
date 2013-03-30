/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
