/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function doUpdateContainerDimensions() {
    var mapimages = getElementsByClassName("mapimage");
    if (mapimages && mapimages.length) {
        for (var i = 0; i < mapimages.length; i++) {
            var mapimage = mapimages[i];
            var topoffset = findPosY(mapimage);
            var mapTargetHeight = (getWindowHeight() - topoffset) + "px";
            if (mapimage.style.height != mapTargetHeight) {
                mapimage.style.height = mapTargetHeight;
                mapimage.style.minHeight = 0;
                var container = document.getElementById("container");
                if (container) {
                    container.style.minHeight = 0;
                }
            }
        }
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

