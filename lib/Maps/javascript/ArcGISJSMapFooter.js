dojo.require("esri.map");
dojo.require("esri.symbol");
dojo.addOnLoad(loadMap);

var map;
var wkid = ___WKID___;
var spatialRef;
var center;
var userLocationMarker;
var userLocationMarkerOnMap = false;
var dragListener = null;
var apiURL = "___API_URL___";

function loadMap() {
    spatialRef = new esri.SpatialReference({wkid : wkid});
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

    ___MORE_LAYER_SCRIPT___

    dojo.connect(map, "onLoad", plotFeatures);

    mapControls.setup({
        zoomin: function() {
            var zoomLevel = map.getLevel();
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;
            map.centerAndZoom(new esri.geometry.Point(x, y, spatialRef), zoomLevel + 1);
        },
        zoomout: function() {
            var zoomLevel = map.getLevel();
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;
            map.centerAndZoom(new esri.geometry.Point(x, y, spatialRef), zoomLevel - 1);
        },
        recenter: function() {
            map.centerAndZoom(center, ___ZOOMLEVEL___);
        },
        locationUpdated: function(location, firstLocation) {
            var params = {
                'lat': location.coords.latitude,
                'lon': location.coords.longitude,
                'from': 4326,
                'to': wkid
            };
            apiRequest(apiURL, params, function(response) {
                var point = new esri.geometry.Point(response["lon"], response["lat"], spatialRef);

                if (typeof userLocationMarker == 'undefined') {
                    // TODO make these more customizable
                    var pointSymbol = new esri.symbol.PictureMarkerSymbol('___FULL_URL_PREFIX___modules/map/images/map-location@2x.png', 16, 16);
                    userLocationMarker = new esri.Graphic(point, pointSymbol);

                } else {
                    userLocationMarker.setGeometry(point);
                }
                
                if (!userLocationMarkerOnMap) {
                    map.graphics.add(userLocationMarker);
                    userLocationMarkerOnMap = true;
                }

                if (firstLocation) {
                    // only recenter on first location so we don't rubber band on scrolling
                    var points = esri.geometry.Multipoint(spatialRef);
                    points.addPoint(center);
                    points.addPoint(point);
                    
                    var extent = points.getExtent();
                    extent = extent.expand(1.5); // add padding around markers
                    map.setExtent(extent);
                }
            }, function(code, message) {});
        },
        locationUpdateStopped: function() {
            if (typeof userLocationMarker != 'undefined') {
                map.graphics.remove(userLocationMarker);
                userLocationMarkerOnMap = false;
            }
        }
    });
}

function plotFeatures() {

    var strokeSymbol = new esri.symbol.SimpleLineSymbol();
    var color = new dojo.Color([255, 0, 0, 0.5]);
    var fillSymbol = new esri.symbol.SimpleFillSymbol(esri.symbol.SimpleFillSymbol.STYLE_SOLID, strokeSymbol, color);
    var polygon;
    ___POLYGON_SCRIPT___
    
    ___PATH_SCRIPT___

    ___MARKER_SCRIPT___

    map.centerAndZoom(center, ___ZOOMLEVEL___);
    resizeMapOnContainerResize();
    
    /*
    // Hack to work around map not actually fully resized at this point
    // TODO: figure out where we should really be calling this
    setTimeout(function() {
        resizeMapOnContainerResize();
    }, 200);
    */
}
