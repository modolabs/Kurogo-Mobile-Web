var userLocationMarker;

function resizeMapOnContainerResize() {
    if (map && map.loaded) {
        map.reposition();
        map.resize();
    }
}

function locationUpdated(location) {
    if (map && map.loaded) {
        var point = new esri.geometry.Point(
            position.coords.latitude,
            position.coords.longitude,
            new esri.SpatialReference({wkid : 4326}));

        if (typeof userLocationMarker == 'undefined') {
            // TODO make these more customizable
            var pointSymbol = new esri.symbol.PictureMarkerSymbol('/modules/map/images/map-location@2x.png', 16, 16);
            userLocationMarker = new esri.Graphic(point, pointSymbol);

        } else {
            userLocationMarker.update(position.coords.latitude, position.coords.longitude);
        }
    }
}

function locationUpdateFailed() {
}
