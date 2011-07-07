dojo.require("esri.map");
dojo.addOnLoad(loadMap);

var map;
var spatialRef;
var center;

function loadMap() {
    spatialRef = new esri.SpatialReference({wkid : ___WKID___});
    center = new esri.geometry.Point(___X___, ___Y___, spatialRef);

    var mapImage = document.getElementById("___MAPELEMENT___");
    mapImage.style.display = "inline-block";
    mapImage.style.width = "___IMAGE_WIDTH___";
    mapImage.style.height = "___IMAGE_HEIGHT___";
    
    map = new esri.Map("___MAPELEMENT___", {
        'logo' : false,
        'slider' : false
    });
    var basemapURL = "___BASE_URL___";
    var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);

    map.addLayer(basemap);

    var zoomIn = document.getElementById("zoomin");
    zoomIn.onclick = function() {
        var zoomLevel = map.getLevel();
        var x = (map.extent.xmin + map.extent.xmax) / 2;
        var y = (map.extent.ymin + map.extent.ymax) / 2;
        map.centerAndZoom(new esri.geometry.Point(x, y, spatialRef), zoomLevel + 1);
    };

    var zoomOut = document.getElementById("zoomout");
    zoomOut.onclick = function() {
        var zoomLevel = map.getLevel();
        var x = (map.extent.xmin + map.extent.xmax) / 2;
        var y = (map.extent.ymin + map.extent.ymax) / 2;
        map.centerAndZoom(new esri.geometry.Point(x, y, spatialRef), zoomLevel - 1);
    };
    
    var recenter = document.getElementById("recenter");
    recenter.onclick = function() {
        map.centerAndZoom(center, ___ZOOMLEVEL___);
    };

    ___MORE_LAYER_SCRIPT___

    dojo.connect(map, "onLoad", plotFeatures);
}

function plotFeatures() {

    var strokeSymbol = new esri.symbol.SimpleLineSymbol();
    var color = new dojo.Color([255, 0, 0, 0.5]);
    var fillSymbol = new esri.symbol.SimpleFillSymbol(esri.symbol.SimpleFillSymbol.STYLE_SOLID, strokeSymbol, color);
    var polygon;
    ___POLYGON_SCRIPT___

    var lineSymbol;
    var polyline;
    ___PATH_SCRIPT___

    var pointSymbol;
    var point;
    ___MARKER_SCRIPT___

    map.centerAndZoom(center, ___ZOOMLEVEL___);
    resizeMapOnContainerResize();
}
