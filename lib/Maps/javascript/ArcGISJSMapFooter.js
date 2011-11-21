mapLoader = kgoEsriMapLoader({
    lat: ___Y___,
    lon: ___X___,
    zoom: ___ZOOMLEVEL___,
    mapElement: "___MAPELEMENT___",
    baseURL: "___BASE_URL___",
    wkid:  ___WKID___
});

dojo.require("esri.map");
dojo.require("esri.symbol");
dojo.addOnLoad(mapLoader.loadMap);

// hide callouts when user clicks map
dojo.connect(map, "onClick", function(e) {
    map.infoWindow.hide();
});

___MORE_LAYER_SCRIPT___

function plotFeatures() {
    ___POLYGON_SCRIPT___
    ___PATH_SCRIPT___
    ___MARKER_SCRIPT___

    map.centerAndZoom(center, mapLoader.initZoom);
    mapLoader.resizeMapOnContainerResize();
    
    /*
    // Hack to work around map not actually fully resized at this point
    // TODO: figure out where we should really be calling this
    setTimeout(function() {
        resizeMapOnContainerResize();
    }, 200);
    */
}
