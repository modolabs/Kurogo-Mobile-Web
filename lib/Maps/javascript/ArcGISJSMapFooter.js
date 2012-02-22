mapLoader = new KGOEsriMapLoader({
    lat: ___Y___,
    lon: ___X___,
    zoom: ___ZOOMLEVEL___,
    mapElement: "___MAPELEMENT___",
    baseURL: "___BASE_URL___",
    layers: ___MORE_LAYER_SCRIPT___,
    wkid:  ___WKID___
});

dojo.require("esri.map");
dojo.require("esri.symbol");
dojo.addOnLoad(mapLoader.loadMap);

function plotFeatures() {
    ___POLYGON_SCRIPT___
    ___PATH_SCRIPT___
    ___MARKER_SCRIPT___

    map.centerAndZoom(mapLoader.center, mapLoader.initZoom);
    mapLoader.resizeMapOnContainerResize();
    mapLoader.showDefaultCallout();
}
