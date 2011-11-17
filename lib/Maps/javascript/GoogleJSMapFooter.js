loadMap();

___POLYGON_SCRIPT___
___PATH_SCRIPT___
___MARKER_SCRIPT___

if (placemarks.length == 1) {
    if (markerOnTop) {
        showCalloutForMarker(placemarks[0]);
    } else {
        showCalloutForOverlay(placemarks[0]);
    }
}

