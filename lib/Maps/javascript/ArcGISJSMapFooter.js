/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

dojo.require("esri.map");
dojo.require("esri.symbol");

mapLoader = new KGOEsriMapLoader({
    lat: ___Y___,
    lon: ___X___,
    zoom: ___ZOOMLEVEL___,
    minZoom: "___MINZOOM___",
    maxZoom: "___MAXZOOM___",
    mapElement: "___MAPELEMENT___",
    baseURL: "___BASE_URL___",
    layers: ___MORE_LAYER_SCRIPT___,
    wkid:  ___WKID___
});

dojo.addOnLoad(mapLoader.loadMap);

function plotFeatures() {
    ___PLACEMARK_SCRIPT___

    mapLoader.centerAndZoomWrapper(mapLoader.center, mapLoader.initZoom);
    mapLoader.resizeMapOnContainerResize();
    mapLoader.showDefaultCallout();
}
