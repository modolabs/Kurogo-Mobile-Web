mapLoader = new KGOGoogleMapLoader({
    lat: ___CENTER_LATITUDE___,
    lon: ___CENTER_LONGITUDE___,
    zoom: ___ZOOMLEVEL___,
    ___OPTIONS___
    mapElement: "___MAPELEMENT___"
});

mapLoader.loadMap();

___POLYGON_SCRIPT___
___PATH_SCRIPT___
___MARKER_SCRIPT___

mapLoader.showDefaultCallout();
