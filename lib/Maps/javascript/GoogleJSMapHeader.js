var map;
var userLocationMarker;
var initLat = ___INITIAL_LATITUDE___;
var initLon = ___INITIAL_LONGITUDE___;
var dragListener = null;
var placemarks = [];
var markerOnTop = true;

function setupLocationUpdates() {
    mapControls.locationUpdated = function(location, firstLocation) {
        var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
        if (typeof userLocationMarker == 'undefined') {
            // TODO make these more customizable
            var icon = new google.maps.MarkerImage('___FULL_URL_PREFIX___modules/map/images/map-location@2x.png',
                null, // original size
                null, // origin (0, 0)
                new google.maps.Point(8, 8), // anchor
                new google.maps.Size(16, 16)); // scaled size

            userLocationMarker = new google.maps.Marker({
                'clickable' : false,
                'map'       : map,
                'position'  : position,
                'flat'      : true,
                'icon'      : icon
            });

        } else {
            if (userLocationMarker.getMap() === null) {
                userLocationMarker.setMap(map);
            }
            userLocationMarker.setPosition(position);
        }

        // only recenter on first location so we don't rubber band on scrolling
        // include current map center on map so zoom/pan is not as confusing
        if (firstLocation) {
            var bounds = new google.maps.LatLngBounds();
            bounds.extend(initCoord);
            bounds.extend(position);
            bounds.extend(map.getCenter());
            map.fitBounds(bounds);
        }
    };
    mapControls.locationUpdateStopped = function() {
        if (typeof userLocationMarker != 'undefined') {
            userLocationMarker.setMap(null); // remove marker
        }
    };
}

function loadMap() {
    var mapImage = document.getElementById("___MAPELEMENT___");

    var initCoord = new google.maps.LatLng(___CENTER_LATITUDE___, ___CENTER_LONGITUDE___);
    var options = {
        zoom: ___ZOOMLEVEL___,
        center: initCoord,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true
    };

    map = new google.maps.Map(mapImage, options);

    setupLocationUpdates();

    var controlDiv = document.createElement('div');
    controlDiv.id = "mapcontrols"

    var zoominButton = document.createElement('a');
    zoominButton.id = "zoomin";
    zoominButton.onclick = function() {
        map.setZoom(map.getZoom() + 1);
    }
    controlDiv.appendChild(zoominButton);

    var zoomoutButton = document.createElement('a');
    zoomoutButton.id = "zoomout";
    zoomoutButton.onclick = function() {
        map.setZoom(map.getZoom() - 1);
    }
    controlDiv.appendChild(zoomoutButton);

    var recenterButton = document.createElement('a');
    recenterButton.id = "recenter";
    recenterButton.onclick = function() {
        map.setCenter(initCoord);
    }
    controlDiv.appendChild(recenterButton);

    if ("geolocation" in navigator && typeof(showUserLocation) != 'undefined') {
        var locateMeButton = document.createElement('a');
        locateMeButton.id = "locateMe";
        locateMeButton.onclick = function() {
            mapControls.toggleLocationUpdates();
        }
        controlDiv.appendChild(locateMeButton);
    }

    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
}

function showCalloutForMarker(marker) {
    marker.infoWindow.open(map, marker);
    var calloutListener = google.maps.event.addDomListener(map, 'click', function() {
        marker.infoWindow.close();
        google.maps.event.removeListener(calloutListener);
    });
}

function showCalloutForOverlay(overlay) {
    overlay.infoWindow.open(map);
    var calloutListener = google.maps.event.addDomListener(map, 'click', function() {
        overlay.infoWindow.close();
        google.maps.event.removeListener(calloutListener);
    });
}

function addMarker(marker, title, subtitle, url) {
    marker.infoWindow = new google.maps.InfoWindow({
        'content' : '<table><tr><td class="calloutMain">' +
                        '<div class="map_name">' + title + '</div>' + 
                        '<div class="smallprint map_address">' + subtitle + '</div>' +
                    '</td>' +
                    '<td class="calloutDisclosure"><a href="' + url + '"><img src="/modules/map/images/info.png" /></a></td></tr></table>',
        'maxWidth' : 200
    });

    google.maps.event.addListener(marker, 'click', function() {
        showCalloutForMarker(marker);
    });

    placemarks.push(marker);
    markerOnTop = true;
}

function addOverlay(overlay, title, subtitle, url, lat, lon) {
    overlay.infoWindow = new google.maps.InfoWindow({
        'content' : '<table><tr><td class="calloutMain">' +
                        '<div class="map_name">' + title + '</div>' + 
                        '<div class="smallprint map_address">' + subtitle + '</div>' +
                    '</td>' +
                    '<td class="calloutDisclosure"><a href="' + url + '"><img src="/modules/map/images/info.png" /></a></td></tr></table>',
        'maxWidth' : 200,
        'position' : new google.maps.LatLng(lat, lon)
    });

    google.maps.event.addListener(overlay, 'click', function() {
        showCalloutForOverlay(overlay);
    });

    placemarks.push(overlay);
    markerOnTop = false;
}

function resizeMapOnContainerResize() {
    if (map) {
        var center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        map.setCenter(center);
    }
}


