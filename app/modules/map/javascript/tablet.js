/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function doUpdateContainerDimensions() {
    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

function setTabsHeight() {
    // Set the height of the tabs container to fill the browser window height
    var tc = document.getElementById("tabscontainer");
    if(tc) { tc.style.height=(getWindowHeight()-56) + "px" }
}
